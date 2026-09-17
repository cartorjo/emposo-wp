<?php
/**
 * The shared content renderers, ported from content/render.mjs.
 *
 * String-returning functions rather than template parts, for two reasons the
 * static source makes unavoidable:
 *
 * 1. They compose. A project page calls the card renderer, which calls the
 *    metric renderer and the picture helper. get_template_part() echoes and
 *    cannot be nested into a string; wrapping each call in ob_start() adds a
 *    failure mode for no gain.
 * 2. The static renderers emit NO inter-tag whitespace — each is one long
 *    template literal. An indented PHP template would introduce text nodes
 *    into flex and grid rows, where they affect layout.
 *
 * Data comes from WordPress, never from the JSON export: the export seeds the
 * database, and the database is then the source of truth, so an editor's change
 * shows up here. Every query is an indexed tax_query, post__in or post_parent
 * lookup — never a meta_query.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Fragments;

use WP_Post;
use function Emposo\Core\Cache\remember;
use function Emposo\Core\Images\picture;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY;
use const Emposo\Core\ContentModel\TAX_DISCIPLINE;
use const Emposo\Core\ContentModel\TAX_INDUSTRY;
use const Emposo\Core\ContentModel\TAX_OUTCOME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The decorative arrow. Straight, never diagonal — an explicit owner decision. */
const ARROW = '<span aria-hidden="true">→</span>';

/**
 * The separator between a detail page's top-level sections.
 *
 * The static renderers are single template literals spanning several source
 * lines, so a newline plus two spaces of indentation sits between each
 * </section> and the next <section>. That whitespace is part of the document —
 * between two nodes it becomes a text node — so it has to be reproduced rather
 * than assumed away. The closing CTA is concatenated with no separator, which
 * is also how the source reads.
 */
const SECTION_GAP = "\n  ";

/**
 * Escape exactly as the static build's escape() does.
 *
 * Four characters — & < > " — and NOT the apostrophe. esc_html() is
 * htmlspecialchars with ENT_QUOTES plus filters, so it would also emit &#039;
 * for an apostrophe. No apostrophe passes through escape() in today's corpus,
 * but the data will grow and the divergence would be silent and per-string.
 *
 * htmlspecialchars runs first so the ampersand in the &quot; we add is not
 * double-encoded; double_encode stays true to match JS replacing & first, which
 * turns a literal & into &amp; and an existing &amp; into &amp;amp; — identical
 * behaviour in both runtimes.
 *
 * @param mixed $value Raw value.
 */
function e( $value ): string {
	return str_replace(
		'"',
		'&quot;',
		htmlspecialchars( (string) $value, ENT_NOQUOTES, 'UTF-8', true )
	);
}

// --------------------------------------------------------------------------
// Data accessors
// --------------------------------------------------------------------------

/**
 * A term's name as raw text.
 *
 * WordPress is inconsistent here, and the inconsistency is easy to miss:
 * `sanitize_term` HTML-encodes the `name` field on insert, so a term stores
 * 'Health &amp; Pharma', while a post title with the same ampersand stores
 * 'AI-Transformation & Daten' raw. Escaping the term name then produces
 * '&amp;amp;' — which rendered as a literal "Health &amp; Pharma" on the page
 * and showed up in the parity diff on four routes at once.
 *
 * So decode at the boundary: every accessor returns raw text, and escaping
 * happens exactly once at output, which is the invariant the rest of this file
 * relies on.
 *
 * @param \WP_Term|null $term Term.
 */
function term_name( ?\WP_Term $term ): string {
	if ( ! $term instanceof \WP_Term ) {
		return '';
	}

	return html_entity_decode( (string) $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/**
 * Turn a cached ID list back into post objects.
 *
 * Derived lists are cached as IDs rather than post objects on purpose. The
 * expensive part of a derived query is the derivation — the post_parent scan,
 * the tax_query, the termmeta join — not fetching rows by primary key, and the
 * post objects are already in the object cache under their own keys. Caching
 * the objects too would store every title and body a second time and go stale
 * on edit independently of the post cache.
 *
 * _prime_post_caches() warms posts, postmeta and object terms in one query
 * each, so the get_post() calls below never hit the database individually.
 * Without it this would be one query per ID.
 *
 * The term cache is primed — the second argument is true — because `fields =>
 * 'ids'` turns off the priming WP_Query does for a normal post query, and the
 * case-study filters call has_term() on every card. Measured with 10 case
 * studies: 29 queries unprimed, 0 primed. For the page lists it costs nothing,
 * since pages carry no taxonomies and update_object_term_cache() then returns
 * without querying.
 *
 * @param int[] $ids Post IDs, in the order they should render.
 * @return WP_Post[]
 */
function hydrate( array $ids ): array {
	if ( ! $ids ) {
		return array();
	}

	_prime_post_caches( $ids, true, true );

	$posts = array();

	foreach ( $ids as $id ) {
		$post = get_post( $id );

		/*
		 * Status is re-checked here, not trusted from the cache: the ID list can
		 * outlive an unpublish by up to the TTL if a save_post hook did not fire
		 * (a direct wp_update_post with a suspended object cache, say). A stale
		 * ID renders nothing rather than a draft.
		 */
		if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
			$posts[] = $post;
		}
	}

	return $posts;
}

/**
 * Every case study, in source order.
 *
 * Ordered by menu_order, not date: the source array's order drives the featured
 * selection
 * and the project grid, and a default date sort would silently reshuffle them.
 *
 * @return WP_Post[]
 */
function case_studies(): array {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$ids = remember(
		'case-studies',
		static function (): array {
			return array_map(
				'intval',
				get_posts(
					array(
						'post_type'        => CPT_CASE_STUDY,
						'post_status'      => 'publish',
						'posts_per_page'   => 100,
						'orderby'          => 'menu_order',
						'order'            => 'ASC',
						'fields'           => 'ids',
						'no_found_rows'    => true,
						'suppress_filters' => false,
					)
				)
			);
		}
	);

	$cache = hydrate( $ids );

	return $cache;
}

/**
 * Every discipline page, in source order.
 *
 * @return WP_Post[]
 */
function disciplines(): array {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$ids = remember(
		'disciplines',
		static function (): array {
			$parent = get_page_by_path( 'expertise' );

			if ( ! $parent instanceof WP_Post ) {
				return array();
			}

			$pages = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post_parent'    => $parent->ID,
					'posts_per_page' => 100,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);

			$pages = array_map( 'intval', $pages );
			_prime_post_caches( $pages, false, true );

			/*
			 * The /expertise/ tree also holds the two hand-authored group
			 * overview pages, which are not disciplines. A discipline is
			 * identified by carrying a linked term — which is data, not a slug
			 * guess. The filter runs inside the cached callback so the meta
			 * reads happen once per invalidation, not once per request.
			 */
			return array_values(
				array_filter(
					$pages,
					static function ( int $id ): bool {
						return (int) get_post_meta( $id, '_emposo_discipline_term', true ) > 0;
					}
				)
			);
		}
	);

	$cache = hydrate( $ids );

	return $cache;
}

