<?php
require_once __DIR__ . '/../app/init.php';
$auth->requireAdmin();
$pageTitle = 'Sync Services';
$om = new OrderManager();
$db = Database::getInstance();

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $om->syncServices();
    if ($result['success']) {
        flash('success', "✅ Synced {$result['synced']} services from provider.");
    } else {
        flash('error', "❌ {$result['error']}");
    }
    redirect(url('admin/admin-services.php'));
}

require_once __DIR__ . '/../layouts/header.php';
?>
<div class="card" style="max-width:500px;text-align:center;padding:40px;">
  <div style="font-size:48px;margin-bottom:16px;">🔄</div>
  <div class="card-title">Sync Services from Provider</div>
  <p style="color:var(--text-muted);font-size:13px;margin-bottom:24px;">
    This will fetch all available services from SmmFollows API and update your services database.
    Existing services will be updated. Your custom markup will be preserved.
  </p>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <button type="submit" class="btn btn-primary btn-block">🔄 Sync Now</button>
  </form>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
