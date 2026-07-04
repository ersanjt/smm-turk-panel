<?php
/**
 * Public Help Center — SEO-friendly guides (no login required).
 */
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/Lang.php';

$lang = Lang::init();
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
$siteUrl  = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$helpPath = path('help.php');
$canonicalUrl = $siteUrl ? $siteUrl . $helpPath : $helpPath;

$pageTitle = __('help_title');
$pageDescription = __('help_meta_desc');
$blogNavActive = 'help';

$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [],
];
for ($i = 1; $i <= 6; $i++) {
    $faqSchema['mainEntity'][] = [
        '@type' => 'Question',
        'name' => __('faq_' . $i),
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => __('faq_' . $i . '_a'),
        ],
    ];
}
$jsonLd = $faqSchema;

require_once __DIR__ . '/layouts/blog-header.php';
?>

<h1><?= h(__('help_title')) ?></h1>
<p style="color:var(--muted);margin-bottom:28px;"><?= h(__('help_intro')) ?></p>

<nav class="help-toc" aria-label="<?= h(__('help_toc_label')) ?>">
    <a href="#getting-started"><?= h(__('help_section_start')) ?></a>
    <a href="#orders"><?= h(__('help_section_orders')) ?></a>
    <a href="#payments"><?= h(__('help_section_payments')) ?></a>
    <a href="#api"><?= h(__('help_section_api')) ?></a>
    <a href="#support"><?= h(__('help_section_support')) ?></a>
    <a href="#instagram"><?= h(__('help_cluster_instagram')) ?></a>
    <a href="#youtube"><?= h(__('help_cluster_youtube')) ?></a>
    <a href="#tiktok"><?= h(__('help_cluster_tiktok')) ?></a>
    <a href="#reseller"><?= h(__('help_cluster_reseller')) ?></a>
    <a href="#faq"><?= h(__('faq_title')) ?></a>
</nav>

<section id="getting-started" class="help-section">
    <h2><?= h(__('help_section_start')) ?></h2>
    <p><?= h(__('help_start_body')) ?></p>
    <ul>
        <li><?= h(__('help_start_1')) ?></li>
        <li><?= h(__('help_start_2')) ?></li>
        <li><?= h(__('help_start_3')) ?></li>
    </ul>
</section>

<section id="orders" class="help-section">
    <h2><?= h(__('help_section_orders')) ?></h2>
    <p><?= h(__('help_orders_body')) ?></p>
    <ul>
        <li><?= h(__('help_orders_1')) ?></li>
        <li><?= h(__('help_orders_2')) ?></li>
        <li><?= h(__('help_orders_3')) ?></li>
    </ul>
</section>

<section id="payments" class="help-section">
    <h2><?= h(__('help_section_payments')) ?></h2>
    <p><?= h(__('help_payments_body')) ?></p>
    <ul>
        <li><?= h(__('help_payments_1')) ?></li>
        <li><?= h(__('help_payments_2')) ?></li>
    </ul>
</section>

<section id="api" class="help-section">
    <h2><?= h(__('help_section_api')) ?></h2>
    <p><?= h(__('help_api_body')) ?></p>
    <p><a href="<?= h(path('api-page.php')) ?>" style="color:var(--primary);font-weight:600;"><?= h(__('footer_api')) ?> →</a></p>
</section>

<section id="support" class="help-section">
    <h2><?= h(__('help_section_support')) ?></h2>
    <p><?= h(__('help_support_body')) ?></p>
    <p><a href="<?= h(path('login.php')) ?>" style="color:var(--primary);font-weight:600;"><?= h(__('nav_sign_in')) ?> →</a></p>
</section>

<section id="instagram" class="help-section help-cluster">
    <h2><?= h(__('help_cluster_instagram')) ?></h2>
    <p><?= h(__('help_cluster_instagram_body')) ?></p>
</section>

<section id="youtube" class="help-section help-cluster">
    <h2><?= h(__('help_cluster_youtube')) ?></h2>
    <p><?= h(__('help_cluster_youtube_body')) ?></p>
</section>

<section id="tiktok" class="help-section help-cluster">
    <h2><?= h(__('help_cluster_tiktok')) ?></h2>
    <p><?= h(__('help_cluster_tiktok_body')) ?></p>
</section>

<section id="reseller" class="help-section help-cluster">
    <h2><?= h(__('help_cluster_reseller')) ?></h2>
    <p><?= h(__('help_cluster_reseller_body')) ?></p>
    <p><a href="<?= h(path('api-page.php')) ?>" style="color:var(--primary);font-weight:600;"><?= h(__('footer_api')) ?> →</a></p>
</section>

<section id="faq" class="help-section">
    <h2><?= h(__('faq_title')) ?></h2>
    <div class="help-faq">
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <details class="help-faq-item">
            <summary><?= h(__('faq_' . $i)) ?></summary>
            <p><?= h(__('faq_' . $i . '_a')) ?></p>
        </details>
        <?php endfor; ?>
    </div>
</section>

<style>
.help-toc{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:32px}
.help-toc a{padding:8px 14px;border-radius:10px;background:var(--white);border:1px solid var(--border);font-size:13px;font-weight:600;transition:background .2s,color .2s}
.help-toc a:hover{background:var(--primary-soft);color:var(--primary)}
.help-section{margin-bottom:36px;padding-bottom:28px;border-bottom:1px solid var(--border)}
.help-section:last-child{border-bottom:none}
.help-section h2{font-family:'Syne',sans-serif;font-size:1.25rem;margin-bottom:12px}
.help-section p,.help-section li{font-size:15px;color:var(--muted);line-height:1.7}
.help-section ul{margin:12px 0 0 20px}
.help-cluster{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px 22px;margin-bottom:20px;border-bottom:none}
.help-faq{display:flex;flex-direction:column;gap:10px;margin-top:16px}
.help-faq-item{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:4px 16px}
.help-faq-item summary{cursor:pointer;font-weight:600;padding:12px 0;list-style:none}
.help-faq-item summary::-webkit-details-marker{display:none}
.help-faq-item p{padding-bottom:14px;font-size:14px;color:var(--muted);line-height:1.65}
</style>

<?php require_once __DIR__ . '/layouts/blog-footer.php'; ?>
