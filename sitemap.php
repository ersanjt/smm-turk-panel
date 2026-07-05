<?php
/**
 * XML Sitemap for search engines. Outputs all public pages + blog posts.
 * Multilingual: xhtml:link hreflang for tr, en, de on each URL.
 * Access as: /sitemap.xml (via .htaccess rewrite)
 */
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/Lang.php';

$siteUrl = Seo::siteUrl();
if ($siteUrl === '') {
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    exit;
}

$db = Database::getInstance();
$base = $siteUrl . (function_exists('base_path') ? base_path() : '');

$urls = [];
$seen = [];

$addUrl = static function (string $loc, string $lastmod, string $freq, string $priority) use (&$urls, &$seen): void {
    if (isset($seen[$loc])) {
        return;
    }
    $seen[$loc] = true;
    $urls[] = ['loc' => $loc, 'lastmod' => $lastmod, 'changefreq' => $freq, 'priority' => $priority];
};

// Public marketing pages
$static = [
    '/' => ['freq' => 'daily', 'priority' => '1.0'],
    '/help' => ['freq' => 'weekly', 'priority' => '0.85'],
    '/blog' => ['freq' => 'daily', 'priority' => '0.9'],
    '/terms' => ['freq' => 'yearly', 'priority' => '0.5'],
];
foreach ($static as $path => $meta) {
    $addUrl($base . $path, date('Y-m-d'), $meta['freq'], $meta['priority']);
}

// Published blog posts
$posts = $db->fetchAll(
    "SELECT slug, updated_at, published_at FROM blog_articles WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW() ORDER BY published_at DESC"
);
foreach ($posts as $row) {
    $lastmod = !empty($row['updated_at']) ? $row['updated_at'] : $row['published_at'];
    $addUrl(
        $base . '/blog/' . rawurlencode($row['slug']),
        date('Y-m-d', strtotime($lastmod)),
        'weekly',
        '0.8'
    );
}

// Blog categories
try {
    $categories = $db->fetchAll(
        "SELECT c.slug, MAX(a.updated_at) AS lastmod FROM blog_categories c
         INNER JOIN blog_articles a ON a.category_id = c.id
         WHERE a.status = 'published' AND a.published_at IS NOT NULL AND a.published_at <= NOW()
         GROUP BY c.slug"
    );
    foreach ($categories as $cat) {
        $addUrl(
            $base . '/blog?category=' . rawurlencode($cat['slug']),
            !empty($cat['lastmod']) ? date('Y-m-d', strtotime($cat['lastmod'])) : date('Y-m-d'),
            'weekly',
            '0.7'
        );
    }
} catch (Throwable $e) {}

// Blog tags (with published posts)
try {
    $tags = $db->fetchAll(
        "SELECT t.slug, MAX(a.updated_at) AS lastmod FROM blog_tags t
         INNER JOIN blog_article_tags at ON at.tag_id = t.id
         INNER JOIN blog_articles a ON a.id = at.article_id
         WHERE a.status = 'published' AND a.published_at IS NOT NULL AND a.published_at <= NOW()
         GROUP BY t.slug"
    );
    foreach ($tags as $tag) {
        $addUrl(
            $base . '/blog?tag=' . rawurlencode($tag['slug']),
            !empty($tag['lastmod']) ? date('Y-m-d', strtotime($tag['lastmod'])) : date('Y-m-d'),
            'weekly',
            '0.65'
        );
    }
} catch (Throwable $e) {}

// Blog pagination (page 2+)
try {
    $totalPosts = (int) $db->fetch(
        "SELECT COUNT(*) c FROM blog_articles WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW()"
    )['c'];
    $perPage = 12;
    $totalPages = max(1, (int) ceil($totalPosts / $perPage));
    for ($p = 2; $p <= $totalPages; $p++) {
        $addUrl($base . '/blog?p=' . $p, date('Y-m-d'), 'weekly', '0.6');
    }
} catch (Throwable $e) {}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($urls as $u): ?>
  <url>
    <loc><?= h($u['loc']) ?></loc><?= Seo::sitemapHreflangLinks($u['loc']) ?>

    <lastmod><?= h($u['lastmod']) ?></lastmod>
    <changefreq><?= h($u['changefreq']) ?></changefreq>
    <priority><?= h($u['priority']) ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
