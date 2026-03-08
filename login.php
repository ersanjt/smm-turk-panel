<?php
require_once __DIR__ . '/includes/init.php';

if ($auth->isLoggedIn()) {
    redirect('/index.php');
}

$mode  = $_GET['mode'] ?? 'login';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'login') {
        $result = $auth->login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
        if ($result['success']) {
            redirect('/index.php');
        } else {
            $error = $result['error'];
        }
    } else {
        $result = $auth->register(
            trim($_POST['username'] ?? ''),
            trim($_POST['email'] ?? ''),
            $_POST['password'] ?? '',
            trim($_POST['ref'] ?? '')
        );
        if ($result['success']) {
            $success = '✅ Account created! Please login.';
            $mode = 'login';
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SMM Turk — <?= $mode === 'login' ? 'Login' : 'Register' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#0a0a1a 0%,#1a1a3a 50%,#0a0a1a 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.auth-box{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:420px;box-shadow:0 24px 80px rgba(0,0,0,.4)}
.logo{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;text-align:center;margin-bottom:6px}
.logo span{color:#4d4dff}
.tagline{text-align:center;color:#6b6b8a;font-size:13px;margin-bottom:28px}
.tabs{display:flex;background:#f0f2ff;border-radius:12px;padding:4px;margin-bottom:24px}
.tab{flex:1;padding:9px;text-align:center;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;color:#6b6b8a;text-decoration:none;transition:all .2s}
.tab.active{background:#1a1aff;color:#fff}
.form-group{margin-bottom:14px}
.form-label{display:block;font-size:11px;font-weight:700;color:#6b6b8a;margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px}
.form-control{width:100%;padding:11px 14px;border:1.5px solid #e0e4ff;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:14px;outline:none;transition:border-color .2s;background:#f8f9ff}
.form-control:focus{border-color:#1a1aff;background:#fff}
.btn{width:100%;padding:13px;background:#1a1aff;color:#fff;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-size:15px;font-weight:700;cursor:pointer;transition:all .2s;margin-top:6px}
.btn:hover{background:#0000cc;transform:translateY(-1px);box-shadow:0 6px 20px rgba(26,26,255,.3)}
.alert{padding:11px 14px;border-radius:10px;font-size:13px;margin-bottom:14px;font-weight:500}
.alert-error{background:#ffe8e8;color:#cc0000;border:1px solid #ffb3b3}
.alert-success{background:#e8ffe8;color:#007700;border:1px solid #b3ffb3}
.footer-link{text-align:center;margin-top:18px;font-size:12.5px;color:#6b6b8a}
.footer-link a{color:#1a1aff;font-weight:600}
</style>
</head>
<body>

<div class="auth-box">
  <div class="logo">SMM<span>Turk</span></div>
  <div class="tagline">Social Media Marketing Panel</div>

  <div class="tabs">
    <a href="?mode=login" class="tab <?= $mode === 'login' ? 'active' : '' ?>">Login</a>
    <a href="?mode=register" class="tab <?= $mode === 'register' ? 'active' : '' ?>">Register</a>
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
      Don't have an account? <a href="?mode=register">Register</a>
    <?php else: ?>
      Already have an account? <a href="?mode=login">Login</a>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
