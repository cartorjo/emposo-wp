<?php
/**
 * Fields on the Disziplinen, Branchen and Wirkungen screens.
 *
 * The importer writes these term meta keys (cli/class-import-command.php,
 * terms and relations stages); until 2026-09-29 only the German name had an
 * input. A new term also needs `_emposo_term_order` to render at all (the
 * lists order by it), so a term created in wp-admin gets the next free
 * position (the trap in issue #43).
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Editorial;

use WP_Term;
use const Emposo\Core\ContentModel\TAX_DISCIPLINE;
use const Emposo\Core\ContentModel\TAX_INDUSTRY;
use const Emposo\Core\ContentModel\TAX_OUTCOME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TERM_NONCE = 'emposo_term_fields';

add_action( 'init', __NAMESPACE__ . '\\register_term_fields', 20 );
foreach ( array( TAX_DISCIPLINE, TAX_INDUSTRY, TAX_OUTCOME ) as $emposo_taxonomy ) {
	add_action( $emposo_taxonomy . '_edit_form_fields', __NAMESPACE__ . '\\render_term_fields', 10, 2 );
	add_action( $emposo_taxonomy . '_add_form_fields', __NAMESPACE__ . '\\render_new_term_fields' );
	add_action( 'edited_' . $emposo_taxonomy, __NAMESPACE__ . '\\save_term_fields' );
	add_action( 'created_' . $emposo_taxonomy, __NAMESPACE__ . '\\save_term_fields' );
}
unset( $emposo_taxonomy );

/**
 * The editable fields per taxonomy.
 *
 * @return array<string, array<string, array{0: string, 1: string}>> Taxonomy => key => [label, type].
 */
function term_field_map(): array {
	return array(
		TAX_DISCIPLINE => array(
			'name_en'    => array( __( 'Name (EN)', 'emposo' ), 'text' ),
			'topics'     => array( __( 'Themen (DE)', 'emposo' ), 'textarea' ),
			'topics_en'  => array( __( 'Themen (EN)', 'emposo' ), 'textarea' ),
			'promise'    => array( __( 'Leistungsversprechen (DE)', 'emposo' ), 'textarea' ),
			'promise_en' => array( __( 'Leistungsversprechen (EN)', 'emposo' ), 'textarea' ),
			'icon'       => array( __( 'Symbol', 'emposo' ), 'icon' ),
			'term_order' => array( __( 'Reihenfolge', 'emposo' ), 'number' ),
		),
		TAX_INDUSTRY   => array(
			'name_en'     => array( __( 'Name (EN)', 'emposo' ), 'text' ),
			'subtitle'    => array( __( 'Unterzeile (DE)', 'emposo' ), 'text' ),
			'subtitle_en' => array( __( 'Unterzeile (EN)', 'emposo' ), 'text' ),
			'tile'        => array( __( 'Als Kachel auf „Branchen“ zeigen', 'emposo' ), 'checkbox' ),
			'image'       => array( __( 'Kachelbild', 'emposo' ), 'image' ),
			'term_order'  => array( __( 'Reihenfolge', 'emposo' ), 'number' ),
		),
		TAX_OUTCOME    => array(
			'term_order' => array( __( 'Reihenfolge', 'emposo' ), 'number' ),
		),
	);
}

/**
 * Register the term meta, so it has a schema and an auth check.
 */
