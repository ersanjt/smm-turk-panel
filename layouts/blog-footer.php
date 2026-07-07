<footer class="blog-footer" role="contentinfo">
    <div class="blog-footer-inner">
        <div>
            <div class="blog-footer-brand"><?= site_name_logo_html() ?></div>
            <p><?= h($siteName ?? 'SMM Turk') ?> — cheapest SMM panel for Instagram, YouTube, TikTok growth. Crypto deposits, reseller API, 24/7 support.</p>
        </div>
        <div>
            <h4>Explore</h4>
            <div class="blog-footer-links">
                <a href="<?= h(home_path()) ?>"><?= function_exists('__') ? h(__('blog_nav_home')) : 'Home' ?></a>
                <a href="<?= h(path('blog.php')) ?>"><?= function_exists('__') ? h(__('blog_nav_blog')) : 'Blog' ?></a>
                <a href="<?= h(path('help.php')) ?>"><?= function_exists('__') ? h(__('help_nav')) : 'Help' ?></a>
                <a href="<?= h(login_next_path('services.php')) ?>">Services</a>
            </div>
        </div>
        <div>
            <h4>Panel</h4>
            <div class="blog-footer-links">
                <a href="<?= h(register_path()) ?>"><?= function_exists('__') ? h(__('nav_sign_up')) : 'Sign Up' ?></a>
                <a href="<?= h(login_next_path('add-funds.php')) ?>">Add Funds</a>
                <?php if (!function_exists('is_child_panel') || !is_child_panel()): ?>
                <a href="<?= h(login_next_path('api-page.php')) ?>">API</a>
                <?php endif; ?>
                <a href="<?= h(path('terms.php')) ?>"><?= function_exists('__') ? h(__('nav_terms')) : 'Terms' ?></a>
            </div>
        </div>
    </div>
    <div class="blog-footer-bottom">© <?= date('Y') ?> <?= h($siteName ?? 'SMM Turk') ?>. All rights reserved.</div>
</footer>
</body>
</html>
