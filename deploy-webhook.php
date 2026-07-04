<?php
/**
 * GitHub Webhook — دیپلوی خودکار بعد از push به main
 *
 * این فایل را داخل public_html روی سرور قرار بده. یک فایل deploy-secret.txt هم
 * بساز (در همان پوشه یا در home) با این محتوا (مقادیر را عوض کن):
 *
 *   WEBHOOK_SECRET=یک_رمز_۳۲_حرفی_تصادفی
 *   DEPLOY_SCRIPT=/home/smmturk/deploy-smm.sh
 *
 * در GitHub: Settings → Webhooks → Add webhook
 *   Payload URL: https://92.205.182.143/deploy-webhook.php (یا با دامنه)
 *   Content type: application/json
 *   Secret: همان WEBHOOK_SECRET
 *   Events: Just the push event
 *
 * راهنما: docs/CHECKLIST-DEPLOY.md
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['diag'])) {
        $homeSecret = dirname(__DIR__) . '/deploy-secret.txt';
        $localSecret = __DIR__ . '/deploy-secret.txt';
        $secretPath = is_readable($localSecret) ? $localSecret : (is_readable($homeSecret) ? $homeSecret : '');
        $diagKey = trim((string)($_GET['key'] ?? ''));
        if ($secretPath === '' || $diagKey === '') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'diag requires ?key=WEBHOOK_SECRET']);
            exit;
        }
        $config = [];
        foreach (file($secretPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
                $config[$m[1]] = trim($m[2], " \t\"'");
            }
        }
        $webhookSecret = $config['WEBHOOK_SECRET'] ?? '';
        if ($webhookSecret === '' || !hash_equals($webhookSecret, $diagKey)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
            exit;
        }
        $deployScript = '/home/smmturk/deploy-smm.sh';
        $cronScript = '/home/smmturk/deploy-cron.sh';
        $repoDir = '/home/smmturk/repositories/smm-turk-panel';
        $disabled = strtolower((string) ini_get('disable_functions'));
        $execOff = !function_exists('exec') || ($disabled !== '' && in_array('exec', array_map('trim', explode(',', $disabled)), true));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'diag' => [
                'deploy_secret' => $secretPath !== '' ? 'found' : 'missing — upload deploy-secret.txt to /home/smmturk/',
                'deploy_script' => is_readable($deployScript) ? 'found' : 'missing — upload deploy-cpanel.sh as /home/smmturk/deploy-smm.sh (755)',
                'deploy_cron' => is_readable($cronScript) ? 'found' : 'missing — upload deploy-cron.sh to /home/smmturk/ (755)',
                'git_repo' => is_dir($repoDir . '/.git') ? 'found' : 'missing — clone repo in cPanel Git Version Control',
                'exec_disabled' => $execOff,
                'hint' => $execOff ? 'Set Cron: * * * * * /home/smmturk/deploy-cron.sh' : 'exec OK — webhook can run deploy directly',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok' => true, 'message' => 'Deploy webhook endpoint. Use POST from GitHub. Add ?diag=1 to check server setup.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
if ($rawInput === false || $rawInput === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Empty body']);
    exit;
}

// بارگذاری سکرت از فایل (خارج از public_html امن‌تر است)
$secretFile = file_exists(__DIR__ . '/deploy-secret.txt') ? __DIR__ . '/deploy-secret.txt' : (file_exists(dirname(__DIR__) . '/deploy-secret.txt') ? dirname(__DIR__) . '/deploy-secret.txt' : '');
if ($secretFile === '' || !is_readable($secretFile)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'deploy-secret.txt not found or not readable']);
    exit;
}

$config = [];
$lines = file($secretFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
        $config[$m[1]] = trim($m[2], " \t\"'");
    }
}

$webhookSecret = $config['WEBHOOK_SECRET'] ?? '';
if ($webhookSecret === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'WEBHOOK_SECRET not set in deploy-secret.txt']);
    exit;
}

// تأیید امضای GitHub (X-Hub-Signature-256)
$sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if ($sig === '') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Missing signature']);
    exit;
}

$expected = 'sha256=' . hash_hmac('sha256', $rawInput, $webhookSecret);
if (!hash_equals($expected, $sig)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid signature']);
    exit;
}

$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

// فقط روی push به برنچ main واکنش نشان بده
$ref = $payload['ref'] ?? '';
if ($ref !== 'refs/heads/main') {
    echo json_encode(['ok' => true, 'skipped' => true, 'reason' => 'Not main branch']);
    exit;
}

$deployScript = $config['DEPLOY_SCRIPT'] ?? '';
$repoPath = $config['REPO_PATH'] ?? '';
$queueFlag = $config['DEPLOY_QUEUE_FLAG'] ?? (dirname(__DIR__) . '/deploy.pending');

$execDisabled = !function_exists('exec');
if (!$execDisabled) {
    $disabled = strtolower((string) ini_get('disable_functions'));
    if ($disabled !== '') {
        $execDisabled = in_array('exec', array_map('trim', explode(',', $disabled)), true);
    }
}

$output = [];
$returnVar = 0;

if ($deployScript !== '') {
    if (!is_readable($deployScript)) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'DEPLOY_SCRIPT not found on server. Copy scripts/deploy-cpanel.sh to /home/smmturk/deploy-smm.sh',
            'path' => $deployScript,
        ]);
        exit;
    }
    if ($execDisabled) {
        if (@file_put_contents($queueFlag, gmdate('c') . " queued\n") === false) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'exec disabled and cannot write deploy.pending. Add Cron for deploy-cron.sh']);
            exit;
        }
        echo json_encode(['ok' => true, 'message' => 'Deploy queued (exec disabled). Cron runs deploy-cron.sh within 1 minute.', 'queued' => true]);
        exit;
    }
    @exec('bash ' . escapeshellarg($deployScript) . ' 2>&1', $output, $returnVar);
} elseif ($repoPath !== '' && is_dir($repoPath)) {
    if ($execDisabled) {
        if (@file_put_contents($queueFlag, gmdate('c') . " queued\n") === false) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'exec disabled. Use DEPLOY_SCRIPT + Cron instead']);
            exit;
        }
        echo json_encode(['ok' => true, 'message' => 'Deploy queued', 'queued' => true]);
        exit;
    }
    $cmd = 'cd ' . escapeshellarg($repoPath) . ' && git pull 2>&1';
    @exec($cmd, $output, $returnVar);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Set DEPLOY_SCRIPT or REPO_PATH in deploy-secret.txt']);
    exit;
}

$log = implode("\n", $output);
if ($returnVar !== 0) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Deploy failed', 'return_code' => $returnVar, 'output' => $log]);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'Deploy completed', 'output' => $log]);
