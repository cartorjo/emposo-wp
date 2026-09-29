<?php
/**
 * The "Projektinhalt" box: every case-study field an editor can change.
 *
 * The storage is the registered meta in inc/content-model.php, unchanged, so
 * the renderers, the importer's per-field hashes (an edited field is then
 * protected from a re-import) and the REST schema all keep working. Lists are
 * textareas with one item per line: native, no repeater script.
 *
 * Structural fields have no input: `_emposo_filter` is derived from the
 * chosen Branchen terms, and the English twin follows the German terms.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Editorial;

use WP_Post;
use WP_Term;
use function Emposo\Core\ContentModel\sanitize_claim;
use function Emposo\Core\ContentModel\sanitize_string_list;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY_EN;
use const Emposo\Core\ContentModel\TAX_DISCIPLINE;
use const Emposo\Core\ContentModel\TAX_INDUSTRY;
use const Emposo\Core\ContentModel\TAX_OUTCOME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CASE_NONCE = 'emposo_case_fields';

add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_case_box' );
add_action( 'save_post', __NAMESPACE__ . '\\save_case_box', 10, 2 );
add_action( 'set_object_terms', __NAMESPACE__ . '\\sync_case_terms', 20, 6 );

/**
 * Split a textarea into lines.
 *
 * @param string $text Raw textarea value (unslashed).
 * @return string[]
 */
function lines( string $text ): array {
	$parts = preg_split( '/\R/', $text );

	return is_array( $parts ) ? $parts : array();
}

/**
 * Split a textarea into paragraphs (blank-line separated).
 *
 * @param string $text Raw textarea value (unslashed).
 * @return string[]
 */
function paragraphs( string $text ): array {
	$parts = preg_split( '/\R\s*\R/', trim( $text ) );

	return array_values(
		array_filter(
			array_map( 'trim', is_array( $parts ) ? $parts : array() ),
			static function ( string $item ): bool {
				return '' !== $item;
			}
		)
	);
}

/**
 * The raw POST value of one of our fields, unslashed.
 *
 * @param string $name Field name.
 */
function posted( string $name ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The caller verifies the nonce first; each field is sanitised by its registered meta callback.
	return isset( $_POST[ $name ] ) ? (string) wp_unslash( $_POST[ $name ] ) : '';
}

/**
 * Register the box on both case-study types.
 */
function add_case_box(): void {
	foreach ( array( CPT_CASE_STUDY, CPT_CASE_STUDY_EN ) as $type ) {
		add_meta_box( 'emposo-case-fields', __( 'Projektinhalt', 'emposo' ), __NAMESPACE__ . '\\render_case_box', $type, 'normal', 'high' );
	}
}

/**
 * The list fields of a case, in page order.
 *
 * @return array<string, array{0: string, 1: string}> Meta key => [label, help].
 */
function case_lists(): array {
	return array(
		'_emposo_facts'          => array( __( 'Projekt', 'emposo' ), __( 'Die Punkte im Kasten „Projekt“. Ein Punkt pro Zeile.', 'emposo' ) ),
		'_emposo_challenge_list' => array( __( 'Herausforderung', 'emposo' ), __( 'Ein Punkt pro Zeile.', 'emposo' ) ),
		'_emposo_solution_list'  => array( __( 'Lösung', 'emposo' ), __( 'Ein Punkt pro Zeile.', 'emposo' ) ),
		'_emposo_results'        => array( __( 'Ergebnis', 'emposo' ), __( 'Ein Punkt pro Zeile.', 'emposo' ) ),
	);
}

/**
 * Render the fields.
 *
 * @param WP_Post $post The case study.
 */
