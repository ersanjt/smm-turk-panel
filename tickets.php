<?php
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'Tickets';
$db  = Database::getInstance();
$uid = $auth->getUserId();

$categories = ['Order', 'Payments', 'Invoice', 'Child Panel', 'API', 'BUG', 'Redeem', 'Request', 'Other'];
$orderSubs  = ['Refill', 'Cancelation', 'Speed Up'];

$uploadDir = __DIR__ . '/uploads/tickets';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Handle new ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket']) && csrf_verify()) {
    $category    = trim($_POST['category'] ?? 'Other');
    $subcategory = trim($_POST['subcategory'] ?? '');
    $orderId     = trim($_POST['order_id'] ?? '');
    $message     = trim($_POST['message'] ?? '');

    if (!$message) {
        flash('error', 'Please enter your message.');
        redirect(url('tickets.php'));
    }

    $subject = $category . ($subcategory ? ' - ' . $subcategory : '');
    if ($orderId) $subject .= ' [Order: ' . mb_substr($orderId, 0, 100) . ']';

    $tid = $db->insert("INSERT INTO tickets (user_id, subject, status) VALUES (?, ?, 'open')", [$uid, $subject]);
    try {
        $db->execute("UPDATE tickets SET category = ?, subcategory = ?, order_id = ? WHERE id = ?", [$category, $subcategory, mb_substr($orderId, 0, 500), $tid]);
    } catch (Throwable $e) {
        /* optional columns may not exist */
    }

    $db->insert("INSERT INTO ticket_replies (ticket_id, user_id, message, is_staff) VALUES (?, ?, ?, 0)", [$tid, $uid, $message]);

    // Attachments (extension allowlist + MIME validation)
    if (!empty($_FILES['attachments']['name'][0]) && is_dir($uploadDir)) {
        $ticketDir = $uploadDir . '/' . (int)$tid;
        @mkdir($ticketDir, 0755, true);
        $allowedExt = ['jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'gif' => ['image/gif'], 'pdf' => ['application/pdf'], 'txt' => ['text/plain'], 'doc' => ['application/msword'], 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document']];
        $maxSize = 5 * 1024 * 1024; // 5MB
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        foreach ($_FILES['attachments']['name'] as $i => $name) {
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK || $_FILES['attachments']['size'][$i] > $maxSize) continue;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!isset($allowedExt[$ext])) continue;
            $tmpPath = $_FILES['attachments']['tmp_name'][$i];
            $detectedMime = $finfo ? finfo_file($finfo, $tmpPath) : '';
            if (!in_array($detectedMime, $allowedExt[$ext], true)) continue;
            $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
            $path = $ticketDir . '/' . uniqid() . '_' . $safe;
            if (move_uploaded_file($tmpPath, $path)) {
                $rel = 'uploads/tickets/' . (int)$tid . '/' . basename($path);
                try {
                    $db->insert("INSERT INTO ticket_attachments (ticket_id, reply_id, file_path, original_name) VALUES (?, NULL, ?, ?)", [$tid, $rel, $name]);
                } catch (Throwable $e) {
                    // table might not exist
                }
            }
        }
        if ($finfo) finfo_close($finfo);
    }

    flash('success', 'Ticket #' . $tid . ' created. We will reply as soon as possible.');
    redirect(url('ticket.php') . '?id=' . (int)$tid);
}

