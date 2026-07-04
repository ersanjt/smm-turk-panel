<?php
require_once __DIR__ . '/../app/init.php';
$auth->requireAdmin();
$pageTitle = 'Email Test';
$db = Database::getInstance();
$mail = new Mail();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    if (isset($_POST['test_email'])) {
        $to = trim($_POST['test_to'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid test email address.');
        } else {
            $ok = $mail->sendTest($to);
            if ($ok) {
                flash('success', 'Test email sent to ' . $to . '. Check inbox and spam folder.');
            } else {
                flash('error', 'Send failed: ' . ($mail->getLastError() ?? 'Unknown error'));
            }
        }
        redirect(url('admin/admin-mail.php'));
    }
}

$diag = $mail->getDiagnostics();
$user = $auth->getCurrentUser();
$defaultTestTo = $user['email'] ?? '';

$logFile = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__)) . '/tmp/logs/mail.log';
$logTail = '';
if (is_readable($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $logTail = implode("\n", array_slice($lines, -25));
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div style="max-width:720px;">
  <div class="card" style="margin-bottom:18px;">
    <div class="card-title"><?= icon('message', 20, '', ['style' => 'vertical-align:-4px;margin-right:8px']) ?> Email diagnostics</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;line-height:1.6;">
      <strong>Receiving</strong> email = create a mailbox in cPanel → Email Accounts (e.g. <code>contact@smm-turk.com</code>).<br>
      <strong>Sending</strong> from the panel = configure below, then send a test.
    </p>
    <table class="table" style="font-size:13px;">
      <tr><td>Mail From</td><td><code><?= h($diag['from']) ?></code></td></tr>
      <tr><td>Reply-To</td><td><code><?= h($diag['reply_to'] ?? '—') ?></code></td></tr>
      <tr><td>Mode</td><td><?= h($diag['mail_mode']) ?></td></tr>
      <tr><td>SMTP Host</td><td><code><?= h($diag['smtp_host']) ?></code></td></tr>
      <tr><td>SMTP Port</td><td><?= h($diag['smtp_port']) ?></td></tr>
      <tr><td>SMTP User</td><td><code><?= h($diag['smtp_user']) ?></code></td></tr>
      <tr><td>Encryption</td><td><?= h($diag['smtp_encryption']) ?></td></tr>
      <tr><td>cPanel SMTP hint</td><td><code><?= h($diag['cpanel_hint_host']) ?></code> port 465 (SSL) or 587 (TLS)</td></tr>
    </table>
    <p style="margin-top:14px;font-size:12px;color:var(--text-muted);">
      <a href="<?= h(path('admin/admin-settings.php')) ?>">Edit email settings →</a>
    </p>
  </div>

  <div class="card" style="margin-bottom:18px;">
    <div class="card-title">Send test email</div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="test_email" value="1">
      <div class="form-group">
        <label class="form-label">Send to</label>
        <input type="email" name="test_to" class="form-control" required value="<?= h($defaultTestTo) ?>" placeholder="your@email.com">
      </div>
      <button type="submit" class="btn btn-primary">Send test email</button>
    </form>
  </div>

  <?php if ($logTail !== ''): ?>
  <div class="card">
    <div class="card-title">Recent mail log</div>
    <pre style="font-size:11px;background:var(--bg);padding:12px;border-radius:10px;overflow:auto;max-height:220px;"><?= h($logTail) ?></pre>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
