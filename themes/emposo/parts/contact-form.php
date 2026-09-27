<?php
/**
 * Ported from the static build's partials/contact-form.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * The interest list comes from an option the importer already writes
 * (`emposo_interests`, `emposo_interests_en`); the literals remain as a
 * fallback. The recipient is no longer in the markup at all: the handler reads
 * `emposo_contact_recipient` (Contact\recipient()).
 *
 * Unlike the reference, the form posts to this site (inc/contact.php in
 * emposo-core) instead of handing off to the visitor's mail client: the
 * action, the hidden fields, the honeypot, the status line and the submit and
 * explanation wording differ from the static build, and the parity harness
 * carves this <form> out on the pages that carry it (tools/parity.config.json,
 * allowedDeltas). Everything the scripts rely on — data-contact-form, the
 * interest select, data-contact-hint — is unchanged.
 *
 * @package Emposo
 */

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

$emposo_lang   = 'en' === emposo_lang() ? 'en' : 'de';
$emposo_status = \Emposo\Core\Contact\status_message( $emposo_lang );
// The page to come back to after the POST (a path; the handler checks it).
$emposo_return = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';

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
<form class="contact-form" id="contact-form" data-contact-form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><?php echo '' !== $emposo_status ? '<p class="contact-form__hint" role="status">' . $emposo_status . '</p>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by status_message(). ?><input type="hidden" name="action" value="<?php echo esc_attr( \Emposo\Core\Contact\ACTION ); ?>"><input type="hidden" name="lang" value="<?php echo esc_attr( $emposo_lang ); ?>"><input type="hidden" name="return" value="<?php echo esc_attr( $emposo_return ); ?>"><input type="hidden" name="ts" value="<?php echo esc_attr( \Emposo\Core\Contact\token() ); ?>"><div hidden><label>Website<input name="<?php echo esc_attr( \Emposo\Core\Contact\HONEYPOT ); ?>" tabindex="-1" autocomplete="off"></label></div><label><?php echo emposo_t( 'form.name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?><input name="name" aria-describedby="contact-name-support" autocomplete="name" required><span class="contact-form__support" id="contact-name-support"></span></label><label><?php echo emposo_t( 'form.company' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?><input name="company" aria-describedby="contact-company-support" autocomplete="organization"><span class="contact-form__support" id="contact-company-support"></span></label><label><?php echo emposo_t( 'form.email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?><input name="email" aria-describedby="contact-email-support" type="email" autocomplete="email" required><span class="contact-form__support" id="contact-email-support"></span></label><label class="contact-form__select"><?php echo emposo_t( 'form.interest' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?><select name="interest" aria-describedby="contact-interest-support" required><option value="" selected disabled><?php echo emposo_t( 'form.choose' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></option><?php echo $emposo_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled above from esc_html()/esc_attr() parts; escaping again would double-encode the labels. ?></select><span class="contact-form__support" id="contact-interest-support"></span></label><p class="contact-form__hint" data-contact-hint hidden aria-live="polite"></p><label class="contact-form__message"><?php echo emposo_t( 'form.message' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?><textarea name="message" aria-describedby="contact-message-support" rows="4" required></textarea><span class="contact-form__support" id="contact-message-support"></span></label><button type="submit" class="header-contact min-h-11"><?php echo esc_html( \Emposo\Core\Contact\t( 'submit', $emposo_lang ) ); ?> <span class="header-contact__arrow" aria-hidden="true">→</span></button><p class="contact-form__explanation"><?php echo esc_html( \Emposo\Core\Contact\t( 'explain', $emposo_lang ) ); ?> <a href="<?php echo esc_url( emposo_href( '/datenschutzerklaerung/' ) ); ?>"><?php echo emposo_t( 'form.privacy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a></p></form>
