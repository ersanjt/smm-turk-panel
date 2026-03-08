<?php
require_once __DIR__ . '/includes/init.php';
$auth->requireLogin();
$pageTitle = 'Affiliates';
$db   = Database::getInstance();
$user = $auth->getCurrentUser();

$refLink    = SITE_URL . '/login.php?mode=register&ref=' . ($user['referral_code'] ?? '');
$commission = $db->getSetting('referral_commission') ?? 2;
$minPayout  = $db->getSetting('referral_min_payout') ?? 10;

$stats = $db->fetch(
    "SELECT COUNT(*) as referrals, SUM(referral_earnings) as earnings FROM users WHERE referred_by = ?",
    [$user['id']]
) ?? ['referrals' => 0, 'earnings' => 0];

require_once __DIR__ . '/includes/header.php';
?>

<div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:16px;padding:24px;color:#fff;margin-bottom:20px;position:relative;overflow:hidden;">
  <div style="position:absolute;top:-40px;right:-40px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.06);"></div>
  <div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;margin-bottom:6px;">💰 Refer & Earn</div>
  <div style="font-size:13px;opacity:.85;margin-bottom:16px;">Earn <?= $commission ?>% commission on every purchase. Minimum payout: $<?= $minPayout ?></div>
  <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:600;">
    <span id="ref-link"><?= h($refLink) ?></span>
    <button onclick="navigator.clipboard.writeText('<?= h($refLink) ?>');this.textContent='Copied!'" style="background:#fff;color:var(--primary);border:none;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;">Copy</button>
  </div>
</div>

<div class="alert alert-warning">⚠️ We pay only for new customers. Re-registration and abuse will result in account termination.</div>

<div class="grid4" style="margin-bottom:20px;">
  <div class="stat-card"><div class="sc-icon" style="background:#e8e8ff;">👥</div><div class="sc-label">Total Referrals</div><div class="sc-value"><?= $stats['referrals'] ?></div></div>
  <div class="stat-card"><div class="sc-icon" style="background:#e8ffe8;">💵</div><div class="sc-label">Total Earnings</div><div class="sc-value">$<?= number_format($stats['earnings'] ?? 0, 2) ?></div></div>
  <div class="stat-card"><div class="sc-icon" style="background:#fff3e0;">📊</div><div class="sc-label">Commission</div><div class="sc-value"><?= $commission ?>%</div></div>
  <div class="stat-card"><div class="sc-icon" style="background:#f0e8ff;">💰</div><div class="sc-label">Min Payout</div><div class="sc-value">$<?= $minPayout ?></div></div>
</div>

<div class="card">
  <table class="table">
    <thead><tr><th>Referral Link</th><th>Commission Rate</th><th>Min Payout</th><th>Unpaid Earnings</th></tr></thead>
    <tbody>
      <tr>
        <td><?= h($refLink) ?></td>
        <td><span class="badge badge-green"><?= $commission ?>%</span></td>
        <td><strong>$<?= $minPayout ?></strong></td>
        <td><strong>$<?= number_format($user['referral_earnings'] ?? 0, 2) ?></strong></td>
      </tr>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
