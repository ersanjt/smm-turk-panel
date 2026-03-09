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

// Security - Use a fixed random 32-char hex string in production
define('SECRET_KEY', bin2hex(random_bytes(16)));
define('SESSION_LIFETIME', 86400);  // 24 hours

define('MARKUP_PERCENT', 10);
define('MIN_DEPOSIT', 10);
define('REFERRAL_COMMISSION', 2);

date_default_timezone_set('UTC');

// Production: set both to 0
error_reporting(E_ALL);
ini_set('display_errors', 1);
