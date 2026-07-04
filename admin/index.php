<?php
require_once __DIR__ . '/../app/init.php';
$auth->requireAdmin();
$pageTitle = 'Admin Panel';
$db  = Database::getInstance();
$api = new SmmApi();

// Stats
$totalUsers   = $db->fetch("SELECT COUNT(*) c FROM users WHERE role='user'")['c'];
$totalOrders  = $db->fetch("SELECT COUNT(*) c FROM orders")['c'];
$totalRevenue = $db->fetch("SELECT SUM(charge) c FROM orders WHERE status='Completed'")['c'] ?? 0;
$pendingOrders= $db->fetch("SELECT COUNT(*) c FROM orders WHERE status='Pending'")['c'];
$openTickets  = $db->fetch("SELECT COUNT(*) c FROM tickets WHERE status='open'")['c'];
$totalServices= $db->fetch("SELECT COUNT(*) c FROM services WHERE status='active'")['c'];
$pendingDeposits = $db->fetch("SELECT COUNT(*) c FROM transactions WHERE type='deposit' AND status='pending'")['c'];
$pendingChildPanels = 0;
try {
    $pendingChildPanels = (int) $db->fetch("SELECT COUNT(*) c FROM child_panels WHERE status='pending'")['c'];
} catch (Throwable $e) {}

// Provider balance
$providerBalance = null;
try {
    $b = $api->balance();
    if ($b && isset($b->balance)) $providerBalance = $b->balance;
} catch (Exception $e) {}

// Recent orders
$recentOrders = $db->fetchAll(
    "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10"
);

// Recent users
$recentUsers = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Chart data — last 7 days
$chartLabels = [];
$chartOrders = [];
$chartRevenue = [];
$rawChart = $db->fetchAll(
    "SELECT DATE(created_at) AS d, COUNT(*) AS cnt, COALESCE(SUM(charge), 0) AS rev
     FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(created_at)"
);
$byDate = [];
foreach ($rawChart as $row) {
    $byDate[$row['d']] = $row;
}
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M j', strtotime($d));
    $chartOrders[] = (int)($byDate[$d]['cnt'] ?? 0);
    $chartRevenue[] = round((float)($byDate[$d]['rev'] ?? 0), 2);
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="admin-grid">
  <div class="admin-card">
    <div class="ac-icon"><?= iconBox('users', 'primary', 24) ?></div>
    <div class="ac-info">
      <div class="ac-label">Total Users</div>
      <div class="ac-value"><?= number_format($totalUsers) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon"><?= iconBox('orders', 'green', 24) ?></div>
    <div class="ac-info">
      <div class="ac-label">Total Orders</div>
      <div class="ac-value"><?= number_format($totalOrders) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon"><?= iconBox('dollar', 'orange', 24) ?></div>
    <div class="ac-info">
      <div class="ac-label">Total Revenue</div>
      <div class="ac-value">$<?= number_format($totalRevenue, 2) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon"><?= iconBox('pending', 'orange', 24) ?></div>
    <div class="ac-info">
      <div class="ac-label">Pending Orders</div>
      <div class="ac-value"><?= number_format($pendingOrders) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon"><?= iconBox('tickets', 'green', 24) ?></div>
    <div class="ac-info">
      <div class="ac-label">Open Tickets</div>
      <div class="ac-value"><?= number_format($openTickets) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon"><?= iconBox('services', 'primary', 24) ?></div>
    <div class="ac-info">
      <div class="ac-label">Active Services</div>
      <div class="ac-value"><?= number_format($totalServices) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon"><?= iconBox('server', 'dark', 24) ?></div>
    <div class="ac-info">
      <div class="ac-label">Provider Balance</div>
      <div class="ac-value"><?= $providerBalance !== null ? '$' . number_format($providerBalance, 2) : 'N/A' ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon"><?= iconBox('deposit', 'orange', 24) ?></div>
    <div class="ac-info">
      <div class="ac-label">Pending Deposits</div>
      <div class="ac-value"><?= number_format($pendingDeposits) ?></div>
    </div>
  </div>
</div>

