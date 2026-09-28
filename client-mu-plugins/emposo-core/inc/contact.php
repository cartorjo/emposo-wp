<?php
/**
 * The contact form backend: validate, store, mail, redirect.
 *
 * The static build hands the enquiry to the visitor's mail client (mailto:),
 * which fails silently for anyone without one configured and leaves no record.
 * Here the form posts to admin-post.php; the handler checks it, keeps a private
 * copy (CPT_ENQUIRY, purged after RETENTION_DAYS) and mails it to the
 * configured recipient through wp_mail() — which the host's SMTP plugin sends.
 *
 * Why not a form plugin (owner decision 2026-09-27): Contact Form 7 submits
 * over REST, which is closed to anonymous visitors (security.php), and prints
 * inline script the CSP forbids; the others bring their own markup and assets,
 * and all of them lean on third-party captchas that the zero-third-party rule
 * excludes.
 *
 * Spam defence is entirely local: a honeypot field, a signed render timestamp
 * (too fast = a bot), and a per-IP rate limit keyed on a salted hash. There is
 * deliberately no wp_nonce — pages sit behind a full-page cache and Cloudflare,
 * so a nonce baked into cached HTML would go stale and reject real visitors.
 * For the same reason the timestamp has no upper bound: a cached page may be
 * served long after it was rendered, and it is still a real visitor
 * submitting it, while a bot can always fetch a fresh token. The signature
 * stops forged tokens; the lower bound only bites on uncached renders, which
 * is where a scripted fetch-and-post comes from anyway.
 *
 * The flow is Post/Redirect/Get back to the submitting page, with a status
 * flag in the query (gesendet / sent, or problem=<key>) that the form partial
 * renders. No new routes: the route contract and the scaffold stay as they are.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Contact;

use const Emposo\Core\ContentModel\CPT_ENQUIRY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The admin-post action name, also the form's hidden `action` field. */
const ACTION = 'emposo_contact';

/** The honeypot field. A plausible name, so form-filling bots take it. */
const HONEYPOT = 'website';

/** Enquiries older than this are deleted by the daily purge. */
const RETENTION_DAYS = 90;

/** The daily purge event. */
const PURGE_HOOK = 'emposo_enquiry_purge';

/** A submission faster than this after render is treated as automated. */
const MIN_SECONDS = 3;

/** Accepted submissions per IP hash per hour. */
const RATE_LIMIT = 5;

/** Field length caps, in characters. */
const MAX_LENGTHS = array(
	'name'     => 200,
	'company'  => 200,
	'email'    => 254,
	'interest' => 200,
	'message'  => 5000,
);

add_action( 'admin_post_nopriv_' . ACTION, __NAMESPACE__ . '\\handle' );
add_action( 'admin_post_' . ACTION, __NAMESPACE__ . '\\handle' );
add_action( 'init', __NAMESPACE__ . '\\schedule_purge' );
add_action( PURGE_HOOK, __NAMESPACE__ . '\\purge' );
add_action( 'add_meta_boxes_' . CPT_ENQUIRY, __NAMESPACE__ . '\\add_meta_box' );

/**
 * WordPress-only UI strings, per locale.
 *
 * These are not in the reference dictionary (data/i18n.json is generated from
 * it and must not be hand-edited): the static build has no backend, so it has
 * no words for "sent" or "failed". The two strings that do exist there
 * (form.submit, form.explain) describe the mailto handoff and are overridden
 * here because on this site they would be false.
 *
 * Owner-approved copy, 2026-09-27 (PR #47): change a string only with a new
 * approval.
 *
 * @param string $key    String key.
 * @param string $locale 'de' or 'en'.
 */
