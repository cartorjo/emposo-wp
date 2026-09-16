#!/usr/bin/env node
/**
 * Protect the Tailwind @source contract.
 *
 * Tailwind's `@source` list must point at authoring sources only, never at
 * rendered output. That is kept tractable by keeping markup out of the theme's
 * root templates — they are deliberately thin `get_template_part()` shims — so
 * the list stays a short closed set of directories.
 *
 * Without this check the contract decays silently: someone adds a div with a
 * class to header.php, Tailwind never scans it, and the utility is missing from
 * site.css with no error anywhere.
 */
import { readdirSync, readFileSync, existsSync } from 'node:fs';
import path from 'node:path';
import { REPO_ROOT } from './routes.mjs';

const THEME_ROOT = path.join(REPO_ROOT, 'themes', 'emposo');
const SOURCE_DIRS = ['parts', 'patterns', 'page-templates', 'inc', 'assets/js'];

function main() {
	const failures = [];

	// 1. No class attributes in root-level templates.
	const rootPhp = readdirSync(THEME_ROOT).filter((f) => f.endsWith('.php'));
	for (const file of rootPhp) {
		const contents = readFileSync(path.join(THEME_ROOT, file), 'utf8');
		if (/\bclass="/.test(contents)) {
			failures.push(
				`themes/emposo/${file} contains class="" — root templates must stay ` +
					'markup-free shims so the @source list can remain a closed set. ' +
					'Move the markup into parts/.'
			);
		}
	}

	// 2. Every directory the @source list names must exist.
	const entry = path.join(THEME_ROOT, 'src', 'styles', 'main.css');
	if (existsSync(entry)) {
		const css = readFileSync(entry, 'utf8');
		const sources = [...css.matchAll(/@source\s+(?:not\s+)?["']([^"']+)["']/g)].map((m) => m[1]);
		if (sources.length === 0) {
			failures.push('src/styles/main.css declares no @source — with source(none) that builds an empty stylesheet.');
		}
		for (const src of sources) {
			const resolved = path.resolve(path.dirname(entry), src);
			if (!existsSync(resolved)) {
				failures.push(`@source "${src}" does not exist (resolved to ${path.relative(REPO_ROOT, resolved)})`);
			}
		}
		if (!/source\(none\)/.test(css)) {
			failures.push(
				'src/styles/main.css must keep `source(none)` on the utilities import: it is the ' +
					'only reason Tailwind does not generate a competing layered `.container` ' +
					'utility that would shadow the hand-written unlayered one.'
			);
		}
		if (!/@layer\s+theme,\s*base,\s*components,\s*utilities/.test(css)) {
			failures.push('src/styles/main.css must declare `@layer theme, base, components, utilities` — the order is load-bearing.');
		}
	} else {
		console.log('note: src/styles/main.css not present yet (phase 5); skipping @source checks.');
	}

	// 3. Directories that must exist for the port to be portable.
	for (const dir of SOURCE_DIRS) {
		if (!existsSync(path.join(THEME_ROOT, dir))) {
			failures.push(`expected theme directory missing: ${dir}`);
		}
	}

	if (failures.length) {
		console.error('Source contract violations:');
		for (const f of failures) console.error(`  - ${f}`);
		process.exit(1);
	}
	console.log(`Source contract OK (${rootPhp.length} root templates checked, all markup-free).`);
}

main();
