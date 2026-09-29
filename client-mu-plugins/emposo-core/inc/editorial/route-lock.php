<?php
/**
 * The route lock, enforced.
 *
 * The scaffold command marks every contract route object `_emposo_route_locked` (the
 * Pages and both case-study types). Until 2026-09-29 nothing read the flag,
 * so an edited slug silently moved a live URL: routes.json, the navigation,
 * the language switch and the redirects all name the old path, and the page
 * fell back to an empty body with HTTP 200. Slugs are locked by owner
 * decision (2026-09-29), so a locked object keeps its slug, parent and
 * published status, and cannot be trashed or deleted from wp-admin.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Editorial;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wp_insert_post_data', __NAMESPACE__ . '\\keep_route', 10, 2 );
add_filter( 'pre_trash_post', __NAMESPACE__ . '\\refuse_removal', 10, 2 );
add_filter( 'pre_delete_post', __NAMESPACE__ . '\\refuse_removal', 10, 2 );
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\hide_route_boxes', 100, 2 );
add_action( 'edit_form_after_title', __NAMESPACE__ . '\\route_notice', 5 );
add_filter( 'page_row_actions', __NAMESPACE__ . '\\row_actions', 10, 2 );
add_filter( 'post_row_actions', __NAMESPACE__ . '\\row_actions', 10, 2 );

/**
 * Whether a post is a locked contract route.
 *
 * @param int $post_id Post ID.
 */
function is_locked( int $post_id ): bool {
	return $post_id > 0 && (bool) get_post_meta( $post_id, '_emposo_route_locked', true );
}

/**
 * Restore slug, parent and status of a locked route on every save.
 *
 * WP-CLI is exempt: scaffold is what sets and repairs these fields.
 *
 * @param array<string, mixed> $data    Slashed post data about to be saved.
 * @param array<string, mixed> $postarr Raw input, including the ID.
 * @return array<string, mixed>
 */
function keep_route( array $data, array $postarr ): array {
	$id = (int) ( $postarr['ID'] ?? 0 );
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || ! is_locked( $id ) ) {
		return $data;
	}

	$current = get_post( $id );
	if ( ! $current instanceof WP_Post ) {
		return $data;
	}

	$data['post_name']   = $current->post_name;
	$data['post_parent'] = $current->post_parent;
	// Autosaves and revisions carry their own status; only the live post is pinned.
	if ( 'publish' === $current->post_status && ! in_array( $data['post_status'] ?? '', array( 'inherit', 'auto-draft' ), true ) ) {
		$data['post_status'] = 'publish';
	}

	return $data;
}

/**
 * Refuse to trash or delete a locked route outside WP-CLI.
 *
 * @param mixed   $check Short-circuit value from earlier filters.
 * @param WP_Post $post  The post.
 * @return mixed False (refuse) for a locked route, otherwise $check.
 */
function refuse_removal( $check, $post ) {
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || ! $post instanceof WP_Post || ! is_locked( (int) $post->ID ) ) {
		return $check;
	}

	return false;
}

/**
 * Hide the slug box and the page-parent selector on locked routes.
 *
 * @param string       $post_type Post type.
 * @param WP_Post|null $post      The post.
 */
function hide_route_boxes( string $post_type, $post ): void {
	if ( ! $post instanceof WP_Post || ! is_locked( (int) $post->ID ) ) {
		return;
	}

	remove_meta_box( 'slugdiv', $post_type, 'normal' );
	if ( 'page' === $post_type ) {
		remove_meta_box( 'pageparentdiv', 'page', 'side' );
	}
}

/**
 * Say why the URL cannot change.
 *
 * @param WP_Post $post The post being edited.
 */
function route_notice( WP_Post $post ): void {
	if ( ! is_locked( (int) $post->ID ) ) {
		return;
	}

	// Trashing is refused (refuse_removal()); hide the link that would only error.
	echo '<style>#delete-action,#edit-slug-buttons{display:none}</style>';
	echo '<p class="description" style="margin:8px 0 0">'
		. esc_html__( 'Die Adresse (URL) dieser Seite ist fest: Navigation, Sprachumschaltung und Weiterleitungen hängen daran. Titel und Inhalte sind frei änderbar.', 'emposo' )
		. '</p>';
}

/**
 * Drop "Trash" and "Quick Edit" from locked rows (Quick Edit edits the slug).
 *
 * @param array<string, string> $actions Row actions.
 * @param WP_Post               $post    The post.
 * @return array<string, string>
 */
function row_actions( array $actions, WP_Post $post ): array {
	if ( is_locked( (int) $post->ID ) ) {
		unset( $actions['trash'], $actions['delete'], $actions['inline hide-if-no-js'] );
	}

	return $actions;
}
