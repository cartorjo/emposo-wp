/* Intent links carry the visitor's selected path into the contact form. This
   keeps the homepage's career and project routes useful without collecting
   data or relying on a third-party form service. */
(function () {
  'use strict';
  if (!window.__onReady) return;

  window.__onReady(function () {
    var form = document.querySelector('[data-contact-form]');
    var select = form && form.querySelector('select[name="interest"]');
    var hint = document.querySelector('[data-contact-hint]');
    if (!select) return;

    function preselect(interest) {
      var known = Array.prototype.slice.call(select.options).some(function (candidate) {
        return candidate.value === interest;
      });
      if (!known) return;
      select.value = interest;
      if (hint) {
        hint.textContent = 'Wir haben „' + interest + '“ für Ihre Anfrage vorausgewählt.';
        hint.hidden = false;
      }
    }

    /* Cross-page intent: /kontakt/?interesse=… carries the visitor's chosen
       path into the form. Without JS the form still works, just unpreselected. */
    var fromQuery = new URLSearchParams(window.location.search).get('interesse');
    if (fromQuery) preselect(fromQuery);

    Array.prototype.slice.call(document.querySelectorAll('[data-contact-interest]')).forEach(function (link) {
      link.addEventListener('click', function () {
        preselect(link.getAttribute('data-contact-interest'));
      });
    });
  });
})();
