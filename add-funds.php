<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

$minDeposit = (float) ($db->getSetting('min_deposit') ?: 10);
$minDeposit = $minDeposit >= 1 ? $minDeposit : 10;

$walletCatalog = [
    'wallet_usdt_trc20' => [
        'label' => 'USDT',
        'network' => 'TRC20',
        'hint' => 'Tron network — lowest fees',
        'color' => '#26a17b',
        'recommended' => true,
    ],
    'wallet_usdt_erc20' => [
        'label' => 'USDT',
        'network' => 'ERC20',
        'hint' => 'Ethereum network',
        'color' => '#627eea',
        'recommended' => false,
    ],
    'wallet_eth' => [
        'label' => 'ETH',
        'network' => 'Ethereum',
        'hint' => 'Native Ethereum',
        'color' => '#627eea',
        'recommended' => false,
    ],
    'wallet_btc' => [
        'label' => 'BTC',
        'network' => 'Bitcoin',
        'hint' => 'Bitcoin mainnet',
        'color' => '#f7931a',
        'recommended' => false,
    ],
    'wallet_bnb' => [
        'label' => 'BNB',
        'network' => 'BEP20',
        'hint' => 'BNB Smart Chain',
        'color' => '#f3ba2f',
        'recommended' => false,
    ],
    'wallet_sol' => [
        'label' => 'SOL',
        'network' => 'Solana',
        'hint' => 'Solana mainnet',
        'color' => '#9945ff',
        'recommended' => false,
    ],
];

$cryptoWallets = [];
foreach ($walletCatalog as $key => $meta) {
    $addr = $db->getSetting($key);
    if ($addr !== null && trim($addr) !== '') {
        $cryptoWallets[$key] = array_merge($meta, [
            'key' => $key,
            'address' => trim($addr),
            'display' => $meta['label'] . ' (' . $meta['network'] . ')',
        ]);
    }
}
if (empty($cryptoWallets) && defined('CRYPTO_WALLET_ADDRESS') && trim(CRYPTO_WALLET_ADDRESS) !== '') {
    $cryptoWallets['wallet_eth'] = [
        'key' => 'wallet_eth',
        'label' => 'ETH',
        'network' => 'ERC20',
        'hint' => 'Ethereum / USDT',
        'color' => '#627eea',
        'recommended' => true,
        'address' => trim(CRYPTO_WALLET_ADDRESS),
        'display' => 'ETH / USDT ERC20',
    ];
}
$walletsConfigured = !empty($cryptoWallets);

$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'history' ? 'history' : 'add';

$pendingDeposit = $db->fetch(
    "SELECT id, amount, description, reference, created_at FROM transactions
     WHERE user_id = ? AND type = 'deposit' AND status = 'pending'
     ORDER BY id DESC LIMIT 1",
    [$user['id']]
);

/** Parse selected wallet key from deposit description */
function deposit_wallet_key(?string $description, array $cryptoWallets): ?string
{
    if ($description === null || $description === '') {
        return null;
    }
    if (preg_match('/\(crypto\)$/i', $description)) {
        return null;
    }
    foreach ($cryptoWallets as $key => $w) {
        $needle = $w['label'] . ' ' . $w['network'];
        if (stripos($description, $needle) !== false) {
            return $key;
        }
        if (stripos($description, '(' . $w['display'] . ')') !== false) {
            return $key;
        }
    }
    return null;
}

/** Build deposit description with coin */
function deposit_description(float $amount, ?array $wallet): string
{
    if ($wallet) {
        return 'Deposit $' . number_format($amount, 2, '.', '') . ' — ' . $wallet['label'] . ' ' . $wallet['network'];
    }
    return 'Deposit $' . number_format($amount, 2, '.', '') . ' (crypto)';
}

// Cancel pending and start fresh
if (isset($_GET['new']) && $_GET['new'] === '1' && $_SERVER['REQUEST_METHOD'] !== 'POST' && $activeTab === 'add') {
    if ($pendingDeposit) {
        $db->execute(
            "UPDATE transactions SET status = 'failed' WHERE id = ? AND user_id = ? AND type = 'deposit' AND status = 'pending'",
            [$pendingDeposit['id'], $user['id']]
        );
        $pendingDeposit = null;
    }
}

