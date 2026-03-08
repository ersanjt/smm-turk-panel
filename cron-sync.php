<?php
/**
 * Cron job: Sync order statuses from SmmFollows
 * Run every 5 minutes: */5 * * * * php /path/to/cron-sync.php
 */
require_once __DIR__ . '/includes/init.php';

$om = new OrderManager();
$updated = $om->syncOrders();

if (php_sapi_name() === 'cli') {
    echo date('Y-m-d H:i:s') . " - Updated $updated orders\n";
}
