<?php
/**
 * Content model: taxonomies, post types and registered meta.
 *
 * ROUTING RULE: one resolver per URL prefix. A path prefix is owned by exactly
 * one of the Page tree, one CPT rewrite, or one taxonomy rewrite — never two.
 * That removes rewrite collisions by construction rather than by rule-order
 * luck, and it is why almost everything here is deliberately NOT public.
 *
 * Concretely, three WordPress behaviours force the design:
 *
 * 1. CPT and taxonomy rewrites are registered via add_permastruct and land
 *    ABOVE the page rules. A Page whose path starts with such a slug 404s: the
 *    rule sets post_type + name, no post of that type has that slug, and
 *    WP_Query returns nothing. `/expertise/` carries two hand-authored group
 *    Pages plus eight discipline Pages, so nothing may own that prefix but the
 *    Page tree.
 * 2. use_verbose_page_rules does not rescue a CPT-shadowed Page. The verbose
 *    fallback in WP::parse_request() only re-validates matches that produced a
 *    `pagename` query var; a match that produced post_type + name is never
 *    re-checked.
 * 3. $wp_rewrite->rules is keyed by regex, so a CPT and a taxonomy sharing a
 *    rewrite slug both generate `expertise/([^/]+)/?$` and whichever registers
 *    last silently overwrites the other, with no warning anywhere.
 *
 * So: the Page tree owns /expertise/, /portfolio/ and /branchen/; exactly one
 * CPT owns /case-studies/<slug>/; and all three classification taxonomies are
 * rewrite => false, contributing no URLs at all. Every derived list is then an
 * indexed tax_query rather than a meta_query.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\ContentModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Taxonomy and post type names, so typos fail fast instead of silently. */
const TAX_DISCIPLINE = 'emposo_discipline';
const TAX_INDUSTRY   = 'emposo_industry';
const TAX_OUTCOME    = 'emposo_outcome';

const CPT_CASE_STUDY = 'emposo_case_study';
/** The English twin of a case study: its own records, the one resolver of /en/case-studies/. */
const CPT_CASE_STUDY_EN = 'emposo_case_study_en';
const CPT_PERSON     = 'emposo_person';
const CPT_ENQUIRY    = 'emposo_enquiry';

/**
 * Taxonomies register at priority 5, before the post types at 10, so
 * show_admin_column and the REST schema attach on the first pass.
 */
add_action( 'init', __NAMESPACE__ . '\\register_taxonomies', 5 );
add_action( 'init', __NAMESPACE__ . '\\register_post_types', 10 );
add_action( 'init', __NAMESPACE__ . '\\register_meta_fields', 10 );

/**
 * Arguments shared by all three classification taxonomies.
 *
 * `public => false` plus `rewrite => false` is the load-bearing part: these
 * classify content for querying and never appear in a URL, so they cannot
 * collide with the Page tree.
 *
 * @param bool $hierarchical Whether terms nest.
 * @return array<string, mixed>
 */
function classification_args( bool $hierarchical ): array {
	return array(
		'public'             => false,
		'publicly_queryable' => false,
		'rewrite'            => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'show_admin_column'  => true,
		'show_in_nav_menus'  => false,
		'show_tagcloud'      => false,
		'hierarchical'       => $hierarchical,
		'sort'               => true,
		'capabilities'       => array(
			'manage_terms' => 'manage_categories',
			'edit_terms'   => 'manage_categories',
			'delete_terms' => 'manage_categories',
			'assign_terms' => 'edit_posts',
		),
	);
}

/**
 * Register the classification taxonomies.
 */
