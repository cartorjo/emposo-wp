#!/usr/bin/env node
/**
 * Measured-quality gate: layout, header alignment, accessibility and budgets.
 *
 * Reproduces the shapes in reference/static/audit-evidence/final/verify.json so
 * static and WordPress numbers are mechanically comparable, and extends them to
 * every route — 25 of the 41 were never measured, because the committed
 * evidence predates the September feedback wave that grew the site from 17
 * pages to 42.
 *
 * Always runs logged out. Logged in, the admin bar adds two requests, an inline
 * `html{margin-top:32px!important}` that causes CLS, and a sticky-header offset
 * shift that fails the header probes — i.e. it measures a different page.
 *
 * Usage:
 *   node tools/audit.mjs --target=static --write-baseline
 *   node tools/audit.mjs --target=wp
 *   node tools/audit.mjs --target=wp --routes=/,/kontakt/
 */
import { writeFileSync, mkdirSync, readFileSync, existsSync } from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright';
import { routes, pageRoutes, STATIC_ROOT, REPO_ROOT } from './routes.mjs';
import { serveStatic } from './static-server.mjs';

const args = process.argv.slice(2);
const flag = (n) => args.includes(`--${n}`);
const opt = (n) => args.find((a) => a.startsWith(`--${n}=`))?.split('=').slice(1).join('=');

const TARGET = opt('target') ?? 'wp';
const WRITE_BASELINE = flag('write-baseline');
const ROUTE_FILTER = opt('routes')?.split(',').filter(Boolean);

/** The ten widths the reference's 170 layout probes use. */
const WIDTHS = [320, 375, 390, 768, 1024, 1280, 1440, 1920, 844, 960];

/** Widths at which the header must sit exactly on the content container. */
const HEADER_WIDTHS = [1280, 1440, 1920];

/**
 * Budgets.
 *
 * Deliberately only the compression-independent ones. This tool measures
 * UNCOMPRESSED resource bytes, whereas 01-audit.md's <=800 KB page budget and
 * the 194-295 KB figures in audit-evidence describe Lighthouse's *compressed
 * transfer* size — css/site.css alone is 64 KB raw and ~12.5 KB gzipped. So
 * comparing raw bytes against a transfer budget reads ~2x over and would
 * manufacture failures on routes the reference passes.
 *
 * Absolute byte budgets therefore belong to the Lighthouse step, which measures
 * the same thing the evidence did. Here, byte growth is gated RELATIVE to the
 * static baseline: the question this tool answers is "is WordPress worse than
 * the reference", not "what is the absolute page weight".
 *
 * Request count, CLS and image size are compression-independent — images are
 * already-compressed binaries, so content-length is their transfer size — and
 * stay absolute.
 */
const BUDGETS = {
	requests: 30,
	largestImageBytes: 200 * 1024,
	cls: 0.05,
	/** Allowed growth over the static baseline before it counts as a regression. */
	bytesToleranceRatio: 1.02,
	requestsTolerance: 0,
};

/** Baseline produced by `--target=static --write-baseline`, when present. */
let baseline = null;

/**
 * Committed, not cached: CI gates byte and request growth against this, and it
 * is the only record that the 25 routes the September evidence never covered
 * were measured at all.
 */
const BASELINE_PATH = path.join(REPO_ROOT, 'audit-evidence', 'static-baseline.json');

/**
 * Where a WordPress-side run records itself.
 *
 * Gitignored on purpose (`/audit-evidence/wp/`): the static baseline is the
 * committed contract, and this is a measurement of one machine at one moment.
 * The admin dashboard reads it to show when the site was last audited, and
 * reports its absence rather than implying a pass.
 */
const WP_REPORT_PATH = path.join(REPO_ROOT, 'audit-evidence', 'wp', 'latest.json');

/**
 * Same-origin path the axe engine is served from.
 *
 * Declared here because two places depend on it agreeing: the route that
 * fulfils it, and the response filter that keeps it out of the byte totals.
 */
const AXE_PATH = '/__axe-core__.js';

