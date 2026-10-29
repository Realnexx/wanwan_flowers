<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/AuthSecurity.php';

class AuthController
{
    public function login()
    {
        require_guest();
        enforce_csrf();
        require_once __DIR__.'/../models/TwoFactor.php';
        if(isset($_GET['restart']))unset($_SESSION['pending_mfa']);
        if(!empty($_SESSION['pending_mfa']))return $this->factorLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = AuthSecurity::input('username') ?: AuthSecurity::input('email');
            $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
            $policy = AuthSecurity::config();
            $wait = AuthSecurity::consume('login-ip', AuthSecurity::ip(), (int)$policy['login_ip_limit'], (int)$policy['login_window']);
            if ($wait) return $this->auth_error('login', 'Too many sign-in attempts. Please wait ' . (int)ceil($wait / 60) . ' minutes.', 429, $wait);
            if ($username === '' || strlen($username) > 254 || $password === '' || strlen($password) > 72 || strpos($password, "\0") !== false) return $this->auth_error('login', 'Enter a valid username or email and your password.');
            $user = User::find_by_username_or_email($username);
            $identity = $user ? 'user:'.$user['id'] : strtolower($username);
            $wait = AuthSecurity::consume('login-account', $identity, (int)$policy['login_account_limit'], (int)$policy['login_window']);
            if ($wait) return $this->auth_error('login', 'Too many sign-in attempts. Please wait ' . (int)ceil($wait / 60) . ' minutes.', 429, $wait);
            // Verify a real bcrypt hash even for an unknown account.
            $valid = password_verify($password, $user['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi');
            if ($user && $valid) {
                AuthSecurity::clear_limit('login-account', $identity);
                if(TwoFactor::required($user['id'])) {
                    session_regenerate_id(true);
                    $_SESSION['pending_mfa']=['id'=>(int)$user['id'],'expires'=>time()+600,'version'=>AuthSecurity::version($user['id']),'secret'=>TwoFactor::factor($user['id'])?null:TwoFactor::secret()];
                    redirect('?route=login&mfa=1');
                }
                return $this->completeLogin($user);
            } else {
                User::log_activity(null, 'login_failed', 'Failed sign-in attempt.');
                Monitoring::security('login_failed');
                return $this->auth_error('login', 'Invalid username or password.');
            }
        }

        require_once __DIR__ . '/../models/Branch.php';
        require_once __DIR__ . '/../models/Setting.php';
        $store_settings = Setting::get_by_category('general');
        $store_name = $store_settings['store_name'] ?? Setting::get('store_name', 'MultiPOS');
        $store_logo = $store_settings['store_logo'] ?? Setting::get('store_logo', 'assets/img/logo.png');
        require __DIR__ . '/../views/auth/login.php';
    }

