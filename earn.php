<?php
/**
 * Public + logged-in hub: 3 ways to earn + SEO for "make money SMM panel".
 */
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/Lang.php';

$loggedIn = $auth->isLoggedIn();
$db = Database::getInstance();
$growth = new GrowthEngine();
$stats = $growth->publicStats();
$offers = $growth->offerLines();
$childPrice = (float) ($db->getSetting('child_panel_price') ?: 5);
$refCommission = (float) ($db->getSetting('referral_commission') ?: 2);
$welcomeCredit = $growth->welcomeCreditAmount();
$depositBonus = (float) ($db->getSetting('deposit_bonus_percent') ?: 0);

if ($loggedIn) {
    $pageTitle = 'Earn Money';
    $extraCssHref = asset_url('assets/css/earn.css');
    $user = $auth->getCurrentUser();
    $refCode = $auth->ensureReferralCode((int) $user['id']);
    $refLink = $refCode !== '' ? url('c/' . $refCode) : '';
    require_once __DIR__ . '/layouts/header.php';
} else {
    $lang = Lang::initPublic();
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
    $canonical = Seo::absoluteUrl(path('earn.php'));
    $seoTitle = 'Make Money with SMM Panel — Reseller, Affiliate, Child Panel | ' . $siteName;
    $seoDescription = 'Earn with SMM Turk: start your own child panel, affiliate commissions, or API reseller business. Cheapest wholesale SMM prices.';
}

$incomePaths = [
    [
        'id' => 'child-panel',
        'icon' => 'server',
        'tone' => 'primary',
        'title' => 'Your own SMM website',
        'subtitle' => 'Child Panel — white-label',
        'desc' => 'Launch a full SMM panel on your domain. Your customers pay you retail; wholesale cost comes from your SMM Turk balance. You keep the markup.',
        'bullets' => [
            'Only $' . number_format($childPrice, 2) . '/month hosting',
            'Your brand, logo, prices & payments',
            'Auto-deploy when DNS is ready',
            'Customers order 24/7 — you earn margin',
        ],
        'cta' => $loggedIn ? path('child-panel.php') : register_path(),
        'cta_label' => $loggedIn ? 'Open Child Panel →' : 'Sign up & start →',
        'earn_you' => 'Recurring panel fee + markup on every customer order',
    ],
    [
        'id' => 'affiliate',
        'icon' => 'users',
        'tone' => 'green',
        'title' => 'Affiliate program',
        'subtitle' => 'Share link — earn commission',
        'desc' => 'Refer new users with your unique link. When they place orders, you earn ' . number_format($refCommission, 0) . '% commission automatically.',
        'bullets' => [
            number_format($refCommission, 0) . '% on referred user orders',
            'Transfer earnings to balance anytime',
            'Share on social media, blog, Telegram',
            'No technical skills needed',
        ],
        'cta' => $loggedIn ? path('affiliates.php') : register_path(),
        'cta_label' => $loggedIn ? 'Get referral link →' : 'Join free →',
        'earn_you' => 'Passive commission income',
    ],
    [
        'id' => 'api',
        'icon' => 'plug',
        'tone' => 'blue',
        'title' => 'API reseller',
        'subtitle' => 'Automate & integrate',
        'desc' => 'Connect your website, bot, or script via API. Buy at wholesale, sell at your price. Perfect for agencies and developers.',
        'bullets' => [
            'Standard SMM API (add, status, balance)',
            'Thousands of services',
            'VIP discounts as you spend more',
            'Mass order support',
        ],
        'cta' => $loggedIn ? path('api-page.php') : login_next_path('api-page.php'),
        'cta_label' => $loggedIn ? 'API documentation →' : 'Login for API key →',
        'earn_you' => 'Sell services in your app or to clients',
    ],
];

if (!$loggedIn):
    $promo = $growth->promoBar();
