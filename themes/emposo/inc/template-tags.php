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
 * The 23 icons the reference build inlines; tools/sync-assets.mjs ships
 * exactly these. Listing them keeps the shipped directory honest and makes an
 * accidental reference fail loudly.
 */
const EMPOSO_ICONS = array(
	'brain-ai-line',
	'building-line',
	'check-discount-line',
	'checkbox-list-line',
	'clipboard-check-line',
	'cloud-connect-line',
	'cpu-line',
	'document-paper-line',
	'documents-2-line',
	'factory-line',
	'finance-trend-line',
	'globe-grid-line',
	'handshake-2-line',
	'layers-4-vertical-line',
	'lightbulb-shine-line',
	'map-pin-simple-2-line',
	'molecules-line',
	'reload-2-line',
	'server-line',
	'settings-cog-2-line',
	'shield-lock-line',
	'target-line',
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
	// Its <style> block is dropped, as assemble.mjs brand() does: the strict CSP
	// forbids inline styles, so the logo's rules live in 11-components.css.
	echo trim( (string) preg_replace( '/\s*<style>[\s\S]*?<\/style>/', '', emposo_read_svg( 'brand', $name ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
	echo emposo_icon_svg( $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allowlisted theme-bundled SVG.
}

/**
 * An icon's markup, for the string-building renderers (emposo-core fragments).
 *
 * @param string $name Icon name.
 */
function emposo_icon_svg( string $name ): string {
	if ( ! in_array( $name, EMPOSO_ICONS, true ) ) {
		return '';
	}

	$svg = emposo_read_svg( 'icons', $name );

	if ( '' === $svg ) {
		return '';
	}

	$svg = preg_replace( '/<svg /', '<svg aria-hidden="true" focusable="false" ', $svg, 1 );
	$svg = (string) preg_replace( '/\swidth="72"\sheight="72"/', '', (string) $svg );

	return str_replace(
		array( 'stroke="#E8730E"', 'fill="#E8730E"' ),
		array( 'stroke="currentColor"', 'fill="currentColor"' ),
		$svg
	);
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
 * Print a page field (inc/fields.php): the editor's text or the default.
 *
 * @param string $key Field key, e.g. 'pages/about-us.07'.
 */
function emposo_f( string $key ): void {
	echo \Emposo\Core\Fields\render( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Default is template markup; overrides are kses'd or escaped in render().
}

/**
 * A page image field: the chosen attachment ID or the default manifest key.
 *
 * @param string $key Field key, e.g. 'pages/about-us.img1'.
 * @return int|string
 */
function emposo_f_image( string $key ) {
	return \Emposo\Core\Fields\image( $key );
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

/**
 * The page language, for <html lang> (the route's locale).
 */
function emposo_lang(): string {
	return function_exists( '\\Emposo\\Core\\I18n\\locale' ) ? \Emposo\Core\I18n\locale() : 'de';
}

/**
 * A UI string from the reference dictionary ({{t:key}} in the partials).
 * Returned raw: dictionary values carry entities and inline markup.
 *
 * @param string $key Dictionary key.
 */
function emposo_t( string $key ): string {
	return function_exists( '\\Emposo\\Core\\I18n\\t' ) ? \Emposo\Core\I18n\t( $key ) : '';
}

/**
 * A German site path in the page language ({{href:/path/}} in the partials).
 *
 * @param string $path German path.
 */
function emposo_href( string $path ): string {
	return function_exists( '\\Emposo\\Core\\I18n\\localize_path' ) ? \Emposo\Core\I18n\localize_path( $path ) : $path;
}

/**
 * The language switch ({{LANGSWITCH:slot}}), as assemble.mjs langSwitch()
 * renders it: a disclosure naming the current language, with a menu of both
 * languages (German first), the current one marked. Printed with the leading
 * newline and indent assemble.mjs emits, so the header stays byte-identical.
 *
 * @param string $slot 'header' or 'menu'.
 */
function emposo_lang_switch( string $slot ): void {
	$route = emposo_route();
	$here  = (string) emposo_lang();
	$there = 'en' === $here ? 'de' : 'en';

	// A 404 has no twin page: the switch leads to the other language's home.
	$not_found = 'not_found' === ( $route['objectType'] ?? '' );
	$paths     = array(
		$here  => $not_found ? ( 'en' === $here ? '/en/' : '/' ) : (string) ( $route['url'] ?? '/' ),
		$there => $not_found ? ( 'en' === $there ? '/en/' : '/' ) : (string) ( $route['twinUrl'] ?? ( 'en' === $there ? '/en/' : '/' ) ),
	);
	$names     = array(
		'de' => 'Deutsch',
		'en' => 'English',
	);

	$options = '';
	foreach ( array( 'de', 'en' ) as $lang ) {
		$options .= '<li><a class="lang-switch__option min-h-11" href="' . esc_url( $paths[ $lang ] ) . '" hreflang="' . $lang . '" lang="' . $lang . '"'
			. ( $lang === $here ? ' aria-current="true"' : ' data-lang-option' ) . '>' . $names[ $lang ] . '</a></li>';
	}

	$class = 'header' === $slot ? 'lang-switch max-nav:hidden' : 'lang-switch lang-switch--menu';

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled from escaped parts, an allowlisted icon and dictionary text.
	echo "\n" . ( 'header' === $slot ? '    ' : '        ' ) . '<details class="' . $class . '" data-lang-switch><summary class="lang-switch__button min-h-11"><span class="lang-switch__icon" aria-hidden="true">' . emposo_icon_svg( 'globe-grid-line' ) . '</span><span class="sr-only">' . emposo_t( 'lang.label' ) . ' </span><span>' . $names[ $here ] . '</span></summary><ul class="lang-switch__menu">' . $options . '</ul></details>';
}
