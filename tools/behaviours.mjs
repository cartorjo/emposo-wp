#!/usr/bin/env node
/**
 * Interaction contracts — the gate DOM parity cannot provide.
 *
 * A broken enqueue, a lost `.js-only` class, or a filter whose data attributes
 * survived but whose script did not, all leave the markup byte-identical. These
 * tests drive a real browser instead.
 *
 * Every expectation is derived from the page's own data attributes or from the
 * reference's content module, never hardcoded — so the tests cannot drift from
 * the site, and they fail if the *data* was ported wrongly as well as if the
 * behaviour was.
 *
 * Usage:
 *   node tools/behaviours.mjs --target=static
 *   node tools/behaviours.mjs --target=wp [--base=http://localhost:8888]
 */
import path from 'node:path';
import { chromium } from 'playwright';
import { STATIC_ROOT, REPO_ROOT } from './routes.mjs';
import { serveStatic } from './static-server.mjs';

const args = process.argv.slice(2);
const opt = (n) => args.find((a) => a.startsWith(`--${n}=`))?.split('=').slice(1).join('=');
const TARGET = opt('target') ?? 'wp';
const ONLY = opt('only');

const results = [];
function record(name, failures, note = '') {
	results.push({ name, failures, note });
	const status = failures.length ? 'FAIL' : 'ok  ';
	console.log(`  ${status}  ${name}${note ? ` — ${note}` : ''}`);
	for (const f of failures.slice(0, 6)) console.log(`          - ${f}`);
	if (failures.length > 6) console.log(`          … ${failures.length - 6} more`);
}

/** The filter pages, and the routes each behaviour needs. */
const FILTER_PAGES = ['/case-studies/', '/branchen/'];

// --------------------------------------------------------------------------
// Filtering: every industry × outcome combination, counts, empty state, ARIA.
// --------------------------------------------------------------------------
async function testFiltering(page, base, route) {
	const failures = [];
	await page.goto(`${base}${route}`, { waitUntil: 'networkidle' });

	const contract = await page.evaluate(() => {
		const grid = document.querySelector('[data-project-grid]');
		if (!grid) return null;
		const cards = [...document.querySelectorAll('[data-project]')].map((c) => ({
			href: c.getAttribute('href'),
			industry: (c.dataset.industry || '').trim().split(/\s+/).filter(Boolean),
			outcome: (c.dataset.outcome || '').trim(),
		}));
		const values = (group) =>
			[...document.querySelectorAll(`[data-filter-group="${group}"]`)].map((b) => b.dataset.filterValue);
		return { cards, industries: values('industry'), outcomes: values('outcome') };
	});

	if (!contract) return [`${route}: no [data-project-grid] found`];
	if (contract.cards.length === 0) failures.push(`${route}: no [data-project] cards`);
	if (contract.industries.length === 0) failures.push(`${route}: no industry filter buttons`);

	// The filter UI ships hidden and is revealed by stripping .js-only, so that
	// it degrades to nothing rather than to dead controls without JS.
	const jsOnlyLeft = await page.locator('.js-only').count();
	if (jsOnlyLeft > 0) failures.push(`${route}: ${jsOnlyLeft} .js-only element(s) still hidden after init`);

	let combinations = 0;

	for (const industry of contract.industries) {
		for (const outcome of contract.outcomes) {
			combinations += 1;

			await page.click(`[data-filter-group="industry"][data-filter-value="${industry}"]`);
			await page.click(`[data-filter-group="outcome"][data-filter-value="${outcome}"]`);

			const expected = contract.cards.filter(
				(c) =>
					(industry === 'all' || c.industry.includes(industry)) &&
					(outcome === 'all' || c.outcome === outcome)
			);

			const state = await page.evaluate(() => ({
				visible: [...document.querySelectorAll('[data-project]')]
					.filter((c) => !c.hidden)
					.map((c) => c.getAttribute('href')),
				countText: document.querySelector('#project-count')?.textContent?.trim() ?? null,
				emptyHidden: document.querySelector('#project-empty')?.hidden ?? null,
				pressed: {
					industry: [...document.querySelectorAll('[data-filter-group="industry"]')]
						.filter((b) => b.getAttribute('aria-pressed') === 'true')
						.map((b) => b.dataset.filterValue),
					outcome: [...document.querySelectorAll('[data-filter-group="outcome"]')]
						.filter((b) => b.getAttribute('aria-pressed') === 'true')
						.map((b) => b.dataset.filterValue),
				},
			}));

			const label = `${route} [${industry}/${outcome}]`;

			const expectedHrefs = expected.map((c) => c.href).sort().join(',');
			const actualHrefs = [...state.visible].sort().join(',');
			if (expectedHrefs !== actualHrefs) {
				failures.push(
					`${label}: visible cards wrong — expected ${expected.length} (${expectedHrefs || 'none'}), got ${state.visible.length} (${actualHrefs || 'none'})`
				);
			}

			// German pluralisation is in the script, so it is part of the contract.
			const word = expected.length === 1 ? 'Projekt' : 'Projekte';
			if (state.countText !== null && !state.countText.includes(`${expected.length} ${word}`)) {
				failures.push(`${label}: count reads "${state.countText}", expected "${expected.length} ${word}"`);
			}

			if (state.emptyHidden !== null && state.emptyHidden !== expected.length > 0) {
				failures.push(
					`${label}: empty state ${state.emptyHidden ? 'hidden' : 'shown'} with ${expected.length} result(s)`
				);
			}

			if (state.pressed.industry.join() !== industry) {
				failures.push(`${label}: aria-pressed industry is [${state.pressed.industry}], expected [${industry}]`);
			}
			if (state.pressed.outcome.join() !== outcome) {
				failures.push(`${label}: aria-pressed outcome is [${state.pressed.outcome}], expected [${outcome}]`);
			}
		}
	}

	return { failures, note: `${combinations} combinations, ${contract.cards.length} cards` };
}

