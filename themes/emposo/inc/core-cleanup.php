<?php
/**
 * Stop WordPress emitting markup, CSS and requests the static build does not have.
 *
 * This is not cosmetic. Two of these are correctness issues:
 *
 * 1. Core's global-styles output is INLINE and UNLAYERED. Unlayered CSS beats
 *    every layered rule regardless of source order, so it would outrank the
 *    theme's whole `@layer theme, base, components, utilities` cascade. It is
 *    emitted even without a theme.json, because core merges its own.
 * 2. wp_global_styles_render_svg_filters() injects an <svg> as the first child
 *    of <body>, ahead of the skip link — which breaks "the skip link is the
 *    first focusable element" and costs the accessibility score.
 *
 * Everything else here is head noise, extra requests, or a third-party origin,
 * each of which the parity harness asserts is absent.
 *
 * Populated in phase 6; the hook point exists now so the load order is fixed.
 *
 * @package Emposo
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove core front-end output that the static build does not produce.
 */
function emposo_core_cleanup(): void {
	// Phase 6: the full remove_action/dequeue inventory.
}
add_action( 'after_setup_theme', 'emposo_core_cleanup' );
