<?php
/**
 * The web equivalent of WP-CLI's exit(1).
 *
 * CLI_Shim::error() throws this instead of terminating the process, so the
 * no-shell installer (inc/installer.php) can catch a failed command, show the
 * buffered output, and leave the admin session alive.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thrown by CLI_Shim::error(); callers treat it as "this step failed".
 */
class Halt_Exception extends \RuntimeException {}
