<?php
// services.php – Services listing (SMMFollows-style layout)
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'Services';
$db = Database::getInstance();

$search = trim($_GET['q'] ?? '');
$cat    = $_GET['cat'] ?? '';
$sort   = $_GET['sort'] ?? 'id';
$dir    = strtolower($_GET['dir'] ?? 'asc');
if (!in_array($dir, ['asc', 'desc'])) $dir = 'asc';

$where  = "WHERE status='active'";
$params = [];
if ($search) { $where .= " AND name LIKE ?"; $params[] = "%$search%"; }
if ($cat)    { $where .= " AND category = ?"; $params[] = $cat; }

$orderBy = 'service_id ASC';
if ($sort === 'rate') $orderBy = 'rate ' . ($dir === 'desc' ? 'DESC' : 'ASC');
if ($sort === 'min')  $orderBy = 'min ' . ($dir === 'desc' ? 'DESC' : 'ASC');
if ($sort === 'max')  $orderBy = 'max ' . ($dir === 'desc' ? 'DESC' : 'ASC');
if ($sort === 'id')   $orderBy = 'service_id ' . ($dir === 'desc' ? 'DESC' : 'ASC');

$services   = $db->fetchAll("SELECT * FROM services $where ORDER BY $orderBy LIMIT 500", $params);
$categories = $db->fetchAll("SELECT DISTINCT category FROM services WHERE status='active' ORDER BY category");

// Platform icons (same as index)
$platformIcons = [
    'YouTube' => '▶', 'Instagram' => '📷', 'TikTok' => '🎵', 'Twitter' => '𝕏', 'Facebook' => 'f', 'LinkedIn' => 'in',
    'Telegram' => '✈', 'Spotify' => '♫', 'SoundCloud' => '🔊', 'Twitch' => '🎮', 'Discord' => '💬', 'Tumblr' => 't',
    'Reddit' => '🔴', 'Pinterest' => 'P', 'Vimeo' => 'V', 'VK' => 'VK', 'Dailymotion' => 'D', 'Apple Music' => '🎵',
    'Website Traffic' => '🌐', 'Mobile' => '📱', 'Kwai' => 'K', 'Deezer' => 'D', 'Clubhouse' => 'C', 'Shazam' => 'S',
    'Rumble' => 'R', 'Kick' => 'K', 'Medium' => 'M', 'BlueSky' => '🦋', 'Binance' => 'B', 'Default' => '+',
];
function platformIcon($category, $map) {
    foreach ($map as $key => $icon) {
        if (stripos($category, $key) !== false) return $icon;
    }
    return mb_substr($category, 0, 1);
}

$newCutoff = date('Y-m-d H:i:s', time() - 7*24*3600);
require_once __DIR__ . '/layouts/header.php';
?>

