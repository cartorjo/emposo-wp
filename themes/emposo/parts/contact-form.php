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
	$emposo_recipient = 'info@emposo.eu';
}

// The interests in the page language: English pages read emposo_interests_en.
$emposo_interests = get_option( 'en' === emposo_lang() ? 'emposo_interests_en' : 'emposo_interests', array() );

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
<form class="contact-form" data-contact-form action="mailto:<?php echo esc_attr( $emposo_recipient ); ?>" method="post" enctype="text/plain"><label><?php echo emposo_t( 'form.name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?><input name="name" aria-describedby="contact-name-support" autocomplete="name" required><span class="contact-form__support" id="contact-name-support"></span></label><label><?php echo emposo_t( 'form.company' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?><input name="company" aria-describedby="contact-company-support" autocomplete="organization"><span class="contact-form__support" id="contact-company-support"></span></label><label><?php echo emposo_t( 'form.email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?><input name="email" aria-describedby="contact-email-support" type="email" autocomplete="email" required><span class="contact-form__support" id="contact-email-support"></span></label><label class="contact-form__select"><?php echo emposo_t( 'form.interest' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?><select name="interest" aria-describedby="contact-interest-support" required><option value="" selected disabled><?php echo emposo_t( 'form.choose' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></option><?php echo $emposo_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled above from esc_html()/esc_attr() parts; escaping again would double-encode the labels. ?></select><span class="contact-form__support" id="contact-interest-support"></span></label><p class="contact-form__hint" data-contact-hint hidden aria-live="polite"></p><label class="contact-form__message"><?php echo emposo_t( 'form.message' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?><textarea name="message" aria-describedby="contact-message-support" rows="4" required></textarea><span class="contact-form__support" id="contact-message-support"></span></label><button type="submit" class="header-contact min-h-11"><?php echo emposo_t( 'form.submit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?> <span class="header-contact__arrow" aria-hidden="true">→</span></button><p class="contact-form__explanation"><?php echo emposo_t( 'form.explain' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?> <a href="<?php echo esc_attr( emposo_href( '/datenschutzerklaerung/' ) ); ?>"><?php echo emposo_t( 'form.privacy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a></p></form>
