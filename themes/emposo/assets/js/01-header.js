/* Header behaviour: close the responsive menu after navigation and add a
   subtle scroll state. Opening/closing is native <details>. */
(function () {
  'use strict';
  if (!window.__onReady) return;

  window.__onReady(function () {
    var menu = document.getElementById('mobile-menu');
    var header = document.querySelector('[data-site-header]');
    /* Keep in sync with --breakpoint-nav in styles/main.css. */
    var wideNav = window.matchMedia('(min-width: 1280px)');

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

      wideNav.addEventListener('change', function (event) {
        if (event.matches) menu.open = false;
      });
    }

    var navGroups = Array.prototype.slice.call(document.querySelectorAll('.site-nav__group'));
    navGroups.forEach(function (group) {
      group.addEventListener('click', function (event) {
        if (event.target.closest('a')) group.open = false;
      });
      /* Only one mega panel open at a time (details name= isn't available on
         the iOS 16 floor; without JS two open panels degrade gracefully). */
      group.addEventListener('toggle', function () {
        if (!group.open) return;
        navGroups.forEach(function (other) {
          if (other !== group) other.open = false;
        });
      });
      group.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          group.open = false;
          var summary = group.querySelector('summary');
          if (summary) summary.focus();
        }
      });
    });

    document.addEventListener('click', function (event) {
      navGroups.forEach(function (group) {
        if (group.open && !group.contains(event.target)) group.open = false;
      });
    });

    var updateHeader = function () {
      if (header) header.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
  });
})();
