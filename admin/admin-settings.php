<?php
require_once __DIR__ . '/../app/init.php';
$auth->requireAdmin();
$pageTitle = 'Settings';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $fields = ['site_name','site_url','api_key','markup_percent','min_deposit','referral_commission','referral_min_payout','registration_enabled','email_verification_required','maintenance_mode',
        'smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','contact_email','wallet_btc','wallet_eth','wallet_usdt_trc20','wallet_usdt_erc20','wallet_bnb','wallet_sol',
        'deposit_auto_confirm','deposit_min_confirmations','deposit_amount_tolerance','api_etherscan','api_trongrid','api_bscscan',
        'child_panel_price','child_panel_ns1','child_panel_ns2'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $db->setSetting($f, trim($_POST[$f]));
        }
    }
    flash('success', '✅ Settings saved successfully.');
    redirect(url('admin/admin-settings.php'));
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
      <div class="form-group">
        <label class="form-label">Require email verification</label>
        <select name="email_verification_required" class="form-control">
          <option value="1" <?= ($settings['email_verification_required']??'1')==='1'?'selected':'' ?>>On — user must verify email before login (recommended)</option>
          <option value="0" <?= ($settings['email_verification_required']??'1')==='0'?'selected':'' ?>>Off — instant access after register</option>
        </select>
      </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
      <div class="card-title">📧 Email</div>
      <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">Leave SMTP empty to use PHP mail() (cPanel). To use Gmail/SendGrid etc., fill SMTP Host and credentials.</p>
      <div class="form-group">
        <label class="form-label">Mail From (sender address)</label>
        <input type="text" name="smtp_from" class="form-control" value="<?= s($settings,'smtp_from') ?>" placeholder="noreply@yourdomain.com">
      </div>
      <div class="form-group">
        <label class="form-label">Contact Email (shown in footer)</label>
        <input type="email" name="contact_email" class="form-control" value="<?= s($settings,'contact_email') ?>" placeholder="contact@yourdomain.com">
      </div>
      <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
        <strong style="font-size:13px;">SMTP (optional)</strong>
      </div>
      <div class="grid2" style="margin-top:10px;">
        <div class="form-group">
          <label class="form-label">SMTP Host</label>
          <input type="text" name="smtp_host" class="form-control" value="<?= s($settings,'smtp_host') ?>" placeholder="smtp.gmail.com">
        </div>
        <div class="form-group">
          <label class="form-label">SMTP Port</label>
          <input type="text" name="smtp_port" class="form-control" value="<?= s($settings,'smtp_port') ?: '587' ?>" placeholder="587 or 465">
        </div>
      </div>
      <div class="grid2">
        <div class="form-group">
          <label class="form-label">SMTP User</label>
          <input type="text" name="smtp_user" class="form-control" value="<?= s($settings,'smtp_user') ?>" placeholder="your@email.com">
        </div>
        <div class="form-group">
          <label class="form-label">SMTP Password</label>
          <input type="password" name="smtp_pass" class="form-control" value="<?= s($settings,'smtp_pass') ?>" placeholder="••••••••" autocomplete="new-password">
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
      <div class="card-title">🔑 Provider API</div>
      <div class="form-group">
        <label class="form-label">SmmFollows API Key</label>
        <input type="text" name="api_key" class="form-control" value="<?= s($settings,'api_key') ?>" placeholder="Your API key from smmfollows.com">
      </div>
      <div style="font-size:12px;color:var(--text-muted);">
        Get your API key from <a href="https://smmfollows.com" target="_blank" rel="noopener noreferrer" style="color:var(--primary);">smmfollows.com</a> → Account → API
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
      <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">
        Copy <strong>Receive</strong> addresses from your wallet (Trust Wallet, MetaMask, etc.) and paste them here.
        Users send crypto to these addresses — the panel verifies payments on the blockchain automatically (no wallet extension connection needed).
      </p>
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

    <div class="card" style="margin-bottom:18px;">
      <div class="card-title">🔗 Auto deposit verification</div>
      <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">
        When a user submits a TxHash, the panel checks the blockchain and credits balance automatically.
        <strong>USDT TRC20</strong> works without API keys. For ETH/ERC20/BNB add free API keys from Etherscan / BscScan.
      </p>
      <div class="form-group">
        <label class="form-label">Auto-confirm deposits</label>
        <select name="deposit_auto_confirm" class="form-control">
          <option value="1" <?= ($settings['deposit_auto_confirm']??'1')==='1'?'selected':'' ?>>On — verify on-chain and credit balance</option>
          <option value="0" <?= ($settings['deposit_auto_confirm']??'1')==='0'?'selected':'' ?>>Off — admin approves manually</option>
        </select>
      </div>
      <div class="grid2">
        <div class="form-group">
          <label class="form-label">Min confirmations</label>
          <input type="number" name="deposit_min_confirmations" class="form-control" value="<?= s($settings,'deposit_min_confirmations') ?: '1' ?>" min="1" max="12">
        </div>
        <div class="form-group">
          <label class="form-label">Amount tolerance (0.03 = ±3%)</label>
          <input type="number" name="deposit_amount_tolerance" class="form-control" value="<?= s($settings,'deposit_amount_tolerance') ?: '0.03' ?>" min="0.01" max="0.15" step="0.01">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Etherscan API key (ETH + USDT ERC20)</label>
        <input type="text" name="api_etherscan" class="form-control" value="<?= s($settings,'api_etherscan') ?>" placeholder="Free at etherscan.io/apis">
      </div>
      <div class="grid2">
        <div class="form-group">
          <label class="form-label">TronGrid API key (optional, USDT TRC20)</label>
          <input type="text" name="api_trongrid" class="form-control" value="<?= s($settings,'api_trongrid') ?>" placeholder="Optional — trongrid.io">
        </div>
        <div class="form-group">
          <label class="form-label">BscScan API key (BNB)</label>
          <input type="text" name="api_bscscan" class="form-control" value="<?= s($settings,'api_bscscan') ?>" placeholder="Free at bscscan.com/apis">
        </div>
      </div>
      <p style="font-size:11px;color:var(--text-muted);margin-top:8px;">
        Cron: <code>*/2 * * * * php /home/smmturk/public_html/cron-verify-deposits.php</code>
      </p>
    </div>

    <div class="card" style="margin-bottom:18px;">
      <div class="card-title">👥 Child Panel</div>
      <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">Monthly price for child panel and nameservers shown to users after order.</p>
      <div class="grid3">
        <div class="form-group">
          <label class="form-label">Price per month ($)</label>
          <input type="number" name="child_panel_price" class="form-control" value="<?= s($settings,'child_panel_price') ?: '5' ?>" min="0" step="0.01">
        </div>
        <div class="form-group">
          <label class="form-label">Nameserver 1</label>
          <input type="text" name="child_panel_ns1" class="form-control" value="<?= s($settings,'child_panel_ns1') ?>" placeholder="ns1.yourdomain.com">
        </div>
        <div class="form-group">
          <label class="form-label">Nameserver 2</label>
          <input type="text" name="child_panel_ns2" class="form-control" value="<?= s($settings,'child_panel_ns2') ?>" placeholder="ns2.yourdomain.com">
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary">💾 Save Settings</button>
  </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