// Submit TxHash
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tx']) && csrf_verify()) {
    $txId = trim($_POST['tx_hash'] ?? '');
    $depositId = (int) ($_POST['deposit_id'] ?? 0);
    if ($txId === '') {
        flash('error', 'Please paste your transaction ID (TxHash) so we can verify your payment.');
        redirect(url('add-funds.php'));
    }
    if ($depositId > 0) {
        $tx = $db->fetch(
            "SELECT id, user_id FROM transactions WHERE id = ? AND user_id = ? AND type = 'deposit' AND status = 'pending'",
            [$depositId, $user['id']]
        );
        if ($tx) {
            $db->execute(
                "UPDATE transactions SET reference = ? WHERE id = ?",
                [substr($txId, 0, 100), $tx['id']]
            );
            $fullTx = $db->fetch(
                "SELECT id, user_id, amount, description, reference, status, created_at FROM transactions WHERE id = ?",
                [$tx['id']]
            );
            $walletCatalog = DepositAutoConfirm::buildWalletCatalog($db);
            $auto = new DepositAutoConfirm();
            $check = $auto->processTransaction($fullTx, $walletCatalog);
            if ($check['approved']) {
                flash('success', 'Payment confirmed on-chain! Your balance has been credited — you can place orders now.');
            } else {
                flash('success', 'Payment submitted. ' . ($check['message'] ?? 'We are verifying your transaction automatically.'));
            }
        }
    }
    redirect(url('add-funds.php'));
}

// Select payment coin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_coin']) && csrf_verify()) {
    if (!$pendingDeposit) {
        flash('error', 'No active deposit. Please choose an amount first.');
        redirect(url('add-funds.php'));
    }
    $coinKey = $_POST['coin_key'] ?? '';
    if (!isset($cryptoWallets[$coinKey])) {
        flash('error', 'Please select a valid payment method.');
        redirect(url('add-funds.php'));
    }
    $wallet = $cryptoWallets[$coinKey];
    $amount = (float) $pendingDeposit['amount'];
    $db->execute(
        "UPDATE transactions SET description = ? WHERE id = ? AND user_id = ?",
        [deposit_description($amount, $wallet), $pendingDeposit['id'], $user['id']]
    );
    redirect(url('add-funds.php'));
}

// Amount form
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
            "UPDATE transactions SET amount = ?, description = ?, reference = '' WHERE id = ? AND user_id = ?",
            [$amount, deposit_description($amount, null), $pendingDeposit['id'], $user['id']]
        );
    } else {
        $balanceBefore = (float) $user['balance'];
        $db->insert(
            "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference, status)
             VALUES (?, 'deposit', ?, ?, ?, ?, '', 'pending')",
            [$user['id'], $amount, $balanceBefore, $balanceBefore, deposit_description($amount, null)]
        );
    }
    redirect(url('add-funds.php'));
}

// Determine wizard step
$step = 'form';
$amount = null;
$selectedWallet = null;
$depositId = 0;
$txSubmitted = false;

if ($activeTab === 'add' && $pendingDeposit) {
    $amount = (float) $pendingDeposit['amount'];
    $depositId = (int) $pendingDeposit['id'];
    $txSubmitted = trim((string) ($pendingDeposit['reference'] ?? '')) !== '';
    $walletKey = deposit_wallet_key($pendingDeposit['description'] ?? '', $cryptoWallets);

    if ($txSubmitted) {
        $step = 'awaiting';
        if ($walletKey && isset($cryptoWallets[$walletKey])) {
            $selectedWallet = $cryptoWallets[$walletKey];
        }
    } elseif ($walletKey && isset($cryptoWallets[$walletKey])) {
        $step = 'crypto_pay';
        $selectedWallet = $cryptoWallets[$walletKey];
    } else {
        $step = 'select_coin';
    }
}

