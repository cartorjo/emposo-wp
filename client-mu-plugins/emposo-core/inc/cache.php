<?php
/**
 * Object-cache helpers for derived queries.
 *
 * Every list on this site is derived — related case studies, an industry's
 * disciplines, the filter vocabulary — and recomputing them per request means
 * several term and meta lookups each time. At 23 content items that is cheap,
 * but the pattern is the one that keeps working as content grows, and it is
 * what a VIP-grade review expects to see.
 *
 * Invalidation is a BUMPABLE VERSION SALT baked into the key, not
 * wp_cache_flush_group(). VIP does not support flushing a group, so a salt is
 * the approach that ports; it is also cheaper, since bumping one integer
 * invalidates every derived entry at once without touching them.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GROUP       = 'emposo';
const VERSION_KEY = 'emposo_cache_version';
const DEFAULT_TTL = HOUR_IN_SECONDS;

add_action( 'save_post', __NAMESPACE__ . '\\bump' );
add_action( 'deleted_post', __NAMESPACE__ . '\\bump' );
add_action( 'edited_term', __NAMESPACE__ . '\\bump' );
add_action( 'created_term', __NAMESPACE__ . '\\bump' );
add_action( 'delete_term', __NAMESPACE__ . '\\bump' );
add_action( 'set_object_terms', __NAMESPACE__ . '\\bump' );
add_action( 'updated_post_meta', __NAMESPACE__ . '\\bump' );
add_action( 'added_post_meta', __NAMESPACE__ . '\\bump' );

/**
 * The current cache version.
 *
 * Autoloaded, so reading it costs nothing beyond the options query every
 * request already makes.
 */
function version(): int {
	$version = get_option( VERSION_KEY );

	if ( false === $version ) {
		update_option( VERSION_KEY, 1, true );

		return 1;
	}

	return (int) $version;
}

/**
 * Invalidate every derived entry by moving the salt.
 *
 * Deliberately coarse. The alternative — tracking which derived lists a given
 * post or term appears in — is more code and more ways to be subtly wrong,
 * for a site whose content changes a few times a week.
 */
function bump(): void {
	// Not during an import: the importer touches every object, and bumping per
	// object would serialise thousands of option writes for no benefit. It
	// flushes the cache itself when it unwinds.
	if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
		return;
	}

	update_option( VERSION_KEY, version() + 1, true );
}

/**
 * Read a derived value from cache, computing it on a miss.
 *
 * @template T
 * @param string        $key     Cache key, unique within the group.
 * @param callable(): T $compute Returns the value on a miss.
 * @param int           $ttl     Lifetime in seconds.
 * @return T
 */
function remember( string $key, callable $compute, int $ttl = DEFAULT_TTL ) {
	$versioned = sprintf( '%s:v%d', $key, version() );

	$found  = false;
	$cached = wp_cache_get( $versioned, GROUP, false, $found );

	if ( $found ) {
		/*
		 * wp_cache_get() is typed mixed, so there is nothing to narrow against —
		 * the value came out of the same $compute() the caller passed in, which
		 * is where the T actually comes from.
		 *
		 * @var T $cached
		 */
		return $cached;
	}

	$value = $compute();
	// phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined -- $ttl defaults to HOUR_IN_SECONDS and every caller passes a constant; the sniff cannot see through the parameter.
	wp_cache_set( $versioned, $value, GROUP, $ttl );

	return $value;
}
