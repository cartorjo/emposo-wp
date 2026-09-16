/* ============================================================================
   07-countup.js — one-shot count-up for the homepage stat numbers
   (.company-facts__value). Progressive enhancement only: the static markup keeps
   the final values, and the animation is skipped without IntersectionObserver
   or under prefers-reduced-motion (checked live at trigger time).
   ========================================================================= */
(function () {
  'use strict';
  if (!window.__onReady) return;

  window.__onReady(function () {
    var stats = document.querySelectorAll('.company-facts__value');
    if (!stats.length || !('IntersectionObserver' in window)) return;

    // "2014", "250+", "2.900+", "4" — digits with optional German thousands
    // separators plus an optional suffix. Anything else stays untouched.
    var pattern = /^(\d{1,3}(?:\.\d{3})+|\d+)(\+?)$/;

    function render(value, grouped) {
      var digits = String(value);
      if (!grouped) return digits;
      return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function animate(dt, parsed) {
      var start = null;
      var duration = 900;
      function frame(now) {
        if (start === null) start = now;
        var t = Math.min((now - start) / duration, 1);
        var eased = 1 - Math.pow(1 - t, 3);
        if (t < 1) {
          dt.textContent = render(Math.round(parsed.target * eased), parsed.grouped) + parsed.suffix;
          window.requestAnimationFrame(frame);
        } else {
          // Always end on the exact authored string.
          dt.textContent = parsed.original;
        }
      }
      window.requestAnimationFrame(frame);
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        observer.unobserve(entry.target);
        if (window.__mq && window.__mq.reduced.matches) return;
        var original = entry.target.textContent.trim();
        var match = pattern.exec(original);
        if (!match) return;
        animate(entry.target, {
          original: original,
          target: parseInt(match[1].replace(/\./g, ''), 10),
          grouped: match[1].indexOf('.') !== -1,
          suffix: match[2]
        });
      });
    }, { threshold: 0.4 });

    stats.forEach(function (dt) {
      observer.observe(dt);
    });
  });
})();
