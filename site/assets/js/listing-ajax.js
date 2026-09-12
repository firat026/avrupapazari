/* Listing pages: swap only the results column (.sh-main) on filter/sort/pagination - sidebar and header stay fixed. */
(function () {
  var main = document.querySelector('.sh-main');
  var layout = document.querySelector('.sh-layout');
  if (!main || !layout) return;
  var busy = false;

  function samePage(url) {
    try {
      var u = new URL(url, window.location.href);
      return u.origin === window.location.origin && u.pathname === window.location.pathname;
    } catch (e) { return false; }
  }

  function load(url, push) {
    if (busy) return;
    busy = true;
    main.classList.add('is-loading');
    fetch(url, { headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var fresh = doc.querySelector('.sh-main');
        var sidebar = doc.querySelector('.sh-sidebar');
        var cur = document.querySelector('.sh-sidebar');
        if (!fresh) { window.location.href = url; return; }
        main.innerHTML = fresh.innerHTML;
        if (sidebar && cur) {
          // keep the sidebar element in place, only sync active states/counts
          cur.querySelectorAll('a.active').forEach(function (a) { a.classList.remove('active'); });
          sidebar.querySelectorAll('a.active').forEach(function (a) {
            var match = cur.querySelector('a[href="' + a.getAttribute('href') + '"]');
            if (match) match.classList.add('active');
          });
        }
        if (push) history.pushState({ ajax: true }, '', url);
        if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
        main.classList.remove('is-loading');
        busy = false;
      })
      .catch(function () { window.location.href = url; });
  }

  function formUrl(form) {
    var params = new URLSearchParams(new FormData(form));
    [].slice.call(params.keys()).forEach(function (k) { if (params.get(k) === '') params.delete(k); });
    var q = params.toString();
    return (form.getAttribute('action') || window.location.pathname) + (q ? '?' + q : '');
  }

  layout.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || (form.method || 'get').toLowerCase() !== 'get') return;
    e.preventDefault();
    load(formUrl(form), true);
  });

  layout.addEventListener('change', function (e) {
    var el = e.target;
    if (!el.form || el.form.method.toLowerCase() !== 'get') return;
    if (el.matches('select, input[type=checkbox], input[type=radio]')) {
      e.preventDefault();
      el.form.removeAttribute('onchange');
      load(formUrl(el.form), true);
    }
  });

  layout.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a || a.target === '_blank' || a.hasAttribute('download')) return;
    var href = a.getAttribute('href');
    if (!samePage(href) || href.charAt(0) === '#') return;
    if (a.closest('.sh-card, .listing-card, .biz-card, .sh-listing-card')) return;
    e.preventDefault();
    load(a.href, true);
  });

  window.addEventListener('popstate', function () { load(window.location.href, false); });
})();
