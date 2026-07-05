<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'Child Panel';
$db   = Database::getInstance();
$user = $auth->getCurrentUser();
$cpm  = new ChildPanelManager();

$price    = $cpm->monthlyPrice();
$autoMode = $cpm->autoMode();
$ns1      = trim($db->getSetting('child_panel_ns1') ?: '');
$ns2      = trim($db->getSetting('child_panel_ns2') ?: '');
$serverIp = trim($db->getSetting('child_panel_server_ip') ?: '92.205.182.143');
$parentApi = trim($db->getSetting('child_panel_parent_api_url') ?: '');
if ($parentApi === '') {
    $siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
    $parentApi = $siteUrl !== '' ? $siteUrl . '/api/v2' : '/api/v2';
}

$currencies = [
    'USD' => 'United States Dollars, USD',
    'EUR' => 'Euro, EUR',
    'GBP' => 'British Pound, GBP',
    'TRY' => 'Turkish Lira, TRY',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    if (isset($_POST['cancel_order'])) {
        $panelId = (int) ($_POST['panel_id'] ?? 0);
        $result = $cpm->cancelOrder($panelId, (int) $user['id']);
        if ($result['success']) {
            $msg = 'Order cancelled.';
            if (!empty($result['refunded'])) {
                $msg .= ' $' . number_format((float) $result['refunded'], 2) . ' refunded to your balance.';
            }
            flash('success', $msg);
        } else {
            flash('error', $result['error'] ?? 'Could not cancel order.');
        }
        redirect(url('child-panel.php'));
    }

    if (isset($_POST['retry_provision'])) {
        $panelId = (int) ($_POST['panel_id'] ?? 0);
        $cfg = $cpm->getConfigForPanel($panelId, (int) $user['id']);
        if (!$cfg) {
            flash('error', 'Panel not found.');
        } else {
            $result = $cpm->provision($panelId);
            if ($result['success']) {
                $msg = 'Your child panel setup completed!';
                if (!empty($result['admin_password_regenerated']) && !empty($result['admin_password'])) {
                    $msg .= ' New admin password: ' . $result['admin_password'];
                }
                flash('success', $msg);
            } else {
                flash('error', $result['error'] ?? 'Setup failed. Try again or contact support.');
            }
        }
        redirect(url('child-panel.php'));
    }

    if (isset($_POST['check_dns'])) {
        $panelId = (int) ($_POST['panel_id'] ?? 0);
        $cfg = $cpm->getConfigForPanel($panelId, (int) $user['id']);
        if (!$cfg) {
            flash('error', 'Panel not found.');
        } else {
            $domain = ChildPanelManager::normalizeDomain((string) ($cfg['panel']['domain'] ?? ''));
            $dns = $cpm->checkDomainReady($domain);
            if ($dns['ready']) {
                $result = $cpm->provision($panelId);
                if ($result['success']) {
                    flash('success', 'DNS verified — your child panel is now live!');
                } else {
                    flash('error', $result['error'] ?? 'Provisioning failed. Contact support.');
                }
            } else {
                $diag = $cpm->getDomainDnsDiagnostics($domain);
                flash('error', $diag['hint']);
            }
        }
        redirect(url('child-panel.php'));
    }

    if (isset($_POST['submit_child'])) {
        $domain    = trim($_POST['domain'] ?? '');
        $currency  = trim($_POST['currency'] ?? 'USD');
        $adminUser = trim($_POST['admin_username'] ?? '');
        $adminPass = $_POST['admin_password'] ?? '';
        $adminPass2 = $_POST['admin_password_confirm'] ?? '';
        $adminEmail = trim($_POST['admin_email'] ?? '');

        $err = [];
        if (!in_array($currency, array_keys($currencies), true)) {
            $currency = 'USD';
        }
        if (strlen($adminUser) < 3) {
            $err[] = 'Admin username must be at least 3 characters.';
        }
        if (strlen($adminPass) < 6) {
            $err[] = 'Admin password must be at least 6 characters.';
        }
        if ($adminPass !== $adminPass2) {
            $err[] = 'Passwords do not match.';
        }

        if (!empty($err)) {
            flash('error', implode(' ', $err));
            redirect(page_url('child-panel.php', ['domain' => ChildPanelManager::normalizeDomain($domain)]));
        }

        $result = $cpm->placeOrder(
            (int) $user['id'],
            $domain,
            $currency,
            $adminUser,
            $adminPass,
            $adminEmail !== '' ? $adminEmail : null
        );

        if (!$result['success']) {
            flash('error', $result['error'] ?? 'Order failed.');
            redirect(page_url('child-panel.php', ['domain' => ChildPanelManager::normalizeDomain($domain)]));
        }

        if (!empty($result['instant'])) {
            flash('success', 'Payment received — your child panel is live! Connection details are below.');
        } elseif ($autoMode === 'dns') {
            flash('success', 'Order paid. Set your domain nameservers, then click “Check DNS” on your order.');
        } elseif ($autoMode === 'manual') {
            flash('success', 'Order submitted. Our team will activate your panel after reviewing your domain.');
        } else {
            flash('success', 'Order paid. Your panel is being set up — refresh in a moment.');
        }
        redirect(url('child-panel.php'));
    }
}