?>
<!DOCTYPE html>
<html lang="<?= h(Seo::htmlLang($lang)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($seoTitle) ?></title>
    <meta name="description" content="<?= h($seoDescription) ?>">
    <meta name="robots" content="<?= h(Seo::robotsContent(true)) ?>">
    <link rel="canonical" href="<?= h($canonical) ?>">
    <?= Seo::hreflangTags(path('earn.php')) ?>
    <meta property="og:title" content="<?= h($seoTitle) ?>">
    <meta property="og:description" content="<?= h($seoDescription) ?>">
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/landing.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/pricing-public.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/earn.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/ui-pro.css')) ?>">
</head>
<body>
<script>(function(){var k='smmturk_theme',d=localStorage.getItem(k)==='dark'||(!localStorage.getItem(k)&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);if(d)document.body.classList.add('theme-dark');})();</script>
<?php if ($promo['enabled']): ?>
<div class="growth-promo-bar"><span><?= h($promo['text']) ?></span><a href="<?= h($promo['cta_url']) ?>"><?= h($promo['cta_label']) ?></a></div>
<?php endif; ?>
<?php $navActive = 'earn'; $registrationEnabled = ($db->getSetting('registration_enabled') ?? '1') === '1'; require __DIR__ . '/partials/landing-nav.php'; ?>
<?php endif; ?>

<div class="earn-page">
  <header class="earn-hero">
    <span class="hero-badge">💰 Income for you · Growth for us</span>
    <h1>Earn money with SMM Turk</h1>
    <p class="earn-hero-lead">Three proven ways to build income — whether you want a full business or passive referrals.</p>
    <?php if (!empty($offers)): ?>
    <div class="hero-offers">
      <?php foreach (array_slice($offers, 0, 4) as $o): ?><span class="hero-offer-pill"><?= h($o) ?></span><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($loggedIn && $refLink !== ''): ?>
    <div class="earn-ref-quick">
      <label for="earnRefLink">Your referral link</label>
      <div class="earn-ref-row">
        <input type="text" readonly value="<?= h($refLink) ?>" id="earnRefLink" aria-label="Referral link">
        <button type="button" class="btn btn-primary btn-sm earn-copy-btn" data-copy-target="earnRefLink">Copy</button>
      </div>
    </div>
    <?php elseif (!$loggedIn): ?>
    <div class="earn-hero-cta">
      <a href="<?= h(register_path()) ?>" class="btn-cta">Create free account<?= $welcomeCredit > 0 ? ' (+$' . number_format($welcomeCredit, 2) . ')' : '' ?> →</a>
      <a href="<?= h(path('pricing.php')) ?>" class="btn-cta-outline">View prices</a>
    </div>
    <?php endif; ?>
    <nav class="earn-quick-nav" aria-label="Jump to income paths">
      <?php foreach ($incomePaths as $path): ?>
      <a href="#<?= h($path['id']) ?>"><?= h($path['subtitle']) ?></a>
      <?php endforeach; ?>
    </nav>
  </header>

  <section class="earn-section" aria-labelledby="earn-paths-heading">
    <p class="section-label" id="earn-paths-heading">Income paths</p>
    <h2 class="earn-section-title">Choose how you want to earn</h2>
    <div class="earn-paths">
      <?php foreach ($incomePaths as $path): ?>
      <article class="earn-card" id="<?= h($path['id']) ?>">
        <div class="earn-card-icon"><?= iconBox($path['icon'], $path['tone'] ?? 'primary', 26) ?></div>
        <div class="earn-card-body">
          <p class="earn-card-sub"><?= h($path['subtitle']) ?></p>
          <h3><?= h($path['title']) ?></h3>
          <p class="earn-card-desc"><?= h($path['desc']) ?></p>
          <ul class="earn-card-list">
            <?php foreach ($path['bullets'] as $b): ?>
            <li><?= h($b) ?></li>
            <?php endforeach; ?>
          </ul>
          <div class="earn-meta">
            <strong>You earn:</strong> <?= h($path['earn_you']) ?>
          </div>
          <a href="<?= h($path['cta']) ?>" class="earn-card-cta"><?= h($path['cta_label']) ?></a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="earn-flow" aria-labelledby="earn-flow-heading">
    <p class="section-label">How it works</p>
    <h2 id="earn-flow-heading" class="earn-section-title">How money flows (Child Panel)</h2>
    <div class="earn-flow-steps">
      <div class="earn-flow-step">
        <span class="earn-flow-num">1</span>
        <p>Customer visits <strong>your domain</strong> & adds funds</p>
      </div>
      <div class="earn-flow-step">
        <span class="earn-flow-num">2</span>
        <p>They place orders at <strong>your prices</strong></p>
      </div>
      <div class="earn-flow-step">
        <span class="earn-flow-num">3</span>
        <p>Wholesale cost deducted from <strong>your SMM Turk balance</strong></p>
      </div>
      <div class="earn-flow-step">
        <span class="earn-flow-num">4</span>
        <p>You keep the <strong>markup profit</strong></p>
      </div>
    </div>
  </section>

  <section class="earn-section" aria-labelledby="earn-stats-heading">
    <p class="section-label" id="earn-stats-heading">Platform scale</p>
    <h2 class="earn-section-title">Built for resellers at scale</h2>
    <div class="stats-row earn-stats-row">
      <div class="stat-item">
        <div class="icon"><?= iconBox('users', 'blue') ?></div>
        <div class="stat-value"><?= h($stats['users']) ?></div>
        <div class="stat-label">Users</div>
      </div>
      <div class="stat-item">
        <div class="icon"><?= iconBox('orders', 'green') ?></div>
        <div class="stat-value"><?= h($stats['orders']) ?></div>
        <div class="stat-label">Orders</div>
      </div>
      <div class="stat-item">
        <div class="icon"><?= iconBox('services', 'primary') ?></div>
        <div class="stat-value"><?= h($stats['services']) ?></div>
        <div class="stat-label">Services</div>
      </div>
      <?php if ($depositBonus > 0): ?>
      <div class="stat-item">
        <div class="icon"><?= iconBox('deposit', 'orange') ?></div>
        <div class="stat-value">+<?= (int) $depositBonus ?>%</div>
        <div class="stat-label">First deposit bonus</div>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if (!$loggedIn): ?>
  <section class="earn-section earn-section-cta">
    <div class="cta-block">
      <h2 class="section-title">Start in 30 seconds</h2>
      <p class="section-desc">Register → get balance → order or launch your panel.</p>
      <a href="<?= h(register_path()) ?>" class="btn-cta">Sign up free →</a>
    </div>
  </section>
  <?php endif; ?>
