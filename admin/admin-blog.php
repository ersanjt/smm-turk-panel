<?php
/**
 * Admin: Blog — list articles, categories, tags. Add category/tag.
 */
require_once __DIR__ . '/../app/init.php';
$auth->requireAdmin();
$pageTitle = 'Blog';
$pageDescription = 'Manage blog articles, categories, and tags';
$db = Database::getInstance();

// POST: add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim((string)($_POST['cat_name'] ?? ''));
    $slug = trim(preg_replace('/[^a-z0-9\-]/', '-', strtolower((string)($_POST['cat_slug'] ?? ''))));
    if ($name !== '' && $slug !== '') {
        $db->insert("INSERT INTO blog_categories (slug, name, meta_description) VALUES (?, ?, ?)", [$slug, $name, trim((string)($_POST['cat_meta'] ?? ''))]);
        setFlash('Category added.', 'success');
    }
    header('Location: ' . url('admin/admin-blog.php'));
    exit;
}

// POST: add tag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tag'])) {
    $name = trim((string)($_POST['tag_name'] ?? ''));
    $slug = trim(preg_replace('/[^a-z0-9\-]/', '-', strtolower((string)($_POST['tag_slug'] ?? ''))));
    if ($name !== '' && $slug !== '') {
        $db->insert("INSERT INTO blog_tags (slug, name) VALUES (?, ?)", [$slug, $name]);
        setFlash('Tag added.', 'success');
    }
    header('Location: ' . url('admin/admin-blog.php'));
    exit;
}

// GET: delete category/tag (optional)
if (isset($_GET['delete_cat']) && ctype_digit((string)$_GET['delete_cat'])) {
    $db->execute("DELETE FROM blog_categories WHERE id = ?", [(int)$_GET['delete_cat']]);
    setFlash('Category deleted.', 'success');
    header('Location: ' . url('admin/admin-blog.php'));
    exit;
}
if (isset($_GET['delete_tag']) && ctype_digit((string)$_GET['delete_tag'])) {
    $db->execute("DELETE FROM blog_tags WHERE id = ?", [(int)$_GET['delete_tag']]);
    setFlash('Tag deleted.', 'success');
    header('Location: ' . url('admin/admin-blog.php'));
    exit;
}

$articles = $db->fetchAll("SELECT a.id, a.slug, a.title, a.status, a.published_at, c.name AS category_name FROM blog_articles a LEFT JOIN blog_categories c ON c.id = a.category_id ORDER BY a.updated_at DESC");
$categories = $db->fetchAll("SELECT id, slug, name FROM blog_categories ORDER BY name");
$tags = $db->fetchAll("SELECT id, slug, name FROM blog_tags ORDER BY name");

require_once __DIR__ . '/../layouts/header.php';
?>
<div class="card">
  <h2 class="card-title">Blog — Articles</h2>
  <p><a href="<?= h(path('admin/admin-blog-edit.php')) ?>" class="btn btn-primary">+ New Article</a> &nbsp; <a href="<?= h(path('blog.php')) ?>" target="_blank" class="btn" style="background:var(--border);">View Blog</a></p>
  <?php if (empty($articles)): ?>
  <p>No articles yet.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($articles as $a): ?>
      <tr>
        <td><strong><?= h($a['title']) ?></strong><br><small><?= h($a['slug']) ?></small></td>
        <td><?= h($a['category_name'] ?? '—') ?></td>
        <td><span class="badge badge-<?= $a['status'] === 'published' ? 'green' : 'gray' ?>"><?= h($a['status']) ?></span></td>
        <td><?= $a['published_at'] ? date('Y-m-d', strtotime($a['published_at'])) : '—' ?></td>
        <td><a href="<?= h(path('admin/admin-blog-edit.php') . '?id=' . $a['id']) ?>">Edit</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="grid2" style="margin-top:24px;">
  <div class="card">
    <h2 class="card-title">Categories</h2>
    <form method="post" style="margin-bottom:16px;">
      <input type="hidden" name="add_category" value="1">
      <div class="form-group">
        <label class="form-label">Name</label>
        <input type="text" name="cat_name" class="form-control" required placeholder="e.g. SMM Tips">
      </div>
      <div class="form-group">
        <label class="form-label">Slug</label>
        <input type="text" name="cat_slug" class="form-control" placeholder="smm-tips">
      </div>
      <div class="form-group">
        <label class="form-label">Meta description (optional)</label>
        <input type="text" name="cat_meta" class="form-control" placeholder="SEO description">
      </div>
      <button type="submit" class="btn btn-primary">Add Category</button>
    </form>
    <ul style="list-style:none;padding:0;">
      <?php foreach ($categories as $c): ?>
      <li style="padding:8px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;">
        <span><?= h($c['name']) ?> <code><?= h($c['slug']) ?></code></span>
        <a href="?delete_cat=<?= (int)$c['id'] ?>" onclick="return confirm('Delete this category?');" style="color:var(--red);">Delete</a>
      </li>
      <?php endforeach; ?>
      <?php if (empty($categories)): ?><li>No categories.</li><?php endif; ?>
    </ul>
  </div>
  <div class="card">
    <h2 class="card-title">Tags</h2>
    <form method="post" style="margin-bottom:16px;">
      <input type="hidden" name="add_tag" value="1">
      <div class="form-group">
        <label class="form-label">Name</label>
        <input type="text" name="tag_name" class="form-control" required placeholder="e.g. Instagram">
      </div>
      <div class="form-group">
        <label class="form-label">Slug</label>
        <input type="text" name="tag_slug" class="form-control" placeholder="instagram">
      </div>
      <button type="submit" class="btn btn-primary">Add Tag</button>
    </form>
    <ul style="list-style:none;padding:0;">
      <?php foreach ($tags as $t): ?>
      <li style="padding:8px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;">
        <span><?= h($t['name']) ?> <code><?= h($t['slug']) ?></code></span>
        <a href="?delete_tag=<?= (int)$t['id'] ?>" onclick="return confirm('Delete this tag?');" style="color:var(--red);">Delete</a>
      </li>
      <?php endforeach; ?>
      <?php if (empty($tags)): ?><li>No tags.</li><?php endif; ?>
    </ul>
  </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
