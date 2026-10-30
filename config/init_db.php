<?php
if (PHP_SAPI !== 'cli' || !in_array('--seed-demo', $argv ?? [], true)) {
    http_response_code(403);
    exit('Demo initialization requires an explicit CLI invocation.');
}

require_once __DIR__ . '/app.php';
if (APP_ENV !== 'local') {
    exit('Demo seeding is disabled in production.');
}
require_once __DIR__ . '/db.php';

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) UNIQUE NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        status ENUM('online', 'offline') DEFAULT 'offline',
        firstname VARCHAR(100) NULL,
        middlename VARCHAR(100) NULL,
        lastname VARCHAR(100) NULL,
        extname VARCHAR(20) NULL,
        birthdate DATE NULL,
        bio TEXT NULL,
        layers TEXT NULL,
        address VARCHAR(255) NULL,
        purok VARCHAR(100) NULL,
        barangay VARCHAR(100) NULL,
        town VARCHAR(100) NULL,
        city VARCHAR(100) NULL,
        province VARCHAR(100) NULL,
        country VARCHAR(100) NULL,
        zip VARCHAR(20) NULL,
        store VARCHAR(100) NULL,
        avatar_url VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        description VARCHAR(255),
        is_system TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        description VARCHAR(255),
        module VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS role_permissions (
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        PRIMARY KEY (role_id, permission_id),
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS user_roles (
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        PRIMARY KEY (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description VARCHAR(255)
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        name VARCHAR(150) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        stock INT NOT NULL DEFAULT 0,
        low_stock_threshold INT NOT NULL DEFAULT 10,
        image_url VARCHAR(255) DEFAULT 'assets/images/default.png',
        is_featured TINYINT(1) DEFAULT 0,
        is_best_seller TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS inventory_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        quantity INT NOT NULL,
        remarks VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cashier_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        discount_amount DECIMAL(10,2) DEFAULT 0.00,
        final_amount DECIMAL(10,2) NOT NULL,
        amount_tendered DECIMAL(10,2) NOT NULL,
        change_amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'Cash',
        order_type VARCHAR(50) DEFAULT 'Dine-in',
        status VARCHAR(50) DEFAULT 'Completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS sales_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS contacts_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS website_content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_key VARCHAR(50) NOT NULL UNIQUE,
        content_value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS branches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        address VARCHAR(255),
        phone VARCHAR(50),
        status VARCHAR(20) DEFAULT 'Active',
        latitude DECIMAL(10, 8) NULL,
        longitude DECIMAL(11, 8) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS ingredients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        image_url VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        description VARCHAR(255) NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(255) NOT NULL,
        user_id INT NULL,
        product_id INT NOT NULL,
        qty INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;"
];

// Existing tables and records are preserved. Demo seeding is local-only.

foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        die("Table creation failed: " . $conn->error);
    }
}

// Ensure all onboarding profile columns exist in users table (migration helper)
$columns_to_add = [
    'firstname' => 'VARCHAR(100) NULL',
    'middlename' => 'VARCHAR(100) NULL',
    'lastname' => 'VARCHAR(100) NULL',
    'extname' => 'VARCHAR(20) NULL',
    'birthdate' => 'DATE NULL',
    'bio' => 'TEXT NULL',
    'layers' => 'TEXT NULL',
    'address' => 'VARCHAR(255) NULL',
    'purok' => 'VARCHAR(100) NULL',
    'barangay' => 'VARCHAR(100) NULL',
    'town' => 'VARCHAR(100) NULL',
    'city' => 'VARCHAR(100) NULL',
    'province' => 'VARCHAR(100) NULL',
    'country' => 'VARCHAR(100) NULL',
    'zip' => 'VARCHAR(20) NULL',
    'phone' => 'VARCHAR(40) NULL',
    'store' => 'VARCHAR(100) NULL',
    'avatar_url' => 'VARCHAR(255) NULL'
];

foreach ($columns_to_add as $col => $definition) {
    $check_col = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
    if ($check_col && $check_col->num_rows == 0) {
        if (!$conn->query("ALTER TABLE users ADD COLUMN $col $definition")) {
            die("Failed to alter users table for column $col: " . $conn->error);
        }
    }
}

