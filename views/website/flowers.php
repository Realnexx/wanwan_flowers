<?php
// Public bouquets use the same products and publishing controls as the staff catalog.
require_once __DIR__ . '/../../config/app.php';
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    $storefrontPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/') . '/';
    session_set_cookie_params(['path' => $storefrontPath, 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
header('Cache-Control: no-store, private');
$e = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
require_once __DIR__ . '/../../models/StorefrontTrust.php';
$trust = in_array($flowerRoute, ['home', 'about'], true)
    ? StorefrontTrust::summary($appConfig['database'])
    : ['rating' => null, 'reviews' => 0, 'completed_orders' => null];

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/session.php';
enforce_session_timeout();
require_once __DIR__ . '/../../models/FlowerCatalog.php';
$flowers = FlowerCatalog::products($conn);
$heroFlower = reset($flowers) ?: ['name'=>'Our seasonal flowers', 'image'=>'', 'price'=>0];
$occasionImage = static function ($occasion) use ($flowers, $heroFlower) {
    foreach ($flowers as $flower) if ($flower['type'] === $occasion) return $flower['image'];
    return $heroFlower['image'];
};
$minimumPrice = $flowers ? min(array_column($flowers, 'price')) : null;

// Remove unpublished or deleted products from saved selections.
require_once __DIR__.'/../../models/FlowerBag.php';
FlowerBag::load($flowers);


$img = static function ($id, $width = 900) {
    if (preg_match('~^photo-[A-Za-z0-9-]+$~', $id)) return 'https://images.unsplash.com/' . $id . '?auto=format&fit=crop&w=' . $width . '&q=85';
    return FlowerCatalog::image($id);
};

$_SESSION['flower_csrf'] = $_SESSION['flower_csrf'] ?? bin2hex(random_bytes(32));
$isCustomerArea = strpos($flowerRoute, 'customer/') === 0;
if ($isCustomerArea) require __DIR__.'/../../controllers/CustomerAreaController.php';

if (in_array($flowerRoute, ['flowers/checkout','flowers/place-order','flowers/order'], true)) {
    require __DIR__.'/../../controllers/FlowerCheckoutController.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isCustomerArea && !in_array($flowerRoute, ['flowers/checkout','flowers/place-order','flowers/order'], true)) {
    if (!is_string($_POST['token'] ?? null) || !hash_equals($_SESSION['flower_csrf'], $_POST['token'])) {
        http_response_code(403);
        exit('Your session expired. Please reload and try again.');
    }
    $id = (int)($_POST['flower_id'] ?? 0);
    $action = (string)($_POST['action'] ?? 'add');
    if (isset($_POST['gift_note']) && is_string($_POST['gift_note'])) {
        $_SESSION['flower_gift_note'] = mb_substr(trim($_POST['gift_note']), 0, 280);
    }
    try {
        if (isset($flowers[$id])) {
            $quantity=1;
            if ($action==='set_quantity') {
                $quantity=filter_var($_POST['qty']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>20]]);
                if ($quantity===false || $quantity===null) throw new DomainException('Enter a whole quantity between 1 and 20.');
            } elseif (!in_array($action,['remove','decrease'],true)) {
                $quantity=max(1,min(10,(int)($_POST['qty']??1)));
            }
            FlowerBag::change($id,$action,$quantity,$flowers);
        }
    } catch (Throwable $error) {
        http_response_code($error instanceof DomainException?422:503);
        $message=$error instanceof DomainException?$error->getMessage():'Could not save your bag. Please try again.';
        if (($_SERVER['HTTP_ACCEPT']??'')==='application/json') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error'=>$message]);exit;
        }
        exit($e($message));
    }
    if (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['count' => array_sum($_SESSION['flower_bag'] ?? [])]);
        exit;
    }
    header('Location: ?route=flowers/bag');
    exit;
}

$bag = $_SESSION['flower_bag'] ?? [];
$count = array_sum($bag);
$activeOrderCount=0;
if (!empty($_SESSION['user']['id'])) {
    $activeOrderCount=(int)RecipeInventory::rows("SELECT COUNT(*) total FROM online_orders WHERE user_id=? AND status IN ('pending','preparing','ready')",'i',[(int)$_SESSION['user']['id']])[0]['total'];
}
$giftNote = $_SESSION['flower_gift_note'] ?? '';

$titles = [
    'home' => 'Flowers for all your feelings',
    'products' => 'Find your kind of lovely',
    'pricing' => 'Find your kind of lovely',
    'about' => 'Rooted in a little love',
    'contact' => 'Let’s make something lovely',
    'branches' => 'Good things are on their way',
    'flowers/detail' => 'A little something beautiful',
    'flowers/bag' => 'Your bag of happiness',
    'flowers/checkout' => 'Customer & delivery details',
    'flowers/place-order' => 'Review & place your order',
    'flowers/order' => 'Your order',
    'customer/home'=>'Your Wanwan', 'customer/orders'=>'My orders', 'customer/history'=>'Order history', 'customer/profile'=>'My profile',
    'customer/receipt'=>'Order receipt',
];

$csrf = $_SESSION['flower_csrf'];
$card = static function ($id, $p) use ($e, $img, $csrf) {
    $badgeStyles = [
        'Best Seller' => ['rose', 'fa-award'],
        'Editor’s Pick' => ['blue', 'fa-wand-magic-sparkles'],
        'Popular Gift' => ['peach', 'fa-gift'],
        'Signature Lush' => ['rose', 'fa-crown'],
        'Seasonal' => ['sage', 'fa-leaf'],
        'Sweet Gesture' => ['rose', 'fa-heart'],
        'Limited Stem' => ['blue', 'fa-gem'],
        'Serene' => ['sage', 'fa-dove'],
    ];
    [$badgeTone, $badgeIcon] = $badgeStyles[$p['badge'] ?? ''] ?? ['blue', 'fa-seedling'];
?>
<article class="product" data-occasion="<?= $e($p['type']) ?>" data-price="<?= (int)$p['price'] ?>" data-title="<?= $e(strtolower($p['name'])) ?>" data-name="<?= $e(strtolower($p['name'] . ' ' . $p['stems'] . ' ' . $p['type'])) ?>">
    <?php if (!empty($p['badge'])): ?><span class="product-badge badge-<?= $e($badgeTone) ?>"><span class="badge-emblem"><i class="fa-solid <?= $e($badgeIcon) ?>" aria-hidden="true"></i></span><span><?= $e($p['badge']) ?></span></span><?php endif; ?>
    <a class="product-photo is-loading" href="?route=flowers/detail&amp;id=<?= $id ?>">
        <img loading="lazy" decoding="async" src="<?= $img($p['image'], 760) ?>" alt="<?= $e($p['name']) ?> floral arrangement">
        <span class="product-quick-pill" aria-hidden="true"><i class="fa-regular fa-eye" aria-hidden="true"></i> View details</span>
        <span class="round-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
    </a>
    <div class="product-body">
        <div class="product-meta">
            <h3><a href="?route=flowers/detail&amp;id=<?= $id ?>"><?= $e($p['name']) ?></a></h3>
            <span class="product-price">₱<?= number_format($p['price']) ?></span>
        </div>
        <p class="product-stems"><i class="fa-solid fa-seedling product-stems-icon" aria-hidden="true"></i><span><?= $e($p['stems']) ?></span></p>
        <div class="product-footer">
            <span class="product-tag"><i class="fa-solid fa-tag" aria-hidden="true"></i> <?= $e($p['type']) ?></span>
            <form method="post" class="quick-add-form">
                <input type="hidden" name="token" value="<?= $e($csrf) ?>">
                <input type="hidden" name="flower_id" value="<?= $id ?>">
                <button type="submit" class="quick-add-btn" aria-label="Add <?= $e($p['name']) ?> to bag"><span class="add-icon"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></span><span>Add to bag</span><i class="fa-solid fa-plus add-plus" aria-hidden="true"></i></button>
            </form>
        </div>
    </div>
</article>
<?php };
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8e8ee">
    <meta name="description" content="Wanwan Flowers — bespoke bouquets, seasonal stems, and little moments of joy. Explore our curated floral collection and plan a special delivery.">
    <title><?= $e($titles[$flowerRoute] ?? 'Wanwan Flowers') ?> | Wanwan Flowers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/flowers.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/flowers.css') ?: 2 ?>">
