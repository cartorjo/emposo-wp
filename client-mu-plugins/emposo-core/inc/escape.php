<?php
/**
 * The static build's escaper, shared by every output site.
 *
 * The reference build escapes exactly four characters — & < > " — and NOT the
 * apostrophe (reference/static/content/render.mjs `escape`). WordPress's
 * esc_html()/esc_attr() are htmlspecialchars with ENT_QUOTES, so they also emit
 * &#039; for an apostrophe. tools/normalise.mjs does not decode entities, so a
 * single apostrophe in a title, description or alt string is a silent,
 * per-string parity divergence the moment an editor types one — green today
 * only because no contract string contains one.
 *
 * Defined once here, loaded before images.php and fragments.php, and wrapped as
 * the global emposo_escape_static() so the theme's head.php can call it too.
 * fragments.php's e() delegates to this: one escaper, one behaviour, no drift.
 *
 * Safe in every context it is used: all four sites are HTML text or a
 * double-quoted attribute, and " is escaped, so an unescaped ' cannot break out.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core {

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Escape a value exactly as the static build's escape() does.
	 *
	 * htmlspecialchars runs first with ENT_NOQUOTES so the & in the &quot; added
	 * afterwards is not double-encoded; double_encode stays true to match JS
	 * replacing & first, which turns a literal & into &amp; and an existing
	 * &amp; into &amp;amp; — identical in both runtimes.
	 *
	 * @param mixed $value Raw value.
	 */
	function escape_static( $value ): string {
		return str_replace(
			'"',
			'&quot;',
			htmlspecialchars( (string) $value, ENT_NOQUOTES, 'UTF-8', true )
		);
	}
}

namespace {
	/**
	 * Global alias so the theme can escape exactly as the static build does.
	 *
	 * The theme's head.php calls bare emposo_* helpers; this gives it the
	 * reference escaper without reaching into a plugin namespace.
	 *
	 * @param mixed $value Raw value.
	 */
	function emposo_escape_static( $value ): string {
		return \Emposo\Core\escape_static( $value );
	}
}
