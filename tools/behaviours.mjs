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
import { readFileSync } from 'node:fs';
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

/** The filter page: industries and projects together on /branchen/ (owner 2026-09-27). */
const FILTER_PAGES = ['/branchen/', '/en/industries/'];

/*
 * Both languages (reference/static docs/i18n.md): the German site and its
 * American English twin under /en/, each with its own filter page, contact
 * page, home, count wording and case-study paths.
 */
// WordPress's own data since the owner took weave-clone off (2026-09-29).
const DATA_DIR = path.join(REPO_ROOT, 'client-mu-plugins', 'emposo-core', 'data');
const CASE_SLUGS = JSON.parse(readFileSync(path.join(DATA_DIR, 'i18n.json'), 'utf8')).caseSlugs;
const LOCALES = [
	{ lang: 'de', home: '/', filter: '/branchen/', contact: '/kontakt/', facts: ['/about-us/', '/karriere/'], words: ['Projekt', 'Projekte'], casePath: (slug) => `/case-studies/${slug}/` },
	{ lang: 'en', home: '/en/', filter: '/en/industries/', contact: '/en/contact/', facts: ['/en/about-us/', '/en/careers/'], words: ['project', 'projects'], casePath: (slug) => `/en/case-studies/${CASE_SLUGS[slug] ?? slug}/` },
];
const localeOf = (route) => (route.startsWith('/en/') ? LOCALES[1] : LOCALES[0]);

/** Filter groups and their URL parameters, as js/06-work.js declares them (B-41). */
const GROUPS = { industry: 'branche', discipline: 'leistung' };