/**
 * Every industry page, in source order.
 *
 * @return WP_Post[]
 */
function industries(): array {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$ids = remember(
		'industries',
		static function (): array {
			$parent = get_page_by_path( 'branchen' );

			if ( ! $parent instanceof WP_Post ) {
				return array();
			}

			return array_map(
				'intval',
				get_posts(
					array(
						'post_type'      => 'page',
						'post_status'    => 'publish',
						'post_parent'    => $parent->ID,
						'posts_per_page' => 100,
						'orderby'        => 'menu_order',
						'order'          => 'ASC',
						'fields'         => 'ids',
						'no_found_rows'  => true,
					)
				)
			);
		}
	);

	$cache = hydrate( $ids );

	return $cache;
}

/**
 * The discipline page a case study belongs to.
 *
 * @param WP_Post $case_study Case study.
 */
function discipline_of( WP_Post $case_study ): ?WP_Post {
	$terms = wp_get_object_terms( $case_study->ID, TAX_DISCIPLINE, array( 'fields' => 'ids' ) );

	if ( is_wp_error( $terms ) || ! $terms ) {
		return null;
	}

	foreach ( disciplines() as $page ) {
		if ( in_array( (int) get_post_meta( $page->ID, '_emposo_discipline_term', true ), array_map( 'intval', $terms ), true ) ) {
			return $page;
		}
	}

	return null;
}

/**
 * The industry label for a case study: the name of its deepest assigned term.
 *
 * Not a stored field. 'Automotive' is the child term under
 * 'Industrials & Manufacturing', so the deepest term is exactly the label the
 * static build printed.
 *
 * @param WP_Post $case_study Case study.
 */
function industry_label( WP_Post $case_study ): string {
	$terms = wp_get_object_terms( $case_study->ID, TAX_INDUSTRY );

	if ( is_wp_error( $terms ) || ! $terms ) {
		return '';
	}

	$deepest = null;
	$depth   = -1;

	foreach ( $terms as $term ) {
		$term_depth = count( get_ancestors( $term->term_id, TAX_INDUSTRY ) );
		if ( $term_depth > $depth ) {
			$depth   = $term_depth;
			$deepest = $term;
		}
	}

	return term_name( $deepest );
}

/**
 * The space-separated industry token string for data-industry.
 *
 * Ancestor-closed by the importer, and ordered deepest-first to match the
 * source's 'industrial automotive'... in fact the source lists parent first,
 * so sort shallowest-first.
 *
 * @param WP_Post $case_study Case study.
 */
function industry_tokens( WP_Post $case_study ): string {
	$terms = wp_get_object_terms( $case_study->ID, TAX_INDUSTRY );

	if ( is_wp_error( $terms ) || ! $terms ) {
		return '';
	}

	usort(
		$terms,
		static function ( $a, $b ): int {
			return count( get_ancestors( $a->term_id, TAX_INDUSTRY ) )
				<=> count( get_ancestors( $b->term_id, TAX_INDUSTRY ) );
		}
	);

	return implode( ' ', wp_list_pluck( $terms, 'slug' ) );
}

/**
 * A case study's outcome slug.
 *
 * @param WP_Post $case_study Case study.
 */
function outcome_of( WP_Post $case_study ): string {
	$terms = wp_get_object_terms( $case_study->ID, TAX_OUTCOME, array( 'fields' => 'slugs' ) );

	return ( is_wp_error( $terms ) || ! $terms ) ? '' : (string) $terms[0];
}

/**
 * Path of a post, relative to the site root.
 *
 * @param WP_Post $post Post.
 */
function path_of( WP_Post $post ): string {
	$path = (string) wp_parse_url( (string) get_permalink( $post ), PHP_URL_PATH );

	return '' === $path ? '/' : $path;
}

// --------------------------------------------------------------------------
// Renderers
// --------------------------------------------------------------------------

/**
 * The metric block.
 *
 * Uses mb_strlen, not strlen: '80.000 €' is 8 characters but 10 BYTES, because the
 * euro sign is three bytes in UTF-8. strlen would add the result-metric__word
 * class to a value the static build leaves plain.
 *
 * @param WP_Post $case_study Case study.
 */
