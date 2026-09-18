<?php
/**
 * `wp emposo verify` — the route and content contract.
 *
 * This is the real URL-preservation guarantee. Thirty-one of the 41 routes are
 * Pages, so a single edited slug or changed parent silently changes a live URL;
 * and the routing design rests on WordPress behaviours (permastruct ordering,
 * has_archive emitting an extra rule) whose breakage is remote from the change
 * that caused it. Neither is visible to a check that only inspects the
 * database, so this runs a real request parse.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\CLI;

use WP_CLI;
use WP_CLI\Utils;
use function Emposo\Core\Rewrites\resolve;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifies routes, content invariants and media against the frozen contract.
 */
class Verify_Command {

	/**
	 * Route expectations, loaded from the exported contract.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $routes = array();

	/**
	 * Verify that every expected route resolves to the expected object.
	 *
	 * ## OPTIONS
	 *
	 * [--routes]
	 * : Check that every route in the contract resolves, and that permalinks round-trip.
	 *
	 * [--content]
	 * : Check content invariants — single-select taxonomies, ancestor closure, claim strings.
	 *
	 * [--media]
	 * : Check that every manifest image has an attachment with alt text.
	 *
	 * [--invariants]
	 * : Check registration facts whose breakage is remote from the change that causes it.
	 *
	 * [--contract=<file>]
	 * : Path to the exported route contract JSON. Defaults to the bundled copy.
	 *
	 * [--porcelain]
	 * : Machine-readable TSV output for CI.
	 *
	 * ## EXAMPLES
	 *
	 *     wp emposo verify --routes
	 *     wp emposo verify --routes --content --media
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );

		$all = ! isset( $assoc_args['routes'] )
			&& ! isset( $assoc_args['content'] )
			&& ! isset( $assoc_args['media'] )
			&& ! isset( $assoc_args['invariants'] );

		$contract  = $assoc_args['contract'] ?? EMPOSO_CORE_DIR . '/data/routes.json';
		$porcelain = isset( $assoc_args['porcelain'] );

		if ( ! file_exists( $contract ) ) {
			WP_CLI::error(
				sprintf(
					'Route contract not found: %s. Generate it with tools/export-content.mjs.',
					$contract
				)
			);
		}

		$decoded = json_decode( (string) file_get_contents( $contract ), true ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file bundled with the plugin, read from CLI.
		if ( ! is_array( $decoded ) || ! isset( $decoded['routes'] ) ) {
			WP_CLI::error( 'Route contract is malformed: expected a "routes" array.' );
		}
		$this->routes = $decoded['routes'];

		$failures = array();

		if ( $all || isset( $assoc_args['routes'] ) ) {
			$failures = array_merge( $failures, $this->verify_routes( $porcelain ) );
		}
		if ( $all || isset( $assoc_args['content'] ) ) {
			$failures = array_merge( $failures, $this->verify_content() );
		}
		if ( $all || isset( $assoc_args['media'] ) ) {
			$failures = array_merge( $failures, $this->verify_media() );
		}
		if ( $all || isset( $assoc_args['invariants'] ) ) {
			$failures = array_merge( $failures, $this->verify_invariants() );
		}

		if ( $failures ) {
			foreach ( $failures as $failure ) {
				WP_CLI::log( WP_CLI::colorize( '%r✗%n ' ) . $failure );
			}
			WP_CLI::error( sprintf( '%d check(s) failed.', count( $failures ) ) );
		}

		WP_CLI::success( 'All checks passed.' );
	}

	/**
	 * Resolve every contract route and assert the object and permalink match.
	 *
	 * @param bool $porcelain Emit TSV instead of a table.
	 * @return array<int, string> Failures.
	 */
	private function verify_routes( bool $porcelain ): array {
		$failures = array();
		$rows     = array();

		foreach ( $this->routes as $route ) {
			$path     = (string) $route['url'];
			$expected = (string) ( $route['objectType'] ?? 'page' );
			$slug     = (string) ( $route['slug'] ?? '' );

			// The 404 template is not an addressable route.
			if ( 'not_found' === $expected ) {
				continue;
			}

			$resolved = resolve( $path );
			$status   = 'ok';

			if ( ! $resolved['found'] ) {
				$failures[] = sprintf( '%s does not resolve to any object', $path );
				$status     = 'MISSING';
			} elseif ( $resolved['type'] !== $expected ) {
				$failures[] = sprintf(
					'%s resolves to a %s, expected %s — a rewrite rule is shadowing it',
					$path,
					$resolved['type'],
					$expected
				);
				$status     = 'WRONG TYPE';
			} else {
				$actual_slug = get_post_field( 'post_name', $resolved['id'] );
				if ( '' !== $slug && $actual_slug !== $slug ) {
					$failures[] = sprintf( '%s has slug "%s", contract says "%s"', $path, $actual_slug, $slug );
					$status     = 'SLUG DRIFT';
				}

				// The permalink must round-trip, or the canonical URL differs
				// from the one we assert and visitors get redirected.
				$permalink = (string) $resolved['permalink'];
				$as_path   = (string) wp_parse_url( $permalink, PHP_URL_PATH );
				if ( $as_path !== $path ) {
					$failures[] = sprintf( '%s permalink round-trips to "%s"', $path, $as_path );
					$status     = 'PERMALINK DRIFT';
				}
			}

			$rows[] = array(
				'route'  => $path,
				'type'   => '' !== $resolved['type'] ? $resolved['type'] : '-',
				'id'     => (string) $resolved['id'],
				'status' => $status,
			);
		}

		if ( $porcelain ) {
			foreach ( $rows as $row ) {
				WP_CLI::line( implode( "\t", $row ) );
			}
		} else {
			WP_CLI::log( sprintf( 'Routes: %d checked', count( $rows ) ) );
			$bad = array_values(
				array_filter(
					$rows,
					static function ( array $row ): bool {
						return 'ok' !== $row['status'];
					}
				)
			);
			if ( $bad ) {
				Utils\format_items( 'table', $bad, array( 'route', 'type', 'id', 'status' ) );
			}
		}

		return $failures;
	}

