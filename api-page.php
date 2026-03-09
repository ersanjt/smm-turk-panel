<?php // api-page.php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'API';
$user = $auth->getCurrentUser();
require_once __DIR__ . '/layouts/header.php';
?>
<div class="card" style="background:var(--sidebar-bg);color:#fff;margin-bottom:18px;">
  <div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;margin-bottom:8px;">⚙️ API Access</div>
  <div style="font-size:13px;opacity:.7;margin-bottom:16px;">Integrate SMM Turk into your own applications.</div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px;margin-bottom:14px;">
    <div><div style="opacity:.5;font-size:10px;text-transform:uppercase;letter-spacing:1px;margin-bottom:3px;">HTTP Method</div><strong>POST</strong></div>
    <div><div style="opacity:.5;font-size:10px;text-transform:uppercase;letter-spacing:1px;margin-bottom:3px;">Format</div><strong>JSON</strong></div>
  </div>
  <div style="opacity:.5;font-size:10px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">API URL</div>
  <div style="background:rgba(255,255,255,.1);border-radius:8px;padding:10px 14px;font-family:monospace;font-size:13px;"><?= SITE_URL ?>/api/v2</div>
  <div style="margin-top:14px;">
    <div style="opacity:.5;font-size:10px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Your API Key</div>
    <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;font-family:monospace;font-size:13px;border:1px solid rgba(255,255,255,.1);">
      <span><?= h($user['api_key'] ?? 'N/A') ?></span>
      <button onclick="navigator.clipboard.writeText('<?= h($user['api_key']) ?>');this.textContent='Copied!'" style="background:#fff;color:var(--primary);border:none;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;">Copy</button>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-title">📋 Endpoints</div>
  <table class="table">
    <thead><tr><th>Action</th><th>Parameters</th><th>Description</th></tr></thead>
    <tbody>
      <tr><td><code>services</code></td><td>key, action</td><td>List all services</td></tr>
      <tr><td><code>add</code></td><td>key, action, service, link, quantity</td><td>Place an order</td></tr>
      <tr><td><code>status</code></td><td>key, action, order</td><td>Get order status</td></tr>
      <tr><td><code>status</code></td><td>key, action, orders</td><td>Get multiple statuses (up to 100)</td></tr>
      <tr><td><code>refill</code></td><td>key, action, order</td><td>Request refill</td></tr>
      <tr><td><code>refill_status</code></td><td>key, action, refill</td><td>Get refill status</td></tr>
      <tr><td><code>cancel</code></td><td>key, action, orders</td><td>Cancel orders</td></tr>
      <tr><td><code>balance</code></td><td>key, action</td><td>Get balance</td></tr>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
