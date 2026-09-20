<?php
/**
 * Security headers and surface reduction.
 *
 * The zero-third-party-request rule the static build enforces for GDPR reasons
 * pays off here: because no visitor request ever leaves this host, a
 * default-src 'self' Content-Security-Policy is actually achievable, rather
 * than the long allowlist a site with embedded analytics or CDN assets needs.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'send_headers', __NAMESPACE__ . '\\send_security_headers' );
add_action( 'init', __NAMESPACE__ . '\\reduce_surface' );

/*
 * The users sitemap would undo the author-enumeration hardening the moment the
 * site launches: with blog_public=1, core's wp-sitemap-users-1.xml lists
 * author URLs — and with archives removed those expose the login name for
 * nothing. Registered HERE, at load, not inside reduce_surface(): core's
 * wp_sitemaps_get_server callback sits in the same init/10 slot but was
 * registered before mu-plugins load, so it runs first and a filter added
 * inside reduce_surface() arrives after the providers already registered.
 */
add_filter(
	'wp_sitemaps_add_provider',
	/**
	 * Drop the users provider; keep every other one.
	 *
	 * @param \WP_Sitemaps_Provider|false $provider The provider, or false to drop it.
	 * @param string                      $name     Provider name.
	 * @return \WP_Sitemaps_Provider|false
	 */
	static function ( $provider, string $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);
add_filter( 'wp_headers', __NAMESPACE__ . '\\avif_content_type' );

/**
 * Security headers.
 *
 * Sent from PHP so they are present in local development and on any host,
 * rather than living only in a web-server config that differs per environment.
 * A production edge may well set them too; duplicate identical headers are
 * harmless, and having them here means a misconfigured host degrades to
 * "protected" rather than "unprotected".
 */
function send_security_headers(): void {
	if ( is_admin() ) {
		return;
	}

	/*
	 * 'unsafe-inline' for style-src is required by WordPress itself, which
	 * prints inline <style> for several features even with the front end
	 * trimmed down. It is NOT granted for script-src: every script this site
	 * loads is a file from this host, so scripts stay strictly same-origin.
	 *
	 * data: is allowed for img-src because the favicon is an inline SVG data
	 * URI — the one deliberate exception, and it is a same-document resource.
	 */
	$csp = array(
		"default-src 'self'",
		"script-src 'self'",
		"style-src 'self' 'unsafe-inline'",
		"img-src 'self' data:",
		"font-src 'self'",
		"connect-src 'self'",
		"form-action 'self' mailto:",
		"frame-ancestors 'none'",
		"base-uri 'self'",
		"object-src 'none'",
	);

	header( 'Content-Security-Policy: ' . implode( '; ', $csp ) );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()' );
	header( 'X-Frame-Options: DENY' );

	// HSTS only over TLS: sent over plain HTTP it is ignored by browsers and
	// meaningless, and in local development there is no TLS at all.
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}
}

/**
 * Serve AVIF with the right content type.
 *
 * This is the entire purpose of the static build's serve.json, and it is not
 * cosmetic: a wrong Content-Type makes browsers reject
 * <source type="image/avif">, fall back to the JPEG, and add roughly 150 KB to
 * a page — which alone breaks the 200 KB largest-image budget.
 *
 * PHP only sees the request if the web server routes it here, and a static file
 * usually bypasses PHP entirely — so this is a safety net, and the host must
 * also be configured. Recorded in the README as a deployment requirement.
 *
 * @param array<string, string> $headers Headers.
 * @return array<string, string>
 */
function avif_content_type( array $headers ): array {
	$path = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_parse_url( sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH )
		: '';

	if ( '' !== $path && str_ends_with( strtolower( $path ), '.avif' ) ) {
		$headers['Content-Type'] = 'image/avif';
	}

	return $headers;
}

/**
 * Reduce the attack surface to what this site actually uses.
 *
 * Every item here is something the static build has no equivalent of, so
 * removing it costs nothing and is also a parity requirement — the harness
 * asserts several of these are absent from the rendered output.
 */
function reduce_surface(): void {
	// XML-RPC. Also drops the X-Pingback header.
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter(
		'wp_headers',
		static function ( array $headers ): array {
			unset( $headers['X-Pingback'] );

			return $headers;
		}
	);

	/*
	 * Author enumeration. /?author=1 redirects to an author archive, which
	 * publishes the login name; there are no author archives on this site.
	 *
	 * Priority 0, not the default 10: core's redirect_canonical also runs on
	 * template_redirect at 10, and with equal priorities registration order
	 * decides — core registers first, so it wins and the redirect to
	 * /author/<login>/ happens anyway. Running first prevents that.
	 */
	add_action(
		'template_redirect',
		static function (): void {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only guard on a public URL shape, not a state change.
			if ( is_author() || isset( $_GET['author'] ) ) {
				wp_safe_redirect( home_url( '/' ), 301 );
				exit;
			}
		},
		0
	);

	// And remove the author archive rules entirely, so the path shape does not
	// exist rather than merely redirecting.
	add_filter( 'author_rewrite_rules', '__return_empty_array' );

	/*
	 * Unknown sitemap providers are a soft 200 in core: the rewrite rules are
	 * generic, and render_sitemaps() plain-returns when the provider lookup
	 * fails, leaving the main query to render the FRONT PAGE under
	 * /wp-sitemap-<anything>-1.xml. With the users provider dropped (below,
	 * at load) that would include /wp-sitemap-users-1.xml. Make it the 404 it
	 * is. 'index' is not a provider — core handles wp-sitemap.xml itself.
	 */
	add_action(
		'template_redirect',
		static function (): void {
			$sitemap = (string) get_query_var( 'sitemap' );

			if ( '' === $sitemap || 'index' === $sitemap ) {
				return;
			}

			if ( wp_sitemaps_get_server()->registry->get_provider( $sitemap ) ) {
				return;
			}

			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		},
		0
	);

	// Application passwords: an authentication path nothing here uses.
	add_filter( 'wp_is_application_passwords_available', '__return_false' );

	/*
	 * Restrict the REST API to logged-in users.
	 *
	 * The block editor needs REST, so it cannot be switched off — but nothing
	 * public consumes it, and anonymous access exposes users, content and meta
	 * schemas. Denying anonymous requests is the narrowest change that keeps
	 * the editor working.
	 */
	add_filter(
		'rest_authentication_errors',
		static function ( $result ) {
			if ( ! empty( $result ) ) {
				return $result;
			}

			if ( ! is_user_logged_in() ) {
				return new \WP_Error(
					'emposo_rest_forbidden',
					__( 'Die REST-API ist auf angemeldete Benutzer beschränkt.', 'emposo' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}

			return $result;
		}
	);
}
