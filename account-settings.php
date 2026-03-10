<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$db = Database::getInstance();
$user = $auth->getCurrentUser();
$pageTitle = 'Account Settings';

$errors = [];

// Optional columns (after running migrate-account-settings.php)
$hasTimezone = array_key_exists('timezone', $user);
$hasTwoFactor = array_key_exists('two_factor_enabled', $user);
$hasApiKeyCreatedAt = array_key_exists('api_key_created_at', $user);
$hasAvatar = array_key_exists('avatar', $user);
$avatarPath = $hasAvatar && !empty(trim($user['avatar'] ?? '')) ? trim($user['avatar']) : null;

$userTimezone = $hasTimezone ? ($user['timezone'] ?? 'UTC') : 'UTC';
$twoFactorEnabled = $hasTwoFactor ? (int)($user['two_factor_enabled'] ?? 0) : 0;
$apiKeyCreatedAt = $hasApiKeyCreatedAt && !empty($user['api_key_created_at']) ? $user['api_key_created_at'] : null;

// Allowed image types and max size (2MB)
$avatarAllowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
$avatarMaxSize = 2 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid request. Please try again.');
        redirect(url('account-settings.php'));
    }

    if (isset($_POST['action']) && $_POST['action'] === 'change_email') {
        $newEmail = trim($_POST['new_email'] ?? '');
        $currentPassword = $_POST['current_password_email'] ?? '';
        $result = $auth->updateEmail($user['id'], $newEmail, $currentPassword);
        if ($result['success']) {
            flash('success', 'Email updated successfully.');
            redirect(url('account-settings.php'));
        }
        $errors['email'] = $result['error'];
    }

    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($newPass !== $confirm) {
            $errors['password'] = 'New password and confirmation do not match.';
        } else {
            $result = $auth->updatePassword($user['id'], $current, $newPass);
            if ($result['success']) {
                flash('success', 'Password changed successfully.');
                redirect(url('account-settings.php'));
            }
            $errors['password'] = $result['error'];
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'enable_2fa' && $hasTwoFactor) {
        $db->execute("UPDATE users SET two_factor_enabled = 1 WHERE id = ?", [$user['id']]);
        flash('success', 'Two-factor authentication enabled.');
        redirect(url('account-settings.php'));
    }

    if (isset($_POST['action']) && $_POST['action'] === 'save_timezone' && $hasTimezone) {
        $tz = trim($_POST['timezone'] ?? 'UTC');
        if (strlen($tz) > 0 && strlen($tz) <= 64) {
            $db->execute("UPDATE users SET timezone = ? WHERE id = ?", [$tz, $user['id']]);
            flash('success', 'Timezone saved.');
            redirect(url('account-settings.php'));
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'generate_api_key') {
        $newKey = bin2hex(random_bytes(20));
        if ($hasApiKeyCreatedAt) {
            $db->execute("UPDATE users SET api_key = ?, api_key_created_at = NOW() WHERE id = ?", [$newKey, $user['id']]);
        } else {
            $db->execute("UPDATE users SET api_key = ? WHERE id = ?", [$newKey, $user['id']]);
        }
        flash('success', 'New API key generated. Update your applications with the new key.');
        redirect(url('account-settings.php'));
    }

    if (isset($_POST['action']) && $_POST['action'] === 'upload_avatar' && $hasAvatar && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!isset($avatarAllowedTypes[$mime]) || $file['size'] > $avatarMaxSize) {
            flash('error', 'Please upload a valid image (JPEG, PNG, GIF or WebP, max 2 MB).');
        } else {
            $dir = ROOT_PATH . '/uploads/avatars';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $ext = $avatarAllowedTypes[$mime];
            $filename = (int)$user['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $fullPath = $dir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                $oldPath = $hasAvatar && !empty($user['avatar']) ? ROOT_PATH . '/uploads/' . $user['avatar'] : null;
                $relPath = 'avatars/' . $filename;
                $db->execute("UPDATE users SET avatar = ? WHERE id = ?", [$relPath, $user['id']]);
                if ($oldPath && file_exists($oldPath)) {
                    @unlink($oldPath);
                }
                flash('success', 'Profile photo updated.');
            } else {
                flash('error', 'Could not save the image. Try again.');
            }
            redirect(url('account-settings.php'));
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'remove_avatar' && $hasAvatar) {
        if ($avatarPath && file_exists(ROOT_PATH . '/uploads/' . $avatarPath)) {
            @unlink(ROOT_PATH . '/uploads/' . $avatarPath);
        }
        $db->execute("UPDATE users SET avatar = NULL WHERE id = ?", [$user['id']]);
        flash('success', 'Profile photo removed.');
        redirect(url('account-settings.php'));
    }
}

