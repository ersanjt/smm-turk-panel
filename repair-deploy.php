<?php
/**
 * Emergency sync of critical PHP files from GitHub main (when rsync/git deploy lags).
 * GET ?key=WEBHOOK_SECRET — same secret as deploy-webhook.php / deploy-secret.txt
 */
header('Content-Type: application/json; charset=utf-8');

$key = trim((string) ($_GET['key'] ?? ''));
$secretFile = file_exists(__DIR__ . '/deploy-secret.txt') ? __DIR__ . '/deploy-secret.txt'
    : (file_exists(dirname(__DIR__) . '/deploy-secret.txt') ? dirname(__DIR__) . '/deploy-secret.txt' : '');

if ($secretFile === '' || !is_readable($secretFile) || $key === '') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden — use ?key=WEBHOOK_SECRET']);
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

$webhookSecret = $config['WEBHOOK_SECRET'] ?? '';
if ($webhookSecret === '' || !hash_equals($webhookSecret, $key)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid key']);
    exit;
}

$repoBase = 'https://raw.githubusercontent.com/ersanjt/smm-turk-panel/main/';
$files = [
    'app/ChildPanelRemoteSettings.php',
    'app/init.php',
    'child-panel.php',
    'DEPLOY_VERSION',
];

$repaired = [];
$errors = [];

foreach ($files as $rel) {
    $url = $repoBase . str_replace('\\', '/', $rel);
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: smm-turk-repair\r\n",
        ],
    ]);
    $content = @file_get_contents($url, false, $ctx);
    if ($content === false || $content === '') {
        $errors[] = "download failed: $rel";
        continue;
    }
    $dest = __DIR__ . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $dir = dirname($dest);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        $errors[] = "mkdir failed: $dir";
        continue;
    }
    if (@file_put_contents($dest, $content) === false) {
        $errors[] = "write failed: $rel";
        continue;
    }
    $repaired[] = $rel;
}

if (function_exists('opcache_reset')) {
    @opcache_reset();
}
if (function_exists('opcache_invalidate')) {
    foreach ($repaired as $rel) {
        @opcache_invalidate(__DIR__ . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel), true);
    }
}

$deployScript = $config['DEPLOY_SCRIPT'] ?? '/home/smmturk/deploy-smm.sh';
$deployRan = false;
$deployOut = '';
if (is_readable($deployScript) && function_exists('exec')) {
    $disabled = strtolower((string) ini_get('disable_functions'));
    $execOff = $disabled !== '' && in_array('exec', array_map('trim', explode(',', $disabled)), true);
    if (!$execOff) {
        $out = [];
        $code = 0;
        @exec('bash ' . escapeshellarg($deployScript) . ' 2>&1', $out, $code);
        $deployRan = true;
        $deployOut = implode("\n", $out);
    }
}

$ok = $errors === [] && $repaired !== [];
http_response_code($ok ? 200 : 500);
echo json_encode([
    'ok' => $ok,
    'repaired' => $repaired,
    'errors' => $errors,
    'opcache_reset' => function_exists('opcache_reset'),
    'deploy_ran' => $deployRan,
    'deploy_output' => $deployOut !== '' ? $deployOut : null,
    'deploy_version' => is_readable(__DIR__ . '/DEPLOY_VERSION') ? trim((string) file_get_contents(__DIR__ . '/DEPLOY_VERSION')) : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
