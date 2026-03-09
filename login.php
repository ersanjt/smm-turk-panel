<?php
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/app/RateLimit.php';

if ($auth->isLoggedIn()) {
    redirect('/index.php');
}
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';

$mode  = $_GET['mode'] ?? 'login';
$error = '';
$success = '';
$db = Database::getInstance();
$googleLoginEnabled = defined('GOOGLE_CLIENT_ID') && trim(GOOGLE_CLIENT_ID) !== '';
$rateLimit = new RateLimit(5, 900);

// Flash from redirect (e.g. Google callback error)
$flash = $_SESSION['flash'] ?? null;
if ($flash) {
    unset($_SESSION['flash']);
    if ($flash['type'] === 'error') $error = $flash['message'];
    else $success = $flash['message'];
}
$registrationEnabled = ($db->getSetting('registration_enabled') ?? '1') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfOk = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '');
    if (!$csrfOk) {
        $error = 'Invalid request. Please try again.';
    } elseif ($mode === 'login') {
        if ($rateLimit->isLimited()) {
            $error = 'Too many login attempts. Please try again in 15 minutes.';
        } else {
            $result = $auth->login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
            if ($result['success']) {
                $rateLimit->clear();
                redirect('/index.php');
            } else {
                $rateLimit->recordAttempt();
                $error = $result['error'];
            }
        }
    } else {
        if (!$registrationEnabled) {
            $error = 'Registration is currently disabled.';
        } else {
            $result = $auth->register(
                trim($_POST['username'] ?? ''),
                trim($_POST['email'] ?? ''),
                $_POST['password'] ?? '',
                trim($_POST['ref'] ?? '')
            );
            if ($result['success']) {
                $success = '✅ Account created! Check your email for the verification link, then login.';
                $mode = 'login';
            } else {
                $error = $result['error'];
            }
        }
    }
}
// Ensure CSRF token exists for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($siteName) ?> — <?= $mode === 'login' ? 'Login' : 'Register' ?></title>
<meta name="description" content="Login or register to SMM Turk — cheapest SMM panel. Crypto deposits, API, 24/7 support.">
<?php if (defined('SITE_URL') && SITE_URL !== ''): ?>
<link rel="canonical" href="<?= h(rtrim(SITE_URL, '/') . '/login.php') ?>">
<?php endif; ?>
<link rel="icon" type="image/svg+xml" href="/assets/img/logo-icon.svg?v=2">
<link rel="apple-touch-icon" href="/assets/img/logo-icon.svg?v=2">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:linear-gradient(135deg,#1a0a0e 0%,#2d1519 50%,#1a0a0e 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.auth-box{background:#fff;border-radius:24px;padding:40px;width:100%;max-width:420px;box-shadow:0 24px 80px rgba(0,0,0,.25),0 0 0 1px rgba(255,255,255,.05)}
.auth-logo{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:8px}
.auth-logo img{width:44px;height:44px;border-radius:12px}
.auth-logo .logo{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:#1a0a0e}
.auth-logo .logo span{color:#E30A17}
.tagline{text-align:center;color:#6b4a50;font-size:13px;margin-bottom:28px}
.tabs{display:flex;background:#fef0f1;border-radius:12px;padding:4px;margin-bottom:24px}
.tab{flex:1;padding:10px;text-align:center;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;color:#6b4a50;text-decoration:none;transition:all .2s}
.tab.active{background:#E30A17;color:#fff}
.form-group{margin-bottom:14px}
.form-label{display:block;font-size:11px;font-weight:700;color:#6b4a50;margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px}
.input-wrap{display:flex;align-items:center;background:#fef8f9;border:1.5px solid #f0e6e8;border-radius:10px;overflow:hidden;transition:border-color .2s,box-shadow .2s}
.input-wrap:focus-within{border-color:#E30A17;background:#fff;box-shadow:0 0 0 3px rgba(227,10,23,.12)}
.input-icon{width:44px;height:44px;display:flex;align-items:center;justify-content:center;color:#6b4a50;flex-shrink:0}
.input-wrap:focus-within .input-icon{color:#E30A17}
.input-wrap .form-control{border:none;border-radius:0;padding:12px 14px 12px 0;background:transparent}
.input-wrap .form-control:focus{box-shadow:none}
.form-control{width:100%;padding:12px 14px;border:1.5px solid #f0e6e8;border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;outline:none;transition:border-color .2s;background:#fef8f9}
.form-control:focus{border-color:#E30A17;background:#fff}
.btn{width:100%;padding:13px;background:#E30A17;color:#fff;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-size:15px;font-weight:700;cursor:pointer;transition:all .2s;margin-top:6px;display:inline-flex;align-items:center;justify-content:center;gap:8px}
.btn svg{width:18px;height:18px}
.btn:hover{background:#B90812;transform:translateY(-1px);box-shadow:0 8px 24px rgba(227,10,23,.35)}
.alert{padding:11px 14px;border-radius:10px;font-size:13px;margin-bottom:14px;font-weight:500}
.alert-error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.auth-divider{display:flex;align-items:center;gap:12px;margin:18px 0;font-size:12px;color:#6b4a50}
.auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:var(--border,#f0e6e8)}
.btn-google{width:100%;padding:12px 16px;background:#fff;color:#1a0a0e;border:2px solid #e5e7eb;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:10px;transition:all .2s;text-decoration:none}
.btn-google:hover{background:#f9fafb;border-color:#E30A17;color:#E30A17;transform:translateY(-1px)}
.footer-link{text-align:center;margin-top:18px;font-size:12.5px;color:#6b4a50}
.footer-link a{color:#E30A17;font-weight:600}
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.auth-box{animation:fadeInUp .4s ease both}
@media(max-width:480px){.auth-box{margin:12px;padding:24px 20px;border-radius:20px}}
@media(prefers-reduced-motion:reduce){.auth-box{animation:none}}
</style>
</head>
<body>

<div class="auth-box">
  <a href="/home.php" style="display:block;text-align:center;margin-bottom:8px;font-size:12px;color:#6b4a50;">← Back to Home</a>
  <div class="auth-logo">
    <img src="/assets/img/logo-icon.svg?v=2" alt="" width="44" height="44">
    <span class="logo">SMM<span>Turk</span></span>
  </div>
  <div class="tagline">Social Media Marketing Panel</div>

  <div class="tabs">
    <a href="?mode=login" class="tab <?= $mode === 'login' ? 'active' : '' ?>">Login</a>
    <?php if ($registrationEnabled): ?>
    <a href="?mode=register" class="tab <?= $mode === 'register' ? 'active' : '' ?>">Register</a>
    <?php endif; ?>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($mode === 'login'): ?>
  <?php if ($googleLoginEnabled): ?>
  <a href="/login-google.php" class="btn-google">
    <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
    Sign in with Google
  </a>
  <div class="auth-divider">or</div>
  <?php endif; ?>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <div class="form-group">
      <label class="form-label">Email or Username</label>
      <div class="input-wrap">
        <span class="input-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
        <input type="text" name="email" class="form-control" placeholder="your@email.com" required autofocus>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <div class="input-wrap">
        <span class="input-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
    </div>
    <div style="margin-bottom:10px;"><a href="/forgot-password.php" style="font-size:13px;color:#E30A17;font-weight:600;">Forgot password?</a></div>
    <button type="submit" class="btn">Login <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></button>
  </form>
  <?php else: ?>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <div class="form-group">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" placeholder="johndoe" required minlength="3">
    </div>
    <div class="form-group">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required minlength="6">
    </div>
    <?php if (isset($_GET['ref'])): ?>
    <input type="hidden" name="ref" value="<?= htmlspecialchars($_GET['ref']) ?>">
    <?php endif; ?>
    <button type="submit" class="btn">✅ Create Account</button>
  </form>
  <?php endif; ?>

  <div class="footer-link">
    <?php if ($mode === 'login'): ?>
      <?php if ($registrationEnabled): ?>Don't have an account? <a href="?mode=register">Register</a><?php endif; ?>
    <?php else: ?>
      Already have an account? <a href="?mode=login">Login</a>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