// --------------------------------------------------------------------------
// Card inventory vs the content module — catches a data porting error.
// --------------------------------------------------------------------------
async function testCardInventory(page, base) {
	const failures = [];
	const { projects } = await import(path.join(STATIC_ROOT, 'content', 'site-data.mjs'));

	await page.goto(`${base}/case-studies/`, { waitUntil: 'domcontentloaded' });
	const hrefs = await page.evaluate(() =>
		[...document.querySelectorAll('[data-project]')].map((c) => c.getAttribute('href'))
	);

	const expected = new Set(projects.map((p) => `/case-studies/${p.slug}/`));
	const actual = new Set(hrefs);

	for (const href of expected) if (!actual.has(href)) failures.push(`missing case-study card: ${href}`);
	for (const href of actual) if (!expected.has(href)) failures.push(`unexpected case-study card: ${href}`);

	return { failures, note: `${expected.size} projects in site-data` };
}

// --------------------------------------------------------------------------
// ?branche= deep link preselects the industry filter.
// --------------------------------------------------------------------------
async function testBrancheDeepLink(page, base) {
	const failures = [];
	const { industries } = await import(path.join(STATIC_ROOT, 'content', 'site-data.mjs'));

	for (const industry of industries) {
		const token = industry.filter.split(/\s+/)[0];
		await page.goto(`${base}/case-studies/?branche=${token}#referenzen`, { waitUntil: 'networkidle' });

		const pressed = await page.evaluate(() =>
			[...document.querySelectorAll('[data-filter-group="industry"]')]
				.filter((b) => b.getAttribute('aria-pressed') === 'true')
				.map((b) => b.dataset.filterValue)
		);
		if (pressed.join() !== token) {
			failures.push(`?branche=${token}: pressed [${pressed}], expected [${token}]`);
		}
	}
	return { failures, note: `${industries.length} industry deep links` };
}

