<?php
/**
 * `wp emposo import` — seed content from the frozen export.
 *
 * Subcommands run in dependency order: terms, then media, then the records that
 * reference both, then the relations that resolve slugs to IDs which only exist
 * after the records.
 *
 * Idempotent by design. Every object carries `_emposo_source_slug`, so a rerun
 * updates rather than duplicates. A per-field hash lets editor changes survive
 * a rerun — but ONLY in the records stage (update_fields()): people, terms,
 * relations, options and the media alt/title refresh rewrite unconditionally,
 * so after go-live re-run records freely and the other stages only when the
 * export is the intended source of truth again.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\CLI;

use WP_CLI;
use WP_Error;
use WP_Post;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY;
use const Emposo\Core\ContentModel\CPT_PERSON;
use const Emposo\Core\ContentModel\TAX_DISCIPLINE;
use const Emposo\Core\ContentModel\TAX_INDUSTRY;
use const Emposo\Core\ContentModel\TAX_OUTCOME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seeds terms, media, records, relations, people and options.
 */
class Import_Command {

	/**
	 * Decoded export.
	 *
	 * @var array<string, mixed>
	 */
	private array $data = array();

	/**
	 * Whether to write anything.
	 *
	 * @var bool
	 */
	private bool $dry_run = false;

	/**
	 * Whether to overwrite editor-modified fields.
	 *
	 * @var bool
	 */
	private bool $force = false;

	/**
	 * Planned actions, for --dry-run.
	 *
	 * @var array<int, array<string, string>>
	 */
	private array $plan = array();

