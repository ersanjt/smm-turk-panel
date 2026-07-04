(function () {
  var themeKey = 'smmturk_theme';
  var dark = localStorage.getItem(themeKey) === 'dark'
    || (!localStorage.getItem(themeKey) && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
  if (dark) document.body.classList.add('theme-dark');

  var btn = document.getElementById('themeToggle');
  if (btn) {
    btn.addEventListener('click', function () {
      document.body.classList.toggle('theme-dark');
      localStorage.setItem(themeKey, document.body.classList.contains('theme-dark') ? 'dark' : 'light');
    });
  }

  var langBtn = document.getElementById('langBtn');
  var langDropdown = document.getElementById('langDropdown');
  if (langBtn && langDropdown) {
    langBtn.addEventListener('click', function () {
      langDropdown.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.nav-lang')) langDropdown.classList.remove('open');
    });
  }

  document.querySelectorAll('.faq-q').forEach(function (el) {
    el.addEventListener('click', function () {
      var item = this.closest('.faq-item');
      var open = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(function (i) { i.classList.remove('open'); });
      if (!open) item.classList.add('open');
    });
  });
})();
