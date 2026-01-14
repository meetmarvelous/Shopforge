<?php
/**
 * ShopForge Configuration Sample File
 * 
 * Copy this file to config.php and update the values for your environment.
 * 
 * @package ShopForge
 * @version 1.0
 */

// Prevent direct access
if (!defined('SHOPFORGE_INIT') && basename($_SERVER['PHP_SELF']) === 'config.php') {
    http_response_code(403);
    exit('Direct access not permitted');
}

// =============================================================================
// ENVIRONMENT SETTINGS
// =============================================================================

define('APP_ENV', 'development'); // Options: 'development', 'staging', 'production'
define('APP_DEBUG', APP_ENV === 'development');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// =============================================================================
// DATABASE CONFIGURATION
// =============================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// =============================================================================
// APPLICATION SETTINGS
// =============================================================================

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Calculate base path from project root directory, not current script
$documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot = str_replace('\\', '/', __DIR__);
$basePath = '';

if (!empty($documentRoot) && strpos($projectRoot, $documentRoot) === 0) {
    $basePath = substr($projectRoot, strlen($documentRoot));
}

$basePath = rtrim($basePath, '/');

define('BASE_URL', $protocol . '://' . $host . $basePath);
define('BASE_PATH', rtrim(str_replace('\\', '/', __DIR__), '/'));

define('SITE_NAME', 'ShopForge');
define('SITE_TAGLINE', 'Your Ultimate Shopping Destination');
define('SITE_EMAIL', 'support@yoursite.com');
define('SITE_PHONE', '+1 234 567 8900');

define('CURRENCY_CODE', 'NGN');
define('CURRENCY_SYMBOL', '₦');
define('CURRENCY_POSITION', 'before');

date_default_timezone_set('Africa/Lagos');

// =============================================================================
// SECURITY SETTINGS
// =============================================================================

define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LIFETIME', 3600);

define('SESSION_NAME', 'SHOPFORGE_SESSION');
define('SESSION_LIFETIME', 7200);
define('SESSION_SECURE', !APP_DEBUG);
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_MIXED_CASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SYMBOLS', false);

define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('UPLOAD_ALLOWED_IMAGES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// =============================================================================
// EMAIL CONFIGURATION (PHPMailer)
// =============================================================================

define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 465);
define('MAIL_USERNAME', 'your-email@example.com');
define('MAIL_PASSWORD', 'your-email-password');
define('MAIL_ENCRYPTION', 'ssl');
define('MAIL_FROM_ADDRESS', 'noreply@yoursite.com');
define('MAIL_FROM_NAME', SITE_NAME);

// =============================================================================
// PAYMENT GATEWAY (Paystack)
// =============================================================================

define('PAYSTACK_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co');
define('PAYSTACK_CALLBACK_URL', BASE_URL . '/payment-callback.php');

define('PAYMENT_MODE', 'sandbox'); // Options: 'sandbox', 'production'

// =============================================================================
// RECAPTCHA (Optional)
// =============================================================================

define('RECAPTCHA_ENABLED', false);
define('RECAPTCHA_SITE_KEY', 'your-site-key');
define('RECAPTCHA_SECRET_KEY', 'your-secret-key');

// =============================================================================
// PAGINATION
// =============================================================================

define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);
define('USERS_PER_PAGE', 20);
define('REVIEWS_PER_PAGE', 5);
define('ADMIN_ITEMS_PER_PAGE', 15);

// =============================================================================
// IMAGE SETTINGS
// =============================================================================

define('PRODUCT_IMAGE_WIDTH', 800);
define('PRODUCT_IMAGE_HEIGHT', 800);
define('PRODUCT_THUMB_WIDTH', 300);
define('PRODUCT_THUMB_HEIGHT', 300);
define('USER_AVATAR_SIZE', 200);

// =============================================================================
// TAX AND SHIPPING
// =============================================================================

define('TAX_ENABLED', true);
define('TAX_RATE', 7.5);
define('FREE_SHIPPING_THRESHOLD', 50000);
define('DEFAULT_SHIPPING_COST', 2000);

// =============================================================================
// PATHS
// =============================================================================

define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('PRODUCTS_UPLOAD_PATH', UPLOADS_PATH . '/products');
define('USERS_UPLOAD_PATH', UPLOADS_PATH . '/users');

define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('PRODUCTS_UPLOAD_URL', UPLOADS_URL . '/products');
define('USERS_UPLOAD_URL', UPLOADS_URL . '/users');

// =============================================================================
// INITIALIZATION FLAG
// =============================================================================

define('SHOPFORGE_INIT', true);