// Fund history
$fundHistory = [];
if ($activeTab === 'history') {
    $fundHistory = $db->fetchAll(
        "SELECT id, amount, description, reference, status, created_at
         FROM transactions WHERE user_id = ? AND type = 'deposit'
         ORDER BY created_at DESC LIMIT 50",
        [$user['id']]
    );
}

$currentBalance = (float) ($user['balance'] ?? 0);
$balanceRow = $db->fetch("SELECT balance FROM users WHERE id = ?", [(int) $user['id']]);
if ($balanceRow !== null) {
    $currentBalance = (float) $balanceRow['balance'];
}

$stepNum = match ($step) {
    'form' => 1,
    'select_coin' => 2,
    'crypto_pay' => 2,
    'awaiting' => 3,
    default => 1,
};

$pageTitle = 'Add Funds';
$pageDescription = 'Add funds with cryptocurrency (BTC, ETH, USDT, BNB, SOL). Fast and secure.';
require_once __DIR__ . '/layouts/header.php';
?>
<link rel="stylesheet" href="<?= h(asset_url('assets/css/add-funds.css')) ?>">

<div class="add-funds-balance-box">
  <span class="balance-label">Your current balance</span>
  <span class="balance-value">$<span><?= number_format($currentBalance, 3) ?></span></span>
</div>

<?php if ($currentBalance <= 0 && $activeTab === 'add' && $step === 'form'): ?>
<div class="add-funds-welcome">
  <div>
    <?= iconBox('wallet', 'primary', 24) ?>
  </div>
  <div>
    <h2>Welcome — let's fund your account</h2>
    <p>Choose an amount below, pick your preferred crypto, send payment, and start placing orders in minutes. Minimum deposit: <strong>$<?= (int) $minDeposit ?></strong>.</p>
  </div>
  <div class="welcome-actions">
    <a href="<?= h(path('index.php')) ?>" class="btn" style="font-size:13px;">Browse services</a>
  </div>
</div>
<?php elseif ($currentBalance > 0 && $activeTab === 'add' && $step === 'form'): ?>
<div class="add-funds-ready">
  <p><?= icon('check-circle', 18, '', ['style' => 'vertical-align:-4px;margin-right:6px']) ?> Your balance is ready — you can place orders now.</p>
  <a href="<?= h(path('index.php')) ?>" class="btn btn-primary" style="padding:10px 18px;font-size:13px;">Place an order →</a>
</div>
<?php endif; ?>

<div class="add-funds-steps" aria-label="Deposit steps">
  <div class="add-funds-step <?= $stepNum === 1 ? 'active' : ($stepNum > 1 ? 'done' : '') ?>"><span>Step 1</span>Choose amount</div>
  <div class="add-funds-step <?= $stepNum === 2 ? 'active' : ($stepNum > 2 ? 'done' : '') ?>"><span>Step 2</span>Send crypto</div>
  <div class="add-funds-step <?= $stepNum === 3 ? 'active' : '' ?>"><span>Step 3</span>Start ordering</div>
</div>

<?php if ($activeTab === 'add'): ?>
<div class="add-funds-banner">
  <strong>Crypto only</strong> — BTC, ETH, USDT, BNB, SOL. No cards or PayPal.
  <?php if ($step === 'form'): ?>
  Pick an amount → choose coin → scan QR or copy address → submit TxHash.
  <?php elseif ($step === 'select_coin'): ?>
  Select your payment method. Send exactly <strong>$<?= number_format($amount, 2) ?></strong> worth of crypto.
  <?php elseif ($step === 'crypto_pay'): ?>
  Send <strong>$<?= number_format($amount, 2) ?></strong> via <strong><?= h($selectedWallet['display']) ?></strong>, then paste your TxHash below.
  <?php else: ?>
  Your payment is being verified. Balance updates after confirmation — check your email.
  <?php endif; ?>
