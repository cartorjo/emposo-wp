<?php
/**
 * Stylesheet and script registration.
 *
 * Two contracts are enforced here rather than left to enqueue order:
 *
 * 1. The JS load order. 00-core.js publishes window.__onReady / __mq / __lenis
 *    and every other script early-returns without it, while 00-core itself
 *    reads window.Lenis. That is expressed as real $deps so the order cannot be
 *    broken by reordering enqueue calls.
 * 2. defer on ALL scripts. WP_Scripts::get_eligible_loading_strategy()
 *    intersects a script's requested strategy with its dependents', so a single
 *    script missing 'strategy' => 'defer' silently downgrades Lenis and
 *    00-core to render-blocking in <head> — regressing LCP and TBT on every
 *    page with no error anywhere.
 *
 * Populated in phase 5.
 *
 * @package Emposo
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache-busting version for a theme-relative asset.
 *
 * Uses filemtime rather than the theme version: a version bump is manual, and
 * a forgotten bump serves stale CSS to returning visitors. filemtime is
 * over-eager after a checkout and never under-eager, which is the safe
 * direction. The parity harness normalises `?ver=` away.
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
	// Phase 5: fonts + site CSS, Lenis + the five scripts, per-route matrix.
}
add_action( 'wp_enqueue_scripts', 'emposo_enqueue_assets' );
