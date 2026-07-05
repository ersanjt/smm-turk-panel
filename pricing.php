<?php
/**
 * Public pricing page — SEO traffic from Google ("cheap instagram followers", etc.)
 */
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/Lang.php';
require_once __DIR__ . '/app/PlatformIcons.php';

if ($auth->isLoggedIn()) {
    redirect(url('services.php'));
}

$lang = Lang::initPublic();
$db = Database::getInstance();
$growth = new GrowthEngine();
$stats = $growth->publicStats();
$offers = $growth->offerLines();
$highlights = $growth->pricingHighlights(2);
$table = $growth->pricingTable(36);

$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
$siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$canonical = Seo::absoluteUrl(path('pricing.php'));
$seoTitle = 'SMM Panel Prices — Instagram, TikTok, YouTube | ' . $siteName;
$seoDescription = 'Cheapest SMM panel prices. Buy Instagram followers, TikTok likes, YouTube views from $' . preg_replace('/[^0-9.]/', '', $stats['min_price']) . '/1K. Crypto payments, instant start.';
$pageImg = og_image_url();
if ($pageImg !== '' && !preg_match('#^https?://#i', $pageImg)) {
    $pageImg = Seo::absoluteUrl($pageImg);
}
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
    <?= Seo::hreflangTags(path('pricing.php')) ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= h($seoTitle) ?>">
    <meta property="og:description" content="<?= h($seoDescription) ?>">
    <meta property="og:url" content="<?= h($canonical) ?>">
    <meta property="og:image" content="<?= h($pageImg) ?>">
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/landing.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/pricing-public.css')) ?>">
</head>
<body>
<?php $promo = $growth->promoBar(); if ($promo['enabled']): ?>
<div class="growth-promo-bar">
    <span><?= h($promo['text']) ?></span>
    <a href="<?= h($promo['cta_url']) ?>"><?= h($promo['cta_label']) ?></a>
</div>
<?php endif; ?>

<header class="nav" role="banner">
    <div class="nav-inner">
        <a href="<?= h(home_path()) ?>" class="nav-logo"><span class="nav-logo-text">SMM <span>TURK</span></span></a>
        <div class="nav-links">
            <a href="<?= h(path('pricing.php')) ?>">Prices</a>
            <a href="<?= h(path('blog.php')) ?>">Blog</a>
            <a href="<?= h(route_path('login.php')) ?>">Sign in</a>
            <a href="<?= h(register_path()) ?>" class="nav-btn">Sign up →</a>
        </div>
    </div>
</header>

<main class="pricing-public">
    <section class="pricing-hero">
        <h1>SMM Panel Prices</h1>
        <p>Transparent rates for Instagram, TikTok, YouTube & more. <?= count($offers) ?> reasons to start today:</p>
        <ul class="pricing-offers">
            <?php foreach ($offers as $line): ?><li>✓ <?= h($line) ?></li><?php endforeach; ?>
        </ul>
        <div class="pricing-hero-cta">
            <a href="<?= h(register_path()) ?>" class="btn-cta">Create free account →</a>
            <a href="<?= h(path('login.php')) ?>" class="btn-cta-outline">Sign in</a>
        </div>
        <div class="stats-row" style="margin-top:32px;">
            <div class="stat-item"><div class="stat-value"><?= h($stats['services']) ?></div><div class="stat-label">Services</div></div>
            <div class="stat-item"><div class="stat-value"><?= h($stats['orders']) ?></div><div class="stat-label">Orders completed</div></div>
            <div class="stat-item"><div class="stat-value"><?= h($stats['min_price']) ?></div><div class="stat-label">Prices from</div></div>
        </div>
    </section>

    <?php if (!empty($highlights)): ?>
    <section class="section" style="padding-top:20px;">
        <h2 class="section-title">Popular platforms</h2>
        <div class="pricing-platform-grid">
            <?php
            $byPlatform = [];
            foreach ($highlights as $s) {
                $byPlatform[$s['platform']][] = $s;
            }
            foreach ($byPlatform as $platform => $items):
                $pKey = platformKeyFromCategory($platform);
            ?>
            <div class="pricing-platform-card">
                <h3><?= platformSvgBrand($pKey, 20) ?> <?= h($platform) ?></h3>
                <ul>
                    <?php foreach ($items as $s): ?>
                    <li>
                        <span><?= h(mb_substr($s['name'], 0, 48)) ?></span>
                        <strong>$<?= number_format((float) $s['retail_rate'], 4) ?>/1k</strong>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="section" style="background:var(--white);">
        <h2 class="section-title">Service price list</h2>
        <p class="section-desc">Register to place orders. Prices include panel markup — VIP discounts apply after you spend more.</p>
        <div class="pricing-table-wrap">
            <table class="pricing-table">
                <thead><tr><th>ID</th><th>Service</th><th>Category</th><th>Rate / 1K</th><th>Min</th></tr></thead>
                <tbody>
                <?php foreach ($table as $s): ?>
                <tr>
                    <td><?= (int) $s['service_id'] ?></td>
                    <td><?= h(mb_substr($s['name'], 0, 70)) ?></td>
                    <td><?= h($s['category'] ?? '') ?></td>
                    <td><strong>$<?= number_format((float) $s['retail_rate'], 4) ?></strong></td>
                    <td><?= number_format((int) $s['min']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="cta-block" style="margin-top:40px;">
            <h2 class="section-title">Ready to order?</h2>
            <p class="section-desc">Create account in 30 seconds — pay with crypto, start in minutes.</p>
            <a href="<?= h(register_path()) ?>" class="btn-cta">Sign up & get started →</a>
        </div>
    </section>
</main>

<footer class="footer" style="padding:24px;text-align:center;font-size:13px;color:var(--muted);">
    <a href="<?= h(home_path()) ?>"><?= h($siteName) ?></a> · <a href="<?= h(path('terms.php')) ?>">Terms</a> · <a href="<?= h(path('help.php')) ?>">Help</a>
</footer>
</body>
</html>
