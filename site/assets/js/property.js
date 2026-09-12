/* Property listing view switcher: list/grid, keyboard accessible, remembers the user's choice. */
(function () {
  'use strict';

  function initPropertyViewSwitcher() {
    var toolbar = document.querySelector('.property-results-view');
    var grid = document.querySelector('.property-grid');
    if (!toolbar || !grid) return;

    var buttons = Array.prototype.slice.call(toolbar.querySelectorAll('button'));
    if (buttons.length < 2) return;

    var styleId = 'property-view-switcher-styles';
    if (!document.getElementById(styleId)) {
      var style = document.createElement('style');
      style.id = styleId;
      style.textContent = [
        '.property-results-view button{cursor:pointer}',
        '.property-results-view button:focus-visible{outline:2px solid #16a34a;outline-offset:2px}',
        '.property-grid.is-list{display:flex;flex-direction:column;gap:0}',
        '.property-grid.is-list .property-card{display:grid;grid-template-columns:220px minmax(0,1fr);grid-template-rows:auto auto 1fr;column-gap:18px;align-items:start;padding:16px 0;border-bottom:1px solid #eef0f1}',
        '.property-grid.is-list .property-card-image{grid-row:1 / 4;width:220px;margin:0;aspect-ratio:1.45}',
        '.property-grid.is-list .property-card>strong{padding-top:2px}',
        '.property-grid.is-list .property-card h3{margin-top:6px}',
        '.property-grid.is-list .property-card p{margin-top:2px}',
        '@media(max-width:600px){.property-grid.is-list .property-card{grid-template-columns:120px minmax(0,1fr);column-gap:12px}.property-grid.is-list .property-card-image{width:120px}}'
      ].join('');
      document.head.appendChild(style);
    }

    buttons[0].setAttribute('aria-label', 'List view');
    buttons[1].setAttribute('aria-label', 'Grid view');
    buttons[0].setAttribute('title', 'List view');
    buttons[1].setAttribute('title', 'Grid view');

    function setView(view, remember) {
      var list = view === 'list';
      grid.classList.toggle('is-list', list);
      buttons.forEach(function (button, index) {
        var active = list ? index === 0 : index === 1;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
      if (remember) {
        try { window.localStorage.setItem('property-view', view); } catch (error) {}
      }
    }

    buttons[0].addEventListener('click', function () { setView('list', true); });
    buttons[1].addEventListener('click', function () { setView('grid', true); });
    buttons.forEach(function (button) {
      button.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowLeft') { event.preventDefault(); buttons[0].focus(); setView('list', true); }
        if (event.key === 'ArrowRight') { event.preventDefault(); buttons[1].focus(); setView('grid', true); }
      });
    });

    var saved = null;
    try { saved = window.localStorage.getItem('property-view'); } catch (error) {}
    setView(saved === 'grid' || saved === 'list' ? saved : 'list', false);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPropertyViewSwitcher);
  } else {
    initPropertyViewSwitcher();
  }
}());
