<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'My Orders';
$om  = new OrderManager();
$uid = $auth->getUserId();

$status  = $_GET['status'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$orders = $om->getUserOrders($uid, $status, $perPage, $offset);
$total  = $om->getUserOrderCount($uid, $status);
$pages  = ceil($total / $perPage);

$statuses = ['', 'Pending', 'Processing', 'In progress', 'Completed', 'Partial', 'Cancelled'];

require_once __DIR__ . '/layouts/header.php';
?>

<div class="card" style="padding:0;overflow:hidden;">
  <div style="padding:18px 20px 0;">
    <div class="card-title">📋 My Orders</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
      <?php foreach ($statuses as $s): ?>
      <a href="?status=<?= urlencode($s) ?>"
         class="badge <?= $status === $s ? 'badge-blue' : 'badge-gray' ?>"
         style="padding:6px 14px;font-size:12px;text-decoration:none;">
        <?= $s ?: 'All' ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="overflow-x:auto;">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Date</th>
          <th>Service</th>
          <th>Link</th>
          <th>Qty</th>
          <th>Charge</th>
          <th>Start</th>
          <th>Remains</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">No orders found.</td></tr>
        <?php else: ?>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong>#<?= $o['id'] ?></strong></td>
          <td style="font-size:11px;color:var(--text-muted);"><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></td>
          <td style="max-width:220px;font-size:11.5px;"><?= h(mb_substr($o['service_name'] ?? '', 0, 70)) ?>…</td>
          <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;">
            <a href="<?= h($o['link']) ?>" target="_blank" style="color:var(--primary);"><?= h(mb_substr($o['link'], 0, 40)) ?>…</a>
          </td>
          <td><?= number_format($o['quantity']) ?></td>
          <td><strong>$<?= number_format($o['charge'], 4) ?></strong></td>
          <td><?= number_format($o['start_count']) ?></td>
          <td><?= number_format($o['remains']) ?></td>
          <td>
            <span class="badge status-<?= str_replace(' ', '-', h($o['status'])) ?>">
              <?= h($o['status']) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <div style="padding:16px 20px;display:flex;gap:6px;align-items:center;border-top:1px solid var(--border);">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?status=<?= urlencode($status) ?>&page=<?= $i ?>"
       class="badge <?= $i == $page ? 'badge-blue' : 'badge-gray' ?>"
       style="padding:5px 12px;text-decoration:none;"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
