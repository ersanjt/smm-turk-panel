<?php
/**
 * Public Terms of Service — indexable, no login required.
 */
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/Lang.php';

$lang = Lang::initPublic();
$siteName = Seo::siteName();
$siteUrl  = Seo::siteUrl();
$termsPath = path('terms.php');
$canonicalUrl = $siteUrl !== '' ? Seo::absoluteUrl($termsPath) : $termsPath;

$pageTitle = __('nav_terms');
$pageDescription = __('terms_meta_desc');
$blogNavActive = '';
$seoHreflang = true;
$seoHreflangBase = $canonicalUrl;
$jsonLd = Seo::breadcrumbSchema([
    ['name' => __('blog_nav_home'), 'url' => $siteUrl !== '' ? Seo::absoluteUrl(home_path()) : home_path()],
    ['name' => $pageTitle, 'url' => $canonicalUrl],
], $lang);

require_once __DIR__ . '/layouts/blog-header.php';
?>

<main class="blog-article-wrap" role="main" style="max-width:800px;margin:0 auto;padding:24px 20px 48px;">
<article class="card" style="padding:28px 32px;">
  <h1 style="margin:0 0 20px;font-size:1.75rem;">Terms of Service</h1>
  <div style="font-size:15px;line-height:1.8;color:var(--text, #334155);">
    <p>By using <?= h($siteName) ?> you agree to the following terms.</p>
    <h2 style="margin:24px 0 10px;font-size:1.1rem;">1. Service</h2>
    <p>We provide social media marketing (SMM) services. Orders are processed by our provider. Start times and delivery speeds vary by service.</p>
    <h2 style="margin:24px 0 10px;font-size:1.1rem;">2. Account &amp; Payment</h2>
    <p>You must provide accurate information. Balance is shown in USD. <strong>Deposits are cryptocurrency only</strong> (e.g. BTC, ETH, USDT, BNB, SOL). Refunds are only given when we are at fault. Chargebacks may result in account suspension.</p>
    <h2 style="margin:24px 0 10px;font-size:1.1rem;">3. Orders</h2>
    <p>Ensure links and profiles are public before ordering. Do not change links or account settings after placing an order.</p>
    <h2 style="margin:24px 0 10px;font-size:1.1rem;">4. Prohibited Use</h2>
    <p>You may not use our services for illegal content, spam, or to violate platform rules. We may suspend or ban accounts that abuse the service.</p>
    <h2 style="margin:24px 0 10px;font-size:1.1rem;">5. Contact</h2>
    <p>For support, <?= $auth->isLoggedIn() ? 'open a ticket from your dashboard or' : 'sign in and open a ticket, or' ?> contact us via the email shown in the footer.</p>
  </div>
  <p style="margin-top:28px;">
    <a href="<?= h(home_path()) ?>">← Back to Home</a>
    <?php if (!$auth->isLoggedIn()): ?>
    · <a href="<?= h(register_path()) ?>">Create account</a>
    <?php else: ?>
    · <a href="<?= h(dashboard_path()) ?>">Go to Dashboard</a>
    <?php endif; ?>
  </p>
</article>
</main>

<?php require_once __DIR__ . '/layouts/blog-footer.php'; ?>