<?php if (!empty($_SESSION['user']['id'])): ?><link rel="stylesheet" href="assets/css/customer-portal.css?v=<?= filemtime(__DIR__.'/../../assets/css/customer-portal.css') ?>"><?php endif; ?>
</head>
<body class="<?= !empty($_SESSION['user']['id']) ? 'customer-signed-in'.(in_array($flowerRoute, ['products','pricing'],true)?' customer-explore-page':'').(in_array($flowerRoute, ['flowers/checkout','flowers/place-order','flowers/order'],true)?' customer-checkout-page':'') : '' ?>">
<a class="skip" href="#main">Skip to content</a>

<div class="announcement">
    <div class="announcement-inner">
        <span><i class="fa-solid fa-seedling" aria-hidden="true"></i> Hand-tied daily in small batches with fresh seasonal stems</span>
        <span class="announcement-divider" aria-hidden="true">•</span>
        <span>Complimentary handwritten note card with every bouquet <i class="fa-regular fa-heart" aria-hidden="true"></i></span>
        <a href="?route=products" class="announcement-cta">Explore blooms <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
</div>

<header class="header">
    <div class="header-inner">
        <a class="brand" href="?route=home">
            <span class="brand-flower" aria-hidden="true"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
            <span>wanwan<small>FLOWERS &amp; LITTLE JOYS</small></span>
        </a>

        <nav id="navigation" aria-label="Main navigation">
            <?php foreach ([
                'home' => 'Home',
                'products' => 'Shop Flowers',
                'about' => 'Our Story',
                'branches' => 'Delivery & Care',
                'contact' => 'Contact Us',
            ] as $key => $label): ?>
                <a <?= ($flowerRoute === $key || ($key === 'products' && in_array($flowerRoute, ['pricing', 'flowers/detail'], true))) ? 'aria-current="page"' : '' ?> href="?route=<?= $key ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <div class="nav-mobile-cta">
                <a class="button button-full" href="?route=products">Shop the Collection <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
            </div>
        </nav>

        <div class="header-actions">
            <?php if (!empty($_SESSION['user']['id'])): ?>
                <a class="account-link" href="?route=customer/home"><i class="fa-regular fa-user" aria-hidden="true"></i><span>My account</span></a><form method="post" action="?route=logout" class="storefront-account-form"><input type="hidden" name="csrf_token" value="<?= $e(csrf_token()) ?>"><button class="account-link" type="submit"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Sign out</span></button></form>
            <?php else: ?>
                <a class="account-link" href="?route=login"><i class="fa-regular fa-user" aria-hidden="true"></i><span>Sign in</span></a>
            <?php endif; ?>
            <a class="header-pill-cta" href="?route=contact">Custom Bouquet</a>
            <a class="bag-link <?= $count > 0 ? 'has-items' : '' ?>" href="?route=flowers/bag" aria-label="Shopping bag, <?= $count ?> items">
                <span class="bag-icon" aria-hidden="true"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></span>
                <span class="bag-label">Bag</span>
                <span class="bag-count"><?= $count ?></span>
            </a>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="navigation" aria-label="Toggle navigation menu">
                <span class="menu-toggle-text">Menu</span>
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</header>
<div class="nav-backdrop" hidden></div>
<?php if (empty($_SESSION['user']['id']) && !in_array($flowerRoute, ['flowers/bag','flowers/checkout','flowers/place-order','flowers/order'], true)): ?>
<a class="mobile-view-bag" href="?route=flowers/bag" <?= $count > 0 ? '' : 'hidden' ?>><span aria-hidden="true"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span> View bag <span class="mobile-bag-count"><?= $count ?></span><span aria-hidden="true"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></a>
<?php endif; ?>
<div class="bag-feedback" role="status" aria-live="polite"></div>

<?php if (!empty($_SESSION['user']['id'])): ?><nav class="customer-bottom-nav" aria-label="Customer navigation"><?php foreach (['customer/home'=>['Home','fa-house'],'customer/orders'=>['My orders','fa-bag-shopping'],'customer/history'=>['History','fa-clock-rotate-left'],'flowers/bag'=>['Bag','fa-basket-shopping'],'customer/profile'=>['Profile','fa-user']] as $navRoute=>$navItem): ?><a href="?route=<?= $navRoute ?>" <?= $flowerRoute===$navRoute?'aria-current="page"':'' ?>><span class="customer-nav-icon"><i class="fa-solid <?= $navItem[1] ?>" aria-hidden="true"></i><?php if($navRoute==='customer/orders' && $activeOrderCount>0): ?><span class="customer-nav-order-count" aria-label="<?= $activeOrderCount ?> active orders"><?= $activeOrderCount ?></span><?php endif; ?><?php if($navRoute==='flowers/bag'): ?><span class="customer-nav-bag-count" aria-live="polite" <?= $count>0?'':'hidden' ?>><?= $count ?></span><?php endif; ?></span><span><?= $navItem[0] ?></span></a><?php endforeach; ?></nav><?php endif; ?>
<main id="main">
<?php if ($flowerRoute==='customer/receipt'): ?>
    <?php require __DIR__.'/customer_receipt.php'; ?>
<?php elseif ($isCustomerArea): ?>
    <?php require __DIR__.'/customer_area.php'; ?>
<?php elseif (in_array($flowerRoute, ['flowers/checkout','flowers/place-order','flowers/order'], true)): ?>
    <?php require __DIR__.'/flower_checkout.php'; ?>
