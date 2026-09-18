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
import { createHash } from 'node:crypto';
import path from 'node:path';
import { REPO_ROOT, STATIC_ROOT } from './routes.mjs';

const THEME_ROOT = path.join(REPO_ROOT, 'themes', 'emposo');
const SOURCE_DIRS = ['parts', 'patterns', 'page-templates', 'inc', 'assets/js'];

/**
 * Verify the recorded source hashes in the exported data.
 *
 * Both data files carry a `sources` map of reference paths to truncated sha256
 * hashes, and tools/export-content.mjs documented that "the importer refuses to
 * run against a stale contract" — which nothing did. Nothing read those hashes
 * at all, so an export could silently describe a reference build that had since
 * changed, and the importer would seed content the parity harness then compared
 * against different HTML.
 *
 * The check lives here rather than in the importer because that is where the
 * files are: reference/static is a submodule at the repository root and is not
 * mapped into the WordPress container, so PHP cannot hash it. `npm run check`
 * runs on the host, where it can.
 */
function checkExportFreshness() {
	const failures = [];

	const files = [
		path.join(REPO_ROOT, 'client-mu-plugins', 'emposo-core', 'data', 'site-export.json'),
		path.join(REPO_ROOT, 'client-mu-plugins', 'emposo-core', 'data', 'routes.json'),
	];

	for (const file of files) {
		if (!existsSync(file)) {
			continue;
		}

		const data = JSON.parse(readFileSync(file, 'utf8'));
		const sources = data.sources;
		const name = path.basename(file);

		if (!sources || typeof sources !== 'object' || Object.keys(sources).length === 0) {
			failures.push(`${name} records no source hashes — regenerate it with the exporter.`);
			continue;
		}

		for (const [rel, expected] of Object.entries(sources)) {
			const target = path.join(STATIC_ROOT, rel);

			if (!existsSync(target)) {
				failures.push(`${name} names a reference source that no longer exists: ${rel}`);
				continue;
			}

			// Same digest and truncation the exporters use.
			const actual = createHash('sha256').update(readFileSync(target)).digest('hex').slice(0, 16);

			if (actual !== expected) {
				failures.push(
					`${name} is stale: reference/static/${rel} now hashes ${actual}, the export records ${expected}. ` +
						'Re-run the exporter (tools/export-content.mjs, tools/export-routes.mjs) and re-import.'
				);
			}
		}
	}

	return failures;
}

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

	// 4. The exported data must still match the reference build it came from.
	failures.push(...checkExportFreshness());

	if (failures.length) {
		console.error('Source contract violations:');
		for (const f of failures) console.error(`  - ${f}`);
		process.exit(1);
	}
	console.log(`Source contract OK (${rootPhp.length} root templates checked, all markup-free).`);
}

main();
