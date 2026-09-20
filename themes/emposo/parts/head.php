<?php
/**
 * Ported from the static build's partials/head.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo \Emposo\Core\escape_static( emposo_document_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escape_static() is the reference build's 4-char escaper; esc_html would emit &#039; and diverge from parity. ?></title>
	<meta name="description" content="<?php echo \Emposo\Core\escape_static( emposo_meta_description() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reference-parity escaper, see above. ?>">
<?php
/*
 * blog_public=0 is the primary, admin-visible noindex lever. EMPOSO_FORCE_NOINDEX
 * (a wp-config.php constant) is the backstop for a staging clone that must never
 * be indexable even if blog_public is flipped. Read via constant() so static
 * analysis does not fold the build-time value and call the guard always-true.
 */
if ( '0' === (string) get_option( 'blog_public' ) || ( defined( 'EMPOSO_FORCE_NOINDEX' ) && constant( 'EMPOSO_FORCE_NOINDEX' ) ) ) :
	?>
	<!-- Temporary preview build: remove this only when launch SEO settings are approved. -->
	<meta name="robots" content="noindex, nofollow">
<?php endif; ?>
	<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%230A0532'/%3E%3Cg transform='translate(2.79 6.87) scale(0.4566)'%3E%3Crect x='20.25' y='3.97' width='14.69' height='5.78' fill='%23F7911E'/%3E%3Crect x='20.25' y='30.23' width='14.69' height='5.78' fill='%23F7911E'/%3E%3Cpolygon points='34.94 30.23 47.4 19.42 34.94 9.75 34.94 3.97 36.03 3.97 55.22 19.42 36.03 36.01 34.94 36.01 34.94 30.23' fill='white'/%3E%3Cpath d='M20.24,16.68V3.97h-4.51c-.75,0-1.35.6-1.35,1.35v11.36s-11.72,0-11.72,0v5.77h11.72v12.21c0,.75.6,1.35,1.35,1.35h4.51v-13.56h11.8v-5.77s-11.8,0-11.8,0Z' fill='white'/%3E%3C/g%3E%3C/svg%3E">
	<!-- Everything is same-origin: Roboto is self-hosted (css/00-fonts.css) and all
		libraries are vendored, so no visitor request ever leaves this host (GDPR).
		crossorigin on the preload is REQUIRED even same-origin — fonts always fetch
		in CORS mode, and a preload without it is re-downloaded. -->
	<link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( EMPOSO_URI . '/assets/fonts/roboto-300.woff2' ); ?>" crossorigin>
	<link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( EMPOSO_URI . '/assets/fonts/roboto-700.woff2' ); ?>" crossorigin>
<?php
/*
 * The two stylesheets and the vendored Lenis script are registered in
 * inc/assets.php rather than written here, which is both the handbook's way and
 * what preserves the static build's order: wp_head() sits exactly where the
 * {{SCRIPTS}} token sat, and inside it wp_print_styles runs at priority 8 and
 * wp_print_head_scripts at 9 — so styles precede scripts, matching the static
 * head's 00-fonts.css, site.css, lenis, per-page scripts sequence.
 *
 * Lenis is vendored, and Roboto is self-hosted above: no visitor request ever
 * leaves this host (GDPR), which is also what makes a default-src 'self' CSP
 * achievable.
 */
wp_head();
?>
</head>
<body class="<?php echo \Emposo\Core\escape_static( emposo_body_class() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reference-parity escaper; the static build emits bodyClass through the same escape(). ?>">