function render_case_box( WP_Post $post ): void {
	wp_nonce_field( CASE_NONCE, CASE_NONCE );

	$value = static function ( string $key ) use ( $post ): string {
		return (string) get_post_meta( $post->ID, $key, true );
	};
	$list  = static function ( string $key ) use ( $post ): string {
		$items = get_post_meta( $post->ID, $key, true );

		return is_array( $items ) ? implode( "\n", array_map( 'strval', $items ) ) : '';
	};

	echo '<table class="form-table" role="presentation"><tbody>';

	printf(
		'<tr><th scope="row"><label for="emposo-metric">%1$s</label></th><td><input type="text" class="regular-text" id="emposo-metric" name="emposo_metric" value="%2$s"><p class="description">%3$s</p></td></tr>',
		esc_html__( 'Kennzahl', 'emposo' ),
		esc_attr( $value( '_emposo_metric' ) ),
		esc_html__( 'Die große Zahl im Seitenkopf und auf der Karte, z. B. „40+“. Leer lassen: dann erscheint kein Kennzahl-Block.', 'emposo' )
	);
	printf(
		'<tr><th scope="row"><label for="emposo-metric-label">%1$s</label></th><td><input type="text" class="large-text" id="emposo-metric-label" name="emposo_metric_label" value="%2$s"></td></tr>',
		esc_html__( 'Text zur Kennzahl', 'emposo' ),
		esc_attr( $value( '_emposo_metric_label' ) )
	);
	printf(
		'<tr><th scope="row"><label for="emposo-industry-label">%1$s</label></th><td><input type="text" class="regular-text" id="emposo-industry-label" name="emposo_industry_label" value="%2$s"><p class="description">%3$s</p></td></tr>',
		esc_html__( 'Branche (Anzeige)', 'emposo' ),
		esc_attr( $value( '_emposo_industry_label' ) ),
		esc_html__( 'Die Branchenzeile über dem Titel. Für den Filter zählen die Branchen rechts.', 'emposo' )
	);

	foreach ( case_lists() as $key => list( $label, $help ) ) {
		$field = str_replace( '_emposo_', 'emposo_', $key );
		$text  = $list( $key );
		// Workbook cases keep one sentence per column; they edit that sentence.
		if ( '' === $text && in_array( $key, array( '_emposo_challenge_list', '_emposo_solution_list' ), true ) ) {
			$single = $value( str_replace( '_list', '', $key ) );
			if ( '' !== $single ) {
				$field = str_replace( '_list', '', $field );
				$text  = $single;
				$help  = __( 'Ein Satz (als Absatz dargestellt). Für eine Aufzählung stattdessen mehrere Zeilen eintragen.', 'emposo' );
			}
		}

		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><textarea class="large-text" rows="5" id="%1$s" name="%1$s">%3$s</textarea><p class="description">%4$s</p></td></tr>',
			esc_attr( $field ),
			esc_html( $label ),
			esc_textarea( $text ),
			esc_html( $help )
		);
	}

	echo '</tbody></table>';

	$twin = twin_of( $post );
	if ( $twin instanceof WP_Post ) {
		printf(
			'<p><a href="%1$s">%2$s</a></p>',
			esc_url( (string) get_edit_post_link( $twin->ID ) ),
			esc_html( CPT_CASE_STUDY === $post->post_type ? __( 'Englische Fassung bearbeiten →', 'emposo' ) : __( 'Deutsche Fassung bearbeiten →', 'emposo' ) )
		);
	}
	if ( CPT_CASE_STUDY_EN === $post->post_type ) {
		echo '<p class="description">' . esc_html__( 'Branchen, Disziplin und Wirkung übernimmt die englische Fassung von der deutschen.', 'emposo' ) . '</p>';
	}
}

/**
 * The other-language record of a case study.
 *
 * @param WP_Post $post A German or English case study.
 */
function twin_of( WP_Post $post ): ?WP_Post {
	if ( CPT_CASE_STUDY_EN === $post->post_type ) {
		$german = get_post( (int) get_post_meta( $post->ID, '_emposo_translation_of', true ) );

		return $german instanceof WP_Post ? $german : null;
	}

	$ids     = get_posts(
		array(
			'post_type'        => CPT_CASE_STUDY_EN,
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_key'         => '_emposo_translation_of', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin screen, 24 rows.
			'meta_value'       => (string) $post->ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Admin screen, 24 rows.
		)
	);
	$english = $ids ? get_post( (int) $ids[0] ) : null;

	return $english instanceof WP_Post ? $english : null;
}

/**
 * Save the fields.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    The post.
 */
