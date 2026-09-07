<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Manage Orders';
$db = Database::getInstance();
$om = new OrderManager();

$allowedStatuses = ['Pending', 'Processing', 'In progress', 'Completed', 'Partial', 'Cancelled', 'Refunded'];
$statusFilter = trim((string) ($_GET['status'] ?? $_POST['status'] ?? ''));
if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}
$search = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$page = max(1, (int) ($_GET['p'] ?? $_POST['p'] ?? 1));

$redirectToList = static function () use (&$search, &$statusFilter, &$page, $allowedStatuses): void {
    $search = trim((string) ($_POST['q'] ?? $search));
    $statusFilter = trim((string) ($_POST['status'] ?? $statusFilter));
    if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
        $statusFilter = '';
    }
    $page = max(1, (int) ($_POST['p'] ?? $page));
    $query = array_filter([
        'q' => $search !== '' ? $search : null,
        'status' => $statusFilter !== '' ? $statusFilter : null,
        'p' => $page > 1 ? $page : null,
    ], static fn ($v) => $v !== null && $v !== '');
    redirect(url('admin/admin-orders.php') . ($query ? '?' . http_build_query($query) : ''));
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expired. Please try again.');
        $redirectToList();
    }
    $action = trim((string) ($_POST['action'] ?? ''));
    $orderId = (int) ($_POST['order_id'] ?? $_POST['cancel_order_id'] ?? 0);
    if ($action === '' && isset($_POST['cancel_order_id'])) {
        $action = 'cancel';
    }

    if ($action === 'sync_all') {
        $updated = $om->syncOrders();
        flash('success', $updated > 0
            ? 'Updated ' . number_format($updated) . ' order(s) from the provider.'
            : 'No open orders needed a status update.');
        $redirectToList();
    }

    if ($action === 'sync_one' && $orderId > 0) {
        $result = $om->refreshOrderStatus($orderId);
        if ($result['success']) {
            $st = (string) ($result['status'] ?? '');
            flash('success', !empty($result['changed'])
                ? 'Order #' . $orderId . ' is now ' . $st . '.'
                : 'Order #' . $orderId . ' is still ' . $st . '.');
        } else {
            flash('error', $result['error'] ?? 'Could not sync this order.');
        }
        $redirectToList();
    }

    if ($action === 'cancel' && $orderId > 0) {
        $result = $om->cancelOrder($orderId, true);
        if (!empty($result['success'])) {
            $refunded = (float) ($result['refunded'] ?? 0);
            $msg = 'Order #' . $orderId . ' cancelled.';
            if ($refunded > 0) {
                $msg .= ' Refunded $' . number_format($refunded, 4) . ' to the customer.';
            }
            if (!empty($result['warning'])) {
                $msg .= ' Provider note: ' . $result['warning'];
            }
            flash('success', $msg);
        } else {
            flash('error', $result['error'] ?? 'Could not cancel this order.');
        }
        $redirectToList();
    }

    flash('error', 'Invalid action.');
    $redirectToList();
}

$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = "1=1";
$params = [];
if ($statusFilter !== '') {
    $where .= " AND o.status = ?";
    $params[] = $statusFilter;
}
if ($search !== '') {
    $where .= " AND (u.username LIKE ? OR u.email LIKE ? OR o.id = ? OR o.link LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = ctype_digit($search) ? $search : -1;
    $params[] = '%' . $search . '%';
}

$total = (int) $db->fetch("SELECT COUNT(*) c FROM orders o JOIN users u ON o.user_id = u.id WHERE $where", $params)['c'];
$orders = $db->fetchAll(
    "SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE $where ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset",
    $params
);
$totalPages = $total ? (int)ceil($total / $perPage) : 1;

$statusCounts = [];
foreach ($db->fetchAll("SELECT status, COUNT(*) c FROM orders GROUP BY status") as $row) {
    $statusCounts[(string) $row['status']] = (int) $row['c'];
}
$allCount = array_sum($statusCounts);

$chipStatuses = [
    '' => 'All',
    'Pending' => 'Pending',
    'Processing' => 'Processing',
    'In progress' => 'In progress',
    'Completed' => 'Completed',
    'Partial' => 'Partial',
    'Cancelled' => 'Cancelled',
    'Refunded' => 'Refunded',
];

$chipUrl = static function (string $status) use ($search): string {
    $q = array_filter([
        'q' => $search !== '' ? $search : null,
        'status' => $status !== '' ? $status : null,
    ], static fn ($v) => $v !== null && $v !== '');
    return '?' . http_build_query($q);
};

$hiddenFilters = static function () use ($search, $statusFilter, $page): void {
    ?>
      <input type="hidden" name="q" value="<?= h($search) ?>">
      <input type="hidden" name="status" value="<?= h($statusFilter) ?>">
      <?php if ($page > 1): ?><input type="hidden" name="p" value="<?= (int) $page ?>"><?php endif; ?>
    <?php
};

