<?php
// CLI-only integration verification. Every database change is rolled back.
if (PHP_SAPI!=='cli') { http_response_code(403);exit; }
ob_start();
session_save_path(__DIR__.'/../scratch');
require __DIR__.'/../config/db.php';
require __DIR__.'/../models/FlowerCheckout.php';
require_once __DIR__.'/../models/FlowerCatalog.php';
require_once __DIR__.'/../models/CustomerContact.php';
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
    $contactPassword='Contact-Test!'.bin2hex(random_bytes(8));
    $contactId=User::create('Contact Test','','','','contact_'.bin2hex(random_bytes(5)),'contact_'.bin2hex(random_bytes(5)).'@example.invalid',$contactPassword,[]);
    $contactUser=User::find_by_id($contactId);
    try { CustomerContact::validate(['email'=>'changed@example.invalid','phone'=>'09171234567'],$contactUser,'');throw new RuntimeException('Contact change accepted without password'); } catch (DomainException $expected) {}
    try { CustomerContact::validate(['email'=>$contactUser['email'],'phone'=>'bad','contact_password'=>$contactPassword],$contactUser,'');throw new RuntimeException('Invalid phone accepted'); } catch (DomainException $expected) {}
    try { CustomerContact::validate(['email'=>User::find_by_id($user)['email'],'phone'=>'','contact_password'=>$contactPassword],$contactUser,'');throw new RuntimeException('Duplicate email accepted'); } catch (DomainException $expected) {}
    $newContactEmail='changed_'.bin2hex(random_bytes(5)).'@example.invalid';
    [$email,$phone]=CustomerContact::validate(['email'=>$newContactEmail,'phone'=>'0917 123 4567','contact_password'=>$contactPassword],$contactUser,'');
    RecipeInventory::query('UPDATE users SET email=?,phone=? WHERE id=?','ssi',[$email,$phone,$contactId]);
    flowerAssert((int)User::find_by_username_or_email($newContactEmail)['id']===(int)$contactId&&User::find_by_id($contactId)['phone']==='09171234567','Contact details did not persist or new login email failed');
    echo "PASS: contact password confirmation, phone validation, duplicate email protection, saved phone and new sign-in email\n";
    $product=RecipeInventory::rows("SELECT id FROM products WHERE show_on_website=1 AND sku LIKE 'WAN-FLOWER-%' ORDER BY id LIMIT 1")[0];
    $branch=(int)RecipeInventory::rows("SELECT id FROM branches WHERE status='Active' ORDER BY id LIMIT 1")[0]['id'];
    $bag=[(int)$product['id']=>2];
    $originalBranch=RecipeInventory::product((int)$product['id'])['branch_id'];
    RecipeInventory::query('UPDATE products SET branch_id=? WHERE id=?','ii',[$branch,(int)$product['id']]);
    $dispatch=FlowerCheckout::dispatchBranches($bag);
    flowerAssert(count($dispatch)===1&&(int)$dispatch[0]['id']===$branch,'Dispatch did not match the product branch');
    flowerAssert(FlowerCheckout::dispatchBranches([])===[],'Empty bag received a dispatch branch');
    RecipeInventory::query('UPDATE products SET branch_id=? WHERE id=?','ii',[$originalBranch,(int)$product['id']]);
    echo "PASS: automatic dispatch uses a branch holding the whole bag\n";
    $catalog=FlowerCatalog::products($conn);
    unset($_SESSION['flower_bag_database']);$_SESSION['flower_bag']=[];
    FlowerBag::load($catalog);
    FlowerBag::change((int)$product['id'],'add',1,$catalog);
    flowerAssert((int)RecipeInventory::rows('SELECT SUM(qty) qty FROM cart WHERE user_id=? AND product_id=?','ii',[$user,(int)$product['id']])[0]['qty']===1,'Add to bag did not insert a cart row');
    FlowerBag::change((int)$product['id'],'add',1,$catalog);
    $_SESSION['flower_bag']=[];
    flowerAssert(FlowerBag::load($catalog)===$bag,'Bag was not restored from database');
    FlowerBag::change((int)$product['id'],'set_quantity',1,$catalog);
    flowerAssert(FlowerBag::read($catalog)===[(int)$product['id']=>1],'Database quantity was not updated');
    FlowerBag::change((int)$product['id'],'remove',1,$catalog);
    flowerAssert(FlowerBag::read($catalog)===[],'Remove left a database cart item');
    FlowerBag::change((int)$product['id'],'add',2,$catalog);
    unset($_SESSION['user']);
    flowerAssert(FlowerBag::read($catalog)===[],'Guest could read customer bag');
    FlowerBag::change((int)$product['id'],'add',1,$catalog);
    flowerAssert((int)RecipeInventory::rows('SELECT SUM(qty) qty FROM cart WHERE session_id=? AND user_id IS NULL AND product_id=?','si',[session_id(),(int)$product['id']])[0]['qty']===1,'Guest bag was not inserted');
    Cart::migrate(session_id(),$user);
    $_SESSION['user']=['id'=>$user];
    flowerAssert(FlowerBag::read($catalog)===[(int)$product['id']=>3],'Guest bag did not merge on sign in');
    FlowerBag::change((int)$product['id'],'set_quantity',2,$catalog);
    echo "PASS: database cart insert, persistence, quantity, removal, owner isolation, guest storage and sign-in merge\n";
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
    [$estimated,$estimateErrors]=FlowerCheckout::validate(array_merge($data,['delivery_date'=>'2099-01-01','delivery_date_end'=>'2099-01-02']));
    $window=FlowerCheckout::deliveryEstimate();
    flowerAssert($estimated['delivery_date']===$window['delivery_date']&&$estimated['delivery_date_end']===$window['delivery_date_end']&&!isset($estimateErrors['delivery_date']),'Customer input replaced the automatic delivery estimate');
    $quote=FlowerCheckout::quote($bag,$branch,$user);$key=bin2hex(random_bytes(32));$draft=['data'=>$data,'quote'=>$quote];
    $_SESSION['flower_bag']=$bag;$_SESSION['flower_checkout_draft']=$draft+['key'=>$key];
    flowerAssert(strpos(flowerRender('flowers/checkout'),'id="flower-checkout-form"')!==false,'Customer details page failed to render');
    flowerAssert(strpos(flowerRender('flowers/place-order'),'id="flower-place-form"')!==false,'Payment page failed to render');
    $order=FlowerCheckout::place($draft,$bag,$user,'cod',$key);
    flowerAssert(FlowerBag::read($catalog)===[],'Purchased products remained in database cart');
    flowerAssert($order['payment_status']==='unpaid'&&$order['payment_choice']==='cod','COD status incorrect');
    $storedDelivery=json_decode($order['delivery_details'],true);
    flowerAssert($storedDelivery['delivery_date']===$window['delivery_date']&&$storedDelivery['delivery_date_end']===$window['delivery_date_end'],'Delivery estimate was not saved on the order');
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
    $mockCheckoutId='cs_Rollback'.bin2hex(random_bytes(8));
    PayMongo::$transport=static function($method,$path,$secret,$body,$key)use(&$payload,&$paid,$qrOrder,$mockCheckoutId){
        if ($method==='POST'&&$path==='/v2/checkout_sessions') {
            $payload=$body;
            flowerAssert($body['data']['attributes']['payment_method_types']===['qrph'],'QR-only selection lost');
            flowerAssert(strpos($body['data']['attributes']['success_url'],'flowers/order')!==false,'Wrong return route');
            return ['data'=>['id'=>$mockCheckoutId,'attributes'=>['checkout_url'=>'https://checkout.paymongo.com/RollbackTest']]];
        }
        if ($method==='GET'&&$path==='/v1/checkout_sessions/'.$mockCheckoutId) {
            $attributes=$payload['data']['attributes'];$attributes['livemode']=false;$attributes['payments']=$paid?[
                ['id'=>'pay_'.str_replace('_','',$mockCheckoutId),'attributes'=>['status'=>'paid','amount'=>(int)round($qrOrder['total']*100),'currency'=>'PHP','livemode'=>false,'source'=>['type'=>'qrph']]]
            ]:[];
            return ['data'=>['id'=>$mockCheckoutId,'attributes'=>$attributes]];
        }
        throw new RuntimeException('Unexpected mocked gateway request');
    };
    PayMongo::checkout($qrOrder['tracking_number'],$user);
    PayMongo::verify($mockCheckoutId);
    flowerAssert(RecipeInventory::rows('SELECT payment_status FROM online_orders WHERE id=?','i',[$qrOrder['id']])[0]['payment_status']==='processing','Unpaid return marked paid');
    $body=json_encode(['data'=>['attributes'=>['type'=>'checkout_session.payment.paid','data'=>['id'=>$mockCheckoutId]]]]);
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
    $savedAddress=Psgc::customerAddress($guestId);
    foreach(['address','region','province','city','barangay','postal_code'] as $field) flowerAssert(!empty($savedAddress[$field]),'Missing fetched address field: '.$field);
    RecipeInventory::query("UPDATE users SET psgc_region='',psgc_province='',psgc_city='',psgc_barangay='',address='',city='',zip='' WHERE id=?",'i',[$guestId]);
    $orderAddress=Psgc::customerAddress($guestId);
    flowerAssert($orderAddress['address']===$guestData['address']&&$orderAddress['city']==='Loay'&&$orderAddress['province']==='Bohol','Latest order address fallback failed');
    flowerAssert(!User::access_context($guestId)['staff'],'Guest account received staff access');
    try { FlowerCheckout::placeGuest($guestDraft,$bag,'cod',bin2hex(random_bytes(32)));throw new RuntimeException('Existing account was claimed by guest'); } catch(DomainException $expected) {}
    flowerAssert(OnlineOrder::detail($order['tracking_number'],$guestId)===null,'Customer can access another account order');
    $_SESSION['user']=['id'=>$guestId,'email'=>$guestData['email'],'name'=>'Guest Test'];AuthSecurity::establish($guestId);
    $_SESSION['new_customer_access']=['user_id'=>$guestId,'email'=>$guestData['email'],'username'=>$guest['user']['username'],'password'=>$guest['password'],'expires'=>time()+900];
    flowerAssert(strpos(flowerRender('flowers/order',$guest['order']['tracking_number']),'new-account-card')!==false,'New account credentials not shown');
    flowerAssert(strpos(flowerRender('flowers/order',$guest['order']['tracking_number']),'new-account-card')===false,'Credentials shown more than once');
    $receiptHtml=flowerRender('customer/receipt',$guest['order']['tracking_number']);
    flowerAssert(strpos($receiptHtml,'id="receipt-title"')!==false&&strpos($receiptHtml,$guest['order']['tracking_number'])!==false&&strpos($receiptHtml,'Cash on delivery')!==false&&strpos($receiptHtml,'Quantity x 2')!==false,'Customer receipt missing order details');
    flowerAssert(strpos(flowerRender('customer/orders'),'route=customer/receipt')!==false,'Order details do not link to receipt');
    foreach(['customer/home','customer/orders','customer/history','customer/profile'] as $route) flowerAssert(strpos(flowerRender($route),'customer-section')!==false,'Customer page failed: '.$route);
    set_flash('success','Your profile has been updated.');
    $noticeHtml=flowerRender('customer/profile');
    flowerAssert(strpos($noticeHtml,'global-toast-messages')!==false&&strpos($noticeHtml,'Your profile has been updated.')!==false,'Save notification missing from global toast');
    flowerAssert(strpos(flowerRender('customer/profile'),'Your profile has been updated.')===false,'Save toast repeated after refresh');
    if (in_array('--preview',$GLOBALS['argv']??[],true)) {
        FlowerBag::change((int)$product['id'],'set_quantity',2,$catalog);
        file_put_contents(__DIR__.'/../scratch/flowers-order-preview.html',flowerRender('flowers/order',$guest['order']['tracking_number']));
        file_put_contents(__DIR__.'/../scratch/customer-receipt-preview.html',flowerRender('customer/receipt',$guest['order']['tracking_number']));
        foreach(['customer/home','customer/orders','customer/profile','flowers/bag','products','flowers/checkout'] as $route) {
            file_put_contents(__DIR__.'/../scratch/'.str_replace('/','-',$route).'-preview.html',str_replace('/pos/','/wanwan/',flowerRender($route)));
        }
    }
    echo "PASS: guest account and order, password hash, Customer-only role, existing-email protection, private orders, one-time credentials, all customer pages\n";
    flowerAssert(OnlineOrder::board_targets($guest['order'],$guestId)===[],'Customer received board actions');
    $adminRole=RecipeInventory::rows("SELECT id FROM roles WHERE LOWER(REPLACE(name,' ',''))='superadmin' LIMIT 1")[0]['id'];
    RecipeInventory::query('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)','ii',[$user,$adminRole]);
    $_SESSION['user']=['id'=>$user];AuthSecurity::establish($user);
    flowerAssert(OnlineOrder::board_targets($order,$user)===['preparing'],'Wrong board next status');
    OnlineOrder::update_status($order['id'],'preparing',$user,'Cash','pending');
    try { OnlineOrder::update_status($order['id'],'ready',$user,'Cash','pending');throw new RuntimeException('Stale drag update accepted'); } catch(DomainException $expected) {}
    OnlineOrder::update_status($order['id'],'ready',$user,'Cash','preparing');
    try { OnlineOrder::update_status($order['id'],'completed',$user,'Cash','ready');throw new RuntimeException('Unpaid order completed'); } catch(DomainException $expected) {}
    OnlineOrder::update_status($qrOrder['id'],'preparing',$user,'Cash','pending');
    OnlineOrder::update_status($qrOrder['id'],'ready',$user,'Cash','preparing');
    $completed=OnlineOrder::update_status($qrOrder['id'],'completed',$user,'Cash','ready');
    flowerAssert($completed['status']==='completed','Paid order did not complete');
    $selected=OnlineOrder::detail($order['tracking_number'],$user,true);
    ob_start();require __DIR__.'/../views/partials/online_order_detail.php';$detailHtml=ob_get_clean();
    flowerAssert(strpos($detailHtml,'order-action-form')!==false,'Modal action form missing');
    if (in_array('--preview',$GLOBALS['argv']??[],true)) {
        $preview='<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/wanwan/assets/admin/css/styles.min.css"><link rel="stylesheet" href="/wanwan/assets/vendor/fontawesome/css/all.min.css"><link rel="stylesheet" href="/wanwan/assets/css/admin-online-orders.css"></head><body><dialog id="order-detail-modal" aria-labelledby="order-modal-title"><div class="order-modal-toolbar"><span>ORDER DETAILS</span><button type="button" onclick="this.closest(\'dialog\').close()" aria-label="Close order details">×</button></div><div id="order-modal-content">'.$detailHtml.'</div></dialog><script>document.querySelector("dialog").showModal();</script></body></html>';
        file_put_contents(__DIR__.'/../scratch/admin-order-modal-preview.html',$preview);
    }
    $orders=OnlineOrder::list_for($user,true);
    ob_start();require __DIR__.'/../views/partials/online_order_board.php';$boardHtml=ob_get_clean();
    flowerAssert(strpos($boardHtml,'data-order-status="preparing"')!==false,'Board lanes missing');
    echo "PASS: board permissions, allowed transitions, stale update rejection, unpaid completion protection, modal and board rendering\n";
    throw new FlowerTestRollback();
});
} catch(FlowerTestRollback $expected) { echo "All test database changes rolled back. No external requests sent.\n"; }
finally { PayMongo::$transport=null; echo ob_get_clean(); }


