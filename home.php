<?php
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/Lang.php';

if ($auth->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$lang = Lang::init();
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
$siteUrl  = defined('SITE_URL') ? SITE_URL : '';
$langParam = '?lang=';
$currentPath = '/home.php';
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($siteName) ?> — <?= h(__('hero_title')) ?></title>
    <meta name="description" content="<?= h(__('hero_desc')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #c41e3a;
            --primary-dark: #9e1830;
            --primary-light: #e63950;
            --primary-soft: rgba(196, 30, 58, 0.08);
            --accent: #d4af37;
            --dark: #1a0a0e;
            --dark2: #2d1519;
            --muted: #6b4a50;
            --light: #fef8f9;
            --white: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--light); color: #1a0a0e; line-height: 1.6; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }

        .nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: rgba(255,255,255,.97); backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(196, 30, 58, 0.1);
            padding: 14px 24px; display: flex; align-items: center; justify-content: space-between;
        }
        .nav-logo { font-family: 'Syne', sans-serif; font-size: 24px; font-weight: 800; color: var(--primary-dark); }
        .nav-logo span { color: var(--primary); }
        .nav-links { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .nav-links a { font-size: 14px; font-weight: 500; color: var(--dark); transition: color .2s; }
        .nav-links a:hover { color: var(--primary); }
        .nav-lang { position: relative; }
        .nav-lang-btn { display: flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 10px; border: 1.5px solid rgba(196,30,58,.2); background: var(--white); font-size: 13px; font-weight: 600; color: var(--dark); cursor: pointer; }
        .nav-lang-btn:hover { border-color: var(--primary); color: var(--primary); }
        .nav-lang-dropdown { position: absolute; top: 100%; right: 0; margin-top: 6px; background: var(--white); border-radius: 12px; box-shadow: 0 12px 40px rgba(0,0,0,.12); border: 1px solid rgba(0,0,0,.06); min-width: 140px; display: none; overflow: hidden; }
        .nav-lang-dropdown.open { display: block; }
        .nav-lang-dropdown a { display: block; padding: 12px 16px; font-size: 14px; color: var(--dark); }
        .nav-lang-dropdown a:hover { background: var(--primary-soft); color: var(--primary); }
        .nav-btn { background: var(--primary); color: var(--white); padding: 10px 22px; border-radius: 10px; font-weight: 700; font-size: 14px; transition: all .2s; }
        .nav-btn:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(196, 30, 58, .35); }

        .hero {
            min-height: 92vh; display: flex; align-items: center; padding: 100px 24px 60px;
            background: linear-gradient(135deg, var(--light) 0%, #fff5f6 50%, var(--light) 100%);
            position: relative;
        }
        .hero::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(ellipse 70% 40% at 20% 50%, var(--primary-soft), transparent 50%),
                        radial-gradient(ellipse 60% 50% at 80% 80%, rgba(212,175,55,.06), transparent 50%);
            pointer-events: none;
        }
        .hero-inner { max-width: 1200px; margin: 0 auto; width: 100%; display: grid; grid-template-columns: 1fr 400px; gap: 48px; align-items: center; position: relative; z-index: 1; }
        .hero-badge { display: inline-block; background: var(--primary); color: var(--white); font-size: 11px; font-weight: 700; letter-spacing: 1.5px; padding: 6px 14px; border-radius: 20px; margin-bottom: 16px; text-transform: uppercase; }
        .hero h1 { font-family: 'Syne', sans-serif; font-size: clamp(32px, 4.5vw, 48px); font-weight: 800; line-height: 1.15; margin-bottom: 20px; letter-spacing: -.02em; color: var(--dark); }
        .hero h1 span { color: var(--primary); }
        .hero-desc { font-size: 16px; color: var(--muted); margin-bottom: 28px; line-height: 1.7; max-width: 520px; }
        .hero-form-box {
            background: var(--white); border-radius: 20px; padding: 32px; box-shadow: 0 20px 60px rgba(196, 30, 58, .1); border: 1px solid rgba(196, 30, 58, .08);
        }
        .hero-form-box .form-group { margin-bottom: 16px; }
        .hero-form-box .form-label { display: block; font-size: 11px; font-weight: 700; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
        .hero-form-box .form-control { width: 100%; padding: 12px 16px; border: 1.5px solid #e8d4d8; border-radius: 12px; font-size: 14px; outline: none; transition: border-color .2s; background: var(--light); }
        .hero-form-box .form-control:focus { border-color: var(--primary); background: var(--white); }
        .hero-form-box .btn-login { width: 100%; padding: 14px; background: var(--primary); color: var(--white); border: none; border-radius: 12px; font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; cursor: pointer; transition: all .2s; margin-top: 8px; }
        .hero-form-box .btn-login:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(196, 30, 58, .3); }
        .hero-form-box .remember { font-size: 13px; color: var(--muted); margin-bottom: 12px; }
        .hero-form-box .forgot { font-size: 13px; color: var(--primary); font-weight: 600; }
        .hero-form-box .divider { text-align: center; margin: 20px 0; font-size: 12px; color: var(--muted); }
        .hero-form-box .btn-google { width: 100%; padding: 12px; border: 1.5px solid #e8d4d8; border-radius: 12px; background: var(--white); font-size: 14px; font-weight: 600; color: var(--dark); cursor: pointer; transition: all .2s; }
        .hero-form-box .btn-google:hover { border-color: var(--primary); color: var(--primary); }
        .hero-form-box .register-link { text-align: center; margin-top: 18px; font-size: 14px; color: var(--muted); }
        .hero-form-box .register-link a { color: var(--primary); font-weight: 700; }

        .section { padding: 80px 24px; max-width: 1100px; margin: 0 auto; }
        .section-label { font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--primary); margin-bottom: 8px; }
        .section-title { font-family: 'Syne', sans-serif; font-size: clamp(28px, 3vw, 36px); font-weight: 800; color: var(--dark); margin-bottom: 16px; }
        .section-desc { color: var(--muted); font-size: 16px; margin-bottom: 40px; max-width: 640px; line-height: 1.7; }
        .benefit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
        .benefit-card {
            background: var(--white); border-radius: 16px; padding: 28px; border: 1px solid rgba(196, 30, 58, .08);
            transition: all .3s; display: flex; gap: 20px; align-items: flex-start;
        }
        .benefit-card:hover { border-color: var(--primary); box-shadow: 0 16px 40px rgba(196, 30, 58, .08); transform: translateY(-4px); }
        .benefit-icon { width: 56px; height: 56px; min-width: 56px; border-radius: 14px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); display: flex; align-items: center; justify-content: center; font-size: 26px; color: var(--white); }
        .benefit-card h3 { font-size: 17px; font-weight: 700; color: var(--dark); margin-bottom: 8px; }
        .benefit-card p { font-size: 14px; color: var(--muted); line-height: 1.6; }

        .three-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 32px; }
        @media (max-width: 900px) { .three-cols { grid-template-columns: 1fr; } }
        .feature-block { background: var(--white); border-radius: 16px; padding: 28px; text-align: center; border: 1px solid rgba(196, 30, 58, .08); transition: all .3s; }
        .feature-block:hover { border-color: var(--primary); box-shadow: 0 12px 32px rgba(196, 30, 58, .06); }
        .feature-block .icon { width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 16px; background: var(--primary-soft); display: flex; align-items: center; justify-content: center; font-size: 28px; }
        .feature-block h3 { font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 8px; }
        .feature-block p { font-size: 13px; color: var(--muted); line-height: 1.6; }

        .cta-block { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 24px; padding: 48px 40px; text-align: center; margin: 48px 0; }
        .cta-block .section-label { color: rgba(255,255,255,.8); }
        .cta-block .section-title { color: var(--white); }
        .cta-block .section-desc { color: rgba(255,255,255,.9); margin-bottom: 28px; }
        .cta-block .btn-cta { display: inline-block; background: var(--white); color: var(--primary); padding: 16px 36px; border-radius: 12px; font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; transition: all .2s; }
        .cta-block .btn-cta:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,0,0,.2); }

        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 40px; }
        @media (max-width: 700px) { .stats-row { grid-template-columns: 1fr; } }
        .stat-item { text-align: center; padding: 28px 20px; background: var(--white); border-radius: 16px; border: 1px solid rgba(196, 30, 58, .08); }
        .stat-item .icon { width: 56px; height: 56px; margin: 0 auto 12px; border-radius: 14px; background: var(--primary-soft); display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-value { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; color: var(--primary); }
        .stat-label { font-size: 13px; color: var(--muted); margin-top: 4px; }

        .faq-list { max-width: 720px; margin: 0 auto; }
        .faq-item { background: var(--white); border: 1px solid rgba(196, 30, 58, .08); border-radius: 14px; margin-bottom: 12px; overflow: hidden; transition: all .2s; }
        .faq-item:hover { border-color: rgba(196, 30, 58, .2); }
        .faq-q { padding: 20px 24px; font-weight: 600; font-size: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; color: var(--dark); }
        .faq-q span { color: var(--primary); font-size: 20px; transition: transform .25s; }
        .faq-item.open .faq-q span { transform: rotate(45deg); }
        .faq-a { padding: 0 24px; font-size: 14px; color: var(--muted); line-height: 1.7; max-height: 0; overflow: hidden; transition: max-height .3s; }
        .faq-item.open .faq-a { padding: 0 24px 20px 24px; max-height: 300px; }

        .why-us-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; margin-top: 40px; }
        @media (max-width: 900px) { .why-us-grid { grid-template-columns: 1fr; } }
        .why-us-icons { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .why-us-icon { display: flex; align-items: center; gap: 16px; padding: 20px; background: var(--primary-soft); border-radius: 14px; border: 1px solid rgba(196, 30, 58, .1); }
        .why-us-icon .ico { width: 48px; height: 48px; border-radius: 12px; background: var(--primary); color: var(--white); display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .why-us-icon span { font-weight: 700; font-size: 14px; color: var(--dark); }

        .footer { background: var(--dark2); color: rgba(255,255,255,.7); padding: 40px 24px; text-align: center; }
        .footer-links { display: flex; gap: 24px; justify-content: center; margin-bottom: 20px; flex-wrap: wrap; }
        .footer-links a { color: rgba(255,255,255,.8); font-size: 14px; }
        .footer-links a:hover { color: var(--white); }
        .footer-copy { font-size: 13px; color: rgba(255,255,255,.5); }

        @media (max-width: 900px) {
            .hero-inner { grid-template-columns: 1fr; text-align: center; }
            .hero-desc { margin-left: auto; margin-right: auto; }
            .hero-form-box { max-width: 400px; margin: 0 auto; }
            .nav-links .hide-mob { display: none; }
        }
    </style>
</head>
<body>

<nav class="nav">
    <a href="<?= h($currentPath) ?>" class="nav-logo">SMM<span>Turk</span></a>
    <div class="nav-links">
        <a href="#benefits" class="hide-mob"><?= h(__('benefits')) ?></a>
        <a href="#services" class="hide-mob"><?= h(__('services_nav')) ?></a>
        <a href="#faq" class="hide-mob"><?= h(__('faq_nav')) ?></a>
        <div class="nav-lang">
            <button type="button" class="nav-lang-btn" id="langBtn" aria-haspopup="true" aria-expanded="false">
                <?= strtoupper($lang) ?> ▼
            </button>
            <div class="nav-lang-dropdown" id="langDropdown">
                <?php foreach (Lang::allowed() as $l): ?>
                <a href="<?= h($currentPath . $langParam . $l) ?>"><?= $l === 'en' ? 'English' : ($l === 'tr' ? 'Türkçe' : ($l === 'de' ? 'Deutsch' : 'Français')) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <a href="/login.php"><?= h(__('nav_sign_in')) ?></a>
        <a href="/login.php?mode=register" class="nav-btn"><?= h(__('nav_sign_up')) ?> →</a>
    </div>
</nav>

<section class="hero">
    <div class="hero-inner">
        <div>
            <span class="hero-badge"><?= h(__('hero_badge')) ?></span>
            <h1><?= h(__('hero_title')) ?></h1>
            <p class="hero-desc"><?= h(__('hero_desc')) ?></p>
        </div>
        <div class="hero-form-box">
            <form method="POST" action="/login.php">
                <div class="form-group">
                    <label class="form-label"><?= h(__('login_username')) ?></label>
                    <input type="text" name="email" class="form-control" placeholder="<?= h(__('login_username')) ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= h(__('login_password')) ?></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="remember"><label><input type="checkbox" name="remember"> <?= h(__('remember_me')) ?></label></div>
                <div style="margin-bottom:12px;"><a href="/login.php" class="forgot"><?= h(__('forgot_password')) ?></a></div>
                <button type="submit" class="btn-login"><?= h(__('btn_login')) ?> →</button>
                <div class="divider">— <?= h(__('login_with_google')) ?> —</div>
                <a href="/login.php" class="btn-google"><?= h(__('login_with_google')) ?></a>
                <p class="register-link"><?= h(__('no_account')) ?> <a href="/login.php?mode=register"><?= h(__('register')) ?></a></p>
            </form>
        </div>
    </div>
</section>

<section id="benefits" class="section">
    <div class="section-label"><?= h(__('benefit_heading')) ?></div>
    <h2 class="section-title"><?= h(__('benefit_title')) ?></h2>
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

<section class="section" style="background: var(--white); padding: 60px 24px;">
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
        <a href="/login.php?mode=register" class="btn-cta"><?= h(__('sign_up_now')) ?> →</a>
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
            <a href="/login.php?mode=register" class="nav-btn" style="display:inline-block;"><?= h(__('sign_up_now')) ?> →</a>
        </div>
    </div>
</section>

<section class="section" style="padding-top: 0;">
    <div class="cta-block">
        <h2 class="section-title"><?= h(__('cta_ready')) ?></h2>
        <p class="section-desc"><?= h(__('cta_join')) ?></p>
        <a href="/login.php?mode=register" class="btn-cta"><?= h(__('cta_btn')) ?></a>
    </div>
</section>

<footer class="footer">
    <div class="footer-links">
        <a href="/login.php"><?= h(__('footer_login')) ?></a>
        <a href="/login.php?mode=register"><?= h(__('footer_signup')) ?></a>
        <a href="/api-page.php"><?= h(__('footer_api')) ?></a>
        <a href="/tickets.php"><?= h(__('footer_support')) ?></a>
    </div>
    <div class="footer-copy">© <?= date('Y') ?> <?= h($siteName) ?>. <?= h(__('footer_copyright')) ?>.</div>
</footer>

<script>
document.getElementById('langBtn').addEventListener('click', function() {
    document.getElementById('langDropdown').classList.toggle('open');
});
document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-lang')) document.getElementById('langDropdown').classList.remove('open');
});
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
