<?php
/**
 * "Texte": the site-wide wording that is not part of one page (phase 2).
 *
 * - UI strings (data/i18n.json: navigation, footer, buttons, form labels,
 *   filter wording), DE and EN, stored as overrides in `emposo_strings` and
 *   `emposo_strings_en` and applied by t() (inc/i18n.php).
 * - The fields of the global templates (the 404 pages), stored in the
 *   `emposo_fields` option (inc/fields.php).
 *
 * As everywhere, an empty input means "use the original".
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Editorial;

use const Emposo\Core\Fields\GLOBAL_OPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TEXTS_SLUG  = 'emposo-texte';
const TEXTS_NONCE = 'emposo_texts';

add_action( 'admin_menu', __NAMESPACE__ . '\\add_texts_page', 20 );
add_action( 'admin_post_emposo_save_texts', __NAMESPACE__ . '\\save_texts' );

/**
 * Register the page below "Emposo Inhalte".
 */
function add_texts_page(): void {
	add_submenu_page( SETTINGS_SLUG, __( 'Texte (Navigation, Footer, Buttons)', 'emposo' ), __( 'Texte', 'emposo' ), SETTINGS_CAP, TEXTS_SLUG, __NAMESPACE__ . '\\render_texts_page' );
}

/**
 * The original UI strings of a locale.
 *
 * @param string $locale 'de' or 'en'.
 * @return array<string, string>
 */
function original_strings( string $locale ): array {
	$strings = (array) ( \Emposo\Core\I18n\tables()['strings'][ $locale ] ?? array() );

	return array_map( 'strval', $strings );
}

/**
 * The option holding a locale's string overrides.
 *
 * @param string $locale 'de' or 'en'.
 */
function strings_option( string $locale ): string {
	return 'en' === $locale ? 'emposo_strings_en' : 'emposo_strings';
}

/**
 * The global templates with fields (the 404 pages).
 *
 * @return array<string, array<string, mixed>>
 */
function global_schemas(): array {
	$out = array();
	foreach ( array( 'pages/404', 'pages/en/404' ) as $template ) {
		$schema = \Emposo\Core\Fields\schema( $template );
		if ( is_array( $schema ) ) {
			$out[ $template ] = $schema;
		}
	}

	return $out;
}

/**
 * Human group names for the string key prefixes.
 *
 * @return array<string, string>
 */
function string_groups(): array {
	return array(
		'nav'        => __( 'Navigation', 'emposo' ),
		'menu'       => __( 'Navigation', 'emposo' ),
		'skip'       => __( 'Navigation', 'emposo' ),
		'lang'       => __( 'Navigation', 'emposo' ),
		'logo'       => __( 'Navigation', 'emposo' ),
		'crumb'      => __( 'Navigation', 'emposo' ),
		'footer'     => __( 'Footer', 'emposo' ),
		'cta'        => __( 'Abschluss-Blöcke (Call to Action)', 'emposo' ),
		'form'       => __( 'Kontaktformular', 'emposo' ),
		'filter'     => __( 'Projektfilter', 'emposo' ),
		'card'       => __( 'Projektkarten und Case Studies', 'emposo' ),
		'project'    => __( 'Projektkarten und Case Studies', 'emposo' ),
		'facts'      => __( 'Kennzahlen', 'emposo' ),
		'management' => __( 'Management', 'emposo' ),
		'jobs'       => __( 'Stellen', 'emposo' ),
		'sitemap'    => __( 'Sitemap', 'emposo' ),
	);
}

/**
 * Render the page.
 */