// Seed Permissions
$permissions = [
    ['view_dashboard', 'View Admin Dashboard analytics', 'Dashboard'],
    ['add_product', 'Add new products to the catalog', 'Inventory'],
    ['edit_product', 'Edit existing product details', 'Inventory'],
    ['delete_product', 'Remove products from the catalog', 'Inventory'],
    ['manage_inventory', 'Adjust stock levels and view logs', 'Inventory'],
    ['print_products', 'Export or print product lists', 'Inventory'],
    ['view_product', 'View product list and details', 'Inventory'],
    ['access_pos', 'Access Cashier POS system', 'POS'],
    ['view_reports', 'View sales and inventory reports', 'Reports'],
    ['manage_users', 'Manage user accounts and assignments', 'Users'],
    ['manage_roles', 'Create roles and assign permissions', 'RBAC'],
    ['manage_settings', 'Configure system-wide settings and branding', 'System'],
    ['access_website_admin', 'Manage website CMS content', 'Website']
];

$stmt = $conn->prepare("INSERT IGNORE INTO permissions (name, description, module) VALUES (?, ?, ?)");
foreach ($permissions as $perm) {
    $stmt->bind_param("sss", $perm[0], $perm[1], $perm[2]);
    $stmt->execute();
}
$stmt->close();

// Seed Roles
$roles = [
    ['Admin', 'Full administrator access to all system modules', 1],
    ['Cashier', 'Access to POS system and transaction history', 1],
    ['Customer', 'Public website user account', 1]
];

$stmt = $conn->prepare("INSERT IGNORE INTO roles (name, description, is_system) VALUES (?, ?, ?)");
foreach ($roles as $role) {
    $stmt->bind_param("ssi", $role[0], $role[1], $role[2]);
    $stmt->execute();
}
$stmt->close();

// Assign all permissions to Admin
$admin_role_query = $conn->query("SELECT id FROM roles WHERE name = 'Admin'");
$admin_role_id = $admin_role_query->fetch_assoc()['id'];

$all_perms = $conn->query("SELECT id FROM permissions");
$stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
while ($perm = $all_perms->fetch_assoc()) {
    $stmt->bind_param("ii", $admin_role_id, $perm['id']);
    $stmt->execute();
}
$stmt->close();

// Assign POS permission to Cashier
$cashier_role_query = $conn->query("SELECT id FROM roles WHERE name = 'Cashier'");
$cashier_role_id = $cashier_role_query->fetch_assoc()['id'];

$pos_perm_query = $conn->query("SELECT id FROM permissions WHERE name = 'access_pos'");
$pos_perm_id = $pos_perm_query->fetch_assoc()['id'];

$stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
$stmt->bind_param("ii", $cashier_role_id, $pos_perm_id);
$stmt->execute();
$stmt->close();

// Seed Default Users
$users = [
    ['Admin Stacey', 'admin', 'admin@haluhalu.com', password_hash('admin123', PASSWORD_BCRYPT), $admin_role_id],
    ['Cashier Jane', 'cashier', 'cashier@haluhalu.com', password_hash('cashier123', PASSWORD_BCRYPT), $cashier_role_id]
];

foreach ($users as $u) {
    $check = $conn->query("SELECT id FROM users WHERE email = '" . $conn->real_escape_string($u[2]) . "'");
    if ($check->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $u[0], $u[1], $u[2], $u[3]);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        $stmt->close();

        $stmt2 = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt2->bind_param("ii", $user_id, $u[4]);
        $stmt2->execute();
        $stmt2->close();
    }
}

// Seed Categories
$categories = [
    ['Halo-halo', 'Signature shaved ice desserts with rich ingredients'],
    ['Drinks', 'Refreshing beverages, milk teas, and fruit shakes'],
    ['Desserts', 'Cakes, leche flan, pastries, and sweet treats'],
    ['Add-ons', 'Extra toppings, scoops of ice cream, and jellies']
];

$stmt = $conn->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, ?)");
foreach ($categories as $cat) {
    $stmt->bind_param("ss", $cat[0], $cat[1]);
    $stmt->execute();
}
$stmt->close();

// Seed Website CMS Content
$cms_content = [
    ['hero_title', 'Experience Ultimate Sweetness at Stacey Halu Halu'],
    ['hero_subtitle', 'Premium shaved ice desserts crafted with love, authentic ingredients, and a modern twist.'],
    ['about_text', 'Welcome to Stacey Halu Halu! Founded with a passion for bringing the best traditional and modern Filipino desserts to our community. Every cup of our signature Halo-Halo is packed with premium leche flan, ube halaya, sweet macapuno, and our secret creamy milk blend.'],
    ['contact_address', '123 Sweet Tooth Blvd, Sugar City, Philippines'],
    ['contact_phone', '+63 912 345 6789'],
    ['contact_email', 'hello@staceyhaluhalu.com']
];

