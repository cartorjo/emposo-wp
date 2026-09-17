<?php
/**
 * Per-request route context.
 *
 * The static build drove titles, descriptions, body classes, the per-page
 * script list and the three-state aria-current model from one manifest entry.
 * This resolves the equivalent record for the current request, so the ported
 * templates keep reading a single source rather than each re-deriving state.
 *
 * @package Emposo
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The route record for this request.
 *
 * Matched by path rather than post ID, so it survives a reimport and works for
 * the 404 case where there is no queried object at all.
 *
 * @return array<string, mixed>
 */
function emposo_route(): array {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$contract = emposo_route_contract();
	$path     = emposo_current_path();

	foreach ( $contract as $route ) {
		if ( ( $route['url'] ?? '' ) === $path ) {
			$cache = $route;

			return $cache;
		}
	}

	// Unmatched paths take the contract's 404 record only when the request is
	// a real 404; the contract carries that record under the sentinel object
	// type rather than a URL. A published object outside the contract — the
	// pre-existing legal pages — falls through to the synthesized record
	// below, whose missing body kind routes emposo_the_body() to
	// parts/fallback.php instead of presenting 404 content at HTTP 200.
	if ( is_404() ) {
		foreach ( $contract as $route ) {
			if ( 'not_found' === ( $route['objectType'] ?? '' ) ) {
				$cache = $route;

				return $cache;
			}
		}
	}

	$cache = array(
		'url'        => $path,
		'title'      => '',
		'bodyClass'  => 'subpage wrap-anywhere',
		'scripts'    => array( '00-core', '01-header' ),
		'nav'        => 'none',
		'navGroup'   => null,
		'navExact'   => true,
		'objectType' => 'page',
	);

	return $cache;
}

/**
 * The frozen route contract.
 *
 * @return array<int, array<string, mixed>>
 */
function emposo_route_contract(): array {
	static $routes = null;

	if ( null !== $routes ) {
		return $routes;
	}

	$path = EMPOSO_CORE_DIR . '/data/routes.json';

	if ( ! file_exists( $path ) ) {
		$routes = array();

		return $routes;
	}

	// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Local JSON bundled with the plugin.
	$decoded = json_decode( (string) file_get_contents( $path ), true );
	$routes  = is_array( $decoded ) && isset( $decoded['routes'] ) ? $decoded['routes'] : array();

	return $routes;
}

/**
 * The requested path, normalised to a leading and trailing slash.
 */
function emposo_current_path(): string {
	if ( is_front_page() ) {
		return '/';
	}

	$queried = get_queried_object();

	if ( $queried instanceof WP_Post ) {
		$permalink = (string) get_permalink( $queried );
		$path      = (string) wp_parse_url( $permalink, PHP_URL_PATH );

		return '' === $path ? '/' : $path;
	}

	// Fall back to the request itself, which is what 404s and unusual query
	// shapes need.
	$requested = isset( $_SERVER['REQUEST_URI'] )
		? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
		: '/';

	$path = (string) wp_parse_url( $requested, PHP_URL_PATH );

	return '' === $path ? '/' : trailingslashit( $path );
}

/**
 * Document title, from the contract rather than core's generator.
 *
 * The contract stores the full title the static build emitted, including the
 * ' | Emposo' suffix and the per-type middle segment ('| Case Study |',
 * '| Expertise |'). Reproducing that with document_title_parts filters would
 * mean re-deriving rules the contract already states.
 */
function emposo_document_title(): string {
	$route = emposo_route();
	$title = (string) ( $route['title'] ?? '' );

	if ( '' !== $title ) {
		return $title;
	}

	return wp_get_document_title();
}

/**
 * Meta description, from the contract.
 */
function emposo_meta_description(): string {
	$route       = emposo_route();
	$description = (string) ( $route['description'] ?? '' );

	if ( '' !== $description ) {
		return $description;
	}

	$queried = get_queried_object();

	return $queried instanceof WP_Post ? (string) $queried->post_excerpt : '';
}

/**
 * The exact body class string, and nothing else.
 *
 * Core's body_class() would add home, blog, page-id-N, wp-theme-emposo and
 * more — a DOM delta on every page and a selector risk, since the hand-written
 * CSS keys off `subpage`. The contract states the full intended value, so this
 * returns it verbatim.
 */
function emposo_body_class(): string {
	$route = emposo_route();

	return (string) ( $route['bodyClass'] ?? 'subpage wrap-anywhere' );
}

/**
 * Three-state active-nav model, replacing {{CUR:}} and {{CURATTR:}}.
 *
 * Lifted from assemble.mjs's stampNav():
 *   exact match, navExact !== false -> aria-current="page"
 *   exact match, navExact === false -> aria-current="true"   (ancestor pages)
 *   navGroup match                  -> aria-current="true"   (megamenu group)
 *
 * @param string $key Nav key from the template.
 * @return array{cur: bool, attr: string}
 */