async function auditRoute(browser, base, route) {
	const report = { url: route.url, layouts: [], header: [], images: {}, requests: null };

	const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
	const page = await context.newPage();

	const consoleMessages = [];
	page.on('console', (m) => consoleMessages.push(`${m.type()}: ${m.text()}`));
	page.on('pageerror', (e) => consoleMessages.push(`pageerror: ${e.message}`));

	/*
	 * Off-host requests are a hard GDPR failure and payload size drives the
	 * budgets, so record every response.
	 *
	 * The row is pushed SYNCHRONOUSLY and its size filled in later. The first
	 * version of this handler was `async` and pushed after awaiting the body,
	 * so `initialCount` below snapshotted a half-filled array: the same route
	 * measured 20 requests on one run and 22 on the next, and one flapped
	 * across its budget. Order and count are exact now; only the byte fill is
	 * asynchronous, and it is awaited before anything reads it.
	 *
	 * Bytes are the DECOMPRESSED body length, not Content-Length. Content-Length
	 * is the compressed size when the server gzips — WordPress behind Apache
	 * does, the static reference server does not — so budgeting one against the
	 * other compared transport against payload and quietly flattered whichever
	 * side happened to be compressed.
	 */
	const requests = [];
	const sized = [];
	page.on('response', (res) => {
		const url = res.url();

		// Skip the audit's own axe fetch: it is a tool, not part of the page,
		// and counting it would put ~600 KB of accessibility engine into the
		// very byte totals it exists to check.
		if (url.endsWith(AXE_PATH)) {
			return;
		}

		const row = { url, status: res.status(), bytes: 0, type: res.request().resourceType() };
		requests.push(row);
		sized.push(
			res
				.body()
				.then((body) => {
					row.bytes = body.length;
				})
				.catch(() => {
					// Redirects and 204s have no body to read; 0 is correct.
				})
		);
	});

	/** Resolve every outstanding size so a count or a sum can be trusted. */
	const settleSizes = async () => {
		await Promise.all(sized.splice(0, sized.length));
	};

	const response = await page.goto(`${base}${route.url}`, { waitUntil: 'networkidle' });
	report.status = response?.status() ?? 0;
	report.headers = response ? response.headers() : {};

	// Split the request log at the end of the initial load. Budgets and the
	// committed Lighthouse figures describe the page AS LOADED; scrolling to
	// force every lazy image then counting the lot measures something else
	// entirely (it read 823 KB for a route Lighthouse recorded at 242 KB).
	// Both numbers are useful, so record both and budget against the initial one.
	await settleSizes();
	const initialCount = requests.length;

	// A redirect adds a full round trip to LCP; the reference serves 200 directly.
	report.redirected = response ? response.request().redirectedFrom() !== null : false;

	// Scroll the page so lazy images load and any post-scroll state is measured,
	// matching how the reference evidence was gathered.
	await page.evaluate(async () => {
		await new Promise((resolve) => {
			let y = 0;
			const step = () => {
				y += window.innerHeight;
				window.scrollTo(0, y);
				if (y < document.body.scrollHeight) requestAnimationFrame(step);
				else {
					window.scrollTo(0, 0);
					resolve();
				}
			};
			step();
		});
	});
	await page.waitForLoadState('networkidle').catch(() => {});

	// --- layout probes -----------------------------------------------------
	for (const width of WIDTHS) {
		const height = width === 844 ? 390 : width === 960 ? 600 : 900;
		await page.setViewportSize({ width, height });
		await page.waitForTimeout(60);

		const probe = await page.evaluate(() => {
			const docWidth = document.documentElement.clientWidth;
			const clipped = [];
			for (const el of document.querySelectorAll('body *')) {
				const style = getComputedStyle(el);
				if (style.display === 'none' || style.visibility === 'hidden') continue;
				const rect = el.getBoundingClientRect();
				if (rect.width === 0 && rect.height === 0) continue;
				// Overflowing the viewport horizontally, or text cut off inside its box.
				if (rect.right > docWidth + 1 || rect.left < -1) {
					clipped.push(`${el.tagName.toLowerCase()}.${String(el.className).split(' ')[0]}`);
				}
			}
			return {
				overflow: document.documentElement.scrollWidth > docWidth + 1,
				clipped: [...new Set(clipped)].slice(0, 8),
				rootFont: getComputedStyle(document.documentElement).fontSize,
			};
		});

		report.layouts.push({ width, ...probe });
	}

	// --- header alignment --------------------------------------------------
	for (const width of HEADER_WIDTHS) {
		await page.setViewportSize({ width, height: 900 });
		await page.waitForTimeout(60);

		const probe = await page.evaluate(() => {
			const header = document.querySelector('[data-site-header]');
			if (!header) return { missing: true };
			const headerContainer = header.querySelector('.container') ?? header;
			const contentContainer = [...document.querySelectorAll('main .container')][0];
			const cta = header.querySelector('.header-contact, .header-contact a');
			const lastNav = [...header.querySelectorAll('.site-nav a, .site-nav summary')].pop();

			const gap =
				cta && lastNav
					? Math.round(cta.getBoundingClientRect().left - lastNav.getBoundingClientRect().right)
					: null;

			return {
				headerLeft: headerContainer ? +headerContainer.getBoundingClientRect().left.toFixed(5) : null,
				contentLeft: contentContainer ? +contentContainer.getBoundingClientRect().left.toFixed(5) : null,
				navVisible: !!header.querySelector('.site-nav') &&
					getComputedStyle(header.querySelector('.site-nav')).display !== 'none',
				gap,
			};
		});

		report.header.push({ width, ...probe });
	}

	await page.setViewportSize({ width: 1440, height: 900 });

	// --- accessibility -----------------------------------------------------
	/*
	 * axe is served from a SAME-ORIGIN URL rather than injected inline.
	 *
	 * The site sends `script-src 'self'`, which correctly blocks
	 * addScriptTag({content}) — the injection is an inline script. Playwright
	 * offers bypassCSP for exactly this, but using it would mean the audit no
	 * longer runs against the policy the site actually sends. Intercepting a
	 * same-origin URL keeps the real CSP in force and still loads axe, so the
	 * measurement stays honest.
	 */
	const axeSource = readFileSync(path.join(REPO_ROOT, 'node_modules', 'axe-core', 'axe.min.js'), 'utf8');
	const axeUrl = `${base}${AXE_PATH}`;

	await page.route(axeUrl, (route) =>
		route.fulfill({
			status: 200,
			contentType: 'text/javascript; charset=utf-8',
			body: axeSource,
		})
	);

	report.axe = [];
	for (const width of [1440, 390]) {
		await page.setViewportSize({ width, height: width === 390 ? 844 : 900 });
		await page.waitForTimeout(60);
		await page.addScriptTag({ url: axeUrl });
		const axe = await page.evaluate(async () =>
			// Same rule sets the reference used.
			window.axe
				.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] } })
				.then((r) => ({
					violations: r.violations.map((v) => ({ id: v.id, impact: v.impact, nodes: v.nodes.length })),
					incomplete: r.incomplete.map((v) => v.id),
				}))
		);
		report.axe.push({ width, ...axe });
	}

	// --- budgets -----------------------------------------------------------
	const sameOrigin = new URL(base).origin;
	const offHost = requests.filter((r) => !r.url.startsWith(sameOrigin) && !r.url.startsWith('data:'));
	const initial = requests.slice(0, initialCount);
	const images = requests.filter((r) => r.type === 'image');
	const sum = (list) => list.reduce((total, r) => total + r.bytes, 0);

	await settleSizes();

	report.requests = {
		// Budgeted: comparable to the committed Lighthouse evidence.
		count: initial.length,
		totalBytes: sum(initial),
		// Informational: the cost of scrolling the whole page.
		fullCount: requests.length,
		fullBytes: sum(requests),
		offHost: offHost.map((r) => r.url),
		largestImage: images.reduce((max, r) => (r.bytes > (max?.bytes ?? 0) ? r : max), null),
		/*
		 * The route's own document is excluded when a 4xx IS the expected
		 * response: the 404 template is audited by requesting a path that
		 * cannot exist, so its 404 is the point, not a broken subresource.
		 */
		broken: requests
			.filter((r) => r.status >= 400 && !(r.status === route.expectStatus && r.url === `${base}${route.url}`))
			.map((r) => `${r.status} ${r.url}`),
	};

	report.console = consoleMessages;

	// --- CLS ---------------------------------------------------------------
	report.cls = await page.evaluate(
		() =>
			new Promise((resolve) => {
				let total = 0;
				try {
					new PerformanceObserver((list) => {
						for (const entry of list.getEntries()) if (!entry.hadRecentInput) total += entry.value;
					}).observe({ type: 'layout-shift', buffered: true });
				} catch {
					resolve(null);
					return;
				}
				setTimeout(() => resolve(+total.toFixed(4)), 400);
			})
	);

	await context.close();
	return report;
}