// --------------------------------------------------------------------------
// ?interesse= preselects the contact form and announces it.
// --------------------------------------------------------------------------
async function testIntentLinks(page, base) {
	const failures = [];

	await page.goto(`${base}/kontakt/`, { waitUntil: 'domcontentloaded' });
	const options = await page.evaluate(() =>
		[...document.querySelectorAll('select[name="interest"] option')]
			.map((o) => o.value)
			.filter(Boolean)
	);
	if (options.length === 0) return [`no interest options found on /kontakt/`];

	for (const value of options) {
		await page.goto(`${base}/kontakt/?interesse=${encodeURIComponent(value)}`, { waitUntil: 'networkidle' });
		const state = await page.evaluate(() => ({
			selected: document.querySelector('select[name="interest"]')?.value ?? null,
			hintHidden: document.querySelector('[data-contact-hint]')?.hidden ?? null,
			hintText: document.querySelector('[data-contact-hint]')?.textContent?.trim() ?? '',
		}));

		if (state.selected !== value) {
			failures.push(`?interesse=${value}: select is "${state.selected}"`);
		}
		if (state.hintHidden !== false) {
			failures.push(`?interesse=${value}: hint not revealed`);
		} else if (!state.hintText.includes(value)) {
			failures.push(`?interesse=${value}: hint does not name the selection ("${state.hintText}")`);
		}
	}
	return { failures, note: `${options.length} interest values` };
}

// --------------------------------------------------------------------------
// Megamenus: one at a time, Escape closes and restores focus, outside click.
// --------------------------------------------------------------------------
async function testMegamenu(page, base) {
	const failures = [];
	await page.setViewportSize({ width: 1440, height: 900 });
	await page.goto(`${base}/`, { waitUntil: 'networkidle' });

	const groups = await page.locator('details.site-nav__group').count();
	if (groups < 2) return [`expected 2 megamenu groups, found ${groups}`];

	const summaries = page.locator('details.site-nav__group > summary');

	await summaries.nth(0).click();
	if (!(await page.locator('details.site-nav__group').nth(0).evaluate((d) => d.open))) {
		failures.push('first megamenu did not open');
	}

	// Opening the second must close the first: native `name=` is avoided because
	// of the iOS 16 floor, so this is enforced in JS and has to be tested.
	await summaries.nth(1).click();
	const openStates = await page.locator('details.site-nav__group').evaluateAll((els) => els.map((e) => e.open));
	if (openStates.filter(Boolean).length !== 1) {
		failures.push(`expected exactly one open megamenu, got ${openStates.filter(Boolean).length}`);
	}
	if (openStates[0] !== false) failures.push('opening the second megamenu did not close the first');

	// Escape closes and returns focus to the summary that opened it.
	await page.keyboard.press('Escape');
	const afterEscape = await page.evaluate(() => ({
		anyOpen: [...document.querySelectorAll('details.site-nav__group')].some((d) => d.open),
		focusIsSummary: document.activeElement?.tagName?.toLowerCase() === 'summary',
	}));
	if (afterEscape.anyOpen) failures.push('Escape did not close the megamenu');
	if (!afterEscape.focusIsSummary) failures.push('Escape did not restore focus to the summary');

	// Clicking outside closes. The click has to land genuinely outside the open
	// panel: targeting <main> at the top-left aims *behind* the panel, which
	// Playwright correctly refuses as an intercepted click.
	await summaries.nth(0).click();
	const panel = page.locator('details.site-nav__group').nth(0).locator('.mega-panel');
	const box = (await panel.count()) ? await panel.boundingBox() : null;
	const belowPanel = box ? Math.min(box.y + box.height + 80, 860) : 700;
	await page.mouse.click(700, belowPanel);
	if (await page.locator('details.site-nav__group').nth(0).evaluate((d) => d.open)) {
		failures.push('outside click did not close the megamenu');
	}

	const panelLinks = await page.locator('details.site-nav__group').nth(0).locator('a').count();
	return { failures, note: `${groups} groups, ${panelLinks} links in the first panel` };
}