// Re-fetch user after possible updates
$user = $auth->getCurrentUser();
$userTimezone = $hasTimezone ? ($user['timezone'] ?? 'UTC') : 'UTC';
$twoFactorEnabled = $hasTwoFactor ? (int)($user['two_factor_enabled'] ?? 0) : 0;
$apiKeyCreatedAt = $hasApiKeyCreatedAt && !empty($user['api_key_created_at']) ? $user['api_key_created_at'] : null;
$avatarPath = $hasAvatar && !empty(trim($user['avatar'] ?? '')) ? trim($user['avatar']) : null;

$timezones = [
    'UTC' => '(UTC) Greenwich Mean Time, Western European Time',
    'Europe/Istanbul' => '(UTC+3) Turkey',
    'America/New_York' => '(UTC-5) Eastern Time',
    'Europe/London' => '(UTC+0) London',
    'Asia/Dubai' => '(UTC+4) Dubai',
    'Asia/Tehran' => '(UTC+3:30) Tehran',
];

$apiKeyMasked = substr($user['api_key'] ?? '', 0, 8) . '•••••••••••••';
$apiKeyFull = $user['api_key'] ?? '';

require_once __DIR__ . '/layouts/header.php';
?>

<style>
.account-settings-page { max-width: 640px; }
.account-settings-page .as-hero { margin-bottom: 24px; }
.account-settings-page .as-hero-title { display: flex; align-items: center; gap: 10px; font-size: 1.2rem; font-weight: 700; color: var(--text); }
.account-settings-page .as-hero-title svg { width: 24px; height: 24px; color: var(--primary); flex-shrink: 0; }
.account-settings-page .as-card { background: var(--white); border-radius: 16px; padding: 22px; margin-bottom: 20px; border: 1px solid var(--border); }
.account-settings-page .as-card h2 { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 16px; }
.account-settings-page .as-card h2 svg { width: 20px; height: 20px; color: var(--primary); opacity: .9; }
.account-settings-page .as-profile-summary { display: flex; align-items: center; gap: 16px; padding: 18px 20px; background: linear-gradient(135deg, rgba(227,10,23,.06) 0%, rgba(227,10,23,.02) 100%); border-radius: 14px; border: 1px solid var(--border); margin-bottom: 24px; }
.account-settings-page .as-profile-summary .as-avatar { width: 56px; height: 56px; min-width: 56px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary), var(--accent)); color: #fff; font-size: 22px; font-weight: 700; }
.account-settings-page .as-profile-summary .as-avatar img { width: 100%; height: 100%; object-fit: cover; }
.account-settings-page .as-profile-summary .as-info { min-width: 0; }
.account-settings-page .as-profile-summary .as-name { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.account-settings-page .as-profile-summary .as-email { font-size: 13px; color: var(--text-muted); }
.account-settings-page .as-section-divider { height: 1px; background: var(--border); margin: 20px 0; border: 0; }
.account-settings-page .form-group { margin-bottom: 14px; }
.account-settings-page .form-group:last-of-type { margin-bottom: 18px; }
.account-settings-page .as-api-row { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 12px; }
.account-settings-page .as-api-row input { flex: 1; min-width: 180px; font-family: monospace; font-size: 13px; }
.account-settings-page .as-api-copy { padding: 10px 16px; font-size: 13px; white-space: nowrap; }
.account-settings-page .as-avatar-block { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 20px; }
.account-settings-page .as-avatar-preview { flex-shrink: 0; }
.account-settings-page .as-avatar-preview img { width: 88px; height: 88px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border); display: block; }
.account-settings-page .as-avatar-placeholder { width: 88px; height: 88px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 32px; font-weight: 700; }
.account-settings-page .as-avatar-actions { flex: 1; min-width: 200px; }
.account-settings-page .as-avatar-actions .btn { margin-right: 8px; margin-bottom: 8px; }
.account-settings-page .as-hint { font-size: 12px; color: var(--text-muted); margin-top: 6px; margin-bottom: 12px; }
.account-settings-page .alert { margin-bottom: 14px; }
</style>