// List: search and pagination
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "user_id = ?";
$params = [$uid];
if ($search !== '') {
    $where .= " AND (subject LIKE ? OR order_id LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$total = (int) $db->fetch("SELECT COUNT(*) c FROM tickets WHERE $where", $params)['c'];
$tickets = $db->fetchAll(
    "SELECT id, subject, status, updated_at FROM tickets WHERE $where ORDER BY updated_at DESC LIMIT $perPage OFFSET $offset",
    $params
);

$totalPages = max(1, (int)ceil($total / $perPage));

require_once __DIR__ . '/layouts/header.php';
?>

<style>
.ticket-banner { background: #fff3e0; color: #b45309; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; border: 1px solid #fcd34d; }
.ticket-banner a { color: var(--primary); font-weight: 600; }
.ticket-cats { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
.ticket-cats input { display: none; }
.ticket-cats label { display: inline-block; padding: 8px 16px; border-radius: 10px; border: 2px solid var(--border); font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s; }
.ticket-cats label:hover { border-color: var(--primary); }
.ticket-cats input:checked + label { border-color: var(--primary); background: #fff8f9; color: var(--primary); }
.ticket-subcats { display: none; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
.ticket-subcats.visible { display: flex; }
.ticket-history-card { background: #fff; border-radius: 14px; border: 1px solid var(--border); overflow: hidden; }
.ticket-search-wrap { display: flex; gap: 8px; padding: 14px; border-bottom: 1px solid var(--border); }
.ticket-search-wrap input { flex: 1; padding: 10px 14px; border: 1px solid var(--border); border-radius: 10px; }
.ticket-search-wrap button { padding: 10px 18px; background: var(--primary); color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
.ticket-item { display: block; padding: 14px 18px; border-bottom: 1px solid var(--border); color: inherit; text-decoration: none; transition: background .1s; }
.ticket-item:hover { background: #fff8f9; }
.ticket-item:last-child { border-bottom: 0; }
.ticket-item-id { font-weight: 700; color: var(--text); }
.ticket-item-meta { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.ticket-item-status { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; margin-left: 8px; }
.ticket-item-status.new, .ticket-item-status.open { background: #e0f2fe; color: #0369a1; }
.ticket-item-status.answered { background: #dcfce7; color: #166534; }
.ticket-item-status.closed { background: #f3f4f6; color: #6b7280; }
.ticket-pagination { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px; flex-wrap: wrap; }
.ticket-pagination a, .ticket-pagination span { padding: 8px 12px; border-radius: 8px; font-size: 13px; text-decoration: none; color: var(--text); border: 1px solid var(--border); }
.ticket-pagination a:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
.ticket-pagination .current { background: var(--primary); color: #fff; border-color: var(--primary); }
.attach-hint { font-size: 12px; color: var(--text-muted); margin-top: 6px; }
</style>

<div class="ticket-banner">
  To get a quicker response to your inquiries or issues, please refer to our <a href="<?= h(path('home.php')) ?>#faq">FAQs page</a> before submitting a ticket.
</div>

<div class="grid2" style="align-items: start; gap: 24px;">
  <div class="card">
    <div class="card-title">New ticket</div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="submit_ticket" value="1">

      <div class="form-group">
        <label class="form-label">Category</label>
        <div class="ticket-cats">
          <?php foreach ($categories as $i => $cat): ?>
          <input type="radio" name="category" id="cat_<?= $i ?>" value="<?= h($cat) ?>" <?= $i === 0 ? 'checked' : '' ?>>
          <label for="cat_<?= $i ?>"><?= h($cat) ?></label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group ticket-subcats visible" id="orderSubcats">
        <label class="form-label">Type (for Order)</label>
        <div class="ticket-cats">
          <?php foreach ($orderSubs as $j => $sub): ?>
          <input type="radio" name="subcategory" id="sub_<?= $j ?>" value="<?= h($sub) ?>" <?= $j === 0 ? 'checked' : '' ?>>
          <label for="sub_<?= $j ?>"><?= h($sub) ?></label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Order ID</label>
        <input type="text" name="order_id" class="form-control" placeholder="ex. 2741705, 2741707815, 274170390" maxlength="500">
        <div class="form-label" style="margin-top:4px;font-weight:400;color:var(--text-muted);">(To receive a faster response, ensure that you assign the correct order IDs.)</div>
      </div>

      <div class="form-group">
        <label class="form-label">Message</label>
        <textarea name="message" class="form-control" rows="6" placeholder="ex. Hi there, I would like to check my second order status." required></textarea>
        <div class="form-label" style="margin-top:4px;font-weight:400;color:var(--text-muted);">How did you hear about our website? Please mention: Search engine, Social media, Referral, Forum, Other.</div>
      </div>

      <div class="form-group">
        <label class="form-label">Attach files</label>
        <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.doc,.docx" style="padding:8px;border:1px solid var(--border);border-radius:10px;width:100%;">
        <div class="attach-hint">Max 5MB per file. Allowed: images, PDF, TXT, DOC.</div>
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="padding:14px;">Submit ticket</button>
    </form>
  </div>

  <div class="ticket-history-card">
    <div class="card-title" style="margin:0;padding:18px 18px 0;">Tickets History</div>
    <form method="GET" class="ticket-search-wrap">
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search">
      <button type="submit">🔍 Search</button>
    </form>
    <?php if (empty($tickets)): ?>
    <div style="padding:40px 18px;text-align:center;color:var(--text-muted);">
      No tickets yet. Create one using the form on the left.
    </div>
    <?php else: ?>
    <?php foreach ($tickets as $t): ?>
    <a href="<?= h(path('ticket.php')) ?>?id=<?= (int)$t['id'] ?>" class="ticket-item">
      <span class="ticket-item-id"><?= (int)$t['id'] ?> — <?= h(mb_substr($t['subject'], 0, 60)) ?><?= mb_strlen($t['subject']) > 60 ? '…' : '' ?></span>
      <span class="ticket-item-meta"><?= h(date('Y-m-d h:i:s A', strtotime($t['updated_at'] ?? 'now'))) ?></span>
      <span class="ticket-item-status <?= ($t['status'] ?? 'open') === 'open' ? 'new' : h($t['status'] ?? 'open') ?>"><?= ($t['status'] ?? 'open') === 'open' ? 'New' : h(ucfirst($t['status'] ?? '')) ?></span>
    </a>
    <?php endforeach; ?>
    <div class="ticket-pagination">
      <?php if ($page > 1): ?>
      <a href="?p=1&q=<?= h(urlencode($search)) ?>">« First</a>
      <a href="?p=<?= $page - 1 ?>&q=<?= h(urlencode($search)) ?>">‹ Prev</a>
      <?php endif; ?>
      <span class="current"><?= $page ?></span>
      <?php if ($page < $totalPages): ?>
      <a href="?p=<?= $page + 1 ?>&q=<?= h(urlencode($search)) ?>">Next ›</a>
      <a href="?p=<?= $totalPages ?>&q=<?= h(urlencode($search)) ?>">Last »</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function(){
  var orderCat = document.querySelector('input[name="category"][value="Order"]');
  var subBlock = document.getElementById('orderSubcats');
  if (!orderCat || !subBlock) return;
  function toggle() {
    subBlock.classList.toggle('visible', orderCat.checked);
  }
  document.querySelectorAll('input[name="category"]').forEach(function(r){
    r.addEventListener('change', toggle);
  });
  toggle();
})();
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
