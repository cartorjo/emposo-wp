<?php
/**
 * `wp emposo migrate`: one-off moves of seeded content into editable records.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\CLI;

use WP_CLI;
use function Emposo\Core\Editorial\job_body_from_option;
use const Emposo\Core\Editorial\CPT_JOB;
use const Emposo\Core\Editorial\CPT_JOB_EN;
use const Emposo\Core\Editorial\EDITORIAL_MODE_OPTION;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Move seeded content into records editors can change.
 */
class Migrate_Command {

	/**
	 * Create one job post per seeded job, in both languages.
	 *
	 * Idempotent: a job whose slug already exists as a post is skipped, so an
	 * editor's changes are never overwritten.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Print what would be created.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function jobs( array $args, array $assoc_args ): void {
		unset( $args );
		$dry_run = isset( $assoc_args['dry-run'] );
		$created = 0;

		foreach ( array(
			'emposo_jobs'    => CPT_JOB,
			'emposo_jobs_en' => CPT_JOB_EN,
		) as $option => $type ) {
			$jobs = get_option( $option, array() );
			foreach ( is_array( $jobs ) ? array_values( $jobs ) : array() as $index => $job ) {
				$slug     = sanitize_title( (string) ( $job['slug'] ?? '' ) );
				$existing = get_posts(
					array(
						'post_type'        => $type,
						'name'             => $slug,
						'post_status'      => 'any',
						'posts_per_page'   => 1,
						'fields'           => 'ids',
						'no_found_rows'    => true,
						'suppress_filters' => false,
					)
				);
				if ( $existing ) {
					WP_CLI::log( sprintf( 'skip    %s %s (exists)', $type, $slug ) );
					continue;
				}
				WP_CLI::log( sprintf( 'create  %s %s', $type, $slug ) );
				if ( $dry_run ) {
					continue;
				}

				$id = wp_insert_post(
					wp_slash(
						array(
							'post_type'    => $type,
							'post_status'  => 'publish',
							'post_title'   => (string) ( $job['title'] ?? '' ),
							'post_name'    => $slug,
							'post_content' => job_body_from_option( (array) $job ),
							'menu_order'   => ( (int) $index + 1 ) * 10,
						)
					),
					true
				);
				if ( is_wp_error( $id ) ) {
					WP_CLI::error( sprintf( '%s: %s', $slug, $id->get_error_message() ) );
				}
				update_post_meta( (int) $id, '_emposo_job_meta', wp_slash( array_map( 'strval', (array) ( $job['meta'] ?? array() ) ) ) );
				update_post_meta( (int) $id, '_emposo_job_tagline', wp_slash( (string) ( $job['tagline'] ?? '' ) ) );
				update_post_meta( (int) $id, '_emposo_job_apply', wp_slash( (string) ( $job['apply'] ?? '' ) ) );
				++$created;
			}
		}

		WP_CLI::success( sprintf( '%d job post(s) %s.', $created, $dry_run ? 'would be created' : 'created' ) );
	}

	/**
	 * Hand the live content to the editors: the importer then refuses to run.
	 *
	 * ## OPTIONS
	 *
	 * [--off]
	 * : Switch editorial mode off again (the importer may then overwrite edits).
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function editorial( array $args, array $assoc_args ): void {
		unset( $args );
		$on = ! isset( $assoc_args['off'] );
		update_option( EDITORIAL_MODE_OPTION, $on ? 1 : 0, true );
		WP_CLI::success( $on ? 'Editorial mode on: `wp emposo import` now refuses without --override-editorial.' : 'Editorial mode off.' );
	}
}
