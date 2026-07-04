<?php
require_once __DIR__ . '/../app/init.php';
$auth->requireAdmin();
$pageTitle = 'Pending Deposits';
$db = Database::getInstance();

// Approve deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id']) && csrf_verify()) {
    $tid = (int) $_POST['approve_id'];
    $dm = new DepositManager();
    $result = $dm->approvePendingDeposit($tid);
    if ($result['success']) {
        $msg = 'Deposit approved. User balance updated by $' . number_format($result['amount'], 2) . '.';
        if (!empty($result['email_sent'])) {
            $msg .= ' Confirmation email sent.';
        } else {
            $msg .= ' Email could not be sent — check SMTP settings.';
        }
        flash('success', $msg);
    } else {
        flash('error', $result['error'] ?? 'Failed to approve deposit.');
    }
    redirect(url('admin/admin-deposits.php'));
}

// Cancel deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id']) && csrf_verify()) {
    $tid = (int) $_POST['cancel_id'];
    $db->execute("UPDATE transactions SET status = 'failed' WHERE id = ? AND type = 'deposit' AND status = 'pending'", [$tid]);
    flash('success', 'Deposit cancelled.');
    redirect(url('admin/admin-deposits.php'));
}

$pending = $db->fetchAll(
    "SELECT t.*, u.username, u.email FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type = 'deposit' AND t.status = 'pending' ORDER BY t.created_at DESC"
);

require_once __DIR__ . '/../layouts/header.php';
?>

<div style="max-width:900px;">
  <div class="card" style="margin-bottom:18px;">
    <div class="card-title">₿ Pending crypto deposits</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Approve after the crypto payment arrives in your wallet. Users pay by sending cryptocurrency only — no cards or PayPal on this panel.</p>
    <?php if (empty($pending)): ?>
    <p style="color:var(--text-muted);">No pending deposits.</p>
    <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>ID</th><th>User</th><th>Amount</th><th>TxHash / Ref</th><th>Date</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pending as $t): ?>
        <tr>
          <td>#<?= $t['id'] ?></td>
          <td><?= h($t['username']) ?><br><span style="font-size:11px;color:var(--text-muted);"><?= h($t['email']) ?></span></td>
          <td><strong>$<?= number_format($t['amount'], 2) ?></strong></td>
          <td style="font-size:11px;word-break:break-all;max-width:180px;"><?= h($t['reference'] ?: '—') ?></td>
          <td style="color:var(--text-muted);font-size:12px;"><?= date('Y-m-d H:i', strtotime($t['created_at'])) ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="approve_id" value="<?= $t['id'] ?>">
              <button type="submit" class="btn btn-primary" style="padding:6px 12px;font-size:12px;">Approve</button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this deposit request?');">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="cancel_id" value="<?= $t['id'] ?>">
              <button type="submit" class="btn" style="padding:6px 12px;font-size:12px;background:var(--text-muted);color:#fff;">Cancel</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
