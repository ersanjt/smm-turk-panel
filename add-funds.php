<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

$minDeposit = (float) ($db->getSetting('min_deposit') ?: 10);
$minDeposit = $minDeposit >= 1 ? $minDeposit : 10;

$paymentMethods = PaymentRegistry::enabledMethods();
$methodsAvailable = !empty($paymentMethods);

$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'history' ? 'history' : 'add';
$selectedMethod = $_POST['method'] ?? $_GET['method'] ?? '';

$pendingDeposit = $db->fetch(
    "SELECT id, amount, description, reference, created_at FROM transactions
     WHERE user_id = ? AND type = 'deposit' AND status = 'pending'
     ORDER BY id DESC LIMIT 1",
    [$user['id']]
);

if (isset($_GET['new']) && $_GET['new'] === '1' && $_SERVER['REQUEST_METHOD'] !== 'POST' && $activeTab === 'add') {
    if ($pendingDeposit) {
        $db->execute(
            "UPDATE transactions SET status = 'failed' WHERE id = ? AND user_id = ? AND type = 'deposit' AND status = 'pending'",
            [$pendingDeposit['id'], $user['id']]
        );
        $pendingDeposit = null;
    }
}

// Submit TxHash (USDT TRC20 manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tx']) && csrf_verify()) {
    $txId = trim($_POST['tx_hash'] ?? '');
    $depositId = (int) ($_POST['deposit_id'] ?? 0);
    if ($txId === '') {
        flash('error', 'Please paste your transaction ID (TxHash).');
        redirect(url('add-funds.php'));
    }
    if ($depositId > 0) {
        $tx = $db->fetch(
            "SELECT id, user_id FROM transactions WHERE id = ? AND user_id = ? AND type = 'deposit' AND status = 'pending'",
            [$depositId, $user['id']]
        );
        if ($tx) {
            $db->execute("UPDATE transactions SET reference = ? WHERE id = ?", [substr($txId, 0, 100), $tx['id']]);
            $fullTx = $db->fetch(
                "SELECT id, user_id, amount, description, reference, status, created_at FROM transactions WHERE id = ?",
                [$tx['id']]
            );
            $walletCatalog = DepositAutoConfirm::buildWalletCatalog($db);
            $auto = new DepositAutoConfirm();
            $check = $auto->processTransaction($fullTx, $walletCatalog);
            if ($check['approved']) {
                flash('success', 'Payment confirmed! Your balance has been credited.');
            } else {
                flash('success', 'Payment submitted. ' . ($check['message'] ?? 'Verifying on-chain…'));
            }
        }
    }
    redirect(url('add-funds.php'));
}

// Create deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_funds']) && csrf_verify()) {
    if (!$methodsAvailable) {
        flash('error', 'No payment methods are enabled. Contact support.');
        redirect(url('add-funds.php'));
    }
    $method = strtolower(trim($_POST['method'] ?? ''));
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;

    if (!isset($paymentMethods[$method])) {
        flash('error', 'Please select a valid payment method.');
        redirect(url('add-funds.php'));
    }
    if ($amount < $minDeposit) {
        flash('error', "Minimum deposit is \${$minDeposit}.");
        redirect(url('add-funds.php'));
    }

    if ($pendingDeposit) {
        $db->execute(
            "UPDATE transactions SET amount = ?, description = ?, reference = '' WHERE id = ? AND user_id = ?",
            [$amount, PaymentRegistry::depositDescription($amount, $method), $pendingDeposit['id'], $user['id']]
        );
        $depositId = (int) $pendingDeposit['id'];
    } else {
        $balanceBefore = (float) $user['balance'];
        $depositId = (int) $db->insert(
            "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference, status)
             VALUES (?, 'deposit', ?, ?, ?, ?, '', 'pending')",
            [$user['id'], $amount, $balanceBefore, $balanceBefore, PaymentRegistry::depositDescription($amount, $method)]
        );
    }

    $processor = new PaymentProcessor();
    $init = $processor->initiate($method, $depositId, (int) $user['id'], $amount, (string) ($user['email'] ?? ''));

    if (!$init['success']) {
        $db->execute(
            "UPDATE transactions SET status = 'failed' WHERE id = ? AND user_id = ?",
            [$depositId, $user['id']]
        );
        flash('error', $init['error'] ?? 'Could not start payment.');
        redirect(url('add-funds.php'));
    }

    if (!empty($init['manual'])) {
        if (!empty($init['heleket'])) {
            redirect(url('add-funds.php'));
        }
        redirect(page_url('add-funds.php', ['method' => $method]));
    }

    if (!empty($init['redirect_url'])) {
        redirect($init['redirect_url']);
    }

    flash('error', 'Payment gateway did not return a URL.');
    redirect(url('add-funds.php'));
}

