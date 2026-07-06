<?php
/**
 * One-shot app file sync from GitHub (bypasses stale OPcache on old filenames).
 * GET ?key=WEBHOOK_SECRET
 */
header('Content-Type: application/json; charset=utf-8');

$key = trim((string) ($_GET['key'] ?? ''));
$secretFile = file_exists(__DIR__ . '/deploy-secret.txt') ? __DIR__ . '/deploy-secret.txt'
    : (file_exists(dirname(__DIR__) . '/deploy-secret.txt') ? dirname(__DIR__) . '/deploy-secret.txt' : '');

if ($secretFile === '' || !is_readable($secretFile) || $key === '') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$config = [];
foreach (file($secretFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
        continue;
    }
    if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
        $config[$m[1]] = trim($m[2], " \t\"'");
    }
}

if (($config['WEBHOOK_SECRET'] ?? '') === '' || !hash_equals($config['WEBHOOK_SECRET'], $key)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid key']);
    exit;
}

if (function_exists('opcache_reset')) {
    @opcache_reset();
}

$repoBase = 'https://raw.githubusercontent.com/ersanjt/smm-turk-panel/main/';
$files = [
    'fix-app.php',
    'app/bootstrap.php',
    'app/init.php',
    'app/init-v2.php',
    'app/ChildPanelRemoteSettings.php',
    'app/ChildPanelRemoteSettingsImpl.php',
    'app/ChildPanelDeployer.php',
    'app/ChildPanelManager.php',
    'app/ChildPanelAutomation.php',
    'app/Auth.php',
    'app/Seo.php',
    'app/Lang.php',
    'lang/tr.php',
    'lang/en.php',
    'lang/de.php',
    'admin/_init.php',
    'child-panel.php',
    'repair-panel.php',
    'pricing.php',
    'earn.php',
    'robots.php',
    'sitemap.php',
    'manifest.php',
    'blog-post.php',
    'partials/public-seo-head.php',
    'partials/landing-nav.php',
    'assets/css/earn.css',
    'assets/css/landing.css',
    'assets/js/landing.js',
    'DEPLOY_VERSION',
];

$repaired = [];
$errors = [];
$ctx = stream_context_create([
    'http' => ['timeout' => 45, 'header' => "User-Agent: smm-turk-fix-app\r\n"],
]);

foreach ($files as $rel) {
    $content = @file_get_contents($repoBase . $rel, false, $ctx);
    if ($content === false || $content === '') {
        $errors[] = "download: $rel";
        continue;
    }
    $dest = __DIR__ . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_file($dest)) {
        @unlink($dest);
    }
    $tmp = $dest . '.tmp.' . getmypid();
    if (@file_put_contents($tmp, $content) === false) {
        $errors[] = "write: $rel";
        continue;
    }
    @chmod($tmp, 0644);
    if (!@rename($tmp, $dest)) {
        @unlink($tmp);
        $errors[] = "rename: $rel";
        continue;
    }
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($dest, true);
    }
    $repaired[] = $rel;
}

if (function_exists('opcache_reset')) {
    @opcache_reset();
}

echo json_encode([
    'ok' => $errors === [] && $repaired !== [],
    'repaired' => $repaired,
    'errors' => $errors,
    'stub_line52' => is_readable(__DIR__ . '/app/ChildPanelRemoteSettings.php')
        ? trim((string) (file(__DIR__ . '/app/ChildPanelRemoteSettings.php')[51] ?? 'n/a'))
        : null,
    'bootstrap_loaded' => is_readable(__DIR__ . '/app/bootstrap.php'),
    'deploy_version' => is_readable(__DIR__ . '/DEPLOY_VERSION') ? trim((string) file_get_contents(__DIR__ . '/DEPLOY_VERSION')) : null,
], JSON_PRETTY_PRINT);
