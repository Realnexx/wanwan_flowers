<?php
$globalToastMessages=$globalToastMessages??[];
?>
<div id="global-toast-region" class="global-toast-region" aria-label="Notifications"></div>
<script id="global-toast-messages" type="application/json"><?= json_encode($globalToastMessages,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/global-toasts.css'),ENT_QUOTES,'UTF-8') ?>?v=<?= filemtime(__DIR__.'/../../assets/css/global-toasts.css') ?>">
<script src="<?= htmlspecialchars(url('assets/js/global-toasts.js'),ENT_QUOTES,'UTF-8') ?>?v=<?= filemtime(__DIR__.'/../../assets/js/global-toasts.js') ?>" defer></script>
