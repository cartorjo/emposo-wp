/**
 * Per-document invariants.
 *
 * The first eight are ported from reference/static/tools/check-content.mjs, so
 * the WordPress build is held to the same contract the static build already
 * passes. The rest are WordPress-specific: they assert that core is not
 * emitting anything the static <head> does not have, which turns the
 * core-cleanup hook inventory from a hopeful list into a per-page test.
 */
import { existsSync, realpathSync, readFileSync } from 'node:fs';
import path from 'node:path';

/**
 * Markers that mean WordPress is emitting something the static build does not.
 * Each is either an extra request, a third-party origin, unlayered CSS that
 * would outrank the theme's @layer cascade, or head noise.
 */
export const HEAD_POLLUTION = [
	['api.w.org', 'REST API discovery link'],
	['s.w.org', 'third-party origin (breaks the zero-third-party rule)'],
	['wp-emoji', 'emoji detection script or styles'],
	['name="generator"', 'generator meta'],
	['json+oembed', 'oEmbed discovery link'],
	['xmlrpc.php?rsd', 'RSD link'],
	['wlwmanifest', 'Windows Live Writer manifest'],
	['rel="shortlink"', 'shortlink'],
	['wp-block-library', 'core block library CSS'],
	['global-styles-inline-css', 'UNLAYERED global styles — outranks the @layer cascade'],
	['classic-theme-styles', 'classic theme styles'],
	['speculationrules', 'WP 6.8 speculative loading rules'],
	['id="admin-bar', 'admin bar (adds requests and CLS, shifts the sticky header)'],
	['wp-custom-css', 'Customizer Additional CSS — unlayered'],
	['type="application/rss+xml"', 'feed link'],
	['secure.gravatar.com', 'third-party avatar origin'],
	['wp-embed.min.js', 'wp-embed script'],
];

const PHP_ERROR_MARKERS = [
	'<?php', '<?=', 'Fatal error', 'Parse error',
	'Warning:', 'Notice:', 'Deprecated:', 'Array to string conversion',
];

/**
 * @typedef {Object} CheckContext
 * @property {string}        html        Raw document.
 * @property {Object}        route       Route record from routes.mjs.
 * @property {Set<string>}   routeUrls   Every addressable URL, for link resolution.
 * @property {string}        assetRoot   Directory that site-absolute asset paths resolve against.
 * @property {(u: string) => string|null} readTarget Fetch another document's HTML, for anchors.
 */

/** Run every invariant. Returns an array of human-readable failures. */
export function runChecks(ctx) {
	const failures = [];
	const add = (message) => failures.push(message);

	checkIds(ctx, add);
	checkSingleH1(ctx, add);
	checkUnresolvedTemplates(ctx, add);
	checkSupersededBranding(ctx, add);
	checkPlaceholderLinks(ctx, add);
	checkBodyClass(ctx, add);
	checkSkipLinkOrder(ctx, add);
	checkMain(ctx, add);
	checkScriptMatrix(ctx, add);
	checkNoindex(ctx, add);
	checkHeadPollution(ctx, add);

	return failures;
}

function checkIds({ html }, add) {
	const ids = [...html.matchAll(/\bid="([^"]+)"/g)].map((m) => m[1]);
	const seen = new Set();
	const dupes = new Set();
	for (const id of ids) {
		if (seen.has(id)) dupes.add(id);
		seen.add(id);
	}
	if (dupes.size) add(`duplicate id(s): ${[...dupes].join(', ')}`);
}

function checkSingleH1({ html }, add) {
	const count = [...html.matchAll(/<h1\b/gi)].length;
	if (count !== 1) add(`expected exactly one <h1>, found ${count}`);
}

