<?php
/**
 * The shared content renderers, ported from content/render.mjs.
 *
 * One PHP function per render.mjs function, in the same order, so a change
 * upstream maps onto one place here (docs/resync.md). String-returning rather
 * than template parts, for two reasons the static source makes unavoidable:
 *
 * 1. They compose. A project page calls the card renderer, which calls the
 *    metric renderer and the picture helper. get_template_part() echoes and
 *    cannot be nested into a string.
 * 2. The static renderers emit NO inter-tag whitespace — each is one long
 *    template literal. An indented PHP template would introduce text nodes
 *    into flex and grid rows, where they affect layout.
 *
 * Data comes from WordPress, never from the JSON export: the export seeds the
 * database, and the database is then the source of truth, so an editor's change
 * shows up here. Case studies are posts; disciplines and industries are terms
 * with term meta (they have no pages of their own since the owner removed the
 * detail subpages); company facts, certifications and job postings are options.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Fragments;

use WP_Post;
use WP_Term;
use function Emposo\Core\Cache\remember;
use function Emposo\Core\Images\picture;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY;
use const Emposo\Core\ContentModel\CPT_PERSON;
use const Emposo\Core\ContentModel\TAX_DISCIPLINE;
use const Emposo\Core\ContentModel\TAX_INDUSTRY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The decorative arrow. Straight, never diagonal — an explicit owner decision. */
const ARROW = '<span aria-hidden="true">→</span>';

/**
 * The separator between a project page's top-level sections.
 *
 * The reference's projectPage() is one template literal spanning three lines, so a
 * newline plus two spaces sits between the hero and each <section>. Between two
 * nodes that is a text node, so it is reproduced rather than assumed away. The
 * closing CTA is concatenated with no separator, as the source reads.
 */
const SECTION_GAP = "\n  ";

/** Applications and enquiries go to the shared inbox (owner decision 2026-09-25). */
const APPLY_EMAIL = 'info@emposo.eu';

/** The homepage collage (render.mjs fragment 'projects-featured'). */
const FEATURED = array( 'data2ai-platform', 'engineering-wissensbasis', 'mlops-medizinprodukte', 'multi-site-transition' );

/**
 * Escape exactly as the static build's escape() does (4 characters).
 *
 * @param mixed $value Raw value.
 */
function e( $value ): string {
	return \Emposo\Core\escape_static( $value );
}

/**
 * An inline icon, as assemble.mjs resolves {{icon:name}} after a fragment.
 *
 * @param string $name Icon name.
 */
function icon( string $name ): string {
	return function_exists( 'emposo_icon_svg' ) ? emposo_icon_svg( $name ) : '';
}

/**
 * JavaScript's encodeURIComponent(): rawurlencode() also encodes ! * ' ( ),
 * which encodeURIComponent leaves alone — "(m/w/d)" in a job title shows it.
 *
 * @param string $value Raw value.
 */
function encode_uri_component( string $value ): string {
	return strtr(
		rawurlencode( $value ),
		array(
			'%21' => '!',
			'%2A' => '*',
			'%27' => "'",
			'%28' => '(',
			'%29' => ')',
		)
	);
}

/**
 * JavaScript's localeCompare(…, 'de') for the chip labels: the collator when
 * intl is present, a case-insensitive byte order (identical for today's
 * labels) when it is not.
 *
 * @param string $a Left.
 * @param string $b Right.
 */
function compare_de( string $a, string $b ): int {
	static $collator = null;

	if ( null === $collator && class_exists( '\\Collator' ) ) {
		$collator = new \Collator( 'de_DE' );
	}

	return $collator ? (int) $collator->compare( $a, $b ) : strcasecmp( $a, $b );
}

/**
 * Whitespace-separated words, as JavaScript's trim().split(/\s+/).
 *
 * @param string $value Text.
 * @return string[]
 */
function words( string $value ): array {
	$parts = preg_split( '/\s+/u', trim( $value ) );

	return is_array( $parts ) && '' !== trim( $value ) ? $parts : array();
}

// --------------------------------------------------------------------------
// Data accessors
// --------------------------------------------------------------------------

/**
 * A term's name as raw text.
 *
 * WordPress's sanitize_term HTML-encodes `name` on insert ('Health &amp; Pharma'), so
 * decode at the boundary: every accessor returns raw text and escaping happens
 * once, at output.
 *
 * @param WP_Term|null $term Term.
 */