$pageHeaderActions = '<form method="POST">'
    . '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">'
    . '<input type="hidden" name="action" value="sync_all">'
    . '<input type="hidden" name="q" value="' . h($search) . '">'
    . '<input type="hidden" name="status" value="' . h($statusFilter) . '">'
    . ($page > 1 ? '<input type="hidden" name="p" value="' . (int) $page . '">' : '')
    . '<button type="submit" class="btn btn-primary">Sync statuses</button>'
    . '</form>';

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card admin-page-card">
  <div class="admin-page-head">
    <form method="GET" class="admin-search-form">
      <input type="text" name="q" value="<?= h($search) ?>" class="form-control" placeholder="User, ID, link…">
      <select name="status" class="form-control">
        <option value="">All statuses</option>
        <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="Processing" <?= $statusFilter === 'Processing' ? 'selected' : '' ?>>Processing</option>
        <option value="In progress" <?= $statusFilter === 'In progress' ? 'selected' : '' ?>>In progress</option>
        <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
        <option value="Partial" <?= $statusFilter === 'Partial' ? 'selected' : '' ?>>Partial</option>
        <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
        <option value="Refunded" <?= $statusFilter === 'Refunded' ? 'selected' : '' ?>>Refunded</option>
      </select>
      <button type="submit" class="btn btn-primary">Search</button>
    </form>
  </div>
  <div class="admin-status-chips" role="navigation" aria-label="Filter by status">
    <?php foreach ($chipStatuses as $value => $label):
        $count = $value === '' ? $allCount : ($statusCounts[$value] ?? 0);
        $active = $statusFilter === $value;
    ?>
    <a href="<?= h($chipUrl($value)) ?>" class="admin-status-chip<?= $active ? ' is-active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>>
      <span><?= h($label) ?></span>
      <strong><?= number_format($count) ?></strong>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="table-wrap admin-table-wrap">
    <table class="table table-wide table-mobile-cards">
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Service</th>
          <th>Link</th>
          <th>Qty</th>
          <th>Charge</th>
          <th>Status</th>
          <th>Provider</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="10" data-label="" class="admin-empty-cell">No orders found.</td></tr>
        <?php else: ?>
        <?php foreach ($orders as $o):
            $canCancel = $om->isCancellable((string) ($o['status'] ?? ''));
            $hasProviderId = (int) ($o['provider_order_id'] ?? 0) > 0;
            $cancelConfirm = 'Cancel order #' . (int) $o['id'] . ' and refund $' . number_format((float) $o['charge'], 4) . ' to ' . (string) $o['username'] . '?';
        ?>
        <tr>
          <td data-label="ID"><strong>#<?= (int)$o['id'] ?></strong></td>
          <td data-label="User"><?= h($o['username']) ?><br><span style="font-size:11px;color:var(--text-muted);"><?= h($o['email']) ?></span></td>
          <td data-label="Service" style="font-size:12px;"><?= h(mb_substr($o['service_name'] ?? '', 0, 50)) ?><?= mb_strlen($o['service_name'] ?? '') > 50 ? '…' : '' ?></td>
          <td data-label="Link"><a href="<?= h($o['link']) ?>" target="_blank" rel="noopener" style="color:var(--primary);font-size:11px;word-break:break-all;"><?= h(mb_substr($o['link'], 0, 60)) ?><?= mb_strlen($o['link']) > 60 ? '…' : '' ?></a></td>
          <td data-label="Qty"><?= number_format($o['quantity']) ?></td>
          <td data-label="Charge"><strong>$<?= number_format($o['charge'], 4) ?></strong></td>
          <td data-label="Status"><span class="badge status-<?= str_replace(' ', '-', h($o['status'])) ?>"><?= h($o['status']) ?></span></td>
          <td data-label="Provider" style="font-size:11px;color:var(--text-muted);">
            <?php if ($hasProviderId): ?>
              #<?= (int) $o['provider_order_id'] ?>
            <?php else: ?>
              <span class="admin-missing-provider">No ID</span>
            <?php endif; ?>
          </td>
          <td data-label="Date" style="font-size:11px;color:var(--text-muted);"><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></td>
          <td data-label="Actions" class="td-actions admin-actions-cell">
            <?php if ($hasProviderId): ?>
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="sync_one">
              <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
              <?php $hiddenFilters(); ?>
              <button type="submit" class="btn">Sync</button>
            </form>
            <?php endif; ?>
            <?php if ($canCancel): ?>
            <form method="POST" onsubmit="return confirm(<?= h(json_encode($cancelConfirm, JSON_UNESCAPED_UNICODE)) ?>);">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="cancel">
              <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
              <?php $hiddenFilters(); ?>
              <button type="submit" class="btn btn-danger">Cancel</button>
            </form>
            <?php elseif (!$hasProviderId): ?>
            <span style="font-size:11px;color:var(--text-muted);">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="admin-pagination">
    <?php
    $qs = http_build_query(array_filter(['q' => $search ?: null, 'status' => $statusFilter ?: null]));
    for ($i = 1; $i <= min($totalPages, 20); $i++):
      $url = '?p=' . $i . ($qs ? '&' . $qs : '');
    ?>
    <a href="<?= $url ?>" class="badge <?= $i === $page ? 'badge-blue' : 'badge-gray' ?>" style="padding:5px 12px;text-decoration:none;"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($totalPages > 20): ?><span style="color:var(--text-muted);font-size:12px;">…</span><?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
