<?php
/**
 * XML Sitemap for search engines. Outputs all public pages + blog posts.
 * Access as: /sitemap.xml (via .htaccess rewrite)
 */
require_once __DIR__ . '/app/init.php';

$siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
if ($siteUrl === '') {
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    exit;
}

$db = Database::getInstance();
$base = $siteUrl . (function_exists('base_path') ? base_path() : '');

$urls = [];

// Public marketing and auth pages only (no login-gated routes)
$static = [
    '/home' => ['freq' => 'daily', 'priority' => '1.0'],
    '/help' => ['freq' => 'weekly', 'priority' => '0.85'],
    '/blog' => ['freq' => 'daily', 'priority' => '0.9'],
    '/login' => ['freq' => 'monthly', 'priority' => '0.6'],
    '/forgot-password' => ['freq' => 'yearly', 'priority' => '0.4'],
];
foreach ($static as $path => $meta) {
    $urls[] = [
        'loc' => $base . $path,
        'lastmod' => date('Y-m-d'),
        'changefreq' => $meta['freq'],
        'priority' => $meta['priority'],
    ];
}

// Published blog posts
$posts = $db->fetchAll(
    "SELECT slug, updated_at, published_at FROM blog_articles WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW() ORDER BY published_at DESC"
);
foreach ($posts as $row) {
    $lastmod = !empty($row['updated_at']) ? $row['updated_at'] : $row['published_at'];
    $urls[] = [
        'loc' => $base . '/blog/' . $row['slug'],
        'lastmod' => date('Y-m-d', strtotime($lastmod)),
        'changefreq' => 'weekly',
        'priority' => '0.8',
    ];
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
  <url>
    <loc><?= h($u['loc']) ?></loc>
    <lastmod><?= h($u['lastmod']) ?></lastmod>
    <changefreq><?= h($u['changefreq']) ?></changefreq>
    <priority><?= h($u['priority']) ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
