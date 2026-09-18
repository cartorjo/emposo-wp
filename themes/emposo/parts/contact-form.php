<?php
/**
 * Ported from the static build's partials/contact-form.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * The recipient and the interest list come from options the importer already
 * writes. They were hard-coded here while `emposo_contact_recipient` and
 * `emposo_interests` sat in the database unread — so the one field on this site
 * that decides where every enquiry goes could not be changed without a deploy,
 * and the admin screen's warning about it pointed at a value nothing could
 * edit. The literals remain as fallbacks, so output is byte-identical when the
 * options are absent or hold what the export holds.
 *
 * @package Emposo
 */

$emposo_recipient = sanitize_email( (string) get_option( 'emposo_contact_recipient', '' ) );

if ( '' === $emposo_recipient ) {
	$emposo_recipient = 'jose.caravaca@emposo.eu';
}

$emposo_interests = get_option( 'emposo_interests', array() );

if ( ! is_array( $emposo_interests ) || ! $emposo_interests ) {
	$emposo_interests = array(
		array( 'value' => 'Optimierung einer bestehenden Leistung' ),
		array( 'value' => 'Transformation / AI-Use-Case' ),
		array( 'value' => 'Skalierung eines Programms' ),
		array( 'value' => 'Karriere bei Emposo' ),
		array( 'value' => 'Anderes Anliegen' ),
	);
}

$emposo_options = '';

foreach ( $emposo_interests as $emposo_interest ) {
	$emposo_value = is_array( $emposo_interest ) ? (string) ( $emposo_interest['value'] ?? '' ) : (string) $emposo_interest;
	$emposo_label = is_array( $emposo_interest ) ? (string) ( $emposo_interest['label'] ?? $emposo_value ) : $emposo_value;

	if ( '' === $emposo_label ) {
		continue;
	}

	/*
	 * A bare <option> when value and label agree — which is how the audited
	 * markup reads, and every current entry is that shape. An explicit value
	 * attribute appears only when it would actually carry information, so
	 * adding one later does not rewrite the other five lines of the diff.
	 */
	$emposo_options .= $emposo_value === $emposo_label
		? '<option>' . esc_html( $emposo_label ) . '</option>'
		: '<option value="' . esc_attr( $emposo_value ) . '">' . esc_html( $emposo_label ) . '</option>';
}

?>
<form class="contact-form" data-contact-form action="mailto:<?php echo esc_attr( $emposo_recipient ); ?>" method="post" enctype="text/plain"><label>Ihr Name<input name="name" autocomplete="name" required></label><label>Unternehmen (optional)<input name="company" autocomplete="organization"></label><label>E-Mail-Adresse<input name="email" type="email" autocomplete="email" required></label><label>Worum geht es?<select name="interest" required><option value="" selected disabled>Bitte auswählen</option><?php echo $emposo_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled above from esc_html()/esc_attr() parts; escaping again would double-encode the labels. ?></select></label><p class="contact-form__hint" data-contact-hint hidden aria-live="polite"></p><label class="contact-form__message">Ihre Nachricht<textarea name="message" rows="4" required></textarea></label><button type="submit" class="min-h-11">Anfrage vorbereiten <span aria-hidden="true">→</span></button><p class="contact-form__explanation">Das Formular öffnet Ihr E-Mail-Programm. Dort können Sie die Anfrage prüfen und absenden. <a href="https://emposo.de/datenschutzerklaerung/">Datenschutz</a></p></form>
