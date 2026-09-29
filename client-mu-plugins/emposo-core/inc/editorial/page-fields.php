<?php
/**
 * The "Seitentexte" box: every text and image of a page, grouped by section,
 * plus the SEO title and description (editorial phase 2).
 *
 * The fields come from the templates the page renders (routes.json body
 * parts) and their schemas in data/fields/. Each input shows the current
 * text; saving stores only what differs from the default (inc/fields.php).
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Editorial;

use WP_Post;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY_EN;
use const Emposo\Core\Fields\META_KEY;
use const Emposo\Core\Fields\SEO_DESC;
use const Emposo\Core\Fields\SEO_TITLE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PAGE_FIELDS_NONCE = 'emposo_page_fields';

add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_page_fields_boxes', 10, 2 );
add_action( 'save_post', __NAMESPACE__ . '\\save_page_fields', 10, 2 );

/**
 * Register the boxes.
 *
 * @param string       $post_type Post type.
 * @param WP_Post|null $post      The post.
 */
function add_page_fields_boxes( string $post_type, $post ): void {
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( 'page' === $post_type && page_schemas( $post ) ) {
		add_meta_box( 'emposo-page-fields', __( 'Seitentexte', 'emposo' ), __NAMESPACE__ . '\\render_page_fields', 'page', 'normal', 'high' );
	}
	if ( in_array( $post_type, array( 'page', CPT_CASE_STUDY, CPT_CASE_STUDY_EN ), true ) && '' !== route_record( $post )['url'] ) {
		add_meta_box( 'emposo-seo', __( 'Suchmaschinen (SEO)', 'emposo' ), __NAMESPACE__ . '\\render_seo_fields', $post_type, 'normal', 'default' );
	}
}

/**
 * The contract record of a post's route (unfiltered).
 *
 * @param WP_Post $post The post.
 * @return array{url: string, title: string, description: string}
 */
function route_record( WP_Post $post ): array {
	$url = \Emposo\Core\Fields\route_of( $post );
	foreach ( function_exists( 'emposo_route_contract' ) ? emposo_route_contract() : array() as $route ) {
		if ( '' !== $url && ( $route['url'] ?? '' ) === $url ) {
			return array(
				'url'         => $url,
				'title'       => (string) ( $route['title'] ?? '' ),
				'description' => (string) ( $route['description'] ?? '' ),
			);
		}
	}

	return array(
		'url'         => '',
		'title'       => '',
		'description' => '',
	);
}

/**
 * The page-store schemas of the templates a page renders.
 *
 * @param WP_Post $post A page.
 * @return array<int, array<string, mixed>>
 */
function page_schemas( WP_Post $post ): array {
	$out = array();
	foreach ( \Emposo\Core\Fields\route_templates( \Emposo\Core\Fields\route_of( $post ) ) as $template ) {
		$schema = \Emposo\Core\Fields\schema( $template );
		if ( is_array( $schema ) && 'page' === ( $schema['store'] ?? 'page' ) && ! empty( $schema['fields'] ) ) {
			$out[] = $schema;
		}
	}

	return $out;
}

/**
 * Plain text of a default, for a preview line.
 *
 * @param string $html Markup.
 */