<?php elseif ($flowerRoute === 'home'): ?>
    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-ambient" aria-hidden="true"></div>
        <div class="hero-copy">
            <div class="trust-pill">
                <?php if ($trust['rating'] !== null): ?><span class="trust-stars" aria-hidden="true"><i class="fa-solid fa-star" aria-hidden="true"></i></span><?php endif; ?>
                <span><?= $trust['rating'] !== null ? $e(number_format($trust['rating'], 1) . '/5 from ' . number_format($trust['reviews']) . ' verified ' . ($trust['reviews'] === 1 ? 'review' : 'reviews')) : 'No ratings yet' ?><?php if ($trust['completed_orders'] !== null): ?> · <?= number_format($trust['completed_orders']) ?> completed orders<?php endif; ?></span>
            </div>
            <div class="eyebrow"><span></span> THOUGHTFULLY PICKED · BEAUTIFULLY GIVEN</div>
            <h1>For the big days.<br>The little days.<br><em>The just-because.</em></h1>
            <p class="hero-lead">Some feelings are better said with flowers. We craft expressive, garden-inspired arrangements wrapped in linen paper and delivered with your personal words.</p>

            <div class="hero-cta-group">
                <a class="button" href="?route=products">Find their flowers <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></a>
                <a class="button button-ghost" href="?route=contact">Bespoke request</a>
            </div>

            <div class="hero-highlights">
                <div class="hero-highlight-item">
                    <span class="highlight-icon" aria-hidden="true"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
                    <div>
                        <strong>Farm-Fresh Stems</strong>
                        <span>Conditioned every morning</span>
                    </div>
                </div>
                <div class="hero-highlight-item">
                    <span class="highlight-icon" aria-hidden="true"><i class="fa-regular fa-envelope" aria-hidden="true"></i></span>
                    <div>
                        <strong>Keepsake Wax Note</strong>
                        <span>Included free with every order</span>
                    </div>
                </div>
                <div class="hero-highlight-item">
                    <span class="highlight-icon" aria-hidden="true"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></span>
                    <div>
                        <strong>Photo Proof</strong>
                        <span>Sent before your blooms leave</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-art">
            <div class="hero-arch-frame">
                <img class="hero-image" src="<?= $img($heroFlower['image'], 1200) ?>" alt="<?= $e($heroFlower['name']) ?>">
                <div class="floating-card floating-card-top">
                    <span class="floating-dot"></span>
                    <div>
                        <small>TODAY IN STUDIO</small>
                        <strong><?= $e($heroFlower['name']) ?></strong>
                    </div>
                </div>
                <div class="floating-card floating-card-bottom">
                    <span class="floating-price"><?= $minimumPrice !== null ? 'From ₱' . number_format($minimumPrice) : 'New blooms coming soon' ?></span>
                    <small>Hand-tied &amp; gift-wrapped</small>
                </div>
                <div class="image-label">a little love, in full bloom</div>
            </div>
            <div class="bloom-seal">HAND-TIED<br><b><i class="fa-regular fa-heart" aria-hidden="true"></i></b><br>HEART-FELT</div>
            <span class="art-spark" aria-hidden="true"><i class="fa-solid fa-spa" aria-hidden="true"></i></span>
        </div>

        <div class="hero-bottom">
            <span>NOT JUST FLOWERS. A FEELING.</span>
            <a href="#featured-blooms" class="scroll-cue">SCROLL TO BLOOM <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
        </div>
    </section>

    <!-- INFINITE MARQUEE RIBBON -->
    <div class="ribbon" aria-label="Studio highlights">
        <div class="ribbon-track">
            <span>Made for your moments</span> <i class="fa-solid fa-spa" aria-hidden="true"></i>
            <span>Wrapped with care in linen paper</span> <i class="fa-solid fa-spa" aria-hidden="true"></i>
            <span>Always a little special</span> <i class="fa-solid fa-spa" aria-hidden="true"></i>
            <span>Complimentary keepsake card</span> <i class="fa-solid fa-spa" aria-hidden="true"></i>
            <span>Small-batch seasonal stems</span> <i class="fa-solid fa-spa" aria-hidden="true"></i>
            <span>Made for your moments</span> <i class="fa-solid fa-spa" aria-hidden="true"></i>
            <span>Wrapped with care in linen paper</span> <i class="fa-solid fa-spa" aria-hidden="true"></i>
            <span>Always a little special</span> <i class="fa-solid fa-spa" aria-hidden="true"></i>
            <span>Complimentary keepsake card</span> <i class="fa-solid fa-spa" aria-hidden="true"></i>
            <span>Small-batch seasonal stems</span> <i class="fa-solid fa-spa" aria-hidden="true"></i>
        </div>
    </div>

    <!-- FEATURED COLLECTION -->
    <section id="featured-blooms" class="section">
        <div class="section-heading">
            <div>
                <div class="eyebrow"><span></span> THE EVERYDAY, MADE EXTRA LOVELY</div>
                <h2>Meet your new <em>favorite blooms.</em></h2>
            </div>
            <a class="text-link" href="?route=products">Explore all <?= count($flowers) ?> arrangements <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
        </div>
        <div class="product-grid">
            <?php foreach (array_slice($flowers, 0, 6, true) as $id => $p) $card($id, $p); ?>
        </div>
    </section>

    <!-- BENTO OCCASION GRID -->
    <section class="occasion">
        <div class="occasion-header">
            <div class="eyebrow"><span></span> THERE’S A BOUQUET FOR THAT</div>
            <h2>Whatever the feeling,<br><em>let it bloom.</em></h2>
            <p>Curated palettes designed around life’s sweetest milestones and quietest gestures.</p>
        </div>
        <div class="occasion-bento">
            <a class="occasion-card occasion-card-featured" href="?route=products&amp;occasion=Romance">
                <img loading="lazy" src="<?= $img($occasionImage('Romance'), 900) ?>" alt="Romantic blush bouquet">
                <div class="occasion-overlay">
                    <span class="occasion-pill">Love &amp; Anniversaries</span>
                    <h3>You, me &amp; a little romance <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></h3>
                    <p>Layered garden roses, sweet pea, and soft blush palettes.</p>
                </div>
            </a>
            <a class="occasion-card" href="?route=products&amp;occasion=Birthday">
                <img loading="lazy" src="<?= $img($occasionImage('Birthday'), 700) ?>" alt="Bright birthday flowers">
                <div class="occasion-overlay">
                    <span class="occasion-pill">Birthday Blooms</span>
                    <h3>Another trip around the sun <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></h3>
                    <p>Joyful apricot, buttercup, and sunlit stems.</p>
                </div>
            </a>
            <a class="occasion-card" href="?route=products&amp;occasion=Just%20because">
                <img loading="lazy" src="<?= $img($occasionImage('Just because'), 700) ?>" alt="Just because seasonal bouquet">
                <div class="occasion-overlay">
                    <span class="occasion-pill">Just Because</span>
                    <h3>No reason. Just you. <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></h3>
                    <p>Unexpected little gestures for ordinary Tuesdays.</p>
                </div>
            </a>
        </div>
    </section>

    <!-- HOW IT WORKS / STUDIO PROCESS -->
    <section class="section process-section">
        <div class="section-heading">
            <div>
                <div class="eyebrow"><span></span> EFFORTLESS GIFTING</div>
                <h2>From our table to <em>their hands.</em></h2>
            </div>
            <a class="text-link" href="?route=branches">Delivery &amp; care guide <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
        </div>
        <div class="process-grid">
            <article class="process-card">
                <div class="process-top">
                    <span class="process-num">01</span>
                    <span class="process-icon" aria-hidden="true"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
                </div>
                <h3>Choose a mood or bouquet</h3>
                <p>Pick a signature arrangement from our collection or tell our florists the color story and feeling you have in mind.</p>
            </article>
            <article class="process-card">
                <div class="process-top">
                    <span class="process-num">02</span>
                    <span class="process-icon" aria-hidden="true">✂</span>
                </div>
                <h3>Hand-tied &amp; photographed</h3>
                <p>We condition fresh seasonal stems, wrap them in signature textured paper, hand-write your card, and send a preview photo.</p>
            </article>
            <article class="process-card">
                <div class="process-top">
                    <span class="process-num">03</span>
                    <span class="process-icon" aria-hidden="true">🚗</span>
                </div>
                <h3>Delivered with care</h3>
                <p>Coordinated courier delivery keeps blooms hydrated and upright from our studio door straight to your recipient.</p>
            </article>
        </div>
    </section>

    <!-- STORY SPLIT BLOCK -->
    <section class="story-block section">
        <div class="story-visual">
            <img loading="lazy" src="<?= $img($heroFlower['image'], 900) ?>" alt="A thoughtful arrangement of seasonal garden flowers">
            <div class="story-badge-card">
                <strong>100% Bespoke</strong>
                <span>Crafted in small daily batches, never mass-produced.</span>
            </div>
        </div>
        <div class="story-content">
            <div class="eyebrow"><span></span> A SMALL SHOP · A WHOLE LOT OF HEART</div>
            <h2>More than a bouquet.<br><em>A little piece of us.</em></h2>
            <p>We believe the smallest gestures can leave the biggest feelings. That’s why every Wanwan bouquet is put together with expressive seasonal stems, a personal touch, and plenty of heart.</p>
            <ul class="story-checklist">
                <li><span><i class="fa-solid fa-check" aria-hidden="true"></i></span> Ethically sourced local &amp; imported blooms</li>
                <li><span><i class="fa-solid fa-check" aria-hidden="true"></i></span> Eco-conscious linen &amp; kraft wrapping</li>
                <li><span><i class="fa-solid fa-check" aria-hidden="true"></i></span> Complimentary flower food &amp; care guide</li>
            </ul>
            <div class="hero-cta-group">
                <a class="button" href="?route=about">Get to know us <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></a>
                <a class="text-link" href="?route=contact">Talk to a florist <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
    </section>

    <!-- SOCIAL PROOF / TESTIMONIALS -->
    <section class="section testimonials-section">
        <div class="section-heading">
            <div>
                <div class="eyebrow"><span></span> LOVE NOTES FROM OUR COMMUNITY</div>
                <h2>Kind words &amp; <em>happy tears.</em></h2>
            </div>
        </div>
        <div class="testimonials-grid">
            <blockquote class="testimonial-card">
                <div class="testimonial-stars" aria-label="5 out of 5 stars"><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i></div>
                <p>“I ordered <em>The Pink Reverie</em> for my mom’s 60th birthday and she actually cried when she opened the card. The stems lasted over a week and smelled heavenly.”</p>
                <footer>
                    <strong>Camille R.</strong>
                    <span>Ordered The Pink Reverie · Birthday</span>
                </footer>
            </blockquote>
            <blockquote class="testimonial-card">
                <div class="testimonial-stars" aria-label="5 out of 5 stars"><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i></div>
                <p>“Unlike generic flower shops, Wanwan’s bouquets look like they came straight out of an editorial magazine. Getting a photo before delivery gave me total peace of mind.”</p>
                <footer>
                    <strong>Marco D.</strong>
                    <span>Ordered Blush Hour · Anniversary</span>
                </footer>
            </blockquote>
            <blockquote class="testimonial-card">
                <div class="testimonial-stars" aria-label="5 out of 5 stars"><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i><i class="fa-solid fa-star" aria-hidden="true"></i></div>
                <p>“<em>Something Blue</em> is my go-to gift whenever a friend needs a pick-me-up. The wrapping and handwritten note always feel so personal and warm.”</p>
                <footer>
                    <strong>Hannah L.</strong>
                    <span>Ordered Something Blue · Just Because</span>
                </footer>
            </blockquote>
        </div>
    </section>