    private function completeLogin($user,$factorVerified=false) {
        require_once __DIR__.'/../models/DemoAccount.php';
        $demo=DemoAccount::start((int)$user['id']);
        if($demo['expired']){unset($_SESSION['pending_mfa'],$_SESSION['demo_notice_pending']);return $this->auth_error('login',DemoAccount::EXPIRED_MESSAGE,403);}
        $guestSession=session_id();session_regenerate_id(true);AuthSecurity::establish($user['id']);$_SESSION['auth']['mfa_verified']=$factorVerified;
        unset($_SESSION['pending_mfa'],$_SESSION['pos_branch_id'],$_SESSION['pos_pin_verified'],$_SESSION['pos_cart'],$_SESSION['login_attempts']);
        $context=User::access_context($user['id']);
        $_SESSION['user']=['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'username'=>$user['username']?:explode('@',$user['email'])[0],'role'=>$context['role'],'branch'=>$context['branch'],'branch_id'=>$context['branch_id'],'avatar_url'=>$user['avatar_url']??$user['image']??null];
        if($demo['is_demo'])$_SESSION['demo_notice_pending']=(int)$user['id'];else unset($_SESSION['demo_notice_pending']);
        Monitoring::security('login_success');User::set_status($user['id'],'online');User::log_activity($user['id'],'login','Signed in as '.$context['role']);
        if(!$context['staff']){require_once __DIR__.'/../models/Cart.php';Cart::migrate($guestSession,$user['id']);}
        set_flash('success','You have signed in successfully.');
        if (!$context['staff'] && !empty($_SESSION['flower_checkout_return'])) { unset($_SESSION['flower_checkout_return']); redirect('?route=flowers/checkout'); }
        redirect('?route='.User::landing_route($user['id']));
    }
    private function factorLogin() {
        $pending=&$_SESSION['pending_mfa'];$user=User::find_by_id($pending['id']);$factorError='';
        if(!$user||$pending['expires']<time()||$pending['version']!==AuthSecurity::version($pending['id'])){unset($_SESSION['pending_mfa']);redirect('?route=login');}
        if($_SERVER['REQUEST_METHOD']==='POST') {
            if(!empty($pending['verified']))return $this->completeLogin($user,true);
            try {
                $result=TwoFactor::verify($pending['id'],AuthSecurity::input('code'),$pending['secret']);
                if(!$result){Monitoring::security('mfa_failed',401);$factorError='Invalid or already used code. Check the time on your device and try again.';}
                elseif($result['recovery']){$pending['verified']=true;$pending['recovery']=$result['recovery'];unset($pending['secret']);}
                else return $this->completeLogin($user,true);
            }catch(DomainException $e){$factorError=$e->getMessage();}
        }
        require __DIR__.'/../views/auth/two_factor.php';
    }

    // Keep old links working through the same authentication flow.
    public function customer_login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') return $this->login();
        redirect('?route=login');
    }

    private function auth_error($route, $message, $status = 422, $retry = null) {
        http_response_code($status);
        if ($retry) header('Retry-After: '.(int)$retry);
        set_flash('danger', $message);
        require __DIR__.'/../views/auth/'.$route.'.php';
    }

