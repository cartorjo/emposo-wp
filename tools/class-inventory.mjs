#!/usr/bin/env node
/**
 * Assert the theme's Tailwind build still emits every class the reference does.
 *
 * This is the one check a DOM diff cannot do. Tailwind only generates a utility
 * if it finds the class name in a scanned file, so repointing `@source` at PHP
 * templates can silently drop a class: the markup still references it, the DOM
 * diff still passes, and the page just renders wrong. A missing class is
 * therefore fatal; extras are reported and must be justified.
 *
 * Classes are extracted from RULE SELECTORS, not the raw file text. A regex
 * over the whole stylesheet also matches decimal fragments in values — `.5rem`
 * yields a phantom class `5rem` — which inflated an early count from 239 to 412.
 */
import { readFileSync, existsSync } from 'node:fs';
import path from 'node:path';
import { REPO_ROOT, STATIC_ROOT } from './routes.mjs';

const REFERENCE_CSS = path.join(STATIC_ROOT, 'css', 'site.css');
const THEME_CSS = path.join(REPO_ROOT, 'themes', 'emposo', 'assets', 'css', 'site.css');

/**
 * Extract class names from a stylesheet's selectors.
 *
 * @param {string} css
 * @returns {Set<string>}
 */
export function extractClasses(css) {
	const classes = new Set();
	// Everything before a `{` is a selector list (or an at-rule prelude).
	for (const [, prelude] of css.matchAll(/([^{}]+)\{/g)) {
		// `\\.` consumes a Tailwind escape as one unit, so `min-h-\[3rem\]`
		// and `nav\:hidden` survive intact.
		for (const [, name] of prelude.matchAll(/\.((?:[A-Za-z0-9_-]|\\.)+)/g)) {
			classes.add(name.replace(/\\(.)/g, '$1'));
		}
	}
	return classes;
}

function main() {
	if (!existsSync(REFERENCE_CSS)) {
		console.error(`Reference stylesheet missing: ${REFERENCE_CSS}`);
		console.error('Is the reference/static submodule initialised?');
		process.exit(2);
	}

	const reference = extractClasses(readFileSync(REFERENCE_CSS, 'utf8'));

	if (!existsSync(THEME_CSS)) {
		console.log(`reference: ${reference.size} classes`);
		console.log(`theme:     not built yet (${path.relative(REPO_ROOT, THEME_CSS)})`);
		console.log('');
		console.log('PENDING: run `npm run build:css` once src/styles is in place (phase 5).');
		process.exit(1);
	}

	const theme = extractClasses(readFileSync(THEME_CSS, 'utf8'));

	const missing = [...reference].filter((c) => !theme.has(c)).sort();
	const extra = [...theme].filter((c) => !reference.has(c)).sort();

	console.log(`reference: ${reference.size} classes`);
	console.log(`theme:     ${theme.size} classes`);
	console.log('');

	if (missing.length) {
		console.log(`MISSING ${missing.length} class(es) — fatal, these render wrong with no DOM diff:`);
		for (const c of missing) console.log(`  - ${c}`);
		console.log('');
		console.log('Usual cause: the @source list does not cover the file that references it.');
		console.log('Tailwind does not scan the database, so utility classes inside imported');
		console.log('post_content are invisible to it — which is why hand-authored bodies are');
		console.log('registered as block patterns in patterns/ (a scanned directory) and the');
		console.log('importer inserts pattern references rather than expanded HTML.');
	}

	if (extra.length) {
		console.log(`extra ${extra.length} class(es) — allowed, but check each is intended:`);
		for (const c of extra.slice(0, 40)) console.log(`  + ${c}`);
		if (extra.length > 40) console.log(`  … ${extra.length - 40} more`);
		console.log('');
		console.log('Note: bare utility names (block, flex, grid, inline, relative, shadow,');
		console.log('table, fixed, invisible) are expected. Tailwind extracts class candidates');
		console.log('from raw text, and @source now covers heavily-commented PHP — so English');
		console.log('prose like "flex and grid rows" or "inline SVG" becomes a candidate. The');
		console.log('reference avoided this because its @source pointed at markup files. Cost');
		console.log('is ~430 bytes raw, ~267 gzipped, against a 25 KB budget: real but');
		console.log('negligible, and a false economy to fight by rewording documentation.');
	}

	if (!missing.length && !extra.length) console.log('Class inventory identical to the reference.');
	else if (!missing.length) console.log('No missing classes.');

	process.exit(missing.length ? 1 : 0);
}

main();
