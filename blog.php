<?php
/**
 * Blog index: list published articles with category/tag filter. SEO-optimized.
 */
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/Lang.php';
$lang = Lang::init();
$db = Database::getInstance();
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
$siteUrl  = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$baseBlogUrl = $siteUrl ? $siteUrl . path('blog') : path('blog');

$categorySlug = isset($_GET['category']) ? trim(preg_replace('/[^a-z0-9\-]/', '', (string)$_GET['category'])) : '';
$tagSlug      = isset($_GET['tag']) ? trim(preg_replace('/[^a-z0-9\-]/', '', (string)$_GET['tag'])) : '';
$pageNum      = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 12;
$offset       = ($pageNum - 1) * $perPage;

// Build query and SEO title/description
$where = ["a.status = 'published'", "a.published_at IS NOT NULL AND a.published_at <= NOW()"];
$params = [];
$pageTitle = function_exists('__') ? __('blog_title') : 'Blog';
$pageDescription = function_exists('__') ? __('blog_meta_desc') : 'SMM Turk Blog — Social media marketing tips, SMM panel guides, Instagram, YouTube, TikTok growth.';
$canonicalUrl = $baseBlogUrl;

if ($categorySlug !== '') {
    $cat = $db->fetch("SELECT id, name, meta_description FROM blog_categories WHERE slug = ?", [$categorySlug]);
    if (!$cat) {
        header('Location: ' . url('blog.php'));
        exit;
    }
    $where[] = 'a.category_id = ?';
    $params[] = $cat['id'];
    $pageTitle = $cat['name'];
    $pageDescription = $cat['meta_description'] ?: ($pageTitle . ' — ' . $siteName . ' Blog.');
    $canonicalUrl = $baseBlogUrl . '?category=' . $categorySlug;
}

if ($tagSlug !== '') {
    $tag = $db->fetch("SELECT id, name FROM blog_tags WHERE slug = ?", [$tagSlug]);
    if (!$tag) {
        header('Location: ' . url('blog.php'));
        exit;
    }
    $where[] = 'EXISTS (SELECT 1 FROM blog_article_tags at WHERE at.article_id = a.id AND at.tag_id = ?)';
    $params[] = $tag['id'];
    if ($categorySlug === '') {
        $pageTitle = $tag['name'];
        $pageDescription = 'Articles tagged with ' . $tag['name'] . ' — ' . $siteName . ' Blog.';
        $canonicalUrl = $baseBlogUrl . '?tag=' . $tagSlug;
    }
}

$whereSql = implode(' AND ', $where);
$countSql = "SELECT COUNT(*) FROM blog_articles a WHERE $whereSql";
$total = (int) $db->fetch($countSql, $params)['COUNT(*)'];
$totalPages = max(1, (int) ceil($total / $perPage));
if ($pageNum > $totalPages) $pageNum = 1;
$offset = ($pageNum - 1) * $perPage;

$articles = $db->fetchAll(
    "SELECT a.id, a.slug, a.title, a.excerpt, a.published_at, a.reading_time_min, a.featured_image, c.name AS category_name, c.slug AS category_slug
     FROM blog_articles a
     LEFT JOIN blog_categories c ON c.id = a.category_id
     WHERE $whereSql
     ORDER BY a.published_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

foreach ($articles as &$row) {
    $row['tags'] = $db->fetchAll("SELECT t.name, t.slug FROM blog_tags t INNER JOIN blog_article_tags at ON at.tag_id = t.id WHERE at.article_id = ?", [$row['id']]);
}
unset($row);

$categories = $db->fetchAll("SELECT slug, name FROM blog_categories ORDER BY name");
$tags = $db->fetchAll("SELECT slug, name FROM blog_tags ORDER BY name LIMIT 30");

// JSON-LD for Blog/ItemList (SEO + AI)
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Blog',
    'name' => $pageTitle . ' — ' . $siteName,
    'description' => $pageDescription,
    'url' => $canonicalUrl,
    'publisher' => ['@type' => 'Organization', 'name' => $siteName, 'url' => $siteUrl],
    'blogPost' => array_map(function ($a) use ($siteUrl) {
        $base = $siteUrl ? $siteUrl . path('blog') : path('blog');
        $postUrl = rtrim($base, '/') . '/' . $a['slug'];
        return [
            '@type' => 'BlogPosting',
            'headline' => $a['title'],
            'description' => $a['excerpt'] ?? '',
            'url' => $postUrl,
            'datePublished' => $a['published_at'],
        ];
    }, $articles),
];

