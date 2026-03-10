<?php
require_once __DIR__ . '/app/init.php';
$db = Database::getInstance();

$token = trim($_GET['token'] ?? '');
$message = '';
$success = false;

if ($token !== '') {
    $user = $db->fetch(
        "SELECT id, username, email_verification_expires FROM users WHERE email_verification_token = ? AND status = 'pending'",
        [$token]
    );
    if ($user && $user['email_verification_expires'] && strtotime($user['email_verification_expires']) > time()) {
        $db->execute(
            "UPDATE users SET status = 'active', email_verification_token = NULL, email_verification_expires = NULL WHERE id = ?",
            [$user['id']]
        );
        $message = 'Email verified successfully! You can now log in.';
        $success = true;
    } elseif ($user) {
        $message = 'This verification link has expired. Please register again or request a new link.';
    } else {
        $message = 'Invalid or already used verification link.';
    }
} else {
    $message = 'No verification token provided.';
}

$siteName = defined('SITE_NAME') ? SITE_NAME : 'SMM Turk';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($siteName) ?> — Email Verification</title>
<link rel="icon" type="image/svg+xml" href="<?= h(path('assets/img/logo-icon.svg?v=3')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:linear-gradient(135deg,#1a0a0e 0%,#2d1519 50%,#1a0a0e 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:#fff;border-radius:24px;padding:40px;width:100%;max-width:420px;box-shadow:0 24px 80px rgba(0,0,0,.25);text-align:center}
.box-logo{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;margin-bottom:20px}
.box-logo img{width:48px;height:48px;border-radius:12px;flex-shrink:0}
.box-logo .logo{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:#1a0a0e;letter-spacing:-.04em;line-height:1.1;text-transform:uppercase}
.box-logo .logo span{color:#E30A17;letter-spacing:-.02em}
.msg{padding:14px;border-radius:12px;font-size:14px;margin-bottom:20px}
.msg.ok{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.msg.err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.btn{display:inline-block;padding:12px 24px;background:#E30A17;color:#fff;border-radius:12px;text-decoration:none;font-weight:700;margin-top:10px;transition:all .2s}
.btn:hover{background:#B90812;box-shadow:0 8px 24px rgba(227,10,23,.35)}
@media(max-width:480px){.box{margin:12px;padding:24px 20px;border-radius:20px}}
</style>
</head>
<body>
<div class="box">
  <div class="box-logo">
    <img src="<?= h(path('assets/img/logo-icon.svg?v=3')) ?>" alt="" width="48" height="48">
    <span class="logo">SMM <span>TURK</span></span>
  </div>
  <div class="msg <?= $success ? 'ok' : 'err' ?>"><?= htmlspecialchars($message) ?></div>
  <a href="<?= h(path('login.php')) ?>" class="btn">Go to Login</a>
</div>
</body>
</html>
