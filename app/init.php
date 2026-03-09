<?php
/**
 * SMM Turk - Application Bootstrap
 * Loads config and core classes
 */
define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Mail.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/SmmApi.php';
require_once __DIR__ . '/OrderManager.php';

$db   = Database::getInstance();
$auth = new Auth();

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
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
