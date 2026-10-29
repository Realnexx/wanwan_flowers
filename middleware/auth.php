<?php
require_once __DIR__ . '/../config/db.php';

function require_login() {
    require_once __DIR__.'/session.php';enforce_session_timeout();
    if (empty($_SESSION['user'])) {
        set_flash('danger', 'Please log in to access this page.');
        redirect('?route=login');
    }
}

function require_guest() {
    if (!empty($_SESSION['user'])) {
        require_once __DIR__ . '/../models/User.php';
        redirect('?route=' . User::landing_route($_SESSION['user']['id']));
    }
}

function enforce_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($token) || !verify_csrf($token)) {
            if (function_exists('access_denied')) access_denied('Your security token expired. Refresh and try again.',403);
            http_response_code(403);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'CSRF token mismatch.']);
                exit;
            }
            set_flash('danger', 'Invalid security token. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? '?route=home');
        }
    }
}
