<?php
/**
 * Job postings as posts: one per job and language, edited in the classic editor.
 *
 * Until 2026-09-29 the jobs were one option array per language with no admin
 * UI. Now each posting is a post:
 *
 * - Title        = job title
 * - Slug         = the card's anchor id (/karriere/#<slug>)
 * - Editor body  = the intro paragraphs, then one heading plus bullet list
 *                  per section ("Deine Aufgaben", "Dein Profil", ...)
 * - Meta box     = the tags (location, contract, start), the tagline and the
 *                  closing sentence
 * - Order        = menu_order
 *
 * jobs_list() reads the posts of the current language through job_entries();
 * while no post exists yet it falls back to the seeded option, so the
 * migration (`wp emposo migrate jobs`) can run at any time.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Editorial;

use DOMDocument;
use DOMElement;
use WP_Post;
use function Emposo\Core\ContentModel\sanitize_string_list;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CPT_JOB    = 'emposo_job';
const CPT_JOB_EN = 'emposo_job_en';
const JOB_NONCE  = 'emposo_job_fields';

add_action( 'init', __NAMESPACE__ . '\\register_jobs', 11 );
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_job_box' );
add_action( 'save_post', __NAMESPACE__ . '\\save_job_box', 10, 2 );

/**
 * Register both job types. Not public: a job has no page of its own.
 */
function register_jobs(): void {
	foreach ( array(
		CPT_JOB    => array( __( 'Stellen', 'emposo' ), __( 'Stelle', 'emposo' ), 23 ),
		CPT_JOB_EN => array( __( 'Stellen (EN)', 'emposo' ), __( 'Stelle (EN)', 'emposo' ), 24 ),
	) as $type => list( $plural, $singular, $position ) ) {
		register_post_type(
			$type,
			array(
				'labels'              => array(
					'name'          => $plural,
					'singular_name' => $singular,
					'menu_name'     => $plural,
					/* translators: %s: singular label. */
					'add_new_item'  => sprintf( __( '%s hinzufügen', 'emposo' ), $singular ),
					/* translators: %s: singular label. */
					'edit_item'     => sprintf( __( '%s bearbeiten', 'emposo' ), $singular ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'supports'            => array( 'title', 'editor', 'revisions', 'page-attributes' ),
				'menu_icon'           => CPT_JOB === $type ? 'dashicons-id' : 'dashicons-id-alt',
				'menu_position'       => $position,
				'hierarchical'        => false,
				'exclude_from_search' => true,
				'map_meta_cap'        => true,
				'delete_with_user'    => false,
			)
		);
	}

	foreach ( array( CPT_JOB, CPT_JOB_EN ) as $type ) {
		foreach ( array( '_emposo_job_tagline', '_emposo_job_apply' ) as $key ) {
			register_post_meta(
				$type,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => 'Emposo\\Core\\ContentModel\\can_edit_meta',
				)
			);
		}
		register_post_meta(
			$type,
			'_emposo_job_meta',
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => 'Emposo\\Core\\ContentModel\\sanitize_string_list',
				'auth_callback'     => 'Emposo\\Core\\ContentModel\\can_edit_meta',
			)
		);
	}
}

/**
 * Register the box.
 */
function add_job_box(): void {
	foreach ( array( CPT_JOB, CPT_JOB_EN ) as $type ) {
		add_meta_box( 'emposo-job-fields', __( 'Stellenangaben', 'emposo' ), __NAMESPACE__ . '\\render_job_box', $type, 'normal', 'high' );
	}
}

/**
 * Render the fields.
 *
 * @param WP_Post $post The job.
 */
function render_job_box( WP_Post $post ): void {
	wp_nonce_field( JOB_NONCE, JOB_NONCE );
	$tags = get_post_meta( $post->ID, '_emposo_job_meta', true );

	echo '<p class="description">' . esc_html__( 'Textfeld oben: zuerst die Einleitung (der erste Absatz steht auf der Karte, der Rest unter „Mehr lesen“), dann je Abschnitt eine Überschrift und eine Aufzählung.', 'emposo' ) . '</p>';
	echo '<table class="form-table" role="presentation"><tbody>';
	printf(
		'<tr><th scope="row"><label for="emposo-job-meta">%1$s</label></th><td><textarea class="large-text" rows="4" id="emposo-job-meta" name="emposo_job_meta">%2$s</textarea><p class="description">%3$s</p></td></tr>',
		esc_html__( 'Eckdaten', 'emposo' ),
		esc_textarea( is_array( $tags ) ? implode( "\n", array_map( 'strval', $tags ) ) : '' ),
		esc_html__( 'Ort, Arbeitsmodell, Vertrag, Start: ein Eintrag pro Zeile.', 'emposo' )
	);
	printf(
		'<tr><th scope="row"><label for="emposo-job-tagline">%1$s</label></th><td><input type="text" class="large-text" id="emposo-job-tagline" name="emposo_job_tagline" value="%2$s"></td></tr>',
		esc_html__( 'Unterzeile', 'emposo' ),
		esc_attr( (string) get_post_meta( $post->ID, '_emposo_job_tagline', true ) )
	);
	printf(
		'<tr><th scope="row"><label for="emposo-job-apply">%1$s</label></th><td><textarea class="large-text" rows="2" id="emposo-job-apply" name="emposo_job_apply">%2$s</textarea><p class="description">%3$s</p></td></tr>',
		esc_html__( 'Schlusssatz', 'emposo' ),
		esc_textarea( (string) get_post_meta( $post->ID, '_emposo_job_apply', true ) ),
		esc_html__( 'Steht vor dem Bewerbungslink.', 'emposo' )
	);
	echo '</tbody></table>';
}

