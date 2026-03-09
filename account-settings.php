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

$userTimezone = $hasTimezone ? ($user['timezone'] ?? 'UTC') : 'UTC';
$twoFactorEnabled = $hasTwoFactor ? (int)($user['two_factor_enabled'] ?? 0) : 0;
$apiKeyCreatedAt = $hasApiKeyCreatedAt && !empty($user['api_key_created_at']) ? $user['api_key_created_at'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid request. Please try again.');
        redirect('/account-settings.php');
    }

    // Change email
    if (isset($_POST['action']) && $_POST['action'] === 'change_email') {
        $newEmail = trim($_POST['new_email'] ?? '');
        $currentPassword = $_POST['current_password_email'] ?? '';
        $result = $auth->updateEmail($user['id'], $newEmail, $currentPassword);
        if ($result['success']) {
            flash('success', 'Email updated successfully.');
            redirect('/account-settings.php');
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
                redirect('/account-settings.php');
            }
            $errors['password'] = $result['error'];
        }
    }

    // Enable Two-Factor
    if (isset($_POST['action']) && $_POST['action'] === 'enable_2fa' && $hasTwoFactor) {
        $db->execute("UPDATE users SET two_factor_enabled = 1 WHERE id = ?", [$user['id']]);
        flash('success', 'Two-factor authentication enabled.');
        redirect('/account-settings.php');
    }

    // Save timezone
    if (isset($_POST['action']) && $_POST['action'] === 'save_timezone' && $hasTimezone) {
        $tz = trim($_POST['timezone'] ?? 'UTC');
        if (strlen($tz) > 0 && strlen($tz) <= 64) {
            $db->execute("UPDATE users SET timezone = ? WHERE id = ?", [$tz, $user['id']]);
            flash('success', 'Timezone saved.');
            redirect('/account-settings.php');
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
        redirect('/account-settings.php');
    }
}

// Re-fetch user after possible updates
$user = $auth->getCurrentUser();
$userTimezone = $hasTimezone ? ($user['timezone'] ?? 'UTC') : 'UTC';
$twoFactorEnabled = $hasTwoFactor ? (int)($user['two_factor_enabled'] ?? 0) : 0;
$apiKeyCreatedAt = $hasApiKeyCreatedAt && !empty($user['api_key_created_at']) ? $user['api_key_created_at'] : null;

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

  <?php if (!($hasTimezone || $hasTwoFactor || $hasApiKeyCreatedAt)): ?>
  <div class="alert alert-info" style="margin-bottom:18px;">
    Run <code>php migrate-account-settings.php</code> once to enable timezone, two-factor and API key date. Until then, only email and password can be changed.
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
