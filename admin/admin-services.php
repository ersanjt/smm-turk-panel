<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Services';
$db = Database::getInstance();

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['cat'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$where = "status = 'active'";
$params = [];
if ($search !== '') {
    $where .= " AND (name LIKE ? OR CAST(service_id AS CHAR) = ?)";
    $params[] = '%' . $search . '%';
    $params[] = ctype_digit($search) ? $search : '-1';
}
if ($category !== '') {
    $where .= " AND category = ?";
    $params[] = $category;
}

$total = (int) $db->fetch("SELECT COUNT(*) c FROM services WHERE $where", $params)['c'];
$services = $db->fetchAll(
    "SELECT * FROM services WHERE $where ORDER BY service_id ASC LIMIT $perPage OFFSET $offset",
    $params
);
$totalPages = $total ? (int)ceil($total / $perPage) : 1;
$categories = $db->fetchAll("SELECT DISTINCT category FROM services WHERE status='active' ORDER BY category");

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card" style="padding:0;overflow:hidden;">
  <div style="padding:18px 20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div class="card-title" style="margin:0">⭐ Services (<?= number_format($total) ?> active)</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
      <form method="GET" style="display:flex;gap:8px;">
        <input type="hidden" name="cat" value="<?= h($category) ?>">
        <input type="text" name="q" value="<?= h($search) ?>" class="form-control" style="width:200px;" placeholder="Search by name or ID…">
        <button type="submit" class="btn btn-primary">Search</button>
      </form>
      <a href="<?= h(path('admin/admin-sync.php')) ?>" class="btn" style="background:var(--primary);color:#fff;">🔄 Sync from provider</a>
    </div>
  </div>
  <div style="padding:0 20px 12px;">
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;">
      <a href="?<?= $search ? 'q=' . urlencode($search) . '&' : '' ?>cat=" class="badge <?= $category === '' ? 'badge-blue' : 'badge-gray' ?>" style="text-decoration:none;">All</a>
      <?php foreach ($categories as $c): ?>
      <a href="?cat=<?= urlencode($c['category']) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="badge <?= $category === $c['category'] ? 'badge-blue' : 'badge-gray' ?>" style="text-decoration:none;"><?= h($c['category']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Category</th>
          <th>Rate</th>
          <th>Min / Max</th>
          <th>Markup %</th>
          <th>Refill</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($services)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">No services found. <a href="<?= h(path('admin/admin-sync.php')) ?>">Sync from provider</a> first.</td></tr>
        <?php else: ?>
        <?php foreach ($services as $s): ?>
        <tr>
          <td><strong><?= (int)$s['service_id'] ?></strong></td>
          <td style="max-width:320px;font-size:12px;"><?= h(mb_substr($s['name'], 0, 80)) ?><?= mb_strlen($s['name']) > 80 ? '…' : '' ?></td>
          <td><?= h($s['category']) ?></td>
          <td>$<?= number_format((float)$s['rate'], 5) ?></td>
          <td><?= number_format($s['min']) ?> / <?= number_format($s['max']) ?></td>
          <td><?= number_format((float)($s['markup'] ?? 0), 1) ?>%</td>
          <td><?= !empty($s['refill']) ? 'Yes' : 'No' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div style="padding:16px 20px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;border-top:1px solid var(--border);">
    <?php
    $qs = http_build_query(array_filter(['q' => $search ?: null, 'cat' => $category ?: null]));
    for ($i = 1; $i <= min($totalPages, 15); $i++):
      $url = '?p=' . $i . ($qs ? '&' . $qs : '');
    ?>
    <a href="<?= $url ?>" class="badge <?= $i === $page ? 'badge-blue' : 'badge-gray' ?>" style="padding:5px 12px;text-decoration:none;"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<p style="margin-top:16px;"><a href="<?= h(path('admin/index.php')) ?>" class="btn btn-primary" style="padding:8px 16px;">← Admin Panel</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
