<?php
require_once __DIR__ . '/includes/init.php';
$auth->requireLogin();
$pageTitle = 'Support Tickets';
$db  = Database::getInstance();
$uid = $auth->getUserId();

// Handle new ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($subject && $message) {
        $tid = $db->insert("INSERT INTO tickets (user_id, subject) VALUES (?, ?)", [$uid, $subject]);
        $db->insert("INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)", [$tid, $uid, $message]);
        flash('success', '✅ Ticket #' . $tid . ' created. We\'ll reply within 24 hours.');
    } else {
        flash('error', 'Please fill in all fields.');
    }
    redirect('/tickets.php');
}

$tickets = $db->fetchAll("SELECT * FROM tickets WHERE user_id=? ORDER BY updated_at DESC", [$uid]);

require_once __DIR__ . '/includes/header.php';
?>

<div class="grid2" style="align-items:start;">
  <div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:18px 18px 0;"><div class="card-title">🎫 My Tickets</div></div>
    <?php if (empty($tickets)): ?>
    <div style="text-align:center;padding:40px;color:var(--text-muted);">
      <div style="font-size:40px;margin-bottom:10px;">📭</div>
      <div>No tickets yet. Create one below.</div>
    </div>
    <?php else: ?>
    <table class="table">
      <thead><tr><th>ID</th><th>Subject</th><th>Status</th><th>Updated</th></tr></thead>
      <tbody>
        <?php foreach ($tickets as $t): ?>
        <tr>
          <td>#<?= $t['id'] ?></td>
          <td><?= h(mb_substr($t['subject'], 0, 50)) ?></td>
          <td><span class="badge <?= $t['status']==='open' ? 'badge-orange' : ($t['status']==='answered' ? 'badge-green' : 'badge-gray') ?>"><?= h($t['status']) ?></span></td>
          <td style="font-size:11px;color:var(--text-muted);"><?= date('Y-m-d H:i', strtotime($t['updated_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title">➕ Open New Ticket</div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label class="form-label">Subject</label>
        <input type="text" name="subject" class="form-control" placeholder="Describe your issue…" required>
      </div>
      <div class="form-group">
        <label class="form-label">Message</label>
        <textarea name="message" class="form-control" rows="6" placeholder="Please describe your issue in detail. Include order ID if relevant…" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Submit Ticket</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
