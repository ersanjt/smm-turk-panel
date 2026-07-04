<?php
/**
 * Apply performance indexes — run once: php migrate-performance-indexes.php
 */
require_once __DIR__ . '/app/init.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$sql = file_get_contents(__DIR__ . '/migrations/007_performance_indexes.sql');
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

$db = Database::getInstance();
foreach ($statements as $stmt) {
    if ($stmt === '' || str_starts_with($stmt, '--')) {
        continue;
    }
    try {
        $db->getConnection()->exec($stmt);
        echo "OK: " . substr($stmt, 0, 60) . "...\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate key name')) {
            echo "SKIP (exists): " . substr($stmt, 0, 50) . "...\n";
        } else {
            echo "ERR: " . $e->getMessage() . "\n";
        }
    }
}
echo "Done.\n";
