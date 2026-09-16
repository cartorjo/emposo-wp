<?php
/**
 * Environment helpers.
 *
 * WordPress VIP exposes vip_get_env_var() and a family of wpcom_vip_* helpers
 * that do not exist on a self-hosted install. Every call to one must be guarded,
 * so the guards live here once rather than being repeated at each call site.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Environment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the site is running on the WordPress VIP platform.
 */
function is_vip(): bool {
	return defined( 'VIP_GO_APP_ENVIRONMENT' ) || defined( 'WPCOM_IS_VIP_ENV' );
}

/**
 * Read an environment variable, preferring VIP's accessor where it exists.
 *
 * Secrets (notably the Anthropic API key) are read through this rather than
 * stored in wp_options, so they never land in a database export or a backup.
 *
 * @param string      $name     Variable name.
 * @param string|null $fallback Value to return when unset.
 * @return string|null
 */
function get_env_var( string $name, ?string $fallback = null ): ?string {
	if ( function_exists( 'vip_get_env_var' ) ) {
		$value = \vip_get_env_var( $name, $fallback );

		return is_string( $value ) ? $value : $fallback;
	}

	if ( defined( $name ) ) {
		$value = constant( $name );

		return is_string( $value ) ? $value : $fallback;
	}

	$value = getenv( $name );

	return ( false === $value || '' === $value ) ? $fallback : $value;
}

/**
 * Whether this is a local or development environment.
 */
function is_development(): bool {
	return in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
}
