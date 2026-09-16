<?php
/**
 * Template tags replacing the static build's inline-SVG and image tokens.
 *
 * @package Emposo
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brand SVGs that may be inlined.
 *
 * An allowlist rather than a path built from input: these are read off disk and
 * printed unescaped, so the set of readable files must be closed.
 */
const EMPOSO_BRAND_SVGS = array( 'emposo-logo-neu26' );

/**
 * Icons that may be inlined.
 *
 * Eight of the vendor set's 529 are used. Listing them keeps the shipped
 * directory honest and makes an accidental reference fail loudly.
 */
const EMPOSO_ICONS = array(
	'building-line',
	'finance-trend-line',
	'layers-4-vertical-line',
	'map-pin-simple-2-line',
	'molecules-line',
	'reload-2-line',
	'settings-cog-2-line',
	'users-group-line',
);

/**
 * Read an SVG from the theme, once per request.
 *
 * @param string $subdir Directory under assets/.
 * @param string $name   File basename without extension.
 */
function emposo_read_svg( string $subdir, string $name ): string {
	static $cache = array();

	$key = $subdir . '/' . $name;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$file = EMPOSO_DIR . '/assets/' . $subdir . '/' . $name . '.svg';

	if ( ! file_exists( $file ) ) {
		$cache[ $key ] = '';

		return '';
	}

	// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a theme-bundled SVG from an allowlisted name; no remote fetch.
	$cache[ $key ] = trim( (string) file_get_contents( $file ) );

	return $cache[ $key ];
}

/**
 * Inline a brand SVG.
 *
 * Must stay inline rather than become an <img>: the logo's ink is currentColor,
 * so a single file renders navy in the header and white in the footer, and its
 * "The Outcome Factory" tagline is live Roboto text an <img> could not load.
 *
 * @param string $name Brand SVG name.
 */
function emposo_brand( string $name ): void {
	if ( ! in_array( $name, EMPOSO_BRAND_SVGS, true ) ) {
		return;
	}

	// Printed unescaped by necessity — it is SVG markup from an allowlisted
	// file inside the theme, not user input.
	echo emposo_read_svg( 'brand', $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Inline an icon, applying the static build's three rewrites.
 *
 * Verbatim from assemble.mjs: mark it decorative, drop the fixed 72x72
 * dimensions so CSS can size it, and swap the vendor's hardcoded #E8730E for
 * currentColor so it inherits the surrounding text colour.
 *
 * @param string $name Icon name.
 */
function emposo_icon( string $name ): void {
	if ( ! in_array( $name, EMPOSO_ICONS, true ) ) {
		return;
	}

	$svg = emposo_read_svg( 'icons', $name );

	if ( '' === $svg ) {
		return;
	}

	$svg = preg_replace( '/<svg /', '<svg aria-hidden="true" focusable="false" ', $svg, 1 );
	$svg = (string) preg_replace( '/\swidth="72"\sheight="72"/', '', (string) $svg );
	$svg = str_replace(
		array( 'stroke="#E8730E"', 'fill="#E8730E"' ),
		array( 'stroke="currentColor"', 'fill="currentColor"' ),
		$svg
	);

	echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allowlisted theme-bundled SVG.
}

/**
 * Print a responsive <picture>.
 *
 * @param int|string $image    Attachment ID or manifest key.
 * @param string     $size_key One of the named sizes.
 * @param bool       $priority True for the LCP image.
 */
function emposo_the_picture( $image, string $size_key = 'default', bool $priority = false ): void {
	// Markup assembled and escaped inside the helper.
	echo \Emposo\Core\Images\picture( $image, $size_key, $priority ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Print a shared content fragment.
 *
 * The static build's 13 `<!-- content:name -->` includes. Implemented as
 * string-returning helpers rather than template parts because they compose —
 * projectPage() calls projectCards() calls picture() — and because the static
 * renderers emit no inter-tag whitespace, which an indented PHP template would
 * introduce into flex and grid rows.
 *
 * @param string $name Fragment name.
 */
function emposo_fragment( string $name ): void {
	if ( ! function_exists( '\\Emposo\\Core\\Fragments\\render' ) ) {
		return;
	}

	echo \Emposo\Core\Fragments\render( $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled and escaped by the fragment renderer.
}