function metric( WP_Post $case_study ): string {
	$value = (string) get_post_meta( $case_study->ID, '_emposo_metric', true );
	$label = (string) get_post_meta( $case_study->ID, '_emposo_metric_label', true );

	$word_class = mb_strlen( $value, 'UTF-8' ) > 8 ? ' class="result-metric__word"' : '';

	return '<div class="result-metric"><strong' . $word_class . '>' . e( $value )
		. '</strong><span>' . e( $label ) . '</span></div>';
}

/**
 * The project card grid.
 *
 * @param WP_Post[] $selection  Case studies, in render order.
 * @param bool      $filterable Whether to emit the filter hooks.
 */
function project_cards( array $selection, bool $filterable = false ): string {
	$out = '<div class="reference-grid"' . ( $filterable ? ' data-project-grid' : '' ) . '>';

	foreach ( $selection as $case_study ) {
		$discipline = discipline_of( $case_study );

		$hooks = $filterable
			? ' data-project data-industry="' . e( industry_tokens( $case_study ) )
				. '" data-outcome="' . e( outcome_of( $case_study ) ) . '"'
			: '';

		$out .= '<a class="reference-card" href="' . e( path_of( $case_study ) ) . '"' . $hooks . '>'
			. '<figure>' . picture( (int) get_post_thumbnail_id( $case_study ), 'default' ) . '</figure>'
			. '<div class="reference-card__meta"><span>' . e( industry_label( $case_study ) ) . '</span>'
			. '<span>' . e( $discipline instanceof WP_Post ? $discipline->post_title : '' ) . '</span></div>'
			. '<h3>' . e( $case_study->post_title ) . '</h3>'
			. '<p>' . e( $case_study->post_excerpt ) . '</p>'
			. metric( $case_study )
			. '<span class="text-link">Case Study lesen ' . ARROW . '</span></a>';
	}

	return $out . '</div>';
}

/**
 * The industry tile grid.
 */
function industry_cards(): string {
	$out   = '<div class="industry-cards">';
	$index = 0;

	foreach ( industries() as $industry ) {
		++$index;
		$subtitle = (string) get_post_meta( $industry->ID, '_emposo_subtitle', true );

		$out .= '<a class="industry-tile" href="' . e( path_of( $industry ) ) . '">'
			. '<figure>' . picture( (int) get_post_thumbnail_id( $industry ), 'tile' ) . '</figure>'
			. '<div class="industry-tile__copy">'
			// Zero-padded to two digits, as the source does with a literal '0'
			// prefix — there are five industries, so it never reaches ten.
			. '<span class="industry-tile__number">0' . $index . '</span>'
			. '<h3>' . e( $industry->post_title ) . '</h3>'
			. ( '' !== $subtitle ? '<p>' . e( $subtitle ) . '</p>' : '' )
			. '<span class="industry-tile__arrow" aria-hidden="true">→</span>'
			. '</div></a>';
	}

	return $out . '</div>';
}

/**
 * The filter bar, count, grid and empty state.
 *
 * The vocabulary comes from the industry and outcome taxonomies rather than a
 * hardcoded table, which is what makes the seven-buttons-for-five-pages
 * asymmetry self-maintaining: 'automotive' is a term with no page, so it
 * appears here and nowhere else.
 */
function filters(): string {
	$all = case_studies();

	$groups = array(
		'industry' => array(
			'label' => 'Branche',
			'aria'  => 'Nach Branche filtern',
			'terms' => ordered_terms( TAX_INDUSTRY ),
		),
		'outcome'  => array(
			'label' => 'Wirkung',
			'aria'  => 'Nach Wirkung filtern',
			'terms' => ordered_terms( TAX_OUTCOME ),
		),
	);

	$out = '<div class="work-filter js-only">';

	foreach ( $groups as $group => $config ) {
		$out .= '<div class="filter-group"><span>' . e( $config['label'] ) . '</span>'
			. '<div role="group" aria-label="' . e( $config['aria'] ) . '">'
			. '<button class="filter-button min-h-11 is-active" type="button" data-filter-group="' . e( $group )
			. '" data-filter-value="all" aria-pressed="true">Alle</button>';

		foreach ( $config['terms'] as $term ) {
			$out .= '<button class="filter-button min-h-11" type="button" data-filter-group="' . e( $group )
				. '" data-filter-value="' . e( $term->slug ) . '" aria-pressed="false">'
				. e( term_name( $term ) ) . '</button>';
		}

		$out .= '</div></div>';
	}

	$out .= '</div>';

	// German pluralisation is part of the contract: js/06-work.js rewrites this
	// text, and the static markup must agree with what the script would produce.
	$count = count( $all );
	$word  = 1 === $count ? 'Projekt' : 'Projekte';

	$out .= '<p class="work-count" id="project-count" aria-live="polite">' . $count . ' ' . $word . '</p>';
	$out .= project_cards( $all, true );
	$out .= '<p class="work-empty" id="project-empty" hidden>Für diese Auswahl ist noch keine Referenz veröffentlicht. '
		. '<a href="/kontakt/">Sprechen Sie mit uns über Ihre Branche.</a></p>';

	return $out;
}

/**
 * Terms in source order.
 *
 * Note get_terms() defaults to alphabetical, which would put Automotive second and
 * reorder the filter bar. The importer stored the source position, so request
 * that instead.
 *
 * @param string $taxonomy Taxonomy name.
 * @return \WP_Term[]
 */