// --------------------------------------------------------------------------
// Filtering: every enabled industry × discipline combination, counts, empty
// state, ARIA, the URL state, and the disabled zero-result chips.
// --------------------------------------------------------------------------
async function testFiltering(page, base, route) {
	const failures = [];
	await page.goto(`${base}${route}`, { waitUntil: 'networkidle' });

	const contract = await page.evaluate((groups) => {
		const grid = document.querySelector('[data-project-grid]');
		if (!grid) return null;
		const tokens = (value) => (value || '').trim().split(/\s+/).filter(Boolean);
		const cards = [...document.querySelectorAll('[data-project]')].map((c) => ({
			href: c.getAttribute('href'),
			industry: tokens(c.dataset.industry),
			discipline: tokens(c.dataset.discipline),
		}));
		const values = {};
		const disabled = {};
		for (const group of Object.keys(groups)) {
			const buttons = [...document.querySelectorAll(`[data-filter-group="${group}"]`)];
			values[group] = buttons.filter((b) => !b.disabled).map((b) => b.dataset.filterValue);
			disabled[group] = buttons.filter((b) => b.disabled).map((b) => b.dataset.filterValue);
		}
		return { cards, values, disabled };
	}, GROUPS);

	if (!contract) return [`${route}: no [data-project-grid] found`];
	if (contract.cards.length === 0) failures.push(`${route}: no [data-project] cards`);
	for (const group of Object.keys(GROUPS)) {
		if (contract.values[group].length === 0) failures.push(`${route}: no enabled ${group} filter buttons`);
		// A disabled chip must be exactly a value no card carries (B-42).
		for (const value of contract.disabled[group]) {
			if (contract.cards.some((c) => c[group].includes(value))) {
				failures.push(`${route}: ${group} chip "${value}" is disabled but ${value} has projects`);
			}
		}
		for (const value of contract.values[group]) {
			if (value !== 'all' && !contract.cards.some((c) => c[group].includes(value))) {
				failures.push(`${route}: ${group} chip "${value}" is enabled but carries no project`);
			}
		}
	}

	// The filter UI ships hidden and is revealed by stripping .js-only, so that
	// it degrades to nothing rather than to dead controls without JS.
	const jsOnlyLeft = await page.locator('.js-only').count();
	if (jsOnlyLeft > 0) failures.push(`${route}: ${jsOnlyLeft} .js-only element(s) still hidden after init`);

	let combinations = 0;

	for (const industry of contract.values.industry) {
		for (const discipline of contract.values.discipline) {
			combinations += 1;

			await page.click(`[data-filter-group="industry"][data-filter-value="${industry}"]`);
			await page.click(`[data-filter-group="discipline"][data-filter-value="${discipline}"]`);

			const expected = contract.cards.filter(
				(c) =>
					(industry === 'all' || c.industry.includes(industry)) &&
					(discipline === 'all' || c.discipline.includes(discipline))
			);

			const state = await page.evaluate((groups) => {
				const pressed = {};
				for (const group of Object.keys(groups)) {
					pressed[group] = [...document.querySelectorAll(`[data-filter-group="${group}"]`)]
						.filter((b) => b.getAttribute('aria-pressed') === 'true')
						.map((b) => b.dataset.filterValue);
				}
				return {
					visible: [...document.querySelectorAll('[data-project]')].filter((c) => !c.hidden).map((c) => c.getAttribute('href')),
					countText: document.querySelector('#project-count')?.textContent?.trim() ?? null,
					emptyHidden: document.querySelector('#project-empty')?.hidden ?? null,
					pressed,
					search: window.location.search,
				};
			}, GROUPS);

			const label = `${route} [${industry}/${discipline}]`;

			const expectedHrefs = expected.map((c) => c.href).sort().join(',');
			const actualHrefs = [...state.visible].sort().join(',');
			if (expectedHrefs !== actualHrefs) {
				failures.push(
					`${label}: visible cards wrong — expected ${expected.length} (${expectedHrefs || 'none'}), got ${state.visible.length} (${actualHrefs || 'none'})`
				);
			}

			// German pluralisation is in the script, so it is part of the contract.
			const word = localeOf(route).words[expected.length === 1 ? 0 : 1];
			if (state.countText !== null && !state.countText.includes(`${expected.length} ${word}`)) {
				failures.push(`${label}: count reads "${state.countText}", expected "${expected.length} ${word}"`);
			}

			if (state.emptyHidden !== null && state.emptyHidden !== expected.length > 0) {
				failures.push(`${label}: empty state ${state.emptyHidden ? 'hidden' : 'shown'} with ${expected.length} result(s)`);
			}

			const selection = { industry, discipline };
			const params = new URLSearchParams(state.search);
			for (const [group, param] of Object.entries(GROUPS)) {
				if (state.pressed[group].join() !== selection[group]) {
					failures.push(`${label}: aria-pressed ${group} is [${state.pressed[group]}], expected [${selection[group]}]`);
				}
				// The selection lives in the URL; "all" is the absence of the parameter.
				const want = selection[group] === 'all' ? null : selection[group];
				if (params.get(param) !== want) {
					failures.push(`${label}: ?${param}= is "${params.get(param)}", expected "${want}"`);
				}
			}
		}
	}

	if (combinations === 0) failures.push(`${route}: filter matrix ran 0 combinations — nothing was verified`);

	return { failures, note: `${combinations} combinations, ${contract.cards.length} cards` };
}

// --------------------------------------------------------------------------
// Card inventory vs the content module — catches a data porting error.
// --------------------------------------------------------------------------
async function testCardInventory(page, base, L) {
	const failures = [];
	const { projects } = JSON.parse(readFileSync(path.join(DATA_DIR, 'site-export.json'), 'utf8'));

	await page.goto(`${base}${L.filter}`, { waitUntil: 'domcontentloaded' });
	const hrefs = await page.evaluate(() =>
		[...document.querySelectorAll('[data-project]')].map((c) => c.getAttribute('href'))
	);

	const expected = new Set(projects.map((p) => L.casePath(p.slug)));
	const actual = new Set(hrefs);

	for (const href of expected) if (!actual.has(href)) failures.push(`missing case-study card: ${href}`);
	for (const href of actual) if (!expected.has(href)) failures.push(`unexpected case-study card: ${href}`);

	return { failures, note: `${expected.size} projects in site-export.json` };
}

