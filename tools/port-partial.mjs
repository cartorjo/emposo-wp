#!/usr/bin/env node
/**
 * One-shot porting aid: turn a static partial into a PHP template part.
 *
 * Used once per partial and then the output is hand-maintained. The point is
 * fidelity on the first pass: the partials contain long literal strings (the
 * inline SVG favicon data URI, the megamenu's fabricated numerals and per-item
 * copy) where retyping is the likeliest source of a silent diff.
 *
 * Token translation:
 *   {{t:key}} {{href:/p/}}      -> emposo_t(), emposo_href() (i18n)
 *   {{LANGSWITCH:slot}}         -> emposo_lang_switch()
 *   {{TITLE}} {{DESCRIPTION}}   -> escaped route fields
 *   {{BODY_CLASS}}              -> body_class(), filtered to the exact set
 *   {{SCRIPTS}}                 -> wp_head()
 *   {{CUR:key: payload}}        -> emposo_cur()
 *   {{CURATTR:key}}             -> emposo_curattr()
 *   {{brand:name}}              -> emposo_brand()
 *   {{icon:name}}               -> emposo_icon()
 *   {{image:key[:hero]}}        -> emposo_picture()
 *   <!-- partial:name -->       -> get_template_part()
 *   <!-- content:name -->       -> emposo_fragment()
 *
 * Usage: node tools/port-partial.mjs partials/header.html parts/header.php
 */
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import path from 'node:path';
import { STATIC_ROOT as PINNED_ROOT, REPO_ROOT } from './routes.mjs';

// Overrides for a re-pin (docs/resync.md): port an older reference into a
// scratch tree to recover the hand edits made on top of the first port.
const STATIC_ROOT = process.env.PORT_STATIC_ROOT ?? PINNED_ROOT;
const THEME_ROOT = process.env.PORT_THEME_ROOT ?? path.join(REPO_ROOT, 'themes', 'emposo');

const [, , source, target] = process.argv;
if (!source || !target) {
	console.error('usage: node tools/port-partial.mjs <static-relative-source> <theme-relative-target>');
	process.exit(2);
}

let html = readFileSync(path.join(STATIC_ROOT, source), 'utf8');

/*
 * Shared frames first. Page sources write <page-hero …>copy</page-hero> and
 * <page-crumb label="…">, which assemble.mjs expands through render.mjs's
 * pageHero() and breadcrumb(). Expanding them here with the same renderers —
 * the same attribute parsing as assemble.mjs expandComponents() — makes the
 * frame markup byte-identical, and leaves a {{image:key:hero}} token for the
 * image rule below.
 */
const { pageHero, breadcrumb, setLocale } = await import(path.join(STATIC_ROOT, 'content', 'render.mjs'));
// English twins expand their frames in English (breadcrumb labels, landmark name).
if (setLocale) setLocale(/^(pages|sections)\/en\//.test(source) ? 'en' : 'de');
const attrs = (raw) => Object.fromEntries([...raw.matchAll(/([a-z-]+)="([^"]*)"/g)].map(([, k, v]) => [k, v]));
html = html
	.replace(/<page-hero\b([^>]*)>([\s\S]*?)<\/page-hero>/g, (_, raw, copy) => {
		const a = attrs(raw);
		return pageHero({
			id: a.id,
			crumb: a.crumb,
			parent: a.parent?.split('|'),
			modifier: a.modifier,
			figureClass: a['figure-class'],
			copy: copy.trim(),
			figure: a.image ? `{{image:${a.image}:hero}}` : null,
		});
	})
	.replace(/<page-crumb label="([^"]*)"><\/page-crumb>/g, (_, label) => breadcrumb(label));

/*
 * English twins (pages/en/, sections/en/) keep German internal paths in their
 * source; assemble.mjs rewrites every href through the route map at build
 * time. The same rewrite happens here, at port time, with the reference's own
 * localizePath(), so the PHP template carries the English paths.
 */
if (/^(pages|sections)\/en\//.test(source)) {
	const { localizePath } = await import(path.join(STATIC_ROOT, 'content', 'i18n.mjs'));
	html = html.replace(/href="(\/[^"]*)"/g, (_, href) => `href="${localizePath(href, 'en')}"`);
}

