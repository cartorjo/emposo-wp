<?php
/**
 * Ported from the static build's partials/contact-form.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<form class="contact-form" data-contact-form action="mailto:jose.caravaca@emposo.eu" method="post" enctype="text/plain"><label>Ihr Name<input name="name" autocomplete="name" required></label><label>Unternehmen (optional)<input name="company" autocomplete="organization"></label><label>E-Mail-Adresse<input name="email" type="email" autocomplete="email" required></label><label>Worum geht es?<select name="interest" required><option value="" selected disabled>Bitte auswählen</option><option>Optimierung einer bestehenden Leistung</option><option>Transformation / AI-Use-Case</option><option>Skalierung eines Programms</option><option>Karriere bei Emposo</option><option>Anderes Anliegen</option></select></label><p class="contact-form__hint" data-contact-hint hidden aria-live="polite"></p><label class="contact-form__message">Ihre Nachricht<textarea name="message" rows="4" required></textarea></label><button type="submit" class="min-h-11">Anfrage vorbereiten <span aria-hidden="true">→</span></button><p class="contact-form__explanation">Das Formular öffnet Ihr E-Mail-Programm. Dort können Sie die Anfrage prüfen und absenden. <a href="https://emposo.de/datenschutzerklaerung/">Datenschutz</a></p></form>
