<?php
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/Lang.php';

if ($auth->isLoggedIn()) {
    header('Location: ' . url('index.php'));
    exit;
}

$lang = Lang::init();
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
$siteUrl  = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$langParam = '?lang=';
$currentPath = 'home.php';
$canonicalUrl = $siteUrl ? $siteUrl . '/' . $currentPath : '';
$logoUrl = $siteUrl ? $siteUrl . path('assets/img/logo-icon.svg?v=2') : path('assets/img/logo-icon.svg?v=2');
$geoRegion = defined('GEO_REGION') ? GEO_REGION : 'TR';
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($siteName) ?> — Cheapest SMM Panel | Turkey & Worldwide</title>
    <meta name="description" content="SMM Turk: Cheapest SMM panel. Instagram, YouTube, TikTok growth. Crypto-only deposits, reseller API, 24/7 support. Turkey & worldwide.">
    <?php if ($canonicalUrl): ?><link rel="canonical" href="<?= h($canonicalUrl) ?>"><?php endif; ?>
    <?php
    $baseCanonical = $siteUrl ? $siteUrl . '/' . $currentPath : '';
    foreach (Lang::allowed() as $l):
        $href = $baseCanonical ? $baseCanonical . ($l === 'en' ? '' : '?lang=' . $l) : path($currentPath) . ($l === 'en' ? '' : '?lang=' . $l);
        $hreflang = $l === 'en' ? 'en' : ($l === 'tr' ? 'tr' : ($l === 'de' ? 'de' : 'fr'));
    ?><link rel="alternate" hreflang="<?= h($hreflang) ?>" href="<?= h($href) ?>"><?php endforeach; ?>
    <?php if ($baseCanonical): ?><link rel="alternate" hreflang="x-default" href="<?= h($baseCanonical) ?>"><?php endif; ?>
    <meta name="theme-color" content="#E30A17">
    <meta name="geo.region" content="<?= h($geoRegion) ?>">
    <meta name="geo.country" content="<?= h($geoRegion) ?>">
    <meta name="ICBM" content="39.9334, 32.8597">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= h($siteName) ?>">
    <meta property="og:title" content="<?= h($siteName) ?> — Cheapest SMM Panel">
    <meta property="og:description" content="Cheapest SMM panel. Crypto-only deposits, reseller API, 24/7 support. Turkey & worldwide.">
    <meta property="og:url" content="<?= h($canonicalUrl ?: path($currentPath)) ?>">
    <meta property="og:image" content="<?= h($logoUrl) ?>">
    <meta property="og:locale" content="<?= $lang === 'tr' ? 'tr_TR' : ($lang === 'de' ? 'de_DE' : ($lang === 'fr' ? 'fr_FR' : 'en_US')) ?>">
    <?php foreach (array_diff(Lang::allowed(), [$lang]) as $alt): $loc = $alt === 'tr' ? 'tr_TR' : ($alt === 'de' ? 'de_DE' : ($alt === 'fr' ? 'fr_FR' : 'en_US')); ?>
    <meta property="og:locale:alternate" content="<?= h($loc) ?>">
    <?php endforeach; ?>
    <link rel="icon" type="image/svg+xml" href="<?= h(path('assets/img/logo-icon.svg?v=2')) ?>">
    <link rel="apple-touch-icon" href="<?= h(path('assets/img/logo-icon.svg?v=2')) ?>">
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization","name":"<?= h($siteName) ?>","url":"<?= h($siteUrl ?: path('')) ?>","description":"Cheapest SMM Panel — Turkey & worldwide. Reseller panel, API, 24/7 support.","logo":"<?= h($logoUrl) ?>","address":{"@type":"PostalAddress","addressCountry":"<?= h($geoRegion) ?>"}}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #E30A17;
            --primary-dark: #B90812;
            --primary-light: #FF4757;
            --primary-soft: rgba(227, 10, 23, 0.08);
            --primary-soft-strong: rgba(227, 10, 23, 0.14);
            --accent: #e63950;
            --dark: #1a0a0e;
            --dark2: #2d1519;
            --muted: #6b4a50;
            --muted-dark: #5a3d42;
            --light: #fef8f9;
            --light-warm: #fff5f6;
            --white: #ffffff;
            --shadow-sm: 0 2px 8px rgba(26, 10, 14, 0.06);
            --shadow-md: 0 12px 40px rgba(26, 10, 14, 0.08);
            --shadow-lg: 0 24px 64px rgba(26, 10, 14, 0.1);
            /* Motion design tokens */
            --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
            --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --duration-fast: 0.2s;
            --duration-normal: 0.35s;
            --duration-slow: 0.6s;
        }
        @keyframes gradientShift {
            0%, 100% { opacity: 1; transform: scale(1) translate(0, 0); }
            50% { opacity: 0.9; transform: scale(1.05) translate(2%, 1%); }
        }
        @keyframes floatBlob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(8px, -12px) scale(1.02); }
            66% { transform: translate(-5px, 8px) scale(0.98); }
        }
        @keyframes floatBlob2 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-10px, -8px); }
        }
        @keyframes heroReveal {
            from { opacity: 0; transform: translateY(28px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes btnPulse {
            0%, 100% { box-shadow: 0 4px 20px rgba(0,0,0,.2); }
            50% { box-shadow: 0 4px 28px rgba(255,255,255,.35), 0 0 0 1px rgba(255,255,255,.2); }
        }
        @keyframes navLineShine {
            0% { background-position: -100% 0; }
            100% { background-position: 200% 0; }
        }
        @keyframes logoShine {
            0% { transform: translateX(-100%) skewX(-15deg); }
            100% { transform: translateX(200%) skewX(-15deg); }
        }
        @keyframes statFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--light); color: var(--dark); line-height: 1.6; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }
        [id] { scroll-margin-top: 80px; }

        /* ——— Header (Landing) ——— */
        .nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(255,252,253,.97) 100%);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(227, 10, 23, 0.1);
            box-shadow: 0 1px 0 rgba(255,255,255,.9) inset, 0 6px 32px rgba(26,10,14,.06);
            padding: 0 24px;
            padding-left: max(24px, env(safe-area-inset-left));
            padding-right: max(24px, env(safe-area-inset-right));
            padding-top: max(0, env(safe-area-inset-top));
        }
        .nav::after {
            content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 2px;
            background: linear-gradient(90deg, transparent 0%, var(--primary) 20%, var(--primary-dark) 50%, var(--primary) 80%, transparent 100%);
            background-size: 200% 100%;
            opacity: 0.85;
            animation: navLineShine 8s ease-in-out infinite;
        }
        .nav-inner {
            max-width: 1280px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
            min-height: 64px; padding: 10px 0;
        }
        .nav-logo {
            display: flex; align-items: center; gap: 14px; font-family: 'Syne', sans-serif; font-size: 23px; font-weight: 800;
            color: var(--dark); text-decoration: none; min-height: 44px; letter-spacing: -0.03em;
            transition: opacity .2s, transform .2s;
        }
        .nav-logo:hover { opacity: 0.92; transform: translateY(-1px); }
        .nav-logo-icon {
            width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            box-shadow: 0 4px 14px rgba(227, 10, 23, .25), 0 1px 0 rgba(255,255,255,.2) inset;
            transition: transform var(--duration-normal) var(--ease-spring), box-shadow var(--duration-normal) var(--ease-smooth);
            position: relative; overflow: hidden;
        }
        .nav-logo-icon::after {
            content: ''; position: absolute; inset: 0; background: linear-gradient(105deg, transparent 0%, rgba(255,255,255,.25) 45%, transparent 55%);
            transform: translateX(-100%); transition: transform 0.6s ease;
        }
        .nav-logo:hover .nav-logo-icon { transform: scale(1.08) rotate(-2deg); box-shadow: 0 8px 24px rgba(227, 10, 23, .4), 0 1px 0 rgba(255,255,255,.2) inset; }
        .nav-logo:hover .nav-logo-icon::after { transform: translateX(100%); }
        .nav-logo img { width: 24px; height: 24px; filter: brightness(0) invert(1); }
        .nav-logo span { color: var(--primary); }
        .nav-logo-tag { font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--muted); margin-left: 2px; font-family: 'Plus Jakarta Sans', sans-serif; }
        .nav-links { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .nav-links a {
            font-size: 13.5px; font-weight: 600; color: var(--dark); letter-spacing: .02em;
            padding: 10px 14px; min-height: 44px; display: inline-flex; align-items: center; border-radius: 10px;
            transition: color .2s, background .2s, transform .2s; position: relative;
        }
        .nav-links a:hover { color: var(--primary); background: var(--primary-soft); transform: translateY(-1px); }
        .nav-links a:not(.nav-btn):hover::after {
            content: ''; position: absolute; left: 14px; right: 14px; bottom: 8px; height: 2px; background: var(--primary); border-radius: 1px; opacity: 0.4;
        }
        .nav-lang { position: relative; }
        .nav-lang-btn {
            display: flex; align-items: center; gap: 8px; padding: 10px 14px; min-height: 44px; box-sizing: border-box;
            border-radius: 10px; border: 1px solid rgba(227,10,23,.15); background: rgba(255,255,255,.9);
            font-size: 12px; font-weight: 700; letter-spacing: .5px; color: var(--dark); cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,.04); transition: border-color .2s, color .2s, box-shadow .2s;
        }
        .nav-lang-btn:hover { border-color: var(--primary); color: var(--primary); box-shadow: 0 2px 8px rgba(227,10,23,.12); }
        .nav-lang-btn svg { width: 10px; height: 10px; opacity: .7; transition: transform .2s; }
        .nav-lang:has(.nav-lang-dropdown.open) .nav-lang-btn svg { transform: rotate(180deg); }
        .nav-lang-dropdown {
            position: absolute; top: calc(100% + 8px); right: 0; min-width: 160px;
            background: var(--white); border-radius: 14px; overflow: hidden;
            box-shadow: 0 16px 48px rgba(0,0,0,.12), 0 0 0 1px rgba(0,0,0,.06);
            display: none;
        }
        .nav-lang-dropdown.open { display: block; }
        .nav-lang-dropdown a { display: block; padding: 12px 18px; font-size: 14px; font-weight: 500; color: var(--dark); transition: background .2s, color .2s; }
        .nav-lang-dropdown a:hover { background: var(--primary-soft); color: var(--primary); }
        .nav-btn {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: var(--white); padding: 12px 24px; min-height: 44px; box-sizing: border-box;
            display: inline-flex; align-items: center; gap: 8px; border-radius: 12px;
            font-weight: 700; font-size: 14px; letter-spacing: .03em;
            box-shadow: 0 4px 14px rgba(227, 10, 23, .3), 0 1px 0 rgba(255,255,255,.15) inset;
            transition: transform .25s ease, box-shadow .25s ease, background .2s; border: none; cursor: pointer;
        }
        .nav-btn:hover { background: linear-gradient(145deg, var(--primary-dark), #9a0610); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(227, 10, 23, .4), 0 1px 0 rgba(255,255,255,.1) inset; }
        .nav-btn:active { transform: translateY(0); }

        /* Theme toggle (oval, light/dark) */
        .nav-theme-toggle {
            width: 52px; height: 32px; border-radius: 20px; border: 1.5px solid var(--border);
            background: var(--light); cursor: pointer; position: relative; display: flex; align-items: center; justify-content: space-between; padding: 0 8px; flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0,0,0,.06); transition: border-color .2s, background .2s;
        }
        .nav-theme-toggle:hover { border-color: var(--primary); background: rgba(227,10,23,.08); }
        .nav-theme-toggle .theme-icon { font-size: 14px; line-height: 1; opacity: .5; transition: opacity .2s; }
        .nav-theme-toggle .theme-icon-dark { opacity: 0; }
        body.theme-dark .nav-theme-toggle { background: var(--dark2); border-color: rgba(255,255,255,.15); }
        body.theme-dark .nav-theme-toggle .theme-icon-light { opacity: 0; }
        body.theme-dark .nav-theme-toggle .theme-icon-dark { opacity: .9; }
        .nav-lang-compact { margin-left: 4px; }

        /* Mobile menu (hamburger) - hidden on desktop */
        .nav-theme-toggle .theme-icon-light { opacity: .85; }
        .nav-mob-menu-btn { display: none; width: 44px; height: 44px; min-width: 44px; min-height: 44px; border: none; background: var(--light); border-radius: 10px; cursor: pointer; align-items: center; justify-content: center; font-size: 20px; color: var(--dark); transition: background .2s, color .2s; }
        .nav-mob-menu-btn:hover { background: var(--primary-soft); color: var(--primary); }
        .nav-mob-drawer { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(26,10,14,.5); z-index: 999; opacity: 0; pointer-events: none; transition: opacity .2s; padding-top: max(70px, calc(56px + env(safe-area-inset-top))); padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right); padding-bottom: env(safe-area-inset-bottom); }
        .nav-mob-drawer.open { opacity: 1; pointer-events: auto; }
        .nav-mob-drawer-inner { background: var(--white); border-radius: 16px 16px 0 0; padding: 16px; max-height: 70vh; overflow-y: auto; box-shadow: 0 -8px 32px rgba(0,0,0,.12); }
        .nav-mob-drawer-inner a { display: flex; align-items: center; padding: 14px 16px; font-size: 15px; font-weight: 600; color: var(--dark); border-radius: 12px; margin-bottom: 4px; min-height: 48px; transition: background .2s; }
        .nav-mob-drawer-inner a:hover { background: var(--primary-soft); color: var(--primary); }
        .nav-mob-drawer-inner a:last-child { margin-bottom: 0; }

        /* Mobile footer bar - fixed bottom CTA (only on landing, mobile) */
        .mob-footer-bar { display: none; position: fixed; bottom: 0; left: 0; right: 0; z-index: 900; background: rgba(255,255,255,.98); backdrop-filter: blur(14px); border-top: 1px solid rgba(227,10,23,.12); padding: 12px 16px; padding-left: max(16px, env(safe-area-inset-left)); padding-right: max(16px, env(safe-area-inset-right)); padding-bottom: max(12px, env(safe-area-inset-bottom)); gap: 12px; flex-wrap: wrap; justify-content: center; align-items: center; box-shadow: 0 -4px 24px rgba(0,0,0,.06); }
        .mob-footer-bar .mob-footer-btn { flex: 1; min-width: 140px; max-width: 200px; padding: 14px 20px; border-radius: 12px; font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; text-align: center; text-decoration: none; transition: transform .2s, box-shadow .2s; min-height: 48px; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box; border: none; cursor: pointer; }
        .mob-footer-bar .mob-footer-btn-primary { background: var(--primary); color: var(--white); }
        .mob-footer-bar .mob-footer-btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(227,10,23,.35); }
        .mob-footer-bar .mob-footer-btn-outline { background: var(--white); color: var(--primary); border: 2px solid var(--primary); }
        .mob-footer-bar .mob-footer-btn-outline:hover { background: var(--primary-soft); transform: translateY(-2px); }

        .hero {
            min-height: 92vh; display: flex; align-items: center; padding: 100px 24px 60px;
            background: linear-gradient(160deg, var(--light) 0%, var(--light-warm) 35%, #fff0f2 70%, var(--light) 100%);
            position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(ellipse 80% 50% at 15% 40%, var(--primary-soft), transparent 55%),
                        radial-gradient(ellipse 70% 60% at 85% 70%, rgba(230,57,80,.08), transparent 50%),
                        radial-gradient(circle at 50% 100%, rgba(255,245,246,.9), transparent 40%);
            pointer-events: none;
            animation: gradientShift 12s ease-in-out infinite;
        }
        .hero::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none; opacity: 0.6;
        }
        .hero-orb {
            position: absolute; border-radius: 50%; pointer-events: none; z-index: 0;
            animation: floatBlob 18s ease-in-out infinite;
        }
        .hero-orb-1 { width: 280px; height: 280px; top: 10%; right: 5%; background: radial-gradient(circle, rgba(227,10,23,.12) 0%, transparent 70%); animation-duration: 20s; animation-delay: 0s; }
        .hero-orb-2 { width: 180px; height: 180px; bottom: 20%; left: 8%; background: radial-gradient(circle, rgba(230,57,80,.1) 0%, transparent 70%); animation: floatBlob2 14s ease-in-out infinite; animation-delay: -3s; }
        .hero-orb-3 { width: 120px; height: 120px; top: 50%; left: 50%; background: radial-gradient(circle, rgba(227,10,23,.08) 0%, transparent 70%); animation-duration: 15s; animation-delay: -5s; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes ctaGlow { 0%, 100% { box-shadow: 0 8px 24px rgba(0,0,0,.15); } 50% { box-shadow: 0 8px 32px rgba(255,255,255,.25); } }
        .hero-inner { max-width: 1200px; margin: 0 auto; width: 100%; display: grid; grid-template-columns: 1fr 400px; gap: 48px; align-items: center; position: relative; z-index: 1; }
        .hero-copy .hero-badge { animation: heroReveal 0.7s var(--ease-out-expo) 0.1s both; }
        .hero-copy h1 { animation: heroReveal 0.75s var(--ease-out-expo) 0.2s both; }
        .hero-copy .hero-desc:nth-of-type(1) { animation: heroReveal 0.7s var(--ease-out-expo) 0.35s both; }
        .hero-copy .hero-desc:nth-of-type(2) { animation: heroReveal 0.7s var(--ease-out-expo) 0.45s both; }
        .hero-copy .hero-desc:nth-of-type(3) { animation: heroReveal 0.7s var(--ease-out-expo) 0.55s both; }
        .hero-form-box {
            background: var(--white); border-radius: 24px; padding: 40px; box-shadow: var(--shadow-lg), 0 0 0 1px rgba(227, 10, 23, .06), 0 32px 64px -16px rgba(227, 10, 23, .12); border: 1px solid rgba(227, 10, 23, .1);
            animation: heroReveal 0.8s var(--ease-out-expo) 0.25s both; position: relative;
            transition: transform var(--duration-normal) var(--ease-spring), box-shadow var(--duration-slow) var(--ease-smooth);
        }
        .hero-form-box:hover { transform: translateY(-4px); box-shadow: 0 32px 64px rgba(227, 10, 23, .15), 0 0 0 1px rgba(227, 10, 23, .08); }
        .hero-form-box::before {
            content: ''; position: absolute; inset: 0; border-radius: 24px; padding: 1px; background: linear-gradient(135deg, rgba(227,10,23,.2), transparent 50%, rgba(227,10,23,.1)); -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none;
        }
        .hero-form-box .form-title { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--dark); margin-bottom: 26px; letter-spacing: -.03em; }
        .hero-badge { display: inline-block; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: var(--white); font-size: 11px; font-weight: 700; letter-spacing: 1.8px; padding: 8px 16px; border-radius: 24px; margin-bottom: 18px; text-transform: uppercase; box-shadow: 0 2px 10px rgba(227, 10, 23, .25); }
        .hero h1 { font-family: 'Syne', sans-serif; font-size: clamp(28px, 4vw, 44px); font-weight: 800; line-height: 1.15; margin-bottom: 18px; letter-spacing: -.03em; color: var(--dark); }
        .hero h1 .hero-title-2 { display: block; font-size: clamp(32px, 4.5vw, 52px); color: var(--primary); margin-top: 4px; }
        .hero-desc { font-size: 15px; color: var(--muted-dark); margin-bottom: 14px; line-height: 1.7; max-width: 520px; font-weight: 500; }
        .hero-desc:last-of-type { margin-bottom: 28px; }
        .hero-desc strong { color: var(--primary); font-weight: 700; }
        .hero-form-box .form-group { margin-bottom: 16px; }
        .hero-form-box .form-label { display: block; font-size: 11px; font-weight: 700; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
        .hero-form-box .input-wrap { display: flex; align-items: center; gap: 0; background: var(--light); border: 1.5px solid #f0e6e8; border-radius: 12px; overflow: hidden; transition: border-color .2s, box-shadow .2s; }
        .hero-form-box .input-wrap:focus-within { border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 3px var(--primary-soft); }
        .hero-form-box .input-icon { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: var(--muted); flex-shrink: 0; }
        .hero-form-box .input-wrap:focus-within .input-icon { color: var(--primary); }
        .hero-form-box .input-wrap .form-control { border: none; border-radius: 0; padding: 12px 14px 12px 0; background: transparent; font-size: 15px; }
        .hero-form-box .input-wrap .form-control::placeholder { color: var(--muted); }
        .hero-form-box .input-wrap .form-control:focus { box-shadow: none; outline: none; }
        .hero-form-box .form-control { width: 100%; padding: 12px 16px; border: 1.5px solid #f0e6e8; border-radius: 12px; font-size: 15px; outline: none; transition: border-color .2s; background: var(--light); }
        .hero-form-box .form-control::placeholder { color: var(--muted); }
        .hero-form-box .form-control:focus { border-color: var(--primary); background: var(--white); }
        .hero-form-box .btn-login { width: 100%; padding: 16px 20px; background: linear-gradient(145deg, var(--primary), var(--primary-dark)); color: var(--white); border: none; border-radius: 12px; font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; letter-spacing: .02em; cursor: pointer; transition: all .25s ease; margin-top: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 14px rgba(227, 10, 23, .3), 0 1px 0 rgba(255,255,255,.15) inset; }
        .hero-form-box .btn-login svg { width: 20px; height: 20px; flex-shrink: 0; }
        .hero-form-box .btn-login:hover { background: linear-gradient(145deg, var(--primary-dark), #9a0610); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(227, 10, 23, .4), 0 1px 0 rgba(255,255,255,.1) inset; }
        .hero-form-box .btn-login:active { transform: translateY(0); }
        .hero-form-box .remember { font-size: 13px; color: var(--muted); margin-bottom: 12px; }
        .hero-form-box .forgot { font-size: 13px; color: var(--primary); font-weight: 600; }
        .hero-form-box .divider { display: flex; align-items: center; gap: 14px; margin: 22px 0; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
        .hero-form-box .divider::before, .hero-form-box .divider::after { content: ''; flex: 1; height: 1px; background: linear-gradient(90deg, transparent, #f0e6e8, transparent); }
        .hero-form-box .btn-google { width: 100%; padding: 14px 16px; border: 2px solid #e8e0e2; border-radius: 12px; background: var(--white); font-size: 15px; font-weight: 600; color: var(--dark); cursor: pointer; transition: all .25s; display: inline-flex; align-items: center; justify-content: center; gap: 10px; box-shadow: var(--shadow-sm); }
        .hero-form-box .btn-google:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-soft); box-shadow: 0 4px 14px rgba(227, 10, 23, .12); transform: translateY(-1px); }
        .hero-form-box .register-link { text-align: center; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(227, 10, 23, .1); font-size: 14px; color: var(--muted); }
        .hero-form-box .register-link a { color: var(--primary); font-weight: 700; transition: color .2s; }
        .hero-form-box .register-link a:hover { color: var(--primary-dark); }

        .section { padding: 80px 24px; max-width: 1100px; margin: 0 auto; }
        .section-benefits { background: linear-gradient(180deg, var(--light-warm) 0%, #fefafa 50%, #fafafa 100%); position: relative; }
        .section-benefits::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(227,10,23,.1), transparent); }
        .section-label { font-size: 11px; font-weight: 700; letter-spacing: 2.2px; text-transform: uppercase; color: var(--primary); margin-bottom: 10px; }
        .section-title { font-family: 'Syne', sans-serif; font-size: clamp(28px, 3vw, 38px); font-weight: 800; color: var(--dark); margin-bottom: 18px; letter-spacing: -.02em; line-height: 1.2; }
        .section-desc { color: var(--muted-dark); font-size: 16px; margin-bottom: 40px; max-width: 640px; line-height: 1.75; font-weight: 500; }
        .benefit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
        .benefit-card {
            background: var(--white); border-radius: 20px; padding: 30px; border: 1px solid rgba(227, 10, 23, .08);
            transition: transform var(--duration-slow) var(--ease-spring), box-shadow var(--duration-slow) var(--ease-smooth), border-color var(--duration-normal) var(--ease-smooth); display: flex; gap: 22px; align-items: flex-start;
            animation: fadeInUp 0.6s var(--ease-out-expo) both; box-shadow: var(--shadow-sm);
        }
        .benefit-card:nth-child(1){animation-delay:.06s}.benefit-card:nth-child(2){animation-delay:.12s}.benefit-card:nth-child(3){animation-delay:.18s}.benefit-card:nth-child(4){animation-delay:.24s}.benefit-card:nth-child(5){animation-delay:.3s}.benefit-card:nth-child(6){animation-delay:.36s}
        .benefit-card:hover { border-color: var(--primary); box-shadow: 0 24px 56px rgba(227, 10, 23, .16), 0 0 0 1px rgba(227, 10, 23, .08); transform: translateY(-8px) scale(1.01); }
        .benefit-icon { width: 58px; height: 58px; min-width: 58px; border-radius: 18px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); display: flex; align-items: center; justify-content: center; font-size: 26px; color: var(--white); box-shadow: 0 6px 20px rgba(227, 10, 23, .28), 0 1px 0 rgba(255,255,255,.2) inset; transition: transform var(--duration-normal) var(--ease-spring); }
        .benefit-card:hover .benefit-icon { transform: scale(1.1) rotate(5deg); }
        .benefit-card h3 { font-size: 18px; font-weight: 700; color: var(--dark); margin-bottom: 8px; font-family: 'Syne', sans-serif; letter-spacing: -.02em; }
        .benefit-card p { font-size: 14px; color: var(--muted-dark); line-height: 1.65; font-weight: 500; }

        .three-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 32px; }
        @media (max-width: 900px) { .three-cols { grid-template-columns: 1fr; } }
        .feature-block { background: var(--white); border-radius: 20px; padding: 32px; text-align: center; border: 1px solid rgba(227, 10, 23, .08); transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease; box-shadow: var(--shadow-sm); }
        .feature-block:hover { border-color: var(--primary); box-shadow: 0 16px 40px rgba(227, 10, 23, .12); transform: translateY(-4px); }
        .feature-block .icon { width: 68px; height: 68px; margin: 0 auto 18px; border-radius: 18px; background: linear-gradient(135deg, var(--primary-soft), var(--primary-soft-strong)); display: flex; align-items: center; justify-content: center; font-size: 30px; transition: transform .3s ease; }
        .feature-block:hover .icon { transform: scale(1.08); }
        .feature-block h3 { font-size: 17px; font-weight: 700; color: var(--dark); margin-bottom: 10px; font-family: 'Syne', sans-serif; letter-spacing: -.02em; }
        .feature-block p { font-size: 14px; color: var(--muted-dark); line-height: 1.65; font-weight: 500; }

        .cta-block { background: linear-gradient(145deg, var(--primary) 0%, var(--primary-dark) 50%, #9a0610 100%); border-radius: 28px; padding: 52px 44px; text-align: center; margin: 48px 0; position: relative; overflow: hidden; box-shadow: 0 20px 60px rgba(227, 10, 23, .35), 0 0 0 1px rgba(0,0,0,.1) inset; }
        .cta-block::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(255,255,255,.12), transparent 60%); pointer-events: none; }
        .cta-block .section-label { color: rgba(255,255,255,.85); }
        .cta-block .section-title { color: var(--white); text-shadow: 0 1px 2px rgba(0,0,0,.1); }
        .cta-block .section-desc { color: rgba(255,255,255,.92); margin-bottom: 30px; }
        .cta-block .btn-cta { display: inline-block; background: var(--white); color: var(--primary); padding: 18px 40px; border-radius: 14px; font-family: 'Syne', sans-serif; font-size: 17px; font-weight: 700; transition: transform var(--duration-normal) var(--ease-spring), box-shadow var(--duration-normal) var(--ease-smooth); animation: btnPulse 2.5s ease-in-out infinite; box-shadow: 0 4px 20px rgba(0,0,0,.2); position: relative; z-index: 1; }
        .cta-block .btn-cta:hover { transform: translateY(-6px) scale(1.04); box-shadow: 0 16px 48px rgba(0,0,0,.35); animation: none; }

        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 40px; }
        @media (max-width: 700px) { .stats-row { grid-template-columns: 1fr; } }
        .stat-item { text-align: center; padding: 32px 24px; background: var(--white); border-radius: 20px; border: 1px solid rgba(227, 10, 23, .08); box-shadow: var(--shadow-sm); transition: transform var(--duration-normal) var(--ease-spring), box-shadow var(--duration-normal) var(--ease-smooth); }
        .stat-item:hover { transform: translateY(-6px); box-shadow: var(--shadow-md); }
        .stat-item .icon { width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 16px; background: var(--primary-soft); display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .stat-value { font-family: 'Syne', sans-serif; font-size: 30px; font-weight: 800; color: var(--primary); letter-spacing: -.02em; }
        .stat-label { font-size: 14px; color: var(--muted-dark); margin-top: 6px; font-weight: 500; }

        .faq-list { max-width: 720px; margin: 0 auto; }
        .faq-item { background: var(--white); border: 1px solid rgba(227, 10, 23, .08); border-radius: 16px; margin-bottom: 14px; overflow: hidden; transition: border-color var(--duration-normal) var(--ease-smooth), box-shadow var(--duration-normal) var(--ease-smooth); box-shadow: var(--shadow-sm); }
        .faq-item:hover { border-color: rgba(227, 10, 23, .18); box-shadow: var(--shadow-sm); }
        .faq-item.open { border-color: var(--primary); box-shadow: 0 8px 24px rgba(227, 10, 23, .1); }
        .faq-q { padding: 22px 26px; font-weight: 600; font-size: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; color: var(--dark); transition: color var(--duration-fast) var(--ease-smooth); }
        .faq-item.open .faq-q { color: var(--primary); }
        .faq-q span { color: var(--primary); font-size: 22px; transition: transform var(--duration-normal) var(--ease-spring); flex-shrink: 0; margin-left: 12px; }
        .faq-item.open .faq-q span { transform: rotate(45deg); }
        .faq-a { padding: 0 26px; font-size: 14px; color: var(--muted-dark); line-height: 1.75; max-height: 0; overflow: hidden; transition: max-height var(--duration-slow) var(--ease-out-expo); font-weight: 500; }
        .faq-item.open .faq-a { padding: 0 26px 22px 26px; max-height: 320px; }

        .why-us-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; margin-top: 40px; }
        @media (max-width: 900px) { .why-us-grid { grid-template-columns: 1fr; } }
        .why-us-icons { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .why-us-icon { display: flex; align-items: center; gap: 18px; padding: 22px; background: var(--primary-soft); border-radius: 16px; border: 1px solid rgba(227, 10, 23, .12); transition: transform .25s ease, box-shadow .25s ease, border-color .25s; }
        .why-us-icon:hover { border-color: rgba(227, 10, 23, .25); box-shadow: 0 8px 24px rgba(227, 10, 23, .1); transform: translateY(-2px); }
        .why-us-icon .ico { width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: var(--white); display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(227, 10, 23, .25); }
        .why-us-icon span { font-weight: 700; font-size: 15px; color: var(--dark); font-family: 'Syne', sans-serif; letter-spacing: -.02em; }

        .footer {
            background: linear-gradient(180deg, var(--dark2) 0%, #1a0a0e 100%); color: rgba(255,255,255,.7); padding: 48px 24px; text-align: center; position: relative;
            padding-left: max(24px, env(safe-area-inset-left));
            padding-right: max(24px, env(safe-area-inset-right));
            padding-bottom: max(48px, env(safe-area-inset-bottom));
        }
        .footer::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(227,10,23,.25), transparent); }
        .footer-links { display: flex; gap: 8px; justify-content: center; margin-bottom: 24px; flex-wrap: wrap; }
        .footer-links a { color: rgba(255,255,255,.85); font-size: 14px; font-weight: 600; padding: 12px 20px; min-height: 48px; display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; transition: color .2s, background .2s; }
        .footer-links a:hover { color: var(--white); background: rgba(255,255,255,.08); }
        .footer-copy { font-size: 13px; color: rgba(255,255,255,.5); word-break: break-word; line-height: 1.6; }


        /* Dark theme (body.theme-dark) */
        body.theme-dark { background: #0f0809; color: #e8dcdd; }
        body.theme-dark .nav { background: linear-gradient(180deg, rgba(26,10,14,.98) 0%, rgba(26,10,14,.95) 100%); border-bottom-color: rgba(227,10,23,.2); box-shadow: 0 4px 24px rgba(0,0,0,.3); }
        body.theme-dark .nav::after { opacity: 0.5; }
        body.theme-dark .nav-logo { color: #fff; }
        body.theme-dark .nav-links a:not(.nav-btn) { color: rgba(255,255,255,.85); }
        body.theme-dark .nav-links a:not(.nav-btn):hover { color: var(--primary-light); background: rgba(227,10,23,.15); }
        body.theme-dark .nav-lang-btn { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.15); color: #fff; }
        body.theme-dark .nav-lang-btn:hover { border-color: var(--primary); color: var(--primary-light); }
        body.theme-dark .hero { background: linear-gradient(160deg, #0f0809 0%, #1a0a0e 35%, #1f0d11 70%, #0f0809 100%); }
        body.theme-dark .hero::before { background: radial-gradient(ellipse 80% 50% at 15% 40%, rgba(227,10,23,.12), transparent 55%), radial-gradient(ellipse 70% 60% at 85% 70%, rgba(230,57,80,.06), transparent 50%); }
        body.theme-dark .hero h1 { color: #fff; }
        body.theme-dark .hero h1 .hero-title-2 { color: var(--primary-light); }
        body.theme-dark .hero-desc { color: rgba(255,255,255,.75); }
        body.theme-dark .hero-desc strong { color: var(--primary-light); }
        body.theme-dark .section-benefits { background: linear-gradient(180deg, #1a0a0e 0%, #15080b 100%); }
        body.theme-dark .section-label { color: var(--primary-light); }
        body.theme-dark .section-title { color: #fff; }
        body.theme-dark .section-desc { color: rgba(255,255,255,.7); }
        body.theme-dark .footer { background: #0a0506; }
        body.theme-dark .mob-footer-bar { background: rgba(26,10,14,.98); border-top-color: rgba(227,10,23,.2); }
        body.theme-dark .nav-mob-drawer-inner { background: var(--dark2); }
        body.theme-dark .nav-mob-drawer-inner a { color: #fff; }
        body.theme-dark .nav-mob-drawer-inner a:hover { background: rgba(227,10,23,.2); color: var(--primary-light); }

        @media (max-width: 900px) {
            .hero-inner { grid-template-columns: 1fr; text-align: center; }
            .hero-desc { margin-left: auto; margin-right: auto; }
            .hero-form-box { max-width: 400px; margin: 0 auto; }
            .hero-copy { text-align: left; }
        }
        @media (max-width: 900px) and (min-width: 769px) {
            .hero-copy { text-align: center; }
        }
        /* Mobile header & footer (768px and below) */
        @media (max-width: 768px) {
            .nav { padding: 0 16px; padding-left: max(16px, env(safe-area-inset-left)); padding-right: max(16px, env(safe-area-inset-right)); padding-top: max(0, env(safe-area-inset-top)); }
            .nav-inner { min-height: 56px; padding: 8px 0; flex-wrap: nowrap; gap: 10px; }
            .nav-logo { font-size: 18px; flex: 0 0 auto; }
            .nav-logo-icon { width: 36px; height: 36px; }
            .nav-logo img { width: 20px; height: 20px; }
            .nav-logo-tag { display: none; }
            .nav-mob-menu-btn { display: flex; order: 1; }
            .nav-links { order: 2; margin-left: auto; justify-content: flex-end; gap: 8px; flex-wrap: nowrap; }
            .nav-links .hide-mob { display: none; }
            .nav-lang-btn { min-height: 44px; padding: 8px 10px; font-size: 12px; }
            .nav-links a:not(.nav-btn) { font-size: 13px; padding: 8px 6px; }
            .nav-btn { padding: 10px 14px; font-size: 13px; min-height: 44px; }
            .hero { padding: 80px 16px 100px; min-height: auto; padding-top: max(80px, env(safe-area-inset-top)); padding-bottom: max(100px, env(safe-area-inset-bottom)); }
            .hero-inner { gap: 32px; }
            .hero h1 { font-size: clamp(26px, 5vw, 36px); }
            .hero-desc { font-size: 15px; }
            .hero-form-box { padding: 24px 20px; }
            .section { padding: 48px 16px; padding-bottom: max(100px, env(safe-area-inset-bottom)); }
            .benefit-grid { grid-template-columns: 1fr; gap: 16px; }
            .benefit-card { padding: 20px; flex-direction: column; align-items: center; text-align: center; }
            .cta-block { padding: 32px 20px; margin: 32px 0; border-radius: 20px; }
            .mob-footer-bar { display: flex; }
            .footer { padding: 32px 16px; padding-left: max(16px, env(safe-area-inset-left)); padding-right: max(16px, env(safe-area-inset-right)); padding-bottom: max(32px, env(safe-area-inset-bottom)); }
            .footer-links { gap: 12px; display: grid; grid-template-columns: 1fr 1fr; justify-items: center; }
            .footer-links a { padding: 14px 12px; min-height: 48px; font-size: 14px; font-weight: 600; width: 100%; justify-content: center; border-radius: 10px; transition: background .2s; }
            .footer-links a:hover { background: rgba(255,255,255,.08); }
        }
        @media (max-width: 480px) {
            .nav { padding: 0 12px; padding-left: max(12px, env(safe-area-inset-left)); padding-right: max(12px, env(safe-area-inset-right)); padding-top: max(0, env(safe-area-inset-top)); }
            .nav-inner { min-height: 52px; gap: 8px; }
            .nav-logo { font-size: 16px; }
            .nav-logo-icon { width: 32px; height: 32px; }
            .nav-logo img { width: 18px; height: 18px; }
            .nav-links { gap: 6px; }
            .nav-links a:not(.nav-btn) { font-size: 12px; padding: 6px 4px; }
            .nav-btn { padding: 10px 12px; font-size: 12px; min-height: 44px; }
            .nav-lang-btn { min-height: 40px; padding: 6px 10px; font-size: 11px; }
            .hero-form-box { padding: 20px 16px; }
            .section-title { font-size: clamp(22px, 4vw, 28px); }
            .mob-footer-bar { padding: 10px 12px; padding-bottom: max(10px, env(safe-area-inset-bottom)); flex-direction: row; }
            .mob-footer-bar .mob-footer-btn { min-width: 120px; padding: 12px 16px; font-size: 14px; }
            .footer { padding: 24px 12px; padding-left: max(12px, env(safe-area-inset-left)); padding-right: max(12px, env(safe-area-inset-right)); padding-bottom: max(24px, env(safe-area-inset-bottom)); }
            .footer-links { grid-template-columns: 1fr; gap: 8px; margin-bottom: 16px; }
            .footer-links a { padding: 14px; font-size: 13px; }
            .footer-copy { font-size: 12px; padding: 0 8px; }
        }
        @media (max-width: 360px) {
            .nav-logo span { font-size: 15px; }
            .nav-links a { font-size: 12px; }
            .nav-btn { padding: 8px 12px; font-size: 12px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
            .cta-block .btn-cta, .nav::after, .hero::before, .hero-orb { animation: none !important; }
            .stat-item:hover { animation: none !important; }
        }
    </style>
</head>
<body>

<header class="nav" role="banner">
    <div class="nav-inner">
        <a href="<?= h(path('home.php')) ?>" class="nav-logo" aria-label="<?= h($siteName) ?> Home">
            <span class="nav-logo-icon"><img src="<?= h(path('assets/img/logo-icon.svg?v=2')) ?>" alt="" width="24" height="24" fetchpriority="high"></span>
            <span>SMM<span>Turk</span></span><span class="nav-logo-tag">SMM Panel</span>
        </a>
        <button type="button" class="nav-mob-menu-btn" id="navMobMenuBtn" aria-label="Menu" aria-expanded="false" aria-controls="navMobDrawer">☰</button>
        <div class="nav-links">
            <a href="<?= h(path('login.php')) ?>"><?= h(__('nav_sign_in')) ?></a>
            <a href="<?= h(path('login.php')) ?>?mode=register"><?= h(__('nav_sign_up')) ?></a>
            <a href="<?= h(path('terms.php')) ?>"><?= h(__('nav_terms')) ?></a>
            <button type="button" class="nav-theme-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Toggle theme">
                <span class="theme-icon theme-icon-light" aria-hidden="true">☀</span>
                <span class="theme-icon theme-icon-dark" aria-hidden="true">🌙</span>
            </button>
            <div class="nav-lang nav-lang-compact">
                <div class="nav-lang-dropdown" id="langDropdown">
                    <?php foreach (Lang::allowed() as $l): ?>
                    <a href="<?= h(path('home.php') . $langParam . $l) ?>"><?= $l === 'en' ? 'English' : ($l === 'tr' ? 'Türkçe' : ($l === 'de' ? 'Deutsch' : 'Français')) ?></a>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="nav-lang-btn" id="langBtn" aria-haspopup="true" aria-expanded="false"><?= strtoupper($lang) ?> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></button>
            </div>
            <a href="<?= h(path('login.php')) ?>?mode=register" class="nav-btn"><?= h(__('nav_sign_up')) ?> →</a>
        </div>
    </div>
</header>
<div class="nav-mob-drawer" id="navMobDrawer" role="dialog" aria-label="Menu" aria-modal="true" aria-hidden="true">
    <div class="nav-mob-drawer-inner">
        <a href="#benefits" class="mob-drawer-link"><?= h(__('benefits')) ?></a>
        <a href="#services" class="mob-drawer-link"><?= h(__('services_nav')) ?></a>
        <a href="#faq" class="mob-drawer-link"><?= h(__('faq_nav')) ?></a>
        <a href="<?= h(path('terms.php')) ?>" class="mob-drawer-link"><?= h(__('nav_terms')) ?></a>
        <a href="<?= h(path('login.php')) ?>" class="mob-drawer-link"><?= h(__('nav_sign_in')) ?></a>
        <a href="<?= h(path('login.php')) ?>?mode=register" class="mob-drawer-link"><?= h(__('nav_sign_up')) ?> →</a>
    </div>
</div>

<main id="main-content">
<section class="hero" aria-labelledby="hero-title">
    <div class="hero-orb hero-orb-1" aria-hidden="true"></div>
    <div class="hero-orb hero-orb-2" aria-hidden="true"></div>
    <div class="hero-orb hero-orb-3" aria-hidden="true"></div>
    <div class="hero-inner">
        <div class="hero-copy">
            <span class="hero-badge"><?= h(__('hero_badge')) ?></span>
            <h1 id="hero-title"><?= h(__('hero_title')) ?><br><span class="hero-title-2"><?= h(__('hero_title_2')) ?></span></h1>
            <p class="hero-desc"><?= __('hero_desc_1') ?></p>
            <p class="hero-desc"><?= __('hero_desc_2') ?></p>
            <p class="hero-desc"><?= __('hero_desc_3') ?></p>
        </div>
        <div class="hero-form-box">
            <h2 class="form-title"><?= h(__('nav_sign_in')) ?></h2>
            <form method="POST" action="<?= h(path('login.php')) ?>">
                <div class="form-group">
                    <label class="form-label"><?= h(__('login_username')) ?></label>
                    <div class="input-wrap">
                        <span class="input-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                        <input type="text" name="email" class="form-control" placeholder="<?= h(__('login_username')) ?>" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= h(__('login_password')) ?></label>
                    <div class="input-wrap">
                        <span class="input-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="remember"><label><input type="checkbox" name="remember"> <?= h(__('remember_me')) ?></label></div>
                <div style="margin-bottom:12px;"><a href="<?= h(path('forgot-password.php')) ?>" class="forgot"><?= h(__('forgot_password')) ?></a></div>
                <button type="submit" class="btn-login"><?= h(__('btn_login_dashboard')) ?> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></button>
                <div class="divider">— <?= h(__('login_with_google')) ?> —</div>
                <?php $googleAuth = defined('GOOGLE_CLIENT_ID') ? trim(GOOGLE_CLIENT_ID) !== '' : false; ?>
<a href="<?= h($googleAuth ? path('login-google.php') : path('login.php')) ?>" class="btn-google"><?= h(__('login_with_google')) ?></a>
                <p class="register-link"><?= h(__('no_account')) ?> <a href="<?= h(path('login.php')) ?>?mode=register">→ <?= h(__('register')) ?></a></p>
            </form>
        </div>
    </div>
</section>

<section id="benefits" class="section section-benefits" aria-labelledby="benefits-heading">
    <div class="section-label"><?= h(__('benefit_heading')) ?></div>
    <h2 id="benefits-heading" class="section-title"><?= h(__('benefit_title')) ?></h2>
    <p class="section-desc"><?= h(__('benefit_intro')) ?></p>
    <div class="benefit-grid">
        <div class="benefit-card">
            <div class="benefit-icon">💰</div>
            <div><h3><?= h(__('cheapest')) ?></h3><p><?= h(__('cheapest_desc')) ?></p></div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon">⚡</div>
            <div><h3><?= h(__('fastest')) ?></h3><p><?= h(__('fastest_desc')) ?></p></div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon">🎯</div>
            <div><h3><?= h(__('easy')) ?></h3><p><?= h(__('easy_desc')) ?></p></div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon">📊</div>
            <div><h3><?= h(__('realtime')) ?></h3><p><?= h(__('realtime_desc')) ?></p></div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon">🔄</div>
            <div><h3><?= h(__('reseller')) ?></h3><p><?= h(__('reseller_desc')) ?></p></div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon">⭐</div>
            <div><h3><?= h(__('quality')) ?></h3><p><?= h(__('quality_desc')) ?></p></div>
        </div>
    </div>
</section>

<section id="services" class="section" style="background: var(--white); padding: 60px 24px;">
    <div class="section-label"><?= h(__('benefit_heading')) ?></div>
    <h2 class="section-title"><?= h(__('services_title')) ?></h2>
    <div class="three-cols">
        <div class="feature-block">
            <div class="icon">💳</div>
            <h3><?= h(__('secure_payment')) ?></h3>
            <p><?= h(__('secure_payment_desc')) ?></p>
        </div>
        <div class="feature-block">
            <div class="icon">🌍</div>
            <h3><?= h(__('services_title')) ?></h3>
            <p><?= h(__('services_desc')) ?></p>
        </div>
        <div class="feature-block">
            <div class="icon">💬</div>
            <h3><?= h(__('support_24_7')) ?></h3>
            <p><?= h(__('support_24_7_desc')) ?></p>
        </div>
    </div>
</section>

<section class="section">
    <div class="cta-block">
        <div class="section-label"><?= h(__('quick_response_label')) ?></div>
        <h2 class="section-title"><?= h(__('quick_response')) ?></h2>
        <p class="section-desc"><?= h(__('quick_response_desc')) ?></p>
        <a href="<?= h(path('login.php')) ?>?mode=register" class="btn-cta"><?= h(__('sign_up_now')) ?> →</a>
    </div>
    <div class="stats-row">
        <div class="stat-item">
            <div class="icon">⏱</div>
            <div class="stat-value">0.3Sec</div>
            <div class="stat-label"><?= h(__('stat_order_every')) ?></div>
        </div>
        <div class="stat-item">
            <div class="icon">✓</div>
            <div class="stat-value">59M+</div>
            <div class="stat-label"><?= h(__('stat_orders_completed')) ?></div>
        </div>
        <div class="stat-item">
            <div class="icon">$</div>
            <div class="stat-value">$0.001/1K</div>
            <div class="stat-label"><?= h(__('stat_prices_from')) ?></div>
        </div>
    </div>
</section>

<section id="faq" class="section" style="background: var(--white);">
    <div class="section-label"><?= h(__('faq_label')) ?></div>
    <h2 class="section-title"><?= h(__('faq_title')) ?></h2>
    <div class="faq-list">
        <div class="faq-item"><div class="faq-q"><?= h(__('faq_1')) ?> <span>+</span></div><div class="faq-a"><?= h(__('faq_1_a')) ?></div></div>
        <div class="faq-item"><div class="faq-q"><?= h(__('faq_2')) ?> <span>+</span></div><div class="faq-a"><?= h(__('faq_2_a')) ?></div></div>
        <div class="faq-item"><div class="faq-q"><?= h(__('faq_3')) ?> <span>+</span></div><div class="faq-a"><?= h(__('faq_3_a')) ?></div></div>
        <div class="faq-item"><div class="faq-q"><?= h(__('faq_4')) ?> <span>+</span></div><div class="faq-a"><?= h(__('faq_4_a')) ?></div></div>
        <div class="faq-item"><div class="faq-q"><?= h(__('faq_5')) ?> <span>+</span></div><div class="faq-a"><?= h(__('faq_5_a')) ?></div></div>
        <div class="faq-item"><div class="faq-q"><?= h(__('faq_6')) ?> <span>+</span></div><div class="faq-a"><?= h(__('faq_6_a')) ?></div></div>
    </div>
</section>

<section id="why-us" class="section">
    <div class="section-label"><?= h(__('why_us_label')) ?></div>
    <h2 class="section-title"><?= h(__('why_us_title')) ?></h2>
    <div class="why-us-grid">
        <div class="why-us-icons">
            <div class="why-us-icon"><div class="ico">💬</div><span><?= h(__('live_chat')) ?></span></div>
            <div class="why-us-icon"><div class="ico">📦</div><span><?= h(__('multi_services')) ?></span></div>
            <div class="why-us-icon"><div class="ico">📋</div><span><?= h(__('mass_order')) ?></span></div>
            <div class="why-us-icon"><div class="ico">🔌</div><span><?= h(__('api_integration')) ?></span></div>
        </div>
        <div>
            <p class="section-desc" style="margin-bottom:24px;"><?= h(__('why_us_desc')) ?></p>
            <a href="<?= h(path('login.php')) ?>?mode=register" class="nav-btn" style="display:inline-block;"><?= h(__('sign_up_now')) ?> →</a>
        </div>
    </div>
</section>

<section class="section" style="padding-top: 0;">
    <div class="cta-block">
        <h2 class="section-title"><?= h(__('cta_ready')) ?></h2>
        <p class="section-desc"><?= h(__('cta_join')) ?></p>
        <a href="<?= h(path('login.php')) ?>?mode=register" class="btn-cta"><?= h(__('cta_btn')) ?></a>
    </div>
</section>

</main>

<!-- Mobile-only fixed bottom bar: quick Login / Sign up (only visible on mobile) -->
<nav class="mob-footer-bar" aria-label="<?= h(__('footer_login')) ?>">
    <a href="<?= h(path('login.php')) ?>" class="mob-footer-btn mob-footer-btn-outline"><?= h(__('footer_login')) ?></a>
    <a href="<?= h(path('login.php')) ?>?mode=register" class="mob-footer-btn mob-footer-btn-primary"><?= h(__('footer_signup')) ?> →</a>
</nav>

<footer class="footer" role="contentinfo">
    <div class="footer-links">
        <a href="<?= h(path('login.php')) ?>"><?= h(__('footer_login')) ?></a>
        <a href="<?= h(path('login.php')) ?>?mode=register"><?= h(__('footer_signup')) ?></a>
        <a href="<?= h(path('terms.php')) ?>"><?= h(__('nav_terms')) ?></a>
        <a href="<?= h(path('api-page.php')) ?>"><?= h(__('footer_api')) ?></a>
        <a href="<?= h(path('tickets.php')) ?>"><?= h(__('footer_support')) ?></a>
    </div>
    <div class="footer-copy">© <?= date('Y') ?> <?= h($siteName) ?>. <?= h(__('footer_copyright')) ?>.</div>
</footer>

<script>
(function() {
    var themeKey = 'smmturk_theme';
    var dark = localStorage.getItem(themeKey) === 'dark' || (!localStorage.getItem(themeKey) && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    if (dark) document.body.classList.add('theme-dark');
    var btn = document.getElementById('themeToggle');
    if (btn) btn.addEventListener('click', function() {
        document.body.classList.toggle('theme-dark');
        localStorage.setItem(themeKey, document.body.classList.contains('theme-dark') ? 'dark' : 'light');
    });
})();
document.getElementById('langBtn').addEventListener('click', function() {
    document.getElementById('langDropdown').classList.toggle('open');
});
document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-lang')) document.getElementById('langDropdown').classList.remove('open');
});
(function(){
    var btn = document.getElementById('navMobMenuBtn');
    var drawer = document.getElementById('navMobDrawer');
    if (btn && drawer) {
        function openDrawer() { drawer.classList.add('open'); drawer.setAttribute('aria-hidden', 'false'); btn.setAttribute('aria-expanded', 'true'); document.body.style.overflow = 'hidden'; }
        function closeDrawer() { drawer.classList.remove('open'); drawer.setAttribute('aria-hidden', 'true'); btn.setAttribute('aria-expanded', 'false'); document.body.style.overflow = ''; }
        btn.addEventListener('click', function() { drawer.classList.contains('open') ? closeDrawer() : openDrawer(); });
        drawer.addEventListener('click', function(e) { if (e.target === drawer) closeDrawer(); });
        drawer.querySelectorAll('.mob-drawer-link').forEach(function(link) { link.addEventListener('click', closeDrawer); });
    }
})();
document.querySelectorAll('.faq-q').forEach(function(el) {
    el.addEventListener('click', function() {
        var item = this.closest('.faq-item');
        var open = item.classList.contains('open');
        document.querySelectorAll('.faq-item').forEach(function(i) { i.classList.remove('open'); });
        if (!open) item.classList.add('open');
    });
});
</script>
</body>
</html>
