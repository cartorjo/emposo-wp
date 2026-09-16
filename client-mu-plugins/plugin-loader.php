<?php
/**
 * Plugin Name: Emposo mu-plugin loader
 * Description: Loads the Emposo site plugin. WordPress (and WordPress VIP) only auto-load single PHP files at the root of the mu-plugins directory, never plugins inside subdirectories — so the subdirectory plugin must be required explicitly from here.
 * Version: 0.1.0
 * Author: Emposo
 * License: Proprietary
 * Text Domain: emposo
 *
 * @package Emposo
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/emposo-core/emposo-core.php';
