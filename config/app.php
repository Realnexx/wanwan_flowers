<?php
date_default_timezone_set('Asia/Manila');
// This loader deliberately has no database or schema side effects.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
try {
    $file = __DIR__ . '/config.local.php';
    if (!is_file($file)) {
        throw new RuntimeException('Private application configuration is missing.');
    }
    $appConfig = require $file;
    if (!is_array($appConfig) || !in_array($appConfig['environment'] ?? '', ['local', 'production'], true)) {
        throw new RuntimeException('Invalid application environment.');
    }
    foreach (['host', 'user', 'password', 'name'] as $key) {
        if (!isset($appConfig['database'][$key]) || !is_string($appConfig['database'][$key])) {
            throw new RuntimeException('Incomplete database configuration.');
        }
    }
    $base = $appConfig['base_path'] ?? null;
    if (!is_string($base) || !preg_match('~^/(?:[A-Za-z0-9_-]+/)*$~D', $base)) {
        throw new RuntimeException('Base path must be / or a path such as /pos/.');
    }
    // Local checkouts may live under a different folder than the saved deployment.
    // Derive one shared base for forms, redirects, assets, and session cookies.
    if ($appConfig['environment'] === 'local' && PHP_SAPI !== 'cli' && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);
        $applicationRoot = realpath(__DIR__ . '/..');
        if ($documentRoot && $applicationRoot) {
            $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
            $applicationRoot = str_replace('\\', '/', $applicationRoot);
            if (strcasecmp($applicationRoot, $documentRoot) === 0) $base = '/';
            elseif (stripos($applicationRoot, $documentRoot . '/') === 0) {
                $relative = substr($applicationRoot, strlen($documentRoot) + 1);
                $base = '/' . implode('/', array_map('rawurlencode', explode('/', $relative))) . '/';
            }
        }
    }
    define('APP_ENV', $appConfig['environment']);
    define('APP_BASE_PATH', $base);
    if (APP_ENV === 'local') {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    }
} catch (Throwable $error) {
    error_log('Application configuration error: ' . $error->getMessage());
    http_response_code(503);
    exit('Service temporarily unavailable.');
}
