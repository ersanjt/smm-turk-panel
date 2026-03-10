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
$pageImg  = $pageImage ?? ($siteUrl ? $siteUrl . path('assets/img/logo-icon.svg?v=3') : path('assets/img/logo-icon.svg?v=3'));
$pageOgTitle = $pageTitle ?? 'Dashboard';
$geoRegion = defined('GEO_REGION') ? GEO_REGION : 'TR';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<meta property="og:locale" content="en_US">
<meta property="og:image:width" content="512">
<meta property="og:image:height" content="512">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= h($pageOgTitle) ?> — <?= h($siteName) ?>">
<meta name="twitter:description" content="<?= h($pageDesc) ?>">
<meta name="twitter:image" content="<?= h($pageImg) ?>">
<link rel="icon" type="image/svg+xml" href="<?= h(path('assets/img/logo-icon.svg?v=3')) ?>">
<link rel="apple-touch-icon" href="<?= h(path('assets/img/logo-icon.svg?v=3')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebApplication","name":"<?= h($siteName) ?>","url":"<?= h($siteUrl) ?>","description":"<?= h($pageDesc) ?>"}</script>
<style>
:root{--primary:#E30A17;--primary-dark:#B90812;--primary-light:#FF4757;--accent:#e63950;--bg:#fafafa;--sidebar-bg:#1a0a0e;--sidebar-text:#c4a5ab;--white:#fff;--border:#f0e6e8;--text:#1a0a0e;--text-muted:#6b4a50;--green:#10b981;--orange:#f59e0b;--red:#dc2626;--shadow:0 4px 24px rgba(227,10,23,.06);--glow:0 0 40px rgba(227,10,23,.08);--ease-spring:cubic-bezier(0.34,1.56,0.64,1);--ease-out:cubic-bezier(0.16,1,0.3,1);--motion-duration:0.35s}
@keyframes topbarShine{0%{opacity:.5}50%{opacity:1}100%{opacity:.5}}
@keyframes sidebarItemIn{from{opacity:0;transform:translateX(-8px)}to{opacity:1;transform:translateX(0)}}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;min-height:100dvh;overflow-x:hidden;-webkit-tap-highlight-color:transparent;width:100%;max-width:100vw}
a{text-decoration:none;color:inherit}
a:focus-visible,.icon-btn:focus-visible,.btn:focus-visible,.nav-item:focus-visible,.menu-toggle:focus-visible,.sidebar-close:focus-visible{outline:2px solid var(--primary);outline-offset:2px}
img{max-width:100%;height:auto}
.main{min-width:0;overflow-x:hidden}
.sidebar{width:240px;min-width:240px;background:linear-gradient(180deg,var(--sidebar-bg) 0%,#0f0608 100%);position:fixed;top:0;left:0;bottom:0;z-index:100;overflow-y:auto;overflow-x:hidden;display:flex;flex-direction:column;-webkit-overflow-scrolling:touch;padding-bottom:env(safe-area-inset-bottom);box-shadow:4px 0 24px rgba(0,0,0,.15)}
.sidebar-close{display:none;position:absolute;top:12px;right:12px;width:36px;height:36px;border-radius:10px;border:none;background:rgba(255,255,255,.08);color:rgba(255,255,255,.8);cursor:pointer;align-items:center;justify-content:center;transition:background .2s,color .2s;z-index:2}
.sidebar-close:hover{background:rgba(255,255,255,.15);color:#fff}
.sidebar-close svg{width:18px;height:18px}
.sidebar-mob-balance{display:none;flex-direction:row;align-items:center;gap:10px;padding:14px 18px;background:linear-gradient(135deg,rgba(227,10,23,.2),rgba(227,10,23,.12));border-bottom:1px solid rgba(255,255,255,.06);font-size:13px;color:#fff;font-weight:700;letter-spacing:.02em}
.sidebar-mob-balance svg{flex-shrink:0;opacity:.95}
.sidebar-logo{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,rgba(255,255,255,.04) 0%,transparent 100%);position:relative}
.sidebar-logo a{display:flex;flex-direction:row;align-items:center;justify-content:center;gap:10px;text-decoration:none;color:inherit;transition:opacity .2s;width:100%;min-width:0}
.sidebar-logo a:hover{opacity:.95}
.logo-icon{width:36px;height:36px;flex-shrink:0;border-radius:10px;display:flex;align-items:center;justify-content:center;overflow:hidden;transition:transform var(--motion-duration) var(--ease-spring),box-shadow var(--motion-duration) var(--ease-out)}
.sidebar-logo:hover .logo-icon{transform:scale(1.05);box-shadow:0 6px 18px rgba(227,10,23,.3)}
.logo-icon img{width:36px;height:36px;object-fit:contain;display:block}
.logo-text{font-family:'Syne',sans-serif;font-size:14px;font-weight:800;color:#fff;letter-spacing:-.03em;line-height:1.1;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.logo-text span{color:var(--primary-light);letter-spacing:-.02em}
.sidebar-user{padding:14px 16px;margin:12px 12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:14px;display:flex;flex-direction:row;align-items:center;gap:12px}
.user-avatar{width:44px;height:44px;min-width:44px;min-height:44px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;overflow:hidden;flex-shrink:0;box-shadow:0 4px 12px rgba(227,10,23,.25)}
.user-avatar img{width:100%;height:100%;object-fit:cover;display:block}
.sidebar-user-info{display:flex;flex-direction:column;gap:2px;min-width:0;flex:1}
.user-name{color:#fff;font-size:13px;font-weight:700;letter-spacing:.01em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.user-bal{color:rgba(255,255,255,.75);font-size:11px;font-weight:600;display:flex;align-items:center;gap:6px}
.user-bal strong{color:var(--primary-light);font-weight:700}
.user-status{display:inline-flex;align-items:center;align-self:flex-start;margin-top:6px;padding:3px 8px;border-radius:20px;font-size:9px;font-weight:700;letter-spacing:.6px;text-transform:uppercase}
.sidebar-user .user-status{background:rgba(227,10,23,.22);color:var(--primary-light);border:1px solid rgba(227,10,23,.35)}
.sidebar-nav{padding:12px 14px;flex:1}
.nav-label{font-size:10px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:rgba(255,255,255,.35);padding:0 12px;margin:20px 0 8px;display:flex;align-items:center;gap:8px}
.nav-label::before{content:'';width:4px;height:4px;border-radius:50%;background:var(--primary);opacity:.7}
.nav-item{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;cursor:pointer;color:var(--sidebar-text);font-size:13px;font-weight:500;transition:background .2s,color .2s,transform .2s ease;margin-bottom:4px;border:1px solid transparent}
.nav-item:hover{background:rgba(255,255,255,.06);color:#fff;transform:translateX(4px);border-color:rgba(255,255,255,.04)}
.nav-item.active{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-color:rgba(255,255,255,.1);box-shadow:0 4px 12px rgba(227,10,23,.25)}
.nav-item.nav-item-cta{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;border:none;box-shadow:0 4px 14px rgba(227,10,23,.35);margin-bottom:8px}
.nav-item.nav-item-cta:hover{background:linear-gradient(135deg,var(--primary-dark),#9a0610);transform:translateY(-2px);box-shadow:0 6px 20px rgba(227,10,23,.4)}
.nav-item.nav-item-cta.active{box-shadow:0 4px 14px rgba(227,10,23,.4)}
.nav-item svg{width:18px;height:18px;flex-shrink:0;transition:transform .2s}
.nav-item:hover svg{transform:scale(1.08)}
.sidebar-nav-footer{margin-top:auto;padding-top:16px;border-top:1px solid rgba(255,255,255,.06)}
.sidebar-nav-footer .nav-item{color:rgba(255,107,107,.95)}
.sidebar-nav-footer .nav-item:hover{background:rgba(255,107,107,.12);color:#ff6b6b}
.main{margin-left:240px;flex:1;display:flex;flex-direction:column;min-height:100vh;min-height:100dvh;min-width:0;max-width:100%}
.topbar{background:linear-gradient(180deg,rgba(255,255,255,.97) 0%,rgba(255,252,253,.95) 100%);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid var(--border);box-shadow:0 1px 0 rgba(255,255,255,.6) inset,0 4px 20px rgba(26,10,14,.04);padding:0 24px;padding-left:max(24px,env(safe-area-inset-left));padding-right:max(24px,env(safe-area-inset-right));padding-top:max(0,env(safe-area-inset-top));min-height:60px;height:auto;display:flex;align-items:center;gap:14px;position:sticky;top:0;z-index:50;flex-wrap:wrap}
.topbar::after{content:'';position:absolute;left:0;right:0;bottom:0;height:2px;background:linear-gradient(90deg,transparent,var(--primary) 30%,var(--primary-dark) 50%,var(--primary) 70%,transparent);background-size:200% 100%;opacity:.6;pointer-events:none;animation:topbarShine 4s ease-in-out infinite}
.topbar-left{display:flex;align-items:center;gap:10px;flex:1;min-width:0}
.topbar-mob-logo{display:none;flex-direction:row;align-items:center;gap:8px;text-decoration:none;color:var(--text);flex-shrink:0;min-width:0}
.topbar-mob-logo img{width:32px;height:32px;border-radius:10px;object-fit:contain;flex-shrink:0}
.topbar-mob-logo .topbar-mob-logo-text{font-family:'Syne',sans-serif;font-size:14px;font-weight:800;letter-spacing:-.03em;line-height:1.1;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.topbar-mob-logo .topbar-mob-logo-text b{color:var(--primary)}
.topbar-badge{display:inline-flex;align-items:center;padding:5px 12px;border-radius:20px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.8px;background:linear-gradient(145deg,var(--primary),var(--primary-dark));color:#fff;margin-right:8px;box-shadow:0 2px 8px rgba(227,10,23,.25)}
.topbar-stats{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.stat-pill{display:flex;align-items:center;gap:8px;padding:8px 14px;background:rgba(255,255,255,.8);border-radius:12px;font-size:12px;font-weight:600;border:1px solid var(--border);box-shadow:0 1px 2px rgba(0,0,0,.03)}
.stat-pill .stat-label{font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-right:4px;letter-spacing:.5px}
.stat-icon{width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff}
.si-blue{background:var(--primary)}.si-green{background:var(--green)}.si-orange{background:var(--orange)}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:8px;flex-shrink:0}
.icon-btn{width:36px;height:36px;min-width:36px;min-height:36px;border-radius:10px;background:rgba(255,255,255,.9);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-muted);transition:all .2s;font-size:15px;flex-shrink:0;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.icon-btn svg{width:18px;height:18px;flex-shrink:0}
.icon-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary);box-shadow:0 4px 12px rgba(227,10,23,.25)}
.content{padding:24px;flex:1;min-width:0;max-width:100%;overflow-x:hidden}
.content-inner{max-width:1400px;margin:0 auto}
.card{background:#fff;border-radius:16px;padding:22px;box-shadow:var(--shadow);border:1px solid var(--border);transition:transform var(--motion-duration) var(--ease-spring),box-shadow var(--motion-duration) var(--ease-out),border-color var(--motion-duration) ease;overflow:hidden}
.card:hover{box-shadow:0 16px 48px rgba(227,10,23,.1);transform:translateY(-4px)}
.stat-pill,.icon-btn{transition:transform var(--motion-duration) var(--ease-spring),box-shadow var(--motion-duration) var(--ease-out)}
.stat-pill:hover,.icon-btn:hover{transform:scale(1.05)}
.card-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:18px}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:6px;letter-spacing:.3px;text-transform:uppercase}
.form-control{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;color:var(--text);background:var(--bg);outline:none;transition:border-color .2s}
.form-control:focus{border-color:var(--primary);background:#fff}
.btn{padding:10px 22px;border-radius:10px;font-family:'Syne',sans-serif;font-size:14px;font-weight:700;cursor:pointer;border:none;transition:transform var(--motion-duration) var(--ease-spring),box-shadow var(--motion-duration) var(--ease-out),background var(--motion-duration) ease}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:var(--primary-dark);transform:translateY(-3px) scale(1.02);box-shadow:0 8px 28px rgba(227,10,23,.4)}
.btn-danger{background:var(--red);color:#fff}
.btn-success{background:var(--green);color:#fff}
.btn-block{width:100%}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:18px;font-weight:500}
.alert-success{background:#e8ffe8;color:#007700;border:1px solid #b3ffb3}
.alert-error{background:#ffe8e8;color:#cc0000;border:1px solid #ffb3b3}
.alert-info{background:#fff0f1;color:var(--primary);border:1px solid #ffc4c8}
.alert-warning{background:#fff3e0;color:#cc6600;border:1px solid #ffcc80}
.table{width:100%;border-collapse:collapse;font-size:13px}
.table th{background:var(--bg);padding:11px 14px;text-align:left;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted);border-bottom:1.5px solid var(--border)}
.table td{padding:13px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
.table tr:hover td{background:#fff8f9}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge-blue{background:#ffe8e8;color:var(--primary)}
.badge-green{background:#e8ffe8;color:var(--green)}
.badge-orange{background:#fff3e0;color:var(--orange)}
.badge-red{background:#ffe8e8;color:var(--red)}
.badge-gray{background:#f0f0f0;color:#666}
.status-Completed{background:#e8ffe8;color:var(--green)}
.status-Pending{background:#fff3e0;color:var(--orange)}
.status-Processing,.status-In-progress{background:#ffe8e8;color:var(--primary)}
.status-Partial{background:#fff3e0;color:var(--orange)}
.status-Cancelled{background:#ffe8e8;color:var(--red)}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.stat-card{background:#fff;border-radius:14px;padding:20px;box-shadow:var(--shadow);border:1px solid var(--border)}
.stat-card .sc-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:10px}
.stat-card .sc-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted);margin-bottom:5px}
.stat-card .sc-value{font-family:'Syne',sans-serif;font-size:22px;font-weight:800}
.footer{background:var(--sidebar-bg);color:rgba(255,255,255,.5);padding:14px 24px;font-size:12px;padding-left:max(24px,env(safe-area-inset-left));padding-right:max(24px,env(safe-area-inset-right));padding-bottom:max(14px,env(safe-area-inset-bottom))}
.footer-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.footer-copy{color:rgba(255,255,255,.5);word-break:break-word}
.footer-contact{color:rgba(255,255,255,.7);text-decoration:none;word-break:break-all}
.footer-contact:hover{color:#fff}
.footer-ticket{background:var(--orange);color:#fff;padding:8px 18px;border-radius:10px;font-weight:700;text-decoration:none;font-size:12px;transition:all .2s;display:inline-block;min-height:44px;line-height:28px;box-sizing:border-box}
.footer-ticket:hover{background:#fbbf24;color:#1a0a0e;transform:translateY(-1px)}
@media(max-width:768px){.footer{padding:20px 16px;padding-left:max(16px,env(safe-area-inset-left));padding-right:max(16px,env(safe-area-inset-right));padding-bottom:max(24px,calc(20px + env(safe-area-inset-bottom)))}.footer-inner{flex-direction:column;text-align:center;gap:16px}.footer-copy{order:1;font-size:13px;line-height:1.5}.footer-contact{order:2;font-size:13px;padding:10px 0;min-height:44px;display:inline-flex;align-items:center;justify-content:center}.footer-ticket{order:3;min-height:48px;padding:12px 24px;font-size:13px;border-radius:12px}}
@media(max-width:380px){.footer{padding:18px 12px}.footer-inner{gap:14px}.footer-ticket{width:100%;text-align:center;max-width:280px;margin:0 auto}}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes softPulse{0%,100%{opacity:1}50%{opacity:.92}}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-12px)}to{opacity:1;transform:translateX(0)}}
.content>*{animation:fadeIn .45s ease both}
.content>*:nth-child(1){animation-delay:0s}
.content>*:nth-child(2){animation-delay:.06s}
.content>*:nth-child(3){animation-delay:.12s}
.content>*:nth-child(4){animation-delay:.18s}
.content>*:nth-child(5){animation-delay:.24s}
.content>*:nth-child(6){animation-delay:.3s}
.content>*:nth-child(7){animation-delay:.36s}
.content [data-reveal]{opacity:0;transform:translateY(24px);transition:opacity .5s ease, transform .5s ease}
.content [data-reveal].revealed{opacity:1;transform:translateY(0)}
.btn:active{transform:translateY(0)}
@media(prefers-reduced-motion:reduce){
.content>*,.card,.nav-item,.stat-pill,.icon-btn,.btn{animation:none!important;transition:none!important}
.content [data-reveal]{opacity:1;transform:none}
.card:hover,.nav-item:hover,.sidebar-logo:hover .logo-icon{transform:none}
.topbar::after{animation:none}
}
.form-control{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;color:var(--text);background:var(--bg);outline:none;transition:border-color .2s, box-shadow .2s}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;margin-bottom:16px;border-radius:12px;border:1px solid var(--border)}
.table-wrap .table{min-width:500px}
.content .card:has(.table){overflow-x:auto!important;-webkit-overflow-scrolling:touch}
.content .card:has(.table) .table{min-width:480px}
.table th,.table td{word-break:break-word}
.svc-table th,.svc-table td{word-break:break-word}
.menu-toggle{display:none;border:1px solid var(--border)}
.menu-toggle svg{width:20px;height:20px;display:block}
@media(max-width:768px){
.topbar-mob-logo{display:flex}
.topbar-mob-logo img{width:30px;height:30px;border-radius:8px}
.topbar-mob-logo .topbar-mob-logo-text{font-size:13px}
.topbar-left{gap:8px}
}
@media(max-width:480px){
.topbar-mob-logo img{width:28px;height:28px}
.topbar-mob-logo .topbar-mob-logo-text{font-size:12px}
.topbar-mob-logo{gap:6px}
}
@media(max-width:992px){
.grid2,.grid3,.grid4{grid-template-columns:1fr 1fr}
.content{padding:16px;padding-left:max(16px,env(safe-area-inset-left));padding-right:max(16px,env(safe-area-inset-right))}
.stat-card .sc-value{font-size:18px}
.admin-grid{grid-template-columns:repeat(2,1fr);gap:12px}
.quick-actions{gap:8px}
}
/* Mobile bottom nav (dashboard) - only on small screens */
.mob-bottom-nav{display:none;position:fixed;bottom:0;left:0;right:0;z-index:95;background:rgba(255,255,255,.98);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-top:1px solid var(--border);padding:6px 4px;padding-left:max(6px,env(safe-area-inset-left));padding-right:max(6px,env(safe-area-inset-right));padding-bottom:max(10px,env(safe-area-inset-bottom));justify-content:space-around;align-items:stretch;gap:4px;box-shadow:0 -4px 24px rgba(0,0,0,.08)}
.mob-bottom-nav a,.mob-bottom-nav button{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:8px 4px;min-height:56px;min-width:0;border-radius:12px;font-size:10px;font-weight:600;color:var(--text-muted);text-decoration:none;background:transparent;border:none;cursor:pointer;transition:background .2s,color .2s,transform .2s;flex:1;max-width:84px;box-sizing:border-box;-webkit-tap-highlight-color:transparent}
.mob-bottom-nav a:hover,.mob-bottom-nav button:hover{background:rgba(227,10,23,.08);color:var(--primary)}
.mob-bottom-nav a:active,.mob-bottom-nav button:active{transform:scale(0.96)}
.mob-bottom-nav a.active{background:linear-gradient(180deg,rgba(227,10,23,.14) 0%,rgba(227,10,23,.08) 100%);color:var(--primary);border-radius:12px;font-weight:700}
.mob-bottom-nav a.active .mob-nav-icon,.mob-bottom-nav a.active .mob-nav-icon svg{color:inherit}
.mob-bottom-nav button{color:inherit}
.mob-bottom-nav .mob-nav-icon{font-size:20px;line-height:1;display:flex;align-items:center;justify-content:center}
.mob-bottom-nav .mob-nav-icon svg{width:24px;height:24px;flex-shrink:0}
.mob-bottom-nav a.active .mob-nav-icon,.mob-bottom-nav a.active .mob-nav-icon svg{color:var(--primary)}
@media(max-width:768px){
.mob-bottom-nav{display:flex}
.main{padding-bottom:max(80px,calc(72px + env(safe-area-inset-bottom)))}
.footer{padding-bottom:max(20px,env(safe-area-inset-bottom))}
.footer-inner .footer-ticket{min-height:48px;padding:12px 20px;font-size:13px}
.sidebar{width:min(280px,88vw);transform:translateX(-100%);transition:transform .3s var(--ease-out);box-shadow:4px 0 32px rgba(0,0,0,.2);padding-left:env(safe-area-inset-left);padding-top:env(safe-area-inset-top)}
body.sidebar-open .sidebar{transform:translateX(0)}
body.sidebar-open .sidebar-overlay{opacity:1;pointer-events:auto}
body.sidebar-open{overflow:hidden;touch-action:none}
.sidebar-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);z-index:99;opacity:0;pointer-events:none;transition:opacity .25s;cursor:pointer}
.sidebar .nav-item{min-height:48px;padding:12px 14px;font-size:14px}
.sidebar-close{display:flex}
.sidebar-mob-balance{display:flex}
.main{margin-left:0}
.menu-toggle{display:flex;align-items:center;justify-content:center;width:44px;height:44px;min-width:44px;min-height:44px;border:none;background:var(--bg);border-radius:12px;cursor:pointer;color:var(--text);margin-right:4px;transition:background .2s,color .2s}
.menu-toggle:hover{background:var(--primary);color:#fff}
.menu-toggle svg{width:22px;height:22px}
.topbar{padding:10px 16px;padding-left:max(16px,env(safe-area-inset-left));padding-right:max(16px,env(safe-area-inset-right));padding-top:max(10px,env(safe-area-inset-top));flex-wrap:wrap;gap:8px;min-height:56px}
.topbar-left{flex:0 1 auto;min-width:0}
.topbar .stat-pill{font-size:11px;padding:6px 10px}
.topbar .topbar-stats .stat-pill:nth-child(n+3){display:none}
.topbar-right{margin-left:auto;width:auto;justify-content:flex-end;flex-wrap:wrap;gap:6px}
.topbar-right .icon-btn{width:40px;height:40px;min-width:40px;min-height:40px;flex-shrink:0}
.topbar-right .icon-btn svg{width:20px;height:20px}
.topbar-right .btn{padding:8px 14px;font-size:12px;min-height:40px;flex-shrink:0}
.grid2,.grid3,.grid4{grid-template-columns:1fr}
.admin-grid{grid-template-columns:1fr;gap:12px}
.admin-card{flex-direction:row;padding:16px;gap:12px}
.admin-card .ac-icon{width:44px;height:44px;font-size:20px}
.admin-card .ac-info .ac-value{font-size:20px}
.quick-actions{flex-direction:column;align-items:stretch}
.quick-actions .qa-btn,.quick-actions a.qa-btn{min-height:44px;justify-content:center}
.card{padding:16px}
.content .form-control,.content select.form-control{width:100%!important;max-width:100%!important;min-width:0!important}
.form-control{min-height:44px;font-size:16px}
.form-control:focus{font-size:16px}
.btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.table th,.table td{padding:10px 12px;font-size:12px}
.svc-table th,.svc-table td{padding:10px 12px;font-size:12px}
.stat-card{padding:16px}
.stat-card .sc-value{font-size:20px}
.alert{padding:14px;font-size:13px}
.order-form-row{flex-direction:column;align-items:stretch}
.order-form-row form{max-width:100%;flex-wrap:wrap}
.order-form-row form .form-control{min-width:0}
.order-form-row form .btn{flex:1 1 auto;min-width:120px}
.order-form-row .form-control,.order-form-row select{min-width:0;width:100%}
.order-form-row label.checkbox-label{min-height:44px;align-items:center}
.order-tabs{overflow-x:auto;-webkit-overflow-scrolling:touch;flex-wrap:nowrap;padding-bottom:4px}
.order-tab{white-space:nowrap;padding:10px 14px;font-size:12px}
.platform-icons{gap:6px}
.platform-btn{width:40px;height:40px;min-width:40px;min-height:40px}
.services-toolbar{flex-direction:column;align-items:stretch}
.services-search-row .form-control,.services-search-row .filter-select{max-width:100%;min-width:0}
.ticket-search-wrap{flex-wrap:wrap}
.ticket-search-wrap input{min-width:0;flex:1 1 100%}
.ticket-search-wrap button{min-height:44px}
.ticket-cats label{padding:10px 14px;min-height:44px;box-sizing:border-box;display:inline-flex;align-items:center}
.add-funds-tabs{overflow-x:auto;-webkit-overflow-scrolling:touch}
.add-funds-tabs a{white-space:nowrap;padding:12px 16px}
.wallet-box code{font-size:11px}
.wallet-box .btn{min-height:44px}
.badge{padding:6px 12px;min-height:32px;display:inline-flex;align-items:center}
input[type="file"]{min-height:44px}
textarea.form-control{min-height:120px}
}
@media(max-width:480px){
.content{padding:12px;padding-left:max(12px,env(safe-area-inset-left));padding-right:max(12px,env(safe-area-inset-right))}
.topbar{padding:8px 12px;padding-left:max(12px,env(safe-area-inset-left));padding-right:max(12px,env(safe-area-inset-right));min-height:52px}
.topbar .topbar-stats .stat-pill:nth-child(2){display:none!important}
.content .form-control[style*="width"],.content select.form-control[style*="width"],.card .form-control[style*="width"],.card select[style*="width"]{width:100%!important;max-width:100%!important;min-width:0!important}
.btn{padding:10px 16px;font-size:13px}
.topbar-right .icon-btn{width:36px;height:36px;min-width:36px;min-height:36px}
.card{padding:14px}
.table th,.table td{padding:8px 10px;font-size:11px}
.svc-table th,.svc-table td{padding:8px 10px;font-size:11px}
.table-wrap .table{min-width:400px}
.form-control{padding:10px 12px}
.price-box{flex-wrap:wrap;gap:8px}
.price-box .amt{font-size:18px}
.order-tab{padding:8px 12px;font-size:11px}
.platform-btn{width:36px;height:36px;min-width:36px;min-height:36px;font-size:12px}
.ptab{padding:6px 12px;font-size:11px}
.ticket-pagination a,.ticket-pagination span{padding:10px 14px;min-height:44px;display:inline-flex;align-items:center}
.admin-card{flex-direction:column;text-align:center;padding:14px}
.admin-card .ac-icon{margin:0 auto}
.admin-card .ac-info .ac-value{font-size:18px}
}
@media(max-width:360px){
.content{padding:10px}
.card{padding:12px}
.topbar-badge{font-size:10px;padding:3px 8px}
.admin-card .ac-info .ac-value{font-size:16px}
}
@media(max-width:768px){
.order-grid{grid-template-columns:1fr}
}
@media(max-width:992px){
.order-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
<aside class="sidebar" id="sidebar" role="navigation">
  <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
  <div class="sidebar-mob-balance" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>Balance: $<?= $balance ?></div>
  <div class="sidebar-logo">
    <a href="<?= h(path('index.php')) ?>">
      <span class="logo-icon"><img src="<?= h(path('assets/img/logo-icon.svg?v=3')) ?>" alt="" width="36" height="36"></span>
      <span class="logo-text">SMM <span>TURK</span></span>
    </a>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?php if (!empty($user['avatar']) && trim($user['avatar']) !== ''): ?><img src="<?= h(path('uploads/' . trim($user['avatar']))) ?>" alt="" loading="lazy"><?php else: ?><?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?><?php endif; ?></div>
    <div class="sidebar-user-info">
      <div class="user-name"><?= h($user['username'] ?? '') ?></div>
      <div class="user-bal">Balance <strong>$<?= $balance ?></strong></div>
      <span class="user-status"><?= h(ucfirst($user['status'] ?? 'active')) ?></span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Orders</div>
    <a class="nav-item nav-item-cta <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= h(path('index.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      New Order
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
      My Orders
    </a>
    <div class="nav-label">Account</div>
    <a class="nav-item <?= $currentPage === 'account-settings' ? 'active' : '' ?>" href="<?= h(path('account-settings.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      Account Settings
    </a>
    <a class="nav-item <?= $currentPage === 'add-funds' ? 'active' : '' ?>" href="<?= h(path('add-funds.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
      Add Funds
    </a>
    <div class="nav-label">Support & Info</div>
    <a class="nav-item" href="<?= h(path('home.php')) ?>#faq">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      FAQ
    </a>
    <a class="nav-item <?= $currentPage === 'api-page' ? 'active' : '' ?>" href="<?= h(path('api-page.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      API
    </a>
    <a class="nav-item <?= $currentPage === 'tickets' ? 'active' : '' ?>" href="<?= h(path('tickets.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Tickets
    </a>
    <a class="nav-item <?= $currentPage === 'terms' ? 'active' : '' ?>" href="<?= h(path('terms.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Terms
    </a>
    <a class="nav-item <?= $currentPage === 'affiliates' ? 'active' : '' ?>" href="<?= h(path('affiliates.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Affiliates
    </a>
    <a class="nav-item <?= $currentPage === 'child-panel' ? 'active' : '' ?>" href="<?= h(path('child-panel.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Child Panel
    </a>
    <?php if ($auth->isAdmin()): ?>
    <div class="nav-label">Admin</div>
    <a class="nav-item <?= strpos($currentPage, 'admin') !== false ? 'active' : '' ?>" href="<?= h(path('admin/index.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      Admin Panel
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
      <a href="<?= h(path('index.php')) ?>" class="topbar-mob-logo" aria-label="<?= h($siteName) ?>"><img src="<?= h(path('assets/img/logo-icon.svg?v=3')) ?>" alt="" width="32" height="32"><span class="topbar-mob-logo-text">SMM <b>TURK</b></span></a>
      <span class="topbar-badge"><?= ($user['status'] ?? 'active') === 'active' ? 'Active' : 'New' ?></span>
      <div class="topbar-stats">
        <div class="stat-pill"><span class="stat-label">Balance</span> $<?= $balance ?></div>
        <div class="stat-pill"><span class="stat-label">Spent</span> $<?= $spent ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <a href="<?= h(path('services.php')) ?>" class="icon-btn" title="Search services"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></a>
      <a href="<?= h(path('add-funds.php')) ?>" class="icon-btn" title="Add Funds"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/></svg></a>
      <a href="<?= h(path('account-settings.php')) ?>" class="icon-btn" title="Account Settings"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></a>
      <a href="<?= h(path('tickets.php')) ?>" class="icon-btn" title="Support"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></a>
      <a href="<?= h(path('logout.php')) ?>" class="icon-btn" title="Logout"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></a>
      <a href="<?= h(path('add-funds.php')) ?>" class="btn btn-primary" style="padding:7px 16px;font-size:12px;">+ Add Funds</a>
    </div>
  </div>
  <div class="content" role="main">
    <div class="content-inner">
<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>
