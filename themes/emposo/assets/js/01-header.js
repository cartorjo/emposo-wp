/* Header behaviour: close the responsive menu after navigation and add a
   subtle scroll state. Opening/closing is native <details>. */
(function () {
  'use strict';
  if (!window.__onReady) return;

  window.__onReady(function () {
    var menu = document.getElementById('mobile-menu');
    var header = document.querySelector('[data-site-header]');
    /* Keep in sync with --breakpoint-nav in styles/main.css. */
    var wideNav = window.matchMedia('(min-width: 75rem)');

    if (menu) {
      menu.addEventListener('toggle', function () {
        // The page language picks the label (docs/i18n.md).
        var en = document.documentElement.lang === 'en';
        menu.querySelector('summary').setAttribute('aria-label', menu.open ? (en ? 'Close menu' : 'Menü schließen') : (en ? 'Open menu' : 'Menü öffnen'));
      });
      menu.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          menu.open = false;
          menu.querySelector('summary').focus();
        }
      });
      menu.addEventListener('click', function (event) {
        if (event.target.closest('a')) menu.open = false;
      });
      // A disclosure, not a dialog: no focus trap, but it closes once focus
      // or a click leaves it, so it never lingers over the page.
      menu.addEventListener('focusout', function (event) {
        if (menu.open && event.relatedTarget && !menu.contains(event.relatedTarget)) menu.open = false;
      });
      document.addEventListener('click', function (event) {
        if (menu.open && !menu.contains(event.target)) menu.open = false;
      });

      wideNav.addEventListener('change', function (event) {
        if (event.matches) menu.open = false;
      });
    }

    /* Language switch: a native disclosure (no JS needed to use it). Escape
       closes it and returns focus to its button; a click or focus outside
       closes it too. */
    var switching = false;
    Array.prototype.slice.call(document.querySelectorAll('[data-lang-switch]')).forEach(function (picker) {
      var button = picker.querySelector('summary');
      picker.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && picker.open) {
          event.stopPropagation();
          picker.open = false;
          button.focus();
        }
      });
      picker.addEventListener('focusout', function (event) {
        if (picker.open && event.relatedTarget && !picker.contains(event.relatedTarget)) picker.open = false;
      });
      document.addEventListener('click', function (event) {
        if (picker.open && !picker.contains(event.target)) picker.open = false;
      });
      picker.addEventListener('click', function (event) {
        if (event.target.closest('[data-lang-option]')) switching = true;
      });
    });

    /* The cross-document view transition (styles/11-components.css) is for
       switching language only: every other navigation skips it. */
    window.addEventListener('pageswap', function (event) {
      if (event.viewTransition && !switching) event.viewTransition.skipTransition();
    });

    var updateHeader = function () {
      if (header) header.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
  });
})();
