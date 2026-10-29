<?php
global $conn;
$escape = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$flash = get_flash();
$user_db = isset($_SESSION['user']['id']) ? User::find_by_id($_SESSION['user']['id']) : [];
$current_email = $user_db['email'] ?? ($_SESSION['user']['email'] ?? '');
$current_avatar = $_SESSION['user']['avatar_url'] ?? ($user_db['avatar_url'] ?? '');

require_once __DIR__ . '/../../models/FlowerCatalog.php';
$authFlowers = FlowerCatalog::products($conn);
$authFlower = reset($authFlowers);
$authImage = FlowerCatalog::image($authFlower['image'] ?? '');

$db_branches = [];
$branches_query = $conn->query("SELECT name FROM branches WHERE status = 'Active' ORDER BY name ASC");
if ($branches_query) {
    while ($row = $branches_query->fetch_assoc()) {
        $db_branches[] = $row['name'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f8e8ee">
<meta name="referrer" content="no-referrer">
<?php require __DIR__ . '/../partials/site_favicon.php'; ?>
<title>Complete your profile | Wanwan Flowers</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/vendor/fontawesome/css/all.min.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/flowers.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/flowers.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/flower-auth.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/flower-auth.css') ?>">
</head>
<body class="flower-auth is-setup-profile">
<a class="skip" href="#main">Skip to form</a>

<header class="auth-header site-header">
    <a class="brand" href="<?= url('?route=home') ?>">
        <span class="brand-flower"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
        <span>wanwan<small>FLOWERS &amp; LITTLE JOYS</small></span>
    </a>
    <div class="auth-header-right">
        <a class="auth-back" href="<?= url('?route=home') ?>">
            <span>Skip for now</span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</header>

<main id="main" class="auth-stage">
    <div class="auth-layout auth-layout-wide">
        <aside class="auth-art" aria-label="Wanwan flower studio">
            <div class="auth-image-wrap">
                <img src="<?= $authImage ?>" alt="<?= $escape($authFlower['name'] ?? 'Wanwan floral arrangement') ?>" width="600" height="650">
                <div class="auth-art-top">
                    <span class="auth-studio-pill">
                        <i class="fa-solid fa-spa" aria-hidden="true"></i>
                        <span>MEMBER PROFILE</span>
                    </span>
                    <div class="auth-seal">
                        <i class="fa-regular fa-heart" aria-hidden="true"></i>
                        <span>WELCOME</span>
                    </div>
                </div>
                <div class="auth-art-overlay">
                    <p class="auth-art-kicker">YOUR PERSONAL FLORAL CONCIERGE</p>
                    <h2>A few details for <em>effortless gifting.</em></h2>
                    <div class="auth-art-badges">
                        <span><i class="fa-solid fa-truck-fast" aria-hidden="true"></i> Faster checkout</span>
                        <span><i class="fa-solid fa-cake-candles" aria-hidden="true"></i> Birthday reminders</span>
                        <span><i class="fa-solid fa-seedling" aria-hidden="true"></i> Saved studio</span>
                    </div>
                </div>
            </div>
        </aside>

        <section class="auth-panel" aria-labelledby="auth-title">
            <div class="auth-intro">
                <div class="auth-intro-head">
                    <div class="auth-intro-icon"><i class="fa-regular fa-id-badge" aria-hidden="true"></i></div>
                    <div>
                        <p class="eyebrow">ALMOST DONE · STEP 2 OF 2</p>
                        <h1 id="auth-title">Complete your <em>profile.</em></h1>
                    </div>
                </div>
                <p class="auth-subtitle">Save your name and delivery area so we can prepare your future bouquets even faster.</p>
            </div>

            <?php require __DIR__ . '/../partials/auth_feedback.php'; ?>

            <form class="signin-form auth-form" id="profile-setup-form" action="<?= url('?route=setup_profile') ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $escape(csrf_token()) ?>">
                <input type="hidden" name="country" value="Philippines">
                <input type="hidden" name="zip" value="1000">

                <div class="auth-grid-2">
                    <div class="auth-field">
                        <div class="auth-label-row"><label for="firstname">First name *</label></div>
                        <div class="auth-input">
                            <i class="fa-regular fa-user" aria-hidden="true"></i>
                            <input id="firstname" name="firstname" type="text" required maxlength="80" placeholder="Clara" value="<?= $escape($user_db['firstname'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="auth-field">
                        <div class="auth-label-row"><label for="lastname">Last name *</label></div>
                        <div class="auth-input">
                            <i class="fa-regular fa-user" aria-hidden="true"></i>
                            <input id="lastname" name="lastname" type="text" required maxlength="80" placeholder="Santos" value="<?= $escape($user_db['lastname'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="auth-grid-2">
                    <div class="auth-field">
                        <div class="auth-label-row">
                            <label for="middlename">Middle name</label>
                            <span class="auth-hint-badge">Optional</span>
                        </div>
                        <div class="auth-input">
                            <i class="fa-regular fa-user" aria-hidden="true"></i>
                            <input id="middlename" name="middlename" type="text" maxlength="80" placeholder="Reyes" value="<?= $escape($user_db['middlename'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="auth-field">
                        <div class="auth-label-row">
                            <label for="birthdate">Birthday</label>
                            <span class="auth-hint-badge">Optional</span>
                        </div>
                        <div class="auth-input">
                            <i class="fa-solid fa-cake-candles" aria-hidden="true"></i>
                            <input id="birthdate" name="birthdate" type="date" value="<?= $escape($user_db['birthdate'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="auth-grid-2">
                    <div class="auth-field">
                        <div class="auth-label-row"><label for="email">Email address *</label></div>
                        <div class="auth-input">
                            <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                            <input id="email" name="email" type="email" required maxlength="254" placeholder="you@example.com" value="<?= $escape($current_email) ?>">
                        </div>
                    </div>
                    <div class="auth-field">
                        <div class="auth-label-row">
                            <label for="profile_image">Profile photo</label>
                            <span class="auth-hint-badge">Optional</span>
                        </div>
                        <div class="auth-input">
                            <i class="fa-regular fa-image" aria-hidden="true"></i>
                            <input id="profile_image" name="profile_image" type="file" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="auth-grid-2">
                    <div class="auth-field">
                        <div class="auth-label-row">
                            <label for="city">City / Municipality</label>
                            <span class="auth-hint-badge">Delivery area</span>
                        </div>
                        <div class="auth-input">
                            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                            <input id="city" name="city" type="text" maxlength="100" placeholder="e.g. Quezon City, Makati" value="<?= $escape($user_db['city'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="auth-field">
                        <div class="auth-label-row">
                            <label for="store">Preferred studio branch</label>
                        </div>
                        <div class="auth-input">
                            <i class="fa-solid fa-store" aria-hidden="true"></i>
                            <select id="store" name="store" class="auth-select">
                                <?php if (!empty($db_branches)): ?>
                                    <?php foreach ($db_branches as $bName): ?>
                                        <option value="<?= $escape($bName) ?>"><?= $escape($bName) ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="Wanwan Main Studio">Wanwan Main Studio</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="auth-field">
                    <div class="auth-label-row">
                        <label for="address">Street address &amp; delivery landmarks</label>
                        <span class="auth-hint-badge">Optional</span>
                    </div>
                    <div class="auth-input">
                        <i class="fa-regular fa-map" aria-hidden="true"></i>
                        <input id="address" name="address" type="text" maxlength="255" placeholder="House/Unit No., Street, Barangay" value="<?= $escape($user_db['address'] ?? '') ?>">
                    </div>
                </div>

                <button class="button auth-submit" id="submitBtn" type="submit">
                    <span class="signin-button-label">Save profile &amp; explore blooms</span>
                    <span class="auth-submit-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
                </button>
            </form>

            <div class="auth-bottom-meta">
                <p class="auth-switch">Want to browse first? <a href="<?= url('?route=home') ?>">Skip to homepage →</a></p>
                <div class="auth-security"><i class="fa-solid fa-shield-heart" aria-hidden="true"></i><span>Private delivery details</span></div>
            </div>
        </section>
    </div>
</main>

<footer class="auth-footer site-footer">
    <span>© <?= date('Y') ?> Wanwan Flowers</span>
    <span>A little flower. A lovely kind of day. <i class="fa-regular fa-heart" aria-hidden="true"></i></span>
</footer>
<script src="<?= url('assets/js/auth-forms.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/auth-forms.js') ?>" defer></script>
</body>
</html>
