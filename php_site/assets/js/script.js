// Dark mode + Dil menüsü — sade, hafif
(function() {
  // ====== DARK MODE ======
  const KEY = 'ap_theme';
  const root = document.documentElement;
  const saved = localStorage.getItem(KEY);
  if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    root.setAttribute('data-theme', 'dark');
  }
  const themeBtn = document.getElementById('themeToggle');
  if (themeBtn) {
    themeBtn.addEventListener('click', () => {
      const isDark = root.getAttribute('data-theme') === 'dark';
      if (isDark) {
        root.removeAttribute('data-theme');
        localStorage.setItem(KEY, 'light');
      } else {
        root.setAttribute('data-theme', 'dark');
        localStorage.setItem(KEY, 'dark');
      }
    });
  }

  // ====== DİL MENÜSÜ ======
  const langBtn = document.getElementById('langBtn');
  const langSwitch = langBtn ? langBtn.closest('.lang-switch') : null;
  if (langBtn && langSwitch) {
    langBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      langSwitch.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
      if (!langSwitch.contains(e.target)) langSwitch.classList.remove('open');
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') langSwitch.classList.remove('open');
    });
  }
})();
