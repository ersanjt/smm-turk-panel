<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

$minDeposit = (float) ($db->getSetting('min_deposit') ?: 10);
$minDeposit = $minDeposit >= 1 ? $minDeposit : 10;

// Single crypto wallet only (from config or fallback)
$cryptoWallet = defined('CRYPTO_WALLET_ADDRESS') && trim(CRYPTO_WALLET_ADDRESS) !== ''
    ? trim(CRYPTO_WALLET_ADDRESS)
    : '0xE74159340aF565AF3E4e1e963d5E42427F653f79';

$step = 'form';
$amount = null;
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'history' ? 'history' : 'add';

// Submit TxHash for pending crypto deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tx']) && csrf_verify()) {
    $txId = trim($_POST['tx_hash'] ?? '');
    $depositId = (int) ($_POST['deposit_id'] ?? 0);
    if ($depositId > 0) {
        $tx = $db->fetch("SELECT id, user_id FROM transactions WHERE id = ? AND user_id = ? AND type = 'deposit' AND status = 'pending'", [$depositId, $user['id']]);
        if ($tx) {
            $db->execute("UPDATE transactions SET reference = ? WHERE id = ?", [substr($txId, 0, 100), $tx['id']]);
            flash('success', 'Transaction ID saved. We will add your balance after confirmation.');
        }
    }
    redirect('/add-funds.php');
}

// Main form: amount + consent (crypto only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_funds']) && csrf_verify()) {
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
    $consent = !empty($_POST['consent']);

    if (!$consent) {
        flash('error', 'You must consent that you are not fraudulent.');
        redirect('/add-funds.php');
    }
    if ($amount < $minDeposit) {
        flash('error', "Minimum deposit is \${$minDeposit}.");
        redirect('/add-funds.php');
    }

    $balanceBefore = (float) $user['balance'];
    $db->insert(
        "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference, status) VALUES (?, 'deposit', ?, ?, ?, ?, '', 'pending')",
        [$user['id'], $amount, $balanceBefore, $balanceBefore, "Deposit \${$amount} (crypto)"]
    );
    $step = 'crypto_pay';
}

// Fund history
$fundHistory = [];
if ($activeTab === 'history') {
    $fundHistory = $db->fetchAll(
        "SELECT id, amount, description, status, created_at FROM transactions WHERE user_id = ? AND type = 'deposit' ORDER BY created_at DESC LIMIT 50",
        [$user['id']]
    );
}

$pageTitle = 'Add Funds';
$pageDescription = 'Deposit funds via cryptocurrency (ETH, USDT ERC20) to your SMM panel balance.';
require_once __DIR__ . '/layouts/header.php';
?>

