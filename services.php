<?php
// services.php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'Services';
$db = Database::getInstance();

$search = trim($_GET['q'] ?? '');
$cat    = $_GET['cat'] ?? '';

$where  = "WHERE status='active'";
$params = [];
if ($search) { $where .= " AND name LIKE ?"; $params[] = "%$search%"; }
if ($cat)    { $where .= " AND category = ?"; $params[] = $cat; }

$services   = $db->fetchAll("SELECT * FROM services $where ORDER BY service_id ASC LIMIT 200", $params);
$categories = $db->fetchAll("SELECT DISTINCT category FROM services WHERE status='active' ORDER BY category");

require_once __DIR__ . '/layouts/header.php';
?>
<style>
.ptab{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:20px;background:#fff;border:1.5px solid var(--border);font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;color:var(--text-muted);text-decoration:none}
.ptab:hover,.ptab.active{background:var(--primary);border-color:var(--primary);color:#fff}
.platform-tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:18px}
</style>

<div style="display:flex;gap:10px;margin-bottom:16px;">
  <form method="GET" style="display:flex;gap:8px;flex:1;">
    <input type="text" name="q" value="<?= h($search) ?>" class="form-control" placeholder="Search services…">
    <?php if ($cat): ?><input type="hidden" name="cat" value="<?= h($cat) ?>"><?php endif; ?>
    <button type="submit" class="btn btn-primary" style="padding:10px 20px;">Search</button>
  </form>
</div>

<div class="platform-tabs">
  <a class="ptab <?= !$cat ? 'active' : '' ?>" href="services.php">All</a>
  <?php foreach ($categories as $c): ?>
  <a class="ptab <?= $cat === $c['category'] ? 'active' : '' ?>" href="?cat=<?= urlencode($c['category']) ?>"><?= h($c['category']) ?></a>
  <?php endforeach; ?>
</div>

<div class="card" style="padding:0;overflow:hidden;">
  <div style="overflow-x:auto;">
    <table class="table">
      <thead>
        <tr><th>ID</th><th>Service</th><th>Category</th><th>Rate/1000</th><th>Min</th><th>Max</th><th>Refill</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($services as $s):
          $displayRate = $s['rate'] * (1 + $s['markup']/100);
        ?>
        <tr>
          <td><strong><?= $s['service_id'] ?></strong></td>
          <td style="font-size:12px;max-width:380px;line-height:1.5;"><?= h(mb_substr($s['name'], 0, 120)) ?></td>
          <td style="font-size:11px;color:var(--text-muted);"><?= h($s['category']) ?></td>
          <td><span class="badge badge-blue">$<?= number_format($displayRate, 5) ?></span></td>
          <td><?= number_format($s['min']) ?></td>
          <td><?= number_format($s['max']) ?></td>
          <td><?= $s['refill'] ? '<span class="badge badge-green">Yes</span>' : '<span class="badge badge-gray">No</span>' ?></td>
          <td><a href="index.php?service=<?= $s['service_id'] ?>" class="btn btn-primary" style="padding:6px 12px;font-size:12px;">Order</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
