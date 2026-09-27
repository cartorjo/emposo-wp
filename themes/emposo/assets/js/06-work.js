/* Filterable project portfolio and contact-form validation.
   No third-party service is used: filters run locally, and a valid form posts
   to this site (emposo-core inc/contact.php). Unlike the static build there is
   no mailto: handoff — the reference's copy of this file keeps that. */
(function () {
  'use strict';
  if (!window.__onReady) return;

  window.__onReady(function () {
    var state = { industry: 'all', discipline: 'all' };
    // The page language picks the UI strings (docs/i18n.md).
    var en = document.documentElement.lang === 'en';
    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-filter-group]'));
    var projects = Array.prototype.slice.call(document.querySelectorAll('[data-project]'));
    var count = document.getElementById('project-count');
    var empty = document.getElementById('project-empty');

    function matches(project, group, value) {
      if (value === 'all') return true;
      return (project.getAttribute('data-' + group) || '').split(/\s+/).indexOf(value) !== -1;
    }

    function render() {
      var visible = 0;
      projects.forEach(function (project) {
        var show = matches(project, 'industry', state.industry) && matches(project, 'discipline', state.discipline);
        project.hidden = !show;
        if (show) visible += 1;
      });
      if (count) count.textContent = visible + (en ? (visible === 1 ? ' project' : ' projects') : (visible === 1 ? ' Projekt' : ' Projekte'));
      if (empty) empty.hidden = visible !== 0;
    }

    // The selection lives in the URL (?branche= / ?leistung=), so a filtered
    // list can be shared and survives reload and back/forward (B-41).
    var params = { industry: 'branche', discipline: 'leistung' };

    function writeUrl() {
      var url = new URL(window.location.href);
      Object.keys(params).forEach(function (group) {
        if (state[group] === 'all') url.searchParams.delete(params[group]);
        else url.searchParams.set(params[group], state[group]);
      });
      if (url.href !== window.location.href) window.history.replaceState(window.history.state, '', url.href);
    }

    function selectFilter(group, value) {
      if (!group || !value) return;
      state[group] = value;
      buttons.forEach(function (candidate) {
        if (candidate.getAttribute('data-filter-group') !== group) return;
        var active = candidate.getAttribute('data-filter-value') === value;
        candidate.classList.toggle('is-active', active);
        candidate.setAttribute('aria-pressed', active ? 'true' : 'false');
        candidate.tabIndex = active ? 0 : -1;
      });
      render();
    }

    // One Tab stop per chip group (the selected chip); arrows move between chips.
    buttons.forEach(function (button) {
      button.tabIndex = button.classList.contains('is-active') ? 0 : -1;
      button.addEventListener('keydown', function (event) {
        var keys = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1, Home: 'first', End: 'last' };
        var step = keys[event.key];
        if (!step) return;
        var group = buttons.filter(function (b) { return !b.disabled && b.getAttribute('data-filter-group') === button.getAttribute('data-filter-group'); });
        var index = group.indexOf(button);
        var next = step === 'first' ? 0 : step === 'last' ? group.length - 1 : (index + step + group.length) % group.length;
        event.preventDefault();
        group[next].focus();
      });
    });

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        var group = button.getAttribute('data-filter-group');
        var value = button.getAttribute('data-filter-value');
        selectFilter(group, value);
        writeUrl();
      });
    });

    // Restore a shared selection; unknown or disabled values are ignored.
    var query = new URLSearchParams(window.location.search);
    Object.keys(params).forEach(function (group) {
      var value = query.get(params[group]);
      if (value && buttons.some(function (button) {
        return !button.disabled && button.getAttribute('data-filter-group') === group && button.getAttribute('data-filter-value') === value;
      })) selectFilter(group, value);
    });

    // The filter UI is meaningless without this script: it ships hidden
    // (.js-only) and is revealed only once the handlers are attached.
    Array.prototype.slice.call(document.querySelectorAll('.js-only')).forEach(function (el) {
      el.classList.remove('js-only');
    });

    var form = document.querySelector('[data-contact-form]');
    if (form) {
      // Errors appear as German supporting text under each field instead of
      // the browser's bubbles (which follow the browser language). Checked on
      // submit, then live per field; not on blur, because text appearing
      // under a field would move the submit button away mid-click.
      var messages = en ? {
        name: { valueMissing: 'Please enter your name.' },
        email: { valueMissing: 'Please enter your email address.', typeMismatch: 'Please enter a valid email address, e.g. name@company.com.' },
        interest: { valueMissing: 'Please select what it is about.' },
        message: { valueMissing: 'Please tell us briefly what it is about.' }
      } : {
        name: { valueMissing: 'Bitte geben Sie Ihren Namen an.' },
        email: { valueMissing: 'Bitte geben Sie Ihre E-Mail-Adresse an.', typeMismatch: 'Bitte geben Sie eine gültige E-Mail-Adresse an, z. B. name@firma.de.' },
        interest: { valueMissing: 'Bitte wählen Sie aus, worum es geht.' },
        message: { valueMissing: 'Bitte schreiben Sie uns kurz, worum es geht.' }
      };
      // The visible fields only: hidden inputs and the honeypot carry no
      // supporting text and are checked on the server.
      var fields = Array.prototype.slice.call(form.querySelectorAll('[aria-describedby]'));
      var check = function (field) {
        var support = document.getElementById(field.getAttribute('aria-describedby'));
        var valid = field.checkValidity();
        var text = '';
        if (!valid) {
          var own = messages[field.name] || {};
          text = (field.validity.valueMissing && own.valueMissing) || (field.validity.typeMismatch && own.typeMismatch) || field.validationMessage;
        }
        field.setAttribute('aria-invalid', valid ? 'false' : 'true');
        if (support) support.textContent = text;
        return valid;
      };
      form.noValidate = true;
      fields.forEach(function (field) {
        field.addEventListener('input', function () { if (field.getAttribute('aria-invalid') === 'true') check(field); });
        field.addEventListener('change', function () { if (field.getAttribute('aria-invalid') === 'true') check(field); });
      });
      form.addEventListener('submit', function (event) {
        var firstInvalid = null;
        fields.forEach(function (field) { if (!check(field) && !firstInvalid) firstInvalid = field; });
        if (!firstInvalid) {
          // Valid: let the browser post it, once.
          var button = form.querySelector('button[type="submit"]');
          if (button) button.disabled = true;
          return;
        }
        event.preventDefault();
        firstInvalid.focus();
      });
    }
  });
})();
