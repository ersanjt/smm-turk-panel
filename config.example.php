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

// Base URL for redirects, canonicals, and all internal links. Must match how users open the site.
// Use https and NO trailing slash. Examples:
//   - Site at root:     https://smm-turk.com
//   - Site in subdir:   https://smm-turk.com/panel
define('SITE_URL', 'https://smm-turk.com');
define('SITE_NAME', 'SMM Turk');
// Geo/SEO: primary region for meta geo.region (ISO 3166-1 alpha-2, e.g. TR for Turkey)
define('GEO_REGION', 'TR');
define('MAIL_FROM', 'noreply@yourdomain.com'); // Optional; used for verification emails (cPanel: use an email from your domain)

// SmmFollows API - Get key from https://smmfollows.com → Account → API
define('PROVIDER_API_URL', 'https://smmfollows.com/api/v2');
define('PROVIDER_API_KEY', 'YOUR_SMMFOLLOWS_API_KEY');

// Security - Session/CSRF key. In production you MUST use a fixed value:
// 1) Set env SMM_SECRET_KEY to a 32-char hex, or 2) define a constant below.
// Do NOT use bin2hex(random_bytes(16)) at runtime in production (sessions break on restart).
define('SECRET_KEY', getenv('SMM_SECRET_KEY') ?: bin2hex(random_bytes(16)));
define('SESSION_LIFETIME', 86400);  // 24 hours

// Crypto deposit — single wallet address (ETH/ERC20/USDT etc.). Customers can only deposit via crypto.
define('CRYPTO_WALLET_ADDRESS', 'YOUR_ETH_OR_USDT_ADDRESS');

define('MARKUP_PERCENT', 10);
define('MIN_DEPOSIT', 10);
define('REFERRAL_COMMISSION', 2);

// Google Sign-In (optional). Get credentials: https://console.cloud.google.com/apis/credentials
// Redirect URI = SITE_URL + '/login-google-callback' (no .php). Add it in Console → Credentials → Your OAuth client → Authorized redirect URIs.
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');

date_default_timezone_set('UTC');

// Error reporting: default is production (no display_errors). Set SMM_DEBUG=1 or define('SMM_DEBUG', true) to show errors.
$isProduction = (getenv('SMM_DEBUG') === '1' || (defined('SMM_DEBUG') && SMM_DEBUG === true)) ? false : true;
define('SMM_PRODUCTION', $isProduction);
if (!$isProduction) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
