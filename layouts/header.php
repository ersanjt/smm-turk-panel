<?php
$user    = $auth->getCurrentUser();
$flash   = getFlash();
// Always fetch latest balance/spent from DB so sidebar and topbar show current values
$balance = '0.000';
$spent   = '0.000';
if ($user && !empty($user['id'])) {
    $db = Database::getInstance();
    $row = $db->fetch("SELECT balance, spent FROM users WHERE id = ?", [(int)$user['id']]);
    if ($row !== null) {
        $balance = number_format((float)$row['balance'], 3);
        $spent   = number_format((float)$row['spent'], 3);
    } else {
        $balance = number_format((float)($user['balance'] ?? 0), 3);
        $spent   = number_format((float)($user['spent'] ?? 0), 3);
    }
} elseif ($user) {
    $balance = number_format((float)($user['balance'] ?? 0), 3);
    $spent   = number_format((float)($user['spent'] ?? 0), 3);
}
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
$siteUrl  = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$canonicalPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$canonicalUrl = $siteUrl ? $siteUrl . $canonicalPath : '';
$pageDesc = $pageDescription ?? 'SMM Turk — Cheapest SMM panel. Instagram, YouTube, TikTok growth. Reseller API, crypto deposits, 24/7 support.';
$pageImg  = $pageImage ?? og_image_url();
$pageOgTitle = $pageTitle ?? 'Dashboard';
$geoRegion = defined('GEO_REGION') ? GEO_REGION : 'TR';
$seoNoindex = $seoNoindex ?? true;
$dashLang = $_SESSION['lang'] ?? 'en';
if (!in_array($dashLang, ['en', 'tr', 'de', 'fr'], true)) {
    $dashLang = 'en';
}
$ogLocale = $dashLang === 'tr' ? 'tr_TR' : ($dashLang === 'de' ? 'de_DE' : ($dashLang === 'fr' ? 'fr_FR' : 'en_US'));
?>
<!DOCTYPE html>
<html lang="<?= h($dashLang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ($seoNoindex): ?><meta name="robots" content="noindex, follow"><?php endif; ?>
<title><?= h($pageTitle ?? 'Dashboard') ?> — <?= h($siteName) ?></title>
<meta name="description" content="<?= h($pageDesc) ?>">
<meta name="theme-color" content="#E30A17">
<meta name="geo.region" content="<?= h($geoRegion) ?>">
<meta name="geo.country" content="<?= h($geoRegion) ?>">
<?php if ($siteUrl !== '' && $canonicalUrl !== ''): ?>
<link rel="canonical" href="<?= h($canonicalUrl) ?>">
<?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= h($siteName) ?>">
<meta property="og:title" content="<?= h($pageOgTitle) ?> — <?= h($siteName) ?>">
<meta property="og:description" content="<?= h($pageDesc) ?>">
<meta property="og:url" content="<?= h($canonicalUrl) ?>">
<meta property="og:image" content="<?= h($pageImg) ?>">
<meta property="og:locale" content="<?= h($ogLocale) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($pageOgTitle) ?> — <?= h($siteName) ?>">
<meta name="twitter:description" content="<?= h($pageDesc) ?>">
<meta name="twitter:image" content="<?= h($pageImg) ?>">
<link rel="icon" type="image/svg+xml" href="<?= h(path('assets/img/logo-icon.svg?v=5')) ?>">
<link rel="apple-touch-icon" href="<?= h(path('assets/img/logo-icon.svg?v=5')) ?>">
<link rel="manifest" href="<?= h(path('manifest.php')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= h(asset_url('assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= h(asset_url('assets/css/ui-pro.css')) ?>">
<link rel="stylesheet" href="<?= h(asset_url('assets/css/panel-follows.css')) ?>">
<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebApplication","name":"<?= h($siteName) ?>","url":"<?= h($siteUrl) ?>","description":"<?= h($pageDesc) ?>"}</script>
</head>
<body class="panel-follows" data-sw="<?= h(path('pwa-sw.php')) ?>" data-sw-scope="<?= h(base_path() !== '' ? base_path() . '/' : '/') ?>">
<script>(function(){var k='smmturk_theme',d=localStorage.getItem(k)==='dark'||(!localStorage.getItem(k)&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);if(d)document.body.classList.add('theme-dark');})();</script>

