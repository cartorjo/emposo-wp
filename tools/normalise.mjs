/**
 * Normalise HTML so the static build and the WordPress render are comparable.
 *
 * Every transform here corrects a difference that is *known to be inert* —
 * a path prefix, a cache-busting query string, an attribute WordPress adds for
 * its own bookkeeping, attribute order. Anything not listed here is a real
 * difference and must fail, which is why this file is deliberately small and
 * why new entries need a reason in parity.config.json rather than a quiet
 * addition here.
 *
 * Notably absent: whitespace collapsing. The ported templates are verbatim, so
 * whitespace should already match, and several `.reference-card` / grid rows
 * are flex children where a stray text node shifts layout. Whitespace-only
 * differences are reported in their own bucket instead of being erased.
 */

/** Attributes WordPress adds to its own <link>/<script> tags, carrying no meaning. */
const INERT_ATTRS = new Set(['id', 'media', 'type']);

const INERT_ATTR_VALUES = {
	media: new Set(['all']),
	type: new Set(['text/css', 'text/javascript']),
};

/**
 * Tokenise a start tag's attributes.
 *
 * Hand-rolled rather than regex-per-attribute because attribute values legally
 * contain `>` (the inline SVG favicon data URI in <head> does), which breaks
 * any pattern that assumes the first `>` ends the tag.
 *
 * @param {string} source Inner text of a start tag, after the tag name.
 * @returns {Array<{name: string, value: string|null, quote: string}>}
 */
export function parseAttributes(source) {
	const attrs = [];
	let i = 0;

	while (i < source.length) {
		while (i < source.length && /\s/.test(source[i])) i += 1;
		if (i >= source.length) break;

		let name = '';
		while (i < source.length && !/[\s=]/.test(source[i])) {
			name += source[i];
			i += 1;
		}
		if (name === '') break;

		while (i < source.length && /\s/.test(source[i])) i += 1;

		if (source[i] !== '=') {
			attrs.push({ name, value: null, quote: '' });
			continue;
		}
		i += 1;
		while (i < source.length && /\s/.test(source[i])) i += 1;

		const quote = source[i] === '"' || source[i] === "'" ? source[i] : '';
		let value = '';
		if (quote) {
			i += 1;
			while (i < source.length && source[i] !== quote) {
				value += source[i];
				i += 1;
			}
			i += 1;
		} else {
			while (i < source.length && !/\s/.test(source[i])) {
				value += source[i];
				i += 1;
			}
		}
		attrs.push({ name, value, quote: quote || '"' });
	}

	return attrs;
}

function serialiseAttributes(attrs) {
	return attrs
		.map(({ name, value, quote }) => (value === null ? name : `${name}=${quote}${value}${quote}`))
		.join(' ');
}

/**
 * Drop an inert attribute only where it is genuinely inert: an `id` on a
 * stylesheet or script WordPress registered, `media="all"`, a legacy `type`.
 * An `id` anywhere else is content and must survive — the anchor checks and
 * the duplicate-id rule both depend on it.
 */
function stripInertAttributes(tagName, attrs) {
	const isAsset = tagName === 'link' || tagName === 'script';
	if (!isAsset) return attrs;

	return attrs.filter(({ name, value }) => {
		if (!INERT_ATTRS.has(name)) return true;
		if (name === 'id') return !/-(css|js)$/.test(value ?? '');
		return !INERT_ATTR_VALUES[name]?.has(value ?? '');
	});
}

/**
 * Rewrite theme-relative asset URLs onto the static build's paths.
 *
 * Done on the whole document rather than per-attribute so it also covers
 * srcset candidate lists and url() inside inline style attributes.
 */
export function rewriteAssetPaths(html, themeBase = '/wp-content/themes/emposo') {
	const base = themeBase.replace(/\/$/, '');

	return html
		.split(`${base}/assets/css/`).join('/css/')
		.split(`${base}/assets/js/`).join('/js/')
		.split(`${base}/assets/`).join('/assets/')
		.split(`${base}/`).join('/');
}

/** Strip cache-busting query strings; filemtime makes them environment-specific. */
export function stripVersionQuery(html) {
	return html.replace(/([?&])ver=[^"'&\s>]*/g, (match, sep) => (sep === '?' ? '' : ''));
}

/**
 * Canonicalise every start tag: strip inert attributes, sort the rest by name,
 * and normalise self-closing syntax.
 *
 * Attribute order is not meaningful in HTML, and WordPress emits a different
 * order than the static templates. Sorting makes order differences invisible
 * while keeping added, removed and changed attributes fully visible.
 */
export function canonicaliseTags(html) {
	let out = '';
	let i = 0;

	while (i < html.length) {
		const lt = html.indexOf('<', i);
		if (lt === -1) {
			out += html.slice(i);
			break;
		}
		out += html.slice(i, lt);

		// Pass comments, doctype and CDATA through untouched.
		if (html.startsWith('<!--', lt)) {
			const end = html.indexOf('-->', lt);
			const stop = end === -1 ? html.length : end + 3;
			out += html.slice(lt, stop);
			i = stop;
			continue;
		}
		if (html[lt + 1] === '!' || html[lt + 1] === '?') {
			const end = html.indexOf('>', lt);
			const stop = end === -1 ? html.length : end + 1;
			out += html.slice(lt, stop);
			i = stop;
			continue;
		}

		const nameMatch = /^<\/?([a-zA-Z][a-zA-Z0-9-]*)/.exec(html.slice(lt));
		if (!nameMatch) {
			out += '<';
			i = lt + 1;
			continue;
		}

		// Find the tag's end, skipping any `>` inside a quoted value.
		let j = lt + nameMatch[0].length;
		let quote = '';
		while (j < html.length) {
			const ch = html[j];
			if (quote) {
				if (ch === quote) quote = '';
			} else if (ch === '"' || ch === "'") {
				quote = ch;
			} else if (ch === '>') {
				break;
			}
			j += 1;
		}
		if (j >= html.length) {
			out += html.slice(lt);
			break;
		}

		const tagName = nameMatch[1].toLowerCase();
		const isClosing = html[lt + 1] === '/';
		let inner = html.slice(lt + nameMatch[0].length, j);
		const selfClosing = /\/\s*$/.test(inner);
		if (selfClosing) inner = inner.replace(/\/\s*$/, '');

		if (isClosing) {
			out += `</${tagName}>`;
		} else {
			let attrs = parseAttributes(inner);
			attrs = stripInertAttributes(tagName, attrs);
			attrs.sort((a, b) => (a.name < b.name ? -1 : a.name > b.name ? 1 : 0));
			const serialised = serialiseAttributes(attrs);
			out += `<${tagName}${serialised ? ` ${serialised}` : ''}>`;
		}

		i = j + 1;
	}

	return out;
}

/**
 * Apply every normalisation, in the order the transforms depend on.
 *
 * Path rewriting runs before tag canonicalisation so `?ver=` stripping sees
 * already-rewritten URLs, and attribute sorting operates on final values.
 *
 * @param {string} html
 * @param {{themeBase?: string}} [options]
 */
export function normalise(html, options = {}) {
	let out = html;
	out = rewriteAssetPaths(out, options.themeBase ?? '/wp-content/themes/emposo');
	out = stripVersionQuery(out);
	out = canonicaliseTags(out);
	return out;
}

/** Collapse runs of whitespace — used only to classify a diff as whitespace-only. */
export function collapseWhitespace(html) {
	return html.replace(/\s+/g, ' ').replace(/>\s+</g, '><').trim();
}