function checkUnresolvedTemplates({ html }, add) {
	if (/\{\{/.test(html)) add('unresolved {{token}}');
	if (/<!--\s*(content|partial):/.test(html)) add('unresolved <!-- content:/partial: --> include');
	for (const marker of PHP_ERROR_MARKERS) {
		if (html.includes(marker)) add(`PHP leaked into output: ${JSON.stringify(marker)}`);
	}
}

function checkSupersededBranding({ html }, add) {
	// Both are explicit owner decisions: "Hays-Gruppe" was removed per the
	// workbook, and the diagonal arrow was replaced by a straight one.
	if (/Hays-Gruppe/.test(html)) add('superseded branding "Hays-Gruppe"');
	if (/↗/.test(html)) add('superseded arrow glyph "↗" (must be "→")');
}

function checkPlaceholderLinks({ html }, add) {
	if (/\bhref="#"/.test(html)) add('placeholder href="#"');
}

function checkBodyClass({ html, route }, add) {
	const match = /<body[^>]*\sclass="([^"]*)"/.exec(html);
	if (!match) {
		add('no class attribute on <body>');
		return;
	}
	const actual = match[1].trim().split(/\s+/).sort().join(' ');
	const expected = route.bodyClass.trim().split(/\s+/).sort().join(' ');
	if (actual !== expected) {
		add(`body class is "${match[1]}", expected exactly "${route.bodyClass}"`);
	}
}

function checkSkipLinkOrder({ html }, add) {
	const skip = html.indexOf('class="skip-link"');
	const header = html.search(/<header\b/i);
	const main = html.search(/<main\b/i);

	if (skip === -1) {
		add('missing skip link');
		return;
	}
	if (header !== -1 && skip > header) {
		add('skip link must precede <header> (it has to skip the navigation)');
	}
	if (main !== -1 && skip > main) {
		add('skip link must precede <main>');
	}
	// The banner landmark must not be inside main.
	if (header !== -1 && main !== -1 && header > main) {
		add('<header> must render outside and before <main>');
	}
}

function checkMain({ html }, add) {
	if (!/<main\b[^>]*\bid="main"/.test(html)) add('missing <main id="main">');
	if (!/<main\b[^>]*\btabindex="-1"/.test(html)) {
		add('<main> must carry tabindex="-1" so the skip link can focus it');
	}
}

function checkScriptMatrix({ html, route }, add) {
	const srcs = [...html.matchAll(/<script\b[^>]*\bsrc="([^"]+)"/g)].map((m) => m[1]);
	const found = new Set();
	for (const src of srcs) {
		const m = /\/js\/([0-9a-z-]+)\.js/.exec(src);
		if (m) found.add(m[1]);
		if (/lenis(\.min)?\.js/.test(src)) found.add('lenis');
	}

	if (!found.has('lenis')) add('vendored Lenis not loaded');

	for (const expected of route.scripts) {
		if (!found.has(expected)) add(`missing script ${expected}.js (per pages.mjs)`);
	}
	for (const actual of found) {
		if (actual === 'lenis') continue;
		if (!route.scripts.includes(actual)) add(`unexpected script ${actual}.js (not in pages.mjs)`);
	}

	// A single script without defer silently downgrades its dependencies to
	// render-blocking, which costs LCP and TBT with no visible error.
	for (const tag of html.matchAll(/<script\b[^>]*\bsrc="[^"]+"[^>]*>/g)) {
		if (!/\bdefer\b/.test(tag[0])) add(`script printed without defer: ${tag[0].slice(0, 120)}`);
	}
}

function checkNoindex({ html }, add) {
	const metas = [...html.matchAll(/<meta\b[^>]*\bname="robots"[^>]*>/gi)];
	if (metas.length === 0) {
		add('missing <meta name="robots"> — the preview noindex must stay until launch is approved');
		return;
	}
	if (metas.length > 1) add(`${metas.length} robots meta tags (core is probably emitting its own)`);

	const content = /content="([^"]*)"/.exec(metas[0])?.[1] ?? '';
	const normalised = content.toLowerCase().replace(/\s+/g, '');
	if (normalised !== 'noindex,nofollow') {
		add(`robots content is "${content}", expected exactly "noindex, nofollow"`);
	}
}

function checkHeadPollution({ html }, add) {
	for (const [marker, why] of HEAD_POLLUTION) {
		if (html.includes(marker)) add(`core output present: ${marker} (${why})`);
	}
}

/**
 * Structural comparison against the reference document.
 *
 * These localise a difference far better than a line number, and they catch two
 * classes of regression a line diff reports badly: a lost `aria-labelledby`
 * (identical rendering, worse semantics) and a purged CSS class (identical DOM,
 * different appearance).
 *
 * Section labelling is compared rather than asserted absolutely, because the
 * static site is not uniform: hand-authored pages label every <section>, while
 * the three generated detail-page renderers in content/render.mjs label none.
 * That is shipped behaviour with axe clean, so the contract is "match the
 * reference", not "label everything".
 */
