<?php // mass-order.php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'Mass Order';
$om  = new OrderManager();
$uid = $auth->getUserId();

$results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $lines = explode("\n", trim($_POST['orders'] ?? ''));
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line) continue;
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) < 3) { $results[] = ['line' => $line, 'error' => 'Invalid format']; continue; }
        [$serviceId, $link, $quantity] = $parts;
        $result = $om->placeOrder($uid, (int)$serviceId, $link, (int)$quantity);
        $results[] = array_merge(['line' => $line], $result);
    }
}
require_once __DIR__ . '/layouts/header.php';
?>
<div class="card">
  <div class="card-title">📦 Mass Order</div>
  <div class="alert alert-info">📌 One order per line in format: <code>service_id | link | quantity</code></div>
  <?php if ($results): ?>
  <div style="margin-bottom:16px;">
    <?php foreach ($results as $r): ?>
    <div class="alert <?= isset($r['success']) && $r['success'] ? 'alert-success' : 'alert-error' ?>" style="margin-bottom:6px;padding:8px 14px;font-size:12px;">
      <?= isset($r['success']) && $r['success'] ? "✅ Order #{$r['order_id']} placed — \${$r['charge']}" : "❌ {$r['error']} — {$r['line']}" ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="form-group">
      <textarea name="orders" class="form-control" rows="10" style="font-family:monospace;font-size:13px;" placeholder="17708 | https://youtu.be/xxxxx | 1000
17701 | https://twitter.com/user | 500
17681 | https://twitter.com/status/xxx | 10000"></textarea>
    </div>
    <button type="submit" class="btn btn-primary btn-block">🚀 Submit All Orders</button>
  </form>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
