<?php
require_once __DIR__ . '/../app/init.php';
$auth->requireAdmin();
$pageTitle = 'Settings';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $fields = ['site_name','site_url','api_key','markup_percent','min_deposit','referral_commission','referral_min_payout','registration_enabled','maintenance_mode',
        'wallet_btc','wallet_eth','wallet_usdt_trc20','wallet_usdt_erc20','wallet_bnb','wallet_sol'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $db->setSetting($f, trim($_POST[$f]));
        }
    }
    flash('success', '✅ Settings saved successfully.');
    redirect('/admin/admin-settings.php');
}

$settings = [];
$rows = $db->fetchAll("SELECT * FROM settings");
foreach ($rows as $r) $settings[$r['key']] = $r['value'];

function s(array $settings, string $key): string {
    return htmlspecialchars($settings[$key] ?? '', ENT_QUOTES);
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div style="max-width:700px;">
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="card" style="margin-bottom:18px;">
      <div class="card-title">🌐 Site Settings</div>
      <div class="grid2">
        <div class="form-group">
          <label class="form-label">Site Name</label>
          <input type="text" name="site_name" class="form-control" value="<?= s($settings,'site_name') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Site URL</label>
          <input type="url" name="site_url" class="form-control" value="<?= s($settings,'site_url') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Maintenance Mode</label>
        <select name="maintenance_mode" class="form-control">
          <option value="0" <?= ($settings['maintenance_mode']??'0')==='0'?'selected':'' ?>>Off</option>
          <option value="1" <?= ($settings['maintenance_mode']??'0')==='1'?'selected':'' ?>>On</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">User Registration</label>
        <select name="registration_enabled" class="form-control">
          <option value="1" <?= ($settings['registration_enabled']??'1')==='1'?'selected':'' ?>>Enabled</option>
          <option value="0" <?= ($settings['registration_enabled']??'1')==='0'?'selected':'' ?>>Disabled</option>
        </select>
      </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
      <div class="card-title">🔑 Provider API</div>
      <div class="form-group">
        <label class="form-label">SmmFollows API Key</label>
        <input type="text" name="api_key" class="form-control" value="<?= s($settings,'api_key') ?>" placeholder="Your API key from smmfollows.com">
      </div>
      <div style="font-size:12px;color:var(--text-muted);">
        Get your API key from <a href="https://smmfollows.com" target="_blank" style="color:var(--primary);">smmfollows.com</a> → Account → API
      </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
      <div class="card-title">💰 Pricing & Commissions</div>
      <div class="grid3">
        <div class="form-group">
          <label class="form-label">Default Markup (%)</label>
          <input type="number" name="markup_percent" class="form-control" value="<?= s($settings,'markup_percent') ?>" min="0" step="0.5">
        </div>
        <div class="form-group">
          <label class="form-label">Min Deposit ($)</label>
          <input type="number" name="min_deposit" class="form-control" value="<?= s($settings,'min_deposit') ?>" min="1">
        </div>
        <div class="form-group">
          <label class="form-label">Referral Commission (%)</label>
          <input type="number" name="referral_commission" class="form-control" value="<?= s($settings,'referral_commission') ?>" min="0" step="0.5">
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
      <div class="card-title">₿ Crypto Wallets (for receiving deposits)</div>
      <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">Enter your wallet addresses. Users will send crypto to these addresses to top up. Leave empty to hide that option.</p>
      <div class="form-group">
        <label class="form-label">Bitcoin (BTC)</label>
        <input type="text" name="wallet_btc" class="form-control" value="<?= s($settings,'wallet_btc') ?>" placeholder="bc1... or 1...">
      </div>
      <div class="form-group">
        <label class="form-label">Ethereum (ETH)</label>
        <input type="text" name="wallet_eth" class="form-control" value="<?= s($settings,'wallet_eth') ?>" placeholder="0x...">
      </div>
      <div class="form-group">
        <label class="form-label">USDT (TRC20 – Tron)</label>
        <input type="text" name="wallet_usdt_trc20" class="form-control" value="<?= s($settings,'wallet_usdt_trc20') ?>" placeholder="T...">
      </div>
      <div class="form-group">
        <label class="form-label">USDT (ERC20 – Ethereum)</label>
        <input type="text" name="wallet_usdt_erc20" class="form-control" value="<?= s($settings,'wallet_usdt_erc20') ?>" placeholder="0x...">
      </div>
      <div class="form-group">
        <label class="form-label">BNB (BEP20)</label>
        <input type="text" name="wallet_bnb" class="form-control" value="<?= s($settings,'wallet_bnb') ?>" placeholder="0x...">
      </div>
      <div class="form-group">
        <label class="form-label">Solana (SOL)</label>
        <input type="text" name="wallet_sol" class="form-control" value="<?= s($settings,'wallet_sol') ?>" placeholder="...">
      </div>
    </div>

    <button type="submit" class="btn btn-primary">💾 Save Settings</button>
  </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
