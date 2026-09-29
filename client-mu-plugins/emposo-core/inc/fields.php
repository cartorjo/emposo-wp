<?php
/**
 * Page fields (editorial phase 2, owner 2026-09-29): the copy of the page
 * templates, editable per section while the layout stays fixed.
 *
 * The converter tools/fieldify.mjs replaced each text leaf of a template with
 * `emposo_f( 'pages/about-us.07' )` and wrote the original text, as the
 * default, to data/fields/<template>.json. A field prints its default
 * verbatim (so an untouched page is byte-identical to before) or the
 * editor's override:
 *
 * - page store: post meta `_emposo_fields` on the route's post
 *   (key => value), edited in the page's "Seitentexte" box;
 * - global store (the 404 templates): the `emposo_fields` option, edited on
 *   the "Emposo Inhalte" page.
 *
 * An empty override means "use the default": the layout is fixed, so a
 * field cannot remove its element.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Fields;

use WP_Post;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY_EN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const META_KEY   = '_emposo_fields';
const GLOBAL_OPT = 'emposo_fields';
const SEO_TITLE  = '_emposo_seo_title';
const SEO_DESC   = '_emposo_seo_description';

add_action( 'init', __NAMESPACE__ . '\\register_field_meta', 20 );
add_filter( 'emposo_route', __NAMESPACE__ . '\\apply_seo' );

/**
 * Register the page-field store (not in REST; the box is the editor).
 */
function register_field_meta(): void {
	register_post_meta(
		'page',
		META_KEY,
		array(
			'type'          => 'object',
			'single'        => true,
			'default'       => array(),
			'show_in_rest'  => false,
			'auth_callback' => 'Emposo\\Core\\ContentModel\\can_edit_meta',
		)
	);
}

/**
 * A template's field schema.
 *
 * @param string $template Template id, e.g. 'pages/about-us'.
 * @return array<string, mixed>|null
 */
function schema( string $template ): ?array {
	static $cache = array();

	if ( ! array_key_exists( $template, $cache ) ) {
		$file = EMPOSO_CORE_DIR . '/data/fields/' . str_replace( '/', '__', $template ) . '.json';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Local JSON bundled with the plugin.
		$decoded            = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
		$cache[ $template ] = is_array( $decoded ) ? $decoded : null;
	}

	return $cache[ $template ];
}

/**
 * One field definition by key.
 *
 * @param string $key Field key, e.g. 'pages/about-us.07'.
 * @return array<string, string>|null
 */
function definition( string $key ): ?array {
	$template = (string) preg_replace( '/\.[a-z0-9]+$/', '', $key );
	$schema   = schema( $template );

	foreach ( (array) ( $schema['fields'] ?? array() ) as $field ) {
		if ( ( $field['key'] ?? '' ) === $key ) {
			return array_map( 'strval', $field );
		}
	}

	return null;
}

/**
 * The post that stores a route's fields (matched by its contract path).
 *
 * @param string $url Route path, e.g. '/about-us/'.
 */
