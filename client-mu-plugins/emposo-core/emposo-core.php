<?php
/**
 * Plugin Name: Emposo Core
 * Description: Content model, blocks, image helpers, settings, dashboard and CLI commands for the Emposo site. Loaded as an mu-plugin so content is never coupled to the active theme.
 * Version: 0.1.0
 * Author: Emposo
 * License: Proprietary
 * Text Domain: emposo
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '0.1.0';

/**
 * Bumped whenever a rewrite-affecting registration argument changes.
 *
 * Rewrite rules are flushed on a version mismatch, never on every request:
 * flush_rewrite_rules() regenerates and re-saves every rule in the site and is
 * far too expensive to run per page load.
 */
const REWRITE_VERSION = '1';

define( 'EMPOSO_CORE_DIR', __DIR__ );

require_once __DIR__ . '/inc/environment.php';
require_once __DIR__ . '/inc/escape.php';
require_once __DIR__ . '/inc/content-model.php';
require_once __DIR__ . '/inc/rewrites.php';
require_once __DIR__ . '/inc/images.php';
require_once __DIR__ . '/inc/fragments.php';
require_once __DIR__ . '/inc/cache.php';
require_once __DIR__ . '/inc/security.php';
require_once __DIR__ . '/inc/claude.php';
require_once __DIR__ . '/inc/dashboard.php';
require_once __DIR__ . '/inc/cli.php';
// Self-gated on the EMPOSO_INSTALLER constant; a no-op unless wp-config.php
// defines it. Required after dashboard.php so its submenu finds the parent.
require_once __DIR__ . '/inc/installer.php';
