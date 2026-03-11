<?php
require_once __DIR__ . '/app/init.php';
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('home.php'), true, 302);
    exit;
}

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
            header('Location: ' . url('orders.php'));
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

// Load categories and services (dedupe by trimmed category to avoid duplicate filter tags)
$categoriesRaw = $db->fetchAll("SELECT DISTINCT category FROM services WHERE status='active' ORDER BY category");
$seen = [];
$categories = [];
foreach ($categoriesRaw as $row) {
    $cat = trim($row['category'] ?? '');
    if ($cat !== '' && !isset($seen[$cat])) {
        $seen[$cat] = true;
        $categories[] = ['category' => $cat];
    }
}
// Per-category service counts for tooltips and badges
$categoryCounts = $db->fetchAll("SELECT TRIM(COALESCE(category,'')) AS category, COUNT(*) AS cnt FROM services WHERE status='active' GROUP BY TRIM(COALESCE(category,''))");
$countByCategory = [];
foreach ($categoryCounts as $r) {
    $countByCategory[trim($r['category'] ?? '')] = (int) $r['cnt'];
}
$totalServicesCount = (int) $db->fetch("SELECT COUNT(*) c FROM services WHERE status='active'")['c'];
$catParam = isset($_GET['cat']) ? trim((string)$_GET['cat']) : null;
$preselectServiceId = isset($_GET['service']) ? (int)$_GET['service'] : 0;
if ($preselectServiceId) {
    $preselect = $db->fetch("SELECT category FROM services WHERE service_id=? AND status='active'", [$preselectServiceId]);
    if ($preselect && ($catParam === null || trim($preselect['category'] ?? '') !== $catParam)) {
        $catParam = trim($preselect['category'] ?? '');
    }
}
// When no category in URL: show all services and only "All" (+ ) is active (limit when "All" to avoid huge page)
$selectedCat = ($catParam !== null && $catParam !== '') ? $catParam : '';
if ($selectedCat !== '') {
    $services = $db->fetchAll(
        "SELECT * FROM services WHERE status='active' AND TRIM(COALESCE(category,''))=? ORDER BY service_id",
        [$selectedCat]
    );
    $showAllLimitHint = false;
} else {
    $services = $db->fetchAll("SELECT * FROM services WHERE status='active' ORDER BY service_id LIMIT 1500");
    $showAllLimitHint = $totalServicesCount > 1500;
}
$searchQ = trim($_GET['q'] ?? '');
if ($searchQ !== '') {
    $services = array_filter($services, function($s) use ($searchQ) {
        return stripos($s['name'], $searchQ) !== false;
    });
    $services = array_values($services);
}
$hasServices = count($services) > 0;

require_once __DIR__ . '/app/PlatformIcons.php';
require_once __DIR__ . '/layouts/header.php';
?>