function evaluate(report, route) {
	const failures = [];
	const bases = baseline?.routes?.find((r) => r.url === route.url);

	if (report.status !== route.expectStatus) {
		failures.push(`HTTP ${report.status}, expected ${route.expectStatus}`);
	}
	if (report.redirected) failures.push('served via a redirect (adds a round trip to LCP)');

	for (const l of report.layouts) {
		if (l.overflow) failures.push(`${l.width}px: horizontal overflow`);
		if (l.clipped?.length) failures.push(`${l.width}px: clipped ${l.clipped.join(', ')}`);
		if (l.rootFont !== '16px') failures.push(`${l.width}px: rootFont is ${l.rootFont}, expected 16px`);
	}

	for (const h of report.header) {
		if (h.missing) {
			failures.push(`${h.width}px: [data-site-header] missing`);
			continue;
		}
		if (h.headerLeft !== null && h.contentLeft !== null && h.headerLeft !== h.contentLeft) {
			failures.push(`${h.width}px: header left ${h.headerLeft} != content left ${h.contentLeft}`);
		}
		if (h.gap !== null && h.gap < 24) failures.push(`${h.width}px: CTA gap ${h.gap}px, minimum 24px`);
	}

	/*
	 * Security headers are asserted directly. Discovering the CSP by watching a
	 * script get blocked is how this check came to exist — the audit tool broke
	 * the moment the policy went in — but a side effect is not a test.
	 *
	 * WordPress only. The static reference is served by tools/static-server.mjs,
	 * which sends none of these and is not what ships — asserting them there
	 * failed every static route, and since --write-baseline runs against static,
	 * it also meant a re-baseline could never exit 0.
	 */
	if (TARGET !== 'static') {
		const requiredHeaders = {
			'content-security-policy': /default-src 'self'/,
			'x-content-type-options': /nosniff/,
			'referrer-policy': /strict-origin/,
			'permissions-policy': /geolocation=\(\)/,
			'x-frame-options': /DENY/,
		};
		for (const [header, pattern] of Object.entries(requiredHeaders)) {
			const value = report.headers?.[header] ?? '';
			if (!pattern.test(value)) {
				failures.push(`header ${header} is ${value ? `"${value}"` : 'absent'}, expected to match ${pattern}`);
			}
		}

		// Scripts must stay strictly same-origin: 'unsafe-inline' or 'unsafe-eval'
		// in script-src would defeat the point of the policy.
		const csp = report.headers?.['content-security-policy'] ?? '';
		const scriptSrc = /script-src([^;]*)/.exec(csp)?.[1] ?? '';
		if (/unsafe-inline|unsafe-eval/.test(scriptSrc)) {
			failures.push(`script-src grants ${scriptSrc.trim()}`);
		}
	}

	for (const a of report.axe) {
		if (a.violations.length) {
			failures.push(
				`${a.width}px: ${a.violations.length} axe violation(s): ${a.violations.map((v) => `${v.id}(${v.nodes})`).join(', ')}`
			);
		}
	}

	const r = report.requests;
	if (r.offHost.length) failures.push(`${r.offHost.length} off-host request(s): ${r.offHost.slice(0, 3).join(', ')}`);
	if (r.broken.length) failures.push(`broken request(s): ${r.broken.slice(0, 3).join(', ')}`);
	if (r.count > BUDGETS.requests) failures.push(`${r.count} requests, budget ${BUDGETS.requests}`);

	/*
	 * Regression against the reference, not an absolute weight — and measured
	 * over the WHOLE page rather than the initial load.
	 *
	 * The initial-load figures are not stable enough to compare at a 2%
	 * tolerance. Two lazy images sit exactly at the fold on /branchen/, and
	 * whether their fetch starts before `networkidle` is a race: the same
	 * static reference measured 20 requests in a 41-route run and 22 in a
	 * single-route run minutes later. Comparing those numbers across two
	 * builds compares two coin flips, which is how this route flapped in and
	 * out of its budget all day.
	 *
	 * After the scroll pass every lazy image has loaded, so the totals are
	 * determined by the page rather than by timing, and a port that genuinely
	 * ships more still fails. The initial-load numbers remain in the report and
	 * on the console, and the absolute gates above still use them.
	 */
	if (bases) {
		const allowedBytes = Math.round((bases.fullBytes ?? bases.totalBytes) * BUDGETS.bytesToleranceRatio);
		if (r.fullBytes > allowedBytes) {
			const reference = bases.fullBytes ?? bases.totalBytes;
			failures.push(
				`${Math.round(r.fullBytes / 1024)} KB vs reference ${Math.round(reference / 1024)} KB ` +
					`(+${Math.round(((r.fullBytes - reference) / reference) * 100)}%, tolerance ${Math.round((BUDGETS.bytesToleranceRatio - 1) * 100)}%, whole page)`
			);
		}
		if (r.fullCount > (bases.fullRequests ?? bases.requests) + BUDGETS.requestsTolerance) {
			failures.push(`${r.fullCount} requests vs reference ${bases.fullRequests ?? bases.requests} (whole page)`);
		}
		/*
		 * Relative CLS, with a noise floor at half the absolute budget.
		 *
		 * The floor was 0.01, which is a fifth of the budget and inside the
		 * spread between two machines: identical code and content measured
		 * 42/42 on a developer laptop and failed three routes on a Linux runner
		 * at 0.0122, 0.0127 and 0.0148 — against references of 0.0003 to 0.0069,
		 * where doubling is also meaningless. The baseline was captured on the
		 * laptop, so below this floor the comparison measures font-swap timing
		 * and the runner, not the site.
		 *
		 * The absolute budget below is the real gate, and it still catches any
		 * shift a visitor could notice. A regression that matters shows up well
		 * above 0.025.
		 */
		if (report.cls !== null && bases.cls !== null && report.cls > Math.max(bases.cls * 2, BUDGETS.cls / 2)) {
			failures.push(`CLS ${report.cls} vs reference ${bases.cls}`);
		}
	}
	if (r.largestImage && r.largestImage.bytes > BUDGETS.largestImageBytes) {
		failures.push(
			`largest image ${Math.round(r.largestImage.bytes / 1024)} KB, budget ${BUDGETS.largestImageBytes / 1024} KB (${r.largestImage.url.split('/').pop()})`
		);
	}
	if (report.cls !== null && report.cls > BUDGETS.cls) failures.push(`CLS ${report.cls}, budget ${BUDGETS.cls}`);
	/*
	 * A 404 document makes the browser log a resource-load error no page can
	 * avoid, so console output is only required to be clean where a 200 is
	 * expected. Everywhere else the reference measured zero messages, and so
	 * must this.
	 */
	if ( 200 === route.expectStatus && report.console.length ) {
		failures.push(`${report.console.length} console message(s): ${report.console[0]}`);
	}

	return failures;
}

