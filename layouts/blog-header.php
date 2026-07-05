<?php
if (!isset($pageTitle)) $pageTitle = 'Blog';
if (!isset($pageDescription)) $pageDescription = 'SMM Turk Blog — Tips for social media growth, SMM panel guides, Instagram, YouTube, TikTok marketing.';
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
$siteUrl  = defined('SITE_URL') ? rtrim(SITE_URL, '') : '';
$canonicalUrl = $canonicalUrl ?? ($siteUrl ? $siteUrl . path('blog.php') : path('blog.php'));
$pageImg  = $pageImage ?? og_image_url();
$geoRegion = defined('GEO_REGION') ? GEO_REGION : 'TR';
$lang = isset($lang) ? $lang : 'en';
$ogType = $ogType ?? 'website';
$ogLocale = $lang === 'tr' ? 'tr_TR' : ($lang === 'de' ? 'de_DE' : ($lang === 'fr' ? 'fr_FR' : 'en_US'));
$articlePublished = $articlePublished ?? null;
$articleModified = $articleModified ?? null;
$articleSection = $articleSection ?? null;
$articleTags = $articleTags ?? [];
$paginationPrev = $paginationPrev ?? null;
$paginationNext = $paginationNext ?? null;
$blogNavActive = $blogNavActive ?? '';
$blogHero = $blogHero ?? true;
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> — <?= h($siteName) ?></title>
    <meta name="description" content="<?= h($pageDescription) ?>">
    <?php if (!empty($metaKeywords)): ?><meta name="keywords" content="<?= h($metaKeywords) ?>"><?php endif; ?>
    <link rel="canonical" href="<?= h($canonicalUrl) ?>">
    <?php if ($paginationPrev): ?><link rel="prev" href="<?= h($paginationPrev) ?>"><?php endif; ?>
    <?php if ($paginationNext): ?><link rel="next" href="<?= h($paginationNext) ?>"><?php endif; ?>
    <meta name="theme-color" content="#E30A17">
    <meta name="geo.region" content="<?= h($geoRegion) ?>">
    <meta property="og:type" content="<?= h($ogType) ?>">
    <meta property="og:site_name" content="<?= h($siteName) ?>">
    <meta property="og:title" content="<?= h($pageTitle) ?> — <?= h($siteName) ?>">
    <meta property="og:description" content="<?= h($pageDescription) ?>">
    <meta property="og:url" content="<?= h($canonicalUrl) ?>">
    <meta property="og:image" content="<?= h($pageImg) ?>">
    <meta property="og:locale" content="<?= h($ogLocale) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <?php if ($ogType === 'article' && $articlePublished): ?>
    <meta property="article:published_time" content="<?= h(date('c', strtotime($articlePublished))) ?>">
    <?php endif; ?>
    <?php if ($ogType === 'article' && $articleModified): ?>
    <meta property="article:modified_time" content="<?= h(date('c', strtotime($articleModified))) ?>">
    <?php endif; ?>
    <?php if ($ogType === 'article' && $articleSection): ?>
    <meta property="article:section" content="<?= h($articleSection) ?>">
    <?php endif; ?>
    <?php foreach ($articleTags as $tagName): ?>
    <meta property="article:tag" content="<?= h($tagName) ?>">
    <?php endforeach; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($pageTitle) ?> — <?= h($siteName) ?>">
    <meta name="twitter:description" content="<?= h($pageDescription) ?>">
    <meta name="twitter:image" content="<?= h($pageImg) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= h(path('assets/img/logo-icon.svg?v=6')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/blog.css')) ?>?v=1">
    <?php if (!empty($jsonLd)): ?><script type="application/ld+json"><?= is_array($jsonLd) ? json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $jsonLd ?></script><?php endif; ?>
    <?php if (!empty($extraStyles)): ?><style><?= $extraStyles ?></style><?php endif; ?>
</head>
<body class="blog-page">
<nav class="blog-nav" role="navigation">
    <div class="blog-nav-inner">
        <a href="<?= h(path('home.php')) ?>" class="blog-nav-logo">
            <img src="<?= h(path('assets/img/logo-icon.svg?v=6')) ?>" alt="<?= h($siteName) ?>" width="40" height="40">
            SMM <span>TURK</span>
        </a>
        <div class="blog-nav-links">
            <a href="<?= h(path('home.php')) ?>"<?= $blogNavActive === 'home' ? ' class="active"' : '' ?>><?= function_exists('__') ? h(__('blog_nav_home')) : 'Home' ?></a>
            <a href="<?= h(path('blog.php')) ?>"<?= $blogNavActive === 'blog' ? ' class="active"' : '' ?>><?= function_exists('__') ? h(__('blog_nav_blog')) : 'Blog' ?></a>
            <a href="<?= h(path('help.php')) ?>"<?= $blogNavActive === 'help' ? ' class="active"' : '' ?>><?= function_exists('__') ? h(__('help_nav')) : 'Help' ?></a>
            <a href="<?= h(path('login.php')) ?>"><?= function_exists('__') ? h(__('nav_sign_in')) : 'Sign In' ?></a>
            <a href="<?= h(path('login.php')) ?>?mode=register" class="blog-nav-cta"><?= function_exists('__') ? h(__('nav_sign_up')) : 'Get Started' ?></a>
        </div>
    </div>
</nav>
