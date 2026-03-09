<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'Child Panel';
$db   = Database::getInstance();
$user = $auth->getCurrentUser();

$price   = (float)($db->getSetting('child_panel_price') ?: 5);
$ns1     = trim($db->getSetting('child_panel_ns1') ?: '');
$ns2     = trim($db->getSetting('child_panel_ns2') ?: '');

$currencies = [
    'USD' => 'United States Dollars, USD',
    'EUR' => 'Euro, EUR',
    'GBP' => 'British Pound, GBP',
    'TRY' => 'Turkish Lira, TRY',
];

// Submit order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_child']) && csrf_verify()) {
    $domain   = trim($_POST['domain'] ?? '');
    $currency = trim($_POST['currency'] ?? 'USD');
    $adminUser = trim($_POST['admin_username'] ?? '');
    $adminPass = $_POST['admin_password'] ?? '';
    $adminPass2 = $_POST['admin_password_confirm'] ?? '';

    $err = [];
    if ($domain === '') $err[] = 'Domain name is required.';
    if (strlen($domain) > 255) $err[] = 'Domain too long.';
    if (!in_array($currency, array_keys($currencies))) $currency = 'USD';
    if (strlen($adminUser) < 3) $err[] = 'Admin username must be at least 3 characters.';
    if (strlen($adminPass) < 6) $err[] = 'Admin password must be at least 6 characters.';
    if ($adminPass !== $adminPass2) $err[] = 'Passwords do not match.';

    if (empty($err)) {
        $balance = (float) $user['balance'];
        if ($balance < $price) {
            flash('error', 'Insufficient balance. You need $' . number_format($price, 2) . '. Add funds first.');
        } else {
            $db->execute("UPDATE users SET balance = balance - ?, spent = spent + ? WHERE id = ?", [$price, $price, $user['id']]);
            $hashed = password_hash($adminPass, PASSWORD_DEFAULT);
            try {
                $db->insert(
                    "INSERT INTO child_panels (user_id, domain, currency, admin_username, admin_password, price, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')",
                    [$user['id'], $domain, $currency, $adminUser, $hashed, $price]
                );
                flash('success', 'Child panel order submitted. We will activate it soon. Please set your domain nameservers as indicated below.');
            } catch (Throwable $e) {
                $db->execute("UPDATE users SET balance = balance + ?, spent = spent - ? WHERE id = ?", [$price, $price, $user['id']]);
                flash('error', 'Order failed. Please try again or contact support.');
            }
            redirect('/child-panel.php');
        }
    } else {
        flash('error', implode(' ', $err));
        redirect('/child-panel.php');
    }
}

$myPanels = [];
try {
    $myPanels = $db->fetchAll("SELECT id, domain, currency, status, price, created_at FROM child_panels WHERE user_id = ? ORDER BY created_at DESC", [$user['id']]);
} catch (Throwable $e) { }

require_once __DIR__ . '/layouts/header.php';
?>

