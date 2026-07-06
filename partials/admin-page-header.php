<?php
/**
 * Admin sub-page header with back link.
 * Requires: $pageTitle. Optional: $pageSubtitle, $backUrl, $backLabel
 */
$backUrl = $backUrl ?? admin_path('index.php');
$backLabel = $backLabel ?? 'Admin Panel';
?>
<div class="page-header">
  <div>
    <a href="<?= h($backUrl) ?>" class="admin-back-link" aria-label="<?= h($backLabel) ?>">← <?= h($backLabel) ?></a>
    <h1 class="page-title"><?= h($pageTitle) ?></h1>
    <?php if (!empty($pageSubtitle)): ?>
    <p class="page-subtitle"><?= h($pageSubtitle) ?></p>
    <?php endif; ?>
  </div>
</div>