function register_term_fields(): void {
	foreach ( term_field_map() as $taxonomy => $fields ) {
		foreach ( $fields as $key => list( , $type ) ) {
			register_term_meta(
				$taxonomy,
				'_emposo_' . $key,
				array(
					'type'              => in_array( $type, array( 'number', 'image', 'checkbox' ), true ) ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => in_array( $type, array( 'number', 'image', 'checkbox' ), true ) ? 'absint' : ( 'textarea' === $type ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
					'auth_callback'     => static function (): bool {
						return current_user_can( 'manage_categories' );
					},
				)
			);
		}
	}
}

/**
 * One field's input.
 *
 * @param string $name  Input name.
 * @param string $type  Field type.
 * @param string $value Current value.
 */
function term_input( string $name, string $type, string $value ): string {
	switch ( $type ) {
		case 'textarea':
			return sprintf( '<textarea name="%1$s" rows="3" class="large-text">%2$s</textarea>', esc_attr( $name ), esc_textarea( $value ) );
		case 'number':
			return sprintf( '<input type="number" name="%1$s" value="%2$s" step="1" min="0" class="small-text">', esc_attr( $name ), esc_attr( $value ) );
		case 'checkbox':
			return sprintf( '<input type="checkbox" name="%1$s" value="1"%2$s>', esc_attr( $name ), checked( '1', $value, false ) );
		case 'icon':
			$out = sprintf( '<select name="%s"><option value="">—</option>', esc_attr( $name ) );
			foreach ( icon_choices() as $icon ) {
				$out .= sprintf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $icon ), selected( $value, $icon, false ) );
			}
			return $out . '</select>';
		case 'image':
			$images = get_posts(
				array(
					'post_type'        => 'attachment',
					'post_mime_type'   => 'image',
					'post_status'      => 'inherit',
					'posts_per_page'   => 100,
					'orderby'          => 'title',
					'order'            => 'ASC',
					'no_found_rows'    => true,
					'suppress_filters' => false,
				)
			);
			$out    = sprintf( '<select name="%s"><option value="0">—</option>', esc_attr( $name ) );
			foreach ( $images as $image ) {
				$out .= sprintf( '<option value="%1$d"%2$s>%3$s</option>', (int) $image->ID, selected( $value, (string) $image->ID, false ), esc_html( get_the_title( $image ) ) );
			}
			return $out . '</select><p class="description">' . esc_html__( 'Neue Bilder zuerst unter Medien hochladen.', 'emposo' ) . '</p>';
		default:
			return sprintf( '<input type="text" name="%1$s" value="%2$s" class="regular-text">', esc_attr( $name ), esc_attr( $value ) );
	}
}

/**
 * The fields on the edit-term screen.
 *
 * @param WP_Term $term     The term.
 * @param string  $taxonomy Taxonomy.
 */
function render_term_fields( WP_Term $term, string $taxonomy ): void {
	wp_nonce_field( TERM_NONCE, TERM_NONCE );
	foreach ( term_field_map()[ $taxonomy ] ?? array() as $key => list( $label, $type ) ) {
		printf(
			'<tr class="form-field"><th scope="row"><label>%1$s</label></th><td>%2$s</td></tr>',
			esc_html( $label ),
			term_input( 'emposo_term[' . $key . ']', $type, (string) get_term_meta( $term->term_id, '_emposo_' . $key, true ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts in term_input().
		);
	}
}

/**
 * The fields on the add-term form.
 *
 * @param string $taxonomy Taxonomy.
 */
function render_new_term_fields( string $taxonomy ): void {
	wp_nonce_field( TERM_NONCE, TERM_NONCE );
	foreach ( term_field_map()[ $taxonomy ] ?? array() as $key => list( $label, $type ) ) {
		if ( 'term_order' === $key ) {
			continue;
		}
		printf(
			'<div class="form-field"><label>%1$s</label>%2$s</div>',
			esc_html( $label ),
			term_input( 'emposo_term[' . $key . ']', $type, '' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts in term_input().
		);
	}
}

/**
 * Save the fields (and give a new term an order so it renders).
 *
 * @param int $term_id Term ID.
 */
function save_term_fields( int $term_id ): void {
	$term = get_term( $term_id );
	if ( ! $term instanceof WP_Term || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	if ( isset( $_POST[ TERM_NONCE ] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ TERM_NONCE ] ) ), TERM_NONCE )
		&& current_user_can( 'manage_categories' ) ) {
		$posted = isset( $_POST['emposo_term'] ) && is_array( $_POST['emposo_term'] ) ? wp_unslash( $_POST['emposo_term'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each value passes the registered sanitize callback.
		foreach ( term_field_map()[ $term->taxonomy ] ?? array() as $key => list( , $type ) ) {
			if ( 'checkbox' === $type ) {
				update_term_meta( $term_id, '_emposo_' . $key, isset( $posted[ $key ] ) ? 1 : 0 );
				continue;
			}
			if ( isset( $posted[ $key ] ) ) {
				update_term_meta( $term_id, '_emposo_' . $key, wp_slash( (string) $posted[ $key ] ) );
			}
		}
	}

	if ( '' === (string) get_term_meta( $term_id, '_emposo_term_order', true ) ) {
		$max    = 0;
		$others = get_terms(
			array(
				'taxonomy'   => $term->taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		foreach ( is_array( $others ) ? $others : array() as $other ) {
			$max = max( $max, (int) get_term_meta( (int) $other, '_emposo_term_order', true ) );
		}
		update_term_meta( $term_id, '_emposo_term_order', $max + 10 );
	}
}