	/**
	 * Content invariants that render identically when broken.
	 *
	 * @return array<int, string> Failures.
	 */
	private function verify_content(): array {
		$failures = array();

		$case_studies = get_posts(
			array(
				'post_type'        => \Emposo\Core\ContentModel\CPT_CASE_STUDY,
				'post_status'      => 'publish',
				// The corpus is 10; the headroom is for editorial growth, and
				// VIP's 100-post ceiling is the limit worth respecting here.
				'posts_per_page'   => 100,
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		foreach ( $case_studies as $post ) {
			// Exactly one discipline and one outcome, and the discipline must be
			// a CHILD term: assigning the parent group directly would break both
			// the card label and include_children queries.
			foreach ( array(
				\Emposo\Core\ContentModel\TAX_DISCIPLINE => 'discipline',
				\Emposo\Core\ContentModel\TAX_OUTCOME    => 'outcome',
			) as $taxonomy => $label ) {
				$terms = wp_get_object_terms( $post->ID, $taxonomy );
				if ( is_wp_error( $terms ) ) {
					$failures[] = sprintf( '%s: cannot read %s terms', $post->post_name, $label );
					continue;
				}
				if ( 1 !== count( $terms ) ) {
					$failures[] = sprintf(
						'%s: %d %s term(s), expected exactly 1',
						$post->post_name,
						count( $terms ),
						$label
					);

					continue;
				}

				/*
				 * And the discipline must actually BE a child. The comment above
				 * has always said so while the check only counted, so a case
				 * study tagged with the parent group — which breaks the card
				 * label and every include_children query — passed verification.
				 * Outcomes are a flat vocabulary and have no parent to require.
				 */
				if ( \Emposo\Core\ContentModel\TAX_DISCIPLINE === $taxonomy && 0 === (int) $terms[0]->parent ) {
					$failures[] = sprintf(
						'%s: discipline "%s" is a top-level group, not a child discipline',
						$post->post_name,
						$terms[0]->slug
					);
				}
			}

			// Ancestor closure: a case study tagged `automotive` must also carry
			// `industrial`, or the client-side `industrial` filter button and a
			// server-side include_children query disagree.
			$industries = wp_get_object_terms( $post->ID, \Emposo\Core\ContentModel\TAX_INDUSTRY );
			if ( ! is_wp_error( $industries ) ) {
				$assigned = wp_list_pluck( $industries, 'term_id' );
				foreach ( $industries as $term ) {
					foreach ( get_ancestors( $term->term_id, \Emposo\Core\ContentModel\TAX_INDUSTRY ) as $ancestor ) {
						if ( ! in_array( $ancestor, $assigned, true ) ) {
							// get_term_field() returns string|WP_Error; casting a
							// WP_Error would surface as "Array to string
							// conversion" inside the failure message itself.
							$ancestor_slug = get_term_field( 'slug', $ancestor, \Emposo\Core\ContentModel\TAX_INDUSTRY );
							$failures[]    = sprintf(
								'%s: has industry "%s" without its ancestor "%s" (ancestor closure broken)',
								$post->post_name,
								$term->slug,
								is_string( $ancestor_slug ) ? $ancestor_slug : sprintf( 'term %d', $ancestor )
							);
						}
					}
				}
			}

			// The headline drives cards, the hero, the meta description and OG.
			// An empty excerpt would be auto-generated from the challenge text.
			if ( '' === trim( (string) $post->post_excerpt ) ) {
				$failures[] = sprintf( '%s: empty excerpt — the headline would be auto-generated', $post->post_name );
			}
		}

		return $failures;
	}

	/**
	 * Registration invariants.
	 *
	 * Each of these is a real mistake already made or narrowly avoided during
	 * the port, where the symptom appears far from the cause. They are asserted
	 * here rather than left as comments because a comment does not fail a build.
	 *
	 * @return array<int, string> Failures.
	 */
	private function verify_invariants(): array {
		$failures = array();

		/*
		 * has_archive must stay false. Setting it true looks like an
		 * improvement and silently 404s the hand-authored /case-studies/ hub
		 * Page, because WordPress then emits a `case-studies/?$` rule ABOVE
		 * the page rules.
		 */
		$case_study = get_post_type_object( \Emposo\Core\ContentModel\CPT_CASE_STUDY );
		if ( ! $case_study ) {
			$failures[] = 'emposo_case_study is not registered';
		} elseif ( false !== $case_study->has_archive ) {
			$failures[] = 'emposo_case_study has_archive must be false, or the /case-studies/ hub Page 404s';
		}

		/*
		 * The classification taxonomies must contribute no URLs. A rewrite on
		 * any of them lands above the page rules and shadows the Page tree that
		 * owns /expertise/ and /branchen/; worse, a taxonomy sharing a rewrite
		 * slug with the CPT would silently overwrite its rule, since
		 * $wp_rewrite->rules is keyed by regex.
		 */
		foreach ( array(
			\Emposo\Core\ContentModel\TAX_DISCIPLINE,
			\Emposo\Core\ContentModel\TAX_INDUSTRY,
			\Emposo\Core\ContentModel\TAX_OUTCOME,
		) as $taxonomy ) {
			$object = get_taxonomy( $taxonomy );
			if ( ! $object ) {
				$failures[] = sprintf( '%s is not registered', $taxonomy );
				continue;
			}
			if ( false !== $object->rewrite ) {
				$failures[] = sprintf( '%s must have rewrite => false; it would shadow the Page tree', $taxonomy );
			}
			if ( $object->publicly_queryable ) {
				$failures[] = sprintf( '%s must not be publicly queryable', $taxonomy );
			}
		}

		/*
		 * The import identity key must survive sanitisation. It previously did
		 * not: sanitize_title() was passed straight in as the callback, and the
		 * meta API's ($value, $meta_key, $meta_type) invocation put the meta key
		 * into sanitize_title()'s $fallback_title slot — so the front page's
		 * key '/' was stored as the literal '_emposo_source_slug', every rerun
		 * tried to create a second front page, and the slug assertion fired.
		 */
		$probe = \Emposo\Core\ContentModel\sanitize_route_path( '/' );
		if ( '/' !== $probe ) {
			$failures[] = sprintf( 'route path "/" sanitises to "%s"; the importer identity key is broken', $probe );
		}
		$nested = \Emposo\Core\ContentModel\sanitize_route_path( '/expertise/engineering/' );
		if ( '/expertise/engineering/' !== $nested ) {
			$failures[] = sprintf( 'nested route path sanitises to "%s"; path structure is being collapsed', $nested );
		}

		// The preview gate. blog_public => 0 is what makes core emit noindex,
		// a Disallow: / robots.txt and no sitemap — the static build's state.
		if ( '0' !== (string) get_option( 'blog_public' ) ) {
			$failures[] = 'blog_public is not 0; the site would be indexable before launch is approved';
		}

		return $failures;
	}

	/**
	 * Every manifest image must exist as an attachment, with alt text.
	 *
	 * Alt text matters disproportionately here: the manifest is the sole source
	 * of alt text for every photograph on the site, so losing it in the import
	 * is an accessibility regression with no visual symptom.
	 *
	 * @return array<int, string> Failures.
	 */
	private function verify_media(): array {
		$failures = array();

		/*
		 * Driven from the export, not from what happens to be in the database.
		 * The first version of this check listed attachments carrying
		 * _emposo_asset_key and asserted each had alt text — so 21 of the 22
		 * images could be absent entirely and the survivor would pass it. The
		 * contract names which images must exist; only the contract can say one
		 * is missing.
		 */
		$export = EMPOSO_CORE_DIR . '/data/site-export.json';

		if ( ! is_readable( $export ) ) {
			$failures[] = sprintf( 'content export not readable: %s', $export );

			return $failures;
		}

		$decoded = json_decode( (string) file_get_contents( $export ), true ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file bundled with the plugin, read from CLI.

		if ( ! is_array( $decoded ) || ! isset( $decoded['assets'] ) || ! is_array( $decoded['assets'] ) ) {
			$failures[] = 'content export is malformed: expected an "assets" array';

			return $failures;
		}

		$theme = get_template_directory();

		foreach ( $decoded['assets'] as $asset ) {
			if ( ! is_array( $asset ) || ! isset( $asset['key'] ) ) {
				continue;
			}

			$key          = (string) $asset['key'];
			$attachment   = $this->attachment_for( $key );
			$expected_alt = trim( (string) ( $asset['alt'] ?? '' ) );

			if ( null === $attachment ) {
				$failures[] = sprintf( 'asset "%s" has no attachment — media has not been imported', $key );
				continue;
			}

			$alt = trim( (string) get_post_meta( $attachment, '_wp_attachment_image_alt', true ) );

			if ( '' === $alt ) {
				$failures[] = sprintf( 'attachment "%s" has no alt text', $key );
			} elseif ( '' !== $expected_alt && $alt !== $expected_alt ) {
				/*
				 * The export is the SOLE source of alt text for every photograph
				 * here, so a divergence is either an editor improving it — which
				 * is allowed and should be exported back — or an import that
				 * half-applied. Either way it is worth seeing.
				 */
				$failures[] = sprintf(
					'attachment "%s" alt text differs from the export: "%s" vs "%s"',
					$key,
					$alt,
					$expected_alt
				);
			}

			// The importer resolves the export's site-absolute src inside the
			// theme; the same resolution has to hold here or the attachment
			// points at a file the site cannot serve.
			$relative = ltrim( str_replace( '/assets/', 'assets/', (string) ( $asset['src'] ?? '' ) ), '/' );

			if ( '' !== $relative && ! file_exists( $theme . '/' . $relative ) ) {
				$failures[] = sprintf( 'asset "%s" file missing in the theme: %s', $key, $relative );
			}
		}

		return $failures;
	}

	/**
	 * The attachment carrying a given asset key, if any.
	 *
	 * @param string $key Asset key from the export.
	 * @return int|null Attachment ID.
	 */
	private function attachment_for( string $key ): ?int {
		$found = get_posts(
			array(
				'post_type'        => 'attachment',
				'post_status'      => 'inherit',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'meta_key'         => '_emposo_asset_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- CLI verification over ~22 rows.
				'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- As above.
				'suppress_filters' => false,
			)
		);

		return $found ? (int) $found[0] : null;
	}
}