// --------------------------------------------------------------------------
// ?branche= / ?leistung= restore the selection; disabled values are ignored.
// --------------------------------------------------------------------------
async function testFilterDeepLinks(page, base, L) {
	const failures = [];
	const route = L.filter;
	await page.goto(`${base}${route}`, { waitUntil: 'domcontentloaded' });
	const chips = await page.evaluate(() =>
		[...document.querySelectorAll('[data-filter-group]')]
			.filter((b) => b.dataset.filterValue !== 'all')
			.map((b) => ({ group: b.dataset.filterGroup, value: b.dataset.filterValue, disabled: b.disabled }))
	);

	for (const chip of chips) {
		const param = GROUPS[chip.group];
		await page.goto(`${base}${route}?${param}=${encodeURIComponent(chip.value)}#referenzen`, { waitUntil: 'networkidle' });
		const pressed = await page.evaluate(
			(group) =>
				[...document.querySelectorAll(`[data-filter-group="${group}"]`)]
					.filter((b) => b.getAttribute('aria-pressed') === 'true')
					.map((b) => b.dataset.filterValue),
			chip.group
		);
		const want = chip.disabled ? 'all' : chip.value;
		if (pressed.join() !== want) {
			failures.push(`?${param}=${chip.value}${chip.disabled ? ' (disabled)' : ''}: pressed [${pressed}], expected [${want}]`);
		}
	}
	return { failures, note: `${chips.length} deep links, ${chips.filter((c) => c.disabled).length} of them disabled` };
}

