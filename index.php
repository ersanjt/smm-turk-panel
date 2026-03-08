<?php
require_once __DIR__ . '/includes/init.php';
$auth->requireLogin();

$pageTitle = 'New Order';
$db = Database::getInstance();
$om = new OrderManager();

$message = '';
$error   = '';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $link      = trim($_POST['link'] ?? '');
    $quantity  = (int)($_POST['quantity'] ?? 0);

    if (!$serviceId || !$link || !$quantity) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($link, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid URL.';
    } else {
        $result = $om->placeOrder($auth->getUserId(), $serviceId, $link, $quantity);
        if ($result['success']) {
            flash('success', "✅ Order #{$result['order_id']} placed! Charged: \${$result['charge']}");
            header('Location: /orders.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

// Load categories and services
$categories = $db->fetchAll("SELECT DISTINCT category FROM services WHERE status='active' ORDER BY category");
$selectedCat = $_GET['cat'] ?? ($categories[0]['category'] ?? '');
$services = $db->fetchAll(
    "SELECT * FROM services WHERE status='active' AND category=? ORDER BY service_id",
    [$selectedCat]
);

require_once __DIR__ . '/includes/header.php';
?>

<style>
.platform-tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:20px}
.ptab{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:20px;background:#fff;border:1.5px solid var(--border);font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;color:var(--text-muted);text-decoration:none}
.ptab:hover,.ptab.active{background:var(--primary);border-color:var(--primary);color:#fff}
.order-grid{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
.price-box{background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:12px;padding:14px 18px;color:#fff;display:flex;justify-content:space-between;align-items:center;margin:16px 0}
.price-box .lbl{font-size:11px;opacity:.8;margin-bottom:2px}
.price-box .amt{font-family:'Syne',sans-serif;font-size:20px;font-weight:800}
.desc-item{display:flex;align-items:flex-start;gap:8px;font-size:12px;margin-bottom:9px;color:var(--text-muted);line-height:1.5}
.desc-item strong{color:var(--text)}
</style>

<!-- Category tabs -->
<div class="platform-tabs">
  <?php foreach ($categories as $cat): ?>
  <a class="ptab <?= $cat['category'] === $selectedCat ? 'active' : '' ?>"
     href="?cat=<?= urlencode($cat['category']) ?>">
    <?= h($cat['category']) ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($error): ?>
<div class="alert alert-error">❌ <?= h($error) ?></div>
<?php endif; ?>

<div class="order-grid">
  <div class="card">
    <div class="card-title">📦 New Order</div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label class="form-label">Service</label>
        <select name="service_id" id="service-select" class="form-control" onchange="updateDesc()" required>
          <option value="">— Select a service —</option>
          <?php foreach ($services as $s): ?>
          <option value="<?= $s['service_id'] ?>"
            data-rate="<?= $s['rate'] ?>"
            data-min="<?= $s['min'] ?>"
            data-max="<?= $s['max'] ?>"
            data-refill="<?= $s['refill'] ? 'Yes' : 'No' ?>"
            data-markup="<?= $s['markup'] ?>">
            ID-<?= $s['service_id'] ?> | <?= h(mb_substr($s['name'], 0, 90)) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Link</label>
        <input type="url" name="link" id="order-link" class="form-control" placeholder="https://..." required>
      </div>

      <div class="form-group">
        <label class="form-label">Quantity</label>
        <input type="number" name="quantity" id="order-qty" class="form-control"
               placeholder="Enter quantity" oninput="calcPrice()" required min="1">
        <div style="text-align:right;font-size:11px;color:var(--text-muted);margin-top:4px;" id="qty-hint">—</div>
      </div>

      <div class="price-box">
        <div>
          <div class="lbl">Estimated Price</div>
          <div class="amt" id="price-display">$0.0000</div>
        </div>
        <div style="text-align:right">
          <div class="lbl">Rate / 1000</div>
          <div style="font-size:14px;font-weight:700" id="rate-display">—</div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">🚀 Place Order</button>
    </form>
  </div>

  <!-- Description panel -->
  <div class="card" id="desc-panel">
    <div class="card-title">📋 Service Info</div>
    <div id="desc-content" style="color:var(--text-muted);font-size:13px;">Select a service to see details.</div>
  </div>
</div>

<script>
const services = <?= json_encode(array_column($services, null, 'service_id')) ?>;

function updateDesc() {
  const sel = document.getElementById('service-select');
  const opt = sel.options[sel.selectedIndex];
  if (!opt.value) return;

  const rate   = parseFloat(opt.dataset.rate);
  const min    = parseInt(opt.dataset.min);
  const max    = parseInt(opt.dataset.max);
  const markup = parseFloat(opt.dataset.markup || 0);
  const markup_rate = rate * (1 + markup/100);
  const refill = opt.dataset.refill;

  document.getElementById('rate-display').textContent = '$' + markup_rate.toFixed(5);
  document.getElementById('qty-hint').textContent = 'Min: ' + min.toLocaleString() + ' — Max: ' + max.toLocaleString();
  document.getElementById('order-qty').min = min;
  document.getElementById('order-qty').max = max;
  document.getElementById('order-qty').placeholder = 'Min: ' + min.toLocaleString() + ' — Max: ' + max.toLocaleString();

  document.getElementById('desc-content').innerHTML = `
    <div class="desc-item">⬆️ <div><strong>Quality:</strong> High Quality</div></div>
    <div class="desc-item">⏱️ <div><strong>Start:</strong> 0-6 Hours</div></div>
    <div class="desc-item">♻️ <div><strong>Refill:</strong> ${refill}</div></div>
    <div class="desc-item">📊 <div><strong>Min Order:</strong> ${min.toLocaleString()}</div></div>
    <div class="desc-item">📊 <div><strong>Max Order:</strong> ${max.toLocaleString()}</div></div>
    <div class="desc-item">💵 <div><strong>Rate per 1000:</strong> $${markup_rate.toFixed(5)}</div></div>
    <div style="background:#fff3e0;border-radius:10px;padding:12px;margin-top:12px;font-size:11.5px;color:#7a4700;line-height:1.7;">
      ⚠️ Make sure your account/page is <strong>public</strong> before ordering.<br>
      🔴 Do not change the link after ordering.
    </div>
  `;
  calcPrice();
}

function calcPrice() {
  const sel    = document.getElementById('service-select');
  const opt    = sel.options[sel.selectedIndex];
  if (!opt.value) return;
  const rate   = parseFloat(opt.dataset.rate || 0);
  const markup = parseFloat(opt.dataset.markup || 0);
  const markup_rate = rate * (1 + markup/100);
  const qty    = parseFloat(document.getElementById('order-qty').value) || 0;
  const price  = (qty / 1000) * markup_rate;
  document.getElementById('price-display').textContent = '$' + price.toFixed(4);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
