<?php
require_once __DIR__ . '/../config/db.php';

class User {
    public static function access_context($id) {
        global $conn;
        $user = self::find_by_id($id);
        $roles = $user ? array_column(self::get_user_roles($id), 'name') : [];
        $global = false; $staff = false; $role = 'Customer';
        foreach ($roles as $name) {
            $key = strtolower(str_replace([' ', '_', '-'], '', $name));
            if ($key !== 'customer') { $staff = true; if (!$global) $role = $name; }
            if (in_array($key, ['admin', 'superadmin'], true)) { $global = true; $role = $name; }
        }
        $branchId = !empty($user['branch_id']) ? (int)$user['branch_id'] : null;
        $branchName = null;
        if ($branchId) {
            $stmt = $conn->prepare("SELECT name FROM branches WHERE id = ? AND status = 'Active'");
            $stmt->bind_param('i', $branchId); $stmt->execute();
            $branchName = $stmt->get_result()->fetch_assoc()['name'] ?? null; $stmt->close();
            if (!$branchName) $branchId = null;
        }
        return ['staff'=>$staff, 'global'=>$global, 'role'=>$role, 'branch_id'=>$branchId,
            'branch'=>$global ? 'All Branches' : ($branchName ?: 'No branch assigned')];
    }
    public static function has_all_branch_access($id) { return self::access_context($id)['global']; }
    public static function landing_route($id) {
        $context = self::access_context($id);
        if (!$context['staff']) return 'customer/home';
        if (($context['global'] || $context['branch_id']) && self::has_permission($id, 'view_dashboard')) return 'dashboard';
        return 'admin/start';
    }