function register_taxonomies(): void {
	/*
	 * Hierarchical: `engineering` and `technology` are parent terms, the eight
	 * disciplines are children. The parent doubles as the group, so
	 * `include_children` makes "every Engineering case study" one indexed hop
	 * instead of a two-query dance.
	 */
	register_taxonomy(
		TAX_DISCIPLINE,
		array( CPT_CASE_STUDY, CPT_CASE_STUDY_EN ),
		array_merge(
			classification_args( true ),
			array(
				'labels' => array(
					'name'          => __( 'Disziplinen', 'emposo' ),
					'singular_name' => __( 'Disziplin', 'emposo' ),
					'menu_name'     => __( 'Disziplinen', 'emposo' ),
					'all_items'     => __( 'Alle Disziplinen', 'emposo' ),
					'edit_item'     => __( 'Disziplin bearbeiten', 'emposo' ),
					'add_new_item'  => __( 'Disziplin hinzufügen', 'emposo' ),
					'parent_item'   => __( 'Übergeordneter Bereich', 'emposo' ),
				),
			)
		)
	);

	/*
	 * Hierarchical with `automotive` as a child of `industrial`. This single
	 * structure models three things at once: the space-separated
	 * `filter: 'industrial automotive'` token pair, the `project.industry`
	 * display label (the deepest assigned term's name), and the filter bar's
	 * seven buttons for five industry pages — `automotive` is a filter value
	 * with no page of its own.
	 */
	register_taxonomy(
		TAX_INDUSTRY,
		array( CPT_CASE_STUDY, CPT_CASE_STUDY_EN ),
		array_merge(
			classification_args( true ),
			array(
				'labels' => array(
					'name'          => __( 'Branchen', 'emposo' ),
					'singular_name' => __( 'Branche', 'emposo' ),
					'menu_name'     => __( 'Branchen', 'emposo' ),
					'all_items'     => __( 'Alle Branchen', 'emposo' ),
					'edit_item'     => __( 'Branche bearbeiten', 'emposo' ),
					'add_new_item'  => __( 'Branche hinzufügen', 'emposo' ),
					'parent_item'   => __( 'Übergeordnete Branche', 'emposo' ),
				),
			)
		)
	);

	// Flat: optimize | transform | scale. Drives data-outcome and the
	// projects-optimize / -transform / -scale card selections.
	register_taxonomy(
		TAX_OUTCOME,
		array( CPT_CASE_STUDY, CPT_CASE_STUDY_EN ),
		array_merge(
			classification_args( false ),
			array(
				'labels' => array(
					'name'          => __( 'Wirkungen', 'emposo' ),
					'singular_name' => __( 'Wirkung', 'emposo' ),
					'menu_name'     => __( 'Wirkungen', 'emposo' ),
					'all_items'     => __( 'Alle Wirkungen', 'emposo' ),
					'edit_item'     => __( 'Wirkung bearbeiten', 'emposo' ),
					'add_new_item'  => __( 'Wirkung hinzufügen', 'emposo' ),
				),
			)
		)
	);
}

/**
 * Register the post types.
 */