async function main() {
	let server = null;
	let base;

	if (TARGET === 'static') {
		server = await serveStatic(STATIC_ROOT);
		base = server.url;
	} else {
		base = opt('base') ?? 'http://localhost:8888';
	}

	if (TARGET !== 'static' && existsSync(BASELINE_PATH)) {
		baseline = JSON.parse(readFileSync(BASELINE_PATH, 'utf8'));
		console.log(`baseline loaded: ${baseline.routes.length} route(s) from the static reference`);
	} else if (TARGET !== 'static') {
		console.log('NOTE: no static baseline found — byte and request growth cannot be gated.');
		console.log('      Run `node tools/audit.mjs --target=static --write-baseline` first.');
	}

	let target = TARGET === 'static' ? pageRoutes : routes;
	if (ROUTE_FILTER) target = target.filter((r) => ROUTE_FILTER.includes(r.url));

	console.log(`Audit against ${TARGET} (${base}) — ${target.length} route(s), logged out`);
	console.log('');

	const browser = await chromium.launch();
	const reports = [];
	let failed = 0;

	for (const route of target) {
		const report = await auditRoute(browser, base, route);
		const failures = evaluate(report, route);
		reports.push({ ...report, failures });

		if (failures.length) {
			failed += 1;
			console.log(`  FAIL  ${route.url}`);
			for (const f of failures.slice(0, 6)) console.log(`          - ${f}`);
			if (failures.length > 6) console.log(`          … ${failures.length - 6} more`);
		} else {
			const r = report.requests;
			console.log(
				`  ok    ${route.url.padEnd(44)} ${String(r.count).padStart(2)} req ${String(Math.round(r.totalBytes / 1024)).padStart(4)} KB` +
					`  (scrolled: ${String(r.fullCount).padStart(2)} req ${String(Math.round(r.fullBytes / 1024)).padStart(4)} KB)  CLS ${report.cls}`
			);
		}
	}

	await browser.close();
	if (server) await server.close();

	console.log('');
	console.log(`${reports.length - failed}/${reports.length} route(s) passing${failed ? `, ${failed} failing` : ''}`);

	if (TARGET !== 'static') {
		mkdirSync(path.dirname(WP_REPORT_PATH), { recursive: true });
		writeFileSync(
			WP_REPORT_PATH,
			JSON.stringify(
				{
					target: TARGET,
					generated: new Date().toISOString(),
					passing: reports.length - failed,
					total: reports.length,
					routes: reports.map((r) => ({
						url: r.url,
						failures: r.failures,
						requests: r.requests.count,
						totalBytes: r.requests.totalBytes,
						cls: r.cls,
						axeViolations: r.axe.reduce((n, a) => n + a.violations.length, 0),
					})),
				},
				null,
				2
			)
		);
	}

	if (WRITE_BASELINE) {
		// --write-baseline is only meaningful against the static reference, and
		// --target defaults to `wp`. Without this guard, one forgotten flag
		// rewrites the audited contract with numbers measured from the port —
		// after which every budget compares WordPress against itself and passes
		// by construction. The file records its own target, so refuse rather
		// than silently overwrite.
		if (TARGET !== 'static') {
			console.error(`refusing to write the baseline from --target=${TARGET}.`);
			console.error('The baseline IS the static reference; use --target=static --write-baseline.');
			process.exit(2);
		}

		mkdirSync(path.dirname(BASELINE_PATH), { recursive: true });
		writeFileSync(
			BASELINE_PATH,
			JSON.stringify(
				{
					target: TARGET,
					generated: 'see git history for when this baseline was produced',
					routes: reports.map((r) => ({
						url: r.url,
						requests: r.requests.count,
						totalBytes: r.requests.totalBytes,
						fullRequests: r.requests.fullCount,
						fullBytes: r.requests.fullBytes,
						largestImageBytes: r.requests.largestImage?.bytes ?? 0,
						cls: r.cls,
						axeViolations: r.axe.reduce((n, a) => n + a.violations.length, 0),
					})),
				},
				null,
				2
			)
		);
		console.log(`baseline written: ${path.relative(REPO_ROOT, BASELINE_PATH)}`);
	} else if (existsSync(BASELINE_PATH)) {
		console.log(`(gated against the baseline at ${path.relative(REPO_ROOT, BASELINE_PATH)})`);
	}

	process.exit(failed ? 1 : 0);
}

await main();
