#!/usr/bin/env node
/**
 * Turn the literal copy of a page template into editable fields (phase 2 of
 * the editorial work, owner 2026-09-29).
 *
 *   node tools/fieldify.mjs pages/about-us sections/02-hero ...
 *   node tools/fieldify.mjs --check pages/about-us     (report, write nothing)
 *
 * For each template under themes/emposo/parts/<id>.php it
 *
 * 1. masks every `<?php … ?>` block with a same-length run of U+E000, so the
 *    HTML parser sees plain markup and all offsets still map 1:1;
 * 2. finds the text leaves: the outermost elements with text and no block
 *    descendants (`<p>Text <a>Link</a></p>` is one field). A leaf whose
 *    content contains PHP falls back to its direct text nodes;
 * 3. replaces each leaf's trimmed content with `<?php emposo_f( 'id.NN' ); ?>`
 *    (surrounding whitespace stays in the template, byte for byte), and each
 *    `emposo_the_picture( 'key'` with an image field;
 * 4. writes the defaults to client-mu-plugins/emposo-core/data/fields/<id>.json.
 *
 * The defaults are the copy source from then on; the rendered HTML is
 * byte-identical as long as no field is overridden. Skipped: <nav>
 * (breadcrumbs), aria-hidden subtrees, svg/script/style, form controls.
 */
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import path from 'node:path';
import { parseFragment } from 'parse5';
import { REPO_ROOT } from './routes.mjs';

const PARTS = path.join(REPO_ROOT, 'themes', 'emposo', 'parts');
const OUT = path.join(REPO_ROOT, 'client-mu-plugins', 'emposo-core', 'data', 'fields');
const MASK = '';

const BLOCK = new Set(['address', 'article', 'aside', 'blockquote', 'details', 'dialog', 'div', 'dl', 'dt', 'dd', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'li', 'main', 'nav', 'ol', 'p', 'pre', 'section', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'ul', 'summary', 'picture']);
const SKIP = new Set(['nav', 'script', 'style', 'svg', 'template', 'noscript', 'input', 'select', 'textarea', 'option', 'picture', 'img', 'form']);
const LABELS = {
	h1: 'Hauptüberschrift', h2: 'Überschrift', h3: 'Zwischenüberschrift', h4: 'Zwischenüberschrift', h5: 'Zwischenüberschrift',
	p: 'Absatz', li: 'Listenpunkt', a: 'Link-Text', button: 'Button-Text', summary: 'Aufklapp-Text', dt: 'Begriff', dd: 'Beschreibung',
	figcaption: 'Bildunterschrift', blockquote: 'Zitat', td: 'Tabellenzelle', th: 'Tabellenkopf', label: 'Beschriftung', strong: 'Hervorhebung',
	span: 'Text', em: 'Text', small: 'Kleingedrucktes', div: 'Text', article: 'Text',
};

const args = process.argv.slice(2);
const CHECK = args.includes('--check');
const ids = args.filter((a) => !a.startsWith('--'));
if (!ids.length) {
	console.error('usage: node tools/fieldify.mjs [--check] pages/about-us sections/02-hero …');
	process.exit(2);
}

const attr = (node, name) => node.attrs?.find((a) => a.name === name)?.value;
// Visible text of a snippet, for labels and group names only (never output as HTML).
const plain = (html) => {
	let text = html.replace(/<br\s*\/?>/gi, ' ');
	let previous;
	do {
		previous = text;
		text = text.replace(/<[^<>]*>/g, '');
	} while (text !== previous);
	return text.replace(/[<>]/g, '').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/\s+/g, ' ').trim();
};

function hasBlockDescendant(node) {
	for (const child of node.childNodes ?? []) {
		if (child.tagName && (BLOCK.has(child.tagName) || hasBlockDescendant(child))) return true;
	}
	return false;
}

function hasSkipped(node) {
	for (const child of node.childNodes ?? []) {
		if (child.tagName && (SKIP.has(child.tagName) || attr(child, 'aria-hidden') === 'true' || hasSkipped(child))) return true;
	}
	return false;
}

function hasText(node) {
	for (const child of node.childNodes ?? []) {
		if (child.nodeName === '#text' && child.value.replace(new RegExp(MASK, 'g'), '').trim()) return true;
		if (child.tagName && !SKIP.has(child.tagName) && hasText(child)) return true;
	}
	return false;
}

