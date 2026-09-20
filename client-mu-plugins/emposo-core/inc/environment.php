<?php
/**
 * Environment helpers.
 *
 * Secrets and a couple of environment facts are read here so the rules live in
 * one place rather than at each call site.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Environment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read an environment variable or its matching constant.
 *
 * Secrets (notably the Anthropic API key) are read through this rather than
 * stored in wp_options, so they never land in a database export or a backup: a
 * wp-config.php constant or a real environment variable, never the database.
 *
 * @param string      $name     Variable name.
 * @param string|null $fallback Value to return when unset.
 * @return string|null
 */
function get_env_var( string $name, ?string $fallback = null ): ?string {
	if ( defined( $name ) ) {
		$value = constant( $name );

		return is_string( $value ) ? $value : $fallback;
	}

	$value = getenv( $name );

	return ( false === $value || '' === $value ) ? $fallback : $value;
}
