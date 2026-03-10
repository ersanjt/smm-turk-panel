<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'Affiliates';
$db   = Database::getInstance();
$user = $auth->getCurrentUser();

$baseUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$refCode = $user['referral_code'] ?? '';
$refLink = $refCode ? ($baseUrl ?: '') . path('c/' . $refCode) : ($baseUrl ?: '') . path('login.php') . '?mode=register';
$commission = (float)($db->getSetting('referral_commission') ?: 2);
$minPayout  = (float)($db->getSetting('referral_min_payout') ?: 10);

$referralsCount = (int) $db->fetch("SELECT COUNT(*) c FROM users WHERE referred_by = ?", [$user['id']])['c'];
$totalEarned = (float) $db->fetch("SELECT COALESCE(SUM(referral_earnings), 0) e FROM users WHERE referred_by = ?", [$user['id']])['e'];
$unpaid = (float)($user['referral_earnings'] ?? 0);
$totalLifetime = (float)($user['total_referral_earnings'] ?? $unpaid);

$visits = 0;
try {
    $visits = (int) $db->fetch("SELECT COUNT(*) c FROM referral_visits WHERE referrer_id = ?", [$user['id']])['c'];
} catch (Throwable $e) { }
$registrations = $referralsCount;
$conversion = $visits > 0 ? round(($registrations / $visits) * 100, 2) : 0.00;

require_once __DIR__ . '/layouts/header.php';
?>

<style>
.aff-desc { background: #fff; border-radius: 14px; padding: 20px 22px; margin-bottom: 16px; border: 1px solid var(--border); font-size: 14px; line-height: 1.6; color: var(--text); }
.aff-warn { background: #fff3e0; color: #b45309; padding: 12px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 13px; border: 1px solid #fcd34d; }
.aff-card { background: #fff; border-radius: 14px; padding: 22px; margin-bottom: 20px; border: 1px solid var(--border); }
.aff-card h3 { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; margin-bottom: 16px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; }
.aff-row { display: flex; flex-wrap: wrap; gap: 24px; margin-bottom: 12px; }
.aff-row:last-child { margin-bottom: 0; }
.aff-label { font-size: 12px; color: var(--text-muted); margin-bottom: 4px; }
.aff-value { font-size: 16px; font-weight: 700; color: var(--text); }
.aff-link-wrap { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.aff-link-wrap input { flex: 1; min-width: 200px; padding: 10px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; }
.aff-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px; }
.aff-stat { text-align: center; padding: 16px; background: var(--bg); border-radius: 12px; }
.aff-stat .label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); margin-bottom: 6px; }
.aff-stat .value { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; color: var(--text); }
</style>

<div class="aff-desc">
  Want to earn money? Refer your friends and join our Affiliate system to get your commission. Refer your friends and let's make money!
</div>
<div class="aff-warn">
  We pay only for new customers. Any re-registration and abuse will be rejected and your referral account will be terminated.
</div>

<div class="grid2" style="align-items: start;">
  <div>
    <div class="aff-card">
      <h3>Referral details</h3>
      <div class="aff-row">
        <div style="flex: 1; min-width: 0;">
          <div class="aff-label">Referral link</div>
          <div class="aff-link-wrap">
            <input type="text" id="refLink" readonly value="<?= h($refLink) ?>">
            <button type="button" class="btn btn-primary" style="padding: 10px 18px;" id="copyRefBtn" onclick="var i=document.getElementById('refLink'); navigator.clipboard.writeText(i.value); var b=document.getElementById('copyRefBtn'); b.textContent='Copied!'; setTimeout(function(){ b.textContent='Copy'; }, 2000);">Copy</button>
          </div>
        </div>
      </div>
      <div class="aff-row">
        <div><div class="aff-label">Commission rate</div><div class="aff-value"><?= number_format($commission, 0) ?>%</div></div>
        <div><div class="aff-label">Minimum payout</div><div class="aff-value">$<?= number_format($minPayout, 2) ?></div></div>
      </div>
    </div>
  </div>
  <div class="aff-card">
    <h3>Statistics</h3>
    <div class="aff-stats">
      <div class="aff-stat"><div class="label">Total visits</div><div class="value"><?= number_format($visits) ?></div></div>
      <div class="aff-stat"><div class="label">Registrations</div><div class="value"><?= number_format($registrations) ?></div></div>
      <div class="aff-stat"><div class="label">Referrals</div><div class="value"><?= number_format($referralsCount) ?></div></div>
      <div class="aff-stat"><div class="label">Conversion rate</div><div class="value"><?= number_format($conversion, 2) ?>%</div></div>
      <div class="aff-stat"><div class="label">Total earnings</div><div class="value">$<?= number_format($totalLifetime, 2) ?></div></div>
      <div class="aff-stat"><div class="label">Unpaid earnings</div><div class="value">$<?= number_format($unpaid, 2) ?></div></div>
    </div>
  </div>
</div>

<p style="font-size: 13px; color: var(--text-muted); margin-top: 8px;">When someone registers using your referral link and places an order, you earn <?= number_format($commission, 0) ?>% commission. Request payout via <a href="<?= h(path('tickets.php')) ?>">support ticket</a> when you reach the minimum.</p>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
