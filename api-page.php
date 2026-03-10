<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'API';
$user = $auth->getCurrentUser();
$apiUrl = (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/api/v2';
require_once __DIR__ . '/layouts/header.php';
?>
<style>
.api-block { margin-bottom: 28px; }
.api-block h3 { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; margin-bottom: 12px; color: var(--text); }
.api-params { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 12px; }
.api-params th { text-align: left; padding: 10px 12px; background: var(--bg); font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); }
.api-params td { padding: 10px 12px; border-bottom: 1px solid var(--border); }
.api-params code { background: rgba(227,10,23,.08); padding: 2px 6px; border-radius: 4px; font-size: 12px; }
.api-example { background: #1a0a0e; color: #e4c5c9; padding: 14px 18px; border-radius: 10px; font-family: monospace; font-size: 12px; overflow-x: auto; white-space: pre; margin-top: 8px; }
.api-example .key { color: #f0a0a8; }
</style>

<div class="card" style="background: var(--sidebar-bg); color: #fff; margin-bottom: 24px;">
  <div style="font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 800; margin-bottom: 8px;">API</div>
  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px; font-size: 13px; margin-bottom: 16px;">
    <div><span style="opacity: .6;">HTTP Method</span><br><strong>POST</strong></div>
    <div><span style="opacity: .6;">API URL</span><br><strong><?= h($apiUrl) ?></strong></div>
    <div><span style="opacity: .6;">Response format</span><br><strong>JSON</strong></div>
  </div>
  <div style="margin-top: 14px;">
    <span style="opacity: .6; font-size: 11px; text-transform: uppercase;">Your API Key</span>
    <div style="background: rgba(255,255,255,.1); border-radius: 10px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; font-family: monospace; font-size: 13px; border: 1px solid rgba(255,255,255,.1); margin-top: 6px;">
      <span><?= h($user['api_key'] ?? 'N/A') ?></span>
      <button type="button" onclick="navigator.clipboard.writeText('<?= h($user['api_key'] ?? '') ?>'); this.textContent='Copied!';" style="background:#fff;color:var(--primary);border:none;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;">Copy</button>
    </div>
  </div>
  <p style="font-size: 12px; opacity: .8; margin-top: 12px;">Get or regenerate your API key on the <a href="<?= h(path('account-settings.php')) ?>" style="color: #ff9aa2;">Account Settings</a> page.</p>
</div>

<div class="card">
  <div class="card-title">Service list</div>
  <div class="api-block">
    <table class="api-params">
      <thead><tr><th>Parameters</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>key</code></td><td>Your API key</td></tr>
        <tr><td><code>action</code></td><td>services</td></tr>
      </tbody>
    </table>
    <div class="api-example">[
  { "service": 1, "name": "Followers", "type": "Default", "category": "First Category", "rate": "0.90", "min": "50", "max": "10000", "refill": true, "cancel": true },
  { "service": 2, "name": "Comments", "type": "Custom Comments", "category": "Second Category", "rate": "8", "min": "10", "max": "1500", "refill": false, "cancel": true }
]</div>
  </div>

  <div class="card-title" style="margin-top: 28px;">Add order</div>
  <div class="api-block">
    <table class="api-params">
      <thead><tr><th>Parameters</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>key</code></td><td>Your API key</td></tr>
        <tr><td><code>action</code></td><td>add</td></tr>
        <tr><td><code>service</code></td><td>Service ID</td></tr>
        <tr><td><code>link</code></td><td>Link to page</td></tr>
        <tr><td><code>quantity</code></td><td>Needed quantity</td></tr>
        <tr><td><code>runs</code> <span style="color:var(--text-muted);">(optional)</span></td><td>Runs to deliver</td></tr>
        <tr><td><code>interval</code> <span style="color:var(--text-muted);">(optional)</span></td><td>Interval in minutes</td></tr>
      </tbody>
    </table>
    <div class="api-example">{ "order": 23501 }</div>
  </div>

  <div class="card-title" style="margin-top: 28px;">Order status</div>
  <div class="api-block">
    <table class="api-params">
      <thead><tr><th>Parameters</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>key</code></td><td>Your API key</td></tr>
        <tr><td><code>action</code></td><td>status</td></tr>
        <tr><td><code>order</code></td><td>Order ID</td></tr>
      </tbody>
    </table>
    <div class="api-example">{ "charge": "0.27819", "start_count": "3572", "status": "Partial", "remains": "157", "currency": "USD" }</div>
  </div>

  <div class="card-title" style="margin-top: 28px;">Multiple orders status</div>
  <div class="api-block">
    <table class="api-params">
      <thead><tr><th>Parameters</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>key</code></td><td>Your API key</td></tr>
        <tr><td><code>action</code></td><td>status</td></tr>
        <tr><td><code>orders</code></td><td>Order IDs separated by comma (up to 100)</td></tr>
      </tbody>
    </table>
    <div class="api-example">{
  "1": { "charge": "0.27819", "start_count": "3572", "status": "Partial", "remains": "157", "currency": "USD" },
  "10": { "error": "Incorrect order ID" },
  "100": { "charge": "1.44219", "start_count": "234", "status": "In progress", "remains": "10", "currency": "USD" }
}</div>
  </div>

  <div class="card-title" style="margin-top: 28px;">Create refill</div>
  <div class="api-block">
    <table class="api-params">
      <thead><tr><th>Parameters</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>key</code></td><td>Your API key</td></tr>
        <tr><td><code>action</code></td><td>refill</td></tr>
        <tr><td><code>order</code></td><td>Order ID</td></tr>
      </tbody>
    </table>
    <div class="api-example">{ "refill": "1" }</div>
  </div>

  <div class="card-title" style="margin-top: 28px;">Create multiple refill</div>
  <div class="api-block">
    <table class="api-params">
      <thead><tr><th>Parameters</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>key</code></td><td>Your API key</td></tr>
        <tr><td><code>action</code></td><td>refill</td></tr>
        <tr><td><code>orders</code></td><td>Order IDs separated by comma (up to 100)</td></tr>
      </tbody>
    </table>
    <div class="api-example">[
  { "order": 1, "refill": 1 },
  { "order": 2, "refill": 2 },
  { "order": 3, "refill": { "error": "Incorrect order ID" } }
]</div>
  </div>

  <div class="card-title" style="margin-top: 28px;">Get refill status</div>
  <div class="api-block">
    <table class="api-params">
      <thead><tr><th>Parameters</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>key</code></td><td>Your API key</td></tr>
        <tr><td><code>action</code></td><td>refill_status</td></tr>
        <tr><td><code>refill</code></td><td>Refill ID</td></tr>
      </tbody>
    </table>
    <div class="api-example">{ "status": "Completed" }</div>
  </div>

  <div class="card-title" style="margin-top: 28px;">Get multiple refill status</div>
  <div class="api-block">
    <table class="api-params">
      <thead><tr><th>Parameters</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>key</code></td><td>Your API key</td></tr>
        <tr><td><code>action</code></td><td>refill_status</td></tr>
        <tr><td><code>refills</code></td><td>Refill IDs separated by comma (up to 100)</td></tr>
      </tbody>
    </table>
    <div class="api-example">[
  { "refill": 1, "status": "Completed" },
  { "refill": 2, "status": "Rejected" },
  { "refill": 3, "status": { "error": "Refill not found" } }
]</div>
  </div>

  <div class="card-title" style="margin-top: 28px;">Create cancel</div>
  <div class="api-block">
    <table class="api-params">
      <thead><tr><th>Parameters</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>key</code></td><td>Your API key</td></tr>
        <tr><td><code>action</code></td><td>cancel</td></tr>
        <tr><td><code>orders</code></td><td>Order IDs separated by comma (up to 100)</td></tr>
      </tbody>
    </table>
    <div class="api-example">[
  { "order": 9, "cancel": { "error": "Incorrect order ID" } },
  { "order": 2, "cancel": 1 }
]</div>
  </div>

  <div class="card-title" style="margin-top: 28px;">User balance</div>
  <div class="api-block">
    <table class="api-params">
      <thead><tr><th>Parameters</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>key</code></td><td>Your API key</td></tr>
        <tr><td><code>action</code></td><td>balance</td></tr>
      </tbody>
    </table>
    <div class="api-example">{ "balance": "100.84292", "currency": "USD" }</div>
  </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