<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
<aside class="sidebar" id="sidebar" role="navigation">
  <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
  <div class="sidebar-mob-balance" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>Balance: $<?= $balance ?></div>
  <div class="sidebar-logo">
    <a href="<?= h(path('index.php')) ?>">
      <span class="logo-icon"><img src="<?= h(path('assets/img/logo-icon.svg?v=5')) ?>" alt="" width="36" height="36"></span>
      <span class="logo-text">SMM <span>TURK</span></span>
    </a>
  </div>
  <div class="sidebar-welcome">
    <div class="user-avatar"><?php if (!empty($user['avatar']) && trim($user['avatar']) !== ''): ?><img src="<?= h(path('uploads/' . trim($user['avatar']))) ?>" alt="" loading="lazy"><?php else: ?><?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?><?php endif; ?></div>
    <div class="sidebar-welcome-text">
      <div class="welcome-label">Welcome</div>
      <div class="welcome-name"><?= h($user['username'] ?? '') ?></div>
    </div>
  </div>
  <a href="<?= h(path('account-settings.php') . '#profile-photo') ?>" class="sidebar-user sidebar-user-link" title="Account settings" aria-label="Open account settings">
    <div class="user-avatar"><?php if (!empty($user['avatar']) && trim($user['avatar']) !== ''): ?><img src="<?= h(path('uploads/' . trim($user['avatar']))) ?>" alt="" loading="lazy"><?php else: ?><?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?><?php endif; ?></div>
    <div class="sidebar-user-info">
      <div class="user-name"><?= h($user['username'] ?? '') ?></div>
      <div class="user-bal">Balance <strong>$<?= $balance ?></strong></div>
      <span class="user-status"><?= h(ucfirst($user['status'] ?? 'active')) ?></span>
    </div>
  </a>
  <nav class="sidebar-nav">
    <a class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= h(path('index.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      New Order
    </a>
    <a class="nav-item <?= $currentPage === 'child-panel' ? 'active' : '' ?>" href="<?= h(path('child-panel.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      Child Panel
    </a>
    <a class="nav-item <?= $currentPage === 'mass-order' ? 'active' : '' ?>" href="<?= h(path('mass-order.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Mass Order
    </a>
    <a class="nav-item <?= $currentPage === 'services' ? 'active' : '' ?>" href="<?= h(path('services.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      Services
    </a>
    <a class="nav-item <?= $currentPage === 'orders' ? 'active' : '' ?>" href="<?= h(path('orders.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Orders
    </a>
    <a class="nav-item nav-item-funds <?= $currentPage === 'add-funds' ? 'active' : '' ?>" href="<?= h(path('add-funds.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
      Add Funds
    </a>
    <a class="nav-item <?= $currentPage === 'help' ? 'active' : '' ?>" href="<?= h(path('help.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      FAQ
    </a>
    <a class="nav-item <?= $currentPage === 'api-page' ? 'active' : '' ?>" href="<?= h(path('api-page.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      API
    </a>
    <a class="nav-item <?= $currentPage === 'tickets' || $currentPage === 'ticket' ? 'active' : '' ?>" href="<?= h(path('tickets.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Tickets
    </a>
    <a class="nav-item <?= $currentPage === 'affiliates' ? 'active' : '' ?>" href="<?= h(path('affiliates.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      Affiliates
    </a>
    <a class="nav-item <?= $currentPage === 'blog' || $currentPage === 'blog-post' ? 'active' : '' ?>" href="<?= h(path('blog.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Blog
    </a>
    <a class="nav-item <?= $currentPage === 'account-settings' ? 'active' : '' ?>" href="<?= h(path('account-settings.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      Settings
    </a>
    <a class="nav-item <?= $currentPage === 'terms' ? 'active' : '' ?>" href="<?= h(path('terms.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Terms
    </a>
    <?php if ($auth->isAdmin()): ?>
    <div class="nav-label">Admin</div>
    <a class="nav-item <?= strpos($currentPage, 'admin') !== false && $currentPage !== 'admin-blog' && $currentPage !== 'admin-blog-edit' ? 'active' : '' ?>" href="<?= h(path('admin/index.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      Admin Panel
    </a>
    <a class="nav-item <?= $currentPage === 'admin-blog' || $currentPage === 'admin-blog-edit' ? 'active' : '' ?>" href="<?= h(path('admin/admin-blog.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Blog
    </a>
    <?php endif; ?>
    <div class="sidebar-nav-footer">
    <a class="nav-item" href="<?= h(path('logout.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
    </div>
  </nav>
</aside>

<main class="main" id="main-dashboard">
  <div class="topbar">
    <div class="topbar-left">
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <a href="<?= h(path('index.php')) ?>" class="topbar-mob-logo" aria-label="<?= h($siteName) ?>"><img src="<?= h(path('assets/img/logo-icon.svg?v=5')) ?>" alt="" width="32" height="32"><span class="topbar-mob-logo-text">SMM <b>TURK</b></span></a>
      <span class="topbar-badge"><?= ($user['status'] ?? 'active') === 'active' ? 'Active' : 'New' ?></span>
      <div class="topbar-stats">
        <div class="topbar-stat-card">
          <span class="tsc-icon tsc-icon-status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <span><span class="tsc-label">Status</span><span class="tsc-value"><?= ($user['status'] ?? 'active') === 'active' ? 'Active' : 'New' ?></span></span>
        </div>
        <div class="topbar-stat-card">
          <span class="tsc-icon tsc-icon-balance"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span>
          <span><span class="tsc-label">Balance</span><span class="tsc-value">$<?= $balance ?></span></span>
        </div>
        <div class="topbar-stat-card">
          <span class="tsc-icon tsc-icon-spent"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
          <span><span class="tsc-label">Spent</span><span class="tsc-value">$<?= $spent ?></span></span>
        </div>
        <div class="stat-pill"><span class="stat-label">Balance</span> $<?= $balance ?></div>
        <div class="stat-pill"><span class="stat-label">Spent</span> $<?= $spent ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <button type="button" class="theme-toggle-btn" id="dashThemeToggle" aria-label="Toggle dark mode" title="Toggle theme">
        <span class="theme-icon theme-icon-light" aria-hidden="true"><?= icon('sun', 18) ?></span>
        <span class="theme-icon theme-icon-dark" aria-hidden="true"><?= icon('moon', 18) ?></span>
      </button>
      <a href="<?= h(path('services.php')) ?>" class="icon-btn" title="Search services"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></a>
      <a href="<?= h(path('add-funds.php')) ?>" class="icon-btn" title="Add Funds"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/></svg></a>
      <a href="<?= h(path('account-settings.php')) ?>" class="icon-btn" title="Account Settings"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></a>
      <a href="<?= h(path('tickets.php')) ?>" class="icon-btn" title="Support"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></a>
      <a href="<?= h(path('logout.php')) ?>" class="icon-btn" title="Logout"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></a>
      <a href="<?= h(path('add-funds.php')) ?>" class="btn btn-primary btn-sm<?= ((float)str_replace(',', '', $balance) <= 0) ? ' btn-pulse-funds' : '' ?>">+ Add Funds</a>
    </div>
  </div>
  <div class="content">
    <div class="content-inner">
<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>" role="alert"><?= h($flash['message']) ?></div>
<?php endif; ?>
