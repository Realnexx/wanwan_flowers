<?php
global $conn;
$authMode = $authMode ?? 'login';
$isRegister = $authMode === 'register';
$isForgot = $authMode === 'forgot_password';
$recoverySent = $isForgot && isset($_GET['sent']) && !empty($_SESSION['recovery_notice']);
$isReset = $authMode === 'reset_password';
$isTwoFactor = $authMode === 'two_factor';
$escape = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$flash = get_flash();
require_once __DIR__ . '/../../models/FlowerCatalog.php';
$authFlowers = FlowerCatalog::products($conn);
$authFlower = reset($authFlowers);
$authImage = FlowerCatalog::image($authFlower['image'] ?? '');

if ($isReset) {
    $token = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';
    $validReset = AuthSecurity::valid_reset($token);
}

$pageTitles = [
    'login' => 'Welcome back',
    'register' => 'Create your account',
    'forgot_password' => 'Forgot password',
    'reset_password' => 'Reset password',
    'two_factor' => 'Verify your sign-in',
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f8e8ee">
<meta name="referrer" content="no-referrer">
<?php require __DIR__ . '/site_favicon.php'; ?>
<title><?= $escape($pageTitles[$authMode] ?? 'Account') ?> | Wanwan Flowers</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/vendor/fontawesome/css/all.min.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/flowers.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/flowers.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/flower-auth.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/flower-auth.css') ?>">
</head>
<body class="flower-auth is-<?= $escape($authMode) ?>">
<a class="skip" href="#main">Skip to form</a>

<header class="auth-header site-header">
    <a class="brand" href="<?= url('?route=home') ?>">
        <span class="brand-flower"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
        <span>wanwan<small>FLOWERS &amp; LITTLE JOYS</small></span>
    </a>
    <div class="auth-header-right">
        <a class="auth-back" href="<?= url('?route=products') ?>">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            <span>Back to blooms</span>
        </a>
    </div>
</header>

<main id="main" class="auth-stage">
    <div class="auth-layout">
        <aside class="auth-art" aria-label="Wanwan flower studio">
            <div class="auth-image-wrap">
                <img src="<?= $authImage ?>" alt="<?= $escape($authFlower['name'] ?? 'Wanwan floral arrangement') ?>" width="600" height="650">
                <div class="auth-art-top">
                    <span class="auth-studio-pill">
                        <i class="fa-solid fa-spa" aria-hidden="true"></i>
                        <span>WANWAN STUDIO</span>
                    </span>
                    <div class="auth-seal">
                        <i class="fa-regular fa-heart" aria-hidden="true"></i>
                        <span>HAND-TIED</span>
                    </div>
                </div>
                <div class="auth-art-overlay">
                    <p class="auth-art-kicker">
                        <?php if ($isRegister): ?>JOIN OUR FLORAL COMMUNITY
                        <?php elseif ($isForgot || $isReset): ?>SAFE &amp; GENTLE RECOVERY
                        <?php elseif ($isTwoFactor): ?>PROTECTED ACCOUNT ACCESS
                        <?php else: ?>A LITTLE LOVE, IN FULL BLOOM<?php endif; ?>
                    </p>
                    <h2>
                        <?php if ($isRegister): ?>Your next lovely moment <em>starts here.</em>
                        <?php elseif ($isForgot || $isReset): ?>Let’s get you back to <em>the blooms.</em>
                        <?php elseif ($isTwoFactor): ?>Keeping your corner <em>safe &amp; sound.</em>
                        <?php else: ?>Good to see you <em>bloom again.</em><?php endif; ?>
                    </h2>
                    <div class="auth-art-badges">
                        <span><i class="fa-solid fa-seedling" aria-hidden="true"></i> Seasonal stems</span>
                        <span><i class="fa-regular fa-envelope" aria-hidden="true"></i> Keepsake notes</span>
                        <span><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Photo proof</span>
                    </div>
                </div>
            </div>
        </aside>

        <section class="auth-panel" aria-labelledby="auth-title">
            <div class="auth-top-bar">
                <?php if ($isForgot || $isReset || $isTwoFactor): ?>
                    <a class="auth-back-inline" href="<?= url($isTwoFactor ? '?route=login&restart=1' : '?route=login') ?>">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        <span><?= $isTwoFactor ? 'Start sign-in again' : 'Back to sign in' ?></span>
                    </a>
                <?php else: ?>
                    <div class="auth-tabs" aria-label="Account options">
                        <a href="<?= url('?route=login') ?>" <?= !$isRegister ? 'aria-current="page"' : '' ?>>
                            <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
                            <span>Sign in</span>
                        </a>
                        <a href="<?= url('?route=register') ?>" <?= $isRegister ? 'aria-current="page"' : '' ?>>
                            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                            <span>Create account</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($isForgot): ?>
                <?php if ($recoverySent): ?>
                <div class="recovery-confirmation" role="status">
                    <div class="recovery-envelope"><i class="fa-regular fa-envelope-open" aria-hidden="true"></i><span><i class="fa-solid fa-check" aria-hidden="true"></i></span></div>
                    <p class="eyebrow">A LITTLE HELP, ON ITS WAY</p>
                    <h1 id="auth-title">Check your <em>inbox.</em></h1>
                    <p>If an account uses <strong><?= $escape($_SESSION['recovery_notice']['email']) ?></strong>, you’ll receive a link to reset your password.</p>
                    <div class="recovery-tips"><div><i class="fa-regular fa-clock" aria-hidden="true"></i><span><strong>A fresh start, for 30 minutes</strong><small>Your reset link expires and can only be used once.</small></span></div><div><i class="fa-regular fa-folder-open" aria-hidden="true"></i><span><strong>Not seeing it yet?</strong><small>Allow a few minutes and check your spam or junk folder.</small></span></div></div>
                    <a class="button auth-submit" href="<?= url('?route=login') ?>">Back to sign in <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a class="recovery-secondary" href="<?= url('?route=forgot_password&new=1') ?>">Try another email or request a new link</a>
                </div>
                <?php else: ?>
                <div class="auth-intro">
                    <div class="auth-intro-head">
                        <div class="auth-intro-icon"><i class="fa-regular fa-envelope" aria-hidden="true"></i></div>
                        <div>
                            <p class="eyebrow">LET’S GET YOU BACK IN</p>
                            <h1 id="auth-title">Forgot your <em>password?</em></h1>
                        </div>
                    </div>
                    <p class="auth-subtitle">Enter the email saved on your account. We’ll send a single-use link to choose a new password.</p>
                </div>

                <?php require __DIR__ . '/auth_feedback.php'; ?>

                <form class="signin-form auth-form" id="forgotForm" action="<?= url('?route=forgot_password') ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $escape(csrf_token()) ?>">
                    <div class="auth-field">
                        <div class="auth-label-row">
                            <label for="email">Email address</label>
                            <span class="auth-hint-badge">Expires in 30m</span>
                        </div>
                        <div class="auth-input">
                            <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                            <input id="email" name="email" type="email" autocomplete="email" required maxlength="254" placeholder="you@example.com" value="<?= $escape(AuthSecurity::input('email')) ?>">
                        </div>
                    </div>

                    <button class="button auth-submit" id="submitBtn" type="submit">
                        <span class="signin-button-label">Send reset link</span>
                        <span class="auth-submit-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
                    </button>
                </form>

                <div class="auth-bottom-meta">
                    <p class="auth-switch">Remembered it? <a href="<?= url('?route=login') ?>">Sign in →</a></p>
                    <div class="auth-security"><i class="fa-regular fa-clock" aria-hidden="true"></i><span>Reset links expire in 30 minutes.</span></div>
                </div>
                <?php endif; ?>

            <?php elseif ($isReset): ?>
                <div class="auth-intro">
                    <div class="auth-intro-head">
                        <div class="auth-intro-icon recovery-key-icon"><i class="fa-solid fa-key" aria-hidden="true"></i></div>
                        <div>
                            <p class="eyebrow">A FRESH START</p>
                            <h1 id="auth-title">Choose a new <em>password.</em></h1>
                        </div>
                    </div>
                    <p class="auth-subtitle"><?= $validReset ? 'Choose a unique password for your account. You’ll sign in again after saving it.' : 'This link is invalid, expired, or already used. Request a new link to continue.' ?></p>
                </div>

                <?php require __DIR__ . '/auth_feedback.php'; ?>

                <?php if ($validReset): ?>
                    <form class="signin-form auth-form" id="resetForm" action="<?= $escape(url('?route=reset_password&token=' . rawurlencode($token))) ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?= $escape(csrf_token()) ?>">
                        <div class="auth-grid-2">
                            <div class="auth-field">
                                <div class="auth-label-row">
                                    <label for="password">New password</label>
                                    <span class="auth-hint-badge" id="password-help">Use at least 12 characters</span>
                                </div>
                                <div class="auth-input">
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12" maxlength="72" aria-describedby="password-help" placeholder="Min. 12 characters">
                                    <button type="button" id="togglePassword" data-password-toggle="password" aria-controls="password" aria-pressed="false" aria-label="Show password">Show</button>
                                </div>
                            </div>
                            <div class="auth-field">
                                <div class="auth-label-row">
                                    <label for="confirm_password">Confirm new password</label>
                                </div>
                                <div class="auth-input">
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required minlength="12" maxlength="72" placeholder="Repeat new password">
                                    <button type="button" data-password-toggle="confirm_password" aria-controls="confirm_password" aria-pressed="false" aria-label="Show password confirmation">Show</button>
                                </div>
                            </div>
                        </div>

                        <div class="auth-Reqs" aria-live="polite">
                            <span class="req-pill" data-req="length"><i class="fa-regular fa-circle" aria-hidden="true"></i> 12+ char passphrase</span>
                            <span class="req-pill" data-req="match"><i class="fa-regular fa-circle" aria-hidden="true"></i> Passwords match</span>
                        </div>

                        <button class="button auth-submit" id="submitBtn" type="submit">
                            <span class="signin-button-label">Save new password</span>
                            <span class="auth-submit-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="recovery-expired"><i class="fa-regular fa-hourglass-half" aria-hidden="true"></i><div><strong>A new link is all you need.</strong><p>For your security, older or previously used links cannot be reopened. Request a fresh one below.</p></div></div>
                    <a class="button auth-submit" href="<?= url('?route=forgot_password') ?>">
                        <span class="signin-button-label">Get a new reset link</span>
                        <span class="auth-submit-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
                    </a>
                <?php endif; ?>

                <div class="auth-bottom-meta">
                    <p class="auth-switch">Need help? <a href="<?= url('?route=login') ?>">Return to sign in →</a></p>
                    <div class="auth-security"><i class="fa-solid fa-shield-heart" aria-hidden="true"></i><span>Single-use recovery token</span></div>
                </div>

            <?php elseif ($isTwoFactor): ?>
                <div class="auth-intro">
                    <div class="auth-intro-head">
                        <div class="auth-intro-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div>
                        <div>
                            <p class="eyebrow">TWO-STEP VERIFICATION</p>
                            <h1 id="auth-title"><?= !empty($pending['verified']) ? 'Save recovery <em>codes.</em>' : (!empty($pending['secret']) ? 'Set up <em>authenticator.</em>' : 'Verify your <em>sign-in.</em>') ?></h1>
                        </div>
                    </div>
                    <?php if (!empty($pending['verified'])): ?>
                        <p class="auth-subtitle">Store these codes somewhere private. Each works once if you lose your authenticator. They will not be shown again.</p>
                    <?php elseif (!empty($pending['secret'])): ?>
                        <p class="auth-subtitle">In your authenticator app, add a time-based account using this setup key, then enter its 6-digit code below.</p>
                    <?php else: ?>
                        <p class="auth-subtitle">Enter the 6-digit code from your authenticator app, or one unused recovery code.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($factorError)): ?>
                    <div class="signin-feedback is-error" role="alert">
                        <span><?= $escape($factorError) ?></span>
                        <button type="button" aria-label="Dismiss notification">×</button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pending['verified'])): ?>
                    <ul class="auth-recovery-codes">
                        <?php foreach ($pending['recovery'] as $code): ?>
                            <li><code><?= $escape($code) ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                <?php elseif (!empty($pending['secret'])): ?>
                    <div class="auth-secret-box">
                        <small><?= $escape($user['email'] ?? '') ?></small>
                        <code><?= $escape($pending['secret']) ?></code>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= url('?route=login&mfa=1') ?>" class="signin-form auth-form">
                    <input type="hidden" name="csrf_token" value="<?= $escape(csrf_token()) ?>">
                    <?php if (empty($pending['verified'])): ?>
                        <div class="auth-field">
                            <div class="auth-label-row">
                                <label for="factor-code">Verification or recovery code</label>
                            </div>
                            <div class="auth-input">
                                <i class="fa-solid fa-key" aria-hidden="true"></i>
                                <input id="factor-code" name="code" autocomplete="one-time-code" maxlength="16" placeholder="6-digit code or recovery code" autofocus required>
                            </div>
                        </div>
                    <?php endif; ?>
                    <button class="button auth-submit" id="submitBtn" type="submit">
                        <span class="signin-button-label"><?= !empty($pending['verified']) ? 'I saved my codes — continue' : 'Verify code' ?></span>
                        <span class="auth-submit-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
                    </button>
                </form>

                <div class="auth-bottom-meta">
                    <p class="auth-switch"><a href="<?= url('?route=login&restart=1') ?>">Start sign-in again →</a></p>
                    <div class="auth-security"><i class="fa-solid fa-shield-heart" aria-hidden="true"></i><span>Expires in 10 minutes</span></div>
                </div>

            <?php else: ?>
                <div class="auth-intro">
                    <div class="auth-intro-head">
                        <div class="auth-intro-icon">
                            <i class="fa-<?= $isRegister ? 'solid fa-seedling' : 'regular fa-user' ?>" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p class="eyebrow"><?= $isRegister ? 'NEW MEMBER REGISTRATION' : 'WELCOME BACK TO WANWAN' ?></p>
                            <h1 id="auth-title"><?= $isRegister ? 'Let’s grow <em>something lovely.</em>' : 'Welcome <em>back.</em>' ?></h1>
                        </div>
                    </div>
                    <p class="auth-subtitle"><?= $isRegister ? 'Set up your account in seconds to save favorite blooms and track orders.' : 'Sign in with your username or email to access your account.' ?></p>
                </div>

                <?php require __DIR__ . '/auth_feedback.php'; ?>

                <form class="signin-form auth-form" id="<?= $isRegister ? 'registerForm' : 'loginForm' ?>" action="<?= url('?route=' . $authMode) ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $escape(csrf_token()) ?>">

                    <?php if ($isRegister): ?>
                        <div class="auth-grid-2">
                            <div class="auth-field">
                                <div class="auth-label-row">
                                    <label for="username">Username</label>
                                    <span class="auth-hint-badge" id="username-help">3–40 chars</span>
                                </div>
                                <div class="auth-input">
                                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                                    <input id="username" name="username" type="text" required autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="40" pattern="[A-Za-z0-9_.\-]{3,40}" aria-describedby="username-help" placeholder="e.g. clara_blooms" value="<?= $escape(AuthSecurity::input('username')) ?>">
                                </div>
                            </div>

                            <div class="auth-field">
                                <div class="auth-label-row">
                                    <label for="email">Email address</label>
                                </div>
                                <div class="auth-input">
                                    <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                                    <input id="email" name="email" type="email" autocomplete="email" required maxlength="254" placeholder="you@example.com" value="<?= $escape(AuthSecurity::input('email')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="auth-grid-2">
                            <div class="auth-field">
                                <div class="auth-label-row">
                                    <label for="password">Password</label>
                                    <span class="auth-hint-badge" id="password-help">12+ chars</span>
                                </div>
                                <div class="auth-input">
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    <input id="password" name="password" type="password" required autocomplete="new-password" minlength="12" maxlength="72" aria-describedby="password-help" placeholder="Min. 12 characters">
                                    <button type="button" id="togglePassword" data-password-toggle="password" aria-controls="password" aria-label="Show password" aria-pressed="false">Show</button>
                                </div>
                            </div>

                            <div class="auth-field">
                                <div class="auth-label-row">
                                    <label for="confirm_password">Confirm password</label>
                                </div>
                                <div class="auth-input">
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required minlength="12" maxlength="72" placeholder="Repeat password">
                                    <button type="button" data-password-toggle="confirm_password" aria-controls="confirm_password" aria-label="Show password confirmation" aria-pressed="false">Show</button>
                                </div>
                            </div>
                        </div>

                        <div class="auth-Reqs" aria-live="polite">
                            <span class="req-pill" data-req="user"><i class="fa-regular fa-circle" aria-hidden="true"></i> Letters, numbers, <code>._-</code></span>
                            <span class="req-pill" data-req="length"><i class="fa-regular fa-circle" aria-hidden="true"></i> 12+ char passphrase</span>
                            <span class="req-pill" data-req="match"><i class="fa-regular fa-circle" aria-hidden="true"></i> Passwords match</span>
                        </div>
                    <?php else: ?>
                        <div class="auth-field">
                            <div class="auth-label-row">
                                <label for="username">Username or email</label>
                            </div>
                            <div class="auth-input">
                                <i class="fa-regular fa-user" aria-hidden="true"></i>
                                <input id="username" name="username" type="text" required autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="254" placeholder="Enter your username or email" value="<?= $escape(AuthSecurity::input('username') ?: AuthSecurity::input('email')) ?>">
                            </div>
                        </div>

                        <div class="auth-field">
                            <div class="auth-label-row">
                                <label for="password">Password</label>
                                <a class="auth-forgot-link" href="<?= url('?route=forgot_password') ?>">Forgot password?</a>
                            </div>
                            <div class="auth-input">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                <input id="password" name="password" type="password" required autocomplete="current-password" maxlength="72" placeholder="Enter your password">
                                <button type="button" id="togglePassword" data-password-toggle="password" aria-controls="password" aria-label="Show password" aria-pressed="false">Show</button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button class="button auth-submit" id="submitBtn" type="submit">
                        <span class="signin-button-label"><?= $isRegister ? 'Create my account' : 'Sign in to Wanwan' ?></span>
                        <span class="auth-submit-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
                    </button>
                </form>

                <div class="auth-bottom-meta">
                    <p class="auth-switch">
                        <?= $isRegister ? 'Already have an account?' : 'New to Wanwan Flowers?' ?>
                        <a href="<?= url('?route=' . ($isRegister ? 'login' : 'register')) ?>"><?= $isRegister ? 'Sign in instead →' : 'Create an account →' ?></a>
                    </p>
                    <div class="auth-security">
                        <i class="fa-solid fa-shield-heart" aria-hidden="true"></i>
                        <span><?= $isRegister ? 'Encrypted &amp; private credentials' : 'Auto-lock after ' . (int)(AuthSecurity::config()['idle_timeout'] / 60) . 'm inactivity' ?></span>
                    </div>
                </div>
            <?php endif; ?>
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
