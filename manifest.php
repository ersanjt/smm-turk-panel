<?php
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/Lang.php';
header('Content-Type: application/manifest+json; charset=UTF-8');
$lang = Lang::current();
$siteName = Seo::siteName();
$start = home_path();
$scope = base_path() !== '' ? base_path() . '/' : '/';
echo json_encode([
    'name' => $siteName,
    'short_name' => $siteName,
    'description' => 'Cheapest SMM panel — Instagram, YouTube, TikTok growth. Turkey & worldwide. Crypto deposits, reseller API.',
    'lang' => Seo::htmlLang($lang),
    'dir' => 'ltr',
    'start_url' => $start,
    'scope' => $scope,
    'display' => 'standalone',
    'background_color' => '#1a0a0e',
    'theme_color' => '#E30A17',
    'orientation' => 'portrait-primary',
    'icons' => [
        [
            'src' => logo_url(),
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
