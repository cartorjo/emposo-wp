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
require_once EMPOSO_CORE_DIR . '/cli/class-import-command.php';
require_once EMPOSO_CORE_DIR . '/cli/class-claude-command.php';

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

\WP_CLI::add_command(
	'emposo import',
	__NAMESPACE__ . '\\Import_Command',
	array(
		'shortdesc' => 'Seed content from the frozen export. Subcommands run in dependency order.',
	)
);

\WP_CLI::add_command(
	'claude',
	__NAMESPACE__ . '\\Claude_Command',
	array(
		'shortdesc' => 'Anthropic-backed editorial tooling: prompt, alt text, claims audit, doctor.',
	)
);
