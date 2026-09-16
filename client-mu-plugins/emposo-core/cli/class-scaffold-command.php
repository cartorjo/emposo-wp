<?php
/**
 * `wp emposo scaffold` — create the empty route skeleton.
 *
 * Creates one object per route with the correct post type, slug and parent, and
 * no content. Separating this from content import means the routing design can
 * be proven end-to-end before any content exists, so a rewrite problem is never
 * mistaken for an import problem.
 *
 * Idempotent: objects are matched by _emposo_source_slug, so re-running updates
 * rather than duplicating.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\CLI;

use WP_CLI;
use WP_Error;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the 41-route object skeleton from the frozen route contract.
 */
class Scaffold_Command {

	/**
	 * Map of route path => created/found post ID.
	 *
	 * @var array<string, int>
	 */
	private array $created = array();

	/**
	 * Planned mutations, for --dry-run.
	 *
	 * @var array<int, array<string, string>>
	 */
	private array $plan = array();

	/**
	 * Whether to write anything.
	 *
	 * @var bool
	 */
	private bool $dry_run = false;

	/**
	 * Create every route's object.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Print the plan and write nothing.
	 *
	 * [--contract=<file>]
	 * : Route contract JSON. Defaults to the bundled copy.
	 *
	 * ## EXAMPLES
	 *
	 *     wp emposo scaffold --dry-run
	 *     wp emposo scaffold
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );

		$this->dry_run = isset( $assoc_args['dry-run'] );
		$contract_path = $assoc_args['contract'] ?? EMPOSO_CORE_DIR . '/data/routes.json';

		$routes = $this->load_contract( $contract_path );

		/*
		 * Bulk-import hygiene. Term counting and cache invalidation are deferred
		 * because they are per-object work that only needs doing once at the
		 * end; WP_IMPORTING additionally suppresses pings.
		 *
		 * The consequence to design around: while invalidation is suspended,
		 * reads inside this run return stale data. Nothing here reads back what
		 * it wrote, and the unwind happens before verification.
		 */
		if ( ! $this->dry_run ) {
			if ( ! defined( 'WP_IMPORTING' ) ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP_IMPORTING is a WordPress core constant, not one of ours.
				define( 'WP_IMPORTING', true );
			}
			wp_defer_term_counting( true );
			wp_suspend_cache_invalidation( true );
		}

		// Pages before case studies, and shallower pages before deeper ones, so
		// a parent always exists when its child is created.
		$pages = array_values(
			array_filter(
				$routes,
				static function ( array $route ): bool {
					return 'page' === $route['objectType'];
				}
			)
		);
		usort(
			$pages,
			static function ( array $a, array $b ): int {
				return substr_count( $a['url'], '/' ) <=> substr_count( $b['url'], '/' );
			}
		);

		foreach ( $pages as $route ) {
			$this->ensure_object( $route, 'page' );
		}

		foreach ( $routes as $route ) {
			if ( 'emposo_case_study' === $route['objectType'] ) {
				$this->ensure_object( $route, 'emposo_case_study' );
			}
		}

		if ( ! $this->dry_run ) {
			wp_suspend_cache_invalidation( false );
			wp_cache_flush();
			wp_defer_term_counting( false );

			$this->configure_front_page();

			// Permalinks must be postname-based for the contract's paths to
			// exist at all, and the rules must be regenerated after creating
			// objects that introduce new paths.
			global $wp_rewrite;
			$wp_rewrite->set_permalink_structure( '/%postname%/' );
			flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- One-off after scaffolding new paths.
		}

		if ( $this->dry_run ) {
			WP_CLI::log( sprintf( 'Plan (%d action(s)):', count( $this->plan ) ) );
			foreach ( $this->plan as $row ) {
				WP_CLI::line( sprintf( '  %-8s %-20s %s', $row['action'], $row['type'], $row['url'] ) );
			}
			WP_CLI::success( 'Dry run complete; nothing was written.' );

			return;
		}