<?php elseif (in_array($flowerRoute, ['products', 'pricing'], true)):
    $occasion = is_string($_GET['occasion'] ?? null) ? $_GET['occasion'] : '';
    $search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    $totalFlowers = count($flowers);
    $occasionCounts = ['' => $totalFlowers];
    $occasions = ['' => 'All blooms'];
    $prices = [];
    $spotlightId = array_key_first($flowers);
    foreach ($flowers as $fid => $flower) {
        $type = $flower['type'];
        $occasions[$type] = $type;
        $occasionCounts[$type] = ($occasionCounts[$type] ?? 0) + 1;
        $prices[] = (int)$flower['price'];
        if (($flower['badge'] ?? '') === 'Best Seller' && $spotlightId === array_key_first($flowers)) {
            $spotlightId = $fid;
        }
    }
    $minPrice = $prices ? min($prices) : 0;
    $maxPrice = $prices ? max($prices) : 0;
    $budgetCounts = [
        'all' => $totalFlowers,
        'under-1800' => 0,
        '1800-2200' => 0,
        'over-2200' => 0,
    ];
    foreach ($prices as $pVal) {
        if ($pVal < 1800) {
            $budgetCounts['under-1800']++;
        } elseif ($pVal <= 2200) {
            $budgetCounts['1800-2200']++;
        } else {
            $budgetCounts['over-2200']++;
        }
    }
    $spotlight = $flowers[$spotlightId] ?? reset($flowers);
    $occasionIcons = [
        '' => 'fa-spa',
        'Romance' => 'fa-heart',
        'Birthday' => 'fa-wand-magic-sparkles',
        'Everyday' => 'fa-sun',
        'Celebration' => 'fa-champagne-glasses',
        'Sympathy' => 'fa-dove',
    ];
