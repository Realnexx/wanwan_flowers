<?php
$destination = empty($_SESSION['user']['id']) ? 'login' : User::landing_route($_SESSION['user']['id']);
$heading = $status === 419 ? 'Please refresh this page' : ($status === 401 ? 'Please sign in' : 'Access denied');
?>
<!doctype html>
<html lang="en">
<head>
<?php require __DIR__ . '/../partials/site_favicon.php'; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f8e8ee">
<title><?= htmlspecialchars($heading) ?> | Wanwan Flowers</title>
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
        <i class="fa-solid fa-shield-heart"></i>
    </div>
    <p class="eyebrow"><span></span> ACCOUNT ACCESS · <?= (int)$status ?></p>
    <h1><?= htmlspecialchars($heading) ?></h1>
    <p class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <div class="help">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <span>If you need access, ask your administrator to check your permissions and assigned branch.</span>
    </div>
    <div class="actions">
        <a class="primary" href="<?= url('?route=' . $destination) ?>">
            <span><?= $destination === 'login' ? 'Sign in' : 'Go to my home page' ?></span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
        <a href="<?= url('?route=home') ?>">Visit website</a>
    </div>
    <p class="footnote">Your account settings keep each branch’s information secure.</p>
</main>
</body>
</html>