// Wizard state
$step = 'form';
$amount = null;
$depositId = 0;
$txSubmitted = false;
$activeMethod = null;
$manualPay = null;
$heleketPay = null;

if ($activeTab === 'add' && $pendingDeposit) {
    $amount = (float) $pendingDeposit['amount'];
    $depositId = (int) $pendingDeposit['id'];
    $txSubmitted = trim((string) ($pendingDeposit['reference'] ?? '')) !== '';
    $methodSlug = PaymentRegistry::parseMethodFromDescription($pendingDeposit['description'] ?? '');

    if ($methodSlug === PaymentRegistry::HELEKET) {
        $activeMethod = PaymentRegistry::HELEKET;
        $hkRef = PaymentRegistry::parseHeleketRef($pendingDeposit['reference'] ?? '');
        if ($hkRef && ($hkRef['address'] !== '' || $hkRef['uuid'] !== '')) {
            $heleketPay = [
                'uuid' => $hkRef['uuid'],
                'address' => $hkRef['address'],
                'payer_amount' => '',
                'currency' => strtoupper(trim((string) $db->getSetting('payment_heleket_currency') ?: 'USDT')),
                'network' => strtolower(trim((string) $db->getSetting('payment_heleket_network') ?: 'bsc')),
            ];
            if ($heleketPay['address'] === '' && $heleketPay['uuid'] !== '') {
                $proc = new PaymentProcessor();
                $info = $proc->heleketPaymentInfo($heleketPay['uuid']);
                if (is_array($info)) {
                    $heleketPay['address'] = trim((string) ($info['address'] ?? ''));
                    $heleketPay['payer_amount'] = (string) ($info['payer_amount'] ?? '');
                    $heleketPay['currency'] = (string) ($info['payer_currency'] ?? $heleketPay['currency']);
                    $heleketPay['network'] = (string) ($info['network'] ?? $heleketPay['network']);
                }
            }
            if ($heleketPay['address'] !== '') {
                $step = 'heleket_pay';
            }
        }
    } elseif ($methodSlug !== null && ($methodSlug === PaymentRegistry::USDT_TRC20 || PaymentRegistry::isManualWalletSlug($methodSlug))) {
        $activeMethod = $methodSlug;
        $manualPay = PaymentRegistry::manualPayMeta($methodSlug);
        if ($txSubmitted) {
            $step = 'awaiting';
        } elseif ($manualPay) {
            $step = 'manual_pay';
        }
    }
}

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

$defaultMethod = $selectedMethod && isset($paymentMethods[$selectedMethod])
    ? $selectedMethod
    : (array_key_first($paymentMethods) ?: '');

$pageTitle = 'Add Funds';
$pageDescription = 'Add funds via SmmPayGate, Heleket, USDT TRC20, Binance Pay, ZarinPal, or CryptoCloud.';
require_once __DIR__ . '/layouts/header.php';
?>
<link rel="stylesheet" href="<?= h(asset_url('assets/css/add-funds.css')) ?>">

<div class="add-funds-balance-box">
  <span class="balance-label">Your current balance</span>
  <span class="balance-value">$<span><?= number_format($currentBalance, 3) ?></span></span>
</div>