function save_case_box( int $post_id, WP_Post $post ): void {
	if ( ! in_array( $post->post_type, array( CPT_CASE_STUDY, CPT_CASE_STUDY_EN ), true )
		|| wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id )
		|| ! isset( $_POST[ CASE_NONCE ] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ CASE_NONCE ] ) ), CASE_NONCE )
		|| ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_emposo_metric', wp_slash( sanitize_claim( posted( 'emposo_metric' ) ) ) );
	update_post_meta( $post_id, '_emposo_metric_label', wp_slash( sanitize_claim( posted( 'emposo_metric_label' ) ) ) );
	update_post_meta( $post_id, '_emposo_industry_label', wp_slash( sanitize_claim( posted( 'emposo_industry_label' ) ) ) );

	foreach ( array_keys( case_lists() ) as $key ) {
		$field = str_replace( '_emposo_', 'emposo_', $key );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $key, wp_slash( sanitize_string_list( lines( posted( $field ) ) ) ) );
			continue;
		}

		// The single-sentence form of a workbook column.
		$single_field = str_replace( '_list', '', $field );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
		if ( isset( $_POST[ $single_field ] ) ) {
			$items = sanitize_string_list( lines( posted( $single_field ) ) );
			if ( count( $items ) > 1 ) {
				update_post_meta( $post_id, $key, wp_slash( $items ) );
				update_post_meta( $post_id, str_replace( '_list', '', $key ), '' );
			} else {
				update_post_meta( $post_id, str_replace( '_list', '', $key ), wp_slash( sanitize_textarea_field( posted( $single_field ) ) ) );
			}
		}
	}
}

/**
 * Keep the filter tokens and the English twin in step with the German terms.
 *
 * `_emposo_filter` holds the space-separated industry slugs the filter bar
 * matches (parents included: 'industrial automotive'). It is recomputed only
 * when the term set differs, so an unchanged case keeps its token order.
 *
 * @param int                    $object_id  Object ID.
 * @param array<int, int|string> $terms     Terms as passed.
 * @param array<int, int>        $tt_ids     Term taxonomy IDs.
 * @param string                 $taxonomy   Taxonomy.
 * @param bool                   $append     Whether terms were appended.
 * @param array<int, int>        $old_tt_ids Previous term taxonomy IDs.
 */
function sync_case_terms( int $object_id, array $terms, array $tt_ids, string $taxonomy, bool $append, array $old_tt_ids ): void {
	unset( $terms, $append );

	static $syncing = false;
	if ( $syncing || ( defined( 'WP_CLI' ) && WP_CLI ) || ! in_array( $taxonomy, array( TAX_INDUSTRY, TAX_DISCIPLINE, TAX_OUTCOME ), true ) ) {
		return;
	}
	$post = get_post( $object_id );
	if ( ! $post instanceof WP_Post || CPT_CASE_STUDY !== $post->post_type ) {
		return;
	}

	$syncing = true;

	if ( TAX_INDUSTRY === $taxonomy ) {
		$tokens   = array();
		$assigned = wp_get_object_terms( $object_id, TAX_INDUSTRY );
		foreach ( is_array( $assigned ) ? $assigned : array() as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			foreach ( array_reverse( get_ancestors( $term->term_id, TAX_INDUSTRY, 'taxonomy' ) ) as $ancestor_id ) {
				$ancestor = get_term( (int) $ancestor_id, TAX_INDUSTRY );
				if ( $ancestor instanceof WP_Term ) {
					$tokens[] = $ancestor->slug;
				}
			}
			$tokens[] = $term->slug;
		}
		$tokens  = array_values( array_unique( $tokens ) );
		$current = preg_split( '/\s+/', trim( (string) get_post_meta( $object_id, '_emposo_filter', true ) ) );
		$current = is_array( $current ) ? array_values(
			array_filter(
				$current,
				static function ( string $item ): bool {
					return '' !== $item;
				}
			)
		) : array();
		sort( $current );
		$sorted = $tokens;
		sort( $sorted );
		if ( $current !== $sorted ) {
			update_post_meta( $object_id, '_emposo_filter', wp_slash( implode( ' ', $tokens ) ) );
		}
	}

	$twin = twin_of( $post );
	if ( $twin instanceof WP_Post && array_values( $tt_ids ) !== array_values( $old_tt_ids ) ) {
		$ids = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( is_array( $ids ) ) {
			wp_set_object_terms( $twin->ID, array_map( 'intval', $ids ), $taxonomy );
			if ( TAX_INDUSTRY === $taxonomy ) {
				update_post_meta( $twin->ID, '_emposo_filter', wp_slash( (string) get_post_meta( $object_id, '_emposo_filter', true ) ) );
			}
		}
	}

	$syncing = false;
}
