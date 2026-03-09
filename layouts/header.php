<?php
$user    = $auth->getCurrentUser();
$flash   = getFlash();
$balance = $user ? number_format((float)$user['balance'], 3) : '0.000';
$spent   = $user ? number_format((float)$user['spent'], 3) : '0.000';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? 'Dashboard') ?> — <?= h($siteName) ?></title>
<link rel="icon" type="image/svg+xml" href="/assets/img/logo-icon.svg">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#E30A17;--primary-dark:#B90812;--primary-light:#FF4757;--accent:#e63950;--bg:#fafafa;--sidebar-bg:#1a0a0e;--sidebar-text:#c4a5ab;--white:#fff;--border:#f0e6e8;--text:#1a0a0e;--text-muted:#6b4a50;--green:#10b981;--orange:#f59e0b;--red:#dc2626;--shadow:0 4px 24px rgba(227,10,23,.06);--glow:0 0 40px rgba(227,10,23,.08)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}
a{text-decoration:none;color:inherit}
.sidebar{width:220px;min-width:220px;background:var(--sidebar-bg);position:fixed;top:0;left:0;bottom:0;z-index:100;overflow-y:auto;display:flex;flex-direction:column}
.sidebar-logo{padding:20px 18px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:12px}
.sidebar-logo a{display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit}
.logo-icon{width:36px;height:36px;flex-shrink:0;border-radius:10px}
.logo-text{font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:#fff;letter-spacing:-.02em}
.logo-text span{color:var(--primary-light)}
.sidebar-user{padding:18px 20px;border-bottom:1px solid rgba(255,255,255,.06)}
.user-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;margin-bottom:8px}
.user-name{color:#fff;font-size:13px;font-weight:600}
.user-bal{color:var(--primary-light);font-size:12px;margin-top:3px;font-weight:600}
.user-status{display:inline-block;background:rgba(227,10,23,.25);color:var(--primary-light);font-size:10px;padding:2px 8px;border-radius:20px;margin-top:4px;font-weight:700;letter-spacing:.5px;text-transform:uppercase}
.sidebar-nav{padding:14px 12px;flex:1}
.nav-label{font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.25);padding:0 8px;margin:14px 0 6px}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;color:var(--sidebar-text);font-size:13.5px;font-weight:500;transition:all .2s;margin-bottom:2px}
.nav-item:hover{background:rgba(255,255,255,.06);color:#fff}
.nav-item.active{background:var(--primary);color:#fff}
.nav-item svg{width:16px;height:16px;flex-shrink:0}
.main{margin-left:220px;flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 24px;height:58px;display:flex;align-items:center;gap:14px;position:sticky;top:0;z-index:50;box-shadow:0 1px 0 rgba(0,0,0,.04)}
.stat-pill{display:flex;align-items:center;gap:8px;padding:6px 14px;background:var(--bg);border-radius:20px;font-size:12px;font-weight:600;border:1px solid var(--border)}
.stat-icon{width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff}
.si-blue{background:var(--primary)}.si-green{background:var(--green)}.si-orange{background:var(--orange)}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:8px}
.icon-btn{width:34px;height:34px;border-radius:9px;background:var(--bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-muted);transition:all .2s;font-size:15px}
.icon-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.content{padding:24px;flex:1}
.card{background:#fff;border-radius:16px;padding:22px;box-shadow:var(--shadow);border:1px solid var(--border)}
.card-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:18px}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:6px;letter-spacing:.3px;text-transform:uppercase}
.form-control{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;color:var(--text);background:var(--bg);outline:none;transition:border-color .2s}
.form-control:focus{border-color:var(--primary);background:#fff}
.btn{padding:10px 22px;border-radius:10px;font-family:'Syne',sans-serif;font-size:14px;font-weight:700;cursor:pointer;border:none;transition:all .2s}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:var(--primary-dark);transform:translateY(-1px);box-shadow:0 6px 24px rgba(227,10,23,.35)}
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
.footer{background:var(--sidebar-bg);color:rgba(255,255,255,.35);text-align:center;padding:14px;font-size:12px}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.content>*{animation:fadeIn .35s ease}
.card{transition:box-shadow .25s ease, border-color .25s ease}
.card:hover{box-shadow:0 12px 40px rgba(227,10,23,.08)}
.form-control{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;color:var(--text);background:var(--bg);outline:none;transition:border-color .2s, box-shadow .2s}
.topbar{background:rgba(255,255,255,.9);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 24px;height:58px;display:flex;align-items:center;gap:14px;position:sticky;top:0;z-index:50;box-shadow:0 1px 0 rgba(0,0,0,.04)}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <a href="/index.php">
      <img src="/assets/img/logo-icon.svg" alt="" class="logo-icon" width="36" height="36">
      <span class="logo-text">SMM<span>Turk</span></span>
    </a>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?></div>
    <div class="user-name"><?= h($user['username'] ?? '') ?></div>
    <div class="user-bal">Balance: $<?= $balance ?></div>
    <span class="user-status">Active</span>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Orders</div>
    <a class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>" href="/index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      New Order
    </a>
    <a class="nav-item <?= $currentPage === 'mass-order' ? 'active' : '' ?>" href="/mass-order.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Mass Order
    </a>
    <a class="nav-item <?= $currentPage === 'services' ? 'active' : '' ?>" href="/services.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      Services
    </a>
    <a class="nav-item <?= $currentPage === 'orders' ? 'active' : '' ?>" href="/orders.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      My Orders
    </a>
    <div class="nav-label">Account</div>
    <a class="nav-item <?= $currentPage === 'add-funds' ? 'active' : '' ?>" href="/add-funds.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
      Add Funds
    </a>
    <a class="nav-item <?= $currentPage === 'tickets' ? 'active' : '' ?>" href="/tickets.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Tickets
    </a>
    <a class="nav-item <?= $currentPage === 'affiliates' ? 'active' : '' ?>" href="/affiliates.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Affiliates
    </a>
    <a class="nav-item <?= $currentPage === 'api-page' ? 'active' : '' ?>" href="/api-page.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      API
    </a>
    <?php if ($auth->isAdmin()): ?>
    <div class="nav-label">Admin</div>
    <a class="nav-item <?= strpos($currentPage, 'admin') !== false ? 'active' : '' ?>" href="/admin/index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      Admin Panel
    </a>
    <?php endif; ?>
    <div style="margin-top:auto;padding-top:20px;">
    <a class="nav-item" href="/logout.php" style="color:#ff6b6b;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
    </div>
  </nav>
</aside>

<div class="main">
  <div class="topbar">
    <div class="stat-pill"><div class="stat-icon si-blue">👤</div> <?= h($user['username'] ?? '') ?></div>
    <div class="stat-pill"><div class="stat-icon si-green">💰</div> $<?= $balance ?></div>
    <div class="stat-pill"><div class="stat-icon si-orange">📊</div> Spent: $<?= $spent ?></div>
    <div class="topbar-right">
      <a href="/add-funds.php" class="btn btn-primary" style="padding:7px 16px;font-size:12px;">+ Add Funds</a>
      <a href="/logout.php" class="icon-btn" title="Logout">🚪</a>
    </div>
  </div>
  <div class="content">
<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>