?>
    <?php if (!empty($_SESSION['user']['id'])): ?><header class="customer-explore-bar"><a href="?route=customer/home" class="explore-back" aria-label="Back to customer home"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a><div><h1>Explore flowers</h1><p>Find your next little lovely.</p></div><a class="customer-explore-bag" href="?route=flowers/bag" aria-label="View your bag"><i class="fa-solid fa-basket-shopping" aria-hidden="true"></i><span class="customer-nav-bag-count" aria-live="polite" <?= $count>0?'':'hidden' ?>><?= $count ?></span></a></header><?php endif; ?>
    <section class="catalog-hero">
        <div class="catalog-hero-inner">
            <div class="catalog-hero-copy">
                <div class="catalog-eyebrow-row">
                    <div class="eyebrow"><span></span> THE WANWAN COLLECTION</div>
                    <span class="catalog-live-pill"><span class="live-dot" aria-hidden="true"></span> Market-fresh stems today</span>
                </div>
                <h1>Find your kind<br>of <em>lovely.</em></h1>
                <p>Thoughtfully proportioned bouquets crafted to order in our studio—wrapped in signature linen paper with hydration care and a keepsake message card.</p>
                <div class="catalog-trust-strip" aria-label="Collection highlights">
                    <div class="catalog-trust-pill">
                        <i class="fa-solid fa-seedling" aria-hidden="true"></i>
                        <div>
                            <strong><?= $totalFlowers ?> Curated Designs</strong>
                            <span>From ₱<?= number_format($minPrice) ?> – ₱<?= number_format($maxPrice) ?></span>
                        </div>
                    </div>
                    <div class="catalog-trust-pill">
                        <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
                        <div>
                            <strong>Free Keepsake Card</strong>
                            <span>Handwritten note included</span>
                        </div>
                    </div>
                    <div class="catalog-trust-pill">
                        <i class="fa-solid fa-camera-retro" aria-hidden="true"></i>
                        <div>
                            <strong>Studio Photo Preview</strong>
                            <span>Sent before dispatch</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($spotlight): ?>
            <aside class="catalog-spotlight" aria-label="Featured arrangement">
                <a class="catalog-spotlight-media is-loading" href="?route=flowers/detail&amp;id=<?= (int)$spotlightId ?>">
                    <img loading="lazy" decoding="async" src="<?= $img($spotlight['image'], 720) ?>" alt="<?= $e($spotlight['name']) ?> spotlight bouquet">
                    <span class="spotlight-tag"><i class="fa-solid fa-crown" aria-hidden="true"></i> Florist’s Spotlight</span>
                </a>
                <div class="catalog-spotlight-body">
                    <div class="spotlight-meta">
                        <span class="spotlight-occasion"><?= $e($spotlight['type']) ?></span>
                        <strong class="spotlight-price">₱<?= number_format($spotlight['price']) ?></strong>
                    </div>
                    <h2><a href="?route=flowers/detail&amp;id=<?= (int)$spotlightId ?>"><?= $e($spotlight['name']) ?></a></h2>
                    <p><?= $e($spotlight['stems']) ?></p>
                    <div class="spotlight-actions">
                        <a class="text-link" href="?route=flowers/detail&amp;id=<?= (int)$spotlightId ?>">Inspect arrangement <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                        <form method="post" class="quick-add-form">
                            <input type="hidden" name="token" value="<?= $e($csrf) ?>">
                            <input type="hidden" name="flower_id" value="<?= (int)$spotlightId ?>">
                            <button type="submit" class="quick-add-btn" aria-label="Add <?= $e($spotlight['name']) ?> to bag">
                                <span class="add-icon"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></span>
                                <span>Quick add</span>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>
            <?php endif; ?>
        </div>
    </section>

    <?php
        $shown = 0;
        foreach ($flowers as $id => $p) {
            $matchOccasion = ($occasion === '' || $occasion === $p['type']);
            $matchSearch = ($search === '' || stripos($p['name'] . ' ' . $p['stems'] . ' ' . $p['type'], $search) !== false);
            if ($matchOccasion && $matchSearch) {
                $shown++;
            }
        }
        $initialFilterCount = ($occasion !== '' ? 1 : 0) + ($search !== '' ? 1 : 0);
    ?>
    <section class="section catalog" id="catalog-section">
        <!-- Mobile Sticky Filter & Search Trigger Bar (visible <= 820px) -->
        <div class="catalog-mobile-bar" aria-label="Mobile catalog controls">
            <button type="button" class="mobile-filter-trigger" id="mobile-filter-open" aria-label="Filters and budget" aria-haspopup="dialog" aria-expanded="false" aria-controls="catalog-tools">
                <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                <span>Filters &amp; Budget</span>
                <small class="mobile-filter-badge" id="mobile-filter-badge" <?= $initialFilterCount > 0 ? '' : 'hidden' ?>><?= (int)$initialFilterCount ?></small>
            </button>

            <form method="get" class="search mobile-quick-search" id="catalog-mobile-search-form" role="search" novalidate>
                <i class="fa-solid fa-magnifying-glass search-leading-icon" aria-hidden="true"></i>
                <input id="catalog-mobile-search" type="search" enterkeyhint="search" autocomplete="off" aria-label="Search bouquets" placeholder="Search blooms…" value="<?= $e($search) ?>">
                <button type="button" id="catalog-mobile-search-clear" class="search-clear-btn" aria-label="Clear search" <?= $search === '' ? 'hidden' : '' ?>><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </form>


        </div>

        <div class="catalog-drawer-backdrop" id="catalog-drawer-backdrop" hidden></div>

        <div class="catalog-tools" id="catalog-tools" aria-label="Filter and sort bouquets">
            <div class="drawer-header">
                <span class="drawer-handle" aria-hidden="true"></span>
                <div class="drawer-title-row">
                    <div>
                        <strong><i class="fa-solid fa-sliders" aria-hidden="true"></i> Filter &amp; Sort Blooms</strong>
                        <p>Refine by occasion, budget tier, or sort order</p>
                    </div>
                    <button type="button" class="drawer-close-btn" id="catalog-drawer-close" aria-label="Close filter drawer">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="drawer-body">
                <div class="catalog-filter-block">
                    <div class="filter-block-header">
                        <span class="filter-block-title"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Filter by Occasion</span>
                    </div>
                    <div class="filters" role="tablist" aria-label="Filter bouquets by occasion">
                        <?php foreach ($occasions as $key => $label):
                            $icon = $occasionIcons[$key] ?? 'fa-seedling';
                            $count = $occasionCounts[$key] ?? 0;
                        ?>
                            <button type="button" class="filter-tab <?= $occasion === $key ? 'selected' : '' ?>" role="tab" aria-selected="<?= $occasion === $key ? 'true' : 'false' ?>" data-filter-occasion="<?= $e($key) ?>">
                                <i class="fa-solid <?= $e($icon) ?> filter-icon" aria-hidden="true"></i>
                                <span><?= $e($label) ?></span>
                                <small class="filter-count"><?= (int)$count ?></small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="catalog-tools-bottom">
                    <div class="budget-filter-group" role="group" aria-label="Filter by budget range">
                        <div class="filter-block-header">
                            <span class="filter-block-title"><i class="fa-solid fa-tags" aria-hidden="true"></i> Budget Range</span>
                            <span class="budget-range-note">₱<?= number_format($minPrice) ?> – ₱<?= number_format($maxPrice) ?></span>
                        </div>
                        <div class="price-filters">
                            <button type="button" class="price-chip active" data-price-range="all" aria-pressed="true">
                                <span class="price-chip-top">
                                    <i class="fa-solid fa-layer-group price-chip-icon" aria-hidden="true"></i>
                                    <strong>All Budgets</strong>
                                    <small class="price-chip-count"><?= (int)$budgetCounts['all'] ?></small>
                                </span>
                                <span class="price-chip-sub">Every price tier</span>
                            </button>
                            <button type="button" class="price-chip" data-price-range="under-1800" aria-pressed="false">
                                <span class="price-chip-top">
                                    <i class="fa-solid fa-seedling price-chip-icon" aria-hidden="true"></i>
                                    <strong>Under ₱1,800</strong>
                                    <small class="price-chip-count"><?= (int)$budgetCounts['under-1800'] ?></small>
                                </span>
                                <span class="price-chip-sub">Petite &amp; sweet</span>
                            </button>
                            <button type="button" class="price-chip" data-price-range="1800-2200" aria-pressed="false">
                                <span class="price-chip-top">
                                    <i class="fa-solid fa-heart price-chip-icon" aria-hidden="true"></i>
                                    <strong>₱1,800 – ₱2,200</strong>
                                    <small class="price-chip-count"><?= (int)$budgetCounts['1800-2200'] ?></small>
                                </span>
                                <span class="price-chip-sub">Signature lush</span>
                            </button>
                            <button type="button" class="price-chip" data-price-range="over-2200" aria-pressed="false">
                                <span class="price-chip-top">
                                    <i class="fa-solid fa-crown price-chip-icon" aria-hidden="true"></i>
                                    <strong>₱2,200 &amp; Above</strong>
                                    <small class="price-chip-count"><?= (int)$budgetCounts['over-2200'] ?></small>
                                </span>
                                <span class="price-chip-sub">Grand statement</span>
                            </button>
                        </div>
                    </div>

                    <div class="catalog-controls-group">
                        <div class="filter-block-header">
                            <span class="filter-block-title"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Search &amp; Sort</span>
                        </div>
                        <div class="catalog-controls">
                            <form method="get" class="search" id="catalog-search-form" role="search" novalidate>
                                <input type="hidden" name="route" value="products">
                                <input type="hidden" name="occasion" id="catalog-occasion-input" value="<?= $e($occasion) ?>">
                                <i class="fa-solid fa-magnifying-glass search-leading-icon" aria-hidden="true"></i>
                                <input id="catalog-search" name="q" type="search" enterkeyhint="search" autocomplete="off" aria-label="Search bouquets" placeholder="Search rose, peony, sunflower…" value="<?= $e($search) ?>">
                                <button type="button" id="catalog-search-clear" class="search-clear-btn" aria-label="Clear search" <?= $search === '' ? 'hidden' : '' ?>><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                <button type="submit" aria-label="Filter results"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                            </form>

                            <div class="catalog-controls-row">
                                <label class="sort-control">
                                    <i class="fa-solid fa-arrow-up-wide-short sort-icon" aria-hidden="true"></i>
                                    <span class="sr-only">Sort bouquets</span>
                                    <select id="catalog-sort" aria-label="Sort bouquets">
                                        <option value="featured">Sort: Featured</option>
                                        <option value="price-asc">Price: Low to High</option>
                                        <option value="price-desc">Price: High to Low</option>
                                        <option value="name-asc">Name: A to Z</option>
                                    </select>
                                </label>

                                <div class="view-switcher desktop-view-switcher" role="group" aria-label="Grid layout density">
                                    <button type="button" class="view-btn active" data-grid-cols="default" aria-label="Standard grid view" title="Standard view">
                                        <i class="fa-solid fa-table-cells" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="view-btn" data-grid-cols="alt" aria-label="Compact / Editorial view" title="Switch grid density">
                                        <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="drawer-footer">
                <button type="button" class="drawer-reset-btn" id="catalog-drawer-reset">
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset
                </button>
                <button type="button" class="button drawer-apply-btn" id="catalog-drawer-apply">
                    <span>Show <strong id="drawer-result-count"><?= (int)$shown ?></strong> arrangements</span>
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="catalog-meta-bar" aria-live="polite">
            <p class="catalog-result-summary">
                Showing <strong id="catalog-visible-count"><?= (int)$shown ?></strong> of <strong><?= (int)$totalFlowers ?></strong> handcrafted arrangements
                <span id="catalog-active-filter-pill" class="catalog-active-pill" <?= ($occasion === '' && $search === '') ? 'hidden' : '' ?>>
                    <?= $e($occasion !== '' ? $occasion : ($search !== '' ? '“' . $search . '”' : '')) ?>
                </span>
            </p>
            <button type="button" id="catalog-reset-inline" class="catalog-reset-link" <?= ($occasion === '' && $search === '') ? 'hidden' : '' ?>>
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Clear all filters
            </button>
        </div>

        <!-- Skeletal Loading Grid for Filter/Search Transitions & Lazy Reveal -->
        <div class="product-grid catalog-skeleton-grid" id="catalog-skeleton" aria-hidden="true" hidden>
            <?php for ($s = 0; $s < 6; $s++): ?>
            <div class="skeleton-card">
                <div class="skeleton-arch shimmer-block"></div>
                <div class="skeleton-body">
                    <div class="skeleton-row">
                        <span class="skeleton-title shimmer-block"></span>
                        <span class="skeleton-price shimmer-block"></span>
                    </div>
                    <span class="skeleton-stems shimmer-block"></span>
                    <span class="skeleton-stems skeleton-stems-short shimmer-block"></span>
                    <div class="skeleton-footer">
                        <span class="skeleton-tag shimmer-block"></span>
                        <span class="skeleton-btn shimmer-block"></span>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <div class="product-grid" id="catalog-grid">
            <?php
            foreach ($flowers as $id => $p) {
                $matchOccasion = ($occasion === '' || $occasion === $p['type']);
                $matchSearch = ($search === '' || stripos($p['name'] . ' ' . $p['stems'] . ' ' . $p['type'], $search) !== false);
                if (!$matchOccasion || !$matchSearch) {
                    ob_start();
                    $card($id, $p);
                    $html = ob_get_clean();
                    echo preg_replace('/^<article class="product"/', '<article class="product" hidden', $html, 1);
                } else {
                    $card($id, $p);
                }
            }
            ?>
        </div>

        <div class="empty" id="catalog-empty" <?= $shown ? 'hidden' : '' ?>>
            <span class="giant-flower" aria-hidden="true"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
            <h2>No blooms match that filter.</h2>
            <p>Try another flower name, adjust your budget range, or explore our full seasonal collection.</p>
            <button type="button" class="button" id="catalog-empty-reset">Reset all filters <i class="fa-solid fa-rotate-left" aria-hidden="true"></i></button>
        </div>

        <div class="catalog-perks" aria-label="Included with every Wanwan bouquet">
            <article class="catalog-perk-card">
                <span class="catalog-perk-icon"><i class="fa-solid fa-droplet" aria-hidden="true"></i></span>
                <div>
                    <h3>Hydration &amp; Linen Wrap</h3>
                    <p>Every bouquet travels with a concealed water pack and breathable French-linen paper wrap so blooms arrive crisp.</p>
                </div>
            </article>
            <article class="catalog-perk-card">
                <span class="catalog-perk-icon"><i class="fa-solid fa-feather-pointed" aria-hidden="true"></i></span>
                <div>
                    <h3>Wax-Sealed Keepsake Note</h3>
                    <p>Add your personal message in the bag—we transcribe it onto textured cotton cardstock and seal it by hand.</p>
                </div>
            </article>
            <article class="catalog-perk-card">
                <span class="catalog-perk-icon"><i class="fa-solid fa-sliders" aria-hidden="true"></i></span>
                <div>
                    <h3>3 Signature Size Tiers</h3>
                    <p>Select Classic, Lush (+35% stems), or Grand Statement (+75% stems) on any bouquet’s detail page.</p>
                </div>
            </article>
        </div>

        <div class="bespoke-banner">
            <div class="bespoke-copy">
                <div class="eyebrow"><span></span> LOOKING FOR SOMETHING ONE-OF-A-KIND?</div>
                <h3>Custom color palettes, bridal bouquets &amp; event florals</h3>
                <p>Tell us your budget, preferred flowers, and mood—our florists will compose a bespoke arrangement tailored to your moment.</p>
                <div class="bespoke-tags">
                    <span><i class="fa-solid fa-check" aria-hidden="true"></i> Custom color story</span>
                    <span><i class="fa-solid fa-check" aria-hidden="true"></i> Bridal &amp; entourage</span>
                    <span><i class="fa-solid fa-check" aria-hidden="true"></i> Private dining &amp; events</span>
                </div>
            </div>
            <div class="bespoke-cta">
                <a class="button" href="?route=contact">Request custom design <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                <small>Typical studio response within 2 hours</small>
            </div>
        </div>

        <p class="fineprint">Our collection is a curated seasonal preview. Exact stem varieties may vary gently with daily market freshness; contact us to confirm availability and arrange your delivery.</p>
    </section>