$myPanels = [];
try {
    $myPanels = $db->fetchAll(
        "SELECT * FROM child_panels WHERE user_id = ? ORDER BY created_at DESC",
        [$user['id']]
    );
} catch (Throwable $e) {
    /* table may be missing */
}

$balance = (float) ($user['balance'] ?? 0);
$canOrder = $balance >= $price;
$prefillDomain = ChildPanelManager::normalizeDomain((string) ($_POST['domain'] ?? $_GET['domain'] ?? ''));

require_once __DIR__ . '/layouts/header.php';
?>

<style>
.cp-hero { display:flex; flex-wrap:wrap; gap:16px; margin-bottom:22px; }
.cp-stat { flex:1; min-width:140px; background:var(--white); border:1px solid var(--border); border-radius:12px; padding:14px 18px; }
.cp-stat-label { font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.cp-stat-value { font-size:20px; font-weight:700; color:var(--text); }
.cp-stat-value.ok { color:#16a34a; }
.cp-stat-value.warn { color:#d97706; }
.cp-intro { font-size:14px; line-height:1.7; color:var(--text); margin-bottom:20px; }
.cp-auto-badge { display:inline-block; font-size:11px; font-weight:600; padding:4px 10px; border-radius:20px; background:rgba(22,163,74,.12); color:#16a34a; margin-left:8px; vertical-align:middle; }
body.theme-dark .cp-auto-badge { background:rgba(34,197,94,.15); color:#4ade80; }
.cp-nsbox { background:rgba(245,158,11,.12); border:1px solid rgba(245,158,11,.35); color:#b45309; padding:14px 18px; border-radius:10px; margin-bottom:24px; font-size:13px; }
.cp-nsbox strong { display:block; margin-bottom:6px; }
body.theme-dark .cp-nsbox { background:rgba(245,158,11,.14); border-color:rgba(251,191,36,.35); color:#fcd34d; }
.cp-steps { display:flex; gap:8px; flex-wrap:wrap; margin:10px 0 4px; }
.cp-step { font-size:11px; padding:4px 10px; border-radius:20px; background:var(--bg); color:var(--text-muted); border:1px solid var(--border); }
.cp-step.done { background:rgba(22,163,74,.12); color:#16a34a; border-color:rgba(22,163,74,.3); }
.cp-step.current { background:rgba(227,10,23,.1); color:var(--primary); border-color:rgba(227,10,23,.3); font-weight:600; }
body.theme-dark .cp-step.done { background:rgba(34,197,94,.15); color:#4ade80; }
.cp-connect { margin-top:12px; padding:14px; background:var(--bg); border:1px solid var(--border); border-radius:10px; font-size:12px; }
.cp-connect dt { color:var(--text-muted); margin-top:8px; }
.cp-connect dt:first-child { margin-top:0; }
.cp-connect dd { margin:4px 0 0; font-family:ui-monospace,monospace; word-break:break-all; color:var(--text); }
.cp-connect a { color:var(--primary); font-weight:600; }
.cp-status-hint { font-size:11px; color:var(--text-muted); display:block; margin-top:4px; line-height:1.4; }
.cp-faq { border:1px solid var(--border); border-radius:14px; overflow:hidden; background:var(--bg); }
.cp-faq-item { border-bottom:1px solid var(--border); }
.cp-faq-item:last-child { border-bottom:0; }
.cp-faq-q { padding:14px 18px; cursor:pointer; font-weight:600; font-size:14px; display:flex; justify-content:space-between; align-items:center; gap:12px; background:transparent; color:var(--text); transition:background .15s,color .15s; }
.cp-faq-q:hover { background:rgba(227,10,23,.06); }
.cp-faq-item.open .cp-faq-q { background:rgba(227,10,23,.1); color:var(--primary); }
body.theme-dark .cp-faq-q:hover { background:rgba(227,10,23,.12); }
body.theme-dark .cp-faq-item.open .cp-faq-q { background:rgba(227,10,23,.18); color:var(--primary-light); }
.cp-faq-q span { font-size:18px; color:var(--primary); flex-shrink:0; transition:transform .2s; }
.cp-faq-item.open .cp-faq-q span { transform:rotate(45deg); }
.cp-faq-a { padding:0 18px; font-size:13px; color:var(--text-muted); line-height:1.6; max-height:0; overflow:hidden; transition:max-height .3s; }
.cp-faq-item.open .cp-faq-a { padding:14px 18px; max-height:400px; }
.cp-faq-a a { color:var(--primary); font-weight:600; text-decoration:none; }
.cp-faq-a a:hover { text-decoration:underline; }
body.theme-dark .cp-faq-a a { color:var(--primary-light); }
.cp-panel-card { border:1px solid var(--border); border-radius:12px; padding:16px; margin-bottom:14px; background:var(--white); }
body.theme-dark .cp-panel-card,
body.panel-follows.theme-dark .cp-panel-card { background:#1a1416; }
.cp-panel-card.cp-panel-failed { border-color:rgba(227,10,23,.35); background:rgba(227,10,23,.04); }
body.theme-dark .cp-panel-card.cp-panel-failed,
body.panel-follows.theme-dark .cp-panel-card.cp-panel-failed { background:rgba(227,10,23,.1); }
.cp-provision-error { display:block; margin-top:8px; font-size:11px; color:var(--primary); line-height:1.45; word-break:break-word; }
body.theme-dark .cp-btn-cancel,
body.panel-follows.theme-dark .cp-btn-cancel { color:#f0e9eb; border-color:rgba(255,255,255,.22); }
body.theme-dark .cp-btn-cancel:hover,
body.panel-follows.theme-dark .cp-btn-cancel:hover { color:#ff8a96; border-color:var(--primary); }
.cp-panel-card h4 { margin:0 0 8px; font-size:15px; }
.cp-dns-status { margin:10px 0 0; padding:12px 14px; border-radius:10px; border:1px solid var(--border); background:var(--bg); font-size:12px; line-height:1.55; color:var(--text-muted); }
.cp-dns-status strong { color:var(--text); }
.cp-dns-status code { font-size:11px; word-break:break-all; }
.cp-dns-status.ok { border-color:rgba(22,163,74,.35); background:rgba(22,163,74,.08); color:#15803d; }
body.theme-dark .cp-dns-status.ok { color:#86efac; background:rgba(34,197,94,.1); }
.cp-panel-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; align-items:center; }
.cp-btn-cancel { font-size:12px; padding:8px 14px; background:transparent; color:var(--text-muted); border:1px solid var(--border); border-radius:8px; cursor:pointer; }
.cp-btn-cancel:hover { border-color:var(--primary); color:var(--primary); }
</style>

<div class="cp-hero">
  <div class="cp-stat">
    <div class="cp-stat-label">Your balance</div>
    <div class="cp-stat-value <?= $canOrder ? 'ok' : 'warn' ?>">$<?= number_format($balance, 2) ?></div>
  </div>
  <div class="cp-stat">
    <div class="cp-stat-label">Monthly price</div>
    <div class="cp-stat-value">$<?= number_format($price, 2) ?></div>
  </div>
  <div class="cp-stat">
    <div class="cp-stat-label">Activation</div>
    <div class="cp-stat-value" style="font-size:14px;">
      <?= match ($autoMode) {
          'instant' => 'Automatic',
          'dns' => 'After DNS',
          'whm' => 'WHM auto',
          default => 'Manual review',
      } ?>
    </div>
  </div>
</div>

<div class="grid2" style="align-items:start;gap:28px;">
  <div>
    <p class="cp-intro">
      Launch your own white-label SMM website for <strong>$<?= number_format($price, 0) ?>/month</strong> (deducted from balance).
      After payment, your panel is <?= $autoMode === 'instant' || $autoMode === 'whm' ? '<span class="cp-auto-badge">activated automatically</span>' : ($autoMode === 'dns' ? 'activated once nameservers are verified' : 'reviewed by our team') ?> and connected to SMM Turk — orders flow to us automatically.
    </p>

    <?php if (!$canOrder): ?>
    <div class="cp-nsbox" style="background:rgba(227,10,23,.08);border-color:rgba(227,10,23,.25);color:var(--primary);">
      <strong>Insufficient balance</strong>
      Add at least $<?= number_format($price, 2) ?> via <a href="<?= h(path('add-funds.php')) ?>" style="color:inherit;font-weight:700;">Add Funds</a> before ordering.
    </div>
    <?php endif; ?>

    <?php if ($ns1 !== '' || $ns2 !== '' || $serverIp !== ''): ?>
    <div class="cp-nsbox">
      <strong>Connect your domain (choose one):</strong>
      <?php if ($ns1 !== '' || $ns2 !== ''): ?>
      <br><strong>Option A — Nameservers</strong> (at your domain registrar):<br>
      <?php if ($ns1 !== '') echo '- ' . h($ns1) . '<br>'; ?>
      <?php if ($ns2 !== '') echo '- ' . h($ns2) . '<br>'; ?>
      <?php endif; ?>
      <?php if ($serverIp !== ''): ?>
      <br><strong>Option B — Cloudflare / A record</strong>:<br>
      - <code>@</code> and <code>www</code> → A → <?= h($serverIp) ?> (DNS only)
      <?php endif; ?>
      <br><small style="opacity:.9;">After DNS propagates, click <strong>Check DNS &amp; activate</strong> on your order — panel deploys automatically.</small>
    </div>
    <?php endif; ?>

    <?php if (!empty($myPanels)): ?>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-title">Your child panels</div>
      <?php foreach ($myPanels as $p):
          $st = $p['status'] ?? 'pending';
          $ps = $p['provision_status'] ?? 'pending';
          $steps = ChildPanelManager::provisionSteps($p);
          $badge = $st === 'active' ? 'badge-green' : ($st === 'cancelled' ? 'badge-red' : 'badge-orange');
          $isActive = $st === 'active' && $ps === ChildPanelManager::PROVISION_READY;
          $hint = match (true) {
              $isActive => 'Live — use connection details below',
              $ps === ChildPanelManager::PROVISION_DNS_WAIT => 'Set nameservers, then click Check DNS',
              $ps === ChildPanelManager::PROVISION_PROVISIONING, $ps === ChildPanelManager::PROVISION_QUEUED => 'Setting up your panel…',
              $ps === ChildPanelManager::PROVISION_FAILED => 'Setup failed — contact support or retry',
              $st === 'pending' => 'Waiting for activation',
              $st === 'suspended' => 'Suspended — contact support',
              $st === 'cancelled' => 'Order cancelled',
              default => '',
          };
      ?>
      <div class="cp-panel-card<?= $ps === ChildPanelManager::PROVISION_FAILED ? ' cp-panel-failed' : '' ?>">
        <h4><?= h($p['domain']) ?> <span class="badge <?= $badge ?>" style="font-size:10px;vertical-align:middle;"><?= h($st) ?></span></h4>
        <div class="cp-steps">
          <?php
          $allSteps = ['paid' => 'Paid', 'dns_wait' => 'DNS', 'dns_ok' => 'DNS OK', 'deployed' => 'Deployed', 'provisioning' => 'Deploying', 'active' => 'Live', 'failed' => 'Failed', 'pending' => 'Pending'];
          $last = end($steps) ?: 'paid';
          foreach ($steps as $i => $stepKey):
              $cls = ($stepKey === $last && $last !== 'failed') ? 'current' : 'done';
              if ($stepKey === 'failed') $cls = 'current';
          ?>
          <span class="cp-step <?= $cls ?>"><?= h($allSteps[$stepKey] ?? $stepKey) ?></span>
          <?php endforeach; ?>
        </div>
        <?php if ($hint !== ''): ?><span class="cp-status-hint"><?= h($hint) ?></span><?php endif; ?>
        <?php if ($ps === ChildPanelManager::PROVISION_FAILED && !empty($p['provision_error'])): ?>
        <span class="cp-provision-error"><?= h((string) $p['provision_error']) ?></span>
        <?php endif; ?>

        <?php if ($ps === ChildPanelManager::PROVISION_DNS_WAIT && $st !== 'cancelled'):
            $dnsDiag = $cpm->getDomainDnsDiagnostics((string) $p['domain']);
        ?>
        <div class="cp-dns-status <?= $dnsDiag['ready'] ? 'ok' : '' ?>">
          <strong>DNS check</strong><br>
          <?php if ($dnsDiag['ns'] !== []): ?>Found NS: <code><?= h(implode(', ', $dnsDiag['ns'])) ?></code><br><?php endif; ?>
          <?php if ($dnsDiag['a'] !== []): ?>Found A: <code><?= h(implode(', ', $dnsDiag['a'])) ?></code><br><?php endif; ?>
          <?php if ($dnsDiag['resolved_ip'] !== ''): ?>Resolves to: <code><?= h($dnsDiag['resolved_ip']) ?></code><br><?php endif; ?>
          Expected: NS <code><?= h(implode(' + ', $dnsDiag['expected_ns'])) ?></code> or A → <code><?= h($dnsDiag['expected_ip']) ?></code><br>
          <?= h($dnsDiag['hint']) ?>
        </div>
        <?php endif; ?>

        <?php if ($isActive):
            $panelUrl = rtrim((string) ($p['panel_url'] ?: 'https://' . $p['domain']), '/');
            $adminPass = $cpm->getStoredAdminPassword($p);
        ?>
        <dl class="cp-connect">
          <dt>Panel URL</dt>
          <dd><a href="<?= h($panelUrl) ?>" target="_blank" rel="noopener"><?= h($panelUrl) ?></a></dd>
          <dt>Login (admin)</dt>
          <dd><a href="<?= h($panelUrl . '/login') ?>" target="_blank" rel="noopener"><?= h($panelUrl . '/login') ?></a></dd>
          <dt>Admin dashboard</dt>
          <dd><a href="<?= h($panelUrl . '/admin') ?>" target="_blank" rel="noopener"><?= h($panelUrl . '/admin') ?></a></dd>
          <dt>Admin username</dt>
          <dd><code><?= h($p['admin_username']) ?></code></dd>
          <dt>Admin password</dt>
          <dd><code id="cp-pass-<?= (int) $p['id'] ?>"><?= $adminPass !== '' ? h($adminPass) : '— (contact support)' ?></code>
            <?php if ($adminPass !== ''): ?>
            <button type="button" class="btn" style="font-size:10px;padding:4px 8px;margin-left:6px;" onclick="navigator.clipboard.writeText(document.getElementById('cp-pass-<?= (int) $p['id'] ?>').textContent)">Copy</button>
            <?php endif; ?>
          </dd>
          <dt>Parent API URL</dt>
          <dd><code><?= h($parentApi) ?></code></dd>
          <dt>API key</dt>
          <dd><code style="word-break:break-all;"><?= h($p['panel_api_key'] ?? '') ?></code></dd>
        </dl>
        <div class="cp-dns-status ok" style="margin-top:10px;">
          <strong>Customize your panel</strong><br>
          1. Open <strong>Login</strong> above with admin username &amp; password.<br>
          2. After login, open <strong>Admin Panel</strong> in the sidebar (or Admin dashboard link).<br>
          3. Go to <strong>Admin → Settings</strong>: change <em>Site Name</em>, <em>Logo path</em>, <em>Favicon path</em>.<br>
          4. Upload your logo via cPanel File Manager to e.g. <code>assets/img/my-logo.png</code> and set that path in Settings.
        </div>
        <?php endif; ?>

        <?php if ($st === 'cancelled'): ?>
        <div class="cp-panel-actions">
          <a href="<?= h(page_url('child-panel.php', ['domain' => $p['domain']])) ?>#order-form" class="btn btn-primary" style="font-size:12px;padding:8px 14px;">Order again</a>
        </div>
        <?php endif; ?>
        <?php
        $showPanelActions = ($ps === ChildPanelManager::PROVISION_DNS_WAIT || $ps === ChildPanelManager::PROVISION_FAILED || $cpm->canCancel($p)) && $st !== 'cancelled';
        if ($showPanelActions):
        ?>
        <div class="cp-panel-actions">
        <?php if ($ps === ChildPanelManager::PROVISION_DNS_WAIT): ?>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="panel_id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="check_dns" value="1">
          <button type="submit" class="btn btn-primary" style="font-size:12px;padding:8px 14px;">Check DNS &amp; deploy panel</button>
        </form>
        <?php elseif ($ps === ChildPanelManager::PROVISION_FAILED): ?>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="panel_id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="retry_provision" value="1">
          <button type="submit" class="btn btn-primary" style="font-size:12px;padding:8px 14px;">Retry setup</button>
        </form>
        <?php endif; ?>
        <?php if ($cpm->canCancel($p)): ?>
        <form method="POST" onsubmit="return confirm('Cancel this order?<?= $cpm->shouldRefundOnCancel($p) ? ' Your payment will be refunded to your balance.' : '' ?>');">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="panel_id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="cancel_order" value="1">
          <button type="submit" class="cp-btn-cancel">Cancel order</button>
        </form>
        <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($isActive && !empty($p['document_root'])): ?>
        <span class="cp-status-hint">Deployed: <?= h($p['document_root']) ?></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card" id="order-form">
      <div class="card-title">Order child panel</div>
      <form method="POST" <?= $canOrder ? '' : 'onsubmit="return false;"' ?>>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="submit_child" value="1">

        <div class="form-group">
          <label class="form-label">Domain name</label>
          <input type="text" name="domain" class="form-control" placeholder="yourpanel.com" value="<?= h($prefillDomain !== '' ? $prefillDomain : ($_POST['domain'] ?? '')) ?>" required <?= $canOrder ? '' : 'disabled' ?>>
        </div>
        <div class="grid2">
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="currency" class="form-control" <?= $canOrder ? '' : 'disabled' ?>>
              <?php foreach ($currencies as $code => $label): ?>
              <option value="<?= h($code) ?>" <?= ($_POST['currency'] ?? 'USD') === $code ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Admin email</label>
            <input type="email" name="admin_email" class="form-control" value="<?= h($_POST['admin_email'] ?? $user['email'] ?? '') ?>" <?= $canOrder ? '' : 'disabled' ?>>
          </div>
        </div>
        <div class="grid2">
          <div class="form-group">
            <label class="form-label">Admin username</label>
            <input type="text" name="admin_username" class="form-control" value="<?= h($_POST['admin_username'] ?? '') ?>" minlength="3" required <?= $canOrder ? '' : 'disabled' ?>>
          </div>
          <div class="form-group">
            <label class="form-label">Admin password</label>
            <input type="password" name="admin_password" class="form-control" minlength="6" required <?= $canOrder ? '' : 'disabled' ?>>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm password</label>
          <input type="password" name="admin_password_confirm" class="form-control" minlength="6" required <?= $canOrder ? '' : 'disabled' ?>>
        </div>
        <div class="form-group">
          <label class="form-label">Price (charged now)</label>
          <input type="text" class="form-control" value="$<?= number_format($price, 2) ?> — first month" readonly style="background:var(--bg);">
        </div>
        <button type="submit" class="btn btn-primary btn-block" <?= $canOrder ? '' : 'disabled' ?>>
          <?= $canOrder ? 'Pay &amp; create panel' : 'Add funds to continue' ?>
        </button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-title">FAQ</div>
    <div class="cp-faq">
      <div class="cp-faq-item"><div class="cp-faq-q">What is a child panel? <span>+</span></div><div class="cp-faq-a">Your own branded SMM website. You set prices; we fulfill orders through our API. No separate hosting bill from us.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">How fast is activation? <span>+</span></div><div class="cp-faq-a"><?= $autoMode === 'instant' || $autoMode === 'whm' ? 'Usually within seconds after payment. You get panel URL and API connection details on this page and by email.' : ($autoMode === 'dns' ? 'After you point nameservers to ours, click Check DNS — activation is automatic.' : 'Our team activates within 24–48 hours after reviewing your domain.') ?></div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">How do I connect to SMM Turk? <span>+</span></div><div class="cp-faq-a">Use the <strong>Parent API URL</strong> and <strong>API key</strong> shown on your active panel card. Standard SMM panel API format — same as ordering from this site.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">Do I need hosting? <span>+</span></div><div class="cp-faq-a">You need a domain. We host the panel infrastructure; point nameservers to <?= $ns1 !== '' ? h($ns1) : 'our NS' ?><?= $ns2 !== '' ? ' and ' . h($ns2) : '' ?>.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">Payment on this panel? <span>+</span></div><div class="cp-faq-a">Child panel fee is deducted from your <strong>balance</strong> (crypto via Add Funds). Your customers pay you through gateways you configure on your child panel.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">Refund policy? <span>+</span></div><div class="cp-faq-a">If your panel was not deployed yet, use <strong>Cancel order</strong> on your panel card — payment returns to your balance automatically. For live panels, contact <a href="<?= h(path('tickets.php')) ?>">support</a>.</div></div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.cp-faq-q').forEach(function(el) {
  el.addEventListener('click', function() {
    var item = this.closest('.cp-faq-item');
    var isOpen = item.classList.contains('open');
    document.querySelectorAll('.cp-faq-item').forEach(function(i) { i.classList.remove('open'); });
    if (!isOpen) item.classList.add('open');
  });
});
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
