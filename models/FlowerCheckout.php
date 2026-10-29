<?php
require_once __DIR__.'/OnlineOrder.php';
require_once __DIR__.'/PayMongo.php';
require_once __DIR__.'/PublicCatalog.php';
require_once __DIR__.'/Psgc.php';
class FlowerCheckout
{
    public static function validate(array $input): array
    {
        $errors=[];$data=[];
        foreach (['name'=>150,'email'=>254,'phone'=>40,'address'=>300,'city'=>100,'postal_code'=>10,'delivery_date'=>10,'gift_note'=>280,'branch_id'=>10,'psgc_region'=>10,'psgc_province'=>10,'psgc_city'=>10,'psgc_barangay'=>10] as $key=>$limit) {
            $data[$key]=is_string($input[$key]??null)?trim($input[$key]):'';
            if (mb_strlen($data[$key])>$limit) $errors[$key]='This value is too long.';
        }
        foreach (['name'=>'your full name','email'=>'your email address','phone'=>'your mobile number','address'=>'the delivery address','postal_code'=>'the postal code','delivery_date'=>'a delivery date','branch_id'=>'a dispatch branch'] as $key=>$label) if ($data[$key]==='') $errors[$key]='Please enter '.$label.'.';
        if (!filter_var($data['email'],FILTER_VALIDATE_EMAIL)) $errors['email']='Enter a valid email address.';
        if (!preg_match('/^(?:\+63|0)9\d{9}$/D',preg_replace('/[\s()-]/','',$data['phone']))) $errors['phone']='Use a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.';
        if (!preg_match('/^\d{4}$/D',$data['postal_code'])) $errors['postal_code']='Enter a four-digit Philippine postal code.';
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$data['delivery_date']);
        if (!$date || $date->format('Y-m-d')!==$data['delivery_date'] || $data['delivery_date']<date('Y-m-d') || $data['delivery_date']>date('Y-m-d',strtotime('+90 days'))) $errors['delivery_date']='Choose a date from today to 90 days ahead.';
        if (!ctype_digit($data['branch_id']) || !RecipeInventory::rows("SELECT id FROM branches WHERE id=? AND status='Active'",'i',[(int)$data['branch_id']])) $errors['branch_id']='Choose an available dispatch branch.';
        [$address,$addressErrors]=Psgc::validate($data);
        $data=array_merge($data,$address);$errors=array_merge($errors,$addressErrors);
        return [$data,$errors];
    }

    public static function quote(array $bag,int $branch,int $user): array
    {
        if (!$bag || count($bag)>100) throw new DomainException('Your bag is empty or too large. Review your selection.');
        $items=[];$quotes=[];
        foreach ($bag as $id=>$qty) {
            if (!is_numeric($id) || !is_int($qty) || $qty<1 || $qty>20) throw new DomainException('Choose quantities between 1 and 20.');
            $quote=RecipeInventory::quoteItem((int)$id,(string)$qty,[],$branch);
            $product=$quote['product'];
            if (!PublicCatalog::visible($product)) throw new DomainException('A bouquet is no longer available. Please review your bag.');
            $quotes[]=$quote;$items[]=['id'=>(int)$id,'qty'=>$qty,'price'=>$quote['price_cents']/100,'name'=>$product['name']];
        }
        $financial=Promotion::financial($quotes,$branch,'online',$user,'','0',true);
        $delivery=Setting::get('flower_delivery_fee','0.00');
        if (!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D',(string)$delivery)) throw new DomainException('Delivery pricing is not configured correctly. Please contact the shop.');
        $deliveryCents=(int)round((float)$delivery*100);
        $financial['delivery_cents']=$deliveryCents;$financial['total_cents']+=$deliveryCents;
        return ['items'=>$items,'financial'=>$financial,'fingerprint'=>Cart::fingerprint($items,$branch),'bag_hash'=>hash('sha256',json_encode($bag))];
    }

    public static function qrAvailable(): bool
    {
        $s=PayMongo::summary();
        return $s['enabled'] && $s['has_secret'] && $s['has_webhook'] && strpos($s['site_url'],'https://')===0 && in_array('qrph',array_column(array_filter($s['methods'],static function($m){return !empty($m['available']);}),'code'),true);
    }

    public static function fee(int $amount): int
    {
        // Published standard QR Ph rate: 1.34%, plus 12% VAT on the fee.
        return (int)round($amount*0.0134*1.12);
    }

    public static function placeGuest(array $draft,array $bag,string $method,string $key): array
    {
        return RecipeInventory::atomic(function()use($draft,$bag,$method,$key){
            [$data,$errors]=self::validate($draft['data']??[]);
            if ($errors) throw new DomainException('Please review your customer details.');
            if (User::find_by_email(strtolower($data['email']))) throw new DomainException('Please sign in with this email before placing your order.');
            $role=RecipeInventory::rows("SELECT id FROM roles WHERE LOWER(name)='customer' LIMIT 1");
            if (!$role) throw new DomainException('Customer registration is temporarily unavailable.');
            $password='Bloom!'.bin2hex(random_bytes(12)).'aA1';
            $username='flower_'.bin2hex(random_bytes(6));
            $user=User::create($data['name'],'','','',$username,strtolower($data['email']),$password,[(int)$role[0]['id']]);
            RecipeInventory::query('UPDATE users SET address=?,city=?,zip=?,psgc_region=?,psgc_province=?,psgc_city=?,psgc_barangay=? WHERE id=?','sssssssi',[$data['address'],$data['city'],$data['postal_code'],$data['psgc_region'],$data['psgc_province'],$data['psgc_city'],$data['psgc_barangay'],$user]);
            // Account and order are committed together; a failed order creates neither.
            $order=self::place($draft,$bag,(int)$user,$method,$key);
            return ['order'=>$order,'user'=>User::find_by_id($user),'password'=>$password];
        });
    }

    public static function place(array $draft,array $bag,int $user,string $method,string $key): array
    {
        if (!in_array($method,['cod','qrph'],true)) throw new DomainException('Choose COD or QR Ph.');
        if ($method==='qrph' && !self::qrAvailable()) throw new DomainException('QR Ph is currently unavailable. Choose COD or try later.');
        [$data,$errors]=self::validate($draft['data']??[]);
        if ($errors) throw new DomainException('Please review your delivery details.');
        $old=RecipeInventory::rows('SELECT * FROM online_orders WHERE checkout_key=? AND user_id=?','si',[$key,$user]);
        if ($old) {
            if ($old[0]['payment_choice']!==$method) throw new DomainException('This order was already placed with a different payment method.');
            return $old[0];
        }
        if (!hash_equals($draft['quote']['bag_hash']??'',hash('sha256',json_encode($bag)))) throw new DomainException('Your bag changed. Return to checkout to review it.');
        return RecipeInventory::atomic(function()use($data,$draft,$bag,$user,$method,$key){
            Cart::lock_owner(session_id(),$user);
            $existing=RecipeInventory::rows('SELECT * FROM online_orders WHERE checkout_key=? AND user_id=? FOR UPDATE','si',[$key,$user]);
            if ($existing) {
                if ($existing[0]['payment_choice']!==$method) throw new DomainException('This checkout was already placed with another payment method.');
                return $existing[0];
            }
            $q=self::quote($bag,(int)$data['branch_id'],$user);$f=$q['financial'];
            $order=OnlineOrder::place($user,session_id(),(int)$data['branch_id'],$data['name'],$data['email'],$data['phone'],$key,number_format($draft['quote']['financial']['total_cents']/100,2,'.',''),$draft['quote']['fingerprint'],'',$q['items'],$f['delivery_cents']);
            $details=json_encode($data,JSON_THROW_ON_ERROR);$delivery=number_format($f['delivery_cents']/100,2,'.','');$fee=number_format(($method==='qrph'?self::fee($f['total_cents']):0)/100,2,'.','');
            RecipeInventory::query("UPDATE online_orders SET fulfillment_type='delivery',payment_choice=?,delivery_details=?,delivery_fee=?,gateway_fee_estimate=? WHERE id=?",'ssssi',[$method,$details,$delivery,$fee,$order['id']]);
            return RecipeInventory::rows('SELECT * FROM online_orders WHERE id=?','i',[$order['id']])[0];
        });
    }
}
