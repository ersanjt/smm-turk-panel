<?php
/**
 * Dynamic robots.txt — Sitemap URL from SITE_URL.
 * Served as /robots.txt via .htaccess rewrite.
 */
require_once __DIR__ . '/app/init.php';
$siteUrl = defined('SITE_URL') && SITE_URL !== '' ? rtrim(SITE_URL, '/') : 'https://smm-turk.com';
header('Content-Type: text/plain; charset=UTF-8');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /api/\n\n";
echo "Sitemap: " . $siteUrl . "/sitemap.xml\n";
