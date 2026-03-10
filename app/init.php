<?php
/**
 * SMM Turk - Application Bootstrap
 * Loads config and core classes
 */
define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Mail.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/SmmApi.php';
require_once __DIR__ . '/OrderManager.php';

$db   = Database::getInstance();
$auth = new Auth();

// Security headers (web only)
if (php_sapi_name() !== 'cli') {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Maintenance mode (web only; allow CLI and logged-in admins)
if (php_sapi_name() !== 'cli') {
    $maintenance = $db->getSetting('maintenance_mode');
    if ($maintenance === '1' && !$auth->isAdmin()) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $allow = (strpos($uri, '/admin/') !== false && $auth->isLoggedIn()) || strpos($uri, '/login.php') !== false;
        if (!$allow) {
            header('HTTP/1.1 503 Service Unavailable');
            header('Retry-After: 300');
            header('Content-Type: text/html; charset=UTF-8');
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars($siteName) . ' — Maintenance</title><style>body{font-family:sans-serif;background:#1a0a0e;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;text-align:center;padding:20px}.box{max-width:400px}h1{color:#E30A17;font-size:1.5rem}p{color:rgba(255,255,255,.8);line-height:1.6}</style></head><body><div class="box"><h1>Under maintenance</h1><p>We are currently updating. Please try again in a few minutes.</p></div></body></html>';
            exit;
        }
    }
}

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** Base path for internal links (e.g. '' or '/panel'). Use in href. */
function base_path(): string {
    if (!defined('SITE_URL') || SITE_URL === '') return '';
    $path = parse_url(SITE_URL, PHP_URL_PATH);
    return $path ? rtrim($path, '/') : '';
}

/** Full base URL for redirects (e.g. 'https://example.com' or 'https://example.com/panel'). */
function site_base(): string {
    return (defined('SITE_URL') && SITE_URL !== '') ? rtrim(SITE_URL, '/') : '';
}

/** Internal URL: full URL for redirects (use in Location header). */
function url(string $path): string {
    $path = '/' . ltrim($path, '/');
    $base = site_base();
    return $base !== '' ? $base . $path : $path;
}

/** Path for internal href (same-origin). Use in HTML: href="<?= h(path('login.php')) ?>". */
function path(string $p): string {
    $p = '/' . ltrim($p, '/');
    return base_path() . $p;
}

function money(float $amount): string {
    return '$' . number_format($amount, 4);
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hr ago';
    return date('Y-m-d', strtotime($datetime));
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