function preview( string $html ): string {
	return wp_html_excerpt( html_entity_decode( wp_strip_all_tags( str_replace( '<br>', ' ', $html ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 60, '…' );
}

/**
 * One field's input row.
 *
 * @param array<string, string> $field     Field definition.
 * @param array<string, string> $overrides Stored overrides.
 */
function field_row( array $field, array $overrides ): string {
	$key   = $field['key'];
	$name  = 'emposo_fields[' . $key . ']';
	$id    = 'emposo-f-' . sanitize_html_class( str_replace( array( '/', '.' ), '-', $key ) );
	$value = $overrides[ $key ] ?? '';

	if ( 'image' === $field['type'] ) {
		$images  = get_posts(
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
		$options = sprintf( '<option value="0">%s</option>', esc_html__( 'Standardbild', 'emposo' ) . ' (' . esc_html( $field['default'] ) . ')' );
		foreach ( $images as $image ) {
			$options .= sprintf( '<option value="%1$d"%2$s>%3$s</option>', (int) $image->ID, selected( $value, (string) $image->ID, false ), esc_html( get_the_title( $image ) ) );
		}

		return sprintf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><select id="%1$s" name="%3$s">%4$s</select></td></tr>', esc_attr( $id ), esc_html( $field['label'] ), esc_attr( $name ), $options );
	}

	$text = '' !== $value ? $value : $field['default'];
	$rows = max( 1, min( 8, (int) ceil( mb_strlen( $text ) / 90 ) ) );
	$help = 'html' === $field['type'] ? '<p class="description">' . esc_html__( 'Hervorgehobene Wörter stehen zwischen <em> und </em>, <br> ist ein Zeilenumbruch.', 'emposo' ) . '</p>' : '';

	return sprintf(
		'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><textarea class="large-text" id="%1$s" name="%3$s" rows="%4$d">%5$s</textarea>%6$s</td></tr>',
		esc_attr( $id ),
		esc_html( $field['label'] ),
		esc_attr( $name ),
		(int) $rows,
		esc_textarea( $text ),
		$help
	);
}

/**
 * Render the page's fields, one table per section.
 *
 * @param WP_Post $post The page.
 */
function render_page_fields( WP_Post $post ): void {
	wp_nonce_field( PAGE_FIELDS_NONCE, PAGE_FIELDS_NONCE );
	$overrides = \Emposo\Core\Fields\overrides( 'page', $post->ID );

	echo '<p class="description">' . esc_html__( 'Alle Texte und Bilder dieser Seite, nach Abschnitten. Ein geleertes Feld stellt den ursprünglichen Text wieder her; das Layout bleibt fest.', 'emposo' ) . '</p>';

	foreach ( page_schemas( $post ) as $schema ) {
		$groups = array();
		foreach ( (array) $schema['fields'] as $field ) {
			$groups[ (string) $field['group'] ][] = array_map( 'strval', $field );
		}
		foreach ( $groups as $group => $fields ) {
			echo '<h3 style="margin:24px 0 0;padding-top:12px;border-top:1px solid #dcdcde">' . esc_html( preview( $group ) ) . '</h3>';
			echo '<table class="form-table" role="presentation"><tbody>';
			foreach ( $fields as $field ) {
				echo field_row( $field, $overrides ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts in field_row().
			}
			echo '</tbody></table>';
		}
	}
}

/**
 * Render the SEO fields.
 *
 * @param WP_Post $post The post.
 */
function render_seo_fields( WP_Post $post ): void {
	// The Seitentexte box already printed the nonce on pages that have one.
	if ( 'page' !== $post->post_type || ! page_schemas( $post ) ) {
		wp_nonce_field( PAGE_FIELDS_NONCE, PAGE_FIELDS_NONCE );
	}
	$route = route_record( $post );
	$title = (string) get_post_meta( $post->ID, SEO_TITLE, true );
	$desc  = (string) get_post_meta( $post->ID, SEO_DESC, true );

	printf(
		'<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="emposo-seo-title-input">%1$s</label></th><td><input type="text" class="large-text" id="emposo-seo-title-input" name="emposo_seo_title" value="%2$s"><p class="description">%3$s</p></td></tr>'
		. '<tr><th scope="row"><label for="emposo-seo-description-input">%4$s</label></th><td><textarea class="large-text" rows="3" id="emposo-seo-description-input" name="emposo_seo_description">%5$s</textarea><p class="description">%6$s</p></td></tr></tbody></table>',
		esc_html__( 'Titel im Browser und bei Google', 'emposo' ),
		esc_attr( '' !== $title ? $title : $route['title'] ),
		esc_html__( 'Etwa 50–60 Zeichen. Gilt auch für Link-Vorschauen (LinkedIn, Teams).', 'emposo' ),
		esc_html__( 'Beschreibung bei Google', 'emposo' ),
		esc_textarea( '' !== $desc ? $desc : $route['description'] ),
		esc_html__( 'Etwa 140–160 Zeichen.', 'emposo' )
	);
}

/**
 * Save the page fields and the SEO fields.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    The post.
 */
function save_page_fields( int $post_id, WP_Post $post ): void {
	if ( ! in_array( $post->post_type, array( 'page', CPT_CASE_STUDY, CPT_CASE_STUDY_EN ), true )
		|| wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id )
		|| ! isset( $_POST[ PAGE_FIELDS_NONCE ] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ PAGE_FIELDS_NONCE ] ) ), PAGE_FIELDS_NONCE )
		|| ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( 'page' === $post->post_type && isset( $_POST['emposo_fields'] ) && is_array( $_POST['emposo_fields'] ) ) {
		$posted = wp_unslash( $_POST['emposo_fields'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per field type below.
		$stored = \Emposo\Core\Fields\overrides( 'page', $post_id );
		foreach ( page_schemas( $post ) as $schema ) {
			foreach ( (array) $schema['fields'] as $field ) {
				$key = (string) $field['key'];
				if ( ! isset( $posted[ $key ] ) ) {
					continue;
				}
				$type = (string) $field['type'];
				if ( 'image' === $type ) {
					$value = absint( $posted[ $key ] );
					$value = $value > 0 ? (string) $value : '';
				} else {
					$value = \Emposo\Core\Fields\sanitize_value( $type, (string) $posted[ $key ] );
					$value = \Emposo\Core\Fields\is_default( $value, (string) $field['default'] ) ? '' : $value;
				}
				if ( '' === $value ) {
					unset( $stored[ $key ] );
				} else {
					$stored[ $key ] = $value;
				}
			}
		}
		update_post_meta( $post_id, META_KEY, wp_slash( $stored ) );
	}

	$route = route_record( $post );
	foreach ( array(
		'emposo_seo_title'       => array( SEO_TITLE, $route['title'] ),
		'emposo_seo_description' => array( SEO_DESC, $route['description'] ),
	) as $input => list( $meta_key, $default ) ) {
		if ( ! isset( $_POST[ $input ] ) ) {
			continue;
		}
		$value = sanitize_text_field( wp_unslash( $_POST[ $input ] ) );
		if ( '' === $value || $value === $default ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, wp_slash( $value ) );
		}
	}
}