// --------------------------------------------------------------------------
// Mobile menu: opens, Escape closes, and it exposes every destination.
// --------------------------------------------------------------------------
async function testMobileMenu(page, base) {
	const failures = [];
	await page.setViewportSize({ width: 390, height: 844 });
	await page.goto(`${base}/`, { waitUntil: 'networkidle' });

	const menu = page.locator('#mobile-menu');
	if ((await menu.count()) === 0) return ['#mobile-menu not found'];

	await menu.locator('summary').click();
	if (!(await menu.evaluate((d) => d.open))) failures.push('mobile menu did not open');

	const links = await menu.locator('a').count();
	if (links === 0) failures.push('mobile menu exposes no links');

	// The label must announce state, since the control is a bare summary.
	const label = await menu.locator('summary').getAttribute('aria-label');
	if (!label) failures.push('mobile menu summary has no aria-label');

	await page.keyboard.press('Escape');
	if (await menu.evaluate((d) => d.open)) failures.push('Escape did not close the mobile menu');

	return { failures, note: `${links} links` };
}

// --------------------------------------------------------------------------
// Skip link focuses main — with Lenis patched over it.
// --------------------------------------------------------------------------
async function testSkipLink(page, base) {
	const failures = [];
	await page.setViewportSize({ width: 1440, height: 900 });
	await page.goto(`${base}/`, { waitUntil: 'networkidle' });

	// It must also be the first focusable element: anything injected ahead of it
	// (core's global-styles SVG filters, for instance) breaks the contract.
	await page.keyboard.press('Tab');
	const first = await page.evaluate(() => ({
		cls: document.activeElement?.className ?? '',
		href: document.activeElement?.getAttribute?.('href') ?? '',
	}));
	if (!String(first.cls).includes('skip-link')) {
		failures.push(`first focusable element is not the skip link (got class "${first.cls}")`);
	}

	await page.keyboard.press('Enter');
	const target = await page.evaluate(() => ({
		id: document.activeElement?.id ?? '',
		tag: document.activeElement?.tagName ?? '',
	}));
	if (target.id !== 'main' || target.tag !== 'MAIN') {
		failures.push(`skip link focused <${target.tag} id="${target.id}">, expected <MAIN id="main">`);
	}
	return failures;
}

// --------------------------------------------------------------------------
// Count-up: ends on the exact authored strings; static under reduced motion.
// --------------------------------------------------------------------------
async function testCountUp(browser, base) {
	const failures = [];

	// Authored values, read from the rendered markup rather than hardcoded.
	const plain = await browser.newContext({ reducedMotion: 'no-preference' });
	const p1 = await plain.newPage();
	await p1.goto(`${base}/`, { waitUntil: 'networkidle' });
	await p1.locator('.company-facts__value').first().scrollIntoViewIfNeeded();
	await p1.waitForTimeout(1600); // the animation is 900ms; allow for observer latency
	const animated = await p1.evaluate(() =>
		[...document.querySelectorAll('.company-facts__value')].map((e) => e.textContent.trim())
	);
	await plain.close();

	if (animated.length === 0) failures.push('no .company-facts__value elements on /');

	// The count-up must always land on the authored string — the static markup is
	// the source of truth, so a mid-animation value must never be the end state.
	const reduced = await browser.newContext({ reducedMotion: 'reduce' });
	const p2 = await reduced.newPage();
	await p2.goto(`${base}/`, { waitUntil: 'networkidle' });
	await p2.locator('.company-facts__value').first().scrollIntoViewIfNeeded();
	await p2.waitForTimeout(1600);
	const still = await p2.evaluate(() =>
		[...document.querySelectorAll('.company-facts__value')].map((e) => e.textContent.trim())
	);
	await reduced.close();

	if (animated.join('|') !== still.join('|')) {
		failures.push(
			`count-up end state differs from the reduced-motion state: [${animated}] vs [${still}]`
		);
	}

	// And only the homepage animates: the same numbers appear in two other
	// markups on /about-us/ and /karriere/ that must stay static.
	const ctx = await browser.newContext();
	const p3 = await ctx.newPage();
	let elsewhere = 0;
	for (const route of ['/about-us/', '/karriere/']) {
		await p3.goto(`${base}${route}`, { waitUntil: 'domcontentloaded' });
		elsewhere += await p3.locator('.company-facts__value').count();
	}
	await ctx.close();
	if (elsewhere !== 0) {
		failures.push(`.company-facts__value appears on ${elsewhere} element(s) outside / — those numbers would start animating`);
	}

	return { failures, note: `values: ${still.join(', ')}` };
}