<?php elseif ($flowerRoute === 'flowers/detail'):
    $id = (int)($_GET['id'] ?? 0);
    $p = $flowers[$id] ?? null;
    if (!$p) http_response_code(404);
?>
    <section class="section detail">
        <?php if ($p): ?>
            <div class="detail-gallery">
                <img src="<?= $img($p['image'], 1100) ?>" alt="<?= $e($p['name']) ?> bouquet">
                <?php if (!empty($p['badge'])): ?>
                    <span class="detail-floating-badge"><?= $e($p['badge']) ?></span>
                <?php endif; ?>
            </div>
            <div class="detail-info">
                <a class="text-link back-link" href="?route=products"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to the collection</a>
                <div class="eyebrow"><span></span> <?= $e(strtoupper($p['type'])) ?> · THE WANWAN COLLECTION</div>
                <h1><?= $e($p['name']) ?></h1>
                <div class="detail-price-row">
                    <p class="price" id="detail-price" data-base-price="<?= (int)$p['price'] ?>">₱<?= number_format($p['price']) ?></p>
                    <span class="detail-stems-pill"><?= $e($p['stems']) ?></span>
                </div>
                <p class="detail-description"><?= $e($p['note']) ?></p>

                <form method="post" class="detail-form">
                    <input type="hidden" name="token" value="<?= $e($_SESSION['flower_csrf']) ?>">
                    <input type="hidden" name="flower_id" value="<?= $id ?>">

                    <div class="size-selector" role="radiogroup" aria-label="Bouquet fullness preview">
                        <span class="selector-label">Arrangement Fullness</span>
                        <div class="size-options">
                            <label class="size-option active">
                                <input type="radio" name="tier" value="classic" checked data-multiplier="1">
                                <strong>Signature</strong>
                                <small>As pictured · ₱<?= number_format($p['price']) ?></small>
                            </label>
                            <label class="size-option">
                                <input type="radio" name="tier" value="lush" data-multiplier="1.35">
                                <strong>Lush (+35% stems)</strong>
                                <small>Extra full · ₱<?= number_format((int)round($p['price'] * 1.35, -1)) ?></small>
                            </label>
                        </div>
                    </div>

                    <label class="gift-note-field">
                        <span>Complimentary Gift Card Message <small>(optional)</small></span>
                        <input type="text" name="gift_note" maxlength="280" value="<?= $e($giftNote) ?>" placeholder="Write a short note for the recipient…">
                    </label>

                    <div class="detail-submit-row">
                        <label class="qty-picker" aria-label="Quantity">
                            <span>Qty</span>
                            <select name="qty">
                                <?php for ($q = 1; $q <= 5; $q++): ?>
                                    <option value="<?= $q ?>"><?= $q ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <button class="button button-grow" type="submit"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> Add to your bag <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></button>
                    </div>
                </form>

                <div class="detail-notes">
                    <div class="detail-note-item"><span><i class="fa-solid fa-spa" aria-hidden="true"></i></span><p><strong>Hand-arranged &amp; wrapped</strong> in signature water-backed linen paper</p></div>
                    <div class="detail-note-item"><span><i class="fa-regular fa-heart" aria-hidden="true"></i></span><p><strong>Keepsake card</strong> handwritten with your personal note</p></div>
                    <div class="detail-note-item"><span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span><p><strong>Pre-delivery photo</strong> shared before your courier departs</p></div>
                </div>
                <p class="fineprint">Photos show the floral mood and color palette. Exact stems depend on daily seasonal availability. Add your flowers to the bag, then continue to checkout for delivery details and payment.</p>
            </div>
        <?php else: ?>
            <div class="empty">
                <span class="giant-flower"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
                <h1>This bloom isn’t here.</h1>
                <p>It may have been a seasonal limited stem. Explore our current arrangements below.</p>
                <a class="button" href="?route=products">Explore the collection <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($p): ?>
    <section class="section related-section">
        <div class="section-heading">
            <div>
                <div class="eyebrow"><span></span> MORE TO FALL IN LOVE WITH</div>
                <h2>You might also <em>adore these.</em></h2>
            </div>
            <a class="text-link" href="?route=products">View all blooms <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
        </div>
        <div class="product-grid">
            <?php
            $relatedCount = 0;
            foreach ($flowers as $relId => $relItem) {
                if ($relId === $id) continue;
                $card($relId, $relItem);
                if (++$relatedCount === 3) break;
            }
            ?>
        </div>
    </section>
    <?php endif; ?>