$pageImage = og_image_url();
$paginationPrev = null;
$paginationNext = null;
if ($totalPages > 1) {
    if ($pageNum > 1) {
        $prevUrl = path('blog.php') . '?p=' . ($pageNum - 1);
        if ($categorySlug) $prevUrl .= '&category=' . rawurlencode($categorySlug);
        if ($tagSlug) $prevUrl .= '&tag=' . rawurlencode($tagSlug);
        $paginationPrev = ($siteUrl ? $siteUrl : '') . $prevUrl;
    }
    if ($pageNum < $totalPages) {
        $nextUrl = path('blog.php') . '?p=' . ($pageNum + 1);
        if ($categorySlug) $nextUrl .= '&category=' . rawurlencode($categorySlug);
        if ($tagSlug) $nextUrl .= '&tag=' . rawurlencode($tagSlug);
        $paginationNext = ($siteUrl ? $siteUrl : '') . $nextUrl;
    }
}
$blogNavActive = 'blog';
require __DIR__ . '/layouts/blog-header.php';
?>
<h1><?= h($pageTitle) ?></h1>
<p><?= h($pageDescription) ?></p>

<?php if (!empty($categories) || !empty($tags)): ?>
<div class="blog-cats">
    <a href="<?= h(path('blog.php')) ?>" class="<?= $categorySlug === '' && $tagSlug === '' ? 'active' : '' ?>"><?= function_exists('__') ? h(__('blog_all')) : 'All' ?></a>
    <?php foreach ($categories as $c): ?>
    <a href="<?= h(path('blog.php') . '?category=' . rawurlencode($c['slug'])) ?>" class="<?= $categorySlug === $c['slug'] ? 'active' : '' ?>"><?= h($c['name']) ?></a>
    <?php endforeach; ?>
    <?php foreach (array_slice($tags, 0, 15) as $t): ?>
    <a href="<?= h(path('blog.php') . '?tag=' . rawurlencode($t['slug'])) ?>" class="<?= $tagSlug === $t['slug'] ? 'active' : '' ?>"><?= h($t['name']) ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="blog-list">
<?php if (empty($articles)): ?>
    <p><?= function_exists('__') ? h(__('blog_no_posts')) : 'No posts yet.' ?></p>
<?php else: ?>
    <?php foreach ($articles as $a): 
        $postUrl = path('blog') . '/' . rawurlencode($a['slug']);
        $dateStr = $a['published_at'] ? date('M j, Y', strtotime($a['published_at'])) : '';
    ?>
    <article class="blog-card">
        <?php if (!empty($a['category_name'])): ?>
        <div class="meta"><a href="<?= h(path('blog.php') . '?category=' . rawurlencode($a['category_slug'])) ?>"><?= h($a['category_name']) ?></a></div>
        <?php endif; ?>
        <h2><a href="<?= h($postUrl) ?>"><?= h($a['title']) ?></a></h2>
        <div class="meta"><?= $dateStr ?><?php if (!empty($a['reading_time_min'])): ?> · <?= (int)$a['reading_time_min'] ?> min read<?php endif; ?></div>
        <?php if (!empty($a['excerpt'])): ?><p class="excerpt"><?= h($a['excerpt']) ?></p><?php endif; ?>
        <?php if (!empty($a['tags'])): ?>
        <div class="blog-tags">
            <?php foreach ($a['tags'] as $t): ?><a href="<?= h(path('blog.php') . '?tag=' . rawurlencode($t['slug'])) ?>" class="blog-tag"><?= h($t['name']) ?></a><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <a href="<?= h($postUrl) ?>" class="read-more"><?= function_exists('__') ? h(__('blog_read_more')) : 'Read more' ?> →</a>
    </article>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<nav class="blog-pagination" aria-label="Pagination">
    <?php if ($pageNum > 1): $prevUrl = path('blog.php') . '?p=' . ($pageNum - 1); if ($categorySlug) $prevUrl .= '&category=' . rawurlencode($categorySlug); if ($tagSlug) $prevUrl .= '&tag=' . rawurlencode($tagSlug); ?>
    <a href="<?= h($prevUrl) ?>">← <?= function_exists('__') ? h(__('blog_prev')) : 'Previous' ?></a>
    <?php endif; ?>
    <?php for ($i = max(1, $pageNum - 2); $i <= min($totalPages, $pageNum + 2); $i++): 
        $pUrl = path('blog.php') . '?p=' . $i; if ($categorySlug) $pUrl .= '&category=' . rawurlencode($categorySlug); if ($tagSlug) $pUrl .= '&tag=' . rawurlencode($tagSlug);
    ?><a href="<?= h($pUrl) ?>" class="<?= $i === $pageNum ? 'current' : '' ?>"><?= $i ?></a><?php endfor; ?>
    <?php if ($pageNum < $totalPages): $nextUrl = path('blog.php') . '?p=' . ($pageNum + 1); if ($categorySlug) $nextUrl .= '&category=' . rawurlencode($categorySlug); if ($tagSlug) $nextUrl .= '&tag=' . rawurlencode($tagSlug); ?>
    <a href="<?= h($nextUrl) ?>"><?= function_exists('__') ? h(__('blog_next')) : 'Next' ?> →</a>
    <?php endif; ?>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/layouts/blog-footer.php'; ?>
