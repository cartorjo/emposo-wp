<?php
/**
 * Media: the English alt text, and modern formats for new uploads.
 *
 * English pages read `_emposo_alt_en` (inc/images.php picture()); until
 * 2026-09-29 it had no input. New uploads are written as WebP sub-sizes, and
 * picture() gives them a srcset, so an editor's image is served like the
 * seeded ones rather than as one 1600 px JPEG.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Editorial;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'attachment_fields_to_edit', __NAMESPACE__ . '\\alt_en_field', 10, 2 );
add_filter( 'attachment_fields_to_save', __NAMESPACE__ . '\\save_alt_en', 10, 2 );
add_filter( 'image_editor_output_format', __NAMESPACE__ . '\\webp_subsizes' );

/**
 * Add "Alternativtext (EN)" below the core alt field.
 *
 * @param array<string, mixed> $fields Fields.
 * @param WP_Post              $post   Attachment.
 * @return array<string, mixed>
 */
function alt_en_field( array $fields, WP_Post $post ): array {
	if ( ! wp_attachment_is_image( $post->ID ) ) {
		return $fields;
	}

	$fields['emposo_alt_en'] = array(
		'label' => __( 'Alternativtext (EN)', 'emposo' ),
		'input' => 'text',
		'value' => (string) get_post_meta( $post->ID, '_emposo_alt_en', true ),
		'helps' => __( 'Für die englischen Seiten. Leer: der deutsche Alternativtext wird verwendet.', 'emposo' ),
	);

	return $fields;
}

/**
 * Save it (core verifies the nonce and capability for this filter).
 *
 * @param array<string, mixed> $post       Attachment data.
 * @param array<string, mixed> $attachment Submitted fields.
 * @return array<string, mixed>
 */
function save_alt_en( array $post, array $attachment ): array {
	$id = (int) ( $post['ID'] ?? 0 );
	if ( $id > 0 && isset( $attachment['emposo_alt_en'] ) && current_user_can( 'edit_post', $id ) ) {
		update_post_meta( $id, '_emposo_alt_en', wp_slash( sanitize_text_field( (string) $attachment['emposo_alt_en'] ) ) );
	}

	return $post;
}

/**
 * Write JPEG and PNG sub-sizes as WebP (every supported browser reads it).
 *
 * @param array<string, string> $formats Source MIME => output MIME.
 * @return array<string, string>
 */
function webp_subsizes( array $formats ): array {
	$formats['image/jpeg'] = 'image/webp';
	$formats['image/png']  = 'image/webp';

	return $formats;
}
