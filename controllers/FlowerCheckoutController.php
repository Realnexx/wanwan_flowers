<?php
// Included by the storefront before any output. All POST routes use its CSRF token.
require_once __DIR__.'/../models/FlowerCheckout.php';
require_once __DIR__.'/../middleware/auth.php';
if (empty($_SESSION['user']['id']) && $flowerRoute==='flowers/order') {
    $_SESSION['flower_checkout_return']=true;
    set_flash('info','Please sign in or create an account to continue checkout.');
    redirect('?route=login');
}
$checkoutUser=(int)($_SESSION['user']['id']??0);
$checkoutErrors=[];$checkoutMessage='';$checkoutOrder=null;$checkoutQuote=null;
$checkoutBranches=RecipeInventory::rows("SELECT id,name FROM branches WHERE status='Active' ORDER BY id");
$checkoutData=$_SESSION['flower_checkout_draft']['data']??['name'=>$_SESSION['user']['name']??'','email'=>$_SESSION['user']['email']??'','gift_note'=>$_SESSION['flower_gift_note']??''];
if ($checkoutUser && empty($_SESSION['flower_checkout_draft'])) {
    $profile=Psgc::customerAddress($checkoutUser);
    foreach(['address','city','psgc_region','psgc_province','psgc_city','psgc_barangay'] as $field) $checkoutData[$field]=$profile[$field]??'';
    $checkoutData['postal_code']=$profile['zip']??'';
    if (!empty($profile['phone'])) $checkoutData['phone']=$profile['phone'];
}
$qrAvailable=FlowerCheckout::qrAvailable();
if ($_SERVER['REQUEST_METHOD']==='POST' && (!is_string($_POST['token']??null)||!hash_equals($_SESSION['flower_csrf'],$_POST['token']))) {
    http_response_code(403);exit('Your checkout session expired. Please refresh and try again.');
}
if ($flowerRoute==='flowers/order') {
    $tracking=is_string($_GET['tracking']??null)?$_GET['tracking']:'';
    $checkoutOrder=OnlineOrder::detail($tracking,$checkoutUser);
    if (!$checkoutOrder || ($checkoutOrder['fulfillment_type']??'')!=='delivery') { http_response_code(404);exit('Order not found in your account.'); }
    $newCustomerAccess=null;
    if (!empty($_SESSION['new_customer_access'])) {
        $access=$_SESSION['new_customer_access'];unset($_SESSION['new_customer_access']);
        if ($access['user_id']===$checkoutUser && $access['expires']>time()) $newCustomerAccess=$access;
    }
    if (($_SERVER['REQUEST_METHOD']==='POST' || isset($_GET['payment_return'])) && $checkoutOrder['payment_choice']==='qrph') {
        try {
            if (AuthSecurity::consume('flower-payment-check',(string)$checkoutUser,12,60)) throw new DomainException('Please wait before checking payment again.');
            if (($_POST['checkout_action']??'')==='retry_payment') {
                $destination=PayMongo::checkout($tracking,$checkoutUser);header('Location: '.$destination,true,303);exit;
            }
            $payment=PayMongo::latest($checkoutOrder['id']);
            if (!empty($payment['session_id'])) PayMongo::verify($payment['session_id']);
            $checkoutOrder=OnlineOrder::detail($tracking,$checkoutUser);
        } catch (DomainException $error) { $checkoutMessage=$error->getMessage(); }
        catch (Throwable $error) { error_log('Flower payment verification failed: '.get_class($error));$checkoutMessage='Payment status could not be checked. Please try again.'; }
    }
} else {
    if (empty($_SESSION['flower_bag'])) {
        if ($flowerRoute==='flowers/place-order' && !empty($_SESSION['flower_checkout_draft']['key'])) {
            $previous=RecipeInventory::rows('SELECT tracking_number FROM online_orders WHERE checkout_key=? AND user_id=?','si',[$_SESSION['flower_checkout_draft']['key'],$checkoutUser]);
            if ($previous) redirect('?route=flowers/order&tracking='.rawurlencode($previous[0]['tracking_number']));
        }
        redirect('?route=flowers/bag');
    }
    $checkoutBranches=FlowerCheckout::dispatchBranches($_SESSION['flower_bag']);
    $dispatchId=(int)($checkoutBranches[0]['id']??0);
    $checkoutData['branch_id']=$dispatchId?(string)$dispatchId:'';
    if ($flowerRoute==='flowers/checkout') $checkoutData=array_merge($checkoutData,FlowerCheckout::deliveryEstimate());
    if (!$dispatchId) $checkoutMessage='No single branch currently has enough stock for your bag. Please adjust the quantities or contact the shop.';
    if ($flowerRoute==='flowers/checkout' && $_SERVER['REQUEST_METHOD']==='POST') {
        $checkoutInput=$_POST;
        $checkoutInput['branch_id']=$dispatchId?(string)$dispatchId:'';
        [$checkoutData,$checkoutErrors]=FlowerCheckout::validate($checkoutInput);
        if (!$dispatchId) $checkoutErrors['branch_id']='No branch can fulfill all items in your bag right now.';
        if (!$checkoutUser && !$checkoutErrors && User::find_by_email(strtolower($checkoutData['email']))) {
            $checkoutErrors['email']='Already have an account with this email? Sign in to continue, or use a different email.';
            $_SESSION['flower_checkout_return']=true;
        }
        if (!$checkoutErrors) {
            try {
                $checkoutQuote=FlowerCheckout::quote($_SESSION['flower_bag'],(int)$checkoutData['branch_id'],$checkoutUser);
                $_SESSION['flower_checkout_draft']=['data'=>$checkoutData,'quote'=>$checkoutQuote,'key'=>bin2hex(random_bytes(32))];
                redirect('?route=flowers/place-order');
            } catch (DomainException $error) { $checkoutMessage=$error->getMessage(); }
            catch (Throwable $error) { error_log('Flower quote failed: '.get_class($error));$checkoutMessage='Checkout could not be prepared. Please try again.'; }
        }
        http_response_code(422);
    }
    if ($flowerRoute==='flowers/place-order') {
        $draft=$_SESSION['flower_checkout_draft']??null;
        if (!$draft) redirect('?route=flowers/checkout');
        $checkoutData=$draft['data'];$checkoutQuote=$draft['quote'];
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            try {
                if (!is_string($_POST['request_key']??null)||!hash_equals($draft['key'],$_POST['request_key'])) throw new DomainException('Checkout expired. Return to customer details to start again.');
                if (AuthSecurity::consume('flower-checkout',$checkoutUser?'user:'.$checkoutUser:'guest:'.AuthSecurity::ip(),10,3600)) throw new DomainException('Too many checkout attempts. Please try later.');
                $method=is_string($_POST['payment_method']??null)?$_POST['payment_method']:'';
                if ($checkoutUser) {
                    $order=FlowerCheckout::place($draft,$_SESSION['flower_bag'],$checkoutUser,$method,$draft['key']);
                } else {
                    $created=FlowerCheckout::placeGuest($draft,$_SESSION['flower_bag'],$method,$draft['key']);
                    $order=$created['order'];$user=$created['user'];$checkoutUser=(int)$user['id'];
                    session_regenerate_id(true);AuthSecurity::establish($checkoutUser);
                    $_SESSION['user']=['id'=>$checkoutUser,'name'=>$user['name'],'email'=>$user['email'],'username'=>$user['username'],'role'=>'Customer','branch_id'=>null];
                    $_SESSION['new_customer_access']=['user_id'=>$checkoutUser,'email'=>$user['email'],'username'=>$user['username'],'password'=>$created['password'],'expires'=>time()+900];
                    User::set_status($checkoutUser,'online');
                }
                $_SESSION['flower_bag']=[];
                // Persist the pending order before opening the gateway so callbacks can reconcile it.
                if ($method==='qrph') {
                    try { $destination=PayMongo::checkout($order['tracking_number'],$checkoutUser);header('Location: '.$destination,true,303);exit; }
                    catch (Throwable $error) { error_log('Flower gateway start failed: '.get_class($error)); }
                }
                redirect('?route=flowers/order&tracking='.rawurlencode($order['tracking_number']));
            } catch (DomainException $error) { $checkoutMessage=$error->getMessage();http_response_code(422); }
            catch (Throwable $error) { error_log('Flower order failed: '.get_class($error));$checkoutMessage='Your order could not be placed. Your bag has been kept. Please try again.';http_response_code(503); }
        }
    }
}