function fieldify(id) {
	const file = path.join(PARTS, `${id}.php`);
	const source = readFileSync(file, 'utf8');
	if (source.includes('emposo_f(')) {
		console.log(`${id}: already converted, skipped`);
		return null;
	}

	// The file docblock (the first PHP block) stays; mask every PHP block.
	const blocks = [...source.matchAll(/<\?php[\s\S]*?\?>/g)];
	let masked = source;
	for (const b of blocks) masked = masked.slice(0, b.index) + MASK.repeat(b[0].length) + masked.slice(b.index + b[0].length);

	const tree = parseFragment(masked, { sourceCodeLocationInfo: true });
	const edits = [];
	const fields = [];
	let group = 'Seitenkopf';
	let n = 0;
	const key = () => `${id}.${String(++n).padStart(2, '0')}`;

	const firstHeading = (node) => {
		for (const child of node.childNodes ?? []) {
			if (!child.tagName) continue;
			if (/^h[1-3]$/.test(child.tagName)) {
				const l = child.sourceCodeLocation;
				return l?.startTag && l?.endTag ? plain(masked.slice(l.startTag.endOffset, l.endTag.startOffset).replace(new RegExp(MASK, 'g'), '')) : '';
			}
			const found = firstHeading(child);
			if (found) return found;
		}
		return '';
	};

	const addField = (start, end, tag, cls = '') => {
		// Trim inside the range so indentation and newlines stay in the template.
		let s = start;
		let e = end;
		while (s < e && /\s/.test(source[s])) s++;
		while (e > s && /\s/.test(source[e - 1])) e--;
		if (s >= e) return;
		const value = source.slice(s, e);
		if (value.includes(MASK) || /<\?php/.test(value)) return;
		// Step numbers ("01", "02") are layout, not copy.
		if (/^\d{1,3}\.?$/.test(plain(value))) return;
		const k = key();
		const label = /\beyebrow\b/.test(cls) ? 'Dachzeile' : (LABELS[tag] ?? 'Text');
		fields.push({ key: k, group, label, type: /<[a-z]/i.test(value) ? 'html' : 'text', default: value });
		edits.push({ start: s, end: e, text: `<?php emposo_f( '${k}' ); ?>` });
	};

	const walk = (node) => {
		for (const child of node.childNodes ?? []) {
			if (child.nodeName === '#text') {
				const loc = child.sourceCodeLocation;
				if (loc && child.value.replace(new RegExp(MASK, 'g'), '').trim()) {
					// A direct text run: split around masked PHP so each run is its own field.
					const raw = masked.slice(loc.startOffset, loc.endOffset);
					let offset = loc.startOffset;
					for (const part of raw.split(new RegExp(`(${MASK}+)`))) {
						if (!part.startsWith(MASK) && part.trim()) addField(offset, offset + part.length, node.tagName ?? 'div');
						offset += part.length;
					}
				}
				continue;
			}
			if (!child.tagName || SKIP.has(child.tagName) || attr(child, 'aria-hidden') === 'true') continue;
			if (child.tagName === 'section') {
				const heading = firstHeading(child);
				if (heading) group = heading.slice(0, 70);
			}

			const loc = child.sourceCodeLocation;
			const inner = loc?.startTag && loc?.endTag ? masked.slice(loc.startTag.endOffset, loc.endTag.startOffset) : null;
			if (inner !== null && hasText(child) && !hasBlockDescendant(child) && !hasSkipped(child) && !inner.includes(MASK)) {
				addField(loc.startTag.endOffset, loc.endTag.startOffset, child.tagName, attr(child, 'class') ?? '');
				continue;
			}
			walk(child);
		}
	};
	walk(tree);

	// Images: the manifest key of every emposo_the_picture() call.
	let img = 0;
	for (const b of blocks) {
		const m = /emposo_the_picture\(\s*'([a-z0-9-]+)'/.exec(b[0]);
		if (!m) continue;
		const k = `${id}.img${++img}`;
		const at = b.index + m.index + m[0].indexOf(`'${m[1]}'`);
		fields.push({ key: k, group: 'Bilder', label: `Bild ${img}`, type: 'image', default: m[1] });
		edits.push({ start: at, end: at + m[1].length + 2, text: `emposo_f_image( '${k}' )` });
	}

	edits.sort((a, b) => b.start - a.start);
	let out = source;
	for (const e of edits) out = out.slice(0, e.start) + e.text + out.slice(e.end);

	const summary = `${id}: ${fields.filter((f) => f.type !== 'image').length} text field(s), ${img} image field(s)`;
	if (CHECK) {
		console.log(summary);
		for (const f of fields) console.log(`  ${f.key}  [${f.group}] ${f.label} (${f.type}): ${plain(f.default).slice(0, 80)}`);
		return fields;
	}

	writeFileSync(file, out);
	mkdirSync(OUT, { recursive: true });
	const json = { template: id, locale: id.includes('/en/') ? 'en' : 'de', store: id.endsWith('/404') ? 'global' : 'page', fields };
	writeFileSync(path.join(OUT, `${id.replace(/\//g, '__')}.json`), `${JSON.stringify(json, null, '\t')}\n`);
	console.log(summary);
	return fields;
}

for (const id of ids) fieldify(id);
