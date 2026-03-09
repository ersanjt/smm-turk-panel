<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'Terms of Service';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="card" style="max-width:800px;">
  <div class="card-title">Terms of Service</div>
  <div style="font-size:14px;line-height:1.8;color:var(--text);">
    <p>By using <?= h(defined('SITE_NAME') ? SITE_NAME : 'SMM Turk') ?> you agree to the following terms.</p>
    <h3 style="margin:20px 0 10px;font-size:15px;">1. Service</h3>
    <p>We provide social media marketing (SMM) services. Orders are processed by our provider. Start times and delivery speeds vary by service.</p>
    <h3 style="margin:20px 0 10px;font-size:15px;">2. Account & Payment</h3>
    <p>You must provide accurate information. Balance is in USD. Refunds are only given when we are at fault. Chargebacks may result in account suspension.</p>
    <h3 style="margin:20px 0 10px;font-size:15px;">3. Orders</h3>
    <p>Ensure links and profiles are public before ordering. Do not change links or account settings after placing an order. We are not responsible for orders placed on private or invalid links.</p>
    <h3 style="margin:20px 0 10px;font-size:15px;">4. Prohibited Use</h3>
    <p>You may not use our services for illegal content, spam, or to violate platform rules. We may suspend or ban accounts that abuse the service.</p>
    <h3 style="margin:20px 0 10px;font-size:15px;">5. Contact</h3>
    <p>For support, open a ticket from the dashboard or contact us via the email shown in the footer.</p>
  </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
