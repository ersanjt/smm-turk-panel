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

require_once __DIR__ . '/../layouts/header.php';
?>

<style>
.admin-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.admin-card{background:#fff;border-radius:14px;padding:20px;box-shadow:var(--shadow);border:1px solid var(--border);display:flex;align-items:center;gap:16px}
.admin-card .ac-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.admin-card .ac-info .ac-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted)}
.admin-card .ac-info .ac-value{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;margin-top:2px}
.quick-actions{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px}
.qa-btn{padding:10px 20px;border-radius:10px;font-family:'Syne',sans-serif;font-size:13px;font-weight:700;cursor:pointer;border:none;text-decoration:none;display:inline-block;transition:all .2s}
</style>

<!-- Stats -->
<div class="admin-grid">
  <div class="admin-card">
    <div class="ac-icon" style="background:#e8e8ff;">👥</div>
    <div class="ac-info">
      <div class="ac-label">Total Users</div>
      <div class="ac-value"><?= number_format($totalUsers) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon" style="background:#e8ffe8;">📦</div>
    <div class="ac-info">
      <div class="ac-label">Total Orders</div>
      <div class="ac-value"><?= number_format($totalOrders) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon" style="background:#fff3e0;">💰</div>
    <div class="ac-info">
      <div class="ac-label">Total Revenue</div>
      <div class="ac-value">$<?= number_format($totalRevenue, 2) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon" style="background:#ffe8e8;">⏳</div>
    <div class="ac-info">
      <div class="ac-label">Pending Orders</div>
      <div class="ac-value"><?= number_format($pendingOrders) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon" style="background:#e8ffe8;">🎫</div>
    <div class="ac-info">
      <div class="ac-label">Open Tickets</div>
      <div class="ac-value"><?= number_format($openTickets) ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon" style="background:#f0e8ff;">🌐</div>
    <div class="ac-info">
      <div class="ac-label">Provider Balance</div>
      <div class="ac-value"><?= $providerBalance !== null ? '$' . number_format($providerBalance, 2) : 'N/A' ?></div>
    </div>
  </div>
  <div class="admin-card">
    <div class="ac-icon" style="background:#e8e0ff;">₿</div>
    <div class="ac-info">
      <div class="ac-label">Pending Deposits</div>
      <div class="ac-value"><?= number_format($pendingDeposits) ?></div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions" style="margin-bottom:24px;">
  <a href="<?= h(path('admin/admin-users.php')) ?>" class="qa-btn" style="background:var(--primary);color:#fff;">👥 Manage Users</a>
  <a href="<?= h(path('admin/admin-orders.php')) ?>" class="qa-btn" style="background:#00c853;color:#fff;">📦 Manage Orders</a>
  <a href="<?= h(path('admin/admin-services.php')) ?>" class="qa-btn" style="background:#ff9100;color:#fff;">⭐ Services</a>
  <a href="<?= h(path('admin/admin-sync.php')) ?>" class="qa-btn" style="background:#0a0a1a;color:#fff;">🔄 Sync Services</a>
  <a href="<?= h(path('admin/admin-settings.php')) ?>" class="qa-btn" style="background:#6b6b8a;color:#fff;">⚙️ Settings</a>
  <a href="<?= h(path('admin/admin-tickets.php')) ?>" class="qa-btn" style="background:#ff3d00;color:#fff;">🎫 Tickets</a>
  <a href="<?= h(path('admin/admin-deposits.php')) ?>" class="qa-btn" style="background:#9c27b0;color:#fff;">₿ Pending Deposits <?= $pendingDeposits > 0 ? '(' . $pendingDeposits . ')' : '' ?></a>
</div>

<div class="grid2">
  <!-- Recent Orders -->
  <div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:18px 18px 0;"><div class="card-title">📦 Recent Orders</div></div>
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

  <!-- Recent Users -->
  <div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:18px 18px 0;"><div class="card-title">👥 Recent Users</div></div>
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
