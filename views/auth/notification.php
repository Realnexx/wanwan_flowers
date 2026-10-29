<?php
$escape = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$title = $title ?? 'Notification';
$message = $message ?? 'Action completed successfully.';
$type = $type ?? 'success';
$redirect = $redirect ?? '?route=home';
$btn_text = $btn_text ?? 'Continue to Wanwan Flowers';
?>
<!doctype html>
<html lang="en">
<head>
<?php require __DIR__ . '/../partials/site_favicon.php'; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f8e8ee">
<title><?= $escape($title) ?> | Wanwan Flowers</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/vendor/fontawesome/css/all.min.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/access-denied.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/access-denied.css') ?>">
</head>
<body>
<header class="access-header">
    <a class="brand" href="<?= url('?route=home') ?>">
        <span class="brand-flower"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
        <span>wanwan<small>FLOWERS &amp; LITTLE JOYS</small></span>
    </a>
</header>
<main class="access-card">
    <div class="access-icon" aria-hidden="true">
        <i class="fa-solid <?= ($type === 'danger' || $type === 'error') ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
    </div>
    <p class="eyebrow"><span></span> WANWAN FLOWERS · UPDATE</p>
    <h1><?= $escape($title) ?></h1>
    <p class="message"><?= $escape($message) ?></p>
    <div class="actions" style="margin-top: 22px;">
        <a class="primary" href="<?= $escape(url($redirect)) ?>">
            <span><?= $escape($btn_text) ?></span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</main>
</body>
</html>