<div class="quick-actions">
  <a href="<?= h(path('admin/admin-users.php')) ?>" class="qa-btn"><?= icon('users', 16) ?> Manage Users</a>
  <a href="<?= h(path('admin/admin-orders.php')) ?>" class="qa-btn qa-btn-outline"><?= icon('orders', 16) ?> Manage Orders</a>
  <a href="<?= h(path('admin/admin-services.php')) ?>" class="qa-btn qa-btn-outline"><?= icon('services', 16) ?> Services</a>
  <a href="<?= h(path('admin/admin-sync.php')) ?>" class="qa-btn qa-btn-dark"><?= icon('sync', 16) ?> Sync Services</a>
  <a href="<?= h(path('admin/admin-settings.php')) ?>" class="qa-btn qa-btn-outline"><?= icon('settings', 16) ?> Settings</a>
  <a href="<?= h(path('admin/admin-tickets.php')) ?>" class="qa-btn qa-btn-outline"><?= icon('tickets', 16) ?> Tickets</a>
  <a href="<?= h(path('admin/admin-deposits.php')) ?>" class="qa-btn"><?= icon('deposit', 16) ?> Pending Deposits<?= $pendingDeposits > 0 ? ' (' . $pendingDeposits . ')' : '' ?></a>
  <a href="<?= h(path('admin/admin-child-panels.php')) ?>" class="qa-btn"><?= icon('server', 16) ?> Child Panels<?= $pendingChildPanels > 0 ? ' (' . $pendingChildPanels . ')' : '' ?></a>
</div>

<div class="admin-charts">
  <div class="admin-chart-card">
    <h3>Orders — last 7 days</h3>
    <div class="admin-chart-wrap"><canvas id="ordersChart" aria-label="Orders last 7 days"></canvas></div>
  </div>
  <div class="admin-chart-card">
    <h3>Revenue — last 7 days</h3>
    <div class="admin-chart-wrap"><canvas id="revenueChart" aria-label="Revenue last 7 days"></canvas></div>
  </div>
</div>

<div class="grid2">
  <div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:18px 18px 0;"><div class="card-title">Recent Orders</div></div>
    <div class="table-wrap">
    <table class="table" style="font-size:12px;">
      <thead><tr><th>ID</th><th>User</th><th>Charge</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($recentOrders as $o): ?>
        <tr>
          <td>#<?= $o['id'] ?></td>
          <td><?= h($o['username']) ?></td>
          <td>$<?= number_format($o['charge'], 4) ?></td>
          <td><span class="badge status-<?= str_replace(' ', '-', h($o['status'])) ?>"><?= h($o['status']) ?></span></td>
          <td style="color:var(--text-muted)"><?= date('m/d H:i', strtotime($o['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:18px 18px 0;"><div class="card-title">Recent Users</div></div>
    <div class="table-wrap">
    <table class="table" style="font-size:12px;">
      <thead><tr><th>User</th><th>Balance</th><th>Spent</th><th>Joined</th></tr></thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
        <tr>
          <td><?= h($u['username']) ?><br><span style="color:var(--text-muted);font-size:10px;"><?= h($u['email']) ?></span></td>
          <td>$<?= number_format($u['balance'], 2) ?></td>
          <td>$<?= number_format($u['spent'], 2) ?></td>
          <td style="color:var(--text-muted)"><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script>
(function () {
  if (typeof Chart === 'undefined') return;
  var labels = <?= json_encode($chartLabels) ?>;
  var orders = <?= json_encode($chartOrders) ?>;
  var revenue = <?= json_encode($chartRevenue) ?>;
  var gridColor = getComputedStyle(document.body).getPropertyValue('--border').trim() || '#f0e6e8';
  var textColor = getComputedStyle(document.body).getPropertyValue('--text-muted').trim() || '#6b4a50';
  var primary = getComputedStyle(document.body).getPropertyValue('--primary').trim() || '#E30A17';
  var scales = {
    x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 } } },
    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 } } }
  };
  new Chart(document.getElementById('ordersChart'), {
    type: 'bar',
    data: { labels: labels, datasets: [{ label: 'Orders', data: orders, backgroundColor: primary, borderRadius: 6 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: scales }
  });
  new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: { labels: labels, datasets: [{ label: 'Revenue ($)', data: revenue, borderColor: primary, backgroundColor: 'rgba(227,10,23,.12)', fill: true, tension: .35 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: scales }
  });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
