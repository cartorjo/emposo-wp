<?php
/**
 * "Emposo Inhalte": the site-wide content that is not a post.
 *
 * Kennzahlen (the company facts strip), certifications (the trust strip) and
 * the contact form's topics, in both languages, plus the form's recipient.
 * These were seeded options with no admin UI.
 *
 * Editors get the page through the `emposo_edit_settings` capability. The
 * recipient address is administrators only: whoever controls it receives
 * every enquiry.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Editorial;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SETTINGS_CAP   = 'emposo_edit_settings';
const SETTINGS_SLUG  = 'emposo-inhalte';
const SETTINGS_NONCE = 'emposo_settings';

add_action( 'admin_init', __NAMESPACE__ . '\\grant_settings_cap' );
add_action( 'admin_menu', __NAMESPACE__ . '\\add_settings_page' );
add_action( 'admin_post_emposo_save_settings', __NAMESPACE__ . '\\save_settings' );

/**
 * Give administrators and editors the capability (idempotent, stored once).
 */
function grant_settings_cap(): void {
	foreach ( array( 'administrator', 'editor' ) as $name ) {
		$role = get_role( $name );
		if ( null !== $role && ! $role->has_cap( SETTINGS_CAP ) ) {
			$role->add_cap( SETTINGS_CAP );
		}
	}
}

/**
 * Register the page as its own top-level menu (the Emposo menu is admins only).
 */
function add_settings_page(): void {
	add_menu_page(
		__( 'Emposo Inhalte', 'emposo' ),
		__( 'Emposo Inhalte', 'emposo' ),
		SETTINGS_CAP,
		SETTINGS_SLUG,
		__NAMESPACE__ . '\\render_settings_page',
		'dashicons-editor-table',
		25
	);
}

/**
 * The icon file names the facts strip can use.
 *
 * @return string[]
 */
function icon_choices(): array {
	$files = glob( get_template_directory() . '/assets/icons/*.svg' );

	return array_map(
		static function ( string $file ): string {
			return basename( $file, '.svg' );
		},
		is_array( $files ) ? $files : array()
	);
}

/**
 * A list option as an array.
 *
 * @param string $name Option name.
 * @return array<int, mixed>
 */
function list_option( string $name ): array {
	$value = get_option( $name, array() );

	return is_array( $value ) ? array_values( $value ) : array();
}

/**
 * Render the page.
 */
function render_settings_page(): void {
	if ( ! current_user_can( SETTINGS_CAP ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'emposo' ), '', array( 'response' => 403 ) );
	}

	$icons = icon_choices();

	echo '<div class="wrap"><h1>' . esc_html__( 'Emposo Inhalte', 'emposo' ) . '</h1>';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag set by our own redirect.
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Gespeichert.', 'emposo' ) . '</p></div>';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="emposo_save_settings">';
	wp_nonce_field( SETTINGS_NONCE, SETTINGS_NONCE );

	foreach ( array(
		'emposo_facts'    => __( 'Kennzahlen (Deutsch)', 'emposo' ),
		'emposo_facts_en' => __( 'Kennzahlen (Englisch)', 'emposo' ),
	) as $name => $title ) {
		echo '<h2>' . esc_html( $title ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Die Kennzahlen-Leiste auf „Über uns“ und „Karriere“. Leere Zeilen entfallen.', 'emposo' ) . '</p>';
		echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>' . esc_html__( 'Wert', 'emposo' ) . '</th><th>' . esc_html__( 'Beschriftung', 'emposo' ) . '</th><th>' . esc_html__( 'Symbol', 'emposo' ) . '</th></tr></thead><tbody>';
		$rows  = list_option( $name );
		$count = max( 4, count( $rows ) + 1 );
		for ( $i = 0; $i < $count; $i++ ) {
			$row = is_array( $rows[ $i ] ?? null ) ? $rows[ $i ] : array();
			printf(
				'<tr><td><input type="text" name="%1$s[%2$d][value]" value="%3$s"></td><td><input type="text" class="regular-text" name="%1$s[%2$d][label]" value="%4$s"></td><td><select name="%1$s[%2$d][icon]">',
				esc_attr( $name ),
				(int) $i,
				esc_attr( (string) ( $row['value'] ?? '' ) ),
				esc_attr( (string) ( $row['label'] ?? '' ) )
			);
			foreach ( $icons as $icon ) {
				printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $icon ), selected( (string) ( $row['icon'] ?? '' ), $icon, false ) );
			}
			echo '</select></td></tr>';
		}
		echo '</tbody></table>';
	}

	echo '<h2>' . esc_html__( 'Zertifikate', 'emposo' ) . '</h2>';
	printf(
		'<textarea class="large-text" rows="4" name="emposo_certifications" style="max-width:900px">%s</textarea><p class="description">%s</p>',
		esc_textarea( implode( "\n", array_map( 'strval', list_option( 'emposo_certifications' ) ) ) ),
		esc_html__( 'Die Zertifikats-Leiste. Ein Eintrag pro Zeile, nur freigegebene Zertifikate.', 'emposo' )
	);

	foreach ( array(
		'emposo_interests'    => __( 'Kontaktformular: Themen (Deutsch)', 'emposo' ),
		'emposo_interests_en' => __( 'Kontaktformular: Themen (Englisch)', 'emposo' ),
	) as $name => $title ) {
		$labels = array_map(
			static function ( $item ): string {
				return is_array( $item ) ? (string) ( $item['label'] ?? '' ) : (string) $item;
			},
			list_option( $name )
		);
		echo '<h2>' . esc_html( $title ) . '</h2>';
		printf(
			'<textarea class="large-text" rows="6" name="%1$s" style="max-width:900px">%2$s</textarea><p class="description">%3$s</p>',
			esc_attr( $name ),
			esc_textarea( implode( "\n", $labels ) ),
			esc_html__( 'Die Auswahl „Worum geht es?“. Ein Thema pro Zeile. Der Link „Anderes Anliegen“ / „Something else“ auf den Seiten wählt das gleichnamige Thema vor; bitte beibehalten.', 'emposo' )
		);
	}

	if ( current_user_can( 'manage_options' ) ) {
		echo '<h2>' . esc_html__( 'Kontaktformular: Empfängeradresse', 'emposo' ) . '</h2>';
		printf(
			'<input type="email" class="regular-text" name="emposo_contact_recipient" value="%s"><p class="description">%s</p>',
			esc_attr( (string) get_option( 'emposo_contact_recipient', '' ) ),
			esc_html__( 'Nur für Administratoren sichtbar.', 'emposo' )
		);
	}

	submit_button( __( 'Speichern', 'emposo' ) );
	echo '</form></div>';
}