<style>
.cp-intro { font-size: 14px; line-height: 1.7; color: var(--text); margin-bottom: 20px; }
.cp-nsbox { background: #fff3e0; border: 1px solid #fcd34d; color: #b45309; padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 13px; }
.cp-nsbox strong { display: block; margin-bottom: 6px; }
.cp-faq { border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
.cp-faq-item { border-bottom: 1px solid var(--border); }
.cp-faq-item:last-child { border-bottom: 0; }
.cp-faq-q { padding: 14px 18px; cursor: pointer; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; background: #fff; transition: background .15s; }
.cp-faq-q:hover { background: var(--bg); }
.cp-faq-q span { font-size: 18px; color: var(--primary); transition: transform .2s; }
.cp-faq-item.open .cp-faq-q span { transform: rotate(45deg); }
.cp-faq-a { padding: 0 18px; font-size: 13px; color: var(--text-muted); line-height: 1.6; max-height: 0; overflow: hidden; transition: max-height .3s; }
.cp-faq-item.open .cp-faq-a { padding: 14px 18px; max-height: 400px; }
</style>

<div class="grid2" style="align-items: start; gap: 28px;">
  <div>
    <p class="cp-intro">
      You can now buy a child panel for $<?= number_format($price, 0) ?> per month (Unlimited Order). (Fund will be deducted from your balance). Child panel is your own website to sell SMM services. You will simply connect it to us, and we will deliver directly to your clients.
    </p>

    <?php if ($ns1 !== '' || $ns2 !== ''): ?>
    <div class="cp-nsbox">
      <strong>Please visit your domain’s nameserver settings and change nameservers to:</strong>
      <?php if ($ns1 !== '') echo '- ' . h($ns1) . '<br>'; ?>
      <?php if ($ns2 !== '') echo '- ' . h($ns2); ?>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-title">Order child panel</div>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="submit_child" value="1">

        <div class="form-group">
          <label class="form-label">Domain name</label>
          <input type="text" name="domain" class="form-control" placeholder="yourpanel.com" value="<?= h($_POST['domain'] ?? '') ?>" required>
        </div>
        <div class="grid2">
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="currency" class="form-control">
              <?php foreach ($currencies as $code => $label): ?>
              <option value="<?= h($code) ?>" <?= ($_POST['currency'] ?? 'USD') === $code ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Admin username</label>
            <input type="text" name="admin_username" class="form-control" value="<?= h($_POST['admin_username'] ?? '') ?>" minlength="3" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Admin password</label>
          <input type="password" name="admin_password" class="form-control" minlength="6" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm password</label>
          <input type="password" name="admin_password_confirm" class="form-control" minlength="6" required>
        </div>
        <div class="form-group">
          <label class="form-label">Price per month</label>
          <input type="text" class="form-control" value="$<?= number_format($price, 2) ?>" readonly style="background:var(--bg);">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Submit order</button>
      </form>
    </div>

    <?php if (!empty($myPanels)): ?>
    <div class="card" style="margin-top:20px;">
      <div class="card-title">Your child panel orders</div>
      <table class="table">
        <thead><tr><th>Domain</th><th>Currency</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($myPanels as $p): ?>
        <tr>
          <td><?= h($p['domain']) ?></td>
          <td><?= h($p['currency']) ?></td>
          <td><span class="badge <?= $p['status'] === 'active' ? 'badge-green' : 'badge-orange' ?>"><?= h($p['status']) ?></span></td>
          <td style="font-size:12px;color:var(--text-muted);"><?= h(date('Y-m-d H:i', strtotime($p['created_at'] ?? ''))) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title">FAQ</div>
    <div class="cp-faq">
      <div class="cp-faq-item"><div class="cp-faq-q">What is child panel? <span>+</span></div><div class="cp-faq-a">A child panel is your own white-label SMM website. You sell services to your clients; we deliver the orders. You set your prices and keep the margin.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">How much does a child panel cost? <span>+</span></div><div class="cp-faq-a">The price is $<?= number_format($price, 0) ?> per month, deducted from your balance. Unlimited orders are included.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">How long until my child panel is activated? <span>+</span></div><div class="cp-faq-a">After you submit the order and set the nameservers for your domain, we usually activate it within 24–48 hours. You will receive instructions by email or ticket.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">Is hosting required for my child panel? <span>+</span></div><div class="cp-faq-a">No. We host the panel for you. You only need a domain and to point its nameservers to ours.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">I already have a domain. What do I do? <span>+</span></div><div class="cp-faq-a">Enter your domain in the order form. Then in your domain registrar’s control panel, set the nameservers to the ones shown above.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">How do I change nameservers for my domain? <span>+</span></div><div class="cp-faq-a">Log in to where you bought the domain (GoDaddy, Namecheap, Cloudflare, etc.), find DNS or Nameservers settings, and replace the current nameservers with the ones we provide.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">How to connect my child panel with WordPress? <span>+</span></div><div class="cp-faq-a">You can embed the panel in an iframe on your WordPress site or link to it. For full integration, use our API from a custom WordPress plugin.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">Can I get a refund after purchasing a child panel? <span>+</span></div><div class="cp-faq-a">Refund policy depends on usage. Contact us via <a href="/tickets.php">support ticket</a> for any refund request.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">How can I change my child panel domain? <span>+</span></div><div class="cp-faq-a">Open a <a href="/tickets.php">ticket</a> with your new domain. We will guide you through the change.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">Is the affiliate feature available on child panel? <span>+</span></div><div class="cp-faq-a">Child panels can have their own referral/affiliate settings. Contact support to enable this for your panel.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">How can I set up payment methods in my child account? <span>+</span></div><div class="cp-faq-a">After activation, you can add payment options in your child panel’s admin area or request integration via support.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">How do I collect money from my customers? <span>+</span></div><div class="cp-faq-a">Your child panel can use payment gateways (e.g. Stripe, PayPal) that you configure. Funds go to your account; you use balance here to pay for orders.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">After integrating with WordPress, can customers discover the main panel? <span>+</span></div><div class="cp-faq-a">If you only link or embed the panel with your domain, customers see your brand. They do not see the main panel unless you expose its name or link.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">Will changing the currency give me access to all payment gateways? <span>+</span></div><div class="cp-faq-a">Available gateways depend on the currency and your country. Support can help you enable the right options for your panel.</div></div>
      <div class="cp-faq-item"><div class="cp-faq-q">Can I use another admin email for my child panel? <span>+</span></div><div class="cp-faq-a">Yes. After activation you can set a different admin email in your child panel’s settings, or request it via ticket when ordering.</div></div>
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