function register_post_types(): void {
	/*
	 * has_archive => false is LOAD-BEARING, not a default.
	 *
	 * With has_archive true (or 'case-studies'), WordPress additionally emits a
	 * `case-studies/?$` rule ABOVE the page rules, and the hand-authored
	 * /case-studies/ hub Page 404s. The single rule this CPT does emit,
	 * `case-studies/([^/]+)/?$`, requires a non-empty second segment, so the
	 * bare hub path falls through to the page rule as intended.
	 *
	 * There is a unit test asserting this stays false, because flipping it looks
	 * like an improvement and the breakage is remote from the change.
	 */
	register_post_type(
		CPT_CASE_STUDY,
		array(
			'labels'              => array(
				'name'          => __( 'Case Studies', 'emposo' ),
				'singular_name' => __( 'Case Study', 'emposo' ),
				'menu_name'     => __( 'Case Studies', 'emposo' ),
				'add_new_item'  => __( 'Case Study hinzufügen', 'emposo' ),
				'edit_item'     => __( 'Case Study bearbeiten', 'emposo' ),
				'all_items'     => __( 'Alle Case Studies', 'emposo' ),
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'case-studies',
				'with_front' => false,
				'feeds'      => false,
				'pages'      => false,
			),
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
			'taxonomies'          => array( TAX_DISCIPLINE, TAX_INDUSTRY, TAX_OUTCOME ),
			'show_in_rest'        => true,
			'rest_base'           => 'case-studies',
			'menu_icon'           => 'dashicons-portfolio',
			'menu_position'       => 21,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'map_meta_cap'        => true,
			'delete_with_user'    => false,
		)
	);

	/*
	 * The English case studies (reference/static docs/i18n.md): separate
	 * records, so an editor edits each language on its own, linked to the
	 * German one by _emposo_translation_of and sharing its terms. has_archive
	 * stays false for the same reason as above; no Page owns /en/case-studies/.
	 */
	register_post_type(
		CPT_CASE_STUDY_EN,
		array(
			'labels'              => array(
				'name'          => __( 'Case Studies (EN)', 'emposo' ),
				'singular_name' => __( 'Case Study (EN)', 'emposo' ),
				'menu_name'     => __( 'Case Studies (EN)', 'emposo' ),
				'add_new_item'  => __( 'English case study hinzufügen', 'emposo' ),
				'edit_item'     => __( 'English case study bearbeiten', 'emposo' ),
				'all_items'     => __( 'Alle Case Studies (EN)', 'emposo' ),
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'en/case-studies',
				'with_front' => false,
				'feeds'      => false,
				'pages'      => false,
			),
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
			'taxonomies'          => array( TAX_DISCIPLINE, TAX_INDUSTRY, TAX_OUTCOME ),
			'show_in_rest'        => true,
			'rest_base'           => 'case-studies-en',
			'menu_icon'           => 'dashicons-translation',
			'menu_position'       => 22,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'map_meta_cap'        => true,
			'delete_with_user'    => false,
		)
	);

	/*
	 * Seven people, two with bios. Not public: the roster renders inside
	 * /about-us/, and a person has no page of their own.
	 *
	 * The "never invent roles or bios" rule is enforced structurally by the
	 * block that renders this, not by a note: a full profile renders only when
	 * post_content is non-empty, a photo tile only when a featured image
	 * exists, and otherwise an initials tile. There is no placeholder path and
	 * no field an editor can half-fill into a fabricated role.
	 */
	register_post_type(
		CPT_PERSON,
		array(
			'labels'              => array(
				'name'          => __( 'Personen', 'emposo' ),
				'singular_name' => __( 'Person', 'emposo' ),
				'menu_name'     => __( 'Personen', 'emposo' ),
				'add_new_item'  => __( 'Person hinzufügen', 'emposo' ),
				'edit_item'     => __( 'Person bearbeiten', 'emposo' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions', 'page-attributes' ),
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-groups',
			'menu_position'       => 22,
			'hierarchical'        => false,
			'exclude_from_search' => true,
			'map_meta_cap'        => true,
			'delete_with_user'    => false,
		)
	);

	/*
	 * Registered but intentionally invisible. The contact form stays a mailto:
	 * handoff for this port, so there is nothing to store yet; show_ui => false
	 * keeps "port only" from growing an empty admin screen while leaving the
	 * type in place for the deferred form backend.
	 */
	register_post_type(
		CPT_ENQUIRY,
		array(
			'labels'              => array(
				'name'          => __( 'Anfragen', 'emposo' ),
				'singular_name' => __( 'Anfrage', 'emposo' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
			'capability_type'     => array( 'emposo_enquiry', 'emposo_enquiries' ),
			'map_meta_cap'        => true,
			'delete_with_user'    => false,
		)
	);
}

/**
 * Permission callback for protected meta.
 *
 * Underscore-prefixed meta is protected, and protected meta with show_in_rest
 * REQUIRES an explicit auth_callback — without one the field is silently
 * invisible to the block editor, which reads through REST.
 *
 * @param bool   $allowed Whether the user can act.
 * @param string $meta_key Meta key.
 * @param int    $post_id Post ID.
 * @return bool
 */
function can_edit_meta( $allowed, $meta_key, $post_id ): bool {
	unset( $allowed, $meta_key );

	return current_user_can( 'edit_post', (int) $post_id );
}

/**
 * Sanitise a metric string.
 *
 * Deliberately only sanitize_text_field: the values carry qualifiers that are
 * factual claims and must survive byte-for-byte — '>70 %', '80.000 €', '27+',
 * '8+'. Nothing in the import or render path may normalise whitespace, convert
 * the euro sign, or strip '+' or '>'.
 *
 * @param mixed $value Raw value.
 */
function sanitize_claim( $value ): string {
	return sanitize_text_field( (string) $value );
}

/**
 * Sanitise a route path used as an import identity key.
 *
 * Two reasons this cannot be `sanitize_title`, both discovered by testing a
 * second import run:
 *
 * 1. sanitize_title() is for slugs, not paths. It turns '/expertise/engineering/'
 *    into 'expertise-engineering', collapsing the path structure that makes the
 *    key unique.
 * 2. Worse, the meta API invokes sanitize callbacks as
 *    ($value, $meta_key, $meta_type). sanitize_title()'s SECOND parameter is
 *    $fallback_title, returned when the sanitised result is empty — so the
 *    front page's key '/' was stored as the literal string
 *    '_emposo_source_slug'. The lookup then never matched, every rerun tried to
 *    create a second front page, and the slug assertion fired on 'startseite-2'.
 *
 * The general lesson: never pass a core function with optional extra parameters
 * straight into a sanitize_callback.
 *
 * @param mixed $value Raw value.
 */
function sanitize_route_path( $value ): string {
	$path = (string) $value;

	// Route paths are ASCII by contract; keep the characters that make them
	// paths and drop anything else rather than transliterating.
	$path = preg_replace( '#[^a-z0-9\-_/]#i', '', $path );

	if ( ! is_string( $path ) || '' === $path ) {
		return '/';
	}

	return '/' . trim( $path, '/' ) . ( '/' === $path ? '' : '/' );
}

/**
 * Sanitise an ordered list of post IDs.
 *
 * Stored as one atomic array rather than multiple single => false rows, whose
 * order depends on meta_id and silently changes when an editor removes and
 * re-adds an item.
 *
 * @param mixed $value Raw value.
 * @return int[]
 */
function sanitize_id_list( $value ): array {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$ids = array_map( 'absint', $value );
	$ids = array_filter(
		$ids,
		static function ( int $id ): bool {
			return $id > 0 && 'publish' === get_post_status( $id );
		}
	);

	return array_values( array_unique( $ids ) );
}

/**
 * Sanitise an ordered list of short strings.
 *
 * Applies sanitize_text_field per item, which preserves the qualifiers these
 * carry —
 * 'Über 70 % weniger Prüfaufwand', 'Bis zu 80.000 EUR ... pro Monat'. Nothing
 * here may normalise whitespace, convert a currency symbol, or strip '+' or
 * '>': each is a factual claim.
 *
 * @param mixed $value Raw value.
 * @return string[]
 */
function sanitize_string_list( $value ): array {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$items = array_map( 'sanitize_text_field', array_map( 'strval', $value ) );

	return array_values(
		array_filter(
			$items,
			static function ( string $item ): bool {
				return '' !== trim( $item );
			}
		)
	);
}

/**
 * Register post meta.
 */
function register_meta_fields(): void {
	$auth = __NAMESPACE__ . '\\can_edit_meta';

	// --- case study -------------------------------------------------------
	$claim_fields = array(
		'_emposo_metric'         => __( 'Kennzahl', 'emposo' ),
		'_emposo_metric_label'   => __( 'Kennzahl-Beschriftung', 'emposo' ),
		'_emposo_industry_label' => __( 'Branche (Anzeige)', 'emposo' ),
		'_emposo_filter'         => __( 'Branchen-Filterwerte (Leerzeichen-getrennt)', 'emposo' ),
	);
	foreach ( $claim_fields as $key => $label ) {
		register_post_meta(
			CPT_CASE_STUDY,
			$key,
			array(
				'type'              => 'string',
				'description'       => $label,
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_claim',
				'auth_callback'     => $auth,
			)
		);
	}

	/*
	 * Prose that appears once, on its own page.
	 *
	 * The plan called for these to be block content, on the sound argument that
	 * prose in a meta field is a worse editorial surface than the block editor.
	 * They are registered meta here instead, deliberately, for one reason: the
	 * renderers must emit the static build's exact markup — a case study's
	 * results are a <ul class="result-list">, not core/list's
	 * <ul class="wp-block-list"> — and reproducing that from core blocks
	 * requires custom wrapper blocks, which require a JS build step this port
	 * does not yet have.
	 *
	 * So the trade is: editable now through a meta box (pure PHP, real UI,
	 * revisions), with the renderer in full control of markup, and a clean
	 * upgrade path to dynamic blocks later — the storage stays the same, only
	 * the editing surface changes. Recorded as a deviation rather than a
	 * silent simplification.
	 */
	// One sentence per column (workbook cases); the _list fields below hold
	// the deck cases' bullets and win when non-empty.
	foreach ( array(
		'_emposo_challenge' => __( 'Herausforderung', 'emposo' ),
		'_emposo_solution'  => __( 'Lösung', 'emposo' ),
	) as $key => $label ) {
		register_post_meta(
			CPT_CASE_STUDY,
			$key,
			array(
				'type'              => 'string',
				'description'       => $label,
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => $auth,
			)
		);
	}

	$list_fields = array(
		CPT_CASE_STUDY => array( '_emposo_results', '_emposo_challenge_list', '_emposo_solution_list', '_emposo_facts' ),
		CPT_PERSON     => array( '_emposo_person_roles' ),
	);
	foreach ( $list_fields as $type => $keys ) {
		foreach ( $keys as $key ) {
			register_post_meta(
				$type,
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
					'sanitize_callback' => __NAMESPACE__ . '\\sanitize_string_list',
					'auth_callback'     => $auth,
				)
			);
		}
	}

	// --- people -----------------------------------------------------------
	register_post_meta(
		CPT_PERSON,
		'_emposo_person_linkedin',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => static function ( $value ): string {
				$url  = esc_url_raw( (string) $value, array( 'https' ) );
				$host = wp_parse_url( $url, PHP_URL_HOST );

				// Host allowlist: the roster links one real profile, and an open
				// URL field on a public page is an open redirect surface.
				if ( ! is_string( $host ) || ! preg_match( '/(^|\.)linkedin\.com$/', $host ) ) {
					return '';
				}

				return $url;
			},
			'auth_callback'     => $auth,
		)
	);

	// --- attachments ------------------------------------------------------
	// Maps a media item back to its manifest key, so emposo_picture() can be
	// called with either an attachment ID or the key the templates already use.
	register_post_meta(
		'attachment',
		'_emposo_asset_key',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => $auth,
		)
	);

	// --- importer bookkeeping --------------------------------------------
	// Not exposed over REST: these are provenance, not content.
	foreach ( array( 'page', CPT_CASE_STUDY, CPT_PERSON, 'attachment' ) as $type ) {
		register_post_meta(
			$type,
			'_emposo_source_slug',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => false,
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_route_path',
				'auth_callback'     => '__return_false',
			)
		);
	}

	/*
	 * Route lock. Twelve of the 35 routes are Pages, so a single edited
	 * slug or changed parent silently changes a live URL — the largest risk in
	 * the project, since URL preservation is a hard requirement.
	 */
	foreach ( array( 'page', CPT_CASE_STUDY ) as $type ) {
		register_post_meta(
			$type,
			'_emposo_route_locked',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => false,
				'show_in_rest'      => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => '__return_false',
			)
		);
	}

	/*
	 * The English case studies carry exactly the German fields, mirrored from
	 * the registry so the two types cannot drift, plus the link to their
	 * German twin.
	 */
	foreach ( get_registered_meta_keys( 'post', CPT_CASE_STUDY ) as $key => $args ) {
		unset( $args['object_subtype'] );
		register_post_meta( CPT_CASE_STUDY_EN, $key, $args );
	}
	register_post_meta(
		CPT_CASE_STUDY_EN,
		'_emposo_translation_of',
		array(
			'type'              => 'integer',
			'description'       => __( 'Deutsche Case Study (Post-ID)', 'emposo' ),
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => $auth,
		)
	);
}
