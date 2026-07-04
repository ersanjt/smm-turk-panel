<?php
/**
 * Single blog post by slug. SEO + JSON-LD Article schema for search and AI.
 */
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/Lang.php';
$lang = Lang::init();
$db = Database::getInstance();
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
$siteUrl  = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

$slug = isset($_GET['slug']) ? trim(preg_replace('/[^a-z0-9\-]/', '', (string)$_GET['slug'])) : '';
if ($slug === '') {
    header('Location: ' . url('blog'));
    exit;
}

$post = $db->fetch(
    "SELECT a.*, c.name AS category_name, c.slug AS category_slug
     FROM blog_articles a
     LEFT JOIN blog_categories c ON c.id = a.category_id
     WHERE a.slug = ? AND a.status = 'published' AND a.published_at IS NOT NULL AND a.published_at <= NOW()",
    [$slug]
);

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    header('Location: ' . url('blog'));
    exit;
}

$tags = $db->fetchAll(
    "SELECT t.name, t.slug FROM blog_tags t INNER JOIN blog_article_tags at ON at.tag_id = t.id WHERE at.article_id = ?",
    [$post['id']]
);

$pageTitle = $post['title'];
$pageDescription = $post['meta_description'] ?: ($post['excerpt'] ?: strip_tags(mb_substr($post['body'], 0, 160)));
$metaKeywords = $post['meta_keywords'];
$canonicalUrl = ($siteUrl ? $siteUrl . path('blog') : path('blog')) . '/' . rawurlencode($post['slug']);
$pageImage = $post['featured_image']
    ? ($siteUrl ? $siteUrl . '/' . ltrim($post['featured_image'], '/') : path($post['featured_image']))
    : og_image_url();
$ogType = 'article';
$articlePublished = $post['published_at'];
$articleModified = $post['updated_at'] ?? $post['published_at'];
$articleSection = $post['category_name'] ?? '';
$articleTags = array_column($tags, 'name');

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $post['title'],
    'description' => $pageDescription,
    'image' => $pageImage,
    'url' => $canonicalUrl,
    'datePublished' => $post['published_at'],
    'dateModified' => $post['updated_at'] ?? $post['published_at'],
    'author' => ['@type' => 'Organization', 'name' => $siteName],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => $siteUrl,
        'logo' => ['@type' => 'ImageObject', 'url' => og_image_url()],
    ],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
];
if (!empty($tags)) {
    $jsonLd['keywords'] = implode(', ', array_column($tags, 'name'));
}

$blogNavActive = 'blog';
require __DIR__ . '/layouts/blog-header.php';
?>
<main class="blog-article-wrap" role="main">
<article>
    <nav class="blog-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= h(path('blog.php')) ?>"><?= function_exists('__') ? h(__('blog_title')) : 'Blog' ?></a>
        <?php if (!empty($post['category_name'])): ?>
        · <a href="<?= h(path('blog.php') . '?category=' . rawurlencode($post['category_slug'])) ?>"><?= h($post['category_name']) ?></a>
        <?php endif; ?>
    </nav>

    <?php if (!empty($post['category_name'])): ?>
    <div class="blog-card-cat"><a href="<?= h(path('blog.php') . '?category=' . rawurlencode($post['category_slug'])) ?>"><?= h($post['category_name']) ?></a></div>
    <?php endif; ?>

    <h1><?= h($post['title']) ?></h1>
    <div class="article-meta">
        <span><?= $post['published_at'] ? date('F j, Y', strtotime($post['published_at'])) : '' ?></span>
        <?php if (!empty($post['reading_time_min'])): ?><span>· <?= (int)$post['reading_time_min'] ?> min read</span><?php endif; ?>
        <span>· <?= h($siteName) ?></span>
    </div>

    <?php if (!empty($tags)): ?>
    <div class="blog-tags-row" style="margin-bottom:24px;">
        <?php foreach ($tags as $t): ?>
        <a href="<?= h(path('blog.php') . '?tag=' . rawurlencode($t['slug'])) ?>" class="blog-tag"><?= h($t['name']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($post['featured_image'])): ?>
    <p><img src="<?= h($post['featured_image'][0] === '/' ? $post['featured_image'] : path($post['featured_image'])) ?>" alt="<?= h($post['title']) ?>" loading="eager" width="800" height="450" style="width:100%;border-radius:16px;margin-bottom:24px;"></p>
    <?php endif; ?>

    <div class="article-body">
        <?= $post['body'] ?>
    </div>
</article>

<div class="blog-cta" style="margin-top:40px;">
  <div>
    <h3>Start using <?= h($siteName) ?> today</h3>
    <p>Cheapest SMM services — Instagram, YouTube, TikTok. Pay with USDT, BTC, or ETH.</p>
  </div>
  <a href="<?= h(path('login.php')) ?>?mode=register" class="blog-cta-btn"><?= function_exists('__') ? h(__('nav_sign_up')) : 'Get Started' ?></a>
</div>

<a href="<?= h(path('blog')) ?>" class="blog-back-link">← <?= function_exists('__') ? h(__('blog_back')) : 'Back to Blog' ?></a>
</main>

<?php require __DIR__ . '/layouts/blog-footer.php'; ?>
