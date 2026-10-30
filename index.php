<?php
$flowerRoute = $_GET['route'] ?? 'home';
if (is_string($flowerRoute) && in_array($flowerRoute, ['home', 'products', 'pricing', 'about', 'contact', 'branches', 'flowers/detail', 'flowers/bag', 'flowers/checkout', 'flowers/place-order', 'flowers/order', 'customer/home', 'customer/orders', 'customer/history', 'customer/profile', 'customer/receipt'], true) && !isset($_GET['init'])) {
    require __DIR__ . '/views/website/flowers.php';
    exit;
}
require_once __DIR__ . '/config/db.php';

// Initialization is a manual CLI operation, never a public route.
if (isset($_GET['init'])) {
    http_response_code(403);
    exit('Database initialization is disabled over HTTP.');
}

// Require Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/PosController.php';
require_once __DIR__ . '/controllers/ExpenseController.php';
require_once __DIR__ . '/controllers/InventoryController.php';
require_once __DIR__ . '/controllers/WebsiteController.php';

// Instantiate Controllers
$authController = new AuthController();
$adminController = new AdminController();
$posController = new PosController();
$expenseController = new ExpenseController();
$inventoryController = new InventoryController();
$websiteController = new WebsiteController();

// Route Dispatcher
$route = $_GET['route'] ?? 'home';
require_once __DIR__ . '/middleware/session.php';
enforce_session_timeout();
if (!empty($_SESSION['user']) || in_array($route, ['login','customer_login','register','forgot_password','reset_password'], true)) {
    header('Cache-Control: no-store, private');
    header('Referrer-Policy: no-referrer');
}
require_once __DIR__ . '/middleware/access_policy.php';
authorize_page($route);

