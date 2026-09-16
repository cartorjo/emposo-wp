<?php
/**
 * Constants PHPStan cannot infer because they are defined in wp-config.php
 * or by the VIP platform rather than in this codebase.
 *
 * @package Emposo
 */

define( 'EMPOSO_VERSION', '0.1.0' );
define( 'EMPOSO_DIR', __DIR__ );
define( 'EMPOSO_URI', 'http://localhost:8888/wp-content/themes/emposo' );
define( 'EMPOSO_FORCE_NOINDEX', true );
