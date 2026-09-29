<?php
/**
 * The person box: roles, English bio and LinkedIn for the management cards.
 *
 * Name = title, German bio = the classic editor body (paragraphs), portrait =
 * featured image. The rest was seeded as meta without any input; this box
 * is that input.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Editorial;

use WP_Post;
use function Emposo\Core\ContentModel\sanitize_string_list;
use const Emposo\Core\ContentModel\CPT_PERSON;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PERSON_NONCE = 'emposo_person_fields';

add_action( 'init', __NAMESPACE__ . '\\register_person_meta', 20 );
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_person_box' );
add_action( 'save_post_' . CPT_PERSON, __NAMESPACE__ . '\\save_person_box', 10, 2 );

/**
 * Register the English roster meta the importer already writes.
 */
function register_person_meta(): void {
	foreach ( array( '_emposo_person_roles_en', '_emposo_person_bio_en' ) as $key ) {
		register_post_meta(
			CPT_PERSON,
			$key,
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				'sanitize_callback' => 'Emposo\\Core\\ContentModel\\sanitize_string_list',
				'auth_callback'     => 'Emposo\\Core\\ContentModel\\can_edit_meta',
			)
		);
	}
}

/**
 * Register the box.
 */
function add_person_box(): void {
	add_meta_box( 'emposo-person-fields', __( 'Profil', 'emposo' ), __NAMESPACE__ . '\\render_person_box', CPT_PERSON, 'normal', 'high' );
}

/**
 * Render the fields.
 *
 * @param WP_Post $post The person.
 */
function render_person_box( WP_Post $post ): void {
	wp_nonce_field( PERSON_NONCE, PERSON_NONCE );

	$list = static function ( string $key, string $glue ) use ( $post ): string {
		$items = get_post_meta( $post->ID, $key, true );

		return is_array( $items ) ? implode( $glue, array_map( 'strval', $items ) ) : '';
	};

	echo '<p class="description">' . esc_html__( 'Name = Titel, Foto = Beitragsbild, deutsche Biografie = Textfeld oben (Absätze durch Leerzeile trennen).', 'emposo' ) . '</p>';
	echo '<table class="form-table" role="presentation"><tbody>';
	printf(
		'<tr><th scope="row"><label for="emposo-roles">%1$s</label></th><td><textarea class="large-text" rows="3" id="emposo-roles" name="emposo_person_roles">%2$s</textarea><p class="description">%3$s</p></td></tr>',
		esc_html__( 'Rollen (DE)', 'emposo' ),
		esc_textarea( $list( '_emposo_person_roles', "\n" ) ),
		esc_html__( 'Eine Rolle pro Zeile.', 'emposo' )
	);
	printf(
		'<tr><th scope="row"><label for="emposo-roles-en">%1$s</label></th><td><textarea class="large-text" rows="3" id="emposo-roles-en" name="emposo_person_roles_en">%2$s</textarea><p class="description">%3$s</p></td></tr>',
		esc_html__( 'Rollen (EN)', 'emposo' ),
		esc_textarea( $list( '_emposo_person_roles_en', "\n" ) ),
		esc_html__( 'Eine Rolle pro Zeile.', 'emposo' )
	);
	printf(
		'<tr><th scope="row"><label for="emposo-bio-en">%1$s</label></th><td><textarea class="large-text" rows="8" id="emposo-bio-en" name="emposo_person_bio_en">%2$s</textarea><p class="description">%3$s</p></td></tr>',
		esc_html__( 'Biografie (EN)', 'emposo' ),
		esc_textarea( $list( '_emposo_person_bio_en', "\n\n" ) ),
		esc_html__( 'Absätze durch eine Leerzeile trennen.', 'emposo' )
	);
	printf(
		'<tr><th scope="row"><label for="emposo-linkedin">%1$s</label></th><td><input type="url" class="large-text" id="emposo-linkedin" name="emposo_person_linkedin" value="%2$s" placeholder="https://www.linkedin.com/in/…"><p class="description">%3$s</p></td></tr>',
		esc_html__( 'LinkedIn', 'emposo' ),
		esc_attr( (string) get_post_meta( $post->ID, '_emposo_person_linkedin', true ) ),
		esc_html__( 'Nur linkedin.com-Adressen; leer lassen für keinen Link.', 'emposo' )
	);
	echo '</tbody></table>';
}

/**
 * Save the fields.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    The post.
 */
function save_person_box( int $post_id, WP_Post $post ): void {
	unset( $post );
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id )
		|| ! isset( $_POST[ PERSON_NONCE ] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ PERSON_NONCE ] ) ), PERSON_NONCE )
		|| ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_emposo_person_roles', wp_slash( sanitize_string_list( lines( posted( 'emposo_person_roles' ) ) ) ) );
	update_post_meta( $post_id, '_emposo_person_roles_en', wp_slash( sanitize_string_list( lines( posted( 'emposo_person_roles_en' ) ) ) ) );
	update_post_meta( $post_id, '_emposo_person_bio_en', wp_slash( sanitize_string_list( paragraphs( posted( 'emposo_person_bio_en' ) ) ) ) );
	// The registered sanitize callback enforces https and the linkedin.com host.
	update_post_meta( $post_id, '_emposo_person_linkedin', wp_slash( trim( posted( 'emposo_person_linkedin' ) ) ) );
}
