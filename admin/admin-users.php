<?php
require_once __DIR__ . '/../includes/init.php';
$auth->requireAdmin();
$pageTitle = 'Manage Users';
$db = Database::getInstance();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['uid'] ?? 0);

    if ($action === 'add_balance' && $uid) {
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount > 0) {
            $u = $db->fetch("SELECT balance FROM users WHERE id=?", [$uid]);
            $db->execute("UPDATE users SET balance = balance + ? WHERE id=?", [$amount, $uid]);
            $db->insert("INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description) VALUES (?,?,?,?,?,?)",
                [$uid, 'admin', $amount, $u['balance'], $u['balance']+$amount, 'Admin deposit']);
            flash('success', "Balance added successfully.");
        }
    } elseif ($action === 'ban' && $uid) {
        $db->execute("UPDATE users SET status='banned' WHERE id=? AND role='user'", [$uid]);
        flash('success', "User banned.");
    } elseif ($action === 'unban' && $uid) {
        $db->execute("UPDATE users SET status='active' WHERE id=?", [$uid]);
        flash('success', "User unbanned.");
    }
    redirect('/admin/admin-users.php');
}

$search = trim($_GET['q'] ?? '');
$where  = $search ? "WHERE username LIKE ? OR email LIKE ?" : "";
$params = $search ? ["%$search%", "%$search%"] : [];
$users  = $db->fetchAll("SELECT * FROM users $where ORDER BY created_at DESC LIMIT 50", $params);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="padding:0;overflow:hidden;">
  <div style="padding:18px 20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div class="card-title" style="margin:0">👥 Manage Users</div>
    <form method="GET" style="display:flex;gap:8px;">
      <input type="text" name="q" value="<?= h($search) ?>" class="form-control" style="width:220px;" placeholder="Search username or email…">
      <button type="submit" class="btn btn-primary" style="padding:8px 16px;">Search</button>
    </form>
  </div>
  <div style="overflow-x:auto;margin-top:16px;">
    <table class="table">
      <thead>
        <tr><th>ID</th><th>Username</th><th>Email</th><th>Balance</th><th>Spent</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>#<?= $u['id'] ?></td>
          <td><strong><?= h($u['username']) ?></strong><?= $u['role']==='admin' ? ' <span class="badge badge-blue">Admin</span>' : '' ?></td>
          <td style="font-size:12px;"><?= h($u['email']) ?></td>
          <td><strong>$<?= number_format($u['balance'], 4) ?></strong></td>
          <td>$<?= number_format($u['spent'], 4) ?></td>
          <td>
            <span class="badge <?= $u['status']==='active' ? 'badge-green' : 'badge-red' ?>">
              <?= h($u['status']) ?>
            </span>
          </td>
          <td style="font-size:11px;color:var(--text-muted);"><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;align-items:center;">
              <!-- Add balance form -->
              <form method="POST" style="display:flex;gap:4px;" onsubmit="return confirm('Add balance?')">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add_balance">
                <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                <input type="number" name="amount" step="0.01" min="0.01" placeholder="$" class="form-control" style="width:70px;padding:5px 8px;font-size:12px;">
                <button type="submit" class="btn btn-success" style="padding:5px 10px;font-size:11px;">+$</button>
              </form>
              <!-- Ban/Unban -->
              <?php if ($u['role'] !== 'admin'): ?>
              <form method="POST" onsubmit="return confirm('Are you sure?')">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                <input type="hidden" name="action" value="<?= $u['status']==='active' ? 'ban' : 'unban' ?>">
                <button type="submit" class="btn <?= $u['status']==='active' ? 'btn-danger' : 'btn-success' ?>"
                        style="padding:5px 10px;font-size:11px;">
                  <?= $u['status']==='active' ? '🚫 Ban' : '✅ Unban' ?>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