export function compareStructure(expectedHtml, actualHtml) {
	const failures = [];

	const sections = (html) => {
		const all = [...html.matchAll(/<section\b[^>]*>/g)].map((m) => m[0]);
		return { total: all.length, labelled: all.filter((s) => /\saria-label(ledby)?="/.test(s)).length };
	};
	const a = sections(expectedHtml);
	const b = sections(actualHtml);
	if (a.total !== b.total) failures.push(`<section> count: expected ${a.total}, got ${b.total}`);
	if (a.labelled !== b.labelled) {
		failures.push(`labelled <section> count: expected ${a.labelled}, got ${b.labelled}`);
	}

	const multiset = (html, re) => {
		const counts = new Map();
		for (const m of html.matchAll(re)) {
			const key = m[1];
			counts.set(key, (counts.get(key) ?? 0) + 1);
		}
		return counts;
	};

	const diffMultiset = (label, expectedCounts, actualCounts) => {
		const keys = new Set([...expectedCounts.keys(), ...actualCounts.keys()]);
		const missing = [];
		const extra = [];
		for (const key of keys) {
			const e = expectedCounts.get(key) ?? 0;
			const g = actualCounts.get(key) ?? 0;
			if (g < e) missing.push(`${key}${e - g > 1 ? ` x${e - g}` : ''}`);
			if (g > e) extra.push(`${key}${g - e > 1 ? ` x${g - e}` : ''}`);
		}
		if (missing.length) failures.push(`${label} missing: ${missing.slice(0, 12).join(', ')}${missing.length > 12 ? ` (+${missing.length - 12})` : ''}`);
		if (extra.length) failures.push(`${label} unexpected: ${extra.slice(0, 12).join(', ')}${extra.length > 12 ? ` (+${extra.length - 12})` : ''}`);
	};

	diffMultiset('id', multiset(expectedHtml, /\bid="([^"]+)"/g), multiset(actualHtml, /\bid="([^"]+)"/g));

	const classTokens = (html) => {
		const counts = new Map();
		for (const m of html.matchAll(/\bclass="([^"]*)"/g)) {
			for (const token of m[1].trim().split(/\s+/)) {
				if (!token) continue;
				counts.set(token, (counts.get(token) ?? 0) + 1);
			}
		}
		return counts;
	};
	diffMultiset('class token', classTokens(expectedHtml), classTokens(actualHtml));

	return failures;
}

// --- link and asset resolution (needs I/O, so kept separate) ---------------

/** Hosts that must never appear in shipped markup, whatever the path. */
const DEV_HOSTS = /^(?:localhost|127\.0\.0\.1|0\.0\.0\.0|\[::1\])$/i;

/** Resolve every local href/src/srcset and every in-page anchor. */
export function checkLinks(ctx) {
	const { html, route, routeUrls, assetRoot, readTarget, siteOrigin = '', themeBase } = ctx;
	const failures = [];

	/** Validate one site-relative path as an asset or a known route. */
	const checkLocal = (pathname, href, hash) => {
		if (isAssetPath(pathname)) {
			verifyAsset(pathname, assetRoot, failures);
		} else if (!routeUrls.has(pathname)) {
			failures.push(`link to unknown route: ${href}`);
		}

		if (hash && routeUrls.has(pathname)) {
			const targetHtml = pathname === route.url ? html : readTarget?.(pathname);
			checkAnchor(targetHtml, hash, href, failures, pathname);
		}
	};

	const hrefs = [...html.matchAll(/\b(?:href|src)="([^"]+)"/g)].map((m) => m[1]);
	for (const href of hrefs) {
		/*
		 * Absolute http(s) URLs used to be skipped outright, which left a real
		 * hole: WordPress renders absolute URLs for everything it enqueues, and
		 * the parity normaliser strips the site origin before comparing — so an
		 * absolute URL pointing at a file that does not exist, or at a developer
		 * machine, satisfied every check in this repository.
		 *
		 * Same-origin absolutes are therefore rewritten the way parity rewrites
		 * them and then validated as local paths. Any other localhost-shaped
		 * host is a portability bug and fails on sight. Genuinely external
		 * origins are left alone — a gate must not depend on the network.
		 */
		if (/^https?:/.test(href)) {
			let parsed;
			try {
				parsed = new URL(href);
			} catch {
				failures.push(`unparseable URL: ${href}`);
				continue;
			}

			if (siteOrigin && href.startsWith(siteOrigin)) {
				/*
				 * Strip the origin and the theme prefix only — NOT the full
				 * reference rewrite. assetRoot on a WordPress run is the theme
				 * directory, so `/assets/js/00-core.js` resolves and the
				 * reference-shaped `/js/00-core.js` does not. Getting this
				 * wrong reported every script on every route as missing.
				 */
				const base = (themeBase ?? '').replace(/\/$/, '');
				let pathname = decodeURIComponent(parsed.pathname);

				if (base && pathname.startsWith(base)) {
					pathname = pathname.slice(base.length);
				}

				checkLocal(pathname, href, parsed.hash);
			} else if (DEV_HOSTS.test(parsed.hostname)) {
				failures.push(`hardcoded local URL in shipped markup: ${href}`);
			}

			continue;
		}

		if (/^(?:mailto:|tel:|data:|#|javascript:)/.test(href)) {
			if (href.startsWith('#')) checkAnchor(html, href, route.url, failures, null);
			continue;
		}

		const url = new URL(href, `https://local.test${route.url}`);
		checkLocal(decodeURIComponent(url.pathname), href, url.hash);
	}

	for (const [, srcset] of html.matchAll(/\bsrcset="([^"]+)"/g)) {
		for (const candidate of srcset.split(',')) {
			const src = candidate.trim().split(/\s+/)[0];
			if (!src || /^(?:https?:|data:)/.test(src)) continue;
			verifyAsset(decodeURIComponent(new URL(src, 'https://local.test/').pathname), assetRoot, failures);
		}
	}

	return failures;
}

function isAssetPath(pathname) {
	return /\.(css|js|jpe?g|png|webp|avif|svg|woff2?|ttf|ico|json|txt|xml)$/i.test(pathname);
}

function verifyAsset(pathname, assetRoot, failures) {
	const target = path.join(assetRoot, pathname.replace(/^\//, ''));
	if (!existsSync(target)) {
		failures.push(`missing asset: ${pathname}`);
		return;
	}
	// existsSync is case-insensitive on APFS; a Linux host would 404.
	try {
		if (realpathSync.native(target) !== target) {
			failures.push(`wrong-case asset path: ${pathname}`);
		}
	} catch {
		failures.push(`unresolvable asset path: ${pathname}`);
	}
}

function checkAnchor(targetHtml, hash, href, failures, pathname) {
	if (!targetHtml) {
		if (pathname) failures.push(`cannot verify anchor ${href} (target not fetched)`);
		return;
	}
	const id = decodeURIComponent(hash.replace(/^#/, ''));
	if (!id) return;
	if (!targetHtml.includes(`id="${id}"`) && !targetHtml.includes(`name="${id}"`)) {
		failures.push(`missing anchor target: ${href}`);
	}
}

/**
 * Every @font-face url() in the fonts stylesheet must resolve, case-sensitively.
 *
 * Takes only the stylesheet path: font URLs resolve relative to the CSS file,
 * not the site root. That is precisely why moving the stylesheet silently 404s
 * all five faces — the paths still look right, they just resolve from a
 * different directory — and every text metric shifts as it falls back to
 * system-ui.
 */
export function checkFontFaces(cssPath) {
	const failures = [];
	if (!existsSync(cssPath)) return [`fonts stylesheet missing: ${cssPath}`];

	const css = readFileSync(cssPath, 'utf8');
	const urls = [...css.matchAll(/url\(\s*['"]?([^'")]+)['"]?\s*\)/g)].map((m) => m[1]);
	if (urls.length === 0) failures.push('fonts stylesheet declares no url()');

	for (const url of urls) {
		// Relative to the stylesheet, which is why a moved CSS file silently
		// 404s every face and falls back to system-ui, shifting all text metrics.
		const target = path.resolve(path.dirname(cssPath), url.split('?')[0]);
		if (!existsSync(target)) {
			failures.push(`@font-face url() does not resolve: ${url}`);
			continue;
		}
		try {
			if (realpathSync.native(target) !== target) failures.push(`@font-face wrong-case path: ${url}`);
		} catch {
			failures.push(`@font-face unresolvable: ${url}`);
		}
	}
	return failures;
}
