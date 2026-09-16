/* ============================================================================
   00-core.js — global runtime: Lenis smooth scroll, media-query handles,
   section-ready helper.
   Loads first (filename order, defer). Section files (01–08) call
   window.__onReady(fn); their callbacks run after this file has initialised
   on DOMContentLoaded.
   Exposes: window.__lenis  — Lenis instance (null under reduced motion)
            window.__mq     — { desktop, reduced } MediaQueryList handles
            window.__onReady(fn) — run fn once core init is done
   ========================================================================= */
(function () {
  'use strict';

  // ---- media-query handles (decisions §D: 767/768 is THE js boundary) ----
  var mq = {
    desktop: window.matchMedia('(min-width: 768px)'),
    reduced: window.matchMedia('(prefers-reduced-motion: reduce)')
  };
  window.__mq = mq;

  // ---- ready queue (defined immediately so section IIFEs can register) ----
  var readyFns = [];
  var isReady = false;
  window.__onReady = function (fn) {
    if (typeof fn !== 'function') return;
    if (isReady) {
      fn();
    } else {
      readyFns.push(fn);
    }
  };

  window.__lenis = null;

  function init() {
    // ---- Lenis smooth scroll (motion.md §0 params; normalizeWheel/smoothTouch
    // were dropped — neither option exists in lenis 1.2.3, both were ignored) ----
    // Skipped entirely under prefers-reduced-motion: native scrolling instead.
    if (!mq.reduced.matches && window.Lenis) {
      var lenis = new window.Lenis({
        lerp: 0.1,
        wheelMultiplier: 0.9,
        gestureOrientation: 'vertical'
      });
      window.__lenis = lenis;

      var rafId = null;
      var raf = function (time) {
        lenis.raf(time);
        rafId = window.requestAnimationFrame(raf);
      };
      rafId = window.requestAnimationFrame(raf);

      // Honour a LIVE change of the motion preference, not just its value at
      // load: destroy the smoother and fall back to native scrolling.
      mq.reduced.addEventListener('change', function (event) {
        if (!event.matches || !window.__lenis) return;
        window.cancelAnimationFrame(rafId);
        window.__lenis.destroy();
        window.__lenis = null;
      });
    }

    // ---- skip link: make the #main jump play nice with Lenis ----
    var skip = document.querySelector('a[href="#main"]');
    if (skip) {
      skip.addEventListener('click', function () {
        if (window.__lenis) {
          window.__lenis.scrollTo('#main', { immediate: true });
        }
      });
    }

    // ---- flush section callbacks ----
    isReady = true;
    for (var i = 0; i < readyFns.length; i++) {
      try {
        readyFns[i]();
      } catch (err) {
        if (window.console && console.error) console.error(err);
      }
    }
    readyFns.length = 0;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