	/**
	 * Run every step in dependency order.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Print the plan and write nothing.
	 *
	 * [--force]
	 * : Overwrite fields an editor has changed since the last import.
	 *
	 * [--export=<file>]
	 * : Export JSON. Defaults to the bundled copy.
	 *
	 * ## EXAMPLES
	 *
	 *     wp emposo import all --dry-run
	 *     wp emposo import all
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function all( array $args, array $assoc_args ): void {
		$this->boot( $assoc_args );

		$this->do_terms();
		$this->do_media();
		$this->do_records();
		$this->do_relations();
		$this->do_people();
		$this->do_options();

		$this->finish();
	}

	/**
	 * Create the classification terms.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Print the plan and write nothing.
	 *
	 * [--export=<file>]
	 * : Export JSON.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function terms( array $args, array $assoc_args ): void {
		$this->boot( $assoc_args );
		$this->do_terms();
		$this->finish();
	}

	/**
	 * Import the 22 manifest images with their alt text.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Print the plan and write nothing.
	 *
	 * [--export=<file>]
	 * : Export JSON.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function media( array $args, array $assoc_args ): void {
		$this->boot( $assoc_args );
		$this->do_media();
		$this->finish();
	}

	/**
	 * Populate the scaffolded records with content.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Print the plan and write nothing.
	 *
	 * [--force]
	 * : Overwrite editor-modified fields.
	 *
	 * [--export=<file>]
	 * : Export JSON.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function records( array $args, array $assoc_args ): void {
		$this->boot( $assoc_args );
		$this->do_records();
		$this->finish();
	}

	/**
	 * Assign terms and resolve entity relations.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Print the plan and write nothing.
	 *
	 * [--export=<file>]
	 * : Export JSON.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function relations( array $args, array $assoc_args ): void {
		$this->boot( $assoc_args );
		$this->do_relations();
		$this->finish();
	}

	/**
	 * Import the management roster.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Print the plan and write nothing.
	 *
	 * [--export=<file>]
	 * : Export JSON.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function people( array $args, array $assoc_args ): void {
		$this->boot( $assoc_args );
		$this->do_people();
		$this->finish();
	}

	/**
	 * Seed the site options: facts, locations, trust strip, FAQ, interests.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Print the plan and write nothing.
	 *
	 * [--export=<file>]
	 * : Export JSON.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function options( array $args, array $assoc_args ): void {
		$this->boot( $assoc_args );
		$this->do_options();
		$this->finish();
	}

	// ----------------------------------------------------------------------
	// Shared lifecycle
	// ----------------------------------------------------------------------

	/**
	 * Load the export and set up bulk-import conditions.
	 *
	 * @param array<string, string> $assoc_args Flags.
	 */
	private function boot( array $assoc_args ): void {
		$this->dry_run = isset( $assoc_args['dry-run'] );
		$this->force   = isset( $assoc_args['force'] );

		/*
		 * kses. Under WP-CLI there is no current user, so
		 * current_user_can('unfiltered_html') is false and content_save_pre runs
		 * wp_filter_post_kses — which strips data-* attributes outright and
		 * mangles block-delimiter JSON. Structurally we avoid raw HTML in
		 * post_content, but assert the capability anyway so a future change
		 * that does store markup fails loudly here rather than silently
		 * shipping stripped output.
		 */
		if ( ! $this->dry_run && ! current_user_can( 'unfiltered_html' ) ) {
			WP_CLI::error(
				'Run as a user with unfiltered_html, e.g. `wp --user=1 emposo import all`. '
				. 'Without it kses strips data-* attributes and block delimiters from any stored markup.'
			);
		}

		$path = $assoc_args['export'] ?? EMPOSO_CORE_DIR . '/data/site-export.json';
		if ( ! file_exists( $path ) ) {
			WP_CLI::error( sprintf( 'Export not found: %s. Run tools/export-content.mjs.', $path ) );
		}

		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reads a local JSON file bundled with this plugin, from CLI.
		$decoded = json_decode( (string) file_get_contents( $path ), true );
		if ( ! is_array( $decoded ) ) {
			WP_CLI::error( 'Export is not valid JSON.' );
		}
		if ( 1 !== ( $decoded['schema'] ?? 0 ) ) {
			WP_CLI::error(
				sprintf( 'Export schema %s is not supported; this importer expects 1.', (string) ( $decoded['schema'] ?? 'missing' ) )
			);
		}
		$this->data = $decoded;

		if ( $this->dry_run ) {
			return;
		}

		if ( ! defined( 'WP_IMPORTING' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core constant.
			define( 'WP_IMPORTING', true );
		}
		wp_defer_term_counting( true );
		wp_suspend_cache_invalidation( true );
	}

	/**
	 * Unwind the bulk-import conditions and report.
	 */
	private function finish(): void {
		if ( $this->dry_run ) {
			WP_CLI::log( sprintf( 'Plan (%d action(s)):', count( $this->plan ) ) );
			foreach ( $this->plan as $row ) {
				WP_CLI::line( sprintf( '  %-14s %-22s %s', $row['action'], $row['kind'], $row['name'] ) );
			}
			WP_CLI::success( 'Dry run complete; nothing was written.' );

			return;
		}

		/*
		 * Unwind in this order. While invalidation is suspended, reads return
		 * stale data — which is why relations run as their own pass and why the
		 * flush happens before anything verifies.
		 */
		wp_suspend_cache_invalidation( false );
		wp_cache_flush();
		wp_defer_term_counting( false );

		$counts = array_count_values( wp_list_pluck( $this->plan, 'action' ) );

		/*
		 * Record the run. Nothing in the content model carries a "last
		 * imported" timestamp, and the admin dashboard needs one: an importer
		 * that last ran before the current export is a silent staleness bug,
		 * and the only way to see it is to write down when it ran.
		 */
		update_option(
			'emposo_last_import',
			array(
				'time'    => time(),
				'actions' => $counts,
			),
			false
		);
		WP_CLI::success(
			sprintf(
				'%d action(s): %s',
				count( $this->plan ),
				implode( ', ', array_map( static fn( $k, $v ) => "{$v} {$k}", array_keys( $counts ), $counts ) )
			)
		);
	}

	/**
	 * Record a planned or performed action.
	 *
	 * @param string $action Action verb.
	 * @param string $kind   Object kind.
	 * @param string $name   Object name.
	 */
	private function note( string $action, string $kind, string $name ): void {
		$this->plan[] = array(
			'action' => $action,
			'kind'   => $kind,
			'name'   => $name,
		);
	}

	// ----------------------------------------------------------------------
	// Steps
	// ----------------------------------------------------------------------

	/**
	 * Create the classification terms, parents before children.
	 */
	private function do_terms(): void {
		$taxonomies = array(
			TAX_DISCIPLINE => $this->data['terms']['emposo_discipline'] ?? array(),
			TAX_INDUSTRY   => $this->data['terms']['emposo_industry'] ?? array(),
			TAX_OUTCOME    => $this->data['terms']['emposo_outcome'] ?? array(),
		);

		foreach ( $taxonomies as $taxonomy => $terms ) {
			/*
			 * Creation order and DISPLAY order are different things, and
			 * conflating them was a real bug: sorting parents first is required
			 * so a child's parent exists when it is created, but it also moved
			 * `automotive` to the end of the array — and since the display
			 * order was assigned from the loop position, the filter bar lost
			 * the source's hierarchical walk (aerospace, energy, health,
			 * industrial, automotive, technology).
			 *
			 * So capture the source position BEFORE sorting, and store that as
			 * the order.
			 */
			foreach ( $terms as $position => $term ) {
				$terms[ $position ]['source_order'] = $position;
			}

			// Parents first: a child's parent must exist when it is created.
			usort(
				$terms,
				static function ( array $a, array $b ): int {
					return ( null === ( $a['parent'] ?? null ) ? 0 : 1 ) <=> ( null === ( $b['parent'] ?? null ) ? 0 : 1 );
				}
			);

			foreach ( $terms as $term ) {
				$slug   = (string) $term['slug'];
				$name   = (string) $term['name'];
				$parent = 0;

				if ( ! empty( $term['parent'] ) ) {
					$parent_term = get_term_by( 'slug', (string) $term['parent'], $taxonomy );
					// absint rather than a plain cast: wp_insert_term/wp_update_term
					// type `parent` as non-negative, and a cast alone does not
					// narrow that for static analysis.
					$parent = $parent_term ? absint( $parent_term->term_id ) : 0;
				}

				$existing = get_term_by( 'slug', $slug, $taxonomy );

				if ( $this->dry_run ) {
					$this->note( $existing ? 'update' : 'create', $taxonomy, $slug );
					continue;
				}

				if ( $existing ) {
					wp_update_term(
						(int) $existing->term_id,
						$taxonomy,
						wp_slash(
							array(
								'name'   => $name,
								'parent' => $parent,
							) 
						)
					);
					$term_id = (int) $existing->term_id;
					$this->note( 'update', $taxonomy, $slug );
				} else {
					$created = wp_insert_term(
						wp_slash( $name ),
						$taxonomy,
						wp_slash(
							array(
								'slug'   => $slug,
								'parent' => $parent,
							) 
						)
					);
					if ( $created instanceof WP_Error ) {
						WP_CLI::error( sprintf( '%s/%s: %s', $taxonomy, $slug, $created->get_error_message() ) );
					}
					$term_id = (int) $created['term_id'];
					$this->note( 'create', $taxonomy, $slug );
				}

				/*
				 * get_terms() defaults to alphabetical, which would put
				 * Automotive second and break the filter bar's source order.
				 * The explicit order is stored so every query can request it.
				 */
				update_term_meta( $term_id, '_emposo_term_order', (int) $term['source_order'] );
				update_term_meta( $term_id, '_emposo_source_slug', $slug );
			}
		}
	}

	/**
	 * Import the 22 manifest images.
	 *
	 * Alt text is the reason this is its own step and is verified separately:
	 * the manifest is the SOLE source of alt text for every photograph on the
	 * site, so losing it is an accessibility regression with no visual symptom.
	 */
	private function do_media(): void {
		$assets = $this->data['assets'] ?? array();
		$dir    = get_template_directory();

		foreach ( $assets as $asset ) {
			$key = (string) $asset['key'];

			$existing = $this->find_attachment( $key );

			if ( $existing instanceof WP_Post ) {
				/*
				 * Refresh metadata, never re-sideload. Copying the file again
				 * would either waste the work or create a "-1" duplicate; the
				 * metadata is the only part that can legitimately change, and
				 * alt text is re-asserted every run because a missing alt is
				 * invisible until an audit catches it.
				 */
				if ( ! $this->dry_run ) {
					$this->apply_asset_meta( $existing->ID, $asset );
				}
				$this->note( 'refresh', 'attachment', $key );
				continue;
			}

			// The manifest's src is site-absolute; resolve it inside the theme.
			$relative = ltrim( str_replace( '/assets/', 'assets/', (string) $asset['src'] ), '/' );
			$file     = $dir . '/' . $relative;

			if ( ! file_exists( $file ) ) {
				WP_CLI::warning( sprintf( '%s: file missing at %s', $key, $relative ) );
				continue;
			}

			if ( $this->dry_run ) {
				$this->note( 'import', 'attachment', $key );
				continue;
			}

			$id = $this->sideload( $file, (string) $asset['alt'], $key );
			if ( 0 === $id ) {
				continue;
			}

			$this->apply_asset_meta( $id, $asset );

			$this->note( 'import', 'attachment', $key );
		}
	}

	/**
	 * Write the metadata that makes an attachment usable by the picture helper.
	 *
	 * Kept in one place so the import and refresh paths cannot drift: a field
	 * added on import but forgotten on refresh would appear to work on a fresh
	 * database and be missing on every existing one.
	 *
	 * @param int                  $id    Attachment ID.
	 * @param array<string, mixed> $asset Manifest entry.
	 */
	private function apply_asset_meta( int $id, array $asset ): void {
		$key = (string) $asset['key'];

		// The manifest is the SOLE source of alt text for every photograph on
		// the site, so losing it is an accessibility regression with no visual
		// symptom.
		update_post_meta( $id, '_wp_attachment_image_alt', wp_slash( (string) $asset['alt'] ) );
		update_post_meta( $id, '_emposo_asset_key', $key );
		update_post_meta( $id, '_emposo_source_slug', '/' . $key . '/' );

		/*
		 * The pre-built AVIF/WebP pyramid, recorded verbatim.
		 *
		 * Core cannot generate AVIF subsizes reliably (it depends on the host's
		 * Imagick build) and never emits <picture>, so the variant set travels
		 * as metadata for the picture helper to read. The tiers are NOT a
		 * uniform pair: each image has a 640 tier plus a per-image large tier,
		 * and JPEG exists only at the large width.
		 */
		update_post_meta( $id, '_emposo_variants', wp_slash( $asset['variants'] ) );

		/*
		 * The pre-built JPEG fallback path, so the <img> is served from the same
		 * place as the <source> candidates: all three formats ship with the
		 * theme and share its cache headers. The attachment stays the editorial
		 * record (alt text, featured-image relations, the media picker) — it is
		 * simply not the delivery path for these 22.
		 */
		update_post_meta( $id, '_emposo_fallback_src', (string) $asset['src'] );
	}

	/**
	 * Fill the scaffolded records with content.
	 */
	private function do_records(): void {
		foreach ( (array) ( $this->data['projects'] ?? array() ) as $index => $project ) {
			$this->apply_case_study( $project, $index );
		}
		foreach ( (array) ( $this->data['disciplines'] ?? array() ) as $index => $discipline ) {
			$this->apply_discipline( $discipline, $index );
		}
		foreach ( (array) ( $this->data['industries'] ?? array() ) as $index => $industry ) {
			$this->apply_industry( $industry, $index );
		}
	}

	/**
	 * Apply one case study's fields.
	 *
	 * @param array<string, mixed> $project Project record.
	 * @param int                  $index   Position in the source array.
	 */
	private function apply_case_study( array $project, int $index ): void {
		$slug = (string) $project['slug'];
		$post = $this->find_post( CPT_CASE_STUDY, '/case-studies/' . $slug . '/' );

		if ( ! $post instanceof WP_Post ) {
			WP_CLI::warning( sprintf( 'case study %s not scaffolded; run `wp emposo scaffold` first', $slug ) );

			return;
		}

		if ( $this->dry_run ) {
			$this->note( 'populate', 'case study', $slug );

			return;
		}

		$this->update_fields(
			$post,
			array(
				'post_title'   => (string) $project['name'],
				// The headline drives cards, the hero, the meta description and
				// OG. An empty excerpt would be auto-generated from the
				// challenge text, silently replacing a written headline.
				'post_excerpt' => (string) $project['headline'],
				'menu_order'   => $index * 10,
			),
			array(
				// sanitize_text_field only: these carry qualifiers that are
				// factual claims and must survive byte-for-byte.
				'_emposo_metric'       => (string) $project['metric'],
				'_emposo_metric_label' => (string) $project['label'],
				'_emposo_challenge'    => (string) $project['challenge'],
				'_emposo_solution'     => (string) $project['solution'],
				'_emposo_results'      => array_map( 'strval', (array) $project['results'] ),
			)
		);

		$this->note( 'populate', 'case study', $slug );
	}

	/**
	 * Apply one discipline page's fields.
	 *
	 * @param array<string, mixed> $discipline Discipline record.
	 * @param int                  $index      Position in the source array.
	 */
	private function apply_discipline( array $discipline, int $index ): void {
		$slug = (string) $discipline['slug'];
		$post = $this->find_post( 'page', '/expertise/' . $slug . '/' );

		if ( ! $post instanceof WP_Post ) {
			WP_CLI::warning( sprintf( 'discipline page %s not scaffolded', $slug ) );

			return;
		}

		if ( $this->dry_run ) {
			$this->note( 'populate', 'discipline', $slug );

			return;
		}

		$term = get_term_by( 'slug', $slug, TAX_DISCIPLINE );

		$this->update_fields(
			$post,
			array(
				'post_title'   => (string) $discipline['name'],
				'post_excerpt' => (string) $discipline['promise'],
				'menu_order'   => $index * 10,
			),
			array(
				'_emposo_topics'          => (string) $discipline['topics'],
				'_emposo_detail'          => (string) $discipline['detail'],
				'_emposo_focus'           => array_map( 'strval', (array) $discipline['focus'] ),
				'_emposo_discipline_term' => $term ? (int) $term->term_id : 0,
			)
		);

		$this->note( 'populate', 'discipline', $slug );
	}

	/**
	 * Apply one industry page's fields.
	 *
	 * @param array<string, mixed> $industry Industry record.
	 * @param int                  $index    Position in the source array.
	 */
	private function apply_industry( array $industry, int $index ): void {
		$slug = (string) $industry['slug'];
		$post = $this->find_post( 'page', '/branchen/' . $slug . '/' );

		if ( ! $post instanceof WP_Post ) {
			WP_CLI::warning( sprintf( 'industry page %s not scaffolded', $slug ) );

			return;
		}

		if ( $this->dry_run ) {
			$this->note( 'populate', 'industry', $slug );

			return;
		}

		// The term slug is the FILTER token, not the page slug.
		$token = explode( ' ', (string) $industry['filter'] )[0];
		$term  = get_term_by( 'slug', $token, TAX_INDUSTRY );

		$this->update_fields(
			$post,
			array(
				'post_title'   => (string) $industry['name'],
				'post_excerpt' => (string) $industry['intro'],
				'menu_order'   => $index * 10,
			),
			array(
				'_emposo_subtitle'      => (string) ( $industry['subtitle'] ?? '' ),
				'_emposo_challenge'     => (string) $industry['challenge'],
				'_emposo_delivery'      => (string) $industry['delivery'],
				'_emposo_industry_term' => $term ? (int) $term->term_id : 0,
			)
		);

		$this->note( 'populate', 'industry', $slug );
	}

	/**
	 * Assign terms to case studies, with ancestor closure.
	 */
	private function do_relations(): void {
		foreach ( (array) ( $this->data['projects'] ?? array() ) as $project ) {
			$slug = (string) $project['slug'];
			$post = $this->find_post( CPT_CASE_STUDY, '/case-studies/' . $slug . '/' );

			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			if ( $this->dry_run ) {
				$this->note( 'relate', 'case study', $slug );
				continue;
			}

			/*
			 * Ancestor closure. `filter` already lists both tokens
			 * ('industrial automotive'), but assign ancestors explicitly so the
			 * invariant holds even if a future record lists only the child:
			 * otherwise the client-side `industrial` button and a server-side
			 * include_children query disagree, and the filter counts diverge
			 * from the data.
			 */
			$tokens = preg_split( '/\s+/', (string) $project['filter'] );
			if ( ! is_array( $tokens ) ) {
				$tokens = array();
			}
			$ids = array();
			foreach ( $tokens as $token ) {
				$term = get_term_by( 'slug', $token, TAX_INDUSTRY );
				if ( ! $term ) {
					continue;
				}
				$ids[] = (int) $term->term_id;
				foreach ( get_ancestors( (int) $term->term_id, TAX_INDUSTRY ) as $ancestor ) {
					$ids[] = (int) $ancestor;
				}
			}
			wp_set_object_terms( $post->ID, array_values( array_unique( $ids ) ), TAX_INDUSTRY, false );

			// Exactly one discipline (a CHILD term) and one outcome.
			$discipline = get_term_by( 'slug', (string) $project['discipline'], TAX_DISCIPLINE );
			if ( $discipline ) {
				wp_set_object_terms( $post->ID, array( (int) $discipline->term_id ), TAX_DISCIPLINE, false );
			}

			$outcome = get_term_by( 'slug', (string) $project['outcome'], TAX_OUTCOME );
			if ( $outcome ) {
				wp_set_object_terms( $post->ID, array( (int) $outcome->term_id ), TAX_OUTCOME, false );
			}

			$this->attach_featured_image( $post->ID, (string) $project['image'] );

			$this->note( 'relate', 'case study', $slug );
		}

		/*
		 * An industry's curated discipline list. Resolved here rather than in
		 * the records pass because it maps slugs to page IDs, and those pages
		 * are created by the scaffolder but only identifiable after every
		 * record has its source key.
		 */
		foreach ( (array) ( $this->data['industries'] ?? array() ) as $industry ) {
			if ( $this->dry_run ) {
				continue;
			}

			$page = $this->find_post( 'page', '/branchen/' . (string) $industry['slug'] . '/' );
			if ( ! $page instanceof WP_Post ) {
				continue;
			}

			$ids = array();
			foreach ( (array) $industry['disciplines'] as $discipline_slug ) {
				$discipline_page = $this->find_post( 'page', '/expertise/' . (string) $discipline_slug . '/' );
				if ( $discipline_page instanceof WP_Post ) {
					$ids[] = $discipline_page->ID;
				}
			}

			update_post_meta( $page->ID, '_emposo_related_disciplines', $ids );

			/*
			 * `$industry['cases']` is read from the export and deliberately NOT
			 * written to _emposo_case_order. That meta is an editorial override,
			 * and the cases list is not the order the site renders: the static
			 * build orders these cards by its global projects array filtered by
			 * industry. On industrials-manufacturing the reference renders
			 * rechenzentrums-umzug third and the cases list has it last, so
			 * importing the list would silently reorder a live page and break
			 * parity. See the matching comment in fragments.php.
			 */
		}

		// Discipline and industry pages get their featured images too.
		foreach ( array(
			'disciplines' => '/expertise/',
			'industries'  => '/branchen/',
		) as $group => $prefix ) {
			foreach ( (array) ( $this->data[ $group ] ?? array() ) as $record ) {
				if ( $this->dry_run ) {
					continue;
				}
				$post = $this->find_post( 'page', $prefix . (string) $record['slug'] . '/' );
				if ( $post instanceof WP_Post ) {
					$this->attach_featured_image( $post->ID, (string) $record['image'] );
				}
			}
		}
	}

	/**
	 * Import the management roster.
	 *
	 * The three-tier shape is deliberate and is what makes "never invent roles
	 * or bios" structural rather than a note: two people have a role and a bio,
	 * two have a photo only, three have initials only. The renderer picks a
	 * tier from what exists, so there is no placeholder path and no field an
	 * editor can half-fill into a fabricated role.
	 */
	private function do_people(): void {
		foreach ( (array) ( $this->data['people'] ?? array() ) as $index => $person ) {
			$name = (string) $person['name'];
			$key  = '/person/' . sanitize_title( $name ) . '/';
			$post = $this->find_post( CPT_PERSON, $key );

			if ( $this->dry_run ) {
				$this->note( $post instanceof WP_Post ? 'update' : 'create', 'person', $name );
				continue;
			}

			$bio = implode( "\n\n", array_map( 'strval', (array) $person['bio'] ) );

			$postarr = array(
				'post_type'    => CPT_PERSON,
				'post_status'  => 'publish',
				'post_title'   => $name,
				'post_name'    => sanitize_title( $name ),
				'post_content' => $bio,
				'menu_order'   => $index * 10,
				// The identity key rides the insert itself: written afterwards,
				// a request killed in between leaves a person the re-run cannot
				// find, and the "idempotent" retry duplicates them.
				'meta_input'   => array( '_emposo_source_slug' => $key ),
			);
			if ( $post instanceof WP_Post ) {
				$postarr['ID'] = $post->ID;
			}

			$id = wp_insert_post( wp_slash( $postarr ), true );
			if ( $id instanceof WP_Error ) {
				WP_CLI::error( sprintf( 'person %s: %s', $name, $id->get_error_message() ) );
			}
			$id = (int) $id;

			update_post_meta( $id, '_emposo_person_role', wp_slash( (string) $person['role'] ) );
			update_post_meta( $id, '_emposo_person_initials', wp_slash( (string) $person['initials'] ) );
			update_post_meta( $id, '_emposo_person_linkedin', wp_slash( (string) $person['linkedin'] ) );

			if ( '' !== (string) $person['image'] ) {
				$this->attach_featured_image( $id, (string) $person['image'] );
			}

			$this->note( $post instanceof WP_Post ? 'update' : 'create', 'person', $name );
		}
	}

	/**
	 * Seed the global options.
	 */
	private function do_options(): void {
		$options = (array) ( $this->data['options'] ?? array() );

		$map = array(
			'emposo_facts'       => $options['facts'] ?? array(),
			'emposo_locations'   => $options['locations'] ?? array(),
			'emposo_trust_strip' => $options['trustStrip'] ?? array(),
			'emposo_faq'         => $options['faq'] ?? array(),
			'emposo_interests'   => $options['interests'] ?? array(),
		);

		foreach ( $map as $name => $value ) {
			if ( $this->dry_run ) {
				$this->note( 'set', 'option', $name );
				continue;
			}
			update_option( $name, $value, false );
			$this->note( 'set', 'option', $name );
		}

		/*
		 * The contact recipient becomes a setting, defaulting to whatever the
		 * static form currently uses so rendered output is unchanged. It is a
		 * personal address, which the dashboard surfaces as a standing warning
		 * rather than silently substituting something we were not given.
		 */
		if ( ! $this->dry_run ) {
			$recipient = (string) ( $options['contactRecipient'] ?? '' );
			if ( '' !== $recipient && '' === (string) get_option( 'emposo_contact_recipient', '' ) ) {
				update_option( 'emposo_contact_recipient', sanitize_email( $recipient ), false );
			}
		}
		$this->note( 'set', 'option', 'emposo_contact_recipient' );
	}

	// ----------------------------------------------------------------------
	// Helpers
	// ----------------------------------------------------------------------

	/**
	 * What the old `(string) $array` cast hashed every array value to.
	 *
	 * It is the MD5 of the literal string "Array", which is what PHP produced
	 * for every array here. Kept as a constant so the migration in
	 * update_fields() is legible rather than a bare magic string.
	 */
	private const LEGACY_ARRAY_HASH = '4410ec34d9e6c1a68100ca0ce033fb17';

	/**
	 * A hash of a meta value that is stable for arrays as well as scalars.
	 *
	 * JSON, not serialize(): serialize() encodes float precision and array
	 * ordering in ways that differ across PHP versions, and this hash has to
	 * mean the same thing on a developer's machine and on a runner.
	 *
	 * @param mixed $value Meta value.
	 */
	private function fingerprint( $value ): string {
		return md5( is_scalar( $value ) ? (string) $value : (string) wp_json_encode( $value ) );
	}

	/**
	 * Whether a meta value counts as empty.
	 *
	 * An empty string, an empty array and a missing value all mean "the editor
	 * has not put anything here", and none of them should read as an edit.
	 *
	 * @param mixed $value Meta value.
	 */
	private function is_blank( $value ): bool {
		if ( is_array( $value ) ) {
			return array() === $value;
		}

		return '' === (string) $value;
	}

	/**
	 * Update post fields and meta, preserving editor changes unless --force.
	 *
	 * A per-field hash of what the importer last wrote is stored alongside the
	 * value. On a rerun, a field whose current value no longer matches that
	 * hash has been changed by a human, so it is left alone. That is what makes
	 * the importer safe to run after go-live instead of only once.
	 *
	 * @param WP_Post              $post   Target post.
	 * @param array<string, mixed> $fields Post fields.
	 * @param array<string, mixed> $meta   Meta fields.
	 */
	private function update_fields( WP_Post $post, array $fields, array $meta ): void {
		$hashes  = (array) get_post_meta( $post->ID, '_emposo_import_hash', true );
		$postarr = array( 'ID' => $post->ID );

		foreach ( $fields as $field => $value ) {
			$current  = (string) ( $post->$field ?? '' );
			$expected = (string) ( $hashes[ $field ] ?? '' );

			$edited = '' !== $expected && md5( $current ) !== $expected && '' !== $current;
			if ( $edited && ! $this->force ) {
				continue;
			}

			$postarr[ $field ] = $value;
			$hashes[ $field ]  = md5( (string) $value );
		}

		if ( count( $postarr ) > 1 ) {
			$updated = wp_update_post( wp_slash( $postarr ), true );
			if ( $updated instanceof WP_Error ) {
				WP_CLI::error( sprintf( '%s: %s', $post->post_name, $updated->get_error_message() ) );
			}
		}

		foreach ( $meta as $key => $value ) {
			$current  = get_post_meta( $post->ID, $key, true );
			$expected = (string) ( $hashes[ $key ] ?? '' );

			/*
			 * Array-valued meta (results, focus, related disciplines) used to be
			 * hashed with a bare (string) cast, which emitted "Array to string
			 * conversion" on every import and — far worse — hashed EVERY array
			 * to md5('Array'). Editor changes to a list were therefore invisible
			 * to this check and silently overwritten on each rerun, which is the
			 * one thing the hash exists to prevent.
			 *
			 * A hash equal to that legacy value carries no information, so it is
			 * treated as absent: the value is re-imported once and a real
			 * fingerprint written in its place.
			 */
			$has_hash = '' !== $expected && self::LEGACY_ARRAY_HASH !== $expected;
			$edited   = $has_hash
				&& $this->fingerprint( $current ) !== $expected
				&& ! $this->is_blank( $current );

			if ( $edited && ! $this->force ) {
				continue;
			}

			update_post_meta( $post->ID, $key, is_string( $value ) ? wp_slash( $value ) : $value );
			$hashes[ $key ] = $this->fingerprint( $value );
		}

		update_post_meta( $post->ID, '_emposo_import_hash', $hashes );
	}

	/**
	 * Find a post by its import identity key.
	 *
	 * @param string $type Post type.
	 * @param string $key  Source key.
	 * @return WP_Post|null
	 */
	private function find_post( string $type, string $key ): ?WP_Post {
		$found = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'no_found_rows'  => true,

				/*
				 * Import-time identity lookup over a handful of rows. A meta
				 * lookup is the right tool because it is the durable key: it
				 * survives a lost options table, which an ID map would not.
				 */
				// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_key'       => '_emposo_source_slug',
				'meta_value'     => $key,
				// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return $found ? $found[0] : null;
	}

	/**
	 * Find an attachment by manifest key.
	 *
	 * @param string $key Manifest key.
	 * @return WP_Post|null
	 */
	private function find_attachment( string $key ): ?WP_Post {
		$found = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_key'       => '_emposo_asset_key',
				'meta_value'     => $key,
				// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return $found ? $found[0] : null;
	}

	/**
	 * Copy a file into the media library.
	 *
	 * @param string $file Absolute path.
	 * @param string $alt  Alt text, used as the title.
	 * @param string $key  Asset key, written as identity meta atomically with the insert.
	 * @return int Attachment ID, or 0 on failure.
	 */
	private function sideload( string $file, string $alt, string $key ): int {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Copy rather than move: the theme's asset directory is the source of
		// truth for the pre-built variants and must stay intact.
		$temp = wp_tempnam( basename( $file ) );
		if ( ! $temp || ! copy( $file, $temp ) ) {
			WP_CLI::warning( sprintf( 'could not stage %s', basename( $file ) ) );

			return 0;
		}

		$id = media_handle_sideload(
			array(
				'name'     => basename( $file ),
				'tmp_name' => $temp,
			),
			0,
			$alt,
			array(
				// The identity key rides the insert: written afterwards (in
				// apply_asset_meta), a request killed in between leaves an
				// attachment find_attachment() can never match, and the retry
				// sideloads a duplicate file.
				'meta_input' => array( '_emposo_asset_key' => $key ),
			)
		);

		if ( $id instanceof WP_Error ) {
			/*
			 * media_handle_sideload() consumes the temp file on success; on
			 * failure it is left behind, so clean it up. wp_tempnam() puts it
			 * under the uploads/temp path, which is the writable location VIP
			 * permits, and WP_Filesystem is not initialised under CLI — so a
			 * direct unlink is correct here rather than a filesystem abstraction.
			 */
			if ( file_exists( $temp ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- Removing a temp file this method created; WP_Filesystem is unavailable under CLI.
				unlink( $temp );
			}

			WP_CLI::warning( sprintf( '%s: %s', basename( $file ), $id->get_error_message() ) );

			return 0;
		}

		return (int) $id;
	}

	/**
	 * Set a post's featured image from a manifest key.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Manifest key.
	 */
	private function attach_featured_image( int $post_id, string $key ): void {
		if ( '' === $key ) {
			return;
		}

		$attachment = $this->find_attachment( $key );
		if ( $attachment instanceof WP_Post ) {
			set_post_thumbnail( $post_id, $attachment->ID );
		}
	}
}
