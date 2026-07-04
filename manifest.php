<?php
require_once __DIR__ . '/app/init.php';
header('Content-Type: application/manifest+json; charset=UTF-8');
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
$start = path('home.php');
$scope = base_path() !== '' ? base_path() . '/' : '/';
echo json_encode([
    'name' => $siteName,
    'short_name' => $siteName,
    'description' => 'Cheapest SMM panel — Instagram, YouTube, TikTok growth. Crypto deposits, reseller API.',
    'start_url' => $start,
    'scope' => $scope,
    'display' => 'standalone',
    'background_color' => '#1a0a0e',
    'theme_color' => '#E30A17',
    'orientation' => 'portrait-primary',
    'icons' => [
        [
            'src' => path('assets/img/logo-icon.svg'),
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