function t( string $key, string $locale ): string {
	$strings = array(
		'de' => array(
			'submit'   => 'Anfrage senden',
			'explain'  => 'Wir verwenden Ihre Angaben nur, um Ihre Anfrage zu beantworten.',
			'sent'     => 'Vielen Dank — Ihre Anfrage ist bei uns eingegangen. Wir melden uns in Kürze.',
			'invalid'  => 'Bitte prüfen Sie Ihre Angaben: Name, E-Mail-Adresse, Thema und Nachricht sind erforderlich.',
			'rejected' => 'Ihre Anfrage konnte nicht gesendet werden. Bitte laden Sie die Seite neu und versuchen Sie es noch einmal.',
			'limited'  => 'Sie haben in kurzer Zeit mehrere Anfragen gesendet. Bitte versuchen Sie es später noch einmal.',
			'failed'   => 'Ihre Anfrage konnte gerade nicht zugestellt werden. Bitte schreiben Sie uns direkt an %s.',
		),
		'en' => array(
			'submit'   => 'Send inquiry',
			'explain'  => 'We use your details only to respond to your inquiry.',
			'sent'     => 'Thank you — we have received your inquiry and will get back to you shortly.',
			'invalid'  => 'Please check your details: name, email address, topic and message are required.',
			'rejected' => 'Your inquiry could not be sent. Please reload the page and try again.',
			'limited'  => 'You have sent several inquiries in a short time. Please try again later.',
			'failed'   => 'Your inquiry could not be delivered just now. Please email us directly at %s.',
		),
	);

	return $strings[ 'en' === $locale ? 'en' : 'de' ][ $key ] ?? '';
}

/**
 * The address every enquiry goes to: the option the importer writes, else the
 * shared inbox (owner decision 2026-09-25).
 */
function recipient(): string {
	$recipient = sanitize_email( (string) get_option( 'emposo_contact_recipient', '' ) );

	return '' === $recipient ? 'info@emposo.eu' : $recipient;
}

/**
 * The interest values the form offers in a locale: the only values accepted.
 *
 * @param string $locale 'de' or 'en'.
 * @return list<string>
 */
function interests( string $locale ): array {
	$interests = get_option( 'en' === $locale ? 'emposo_interests_en' : 'emposo_interests', array() );
	$values    = array();

	foreach ( is_array( $interests ) ? $interests : array() as $interest ) {
		$value = is_array( $interest ) ? (string) ( $interest['value'] ?? '' ) : (string) $interest;

		if ( '' !== $value ) {
			$values[] = $value;
		}
	}

	return $values;
}

/**
 * A signed render timestamp for the form's hidden `ts` field.
 */
function token(): string {
	$time = (string) time();

	return $time . '.' . hash_hmac( 'sha256', $time, wp_salt( 'nonce' ) );
}

/**
 * Seconds since the token was issued, or null when it is missing or forged.
 *
 * @param string $token The submitted `ts` field.
 */
function token_age( string $token ): ?int {
	$parts = explode( '.', $token, 2 );

	if ( 2 !== count( $parts ) || ! ctype_digit( $parts[0] ) ) {
		return null;
	}

	if ( ! hash_equals( hash_hmac( 'sha256', $parts[0], wp_salt( 'nonce' ) ), $parts[1] ) ) {
		return null;
	}

	return time() - (int) $parts[0];
}

/**
 * The status flag the partial should render, from the query string.
 *
 * Returns the string key (sent, invalid, …) or '' when there is none.
 */
