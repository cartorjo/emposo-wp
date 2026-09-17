<?php
/**
 * Environment configuration.
 *
 * On WordPress VIP this file is loaded from wp-config.php before WordPress
 * boots. Self-hosted, it has to be required from wp-config.php explicitly —
 * recorded in the README as a deployment step, because a config file that
 * silently never loads is worse than not having one.
 *
 * Constants are gated on the environment rather than set unconditionally: a
 * local install needs plugin installation and file modification to work, and a
 * production install must not allow either.
 *
 * @package Emposo\Config
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * wp_get_environment_type() is not available this early — it is defined in
 * wp-includes/load.php — so read the same value wp-config would set.
 */
$emposo_environment = getenv( 'WP_ENVIRONMENT_TYPE' );
if ( ! is_string( $emposo_environment ) || '' === $emposo_environment ) {
	$emposo_environment = defined( 'WP_ENVIRONMENT_TYPE' ) ? (string) WP_ENVIRONMENT_TYPE : 'production';
}

$emposo_is_local = in_array( $emposo_environment, array( 'local', 'development' ), true );

/*
 * Never editable from the admin, in any environment. The theme and plugin are
 * deployed from git, so the file editor can only ever produce a change that the
 * next deploy silently reverts — while also being a direct code-execution path
 * for a compromised admin account.
 */
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

if ( ! $emposo_is_local ) {
	// Plugin and theme installation is a deploy, not an admin action.
	if ( ! defined( 'DISALLOW_FILE_MODS' ) ) {
		define( 'DISALLOW_FILE_MODS', true );
	}

	if ( ! defined( 'FORCE_SSL_ADMIN' ) ) {
		define( 'FORCE_SSL_ADMIN', true );
	}

	// Errors are logged, never displayed: a stack trace in the response body
	// leaks paths and would also break every parity assertion.
	if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
		define( 'WP_DEBUG_DISPLAY', false );
	}

	if ( ! defined( 'SCRIPT_DEBUG' ) ) {
		define( 'SCRIPT_DEBUG', false );
	}

	// Core auto-updates would change deployed code out from under git.
	if ( ! defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) {
		define( 'AUTOMATIC_UPDATER_DISABLED', true );
	}
	if ( ! defined( 'WP_AUTO_UPDATE_CORE' ) ) {
		define( 'WP_AUTO_UPDATE_CORE', false );
	}

	// Post revisions are useful; unbounded ones are a database-growth problem.
	if ( ! defined( 'WP_POST_REVISIONS' ) ) {
		define( 'WP_POST_REVISIONS', 20 );
	}

	if ( ! defined( 'EMPTY_TRASH_DAYS' ) ) {
		define( 'EMPTY_TRASH_DAYS', 14 );
	}
}

/*
 * The launch gate, as a constant rather than only an option.
 *
 * blog_public = 0 is the primary mechanism — core derives noindex, a
 * Disallow: / robots.txt and a disabled sitemap from it — but an option can be
 * changed in the admin. On a staging clone that must never be indexable
 * regardless, set EMPOSO_FORCE_NOINDEX in the environment.
 */
if ( ! defined( 'EMPOSO_FORCE_NOINDEX' ) ) {
	$emposo_force_noindex = getenv( 'EMPOSO_FORCE_NOINDEX' );
	define( 'EMPOSO_FORCE_NOINDEX', ! empty( $emposo_force_noindex ) );
}

/*
 * Proof of loading, for the guard in the site plugin. Without a marker a
 * missing require from wp-config.php is completely silent: every constant above
 * simply stays undefined and the site runs unhardened, looking fine.
 */
if ( ! defined( 'EMPOSO_CONFIG_LOADED' ) ) {
	define( 'EMPOSO_CONFIG_LOADED', true );
}

unset( $emposo_environment, $emposo_is_local );
