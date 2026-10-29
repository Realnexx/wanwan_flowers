<?php
// CLI-only integration verification. Every database change is rolled back.
if (PHP_SAPI!=='cli') { http_response_code(403);exit; }
ob_start();
session_save_path(__DIR__.'/../scratch');
require __DIR__.'/../config/db.php';
require __DIR__.'/../models/FlowerCheckout.php';
class FlowerTestRollback extends RuntimeException {}
function flowerAssert($condition,$message) { if (!$condition) throw new RuntimeException($message); }
function flowerRender($flowerRoute,$tracking='') {
    global $conn;
    $_SERVER['REQUEST_METHOD']='GET';$_SERVER['SCRIPT_NAME']='/wanwan/index.php';
    $_GET=['route'=>$flowerRoute];if ($tracking!=='') $_GET['tracking']=$tracking;
    ob_start();require __DIR__.'/../views/website/flowers.php';return ob_get_clean();
}
try {
RecipeInventory::atomic(function()use($conn){
    RecipeInventory::query("UPDATE settings SET setting_value='1' WHERE setting_key='online_ordering_enabled'");
    $user=User::create('Checkout Test','','','','checkout_test_'.bin2hex(random_bytes(5)),'checkout_'.bin2hex(random_bytes(5)).'@example.invalid','Temporary-'.bin2hex(random_bytes(16)),[]);
    $_SESSION['user']=['id'=>$user];
    AuthSecurity::establish($user);
    $product=RecipeInventory::rows("SELECT id FROM products WHERE show_on_website=1 AND sku LIKE 'WAN-FLOWER-%' ORDER BY id LIMIT 1")[0];
    $branch=(int)RecipeInventory::rows("SELECT id FROM branches WHERE status='Active' ORDER BY id LIMIT 1")[0]['id'];
    $bag=[(int)$product['id']=>2];
    $data=['name'=>'Checkout Test','email'=>'checkout@example.invalid','phone'=>'09171234567','address'=>'Test Street, Test Barangay','city'=>'Test City','postal_code'=>'6300','delivery_date'=>date('Y-m-d',strtotime('+1 day')),'gift_note'=>'Test note','branch_id'=>(string)$branch];
    $town=array_values(array_filter(Psgc::data()['cities'],static function($c){return $c['name']==='Loay';}))[0];
    $barangay=Psgc::options('barangays',$town['code'])[0];
    $data=array_merge($data,['psgc_region'=>$town['region'],'psgc_province'=>$town['province'],'psgc_city'=>$town['code'],'psgc_barangay'=>$barangay['code']]);
    [$valid,$errors]=FlowerCheckout::validate($data);flowerAssert(!$errors,'Valid delivery details rejected');
    flowerAssert($valid['city']==='Loay','City name was not resolved from PSGC');
    [$invalid,$errors]=FlowerCheckout::validate(array_merge($data,['psgc_region'=>'0100000000']));flowerAssert(!empty($errors),'Mismatched PSGC hierarchy accepted');
    $ncr=array_values(array_filter(Psgc::data()['cities'],static function($c){return $c['region']==='1300000000'&&$c['province']==='';}))[0];
    [$ncrNames,$ncrErrors]=Psgc::validate(['psgc_region'=>$ncr['region'],'psgc_province'=>'none','psgc_city'=>$ncr['code'],'psgc_barangay'=>Psgc::options('barangays',$ncr['code'])[0]['code']]);
    flowerAssert(!$ncrErrors,'NCR province-free address rejected');
    echo "PASS: PSGC parent validation, canonical city names, NCR without province\n";
    [$invalid,$errors]=FlowerCheckout::validate(array_merge($data,['phone'=>'bad','email'=>'bad','postal_code'=>'bad']));flowerAssert(count($errors)>=3,'Invalid fields accepted');
    $quote=FlowerCheckout::quote($bag,$branch,$user);$key=bin2hex(random_bytes(32));$draft=['data'=>$data,'quote'=>$quote];
    $_SESSION['flower_bag']=$bag;$_SESSION['flower_checkout_draft']=$draft+['key'=>$key];
    flowerAssert(strpos(flowerRender('flowers/checkout'),'id="flower-checkout-form"')!==false,'Customer details page failed to render');
    flowerAssert(strpos(flowerRender('flowers/place-order'),'id="flower-place-form"')!==false,'Payment page failed to render');
    $order=FlowerCheckout::place($draft,$bag,$user,'cod',$key);
    flowerAssert($order['payment_status']==='unpaid'&&$order['payment_choice']==='cod','COD status incorrect');
    flowerAssert(FlowerCheckout::place($draft,$bag,$user,'cod',$key)['id']===$order['id'],'Duplicate COD order');
    flowerAssert(count(RecipeInventory::rows('SELECT * FROM online_order_items WHERE order_id=?','i',[$order['id']]))===1,'Order items missing');
    flowerAssert(strpos(flowerRender('flowers/order',$order['tracking_number']),'Your order is')!==false,'Order confirmation failed to render');
    try { FlowerCheckout::place($draft,[(int)$product['id']=>3],$user,'cod',bin2hex(random_bytes(32)));throw new RuntimeException('Changed bag accepted'); } catch (DomainException $expected) {}
    echo "PASS: customer validation, COD persistence, item snapshot, idempotency, changed bag rejection\n";
    // Gateway adapter only; never transmit a request or money in this test.
    $secret=PayMongo::encrypt('sk_test_rollback_only');$webhookSecret='rollback_webhook_secret';$webhook=PayMongo::encrypt($webhookSecret);
    RecipeInventory::query("INSERT INTO paymongo_config(id,enabled,mode,public_key,secret_cipher,webhook_cipher,site_url) VALUES(1,1,'test','pk_test_rollback',?,?,'https://example.invalid/wanwan/') ON DUPLICATE KEY UPDATE enabled=1,mode='test',secret_cipher=VALUES(secret_cipher),webhook_cipher=VALUES(webhook_cipher),site_url=VALUES(site_url)",'ss',[$secret,$webhook]);
    RecipeInventory::query("INSERT INTO paymongo_methods(mode,code,label,available) VALUES('test','qrph','QR Ph',1) ON DUPLICATE KEY UPDATE available=1");
    flowerAssert(FlowerCheckout::qrAvailable(),'QR availability incorrect');
    $qrOrder=FlowerCheckout::place($draft,$bag,$user,'qrph',bin2hex(random_bytes(32)));
    $payload=null;$paid=false;
    PayMongo::$transport=static function($method,$path,$secret,$body,$key)use(&$payload,&$paid,$qrOrder){
        if ($method==='POST'&&$path==='/v2/checkout_sessions') {
            $payload=$body;
            flowerAssert($body['data']['attributes']['payment_method_types']===['qrph'],'QR-only selection lost');
            flowerAssert(strpos($body['data']['attributes']['success_url'],'flowers/order')!==false,'Wrong return route');
            return ['data'=>['id'=>'cs_RollbackTest','attributes'=>['checkout_url'=>'https://checkout.paymongo.com/RollbackTest']]];
        }
        if ($method==='GET'&&$path==='/v1/checkout_sessions/cs_RollbackTest') {
            $attributes=$payload['data']['attributes'];$attributes['livemode']=false;$attributes['payments']=$paid?[
                ['id'=>'pay_RollbackTest','attributes'=>['status'=>'paid','amount'=>(int)round($qrOrder['total']*100),'currency'=>'PHP','livemode'=>false,'source'=>['type'=>'qrph']]]
            ]:[];
            return ['data'=>['id'=>'cs_RollbackTest','attributes'=>$attributes]];
        }
        throw new RuntimeException('Unexpected mocked gateway request');
    };
    PayMongo::checkout($qrOrder['tracking_number'],$user);
    PayMongo::verify('cs_RollbackTest');
    flowerAssert(RecipeInventory::rows('SELECT payment_status FROM online_orders WHERE id=?','i',[$qrOrder['id']])[0]['payment_status']==='processing','Unpaid return marked paid');
    $body=json_encode(['data'=>['attributes'=>['type'=>'checkout_session.payment.paid','data'=>['id'=>'cs_RollbackTest']]]]);
    try { PayMongo::webhook($body,'t='.time().',te=invalid');throw new RuntimeException('Forged webhook accepted'); } catch(DomainException $expected) {}
    $paid=true;$time=time();$signature='t='.$time.',te='.hash_hmac('sha256',$time.'.'.$body,$webhookSecret);
    PayMongo::webhook($body,$signature);PayMongo::webhook($body,$signature);
    $saved=RecipeInventory::rows('SELECT * FROM online_orders WHERE id=?','i',[$qrOrder['id']])[0];
    flowerAssert($saved['payment_status']==='paid'&&$saved['sale_id'],'Verified payment not recorded');
    flowerAssert((int)RecipeInventory::rows('SELECT COUNT(*) n FROM sales WHERE request_key=?','s',['online-'.$qrOrder['checkout_key']])[0]['n']===1,'Duplicate payment sale');
    echo "PASS: QR-only checkout, unpaid redirect safety, forged webhook rejection, verified payment, webhook replay protection\n";
    $guestData=array_merge($data,['email'=>'guest_'.bin2hex(random_bytes(6)).'@example.invalid']);
    $guestDraft=['data'=>$guestData,'quote'=>FlowerCheckout::quote($bag,$branch,0)];
    $guest=FlowerCheckout::placeGuest($guestDraft,$bag,'cod',bin2hex(random_bytes(32)));
    $guestId=(int)$guest['user']['id'];
    flowerAssert(password_verify($guest['password'],$guest['user']['password']),'Generated password cannot sign in');
    flowerAssert((int)$guest['order']['user_id']===$guestId,'Guest order not owned by new account');
    flowerAssert(!User::access_context($guestId)['staff'],'Guest account received staff access');
    try { FlowerCheckout::placeGuest($guestDraft,$bag,'cod',bin2hex(random_bytes(32)));throw new RuntimeException('Existing account was claimed by guest'); } catch(DomainException $expected) {}
    flowerAssert(OnlineOrder::detail($order['tracking_number'],$guestId)===null,'Customer can access another account order');
    $_SESSION['user']=['id'=>$guestId,'email'=>$guestData['email'],'name'=>'Guest Test'];AuthSecurity::establish($guestId);
    $_SESSION['new_customer_access']=['user_id'=>$guestId,'email'=>$guestData['email'],'username'=>$guest['user']['username'],'password'=>$guest['password'],'expires'=>time()+900];
    flowerAssert(strpos(flowerRender('flowers/order',$guest['order']['tracking_number']),'new-account-card')!==false,'New account credentials not shown');
    flowerAssert(strpos(flowerRender('flowers/order',$guest['order']['tracking_number']),'new-account-card')===false,'Credentials shown more than once');
    foreach(['customer/home','customer/orders','customer/history','customer/profile'] as $route) flowerAssert(strpos(flowerRender($route),'customer-section')!==false,'Customer page failed: '.$route);
    if (in_array('--preview',$GLOBALS['argv']??[],true)) {
        foreach(['customer/home','customer/orders','customer/profile'] as $route) {
            file_put_contents(__DIR__.'/../scratch/'.str_replace('/','-',$route).'-preview.html',str_replace('/pos/','/wanwan/',flowerRender($route)));
        }
    }
    echo "PASS: guest account and order, password hash, Customer-only role, existing-email protection, private orders, one-time credentials, all customer pages\n";
    throw new FlowerTestRollback();
});
} catch(FlowerTestRollback $expected) { echo "All test database changes rolled back. No external requests sent.\n"; }
finally { PayMongo::$transport=null; echo ob_get_clean(); }
