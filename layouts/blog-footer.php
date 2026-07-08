<?php
$footerSiteName = $siteName ?? (function_exists('site_name') ? site_name() : 'SMM Turk');
$footerIsChild = function_exists('is_child_panel') && is_child_panel();
?>
<nav class="mob-footer-bar" aria-label="<?= h(__('footer_login')) ?>">
    <a href="<?= h(route_path('login.php')) ?>" class="mob-footer-btn mob-footer-btn-outline"><?= h(__('footer_login')) ?></a>
    <a href="<?= h(register_path()) ?>" class="mob-footer-btn mob-footer-btn-primary"><?= h(__('footer_signup')) ?> →</a>
</nav>

<footer class="footer" role="contentinfo">
    <p class="footer-nav-label" aria-hidden="true"><?= h(__('footer_quick_links') ?: 'Quick links') ?></p>
    <div class="footer-links">
        <a href="<?= h(route_path('login.php')) ?>"><?= h(__('footer_login')) ?></a>
        <a href="<?= h(register_path()) ?>"><?= h(__('footer_signup')) ?></a>
        <a href="<?= h(path('blog.php')) ?>"><?= h(__('blog_nav_blog')) ?></a>
        <a href="<?= h(path('help.php')) ?>"><?= h(__('help_nav')) ?></a>
        <a href="<?= h(path('terms.php')) ?>"><?= h(__('nav_terms')) ?></a>
        <?php if (!$footerIsChild): ?>
        <a href="<?= h(login_next_path('api-page.php')) ?>"><?= h(__('footer_api')) ?></a>
        <?php endif; ?>
        <a href="<?= h(path('help.php')) ?>"><?= h(__('footer_support')) ?></a>
    </div>
    <div class="footer-copy">© <?= date('Y') ?> <?= h($footerSiteName) ?>. <?= h(__('footer_copyright')) ?>.</div>
</footer>

<script src="<?= h(asset_url('assets/js/landing.js')) ?>" defer></script>
<script src="<?= h(asset_url('assets/js/pwa.js')) ?>" defer></script>
</body>
</html>
