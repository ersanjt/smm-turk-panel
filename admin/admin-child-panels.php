<?php
require_once __DIR__ . '/../app/init.php';
$auth->requireAdmin();
$pageTitle = 'Child Panels';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $id = (int) ($_POST['panel_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $panel = $db->fetch(
        "SELECT cp.*, u.username, u.email FROM child_panels cp JOIN users u ON u.id = cp.user_id WHERE cp.id = ?",
        [$id]
    );

    if (!$panel) {
        flash('error', 'Child panel order not found.');
        redirect(url('admin/admin-child-panels.php'));
    }

    if ($action === 'activate' && $panel['status'] === 'pending') {
        $db->execute("UPDATE child_panels SET status = 'active' WHERE id = ?", [$id]);
        flash('success', 'Child panel activated for ' . $panel['domain'] . '. User can now access their panel.');
    } elseif ($action === 'suspend' && $panel['status'] === 'active') {
        $db->execute("UPDATE child_panels SET status = 'suspended' WHERE id = ?", [$id]);
        flash('success', 'Child panel suspended.');
    } elseif ($action === 'reactivate' && $panel['status'] === 'suspended') {
        $db->execute("UPDATE child_panels SET status = 'active' WHERE id = ?", [$id]);
        flash('success', 'Child panel reactivated.');
    } elseif ($action === 'cancel' && in_array($panel['status'], ['pending', 'suspended'], true)) {
        $db->beginTransaction();
        try {
            $db->execute("UPDATE child_panels SET status = 'cancelled' WHERE id = ?", [$id]);
            if ($panel['status'] === 'pending') {
                $price = (float) $panel['price'];
                $db->execute(
                    "UPDATE users SET balance = balance + ?, spent = GREATEST(0, spent - ?) WHERE id = ?",
                    [$price, $price, $panel['user_id']]
                );
            }
            $db->commit();
            flash('success', 'Order cancelled' . ($panel['status'] === 'pending' ? ' and balance refunded.' : '.'));
        } catch (Throwable $e) {
            $db->rollBack();
            flash('error', 'Could not cancel order.');
        }
    } else {
        flash('error', 'Invalid action for current status.');
    }
    redirect(url('admin/admin-child-panels.php'));
}

$panels = [];
try {
    $panels = $db->fetchAll(
        "SELECT cp.*, u.username, u.email
         FROM child_panels cp
         JOIN users u ON u.id = cp.user_id
         ORDER BY FIELD(cp.status, 'pending', 'active', 'suspended', 'cancelled'), cp.created_at DESC"
    );
} catch (Throwable $e) {
    flash('error', 'child_panels table missing. Run: php migrate-child-panel.php');
}

$pendingCount = count(array_filter($panels, fn($p) => ($p['status'] ?? '') === 'pending'));

require_once __DIR__ . '/../layouts/header.php';
?>

<div style="max-width:1100px;">
  <div class="card" style="margin-bottom:18px;">
    <div class="card-title"><?= icon('server', 20, '', ['style' => 'vertical-align:-4px;margin-right:8px']) ?> Child panel orders</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
      Orders stay <strong>pending</strong> until you activate them manually (usually after nameservers are set).
      <?= $pendingCount > 0 ? '<strong style="color:var(--primary);">' . $pendingCount . ' waiting for activation.</strong>' : '' ?>
    </p>
    <?php if (empty($panels)): ?>
    <p style="color:var(--text-muted);">No child panel orders yet.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Domain</th>
            <th>Admin user</th>
            <th>Price</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($panels as $p):
            $st = $p['status'] ?? 'pending';
            $badge = match ($st) {
                'active' => 'badge-green',
                'suspended' => 'badge-orange',
                'cancelled' => 'badge-red',
                default => 'badge-orange',
            };
        ?>
          <tr>
            <td>#<?= (int) $p['id'] ?></td>
            <td><?= h($p['username']) ?><br><span style="font-size:11px;color:var(--text-muted);"><?= h($p['email']) ?></span></td>
            <td><strong><?= h($p['domain']) ?></strong><br><span style="font-size:11px;color:var(--text-muted);"><?= h($p['currency']) ?></span></td>
            <td style="font-size:12px;"><?= h($p['admin_username']) ?></td>
            <td>$<?= number_format((float) $p['price'], 2) ?></td>
            <td><span class="badge <?= $badge ?>"><?= h($st) ?></span></td>
            <td style="font-size:12px;color:var(--text-muted);"><?= h(date('Y-m-d H:i', strtotime($p['created_at'] ?? 'now'))) ?></td>
            <td style="white-space:nowrap;">
              <?php if ($st === 'pending'): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="panel_id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="action" value="activate">
                <button type="submit" class="btn btn-primary" style="padding:6px 10px;font-size:11px;">Activate</button>
              </form>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel and refund user?');">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="panel_id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="btn" style="padding:6px 10px;font-size:11px;background:var(--text-muted);color:#fff;">Cancel</button>
              </form>
              <?php elseif ($st === 'active'): ?>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Suspend this panel?');">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="panel_id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="action" value="suspend">
                <button type="submit" class="btn" style="padding:6px 10px;font-size:11px;">Suspend</button>
              </form>
              <?php elseif ($st === 'suspended'): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="panel_id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="action" value="reactivate">
                <button type="submit" class="btn btn-primary" style="padding:6px 10px;font-size:11px;">Reactivate</button>
              </form>
              <?php else: ?>
              <span style="font-size:11px;color:var(--text-muted);">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <p style="font-size:12px;color:var(--text-muted);">
    Set nameservers in <a href="<?= h(path('admin/admin-settings.php')) ?>">Settings → Child Panel</a> so users know what to configure.
  </p>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