function ordered_terms( string $taxonomy ): array {
	/*
	 * This is the one derived query with a metadata join, and it runs on every
	 * page carrying a filter bar — so it is the query the cache exists for.
	 * Term IDs are cached rather than term objects, for the same reason post
	 * IDs are: get_term() reads from the term cache.
	 */
	$ids = remember(
		'terms:' . $taxonomy,
		static function () use ( $taxonomy ): array {
			$found = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
					'meta_key'   => '_emposo_term_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Small closed vocabulary; the alternative is a wrong order.
					'orderby'    => 'meta_value_num',
					'order'      => 'ASC',
				)
			);

			if ( is_wp_error( $found ) ) {
				return array();
			}

			return array_map( 'intval', $found );
		}
	);

	$terms = array();

	foreach ( $ids as $id ) {
		$term = get_term( $id, $taxonomy );

		if ( $term instanceof \WP_Term ) {
			$terms[] = $term;
		}
	}

	/*
	 * The discipline taxonomy nests groups over disciplines, but the industry
	 * vocabulary is flat as far as the filter bar is concerned: the parent and
	 * its 'automotive' child are both buttons. So no filtering by depth here —
	 * the stored order already encodes the source's sequence.
	 */
	return $terms;
}

/**
 * The two-column discipline matrix.
 */
function discipline_grid(): string {
	$out = '<div class="expertise-matrix">';

	foreach ( array( 'Engineering', 'Technology' ) as $group ) {
		$out .= '<section class="expertise-column" aria-labelledby="disciplines-' . e( $group ) . '">'
			. '<h3 class="expertise-column__title" id="disciplines-' . e( $group ) . '">' . e( $group ) . '</h3>'
			. '<div class="expertise-list">';

		foreach ( disciplines_in_group( $group ) as $discipline ) {
			$out .= '<a class="expertise-card" href="' . e( path_of( $discipline ) ) . '">'
				. '<h4>' . e( $discipline->post_title ) . '</h4>'
				. '<p>' . e( (string) get_post_meta( $discipline->ID, '_emposo_topics', true ) ) . '</p>'
				. '<span class="expertise-card__promise">' . e( $discipline->post_excerpt ) . '</span></a>';
		}

		$out .= '</div></section>';
	}

	return $out . '</div>';
}

/**
 * Disciplines belonging to one group.
 *
 * The group is the PARENT of the discipline's linked term, so this is one
 * indexed hop rather than a stored duplicate of the group name.
 *
 * @param string $group Group name.
 * @return WP_Post[]
 */
function disciplines_in_group( string $group ): array {
	$parent = get_term_by( 'slug', strtolower( $group ), TAX_DISCIPLINE );

	if ( ! $parent ) {
		return array();
	}

	return array_values(
		array_filter(
			disciplines(),
			static function ( WP_Post $page ) use ( $parent ): bool {
				$term_id = (int) get_post_meta( $page->ID, '_emposo_discipline_term', true );

				return $term_id > 0
					&& in_array( (int) $parent->term_id, get_ancestors( $term_id, TAX_DISCIPLINE ), true );
			}
		)
	);
}

/**
 * The group a discipline belongs to, and that group's overview page path.
 *
 * @param WP_Post $discipline Discipline page.
 * @return array{group: string, overview: string}
 */
function group_of( WP_Post $discipline ): array {
	$term_id   = (int) get_post_meta( $discipline->ID, '_emposo_discipline_term', true );
	$ancestors = $term_id > 0 ? get_ancestors( $term_id, TAX_DISCIPLINE ) : array();

	if ( ! $ancestors ) {
		return array(
			'group'    => '',
			'overview' => '/expertise/',
		);
	}

	$parent = get_term( (int) $ancestors[0], TAX_DISCIPLINE );

	if ( ! $parent instanceof \WP_Term ) {
		return array(
			'group'    => '',
			'overview' => '/expertise/',
		);
	}

	$overview_page = get_page_by_path( 'expertise/' . $parent->slug );

	return array(
		'group'    => term_name( $parent ),
		'overview' => $overview_page instanceof WP_Post ? path_of( $overview_page ) : '/expertise/',
	);
}

/**
 * The shared closing call to action.
 *
 * @param string $title Heading.
 */
function cta( string $title = 'Jetzt Kontakt aufnehmen!' ): string {
	return '<section class="page-section page-section--deep"><div class="gutter"><div class="container">'
		. '<div class="page-cta"><div><p class="page-eyebrow page-eyebrow--light">Ihr nächster Schritt</p>'
		. '<h2 class="page-cta__title">' . $title . '</h2></div>'
		. '<div class="page-cta__copy"><p>Ob konkretes Vorhaben, erste Orientierung oder weitere Fragen: '
		. 'Erzählen Sie uns kurz, worum es geht.</p>'
		. '<a class="page-link page-link--light" href="/kontakt/">Projekt besprechen</a></div>'
		. '</div></div></div></section>';
}

/**
 * The management roster.
 *
 * Renders from the person post type, so the roster is editable — and the
 * "never invent roles or bios" rule is structural rather than a note: a full
 * profile renders only when there is body content, a photo tile only when
 * there is a featured image, and otherwise an initials tile. There is no
 * placeholder path and no field an editor can half-fill into a fabricated role.
 */
