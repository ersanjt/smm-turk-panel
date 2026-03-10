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

    // Change email
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

    // Change password
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

    // Enable Two-Factor
    if (isset($_POST['action']) && $_POST['action'] === 'enable_2fa' && $hasTwoFactor) {
        $db->execute("UPDATE users SET two_factor_enabled = 1 WHERE id = ?", [$user['id']]);
        flash('success', 'Two-factor authentication enabled.');
        redirect(url('account-settings.php'));
    }

    // Save timezone
    if (isset($_POST['action']) && $_POST['action'] === 'save_timezone' && $hasTimezone) {
        $tz = trim($_POST['timezone'] ?? 'UTC');
        if (strlen($tz) > 0 && strlen($tz) <= 64) {
            $db->execute("UPDATE users SET timezone = ? WHERE id = ?", [$tz, $user['id']]);
            flash('success', 'Timezone saved.');
            redirect(url('account-settings.php'));
        }
    }

    // Generate new API key
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

    // Upload profile photo / avatar
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

    // Remove profile photo
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

require_once __DIR__ . '/layouts/header.php';
?>

<div class="card" style="margin-bottom:24px;">
  <div class="card-title">Account Settings</div>

  <?php if (!($hasTimezone || $hasTwoFactor || $hasApiKeyCreatedAt || $hasAvatar)): ?>
  <div class="alert alert-info" style="margin-bottom:18px;">
    Run <code>php migrate-account-settings.php</code> once to enable timezone, two-factor, API key date and profile photo. Until then, only email and password can be changed.
  </div>
  <?php endif; ?>

  <!-- Profile photo / avatar -->
  <?php if ($hasAvatar): ?>
  <div class="profile-avatar-section" style="margin-bottom:28px;padding-bottom:24px;border-bottom:1px solid var(--border);">
    <div class="card-title" style="font-size:14px;margin-bottom:14px;">Profile photo</div>
    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:20px;">
      <div class="profile-avatar-preview" style="flex-shrink:0;">
        <?php if ($avatarPath): ?>
        <img src="<?= h(path('uploads/' . $avatarPath)) ?>?v=<?= time() ?>" alt="Profile" class="profile-avatar-img" width="96" height="96" style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--border);display:block;">
        <?php else: ?>
        <div class="profile-avatar-placeholder" style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;color:#fff;font-size:36px;font-weight:700;"><?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?></div>
        <?php endif; ?>
      </div>
      <div style="flex:1;min-width:200px;">
        <form method="post" enctype="multipart/form-data" style="margin-bottom:12px;">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="upload_avatar">
          <div class="form-group" style="margin-bottom:10px;">
            <label class="form-label">Upload photo or logo</label>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control" style="padding:8px;">
          </div>
          <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">JPEG, PNG, GIF or WebP. Max 2 MB. Your photo will appear in the sidebar and across your account.</p>
          <button type="submit" class="btn btn-primary">Upload</button>
        </form>
        <?php if ($avatarPath): ?>
        <form method="post" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="remove_avatar">
          <button type="submit" class="btn btn-danger" style="margin-left:0;">Remove photo</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- User information -->
  <div style="margin-bottom:28px;">
    <div class="form-group">
      <label class="form-label">Username</label>
      <input type="text" class="form-control" value="<?= h($user['username'] ?? '') ?>" readonly disabled>
    </div>
    <form method="post" style="margin-top:12px;">
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
      <div class="alert alert-error" style="margin-bottom:12px;"><?= h($errors['email']) ?></div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary">Change email</button>
    </form>
  </div>

  <hr style="border:0;height:1px;background:var(--border);margin:24px 0;">

  <!-- Password -->
  <div style="margin-bottom:28px;">
    <div class="card-title" style="font-size:14px;margin-bottom:12px;">Password</div>
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
      <div class="alert alert-error" style="margin-bottom:12px;"><?= h($errors['password']) ?></div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary">Change password</button>
    </form>
  </div>

  <?php if ($hasTwoFactor): ?>
  <hr style="border:0;height:1px;background:var(--border);margin:24px 0;">
  <div style="margin-bottom:28px;">
    <div class="card-title" style="font-size:14px;margin-bottom:8px;">Two-factor authentication</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px;">
      Email-based option to add an extra layer of protection to your account. When signing in you'll need to enter a code that will be sent to your email address.
    </p>
    <?php if ($twoFactorEnabled): ?>
    <p class="alert alert-success">Two-factor authentication is enabled.</p>
    <?php else: ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="enable_2fa">
      <button type="submit" class="btn btn-primary">Enable</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($hasTimezone): ?>
  <hr style="border:0;height:1px;background:var(--border);margin:24px 0;">
  <div style="margin-bottom:28px;">
    <div class="card-title" style="font-size:14px;margin-bottom:12px;">Timezone</div>
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

  <hr style="border:0;height:1px;background:var(--border);margin:24px 0;">
  <div>
    <div class="card-title" style="font-size:14px;margin-bottom:8px;">API Key</div>
    <div class="form-group">
      <label class="form-label">Your API Key</label>
      <input type="text" class="form-control" value="<?= h(substr($user['api_key'] ?? '', 0, 8) . '***********') ?>" readonly style="font-family:monospace;">
    </div>
    <?php if ($apiKeyCreatedAt): ?>
    <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">Created: <?= h(date('Y-m-d H:i', strtotime($apiKeyCreatedAt))) ?></p>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="generate_api_key">
      <button type="submit" class="btn btn-primary">Generate new</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