<style>
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.order-tabs{display:flex;gap:4px;margin-bottom:18px;border-bottom:1px solid var(--border);padding-bottom:0}
.order-tab{padding:10px 18px;border-radius:10px 10px 0 0;font-size:13px;font-weight:600;text-decoration:none;color:var(--text-muted);transition:all .2s}
.order-tab:hover{color:var(--primary);background:var(--bg)}
.order-tab.active{background:var(--primary);color:#fff}
.order-section-label{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);margin-bottom:10px;display:block}
/* Toolbar: search + category + filters in one block */
.order-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-bottom:16px;padding:14px 18px;background:var(--bg);border:1px solid var(--border);border-radius:12px}
.order-toolbar .form-control{min-width:160px}
.order-toolbar .btn{padding:10px 16px}
.order-toolbar label.checkbox-label{display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--text-muted);margin:0}
.checkbox-hint{font-weight:500;font-size:11px;opacity:.85}
/* Category pills: labeled, scrollable, no icon-only grid */
.order-cat-pills{display:flex;gap:8px;overflow-x:auto;padding-bottom:8px;margin-bottom:20px;align-items:center;-webkit-overflow-scrolling:touch;scrollbar-width:thin}
.order-cat-pills::-webkit-scrollbar{height:6px}
.order-cat-pill{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:999px;border:2px solid var(--border);background:#fff;white-space:nowrap;text-decoration:none;color:var(--text);font-size:13px;font-weight:600;transition:border-color .2s,background .2s,color .2s;flex-shrink:0}
.order-cat-pill svg{width:18px;height:18px;flex-shrink:0}
.order-cat-pill .order-cat-name{max-width:180px;overflow:hidden;text-overflow:ellipsis}
.order-cat-pill .order-cat-count{min-width:22px;padding:2px 6px;border-radius:10px;background:var(--border);color:var(--text-muted);font-size:11px;font-weight:700;text-align:center}
.order-cat-pill:hover,.order-cat-pill.active{border-color:var(--primary);background:var(--primary);color:#fff}
.order-cat-pill:hover .order-cat-count,.order-cat-pill.active .order-cat-count{background:rgba(255,255,255,.25);color:#fff}
.order-form-row{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px}
.order-form-row .form-group{margin-bottom:0;flex:1;min-width:180px}
.order-form-row label.checkbox-label{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--text-muted)}
.order-grid{display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start}
.price-box{background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:12px;padding:14px 18px;color:#fff;display:flex;justify-content:space-between;align-items:center;margin:16px 0}
.price-box .lbl{font-size:11px;opacity:.8;margin-bottom:2px}
.price-box .amt{font-family:'Syne',sans-serif;font-size:20px;font-weight:800}
.desc-item{display:flex;align-items:flex-start;gap:8px;font-size:12px;margin-bottom:9px;color:var(--text-muted);line-height:1.5}
.desc-item strong{color:var(--text)}
.desc-notes{margin-top:14px;padding-top:12px;border-top:1px solid var(--border)}
.desc-notes li{display:flex;align-items:flex-start;gap:8px;margin-bottom:8px;font-size:11.5px;color:var(--text-muted);line-height:1.6}
.desc-notes .asterisk{color:var(--primary);font-weight:700;flex-shrink:0}
#desc-panel pre{word-break:break-all;overflow-x:auto;max-width:100%}
.order-form-row form{min-width:0}
@media(max-width:768px){
  .order-toolbar{flex-direction:column;align-items:stretch}
  .order-toolbar .form-control,.order-toolbar #cat-select{min-width:0;width:100%}
  .order-form-row{flex-direction:column;align-items:stretch}
  .order-form-row form{max-width:100%;width:100%}
  .order-form-row form .form-control{flex:1}
  .order-form-row > div{width:100%}
  .order-form-row #cat-select{min-width:100%}
  .order-grid{grid-template-columns:1fr}
}
</style>

<!-- Order type tabs -->
<nav class="order-tabs" aria-label="Order type">
  <a class="order-tab active" href="<?= h(path('index.php')) ?>">New Order</a>
  <a class="order-tab" href="<?= h(path('mass-order.php')) ?>">Mass Order</a>
  <a class="order-tab" href="<?= h(path('services.php')) ?>">Services</a>
</nav>

<!-- Search & filter toolbar -->
<div class="order-toolbar">
  <form method="GET" style="display:flex;gap:8px;flex:1;min-width:200px;max-width:380px;" role="search" aria-label="Search services">
    <?php if ($selectedCat): ?><input type="hidden" name="cat" value="<?= h($selectedCat) ?>"><?php endif; ?>
    <label for="search-service" class="sr-only">Search services</label>
    <input type="search" id="search-service" name="q" value="<?= h($searchQ) ?>" class="form-control" placeholder="Search services…" style="flex:1;" autocomplete="off">
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
  <label for="cat-select" class="sr-only">Category</label>
  <select id="cat-select" class="form-control" style="width:auto;min-width:200px;" onchange="location.href='<?= h(path('index.php')) ?>'+(this.value ? '?cat='+encodeURIComponent(this.value) : '')">
    <option value="" <?= $selectedCat === '' ? 'selected' : '' ?>>All categories</option>
    <?php foreach ($categories as $c): ?>
    <option value="<?= h($c['category']) ?>" <?= $c['category'] === $selectedCat ? 'selected' : '' ?>><?= h($c['category']) ?></option>
    <?php endforeach; ?>
  </select>
  <label class="checkbox-label" title="Show only services added in the last 7 days">
    <input type="checkbox" id="newOnlyCheck" onchange="filterNew()"> New added <span class="checkbox-hint">(7 days)</span>
  </label>
</div>

<!-- Category quick filter: labeled pills (name + count), scrollable -->
<p class="order-section-label">Category</p>
<div class="order-cat-pills">
  <a class="order-cat-pill <?= $selectedCat === '' ? 'active' : '' ?>" href="<?= h(path('index.php')) ?><?= $searchQ ? '?q=' . urlencode($searchQ) : '' ?>" title="All — <?= (int)$totalServicesCount ?> services">
    <span class="order-cat-name">All</span>
    <span class="order-cat-count" aria-hidden="true"><?= (int)$totalServicesCount ?></span>
  </a>
  <?php foreach ($categories as $cat):
    $pKey = platformKeyFromCategory($cat['category']);
    $isActive = $selectedCat !== '' && $cat['category'] === $selectedCat;
    $cnt = $countByCategory[$cat['category']] ?? 0;
  ?>
  <a class="order-cat-pill <?= $isActive ? 'active' : '' ?>" href="<?= h(path('index.php')) ?>?cat=<?= urlencode($cat['category']) ?><?= $searchQ ? '&q=' . urlencode($searchQ) : '' ?>" title="<?= h($cat['category']) ?> — <?= $cnt ?> services">
    <?php $catIcon = platformSvg($pKey, 18); if ($catIcon !== '') echo $catIcon; ?>
    <span class="order-cat-name"><?= h($cat['category']) ?></span>
    <span class="order-cat-count" aria-hidden="true"><?= $cnt ?></span>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<?php if ($showAllLimitHint): ?>
<div class="alert alert-info" style="margin-bottom:12px;">Showing first 1,500 services. Select a category above to narrow down.</div>
<?php endif; ?>
<?php if (!$hasServices): ?>
<div class="alert alert-warning">No services match your filter. Try another category or clear the search. <a href="<?= h(path('index.php')) ?>">Show all categories</a></div>
<?php endif; ?>

<div class="order-grid">
  <div class="card">
    <div class="card-title">New Order</div>
    <form method="POST" action="<?= h(path('index.php')) ?>" id="order-form">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

      <div class="form-group">
        <label class="form-label" for="service-select">Service</label>
        <select name="service_id" id="service-select" class="form-control" onchange="updateDesc()" required aria-required="true" <?= !$hasServices ? 'disabled' : '' ?>>
          <option value="">— Select a service —</option>
          <?php foreach ($services as $s):
            $updatedAt = isset($s['updated_at']) ? strtotime($s['updated_at']) : 0;
          ?>
          <option value="<?= $s['service_id'] ?>"
            data-rate="<?= $s['rate'] ?>"
            data-min="<?= $s['min'] ?>"
            data-max="<?= $s['max'] ?>"
            data-refill="<?= $s['refill'] ? 'Yes' : 'No' ?>"
            data-markup="<?= $s['markup'] ?>"
            data-updated="<?= $updatedAt ?>"
            data-category="<?= h($s['category']) ?>">
            ID-<?= $s['service_id'] ?> | <?= h(mb_substr($s['name'], 0, 90)) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="order-link">Link</label>
        <input type="url" name="link" id="order-link" class="form-control" placeholder="https://..." required aria-required="true">
      </div>

      <div class="form-group">
        <label class="form-label" for="order-qty">Quantity</label>
        <input type="number" name="quantity" id="order-qty" class="form-control"
               placeholder="Enter quantity" oninput="calcPrice()" required min="1" aria-describedby="qty-hint">
        <div style="text-align:right;font-size:11px;color:var(--text-muted);margin-top:4px;" id="qty-hint" aria-live="polite">—</div>
      </div>

      <div class="form-group">
        <label class="checkbox-label">
          <input type="checkbox" name="drip_feed" value="1"> Drip-feed
        </label>
      </div>

      <div class="price-box">
        <div>
          <div class="lbl">Charge</div>
          <div class="amt" id="price-display">$0.0000</div>
        </div>
        <div style="text-align:right">
          <div class="lbl">Rate / 1000</div>
          <div style="font-size:14px;font-weight:700" id="rate-display">—</div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block" <?= !$hasServices ? 'disabled' : '' ?>>Submit</button>
    </form>
  </div>

  <!-- Description panel -->
  <div class="card" id="desc-panel">
    <div class="card-title">Description</div>
    <div id="desc-content" style="color:var(--text-muted);font-size:13px;">Select a service to see details.</div>
  </div>
</div>

<script>
(function(){
  var preselectServiceId = <?= $preselectServiceId ? (int)$preselectServiceId : 0 ?>;
  if (preselectServiceId) {
    var sel = document.getElementById('service-select');
    if (sel && !sel.disabled && sel.querySelector('option[value="' + preselectServiceId + '"]')) {
      sel.value = preselectServiceId;
      if (typeof updateDesc === 'function') updateDesc();
    }
  }
})();

function updateDesc() {
  var sel = document.getElementById('service-select');
  var descEl = document.getElementById('desc-content');
  var rateEl = document.getElementById('rate-display');
  var qtyHint = document.getElementById('qty-hint');
  var orderQty = document.getElementById('order-qty');
  if (!sel || !descEl || !rateEl || !qtyHint || !orderQty || sel.disabled) return;
  var opt = sel.options[sel.selectedIndex];
  if (!opt || !opt.value) {
    descEl.textContent = 'Select a service to see details.';
    rateEl.textContent = '—';
    qtyHint.textContent = '—';
    return;
  }
  var rate   = parseFloat(opt.dataset.rate) || 0;
  var min    = parseInt(opt.dataset.min, 10) || 0;
  var max    = parseInt(opt.dataset.max, 10) || 0;
  var markup = parseFloat(opt.dataset.markup) || 0;
  var markup_rate = rate * (1 + markup/100);
  var refill = opt.dataset.refill || 'No';
  rateEl.textContent = '$' + markup_rate.toFixed(5);
  qtyHint.textContent = 'Min: ' + min.toLocaleString() + ' — Max: ' + max.toLocaleString();
  orderQty.min = min;
  orderQty.max = max;
  orderQty.placeholder = 'Min: ' + min.toLocaleString() + ' — Max: ' + max.toLocaleString();
  var cat = (opt.dataset.category || '').toLowerCase();
  var exampleLinks = cat.indexOf('youtube') !== -1
    ? 'https://www.youtube.com/watch?v=xxxxxxx\nhttps://youtu.be/xxxxxx'
    : cat.indexOf('instagram') !== -1
    ? 'https://www.instagram.com/username/\nhttps://www.instagram.com/p/xxxxx/'
    : 'https://example.com/your-link';
  descEl.innerHTML = '<div class="desc-item"><strong>Quality:</strong> High Quality</div>' +
    '<div class="desc-item"><strong>Start:</strong> 0-6 Hours</div>' +
    '<div class="desc-item"><strong>Speed:</strong> Up to service limit</div>' +
    '<div class="desc-item"><strong>Refill:</strong> ' + (refill === 'Yes' ? 'Yes' : 'No') + '</div>' +
    '<div class="desc-item"><strong>Min:</strong> ' + min.toLocaleString() + ' — <strong>Max:</strong> ' + max.toLocaleString() + '</div>' +
    '<div class="desc-item"><strong>Rate per 1000:</strong> $' + markup_rate.toFixed(5) + '</div>' +
    '<div style="margin-top:12px;"><strong style="color:var(--text);">Example links:</strong><pre style="font-size:11px;background:var(--bg);padding:10px;border-radius:8px;margin-top:6px;white-space:pre-wrap;word-break:break-all;">' + exampleLinks.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</pre></div>' +
    '<div class="desc-item" style="margin-top:8px;">Possible engagements (likes, comments, subscribers) depend on the service.</div>' +
    '<ul class="desc-notes" style="list-style:none;padding:0;margin:0;">' +
    '<li><span class="asterisk">*</span> Content must be PUBLIC and open for all countries.</li>' +
    '<li><span class="asterisk">*</span> Check the link format before placing the order.</li>' +
    '<li><span class="asterisk">*</span> Do not change your post link, username, or account setting after ordering.</li>' +
    '<li><span class="asterisk">*</span> If you set account to private or delete it, the order may be marked completed without refund.</li></ul>';
  calcPrice();
}

function filterNew() {
  var check = document.getElementById('newOnlyCheck');
  var sel = document.getElementById('service-select');
  if (!check || !sel || sel.disabled) return;
  var cutoff = (Date.now()/1000) - (7*24*60*60);
  for (var i = 1; i < sel.options.length; i++) {
    var opt = sel.options[i];
    var updated = parseInt(opt.dataset.updated || 0, 10);
    opt.style.display = check.checked && updated < cutoff ? 'none' : '';
  }
}

function calcPrice() {
  var sel = document.getElementById('service-select');
  var priceEl = document.getElementById('price-display');
  var orderQty = document.getElementById('order-qty');
  if (!sel || !priceEl || !orderQty || sel.disabled) return;
  var opt = sel.options[sel.selectedIndex];
  if (!opt || !opt.value) { priceEl.textContent = '$0.0000'; return; }
  var rate   = parseFloat(opt.dataset.rate) || 0;
  var markup = parseFloat(opt.dataset.markup) || 0;
  var markup_rate = rate * (1 + markup/100);
  var qty    = parseFloat(orderQty.value, 10) || 0;
  var price  = (qty / 1000) * markup_rate;
  priceEl.textContent = '$' + price.toFixed(4);
}
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
