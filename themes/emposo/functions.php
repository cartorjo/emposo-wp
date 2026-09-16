<?php
/**
 * Emposo theme bootstrap.
 *
 * The theme owns presentation only. Content types, taxonomies, meta, blocks and
 * CLI commands live in client-mu-plugins/emposo-core so that content survives a
 * theme change (the functionality-plugin principle from the Plugin Handbook).
 *
 * @package Emposo
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EMPOSO_VERSION', '0.1.0' );
define( 'EMPOSO_DIR', get_template_directory() );
define( 'EMPOSO_URI', get_template_directory_uri() );

require_once EMPOSO_DIR . '/inc/core-cleanup.php';
require_once EMPOSO_DIR . '/inc/assets.php';
