<?php
/**
 * The one WP_CLI\Utils function the commands call, for web requests.
 *
 * Companion to cli/class-cli-shim.php, in its own file because the namespace
 * below cannot share a file with the shim class without curly-brace namespace
 * syntax. Loaded only by the shim, which is loaded only by the no-shell
 * installer — under real WP-CLI the genuine function exists first and the
 * guard keeps this one out.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Deliberately WP-CLI's namespace: the verify command calls WP_CLI\Utils\format_items(), which must resolve on a web request where wp-cli is not loaded.
namespace WP_CLI\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( __NAMESPACE__ . '\\format_items' ) ) {
	/**
	 * Plain-text stand-in for WP-CLI's table renderer.
	 *
	 * Only the call shape matches the real function; every format renders as
	 * an aligned text table into the shim's buffer, which is enough for the
	 * one caller (verify's bad-route table) and for an admin screen.
	 *
	 * @param string                    $format Ignored.
	 * @param array<int, mixed>         $items  Rows.
	 * @param array<int, string>|string $fields Column keys.
	 */
	function format_items( $format, $items, $fields ): void {
		unset( $format );

		$fields = is_string( $fields ) ? array_map( 'trim', explode( ',', $fields ) ) : $fields;

		$widths = array();
		foreach ( $fields as $field ) {
			$widths[ $field ] = strlen( $field );
		}

		$rows = array();
		foreach ( $items as $item ) {
			$item = (array) $item;
			$row  = array();
			foreach ( $fields as $field ) {
				$value            = isset( $item[ $field ] ) && is_scalar( $item[ $field ] ) ? (string) $item[ $field ] : '';
				$row[ $field ]    = $value;
				$widths[ $field ] = max( $widths[ $field ], strlen( $value ) );
			}
			$rows[] = $row;
		}

		$render = static function ( array $cells ) use ( $fields, $widths ): string {
			$out = array();
			foreach ( $fields as $field ) {
				$out[] = str_pad( (string) $cells[ $field ], $widths[ $field ] );
			}

			return rtrim( implode( '  ', $out ) );
		};

		\Emposo\Core\CLI\CLI_Shim::log( $render( array_combine( $fields, $fields ) ) );
		foreach ( $rows as $row ) {
			\Emposo\Core\CLI\CLI_Shim::log( $render( $row ) );
		}
	}
}
