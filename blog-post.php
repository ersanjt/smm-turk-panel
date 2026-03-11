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

$tags = $db->fetchAll("SELECT t.name, t.slug FROM blog_tags t INNER JOIN blog_article_tags at ON at.tag_id = t.id WHERE at.article_id = ?", [$post['id']]);

$pageTitle = $post['title'];
$pageDescription = $post['meta_description'] ?: ($post['excerpt'] ?: strip_tags(mb_substr($post['body'], 0, 160)));
$metaKeywords = $post['meta_keywords'];
$canonicalUrl = ($siteUrl ? $siteUrl . path('blog') : path('blog')) . '/' . rawurlencode($post['slug']);
$pageImage = $post['featured_image']
    ? ($siteUrl ? $siteUrl . '/' . ltrim($post['featured_image'], '/') : path($post['featured_image']))
    : ($siteUrl ? $siteUrl . path('assets/img/logo-icon.svg?v=3') : path('assets/img/logo-icon.svg?v=3'));

// JSON-LD Article for SEO and AI crawlers
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
        'logo' => ['@type' => 'ImageObject', 'url' => $siteUrl ? $siteUrl . path('assets/img/logo-icon.svg?v=3') : path('assets/img/logo-icon.svg?v=3')],
    ],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
];
if (!empty($tags)) {
    $jsonLd['keywords'] = implode(', ', array_column($tags, 'name'));
}

// Extra styles for post body (headings, lists, images)
$extraStyles = '
.article-body{font-size:1.05rem;line-height:1.75;}
.article-body h2{font-family:Syne,sans-serif;font-size:1.35rem;margin:1.5em 0 .5em;}
.article-body h3{font-size:1.15rem;margin:1.25em 0 .4em;}
.article-body p{margin-bottom:1em;}
.article-body ul,.article-body ol{margin:0 0 1em 1.5em;}
.article-body li{margin-bottom:.35em;}
.article-body img{max-width:100%;height:auto;border-radius:12px;}
.article-body a{color:var(--primary);font-weight:600;}
.article-body a:hover{text-decoration:underline;}
.article-meta{font-size:14px;color:var(--muted);margin-bottom:20px;}
.article-meta a{margin-right:12px;}
';
require __DIR__ . '/layouts/blog-header.php';

// Add extra styles for this page
if (!empty($extraStyles)) echo '<style>' . $extraStyles . '</style>';
?>
<article>
    <?php if (!empty($post['category_name'])): ?>
    <div class="article-meta"><a href="<?= h(path('blog.php') . '?category=' . rawurlencode($post['category_slug'])) ?>"><?= h($post['category_name']) ?></a></div>
    <?php endif; ?>
    <h1><?= h($post['title']) ?></h1>
    <div class="article-meta">
        <?= $post['published_at'] ? date('F j, Y', strtotime($post['published_at'])) : '' ?>
        <?php if (!empty($post['reading_time_min'])): ?> · <?= (int)$post['reading_time_min'] ?> min read<?php endif; ?>
    </div>
    <?php if (!empty($tags)): ?>
    <div class="blog-tags" style="margin-bottom:24px;">
        <?php foreach ($tags as $t): ?><a href="<?= h(path('blog.php') . '?tag=' . rawurlencode($t['slug'])) ?>" class="blog-tag"><?= h($t['name']) ?></a><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($post['featured_image'])): ?>
    <p><img src="<?= h($post['featured_image'][0] === '/' ? $post['featured_image'] : path($post['featured_image'])) ?>" alt="<?= h($post['title']) ?>" loading="lazy" width="800" height="450" style="width:100%;max-width:800px;"></p>
    <?php endif; ?>
    <div class="article-body">
        <?= $post['body'] ?>
    </div>
</article>
<p style="margin-top:32px;"><a href="<?= h(path('blog')) ?>" class="read-more">← <?= function_exists('__') ? h(__('blog_back')) : 'Back to Blog' ?></a></p>

<?php require __DIR__ . '/layouts/blog-footer.php'; ?>
