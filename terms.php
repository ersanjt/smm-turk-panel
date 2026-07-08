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
$blogNavActive = 'terms';
$seoHreflang = true;
$seoHreflangBase = $canonicalUrl;
$jsonLd = Seo::breadcrumbSchema([
    ['name' => __('blog_nav_home'), 'url' => $siteUrl !== '' ? Seo::absoluteUrl(home_path()) : home_path()],
    ['name' => $pageTitle, 'url' => $canonicalUrl],
], $lang);

require_once __DIR__ . '/layouts/blog-header.php';
?>

<main class="blog-article-wrap" role="main">
<article>
  <nav class="blog-breadcrumb" aria-label="Breadcrumb">
    <a href="<?= h(home_path()) ?>"><?= h(__('blog_nav_home')) ?></a> · <?= h($pageTitle) ?>
  </nav>
  <h1><?= h($pageTitle) ?></h1>
  <div class="article-body">
    <p>By using <?= h($siteName) ?> you agree to the following terms.</p>
    <h2>1. Service</h2>
    <p>We provide social media marketing (SMM) services. Orders are processed by our provider. Start times and delivery speeds vary by service.</p>
    <h2>2. Account &amp; Payment</h2>
    <p>You must provide accurate information. Balance is shown in USD. <strong>Deposits are cryptocurrency only</strong> (e.g. BTC, ETH, USDT, BNB, SOL). Refunds are only given when we are at fault. Chargebacks may result in account suspension.</p>
    <h2>3. Orders</h2>
    <p>Ensure links and profiles are public before ordering. Do not change links or account settings after placing an order.</p>
    <h2>4. Prohibited Use</h2>
    <p>You may not use our services for illegal content, spam, or to violate platform rules. We may suspend or ban accounts that abuse the service.</p>
    <h2>5. Contact</h2>
    <p>For support, <?= $auth->isLoggedIn() ? 'open a ticket from your dashboard or' : 'sign in and open a ticket, or' ?> contact us via the email shown in the footer.</p>
  </div>
  <a href="<?= h(home_path()) ?>" class="blog-back-link">← <?= h(__('blog_nav_home')) ?></a>
</article>
</main>

<?php require_once __DIR__ . '/layouts/blog-footer.php'; ?>
