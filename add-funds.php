<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Min deposit from settings
$minDeposit = (float) ($db->getSetting('min_deposit') ?: 10);
$minDeposit = $minDeposit >= 1 ? $minDeposit : 10;

// Get wallet addresses (only non-empty)
$walletKeys = ['wallet_btc' => 'Bitcoin (BTC)', 'wallet_eth' => 'Ethereum (ETH)', 'wallet_usdt_trc20' => 'USDT (TRC20)', 'wallet_usdt_erc20' => 'USDT (ERC20)', 'wallet_bnb' => 'BNB (BEP20)', 'wallet_sol' => 'Solana (SOL)'];
$wallets = [];
foreach ($walletKeys as $key => $label) {
    $addr = $db->getSetting($key);
    if ($addr !== null && trim($addr) !== '') {
        $wallets[$key] = ['label' => $label, 'address' => trim($addr)];
    }
}

$step = 'form';
$amount = null;
$method = '';
$txId = null;

// Handle "I've paid" (submit tx hash for existing pending deposit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tx'])) {
    $txId = trim($_POST['tx_hash'] ?? '');
    $depositId = (int) ($_POST['deposit_id'] ?? 0);
    if ($depositId > 0) {
        $tx = $db->fetch("SELECT id, user_id FROM transactions WHERE id = ? AND user_id = ? AND type = 'deposit' AND status = 'pending'", [$depositId, $user['id']]);
        if ($tx) {
            $db->execute("UPDATE transactions SET reference = ? WHERE id = ?", [substr($txId, 0, 100), $tx['id']]);
            flash('success', '✅ Transaction ID saved. We will add your balance after confirmation.');
        }
    }
    redirect('/add-funds.php');
}

// Handle main form: method + amount
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['method'])) {
    $method = $_POST['method'] ?? '';
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
    if ($amount < $minDeposit) {
        flash('error', "Minimum deposit is \${$minDeposit}.");
        redirect('/add-funds.php');
    }
    if ($method === 'crypto') {
        if (empty($wallets)) {
            flash('error', 'Crypto deposits are not configured. Please contact support.');
            redirect('/add-funds.php');
        }
        // Create pending deposit transaction
        $balanceBefore = (float) $user['balance'];
        $db->insert(
            "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference, status) VALUES (?, 'deposit', ?, ?, ?, ?, '', 'pending')",
            [$user['id'], $amount, $balanceBefore, $balanceBefore, "Deposit \${$amount} (crypto)"]
        );
        $step = 'crypto_pay';
    } elseif ($method === 'card' || $method === 'other') {
        $step = 'other';
    } else {
        redirect('/add-funds.php');
    }
}

$pageTitle = 'Add Funds';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="alert alert-info">💡 Choose a payment method. After paying with crypto, your balance will be added once we confirm the transaction.</div>
<div style="max-width:560px;">
  <?php if ($step === 'form'): ?>
  <div class="card">
    <div class="card-title">💳 Add Funds</div>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Payment Method</label>
        <select name="method" class="form-control" required>
          <?php if (!empty($wallets)): ?>
          <option value="crypto">₿ Cryptocurrency — BTC, ETH, USDT, BNB, SOL</option>
          <?php endif; ?>
          <option value="card">💳 Card / Other (contact support)</option>
          <option value="other">🔗 Other methods</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Amount (USD)</label>
        <input type="number" name="amount" class="form-control" placeholder="Min: $<?= (int)$minDeposit ?>" min="<?= $minDeposit ?>" step="0.01" value="<?= $amount !== null ? h((string)$amount) : '' ?>" required>
      </div>
      <label style="display:flex;align-items:center;gap:10px;font-size:13px;margin-bottom:18px;cursor:pointer;">
        <input type="checkbox" name="confirm" required> I confirm this payment is legitimate.
      </label>
      <button type="submit" class="btn btn-primary btn-block">Proceed</button>
    </form>
  </div>
  <?php elseif ($step === 'other'): ?>
  <div class="card">
    <div class="card-title">💳 Card / Other payment</div>
    <p style="color:var(--text-muted);margin-bottom:16px;">For card or bank transfer, please open a <a href="/tickets.php">support ticket</a> with the amount you want to deposit. You can also use <strong>crypto</strong> for instant option.</p>
    <a href="/add-funds.php" class="btn btn-primary">← Back to Add Funds</a>
  </div>
  <?php elseif ($step === 'crypto_pay'): ?>
  <?php
    $pending = $db->fetch("SELECT id FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'pending' ORDER BY id DESC LIMIT 1", [$user['id']]);
    $depositId = $pending ? (int)$pending['id'] : 0;
  ?>
  <div class="card">
    <div class="card-title">₿ Pay with crypto — $<?= number_format($amount, 2) ?> USD</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Send the equivalent of <strong>$<?= number_format($amount, 2) ?></strong> in one of the currencies below to the address shown. Use current exchange rate. After sending, you can submit your transaction ID below.</p>
    <?php foreach ($wallets as $key => $w): ?>
    <div style="background:var(--bg);border-radius:12px;padding:14px;margin-bottom:12px;border:1px solid var(--border);">
      <div style="font-weight:700;margin-bottom:6px;"><?= h($w['label']) ?></div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <code id="addr-<?= h($key) ?>" style="font-size:11px;word-break:break-all;flex:1;min-width:0;"><?= h($w['address']) ?></code>
        <button type="button" class="btn" style="padding:6px 12px;font-size:12px;" onclick="navigator.clipboard.writeText(document.getElementById('addr-<?= h($key) ?>').innerText);this.textContent='✓ Copied'">Copy</button>
      </div>
    </div>
    <?php endforeach; ?>
    <hr style="margin:18px 0;border:0;border-top:1px solid var(--border);">
    <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">After you sent the payment, paste your transaction ID (TxHash) below so we can confirm faster:</p>
    <form method="POST">
      <input type="hidden" name="deposit_id" value="<?= $depositId ?>">
      <input type="hidden" name="submit_tx" value="1">
      <div class="form-group">
        <input type="text" name="tx_hash" class="form-control" placeholder="Transaction ID / TxHash (optional)">
      </div>
      <button type="submit" class="btn btn-primary">I've sent the payment</button>
    </form>
    <p style="margin-top:14px;font-size:12px;color:var(--text-muted);">Balance will be added after we confirm the payment. You can also open a <a href="/tickets.php">ticket</a> with the TxHash.</p>
    <a href="/add-funds.php" style="display:inline-block;margin-top:12px;font-size:13px;">← New deposit</a>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
