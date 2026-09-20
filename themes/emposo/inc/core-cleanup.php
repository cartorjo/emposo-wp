<?php
/**
 * Stop WordPress emitting markup, CSS and requests the static build lacks.
 *
 * Not cosmetic. Three of these are correctness issues rather than tidiness:
 *
 * 1. Core's global-styles output is INLINE and UNLAYERED. Unlayered CSS beats
 *    every layered rule regardless of source order, so it outranks the theme's
 *    whole `@layer theme, base, components, utilities` cascade. It is emitted
 *    even without a theme.json, because core merges its own.
 * 2. wp_global_styles_render_svg_filters() injects an <svg> as the first child
 *    of <body>, ahead of the skip link — which breaks "the skip link is the
 *    first focusable element" and costs the accessibility score.
 * 3. The admin bar is deliberately NOT removed. It adds two requests (~70 KB),
 *    an inline html{margin-top:32px!important} that causes layout shift, and a
 *    sticky-header offset shift — but only ever for logged-in users, who are
 *    editors rather than visitors. Removing it would degrade the editor
 *    experience to fix a measurement problem that does not exist, because the
 *    parity and audit harnesses both run logged out. That is also why they
 *    must keep doing so: measured logged in, they measure a different page.
 *
 * The rest are head noise, extra requests, or third-party origins — each of
 * which the parity harness asserts is absent, so this list is a test rather
 * than an intention.
 *
 * @package Emposo
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme supports, chosen for what they do NOT add.
 */
function emposo_theme_supports(): void {
	/*
	 * html5 with 'style' and 'script' makes core print <link> and <script>
	 * without the legacy type attributes, which is what the static build emits.
	 */
	add_theme_support(
		'html5',
		array( 'style', 'script', 'navigation-widgets', 'search-form', 'caption', 'gallery' )
	);

	// No core block-layout :where() CSS.
	add_theme_support( 'disable-layout-styles' );

	remove_theme_support( 'core-block-patterns' );
	add_filter( 'should_load_remote_block_patterns', '__return_false' );

	/*
	 * Deliberately NOT added:
	 *
	 * - 'title-tag': prints <title> at wp_head priority 1, which in this head
	 *   layout is BELOW the stylesheets, moving it out of position. parts/head.php
	 *   prints it literally, in place, from the route contract.
	 * - 'wp-block-styles': explicitly enqueues wp-block-library-theme.
	 * - 'editor-styles': can pull the editor stylesheet onto the front end.
	 * - 'responsive-embeds' / 'align-wide': emit markup and CSS.
	 * - 'automatic-feed-links': adds feed <link> tags the static build lacks.
	 */

	register_nav_menus(
		array(
			'emposo_primary'         => __( 'Hauptnavigation', 'emposo' ),
			'emposo_footer_business' => __( 'Footer: Für Unternehmen', 'emposo' ),
			'emposo_footer_company'  => __( 'Footer: Unternehmen', 'emposo' ),
		)
	);
}
add_action( 'after_setup_theme', 'emposo_theme_supports' );

/**
 * Remove core front-end output.
 */
function emposo_core_cleanup(): void {
	// --- head link and meta noise ---------------------------------------
	remove_action( 'wp_head', 'wp_generator' );
	add_filter( 'the_generator', '__return_empty_string' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	// The shortlink is emitted TWICE by core: once as a <link> in the head and
	// once as an HTTP Link: header. Removing only the first leaves the header
	// advertising /?p=<id> on every response.
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

	// The static build emits no canonical. Re-add with the launch SEO package.
	remove_action( 'wp_head', 'rel_canonical' );

	/*
	 * parts/head.php prints the literal `noindex, nofollow` meta. Core's
	 * wp_robots would print a SECOND one, worded differently
	 * ('noindex, nofollow, max-image-preview:large'), so the static build's
	 * exact string is preserved by removing core's.
	 */
	remove_action( 'wp_head', 'wp_robots', 1 );

	// The inline data-URI favicon in parts/head.php is the only icon link.
	remove_action( 'wp_head', 'wp_site_icon', 99 );

	// Kills the //s.w.org dns-prefetch — a third-party origin.
	remove_action( 'wp_head', 'wp_resource_hints', 2 );

	// Preloads are literal in parts/head.php so they precede the CSS.
	remove_action( 'wp_head', 'wp_preload_resources', 1 );

	// --- emoji: an inline script, a request, and a third-party origin -----
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );

	// --- global styles: unlayered inline CSS + an <svg> before the skip link
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles_custom_css' );
	remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters', 10 );

	// Customizer "Additional CSS" is unlayered and would silently outrank the
	// theme's component layer. All CSS goes through the Tailwind entry.
	remove_action( 'wp_head', 'wp_custom_css_cb', 101 );

	// --- speculative loading (WP 6.8+): an inline script in the footer ----
	add_filter( 'wp_speculation_rules_configuration', '__return_null' );

	// --- core image handling must not touch our markup -------------------
	// emposo_picture() sets loading and fetchpriority explicitly.
	add_filter( 'wp_lazy_loading_enabled', '__return_false' );
	remove_filter( 'the_content', 'wp_filter_content_tags' );
	remove_filter( 'the_excerpt', 'wp_filter_content_tags' );

	// --- zero cookies, zero third-party avatars --------------------------
	add_filter( 'comments_open', '__return_false', 20 );
	add_filter( 'pings_open', '__return_false', 20 );
	add_filter( 'option_show_avatars', '__return_false' );
	// XML-RPC is disabled authoritatively in the mu-plugin (inc/security.php),
	// which survives a theme swap; a duplicate filter here added nothing.
}
add_action( 'after_setup_theme', 'emposo_core_cleanup' );

/**
 * Dequeue core stylesheets and scripts.
 *
 * Late priority so anything registered by core or a plugin is already queued.
 */
function emposo_dequeue_core_assets(): void {
	$styles = array(
		'wp-block-library',
		'wp-block-library-theme',
		'classic-theme-styles',
		'global-styles',
		'wp-emoji-styles',
		'core-block-supports',
		'wp-img-auto-sizes-contain',

		/*
		 * NOT 'admin-bar' and NOT 'dashicons'. Both were in this list, which
		 * contradicted point 3 of this file's own header: the admin bar is kept
		 * deliberately, and deregistering its stylesheet and icon font left
		 * editors with an unstyled bar — the editor experience the decision was
		 * made to protect. Core only enqueues them when the bar renders, so a
		 * logged-out visitor never pays for them, and the parity and audit
		 * harnesses run logged out.
		 */
	);

	foreach ( $styles as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}

	wp_dequeue_script( 'wp-embed' );
	wp_deregister_script( 'wp-embed' );
}
add_action( 'wp_enqueue_scripts', 'emposo_dequeue_core_assets', 100 );

/**
 * Strip inline core styles that are printed rather than enqueued.
 *
 * Some core output (block supports, auto-sizes) arrives as inline <style> via
 * wp_add_inline_style on a handle we have already deregistered, or via its own
 * print action. Removing the print actions is more reliable than chasing
 * handles, and each of these is unlayered CSS.
 */
function emposo_remove_inline_core_styles(): void {
	remove_action( 'wp_footer', 'wp_enqueue_stored_styles', 1 );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_stored_styles', 1 );
}
add_action( 'init', 'emposo_remove_inline_core_styles' );
