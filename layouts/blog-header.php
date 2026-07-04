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
    <link rel="icon" type="image/svg+xml" href="<?= h(path('assets/img/logo-icon.svg?v=3')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($jsonLd)): ?><script type="application/ld+json"><?= is_array($jsonLd) ? json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $jsonLd ?></script><?php endif; ?>
    <style>
        :root{--primary:#E30A17;--primary-dark:#B90812;--primary-soft:rgba(227,10,23,.08);--dark:#1a0a0e;--muted:#6b4a50;--light:#fdf9fa;--white:#fff;--border:#f0e6e8;--ease:cubic-bezier(0.16,1,0.3,1);}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--light);color:var(--dark);line-height:1.6;}
        a{text-decoration:none;color:inherit;}
        .blog-nav{background:rgba(255,255,255,.98);border-bottom:1px solid var(--border);padding:0 24px;position:sticky;top:0;z-index:100;}
        .blog-nav-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;min-height:64px;flex-wrap:wrap;gap:12px;}
        .blog-nav-logo{display:flex;align-items:center;gap:10px;color:var(--dark);font-family:'Syne',sans-serif;font-weight:800;font-size:18px;}
        .blog-nav-logo img{width:40px;height:40px;border-radius:10px;}
        .blog-nav-logo span{color:var(--primary);}
        .blog-nav-links{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
        .blog-nav-links a{padding:10px 16px;border-radius:10px;font-weight:600;font-size:14px;color:var(--dark);transition:background .2s,color .2s;}
        .blog-nav-links a:hover{background:var(--primary-soft);color:var(--primary);}
        .blog-nav-links a.active{background:var(--primary);color:var(--white);}
        .blog-main{max-width:900px;margin:0 auto;padding:32px 24px;min-height:60vh;}
        .blog-main h1{font-family:'Syne',sans-serif;font-size:clamp(1.75rem,4vw,2.25rem);margin-bottom:12px;}
        .blog-list{margin-top:24px;}
        .blog-card{background:var(--white);border-radius:16px;padding:24px;margin-bottom:20px;border:1px solid var(--border);transition:box-shadow .25s var(--ease),transform .25s var(--ease);}
        .blog-card:hover{box-shadow:0 12px 40px rgba(227,10,23,.1);transform:translateY(-2px);}
        .blog-card h2{font-family:'Syne',sans-serif;font-size:1.25rem;margin-bottom:8px;line-height:1.3;}
        .blog-card h2 a:hover{color:var(--primary);}
        .blog-card .meta{font-size:13px;color:var(--muted);margin-bottom:10px;}
        .blog-card .meta a{margin-right:12px;}
        .blog-card .meta a:hover{color:var(--primary);}
        .blog-card .excerpt{color:var(--muted);font-size:14px;line-height:1.6;}
        .blog-card .read-more{display:inline-block;margin-top:12px;font-weight:700;color:var(--primary);font-size:14px;}
        .blog-card .read-more:hover{text-decoration:underline;}
        .blog-tags{margin-top:16px;display:flex;flex-wrap:wrap;gap:8px;}
        .blog-tag{display:inline-block;padding:4px 12px;border-radius:20px;background:var(--primary-soft);color:var(--primary);font-size:12px;font-weight:600;}
        .blog-tag:hover{background:var(--primary);color:var(--white);}
        .blog-cats{margin-bottom:24px;display:flex;flex-wrap:wrap;gap:8px;}
        .blog-cats a{padding:8px 16px;border-radius:10px;background:var(--white);border:1px solid var(--border);font-size:13px;font-weight:600;color:var(--dark);}
        .blog-cats a:hover,.blog-cats a.active{background:var(--primary);color:var(--white);border-color:var(--primary);}
        .blog-pagination{margin-top:32px;display:flex;gap:8px;flex-wrap:wrap;}
        .blog-pagination a,.blog-pagination span{padding:10px 18px;border-radius:10px;background:var(--white);border:1px solid var(--border);font-weight:600;}
        .blog-pagination a:hover{background:var(--primary);color:var(--white);border-color:var(--primary);}
        .blog-pagination .current{background:var(--primary);color:var(--white);border-color:var(--primary);}
        .blog-footer{background:var(--dark);color:rgba(255,255,255,.7);padding:24px;text-align:center;font-size:14px;}
        .blog-footer a{color:rgba(255,255,255,.9);}
        .blog-footer a:hover{color:var(--white);}
    </style>
</head>
<body>
<nav class="blog-nav" role="navigation">
    <div class="blog-nav-inner">
        <a href="<?= h(path('home.php')) ?>" class="blog-nav-logo"><img src="<?= h(path('assets/img/logo-icon.svg?v=3')) ?>" alt=""> SMM <span>TURK</span></a>
        <div class="blog-nav-links">
            <a href="<?= h(path('home.php')) ?>"<?= $blogNavActive === 'home' ? ' class="active"' : '' ?>><?= function_exists('__') ? h(__('blog_nav_home')) : 'Home' ?></a>
            <a href="<?= h(path('blog.php')) ?>"<?= $blogNavActive === 'blog' ? ' class="active"' : '' ?>><?= function_exists('__') ? h(__('blog_nav_blog')) : 'Blog' ?></a>
            <a href="<?= h(path('help.php')) ?>"<?= $blogNavActive === 'help' ? ' class="active"' : '' ?>><?= function_exists('__') ? h(__('help_nav')) : 'Help' ?></a>
            <a href="<?= h(path('login.php')) ?>"><?= function_exists('__') ? h(__('nav_sign_in')) : 'Sign In' ?></a>
            <a href="<?= h(path('login.php')) ?>?mode=register"><?= function_exists('__') ? h(__('nav_sign_up')) : 'Sign Up' ?></a>
        </div>
    </div>
</nav>
<main class="blog-main" role="main">
