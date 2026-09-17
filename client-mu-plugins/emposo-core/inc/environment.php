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

/**
 * Whether vip-config/vip-config.php was loaded.
 *
 * On WordPress VIP the platform loads it. Self-hosted, wp-config.php has to
 * require it explicitly, and locally wp-env sets the same constants through its
 * own `config` block instead — so "not loaded" is normal in exactly one of the
 * three cases and a hardening failure in another.
 */
function config_loaded(): bool {
	return defined( 'EMPOSO_CONFIG_LOADED' );
}

/**
 * Load vip-config.php if wp-config.php did not, and say so.
 *
 * This is a fallback, not the intended path. mu-plugins load after
 * wp-settings.php has already called wp_debug_mode(), so WP_DEBUG_DISPLAY is
 * the one constant that cannot be rescued this late — it has been read and
 * applied by now. Everything else (DISALLOW_FILE_MODS, FORCE_SSL_ADMIN, the
 * auto-update and revision limits) is read lazily and still takes effect.
 *
 * So the fallback buys real hardening rather than the appearance of it, but it
 * is deliberately noisy: the fix is one require in wp-config.php and the log
 * line says which constant the fallback could not cover.
 */
function load_config_fallback(): void {
	if ( config_loaded() || is_vip() ) {
		return;
	}

	// Locally, wp-env's own `config` block plays the part of wp-config.php.
	// Requiring the production file on top of it would be noise, not safety.
	if ( is_development() ) {
		return;
	}

	$config = dirname( ABSPATH ) . '/vip-config/vip-config.php';

	if ( is_readable( $config ) ) {
		require_once $config;
	}

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- A misconfiguration that must be visible in the host's log; there is no admin screen this early.
	error_log(
		'Emposo: vip-config/vip-config.php was not loaded from wp-config.php. '
		. 'Hardening constants were applied late as a fallback; WP_DEBUG_DISPLAY could not be, '
		. 'because wp_debug_mode() runs before mu-plugins. Add the require to wp-config.php.'
	);
}

load_config_fallback();