/**
 * Save the page.
 */
function save_settings(): void {
	if ( ! current_user_can( SETTINGS_CAP )
		|| ! isset( $_POST[ SETTINGS_NONCE ] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ SETTINGS_NONCE ] ) ), SETTINGS_NONCE ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'emposo' ), '', array( 'response' => 403 ) );
	}

	$icons = icon_choices();

	foreach ( array( 'emposo_facts', 'emposo_facts_en' ) as $name ) {
		$rows = isset( $_POST[ $name ] ) && is_array( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per cell below.
		$out  = array();
		foreach ( (array) $rows as $row ) {
			$value = sanitize_text_field( (string) ( $row['value'] ?? '' ) );
			$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
			$icon  = sanitize_key( (string) ( $row['icon'] ?? '' ) );
			if ( '' === $value && '' === $label ) {
				continue;
			}
			$out[] = array(
				'icon'  => in_array( $icon, $icons, true ) ? $icon : ( $icons[0] ?? '' ),
				'value' => $value,
				'label' => $label,
			);
		}
		update_option( $name, $out, false );
	}

	update_option( 'emposo_certifications', settings_lines( 'emposo_certifications' ), false );

	foreach ( array( 'emposo_interests', 'emposo_interests_en' ) as $name ) {
		// The form validates the posted value against this list; value = label.
		$items = array_map(
			static function ( string $label ): array {
				return array(
					'value' => $label,
					'label' => $label,
				);
			},
			settings_lines( $name )
		);
		update_option( $name, $items, false );
	}

	if ( current_user_can( 'manage_options' ) && isset( $_POST['emposo_contact_recipient'] ) ) {
		$email = sanitize_email( wp_unslash( $_POST['emposo_contact_recipient'] ) );
		if ( is_email( $email ) ) {
			update_option( 'emposo_contact_recipient', $email, false );
		}
	}

	// Rendered lists are cached (inc/cache.php); move the salt.
	\Emposo\Core\Cache\bump();

	wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=' . SETTINGS_SLUG ) ) );
	exit;
}

/**
 * A posted textarea as trimmed, non-empty lines.
 *
 * @param string $name Field name.
 * @return string[]
 */
function settings_lines( string $name ): array {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in save_settings().
	$raw = isset( $_POST[ $name ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) ) : '';

	return array_values(
		array_filter(
			array_map( 'trim', lines( $raw ) ),
			static function ( string $item ): bool {
				return '' !== $item;
			}
		)
	);
}