<style>
.add-funds-banner { background: linear-gradient(135deg, #e8f4fd 0%, #f0f9ff 100%); color: #0c5460; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; border: 1px solid #b8daff; }
.add-funds-banner a { color: var(--primary); font-weight: 600; }
.add-funds-tabs { display: flex; gap: 0; margin-bottom: 24px; border-bottom: 2px solid var(--border); }
.add-funds-tabs a { padding: 12px 20px; font-size: 14px; font-weight: 600; color: var(--text-muted); text-decoration: none; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: color .2s, border-color .2s; }
.add-funds-tabs a:hover { color: var(--text); }
.add-funds-tabs a.active { color: var(--primary); border-bottom-color: var(--primary); }
.wallet-box { background: var(--bg); border-radius: 14px; padding: 18px; margin-bottom: 16px; border: 1px solid var(--border); transition: box-shadow .25s ease, transform .2s ease; }
.wallet-box:hover { box-shadow: 0 8px 24px rgba(227,10,23,.06); }
.wallet-box .wallet-label { font-weight: 700; font-size: 14px; color: var(--text); margin-bottom: 8px; }
.wallet-box code { font-size: 12px; word-break: break-all; display: block; margin-bottom: 10px; color: var(--text-muted); }
.wallet-box .btn { padding: 8px 16px; font-size: 12px; }
.add-funds-instructions { background: var(--bg); border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; font-size: 13px; color: var(--text-muted); line-height: 1.6; border: 1px solid var(--border); }
.add-funds-instructions a { color: var(--primary); font-weight: 600; }
</style>

<div class="add-funds-banner">
  Deposits are <strong>crypto only</strong>. Send ETH or USDT (ERC20) to the wallet below. After sending, submit your transaction ID so we can credit your balance.
</div>

<div class="add-funds-tabs">
  <a href="/add-funds.php" class="<?= $activeTab === 'add' ? 'active' : '' ?>">Add Funds</a>
  <a href="/add-funds.php?tab=history" class="<?= $activeTab === 'history' ? 'active' : '' ?>">Fund History</a>
</div>

<?php if ($activeTab === 'history'): ?>
  <div class="card">
    <div class="card-title">Fund History</div>
    <?php if (empty($fundHistory)): ?>
    <p style="color:var(--text-muted);">No deposits yet.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>ID</th><th>Date</th><th>Method</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($fundHistory as $t): ?>
        <tr>
          <td><?= (int)$t['id'] ?></td>
          <td><?= h(date('Y-m-d H:i:s', strtotime($t['created_at']))) ?></td>
          <td><?= h($t['description'] ?: '—') ?></td>
          <td>$<?= number_format((float)$t['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <p style="margin-top:16px;"><a href="/add-funds.php" class="btn btn-primary">Add Funds (Crypto)</a></p>
  </div>
<?php elseif ($step === 'form'): ?>
  <div class="card">
    <div class="card-title">Add Funds — Crypto only</div>
    <form method="POST" id="addFundsForm">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="add_funds" value="1">
      <div class="add-funds-instructions">
        We accept <strong>ETH</strong> and <strong>USDT (ERC20)</strong>. Send the equivalent in USD to the wallet address shown on the next step. Minimum deposit: $<?= (int)$minDeposit ?>.
      </div>
      <div class="form-group">
        <label class="form-label">Amount (USD)</label>
        <input type="number" name="amount" id="amountInput" class="form-control" placeholder="Min $<?= (int)$minDeposit ?>" min="<?= $minDeposit ?>" step="0.01" required>
      </div>
      <label style="display:flex;align-items:center;gap:10px;font-size:13px;margin-bottom:18px;cursor:pointer;">
        <input type="checkbox" name="consent" value="1" required> By submitting this payment, I consent that I'm not fraudulent.
      </label>
      <button type="submit" class="btn btn-primary btn-block" style="padding:14px;font-size:15px;">Continue to wallet address</button>
    </form>
  </div>
<?php elseif ($step === 'crypto_pay'): ?>
  <?php
  $pending = $db->fetch("SELECT id FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'pending' ORDER BY id DESC LIMIT 1", [$user['id']]);
  $depositId = $pending ? (int)$pending['id'] : 0;
  ?>
  <div class="card">
    <div class="card-title">Pay with crypto — $<?= number_format($amount, 2) ?> USD</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Send the equivalent of <strong>$<?= number_format($amount, 2) ?></strong> in <strong>ETH</strong> or <strong>USDT (ERC20)</strong> to the address below.</p>
    <div class="wallet-box">
      <div class="wallet-label">Wallet address (ETH / USDT ERC20)</div>
      <code id="crypto-addr"><?= h($cryptoWallet) ?></code>
      <button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('crypto-addr').innerText);this.textContent='✓ Copied'">Copy address</button>
    </div>
    <hr style="margin:18px 0;border:0;border-top:1px solid var(--border);">
    <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">After you sent the payment, paste your transaction ID (TxHash) below:</p>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="deposit_id" value="<?= $depositId ?>">
      <input type="hidden" name="submit_tx" value="1">
      <div class="form-group">
        <input type="text" name="tx_hash" class="form-control" placeholder="Transaction ID / TxHash (optional)">
      </div>
      <button type="submit" class="btn btn-primary">I've sent the payment</button>
    </form>
    <p style="margin-top:14px;font-size:12px;color:var(--text-muted);">Balance will be added after we confirm. You can also open a <a href="/tickets.php">ticket</a> with the TxHash.</p>
    <a href="/add-funds.php" style="display:inline-block;margin-top:12px;font-size:13px;">← New deposit</a>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
