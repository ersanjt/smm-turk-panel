<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

$minDeposit = (float) ($db->getSetting('min_deposit') ?: 10);
$minDeposit = $minDeposit >= 1 ? $minDeposit : 10;

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
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'history' ? 'history' : 'add';

// Submit TxHash for pending crypto deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tx'])) {
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

// Main form: method + amount + consent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['method'])) {
    $method = $_POST['method'] ?? '';
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

    if ($method === 'crypto') {
        if (empty($wallets)) {
            flash('error', 'Crypto deposits are not configured. Please contact support.');
            redirect('/add-funds.php');
        }
        $db->insert(
            "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference, status) VALUES (?, 'deposit', ?, ?, ?, ?, '', 'pending')",
            [$user['id'], $amount, $balanceBefore, $balanceBefore, "Deposit \${$amount} (crypto)"]
        );
        $step = 'crypto_pay';
    } elseif ($method === 'card') {
        $db->insert(
            "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference, status) VALUES (?, 'deposit', ?, ?, ?, ?, '', 'pending')",
            [$user['id'], $amount, $balanceBefore, $balanceBefore, 'Credit/Debit Card - Visa/Master/AmEx (submit ticket)']
        );
        $step = 'other';
    } elseif ($method === 'other') {
        $db->insert(
            "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference, status) VALUES (?, 'deposit', ?, ?, ?, ?, '', 'pending')",
            [$user['id'], $amount, $balanceBefore, $balanceBefore, 'Other payment (submit ticket)']
        );
        $step = 'other';
    } else {
        redirect('/add-funds.php');
    }
}

// Fund history for this user (all deposits)
$fundHistory = [];
if ($activeTab === 'history') {
    $fundHistory = $db->fetchAll(
        "SELECT id, amount, description, status, created_at FROM transactions WHERE user_id = ? AND type = 'deposit' ORDER BY created_at DESC LIMIT 50",
        [$user['id']]
    );
}

$pageTitle = 'Add Funds';
require_once __DIR__ . '/layouts/header.php';
?>

