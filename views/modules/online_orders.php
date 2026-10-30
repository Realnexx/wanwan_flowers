<?php $title='Online Orders';$h=static function($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};ob_start(); ?>
<link rel="stylesheet" href="<?= url('assets/vendor/fontawesome/css/all.min.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/admin-online-orders.css') ?>?v=<?= filemtime(__DIR__.'/../../assets/css/admin-online-orders.css') ?>">
<div id="online-orders-app" data-endpoint="<?= url('?route=admin/online_orders') ?>" data-csrf="<?= $h(csrf_token()) ?>">
<div class="orders-page-heading"><div><span class="orders-eyebrow">THE FULFILLMENT STUDIO</span><h2>Online orders</h2><p>Every lovely moment, from new order to their doorstep.</p></div><button class="btn btn-outline-primary" type="button" id="orders-refresh">Refresh board</button></div>
<div class="orders-toolbar"><form id="order-search" method="get" action="<?= url('') ?>"><input type="hidden" name="route" value="admin/online_orders"><label class="visually-hidden" for="tracking-search">Order tracking number</label><input id="tracking-search" name="tracking" placeholder="Open an order by tracking number…" value="<?= $h($tracking) ?>"><button type="submit">Find order</button></form><p><span class="drag-legend"><i class="fa-solid fa-grip-vertical" aria-hidden="true"></i></span> Drag an order to its next status, or open it to use the action buttons.</p></div>
<div id="orders-feedback" role="status" aria-live="polite"></div>
<div id="order-board-region"><?php require __DIR__.'/../partials/online_order_board.php'; ?></div>
<dialog id="order-detail-modal" aria-labelledby="order-modal-title"><div class="order-modal-toolbar"><span>ORDER DETAILS</span><button type="button" id="order-modal-close" aria-label="Close order details">×</button></div><div id="order-modal-content"><?php if($selected)require __DIR__.'/../partials/online_order_detail.php'; ?></div><div class="order-modal-feedback" role="status" aria-live="polite"></div></dialog>
</div>
<?php require __DIR__.'/../partials/global_toasts.php'; ?>
<script src="<?= url('assets/js/admin-online-orders.js') ?>?v=<?= filemtime(__DIR__.'/../../assets/js/admin-online-orders.js') ?>" defer></script>
<?php $content=ob_get_clean();require __DIR__.'/../layouts/admin.php'; ?>