function management(): string {
	$people = hydrate(
		remember(
			'people',
			static function (): array {
				return array_map(
					'intval',
					get_posts(
						array(
							'post_type'      => \Emposo\Core\ContentModel\CPT_PERSON,
							'post_status'    => 'publish',
							'posts_per_page' => 100,
							'orderby'        => 'menu_order',
							'order'          => 'ASC',
							'fields'         => 'ids',
							'no_found_rows'  => true,
						)
					)
				);
			}
		)
	);

	$profiles = '';
	$tiles    = '';

	foreach ( $people as $person ) {
		$role     = (string) get_post_meta( $person->ID, '_emposo_person_role', true );
		$initials = (string) get_post_meta( $person->ID, '_emposo_person_initials', true );
		$linkedin = (string) get_post_meta( $person->ID, '_emposo_person_linkedin', true );
		$thumb    = (int) get_post_thumbnail_id( $person );
		$body     = trim( (string) $person->post_content );

		if ( '' !== $body ) {
			// The second profile's portrait is wider than the first's.
			$wide = '' !== $profiles ? ' class="management-profile__media--wide"' : '';

			$paragraphs = '';
			$blocks     = preg_split( '/\R{2,}/', $body );
			foreach ( is_array( $blocks ) ? $blocks : array() as $paragraph ) {
				$paragraph = trim( (string) $paragraph );
				if ( '' !== $paragraph ) {
					$paragraphs .= '<p>' . e( $paragraph ) . '</p>';
				}
			}

			$profiles .= '<article class="management-profile">'
				. '<figure' . $wide . '>' . picture( $thumb, 'portrait' ) . '</figure>'
				. '<div><h3>' . e( $person->post_title ) . '</h3>'
				. '<p class="management-profile__role">' . e( $role ) . '</p>'
				. $paragraphs
				. ( '' !== $linkedin
					? '<a class="text-link" href="' . esc_url( $linkedin ) . '">' . e( $person->post_title )
						. ' auf LinkedIn ' . ARROW . '</a>'
					: '' )
				. '</div></article>';

			continue;
		}

		$tiles .= '<li>'
			. ( $thumb > 0
				? '<figure>' . picture( $thumb, 'portrait_small' ) . '</figure>'
				: '<div class="management-grid__initials" aria-hidden="true">' . e( $initials ) . '</div>' )
			. '<span>' . e( $person->post_title ) . '</span></li>';
	}

	return '<section class="page-section page-section--paper" id="management" aria-labelledby="management-title">'
		. '<div class="gutter"><div class="container"><p class="eyebrow">Management</p>'
		. '<h2 class="page-title" id="management-title">Menschen, die Verantwortung übernehmen.</h2>'
		. '<div class="management-list">' . $profiles . '</div>'
		. '<ul class="management-grid" aria-label="Weitere Mitglieder des Management-Teams">' . $tiles . '</ul>'
		. '</div></div></section>';
}

/**
 * The HTML sitemap.
 */
function sitemap(): string {
	$out = '<div><h2>Leistungen</h2><a href="/">Startseite</a><a href="/portfolio/">Unsere Leistungen</a>'
		. '<a href="/expertise/">Alle Disziplinen</a><a href="/expertise/engineering/">Engineering im Überblick</a>'
		. '<a href="/expertise/technology/">Technology im Überblick</a>';

	foreach ( disciplines() as $discipline ) {
		$out .= '<a href="' . e( path_of( $discipline ) ) . '">' . e( $discipline->post_title ) . '</a>';
	}

	foreach ( array( 'optimieren', 'transformieren', 'skalieren', 'verzahnen' ) as $pillar ) {
		$out .= '<a href="/portfolio/' . e( $pillar ) . '/">Wir ' . e( $pillar ) . '</a>';
	}

	$out .= '</div><div><h2>Branchen</h2><a href="/branchen/">Alle Branchen</a>';

	foreach ( industries() as $industry ) {
		$out .= '<a href="' . e( path_of( $industry ) ) . '">' . e( $industry->post_title ) . '</a>';
	}

	$out .= '<h2>Unternehmen</h2><a href="/about-us/">Über uns</a><a href="/about-us/#management">Management</a>'
		. '<a href="/karriere/">Karriere</a><a href="/kontakt/">Kontakt</a>'
		. '<a href="/zertifizierungen/">Zertifizierungen</a><a href="/cookies/">Cookies</a>'
		. '<a href="/barrierefreiheit/">Barrierefreiheit</a>'
		. '<a href="https://emposo.de/impressum/">Impressum</a>'
		. '<a href="https://emposo.de/datenschutzerklaerung/">Datenschutz</a>'
		. '</div><div><h2>Referenzprojekte</h2><a href="/case-studies/">Alle Case Studies</a>';

	foreach ( case_studies() as $case_study ) {
		$out .= '<a href="' . e( path_of( $case_study ) ) . '">' . e( $case_study->post_title ) . '</a>';
	}

	return $out . '</div>';
}

/**
 * The 13 named fragments.
 *
 * Selections that were hardcoded slug lists in the static source are expressed
 * here as the query they actually are — by discipline, by outcome, by group,
 * with an explicit exclusion — so an editor tagging a new case study changes
 * the site without a code edit. The one genuinely curated list, the four
 * featured projects, stays explicit.
 *
 * @param string $name Fragment name.
 */