function route_post_id( string $url ): int {
	static $cache = array();

	if ( ! isset( $cache[ $url ] ) ) {
		$ids           = get_posts(
			array(
				'post_type'        => array( 'page', CPT_CASE_STUDY, CPT_CASE_STUDY_EN ),
				'post_status'      => array( 'publish', 'private', 'draft' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'meta_key'         => '_emposo_source_slug', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One indexed lookup per request, cached.
				'meta_value'       => $url, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- As above.
			)
		);
		$cache[ $url ] = $ids ? (int) $ids[0] : 0;
	}

	return $cache[ $url ];
}

/**
 * The stored overrides of a store.
 *
 * @param string $store   'page' or 'global'.
 * @param int    $post_id Page ID for the page store.
 * @return array<string, string>
 */
function overrides( string $store, int $post_id ): array {
	$value = 'global' === $store ? get_option( GLOBAL_OPT, array() ) : ( $post_id > 0 ? get_post_meta( $post_id, META_KEY, true ) : array() );

	return is_array( $value ) ? array_map( 'strval', $value ) : array();
}

/**
 * The inline tags an html field may carry.
 *
 * @return array<string, array<string, bool>>
 */
function allowed_inline(): array {
	return array(
		'em'     => array( 'class' => true ),
		'strong' => array( 'class' => true ),
		'br'     => array(),
		'span'   => array( 'class' => true ),
		'a'      => array(
			'href'  => true,
			'class' => true,
		),
	);
}

/**
 * Sanitise an editor's value for a field type.
 *
 * @param string $type  'text' or 'html'.
 * @param string $value Raw value (unslashed).
 */
function sanitize_value( string $type, string $value ): string {
	$value = trim( $value );

	return 'html' === $type ? wp_kses( $value, allowed_inline() ) : sanitize_textarea_field( $value );
}

/**
 * Whether an editor's value is the default (compared as visible text).
 *
 * @param string $value    Sanitised value.
 * @param string $fallback Default markup.
 */
function is_default( string $value, string $fallback ): bool {
	$normal = static function ( string $text ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	};

	return '' === $value || $normal( $value ) === $normal( $fallback );
}

/**
 * The store and post of the current request for a template.
 *
 * @param array<string, mixed> $schema Template schema.
 * @return array{0: string, 1: int}
 */
function current_store( array $schema ): array {
	$store = (string) ( $schema['store'] ?? 'page' );
	if ( 'global' === $store ) {
		return array( 'global', 0 );
	}

	$route = function_exists( 'emposo_route' ) ? emposo_route() : array();

	return array( 'page', route_post_id( (string) ( $route['url'] ?? '' ) ) );
}

/**
 * A text field's HTML: the override, or the default verbatim.
 *
 * @param string $key Field key.
 */
function render( string $key ): string {
	$field = definition( $key );
	if ( null === $field ) {
		return '';
	}

	$schema                  = (array) schema( (string) preg_replace( '/\.[a-z0-9]+$/', '', $key ) );
	list( $store, $post_id ) = current_store( $schema );
	$value                   = overrides( $store, $post_id )[ $key ] ?? '';

	if ( '' === $value ) {
		return $field['default'];
	}

	// Stored values were sanitised on save; escape again on output (defence in depth).
	return 'html' === $field['type'] ? wp_kses( $value, allowed_inline() ) : \Emposo\Core\escape_static( $value );
}

/**
 * An image field: the chosen attachment ID, or the default manifest key.
 *
 * @param string $key Field key.
 * @return int|string
 */
function image( string $key ) {
	$field = definition( $key );
	if ( null === $field ) {
		return '';
	}

	$schema                  = (array) schema( (string) preg_replace( '/\.[a-z0-9]+$/', '', $key ) );
	list( $store, $post_id ) = current_store( $schema );
	$chosen                  = (int) ( overrides( $store, $post_id )[ $key ] ?? 0 );

	return $chosen > 0 && wp_attachment_is_image( $chosen ) ? $chosen : $field['default'];
}

/**
 * The templates a route renders, from the route contract.
 *
 * @param string $url Route path.
 * @return string[]
 */
function route_templates( string $url ): array {
	foreach ( function_exists( 'emposo_route_contract' ) ? emposo_route_contract() : array() as $route ) {
		if ( ( $route['url'] ?? '' ) === $url && 'parts' === ( $route['body']['kind'] ?? '' ) ) {
			return array_map( 'strval', (array) ( $route['body']['parts'] ?? array() ) );
		}
	}

	return array();
}

/**
 * The page a WP_Post stands for in the contract.
 *
 * @param WP_Post $post A page.
 */
function route_of( WP_Post $post ): string {
	return (string) get_post_meta( $post->ID, '_emposo_source_slug', true );
}

/**
 * Apply the editors' SEO title and description to a route record.
 *
 * The head block (canonical, OG/Twitter, JSON-LD) is a prebuilt string from
 * the contract, so the old title and description are replaced inside it in
 * both encodings they appear in: attribute-escaped and JSON.
 *
 * @param array<string, mixed> $route Route record.
 * @return array<string, mixed>
 */
function apply_seo( array $route ): array {
	$post_id = route_post_id( (string) ( $route['url'] ?? '' ) );
	if ( $post_id <= 0 ) {
		return $route;
	}

	$head = (string) ( $route['headMeta'] ?? '' );
	foreach ( array(
		'title'       => SEO_TITLE,
		'description' => SEO_DESC,
	) as $field => $meta_key ) {
		$new = trim( (string) get_post_meta( $post_id, $meta_key, true ) );
		$old = (string) ( $route[ $field ] ?? '' );
		if ( '' === $new || $new === $old ) {
			continue;
		}

		$route[ $field ] = $new;
		if ( '' !== $old ) {
			$json = static function ( string $text ): string {
				return substr( (string) wp_json_encode( $text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), 1, -1 );
			};
			$head = str_replace(
				array( '"' . esc_attr( $old ) . '"', '"' . $old . '"', '"' . $json( $old ) . '"' ),
				array( '"' . esc_attr( $new ) . '"', '"' . esc_attr( $new ) . '"', '"' . $json( $new ) . '"' ),
				$head
			);
		}
	}
	$route['headMeta'] = $head;

	return $route;
}
