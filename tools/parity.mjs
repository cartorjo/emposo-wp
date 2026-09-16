#!/usr/bin/env node
/**
 * DOM parity: every WordPress route against the pinned static build.
 *
 * This is the primary gate of the port. It normalises both sides identically
 * (see normalise.mjs, which has its own self-test), diffs them, and runs the
 * per-document invariants from checks.mjs.
 *
 * Usage:
 *   node tools/parity.mjs                 # report, non-strict
 *   node tools/parity.mjs --strict        # whitespace-only diffs also fail (CI)
 *   node tools/parity.mjs --route=/kontakt/
 *   node tools/parity.mjs --self-test     # static vs static: proves the normaliser is idempotent
 *   node tools/parity.mjs --target=static # run the invariants against the reference itself
 *   node tools/parity.mjs --report        # write a per-route diff report
 */
import { readFileSync, existsSync, writeFileSync, mkdirSync } from 'node:fs';
import path from 'node:path';
import { routes, pageRoutes, STATIC_ROOT, REPO_ROOT } from './routes.mjs';
import { normalise, collapseWhitespace } from './normalise.mjs';
import { runChecks, checkLinks, checkFontFaces, compareStructure } from './checks.mjs';

const args = process.argv.slice(2);
const flag = (name) => args.includes(`--${name}`);
const opt = (name) => args.find((a) => a.startsWith(`--${name}=`))?.split('=').slice(1).join('=');

const STRICT = flag('strict');
const SELF_TEST = flag('self-test');
const WRITE_REPORT = flag('report');
const ONLY = opt('route');
const TARGET = opt('target') ?? 'wp';
const AGAINST_STATIC = TARGET === 'static';

const config = JSON.parse(readFileSync(path.join(REPO_ROOT, 'tools/parity.config.json'), 'utf8'));
const WP_BASE = opt('base') ?? config.wpBase;
const THEME_DIR = path.join(REPO_ROOT, 'themes', 'emposo');

const routeUrls = new Set(pageRoutes.map((r) => r.url));
const staticCache = new Map();

function readStatic(route) {
	const file = path.join(STATIC_ROOT, route.staticFile);
	if (!existsSync(file)) return null;
	return readFileSync(file, 'utf8');
}

function readStaticByUrl(url) {
	if (staticCache.has(url)) return staticCache.get(url);
	const route = routes.find((r) => r.url === url);
	const html = route ? readStatic(route) : null;
	staticCache.set(url, html);
	return html;
}

async function fetchWp(route) {
	const res = await fetch(`${WP_BASE}${route.url}`, { redirect: 'manual' });
	return { status: res.status, html: await res.text() };
}

/**
 * First divergence, as a character window.
 *
 * Normalisation collapses inter-node whitespace, so the normalised form is
 * effectively one long line and a line number carries no information. A
 * character offset plus surrounding context does.
 */
function firstDiff(a, b) {
	const max = Math.max(a.length, b.length);

	for (let i = 0; i < max; i += 1) {
		if (a[i] !== b[i]) {
			// Back up to the start of the enclosing tag so the window begins
			// somewhere meaningful rather than mid-attribute.
			const from = Math.max(0, a.lastIndexOf('<', i) === -1 ? i - 40 : Math.min(a.lastIndexOf('<', i), i - 10 < 0 ? 0 : i - 10));
			return {
				offset: i,
				expected: a.slice(from, from + 170) || '(end of document)',
				actual: b.slice(from, from + 170) || '(end of document)',
			};
		}
	}

	return null;
}

function classify(expected, actual) {
	if (expected === actual) return 'identical';
	if (collapseWhitespace(expected) === collapseWhitespace(actual)) return 'whitespace-only';
	return 'different';
}

