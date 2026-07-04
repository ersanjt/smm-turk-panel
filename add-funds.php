<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

$minDeposit = (float) ($db->getSetting('min_deposit') ?: 10);
$minDeposit = $minDeposit >= 1 ? $minDeposit : 10;

// Wallets from admin settings (DB); fallback to single config wallet
$walletKeys = [
    'wallet_eth' => 'ETH',
    'wallet_usdt_erc20' => 'USDT (ERC20)',
    'wallet_usdt_trc20' => 'USDT (TRC20)',
    'wallet_btc' => 'BTC',
    'wallet_bnb' => 'BNB',
    'wallet_sol' => 'SOL',
];
$cryptoWallets = [];
foreach ($walletKeys as $key => $label) {
    $addr = $db->getSetting($key);
    if ($addr !== null && trim($addr) !== '') {
        $cryptoWallets[] = ['label' => $label, 'address' => trim($addr)];
    }
}
if (empty($cryptoWallets) && defined('CRYPTO_WALLET_ADDRESS') && trim(CRYPTO_WALLET_ADDRESS) !== '') {
    $cryptoWallets[] = ['label' => 'ETH / USDT ERC20', 'address' => trim(CRYPTO_WALLET_ADDRESS)];
}
$walletsConfigured = !empty($cryptoWallets);

$step = 'form';
$amount = null;
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'history' ? 'history' : 'add';
$pendingDeposit = $db->fetch(
    "SELECT id, amount FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'pending' ORDER BY id DESC LIMIT 1",
    [$user['id']]
);

// Start a new deposit (cancel previous pending request)
$skipPendingResume = false;
if (isset($_GET['new']) && $_GET['new'] === '1' && $_SERVER['REQUEST_METHOD'] !== 'POST' && $activeTab === 'add') {
    if ($pendingDeposit) {
        $db->execute(
            "UPDATE transactions SET status = 'failed' WHERE id = ? AND user_id = ? AND type = 'deposit' AND status = 'pending'",
            [$pendingDeposit['id'], $user['id']]
        );
        $pendingDeposit = null;
    }
    $step = 'form';
    $amount = null;
    $skipPendingResume = true;
}

// Resume pending deposit (persist wallet step across refresh)
if (!$skipPendingResume && $pendingDeposit && $activeTab === 'add' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $step = 'crypto_pay';
    $amount = (float)$pendingDeposit['amount'];
}

// Submit TxHash for pending crypto deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tx']) && csrf_verify()) {
    $txId = trim($_POST['tx_hash'] ?? '');
    $depositId = (int) ($_POST['deposit_id'] ?? 0);
    if ($txId === '') {
        flash('error', 'Please paste your transaction ID (TxHash) so we can match your payment.');
        redirect(url('add-funds.php'));
    }
    if ($depositId > 0) {
        $tx = $db->fetch("SELECT id, user_id FROM transactions WHERE id = ? AND user_id = ? AND type = 'deposit' AND status = 'pending'", [$depositId, $user['id']]);
        if ($tx) {
            $db->execute("UPDATE transactions SET reference = ? WHERE id = ?", [substr($txId, 0, 100), $tx['id']]);
            flash('success', 'Crypto payment submitted! We will confirm on-chain and email you when your balance is credited.');
        }
    }
    redirect(url('add-funds.php'));
}

// Main form: amount + consent (crypto only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_funds']) && csrf_verify()) {
    if (!$walletsConfigured) {
        flash('error', 'Deposits are temporarily unavailable. Please contact support.');
        redirect(url('add-funds.php'));
    }
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;

    if ($amount < $minDeposit) {
        flash('error', "Minimum deposit is \${$minDeposit}.");
        redirect(url('add-funds.php'));
    }

    if ($pendingDeposit) {
        $db->execute(
            "UPDATE transactions SET amount = ?, description = ? WHERE id = ? AND user_id = ?",
            [$amount, "Deposit \${$amount} (crypto)", $pendingDeposit['id'], $user['id']]
        );
        $step = 'crypto_pay';
    } else {
        $balanceBefore = (float) $user['balance'];
        $db->insert(
            "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference, status) VALUES (?, 'deposit', ?, ?, ?, ?, '', 'pending')",
            [$user['id'], $amount, $balanceBefore, $balanceBefore, "Deposit \${$amount} (crypto)"]
        );
        $step = 'crypto_pay';
    }
}

// Fund history
$fundHistory = [];
if ($activeTab === 'history') {
    $fundHistory = $db->fetchAll(
        "SELECT id, amount, description, status, created_at FROM transactions WHERE user_id = ? AND type = 'deposit' ORDER BY created_at DESC LIMIT 50",
        [$user['id']]
    );
}

// Current balance for display (header also fetches it; we need it here for the balance block)
$currentBalance = (float) ($user['balance'] ?? 0);
$balanceRow = $db->fetch("SELECT balance FROM users WHERE id = ?", [(int)$user['id']]);
if ($balanceRow !== null) {
    $currentBalance = (float) $balanceRow['balance'];
}