function term_name( ?WP_Term $term ): string {
	return $term instanceof WP_Term ? html_entity_decode( (string) $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
}

/**
 * Turn a cached ID list back into published post objects, priming caches.
 *
 * @param int[] $ids Post IDs, in render order.
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
		if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
			$posts[] = $post;
		}
	}

	return $posts;
}

/**
 * Published posts of a type in menu_order (the source array's order).
 *
 * @param string $type Post type.
 * @return WP_Post[]
 */
function ordered_posts( string $type ): array {
	static $cache = array();

	if ( ! isset( $cache[ $type ] ) ) {
		$cache[ $type ] = hydrate(
			remember(
				'posts-' . $type,
				static function () use ( $type ): array {
					return array_map(
						'intval',
						get_posts(
							array(
								'post_type'        => $type,
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
			)
		);
	}

	return $cache[ $type ];
}

/**
 * Every case study, in source order.
 *
 * @return WP_Post[]
 */
function case_studies(): array {
	return ordered_posts( CPT_CASE_STUDY );
}

/**
 * Terms of a taxonomy in source order (the importer's _emposo_term_order).
 *
 * @param string $taxonomy Taxonomy.
 * @return WP_Term[]
 */
function ordered_terms( string $taxonomy ): array {
	static $cache = array();

	if ( ! isset( $cache[ $taxonomy ] ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'meta_key'   => '_emposo_term_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- A dozen terms.
				'orderby'    => 'meta_value_num',
				'order'      => 'ASC',
			)
		);

		$cache[ $taxonomy ] = is_array( $terms ) ? array_values( $terms ) : array();
	}

	return $cache[ $taxonomy ];
}

/**
 * The disciplines: the child terms of the two group terms, in source order.
 *
 * @return WP_Term[]
 */
function disciplines(): array {
	return array_values(
		array_filter(
			ordered_terms( TAX_DISCIPLINE ),
			static function ( WP_Term $term ): bool {
				return 0 !== (int) $term->parent;
			}
		)
	);
}

/**
 * The five industries of the tile grid (Automotive is a filter value only).
 *
 * @return WP_Term[]
 */
function industries(): array {
	return array_values(
		array_filter(
			ordered_terms( TAX_INDUSTRY ),
			static function ( WP_Term $term ): bool {
				return (bool) get_term_meta( $term->term_id, '_emposo_tile', true );
			}
		)
	);
}

/**
 * A case study's discipline term.
 *
 * @param WP_Post $case_study Case study.
 */
function discipline_of( WP_Post $case_study ): ?WP_Term {
	$terms = get_the_terms( $case_study, TAX_DISCIPLINE );

	return is_array( $terms ) && $terms ? $terms[0] : null;
}

/**
 * A meta string.
 *
 * @param WP_Post $post Post.
 * @param string  $key  Meta key.
 */
function meta( WP_Post $post, string $key ): string {
	return (string) get_post_meta( $post->ID, $key, true );
}

/**
 * A meta list of strings.
 *
 * @param WP_Post $post Post.
 * @param string  $key  Meta key.
 * @return string[]
 */
function meta_list( WP_Post $post, string $key ): array {
	$value = get_post_meta( $post->ID, $key, true );

	return is_array( $value ) ? array_map( 'strval', $value ) : array();
}

/**
 * A list-valued option.
 *
 * @param string $name Option name.
 * @return array<int, mixed>
 */
function option_list( string $name ): array {
	$value = get_option( $name, array() );

	return is_array( $value ) ? array_values( $value ) : array();
}

// --------------------------------------------------------------------------
// Renderers, in render.mjs order
// --------------------------------------------------------------------------

/**
 * The one breadcrumb: Startseite, an optional parent [href, label], the label.
 *
 * @param string        $label  Page label ('' for none).
 * @param string[]|null $trail  Parent link as [ href, label ].
 */
function breadcrumb( string $label, ?array $trail = null ): string {
	$sep   = '<span aria-hidden="true">/</span>';
	$items = array( '<a href="/">Startseite</a>' );

	if ( $trail ) {
		$items[] = '<a href="' . $trail[0] . '">' . $trail[1] . '</a>';
	}
	if ( '' !== $label ) {
		$items[] = '<span aria-current="page">' . $label . '</span>';
	}

	$out = '';
	foreach ( $items as $i => $item ) {
		$out .= '<li>' . ( $i ? $sep : '' ) . $item . '</li>';
	}

	return '<nav class="page-breadcrumb" aria-label="Brotkrümelnavigation"><ol>' . $out . '</ol></nav>';
}

/**
 * The one subpage hero frame.
 *
 * @param array<string, mixed> $args id, crumb, parent, modifier, figureClass, copy, figure.
 */
function page_hero( array $args ): string {
	$id       = (string) ( $args['id'] ?? '' );
	$modifier = (string) ( $args['modifier'] ?? '' );
	$fclass   = (string) ( $args['figureClass'] ?? '' );
	$figure   = $args['figure'] ?? null;

	return '<section class="page-hero' . ( '' !== $modifier ? ' ' . $modifier : '' ) . '"'
		. ( '' !== $id ? ' aria-labelledby="' . $id . '"' : '' ) . '>'
		. '<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">'
		. '<div class="page-hero__copy">' . breadcrumb( (string) ( $args['crumb'] ?? '' ), $args['parent'] ?? null ) . (string) $args['copy'] . '</div>'
		. ( null === $figure ? '' : '<figure class="page-hero__visual' . ( '' !== $fclass ? ' ' . $fclass : '' ) . '">' . $figure . '</figure>' )
		. '</div></div></div></section>';
}

/**
 * The trust strip: the released certifications (owner 2026-09-25).
 */
function trust_strip(): string {
	$out = '';
	foreach ( option_list( 'emposo_certifications' ) as $certification ) {
		$out .= '<span>' . (string) $certification . '</span>';
	}

	return '<div class="trust-strip">' . $out . '</div>';
}

/**
 * The industry tiles: static content, no link, no arrow (owner 24-09).
 */
function industry_cards(): string {
	$out = '';
	foreach ( industries() as $i => $industry ) {
		$subtitle = (string) get_term_meta( $industry->term_id, '_emposo_subtitle', true );

		$out .= '<div class="industry-tile"><figure>' . picture( (int) get_term_meta( $industry->term_id, '_emposo_image', true ), 'tile' ) . '</figure>'
			. '<div class="industry-tile__copy"><span class="industry-tile__number">0' . ( $i + 1 ) . '</span>'
			. '<h3>' . e( term_name( $industry ) ) . '</h3>'
			. ( '' !== $subtitle ? '<p>' . e( $subtitle ) . '</p>' : '' )
			. '</div></div>';
	}

	return '<div class="industry-cards">' . $out . '</div>';
}

/**
 * Company Kennzahlen: one component, one content set.
 */
function company_facts(): string {
	$out = '';
	foreach ( option_list( 'emposo_facts' ) as $fact ) {
		// The value is emitted as-is, as render.mjs does: '2.900+' carries the
		// German thousands separator the count-up script re-inserts.
		$out .= '<div><dt><span class="company-facts__icon">' . icon( (string) ( $fact['icon'] ?? '' ) ) . '</span>'
			. '<span class="company-facts__value">' . (string) ( $fact['value'] ?? '' ) . '</span></dt>'
			. '<dd>' . e( (string) ( $fact['label'] ?? '' ) ) . '</dd></div>';
	}

	return '<dl class="company-facts">' . $out . '</dl>';
}

/**
 * A case column: one sentence (<p>) or the deck's bullets (<ul>).
 *
 * @param WP_Post $case_study Case study.
 * @param string  $key        '_emposo_challenge' or '_emposo_solution'.
 */
function column( WP_Post $case_study, string $key ): string {
	$items = meta_list( $case_study, $key . '_list' );

	if ( $items ) {
		$out = '';
		foreach ( $items as $item ) {
			$out .= '<li>' . e( $item ) . '</li>';
		}

		return '<ul class="result-list">' . $out . '</ul>';
	}

	return '<p>' . e( meta( $case_study, $key ) ) . '</p>';
}

/**
 * The metric block.
 *
 * Uses mb_strlen, not strlen: '80.000 €' is 8 characters but 10 bytes.
 *
 * @param WP_Post $case_study Case study.
 */
function metric( WP_Post $case_study ): string {
	$value = meta( $case_study, '_emposo_metric' );

	return '<div class="result-metric"><strong' . ( mb_strlen( $value, 'UTF-8' ) > 8 ? ' class="result-metric__word"' : '' ) . '>'
		. e( $value ) . '</strong><span>' . e( meta( $case_study, '_emposo_metric_label' ) ) . '</span></div>';
}

/**
 * The project card grid.
 *
 * @param WP_Post[] $selection  Case studies, in render order.
 * @param bool      $filterable Emit the filter hooks.
 * @param bool      $collage    The mixed-size homepage grid.
 */
function project_cards( array $selection, bool $filterable = false, bool $collage = false ): string {
	$out = '';

	foreach ( $selection as $index => $case_study ) {
		$discipline = discipline_of( $case_study );
		$size       = $collage ? ( 1 === $index || 2 === $index ? 'collage_wide' : 'collage_narrow' ) : 'default';
		$hooks      = $filterable
			? ' data-project data-industry="' . meta( $case_study, '_emposo_filter' ) . '" data-discipline="' . ( $discipline ? $discipline->slug : '' ) . '"'
			: '';

		$out .= '<a class="reference-card" href="/case-studies/' . $case_study->post_name . '/"' . $hooks . '>'
			. '<figure>' . picture( (int) get_post_thumbnail_id( $case_study ), $size, false, true ) . '</figure>'
			. '<div class="reference-card__copy"><div class="reference-card__meta">'
			. '<span>' . e( meta( $case_study, '_emposo_industry_label' ) ) . '</span>'
			. '<span>' . e( term_name( $discipline ) ) . '</span></div>'
			. '<h3>' . e( $case_study->post_title ) . '</h3>'
			. '<p>' . e( $case_study->post_excerpt ) . '</p>'
			. metric( $case_study )
			. '<span class="text-link">Case Study lesen ' . ARROW . '</span></div></a>';
	}

	return '<div class="reference-grid' . ( $collage ? ' reference-grid--collage' : '' ) . '"' . ( $filterable ? ' data-project-grid' : '' ) . '>' . $out . '</div>';
}

/**
 * The filter bar, count, grid and empty state.
 *
 * Branche + Leistung, chips A–Z with "Alle" first; a value no project carries
 * is rendered disabled, not hidden (owner 26.09., B-42).
 */
function filters(): string {
	$projects = case_studies();

	$carried = array(
		'industry'   => array(),
		'discipline' => array(),
	);
	foreach ( $projects as $case_study ) {
		foreach ( words( meta( $case_study, '_emposo_filter' ) ) as $token ) {
			$carried['industry'][ $token ] = true;
		}
		$discipline = discipline_of( $case_study );
		if ( $discipline ) {
			$carried['discipline'][ $discipline->slug ] = true;
		}
	}

	$az = static function ( array $terms ): array {
		$choices = array();
		foreach ( $terms as $term ) {
			$choices[] = array( $term->slug, term_name( $term ) );
		}
		usort(
			$choices,
			static function ( array $a, array $b ): int {
				return compare_de( $a[1], $b[1] );
			}
		);

		return $choices;
	};

	$groups = array(
		array( 'industry', 'Branche', 'Nach Branche filtern', $az( ordered_terms( TAX_INDUSTRY ) ) ),
		array( 'discipline', 'Leistung', 'Nach Leistung filtern', $az( disciplines() ) ),
	);

	$chip = static function ( string $group, string $key, string $name ) use ( $carried ): string {
		$all      = 'all' === $key;
		$disabled = ! $all && ! isset( $carried[ $group ][ $key ] );

		return '<button class="filter-button min-h-11' . ( $all ? ' is-active' : '' ) . '" type="button" data-filter-group="' . $group . '" data-filter-value="' . $key . '" aria-pressed="' . ( $all ? 'true' : 'false' ) . '"' . ( $disabled ? ' disabled' : '' ) . '>'
			. '<svg class="filter-button__check" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M3 8.5l3.2 3L13 4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
			. e( $name ) . '</button>';
	};

	$bar = '';
	foreach ( $groups as list( $key, $label, $aria, $choices ) ) {
		$rest = '';
		foreach ( $choices as list( $value, $name ) ) {
			$rest .= $chip( $key, $value, $name );
		}
		$bar .= '<div class="filter-group"><span>' . $label . '</span><div role="group" aria-label="' . $aria . '">'
			. $chip( $key, 'all', 'Alle' ) . '<div class="filter-choices">' . $rest . '</div></div></div>';
	}

	return '<div class="work-filter js-only">' . $bar . '</div>'
		. '<p class="work-count" id="project-count" aria-live="polite">' . count( $projects ) . ' Projekte</p>'
		. project_cards( $projects, true )
		. '<p class="work-empty" id="project-empty" hidden>Für diese Auswahl ist noch keine Referenz veröffentlicht. <a href="/kontakt/">Sprechen Sie mit uns über Ihre Branche.</a></p>';
}

/**
 * The disciplines as a 2×4 table with group headers.
 */
function discipline_grid(): string {
	$out = '';

	foreach ( ordered_terms( TAX_DISCIPLINE ) as $group ) {
		if ( 0 !== (int) $group->parent ) {
			continue;
		}

		$out .= '<h3 class="discipline-table__head">' . term_name( $group ) . '</h3>';

		foreach ( disciplines() as $discipline ) {
			if ( (int) $discipline->parent !== (int) $group->term_id ) {
				continue;
			}
			$id   = $discipline->term_id;
			$out .= '<article class="discipline-cell" id="' . $discipline->slug . '">'
				. '<span class="discipline-cell__icon" aria-hidden="true">' . icon( (string) get_term_meta( $id, '_emposo_icon', true ) ) . '</span>'
				. '<h4>' . e( term_name( $discipline ) ) . '</h4>'
				. '<p>' . e( (string) get_term_meta( $id, '_emposo_topics', true ) ) . '</p>'
				. '<p class="discipline-cell__promise">' . e( (string) get_term_meta( $id, '_emposo_promise', true ) ) . '</p></article>';
		}
	}

	return '<div class="discipline-table">' . $out . '</div>';
}

/**
 * The job postings on /karriere/, each in the canonical expander.
 */
function jobs_list(): string {
	$out = '';

	foreach ( option_list( 'emposo_jobs' ) as $job ) {
		$title = (string) ( $job['title'] ?? '' );
		$intro = array_map( 'strval', (array) ( $job['intro'] ?? array() ) );

		$meta = '';
		foreach ( (array) ( $job['meta'] ?? array() ) as $item ) {
			$meta .= '<span class="tag">' . e( (string) $item ) . '</span>';
		}

		$more = '';
		foreach ( array_slice( $intro, 1 ) as $text ) {
			$more .= '<p class="job-card__text">' . e( $text ) . '</p>';
		}
		foreach ( (array) ( $job['sections'] ?? array() ) as $section ) {
			$items = '';
			foreach ( (array) ( $section['items'] ?? array() ) as $item ) {
				$items .= '<li>' . e( (string) $item ) . '</li>';
			}
			$more .= '<h4>' . e( (string) ( $section['title'] ?? '' ) ) . '</h4><ul class="result-list result-list--compact">' . $items . '</ul>';
		}

		$out .= '<article class="job-card" id="' . (string) ( $job['slug'] ?? '' ) . '"><h3>' . e( $title ) . '</h3>'
			. '<p class="job-card__meta">' . $meta . '</p>'
			. '<p class="job-card__tagline">' . e( (string) ( $job['tagline'] ?? '' ) ) . '</p>'
			. '<p class="job-card__text">' . e( $intro[0] ?? '' ) . '</p>'
			. '<details class="expander"><summary class="min-h-11"><span class="expander__open">Zur vollständigen Ausschreibung</span><span class="expander__close">Weniger anzeigen</span><span class="sr-only"> – ' . e( $title ) . '</span></summary>'
			. $more
			. '<p class="job-card__text">' . e( (string) ( $job['apply'] ?? '' ) ) . '</p>'
			. '<p class="job-card__apply"><a class="text-link" href="mailto:' . APPLY_EMAIL . '?subject=' . encode_uri_component( 'Bewerbung: ' . $title ) . '">Bewerbung an ' . APPLY_EMAIL . '<span class="sr-only"> – ' . e( $title ) . '</span> <span aria-hidden="true">→</span></a></p>'
			. '</details></article>';
	}

	return '<div class="job-list">' . $out . '</div>'
		. '<p class="job-list__apply">Keine passende Position dabei? Schick uns Deine Initiativbewerbung an <a href="mailto:' . APPLY_EMAIL . '?subject=Initiativbewerbung">' . APPLY_EMAIL . '</a>.</p>';
}

/**
 * The one CTA block. Variants are content, never copied markup.
 *
 * @param string $name default | portfolio | karriere.
 */
function cta( string $name = 'default' ): string {
	$ctas = array(
		'default'   => array(
			'title' => 'Jetzt Kontakt aufnehmen!',
			'copy'  => array( 'Ob konkretes Vorhaben, erste Orientierung oder weitere Fragen: Erzählen Sie uns kurz, worum es geht.' ),
			'link'  => true,
		),
		'portfolio' => array(
			'id'    => 'portfolio-cta-title',
			'title' => 'Welche Leistung sollen wir für Sie <em>liefern?</em>',
			'copy'  => array( 'Von der bestehenden Leistung bis zum neuen Use Case: Sprechen wir über die Ergebnisdefinition und den sinnvollsten Einstieg.' ),
			'link'  => true,
		),
		'karriere'  => array(
			'id'      => 'karriere-statement-title',
			'eyebrow' => 'Warum Emposo',
			'title'   => 'Wir entwickeln nicht nur Technologien.<br>Wir schaffen <em>Ergebnisse.</em>',
			'copy'    => array( 'Dafür suchen wir Menschen, die neugierig sind, Verantwortung übernehmen und Dinge ins Ziel bringen wollen. Ob Engineering, Software, AI, Cyber Security oder Projektmanagement: Bei Emposo arbeitest Du an Projekten, die sichtbar etwas bewegen. Gemeinsam mit erfahrenen Kolleginnen und Kollegen, starken Kunden und der Skalierungskraft der Hays Gruppe.', '<strong>Tomorrow, created today.</strong>' ),
			'link'    => false,
		),
	);
	$c    = $ctas[ $name ] ?? $ctas['default'];

	$id   = (string) ( $c['id'] ?? '' );
	$copy = '';
	foreach ( $c['copy'] as $paragraph ) {
		$copy .= '<p>' . $paragraph . '</p>';
	}

	return '<section class="page-section page-section--deep"' . ( '' !== $id ? ' aria-labelledby="' . $id . '"' : '' ) . '><div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">'
		. '<div><p class="eyebrow eyebrow--light">' . ( $c['eyebrow'] ?? 'Ihr nächster Schritt' ) . '</p><h2 class="display-large display-large--light"' . ( '' !== $id ? ' id="' . $id . '"' : '' ) . '>' . $c['title'] . '</h2></div>'
		. '<div class="page-cta__copy">' . $copy . ( $c['link'] ? '<a class="text-link text-link--light" href="/kontakt/">Projekt besprechen <span aria-hidden="true">→</span></a>' : '' ) . '</div>'
		. '</div></div></div></section>';
}

/**
 * A case-study detail page.
 *
 * Related slot: same discipline, then same industry label, then the next
 * projects in data order (wrapping), so it always shows two cards.
 *
 * @param string $slug Case-study slug.
 */
function project_page( string $slug ): string {
	$projects = case_studies();
	$at       = null;

	foreach ( array_values( $projects ) as $i => $candidate ) {
		if ( $candidate->post_name === $slug ) {
			$at = (int) $i;
			break;
		}
	}

	if ( null === $at ) {
		return '';
	}

	$p          = $projects[ $at ];
	$discipline = discipline_of( $p );
	$d_slug     = $discipline ? $discipline->slug : '';
	$d_name     = term_name( $discipline );
	$industry   = meta( $p, '_emposo_industry_label' );

	$next    = array_merge( array_slice( $projects, $at + 1 ), array_slice( $projects, 0, $at ) );
	$related = array();
	foreach ( $projects as $other ) {
		if ( $other->ID !== $p->ID && discipline_of( $other ) && discipline_of( $other )->slug === $d_slug ) {
			$related[ $other->ID ] = $other;
		}
	}
	foreach ( $projects as $other ) {
		$other_d = discipline_of( $other );
		if ( $other->ID !== $p->ID && ( $other_d ? $other_d->slug : '' ) !== $d_slug && meta( $other, '_emposo_industry_label' ) === $industry ) {
			$related[ $other->ID ] = $other;
		}
	}
	foreach ( $next as $other ) {
		$related[ $other->ID ] = $related[ $other->ID ] ?? $other;
	}
	$related = array_slice( array_values( $related ), 0, 2 );

	$metric_value = meta( $p, '_emposo_metric' );

	$hero = page_hero(
		array(
			'id'     => 'project-title',
			'parent' => array( '/branchen/#referenzen', 'Projekte' ),
			'copy'   => '<p class="eyebrow eyebrow--light">' . e( $industry ) . '</p><h1 class="display-large display-large--light" id="project-title">' . e( $p->post_title ) . '</h1><p class="page-hero__intro">' . e( $p->post_excerpt ) . '</p>',
			'figure' => picture( (int) get_post_thumbnail_id( $p ), 'detail', true )
				. '<div class="page-hero__metric"><strong' . ( mb_strlen( $metric_value, 'UTF-8' ) > 8 ? ' class="page-hero__metric--word"' : '' ) . '>' . e( $metric_value ) . '</strong><span>' . e( meta( $p, '_emposo_metric_label' ) ) . '</span></div>',
		)
	);

	$facts = '';
	foreach ( meta_list( $p, '_emposo_facts' ) as $fact ) {
		$facts .= '<li>' . e( $fact ) . '</li>';
	}
	$results = '';
	foreach ( meta_list( $p, '_emposo_results' ) as $result ) {
		$results .= '<li>' . e( $result ) . '</li>';
	}

	return $hero
		. SECTION_GAP . '<section class="page-section"><div class="gutter"><div class="container"><h2 class="display-large" id="projekt-title">Projekt</h2>'
		. '<p class="section-lede">' . e( $industry ) . ' · ' . e( $d_name ) . '</p>'
		. ( '' !== $facts ? '<ul class="result-list result-list--compact project-facts">' . $facts . '</ul>' : '' )
		. '<div class="company-values case-facets">'
		. '<article><span class="company-values__icon" aria-hidden="true">' . icon( 'document-paper-line' ) . '</span><h3>Herausforderung</h3>' . column( $p, '_emposo_challenge' ) . '</article>'
		. '<article><span class="company-values__icon" aria-hidden="true">' . icon( 'lightbulb-shine-line' ) . '</span><h3>Lösung</h3>' . column( $p, '_emposo_solution' ) . '</article>'
		. '<article><span class="company-values__icon" aria-hidden="true">' . icon( 'check-discount-line' ) . '</span><h3>Ergebnis</h3><ul class="result-list">' . $results . '</ul></article>'
		. '</div><p class="section-more"><a class="text-link" href="/portfolio/#' . $d_slug . '">' . e( $d_name ) . ' ' . ARROW . '</a></p></div></div></section>'
		. SECTION_GAP . '<section class="page-section page-section--paper"><div class="gutter"><div class="container"><p class="eyebrow">Weitere Projekte</p><h2 class="display-large">Expertise, die Ergebnisse liefert.</h2>'
		. project_cards( $related )
		. '<p class="section-more"><a class="text-link" href="/branchen/#referenzen">Alle Projekte ' . ARROW . '</a></p></div></div></section>'
		. cta();
}

/**
 * Split a bio into the visible teaser (≤ 48 words, cut at a sentence
 * boundary) and the rest, as render.mjs splitBio() does.
 *
 * @param string[] $paragraphs Bio paragraphs.
 * @return array{0: string[], 1: string[]} Teaser and rest.
 */
function split_bio( array $paragraphs ): array {
	$teaser = array();
	$rest   = array();
	$count  = 0;
	$full   = false;

	foreach ( $paragraphs as $paragraph ) {
		if ( $full ) {
			$rest[] = $paragraph;
			continue;
		}

		$keep  = '';
		$spill = '';
		$found = preg_match_all( '/[^.!?]+[.!?]+["\']?(\s+|$)/u', $paragraph, $matches );
		foreach ( $found ? $matches[0] : array( $paragraph ) as $sentence ) {
			$words = count( words( $sentence ) );
			if ( ! $full && $count + $words <= 48 ) {
				$keep  .= $sentence;
				$count += $words;
			} else {
				$full   = true;
				$spill .= $sentence;
			}
		}

		if ( '' !== trim( $keep ) ) {
			$teaser[] = trim( $keep );
		}
		if ( '' !== trim( $spill ) ) {
			$rest[] = trim( $spill );
		}
	}

	return array( $teaser, $rest );
}

/**
 * The management cards: photo, name, roles, teaser, Mehr-lesen expander.
 */
function management(): string {
	$cards = '';

	foreach ( ordered_posts( CPT_PERSON ) as $person ) {
		$name     = $person->post_title;
		$roles    = meta_list( $person, '_emposo_person_roles' );
		$linkedin = meta( $person, '_emposo_person_linkedin' );
		$blocks   = preg_split( '/\R{2,}/', trim( (string) $person->post_content ) );
		$bio      = array_values( array_filter( array_map( 'trim', is_array( $blocks ) ? $blocks : array() ) ) );

		list( $teaser, $rest ) = split_bio( $bio );

		$more = '';
		foreach ( $rest as $text ) {
			$more .= '<p class="management-card__bio">' . e( $text ) . '</p>';
		}
		if ( '' !== $linkedin ) {
			$more .= '<p class="management-card__bio"><a class="text-link" href="' . $linkedin . '">' . e( $name ) . ' auf LinkedIn ' . ARROW . '</a></p>';
		}

		$visible = '';
		foreach ( $teaser as $text ) {
			$visible .= '<p class="management-card__bio">' . e( $text ) . '</p>';
		}

		$cards .= '<article class="management-card"><figure>' . picture( (int) get_post_thumbnail_id( $person ), 'management', false, true ) . '</figure>'
			. '<h3>' . e( $name ) . '</h3><p class="management-card__role">' . implode( '<br>', array_map( __NAMESPACE__ . '\\e', $roles ) ) . '</p>'
			. $visible
			. '<details class="expander"><summary class="min-h-11"><span class="expander__open">Mehr lesen</span><span class="expander__close">Weniger anzeigen</span><span class="sr-only"> – ' . e( $name ) . '</span></summary>' . $more . '</details>'
			. '</article>';
	}

	return '<section class="page-section page-section--paper" id="management" aria-labelledby="management-title"><div class="gutter"><div class="container"><p class="eyebrow">Management</p><h2 class="display-large" id="management-title">Menschen, die Verantwortung übernehmen.</h2><div class="management-cards">' . $cards . '</div></div></div></section>';
}

/**
 * Onward links at the end of pages that would otherwise dead-end (B-45).
 */
function keep_exploring(): string {
	$links = '';
	foreach ( array(
		array( '/portfolio/', 'Leistungen' ),
		array( '/branchen/', 'Branchen' ),
		array( '/about-us/', 'Über uns' ),
	) as list( $href, $label ) ) {
		$links .= '<li><a class="text-link" href="' . $href . '">' . $label . ' <span aria-hidden="true">→</span></a></li>';
	}

	return '<section class="page-section explore" aria-labelledby="explore-title"><div class="gutter"><div class="container"><h2 class="explore__title" id="explore-title">Weiter entdecken</h2><ul class="explore__links">' . $links . '</ul></div></div></section>';
}

/**
 * The HTML sitemap.
 */
function sitemap(): string {
	$projects = '';
	foreach ( case_studies() as $case_study ) {
		$projects .= '<a href="/case-studies/' . $case_study->post_name . '/">' . e( $case_study->post_title ) . '</a>';
	}

	return '<div><h2>Leistungen</h2><a href="/">Startseite</a><a href="/portfolio/">Unsere Leistungen</a></div>'
		. '<div><h2>Branchen</h2><a href="/branchen/">Alle Branchen</a><h2>Unternehmen</h2><a href="/about-us/">Über uns</a><a href="/about-us/#management">Management</a><a href="/karriere/">Karriere</a><a href="/kontakt/">Kontakt</a><a href="/cookies/">Cookies</a><a href="/barrierefreiheit/">Barrierefreiheit</a><a href="/impressum/">Impressum</a><a href="/datenschutzerklaerung/">Datenschutz</a><a href="/nutzungsbestimmungen/">Nutzungsbestimmungen</a></div>'
		. '<div><h2>Projekte</h2><a href="/branchen/#referenzen">Alle Projekte</a>' . $projects . '</div>';
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

	return array_values( array_filter( array_map( static fn( string $slug ) => $by_slug[ $slug ] ?? null, $slugs ) ) );
}

/**
 * A `<!-- content:name -->` include (render.mjs fragment()).
 *
 * @param string $name Fragment name.
 */
function render( string $name ): string {
	switch ( $name ) {
		case 'industry-cards':
			return industry_cards();
		case 'trust-strip':
			return trust_strip();
		case 'cta-portfolio':
			return cta( 'portfolio' );
		case 'cta-karriere':
			return cta( 'karriere' );
		case 'company-facts':
			return company_facts();
		case 'jobs':
			return jobs_list();
		case 'keep-exploring':
			return keep_exploring();
		case 'projects-featured':
			return project_cards( by_slugs( FEATURED ), false, true );
		case 'projects-all':
			return filters();
		case 'disciplines':
			return discipline_grid();
		case 'management':
			return management();
		case 'sitemap':
			return sitemap();
	}

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		_doing_it_wrong( __FUNCTION__, esc_html( sprintf( 'Unknown fragment: %s', $name ) ), '0.1.0' );
	}

	return '';
}

/**
 * A detail body from the route contract (`project:<slug>`).
 *
 * @param string $kind Body kind.
 * @param string $slug Record slug.
 */
function render_detail( string $kind, string $slug ): string {
	return 'project' === $kind ? project_page( $slug ) : '';
}