</div>

<?php if ($loggedIn): ?>
<script>
document.querySelectorAll('.earn-copy-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var id = btn.getAttribute('data-copy-target');
    var el = id ? document.getElementById(id) : null;
    if (!el) return;
    navigator.clipboard.writeText(el.value).then(function () {
      btn.textContent = 'Copied!';
      setTimeout(function () { btn.textContent = 'Copy'; }, 2000);
    });
  });
});
</script>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
<?php else: ?>
<footer class="footer earn-footer" role="contentinfo">
  <div class="footer-links">
    <a href="<?= h(home_path()) ?>">Home</a>
    <a href="<?= h(path('pricing.php')) ?>">Prices</a>
    <a href="<?= h(path('help.php')) ?>">Help</a>
    <a href="<?= h(register_path()) ?>">Sign up</a>
  </div>
  <div class="footer-copy">© <?= date('Y') ?> <?= h($siteName ?? 'SMM Turk') ?>. All rights reserved.</div>
</footer>
<script src="<?= h(asset_url('assets/js/landing.js')) ?>" defer></script>
<script>
document.querySelectorAll('.earn-copy-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var id = btn.getAttribute('data-copy-target');
    var el = id ? document.getElementById(id) : null;
    if (!el) return;
    navigator.clipboard.writeText(el.value).then(function () {
      btn.textContent = 'Copied!';
      setTimeout(function () { btn.textContent = 'Copy'; }, 2000);
    });
  });
});
</script>
</body></html>
<?php endif; ?>
