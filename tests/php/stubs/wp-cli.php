<?php
/**
 * Minimal WP-CLI stubs for static analysis.
 *
 * php-stubs/wp-cli-stubs cannot be used here: v2.12 constrains
 * php-stubs/wordpress-stubs to ^4.7 || ^5.0 || ^6.0, while this project targets
 * WordPress 7.1 and wants accurate core stubs for it. Downgrading the core
 * stubs to satisfy a CLI stub package would trade real analysis coverage of the
 * code that runs on every request for coverage of code that runs only under
 * WP-CLI — the wrong way round.
 *
 * So this file declares exactly the WP-CLI surface this codebase uses, and
 * nothing more. That is a feature: adding a new WP-CLI call requires adding it
 * here, which keeps the dependency on that API explicit and reviewable.
 *
 * Brace syntax is required — a file cannot mix global-namespace code with a
 * later `namespace` statement.
 *
 * Never loaded at runtime; referenced only from phpstan.neon's scanFiles.
 *
 * @package Emposo\Core
 */

// phpcs:disable

namespace {
	/**
	 * @see https://make.wordpress.org/cli/handbook/references/internal-api/
	 */
	class WP_CLI {
		/**
		 * @param string                 $name
		 * @param callable|object|string $callable
		 * @param array<string, mixed>   $args
		 * @return bool
		 */
		public static function add_command( $name, $callable, $args = array() ) {
			return true;
		}

		/** @param string $message @return void */
		public static function log( $message ) {}

		/** @param string $message @return void */
		public static function line( $message = '' ) {}

		/** @param string $message @return void */
		public static function success( $message ) {}

		/** @param string $message @return void */
		public static function warning( $message ) {}

		/**
		 * Terminates the process, hence `never`.
		 *
		 * @param string|\WP_Error $message
		 * @param bool|int         $exit
		 * @return never
		 */
		public static function error( $message, $exit = true ) {
			exit( 1 );
		}

		/**
		 * @param string               $question
		 * @param array<string, mixed> $assoc_args
		 * @return void
		 */
		public static function confirm( $question, $assoc_args = array() ) {}

		/** @param string $string @return string */
		public static function colorize( $string ) {
			return $string;
		}

		/**
		 * @param string               $command
		 * @param array<string, mixed> $options
		 * @return mixed
		 */
		public static function runcommand( $command, $options = array() ) {
			return null;
		}
	}
}

namespace cli\progress {
	/**
	 * What Utils\make_progress_bar() actually returns on a TTY, from
	 * wp-cli/php-cli-tools. Declared here because `@return object` is not
	 * analysable at level 8 — a method call on it is an error.
	 */
	class Bar {
		/** @param int $increment @return void */
		public function tick( $increment = 1 ) {}

		/** @return void */
		public function finish() {}
	}
}

namespace WP_CLI\Utils {
	/**
	 * @param string                    $format
	 * @param array<int, mixed>         $items
	 * @param array<int, string>|string $fields
	 * @return void
	 */
	function format_items( $format, $items, $fields ) {}

	/**
	 * @param string $message
	 * @param int    $count
	 * @param int    $interval
	 * @return \cli\progress\Bar
	 */
	function make_progress_bar( $message, $count, $interval = 100 ) {
		return new \cli\progress\Bar();
	}

	/**
	 * @param array<string, mixed> $assoc_args
	 * @param string               $flag
	 * @param mixed                $default_value
	 * @return mixed
	 */
	function get_flag_value( $assoc_args, $flag, $default_value = null ) {
		return $assoc_args[ $flag ] ?? $default_value;
	}
}