<style>
.services-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-bottom:18px}
.platform-row{display:flex;align-items:center;gap:8px;overflow-x:auto;padding:6px 0;flex:1;min-width:0;-webkit-overflow-scrolling:touch}
.platform-row::-webkit-scrollbar{height:6px}
.platform-chip{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;background:#fff;border:1.5px solid var(--border);font-size:12px;font-weight:600;white-space:nowrap;text-decoration:none;color:var(--text-muted);transition:all .2s;flex-shrink:0}
.platform-chip:hover,.platform-chip.active{background:var(--primary);border-color:var(--primary);color:#fff}
.services-search-row{display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
.services-search-row .form-control{max-width:320px}
.filter-select{min-width:180px}
.new-services-banner{background:linear-gradient(135deg, rgba(227,10,23,.12), rgba(227,10,23,.06));border:1px solid rgba(227,10,23,.2);border-radius:12px;padding:10px 16px;margin-bottom:12px;font-size:13px;font-weight:700;color:var(--primary);display:flex;align-items:center;gap:10px}
.svc-table{width:100%;border-collapse:collapse;font-size:13px}
.svc-table th{background:var(--bg);padding:12px 14px;text-align:left;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);border-bottom:1.5px solid var(--border);white-space:nowrap}
.svc-table th a{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
.svc-table th a:hover{color:var(--primary)}
.svc-table td{padding:12px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
.svc-table tr:hover td{background:#fff8f9}
.svc-id{display:flex;align-items:center;gap:8px}
.svc-star{color:var(--border);font-size:16px;cursor:pointer;text-decoration:none;transition:color .2s}
.svc-star:hover,.svc-star.fav{color:var(--orange)}
.svc-name{font-size:12px;line-height:1.5;max-width:420px;color:var(--text)}
.svc-name .svc-tag{display:inline-block;background:rgba(227,10,23,.1);color:var(--primary);font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;margin-right:6px;margin-bottom:4px}
.svc-rate{font-weight:700;color:var(--primary)}
.svc-min{background:#fff5f6;border:1px solid rgba(227,10,23,.25);border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;color:var(--text);min-width:70px;text-align:center}
.svc-max{background:#f5f4ff;border:1px solid rgba(99,102,241,.2);border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;color:var(--text);min-width:70px;text-align:center}
.svc-time{font-size:12px;color:var(--text-muted)}
.svc-order{padding:6px 14px;font-size:12px;border-radius:8px}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border-radius:12px;border:1px solid var(--border)}
</style>

<div class="services-toolbar">
  <div class="platform-row">
    <a class="platform-chip <?= !$cat ? 'active' : '' ?>" href="services.php">Everything</a>
    <?php foreach ($categories as $c):
      $icon = platformIcon($c['category'], $platformIcons);
    ?>
    <a class="platform-chip <?= $cat === $c['category'] ? 'active' : '' ?>" href="?cat=<?= urlencode($c['category']) ?>"><?= h($icon) ?> <?= h($c['category']) ?></a>
    <?php endforeach; ?>
  </div>
  <form method="GET" style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
    <?php if ($cat): ?><input type="hidden" name="cat" value="<?= h($cat) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= h($search) ?>" class="form-control" placeholder="Search" style="width:160px;">
    <select name="cat" class="form-control filter-select" onchange="this.form.submit()">
      <option value="">Filter By Category</option>
      <?php foreach ($categories as $c): ?>
      <option value="<?= h($c['category']) ?>" <?= $cat === $c['category'] ? 'selected' : '' ?>><?= h($c['category']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
</div>

<div class="services-search-row">
  <form method="GET" style="display:flex;gap:8px;">
    <?php if ($cat): ?><input type="hidden" name="cat" value="<?= h($cat) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= h($search) ?>" class="form-control" placeholder="Search services by name…">
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
</div>

<div class="card" style="padding:0;overflow:hidden;">
  <div class="table-wrap">
    <table class="svc-table">
      <thead>
        <tr>
          <?php
$q = $_GET;
$sortUrl = function($col) use ($q, $sort, $dir) {
  $q['sort'] = $col;
  $q['dir'] = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
  return '?' . http_build_query($q);
};
?>
          <th><a href="<?= $sortUrl('id') ?>">ID ▼</a></th>
          <th>Service</th>
          <th><a href="<?= $sortUrl('rate') ?>">Rate per 1000 ▼</a></th>
          <th><a href="<?= $sortUrl('min') ?>">Min order ▼</a></th>
          <th><a href="<?= $sortUrl('max') ?>">Max order ▼</a></th>
          <th title="Completion time varies by service">Average completion time <span style="opacity:.6;">?</span></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $displayedNew = false;
        foreach ($services as $s):
          $displayRate = $s['rate'] * (1 + $s['markup']/100);
          $isNew = isset($s['updated_at']) && $s['updated_at'] >= $newCutoff;
          if ($isNew && !$displayedNew) {
            echo '<tr><td colspan="7" class="new-services-banner" style="margin:0;border-radius:0;">🆕 New Added Services</td></tr>';
            $displayedNew = true;
          }
          $orderUrl = '/index.php?cat=' . urlencode($s['category']) . '&service=' . $s['service_id'];
        ?>
        <tr>
          <td>
            <div class="svc-id">
              <a href="<?= h($orderUrl) ?>" class="svc-star" title="Order this service">★</a>
              <strong><?= $s['service_id'] ?></strong>
            </div>
          </td>
          <td>
            <div class="svc-name">
              <span class="svc-tag"><?= h($s['category']) ?></span>
              <?= h(mb_substr($s['name'], 0, 200)) ?>
            </div>
          </td>
          <td class="svc-rate">$<?= number_format($displayRate, 2) ?></td>
          <td><span class="svc-min"><?= number_format($s['min']) ?></span></td>
          <td><span class="svc-max"><?= number_format($s['max']) ?></span></td>
          <td class="svc-time">—</td>
          <td><a href="<?= h($orderUrl) ?>" class="btn btn-primary svc-order">Order</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (empty($services)): ?>
<div class="card" style="text-align:center;padding:48px;">
  <p style="color:var(--text-muted);margin-bottom:12px;">No services match your filters.</p>
  <a href="services.php" class="btn btn-primary">Show all</a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
