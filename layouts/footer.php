  </div><!-- end content -->
  <footer class="footer">
    <div class="footer-inner">
      <span class="footer-copy">© <?= date('Y') ?> <?= h(defined('SITE_NAME') ? SITE_NAME : 'SMM Turk') ?>. All Rights Reserved.</span>
      <?php
      $contactEmail = class_exists('Database') ? (Database::getInstance()->getSetting('contact_email') ?: '') : '';
      if ($contactEmail): ?>
      <a href="mailto:<?= h($contactEmail) ?>" class="footer-contact"><?= h($contactEmail) ?></a>
      <?php endif; ?>
      <a href="/tickets.php" class="footer-ticket">Open a ticket</a>
    </div>
  </footer>
  <!-- Mobile bottom navigation (visible only on small screens) -->
  <nav class="mob-bottom-nav" aria-label="Main navigation">
    <a href="/index.php" class="<?= ($currentPage ?? '') === 'index' ? 'active' : '' ?>"><span class="mob-nav-icon">➕</span><span>Order</span></a>
    <a href="/orders.php" class="<?= ($currentPage ?? '') === 'orders' ? 'active' : '' ?>"><span class="mob-nav-icon">📋</span><span>Orders</span></a>
    <a href="/add-funds.php" class="<?= ($currentPage ?? '') === 'add-funds' ? 'active' : '' ?>"><span class="mob-nav-icon">💳</span><span>Funds</span></a>
    <a href="/tickets.php" class="<?= ($currentPage ?? '') === 'tickets' ? 'active' : '' ?>"><span class="mob-nav-icon">🎫</span><span>Tickets</span></a>
    <button type="button" id="mobNavMenuBtn" aria-label="Open menu"><span class="mob-nav-icon">☰</span><span>Menu</span></button>
  </nav>
</div><!-- end main -->

<script>
(function(){
  var btn = document.getElementById('menuToggle');
  var mobMenuBtn = document.getElementById('mobNavMenuBtn');
  var overlay = document.getElementById('sidebarOverlay');
  var sidebar = document.getElementById('sidebar');
  function openSidebar() { document.body.classList.add('sidebar-open'); if (overlay) overlay.setAttribute('aria-hidden','false'); }
  function closeSidebar() { document.body.classList.remove('sidebar-open'); if (overlay) overlay.setAttribute('aria-hidden','true'); }
  function toggleSidebar() { document.body.classList.contains('sidebar-open') ? closeSidebar() : openSidebar(); }
  if (btn) btn.addEventListener('click', toggleSidebar);
  if (mobMenuBtn) mobMenuBtn.addEventListener('click', openSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);
  if (sidebar) sidebar.querySelectorAll('.nav-item').forEach(function(el){ el.addEventListener('click', function(){ if (window.innerWidth <= 768) closeSidebar(); }); });
  window.addEventListener('resize', function(){ if (window.innerWidth > 768) closeSidebar(); });
  // Scroll reveal (respects prefers-reduced-motion)
  var reveal = document.querySelectorAll('[data-reveal]');
  if (reveal.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var io = new IntersectionObserver(function(entries){ entries.forEach(function(e){ if (e.isIntersecting) e.target.classList.add('revealed'); }); }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    reveal.forEach(function(el){ io.observe(el); });
  }
})();
</script>
</body>
</html>