</div>
<?php endif; ?>

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
        <thead><tr><th>ID</th><th>Date</th><th>Method</th><th>Status</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($fundHistory as $t):
            $st = strtolower($t['status'] ?? 'pending');
            $stClass = $st === 'completed' ? 'status-completed' : ($st === 'failed' ? 'status-failed' : 'status-pending');
            $method = 'Crypto';
            if (preg_match('/—\s*(.+)$/', $t['description'] ?? '', $m)) {
                $method = trim($m[1]);
            } elseif (preg_match('/\(([^)]+)\)/', $t['description'] ?? '', $m) && strtolower($m[1]) !== 'crypto') {
                $method = $m[1];
            }
        ?>
        <tr>
          <td>#<?= (int) $t['id'] ?></td>
          <td><?= h(date('Y-m-d H:i', strtotime($t['created_at']))) ?></td>
          <td><?= h($method) ?></td>
          <td><span class="<?= h($stClass) ?>"><?= h(ucfirst($st)) ?></span></td>
          <td><strong>$<?= number_format((float) $t['amount'], 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <p style="margin-top:16px;"><a href="<?= h(path('add-funds.php')) ?>" class="btn btn-primary">+ Add Funds</a></p>
  </div>

<?php elseif ($step === 'form'): ?>
  <?php if (!$walletsConfigured): ?>
  <div class="card">
    <div class="card-title">Add Funds — Unavailable</div>
    <p style="color:var(--text-muted);">Crypto wallet addresses are not configured yet. Please <a href="<?= h(path('tickets.php')) ?>">contact support</a>.</p>
  </div>
  <?php else: ?>
  <div class="card">
    <div class="card-title"><?= icon('wallet', 20, '', ['style' => 'vertical-align:-4px;margin-right:8px']) ?> How much do you want to add?</div>
    <form method="POST" id="addFundsForm">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="add_funds" value="1">
      <div class="add-funds-instructions">
        Tap a quick amount or enter your own. You will choose the crypto network on the next step.
      </div>
      <div class="form-group">
        <label class="form-label">Amount (USD)</label>
        <div class="add-funds-presets">
          <?php
          $presets = [10, 25, 50, 100, 200, 500];
          $defaultAmount = $pendingDeposit ? (float) $pendingDeposit['amount'] : max($minDeposit, 25);
          foreach ($presets as $preset):
              if ($preset >= $minDeposit):
          ?>
          <button type="button" class="preset-btn<?= abs($defaultAmount - $preset) < 0.01 ? ' active' : '' ?>" data-amount="<?= $preset ?>">$<?= $preset ?></button>
          <?php endif; endforeach; ?>
        </div>
        <input type="number" name="amount" id="amountInput" class="form-control" placeholder="Min $<?= (int) $minDeposit ?>" min="<?= $minDeposit ?>" step="0.01" required value="<?= number_format($defaultAmount, 2, '.', '') ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-block" style="padding:14px;font-size:15px;">Continue → Choose payment method</button>
    </form>
  </div>
  <?php endif; ?>

<?php elseif ($step === 'select_coin'): ?>
  <div class="card">
    <div class="card-title">Choose payment method — $<?= number_format($amount, 2) ?> USD</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:18px;">Select the cryptocurrency you will send. Make sure you use the correct network — wrong network means lost funds.</p>
    <form method="POST" id="coinSelectForm">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="select_coin" value="1">
      <input type="hidden" name="coin_key" id="coinKeyInput" value="">
      <div class="coin-grid">
        <?php foreach ($cryptoWallets as $key => $w): ?>
        <button type="button" class="coin-card" data-coin="<?= h($key) ?>" style="--coin-color:<?= h($w['color']) ?>">
          <?php if (!empty($w['recommended'])): ?><span class="coin-rec">Popular</span><?php endif; ?>
          <span class="coin-badge"><?= h($w['label']) ?></span>
          <span class="coin-name"><?= h($w['label']) ?></span>
          <span class="coin-network"><?= h($w['network']) ?></span>
          <span class="coin-hint"><?= h($w['hint']) ?></span>
        </button>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-primary btn-block" id="coinContinueBtn" style="padding:14px;font-size:15px;" disabled>Continue → Show wallet address</button>
    </form>
    <a href="<?= h(path('add-funds.php')) ?>?new=1" style="display:inline-block;margin-top:14px;font-size:13px;color:var(--text-muted);">← Change amount</a>
  </div>

<?php elseif ($step === 'crypto_pay' && $selectedWallet): ?>
  <div class="card">
    <div class="pay-summary">
      <div>
        <div class="pay-amount">
          $<?= number_format($amount, 2) ?>
          <small>Amount to send (USD equivalent)</small>
        </div>
      </div>
      <div class="pay-coin-pill" style="--coin-color:<?= h($selectedWallet['color']) ?>">
        <span class="pay-coin-dot"></span>
        <?= h($selectedWallet['display']) ?>
      </div>
    </div>

    <div class="pay-panel">
      <div class="pay-qr-wrap">
        <canvas id="walletQr" width="180" height="180" aria-label="QR code for wallet address"></canvas>
        <span class="pay-qr-label">Scan with your wallet app</span>
      </div>
      <div class="pay-address-block">
        <div class="network-warn">
          <strong>Important:</strong> Send only <strong><?= h($selectedWallet['display']) ?></strong> to this address.
          Sending via the wrong network will result in lost funds.
        </div>
        <div class="addr-label">Wallet address</div>
        <code id="walletAddress"><?= h($selectedWallet['address']) ?></code>
        <div class="pay-copy-row">
          <button type="button" class="btn btn-primary" id="copyAddressBtn"><?= icon('clipboard', 16, '', ['style' => 'vertical-align:-3px;margin-right:6px']) ?> Copy address</button>
          <a href="<?= h(path('add-funds.php')) ?>?new=1" class="btn" style="font-size:13px;">Change amount</a>
        </div>
      </div>
    </div>

    <p style="font-size:13px;font-weight:700;margin-bottom:10px;">After sending, paste your transaction ID</p>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="deposit_id" value="<?= $depositId ?>">
      <input type="hidden" name="submit_tx" value="1">
      <div class="form-group">
        <label class="form-label">Transaction ID (TxHash) <span style="color:var(--primary);">*</span></label>
        <input type="text" name="tx_hash" class="form-control" placeholder="e.g. 0xabc123... or Tron tx hash" required autocomplete="off">
        <small style="display:block;margin-top:6px;color:var(--text-muted);font-size:12px;">Find this in your wallet app under transaction history / receipt.</small>
      </div>
      <button type="submit" class="btn btn-primary btn-block" style="padding:14px;font-size:15px;">I've sent the payment</button>
    </form>

    <div class="add-funds-next-box">
      <strong>What happens next?</strong><br>
      1. Paste your TxHash — we verify it on the blockchain automatically.<br>
      2. USDT TRC20 is usually confirmed within 1–3 minutes.<br>
      3. Balance updates and you receive an <strong>email</strong> — then place your first order.
    </div>
    <p style="margin-top:14px;font-size:12px;color:var(--text-muted);">
      Wrong coin selected? <a href="<?= h(path('add-funds.php')) ?>?new=1">Start over</a> or
      <a href="<?= h(path('tickets.php')) ?>">open a ticket</a> with your TxHash.
    </p>
  </div>

<?php elseif ($step === 'awaiting'): ?>
  <div class="card">
    <div class="deposit-status-card" id="depositStatusCard">
      <div class="status-icon" id="depositStatusIcon"><?= icon('clock', 28) ?></div>
      <h3 id="depositStatusTitle">Verifying payment on blockchain…</h3>
      <p id="depositStatusMsg">We are checking your transaction automatically. This page updates every few seconds.</p>
      <div class="deposit-status-meta">
        <div><strong>Deposit ID:</strong> #<?= $depositId ?></div>
        <?php if ($selectedWallet): ?>
        <div><strong>Method:</strong> <?= h($selectedWallet['display']) ?></div>
        <?php endif; ?>
        <div><strong>Amount:</strong> $<?= number_format($amount, 2) ?></div>
        <div><strong>TxHash:</strong> <code style="font-size:11px;word-break:break-all;"><?= h($pendingDeposit['reference'] ?? '') ?></code></div>
        <div><strong>Status:</strong> <span id="depositLiveStatus">Checking…</span></div>
      </div>
    </div>
    <div id="depositConfirmedActions" style="display:none;">
      <div class="add-funds-ready" style="margin-bottom:16px;">
        <p><?= icon('check-circle', 18, '', ['style' => 'vertical-align:-4px;margin-right:6px']) ?> <span id="depositConfirmedText">Payment confirmed!</span></p>
        <a href="<?= h(path('index.php')) ?>" class="btn btn-primary">Place your first order →</a>
      </div>
    </div>
    <div id="depositWaitingActions">
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="<?= h(path('index.php')) ?>" class="btn">Browse services while you wait</a>
        <a href="<?= h(path('tickets.php')) ?>" class="btn">Contact support</a>
      </div>
    </div>
    <p style="margin-top:16px;font-size:12px;color:var(--text-muted);">
      Need to deposit more? <a href="<?= h(path('add-funds.php')) ?>?new=1">Start a new deposit</a>
    </p>
  </div>
<?php endif; ?>

<div class="add-funds-toast" id="copyToast" role="status" aria-live="polite">Address copied!</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js" crossorigin="anonymous"></script>
<script>
(function(){
  document.querySelectorAll('.preset-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.getElementById('amountInput').value = this.dataset.amount;
      document.querySelectorAll('.preset-btn').forEach(function(b){ b.classList.remove('active'); });
      this.classList.add('active');
    });
  });

  var coinCards = document.querySelectorAll('.coin-card');
  var coinKeyInput = document.getElementById('coinKeyInput');
  var coinContinueBtn = document.getElementById('coinContinueBtn');
  if (coinCards.length && coinKeyInput) {
    coinCards.forEach(function(card){
      card.addEventListener('click', function(){
        coinCards.forEach(function(c){ c.classList.remove('selected'); });
        this.classList.add('selected');
        coinKeyInput.value = this.dataset.coin;
        if (coinContinueBtn) coinContinueBtn.disabled = false;
      });
    });
  }

  var toast = document.getElementById('copyToast');
  function showToast(msg) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(function(){ toast.classList.remove('show'); }, 2200);
  }

  var copyBtn = document.getElementById('copyAddressBtn');
  var walletAddr = document.getElementById('walletAddress');
  if (copyBtn && walletAddr) {
    copyBtn.addEventListener('click', function(){
      var text = walletAddr.textContent.trim();
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function(){ showToast('Address copied!'); });
      } else {
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('Address copied!');
      }
    });
  }

  var qrCanvas = document.getElementById('walletQr');
  if (qrCanvas && walletAddr && typeof QRCode !== 'undefined') {
    QRCode.toCanvas(qrCanvas, walletAddr.textContent.trim(), {
      width: 180,
      margin: 2,
      color: { dark: '#111827', light: '#ffffff' }
    }, function(err){
      if (err) console.warn('QR generation failed', err);
    });
  }

  var depositPoll = document.getElementById('depositLiveStatus');
  if (depositPoll) {
    var statusUrl = <?= json_encode(path('api/deposit-status.php')) ?>;
    function pollDeposit() {
      fetch(statusUrl, { credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(data){
          if (!data.ok) return;
          var live = document.getElementById('depositLiveStatus');
          var msg = document.getElementById('depositStatusMsg');
          var title = document.getElementById('depositStatusTitle');
          if (live) live.textContent = data.status || 'pending';
          if (msg && data.message) msg.textContent = data.message;
          if (data.approved || data.status === 'confirmed') {
            if (title) title.textContent = 'Payment confirmed!';
            var card = document.getElementById('depositStatusCard');
            if (card) card.style.borderColor = '#bbf7d0';
            var confirmed = document.getElementById('depositConfirmedActions');
            var waiting = document.getElementById('depositWaitingActions');
            if (confirmed) confirmed.style.display = 'block';
            if (waiting) waiting.style.display = 'none';
            if (data.balance != null) {
              var t = document.getElementById('depositConfirmedText');
              if (t) t.textContent = 'Payment confirmed! New balance: $' + Number(data.balance).toFixed(3);
            }
            return;
          }
          setTimeout(pollDeposit, 8000);
        })
        .catch(function(){ setTimeout(pollDeposit, 12000); });
    }
    setTimeout(pollDeposit, 3000);
  }
})();
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