function render( string $name ): string {
	$all = case_studies();

	switch ( $name ) {
		case 'industry-cards':
			return industry_cards();

		case 'projects-all':
			return filters();

		case 'disciplines':
			return discipline_grid();

		case 'management':
			return management();

		case 'sitemap':
			return sitemap();

		case 'projects-featured':
			// Genuinely curated: four specific projects in a fixed order.
			return project_cards( by_slugs( array( 'data2ai-platform', 'engineering-wissensbasis', 'mlops-medizinprodukte', 'multi-site-transition' ) ) );

		case 'projects-ai':
			return project_cards( by_discipline( 'ai-daten', array( 'data2ai-platform' ) ) );

		case 'projects-engineering':
			return project_cards( by_group( 'Engineering' ) );

		case 'projects-technology':
			// Narrower than the name suggests, as in the source: enterprise
			// services only, not the whole Technology group.
			return project_cards( by_discipline( 'enterprise-services' ) );

		case 'projects-optimize':
			return project_cards( by_outcome( 'optimize' ) );

		case 'projects-scale':
			return project_cards( by_outcome( 'scale' ) );

		case 'projects-transform':
			return project_cards( by_outcome( 'transform', array( 'data2ai-platform' ) ) );

		case 'projects-verzahnen':
			return project_cards( by_slugs( array( 'multi-site-transition' ) ) );

		default:
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				_doing_it_wrong( __FUNCTION__, esc_html( sprintf( 'Unknown fragment: %s', $name ) ), '0.1.0' );
			}

			return '';
	}
	// phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable -- Defensive.
}

/**
 * Case studies matching an ordered slug list.
 *
 * @param string[] $slugs Slugs, in render order.
 * @return WP_Post[]
 */
function by_slugs( array $slugs ): array {
	$by_slug = array();
	foreach ( case_studies() as $case_study ) {
		$by_slug[ $case_study->post_name ] = $case_study;
	}

	$out = array();
	foreach ( $slugs as $slug ) {
		if ( isset( $by_slug[ $slug ] ) ) {
			$out[] = $by_slug[ $slug ];
		}
	}

	return $out;
}

/**
 * Case studies in one discipline.
 *
 * @param string   $slug    Discipline term slug.
 * @param string[] $exclude Case-study slugs to omit.
 * @return WP_Post[]
 */
function by_discipline( string $slug, array $exclude = array() ): array {
	return array_values(
		array_filter(
			case_studies(),
			static function ( WP_Post $case_study ) use ( $slug, $exclude ): bool {
				if ( in_array( $case_study->post_name, $exclude, true ) ) {
					return false;
				}

				return has_term( $slug, TAX_DISCIPLINE, $case_study );
			}
		)
	);
}

/**
 * Case studies in one discipline group.
 *
 * @param string $group Group name.
 * @return WP_Post[]
 */
function by_group( string $group ): array {
	$parent = get_term_by( 'slug', strtolower( $group ), TAX_DISCIPLINE );

	if ( ! $parent ) {
		return array();
	}

	return array_values(
		array_filter(
			case_studies(),
			static function ( WP_Post $case_study ) use ( $parent ): bool {
				$terms = wp_get_object_terms( $case_study->ID, TAX_DISCIPLINE, array( 'fields' => 'ids' ) );
				if ( is_wp_error( $terms ) ) {
					return false;
				}
				foreach ( $terms as $term_id ) {
					if ( in_array( (int) $parent->term_id, get_ancestors( (int) $term_id, TAX_DISCIPLINE ), true ) ) {
						return true;
					}
				}

				return false;
			}
		)
	);
}

/**
 * Case studies with one outcome.
 *
 * @param string   $slug    Outcome term slug.
 * @param string[] $exclude Case-study slugs to omit.
 * @return WP_Post[]
 */
function by_outcome( string $slug, array $exclude = array() ): array {
	return array_values(
		array_filter(
			case_studies(),
			static function ( WP_Post $case_study ) use ( $slug, $exclude ): bool {
				if ( in_array( $case_study->post_name, $exclude, true ) ) {
					return false;
				}

				return has_term( $slug, TAX_OUTCOME, $case_study );
			}
		)
	);
}

// --------------------------------------------------------------------------
// Detail pages
// --------------------------------------------------------------------------

/**
 * Render one of the three data-driven detail pages.
 *
 * Together these serve 22 of the 41 routes: 10 case studies, 7 generated
 * disciplines and 5 industries.
 *
 * @param string $kind 'project', 'discipline' or 'industry'.
 * @param string $slug Object slug.
 */
function render_detail( string $kind, string $slug ): string {
	switch ( $kind ) {
		case 'project':
			return project_page( $slug );
		case 'discipline':
			return discipline_page( $slug );
		case 'industry':
			return industry_page( $slug );
		default:
			return '';
	}
}

/**
 * The hero section shared by all three detail pages.
 *
 * @param string $breadcrumb Pre-rendered breadcrumb markup.
 * @param string $kicker     Kicker text.
 * @param string $title      Heading.
 * @param string $intro      Lede.
 * @param int    $image_id   Attachment ID for the hero image.
 */
function detail_hero( string $breadcrumb, string $kicker, string $title, string $intro, int $image_id ): string {
	return '<section class="page-hero"><div class="gutter"><div class="container"><div class="page-hero__grid">'
		. '<div class="page-hero__copy">' . $breadcrumb
		. '<p class="page-kicker">' . e( $kicker ) . '</p>'
		. '<h1 class="page-display">' . e( $title ) . '</h1>'
		. '<p class="page-hero__intro">' . e( $intro ) . '</p></div>'
		// The hero is the LCP image on these routes: eager, high fetchpriority.
		. '<figure class="page-hero__visual">' . picture( $image_id, 'detail', true ) . '</figure>'
		. '</div></div></div></section>';
}

/**
 * A case study page.
 *
 * @param string $slug Case-study slug.
 */
