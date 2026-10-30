<?php
require_once __DIR__.'/../models/OnlineOrder.php';
require_once __DIR__.'/../models/AuthSecurity.php';
class OnlineOrderController {
    public function index() {
        require_permission('manage_online_orders');
        header('Cache-Control: no-store, private');
        $json=strpos($_SERVER['HTTP_ACCEPT']??'','application/json')!==false;
        if($_SERVER['REQUEST_METHOD']==='POST') {
            enforce_csrf();
            if(!OnlineOrder::can_action((int)$_SESSION['user']['id'],AuthSecurity::input('status')))access_denied('You do not have permission for this online order action.');
            try{$order=OnlineOrder::update_status((int)AuthSecurity::input('order_id'),AuthSecurity::input('status'),(int)$_SESSION['user']['id'],AuthSecurity::input('payment_method') ?: 'Cash',isset($_POST['expected_status'])?AuthSecurity::input('expected_status'):null);if($json){header('Content-Type: application/json');echo json_encode(['success'=>true,'tracking'=>$order['tracking_number']]);exit;}set_flash('success','Order '.$order['tracking_number'].' updated.');}
            catch(DomainException $e){if($json){http_response_code(422);header('Content-Type: application/json');echo json_encode(['success'=>false,'message'=>$e->getMessage()]);exit;}set_flash('danger',$e->getMessage());}
            catch(Throwable $e){if($json){http_response_code(503);header('Content-Type: application/json');echo json_encode(['success'=>false,'message'=>'The order could not be updated. Refresh and retry.']);exit;}error_log('Online order update failed: '.$e->getMessage());set_flash('danger','The order could not be updated. Please refresh and retry.');}
            redirect('?route=admin/online_orders&tracking='.rawurlencode($order['tracking_number'] ?? AuthSecurity::input('tracking')));
        }
        $orders=OnlineOrder::list_for((int)$_SESSION['user']['id'],true);
        $tracking=isset($_GET['tracking'])&&is_string($_GET['tracking'])?$_GET['tracking']:'';
        $selected=$tracking!==''?OnlineOrder::detail($tracking,(int)$_SESSION['user']['id'],true):null;
        if($tracking!==''&&!$selected)access_denied('This order is not available in your branch.',404);
        if(isset($_GET['detail'])){if(!$selected)access_denied('Select an order.',404);require __DIR__.'/../views/partials/online_order_detail.php';return;}
        if(isset($_GET['board'])){require __DIR__.'/../views/partials/online_order_board.php';return;}
        require __DIR__.'/../views/modules/online_orders.php';
    }
}
