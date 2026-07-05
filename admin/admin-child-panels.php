<?php
require_once __DIR__ . '/../app/init.php';
$auth->requireAdmin();
$pageTitle = 'Child Panels';
$db = Database::getInstance();
$cpm = new ChildPanelManager();

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

    if ($action === 'provision' || ($action === 'activate' && ($panel['status'] ?? '') === 'pending')) {
        $result = $cpm->provision($id);
        if ($result['success']) {
            flash('success', 'Panel provisioned for ' . $panel['domain'] . '.');
        } else {
            flash('error', $result['error'] ?? 'Provisioning failed.');
        }
    } elseif ($action === 'suspend' && $panel['status'] === 'active') {
        $db->execute("UPDATE child_panels SET status = 'suspended' WHERE id = ?", [$id]);
        flash('success', 'Child panel suspended.');
    } elseif ($action === 'reactivate' && $panel['status'] === 'suspended') {
        $db->execute("UPDATE child_panels SET status = 'active' WHERE id = ?", [$id]);
        flash('success', 'Child panel reactivated.');
    } elseif ($action === 'cancel' && in_array($panel['status'], ['pending', 'suspended'], true)) {
        $db->beginTransaction();
        try {
            $db->execute("UPDATE child_panels SET status = 'cancelled', provision_status = 'failed' WHERE id = ?", [$id]);
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
    flash('error', 'child_panels table missing. Run: php migrate-db.php');
}

$autoMode = $cpm->autoMode();
$pendingCount = count(array_filter($panels, fn($p) => ($p['status'] ?? '') === 'pending'));

require_once __DIR__ . '/../layouts/header.php';
?>

<div style="max-width:1200px;">
  <div class="card" style="margin-bottom:18px;">
    <div class="card-title"><?= icon('server', 20, '', ['style' => 'vertical-align:-4px;margin-right:8px']) ?> Child panel orders</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
      Automation: <strong><?= h($autoMode) ?></strong>
      — WHM + auto-deploy <?= $cpm->provisioningEnabled() ? '<span style="color:#16a34a;">configured</span>' : '<span style="color:var(--primary);">not configured (set WHM API in Settings)</span>' ?>.
      Cron retries DNS-waiting orders every 5 min.
      <?= $pendingCount > 0 ? '<br><strong style="color:var(--primary);">' . $pendingCount . ' pending.</strong>' : '' ?>
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
            <th>Provision</th>
            <th>Price</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($panels as $p):
            $st = $p['status'] ?? 'pending';
            $ps = $p['provision_status'] ?? 'pending';
            $badge = match ($st) {
                'active' => 'badge-green',
                'suspended' => 'badge-orange',
                'cancelled' => 'badge-red',
                default => 'badge-orange',
            };
            $psBadge = match ($ps) {
                'ready' => 'badge-green',
                'failed' => 'badge-red',
                'dns_wait' => 'badge-orange',
                default => 'badge-orange',
            };
        ?>
          <tr>
            <td>#<?= (int) $p['id'] ?></td>
            <td><?= h($p['username']) ?><br><span style="font-size:11px;color:var(--text-muted);"><?= h($p['email']) ?></span></td>
            <td>
              <strong><?= h($p['domain']) ?></strong>
              <?php if (!empty($p['panel_url'])): ?><br><a href="<?= h($p['panel_url']) ?>" target="_blank" rel="noopener" style="font-size:11px;"><?= h($p['panel_url']) ?></a><?php endif; ?>
              <br><span style="font-size:11px;color:var(--text-muted);"><?= h($p['admin_username']) ?> · <?= h($p['currency']) ?></span>
            </td>
            <td>
              <span class="badge <?= $psBadge ?>"><?= h($ps) ?></span>
              <?php if (!empty($p['ns_verified'])): ?><br><span style="font-size:10px;color:#16a34a;">NS ok</span><?php endif; ?>
              <?php if (!empty($p['provision_error'])): ?><br><span style="font-size:10px;color:var(--primary);" title="<?= h($p['provision_error']) ?>">⚠ WHM</span><?php endif; ?>
            </td>
            <td>$<?= number_format((float) $p['price'], 2) ?></td>
            <td><span class="badge <?= $badge ?>"><?= h($st) ?></span></td>
            <td style="font-size:12px;color:var(--text-muted);"><?= h(date('Y-m-d H:i', strtotime($p['created_at'] ?? 'now'))) ?></td>
            <td style="white-space:nowrap;">
              <?php
              $needsDeploy = $ps !== 'ready' || (empty($p['document_root']) && $st === 'active');
              if ($st === 'pending' || $needsDeploy):
              ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="panel_id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="action" value="provision">
                <button type="submit" class="btn btn-primary" style="padding:6px 10px;font-size:11px;"><?= $st === 'pending' ? 'Deploy' : 'Retry deploy' ?></button>
              </form>
              <?php endif; ?>
              <?php if ($st === 'pending'): ?>
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
          <?php if (!empty($p['provision_log'])): ?>
          <tr>
            <td colspan="8" style="font-size:11px;color:var(--text-muted);background:var(--bg);padding:8px 12px;">
              <pre style="margin:0;white-space:pre-wrap;font-family:ui-monospace,monospace;max-height:80px;overflow:auto;"><?= h($p['provision_log']) ?></pre>
            </td>
          </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <p style="font-size:12px;color:var(--text-muted);">
    Configure automation in <a href="<?= h(path('admin/admin-settings.php')) ?>">Settings → Child Panel</a>.
    Config files: <code>storage/child-panels/{id}/config.json</code>
  </p>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