function project_page( string $slug ): string {
	$case_study = null;
	foreach ( case_studies() as $candidate ) {
		if ( $candidate->post_name === $slug ) {
			$case_study = $candidate;
			break;
		}
	}

	if ( ! $case_study instanceof WP_Post ) {
		return '';
	}

	$discipline = discipline_of( $case_study );
	$results    = (array) get_post_meta( $case_study->ID, '_emposo_results', true );

	$breadcrumb = '<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span>'
		. '<a href="/case-studies/">Case Studies</a></p>';

	$out = detail_hero(
		$breadcrumb,
		industry_label( $case_study ),
		$case_study->post_title,
		$case_study->post_excerpt,
		(int) get_post_thumbnail_id( $case_study )
	);

	$list = '';
	foreach ( $results as $result ) {
		$list .= '<li>' . e( (string) $result ) . '</li>';
	}

	$out .= SECTION_GAP . '<section class="page-section"><div class="gutter"><div class="container"><div class="case-story">'
		. '<div><p class="eyebrow">Der Outcome</p>' . metric( $case_study ) . '</div>'
		. '<div><h2>Die Herausforderung</h2><p>'
		. e( (string) get_post_meta( $case_study->ID, '_emposo_challenge', true ) ) . '</p>'
		. '<h2>Unsere Lösung</h2><p>'
		. e( (string) get_post_meta( $case_study->ID, '_emposo_solution', true ) ) . '</p>'
		. '<h2>Das Ergebnis</h2><ul class="result-list">' . $list . '</ul>'
		. ( $discipline instanceof WP_Post
			? '<a class="text-link" href="' . e( path_of( $discipline ) ) . '">'
				. e( $discipline->post_title ) . ' ' . ARROW . '</a>'
			: '' )
		. '</div></div></div></div></section>';

	/*
	 * Related: two case studies in the same discipline, falling back to any
	 * two. The fallback matters — without it a discipline with a single case
	 * study would render an empty grid.
	 */
	$same_discipline = array();
	$any_other       = array();
	foreach ( case_studies() as $other ) {
		if ( $other->ID === $case_study->ID ) {
			continue;
		}
		$any_other[]      = $other;
		$other_discipline = discipline_of( $other );
		if ( $discipline instanceof WP_Post && $other_discipline instanceof WP_Post
			&& $other_discipline->ID === $discipline->ID ) {
			$same_discipline[] = $other;
		}
	}

	$related = array_slice( $same_discipline ? $same_discipline : $any_other, 0, 2 );

	$out .= SECTION_GAP . '<section class="page-section page-section--paper"><div class="gutter"><div class="container">'
		. '<p class="eyebrow">Weitere Referenzen</p>'
		. '<h2 class="page-title">Expertise, die Ergebnisse liefert.</h2>'
		. project_cards( $related )
		. '<p class="section-more"><a class="text-link" href="/case-studies/">Alle Case Studies '
		. ARROW . '</a></p></div></div></section>';

	return $out . cta();
}

/**
 * A discipline page.
 *
 * @param string $slug Discipline slug.
 */
function discipline_page( string $slug ): string {
	$discipline = null;
	foreach ( disciplines() as $candidate ) {
		if ( $candidate->post_name === $slug ) {
			$discipline = $candidate;
			break;
		}
	}

	if ( ! $discipline instanceof WP_Post ) {
		return '';
	}

	$group  = group_of( $discipline );
	$focus  = (array) get_post_meta( $discipline->ID, '_emposo_focus', true );
	$detail = (string) get_post_meta( $discipline->ID, '_emposo_detail', true );

	$breadcrumb = '<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span>'
		. '<a href="/expertise/">Expertise</a><span aria-hidden="true">/</span>'
		. '<a href="' . e( $group['overview'] ) . '">' . $group['group'] . '</a></p>';

	$out = detail_hero(
		$breadcrumb,
		'Expertise / ' . $group['group'],
		$discipline->post_title,
		$discipline->post_excerpt,
		(int) get_post_thumbnail_id( $discipline )
	);

	$items = '';
	foreach ( $focus as $item ) {
		$items .= '<li>' . e( (string) $item ) . '</li>';
	}

	$out .= SECTION_GAP . '<section class="page-section"><div class="gutter"><div class="container">'
		. '<div class="page-section__top"><div><p class="eyebrow">Leistungsschwerpunkte</p>'
		. '<h2 class="page-title">Eine Disziplin. Ein klares <em class="text-accent-text">Ergebnis.</em></h2></div>'
		. '<div class="page-section__lede"><p>' . e( $detail ) . '</p>'
		. '<p>Jede Leistung ist klar abgegrenzt, einzeln beauftragbar und wird bis zur Abnahme geführt.</p>'
		. '</div></div><ul class="discipline-focus">' . $items . '</ul>'
		. '<p class="section-more"><a class="text-link" href="' . e( $group['overview'] ) . '">Alle '
		. $group['group'] . '-Disziplinen ' . ARROW . '</a></p></div></div></section>';

	$related = by_discipline( $slug );

	/*
	 * The gap precedes the section whether or not it renders, because the
	 * source's final template line begins with it: `\n  ${related.length ? ...
	 * : ''}${cta()}`. So a discipline with no case studies still carries the
	 * separator before its CTA.
	 */
	$out .= SECTION_GAP;

	if ( $related ) {
		$out .= '<section class="page-section page-section--paper"><div class="gutter"><div class="container">'
			. '<p class="eyebrow">Referenzprojekte</p>'
			. '<h2 class="page-title">Unsere Erfolge sprechen für sich.</h2>'
			. project_cards( $related )
			. '<p class="section-more"><a class="text-link" href="/case-studies/">Alle Case Studies '
			. ARROW . '</a></p></div></div></section>';
	}

	return $out . cta();
}