<?php elseif ($flowerRoute === 'flowers/bag'): ?>
    <section class="page-heading bag-page-heading">
        <div class="eyebrow"><span></span> GOOD FEELINGS, GATHERED</div>
        <h1><?= !empty($_SESSION['user']['id']) ? 'My bag <span class="bag-heading-count">'.(int)$count.'</span>' : 'Your bag of <em>happiness.</em>' ?></h1>
        <p>Your lovely picks, ready when you are.</p>
    </section>

    <section class="section bag">
        <?php
        $total = 0;
        $hasItems = false;
        foreach ($bag as $id => $qty) {
            if (isset($flowers[$id])) {
                $hasItems = true;
                $total += $flowers[$id]['price'] * $qty;
            }
        }
        ?>
        <?php if (!$hasItems): ?>
            <div class="empty">
                <span class="giant-flower"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
                <h2>A little room for lovely.</h2>
                <p>Your bag is waiting for its first bloom. Explore our hand-tied arrangements below.</p>
                <a class="button" href="?route=products">Explore the flowers <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
            </div>
        <?php else: ?>
            <div class="bag-layout">
                <div class="bag-items">
                    <?php foreach ($bag as $id => $qty):
                        if (!isset($flowers[$id])) continue;
                        $p = $flowers[$id];
                    ?>
                        <article class="bag-row">
                            <a href="?route=flowers/detail&amp;id=<?= $id ?>" class="bag-thumb">
                                <img src="<?= $img($p['image'], 280) ?>" alt="<?= $e($p['name']) ?>">
                            </a>
                            <div class="bag-item-info">
                                <span class="bag-item-tag"><?= $e($p['type']) ?></span>
                                <a href="?route=flowers/detail&amp;id=<?= $id ?>"><h3><?= $e($p['name']) ?></h3></a>
                                <p><?= $e($p['stems']) ?></p>
                                <p class="bag-unit-price">₱<?= number_format($p['price']) ?> each</p>
                            </div>
                            <div class="bag-qty-controls" aria-label="Quantity controls for <?= $e($p['name']) ?>">
                                <form method="post">
                                    <input type="hidden" name="token" value="<?= $e($_SESSION['flower_csrf']) ?>">
                                    <input type="hidden" name="flower_id" value="<?= $id ?>">
                                    <input type="hidden" name="action" value="decrease">
                                    <button type="submit" class="qty-btn" aria-label="Decrease quantity of <?= $e($p['name']) ?>"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
                                </form>
                                <form method="post" class="bag-quantity-form">
                                    <input type="hidden" name="token" value="<?= $e($_SESSION['flower_csrf']) ?>">
                                    <input type="hidden" name="flower_id" value="<?= $id ?>">
                                    <input type="hidden" name="action" value="set_quantity">
                                    <input class="qty-value bag-quantity-input" type="number" name="qty" min="1" max="20" step="1" required inputmode="numeric" value="<?= (int)$qty ?>" aria-label="Quantity of <?= $e($p['name']) ?>">
                                    <button type="submit" class="quantity-save" aria-label="Save quantity of <?= $e($p['name']) ?>"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="token" value="<?= $e($_SESSION['flower_csrf']) ?>">
                                    <input type="hidden" name="flower_id" value="<?= $id ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="qty-btn" aria-label="Increase quantity of <?= $e($p['name']) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                                </form>
                            </div>
                            <div class="bag-item-right">
                                <strong>₱<?= number_format($p['price'] * $qty) ?></strong>
                                <form method="post">
                                    <input type="hidden" name="token" value="<?= $e($_SESSION['flower_csrf']) ?>">
                                    <input type="hidden" name="flower_id" value="<?= $id ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="remove bag-remove" aria-label="Remove <?= $e($p['name']) ?> from bag" title="Remove from bag"><i class="fa-regular fa-trash-can" aria-hidden="true"></i><span>Remove</span></button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="bag-total">
                    <div class="eyebrow"><span></span> SELECTION SUMMARY</div>
                    <h2>Subtotal <em>₱<?= number_format($total) ?></em></h2>
                    <?php if ($giftNote !== ''): ?>
                        <div class="bag-gift-preview">
                            <small><i class="fa-regular fa-envelope" aria-hidden="true"></i> COMPLIMENTARY CARD NOTE</small>
                            <p>“<?= $e($giftNote) ?>”</p>
                        </div>
                    <?php endif; ?>
                    <ul class="bag-perks">
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Includes linen wrapping &amp; hydration pack</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Complimentary handwritten message card</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Delivery fee quoted by recipient location</li>
                    </ul>
                    <a class="button button-full" href="?route=flowers/checkout">Continue to checkout <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></a>
                    <a class="text-link bag-continue" href="?route=products"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Continue browsing blooms</a>
                    <p class="fineprint">Enter your delivery details, review the final total, and choose cash on delivery or QR Ph.</p>
                </aside>
            </div>
        <?php endif; ?>
    </section>

<?php elseif ($flowerRoute === 'about'): ?>
    <section class="page-heading">
        <div class="eyebrow"><span></span> HELLO, WE’RE WANWAN</div>
        <h1>Rooted in<br><em>a little love.</em></h1>
        <p>Flowers for the moments that make us human.</p>
    </section>

    <section class="story-block section">
        <div class="story-visual">
            <img src="<?= $img($heroFlower['image'], 900) ?>" alt="Seasonal flowers thoughtfully arranged">
            <div class="story-badge-card">
                <strong>Est. with Heart</strong>
                <span>Every stem selected by hand each morning.</span>
            </div>
        </div>
        <div class="story-content">
            <div class="eyebrow"><span></span> OUR WAY OF BLOOMING</div>
            <h2>Small gestures.<br><em>Beautiful feelings.</em></h2>
            <p>A birthday remembered. A thank-you that means a little more. A quiet reminder that someone is thinking of you. Wanwan is built around these everyday moments of connection.</p>
            <p>Our style is soft, expressive, and a little playful. We bring together garden-style seasonal flowers, airy textures, and thoughtful details to make every arrangement feel personal.</p>
            <a class="button" href="?route=products">Meet the flowers <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></a>
        </div>
    </section>

    <section class="studio-stats section">
        <div class="stat-card">
            <strong>100%</strong>
            <span>Hand-tied to order in small daily batches</span>
        </div>
        <div class="stat-card">
            <strong>14–24</strong>
            <span>Freshly conditioned stems in every signature bouquet</span>
        </div>
        <div class="stat-card">
            <strong><?= $trust['rating'] !== null ? $e(number_format($trust['rating'], 1)) : 'No ratings yet' ?><?php if ($trust['rating'] !== null): ?> <i class="fa-solid fa-star" aria-hidden="true"></i><?php endif; ?></strong>
            <span><?= $trust['reviews'] > 0 ? $e('From ' . number_format($trust['reviews']) . ' approved verified-order reviews') : 'Customer ratings will appear once approved' ?></span>
        </div>
    </section>

    <section class="values section">
        <article class="value-card">
            <span>01 / INTENTION</span>
            <h3>Picked with intention</h3>
            <p>Seasonal stems chosen for their movement, color harmony, fragrance, and natural garden character.</p>
        </article>
        <article class="value-card">
            <span>02 / CRAFT</span>
            <h3>Made by hand</h3>
            <p>Each bouquet is spiraled by hand, hydrated for travel, and dressed in textured linen paper and ribbon.</p>
        </article>
        <article class="value-card">
            <span>03 / CONNECTION</span>
            <h3>Given with feeling</h3>
            <p>A thoughtful gesture accompanied by your personal words on our signature keepsake card.</p>
        </article>
    </section>

