<?php
require_once __DIR__.'/Setting.php';
require_once __DIR__.'/OnlineOrder.php';

class PayMongoApiException extends DomainException {
    public $httpStatus;
    public function __construct($message,$status=0){parent::__construct($message);$this->httpStatus=$status;}
}

class PayMongo {
    // Used only by isolated tests; production always calls the fixed HTTPS API.
    public static $transport=null;
    private static function cents($value){$scaled=InventoryQuantity::parse((string)$value,true);if($scaled%100!==0)throw new DomainException('Payment amount must have at most two decimal places.');return intdiv($scaled,100);}
    public static function ready(){return (int)RecipeInventory::rows("SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('paymongo_config','paymongo_methods','paymongo_payments')")[0]['n']===3;}
    public static function encryptionKey(){
        $path=__DIR__.'/../config/paymongo.key';
        if(!is_file($path)){
            $handle=@fopen($path,'x');
            if($handle){fwrite($handle,base64_encode(random_bytes(32)));fclose($handle);@chmod($path,0600);}
        }
        $key=is_file($path)?base64_decode(file_get_contents($path),true):false;
        if($key===false || strlen($key)!==32)throw new DomainException('Payment credential storage is unavailable. Contact your administrator.');
        return $key;
    }
    public static function encrypt($plain){if($plain==='')return ''; $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($plain,'aes-256-gcm',self::encryptionKey(),OPENSSL_RAW_DATA,$iv,$tag);if($cipher===false)throw new RuntimeException('Credential encryption failed.');return base64_encode($iv.$tag.$cipher);}
    private static function decrypt($cipher){if($cipher==='')return '';$raw=base64_decode($cipher,true);if($raw===false||strlen($raw)<28)throw new DomainException('Stored payment credentials could not be read.');$plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',self::encryptionKey(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16));if($plain===false)throw new DomainException('Stored payment credentials could not be read.');return $plain;}
    private static function config(){if(!self::ready())return null;return RecipeInventory::rows('SELECT * FROM paymongo_config WHERE id=1')[0] ?? null;}
    public static function summary(){
        $c=self::config();return ['ready'=>self::ready(),'enabled'=>!empty($c['enabled']),'mode'=>$c['mode']??'test','has_secret'=>!empty($c['secret_cipher']),'has_public'=>!empty($c['public_key']),'has_webhook'=>!empty($c['webhook_cipher']),'site_url'=>$c['site_url']??'','synced_at'=>$c['synced_at']??null,'methods'=>$c?RecipeInventory::rows('SELECT code,label,available FROM paymongo_methods WHERE mode=? ORDER BY label','s',[$c['mode']]):[]];
    }
    public static function available(){if(!self::ready())return false;$c=self::config();return $c && $c['enabled'] && (int)RecipeInventory::rows('SELECT COUNT(*) n FROM paymongo_methods WHERE mode=? AND available=1','s',[$c['mode']])[0]['n']>0;}
    public static function request($method,$path,$secret,$payload=null,$key=null){
        if(self::$transport!==null && PHP_SAPI==='cli')return call_user_func(self::$transport,$method,$path,$secret,$payload,$key);
        if(!extension_loaded('curl'))throw new DomainException('The server needs PHP cURL for payments.');
        $ch=curl_init('https://api.paymongo.com'.$path);$headers=['Accept: application/json','Content-Type: application/json'];if($key)$headers[]='Idempotency-Key: '.$key;
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_USERPWD=>$secret.':',CURLOPT_HTTPAUTH=>CURLAUTH_BASIC,CURLOPT_HTTPHEADER=>$headers,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>25,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
        if($payload!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload,JSON_THROW_ON_ERROR));
        $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if($body===false)throw new PayMongoApiException('PayMongo could not be reached. Please retry; an existing checkout will be reused.');
        if($status<200||$status>=300)throw new PayMongoApiException(in_array($status,[401,403])?'PayMongo rejected the credentials or account access. Check your keys and account activation.':'PayMongo could not complete the request (HTTP '.$status.'). Please check your account or retry.',$status);
        $json=json_decode($body,true);if(!is_array($json))throw new PayMongoApiException('PayMongo returned an invalid response. Please retry.');return $json;
    }
    public static function parseMethods($json){
        $list=$json['data']['attributes']['payment_methods'] ?? $json['data']['payment_methods'] ?? $json['data']['attributes']['payment_method_types'] ?? $json['payment_methods'] ?? $json['data'] ?? $json;
        if(!is_array($list)||($list!==[] && array_keys($list)!==range(0,count($list)-1)))throw new DomainException('PayMongo did not return a recognizable payment-method list. Existing settings were kept.');
        $codes=[];foreach($list as $code){if(!is_string($code)||!preg_match('/^[a-z][a-z0-9_]{0,79}$/D',$code))throw new DomainException('PayMongo returned unsupported capability data. Existing settings were kept.');$codes[$code]=$code;}return array_values($codes);
    }
    public static function label($code){return ['card'=>'Credit / debit card','gcash'=>'GCash','paymaya'=>'Maya','maya'=>'Maya','qrph'=>'QR Ph','grab_pay'=>'GrabPay','shopeepay'=>'ShopeePay','dob'=>'Online banking','billease'=>'BillEase'][$code] ?? ucwords(str_replace('_',' ',$code));}
    public static function applicationUrl(){
        global $appConfig;
        $configured=getenv('APP_URL') ?: ($appConfig['public_url']??'');
        if($configured!=='')$site=rtrim($configured,'/').'/';
        elseif(!empty($_SERVER['HTTP_HOST'])){
            $host=$_SERVER['HTTP_HOST'];
            if(!is_string($host)||!preg_match('/^(?:[a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]+(?::[0-9]{1,5})?$/D',$host))throw new DomainException('The application host is invalid.');
            $https=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') || (int)($_SERVER['SERVER_PORT']??0)===443;
            $site=($https?'https://':'http://').$host.APP_BASE_PATH;
        } else $site=self::config()['site_url']??'';
        $parts=parse_url($site);
        if(!filter_var($site,FILTER_VALIDATE_URL)||!in_array($parts['scheme']??'',['http','https'],true)||!empty($parts['user'])||!empty($parts['pass'])||isset($parts['query'])||isset($parts['fragment']))throw new DomainException('The application address could not be detected. Ask your administrator to configure APP_URL.');
        return $site;
    }
    public static function save($input,$actor){
        if((int)($_SESSION['user']['id']??0)!==(int)$actor || !User::has_permission($actor,'manage_settings'))throw new DomainException('You cannot change payment settings.');
        if(!self::ready())throw new DomainException('Install the PayMongo database migration first.');
        $old=self::config();$mode=$input['mode']??'test';$enable=($input['enabled']??'0')==='1';
        if(!in_array($mode,['test','live'],true))throw new DomainException('Choose test or live mode.');
        // A dedicated disable action never depends on PayMongo being reachable.
        if(($input['action']??'')==='disable'){RecipeInventory::query('UPDATE paymongo_config SET enabled=0,updated_by=? WHERE id=1','i',[$actor]);return;}
        $secret=trim($input['secret_key']??'');$public=trim($input['public_key']??'');$webhook=trim($input['webhook_secret']??'');
        $secret=$secret!==''?$secret:($old?self::decrypt($old['secret_cipher']):'');$public=$public!==''?$public:($old['public_key']??'');
        $sameAccount=$old && $old['mode']===$mode && hash_equals(self::decrypt($old['secret_cipher']),$secret);
        $webhook=$webhook!==''?$webhook:($sameAccount?self::decrypt($old['webhook_cipher']):'');
        if(!preg_match('/^sk_'.$mode.'_[A-Za-z0-9]{8,240}$/D',$secret)||!preg_match('/^pk_'.$mode.'_[A-Za-z0-9]{8,240}$/D',$public))throw new DomainException('Enter valid public and secret keys for the same selected mode.');
        $site=self::applicationUrl();
        if($old && ($old['mode']!==$mode || !hash_equals(self::decrypt($old['secret_cipher']),$secret) || $old['public_key']!==$public) && RecipeInventory::rows("SELECT id FROM paymongo_payments WHERE state IN ('creating','pending','review') LIMIT 1"))throw new DomainException('Reconcile or expire outstanding checkouts before changing payment keys or mode.');
        $methods=self::parseMethods(self::request('GET','/v1/merchants/capabilities/payment_methods',$secret));
        if($enable && !$methods)throw new DomainException('No active payment methods were found. Activate methods in PayMongo before enabling payments.');
        RecipeInventory::atomic(function()use($actor,$mode,$enable,$secret,$public,$webhook,$site,$methods){
            $current=RecipeInventory::rows('SELECT * FROM paymongo_config WHERE id=1 FOR UPDATE')[0]??null;
            if($current && ($current['mode']!==$mode || !hash_equals(self::decrypt($current['secret_cipher']),$secret) || $current['public_key']!==$public) && RecipeInventory::rows("SELECT id FROM paymongo_payments WHERE state IN ('creating','pending','review') LIMIT 1"))throw new DomainException('Reconcile or expire outstanding checkouts before changing payment keys or mode.');
            RecipeInventory::query('INSERT INTO paymongo_config(id,enabled,mode,public_key,secret_cipher,webhook_cipher,site_url,synced_at,updated_by) VALUES(1,?,?,?,?,?,?,NOW(),?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),mode=VALUES(mode),public_key=VALUES(public_key),secret_cipher=VALUES(secret_cipher),webhook_cipher=VALUES(webhook_cipher),site_url=VALUES(site_url),synced_at=NOW(),updated_by=VALUES(updated_by)','isssssi',[(int)$enable,$mode,$public,self::encrypt($secret),self::encrypt($webhook),$site,$actor]);
            RecipeInventory::query('UPDATE paymongo_methods SET available=0 WHERE mode=?','s',[$mode]);
            foreach($methods as $code)RecipeInventory::query('INSERT INTO paymongo_methods(mode,code,label,available) VALUES(?,?,?,1) ON DUPLICATE KEY UPDATE label=VALUES(label),available=1,synced_at=NOW()','sss',[$mode,$code,self::label($code)]);
        });
        return null;
    }
    private static function connectWebhook($secret,$site,$mode){
        $url=$site.'ajax/paymongo_webhook.php';$hooks=self::request('GET','/v1/webhooks',$secret)['data']??[];$hook=null;
        foreach($hooks as $candidate){$a=$candidate['attributes']??[];if(($a['url']??'')===$url && ($a['livemode']??null)===($mode==='live') && in_array('checkout_session.payment.paid',$a['events']??[],true)){$hook=$candidate;break;}}
        if(!$hook)$hook=self::request('POST','/v1/webhooks',$secret,['data'=>['attributes'=>['url'=>$url,'events'=>['checkout_session.payment.paid']]]],hash('sha256','hook:'.$mode.':'.$url))['data']??[];
        if(($hook['attributes']['status']??'')==='disabled'){
            if(!preg_match('/^hook_[A-Za-z0-9]+$/D',$hook['id']??''))throw new DomainException('PayMongo returned an invalid webhook identifier.');
            self::request('POST','/v1/webhooks/'.$hook['id'].'/enable',$secret);
        }
        $signing=$hook['attributes']['secret_key']??'';
        if(!is_string($signing)||!preg_match('/^[A-Za-z0-9_\-]{8,255}$/D',$signing))throw new DomainException('The webhook signing secret could not be obtained. Enter it from the PayMongo dashboard and retry.');
        return $signing;
    }
    public static function latest($order){if(!self::ready())return null;return RecipeInventory::rows('SELECT id,session_id,state,mode,method,checkout_url FROM paymongo_payments WHERE order_id=? ORDER BY id DESC LIMIT 1','i',[$order])[0]??null;}
    public static function checkout($tracking,$user){
        if((int)($_SESSION['user']['id']??0)!==(int)$user)throw new DomainException('Please sign in to pay for this order.');
        if(!self::available())throw new DomainException('Online payments are currently unavailable.');
        $attempt=RecipeInventory::atomic(function()use($tracking,$user){
            $c=RecipeInventory::rows('SELECT * FROM paymongo_config WHERE id=1 FOR UPDATE')[0]??null;
            if(!$c || !$c['enabled'])throw new DomainException('Online payments are currently unavailable.');
            global $conn;$order=RecipeInventory::rows('SELECT * FROM online_orders WHERE tracking_number=? AND user_id=? FOR UPDATE','si',[$tracking,$user])[0]??null;
            if(!$order || !in_array($order['status'],['pending','preparing','ready'],true)||!in_array($order['payment_status'],['unpaid','processing'],true))throw new DomainException('This order cannot accept an online payment.');
            $prior=RecipeInventory::rows('SELECT * FROM paymongo_payments WHERE active_order_id=? FOR UPDATE','i',[$order['id']]);if($prior)return $prior[0];
            $amount=self::cents($order['total']);if($amount<100)throw new DomainException('This order must be paid at the counter.');
            $return=$c['site_url'].'?route='.(($order['fulfillment_type']??'pickup')==='delivery'?'flowers/order':'order_tracking').'&tracking='.rawurlencode($tracking);
            $methods=array_column(RecipeInventory::rows('SELECT code FROM paymongo_methods WHERE mode=? AND available=1 ORDER BY code','s',[$c['mode']]),'code');
            if (($order['payment_choice'] ?? '') === 'qrph') {
                if (!in_array('qrph', $methods, true)) throw new DomainException('QR Ph is not available for this account.');
                $methods = ['qrph'];
            }
            $payload=['data'=>['attributes'=>['line_items'=>[['name'=>'Order '.$tracking,'amount'=>$amount,'currency'=>'PHP','quantity'=>1]],'payment_method_types'=>$methods,'reference_number'=>$tracking,'metadata'=>['order_id'=>(string)$order['id']],'billing'=>['name'=>$order['customer_name'],'email'=>$order['email'],'phone'=>$order['phone']],'send_email_receipt'=>true,'pass_on_fees'=>false,'success_url'=>$return.'&payment_return=1','cancel_url'=>$return.'&payment_cancelled=1']]];
            RecipeInventory::query('INSERT INTO paymongo_payments(order_id,branch_id,active_order_id,request_key,amount_cents,mode,secret_cipher,webhook_cipher,request_payload) VALUES(?,?,?,?,?,?,?,?,?)','iiisissss',[$order['id'],$order['branch_id'],$order['id'],bin2hex(random_bytes(32)),$amount,$c['mode'],$c['secret_cipher'],$c['webhook_cipher'],json_encode($payload,JSON_THROW_ON_ERROR)]);
            $id=$conn->insert_id;RecipeInventory::query("UPDATE online_orders SET payment_status='processing' WHERE id=?",'i',[$order['id']]);return RecipeInventory::rows('SELECT * FROM paymongo_payments WHERE id=?','i',[$id])[0];
        });
        if($attempt['session_id']){self::verify($attempt['session_id']);$latest=self::latest($attempt['order_id']);if($latest['state']!=='pending')throw new DomainException('The payment status changed. Refresh your order before continuing.');return $latest['checkout_url'];}
        if(RecipeInventory::rows('SELECT id FROM paymongo_payments WHERE id=? AND created_at<DATE_SUB(NOW(),INTERVAL 23 HOUR)','i',[$attempt['id']]))throw new DomainException('This checkout needs administrator reconciliation before it can be retried.');
        try{$result=self::request('POST','/v2/checkout_sessions',self::decrypt($attempt['secret_cipher']),json_decode($attempt['request_payload'],true),$attempt['request_key']);}
        catch(PayMongoApiException $error){if(in_array($error->httpStatus,[400,401,403,422],true))self::release($attempt['id'],'failed');throw $error;}
        $session=$result['data']??[];$url=$session['attributes']['checkout_url']??'';
        if(!preg_match('/^cs_[A-Za-z0-9]+$/D',$session['id']??'')||!self::safeCheckoutUrl($url))throw new DomainException('PayMongo returned an unexpected checkout. Please contact the store before retrying.');
        RecipeInventory::query("UPDATE paymongo_payments SET session_id=?,checkout_url=?,state='pending' WHERE id=? AND state='creating'",'ssi',[$session['id'],$url,$attempt['id']]);return $url;
    }
    public static function safeCheckoutUrl($url){$p=parse_url($url);return is_array($p)&&($p['scheme']??'')==='https'&&($p['host']??'')==='checkout.paymongo.com'&&!isset($p['user'])&&!isset($p['pass']);}
    private static function release($id,$state){RecipeInventory::atomic(function()use($id,$state){$p=RecipeInventory::rows('SELECT order_id FROM paymongo_payments WHERE id=?','i',[$id])[0];RecipeInventory::rows('SELECT id FROM online_orders WHERE id=? FOR UPDATE','i',[$p['order_id']]);$p=RecipeInventory::rows('SELECT * FROM paymongo_payments WHERE id=? FOR UPDATE','i',[$id])[0];if(!in_array($p['state'],['creating','pending'],true))return;RecipeInventory::query('UPDATE paymongo_payments SET state=?,active_order_id=NULL WHERE id=?','si',[$state,$id]);RecipeInventory::query("UPDATE online_orders SET payment_status='unpaid' WHERE id=? AND payment_status='processing'",'i',[$p['order_id']]);});}
    public static function verify($session){
        $p=RecipeInventory::rows('SELECT * FROM paymongo_payments WHERE session_id=?','s',[$session])[0]??null;if(!$p)throw new DomainException('Payment session not found.');if($p['state']==='paid')return;
        $resource=self::request('GET','/v1/checkout_sessions/'.rawurlencode($session),self::decrypt($p['secret_cipher']))['data']??[];
        if(($resource['id']??'')!==$session)throw new DomainException('Payment verification failed.');
        $a=$resource['attributes']??[];$paid=null;foreach($a['payments']??[] as $payment)if(($payment['attributes']['status']??'')==='paid'){$paid=$payment;break;}
        if(!$paid){if(($a['status']??'')==='expired')self::release($p['id'],'expired');return;}
        $v=$paid['attributes'];$mode=$a['livemode']??$v['livemode']??null;
        if(($a['reference_number']??'')!==json_decode($p['request_payload'],true)['data']['attributes']['reference_number'] || (string)($a['metadata']['order_id']??'')!==(string)$p['order_id'] || ($v['currency']??'')!=='PHP' || (int)($v['amount']??0)!==(int)$p['amount_cents'] || !is_bool($mode) || $mode!==($p['mode']==='live') || !preg_match('/^pay_[A-Za-z0-9]+$/D',$paid['id']??''))throw new DomainException('Payment details did not match the order. Contact the store.');
        $applied=RecipeInventory::atomic(function()use($p,$paid,$v){
            $order=RecipeInventory::rows('SELECT * FROM online_orders WHERE id=? FOR UPDATE','i',[$p['order_id']])[0];$current=RecipeInventory::rows('SELECT * FROM paymongo_payments WHERE id=? FOR UPDATE','i',[$p['id']])[0];if($current['state']==='paid')return true;
            if($order['payment_status']==='paid' || !in_array($order['status'],['pending','preparing','ready'],true) || self::cents($order['total'])!==(int)$p['amount_cents']){RecipeInventory::query("UPDATE paymongo_payments SET state='review',payment_id=? WHERE id=?",'si',[$paid['id'],$p['id']]);return false;}
            $method=$v['source']['type']??'online';$sale=OnlineOrder::record_gateway_payment($order,'PayMongo '.self::label($method));
            RecipeInventory::query("UPDATE paymongo_payments SET state='paid',payment_id=?,method=?,sale_id=? WHERE id=?",'ssii',[$paid['id'],$method,$sale,$p['id']]);return true;
        });
        if(!$applied)throw new DomainException('This paid transaction needs administrator reconciliation.');
    }
    public static function expire($id,$actor){
        if((int)($_SESSION['user']['id']??0)!==(int)$actor)throw new DomainException('Your session does not match this payment action.');
        $p=RecipeInventory::rows('SELECT p.*,o.user_id FROM paymongo_payments p JOIN online_orders o ON o.id=p.order_id WHERE p.id=?','i',[(int)$id])[0]??null;
        if(!$p || ((int)$p['user_id']!==(int)$actor && !User::has_permission($actor,'manage_settings')))throw new DomainException('You cannot cancel this payment session.');
        if(!$p['session_id'] || $p['state']!=='pending')throw new DomainException('This payment session cannot be expired automatically.');
        try {self::request('POST','/v1/checkout_sessions/'.rawurlencode($p['session_id']).'/expire',self::decrypt($p['secret_cipher']));}
        catch(PayMongoApiException $error){if($error->httpStatus!==400)throw $error;}
        self::verify($p['session_id']);
        $state=RecipeInventory::rows('SELECT state FROM paymongo_payments WHERE id=?','i',[$p['id']])[0]['state'];
        if($state!=='expired')throw new DomainException('The checkout was not expired. Its payment may be paid or still processing; check the order status before accepting another payment.');
    }
    public static function assertLocalRefundAllowed($sale){
        if(self::ready() && RecipeInventory::rows("SELECT id FROM paymongo_payments WHERE sale_id=? AND state='paid' LIMIT 1",'i',[$sale]))throw new DomainException('This is a PayMongo payment. A local refund cannot return gateway funds. Ask an administrator to refund and reconcile it through PayMongo.');
    }
    public static function adminPayments(){if(!self::ready())return [];return RecipeInventory::rows('SELECT p.id,p.state,p.mode,p.amount_cents,p.created_at,p.session_id,o.tracking_number,b.name branch_name FROM paymongo_payments p JOIN online_orders o ON o.id=p.order_id LEFT JOIN branches b ON b.id=p.branch_id ORDER BY p.id DESC LIMIT 30');}
    public static function reconcile($id,$actor){if((int)($_SESSION['user']['id']??0)!==(int)$actor || !User::has_permission($actor,'manage_settings'))throw new DomainException('You cannot reconcile online payments.');$p=RecipeInventory::rows('SELECT session_id FROM paymongo_payments WHERE id=?','i',[(int)$id])[0]??null;if(!$p || !$p['session_id'])throw new DomainException('No checkout session was recorded. Contact PayMongo to reconcile this attempt before another payment is accepted.');self::verify($p['session_id']);}
    public static function webhook($body,$signature){
        $json=json_decode($body,true);if(!is_array($json))throw new DomainException('Invalid notification.');
        $event=$json['data']['attributes']??$json['data']??[];$type=$event['type']??'';
        if($type!=='checkout_session.payment.paid')return;
        $session=$event['data']['id']??'';if(!is_string($session))throw new DomainException('Invalid session.');
        $p=RecipeInventory::rows('SELECT * FROM paymongo_payments WHERE session_id=?','s',[$session])[0]??null;if(!$p)throw new DomainException('Unknown session.');
        $secret=self::decrypt($p['webhook_cipher']);$parts=[];foreach(explode(',',$signature) as $part){$pair=explode('=',trim($part),2);if(count($pair)===2)$parts[$pair[0]]=$pair[1];}
        $timestamp=$parts['t']??'';$value=$parts[$p['mode']==='live'?'li':'te']??'';
        if($secret===''||!ctype_digit($timestamp)||abs(time()-(int)$timestamp)>300||!hash_equals(hash_hmac('sha256',$timestamp.'.'.$body,$secret),$value))throw new DomainException('Invalid notification signature.');
        self::verify($session);
    }
}
