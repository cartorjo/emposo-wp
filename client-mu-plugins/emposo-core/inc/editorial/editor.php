<?php
/**
 * The editing surface: Classic Editor, without the Classic Editor plugin.
 *
 * Owner decision 2026-09-29: colleagues edit every page, post and image in
 * wp-admin, with the classic editor, and no extra plugin (the host has
 * DISALLOW_FILE_MODS, so every plugin is an SFTP upload to maintain). Core's
 * own filters do what the plugin does.
 *
 * Where the body editor would be a trap it is removed instead: case studies
 * render from their fields (case-study-box.php), and a Page's body is its
 * template until the page fields arrive (phase 2). An empty editor that
 * nothing reads is what the owner reported as "the blocks appear empty".
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Editorial;

use WP_Post;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY;
use const Emposo\Core\ContentModel\CPT_CASE_STUDY_EN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Once set, the importer refuses to overwrite live content (see cli/class-import-command.php). */
const EDITORIAL_MODE_OPTION = 'emposo_editorial_mode';

add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
add_filter( 'use_block_editor_for_post', '__return_false', 100 );
add_filter( 'use_widgets_block_editor', '__return_false', 100 );
add_action( 'init', __NAMESPACE__ . '\\trim_supports', 20 );
add_action( 'edit_form_after_title', __NAMESPACE__ . '\\page_notice' );
add_filter( 'gettext', __NAMESPACE__ . '\\excerpt_labels', 10, 3 );

/**
 * Whether editors own the live content (the importer then stays out).
 */
function editorial_mode(): bool {
	return (bool) get_option( EDITORIAL_MODE_OPTION, false );
}

/**
 * Remove the body editor where nothing renders it.
 *
 * `custom-fields` goes too: the Custom Fields box hides every `_emposo_*`
 * key anyway, and the real fields are the meta boxes.
 */
function trim_supports(): void {
	foreach ( array( CPT_CASE_STUDY, CPT_CASE_STUDY_EN ) as $type ) {
		remove_post_type_support( $type, 'editor' );
		remove_post_type_support( $type, 'custom-fields' );
	}
	remove_post_type_support( 'page', 'editor' );
	remove_post_type_support( 'page', 'custom-fields' );
}

/**
 * Tell editors where a Page's text lives today.
 *
 * @param WP_Post $post The post being edited.
 */
function page_notice( WP_Post $post ): void {
	// Pages with fields show their "Seitentexte" box instead (page-fields.php).
	if ( 'page' !== $post->post_type || page_schemas( $post ) ) {
		return;
	}

	echo '<div class="notice notice-info inline" style="margin:16px 0"><p>'
		. esc_html__( 'Die Texte dieser Seite (Rechtstexte, Sitemap) sind noch Teil der Vorlage; das Emposo-Team ändert sie auf Anfrage.', 'emposo' )
		. '</p></div>';
}

/**
 * Name the excerpt for what it is on a case study: the hero and card text.
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $domain      Text domain.
 */
function excerpt_labels( string $translation, string $text, string $domain ): string {
	if ( 'default' !== $domain || ! is_admin() ) {
		return $translation;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( null === $screen || ! in_array( $screen->post_type, array( CPT_CASE_STUDY, CPT_CASE_STUDY_EN ), true ) ) {
		return $translation;
	}

	if ( 'Excerpt' === $text ) {
		return __( 'Kurzbeschreibung (Seitenkopf und Karte)', 'emposo' );
	}
	if ( 'Excerpts are optional hand-crafted summaries of your content that can be used in your theme. <a href="%s">Learn more about manual excerpts</a>.' === $text ) {
		return __( 'Erscheint im Seitenkopf unter dem Titel und auf den Projektkarten.', 'emposo' );
	}

	return $translation;
}
