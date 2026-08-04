<?php
declare(strict_types=1);

if (defined('APP_BOOTSTRAPPED')) {
    return;
}
define('APP_BOOTSTRAPPED', true);

$envFile = __DIR__ . '/env.php';
if (!file_exists($envFile)) {
    http_response_code(500);
    die('ملف الإعداد app/config/env.php غير موجود. انسخ env.php.example إليه واملأ بيانات الاتصال.');
}
$env = require $envFile;

define('APP_ENV', $env['APP_ENV'] ?? 'production');
define('APP_URL', rtrim($env['APP_URL'] ?? '', '/'));
define('APP_DEBUG', (bool)($env['APP_DEBUG'] ?? false));

define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? '');
define('DB_USER', $env['DB_USER'] ?? '');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('DB_PORT', $env['DB_PORT'] ?? '3306');

define('APP_ROOT', dirname(__DIR__, 2));
define('APP_DIR', dirname(__DIR__));

// إعدادات عامة للتطبيق (يمكن نقلها لجدول settings لاحقًا للتعديل من الواجهة)
define('DEFAULT_CURRENCY', 'ر.س');
define('INVOICE_PREFIX_SALE', 'S-');
define('INVOICE_PREFIX_PURCHASE', 'P-');
define('DEFAULT_LOW_STOCK_THRESHOLD', 5);

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_DIR . '/php-error.log');
date_default_timezone_set('Asia/Riyadh');

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('inv_session');
    session_start();
}

require_once APP_DIR . '/config/db.php';
require_once APP_DIR . '/includes/functions.php';
require_once APP_DIR . '/includes/csrf.php';
require_once APP_DIR . '/includes/flash.php';
require_once APP_DIR . '/includes/auth.php';
