<?php
/**
 * Rewrite rule maintenance.
 *
 * Flushing regenerates and re-saves every rewrite rule on the site. It is
 * far too expensive to run per request, and calling it on `init` is a
 * well-known performance mistake. So it runs only when a version constant
 * changes — bump REWRITE_VERSION in emposo-core.php whenever a registration
 * argument that affects rewrites changes (a CPT slug, has_archive, a
 * taxonomy's rewrite), and the next request applies it once.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Rewrites;

use const Emposo\Core\REWRITE_VERSION;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION_OPTION = 'emposo_rewrite_version';

add_action( 'init', __NAMESPACE__ . '\\maybe_flush', 99 );

/**
 * Flush once after a registration change, then never again until the next bump.
 */
function maybe_flush(): void {
	if ( get_option( VERSION_OPTION ) === REWRITE_VERSION ) {
		return;
	}

	/*
	 * Never flush under plain permalinks: register_post_type() skipped every
	 * permastruct this boot, so the flush would persist a ruleset missing all
	 * custom post types — and burn the version so nothing retries. This state
	 * exists mid-provision (`wp core install` runs before scaffold sets the
	 * structure); leaving the version unset lets the flush fire on the first
	 * request after the structure exists.
	 */
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		return;
	}

	/*
	 * WordPress VIP flushes rewrite rules as part of a deploy, so calling it
	 * there is both unnecessary and the thing VIPMinimum is warning about.
	 * Self-hosted there is no deploy hook, so a version-gated one-time flush is
	 * the legitimate case the rule's "any normal circumstances" wording
	 * excludes: it runs once per registration change, never per request.
	 */
	if ( ! \Emposo\Core\Environment\is_vip() ) {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- Version-gated, self-hosted only; VIP flushes on deploy.
		flush_rewrite_rules( false );
	}

	update_option( VERSION_OPTION, REWRITE_VERSION, false );
}

/**
 * Resolve a URL path to the object and template WordPress would use for it.
 *
 * This is the mechanism behind `wp emposo verify --routes`. It runs a real
 * request parse rather than guessing, because the failure modes that matter —
 * a CPT rewrite shadowing a Page into a 404, a permastruct registered in the
 * wrong order — are invisible to any check that only looks at the database.
 *
 * @param string $path Path with leading and trailing slashes, e.g. '/expertise/'.
 * @return array{path: string, found: bool, id: int, type: string, permalink: string, note: string}
 */
function resolve( string $path ): array {
	$result = array(
		'path'      => $path,
		'found'     => false,
		'id'        => 0,
		'type'      => '',
		'permalink' => '',
		'note'      => '',
	);

	$trimmed = trim( $path, '/' );

	/*
	 * url_to_postid() understands CPT permastructs; get_page_by_path() is the
	 * fallback for the Page tree, which url_to_postid also handles but which we
	 * check explicitly so a mismatch between the two is visible.
	 *
	 * VIP provides a cached wrapper because the core function is uncached; use
	 * it where it exists. This runs from WP-CLI verification rather than a page
	 * load, so the uncached path is acceptable self-hosted.
	 */
	if ( function_exists( 'wpcom_vip_url_to_postid' ) ) {
		$id = \wpcom_vip_url_to_postid( home_url( $path ) );
	} else {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.url_to_postid_url_to_postid -- Cached wrapper preferred above; this is the self-hosted fallback, called from CLI verification only.
		$id = url_to_postid( home_url( $path ) );
	}

	if ( 0 === $id && '' !== $trimmed ) {
		$page = get_page_by_path( $trimmed );
		if ( $page instanceof \WP_Post ) {
			$id             = $page->ID;
			$result['note'] = 'resolved via get_page_by_path, not url_to_postid';
		}
	}

	if ( 0 === $id && '' === $trimmed ) {
		$front = (int) get_option( 'page_on_front' );
		if ( $front > 0 ) {
			$id = $front;
		}
	}

	if ( 0 === $id ) {
		return $result;
	}

	$post = get_post( $id );
	if ( ! $post instanceof \WP_Post ) {
		return $result;
	}

	$result['found']     = true;
	$result['id']        = $id;
	$result['type']      = $post->post_type;
	$result['permalink'] = (string) get_permalink( $id );

	return $result;
}