    public static function find_by_email($email) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public static function find_by_username_or_email($input) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND deleted_at IS NULL");
        $stmt->bind_param("ss", $input, $input);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public static function find_by_id($id) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    // Exclude any account carrying a Super Admin role, including multi-role accounts.
    private static function directory_condition() {
        return "u.deleted_at IS NULL AND NOT EXISTS (
            SELECT 1 FROM user_roles hidden_ur
            INNER JOIN roles hidden_r ON hidden_r.id = hidden_ur.role_id
            WHERE hidden_ur.user_id = u.id
            AND LOWER(REPLACE(REPLACE(REPLACE(hidden_r.name, ' ', ''), '_', ''), '-', '')) = 'superadmin'
        )";
    }

    public static function get_dt_users($start, $length, $search, $order_col, $order_dir) {
        global $conn;
        $order_dir = strtoupper((string)$order_dir) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT u.*, (SELECT b.name FROM branches b WHERE b.id=u.branch_id) as assigned_branch_name, GROUP_CONCAT(r.name SEPARATOR ', ') as roles 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                LEFT JOIN roles r ON ur.role_id = r.id 
                WHERE " . self::directory_condition();
        
        if (!empty($search)) {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR r.name LIKE ?)";
        }
        
        $sql .= " GROUP BY u.id";
        
        $order_cols = ['u.name', 'u.email', 'roles', 'u.created_at'];
        $order_by = $order_cols[$order_col] ?? 'u.id';
        $sql .= " ORDER BY $order_by $order_dir";
        
        $sql .= " LIMIT ?, ?";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($search)) {
            $search_param = "%$search%";
            $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $start, $length);
        } else {
            $stmt->bind_param("ii", $start, $length);
        }
        
        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $users;
    }

    public static function count_all_users() {
        global $conn;
        $res = $conn->query("SELECT COUNT(*) as count FROM users u WHERE " . self::directory_condition());
        return $res->fetch_assoc()['count'];
    }

    public static function count_filtered_users($search) {
        global $conn;
        if (empty($search)) return self::count_all_users();
        
        $sql = "SELECT COUNT(DISTINCT u.id) as count 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                LEFT JOIN roles r ON ur.role_id = r.id 
                WHERE " . self::directory_condition() . " AND (u.name LIKE ? OR u.email LIKE ? OR r.name LIKE ?)";
        
        $stmt = $conn->prepare($sql);
        $search_param = "%$search%";
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        return $count;
    }

    public static function get_role_distribution() {
        global $conn;
        $sql = "SELECT r.name, COUNT(u.id) as count
                FROM roles r
                LEFT JOIN user_roles ur ON r.id = ur.role_id
                LEFT JOIN users u ON ur.user_id = u.id AND " . self::directory_condition() . "
                WHERE LOWER(REPLACE(REPLACE(REPLACE(r.name, ' ', ''), '_', ''), '-', '')) <> 'superadmin'
                GROUP BY r.id";
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public static function get_all_users() {
        global $conn;
        $query = "SELECT u.*, GROUP_CONCAT(r.name SEPARATOR ', ') as roles 
                  FROM users u 
                  LEFT JOIN user_roles ur ON u.id = ur.user_id 
                  LEFT JOIN roles r ON ur.role_id = r.id 
                  WHERE u.deleted_at IS NULL
                  GROUP BY u.id ORDER BY u.id DESC";
        return $conn->query($query)->fetch_all(MYSQLI_ASSOC);
    }

    public static function create($firstname, $middlename, $lastname, $extname, $username, $email, $password, $role_ids = [], $image = null) {
        global $conn;
        $name_parts = array_filter([$firstname, $middlename, $lastname, $extname]);
        $name = implode(' ', $name_parts);
        require_once __DIR__.'/AuthSecurity.php';
        if($error=AuthSecurity::password_error($password))throw new DomainException($error);
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, firstname, middlename, lastname, extname, username, email, password, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $name, $firstname, $middlename, $lastname, $extname, $username, $email, $hashed, $image);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $stmt->close();

            if (!empty($role_ids)) {
                $stmt_role = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                foreach ($role_ids as $r_id) {
                    $stmt_role->bind_param("ii", $user_id, $r_id);
                    $stmt_role->execute();
                }
                $stmt_role->close();
            }
            return $user_id;
        }
        $stmt->close();
        return false;
    }

    public static function update($id, $firstname, $middlename, $lastname, $extname, $email, $password = null, $role_ids = [], $image = null) {
        global $conn;
        $name_parts = array_filter([$firstname, $middlename, $lastname, $extname]);
        $name = implode(' ', $name_parts);

        if ($password) {
            require_once __DIR__.'/AuthSecurity.php';
            if($error=AuthSecurity::password_error($password))throw new DomainException($error);
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            if ($image) {
                $stmt = $conn->prepare("UPDATE users SET name = ?, firstname = ?, middlename = ?, lastname = ?, extname = ?, email = ?, password = ?, image = ? WHERE id = ?");
                $stmt->bind_param("ssssssssi", $name, $firstname, $middlename, $lastname, $extname, $email, $hashed, $image, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, firstname = ?, middlename = ?, lastname = ?, extname = ?, email = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssssi", $name, $firstname, $middlename, $lastname, $extname, $email, $hashed, $id);
            }
        } else {
            if ($image) {
                $stmt = $conn->prepare("UPDATE users SET name = ?, firstname = ?, middlename = ?, lastname = ?, extname = ?, email = ?, image = ? WHERE id = ?");
                $stmt->bind_param("sssssssi", $name, $firstname, $middlename, $lastname, $extname, $email, $image, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, firstname = ?, middlename = ?, lastname = ?, extname = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $name, $firstname, $middlename, $lastname, $extname, $email, $id);
            }
        }
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            if($password)AuthSecurity::revoke_sessions($id);
            $conn->query("DELETE FROM user_roles WHERE user_id = " . intval($id));
            if (!empty($role_ids)) {
                $stmt_role = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                foreach ($role_ids as $r_id) {
                    $stmt_role->bind_param("ii", $id, $r_id);
                    $stmt_role->execute();
                }
                $stmt_role->close();
            }
        }
        return $success;
    }

    public static function update_profile($id, $firstname, $middlename, $lastname, $extname, $email, $birthdate, $bio, $layers, $address, $purok, $barangay, $town, $city, $province, $country, $zip, $store, $avatar_url = null) {
        global $conn;
        $name_parts = array_filter([$firstname, $middlename, $lastname, $extname]);
        $real_name = implode(' ', $name_parts);

        $stmt = $conn->prepare("UPDATE users SET 
            name = ?, 
            email = ?, 
            firstname = ?, 
            middlename = ?, 
            lastname = ?, 
            extname = ?, 
            birthdate = ?, 
            bio = ?, 
            layers = ?, 
            address = ?, 
            purok = ?, 
            barangay = ?, 
            town = ?, 
            city = ?, 
            province = ?, 
            country = ?, 
            zip = ?, 
            store = ?,
            avatar_url = ?
            WHERE id = ?");
        
        $stmt->bind_param("sssssssssssssssssssi", 
            $real_name, 
            $email, 
            $firstname, 
            $middlename, 
            $lastname, 
            $extname, 
            $birthdate, 
            $bio, 
            $layers, 
            $address, 
            $purok, 
            $barangay, 
            $town, 
            $city, 
            $province, 
            $country, 
            $zip, 
            $store,
            $avatar_url,
            $id
        );
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function log_activity($user_id, $action, $description = null) {
        global $conn;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, ?)");
        $event_time=(new DateTimeImmutable('now',new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
        $stmt->bind_param("issss", $user_id, $action, $description, $ip,$event_time);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public static function set_status($id, $status) {
        global $conn;
        if (!in_array($status, ['online', 'offline'])) return false;
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function delete($id) {
        require_once __DIR__.'/CashShift.php';
        return RecipeInventory::atomic(function()use($id){
            global $conn;
            RecipeInventory::rows('SELECT id FROM users WHERE id=? FOR UPDATE','i',[$id]);
            if (CashShift::ready() && RecipeInventory::rows('SELECT id FROM cash_shifts WHERE active_cashier=?','i',[$id])) return false;
            $stmt = $conn->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        });
    }

    public static function get_user_roles($user_id) {
        global $conn;
        $stmt = $conn->prepare("SELECT r.* FROM roles r INNER JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public static function is_product_branch_admin($userId) {
        foreach(self::get_user_roles($userId) as $role){
            $name=strtolower(preg_replace('/[ _-]+/','',$role['name']));
            if(in_array($name,['admin','superadmin'],true))return true;
        }
        return false;
    }
    public static function can_change_product_branch($userId) {
        if(!self::has_permission($userId,'edit_product'))return false;
        foreach(self::get_user_roles($userId) as $role){
            $name=strtolower(preg_replace('/[ _-]+/','',$role['name']));
            if(in_array($name,['admin','superadmin'],true))return true;
        }
        return false;
    }
    public static function is_superadmin($user_id) {
        global $conn;
        $stmt=$conn->prepare("SELECT 1 FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.id=? AND u.deleted_at IS NULL AND LOWER(REPLACE(REPLACE(REPLACE(r.name,' ',''),'_',''),'-',''))='superadmin' LIMIT 1");
        $stmt->bind_param('i',$user_id);$stmt->execute();$found=(bool)$stmt->get_result()->fetch_assoc();$stmt->close();return $found;
    }

    public static function can_manage_user($actor, $target) {
        global $conn;
        if(self::is_superadmin($actor)) return true;
        if(self::is_superadmin($target)) return false;
        $stmt=$conn->prepare('SELECT DISTINCT p.name FROM user_roles ur JOIN role_permissions rp ON rp.role_id=ur.role_id JOIN permissions p ON p.id=rp.permission_id WHERE ur.user_id=?');
        $stmt->bind_param('i',$target);$stmt->execute();$permissions=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
        foreach($permissions as $permission) if(!self::has_permission($actor,$permission['name'])) return false;
        return true;
    }

    public static function has_permission($user_id, $permission_name) {
        global $conn;
        $canonical = $permission_name === 'manage_cms' ? 'access_website_admin' : $permission_name;
        $legacy = $canonical === 'access_website_admin' ? 'manage_cms' : $canonical;
        $stmt=$conn->prepare("SELECT 1 FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id LEFT JOIN role_permissions rp ON rp.role_id=r.id LEFT JOIN permissions p ON p.id=rp.permission_id WHERE u.id=? AND u.deleted_at IS NULL AND (p.name IN (?,?) OR LOWER(REPLACE(REPLACE(REPLACE(r.name,' ',''),'_',''),'-',''))='superadmin') LIMIT 1");
        $stmt->bind_param('iss',$user_id,$canonical,$legacy);$stmt->execute();$found=(bool)$stmt->get_result()->fetch_assoc();$stmt->close();return $found;
    }

    public static function update_password($id, $new_password) {
        global $conn;
        require_once __DIR__.'/AuthSecurity.php';
        if($error=AuthSecurity::password_error($new_password))throw new DomainException($error);
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $id);
        $success = $stmt->execute();
        $stmt->close();
        if($success)AuthSecurity::revoke_sessions($id);
        return $success;
    }

    public static function verify_pin($user_id, $pin) {
        global $conn;
        $stmt = $conn->prepare("SELECT pos_pin FROM users WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($result && !empty($result['pos_pin'])) {
            require_once __DIR__.'/SystemPolicy.php';
            return SystemPolicy::verifyPin($pin,$result['pos_pin']);
        }
        return false;
    }
}
