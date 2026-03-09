<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'Child Panel';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="card" style="max-width:560px;text-align:center;">
  <div style="font-size:48px;margin-bottom:16px;">👥</div>
  <div class="card-title">Child Panel / Reseller</div>
  <p style="color:var(--text-muted);margin-bottom:20px;">Create sub-panels under your account and resell our SMM services with your own branding and markup.</p>
  <p style="font-size:13px;color:var(--text-muted);">This feature can be enabled by admin. Contact support via <a href="/tickets.php">Tickets</a> or use our <a href="/affiliates.php">Affiliates</a> program to earn from referrals.</p>
  <a href="/index.php" class="btn btn-primary" style="margin-top:16px;">Back to New Order</a>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
