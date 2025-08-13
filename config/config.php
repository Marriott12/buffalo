<?php
/**
 * Buffalo Marathon 2025 - Production Configuration
 * Generated: 2025-08-08 10:33:55 UTC
 * Environment: Production Ready
 */

// Prevent multiple inclusions
if (defined('BUFFALO_CONFIG_LOADED')) {
    return;
}
define('BUFFALO_CONFIG_LOADED', true);

// Security: Prevent direct access
if (!defined('BUFFALO_SECURE_ACCESS')) {
    die('Direct access denied');
}

// Site Configuration
define('SITE_NAME', 'Buffalo Marathon 2025');
define('SITE_URL', 'https://buffalo-marathon.com');
define('SITE_EMAIL', 'info@buffalo-marathon.com');
define('ADMIN_EMAIL', 'admin@buffalo-marathon.com');
define('NOREPLY_EMAIL', 'noreply@buffalo-marathon.com');

// Contact Information
define('CONTACT_PHONE_PRIMARY', '+260 972 545 658');
define('CONTACT_PHONE_SECONDARY', '+260 770 809 062');
define('CONTACT_PHONE_TERTIARY', '+260 771 470 868');
define('CONTACT_PHONES_ALL', '+260 972 545 658 / +260 770 809 062 / +260 771 470 868');

// Database Configuration - Production Ready
define('DB_HOST', 'localhost');
define('DB_NAME', 'envithcy_marathon');
define('DB_USER', 'envithcy_buffalo');
define('DB_PASS', 'buffalo-marathon2025');
define('DB_CHARSET', 'utf8mb4');

// Event Configuration
define('MARATHON_DATE', '2025-10-11');
define('MARATHON_TIME', '07:00:00');
define('REGISTRATION_DEADLINE', '2025-09-30 23:59:59');
define('EARLY_BIRD_DEADLINE', '2025-08-31 23:59:59');
define('EVENT_VENUE', 'Buffalo Park Recreation Centre');
define('EVENT_ADDRESS', 'Chalala-Along Joe Chibangu Road');
define('EVENT_CITY', 'Lusaka, Zambia');

// Current Status (August 12, 2025)
define('CURRENT_DATE', '2025-08-12');
define('DAYS_UNTIL_MARATHON', 60);  // Updated calculation
define('DAYS_UNTIL_DEADLINE', 49);  // Updated calculation
define('DAYS_UNTIL_EARLY_BIRD', 19); // Updated calculation

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 7200); // 2 hours
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Email Configuration - Buffalo Marathon Server
define('SMTP_HOST', 'mail.buffalo-marathon.com');       // Primary mail server
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@buffalo-marathon.com'); // Production email
define('SMTP_PASSWORD', 'Buffalo@2025');                 // Production password
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_NAME', 'Buffalo Marathon 2025');
define('SMTP_BACKUP_HOST', 'smtp.buffalo-marathon.com');           // Backup server if primary fails

// ZANACO Bank Payment Details
define('BANK_NAME', 'ZANACO');
define('BANK_ACCOUNT_NAME', 'Buffalo Park Recreation Center');
define('BANK_BRANCH', 'Government Complex Branch');
define('BANK_ACCOUNT_NUMBER', '0307107300745');
define('BANK_SWIFT_CODE', 'ZANAZMLU');
define('BANK_CURRENCY', 'ZMW');

// Mobile Money Configuration (Zambian Networks)
define('MTN_ENABLED', true);
define('MTN_SHORTCODE', '303');
define('AIRTEL_ENABLED', true);
define('AIRTEL_SHORTCODE', '115');
define('ZAMTEL_ENABLED', true);
define('ZAMTEL_SHORTCODE', '155');

// Environment Settings
define('ENVIRONMENT', 'production'); // production, staging, development
define('DEBUG_MODE', false); // SET TO FALSE FOR PRODUCTION
define('LOG_ERRORS', true);
define('MAINTENANCE_MODE', false);

// Rate Limiting
define('RATE_LIMIT_REGISTRATION', 3); // Max 3 registration attempts per hour
define('RATE_LIMIT_LOGIN', 5);        // Max 5 login attempts per 15 minutes
define('RATE_LIMIT_EMAIL', 10);       // Max 10 emails per hour

// Payment Configuration
define('MOBILE_MONEY_ENABLED', true);
define('BANK_TRANSFER_ENABLED', true);
define('CASH_PAYMENT_ENABLED', true);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour

// Timezone and Locale
date_default_timezone_set('Africa/Lusaka');
setlocale(LC_TIME, 'en_US.UTF-8');

// Error Reporting (Production Settings)
if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Session Configuration
ini_set('session.cookie_secure', 1);     // HTTPS only
ini_set('session.cookie_httponly', 1);   // No JavaScript access
ini_set('session.use_strict_mode', 1);   // Strict session management
ini_set('session.cookie_samesite', 'Strict');

// Security Headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Auto-create necessary directories
$required_dirs = [
    __DIR__ . '/../logs',
    __DIR__ . '/../uploads',
    __DIR__ . '/../cache',
    __DIR__ . '/../backups'
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Application Constants
define('APP_VERSION', '1.0.0');
define('APP_LAUNCH_DATE', '2025-08-08');
define('COPYRIGHT_YEAR', '2025');
?>