function status(): string {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display flag set by our own redirect; it changes no state.
	if ( isset( $_GET['gesendet'] ) || isset( $_GET['sent'] ) ) {
		return 'sent';
	}

	$error = isset( $_GET['problem'] ) ? sanitize_key( wp_unslash( (string) $_GET['problem'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	return in_array( $error, array( 'invalid', 'rejected', 'limited', 'failed' ), true ) ? $error : '';
}

/**
 * The status message for the current request, as safe HTML, or ''.
 *
 * @param string $locale 'de' or 'en'.
 */
function status_message( string $locale ): string {
	$status = status();

	if ( '' === $status ) {
		return '';
	}

	if ( 'failed' === $status ) {
		$address = recipient();

		return sprintf( esc_html( t( 'failed', $locale ) ), '<a href="mailto:' . esc_attr( $address ) . '">' . esc_html( $address ) . '</a>' );
	}

	return esc_html( t( $status, $locale ) );
}

/**
 * Where to send the visitor back to: the submitting page, same host only.
 *
 * @param string $locale 'de' or 'en'.
 */
function return_url( string $locale ): string {
	$fallback = home_url( 'en' === $locale ? '/en/contact/' : '/kontakt/' );
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce-free by design (full-page cache); see the file header.
	$raw = isset( $_POST['return'] ) ? esc_url_raw( wp_unslash( (string) $_POST['return'] ) ) : '';
	// A path only: anything with a scheme or host is replaced, so this cannot
	// become an open redirect even before wp_validate_redirect() sees it.
	$path = (string) wp_parse_url( $raw, PHP_URL_PATH );

	if ( null !== wp_parse_url( $raw, PHP_URL_HOST ) || '' === $path || '/' !== $path[0] || str_starts_with( $path, '//' ) ) {
		return $fallback;
	}

	return (string) wp_validate_redirect( home_url( $path ), $fallback );
}

/**
 * Redirect back with a status flag and stop.
 *
 * @param string $url    Return URL.
 * @param string $status 'sent' or an error key.
 * @param string $locale 'de' or 'en'.
 */
function finish( string $url, string $status, string $locale ): void {
	$en = 'en' === $locale;
	// Not `error`: that is a core query var, and ?error=404 makes WordPress
	// send a 404 status for the page.
	$args = 'sent' === $status
		? array( ( $en ? 'sent' : 'gesendet' ) => '1' )
		: array( 'problem' => $status );

	wp_safe_redirect( add_query_arg( $args, $url ) . '#contact-form', 303 );
	exit;
}

/**
 * A salted hash of the client IP: enough to rate-limit, not enough to store.
 */
function client_hash(): string {
	// REMOTE_ADDR only: forwarded headers are client-controlled. Behind
	// Cloudflare the host normally rewrites REMOTE_ADDR to the visitor's IP.
	// This runs in admin-post.php, which no page cache serves, so reading it
	// server-side is sound; it is validated as an IP and only ever hashed.
	// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__ -- See above: uncached request, validated, hashed, used only as a rate-limit key.
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : '';

	return substr( hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ), 0, 32 );
}

/**
 * One field from the POST body, sanitised and capped.
 *
 * @param string $name Field name.
 */
function field( string $name ): string {
	// Nonce-free by design (full-page cache; see the file header). Sanitised
	// on the next line, per field type.
	$raw   = isset( $_POST[ $name ] ) ? (string) wp_unslash( $_POST[ $name ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$value = 'message' === $name ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );

	return mb_substr( trim( $value ), 0, MAX_LENGTHS[ $name ] ?? 200 );
}

/**
 * The admin-post handler.
 */
function handle(): void {
	$locale = 'en' === field( 'lang' ) ? 'en' : 'de';
	$return = return_url( $locale );

	// Honeypot filled: pretend it worked, so the bot learns nothing.
	if ( '' !== field( HONEYPOT ) ) {
		finish( $return, 'sent', $locale );
	}

	$age = token_age( field( 'ts' ) );

	if ( null === $age || $age < MIN_SECONDS ) {
		finish( $return, 'rejected', $locale );
	}

	$data = array();

	foreach ( array_keys( MAX_LENGTHS ) as $name ) {
		$data[ $name ] = field( $name );
	}

	$interests = interests( $locale );

	if (
		'' === $data['name']
		|| '' === $data['message']
		|| ! is_email( $data['email'] )
		|| '' === $data['interest']
		|| ( $interests && ! in_array( $data['interest'], $interests, true ) )
	) {
		finish( $return, 'invalid', $locale );
	}

	$rate_key = 'emposo_contact_' . client_hash();
	$count    = (int) get_transient( $rate_key );

	if ( $count >= RATE_LIMIT ) {
		finish( $return, 'limited', $locale );
	}

	set_transient( $rate_key, $count + 1, HOUR_IN_SECONDS );

	$body = body( $data, $locale );

	store( $data, $body, $locale );

	// One message per accepted enquiry, rate-limited above, sent through the
	// host's SMTP plugin — not bulk mail.
	$sent = wp_mail( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
		recipient(),
		subject( $data, $locale ),
		$body,
		array( 'Reply-To: ' . reply_name( $data['name'] ) . ' <' . $data['email'] . '>' )
	);

	finish( $return, $sent ? 'sent' : 'failed', $locale );
}

/**
 * The visitor's name, safe as a Reply-To display name.
 *
 * WordPress's wp_mail() splits Reply-To on commas, so "Doe, Jane" would become two broken
 * addresses; quotes, angle brackets and semicolons would confuse the parse too.
 *
 * @param string $name Validated name.
 */
function reply_name( string $name ): string {
	$clean = trim( (string) preg_replace( '/[,;"<>]+/', ' ', $name ) );

	return '' === $clean ? 'Emposo' : $clean;
}

/**
 * The mail subject — the wording the mailto handoff used.
 *
 * @param array<string, string> $data   Validated fields.
 * @param string                $locale 'de' or 'en'.
 */
function subject( array $data, string $locale ): string {
	return ( 'en' === $locale ? 'Emposo inquiry: ' : 'Emposo Anfrage: ' ) . $data['interest'];
}

/**
 * The mail body — the layout the mailto handoff used (06-work.js), so the
 * inbox sees the same shape as before.
 *
 * @param array<string, string> $data   Validated fields.
 * @param string                $locale 'de' or 'en'.
 */
function body( array $data, string $locale ): string {
	$labels = 'en' === $locale
		? array( 'Name: ', 'Company: ', 'Email: ', 'Interest: ', 'Message:' )
		: array( 'Name: ', 'Unternehmen: ', 'E-Mail: ', 'Interesse: ', 'Nachricht:' );

	return implode(
		"\n",
		array(
			$labels[0] . $data['name'],
			$labels[1] . $data['company'],
			$labels[2] . $data['email'],
			$labels[3] . $data['interest'],
			'',
			$labels[4],
			$data['message'],
		)
	);
}

/**
 * Keep a private copy, so an enquiry survives a mail failure.
 *
 * Everything lives in the post itself — title and content — with no meta: an
 * enquiry carries only what the visitor typed, the language and the time. The
 * derived-query cache is suspended for the insert: saving a post bumps its
 * version, and a burst of submissions should not invalidate every list.
 *
 * @param array<string, string> $data   Validated fields.
 * @param string                $body   The mail body.
 * @param string                $locale 'de' or 'en'.
 */
function store( array $data, string $body, string $locale ): void {
	\Emposo\Core\Cache\suspend( true );

	wp_insert_post(
		array(
			'post_type'    => CPT_ENQUIRY,
			'post_status'  => 'private',
			'post_title'   => sprintf( '%s — %s (%s)', $data['name'], $data['interest'], strtoupper( $locale ) ),
			'post_content' => $body,
		)
	);

	\Emposo\Core\Cache\suspend( false );
}

/**
 * The enquiry as received, read-only, on its edit screen.
 *
 * The type supports only a title (content-model.php), so the text itself is
 * shown here rather than in an editor that would invite changing it.
 */
function add_meta_box(): void {
	\add_meta_box(
		'emposo-enquiry',
		__( 'Anfrage', 'emposo' ),
		static function ( \WP_Post $post ): void {
			echo '<pre style="white-space:pre-wrap;font:inherit;margin:0">' . esc_html( $post->post_content ) . '</pre>';
		},
		CPT_ENQUIRY,
		'normal',
		'high'
	);
}

/**
 * Schedule the daily purge once.
 */
function schedule_purge(): void {
	if ( ! wp_next_scheduled( PURGE_HOOK ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', PURGE_HOOK );
	}
}

/**
 * Delete enquiries older than RETENTION_DAYS, for good (no trash).
 */
function purge(): void {
	$old = get_posts(
		array(
			'post_type'        => CPT_ENQUIRY,
			'post_status'      => 'any',
			'fields'           => 'ids',
			'posts_per_page'   => 100,
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'date_query'       => array(
				array(
					'column' => 'post_date_gmt',
					'before' => RETENTION_DAYS . ' days ago',
				),
			),
		)
	);

	\Emposo\Core\Cache\suspend( true );

	foreach ( $old as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}

	\Emposo\Core\Cache\suspend( false );
}
