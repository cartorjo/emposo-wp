/**
 * Self-test for the normaliser.
 *
 * The parity gate is only as trustworthy as this file: a normaliser that erases
 * too much turns a regression into a pass. Each case below pins one transform,
 * and the last group pins what must NOT be erased.
 */
import assert from 'node:assert/strict';
import {
	normalise,
	parseAttributes,
	canonicaliseTags,
	rewriteAssetPaths,
	stripVersionQuery,
	collapseWhitespace,
} from './normalise.mjs';

let pass = 0;
function t(name, fn) {
	try {
		fn();
		pass += 1;
	} catch (error) {
		console.error(`FAIL: ${name}\n  ${error.message}`);
		process.exitCode = 1;
	}
}

// --- attribute tokenizer ---------------------------------------------------
t('parses quoted, unquoted and valueless attributes', () => {
	const attrs = parseAttributes('href="/a" hidden data-x=1 aria-label=\'Menü öffnen\'');
	assert.deepEqual(attrs.map((a) => [a.name, a.value]), [
		['href', '/a'],
		['hidden', null],
		['data-x', '1'],
		['aria-label', 'Menü öffnen'],
	]);
});

t('a value containing > does not end the tag (the inline SVG favicon)', () => {
	const html = `<link href="data:image/svg+xml,%3Csvg viewBox='0 0 32 32'%3E%3C/svg%3E" rel="icon">`;
	const out = canonicaliseTags(html);
	assert.ok(out.includes('%3C/svg%3E'), 'data URI survived');
	assert.equal((out.match(/<link/g) || []).length, 1, 'tag not split');
});

// --- asset paths -----------------------------------------------------------
t('rewrites theme css/js/asset paths onto static paths', () => {
	assert.equal(rewriteAssetPaths('/wp-content/themes/emposo/assets/css/site.css'), '/css/site.css');
	assert.equal(rewriteAssetPaths('/wp-content/themes/emposo/assets/js/00-core.js'), '/js/00-core.js');
	assert.equal(
		rewriteAssetPaths('/wp-content/themes/emposo/assets/supplied/hero-flow-1600.jpg'),
		'/assets/supplied/hero-flow-1600.jpg'
	);
});

t('rewrites every candidate in a srcset', () => {
	const out = rewriteAssetPaths(
		'srcset="/wp-content/themes/emposo/assets/supplied/a-640.avif 640w, /wp-content/themes/emposo/assets/supplied/a-1600.avif 1600w"'
	);
	assert.equal(out, 'srcset="/assets/supplied/a-640.avif 640w, /assets/supplied/a-1600.avif 1600w"');
});

// --- version query ---------------------------------------------------------
t('strips ?ver= but keeps other query args', () => {
	assert.equal(stripVersionQuery('/css/site.css?ver=1758012345'), '/css/site.css');
	assert.equal(stripVersionQuery('/kontakt/?interesse=Karriere'), '/kontakt/?interesse=Karriere');
});

// --- inert attributes ------------------------------------------------------
t("drops WordPress's -css/-js ids, media=all and legacy type on assets", () => {
	const wp = canonicaliseTags(
		`<link rel="stylesheet" id="emposo-site-css" href="/css/site.css" media="all" type="text/css">`
	);
	const stat = canonicaliseTags(`<link rel="stylesheet" href="/css/site.css">`);
	assert.equal(wp, stat);
});

t('keeps a content id on a non-asset element', () => {
	const out = canonicaliseTags('<main id="main" tabindex="-1">');
	assert.ok(out.includes('id="main"'), 'content id must survive');
});

t('keeps an id that merely ends in -css on a non-asset element', () => {
	const out = canonicaliseTags('<section id="custom-css">');
	assert.ok(out.includes('id="custom-css"'));
});

// --- attribute order + self-closing ---------------------------------------
t('attribute order stops mattering', () => {
	assert.equal(
		canonicaliseTags('<img alt="x" src="/a.jpg" width="10">'),
		canonicaliseTags('<img width="10" src="/a.jpg" alt="x">')
	);
});

t('XHTML-style self-closing normalises to plain', () => {
	assert.equal(canonicaliseTags('<meta charset="utf-8" />'), canonicaliseTags('<meta charset="utf-8">'));
});

// --- what must NOT be erased ----------------------------------------------
t('a changed attribute VALUE still fails', () => {
	assert.notEqual(
		normalise('<body class="subpage wrap-anywhere">'),
		normalise('<body class="wrap-anywhere">')
	);
});

t('an ADDED class still fails', () => {
	assert.notEqual(
		normalise('<body class="wrap-anywhere">'),
		normalise('<body class="wrap-anywhere page-id-7">')
	);
});

t('a missing data-* hook still fails', () => {
	assert.notEqual(
		normalise('<a class="reference-card" data-project data-outcome="transform">'),
		normalise('<a class="reference-card" data-outcome="transform">')
	);
});

t('a dropped element still fails', () => {
	assert.notEqual(
		normalise('<h1>A</h1><p>B</p>'),
		normalise('<h1>A</h1>')
	);
});

t('changed text content still fails', () => {
	assert.notEqual(normalise('<p>Über 70 %</p>'), normalise('<p>70 %</p>'));
});

t('whitespace is NOT collapsed by normalise', () => {
	assert.notEqual(normalise('<span>a</span> <span>b</span>'), normalise('<span>a</span><span>b</span>'));
	// ...but is detectable as whitespace-only via the separate helper.
	assert.equal(
		collapseWhitespace(normalise('<span>a</span> <span>b</span>')),
		collapseWhitespace(normalise('<span>a</span><span>b</span>'))
	);
});

t('comments pass through unchanged', () => {
	const html = '<!-- Temporary preview build: remove this only when launch SEO settings are approved. -->';
	assert.equal(normalise(html), html);
});

t('doctype passes through unchanged', () => {
	assert.ok(normalise('<!DOCTYPE html>\n<html lang="de">').startsWith('<!DOCTYPE html>'));
});

t('idempotent', () => {
	const html = '<link href="/css/site.css?ver=9" id="x-css" media="all" rel="stylesheet">';
	assert.equal(normalise(normalise(html)), normalise(html));
});

console.log(`normaliser: ${pass} assertions passed`);