<div class="account-settings-page">
  <div class="as-hero">
    <h1 class="as-hero-title">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Account Settings
    </h1>
  </div>

  <!-- Profile summary -->
  <div class="as-profile-summary">
    <div class="as-avatar">
      <?php if ($avatarPath): ?>
      <img src="<?= h(path('uploads/' . $avatarPath)) ?>?v=<?= time() ?>" alt="">
      <?php else: ?>
      <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
      <?php endif; ?>
    </div>
    <div class="as-info">
      <div class="as-name"><?= h($user['username'] ?? '') ?></div>
      <div class="as-email"><?= h($user['email'] ?? '') ?></div>
    </div>
  </div>

  <?php if (!($hasTimezone || $hasTwoFactor || $hasApiKeyCreatedAt || $hasAvatar)): ?>
  <div class="alert alert-info">
    Run <code>php migrate-account-settings.php</code> once to enable timezone, two-factor, API key date and profile photo.
  </div>
  <?php endif; ?>

  <!-- Profile photo -->
  <?php if ($hasAvatar): ?>
  <div class="as-card">
    <h2>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Profile photo
    </h2>
    <div class="as-avatar-block">
      <div class="as-avatar-preview">
        <?php if ($avatarPath): ?>
        <img src="<?= h(path('uploads/' . $avatarPath)) ?>?v=<?= time() ?>" alt="Profile" width="88" height="88">
        <?php else: ?>
        <div class="as-avatar-placeholder"><?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?></div>
        <?php endif; ?>
      </div>
      <div class="as-avatar-actions">
        <form method="post" enctype="multipart/form-data" style="margin-bottom:12px;">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="upload_avatar">
          <div class="form-group" style="margin-bottom:10px;">
            <label class="form-label">Upload photo</label>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control" style="padding:8px;">
          </div>
          <p class="as-hint">JPEG, PNG, GIF or WebP. Max 2 MB.</p>
          <button type="submit" class="btn btn-primary">Upload</button>
        </form>
        <?php if ($avatarPath): ?>
        <form method="post" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="remove_avatar">
          <button type="submit" class="btn btn-danger">Remove photo</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Email -->
  <div class="as-card">
    <h2>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      Email address
    </h2>
    <div class="form-group">
      <label class="form-label">Username</label>
      <input type="text" class="form-control" value="<?= h($user['username'] ?? '') ?>" readonly disabled>
    </div>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="change_email">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="new_email" class="form-control" value="<?= h($user['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Current password</label>
        <input type="password" name="current_password_email" class="form-control" placeholder="Enter current password" required>
      </div>
      <?php if (!empty($errors['email'])): ?>
      <div class="alert alert-error"><?= h($errors['email']) ?></div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary">Update email</button>
    </form>
  </div>

  <!-- Password -->
  <div class="as-card">
    <h2>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      Password
    </h2>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label class="form-label">Current password</label>
        <input type="password" name="current_password" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">New password</label>
        <input type="password" name="new_password" class="form-control" minlength="6" required>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm new password</label>
        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
      </div>
      <?php if (!empty($errors['password'])): ?>
      <div class="alert alert-error"><?= h($errors['password']) ?></div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary">Change password</button>
    </form>
  </div>

  <?php if ($hasTwoFactor): ?>
  <div class="as-card">
    <h2>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
      Two-factor authentication
    </h2>
    <p class="as-hint" style="margin-bottom:14px;">Extra protection: sign-in codes sent to your email.</p>
    <?php if ($twoFactorEnabled): ?>
    <p class="alert alert-success" style="margin-bottom:0;">Two-factor authentication is enabled.</p>
    <?php else: ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="enable_2fa">
      <button type="submit" class="btn btn-primary">Enable 2FA</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($hasTimezone): ?>
  <div class="as-card">
    <h2>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Timezone
    </h2>
    <form method="post" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_timezone">
      <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0;">
        <label class="form-label">Timezone</label>
        <select name="timezone" class="form-control">
          <?php foreach ($timezones as $tz => $label): ?>
          <option value="<?= h($tz) ?>" <?= $userTimezone === $tz ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Save</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- API Key -->
  <div class="as-card">
    <h2>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
      API Key
    </h2>
    <div class="form-group" style="margin-bottom:8px;">
      <label class="form-label">Your API Key</label>
      <div class="as-api-row">
        <input type="text" id="apiKeyInput" class="form-control" value="<?= h($apiKeyMasked) ?>" readonly data-full="<?= h($apiKeyFull) ?>" data-masked="<?= h($apiKeyMasked) ?>">
        <button type="button" class="btn btn-primary as-api-copy" id="apiKeyCopyBtn">Copy</button>
      </div>
    </div>
    <?php if ($apiKeyCreatedAt): ?>
    <p class="as-hint">Created: <?= h(date('Y-m-d H:i', strtotime($apiKeyCreatedAt))) ?></p>
    <?php endif; ?>
    <form method="post" style="margin-top:12px;">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="generate_api_key">
      <button type="submit" class="btn btn-primary">Generate new API key</button>
    </form>
    <p class="as-hint" style="margin-top:10px;">Regenerating will invalidate the current key. Update your apps with the new key.</p>
  </div>
</div>

<script>
(function(){
  var inp = document.getElementById('apiKeyInput');
  var btn = document.getElementById('apiKeyCopyBtn');
  if (!inp || !btn) return;
  var full = inp.getAttribute('data-full') || '';
  var masked = inp.getAttribute('data-masked') || '';
  btn.addEventListener('click', function(){
    var text = full && full.length > 8 ? full : (inp.value || '');
    if (!text) return;
    navigator.clipboard.writeText(text).then(function(){
      btn.textContent = 'Copied!';
      btn.style.background = 'var(--green)';
      setTimeout(function(){ btn.textContent = 'Copy'; btn.style.background = ''; }, 2000);
    });
  });
  inp.addEventListener('focus', function(){ this.value = full || masked; this.select(); });
  inp.addEventListener('blur', function(){ if (full && full.length > 8) this.value = masked; });
})();
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
