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
</div><!-- end main -->

<script>
(function(){
  var btn = document.getElementById('menuToggle');
  var overlay = document.getElementById('sidebarOverlay');
  var sidebar = document.getElementById('sidebar');
  if (btn && overlay) {
    function open() { document.body.classList.add('sidebar-open'); overlay.setAttribute('aria-hidden','false'); }
    function close() { document.body.classList.remove('sidebar-open'); overlay.setAttribute('aria-hidden','true'); }
    function toggle() { document.body.classList.contains('sidebar-open') ? close() : open(); }
    btn.addEventListener('click', toggle);
    overlay.addEventListener('click', close);
    if (sidebar) sidebar.querySelectorAll('.nav-item').forEach(function(el){ el.addEventListener('click', function(){ if (window.innerWidth <= 768) close(); }); });
    window.addEventListener('resize', function(){ if (window.innerWidth > 768) close(); });
  }
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
