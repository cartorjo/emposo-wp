#!/usr/bin/env node
/**
 * Copy the static build's shipped assets into the theme, exactly the set the
 * reference uses, and remove what it no longer uses.
 *
 * Run after moving the reference/static pin (docs/resync.md). What it copies:
 *
 * - js/*.js                       -> assets/js/          (verbatim, per-page scripts)
 * - assets/fonts/, assets/vendor/ -> same paths          (self-hosted Roboto, Lenis)
 * - assets/supplied/              -> only the files the manifest references
 * - assets/icons/                 -> only the icons some source inlines
 * - assets/brand/                 -> only the brand SVGs some partial inlines
 *
 * The share crops (assets/share/) and the Organization logo PNG are NOT theme
 * assets: the head block names them at https://emposo.de/assets/..., the
 * production origin, so they belong in the launch webroot (docs/resync.md).
 *
 * One deliberate exception, LOCAL_OVERRIDES: a file WordPress keeps its own
 * version of, because the static build's behaviour is wrong here. It is never
 * overwritten; instead the reference copy's hash is pinned, and when the
 * reference changes that file the run reports drift (and --check fails) until
 * the change is merged by hand and the pin updated.
 *
 * Usage: node tools/sync-assets.mjs [--check]   (--check: report drift, exit 1)
 */
import { createHash } from 'node:crypto';
import { copyFileSync, existsSync, mkdirSync, readdirSync, readFileSync, rmSync, statSync } from 'node:fs';
import path from 'node:path';
import { STATIC_ROOT, REPO_ROOT } from './routes.mjs';

const THEME = path.join(REPO_ROOT, 'themes', 'emposo');
const CHECK = process.argv.includes('--check');
const drift = [];

const read = (rel) => readFileSync(path.join(STATIC_ROOT, rel), 'utf8');
const walk = (dir) =>
	readdirSync(dir).flatMap((name) => {
		const full = path.join(dir, name);
		return statSync(full).isDirectory() ? walk(full) : [full];
	});

/**
 * Theme files that differ from the reference on purpose, keyed by path under
 * the theme, with the sha256 of the reference copy they were last merged with.
 *
 * - assets/js/06-work.js: the contact form posts to this site (emposo-core
 *   inc/contact.php) instead of opening a mailto: link, so the submit handler
 *   only validates. The filter code above it is still the reference's.
 */
const LOCAL_OVERRIDES = {
	'assets/js/06-work.js': '26ae2415e1180acdbadc4f70dd9b37028f498f67706c4bd3d4194f40ddebc648',
};

/** Make `destDir` hold exactly `files` (basenames) copied from `srcDir`. */
function mirror(srcDir, destDir, files, { prune = true } = {}) {
	mkdirSync(destDir, { recursive: true });
	const wanted = new Set(files);
	for (const name of wanted) {
		const from = path.join(srcDir, name);
		const to = path.join(destDir, name);
		if (!existsSync(from)) throw new Error(`reference is missing ${path.relative(STATIC_ROOT, from)}`);
		const override = LOCAL_OVERRIDES[path.relative(THEME, to)];
		if (override) {
			const hash = createHash('sha256').update(readFileSync(from)).digest('hex');
			if (hash !== override) {
				drift.push(`merge ${path.relative(STATIC_ROOT, from)} into ${path.relative(REPO_ROOT, to)} by hand (local override), then pin ${hash} in LOCAL_OVERRIDES`);
			}
			continue;
		}
		const same = existsSync(to) && readFileSync(from).equals(readFileSync(to));
		if (same) continue;
		drift.push(`update ${path.relative(REPO_ROOT, to)}`);
		if (!CHECK) {
			mkdirSync(path.dirname(to), { recursive: true });
			copyFileSync(from, to);
		}
	}
	if (!prune) return;
	for (const name of readdirSync(destDir)) {
		if (wanted.has(name) || statSync(path.join(destDir, name)).isDirectory()) continue;
		drift.push(`remove ${path.relative(REPO_ROOT, path.join(destDir, name))}`);
		if (!CHECK) rmSync(path.join(destDir, name));
	}
}

// Scripts, fonts, vendor: whole directories.
mirror(path.join(STATIC_ROOT, 'js'), path.join(THEME, 'assets', 'js'), readdirSync(path.join(STATIC_ROOT, 'js')).filter((f) => f.endsWith('.js')));
mirror(path.join(STATIC_ROOT, 'assets', 'fonts'), path.join(THEME, 'assets', 'fonts'), readdirSync(path.join(STATIC_ROOT, 'assets', 'fonts')));
mirror(path.join(STATIC_ROOT, 'assets', 'vendor'), path.join(THEME, 'assets', 'vendor'), readdirSync(path.join(STATIC_ROOT, 'assets', 'vendor')));

// Supplied photographs: exactly what the manifest references.
const manifest = JSON.parse(read('assets/supplied/manifest.json'));
const supplied = new Set(['manifest.json']);
for (const asset of Object.values(manifest)) {
	for (const src of [asset.src, ...asset.variants.map((v) => v.src)]) supplied.add(path.basename(src));
}
mirror(path.join(STATIC_ROOT, 'assets', 'supplied'), path.join(THEME, 'assets', 'supplied'), [...supplied]);

// Icons and brand SVGs: exactly the ones some source inlines.
const sources = ['partials', 'pages', 'sections', 'content']
	.flatMap((dir) => walk(path.join(STATIC_ROOT, dir)))
	.map((file) => readFileSync(file, 'utf8'))
	.join('\n');
const { disciplines } = await import(path.join(STATIC_ROOT, 'content', 'site-data.mjs'));
const icons = new Set([
	...[...sources.matchAll(/\{\{icon:([a-z0-9-]+)\}\}/g)].map((m) => m[1]),
	...[...sources.matchAll(/\bicon:\s*'([a-z0-9-]+)'/g)].map((m) => m[1]),
	...disciplines.map((d) => d.icon),
	// Icons assemble.mjs inlines itself, e.g. the language switch's globe.
	...[...readFileSync(path.join(STATIC_ROOT, 'assemble.mjs'), 'utf8').matchAll(/\bicon\('([a-z0-9-]+)'\)/g)].map((m) => m[1]),
]);
mirror(path.join(STATIC_ROOT, 'assets', 'icons'), path.join(THEME, 'assets', 'icons'), [...icons].map((i) => `${i}.svg`));
const brands = new Set([...sources.matchAll(/\{\{brand:([a-z0-9-]+)\}\}/g)].map((m) => m[1]));
mirror(path.join(STATIC_ROOT, 'assets', 'brand'), path.join(THEME, 'assets', 'brand'), [...brands].map((b) => `${b}.svg`));

console.log(`${CHECK ? 'drift' : 'synced'}: ${drift.length} change(s); ${icons.size} icons, ${brands.size} brand SVG(s), ${supplied.size - 1} supplied files`);
for (const line of drift.slice(0, 40)) console.log(`  ${line}`);
if (drift.length > 40) console.log(`  … ${drift.length - 40} more`);
console.log(`icons: ${[...icons].sort().join(' ')}`);
if (CHECK && drift.length) process.exitCode = 1;