$pageTitle = 'Add Funds';
$pageDescription = 'Add funds with cryptocurrency only (BTC, ETH, USDT, BNB, SOL). No cards or PayPal.';
require_once __DIR__ . '/layouts/header.php';
?>

<style>
.add-funds-balance-box { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 14px 18px; background: linear-gradient(135deg, rgba(227,10,23,.08) 0%, rgba(227,10,23,.04) 100%); border: 1px solid var(--border); border-radius: 14px; margin-bottom: 20px; }
.add-funds-balance-box .balance-label { font-size: 13px; font-weight: 600; color: var(--text-muted); }
.add-funds-balance-box .balance-value { font-size: 20px; font-weight: 800; color: var(--text); font-family: 'Syne', sans-serif; }
.add-funds-balance-box .balance-value span { color: var(--primary); }
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
.add-funds-presets{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
.add-funds-presets button{padding:8px 14px;border-radius:10px;border:1.5px solid var(--border);background:#fff;font-size:13px;font-weight:600;cursor:pointer}
.add-funds-presets button:hover{border-color:var(--primary);color:var(--primary)}
.add-funds-steps{display:flex;gap:8px;margin-bottom:22px;flex-wrap:wrap}
.add-funds-step{flex:1;min-width:100px;padding:10px 12px;border-radius:12px;background:var(--bg);border:1px solid var(--border);font-size:12px;font-weight:600;color:var(--text-muted);text-align:center;line-height:1.4}
.add-funds-step span{display:block;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--primary);margin-bottom:4px}
.add-funds-step.active{background:linear-gradient(135deg,rgba(227,10,23,.1),rgba(227,10,23,.04));border-color:rgba(227,10,23,.25);color:var(--text)}
.add-funds-step.done{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
.add-funds-ready{background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:1px solid #bbf7d0;border-radius:14px;padding:16px 18px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.add-funds-ready p{margin:0;font-size:14px;color:#166534;font-weight:600}
.add-funds-next-box{background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;margin-top:16px;font-size:13px;color:#92400e;line-height:1.6}
.add-funds-presets button.active{border-color:var(--primary);background:rgba(227,10,23,.08);color:var(--primary)}
.status-pending{color:#d97706;font-weight:700}
.status-completed{color:#16a34a;font-weight:700}
.status-failed{color:#dc2626;font-weight:700}
</style>

<div class="add-funds-balance-box">
  <span class="balance-label">Your current balance</span>
  <span class="balance-value">$<span><?= number_format($currentBalance, 3) ?></span></span>
</div>

<?php if ($currentBalance > 0 && $activeTab === 'add'): ?>
<div class="add-funds-ready">
  <p>✓ Your balance is ready — you can place orders now.</p>
  <a href="<?= h(path('index.php')) ?>" class="btn btn-primary" style="padding:10px 18px;font-size:13px;">Place an order →</a>
</div>
<?php endif; ?>

<?php
$stepNum = $step === 'crypto_pay' ? 2 : 1;
?>
<div class="add-funds-steps" aria-label="Deposit steps">
  <div class="add-funds-step <?= $stepNum === 1 ? 'active' : 'done' ?>"><span>Step 1</span>Choose amount</div>
  <div class="add-funds-step <?= $stepNum === 2 ? 'active' : '' ?>"><span>Step 2</span>Send crypto</div>
  <div class="add-funds-step"><span>Step 3</span>Start ordering</div>
</div>

<div class="add-funds-banner">
  <strong>Crypto only:</strong> we accept BTC, ETH, USDT (TRC20/ERC20), BNB, and SOL — no cards, PayPal, or bank transfer. Pick an amount → send crypto to a wallet below → submit your TxHash. Balance is credited after confirmation and we email you.
</div>

<div class="add-funds-tabs">
  <a href="<?= h(path('add-funds.php')) ?>" class="<?= $activeTab === 'add' ? 'active' : '' ?>">Add Funds</a>
  <a href="<?= h(path('add-funds.php')) ?>?tab=history" class="<?= $activeTab === 'history' ? 'active' : '' ?>">Fund History</a>
</div>

<?php if ($activeTab === 'history'): ?>
  <div class="card">
    <div class="card-title">Fund History</div>
    <?php if (empty($fundHistory)): ?>
    <p style="color:var(--text-muted);">No deposits yet.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>ID</th><th>Date</th><th>Status</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($fundHistory as $t):
            $st = strtolower($t['status'] ?? 'pending');
            $stClass = $st === 'completed' ? 'status-completed' : ($st === 'failed' ? 'status-failed' : 'status-pending');
        ?>
        <tr>
          <td><?= (int)$t['id'] ?></td>
          <td><?= h(date('Y-m-d H:i:s', strtotime($t['created_at']))) ?></td>
          <td><span class="<?= h($stClass) ?>"><?= h(ucfirst($st)) ?></span></td>
          <td>$<?= number_format((float)$t['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <p style="margin-top:16px;"><a href="<?= h(path('add-funds.php')) ?>" class="btn btn-primary">Add Funds (Crypto)</a></p>
  </div>
<?php elseif ($step === 'form'): ?>
  <?php if (!$walletsConfigured): ?>
  <div class="card">
    <div class="card-title">Add Funds — Unavailable</div>
    <p style="color:var(--text-muted);">Crypto wallet addresses are not configured yet. Please contact support or try again later.</p>
  </div>
  <?php else: ?>
  <?php if ($pendingDeposit && $step === 'form'): ?>
  <div class="alert alert-info" style="margin-bottom:16px;">
    You have a pending deposit of <strong>$<?= number_format((float)$pendingDeposit['amount'], 2) ?></strong>.
    <a href="<?= h(path('add-funds.php')) ?>" style="font-weight:700;color:var(--primary);">Continue payment →</a>
  </div>
  <?php endif; ?>
  <div class="card">
    <div class="card-title">Add Funds — Crypto only</div>
    <form method="POST" id="addFundsForm">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="add_funds" value="1">
      <div class="add-funds-instructions">
        Tap a quick amount or enter your own. Minimum deposit: <strong>$<?= (int)$minDeposit ?></strong>.
      </div>
      <div class="form-group">
        <label class="form-label">Amount (USD)</label>
        <div class="add-funds-presets">
          <?php
          $presets = [10, 25, 50, 100, 200];
          $defaultAmount = $pendingDeposit ? (float)$pendingDeposit['amount'] : max($minDeposit, 25);
          foreach ($presets as $preset):
            if ($preset >= $minDeposit):
          ?>
          <button type="button" class="preset-btn<?= abs($defaultAmount - $preset) < 0.01 ? ' active' : '' ?>" data-amount="<?= $preset ?>">$<?= $preset ?></button>
          <?php endif; endforeach; ?>
        </div>
        <input type="number" name="amount" id="amountInput" class="form-control" placeholder="Min $<?= (int)$minDeposit ?>" min="<?= $minDeposit ?>" step="0.01" required value="<?= number_format($defaultAmount, 2, '.', '') ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-block" style="padding:14px;font-size:15px;">Continue — show wallet address</button>
    </form>
  </div>
  <?php endif; ?>
<?php elseif ($step === 'crypto_pay'): ?>
  <?php
  $pending = $db->fetch("SELECT id FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'pending' ORDER BY id DESC LIMIT 1", [$user['id']]);
  $depositId = $pending ? (int)$pending['id'] : 0;
  ?>
  <div class="card">
    <div class="card-title">Pay with crypto — $<?= number_format($amount, 2) ?> USD</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Send the equivalent of <strong>$<?= number_format($amount, 2) ?></strong> to one of the addresses below (use the correct network).</p>
    <?php foreach ($cryptoWallets as $i => $w): ?>
    <div class="wallet-box">
      <div class="wallet-label"><?= h($w['label']) ?></div>
      <code class="crypto-addr" id="crypto-addr-<?= $i ?>"><?= h($w['address']) ?></code>
      <button type="button" class="btn btn-primary" onclick="var el=document.getElementById('crypto-addr-<?= $i ?>');navigator.clipboard.writeText(el.innerText);this.textContent='✓ Copied'">Copy address</button>
    </div>
    <?php endforeach; ?>
    <hr style="margin:18px 0;border:0;border-top:1px solid var(--border);">
    <p style="font-size:13px;font-weight:700;margin-bottom:10px;">After you send the payment</p>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="deposit_id" value="<?= $depositId ?>">
      <input type="hidden" name="submit_tx" value="1">
      <div class="form-group">
        <label class="form-label">Transaction ID (TxHash)</label>
        <input type="text" name="tx_hash" class="form-control" placeholder="Paste your blockchain transaction ID" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block" style="padding:14px;">I've sent the payment</button>
    </form>
    <div class="add-funds-next-box">
      <strong>What happens next?</strong><br>
      1. We verify your payment on the blockchain.<br>
      2. Your balance is credited automatically after admin approval.<br>
      3. You receive an <strong>email confirmation</strong> with a link to place your first order.
    </div>
    <p style="margin-top:14px;font-size:12px;color:var(--text-muted);">Need help? Open a <a href="<?= h(path('tickets.php')) ?>">support ticket</a> with your TxHash.</p>
    <a href="<?= h(path('add-funds.php')) ?>?new=1" style="display:inline-block;margin-top:12px;font-size:13px;">← Change amount</a>
  </div>
<?php endif; ?>

<script>
document.querySelectorAll('.preset-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.getElementById('amountInput').value = this.dataset.amount;
    document.querySelectorAll('.preset-btn').forEach(function(b){ b.classList.remove('active'); });
    this.classList.add('active');
  });
});
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