		WP_CLI::success( sprintf( '%d route object(s) present.', count( $this->created ) ) );
	}

	/**
	 * Read and validate the route contract.
	 *
	 * @param string $path Contract path.
	 * @return array<int, array<string, mixed>>
	 */
	private function load_contract( string $path ): array {
		if ( ! file_exists( $path ) ) {
			WP_CLI::error( sprintf( 'Route contract not found: %s', $path ) );
		}

		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reads a local JSON file bundled with this plugin, from CLI; no remote fetch and nothing to cache.
		$decoded = json_decode( (string) file_get_contents( $path ), true );

		if ( ! is_array( $decoded ) || ! isset( $decoded['routes'] ) || ! is_array( $decoded['routes'] ) ) {
			WP_CLI::error( 'Route contract is malformed: expected a "routes" array.' );
		}

		return array_values(
			array_filter(
				$decoded['routes'],
				static function ( $route ): bool {
					return is_array( $route ) && 'not_found' !== ( $route['objectType'] ?? '' );
				}
			)
		);
	}

	/**
	 * Create or update one route object.
	 *
	 * @param array<string, mixed> $route Route record.
	 * @param string               $type  Post type.
	 */
	private function ensure_object( array $route, string $type ): void {
		$url  = (string) $route['url'];
		$slug = (string) $route['slug'];

		// The front page has no slug of its own in the contract.
		if ( '' === $slug && '/' === $url ) {
			$slug = 'startseite';
		}

		$existing = $this->find_by_source_slug( $type, $url );
		$parent   = $this->resolve_parent( (string) ( $route['parentPath'] ?? '' ) );

		$action       = $existing instanceof WP_Post ? 'update' : 'create';
		$this->plan[] = array(
			'action' => $action,
			'type'   => $type,
			'url'    => $url,
		);

		if ( $this->dry_run ) {
			// Record a placeholder so child parent-resolution still reports.
			$this->created[ $url ] = 0;

			return;
		}

		$postarr = array(
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => $this->title_for( $route ),
			'post_name'    => $slug,
			'post_parent'  => $parent,
			'post_content' => '',
			'menu_order'   => 0,
		);

		if ( $existing instanceof WP_Post ) {
			$postarr['ID'] = $existing->ID;
		}

		// wp_insert_post expects slashed data: it was built for $_POST and calls
		// wp_unslash internally, so unslashed input loses backslashes.
		$id = wp_insert_post( wp_slash( $postarr ), true );

		if ( $id instanceof WP_Error ) {
			WP_CLI::error( sprintf( '%s: %s', $url, $id->get_error_message() ) );
		}

		$id = (int) $id;

		/*
		 * wp_unique_post_slug() may append "-2" if anything else occupies the
		 * slug — a silent route change. Assert rather than trust.
		 */
		$actual = (string) get_post_field( 'post_name', $id );
		if ( $actual !== $slug ) {
			// Remove the object we just made before bailing: leaving it behind
			// would itself occupy the slug and make the next run fail too,
			// turning one fixable problem into a spreading one.
			if ( ! $existing instanceof WP_Post ) {
				wp_delete_post( $id, true );
			}

			WP_CLI::error(
				sprintf(
					'%s: slug became "%s" instead of "%s". Something else holds that slug — check `wp post list --post_status=any --name=%s`.',
					$url,
					$actual,
					$slug,
					$slug
				)
			);
		}

		update_post_meta( $id, '_emposo_source_slug', $url );
		update_post_meta( $id, '_emposo_route_locked', true );

		$this->created[ $url ] = $id;
	}

	/**
	 * Human-readable title, derived from the contract's document title.
	 *
	 * The contract stores full document titles ("Kontakt | Emposo"); the post
	 * title is the leading segment, because the suffix is the site name and
	 * would otherwise be duplicated in menus, breadcrumbs and admin lists.
	 *
	 * @param array<string, mixed> $route Route record.
	 */
	private function title_for( array $route ): string {
		$title = (string) ( $route['title'] ?? '' );
		$parts = explode( '|', $title );

		return trim( $parts[0] );
	}

	/**
	 * Find an object previously created for this route.
	 *
	 * @param string $type Post type.
	 * @param string $url  Route path, used as the source key.
	 * @return WP_Post|null
	 */
	private function find_by_source_slug( string $type, string $url ): ?WP_Post {
		$found = get_posts(
			array(
				'post_type'        => $type,
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'no_found_rows'    => true,

				/*
				 * Import-time identity lookup over a handful of rows, never a
				 * front-end query. A meta lookup is the right tool here
				 * precisely because it is the durable key: it survives a lost
				 * options table, which an ID map would not.
				 */
				// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_key'         => '_emposo_source_slug',
				'meta_value'       => $url,
				// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => false,
			)
		);

		return $found ? $found[0] : null;
	}

	/**
	 * Resolve a contract parentPath to a post ID.
	 *
	 * @param string $parent_path Path without leading or trailing slashes.
	 */
	private function resolve_parent( string $parent_path ): int {
		if ( '' === $parent_path ) {
			return 0;
		}

		$url = '/' . trim( $parent_path, '/' ) . '/';

		if ( isset( $this->created[ $url ] ) && $this->created[ $url ] > 0 ) {
			return $this->created[ $url ];
		}

		$page = get_page_by_path( $parent_path );

		return $page instanceof WP_Post ? $page->ID : 0;
	}

	/**
	 * Point the site at the scaffolded front page.
	 */
	private function configure_front_page(): void {
		$front = $this->created['/'] ?? 0;

		if ( $front <= 0 ) {
			WP_CLI::warning( 'No object was created for "/", so the front page was not set.' );

			return;
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front );
		update_option( 'page_for_posts', 0 );

		// The preview gate: core derives noindex, a Disallow: / robots.txt and a
		// disabled wp-sitemap.xml from this single option, which is exactly the
		// state the static build ships in.
		update_option( 'blog_public', 0 );
	}
}
