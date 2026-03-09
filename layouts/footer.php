  </div><!-- end content -->
  <div class="footer">
    © <?= date('Y') ?> <?= h(defined('SITE_NAME') ? SITE_NAME : 'SMM Turk') ?> — <?= defined('SITE_URL') ? parse_url(SITE_URL, PHP_URL_HOST) : 'smm-turk.com' ?>
  </div>
</div><!-- end main -->

<script>
(function(){
  var btn = document.getElementById('menuToggle');
  var overlay = document.getElementById('sidebarOverlay');
  var sidebar = document.getElementById('sidebar');
  if (!btn || !overlay) return;
  function open() { document.body.classList.add('sidebar-open'); overlay.setAttribute('aria-hidden','false'); }
  function close() { document.body.classList.remove('sidebar-open'); overlay.setAttribute('aria-hidden','true'); }
  function toggle() { document.body.classList.contains('sidebar-open') ? close() : open(); }
  btn.addEventListener('click', toggle);
  overlay.addEventListener('click', close);
  sidebar.querySelectorAll('.nav-item').forEach(function(el){ el.addEventListener('click', function(){ if (window.innerWidth <= 768) close(); }); });
  window.addEventListener('resize', function(){ if (window.innerWidth > 768) close(); });
})();
</script>
</body>
</html>
