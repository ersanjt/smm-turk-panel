</main>
<footer class="blog-footer" role="contentinfo">
    <a href="<?= h(path('home.php')) ?>"><?= h($siteName ?? 'SMM Turk') ?></a> —
    <a href="<?= h(path('blog.php')) ?>">Blog</a> —
    <a href="<?= h(path('terms.php')) ?>"><?= function_exists('__') ? h(__('nav_terms')) : 'Terms' ?></a> —
    <a href="<?= h(path('api-page.php')) ?>">API</a>
    <br><br>© <?= date('Y') ?> <?= h($siteName ?? 'SMM Turk') ?>.
</footer>
</body>
</html>
