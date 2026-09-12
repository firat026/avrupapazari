(function () {
  var root = document.documentElement;

  // Theme (persisted in localStorage, applied early by the inline head script)
  var themeBtn = document.getElementById('themeToggle');
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('site_theme', next); } catch (e) {}
    });
  }

  // Dropdowns
  var dropdowns = document.querySelectorAll('[data-dropdown]');
  function closeDropdowns(except) {
    dropdowns.forEach(function (d) { if (d !== except) d.classList.remove('open'); });
  }
  dropdowns.forEach(function (d) {
    var toggle = d.querySelector('.dropdown-toggle');
    if (!toggle) return;
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var willOpen = !d.classList.contains('open');
      closeDropdowns(d);
      d.classList.toggle('open', willOpen);
    });
  });
  document.addEventListener('click', function () { closeDropdowns(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeDropdowns(); closeAuth(); } });

  // Auth modal
  var modal = document.getElementById('authModal');
  function openAuth(tab) {
    if (!modal) return;
    setTab(tab || 'register');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    var first = modal.querySelector('.auth-form.active input:not([type=hidden])');
    if (first) setTimeout(function () { first.focus(); }, 120);
  }
  function closeAuth() {
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }
  function setTab(tab) {
    modal.querySelectorAll('.auth-tab').forEach(function (b) { b.classList.toggle('active', b.dataset.authTab === tab); });
    modal.querySelectorAll('.auth-form').forEach(function (f) { f.classList.toggle('active', f.dataset.authForm === tab); });
  }
  document.querySelectorAll('[data-open-auth]').forEach(function (el) {
    el.addEventListener('click', function (e) { e.preventDefault(); openAuth(el.dataset.openAuth); });
  });
  if (modal) {
    modal.querySelectorAll('[data-close-auth]').forEach(function (b) { b.addEventListener('click', closeAuth); });
    modal.addEventListener('click', function (e) { if (e.target === modal) closeAuth(); });
    modal.querySelectorAll('.auth-tab').forEach(function (b) { b.addEventListener('click', function () { setTab(b.dataset.authTab); }); });
    modal.querySelectorAll('.auth-form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var err = form.querySelector('[data-auth-error]');
        var btn = form.querySelector('.auth-submit');
        err.hidden = true; btn.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.ok) { window.location.href = data.redirect || window.location.href; return; }
            err.textContent = data.error || 'Error'; err.hidden = false; btn.disabled = false;
          })
          .catch(function () { err.textContent = 'Network error'; err.hidden = false; btn.disabled = false; });
      });
    });
    var params = new URLSearchParams(window.location.search);
    if (params.get('auth') === 'login' || params.get('auth') === 'register') openAuth(params.get('auth'));
  }

  window.openAuth = openAuth;

  // Favorites (heart buttons on cards)
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-fav]');
    if (!btn) return;
    e.preventDefault(); e.stopPropagation();
    if (!window.SITE_USER) { openAuth('login'); return; }
    var base = document.querySelector('.site-header .brand').getAttribute('href').replace(/\/$/, '');
    btn.disabled = true;
    fetch(base + '/ajax/toggle-favorite.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'listing_id=' + encodeURIComponent(btn.dataset.fav) })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.status === 'login_required') { openAuth('login'); return; }
        var on = data.status === 'added';
        btn.classList.toggle('active', on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        var card = btn.closest('[data-fav-card]');
        if (card && !on) card.remove();
      })
      .catch(function () {})
      .then(function () { btn.disabled = false; });
  });

  // Scroll to top
  var top = document.getElementById('scrollTop');
  if (top) {
    window.addEventListener('scroll', function () { top.classList.toggle('show', window.scrollY > 400); }, { passive: true });
    top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  // Lucide icons used by legacy pages
  if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
  window.addEventListener('load', function () { if (window.lucide && window.lucide.createIcons) window.lucide.createIcons(); });
})();
