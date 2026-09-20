<?php
/**
 * Image handling.
 *
 * The media decision in one place: the 22 photographs are imported into the
 * media library so editors own them, AND the hand-built AVIF/WebP pyramid is
 * kept, because WordPress core cannot reliably generate AVIF subsizes (it
 * depends on the host's Imagick build) and never emits <picture> at all. A pure
 * media-library import would silently drop the AVIF tier — most of the
 * page-weight win — and lose <source type> negotiation.
 *
 * So the variant set travels as attachment meta (`_emposo_variants`, the
 * manifest's own array) and emposo_picture() reads it. The tiers are NOT a
 * uniform pair: each image has a 640 tier plus a per-image large tier
 * (800/848/900/1085/1122/1600, never upscaled), and JPEG exists only at the
 * large width — so each <img> carries a single-width src plus explicit
 * dimensions. Any hardcoded width pair or global `sizes` filter would be wrong.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Images;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The five distinct `sizes` strings the site actually uses.
 *
 * Named rather than inlined because each corresponds to a real layout: the
 * static build emits exactly these, and getting one wrong changes which
 * candidate the browser picks without changing the markup's shape.
 */
const SIZES = array(
	'default'        => '(max-width: 700px) 100vw, 50vw',
	'hero'           => '(max-width: 900px) 100vw, 65vw',
	'tile'           => '(max-width: 600px) 100vw, (max-width: 1000px) 50vw, 33vw',
	'detail'         => '(max-width: 900px) 100vw, 50vw',
	'portrait'       => '(max-width: 700px) 100vw, 25vw',
	'portrait_small' => '(max-width: 700px) 50vw, 20vw',
);

add_action( 'after_setup_theme', __NAMESPACE__ . '\\register_sizes' );
add_filter( 'intermediate_image_sizes_advanced', __NAMESPACE__ . '\\prune_subsizes' );
add_filter( 'big_image_size_threshold', __NAMESPACE__ . '\\big_image_threshold' );

/**
 * Register only the sizes we use.
 *
 * These matter for images an editor uploads later; the 22 imported ones use
 * their pre-built files and never touch a registered size.
 */
function register_sizes(): void {
	add_theme_support( 'post-thumbnails' );

	// Width-constrained, never cropped: the design relies on the source aspect.
	add_image_size( 'emposo-640', 640, 0, false );
	add_image_size( 'emposo-1600', 1600, 0, false );
}

/**
 * Stop WordPress generating subsizes nothing renders.
 *
 * Without this, every upload produces five extra files (medium, large,
 * thumbnail, medium_large, 1536x1536) — 110 files across the 22 imported
 * photographs alone, none of which any template references.
 *
 * thumbnail and medium are kept: the admin media library and the featured-image
 * picker use them, so removing those makes the editor experience worse for no
 * front-end gain.
 *
 * @param array<string, mixed> $sizes Sizes to generate.
 * @return array<string, mixed>
 */
function prune_subsizes( array $sizes ): array {
	unset( $sizes['medium_large'], $sizes['1536x1536'], $sizes['2048x2048'], $sizes['large'] );

	return $sizes;
}

/**
 * Cap the "big image" threshold at the largest width the design uses.
 *
 * Default is 2560, which produces a `-scaled` duplicate of every large upload
 * that nothing references.
 */
function big_image_threshold(): int {
	return 1600;
}

/**
 * Resolve an attachment ID from either an ID or a manifest key.
 *
 * Templates refer to images by the key the static build used ('hero-flow'), so
 * accepting both keeps the ported markup readable and survives the eventual
 * switch to editor-chosen images.
 *
 * @param int|string $image Attachment ID or manifest key.
 */
function resolve_id( $image ): int {
	if ( is_int( $image ) || ctype_digit( (string) $image ) ) {
		return (int) $image;
	}

	$key = sanitize_key( (string) $image );

	// Cached: a page renders up to 13 images, and this would otherwise be a
	// meta query per image on every uncached request.
	$cache_key = 'emposo_asset_id_' . $key;
	$cached    = wp_cache_get( $cache_key, 'emposo' );
	if ( false !== $cached ) {
		return (int) $cached;
	}

	$found = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_key'       => '_emposo_asset_key',
			'meta_value'     => $key,
			// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	$id = $found ? (int) $found[0] : 0;
	wp_cache_set( $cache_key, $id, 'emposo', HOUR_IN_SECONDS );

	return $id;
}