/**
 * Save the fields.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    The post.
 */
function save_job_box( int $post_id, WP_Post $post ): void {
	if ( ! in_array( $post->post_type, array( CPT_JOB, CPT_JOB_EN ), true )
		|| wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id )
		|| ! isset( $_POST[ JOB_NONCE ] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ JOB_NONCE ] ) ), JOB_NONCE )
		|| ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_emposo_job_meta', wp_slash( sanitize_string_list( lines( posted( 'emposo_job_meta' ) ) ) ) );
	update_post_meta( $post_id, '_emposo_job_tagline', wp_slash( sanitize_text_field( posted( 'emposo_job_tagline' ) ) ) );
	update_post_meta( $post_id, '_emposo_job_apply', wp_slash( sanitize_text_field( posted( 'emposo_job_apply' ) ) ) );
}

/**
 * The jobs of a language in the shape jobs_list() renders.
 *
 * @param bool $english English postings.
 * @return array<int, array<string, mixed>>|null Null while no job post exists (the caller uses the option).
 */
function job_entries( bool $english ): ?array {
	$posts = get_posts(
		array(
			'post_type'        => $english ? CPT_JOB_EN : CPT_JOB,
			'post_status'      => 'publish',
			'posts_per_page'   => 50,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			),
			'no_found_rows'    => true,
			'suppress_filters' => false,
		)
	);

	if ( ! $posts ) {
		$any = get_posts(
			array(
				'post_type'        => $english ? CPT_JOB_EN : CPT_JOB,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		// Jobs exist but none is published: show none, not the seeded ones.
		return $any ? array() : null;
	}

	$entries = array();
	foreach ( $posts as $post ) {
		$tags      = get_post_meta( $post->ID, '_emposo_job_meta', true );
		$parsed    = parse_job_body( (string) $post->post_content );
		$entries[] = array(
			'slug'     => $post->post_name,
			'title'    => (string) $post->post_title,
			'meta'     => is_array( $tags ) ? array_map( 'strval', $tags ) : array(),
			'tagline'  => (string) get_post_meta( $post->ID, '_emposo_job_tagline', true ),
			'intro'    => $parsed['intro'],
			'sections' => $parsed['sections'],
			'apply'    => (string) get_post_meta( $post->ID, '_emposo_job_apply', true ),
		);
	}

	return $entries;
}

/**
 * Split a job body into intro paragraphs and heading + list sections.
 *
 * Paragraphs before the first heading are the intro. A heading (h2-h5)
 * opens a section; the list items that follow are its bullets. Text is
 * plain: the card markup is fixed.
 *
 * @param string $content Editor content.
 * @return array{intro: string[], sections: array<int, array{title: string, items: string[]}>}
 */
function parse_job_body( string $content ): array {
	$intro    = array();
	$sections = array();
	$empty    = array(
		'intro'    => array(),
		'sections' => array(),
	);
	if ( '' === trim( $content ) ) {
		return $empty;
	}

	$dom      = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8"?><body>' . wpautop( $content ) . '</body>' );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	$body = $dom->getElementsByTagName( 'body' )->item( 0 );
	if ( null === $body ) {
		return $empty;
	}

	$clean = static function ( string $text ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	};

	/**
	 * The section being filled.
	 *
	 * @var array{title: string, items: string[]}|null $open
	 */
	$open = null;
	foreach ( $body->childNodes as $node ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API.
		if ( ! $node instanceof DOMElement ) {
			continue;
		}
		$tag  = strtolower( $node->tagName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API.
		$text = $clean( $node->textContent ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API.

		if ( in_array( $tag, array( 'h2', 'h3', 'h4', 'h5' ), true ) ) {
			if ( null !== $open ) {
				$sections[] = $open;
			}
			$open = array(
				'title' => $text,
				'items' => array(),
			);
			continue;
		}

		if ( null !== $open && in_array( $tag, array( 'ul', 'ol' ), true ) ) {
			foreach ( $node->getElementsByTagName( 'li' ) as $item ) {
				$line = $clean( $item->textContent ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API.
				if ( '' !== $line ) {
					$open['items'][] = $line;
				}
			}
			continue;
		}

		if ( null === $open && 'p' === $tag && '' !== $text ) {
			$intro[] = $text;
		}
	}
	if ( null !== $open ) {
		$sections[] = $open;
	}

	return array(
		'intro'    => $intro,
		'sections' => $sections,
	);
}

/**
 * Build a job body from a seeded option entry (migration and tests).
 *
 * @param array<string, mixed> $job Option entry.
 */
function job_body_from_option( array $job ): string {
	$html = '';
	foreach ( (array) ( $job['intro'] ?? array() ) as $paragraph ) {
		$html .= esc_html( (string) $paragraph ) . "\n\n";
	}
	foreach ( (array) ( $job['sections'] ?? array() ) as $section ) {
		$html .= '<h4>' . esc_html( (string) ( $section['title'] ?? '' ) ) . "</h4>\n<ul>\n";
		foreach ( (array) ( $section['items'] ?? array() ) as $item ) {
			$html .= "\t<li>" . esc_html( (string) $item ) . "</li>\n";
		}
		$html .= "</ul>\n\n";
	}

	return trim( $html );
}