function emposo_nav_state( string $key ): array {
	$route = emposo_route();

	$nav       = (string) ( $route['nav'] ?? 'none' );
	$nav_group = (string) ( $route['navGroup'] ?? '' );
	$exact     = false !== ( $route['navExact'] ?? true );

	if ( $key === $nav ) {
		return array(
			'cur'  => true,
			'attr' => $exact ? ' aria-current="page"' : ' aria-current="true"',
		);
	}

	if ( '' !== $nav_group && $key === $nav_group ) {
		return array(
			'cur'  => true,
			'attr' => ' aria-current="true"',
		);
	}

	return array(
		'cur'  => false,
		'attr' => '',
	);
}

/**
 * Emit a payload when this nav key is current. Replaces {{CUR:key: payload}}.
 *
 * @param string $key     Nav key.
 * @param string $payload Literal payload, typically ' is-current'.
 */
function emposo_cur( string $key, string $payload ): void {
	if ( emposo_nav_state( $key )['cur'] ) {
		echo esc_attr( $payload );
	}
}

/**
 * Emit the aria-current attribute for this nav key. Replaces {{CURATTR:key}}.
 *
 * @param string $key Nav key.
 */
function emposo_curattr( string $key ): void {
	$attr = emposo_nav_state( $key )['attr'];

	if ( '' === $attr ) {
		return;
	}

	// A fixed attribute string from a closed set of three values, so printing
	// it whole is safe and keeps the output byte-identical to the static build.
	echo ' aria-current="' . esc_attr( trim( str_replace( array( ' aria-current="', '"' ), '', $attr ) ) ) . '"';
}

/**
 * Render this route's <main> content.
 *
 * Every root template dispatches through here, because the mapping from a route
 * to its body is data in the route contract rather than a filename convention.
 * That keeps the homepage's section ORDER authoritative — it is manifest order,
 * not filename order, and 03-models renders sixth — and lets a route mix
 * directories, as /portfolio/ does by appending the FAQ section to its page
 * body.
 */
function emposo_the_body(): void {
	$route = emposo_route();
	$body  = (array) ( $route['body'] ?? array() );
	$kind  = (string) ( $body['kind'] ?? '' );

	switch ( $kind ) {
		case 'parts':
			/*
			 * Each part is TRIMMED and the parts are joined with a blank line,
			 * because that is exactly what assemble.mjs did: it read each
			 * source with .trim() and joined an array with '\n\n'. Letting
			 * get_template_part() echo directly instead emits each file's
			 * trailing newline, which put whitespace between </form> and its
			 * closing </div> where the static build has none — a real
			 * difference, since whitespace between nodes becomes a text node.
			 */
			$rendered = array();

			foreach ( (array) ( $body['parts'] ?? array() ) as $part ) {
				// Names come from the frozen contract, but constrain them
				// anyway: this value reaches a file lookup.
				if ( ! preg_match( '#^(pages|sections)/[a-z0-9-]+$#', (string) $part ) ) {
					continue;
				}
				$rendered[] = emposo_part_html( 'parts/' . $part );
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output, escaped at its own point of use.
			echo implode( "\n\n", $rendered );
			break;

		case 'project':
		case 'industry':
		case 'discipline':
			/*
			 * The three data-driven detail-page renderers, which together serve
			 * 22 of the 41 routes. Implemented as string-returning helpers
			 * rather than template parts because they compose — a project page
			 * calls the card renderer, which calls the picture helper — and
			 * because the static renderers emit no inter-tag whitespace, which
			 * an indented template would introduce into flex and grid rows.
			 */
			if ( function_exists( '\\Emposo\\Core\\Fragments\\render_detail' ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled and escaped by the renderer.
				echo \Emposo\Core\Fragments\render_detail( $kind, (string) ( $body['slug'] ?? '' ) );
			}
			break;

		default:
			get_template_part( 'parts/fallback' );
			break;
	}
}

/**
 * Capture a template part's output, trimmed.
 *
 * The static assembler read every partial and page body with .trim(), so a
 * file's own trailing newline never reached the document. get_template_part()
 * echoes directly and does reach it, which shows up as whitespace between two
 * elements that were adjacent — and whitespace between nodes is a text node,
 * so it can change how a flex or grid row lays out.
 *
 * @param string $slug Template part slug, relative to the theme root.
 */
function emposo_part_html( string $slug ): string {
	ob_start();
	get_template_part( $slug );

	return trim( (string) ob_get_clean() );
}