    public function register() {
        require_guest(); enforce_csrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $wait=AuthSecurity::consume('register-ip',AuthSecurity::ip(),5,3600);
            if($wait)return $this->auth_error('register','Too many registration attempts. Please try again later.',429,$wait);
            $username=AuthSecurity::input('username');$email=strtolower(AuthSecurity::input('email'));
            $password=isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
            $confirm=isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            if(!preg_match('/^[A-Za-z0-9_.-]{3,40}$/D',$username))return $this->auth_error('register','Use 3–40 letters, numbers, dots, dashes, or underscores for your username.');
            if(strlen($email)>254 || !filter_var($email,FILTER_VALIDATE_EMAIL))return $this->auth_error('register','Enter a valid email address.');
            if($error=AuthSecurity::password_error($password))return $this->auth_error('register',$error);
            if($password!==$confirm)return $this->auth_error('register','Your passwords do not match.');
            if(User::find_by_username_or_email($username)||User::find_by_email($email))return $this->auth_error('register','An account already uses that username or email. Sign in or recover your password.');
            global $conn;
            $role=$conn->query("SELECT id FROM roles WHERE LOWER(name)='customer' LIMIT 1")->fetch_assoc();
            if(!$role)return $this->auth_error('register','Registration is temporarily unavailable.',503);
            $user_id=User::create($username,'','','',$username,$email,$password,[(int)$role['id']]);
            if(!$user_id)return $this->auth_error('register','We could not create your account. Please try again.',500);
            $guestSession=session_id();session_regenerate_id(true);AuthSecurity::establish($user_id);
            $_SESSION['user']=['id'=>$user_id,'name'=>$username,'email'=>$email,'username'=>$username,'role'=>'Customer','branch_id'=>null];
            require_once __DIR__.'/../models/Cart.php';Cart::migrate($guestSession,$user_id);
            User::set_status($user_id,'online');User::log_activity($user_id,'register','Customer registered.');
            set_flash('success','Your account is ready. Welcome!');
            if (!empty($_SESSION['flower_checkout_return'])) { unset($_SESSION['flower_checkout_return']); redirect('?route=flowers/checkout'); }
            redirect('?route=setup_profile');
        }
        require __DIR__.'/../views/auth/register.php';
    }

    public function forgot_password() {
        require_guest();enforce_csrf();
        if (isset($_GET['new'])) unset($_SESSION['recovery_notice']);
        if($_SERVER['REQUEST_METHOD']==='POST') {
            $wait=AuthSecurity::consume('forgot-ip',AuthSecurity::ip(),10,3600);
            if($wait)return $this->auth_error('forgot_password','Too many requests. Please try again later.',429,$wait);
            $email=strtolower(AuthSecurity::input('email'));
            if(strlen($email)>254||!filter_var($email,FILTER_VALIDATE_EMAIL))return $this->auth_error('forgot_password','Enter a valid email address.');
            $wait=AuthSecurity::consume('forgot-email',$email,3,900);
            if($wait)return $this->auth_error('forgot_password','Too many requests. Please try again later.',429,$wait);
            $user=User::find_by_email($email);
            if($user) {
                $token=AuthSecurity::issue_reset($user['id']);
                try {
                    require_once __DIR__.'/../services/PasswordMailer.php';require_once __DIR__.'/../models/Setting.php';
                    PasswordMailer::send_reset($email,$token,'Wanwan Flowers');
                } catch(Throwable $e) { AuthSecurity::revoke_reset($token);error_log('Password recovery email could not be delivered. Check SMTP configuration.'); }
            }
            $_SESSION['recovery_notice']=['email'=>$email];
            redirect('?route=forgot_password&sent=1');
        }
        require __DIR__.'/../views/auth/forgot_password.php';
    }

    public function reset_password() {
        require_guest();enforce_csrf();
        header('Referrer-Policy: no-referrer');
        $token=isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';
        if($_SERVER['REQUEST_METHOD']==='POST') {
            $wait=AuthSecurity::consume('reset-ip',AuthSecurity::ip(),10,900);
            if($wait)return $this->auth_error('reset_password','Too many attempts. Please try again later.',429,$wait);
            $password=isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
            $confirm=isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            if($error=AuthSecurity::password_error($password))return $this->auth_error('reset_password',$error);
            if($password!==$confirm)return $this->auth_error('reset_password','Your passwords do not match.');
            if(!AuthSecurity::reset_password($token,$password))return $this->auth_error('reset_password','This reset link has expired or has already been used. Request a new one.');
            session_regenerate_id(true);unset($_SESSION['csrf_token']);
            Monitoring::security('password_reset_success');
            set_flash('success','Your password has been updated. Sign in with your new password.');redirect('?route=login');
        }
        require __DIR__.'/../views/auth/reset_password.php';
    }

    public function setup_profile()
    {
        require_login();
        $user_id = $_SESSION['user']['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            enforce_csrf();
            $firstname = sanitize($_POST['firstname'] ?? '');
            $middlename = sanitize($_POST['middlename'] ?? '');
            $lastname = sanitize($_POST['lastname'] ?? '');
            $extname = sanitize($_POST['extname'] ?? '');
            $real_email = sanitize($_POST['email'] ?? '');

            if (empty($firstname) || empty($lastname) || empty($real_email)) {
                set_flash('danger', 'First name, last name, and email are required to complete your profile.');
                redirect('?route=setup_profile');
            }

            $name_parts = array_filter([$firstname, $middlename, $lastname, $extname]);
            $real_name = implode(' ', $name_parts);

            $birthdate = sanitize($_POST['birthdate'] ?? '');
            $bio = sanitize($_POST['bio'] ?? '');
            $selected_layers = $_POST['layers'] ?? [];
            $layers_str = implode(', ', array_map('sanitize', $selected_layers));
            $address = sanitize($_POST['address'] ?? '');
            $purok = sanitize($_POST['purok'] ?? '');
            $barangay = sanitize($_POST['barangay'] ?? '');
            $town = sanitize($_POST['town'] ?? '');
            $city = sanitize($_POST['city'] ?? '');
            $province = sanitize($_POST['province'] ?? '');
            $country = sanitize($_POST['country'] ?? '');
            $zip = sanitize($_POST['zip'] ?? '');
            $store = sanitize($_POST['store'] ?? '');

            // Handle Profile Image Upload
            $avatar_url = 'https://lh3.googleusercontent.com/aida-public/AB6AXuCuom_75fEmAQrawrwRszprdNd7mbTW-uLVHnh7vK0oZIlXiJ51O3bRSjPy2zfOnXs19wgqHa58KiiU1XP4qCp1GqPikps7CByYEWxDFZDEMjR_MV2kTMmnzhbKDq-uDPvFY3WqP3eNZsYoc0oylqlF_NZHGa75-wHJprW5fm2be_lMFq6iWViq07kIzkDKckPdkivgPOHn7-THyinajDZk44gk0FhO2VLeV43FMUgIvOuEMbwlOnmb2a0qY4PnCTGczI2ufBAMAB8';
            $curr_user = User::find_by_id($user_id);
            if ($curr_user && !empty($curr_user['avatar_url'])) {
                $avatar_url = $curr_user['avatar_url'];
            }

            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['profile_image']['tmp_name'];
                $fileName = $_FILES['profile_image']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    $uploadFileDir = __DIR__ . '/../assets/uploads/avatars/';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0755, true);
                    }

                    $newFileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
                    $dest_path = $uploadFileDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        $avatar_url = 'assets/uploads/avatars/' . $newFileName;
                    }
                }
            }

            $existing_user = User::find_by_email($real_email);
            if ($existing_user && $existing_user['id'] != $user_id) {
                set_flash('danger', 'This email address is already in use by another member.');
                redirect('?route=setup_profile');
            }

            $success = User::update_profile(
                $user_id,
                $firstname,
                $middlename,
                $lastname,
                $extname,
                $real_email,
                $birthdate,
                $bio,
                $layers_str,
                $address,
                $purok,
                $barangay,
                $town,
                $city,
                $province,
                $country,
                $zip,
                $store,
                $avatar_url
            );
            if ($success) {
                $_SESSION['user']['name'] = $real_name;
                $_SESSION['user']['email'] = $real_email;
                $_SESSION['user']['avatar_url'] = $avatar_url;

                $_SESSION['notification'] = [
                    'title' => 'Profile Configured Successfully!',
                    'message' => 'Welcome to Stacey Halo-Halo Club, ' . htmlspecialchars($real_name) . '! Your onboarding is complete and your custom signature layers are active.',
                    'type' => 'success',
                    'redirect' => '?route=home',
                    'btn_text' => 'Enter Stacey Halo-Halo'
                ];

                redirect('?route=notification');
            } else {
                set_flash('danger', 'Failed to update profile. Please try again.');
                redirect('?route=setup_profile');
            }
        }

        global $conn;
        $ingredients_query = $conn->query("SELECT * FROM ingredients WHERE status = 'Active' ORDER BY name ASC");
        $db_ingredients = [];
        if ($ingredients_query) {
            while ($row = $ingredients_query->fetch_assoc()) {
                $db_ingredients[] = $row;
            }
        }

        require __DIR__ . '/../views/auth/setup_profile.php';
    }

    public function notification()
    {
        require_login();

        $title = sanitize($_GET['title'] ?? $_SESSION['notification']['title'] ?? 'Success!');
        $message = sanitize($_GET['message'] ?? $_SESSION['notification']['message'] ?? 'Action completed successfully.');
        $type = sanitize($_GET['type'] ?? $_SESSION['notification']['type'] ?? 'success');
        $redirect = sanitize($_GET['redirect'] ?? $_SESSION['notification']['redirect'] ?? '?route=home');
        $btn_text = sanitize($_GET['btn_text'] ?? $_SESSION['notification']['btn_text'] ?? 'Continue');

        // Clear session notification payload to prevent refresh retention
        unset($_SESSION['notification']);

        require __DIR__ . '/../views/auth/notification.php';
    }

    public function logout()
    {
        if (isset($_SESSION['user'])) {
            $user_id = $_SESSION['user']['id'];
            $username = $_SESSION['user']['username'] ?? $_SESSION['user']['name'] ?? 'unknown';
            User::log_activity($user_id, 'logout', 'User logged out: ' . $username);
            Monitoring::security('logout');
            User::set_status($user_id, 'offline');
        }
        session_unset();
        session_destroy();
        session_start();
        set_flash('success', 'You have been logged out successfully.');
        redirect('?route=login');
    }
}
