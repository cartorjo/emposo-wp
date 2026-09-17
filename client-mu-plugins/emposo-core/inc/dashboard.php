<?php
/**
 * The Emposo admin screen.
 *
 * One page that answers the questions this site's failure modes actually pose:
 * is the content complete, when was it last imported, which uploads have no
 * AVIF renditions, do the security headers still hold, when was the site last
 * audited — and is the contact form still pointing at a personal mailbox.
 *
 * Read-only apart from one write (re-running the header check), which carries a
 * nonce and a capability check. Every outbound request is admin-side; nothing
 * here is reachable from the front end.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Dashboard;

use function Emposo\Core\Claude\is_configured as claude_configured;
use function Emposo\Core\Claude\key_source as claude_key_source;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY;
use const Emposo\Core\ContentModel\CPT_PERSON;
use const Emposo\Core\ContentModel\TAX_DISCIPLINE;
use const Emposo\Core\ContentModel\TAX_INDUSTRY;
use const Emposo\Core\ContentModel\TAX_OUTCOME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MENU_SLUG      = 'emposo';
const HEADER_CACHE   = 'emposo_header_check';
const RECHECK_ACTION = 'emposo_recheck_headers';

/**
 * The repository, for the Actions links.
 *
 * A constant rather than a setting: it is a property of this codebase, and a
 * settings field for it would be a field nobody ever changes.
 */
const REPO = 'cartorjo/emposo-wp';

/**
 * The headers inc/security.php must be sending, and the pattern each must match.
 *
 * Asserted rather than listed: a header that silently stopped being sent is the
 * failure this panel exists to catch.
 */
const REQUIRED_HEADERS = array(
	'content-security-policy' => "default-src 'self'",
	'x-content-type-options'  => 'nosniff',
	'referrer-policy'         => 'strict-origin',
	'permissions-policy'      => 'geolocation=()',
);

/**
 * Headers that must NOT be present.
 *
 * Both were removed in inc/security.php and both come back the moment a
 * `remove_action` stops matching core's priority — which is invisible from the
 * rendered page, because neither appears in the HTML.
 */
const FORBIDDEN_HEADERS = array( 'link', 'x-pingback' );

add_action( 'admin_menu', __NAMESPACE__ . '\\register_page' );
add_action( 'admin_post_' . RECHECK_ACTION, __NAMESPACE__ . '\\handle_recheck' );

/**
 * Register the menu entry.
 */
function register_page(): void {
	add_menu_page(
		__( 'Emposo', 'emposo' ),
		__( 'Emposo', 'emposo' ),
		'manage_options',
		MENU_SLUG,
		__NAMESPACE__ . '\\render',
		'dashicons-chart-area',
		2
	);
}

/**
 * Clear the cached header check.
 *
 * The one write on this screen, so it does what every admin write here must:
 * verifies the nonce, then the capability, then acts.
 */
function handle_recheck(): void {
	check_admin_referer( RECHECK_ACTION );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'emposo' ), '', array( 'response' => 403 ) );
	}

	delete_transient( HEADER_CACHE );

	wp_safe_redirect( add_query_arg( 'page', MENU_SLUG, admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Render the screen.
 */
function render(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'emposo' ), '', array( 'response' => 403 ) );
	}

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Emposo', 'emposo' ) . '</h1>';

	render_recipient_warning();
	render_content_panel();
	render_media_panel();
	render_header_panel();
	render_audit_panel();
	render_tooling_panel();

	echo '</div>';
}

/**
 * The standing warning about the contact recipient.
 *
 * The contact form is a mailto: form — there is no backend — so whatever
 * address it carries receives every enquiry the site produces. It currently
 * ships a personal address, which is fine for a staging site and wrong for a
 * launched one, and it is hard-coded in the template rather than stored in an
 * option, so nothing else on this screen would reveal it.
 *
 * This notice is deliberately not dismissible.
 */