/**
 * An industry page.
 *
 * @param string $slug Industry page slug.
 */
function industry_page( string $slug ): string {
	$industry = null;
	foreach ( industries() as $candidate ) {
		if ( $candidate->post_name === $slug ) {
			$industry = $candidate;
			break;
		}
	}

	if ( ! $industry instanceof WP_Post ) {
		return '';
	}

	$subtitle = (string) get_post_meta( $industry->ID, '_emposo_subtitle', true );
	$term_id  = (int) get_post_meta( $industry->ID, '_emposo_industry_term', true );
	$term     = $term_id > 0 ? get_term( $term_id, TAX_INDUSTRY ) : null;

	$breadcrumb = '<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span>'
		. '<a href="/branchen/">Branchen</a></p>';

	$out = detail_hero(
		$breadcrumb,
		// The kicker falls back when an industry has no subtitle; only
		// industrials-manufacturing has one.
		'' !== $subtitle ? $subtitle : 'Branchenwissen in Anwendung',
		$industry->post_title,
		$industry->post_excerpt,
		(int) get_post_thumbnail_id( $industry )
	);

	$cards = '';
	foreach ( (array) get_post_meta( $industry->ID, '_emposo_related_disciplines', true ) as $discipline_id ) {
		$discipline = get_post( (int) $discipline_id );
		if ( ! $discipline instanceof WP_Post ) {
			continue;
		}
		$cards .= '<a class="expertise-card" href="' . e( path_of( $discipline ) ) . '">'
			. '<h3>' . e( $discipline->post_title ) . '</h3>'
			. '<p>' . e( $discipline->post_excerpt ) . '</p>'
			. '<span class="text-link">Expertise entdecken ' . ARROW . '</span></a>';
	}

	$out .= SECTION_GAP . '<section class="page-section"><div class="gutter"><div class="container">'
		. '<div class="page-section__top"><div><p class="eyebrow">Ihre Branche. Unsere Expertise.</p>'
		. '<h2 class="page-title">Unsere Teams kommen direkt aus Ihrer Branche.</h2></div>'
		. '<div class="page-section__lede"><p>'
		. e( (string) get_post_meta( $industry->ID, '_emposo_challenge', true ) ) . '</p><p>'
		. e( (string) get_post_meta( $industry->ID, '_emposo_delivery', true ) ) . '</p></div></div>'
		. '<div class="industry-disciplines">' . $cards . '</div></div></div></section>';

	/*
	 * Related case studies are DERIVED from the industry term, not a stored
	 * list — so tagging a new case study makes it appear here, and in the
	 * /case-studies/?branche= result, with no developer involvement.
	 *
	 * Two of the five industries have none, and the whole section must then
	 * disappear: heading, eyebrow and the "all matching references" link, which
	 * would otherwise point at a guaranteed-empty filter result.
	 */
	$related = $term instanceof \WP_Term ? by_industry_term( $term ) : array();

	// As on discipline pages, the separator precedes the section whether or not
	// it renders — two of the five industries have no case studies.
	$out .= SECTION_GAP;

	if ( $related ) {
		$out .= '<section class="page-section page-section--paper"><div class="gutter"><div class="container">'
			. '<p class="eyebrow">Referenzprojekte</p>'
			. '<h2 class="page-title">Unsere Erfolge sprechen für sich.</h2>'
			. project_cards( $related )
			. '<p class="section-more"><a class="text-link" href="/case-studies/?branche='
			. e( $term->slug ) . '#referenzen">Alle passenden Referenzen ' . ARROW . '</a></p>'
			. '</div></div></section>';
	}

	return $out . cta();
}

/**
 * Case studies carrying an industry term, including via its children.
 *
 * Ordered by the industry's stored hint where one exists, so the source's one
 * ordering divergence is reproduced, then by source order for anything the hint
 * does not mention — which is what lets a newly tagged case study appear
 * without an editor touching the hint.
 *
 * @param \WP_Term $term Industry term.
 * @return WP_Post[]
 */
function by_industry_term( \WP_Term $term ): array {
	$descendants = get_term_children( $term->term_id, TAX_INDUSTRY );
	$ids         = array_merge( array( (int) $term->term_id ), is_array( $descendants ) ? array_map( 'intval', $descendants ) : array() );

	$matching = array_values(
		array_filter(
			case_studies(),
			static function ( WP_Post $case_study ) use ( $ids ): bool {
				$terms = wp_get_object_terms( $case_study->ID, TAX_INDUSTRY, array( 'fields' => 'ids' ) );

				return ! is_wp_error( $terms ) && array_intersect( array_map( 'intval', $terms ), $ids );
			}
		)
	);

	$page = null;
	foreach ( industries() as $candidate ) {
		if ( (int) get_post_meta( $candidate->ID, '_emposo_industry_term', true ) === (int) $term->term_id ) {
			$page = $candidate;
			break;
		}
	}

	$hint = $page instanceof WP_Post
		? array_map( 'intval', (array) get_post_meta( $page->ID, '_emposo_case_order', true ) )
		: array();

	if ( ! $hint ) {
		return $matching;
	}

	usort(
		$matching,
		static function ( WP_Post $a, WP_Post $b ) use ( $hint ): int {
			$position = static function ( int $id ) use ( $hint ): int {
				$index = array_search( $id, $hint, true );

				return false === $index ? PHP_INT_MAX : (int) $index;
			};

			return $position( $a->ID ) <=> $position( $b->ID );
		}
	);

	return $matching;
}
