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
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#0a0a1a 0%,#1a1a3a 50%,#0a0a1a 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:420px;box-shadow:0 24px 80px rgba(0,0,0,.4);text-align:center}
.logo{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;margin-bottom:20px}
.logo span{color:#4d4dff}
.msg{padding:14px;border-radius:12px;font-size:14px;margin-bottom:20px}
.msg.ok{background:#e8ffe8;color:#007700;border:1px solid #b3ffb3}
.msg.err{background:#ffe8e8;color:#cc0000;border:1px solid #ffb3b3}
.btn{display:inline-block;padding:12px 24px;background:#1a1aff;color:#fff;border-radius:12px;text-decoration:none;font-weight:700;margin-top:10px}
.btn:hover{background:#0000cc}
</style>
</head>
<body>
<div class="box">
  <div class="logo">SMM<span>Turk</span></div>
  <div class="msg <?= $success ? 'ok' : 'err' ?>"><?= htmlspecialchars($message) ?></div>
  <a href="/login.php" class="btn">Go to Login</a>
</div>
</body>
</html>
