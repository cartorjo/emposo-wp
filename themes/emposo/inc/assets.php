<?php
/**
 * Stylesheet and script registration.
 *
 * Two contracts are enforced structurally here rather than left to enqueue
 * order:
 *
 * 1. Load order. 00-core.js publishes window.__onReady / __mq / __lenis and
 *    every other script early-returns without it, while 00-core itself reads
 *    window.Lenis. Expressed as real $deps so reordering enqueue calls cannot
 *    break it.
 * 2. defer on every script. WP_Scripts::get_eligible_loading_strategy()
 *    intersects a script's requested strategy with its DEPENDENTS', so one
 *    script missing 'strategy' => 'defer' silently downgrades Lenis and
 *    00-core to render-blocking in <head> — regressing LCP and TBT on every
 *    page, with no error anywhere. A WP_DEBUG assertion catches it.
 *
 * @package Emposo
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The five front-end scripts, mapped to handles.
 *
 * Keys are the filenames the route contract lists, so the per-page matrix is
 * read from the contract rather than restated here.
 */
const EMPOSO_SCRIPTS = array(
	'00-core'         => 'emposo-core',
	'01-header'       => 'emposo-header',
	'02-intent-links' => 'emposo-intent',
	'06-work'         => 'emposo-work',
	'07-countup'      => 'emposo-countup',
);

/** Handles that every page loads, in dependency order. */
const EMPOSO_BASE_SCRIPTS = array( '00-core', '01-header' );

/**
 * Cache-busting version for a theme-relative asset.
 *
 * Uses filemtime rather than the theme version: a version bump is manual, and
 * a forgotten bump serves stale CSS to returning visitors — the worst silent
 * failure available. filemtime is over-eager after a checkout and never
 * under-eager, which is the safe direction. The parity harness normalises
 * `?ver=` away.
 *
 * @param string $relative_path Path relative to the theme root, with a leading slash.
 */
function emposo_asset_version( string $relative_path ): string {
	static $versions = array();

	if ( isset( $versions[ $relative_path ] ) ) {
		return $versions[ $relative_path ];
	}

	$absolute = EMPOSO_DIR . $relative_path;
	$mtime    = file_exists( $absolute ) ? filemtime( $absolute ) : false;

	$versions[ $relative_path ] = false === $mtime ? EMPOSO_VERSION : (string) $mtime;

	return $versions[ $relative_path ];
}

/**
 * Register front-end styles and scripts.
 */
function emposo_enqueue_assets(): void {
	/*
	 * site.css depends on 00-fonts.css so the order is structural rather than
	 * incidental to enqueue sequence, matching the static head.
	 */
	wp_enqueue_style(
		'emposo-fonts',
		EMPOSO_URI . '/assets/css/00-fonts.css',
		array(),
		emposo_asset_version( '/assets/css/00-fonts.css' )
	);

	wp_enqueue_style(
		'emposo-site',
		EMPOSO_URI . '/assets/css/site.css',
		array( 'emposo-fonts' ),
		emposo_asset_version( '/assets/css/site.css' )
	);

	// Head, not footer: the static build loads all six deferred in <head>, and
	// that is the configuration that measured TBT 0 and CLS 0.
	$defer = array(
		'in_footer' => false,
		'strategy'  => 'defer',
	);

	wp_enqueue_script(
		'emposo-lenis',
		EMPOSO_URI . '/assets/vendor/lenis.min.js',
		array(),
		emposo_asset_version( '/assets/vendor/lenis.min.js' ),
		$defer
	);

	$route   = emposo_route();
	$wanted  = (array) ( $route['scripts'] ?? EMPOSO_BASE_SCRIPTS );
	$handles = array( '00-core' => array( 'emposo-lenis' ) );

	foreach ( $wanted as $file ) {
		if ( ! isset( EMPOSO_SCRIPTS[ $file ] ) ) {
			continue;
		}

		// Everything except 00-core depends on it, because each opens with
		// `if (!window.__onReady) return;`.
		$deps = $handles[ $file ] ?? array( 'emposo-core' );

		wp_enqueue_script(
			EMPOSO_SCRIPTS[ $file ],
			EMPOSO_URI . '/assets/js/' . $file . '.js',
			$deps,
			emposo_asset_version( '/assets/js/' . $file . '.js' ),
			$defer
		);
	}
}
add_action( 'wp_enqueue_scripts', 'emposo_enqueue_assets' );

/**
 * Restore the static build's note above the vendored Lenis script.
 *
 * Attached to the tag itself rather than hooked into wp_head at a priority
 * between wp_print_styles (8) and wp_print_head_scripts (9): same-priority
 * ordering depends on registration order, which core controls, whereas
 * filtering the tag places the comment deterministically.
 *
 * The note is worth keeping. It records the reason Lenis is vendored at all —
 * no visitor request may leave this host — which is the constraint that also
 * rules out CDN assets, Google Fonts, Gravatar and third-party analytics.
 *
 * @param string $tag    Script tag.
 * @param string $handle Script handle.
 * @return string
 */
function emposo_annotate_lenis( string $tag, string $handle ): string {
	if ( 'emposo-lenis' !== $handle ) {
		return $tag;
	}

	return "<!-- Lenis is vendored locally; no visitor request leaves this host. -->\n" . $tag;
}
add_filter( 'script_loader_tag', 'emposo_annotate_lenis', 5, 2 );

/**
 * Assert every printed script is deferred.
 *
 * The strategy-intersection rule makes this failure silent and expensive, so it
 * is worth a runtime check in development.
 *
 * @param string $tag    Script tag.
 * @param string $handle Script handle.
 * @return string
 */
function emposo_assert_defer( string $tag, string $handle ): string {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! is_admin() && false === strpos( $tag, ' defer' ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Development-only assertion.
		trigger_error(
			sprintf( 'Emposo: script "%s" printed without defer; its dependencies are now render-blocking.', esc_html( $handle ) ),
			E_USER_WARNING
		);
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'emposo_assert_defer', 10, 2 );

/**
 * Assert the front-end asset queues contain only what this theme registered.
 *
 * "No plugins, no core stylesheets" is a policy, and policies decay. This turns
 * it into a test: anything unexpected in the queue means core injected a
 * stylesheet — which would be UNLAYERED and outrank the whole @layer cascade —
 * or a plugin was activated.
 */
function emposo_assert_queues(): void {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || is_admin() ) {
		return;
	}

	$expected_styles = array( 'emposo-fonts', 'emposo-site' );
	$actual_styles   = wp_styles()->queue;

	$unexpected = array_diff( $actual_styles, $expected_styles );
	if ( $unexpected ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Development-only assertion.
		trigger_error(
			sprintf( 'Emposo: unexpected front-end stylesheet(s): %s', esc_html( implode( ', ', $unexpected ) ) ),
			E_USER_WARNING
		);
	}
}
add_action( 'wp_print_styles', 'emposo_assert_queues', 1 );
