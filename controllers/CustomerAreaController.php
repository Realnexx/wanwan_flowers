<?php
require_once __DIR__.'/../models/OnlineOrder.php';
require_once __DIR__.'/../middleware/auth.php';
require_once __DIR__.'/../models/Psgc.php';
require_once __DIR__.'/../models/CustomerContact.php';
require_login();
$customerId=(int)$_SESSION['user']['id'];
if ($flowerRoute==='customer/receipt') {
    if ($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405);exit; }
    $receiptTracking=is_string($_GET['tracking']??null)?$_GET['tracking']:'';
    $receiptOrder=OnlineOrder::detail($receiptTracking,$customerId);
    if (!$receiptOrder) { http_response_code(404);exit('Receipt not found in your account.'); }
}
$customer=Psgc::customerAddress($customerId);
$customerPhone=trim((string)($customer['phone']??''));
if ($customerPhone==='') {
    $phoneOrder=RecipeInventory::rows("SELECT phone FROM online_orders WHERE user_id=? AND fulfillment_type='delivery' AND phone IS NOT NULL AND TRIM(phone)<>'' ORDER BY id DESC LIMIT 1",'i',[$customerId]);
    $customerPhone=$phoneOrder[0]['phone']??'';
}
$customerError='';$customerNotice=get_flash();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!is_string($_POST['token']??null)||!hash_equals($_SESSION['flower_csrf'],$_POST['token'])) { http_response_code(403);exit('Please refresh and try again.'); }
    if ($flowerRoute!=='customer/profile') { http_response_code(405);exit; }
    try {
        if (($_POST['profile_action']??'')==='password') {
            if (AuthSecurity::consume('customer-password',(string)$customerId,5,900)) throw new DomainException('Please wait before trying to change your password again.');
            $current=is_string($_POST['current_password']??null)?$_POST['current_password']:'';
            $next=is_string($_POST['password']??null)?$_POST['password']:'';
            if (!password_verify($current,$customer['password'])) throw new DomainException('Your current password is incorrect.');
            if ($error=AuthSecurity::password_error($next)) throw new DomainException($error);
            if ($next!==($_POST['confirm_password']??'')) throw new DomainException('Your new passwords do not match.');
            User::update_password($customerId,$next);
            $mfa=$_SESSION['auth']['mfa_verified']??false;
            session_regenerate_id(true);AuthSecurity::establish($customerId);$_SESSION['auth']['mfa_verified']=$mfa;
            unset($_SESSION['new_customer_access']);
            set_flash('success','Your password has been changed. Other sessions have been signed out.');
        } else {
            [$contactEmail,$contactPhone]=CustomerContact::validate($_POST,$customer,$customerPhone);
            $values=[];
            foreach (['name'=>150,'address'=>300,'city'=>100,'zip'=>10] as $field=>$limit) {
                $values[$field]=is_string($_POST[$field]??null)?trim($_POST[$field]):'';
                if (mb_strlen($values[$field])>$limit) throw new DomainException('One of your profile details is too long.');
            }
            if ($values['name']==='') throw new DomainException('Please enter your full name.');
            if ($values['zip']!==''&&!preg_match('/^\d{4}$/D',$values['zip'])) throw new DomainException('Use a four-digit postal code.');
            [$psgc,$addressErrors]=Psgc::validate($_POST);
            $profileAddressErrors=$addressErrors;
            if ($addressErrors) throw new DomainException(reset($addressErrors));
            RecipeInventory::query('UPDATE users SET name=?,address=?,city=?,zip=?,psgc_region=?,psgc_province=?,psgc_city=?,psgc_barangay=?,email=?,phone=? WHERE id=?','ssssssssssi',[$values['name'],$values['address'],$psgc['city'],$values['zip'],$psgc['psgc_region'],$psgc['psgc_province'],$psgc['psgc_city'],$psgc['psgc_barangay'],$contactEmail,$contactPhone,$customerId]);
            $_SESSION['user']['name']=$values['name'];
            $_SESSION['user']['email']=$contactEmail;
            unset($_SESSION['flower_checkout_draft']);
            set_flash('success','Your profile has been updated.');
        }
        redirect('?route=customer/profile');
    } catch(DomainException $error) {
        $customerError=$error->getMessage();http_response_code(422);
        if (($_POST['profile_action']??'')!=='password') {
            if (is_string($_POST['email']??null)) $customer['email']=$_POST['email'];
            if (is_string($_POST['phone']??null)) $customerPhone=$_POST['phone'];
            foreach (['name','address','city','zip','psgc_region','psgc_province','psgc_city','psgc_barangay'] as $field) if (is_string($_POST[$field]??null)) $customer[$field]=$_POST[$field];
        }
    }
}
$customerOrders=OnlineOrder::list_for($customerId);
$activeOrders=array_values(array_filter($customerOrders,static function($o){return in_array($o['status'],['pending','preparing','ready'],true);}));
$pastOrders=array_values(array_filter($customerOrders,static function($o){return !in_array($o['status'],['pending','preparing','ready'],true);}));
$customerOrderItems=[];
foreach(RecipeInventory::rows('SELECT i.order_id,i.product_name,i.quantity,p.image_url FROM online_order_items i JOIN online_orders o ON o.id=i.order_id LEFT JOIN products p ON p.id=i.product_id WHERE o.user_id=? ORDER BY o.id DESC,i.id ASC LIMIT 1000','i',[$customerId]) as $item) $customerOrderItems[$item['order_id']][]=$item;