const replacements = [
	/*
	 * The i18n tokens (reference/static docs/i18n.md). {{t:key}} prints the
	 * dictionary entry raw (it carries entities and inline markup), {{href:}}
	 * a German path in the page language. {{LANGSWITCH:slot}} replaces its whole
	 * line: emposo_lang_switch() prints the newline and indent assemble.mjs
	 * emits, and the extra newline after the PHP tag survives PHP swallowing
	 * the one directly behind `?>`.
	 */
	[
		/\n[ ]*\{\{LANGSWITCH:([a-z]+)\}\}/g,
		(_, slot) => `<?php emposo_lang_switch( '${slot}' ); ?>\n`,
	],
	[
		/\{\{t:([a-zA-Z0-9.]+)\}\}/g,
		(_, key) => `<?php echo emposo_t( '${key}' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?>`,
	],
	[
		/\{\{href:([^}]*)\}\}/g,
		(_, href) => `<?php echo esc_url( emposo_href( '${href.replace(/[\\']/g, "\\$&")}' ) ); ?>`,
	],
	// Head substitutions.
	[/\{\{TITLE\}\}/g, '<?php echo esc_html( emposo_document_title() ); ?>'],
	[/\{\{DESCRIPTION\}\}/g, '<?php echo esc_attr( emposo_meta_description() ); ?>'],
	[/\{\{BODY_CLASS\}\}/g, '<?php echo esc_attr( emposo_body_class() ); ?>'],
	[/^\s*\{\{SCRIPTS\}\}\s*$/gm, '<?php wp_head(); ?>'],

	/*
	 * Active-nav stamping. The payload is everything after the colon, VERBATIM
	 * — including its leading space: the token is `{{CUR:expertise: is-current}}`
	 * and assemble.mjs captures `' is-current'`, because the value is
	 * concatenated straight into a class attribute. An earlier `\s*` here
	 * consumed that space and produced `class="site-nav__groupis-current"`.
	 */
	[
		/\{\{CUR:([a-z-]+):([^}]*)\}\}/g,
		(_, key, payload) => `<?php emposo_cur( '${key}', '${payload.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}' ); ?>`,
	],
	[/\{\{CURATTR:([a-z-]+)\}\}/g, (_, key) => `<?php emposo_curattr( '${key}' ); ?>`],

	// Inline SVG. Both stay inline: the logo's ink is currentColor, so one file
	// renders navy in the header and white in the footer, and the icons are
	// rewritten at render time to inherit colour the same way.
	[/\{\{brand:([a-z0-9-]+)\}\}/g, (_, name) => `<?php emposo_brand( '${name}' ); ?>`],
	[/\{\{icon:([a-z0-9-]+)\}\}/g, (_, name) => `<?php emposo_icon( '${name}' ); ?>`],

	// Images. ':hero' means eager + high fetchpriority and the hero sizes.
	[
		/\{\{image:([a-z0-9-]+):hero\}\}/g,
		(_, key) => `<?php emposo_the_picture( '${key}', 'hero', true ); ?>`,
	],
	[/\{\{image:([a-z0-9-]+)\}\}/g, (_, key) => `<?php emposo_the_picture( '${key}' ); ?>`],

	// Includes.
	[
		/<!--\s*partial:([a-z0-9-]+)\s*-->/g,
		(_, name) => `<?php get_template_part( 'parts/${name}' ); ?>`,
	],
	[
		/<!--\s*content:([a-z0-9-]+)\s*-->/g,
		(_, name) => `<?php emposo_fragment( '${name}' ); ?>`,
	],
];

for (const [pattern, replacement] of replacements) {
	html = html.replace(pattern, replacement);
}

const header = `<?php
/**
 * Ported from the static build's ${source}.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
`;

const out = path.join(THEME_ROOT, target);
mkdirSync(path.dirname(out), { recursive: true });
writeFileSync(out, header + html);

const unresolved = [...html.matchAll(/\{\{[^}]*\}\}|<!--\s*(?:partial|content):[^>]*-->/g)].map((m) => m[0]);
console.log(`ported ${source} -> ${target}`);
if (unresolved.length) {
	console.log(`  UNRESOLVED tokens (${unresolved.length}): ${[...new Set(unresolved)].join(', ')}`);
	process.exitCode = 1;
}