async function main() {
	const target = ONLY ? routes.filter((r) => r.url === ONLY) : routes;
	if (target.length === 0) {
		console.error(`No route matches --route=${ONLY}`);
		process.exit(2);
	}

	console.log(
		SELF_TEST
			? `Parity SELF-TEST (normaliser idempotency) — ${target.length} route(s)`
			: AGAINST_STATIC
				? `Invariants against reference/static — ${target.length} route(s)`
				: `Parity: ${WP_BASE} vs reference/static — ${target.length} route(s)${STRICT ? ' [strict]' : ''}`
	);
	console.log('');

	const results = [];

	for (const route of target) {
		const staticHtml = readStatic(route);
		if (staticHtml === null) {
			results.push({ route, state: 'missing-reference' });
			continue;
		}

		let actualHtml;
		let status;

		if (SELF_TEST || AGAINST_STATIC) {
			// Compare the reference to itself through the normaliser. If this
			// fails, the normaliser is not idempotent and nothing downstream
			// can be trusted. With --target=static the invariants also run, which
			// is what proves they encode what the static build actually does.
			actualHtml = staticHtml;
			status = route.expectStatus;
		} else {
			try {
				const res = await fetchWp(route);
				actualHtml = res.html;
				status = res.status;
			} catch (error) {
				results.push({ route, state: 'unreachable', error: error.message });
				continue;
			}
		}

		const normaliseOptions = {
			themeBase: config.themeBase,
			// Only strip the origin on the WordPress side; the reference has none.
			siteOrigin: SELF_TEST || AGAINST_STATIC ? '' : WP_BASE,
		};
		const expected = normalise(staticHtml, { themeBase: config.themeBase });
		const got = normalise(actualHtml, normaliseOptions);
		const state = classify(expected, got);

		const assetRoot = AGAINST_STATIC ? STATIC_ROOT : THEME_DIR;

		const checks = SELF_TEST
			? []
			: runChecks({ html: actualHtml, route, routeUrls });

		const links = SELF_TEST
			? []
			: checkLinks({
					html: actualHtml,
					route,
					routeUrls,
					assetRoot,
					readTarget: readStaticByUrl,
				});

		const structure = SELF_TEST || AGAINST_STATIC ? [] : compareStructure(expected, got);

		results.push({
			route,
			state,
			structure,
			status,
			statusOk: status === route.expectStatus,
			diff: state === 'different' || state === 'whitespace-only' ? firstDiff(expected, got) : null,
			checks,
			links,
			expected,
			got,
		});
	}

	// --- cross-document checks ---------------------------------------------
	const fontFailures = SELF_TEST || AGAINST_STATIC
		? checkFontFaces(path.join(STATIC_ROOT, 'css', '00-fonts.css'))
		: checkFontFaces(path.join(THEME_DIR, 'assets', 'css', '00-fonts.css'));

	// --- report ------------------------------------------------------------
	let failed = 0;
	for (const r of results) {
		const problems = [];

		if (r.state === 'missing-reference') problems.push('no reference document');
		if (r.state === 'unreachable') problems.push(`unreachable: ${r.error}`);
		if (r.statusOk === false) problems.push(`HTTP ${r.status}, expected ${r.route.expectStatus}`);
		if (r.state === 'different') problems.push('DOM differs');
		if (r.state === 'whitespace-only' && STRICT) problems.push('whitespace differs (strict)');
		problems.push(...(r.structure ?? []), ...(r.checks ?? []), ...(r.links ?? []));

		const isWhitespaceOnly = r.state === 'whitespace-only' && !STRICT;

		if (problems.length === 0) {
			console.log(`  ok        ${r.route.url}`);
			continue;
		}
		if (isWhitespaceOnly && problems.length === 0) {
			console.log(`  ws-only   ${r.route.url}`);
			continue;
		}

		failed += 1;
		console.log(`  FAIL      ${r.route.url}`);
		for (const p of problems.slice(0, 8)) console.log(`              - ${p}`);
		if (problems.length > 8) console.log(`              … ${problems.length - 8} more`);
		if (r.diff) {
			console.log(`              first diff at char ${r.diff.offset}`);
			console.log(`                expected: ${truncate(r.diff.expected, 170)}`);
			console.log(`                actual:   ${truncate(r.diff.actual, 170)}`);
		}
	}

	let crossDocumentFailures = 0;
	if (fontFailures.length) {
		crossDocumentFailures += 1;
		console.log('  FAIL      css/00-fonts.css');
		for (const f of fontFailures) console.log(`              - ${f}`);
	}

	console.log('');
	console.log(`allowed deltas configured: ${config.allowedDeltas.length}`);
	for (const d of config.allowedDeltas) {
		console.log(`  - ${d.reason} (max ${d.maxCount})`);
	}
	console.log('');
	console.log(
		`${results.length - failed}/${results.length} route(s) passing` +
			(failed ? `, ${failed} failing` : '') +
			(crossDocumentFailures ? `, ${crossDocumentFailures} cross-document check failing` : '')
	);

	if (WRITE_REPORT) {
		const dir = path.join(REPO_ROOT, 'tools', '.cache');
		mkdirSync(dir, { recursive: true });
		const out = path.join(dir, 'parity-report.json');
		writeFileSync(
			out,
			JSON.stringify(
				results.map((r) => ({
					url: r.route.url,
					state: r.state,
					status: r.status,
					checks: r.checks,
					links: r.links,
					structure: r.structure,
					diff: r.diff,
				})),
				null,
				2
			)
		);
		console.log(`report written: ${path.relative(REPO_ROOT, out)}`);
	}

	process.exit(failed === 0 && crossDocumentFailures === 0 ? 0 : 1);
}

/**
 * Render a line with leading/trailing whitespace made visible.
 *
 * Without this, an indentation-only difference prints as two identical-looking
 * lines and reads as a harness bug rather than a real (if minor) diff.
 */
function truncate(s, n = 160) {
	const marked = s
		.replace(/^([ \t]+)/, (_, ws) => '·'.repeat(ws.length))
		.replace(/([ \t]+)$/, (_, ws) => '·'.repeat(ws.length));
	return marked.length > n ? `${marked.slice(0, n)}…` : marked;
}

await main();