switch ($route) {
    case 'admin/promotions': require __DIR__.'/controllers/PromotionController.php'; (new PromotionController())->index(); break;
    case 'cart/promotion': $websiteController->cart_promotion(); break;
    case 'admin/monitoring':
        require __DIR__.'/controllers/MonitoringController.php';
        (new MonitoringController())->index(); break;
    // Public Website Routes
    case 'home':
        $websiteController->home();
        break;
    case 'products':
        $websiteController->products();
        break;
    case 'product_detail':
        $websiteController->product_detail();
        break;
    case 'about':
        $websiteController->about();
        break;
    case 'pricing': $websiteController->pricing(); break;
    case 'admin/pricing': $adminController->pricing(); break;
    case 'request_demo': $websiteController->request_demo(); break;
    case 'admin/demo_requests/data': $adminController->demoRequestsData(); break;
    case 'admin/demo_requests': $adminController->demoRequests(); break;
    case 'contact':
        $websiteController->contact();
        break;
    case 'branches':
        $websiteController->branches();
        break;
    case 'cart':
        $websiteController->cart();
        break;
    case 'cart/checkout': $websiteController->checkout(); break;
    case 'orders/cancel_payment': $websiteController->cancel_order_payment(); break;
    case 'orders/pay': $websiteController->pay_order(); break;
    case 'orders/verify_payment': $websiteController->verify_order_payment(); break;
    case 'orders': $websiteController->orders(); break;
    case 'order_tracking': $websiteController->order_tracking(); break;
    case 'admin/online_orders':
        require __DIR__.'/controllers/OnlineOrderController.php';
        (new OnlineOrderController())->index(); break;
    case 'cart/add':
        $websiteController->cart_add();
        break;
    case 'cart/remove':
        $websiteController->cart_remove();
        break;
    case 'cart/update':
        $websiteController->cart_update();
        break;

    // Authentication Routes
    case 'login':
        $authController->login();
        break;
    case 'customer_login':
        $authController->customer_login();
        break;
    case 'forgot_password':
        $authController->forgot_password();
        break;
    case 'reset_password':
        $authController->reset_password();
        break;
    case 'register':
        $authController->register();
        break;
    case 'setup_profile':
        $authController->setup_profile();
        break;
    case 'notification':
        redirect('?route=home');
        break;
    case 'logout':
        $authController->logout();
        break;

    // Admin Dashboard & Management Routes
    case 'admin/start':
        $context = User::access_context($_SESSION['user']['id']);
        if (($context['global'] || $context['branch_id']) && User::has_permission($_SESSION['user']['id'], 'view_dashboard')) {
            redirect('?route=dashboard');
        }
        require __DIR__ . '/views/modules/staff_start.php';
        break;
    case 'dashboard':
        $adminController->dashboard();
        break;
    case 'admin/expenses':
        $expenseController->index();
        break;
    case 'admin/users':
        $adminController->users();
        break;
    case 'admin/users/data':
        $adminController->getUserData();
        break;
    case 'admin/users/single':
        $adminController->getSingleUser();
        break;
    case 'admin/roles/data':
        $adminController->getRoles();
        break;
    case 'admin/permissions/data':
        $adminController->getPermissionsData();
        break;
    case 'admin/expenses/data':
        $expenseController->data();
        break;
    case 'admin/expenses/print':
        $expenseController->print();
        break;
    case 'admin/expenses/summary_print':
        $expenseController->summary_print();
        break;
    case 'admin/roles':
        $adminController->roles();
        break;
    case 'admin/cms':
        $adminController->cms();
        break;
    case 'admin/reports':
        $adminController->reports();
        break;
    case 'admin/reports/print':
        $adminController->reportsPrint();
        break;
    case 'admin/branches':
        $adminController->branches();
        break;
    case 'admin/branches/report':
        $adminController->branchesReport();
        break;
    case 'admin/settings':
        $adminController->settings();
        break;
    case 'admin/activity_logs':
        $adminController->activityLogs();
        break;
    case 'admin/activity_logs/data':
        $adminController->getActivityLogsData();
        break;
    case 'admin/activity_logs/print':
        $adminController->activityLogsPrint();
        break;

    // Cashier POS Routes
    case 'pos':
        $posController->index();
        break;
    case 'pos/shifts':
        require_once __DIR__.'/controllers/CashShiftController.php';
        (new CashShiftController())->page();
        break;
    case 'pos/history':
        $posController->history();
        break;
    case 'pos/history/data':
        $posController->historyData();
        break;
    case 'pos/history/stats':
        $posController->historyStats();
        break;
    case 'pos/history/update_status':
        $posController->updateStatus();
        break;
    case 'pos/receipt':
        $posController->receipt();
        break;

    // Inventory Management Routes
    case 'inventory/suppliers':
    case 'inventory/purchases':
    case 'inventory/purchase_reports':
        require_once __DIR__.'/controllers/PurchasingController.php';
        (new PurchasingController())->page($route);
        break;
    case 'inventory/recipes/data':
        $inventoryController->recipesData();
        break;
    case 'inventory/recipes':
        $inventoryController->recipes();
        break;
    case 'inventory/transfers':
        $inventoryController->transfers();
        break;
    case 'inventory/products':
        $inventoryController->products();
        break;
    case 'inventory/categories':
        $inventoryController->categories();
        break;
    case 'inventory/logs/print':
        $inventoryController->printLogs();
        break;
    case 'inventory/logs':
        $inventoryController->logs();
        break;
    case 'inventory/low_stock':
        $inventoryController->low_stock();
        break;

    // AJAX Endpoints Dispatch
    case 'ajax/pos_cart':
        require __DIR__ . '/ajax/pos_cart.php';
        break;
    case 'ajax/cash_shift_audit':
        require __DIR__.'/ajax/cash_shift_audit.php';
        break;
    case 'ajax/pos_drawer':
        require __DIR__.'/ajax/pos_drawer.php';
        break;
    case 'ajax/inventory_action':
        require __DIR__ . '/ajax/inventory_action.php';
        break;
    case 'ajax/role_action':
        require __DIR__ . '/ajax/role_action.php';
        break;
    case 'ajax/cms_action':
        require __DIR__ . '/ajax/cms_action.php';
        break;
    case 'ajax/branch_action':
        require __DIR__ . '/ajax/branch_action.php';
        break;
    case 'ajax/expense_action':
        require __DIR__ . '/ajax/expense_action.php';
        break;

    // Default 404 Fallback
    default:
        header("HTTP/1.0 404 Not Found");
        echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'>
                <h1>404 - Page Not Found</h1>
                <p>The requested route '{$route}' does not exist.</p>
                <a href='" . url('?route=home') . "' style='padding: 10px 20px; background: #ff4d6d; color: white; text-decoration: none; border-radius: 20px;'>Return to Homepage</a>
              </div>";
        break;
}