<style>
.add-funds-banner { background: #e8f4fd; color: #0c5460; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; border: 1px solid #b8daff; }
.add-funds-banner a { color: var(--primary); font-weight: 600; }
.add-funds-tabs { display: flex; gap: 0; margin-bottom: 24px; border-bottom: 2px solid var(--border); }
.add-funds-tabs a { padding: 12px 20px; font-size: 14px; font-weight: 600; color: var(--text-muted); text-decoration: none; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: color .2s, border-color .2s; }
.add-funds-tabs a:hover { color: var(--text); }
.add-funds-tabs a.active { color: var(--primary); border-bottom-color: var(--primary); }
.method-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 24px; }
@media (max-width: 768px) { .method-cards { grid-template-columns: 1fr; } }
.method-card { background: #fff; border: 2px solid var(--border); border-radius: 14px; padding: 20px; text-align: center; cursor: pointer; transition: all .2s; }
.method-card:hover { border-color: var(--primary); box-shadow: 0 4px 16px rgba(227,10,23,.08); }
.method-card.active { border-color: var(--primary); background: #fff8f9; }
.method-card .icon { font-size: 28px; margin-bottom: 10px; }
.method-card .title { font-weight: 700; font-size: 14px; color: var(--text); }
.method-card .note { font-size: 12px; color: var(--text-muted); margin-top: 8px; line-height: 1.4; }
.method-card.disabled { opacity: 0.7; cursor: not-allowed; pointer-events: none; }
.add-funds-instructions { background: var(--bg); border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; font-size: 13px; color: var(--text-muted); line-height: 1.6; border: 1px solid var(--border); }
.add-funds-instructions a { color: var(--primary); font-weight: 600; }
</style>

<div class="add-funds-banner">
  Choose from multiple Card payment options. If one fails, try another payment method. Multiple attempts with the same method may lead to account suspension.
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
    <div class="method-cards" style="margin-top:28px;">
      <a href="/add-funds.php" class="method-card active">
        <div class="icon">💳</div>
        <div class="title">Credit Card</div>
        <div class="note">Credit Card payment is enabled for Everyone.</div>
        <div class="note"><strong>$<?= (int)$minDeposit ?> Minimum Payment!</strong></div>
      </a>
      <a href="/add-funds.php" class="method-card">
        <div class="icon">₿</div>
        <div class="title">Crypto Currency</div>
        <div class="note">BTC, ETH, USDT, BNB, SOL</div>
      </a>
      <a href="/add-funds.php" class="method-card">
        <div class="icon">🔗</div>
        <div class="title">Other</div>
        <div class="note">Open a ticket for other methods</div>
      </a>
    </div>
  </div>
<?php elseif ($step === 'form'): ?>
  <div class="card">
    <div class="card-title">Add Funds</div>
    <form method="POST" id="addFundsForm">
      <div class="form-group">
        <label class="form-label">Method</label>
        <select name="method" id="methodSelect" class="form-control" required>
          <option value="card">Credit/Debit Card - Secure Checkout - Visa/Master/AmEx</option>
          <?php if (!empty($wallets)): ?>
          <option value="crypto">Crypto Currency - BTC, ETH, USDT, BNB, SOL</option>
          <?php endif; ?>
          <option value="other">Other payment methods</option>
        </select>
      </div>
      <div class="add-funds-instructions">
        If your card is not 3D SECURE, ask your bank to allow the transaction.<br>
        Payment not arrival automatically? Kindly <a href="/tickets.php">submit a ticket</a> to us.
      </div>
      <div class="form-group">
        <label class="form-label">Amount (USD)</label>
        <input type="number" name="amount" id="amountInput" class="form-control" placeholder="Min $<?= (int)$minDeposit ?>" min="<?= $minDeposit ?>" step="0.01" required>
      </div>
      <label style="display:flex;align-items:center;gap:10px;font-size:13px;margin-bottom:18px;cursor:pointer;">
        <input type="checkbox" name="consent" value="1" required> By submitting this payment, I consent that I'm not fraudulent.
      </label>
      <button type="submit" class="btn btn-primary btn-block" style="padding:14px;font-size:15px;">Pay</button>
    </form>
    <div class="method-cards">
      <div class="method-card active" data-method="card">
        <div class="icon">💳</div>
        <div class="title">Credit Card</div>
        <div class="note">Credit Card payment is enabled for Everyone.</div>
        <div class="note"><strong>$<?= (int)$minDeposit ?> Minimum Payment!</strong></div>
      </div>
      <div class="method-card <?= empty($wallets) ? 'disabled' : '' ?>" data-method="crypto">
        <div class="icon">₿</div>
        <div class="title">Crypto Currency</div>
        <div class="note">BTC, ETH, USDT, BNB, SOL</div>
      </div>
      <div class="method-card" data-method="other">
        <div class="icon">🔗</div>
        <div class="title">Other</div>
        <div class="note">Open a ticket for other methods</div>
      </div>
    </div>
  </div>
  <script>
  (function(){
    var sel = document.getElementById('methodSelect');
    var cards = document.querySelectorAll('.method-card[data-method]');
    cards.forEach(function(c){
      c.addEventListener('click', function(){
        var m = this.getAttribute('data-method');
        if (m === 'crypto' && this.classList.contains('disabled')) return;
        cards.forEach(function(x){ x.classList.remove('active'); });
        this.classList.add('active');
        sel.value = m;
      });
    });
  })();
  </script>
<?php elseif ($step === 'other'): ?>
  <div class="card">
    <div class="card-title">Card / Other payment</div>
    <p style="color:var(--text-muted);margin-bottom:16px;">Please open a <a href="/tickets.php">support ticket</a> with the amount you want to deposit. We will guide you through the payment.</p>
    <a href="/add-funds.php" class="btn btn-primary">← Back to Add Funds</a>
  </div>
<?php elseif ($step === 'crypto_pay'): ?>
  <?php
  $pending = $db->fetch("SELECT id FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'pending' ORDER BY id DESC LIMIT 1", [$user['id']]);
  $depositId = $pending ? (int)$pending['id'] : 0;
  ?>
  <div class="card">
    <div class="card-title">Pay with crypto — $<?= number_format($amount, 2) ?> USD</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Send the equivalent of <strong>$<?= number_format($amount, 2) ?></strong> in one of the currencies below. After sending, you can submit your transaction ID below.</p>
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
    <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">After you sent the payment, paste your transaction ID (TxHash) below:</p>
    <form method="POST">
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