// --------------------------------------------------------------------------
// ?interesse= preselects the contact form and announces it.
// --------------------------------------------------------------------------
async function testIntentLinks(page, base, L) {
	const failures = [];

	await page.goto(`${base}${L.contact}`, { waitUntil: 'domcontentloaded' });
	const options = await page.evaluate(() =>
		[...document.querySelectorAll('select[name="interest"] option')]
			.map((o) => o.value)
			.filter(Boolean)
	);
	if (options.length === 0) return [`no interest options found on ${L.contact}`];

	for (const value of options) {
		await page.goto(`${base}${L.contact}?interesse=${encodeURIComponent(value)}`, { waitUntil: 'networkidle' });
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
// Mobile menu: opens, Escape closes, and it exposes every destination.
// --------------------------------------------------------------------------
async function testMobileMenu(page, base) {
	const failures = [];
	await page.setViewportSize({ width: 390, height: 844 });
	await page.goto(`${base}/`, { waitUntil: 'networkidle' });

	const menu = page.locator('#mobile-menu');
	if ((await menu.count()) === 0) return ['#mobile-menu not found'];

	await menu.locator(':scope > summary').click();
	if (!(await menu.evaluate((d) => d.open))) failures.push('mobile menu did not open');

	const links = await menu.locator('a').count();
	if (links === 0) failures.push('mobile menu exposes no links');

	// The label must announce state, since the control is a bare summary.
	const label = await menu.locator(':scope > summary').getAttribute('aria-label');
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
async function testCountUp(browser, base, L) {
	const failures = [];

	// Authored values, read from the rendered markup rather than hardcoded.
	const plain = await browser.newContext({ reducedMotion: 'no-preference' });
	const p1 = await plain.newPage();
	await p1.goto(`${base}${L.home}`, { waitUntil: 'networkidle' });
	await p1.locator('.company-facts__value').first().scrollIntoViewIfNeeded();
	await p1.waitForTimeout(1600); // the animation is 900ms; allow for observer latency
	const animated = await p1.evaluate(() =>
		[...document.querySelectorAll('.company-facts__value')].map((e) => e.textContent.trim())
	);
	await plain.close();

	if (animated.length === 0) failures.push(`no .company-facts__value elements on ${L.home}`);

	// The count-up must always land on the authored string — the static markup is
	// the source of truth, so a mid-animation value must never be the end state.
	const reduced = await browser.newContext({ reducedMotion: 'reduce' });
	const p2 = await reduced.newPage();
	await p2.goto(`${base}${L.home}`, { waitUntil: 'networkidle' });
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

	// The row is one component on every page (owner 24-09); the count-up runs
	// only where 07-countup.js is loaded, which parity's script matrix pins.
	// Wherever it appears, it must show the same authored values.
	const ctx = await browser.newContext({ reducedMotion: 'reduce' });
	const p3 = await ctx.newPage();
	for (const route of L.facts) {
		await p3.goto(`${base}${route}`, { waitUntil: 'networkidle' });
		const values = await p3.evaluate(() => [...document.querySelectorAll('.company-facts__value')].map((e) => e.textContent.trim()));
		if (values.length && values.join('|') !== still.join('|')) {
			failures.push(`${route}: company facts read [${values}], expected [${still}]`);
		}
	}
	await ctx.close();

	return { failures, note: `values: ${still.join(', ')}` };
}

// --------------------------------------------------------------------------
// No-JS: filters hidden, content static, page still usable.
// --------------------------------------------------------------------------
async function testNoJs(browser, base, L) {
	const failures = [];
	const ctx = await browser.newContext({ javaScriptEnabled: false });
	const page = await ctx.newPage();

	for (const route of [L.home, L.filter]) {
		await page.goto(`${base}${route}`, { waitUntil: 'domcontentloaded' });
		const state = await page.evaluate(() => ({
			h1: document.querySelectorAll('h1').length,
			cards: document.querySelectorAll('[data-project]').length,
			jsOnly: document.querySelectorAll('.js-only').length,
			navReachable: document.querySelectorAll('.site-nav a, #mobile-menu a').length,
		}));

		if (state.h1 !== 1) failures.push(`${route} (no JS): ${state.h1} <h1>`);
		// The filter UI must stay hidden rather than render dead controls.
		if (route === L.filter && state.jsOnly === 0) {
			failures.push(`${route} (no JS): filter UI is not gated behind .js-only`);
		}
		if (route === L.filter && state.cards === 0) {
			failures.push(`${route} (no JS): no cards in the static markup`);
		}
		// Plain links and a native <details> mobile menu: navigation needs no JS.
		if (state.navReachable === 0) failures.push(`${route} (no JS): navigation links unreachable`);
	}

	await ctx.close();
	return failures;
}

// --------------------------------------------------------------------------
// Language switch: names the current language, opens its menu, Escape closes
// it, and the other language's option lands on the twin page.
// --------------------------------------------------------------------------
async function testLanguageSwitch(page, base) {
	const failures = [];
	await page.setViewportSize({ width: 1440, height: 900 });
	for (const [from, to, current] of [['/portfolio/', '/en/services/', 'Deutsch'], ['/en/services/', '/portfolio/', 'English']]) {
		await page.goto(`${base}${from}`, { waitUntil: 'networkidle' });
		const picker = page.locator('.site-header [data-lang-switch]:not(.lang-switch--menu)');
		if ((await picker.count()) === 0) { failures.push(`${from}: no language switch in the header`); continue; }
		const label = (await picker.locator(':scope > summary').innerText()).trim();
		if (!label.endsWith(current)) failures.push(`${from}: switch reads "${label}", expected "${current}"`);
		await picker.locator(':scope > summary').click();
		if (!(await picker.evaluate((d) => d.open))) failures.push(`${from}: switch did not open`);
		await page.keyboard.press('Escape');
		if (await picker.evaluate((d) => d.open)) failures.push(`${from}: Escape did not close the switch`);
		await picker.locator(':scope > summary').click();
		await Promise.all([page.waitForURL(`**${to}`), picker.locator('[data-lang-option]').click()]);
		const path = new URL(page.url()).pathname;
		if (path !== to) failures.push(`${from}: switched to ${path}, expected ${to}`);
	}
	return { failures, note: 'DE -> EN and EN -> DE' };
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
	for (const L of LOCALES) {
		await run(`card inventory (${L.lang})`, () => testCardInventory(page, base, L));
		await run(`?branche= / ?leistung= deep links (${L.lang})`, () => testFilterDeepLinks(page, base, L));
		await run(`?interesse= intent links (${L.lang})`, () => testIntentLinks(page, base, L));
		await run(`count-up (${L.lang})`, () => testCountUp(browser, base, L));
		await run(`no-JS (${L.lang})`, () => testNoJs(browser, base, L));
	}
	await run('language switch', () => testLanguageSwitch(page, base));
	await run('mobile menu', () => testMobileMenu(page, base));
	await run('skip link', () => testSkipLink(page, base));

	await run('console clean', () => (consoleMessages.length ? consoleMessages : []));

	await browser.close();
	if (server) await server.close();

	const failed = results.filter((r) => r.failures.length).length;
	console.log('');
	console.log(`${results.length - failed}/${results.length} behaviour group(s) passing${failed ? `, ${failed} failing` : ''}`);
	process.exit(failed ? 1 : 0);
}

await main();
