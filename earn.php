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
$refLink = '';
$refPct = number_format($refCommission, 0);

if ($loggedIn) {
    Lang::initUser();
    $pageTitle = __('nav_earn');
    $extraCssHref = asset_url('assets/css/earn.css');
    $user = $auth->getCurrentUser();
    $refCode = $auth->ensureReferralCode((int) $user['id']);
    $refLink = $refCode !== '' ? url('c/' . $refCode) : '';
    require_once __DIR__ . '/layouts/header.php';
} else {
    $lang = Lang::initPublic();
    $siteName = function_exists('site_name') ? site_name() : (defined('SITE_NAME') ? SITE_NAME : 'SMM Turk');
    $baseCanonical = Seo::absoluteUrl(path('earn.php'));
    $seoTitle = __('earn_meta_title');
    $seoDescription = __('earn_meta_desc');
    $metaKeywords = __('earn_keywords');
    $jsonLdGraph = [
        Seo::organizationSchema($seoDescription, $lang),
        Seo::websiteSchema($seoDescription),
        Seo::webPageSchema(__('earn_hero_h1'), $seoDescription, Seo::pageCanonical($baseCanonical, $lang), $lang),
        Seo::breadcrumbSchema([
            ['name' => __('blog_nav_home'), 'url' => Seo::absoluteUrl(home_path())],
            ['name' => __('nav_earn'), 'url' => $baseCanonical],
        ], $lang),
        Seo::faqSchema([
            ['name' => __('earn_child_title'), 'text' => __('earn_child_desc')],
            ['name' => __('earn_aff_title'), 'text' => sprintf(__('earn_aff_desc'), $refPct)],
            ['name' => __('earn_api_title'), 'text' => __('earn_api_desc')],
        ], $lang),
    ];
    $extraCssHrefs = [asset_url('assets/css/earn.css')];
}

$incomePaths = [
    [
        'id' => 'child-panel',
        'icon' => 'server',
        'tone' => 'primary',
        'title' => __('earn_child_title'),
        'subtitle' => __('earn_child_sub'),
        'desc' => __('earn_child_desc'),
        'bullets' => [
            sprintf(__('earn_child_b1'), number_format($childPrice, 2)),
            __('earn_child_b2'),
            __('earn_child_b3'),
            __('earn_child_b4'),
        ],
        'cta' => $loggedIn ? path('child-panel.php') : register_path(),
        'cta_label' => $loggedIn ? __('earn_child_cta_user') : __('earn_child_cta_guest'),
        'earn_you' => __('earn_child_you'),
    ],
    [
        'id' => 'affiliate',
        'icon' => 'users',
        'tone' => 'green',
        'title' => __('earn_aff_title'),
        'subtitle' => __('earn_aff_sub'),
        'desc' => sprintf(__('earn_aff_desc'), $refPct),
        'bullets' => [
            sprintf(__('earn_aff_b1'), $refPct),
            __('earn_aff_b2'),
            __('earn_aff_b3'),
            __('earn_aff_b4'),
        ],
        'cta' => $loggedIn ? path('affiliates.php') : register_path(),
        'cta_label' => $loggedIn ? __('earn_aff_cta_user') : __('earn_aff_cta_guest'),
        'earn_you' => __('earn_aff_you'),
    ],
    [
        'id' => 'api',
        'icon' => 'plug',
        'tone' => 'blue',
        'title' => __('earn_api_title'),
        'subtitle' => __('earn_api_sub'),
        'desc' => __('earn_api_desc'),
        'bullets' => [
            __('earn_api_b1'),
            __('earn_api_b2'),
            __('earn_api_b3'),
            __('earn_api_b4'),
        ],
        'cta' => $loggedIn ? path('api-page.php') : login_next_path('api-page.php'),
        'cta_label' => $loggedIn ? __('earn_api_cta_user') : __('earn_api_cta_guest'),
        'earn_you' => __('earn_api_you'),
    ],
];

if (!$loggedIn):
    $promo = $growth->promoBar();
