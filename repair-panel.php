<?php
/**
 * Repair a child panel deploy (fixes 403 when files missing).
 * GET ?key=WEBHOOK_SECRET&panel_id=2
 */
header('Content-Type: application/json; charset=utf-8');

$key = trim((string) ($_GET['key'] ?? ''));
$panelId = (int) ($_GET['panel_id'] ?? 0);
$secretFile = file_exists(__DIR__ . '/deploy-secret.txt') ? __DIR__ . '/deploy-secret.txt'
    : (file_exists(dirname(__DIR__) . '/deploy-secret.txt') ? dirname(__DIR__) . '/deploy-secret.txt' : '');

if ($secretFile === '' || !is_readable($secretFile) || $key === '' || $panelId <= 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Use ?key=WEBHOOK_SECRET&panel_id=ID']);
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

require_once __DIR__ . '/app/bootstrap.php';

$panel = Database::getInstance()->fetch('SELECT * FROM child_panels WHERE id = ?', [$panelId]);
if (!$panel) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Panel not found']);
    exit;
}

$cpm = new ChildPanelManager();
$before = (new ChildPanelDeployer())->documentRootReady(
    (new ChildPanelDeployer())->resolveDocumentRoot((string) $panel['domain'], (string) ($panel['document_root'] ?? ''))
);

$result = $cpm->provision($panelId, null, true);
$login = $cpm->syncAdminFromParentAccount($panelId, null);

$afterRoot = trim((string) (Database::getInstance()->fetch('SELECT document_root FROM child_panels WHERE id = ?', [$panelId])['document_root'] ?? ''));
$after = (new ChildPanelDeployer())->documentRootReady($afterRoot);

echo json_encode([
    'ok' => !empty($result['success']),
    'domain' => $panel['domain'] ?? '',
    'before' => $before,
    'after' => $after,
    'document_root' => $afterRoot,
    'provision' => $result,
    'login_sync' => $login,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