<?php elseif ($flowerRoute === 'branches'): ?>
    <section class="page-heading">
        <div class="eyebrow"><span></span> FROM OUR HANDS TO THEIR DOOR</div>
        <h1>Happiness,<br><em>on its way.</em></h1>
        <p>A little care at every step of the journey.</p>
    </section>

    <section class="section delivery-zones">
        <div class="zone-card">
            <span class="zone-tag">Same-Day &amp; Next-Day</span>
            <h3>Metro Studio Dispatch</h3>
            <p>Carefully packaged upright in water-backed wraps so petals arrive perky and camera-ready.</p>
        </div>
        <div class="zone-card">
            <span class="zone-tag">Morning &amp; Afternoon Windows</span>
            <h3>Flexible Time Slots</h3>
            <p>Choose a morning (9 AM – 1 PM) or afternoon (1 PM – 6 PM) window when confirming your order.</p>
        </div>
        <div class="zone-card">
            <span class="zone-tag">Studio Pick-Up Available</span>
            <h3>Pre-Arranged Collection</h3>
            <p>Prefer to hand-deliver the blooms yourself? Coordinate a convenient pick-up time with our florists.</p>
        </div>
    </section>

    <section class="section info-layout">
        <div>
            <div class="eyebrow"><span></span> DELIVERY CONCIERGE</div>
            <h2>Let’s plan your<br><em>flower delivery.</em></h2>
            <p>Tell us the destination and your preferred date. We’ll confirm service coverage, available delivery windows, and the exact courier fee before you place an order.</p>
            <div class="contact-note">
                <i class="fa-solid fa-seedling" aria-hidden="true"></i> <strong>Care Tip for Longer-Lasting Blooms</strong>
                <p>Trim 1–2 cm off the stems at a 45° angle, place in cool clean water, and refresh every two days away from direct sunlight.</p>
            </div>
            <a class="button" href="?route=contact">Check delivery availability <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></a>
        </div>

        <div class="faqs">
            <details open>
                <summary>Where do you deliver?</summary>
                <p>Delivery coverage depends on the destination neighborhood. Send us the recipient’s city or area so we can check courier availability and quote the exact fee.</p>
            </details>
            <details>
                <summary>Can I arrange same-day flowers?</summary>
                <p>Same-day arrangements depend on available morning stems and studio capacity. Please contact us early in the day to check what’s fresh on our flower table.</p>
            </details>
            <details>
                <summary>Can I choose a specific delivery time?</summary>
                <p>Share your preferred date and time window when enquiring. We will confirm a suitable slot before finalizing your order.</p>
            </details>
            <details>
                <summary>Can I include a gift message?</summary>
                <p>Of course! Every bouquet includes a complimentary handwritten keepsake card. You can draft your note on any bouquet page or in our contact form.</p>
            </details>
            <details>
                <summary>How should I care for my flowers?</summary>
                <p>Use a clean vase, trim the stems at an angle, and refresh the water regularly. Keep blooms in a cool room away from direct sun, heat, and ripening fruit.</p>
            </details>
        </div>
    </section>

<?php elseif ($flowerRoute === 'contact'): ?>
    <section class="page-heading">
        <div class="eyebrow"><span></span> A LOVELY CONVERSATION STARTS HERE</div>
        <h1>Let’s make<br><em>something lovely.</em></h1>
        <p>A question, a special occasion, or a custom bouquet on your mind?</p>
    </section>

    <section class="section info-layout">
        <div>
            <div class="eyebrow"><span></span> FLORAL CONCIERGE</div>
            <h2>A little detail.<br><em>A lot of possibility.</em></h2>
            <p>Put your ideas together using the planner and copy your ready-to-send enquiry to share with our studio through Instagram, Viber, or email.</p>

            <div class="contact-channels">
                <div class="channel-card">
                    <span><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
                    <div>
                        <strong>Studio Hours</strong>
                        <p>Monday – Sunday · 8:00 AM – 6:00 PM</p>
                    </div>
                </div>
                <div class="channel-card">
                    <span><i class="fa-regular fa-envelope" aria-hidden="true"></i></span>
                    <div>
                        <strong>What We Need</strong>
                        <p>Preferred bouquet or palette, target date, delivery city, and card message.</p>
                    </div>
                </div>
            </div>

            <div class="contact-note">
                <i class="fa-solid fa-seedling" aria-hidden="true"></i> <strong>Planning a wedding or intimate event?</strong>
                <p>Include your venue, date, color story, and estimated stem count so our head florist can prepare a tailored proposal.</p>
            </div>
            <p class="fineprint">Online enquiry delivery is prepared locally in your browser so you can paste it directly into your preferred messaging app.</p>
        </div>

        <form id="enquiry-form" class="contact-form">
            <div class="form-row-2">
                <label>Your name
                    <input name="name" required maxlength="100" autocomplete="name" placeholder="e.g. Clara Santos">
                </label>
                <label>Email or contact number
                    <input name="email" required type="text" maxlength="200" autocomplete="email" placeholder="you@example.com or 0917…">
                </label>
            </div>
            <div class="form-row-2">
                <label>What’s the occasion?
                    <select name="occasion">
                        <option>A bouquet enquiry</option>
                        <option>Birthday flowers</option>
                        <option>Love &amp; anniversaries</option>
                        <option>Just because</option>
                        <option>Wedding or event</option>
                        <option>Something else</option>
                    </select>
                </label>
                <label>Preferred date
                    <input name="date" type="date">
                </label>
            </div>
            <label>Tell us a little more
                <textarea name="message" required maxlength="3000" rows="4" placeholder="Which bouquet caught your eye, delivery city/area, and any card message…"><?php
                if (!empty($bag)) {
                    $lines = [];
                    foreach ($bag as $bId => $bQty) {
                        if (isset($flowers[$bId])) {
                            $lines[] = $bQty . 'x ' . $flowers[$bId]['name'] . ' (₱' . number_format($flowers[$bId]['price'] * $bQty) . ')';
                        }
                    }
                    if ($lines) {
                        echo $e("Selected bouquets:\n- " . implode("\n- ", $lines) . ($giftNote !== '' ? "\n\nCard note: \"" . $giftNote . "\"" : '') . "\n\nDelivery area: ");
                    }
                }
                ?></textarea>
            </label>
            <button class="button button-full" type="submit">Prepare your enquiry <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></button>
            <div id="enquiry-result" class="enquiry-result" role="status" hidden>
                <label>Your formatted studio message
                    <textarea id="enquiry-output" rows="7" readonly></textarea>
                </label>
                <div class="enquiry-actions">
                    <button class="button" type="button" id="copy-enquiry">Copy message <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></button>
                    <p id="copy-status"></p>
                </div>
            </div>
        </form>
    </section>
<?php endif; ?>

    <section class="closing">
        <div class="closing-inner">
            <span class="closing-flower" aria-hidden="true"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
            <div class="eyebrow"><span></span> READY TO MAKE SOMEONE SMILE?</div>
            <h2>A little flower.<br><em>A lovely kind of day.</em></h2>
            <p>Hand-tied seasonal bouquets crafted with heart and delivered with your words.</p>
            <div class="hero-cta-group closing-ctas">
                <a href="?route=products" class="button">Send some happiness <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></a>
                <a href="?route=contact" class="button button-ghost">Ask a florist</a>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="footer-top">
        <div class="footer-brand-col">
            <a class="brand" href="?route=home">
                <span class="brand-flower" aria-hidden="true"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
                <span>wanwan<small>FLOWERS &amp; LITTLE JOYS</small></span>
            </a>
            <p>Thoughtful, small-batch blooms crafted for beautifully ordinary days and unforgettable milestones.</p>
        </div>
        <div>
            <h4>Collection</h4>
            <a href="?route=products">All Arrangements</a>
            <a href="?route=products&amp;occasion=Romance">Love &amp; Romance</a>
            <a href="?route=products&amp;occasion=Birthday">Birthday Blooms</a>
            <a href="?route=products&amp;occasion=Just%20because">Just Because</a>
        </div>
        <div>
            <h4>Studio</h4>
            <a href="?route=about">Our Story</a>
            <a href="?route=branches">Delivery &amp; Care Guide</a>
            <a href="?route=contact">Bespoke &amp; Events</a>
            <a href="?route=flowers/bag">Your Bag (<?= $count ?>)</a>
        </div>
        <div>
            <h4>Studio Note</h4>
            <p class="footer-note">Every bouquet is arranged fresh to order and includes a complimentary keepsake message card.</p>
            <a class="text-link" href="?route=contact">Plan a delivery <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© <?= date('Y') ?> Wanwan Flowers. Handcrafted with a little love.</span>
        <span>Bloom a little. Feel a lot. <i class="fa-regular fa-heart" aria-hidden="true"></i></span>
    </div>
</footer>
<?php
$globalToastMessages=[];
$toastNotice=$customerNotice??get_flash();
if ($toastNotice) $globalToastMessages[]=$toastNotice;
if (!empty($customerError)) $globalToastMessages[]=['type'=>'danger','message'=>$customerError];
require __DIR__.'/../partials/global_toasts.php';
?>
<?php if (!empty($_SESSION['user']['id'])): ?>
<?php require __DIR__.'/../partials/customer_signout_modal.php'; ?>
<script src="assets/js/customer-signout.js?v=<?= filemtime(__DIR__.'/../../assets/js/customer-signout.js') ?>" defer></script>
<?php endif; ?>
<script src="assets/js/flowers.js?v=<?= @filemtime(__DIR__ . '/../../assets/js/flowers.js') ?: 2 ?>" defer></script>
</body>
</html>
