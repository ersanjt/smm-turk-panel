<?php
// ============================================================
//  SMM Turk Panel - Configuration Template
//  Copy this file to config.php and fill in your values
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'smm_turk');          // Your database name
define('DB_USER', 'your_db_user');      // Your MySQL username
define('DB_PASS', 'your_db_password');  // Your MySQL password
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'https://yourdomain.com');  // No trailing slash
define('SITE_NAME', 'SMM Turk');
define('MAIL_FROM', 'noreply@yourdomain.com'); // Optional; used for verification emails (cPanel: use an email from your domain)

// SmmFollows API - Get key from https://smmfollows.com → Account → API
define('PROVIDER_API_URL', 'https://smmfollows.com/api/v2');
define('PROVIDER_API_KEY', 'YOUR_SMMFOLLOWS_API_KEY');

// Security - In production use a FIXED 32-char hex (e.g. from: bin2hex(random_bytes(16)))
// Do not use random_bytes() at runtime in production (breaks session continuity)
define('SECRET_KEY', getenv('SMM_SECRET_KEY') ?: bin2hex(random_bytes(16)));
define('SESSION_LIFETIME', 86400);  // 24 hours

define('MARKUP_PERCENT', 10);
define('MIN_DEPOSIT', 10);
define('REFERRAL_COMMISSION', 2);

date_default_timezone_set('UTC');

// Development: show errors. Production: set SMM_DEBUG=0 or define('SMM_DEBUG', false) in config.php
$isProduction = (getenv('SMM_DEBUG') === '0' || (defined('SMM_DEBUG') && SMM_DEBUG === false));
if (!$isProduction) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
