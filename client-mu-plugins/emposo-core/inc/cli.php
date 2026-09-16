<?php
/**
 * WP-CLI command registration.
 *
 * Guarded so nothing here is parsed on a web request: the command classes are
 * only loaded under WP-CLI.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

require_once EMPOSO_CORE_DIR . '/cli/class-verify-command.php';
require_once EMPOSO_CORE_DIR . '/cli/class-scaffold-command.php';

\WP_CLI::add_command(
	'emposo verify',
	__NAMESPACE__ . '\\Verify_Command',
	array(
		'shortdesc' => 'Verify routes, content invariants and media against the frozen contract.',
	)
);

\WP_CLI::add_command(
	'emposo scaffold',
	__NAMESPACE__ . '\\Scaffold_Command',
	array(
		'shortdesc' => 'Create the empty route skeleton from the frozen route contract.',
	)
);
