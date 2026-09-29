<?php
/**
 * The locale layer, mirroring reference/static content/i18n.mjs.
 *
 * German is the source language; the American English twin of every page
 * lives under /en/. The UI strings, the English route map and the English
 * case-study slugs come from data/i18n.json, which tools/export-content.mjs
 * writes from the reference, so t() and localize_path() here are table
 * lookups with no logic to keep in sync with assemble.mjs.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\I18n;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DEFAULT_LOCALE = 'de';

/**
 * The exported tables: strings, paths, caseSlugs.
 *
 * @return array<string, mixed>
 */
function tables(): array {
	static $tables = null;

	if ( null === $tables ) {
		$file = EMPOSO_CORE_DIR . '/data/i18n.json';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local JSON bundled with the plugin.
		$decoded = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
		$tables  = is_array( $decoded ) ? $decoded : array();
	}

	return $tables;
}

/**
 * The locale of the page being rendered: the route's, else German.
 */
function locale(): string {
	if ( function_exists( 'emposo_route' ) ) {
		$locale = (string) ( emposo_route()['locale'] ?? DEFAULT_LOCALE );

		return 'en' === $locale ? 'en' : DEFAULT_LOCALE;
	}

	return DEFAULT_LOCALE;
}

/**
 * A UI string, exactly as the reference's t() returns it (HTML allowed: the
 * dictionary carries entities and inline markup such as <br> and <em>).
 *
 * @param string      $key    Dictionary key.
 * @param string|null $locale Locale; the current page's by default.
 */
function t( string $key, ?string $locale = null ): string {
	$locale  = $locale ?? locale();
	$strings = (array) ( tables()['strings'] ?? array() );
	$value   = $strings[ $locale ][ $key ] ?? $strings[ DEFAULT_LOCALE ][ $key ] ?? null;
	if ( ! is_string( $value ) ) {
		return '';
	}

	// An editor's override (inc/editorial/texts.php), sanitised on save.
	// Plain strings are escaped, because t() also fills attributes.
	$override = override( $key, $locale );
	if ( '' !== $override ) {
		return false !== strpos( $value, '<' )
			? wp_kses( $override, \Emposo\Core\Fields\allowed_inline() )
			: \Emposo\Core\escape_static( $override );
	}

	return $value;
}

/**
 * An editor's override of a UI string, or ''.
 *
 * @param string $key    Dictionary key.
 * @param string $locale Locale.
 */
function override( string $key, string $locale ): string {
	static $cache = array();

	if ( ! isset( $cache[ $locale ] ) ) {
		$stored           = get_option( 'en' === $locale ? 'emposo_strings_en' : 'emposo_strings', array() );
		$cache[ $locale ] = is_array( $stored ) ? array_map( 'strval', $stored ) : array();
	}

	return $cache[ $locale ][ $key ] ?? '';
}

/**
 * A site-relative href in the given locale (the reference's localizePath()).
 * Fragments and queries are kept; assets, mailto: and external links are
 * returned unchanged.
 *
 * @param string      $href   German path, e.g. '/kontakt/' or '/portfolio/#ai-daten'.
 * @param string|null $locale Locale; the current page's by default.
 */
function localize_path( string $href, ?string $locale = null ): string {
	$locale = $locale ?? locale();

	if ( DEFAULT_LOCALE === $locale || 0 !== strpos( $href, '/' ) || preg_match( '#^/(assets|css|js)/#', $href ) ) {
		return $href;
	}

	if ( ! preg_match( '#^([^?\#]*)(.*)$#', $href, $parts ) ) {
		return $href;
	}

	$paths = (array) ( tables()['paths'] ?? array() );
	if ( isset( $paths[ $parts[1] ] ) ) {
		return $paths[ $parts[1] ] . $parts[2];
	}

	if ( preg_match( '#^/case-studies/([^/]+)/$#', $parts[1], $study ) ) {
		return '/en/case-studies/' . case_slug( $study[1] ) . '/' . $parts[2];
	}

	return $href;
}

/**
 * A case study's English slug (its German slug when unmapped).
 *
 * @param string $german German slug.
 */
function case_slug( string $german ): string {
	$slugs = (array) ( tables()['caseSlugs'] ?? array() );

	return (string) ( $slugs[ $german ] ?? $german );
}
