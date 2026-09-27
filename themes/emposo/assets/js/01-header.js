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
        menu.querySelector('summary').setAttribute('aria-label', menu.open ? 'Menü schließen' : 'Menü öffnen');
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

    var updateHeader = function () {
      if (header) header.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
  });
})();