$stmt = $conn->prepare("INSERT IGNORE INTO website_content (section_key, content_value) VALUES (?, ?)");
foreach ($cms_content as $cms) {
    $stmt->bind_param("ss", $cms[0], $cms[1]);
    $stmt->execute();
}
$stmt->close();

// Seed Branches with Geolocation Coordinates
$branches = [
    ['Quezon City (Flagship)', '123 Sweet Tooth Blvd, Quezon City', '+63 912 345 6789', 14.6508, 121.0747],
    ['Makati CBD', '456 Ayala Ave, Makati City', '+63 917 123 4567', 14.5524, 121.0205],
    ['Manila Bay', '789 Seaside Blvd, Pasay City', '+63 920 987 6543', 14.5262, 120.9790],
    ['Cebu IT Park', '101 Abad St, Cebu City', '+63 932 456 7890', 10.3278, 123.9061],
    ['Davao Town Center', '202 Roxas Ave, Davao City', '+63 945 789 0123', 7.0722, 125.6119],
    ['Tagbilaran Plaza Branch', 'Plaza Area, Tagbilaran City, Bohol', '+63 938 111 2222', 9.6412, 123.8524],
    ['Panglao Alona Branch', 'Alona Beach Road, Panglao, Bohol', '+63 938 333 4444', 9.5492, 123.7745]
];
$stmt_branch = $conn->prepare("INSERT IGNORE INTO branches (name, address, phone, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
foreach ($branches as $branch) {
    $stmt_branch->bind_param("sssdd", $branch[0], $branch[1], $branch[2], $branch[3], $branch[4]);
    $stmt_branch->execute();
}
$stmt_branch->close();

// Seed Sample Products
$cat_halo_query = $conn->query("SELECT id FROM categories WHERE name = 'Halo-halo'");
$cat_halo_id = $cat_halo_query->fetch_assoc()['id'] ?? 1;

$cat_drinks_query = $conn->query("SELECT id FROM categories WHERE name = 'Drinks'");
$cat_drinks_id = $cat_drinks_query->fetch_assoc()['id'] ?? 2;

$cat_desserts_query = $conn->query("SELECT id FROM categories WHERE name = 'Desserts'");
$cat_desserts_id = $cat_desserts_query->fetch_assoc()['id'] ?? 3;

$products = [
    [$cat_halo_id, 'Stacey Supreme Halo-Halo', 'Our best-selling signature halo-halo with 12 premium ingredients, double ube scoop, and special leche flan slice.', 149.00, 80.00, 50, 10, 'assets/images/halo_supreme.jpg', 1, 1],
    [$cat_halo_id, 'Classic Fiesta Halo-Halo', 'Traditional halo-halo with sweet beans, nata de coco, kaong, jello, and evaporated milk.', 119.00, 65.00, 40, 10, 'assets/images/halo_classic.jpg', 1, 0],
    [$cat_halo_id, 'Ube Macapuno Overload', 'Special halo-halo loaded with extra pure ube halaya and soft macapuno strings.', 139.00, 75.00, 35, 10, 'assets/images/halo_ube.jpg', 0, 1],
    [$cat_drinks_id, 'Mango Graham Shake', 'Fresh ripe mangoes blended with milk, crushed graham crackers, and whipped cream.', 99.00, 50.00, 60, 15, 'assets/images/mango_graham.jpg', 1, 1],
    [$cat_drinks_id, 'Taro Cream Milk Tea', 'Rich taro infused milk tea with chewy brown sugar boba pearls.', 89.00, 45.00, 80, 20, 'assets/images/taro_milktea.jpg', 0, 0],
    [$cat_desserts_id, 'Creamy Leche Flan', 'Melt-in-your-mouth traditional egg custard topped with rich caramel syrup.', 150.00, 70.00, 25, 5, 'assets/images/leche_flan.jpg', 1, 1]
];

$stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, cost_price, stock, low_stock_threshold, image_url, is_featured, is_best_seller) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($products as $prod) {
    $check = $conn->query("SELECT id FROM products WHERE name = '" . $conn->real_escape_string($prod[1]) . "'");
    if ($check->num_rows === 0) {
        $stmt->bind_param("issddiisii", $prod[0], $prod[1], $prod[2], $prod[3], $prod[4], $prod[5], $prod[6], $prod[7], $prod[8], $prod[9]);
        $stmt->execute();
        $prod_id = $stmt->insert_id;

        // Log initial stock
        $log_stmt = $conn->prepare("INSERT INTO inventory_logs (product_id, user_id, action, quantity, remarks) VALUES (?, 1, 'initial', ?, 'Initial stock seeding')");
        $log_stmt->bind_param("ii", $prod_id, $prod[5]);
        $log_stmt->execute();
        $log_stmt->close();
    }
}
// Seed Ingredients with High Quality Hosted Images
$ingredients = [
    ['Ube', 'https://lh3.googleusercontent.com/aida-public/AB6AXuAHAKU0LeesX7pTGal9WSJB9YBpkLbGcy3sBrfpkglUlSvv_tJOK0UV49g_vC3n02nRllhIRhCp74a1nOx_r_3CGobQsVy5OV3KebaWaTLn-Jh9-2nIr7G0ytKn4UWmL8bMOASe9egDOSmQVYSo5iCqLDuSzuExD7F8gNdFK27u7AkPMS-4i9slWRAUVnOZgZqNFnedU4_YY3e6TBKXLdA5PpvfiYr-S0DslX2kadnNeR09CrVefCMTMXrUmOb12GjnWpTlagy6MPI'],
    ['Leche Flan', 'https://lh3.googleusercontent.com/aida-public/AB6AXuCIt5CSeJJ2kkqxn51omKtMRHh5m0SsqDvn1rngdOAgi9jOAaTvxWiwREtIC9cfUcVYT0V4cZzIWvlBGBBR7Baiy9XGNIrZOFUL5gshsR2Z931tne_i9jroq4EFVRvLDiCgjRbPRzYjMuQPqOyEcCIx7i_6WkP2gjfxIfbRLqUAjvZwEQaztLwojwqJr0DbFMPvnA4grryxznoLc1A-qO-bt5eYNPcFuoOryquvcVakMQr1FBTGJuKw2rcPv_LrVyksZ1FDNWSeOqI'],
    ['Sweet Mango', 'https://lh3.googleusercontent.com/aida-public/AB6AXuCMRCcZvGukWIf0WGBH8mjNtYrLbKGmyMyr1k98OEyIA6PFVVsy7HlK6spYzrfGGZAv64SMH0VxU6ZQrx9RfIWUdxgy-11QbrsN4AAB6iJgwSjE2NVgY1MEWmRklmJSqpdZ7AMtXUGQ8CP9pbSyMQOo-X1EpyWySEJyBZWu58QOGG6S7GluaAGnuVWbKiw7eXdyrD0Ybk_5IrLGAyOh-du6uRBiy8mKdEcgZ5f1zIe5Ek4AbEnAZs2vbBUHDYUAgPVEGExHKYP7U3M'],
    ['Nata de Coco', 'https://lh3.googleusercontent.com/aida-public/AB6AXuAh6eGMBPVKS1sQ8BYTXAJ4wzoo3LCytQL3zKEMKAJ_4ZwZopDEjnq0lA6dfGsQ4Ch1MzViW4w2RZ66vlPIm1wLNdhdBPNVl7Qvyn3-4R1GIc2234aMfYM343xLXxlQq8PF-0BKm1ebLjtj7LiQDNyCkJpmecKlU_YNROpVgBz8pXcsISUh3eGCFahtJ_fPdE46-yRsH6Qyf-4K1ZUgMlSBUwlj-YYAICd7sfQ9nWoobY3VF49RsrNya1BdezX1lVV46VHk6KJnuns'],
    ['Sweet Bananas', 'https://lh3.googleusercontent.com/aida-public/AB6AXuBGVnk0eA7kTF7Ol2FHuK2JJ7_4P3DNPocaoWb2iT5nMetP3HVKQM-HMm1hae1Vrf3E0HeTtivJdZufBb5zp048NfMtBsUKIgwig7xbMCpKPWL1MtJ8RvU51-VtF9TL23y2W9BtxBqM7ki99V14RHA4rX_oAPzk17U4Pxxz10vxX8NdaNPVzpMHumD_gunj-hO7l-rFXPD_N2nP9jNKkTJlJbSlSC3TR0am9rv6-dcQAwfynrMobmSQX1TNAo_cs95ac58JgDTQFIQ'],
    ['Red Beans', 'https://lh3.googleusercontent.com/aida-public/AB6AXuDueU1_jQ3LEZ6fSM_SBKKdUgTsopE0IhZpeLdxpd519uKFZuav9TkjA6f8uDZA_TWPCgfNO4iztV8MkHu84xLnX2lJ7F0tSFqq96KvPWHL01QpZgQ-qbTyVfh4j-qU02nQ9pgQqf2g5CDKOY4SAlkXM9f7xcTVFPYWAVTibGv1Iw4s8-cNo_OxtmOJxzJbURcRCApkPETgervM_nIAZ2Ec1fksAhP7u6vqVouECkUXcsaC1vuIPpxcqICP-M3FSVcdvJvtQ7uhoo8'],
    ['Macapuno', 'https://lh3.googleusercontent.com/aida-public/AB6AXuBkZZG8u0W72XEZym4BD2mog0Qf8hkESS95MIfEO0GwP1Nc8uKenigprUsi0FBIThagbzMIM7XlsJLOUdT6W6gArVXc9HEmXYkoUpnbsHCfYjUfDd7LKOajpSMx9HHOX6QqM3JyGNwWoKl_MDrcD-tnTp_1aw4SnwJwF9acdDbJVM9fKgMj6n-TScGdR2TfCsHSVKJNf_hjV1Lq2CGCCuO1doay3D5kBUnwIGLPG5d1QvE2VdgZ6wZBT0BeJj3T3igr0iRZ3WiINK0'],
    ['Rice Crispies', 'https://lh3.googleusercontent.com/aida-public/AB6AXuAlF7rM-Ra0288-aIlhfWDdUgewk9JYMv9In0_rsZHKd9eD9syMDJVhwM-YIUETQaEChwRhmdHkAG1c9Jc013hUizw40t8ErGgdNmnNYx4ftjJfsmnDfJ4D6T3zaXC7r0-YP4XBKZ9FyejiQIpDNPkdGxsqhYRanw7HpYTTMwPQS-hBO0qm-PCry7Cd3k5x-QyE-hzL8Exg_hBBqMCLcRSOuQ9MEpJSCsve8lcSTO5GiIcbur8hUGv_pD8ileR8vhbnMcEY8MPcsLw'],
    ['Vanilla Ice Cream', 'https://lh3.googleusercontent.com/aida-public/AB6AXuCuom_75fEmAQrawrwRszprdNd7mbTW-uLVHnh7vK0oZIlXiJ51O3bRSjPy2zfOnXs19wgqHa58KiiU1XP4qCp1GqPikps7CByYEWxDFZDEMjR_MV2kTMmnzhbKDq-uDPvFY3WqP3eNZsYoc0oylqlF_NZHGa75-wHJprW5fm2be_lMFq6iWViq07kIzkDKckPdkivgPOHn7-THyinajDZk44gk0FhO2VLeV43FMUgIvOuEMbwlOnmb2a0qY4PnCTGczI2ufBAMAB8'],
    ['Cheese Ice Cream', 'https://lh3.googleusercontent.com/aida-public/AB6AXuBzsE5R75Ca6g7E9ROczvJO1x0VVafs2OOSlB5jp-hdFxQHhuTfibSiGTtTTaPyOXaUdv3BMuWQf--DLN5MeX7NmH8ylxUWRGqxYr03eQB5CY6GQ9irOx4B-RXOjJqrBlgMWMFupyu-VbPrlA0fv82SRlFGI0CtyE81xVsYnKblB-GldwFiYWdOuzD0FTNxzmtwTi-1ZEN5CwTGMYIDnJTI3Tsrn14dhkL9G4m8rHEEpO0x9wdvNBwQ8M7g5iv13bwtXtVI2QapunU']
];

$stmt = $conn->prepare("INSERT INTO ingredients (name, image_url) VALUES (?, ?)");
foreach ($ingredients as $ing) {
    $check = $conn->query("SELECT id FROM ingredients WHERE name = '" . $conn->real_escape_string($ing[0]) . "'");
    if ($check->num_rows === 0) {
        $stmt->bind_param("ss", $ing[0], $ing[1]);
        $stmt->execute();
    }
}
$stmt->close();

echo "Database initialization and seeding completed successfully.";

require_once __DIR__ . '/../models/PermissionSeeder.php';
PermissionSeeder::run($conn);

require_once __DIR__.'/../models/AuthSecurity.php';
AuthSecurity::migrate($conn);

require_once __DIR__.'/../models/OnlineOrder.php';
OnlineOrder::migrate($conn);

require_once __DIR__.'/../models/Monitoring.php';
Monitoring::migrate($conn);