<div class="add-funds-tabs">
  <a href="<?= h(path('add-funds.php')) ?>" class="<?= $activeTab === 'add' ? 'active' : '' ?>">Add Funds</a>
  <a href="<?= h(path('add-funds.php')) ?>?tab=history" class="<?= $activeTab === 'history' ? 'active' : '' ?>">Fund History</a>
</div>

<?php if ($activeTab === 'history'): ?>
  <div class="card add-funds-card">
    <div class="card-title">Fund History</div>
    <?php if (empty($fundHistory)): ?>
    <p class="text-muted">No deposits yet.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table add-funds-table">
        <thead><tr><th>ID</th><th>Date</th><th>Method</th><th>Status</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($fundHistory as $t):
            $st = strtolower($t['status'] ?? 'pending');
            $stClass = $st === 'completed' ? 'status-completed' : ($st === 'failed' ? 'status-failed' : 'status-pending');
            $method = PaymentRegistry::parseMethodFromDescription($t['description'] ?? '');
            $methodLabel = $method ? PaymentRegistry::label($method) : 'Deposit';
            if (preg_match('/—\s*(.+)$/', $t['description'] ?? '', $m)) {
                $methodLabel = trim($m[1]);
            }
        ?>
        <tr>
          <td>#<?= (int) $t['id'] ?></td>
          <td><?= h(date('Y-m-d H:i', strtotime($t['created_at']))) ?></td>
          <td><?= h($methodLabel) ?></td>
          <td><span class="<?= h($stClass) ?>"><?= h(ucfirst($st)) ?></span></td>
          <td><strong>$<?= number_format((float) $t['amount'], 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <p class="add-funds-history-cta"><a href="<?= h(path('add-funds.php')) ?>" class="btn btn-primary">+ Add Funds</a></p>
  </div>

<?php elseif ($step === 'heleket_pay' && $heleketPay): ?>
  <div class="card add-funds-card">
    <div class="pay-summary">
      <div class="pay-amount">
        $<?= number_format($amount, 2) ?>
        <small>Deposit amount (USD)</small>
      </div>
      <div class="pay-coin-pill" style="--coin-color:#e30a17">
        <span class="pay-coin-dot"></span>
        Heleket · <?= h($heleketPay['currency']) ?> <?= h(PaymentRegistry::heleketNetworkLabel($heleketPay['network'])) ?>
      </div>
    </div>
    <?php if ($heleketPay['payer_amount'] !== ''): ?>
    <div class="heleket-pay-amount">
      Send exactly <strong><?= h($heleketPay['payer_amount']) ?> <?= h($heleketPay['currency']) ?></strong>
    </div>
    <?php endif; ?>
    <div class="pay-panel">
      <div class="pay-qr-wrap">
        <div id="walletQr" class="pay-qr-canvas" aria-label="Heleket QR"></div>
        <span class="pay-qr-label">Scan with your wallet app</span>
      </div>
      <div class="pay-address-block">
        <div class="network-warn">
          <strong>Heleket:</strong> Send only <strong><?= h($heleketPay['currency']) ?></strong> on
          <strong><?= h(PaymentRegistry::heleketNetworkLabel($heleketPay['network'])) ?></strong>.
          Wrong network = lost funds.
        </div>
        <div class="addr-label">Wallet address</div>
        <code id="walletAddress"><?= h($heleketPay['address']) ?></code>
        <div class="pay-copy-row">
          <button type="button" class="btn btn-primary" id="copyAddressBtn">Copy address</button>
          <?php if (!empty($heleketPay['pay_url'])): ?>
          <a href="<?= h($heleketPay['pay_url']) ?>" class="btn" target="_blank" rel="noopener">Open Heleket page</a>
          <?php endif; ?>
          <a href="<?= h(path('add-funds.php')) ?>?new=1" class="btn">Cancel</a>
        </div>
      </div>
    </div>
    <div class="add-funds-next-box">
      <strong>What happens next?</strong><br>
      1. Send crypto to the address above.<br>
      2. Heleket confirms payment and sends a webhook to our panel.<br>
      3. Balance updates automatically — this page refreshes status below.
    </div>
    <div class="deposit-status-card" id="depositStatusCard" style="margin-top:16px;">
      <h3 id="depositStatusTitle">Waiting for Heleket payment…</h3>
      <p id="depositStatusMsg">Status checks every few seconds.</p>
      <div class="deposit-status-meta">
        <div><strong>Deposit ID:</strong> #<?= $depositId ?></div>
        <div><strong>Status:</strong> <span id="depositLiveStatus">Pending</span></div>
      </div>
    </div>
    <div id="depositConfirmedActions" style="display:none;margin-top:12px;">
      <a href="<?= h(path('index.php')) ?>" class="btn btn-primary">Place an order →</a>
    </div>
  </div>

<?php elseif ($step === 'manual_pay' && $manualPay): ?>
  <div class="card add-funds-card">
    <div class="pay-summary">
      <div class="pay-amount">$<?= number_format($amount, 2) ?><small>Send <?= h($manualPay['label']) ?> (<?= h($manualPay['network']) ?>)</small></div>
      <div class="pay-coin-pill" style="--coin-color:#26a17b"><span class="pay-coin-dot"></span><?= h($manualPay['label'] . ' ' . $manualPay['network']) ?></div>
    </div>
    <div class="pay-panel">
      <div class="pay-qr-wrap">
        <div id="walletQr" class="pay-qr-canvas" aria-label="QR code"></div>
        <span class="pay-qr-label">Scan with wallet app</span>
      </div>
      <div class="pay-address-block">
        <div class="network-warn"><strong>Important:</strong> Send only <strong><?= h($manualPay['label']) ?> on <?= h($manualPay['network']) ?></strong> to this address.</div>
        <div class="addr-label">Wallet address</div>
        <code id="walletAddress"><?= h($manualPay['address']) ?></code>
        <div class="pay-copy-row">
          <button type="button" class="btn btn-primary" id="copyAddressBtn">Copy address</button>
          <a href="<?= h(path('add-funds.php')) ?>?new=1" class="btn">Cancel</a>
        </div>
      </div>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="deposit_id" value="<?= $depositId ?>">
      <input type="hidden" name="submit_tx" value="1">
      <div class="form-group">
        <label class="form-label">Transaction ID (TxHash)</label>
        <input type="text" name="tx_hash" class="form-control" placeholder="Paste blockchain transaction hash" required autocomplete="off">
      </div>
      <button type="submit" class="btn btn-primary btn-block">I've sent the payment</button>
    </form>
  </div>

<?php elseif ($step === 'awaiting'): ?>
  <div class="card add-funds-card">
    <div class="deposit-status-card" id="depositStatusCard">
      <h3 id="depositStatusTitle">Verifying payment…</h3>
      <p id="depositStatusMsg">Checking your <?= h($manualPay['label'] ?? 'crypto') ?> <?= h($manualPay['network'] ?? '') ?> transaction.</p>
      <div class="deposit-status-meta">
        <div><strong>Deposit ID:</strong> #<?= $depositId ?></div>
        <div><strong>Amount:</strong> $<?= number_format($amount, 2) ?></div>
        <div><strong>TxHash:</strong> <code><?= h($pendingDeposit['reference'] ?? '') ?></code></div>
        <div><strong>Status:</strong> <span id="depositLiveStatus">Checking…</span></div>
      </div>
    </div>
    <div id="depositConfirmedActions" style="display:none;">
      <a href="<?= h(path('index.php')) ?>" class="btn btn-primary">Place an order →</a>
    </div>
    <p class="text-muted-sm"><a href="<?= h(path('add-funds.php')) ?>?new=1">Start a new deposit</a></p>
  </div>

<?php else: ?>
  <div class="card add-funds-card">
    <div class="card-title">Add Funds</div>
    <?php if (!$methodsAvailable): ?>
    <p class="text-muted">No payment methods are active yet.</p>
    <ul class="text-muted" style="font-size:13px;line-height:1.7;margin:12px 0 16px;padding-left:20px;">
      <li><strong>Crypto wallets</strong> — set BTC, ETH, USDT, BNB, SOL addresses in Admin → Settings → Crypto Wallets</li>
      <li><strong>Gateways</strong> — enable SmmPayGate, Heleket, Binance Pay, ZarinPal, CryptoCloud and add API keys</li>
    </ul>
    <?php if ($auth->isAdmin()): ?>
    <p><a href="<?= h(path('admin/admin-settings.php')) ?>" class="btn btn-primary">Open Admin Settings</a></p>
    <?php else: ?>
    <p><a href="<?= h(path('tickets.php')) ?>" class="btn">Contact support</a></p>
    <?php endif; ?>
    <?php else: ?>
    <form method="POST" class="add-funds-form">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="add_funds" value="1">
      <div class="form-group">
        <label class="form-label" for="payMethod">Method</label>
        <select name="method" id="payMethod" class="form-control add-funds-method-select" required>
          <?php foreach ($paymentMethods as $slug => $meta): ?>
          <option value="<?= h($slug) ?>" <?= $slug === $defaultMethod ? 'selected' : '' ?>><?= h($meta['icon'] . ' ' . $meta['label']) ?> — <?= h($meta['desc']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="amountInput">Amount (USD)</label>
        <div class="add-funds-presets">
          <?php
          $presets = [10, 25, 50, 100, 200, 500];
          $defaultAmount = max($minDeposit, 25);
          foreach ($presets as $preset):
              if ($preset >= $minDeposit):
          ?>
          <button type="button" class="preset-btn" data-amount="<?= $preset ?>">$<?= $preset ?></button>
          <?php endif; endforeach; ?>
        </div>
        <input type="number" name="amount" id="amountInput" class="form-control" min="<?= $minDeposit ?>" step="0.01" required value="<?= number_format($defaultAmount, 2, '.', '') ?>" placeholder="Min $<?= (int) $minDeposit ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-block add-funds-submit">Continue to payment</button>
      <p class="add-funds-hint">Minimum deposit: <strong>$<?= (int) $minDeposit ?></strong>. Crypto wallets and redirect gateways (Heleket, Binance Pay, ZarinPal, etc.) appear when configured in Admin → Settings.</p>
    </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="add-funds-toast" id="copyToast" role="status">Address copied!</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" crossorigin="anonymous"></script>
<script>
(function(){
  document.querySelectorAll('.preset-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      var inp = document.getElementById('amountInput');
      if (inp) inp.value = this.dataset.amount;
      document.querySelectorAll('.preset-btn').forEach(function(b){ b.classList.remove('active'); });
      this.classList.add('active');
    });
  });
  var copyBtn = document.getElementById('copyAddressBtn');
  var walletAddr = document.getElementById('walletAddress');
  var toast = document.getElementById('copyToast');
  if (copyBtn && walletAddr) {
    copyBtn.addEventListener('click', function(){
      var text = walletAddr.textContent.trim();
      (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function(){
        if (toast) { toast.classList.add('show'); setTimeout(function(){ toast.classList.remove('show'); }, 2000); }
      });
    });
  }
  var qrEl = document.getElementById('walletQr');
  if (qrEl && walletAddr && typeof QRCode !== 'undefined') {
    var addr = walletAddr.textContent.trim();
    if (addr) {
      qrEl.innerHTML = '';
      new QRCode(qrEl, {
        text: addr,
        width: 180,
        height: 180,
        colorDark: '#0f172a',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });
    }
  }
  var depositPoll = document.getElementById('depositLiveStatus');
  if (depositPoll) {
    var statusUrl = <?= json_encode(path('api/deposit-status.php')) ?>;
    function pollDeposit() {
      fetch(statusUrl, { credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(data){
        if (!data.ok) return;
        var live = document.getElementById('depositLiveStatus');
        if (live) live.textContent = data.status || 'pending';
        if (data.approved || data.status === 'confirmed') {
          var confirmed = document.getElementById('depositConfirmedActions');
          if (confirmed) confirmed.style.display = 'block';
          return;
        }
        setTimeout(pollDeposit, 8000);
      }).catch(function(){ setTimeout(pollDeposit, 12000); });
    }
    setTimeout(pollDeposit, 3000);
  }
})();
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
