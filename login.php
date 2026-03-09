<?php
require_once __DIR__ . '/app/init.php';

if ($auth->isLoggedIn()) {
    redirect('/index.php');
}
$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';

$mode  = $_GET['mode'] ?? 'login';
$error = '';
$success = '';
$db = Database::getInstance();
$registrationEnabled = ($db->getSetting('registration_enabled') ?? '1') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'login') {
        $result = $auth->login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
        if ($result['success']) {
            redirect('/index.php');
        } else {
            $error = $result['error'];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($siteName) ?> — <?= $mode === 'login' ? 'Login' : 'Register' ?></title>
<link rel="icon" type="image/svg+xml" href="/assets/img/logo-icon.svg">
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
.form-control{width:100%;padding:12px 14px;border:1.5px solid #f0e6e8;border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;outline:none;transition:border-color .2s;background:#fef8f9}
.form-control:focus{border-color:#E30A17;background:#fff}
.btn{width:100%;padding:13px;background:#E30A17;color:#fff;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-size:15px;font-weight:700;cursor:pointer;transition:all .2s;margin-top:6px}
.btn:hover{background:#B90812;transform:translateY(-1px);box-shadow:0 8px 24px rgba(227,10,23,.35)}
.alert{padding:11px 14px;border-radius:10px;font-size:13px;margin-bottom:14px;font-weight:500}
.alert-error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.footer-link{text-align:center;margin-top:18px;font-size:12.5px;color:#6b4a50}
.footer-link a{color:#E30A17;font-weight:600}
</style>
</head>
<body>

<div class="auth-box">
  <a href="/home.php" style="display:block;text-align:center;margin-bottom:8px;font-size:12px;color:#6b4a50;">← Back to Home</a>
  <div class="auth-logo">
    <img src="/assets/img/logo-icon.svg" alt="" width="44" height="44">
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
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Email or Username</label>
      <input type="text" name="email" class="form-control" placeholder="your@email.com" required autofocus>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn">🚀 Login</button>
  </form>
  <?php else: ?>
  <form method="POST">
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