function render_texts_page(): void {
	if ( ! current_user_can( SETTINGS_CAP ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'emposo' ), '', array( 'response' => 403 ) );
	}

	echo '<div class="wrap"><h1>' . esc_html__( 'Texte', 'emposo' ) . '</h1>';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag set by our own redirect.
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Gespeichert.', 'emposo' ) . '</p></div>';
	}
	echo '<p class="description">' . esc_html__( 'Wörter und Sätze, die auf vielen Seiten vorkommen. Ein geleertes Feld stellt den ursprünglichen Text wieder her. <br> ist ein Zeilenumbruch.', 'emposo' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="emposo_save_texts">';
	wp_nonce_field( TEXTS_NONCE, TEXTS_NONCE );

	$groups = string_groups();
	foreach ( array(
		'de' => __( 'Deutsch', 'emposo' ),
		'en' => __( 'Englisch', 'emposo' ),
	) as $locale => $language ) {
		$overrides = get_option( strings_option( $locale ), array() );
		$overrides = is_array( $overrides ) ? $overrides : array();
		$current   = '';
		echo '<h2 style="margin-top:32px">' . esc_html( $language ) . '</h2>';
		foreach ( original_strings( $locale ) as $key => $original ) {
			$group = $groups[ strtok( $key, '.' ) ] ?? __( 'Weitere', 'emposo' );
			if ( $group !== $current ) {
				echo ( '' !== $current ? '</tbody></table>' : '' ) . '<h3>' . esc_html( $group ) . '</h3><table class="form-table" role="presentation"><tbody>';
				$current = $group;
			}
			$value = (string) ( $overrides[ $key ] ?? '' );
			printf(
				'<tr><th scope="row"><code>%1$s</code></th><td><textarea class="large-text" rows="%2$d" name="emposo_strings[%3$s][%1$s]">%4$s</textarea></td></tr>',
				esc_html( $key ),
				(int) max( 1, min( 4, (int) ceil( mb_strlen( $original ) / 90 ) ) ),
				esc_attr( $locale ),
				esc_textarea( '' !== $value ? $value : $original )
			);
		}
		echo '</tbody></table>';
	}

	$stored = get_option( GLOBAL_OPT, array() );
	$stored = is_array( $stored ) ? $stored : array();
	foreach ( global_schemas() as $template => $schema ) {
		echo '<h2 style="margin-top:32px">' . esc_html( 'pages/404' === $template ? __( 'Fehlerseite 404 (Deutsch)', 'emposo' ) : __( 'Fehlerseite 404 (Englisch)', 'emposo' ) ) . '</h2><table class="form-table" role="presentation"><tbody>';
		foreach ( (array) $schema['fields'] as $field ) {
			echo field_row( array_map( 'strval', $field ), array_map( 'strval', $stored ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts in field_row().
		}
		echo '</tbody></table>';
	}

	$custom = get_option( \Emposo\Core\Hyphenation\OPTION, array() );
	echo '<h2 style="margin-top:32px">' . esc_html__( 'Silbentrennung in großen Überschriften', 'emposo' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'Lange Wörter trennen an den markierten Stellen, z. B. „Gewichts|management“. Ein Wort pro Zeile, genau so geschrieben wie in der Überschrift. Bereits eingebaut:', 'emposo' ) . ' ' . esc_html( implode( ', ', \Emposo\Core\Hyphenation\builtin() ) ) . '</p>';
	printf(
		'<textarea class="large-text" rows="6" name="emposo_hyphenation" style="max-width:900px">%s</textarea>',
		esc_textarea( implode( "\n", is_array( $custom ) ? array_map( 'strval', $custom ) : array() ) )
	);

	submit_button( __( 'Speichern', 'emposo' ) );
	echo '</form></div>';
}

/**
 * Save the page.
 */
function save_texts(): void {
	if ( ! current_user_can( SETTINGS_CAP )
		|| ! isset( $_POST[ TEXTS_NONCE ] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ TEXTS_NONCE ] ) ), TEXTS_NONCE ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'emposo' ), '', array( 'response' => 403 ) );
	}

	$posted = isset( $_POST['emposo_strings'] ) && is_array( $_POST['emposo_strings'] ) ? wp_unslash( $_POST['emposo_strings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per string below.
	foreach ( array( 'de', 'en' ) as $locale ) {
		$out = array();
		foreach ( original_strings( $locale ) as $key => $original ) {
			if ( ! isset( $posted[ $locale ][ $key ] ) ) {
				continue;
			}
			// Strings that carry markup keep the inline tags; plain ones stay plain.
			$type  = false !== strpos( $original, '<' ) ? 'html' : 'text';
			$value = \Emposo\Core\Fields\sanitize_value( $type, (string) $posted[ $locale ][ $key ] );
			if ( ! \Emposo\Core\Fields\is_default( $value, $original ) ) {
				$out[ $key ] = $value;
			}
		}
		update_option( strings_option( $locale ), $out, false );
	}

	$fields = isset( $_POST['emposo_fields'] ) && is_array( $_POST['emposo_fields'] ) ? wp_unslash( $_POST['emposo_fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per field type below.
	$stored = array();
	foreach ( global_schemas() as $schema ) {
		foreach ( (array) $schema['fields'] as $field ) {
			$key = (string) $field['key'];
			if ( ! isset( $fields[ $key ] ) ) {
				continue;
			}
			if ( 'image' === $field['type'] ) {
				$value = absint( $fields[ $key ] ) > 0 ? (string) absint( $fields[ $key ] ) : '';
			} else {
				$value = \Emposo\Core\Fields\sanitize_value( (string) $field['type'], (string) $fields[ $key ] );
				$value = \Emposo\Core\Fields\is_default( $value, (string) $field['default'] ) ? '' : $value;
			}
			if ( '' !== $value ) {
				$stored[ $key ] = $value;
			}
		}
	}
	update_option( GLOBAL_OPT, $stored, false );

	// Hyphenation entries: letters and "|" only; anything else is dropped.
	$entries = array_values(
		array_filter(
			settings_lines( 'emposo_hyphenation' ),
			static function ( string $entry ): bool {
				return false !== strpos( $entry, '|' ) && 1 === preg_match( '/^[\p{L}|]+$/u', $entry );
			}
		)
	);
	update_option( \Emposo\Core\Hyphenation\OPTION, $entries, false );

	\Emposo\Core\Cache\bump();

	wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=' . TEXTS_SLUG ) ) );
	exit;
}
