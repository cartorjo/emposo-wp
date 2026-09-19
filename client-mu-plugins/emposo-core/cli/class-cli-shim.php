<?php
/**
 * A minimal WP_CLI façade so the emposo commands can run on a web request.
 *
 * The production host offers SFTP and wp-admin but no terminal, so the tested
 * Scaffold/Import/Verify command classes must be invocable from an admin
 * screen (inc/installer.php) rather than rewritten. Those classes touch
 * exactly this much of the WP-CLI API: the statics log, line, warning,
 * success, colorize and error, plus WP_CLI\Utils\format_items() — the same
 * surface tests/php/stubs/wp-cli.php declares for static analysis, which is
 * the reviewable list of what this shim (and cli/wp-cli-utils.php for the
 * function) must keep covering.
 *
 * Output calls are buffered for the installer to render; error() throws
 * Halt_Exception instead of exiting. The class is aliased to the global
 * WP_CLI name only when real WP-CLI is absent, so loading this file under
 * `wp` is harmless — and static analysis keeps resolving WP_CLI to the stub,
 * because the alias is invisible to it.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-halt-exception.php';
require_once __DIR__ . '/wp-cli-utils.php';

/**
 * Buffering stand-in for the WP_CLI statics the command classes call.
 *
 * Method signatures are loose (untyped parameters) on purpose: they must
 * accept whatever the real WP_CLI accepts, because the command classes are
 * written against the real API, not against this shim.
 */
class CLI_Shim {

	/**
	 * Captured output, in call order.
	 *
	 * @var array<int, array{level: string, text: string}>
	 */
	private static array $lines = array();

	/**
	 * Return everything captured so far and clear the buffer.
	 *
	 * @return array<int, array{level: string, text: string}>
	 */
	public static function drain(): array {
		$lines       = self::$lines;
		self::$lines = array();

		return $lines;
	}

	/**
	 * Record one output line.
	 *
	 * @param string $level One of log, warning, success, error.
	 * @param string $text  The line.
	 */
	public static function record( string $level, string $text ): void {
		self::$lines[] = array(
			'level' => $level,
			'text'  => $text,
		);
	}

	/**
	 * WP_CLI::log().
	 *
	 * @param string $message The line.
	 */
	public static function log( $message ): void {
		self::record( 'log', (string) $message );
	}

	/**
	 * WP_CLI::line().
	 *
	 * @param string $message The line.
	 */
	public static function line( $message = '' ): void {
		self::record( 'log', (string) $message );
	}

	/**
	 * WP_CLI::warning().
	 *
	 * @param string $message The line.
	 */
	public static function warning( $message ): void {
		self::record( 'warning', (string) $message );
	}

	/**
	 * WP_CLI::success().
	 *
	 * @param string $message The line.
	 */
	public static function success( $message ): void {
		self::record( 'success', (string) $message );
	}

	/**
	 * WP_CLI::colorize() — there is no terminal, so strip the tokens.
	 *
	 * @param string $text String with %-prefixed colour tokens.
	 * @return string The string without them.
	 */
	public static function colorize( $text ): string {
		return (string) preg_replace( '/%[a-zA-Z0-9]/', '', (string) $text );
	}

	/**
	 * WP_CLI::error() — abort the command without killing the request.
	 *
	 * @param string|\WP_Error $message Why.
	 * @param bool|int         $halt    Ignored; the real API's exit code.
	 * @return never
	 * @throws Halt_Exception Always; the installer catches it.
	 */
	public static function error( $message, $halt = true ) {
		unset( $halt );
		$text = $message instanceof \WP_Error ? $message->get_error_message() : (string) $message;
		self::record( 'error', $text );
		throw new Halt_Exception( $text );
	}
}

if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) && ! class_exists( 'WP_CLI' ) ) {
	class_alias( __NAMESPACE__ . '\\CLI_Shim', 'WP_CLI' );
}