?>
<!DOCTYPE html>
<html lang="<?= h(Seo::htmlLang($lang)) ?>">
<head>
<?php require __DIR__ . '/partials/public-seo-head.php'; ?>
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
    <span class="hero-badge"><?= h(__('earn_hero_badge')) ?></span>
    <h1><?= h(__('earn_hero_h1')) ?></h1>
    <p class="earn-hero-lead"><?= h(__('earn_hero_lead')) ?></p>
    <?php if (!empty($offers)): ?>
    <div class="hero-offers">
      <?php foreach (array_slice($offers, 0, 4) as $o): ?><span class="hero-offer-pill"><?= h($o) ?></span><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($loggedIn && $refLink !== ''): ?>
    <div class="earn-ref-quick">
      <label for="earnRefLink"><?= h(__('earn_ref_label')) ?></label>
      <div class="earn-ref-row">
        <input type="text" readonly value="<?= h($refLink) ?>" id="earnRefLink" aria-label="<?= h(__('earn_ref_label')) ?>">
        <button type="button" class="btn btn-primary btn-sm earn-copy-btn" data-copy-target="earnRefLink" data-copy-done="<?= h(__('earn_copy')) ?>"><?= h(__('earn_copy')) ?></button>
      </div>
    </div>
    <?php elseif (!$loggedIn): ?>
    <div class="earn-hero-cta">
      <a href="<?= h(register_path()) ?>" class="btn-cta"><?= h(__('earn_create_account')) ?><?= $welcomeCredit > 0 ? ' (+$' . number_format($welcomeCredit, 2) . ')' : '' ?> →</a>
      <a href="<?= h(path('pricing.php')) ?>" class="btn-cta-outline"><?= h(__('earn_view_prices')) ?></a>
    </div>
    <?php endif; ?>
    <nav class="earn-quick-nav" aria-label="<?= h(__('earn_section_label')) ?>">
      <?php foreach ($incomePaths as $path): ?>
      <a href="#<?= h($path['id']) ?>"><?= h($path['subtitle']) ?></a>
      <?php endforeach; ?>
    </nav>
  </header>

  <section class="earn-section" aria-labelledby="earn-paths-heading">
    <p class="section-label" id="earn-paths-heading"><?= h(__('earn_section_label')) ?></p>
    <h2 class="earn-section-title"><?= h(__('earn_section_title')) ?></h2>
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
            <strong><?= h(__('earn_you_label')) ?></strong> <?= h($path['earn_you']) ?>
          </div>
          <a href="<?= h($path['cta']) ?>" class="earn-card-cta"><?= h($path['cta_label']) ?></a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="earn-flow" aria-labelledby="earn-flow-heading">
    <p class="section-label"><?= h(__('earn_flow_label')) ?></p>
    <h2 id="earn-flow-heading" class="earn-section-title"><?= h(__('earn_flow_title')) ?></h2>
    <div class="earn-flow-steps">
      <div class="earn-flow-step">
        <span class="earn-flow-num">1</span>
        <p><?= __('earn_flow_s1') ?></p>
      </div>
      <div class="earn-flow-step">
        <span class="earn-flow-num">2</span>
        <p><?= __('earn_flow_s2') ?></p>
      </div>
      <div class="earn-flow-step">
        <span class="earn-flow-num">3</span>
        <p><?= __('earn_flow_s3') ?></p>
      </div>
      <div class="earn-flow-step">
        <span class="earn-flow-num">4</span>
        <p><?= __('earn_flow_s4') ?></p>
      </div>
    </div>
  </section>

  <section class="earn-section" aria-labelledby="earn-stats-heading">
    <p class="section-label" id="earn-stats-heading"><?= h(__('earn_stats_label')) ?></p>
    <h2 class="earn-section-title"><?= h(__('earn_stats_title')) ?></h2>
    <div class="stats-row earn-stats-row">
      <div class="stat-item">
        <div class="icon"><?= iconBox('users', 'blue') ?></div>
        <div class="stat-value"><?= h($stats['users']) ?></div>
        <div class="stat-label"><?= h(__('earn_stat_users')) ?></div>
      </div>
      <div class="stat-item">
        <div class="icon"><?= iconBox('orders', 'green') ?></div>
        <div class="stat-value"><?= h($stats['orders']) ?></div>
        <div class="stat-label"><?= h(__('earn_stat_orders')) ?></div>
      </div>
      <div class="stat-item">
        <div class="icon"><?= iconBox('services', 'primary') ?></div>
        <div class="stat-value"><?= h($stats['services']) ?></div>
        <div class="stat-label"><?= h(__('earn_stat_services')) ?></div>
      </div>
      <?php if ($depositBonus > 0): ?>
      <div class="stat-item">
        <div class="icon"><?= iconBox('deposit', 'orange') ?></div>
        <div class="stat-value">+<?= (int) $depositBonus ?>%</div>
        <div class="stat-label"><?= h(__('earn_deposit_bonus')) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if (!$loggedIn): ?>
  <section class="earn-section earn-section-cta">
    <div class="cta-block">
      <h2 class="section-title"><?= h(__('earn_cta_title')) ?></h2>
      <p class="section-desc"><?= h(__('earn_cta_desc')) ?></p>
      <a href="<?= h(register_path()) ?>" class="btn-cta"><?= h(__('earn_cta_btn')) ?></a>
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
    var done = btn.getAttribute('data-copy-done') || 'Copied!';
    var orig = btn.textContent;
    navigator.clipboard.writeText(el.value).then(function () {
      btn.textContent = done + '!';
      setTimeout(function () { btn.textContent = orig; }, 2000);
    });
  });
});
</script>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
<?php else: ?>
<footer class="footer earn-footer" role="contentinfo">
  <div class="footer-links">
    <a href="<?= h(home_path()) ?>"><?= h(__('blog_nav_home')) ?></a>
    <a href="<?= h(path('pricing.php')) ?>"><?= h(__('nav_prices')) ?></a>
    <a href="<?= h(path('help.php')) ?>"><?= h(__('help_title')) ?></a>
    <a href="<?= h(register_path()) ?>"><?= h(__('nav_sign_up')) ?></a>
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
    var done = btn.getAttribute('data-copy-done') || 'Copied!';
    var orig = btn.textContent;
    navigator.clipboard.writeText(el.value).then(function () {
      btn.textContent = done + '!';
      setTimeout(function () { btn.textContent = orig; }, 2000);
    });
  });
});
</script>
</body></html>
<?php endif; ?>
