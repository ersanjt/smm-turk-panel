<?php // add-funds.php
require_once __DIR__ . '/includes/init.php';
$auth->requireLogin();
$pageTitle = 'Add Funds';
require_once __DIR__ . '/includes/header.php';
?>
<div class="alert alert-info">💡 Choose a payment method. Multiple failed attempts with the same method may lead to account suspension.</div>
<div style="max-width:520px;">
  <div class="card">
    <div class="card-title">💳 Add Funds</div>
    <div class="form-group">
      <label class="form-label">Payment Method</label>
      <select class="form-control">
        <option>💳 Credit/Debit Card — Visa/Master/Amex — Min $10</option>
        <option>₿ Cryptocurrency — Bitcoin, ETH, USDT</option>
        <option>🔗 Other Methods</option>
      </select>
    </div>
    <div style="background:#f8f9ff;border-radius:10px;padding:14px;margin-bottom:16px;font-size:12.5px;color:var(--text-muted);line-height:1.8;">
      📌 If your card didn't work, ask your bank to allow the transaction.<br>
      📌 If payment isn't added automatically, open a support ticket.
    </div>
    <div class="form-group">
      <label class="form-label">Amount (USD)</label>
      <input type="number" class="form-control" placeholder="Min: $10.00" min="10" step="0.01">
    </div>
    <label style="display:flex;align-items:center;gap:10px;font-size:13px;margin-bottom:18px;cursor:pointer;">
      <input type="checkbox" checked> By submitting this payment, I confirm it is not fraudulent.
    </label>
    <button class="btn btn-primary btn-block">💳 Proceed to Payment</button>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