/**
 * Emit the responsive <picture> the static build emits.
 *
 * Never wp_get_attachment_image(): core cannot produce a <picture> at all, so
 * the AVIF and WebP sources would be lost; it also computes its own `sizes`,
 * injects loading/decoding/fetchpriority through
 * wp_get_loading_optimization_attributes(), and rewrites filenames to
 * `-{w}x{h}`. Four simultaneous divergences, and losing AVIF alone pushes the
 * largest image past its 200 KB budget.
 *
 * @param int|string $image     Attachment ID or manifest key.
 * @param string     $size_key  One of the SIZES keys.
 * @param bool       $priority  True for the LCP image: eager + high fetchpriority.
 * @return string Markup, or '' when the image is missing.
 */
function picture( $image, string $size_key = 'default', bool $priority = false ): string {
	$id = resolve_id( $image );

	if ( 0 === $id ) {
		// Return empty rather than throwing: the static picture() throws, but a
		// front-end template must not take the page down over a missing image.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			_doing_it_wrong( __FUNCTION__, esc_html( sprintf( 'Unknown image: %s', (string) $image ) ), '0.1.0' );
		}

		return '';
	}

	$sizes    = SIZES[ $size_key ] ?? SIZES['default'];
	$variants = get_post_meta( $id, '_emposo_variants', true );
	$alt      = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
	$meta     = wp_get_attachment_metadata( $id );

	$width  = (int) ( $meta['width'] ?? 0 );
	$height = (int) ( $meta['height'] ?? 0 );

	/*
	 * Serve the pre-built JPEG from the theme when we have one, so the <img>
	 * and the <source> candidates come from the same place. An editor's own
	 * upload has no pre-built file and correctly falls back to the uploads URL.
	 */
	$fallback = (string) get_post_meta( $id, '_emposo_fallback_src', true );
	$src      = '' !== $fallback
		? variant_url( $fallback )
		: (string) wp_get_attachment_image_url( $id, 'emposo-1600' );

	$sources = '';

	if ( is_array( $variants ) && $variants ) {
		// AVIF first, then WebP: the browser takes the first type it supports.
		foreach ( array( 'avif', 'webp' ) as $format ) {
			$candidates = array();
			foreach ( $variants as $variant ) {
				if ( ( $variant['format'] ?? '' ) !== $format ) {
					continue;
				}
				$candidates[] = sprintf(
					'%s %dw',
					esc_url( variant_url( (string) $variant['src'] ) ),
					(int) $variant['width']
				);
			}
			if ( $candidates ) {
				$sources .= sprintf(
					'<source type="image/%s" srcset="%s" sizes="%s">',
					esc_attr( $format ),
					esc_attr( implode( ', ', $candidates ) ),
					esc_attr( $sizes )
				);
			}
		}
	}

	$loading = $priority ? 'fetchpriority="high"' : 'loading="lazy"';

	return sprintf(
		'<picture>%s<img src="%s" alt="%s" width="%d" height="%d" %s decoding="async"></picture>',
		$sources,
		esc_url( $src ),
		// The reference build escapes alt with its 4-char escape(); esc_attr
		// would emit &#039; for an apostrophe and diverge from parity.
		\Emposo\Core\escape_static( $alt ),
		$width,
		$height,
		$loading
	);
}

/**
 * Map a manifest-relative variant path onto a servable URL.
 *
 * The manifest stores site-absolute paths from the static build
 * ('/assets/supplied/x-640.avif'). The variant FILES ship with the theme, so
 * resolve them against the theme URL rather than the uploads directory — which
 * also means they are covered by the theme's immutable cache headers.
 *
 * @param string $manifest_path Path as stored in the manifest.
 */
function variant_url( string $manifest_path ): string {
	$relative = ltrim( $manifest_path, '/' );

	return get_template_directory_uri() . '/' . $relative;
}