// --------------------------------------------------------------------------
// No-JS: filters hidden, content static, page still usable.
// --------------------------------------------------------------------------
async function testNoJs(browser, base) {
	const failures = [];
	const ctx = await browser.newContext({ javaScriptEnabled: false });
	const page = await ctx.newPage();

	for (const route of ['/', '/case-studies/']) {
		await page.goto(`${base}${route}`, { waitUntil: 'domcontentloaded' });
		const state = await page.evaluate(() => ({
			h1: document.querySelectorAll('h1').length,
			cards: document.querySelectorAll('[data-project]').length,
			jsOnly: document.querySelectorAll('.js-only').length,
			megaReachable: document.querySelectorAll('details.site-nav__group a').length,
		}));

		if (state.h1 !== 1) failures.push(`${route} (no JS): ${state.h1} <h1>`);
		// The filter UI must stay hidden rather than render dead controls.
		if (route === '/case-studies/' && state.jsOnly === 0) {
			failures.push(`${route} (no JS): filter UI is not gated behind .js-only`);
		}
		if (route === '/case-studies/' && state.cards === 0) {
			failures.push(`${route} (no JS): no cards in the static markup`);
		}
		// Native <details> means the megamenu works without JS.
		if (state.megaReachable === 0) failures.push(`${route} (no JS): megamenu links unreachable`);
	}

	await ctx.close();
	return failures;
}

// --------------------------------------------------------------------------
async function main() {
	let server = null;
	let base;

	if (TARGET === 'static') {
		server = await serveStatic(STATIC_ROOT);
		base = server.url;
	} else {
		base = opt('base') ?? 'http://localhost:8888';
	}

	console.log(`Behaviours against ${TARGET} (${base})`);
	console.log('');

	const browser = await chromium.launch();
	const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
	const page = await context.newPage();

	// Any console message is a failure: the reference measured zero.
	const consoleMessages = [];
	page.on('console', (m) => consoleMessages.push(`${m.type()}: ${m.text()}`));
	page.on('pageerror', (e) => consoleMessages.push(`pageerror: ${e.message}`));

	const run = async (name, fn) => {
		if (ONLY && !name.includes(ONLY)) return;
		try {
			const out = await fn();
			const failures = Array.isArray(out) ? out : out.failures;
			const note = Array.isArray(out) ? '' : (out.note ?? '');
			record(name, failures, note);
		} catch (error) {
			record(name, [`threw: ${error.message}`]);
		}
	};

	for (const route of FILTER_PAGES) {
		await run(`filtering ${route}`, () => testFiltering(page, base, route));
	}
	await run('card inventory', () => testCardInventory(page, base));
	await run('?branche= deep links', () => testBrancheDeepLink(page, base));
	await run('?interesse= intent links', () => testIntentLinks(page, base));
	await run('megamenu', () => testMegamenu(page, base));
	await run('mobile menu', () => testMobileMenu(page, base));
	await run('skip link', () => testSkipLink(page, base));
	await run('count-up', () => testCountUp(browser, base));
	await run('no-JS', () => testNoJs(browser, base));

	await run('console clean', () => (consoleMessages.length ? consoleMessages : []));

	await browser.close();
	if (server) await server.close();

	const failed = results.filter((r) => r.failures.length).length;
	console.log('');
	console.log(`${results.length - failed}/${results.length} behaviour group(s) passing${failed ? `, ${failed} failing` : ''}`);
	process.exit(failed ? 1 : 0);
}

await main();