function render_recipient_warning(): void {
	$recipient = (string) get_option( 'emposo_contact_recipient', '' );
	$template  = get_theme_file_path( 'parts/contact-form.php' );
	$in_markup = '';

	if ( is_readable( $template ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- A theme template on local disk, never a URL; read once on an admin screen.
		$markup = (string) file_get_contents( $template );

		if ( preg_match( '/mailto:([^"\']+)/', $markup, $matches ) ) {
			$in_markup = sanitize_email( $matches[1] );
		}
	}

	$addresses = array_values( array_unique( array_filter( array( $in_markup, $recipient ) ) ) );

	if ( ! $addresses ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>';
	echo esc_html__( 'Kontaktformular: Empfängeradresse prüfen', 'emposo' );
	echo '</strong><br>';
	printf(
		/* translators: %s: comma-separated list of email addresses. */
		esc_html__( 'Jede Anfrage über das Formular geht an: %s. Das Formular ist ein mailto:-Formular ohne Backend — diese Adresse empfängt alles. Vor dem Launch auf eine Funktionsadresse ändern (parts/contact-form.php).', 'emposo' ),
		'<code>' . esc_html( implode( '</code>, <code>', $addresses ) ) . '</code>'
	);
	echo '</p></div>';
}

/**
 * Content counts against the frozen contract.
 */
function render_content_panel(): void {
	$expected = array(
		CPT_CASE_STUDY => 10,
		CPT_PERSON     => 7,
		'page'         => 31,
	);

	echo '<h2>' . esc_html__( 'Inhalte', 'emposo' ) . '</h2>';
	echo '<table class="widefat striped" style="max-width:48rem"><thead><tr>';
	echo '<th>' . esc_html__( 'Typ', 'emposo' ) . '</th>';
	echo '<th>' . esc_html__( 'Veröffentlicht', 'emposo' ) . '</th>';
	echo '<th>' . esc_html__( 'Erwartet', 'emposo' ) . '</th>';
	echo '</tr></thead><tbody>';

	foreach ( $expected as $type => $target ) {
		$counts = wp_count_posts( $type );
		$live   = isset( $counts->publish ) ? (int) $counts->publish : 0;

		printf(
			'<tr><td><code>%s</code></td><td>%s</td><td>%s</td></tr>',
			esc_html( $type ),
			$live === $target
				? esc_html( (string) $live )
				: '<strong style="color:#b32d2e">' . esc_html( (string) $live ) . '</strong>',
			esc_html( (string) $target )
		);
	}

	foreach ( array( TAX_DISCIPLINE, TAX_INDUSTRY, TAX_OUTCOME ) as $taxonomy ) {
		printf(
			'<tr><td><code>%s</code></td><td>%s</td><td>%s</td></tr>',
			esc_html( $taxonomy ),
			esc_html( term_count( $taxonomy ) ),
			esc_html__( '—', 'emposo' )
		);
	}

	echo '</tbody></table>';

	$import = get_option( 'emposo_last_import' );

	if ( is_array( $import ) && isset( $import['time'] ) ) {
		printf(
			'<p>%s</p>',
			esc_html(
				sprintf(
					/* translators: %s: human-readable time difference. */
					__( 'Letzter Import: vor %s.', 'emposo' ),
					human_time_diff( (int) $import['time'] )
				)
			)
		);
	} else {
		printf(
			'<p>%s <code>wp emposo import all</code></p>',
			esc_html__( 'Kein Import aufgezeichnet. Seeden mit:', 'emposo' )
		);
	}
}

/**
 * Published term count for a taxonomy.
 *
 * A WP_Error comes back from wp_count_terms() for an unregistered taxonomy, so
 * the count cannot simply be cast — on this screen a broken registration should
 * read as unknown, not as zero terms.
 *
 * @param string $taxonomy Taxonomy name.
 */
function term_count( string $taxonomy ): string {
	$count = wp_count_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);

	return is_wp_error( $count ) ? '?' : (string) $count;
}

/**
 * Uploads that have no pre-built renditions.
 *
 * An upload with no manifest asset key has no AVIF or WebP variant, so it is
 * served as the original JPEG — which is how a single editor upload breaks the
 * 200 KB largest-image budget the whole build is measured against. Missing alt
 * text is listed beside it because `wp claude alt-text` fixes exactly that.
 */
function render_media_panel(): void {
	$attachments = get_posts(
		array(
			'post_type'        => 'attachment',
			'post_status'      => 'inherit',
			'post_mime_type'   => 'image',
			'posts_per_page'   => 100,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		)
	);

	$attachments = array_map( 'intval', $attachments );
	$no_variants = array();
	$no_alt      = array();

	foreach ( $attachments as $id ) {
		if ( '' === (string) get_post_meta( $id, '_emposo_asset_key', true ) ) {
			$no_variants[] = $id;
		}

		if ( '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
			$no_alt[] = $id;
		}
	}

	echo '<h2>' . esc_html__( 'Medien', 'emposo' ) . '</h2><ul>';

	printf(
		'<li>%s</li>',
		esc_html(
			sprintf(
				/* translators: %d: number of images. */
				__( '%d Bilder insgesamt.', 'emposo' ),
				count( $attachments )
			)
		)
	);

	printf(
		'<li>%s</li>',
		count( $no_variants ) > 0
			? '<strong>' . esc_html(
				sprintf(
					/* translators: %d: number of images. */
					__( '%d Upload(s) ohne vorgebaute AVIF/WebP-Varianten — werden als JPEG ausgeliefert.', 'emposo' ),
					count( $no_variants )
				)
			) . '</strong>'
			: esc_html__( 'Alle Bilder haben vorgebaute Varianten.', 'emposo' )
	);

	printf(
		'<li>%s</li>',
		count( $no_alt ) > 0
			? '<strong>' . esc_html(
				sprintf(
					/* translators: %d: number of images. */
					__( '%d Bild(er) ohne Alternativtext.', 'emposo' ),
					count( $no_alt )
				)
			) . '</strong> <code>wp claude alt-text ' . esc_html( implode( ' ', array_slice( $no_alt, 0, 5 ) ) ) . ' --user=1</code>'
			: esc_html__( 'Alle Bilder haben Alternativtext.', 'emposo' )
	);

	echo '</ul>';
}

/**
 * Live check of the security headers.
 *
 * Cached for five minutes: this is an outbound request, and an admin screen
 * that makes one on every page load is an admin screen nobody opens twice.
 */
function render_header_panel(): void {
	$check = get_transient( HEADER_CACHE );

	if ( ! is_array( $check ) ) {
		$check = probe_headers();
		set_transient( HEADER_CACHE, $check, 5 * MINUTE_IN_SECONDS );
	}

	echo '<h2>' . esc_html__( 'Sicherheits-Header', 'emposo' ) . '</h2>';

	if ( isset( $check['error'] ) ) {
		printf(
			'<div class="notice notice-info inline"><p>%s</p></div>',
			esc_html( (string) $check['error'] )
		);
	} elseif ( empty( $check['failures'] ) ) {
		printf(
			'<div class="notice notice-success inline"><p>%s</p></div>',
			esc_html__( 'Alle erwarteten Header sind gesetzt, und Link:/X-Pingback fehlen wie vorgesehen.', 'emposo' )
		);
	} else {
		echo '<div class="notice notice-error inline"><ul>';
		foreach ( (array) $check['failures'] as $failure ) {
			printf( '<li>%s</li>', esc_html( (string) $failure ) );
		}
		echo '</ul></div>';
	}

	printf(
		'<form method="post" action="%s"><input type="hidden" name="action" value="%s">',
		esc_url( admin_url( 'admin-post.php' ) ),
		esc_attr( RECHECK_ACTION )
	);
	wp_nonce_field( RECHECK_ACTION );
	printf(
		'<button type="submit" class="button">%s</button></form>',
		esc_html__( 'Erneut prüfen', 'emposo' )
	);
}

/**
 * Fetch the front page and assert the header contract.
 *
 * @return array{failures?: array<int, string>, error?: string, checked?: string}
 */
function probe_headers(): array {
	/*
	 * wp_remote_get(), not vip_safe_wp_remote_get(): the VIP helper is built to
	 * protect a visitor from a slow third party, and it fails soft. Here a
	 * failure must be reported, not swallowed — an unreachable front page is
	 * itself the finding.
	 */
	// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get -- See above: an admin-side request to this site's own front page, where a swallowed failure would read as a pass.
	$response = wp_remote_get(
		home_url( '/' ),
		array(
			'redirection' => 0,
			// phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- An admin-screen request to this site's own front page, cached for five minutes; the default 5s is not enough for a cold render.
			'timeout'     => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array(
			'error' => sprintf(
				/* translators: 1: error message, 2: site URL. */
				__( 'Die Startseite war von hier nicht erreichbar (%1$s). Lokal ist das normal — der Container erreicht sich nicht unter seiner öffentlichen URL. Prüfung per CLI: curl -sI %2$s', 'emposo' ),
				$response->get_error_message(),
				home_url( '/' )
			),
		);
	}

	$headers  = wp_remote_retrieve_headers( $response );
	$failures = array();

	foreach ( REQUIRED_HEADERS as $header => $needle ) {
		$value = (string) $headers[ $header ];

		if ( '' === $value ) {
			/* translators: %s: HTTP header name. */
			$failures[] = sprintf( __( '%s fehlt.', 'emposo' ), $header );
			continue;
		}

		if ( false === strpos( $value, $needle ) ) {
			$failures[] = sprintf(
				/* translators: 1: header name, 2: expected substring, 3: actual value. */
				__( '%1$s enthält nicht "%2$s" (ist: %3$s).', 'emposo' ),
				$header,
				$needle,
				$value
			);
		}
	}

	// script-src must stay strictly same-origin, or the policy is decoration.
	$csp = (string) $headers['content-security-policy'];

	if ( '' !== $csp && preg_match( '/script-src([^;]*)/', $csp, $matches ) && preg_match( '/unsafe-(inline|eval)/', $matches[1] ) ) {
		$failures[] = sprintf(
			/* translators: %s: the script-src directive. */
			__( 'script-src erlaubt unsafe-inline oder unsafe-eval (%s).', 'emposo' ),
			trim( $matches[1] )
		);
	}

	foreach ( FORBIDDEN_HEADERS as $header ) {
		if ( '' !== (string) $headers[ $header ] ) {
			$failures[] = sprintf(
				/* translators: 1: header name, 2: header value. */
				__( '%1$s wird wieder gesendet (%2$s).', 'emposo' ),
				$header,
				(string) $headers[ $header ]
			);
		}
	}

	return array(
		'failures' => $failures,
		'checked'  => (string) wp_remote_retrieve_response_code( $response ),
	);
}

/**
 * Parity and audit status, from the artefacts on disk.
 */
function render_audit_panel(): void {
	echo '<h2>' . esc_html__( 'Messungen', 'emposo' ) . '</h2><ul>';

	/*
	 * WP_CONTENT_DIR, not a path relative to ABSPATH: on a VIP-style deploy the
	 * repository root IS wp-content, and .wp-env.json maps audit-evidence to
	 * the same place locally so this panel is not silently empty in
	 * development — which is exactly where it was, reporting "no baseline"
	 * while the committed baseline sat a directory away, unmounted.
	 */
	$report = read_json( WP_CONTENT_DIR . '/audit-evidence/wp/latest.json' );

	if ( null === $report ) {
		printf(
			'<li>%s <code>npm run audit</code></li>',
			esc_html__( 'Kein WordPress-Audit auf dieser Maschine. Messen mit:', 'emposo' )
		);
	} else {
		$passing = (int) ( $report['passing'] ?? 0 );
		$total   = (int) ( $report['total'] ?? 0 );

		printf(
			'<li>%s</li>',
			esc_html(
				sprintf(
					/* translators: 1: passing routes, 2: total routes, 3: ISO timestamp. */
					__( 'Audit: %1$d/%2$d Routen bestanden (%3$s).', 'emposo' ),
					$passing,
					$total,
					(string) ( $report['generated'] ?? '?' )
				)
			)
		);
	}

	$baseline = read_json( WP_CONTENT_DIR . '/audit-evidence/static-baseline.json' );

	printf(
		'<li>%s</li>',
		null === $baseline
			? esc_html__( 'Keine statische Baseline gefunden.', 'emposo' )
			: esc_html(
				sprintf(
					/* translators: %d: number of routes. */
					__( 'Statische Baseline: %d Routen.', 'emposo' ),
					is_array( $baseline['routes'] ?? null ) ? count( $baseline['routes'] ) : 0
				)
			)
	);

	printf(
		'<li><a href="%s">%s</a></li>',
		esc_url( sprintf( 'https://github.com/%s/actions', REPO ) ),
		esc_html__( 'CI-Läufe auf GitHub', 'emposo' )
	);

	echo '</ul>';
}

/**
 * Tooling state: environment, cache, and the Anthropic credential.
 */
function render_tooling_panel(): void {
	echo '<h2>' . esc_html__( 'Werkzeuge', 'emposo' ) . '</h2><ul>';

	printf(
		'<li>%s <code>%s</code></li>',
		esc_html__( 'Umgebung:', 'emposo' ),
		esc_html( wp_get_environment_type() )
	);

	printf(
		'<li>%s <code>%s</code></li>',
		esc_html__( 'Objekt-Cache:', 'emposo' ),
		esc_html(
			wp_using_ext_object_cache()
				? __( 'persistent', 'emposo' )
				: __( 'nur pro Request (kein Drop-in)', 'emposo' )
		)
	);

	printf(
		'<li>%s <code>%s</code>%s</li>',
		esc_html__( 'Anthropic-Schlüssel:', 'emposo' ),
		esc_html( claude_key_source() ),
		claude_configured() ? '' : ' — <code>wp claude doctor</code>'
	);

	echo '</ul>';
}

/**
 * Read a JSON file, or null.
 *
 * @param string $path Absolute path.
 * @return array<string, mixed>|null
 */
function read_json( string $path ): ?array {
	if ( ! is_readable( $path ) ) {
		return null;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- A build artefact on local disk, never a URL.
	$decoded = json_decode( (string) file_get_contents( $path ), true );

	return is_array( $decoded ) ? $decoded : null;
}
