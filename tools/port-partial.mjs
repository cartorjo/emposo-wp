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
import { STATIC_ROOT, REPO_ROOT } from './routes.mjs';

const [, , source, target] = process.argv;
if (!source || !target) {
	console.error('usage: node tools/port-partial.mjs <static-relative-source> <theme-relative-target>');
	process.exit(2);
}

let html = readFileSync(path.join(STATIC_ROOT, source), 'utf8');

const replacements = [
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
		(_, key, payload) => `<?php emposo_cur( '${key}', '${payload.replace(/'/g, "\\'")}' ); ?>`,
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

const out = path.join(REPO_ROOT, 'themes', 'emposo', target);
mkdirSync(path.dirname(out), { recursive: true });
writeFileSync(out, header + html);

const unresolved = [...html.matchAll(/\{\{[^}]*\}\}|<!--\s*(?:partial|content):[^>]*-->/g)].map((m) => m[0]);
console.log(`ported ${source} -> ${target}`);
if (unresolved.length) {
	console.log(`  UNRESOLVED tokens (${unresolved.length}): ${[...new Set(unresolved)].join(', ')}`);
	process.exitCode = 1;
}
