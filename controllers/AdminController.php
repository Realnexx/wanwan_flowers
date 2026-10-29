<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Website.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class AdminController {
    public function pricing(){
        require_permission('view_pricing_tiers');enforce_csrf();require_once __DIR__.'/../models/Pricing.php';
        $pricingMode=is_string($_GET['mode']??null)?$_GET['mode']:'view';$pricingError='';$editingTier=null;
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $action=$_POST['action']??'';
            if(!in_array($action,['add','edit'],true))access_denied('Unknown pricing action.',400);
            require_permission($action==='add'?'add_pricing_tiers':'edit_pricing_tiers');
            try{Pricing::saveTier($action,$_POST['tier_id']??'',$_POST['tier']??[]);ActivityLog::log('Pricing Updated',$action==='add'?'Added a pricing tier.':'Updated a pricing tier.');set_flash('success','Pricing tier saved.');redirect('?route=admin/pricing');}
            catch(DomainException $e){$pricingError=$e->getMessage();$pricingMode=$action==='add'?'add':'edit';$editingTier=array_merge(is_array($_POST['tier']??null)?$_POST['tier']:[],['id'=>$_POST['tier_id']??'']);}
        }
        if($pricingMode==='add')require_permission('add_pricing_tiers');
        if($pricingMode==='edit')require_permission('edit_pricing_tiers');
        $tiers=Pricing::all();
        if(!in_array($pricingMode,['view','add','edit'],true))$pricingMode='view';
        if($pricingMode==='edit'&&$editingTier===null){foreach($tiers as $tier)if($tier['id']===($_GET['id']??''))$editingTier=$tier;if(!$editingTier){set_flash('danger','Pricing tier not found.');redirect('?route=admin/pricing');}}
        require __DIR__.'/../views/modules/pricing.php';
    }
    public function demoRequestsData(){require_permission('view_demo_requests');require_once __DIR__.'/../models/DemoRequest.php';header('Content-Type: application/json');header('Cache-Control: no-store, private');echo json_encode(DemoRequest::dataTable($_GET));}
    public function demoRequests() {
        require_permission('view_demo_requests');enforce_csrf();
        require_once __DIR__.'/../models/DemoRequest.php';
        if($_SERVER['REQUEST_METHOD']==='POST') {
            require_permission('update_demo_request_status');
            try {DemoRequest::updateStatus($_POST['id']??0,$_POST['status']??'');ActivityLog::log('Demo Request Updated','Updated request #'.(int)($_POST['id']??0));set_flash('success','Request status updated.');}
            catch(DomainException $e){set_flash('danger',$e->getMessage());}
            redirect('?route=admin/demo_requests');
        }
        $summary=DemoRequest::counts();require __DIR__.'/../views/modules/demo_requests.php';
    }
    public function dashboard() {
        require_permission('view_dashboard');
        require_once __DIR__ . '/../models/Branch.php';

        require_once __DIR__.'/../models/DashboardData.php';
        try {
            $dashboard=DashboardData::load($_GET['branch'] ?? $_SESSION['dashboard_branch'] ?? 'all',$_GET['date_range'] ?? null);
        } catch(DomainException $error) { access_denied($error->getMessage()); }
        extract($dashboard,EXTR_SKIP);
        $is_admin=$can_choose_branch;
        $all_branches=$can_choose_branch ? Branch::get_all_active() : [];
        if($can_choose_branch) $_SESSION['dashboard_branch']=$selected_branch;
        require __DIR__ . '/../views/modules/dashboard.php';
    }

    public function users() {
        require_permission('manage_users');
        enforce_csrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $this->validateUserAccess((int)($_POST['user_id'] ?? 0), (array)($_POST['roles'] ?? []), true);
            if(in_array($action,['create','edit'],true)&&($action==='create'||!empty($_POST['password']))) {
                require_once __DIR__.'/../models/AuthSecurity.php';
                if($error=AuthSecurity::password_error($_POST['password']??'')){$_SESSION['user_field_error']=['action'=>$action,'field'=>'password','message'=>$error,'values'=>array_intersect_key($_POST,array_flip(['user_id','firstname','middlename','lastname','extname','email','roles']))];redirect('?route=admin/users');}
            }

            if ($action === 'create') {
                $firstname = sanitize($_POST['firstname'] ?? '');
                $middlename = sanitize($_POST['middlename'] ?? '');
                $lastname = sanitize($_POST['lastname'] ?? '');
                $extname = sanitize($_POST['extname'] ?? '');
                $email = sanitize($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $roles = $_POST['roles'] ?? [];

                if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
                    set_flash('danger', 'All fields are required.');
                } elseif (User::find_by_email($email)) {
                    set_flash('danger', 'Email already exists.');
                } else {
                    $username = explode('@', $email)[0];
                    $image = $this->handleUserImage($_FILES['image'] ?? null);
                    $new_id = User::create($firstname, $middlename, $lastname, $extname, $username, $email, $password, $roles, $image);
                    if ($new_id) {
                        $pin=trim($_POST['pos_pin']??'');
                        if(preg_match('/^[0-9]{4}$/D',$pin)){require_once __DIR__.'/../models/SystemPolicy.php';SystemPolicy::setPin($new_id,$pin);}
                    }
                    User::log_activity($_SESSION['user']['id'], 'USER_CREATE', 'Created user: ' . $email);
                    ActivityLog::log('User Created', 'Created user: ' . $email . ' (ID: ' . $new_id . ')');
                    set_flash('success', 'User created successfully.');
                }
                redirect('?route=admin/users');
            } elseif ($action === 'edit') {
                $id = intval($_POST['user_id'] ?? 0);
                $firstname = sanitize($_POST['firstname'] ?? '');
                $middlename = sanitize($_POST['middlename'] ?? '');
                $lastname = sanitize($_POST['lastname'] ?? '');
                $extname = sanitize($_POST['extname'] ?? '');
                $email = sanitize($_POST['email'] ?? '');
                $password = !empty($_POST['password']) ? $_POST['password'] : null;
                $roles = $_POST['roles'] ?? [];

                $image = $this->handleUserImage($_FILES['image'] ?? null);
                User::update($id, $firstname, $middlename, $lastname, $extname, $email, $password, $roles, $image);

                // Handle POS PIN update
                $pos_pin = trim($_POST['pos_pin'] ?? '');
                if (!empty($pos_pin) && preg_match('/^\d{4}$/', $pos_pin)) {
                    require_once __DIR__.'/../models/SystemPolicy.php';SystemPolicy::setPin($id,$pos_pin);
                }

                User::log_activity($_SESSION['user']['id'], 'USER_UPDATE', 'Updated user: ' . $email . ' (ID: ' . $id . ')');
                ActivityLog::log('User Updated', 'Updated user: ' . $email . ' (ID: ' . $id . ')');
                set_flash('success', 'User updated successfully.');
                redirect('?route=admin/users');
            } elseif ($action === 'delete') {
                $id = intval($_POST['user_id'] ?? 0);
                if ($id === $_SESSION['user']['id']) {
                    set_flash('danger', 'You cannot delete your own account.');
                } else {
                    User::delete($id);
                    User::log_activity($_SESSION['user']['id'], 'USER_DELETE', 'Deleted user ID: ' . $id);
                    ActivityLog::log('User Deleted', 'Deleted user ID: ' . $id);
                    set_flash('success', 'User deleted successfully.');
                }
                redirect('?route=admin/users');
            }
        }

        require __DIR__ . '/../views/modules/users.php';
    }

    public function getUserData() {
        require_permission('manage_users');
        header('Content-Type: application/json');
        
        $draw = intval($_GET['draw'] ?? 0);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $search = $_GET['search']['value'] ?? '';
        $order_col = intval($_GET['order'][0]['column'] ?? 0);
        $order_dir = $_GET['order'][0]['dir'] ?? 'DESC';

        $users = User::get_dt_users($start, $length, $search, $order_col, $order_dir);
        foreach ($users as &$listed_user) { unset($listed_user['password'], $listed_user['pos_pin']); $listed_user['can_manage'] = User::can_manage_user($_SESSION['user']['id'], $listed_user['id']); }
        unset($listed_user);
        $totalRecords = User::count_all_users();
        $totalFiltered = User::count_filtered_users($search);

        $role_counts = User::get_role_distribution();

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalFiltered,
            'data' => $users,
            'role_counts' => $role_counts
        ]);
        exit;
    }

    public function getRoles() {
        require_permissions(['manage_roles','manage_users']);
        header('Content-Type: application/json');
        $permissionNames=[];foreach(Role::get_all_permissions() as $group) foreach($group as $permission) $permissionNames[$permission['id']]=$permission['name'];
        $actorSuper=User::is_superadmin($_SESSION['user']['id']);
        echo json_encode([
            'success' => true,
            'roles' => array_map(function($r) use($permissionNames,$actorSuper) {
                $r['is_superadmin'] = preg_replace('/[ _-]/','',strtolower($r['name'])) === 'superadmin';
                $allowed=$actorSuper || !$r['is_superadmin'];
                if(!$actorSuper) foreach(array_filter(explode(',',$r['permission_ids'] ?? '')) as $pid) if(isset($permissionNames[$pid]) && !User::has_permission($_SESSION['user']['id'],$permissionNames[$pid])) $allowed=false;
                $r['can_assign']=$allowed;
                $r['can_edit']=$allowed && !$r['is_superadmin'];
                $r['can_delete']=$r['can_edit'] && !(int)$r['is_system'];
                return $r;
            }, array_values(array_filter(Role::get_all_roles(), static function($role) use($actorSuper) {
                return $actorSuper || preg_replace('/[ _-]/','',strtolower($role['name'])) !== 'superadmin';
            })))
        ]);
        exit;
    }

    public function getPermissionsData() {
        require_permission('manage_roles');
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'permissions' => Role::get_all_permissions()
        ]);
        exit;
    }

    public function getSingleUser() {
        require_permission('manage_users');
        header('Content-Type: application/json');
        $id = intval($_GET['id'] ?? 0);
        $this->validateUserAccess($id, []);
        $user = User::find_by_id($id);
        if ($user) unset($user['password'], $user['pos_pin']);
        
        if ($user) {
            // Include roles since find_by_id doesn't natively include them in the same format as ALL
            $roles = User::get_user_roles($id);
            $user['roles'] = implode(', ', array_column($roles, 'name'));
            
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        exit;
    }

    public function roles() {
        require_permission('manage_roles');
        enforce_csrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $this->validateRoleAccess((int)($_POST['role_id'] ?? 0), (string)($_POST['name'] ?? ''), (array)($_POST['permissions'] ?? []));
            if ($action === 'create') {
                $name = sanitize($_POST['name'] ?? '');
                $description = sanitize($_POST['description'] ?? '');
                $permissions = $_POST['permissions'] ?? [];

                if (empty($name)) {
                    set_flash('danger', 'Role name is required.');
                } else {
                    Role::create($name, $description, $permissions);
                    User::log_activity($_SESSION['user']['id'], 'ROLE_CREATE', 'Created role: ' . $name);
                    ActivityLog::log('Role Created', 'Created role: ' . $name);
                    set_flash('success', 'Role created successfully.');
                }
                if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') { header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit; }
                redirect('?route=admin/roles');
            } elseif ($action === 'edit') {
                $id = intval($_POST['role_id'] ?? 0);
                $name = sanitize($_POST['name'] ?? '');
                $description = sanitize($_POST['description'] ?? '');
                $permissions = $_POST['permissions'] ?? [];

                Role::update($id, $name, $description, $permissions);
                User::log_activity($_SESSION['user']['id'], 'ROLE_UPDATE', 'Updated role: ' . $name . ' (ID: ' . $id . ')');
                ActivityLog::log('Role Updated', 'Updated role: ' . $name . ' (ID: ' . $id . ')');
                set_flash('success', 'Role updated successfully.');
                if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') { header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit; }
                redirect('?route=admin/roles');
            } elseif ($action === 'delete') {
                $id = intval($_POST['role_id'] ?? 0);
                $deleted=Role::delete($id);
                if ($deleted) {
                    User::log_activity($_SESSION['user']['id'], 'ROLE_DELETE', 'Deleted role ID: ' . $id);
                    ActivityLog::log('Role Deleted', 'Deleted role ID: ' . $id);
                    set_flash('success', 'Role deleted successfully.');
                } else {
                    set_flash('danger', 'This role is protected, no longer exists, or cannot be deleted.');
                }
                if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') { if(!$deleted) http_response_code(409); header('Content-Type: application/json'); echo json_encode(['success'=>$deleted,'message'=>$deleted?'Role deleted.':'This role is protected.']); exit; }
                redirect('?route=admin/roles');
            }
        }

        $roles = Role::get_all_roles();
        $permissions_grouped = Role::get_all_permissions();
        require __DIR__ . '/../views/modules/roles.php';
    }

    public function cms() {
        require_permission('access_website_admin');
        enforce_csrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'update_content') {
                $content = [
                    'hero_title' => sanitize($_POST['hero_title'] ?? ''),
                    'hero_subtitle' => sanitize($_POST['hero_subtitle'] ?? ''),
                    'about_text' => sanitize($_POST['about_text'] ?? ''),
                    'contact_address' => sanitize($_POST['contact_address'] ?? ''),
                    'contact_phone' => sanitize($_POST['contact_phone'] ?? ''),
                    'contact_email' => sanitize($_POST['contact_email'] ?? '')
                ];
                Website::update_content($content);
                ActivityLog::log('Website Content Updated', 'Updated website content (hero, about, contact)');
                set_flash('success', 'Website content updated successfully.');
                redirect('?route=admin/cms');
            } elseif ($action === 'mark_read') {
                $id = intval($_POST['message_id'] ?? 0);
                Website::mark_message_read($id);
                ActivityLog::log('Message Marked Read', 'Marked contact message ID ' . $id . ' as read');
                set_flash('success', 'Message marked as read.');
                redirect('?route=admin/cms');
            }
        }

        $content = Website::get_content();
        $messages = Website::get_all_messages();
        require __DIR__ . '/../views/modules/cms.php';
    }

    public function reports() {
        require_once __DIR__ . '/../models/Branch.php';
        require_permission('view_reports');
        
        $is_admin = User::has_all_branch_access($_SESSION['user']['id']);
        $branches = $is_admin ? (Branch::get_all_active() ?: []) : [];
        
        require __DIR__ . '/../views/modules/reports.php';
    }

    public function reportsPrint() {
        require_once __DIR__ . '/../models/Branch.php';
        require_once __DIR__ . '/../models/Product.php';
        require_permission('view_reports');

        $type = $_GET['type'] ?? 'sales';
        $branch_id_req = $_GET['branch_id'] ?? 'all';
        $start_date = $_GET['start_date'] ?? $_GET['date_from'] ?? null;
        $end_date = $_GET['end_date'] ?? $_GET['date_to'] ?? null;
        
        // Advanced filters from POS History
        $payment_status = $_GET['payment_status'] ?? 'All';
        $raw_channels = isset($_GET['channels']) ? (is_array($_GET['channels']) ? $_GET['channels'] : [$_GET['channels']]) : [];
        $raw_locations = isset($_GET['locations']) ? (is_array($_GET['locations']) ? $_GET['locations'] : [$_GET['locations']]) : [];

        $access = User::access_context((int)$_SESSION['user']['id']);
        $is_admin = $access['global'];
        $branch_id = ($is_admin && $branch_id_req !== 'all') ? (int)$branch_id_req : ($is_admin ? null : ($access['branch_id'] ?? 0));

        $data = [];
        
        if ($type === 'sales') {
            // Build filters for Sale::get_filtered_sales
            $filters = [
                'branch_id' => $branch_id,
                'search' => $_GET['search'] ?? '',
                'date_from' => $start_date,
                'date_to' => $end_date,
                'payment_status' => $payment_status,
                'channels' => array_filter($raw_channels),
                'locations' => array_filter($raw_locations)
            ];

            // If we have advanced filters beyond just date/branch, use the more robust get_filtered_sales
            if (!empty($filters['channels']) || !empty($filters['locations']) || $filters['payment_status'] !== 'All' || !empty($filters['search'])) {
                $data = Sale::get_filtered_sales($filters, 1, 5000, 's.id', 'DESC');
                
                // Recalculate stats for these specific filtered results
                $rev = 0; $disc = 0; $count = 0;
                foreach($data as $s) {
                    if ($s['status'] === 'Completed') {
                        $count++;
                        $rev += $s['final_amount'];
                        $disc += $s['discount_amount'];
                    }
                }
                $stats = ['revenue' => $rev, 'discounts' => $disc, 'count' => $count];
            } else {
                $stats = Sale::get_summary_stats($branch_id, $start_date, $end_date);
                $data = Sale::get_dt_sales(0, 5000, '', 0, 'desc', $branch_id, $start_date, $end_date);
            }
        } else {
            $stats = Sale::get_summary_stats($branch_id, $start_date, $end_date);
            $data = Product::get_dt_inventory_logs(0, 5000, '', -1, 'desc', $branch_id, $start_date, $end_date);
        }

        $branches = Branch::get_all();
        $selected_branch_name = "Global Network";
        if ($branch_id) {
            foreach ($branches as $b) {
                if ($b['id'] == $branch_id) {
                    $selected_branch_name = $b['name'];
                    break;
                }
            }
        }

        // Pass these for displaying filters in the print view
        $applied_filters = [
            'status' => $payment_status,
            'channels' => $raw_channels,
            'locations' => $raw_locations,
            'search' => $_GET['search'] ?? ''
        ];

        $store_name = Setting::get('store_name', 'Stacey Halo Halo');
        $store_logo = Setting::get('store_logo', '');
        
        require __DIR__ . '/../views/modules/reports_print.php';
        exit;
    }

    public function branches() {
        require_once __DIR__ . '/../models/Branch.php';
        // Require permission or manage_users as fallback
        require_permission('manage_branches');

        
        $branches = Branch::get_all();
        $stats = Branch::get_stats();
        $settings = Setting::get_by_category('general');
        require __DIR__ . '/../views/modules/branches.php';
    }

    public function branchesReport() {
        require_once __DIR__ . '/../models/Branch.php';
        require_permission('manage_branches');
        
        $branches = Branch::get_all();
        $stats = Branch::get_stats();
        $settings = Setting::get_by_category('general');
        
        require __DIR__ . '/../views/modules/branch_report.php';
    }

    public function settings() {
        require_permission('manage_settings');
        enforce_csrf();

        $cashiers = []; // Pre-initialize to prevent undefined variable error

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category = $_POST['setting_category'] ?? 'general';
            
            if ($category === 'paymongo') {
                require_once __DIR__.'/../models/PayMongo.php';
                try {
                    $paymentNotice=null;
                    if(($_POST['paymongo']['action']??'')==='expire')PayMongo::expire($_POST['paymongo']['payment_id']??0,(int)$_SESSION['user']['id']);
                    elseif(($_POST['paymongo']['action']??'')==='reconcile')PayMongo::reconcile($_POST['paymongo']['payment_id']??0,(int)$_SESSION['user']['id']);
                    else $paymentNotice=PayMongo::save($_POST['paymongo'] ?? [],(int)$_SESSION['user']['id']);
                    ActivityLog::log('Payment Settings Updated','PayMongo settings saved; no credentials logged.');
                    $messages=['disable'=>'PayMongo disabled for new checkouts. Existing payments can still be verified.','reconcile'=>'Payment status checked.','expire'=>'Unpaid checkout expired. The order can now accept another payment.'];
                    set_flash($paymentNotice?'warning':'success',$paymentNotice??($messages[$_POST['paymongo']['action']??'']??'PayMongo keys checked and available payment methods synchronized.'));
                } catch(DomainException $error) { set_flash('danger',$error->getMessage()); }
                catch(Throwable $error) { error_log('PayMongo settings update failed.');set_flash('danger','Payment settings could not be saved. Existing configuration was kept.'); }
                redirect('?route=admin/settings&tab=paymongo');
            }

            if ($category === 'backup') {
                require_once __DIR__.'/../models/DatabaseBackup.php';
                try {
                    DatabaseBackup::authorize((int)$_SESSION['user']['id'],$_POST['current_password']??'');
                    $action=$_POST['backup_action']??'';
                    if($action==='save')DatabaseBackup::save($_POST['backup']??[]);
                    elseif($action==='create')DatabaseBackup::create((int)$_SESSION['user']['id']);
                    elseif($action==='verify')DatabaseBackup::verify($_POST['backup_id']??'');
                    elseif($action==='download'||$action==='key') {
                        $path=$action==='download'?DatabaseBackup::download($_POST['backup_id']??''):null;
                        ActivityLog::log('Backup Download', $action==='key'?'Recovery key downloaded.':'Encrypted database backup downloaded.');
                        header('Content-Type: application/octet-stream');header('Cache-Control: no-store, private');header('X-Content-Type-Options: nosniff');
                        header('Content-Disposition: attachment; filename="'.($action==='key'?'pos-backup-recovery.key':basename($path)).'"');
                        if($path)readfile($path);else echo DatabaseBackup::recoveryKey();exit;
                    } else throw new DomainException('Unknown backup action.');
                    ActivityLog::log('Database Backup', 'Backup action completed: '.$action);
                    set_flash('success',$action==='verify'?'Restore verified on a temporary database. The live database was unchanged.':'Backup action completed successfully.');
                }catch(DomainException $e){set_flash('danger',$e->getMessage());}
                catch(Throwable $e){error_log('Database backup action failed: '.get_class($e));set_flash('danger','Backup action failed. Check storage, database permissions and the maintenance log.');}
                redirect('?route=admin/settings&tab=backup');
            }

            // Handle Password Update specifically
            if ($category === 'password') {
                $current_pass = $_POST['current_password'] ?? '';
                $new_pass = $_POST['new_password'] ?? '';
                $confirm_pass = $_POST['confirm_password'] ?? '';
                
                $user = User::find_by_id($_SESSION['user']['id']);
                if (!$user || !password_verify($current_pass, $user['password'])) {
                    set_flash('danger', 'Current password is incorrect.');
                } elseif ($new_pass !== $confirm_pass) {
                    set_flash('danger', 'New passwords do not match.');
                } elseif ($passwordError = AuthSecurity::password_error($new_pass)) {
                    set_flash('danger', $passwordError);
                } else {
                    User::update_password($user['id'], $new_pass);
                    User::log_activity($user['id'], 'PASSWORD_CHANGE', 'User changed their own password.');
                    ActivityLog::log('Password Changed', 'User #' . $user['id'] . ' changed their password');
                    set_flash('success', 'Password updated successfully.');
                }
                redirect('?route=admin/settings&tab=password');
            }

            $data = $_POST['settings'][$category] ?? [];
            if($category==='security') {
                require_once __DIR__.'/../models/SystemPolicy.php';
                try {SystemPolicy::saveSecurity($data,$_POST['user_pins']??[],$_POST['current_password']??'',(int)$_SESSION['user']['id']);ActivityLog::log('Security Settings Updated','Access policies and PINs updated; secrets omitted.');set_flash('success','Security policies updated. When 2FA is enabled, sign in again to verify or enroll your authenticator.');}
                catch(DomainException $e){set_flash('danger',$e->getMessage());}
                redirect('?route=admin/settings&tab=security');
            }
            if(in_array($category,['financial','pos'],true)) {
                require_once __DIR__.'/../models/CheckoutPolicy.php';
                try {CheckoutPolicy::save($category,$data);ActivityLog::log('Checkout Settings Updated','Updated '.$category.' configuration.');set_flash('success','Checkout settings updated. Existing receipts retain their saved values.');}
                catch(DomainException $e){set_flash('danger',$e->getMessage());}
                redirect('?route=admin/settings&tab='.$category);
            }
            if(!in_array($category,['general','appearance'],true)){set_flash('danger','Unknown settings category.');redirect('?route=admin/settings');}
            $allowed=$category==='general'?['store_name','store_address','store_phone','store_email','store_logo','online_ordering_enabled','flower_delivery_fee','store_phone_alt','founder_name','founder_title','founder_quote','expansion_milestone']:['primary_color','secondary_color','font_family','theme','dark_mode','sidebar_color','layout_mode'];
            $data=array_intersect_key($data,array_flip($allowed));
            if(isset($data['online_ordering_enabled']))$data['online_ordering_enabled']=$data['online_ordering_enabled']==='1'?'1':'0';
            // Handle logo upload if present
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../assets/img/';
                if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);
                $ext = pathinfo($_FILES['store_logo']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $targetDir . $filename)) {
                    $data['store_logo'] = 'assets/img/' . $filename;
                }
            }

            if (isset($data['flower_delivery_fee']) && (!is_string($data['flower_delivery_fee']) || !preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $data['flower_delivery_fee']))) {
                set_flash('danger','Enter a valid flower delivery fee with up to two decimal places.');
                redirect('?route=admin/settings&tab=general');
            }
            Setting::update_all($data, $category);
            User::log_activity($_SESSION['user']['id'], 'SETTINGS_UPDATE', 'Updated settings category: ' . $category);
            ActivityLog::log('Settings Updated', 'Updated settings category: ' . $category);
            set_flash('success', 'Settings updated successfully.');
            redirect('?route=admin/settings&tab=' . $category);
        }

        $all_settings = [
            'general' => Setting::get_by_category('general'),
            'financial' => Setting::get_by_category('financial'),
            'pos' => Setting::get_by_category('pos'),
            'appearance' => Setting::get_by_category('appearance'),
            'security' => Setting::get_by_category('security')
        ];

        // Fetch Cashiers for PIN management
        $res = $GLOBALS['conn']->query("SELECT u.id, u.name, u.pos_pin, b.name as branch_name 
                                        FROM users u 
                                        LEFT JOIN branches b ON u.branch_id = b.id 
                                        JOIN user_roles ur ON u.id = ur.user_id 
                                        JOIN roles r ON ur.role_id = r.id 
                                        WHERE r.name = 'Cashier' OR r.name = 'Staff'
                                        ORDER BY b.name, u.name");
        if ($res) {
            while($row = $res->fetch_assoc()) $cashiers[] = $row;
        }

        require __DIR__ . '/../views/modules/settings.php';
    }
    private function handleUserImage($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;

        $targetDir = __DIR__ . '/../assets/img/users/';
        if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return 'assets/img/users/' . $filename;
        }
        $_SESSION['user_field_error']=['action'=>$_POST['action']??'edit','field'=>'image','message'=>'The profile photo could not be stored. Ask an administrator to check the upload folder permissions.','values'=>array_intersect_key($_POST,array_flip(['user_id','firstname','middlename','lastname','extname','email','roles']))];
        redirect('?route=admin/users');
    }
    public function activityLogs() {
        require_once __DIR__ . '/../models/ActivityLog.php';
        require_permission('view_activity_logs');

        $users = User::get_all_users();
        $total_logs = ActivityLog::countAll();
        
        require __DIR__ . '/../views/modules/activity_logs.php';
    }

    public function activityLogsPrint() {
        require_once __DIR__ . '/../models/ActivityLog.php';
        require_permission('view_activity_logs');

        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        // Fetch logs with filters (unlimited for print, or a high limit like 1000)
        $logs = ActivityLog::getDTLogs(0, 1000, $filters, 'l.created_at', 'DESC');
        
        $total_logs = count($logs);
        
        require __DIR__ . '/../views/modules/activity_logs_print.php';
    }

    public function getActivityLogsData() {
        require_once __DIR__ . '/../models/ActivityLog.php';
        require_permission('view_activity_logs');

        $params = $_GET;
        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $search = $params['search']['value'] ?? '';

        $filters = [
            'user_id' => $params['user_id'] ?? null,
            'action' => $params['action'] ?? null,
            'date_from' => $params['date_from'] ?? null,
            'date_to' => $params['date_to'] ?? null,
            'search' => $search
        ];

        $order_col_idx = $params['order'][0]['column'] ?? 4;
        $order_dir = $params['order'][0]['dir'] ?? 'desc';
        $order_cols = ['u.name', 'l.action', 'l.description', 'l.ip_address', 'l.created_at'];
        $order_col = $order_cols[$order_col_idx] ?? 'l.created_at';

        $recordsTotal = ActivityLog::countAll();
        $recordsFiltered = ActivityLog::countFiltered($filters);
        $logs = ActivityLog::getDTLogs($start, $length, $filters, $order_col, $order_dir);

        $data = [];
        foreach ($logs as $l) {
            $avatar = !empty($l['avatar_url']) ? $l['avatar_url'] : '';
            $user_initial = strtoupper(substr($l['user_name'] ?? 'S', 0, 1));
            
            $actionClass = 'bg-secondary-subtle text-secondary';
            if (strpos($l['action'], 'DELETE') !== false || strpos($l['action'], 'CANCEL') !== false) $actionClass = 'bg-danger-subtle text-danger';
            if (strpos($l['action'], 'CREATE') !== false || strpos($l['action'], 'LOGIN') !== false) $actionClass = 'bg-success-subtle text-success';
            if (strpos($l['action'], 'UPDATE') !== false) $actionClass = 'bg-warning-subtle text-warning';

            $data[] = [
                // User Column
                '<div class="d-flex align-items-center gap-3">
                    <div class="avatar-circle rounded-circle bg-secondary-subtle d-flex align-items-center justify-content-center fw-bold text-secondary" style="width: 32px; height: 32px; font-size: 12px; overflow:hidden;">' . 
                    (!empty($avatar) ? '<img src="' . htmlspecialchars($avatar) . '" class="w-100 h-100 object-fit-cover">' : $user_initial) . 
                    '</div>
                    <div>
                        <div class="fw-bold text-dark small">' . htmlspecialchars($l['user_name'] ?? 'System') . '</div>
                        <div class="text-muted smaller" style="font-size: 10px;">User ID: #' . ($l['user_id'] ?? '0') . '</div>
                    </div>
                </div>',
                // Action
                '<span class="badge ' . $actionClass . ' rounded-pill px-3 py-1 fw-bold smaller">' . htmlspecialchars($l['action']) . '</span>',
                // Description
                '<span class="text-muted small">' . htmlspecialchars($l['description']) . '</span>',
                // IP Address
                '<code class="small text-secondary bg-light px-2 py-1 rounded">' . htmlspecialchars($l['ip_address']) . '</code>',
                // Timestamp
                '<div class="text-dark fw-medium small">' . date('M d, Y', strtotime($l['created_at'])) . '</div>
                 <div class="text-muted smaller" style="font-size: 10px;">' . date('h:i:s A', strtotime($l['created_at'])) . ' PHT</div>'
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            "draw" => intval($params['draw'] ?? 1),
            "recordsTotal" => intval($recordsTotal),
            "recordsFiltered" => intval($recordsFiltered),
            "data" => $data
        ]);
        exit;
    }
    private function validateRoleAccess($target, $name, array $permission_ids) {
        $known=[];foreach(Role::get_all_permissions() as $group) foreach($group as $p) $known[]=(int)$p['id'];
        if(array_diff(array_map('intval',$permission_ids),$known)) access_denied('Select valid permissions.',400);
        if (User::is_superadmin($_SESSION['user']['id'])) return;
        foreach (Role::get_all_roles() as $role) {
            if ((int)$role['id']===$target) { foreach(Role::get_all_permissions() as $group) foreach($group as $permission) if(in_array((int)$permission['id'],array_map('intval',explode(',',$role['permission_ids'] ?? '')),true) && !User::has_permission($_SESSION['user']['id'],$permission['name'])) access_denied('You cannot change a role with greater access than your own.'); }
            if ((int)$role['id']===$target && preg_replace('/[ _-]/','',strtolower($role['name']))==='superadmin') access_denied('Only Superadmin can change this role.');
        }
        if (preg_replace('/[ _-]/','',strtolower($name))==='superadmin') access_denied('Only Superadmin can create this role.');
        foreach (Role::get_all_permissions() as $group) foreach($group as $permission) {
            if (in_array((int)$permission['id'],array_map('intval',$permission_ids),true) && !User::has_permission($_SESSION['user']['id'],$permission['name'])) access_denied('You cannot grant permissions you do not have.');
        }
    }
    private function validateUserAccess($target, array $roles, $write = false) {
        $known=array_map('intval',array_column(Role::get_all_roles(),'id'));
        if(array_diff(array_map('intval',$roles),$known)) access_denied('Select a valid role.',400);
        if ($write && $target && User::is_superadmin($target)) {
            $keeps_superadmin=false;
            foreach(Role::get_all_roles() as $r) if(in_array((int)$r['id'],array_map('intval',$roles),true) && preg_replace('/[ _-]/','',strtolower($r['name']))==='superadmin') $keeps_superadmin=true;
            if(!$keeps_superadmin) {
                $other=false;
                foreach(User::get_all_users() as $account) if((int)$account['id']!==$target && User::is_superadmin($account['id'])) { $other=true;break; }
                if(!$other) access_denied('Keep at least one active Superadmin account.');
            }
        }
        if (User::is_superadmin($_SESSION['user']['id'])) return;
        if ($target && User::is_superadmin($target)) access_denied('This account is protected.');
        if ($write && $target && !User::can_manage_user($_SESSION['user']['id'],$target)) access_denied('You cannot change an account with greater access than your own.');
        foreach (Role::get_all_roles() as $role) if (in_array((int)$role['id'],array_map('intval',$roles),true)) {
            if (preg_replace('/[ _-]/','',strtolower($role['name']))==='superadmin') access_denied('Only Superadmin can assign this role.');
            foreach (Role::get_all_permissions() as $group) foreach($group as $permission) {
                if (in_array((int)$permission['id'],array_map('intval',explode(',',$role['permission_ids'] ?? '')),true) && !User::has_permission($_SESSION['user']['id'],$permission['name'])) access_denied('You cannot assign a role with greater access than your own.');
            }
        }
    }

}
