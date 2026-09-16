/**
 * The route contract, read straight from the pinned reference.
 *
 * pages.mjs is the static build's own manifest, so importing it rather than
 * duplicating a routes.json means the route list, titles, descriptions, nav
 * state, body classes and per-route script lists cannot drift from the thing
 * we are asserting parity against. It is an ES module and these tools are ESM,
 * so no parsing is involved.
 */
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const here = path.dirname(fileURLToPath(import.meta.url));

export const REPO_ROOT = path.resolve(here, '..');
export const STATIC_ROOT = path.join(REPO_ROOT, 'reference', 'static');

const manifest = (await import(path.join(STATIC_ROOT, 'pages.mjs'))).default;

/**
 * `out` is a filesystem path (`about-us/index.html`); the served URL drops the
 * `index.html`. `404.html` is the one entry that is not an addressable route in
 * WordPress — it becomes 404.php, served for every unmatched path — so it is
 * carried with `kind: '404'` and probed by requesting a path that cannot exist.
 */
function toRoute(page) {
	const isNotFound = page.out === '404.html';
	const url = page.out === 'index.html'
		? '/'
		: `/${page.out.replace(/index\.html$/, '')}`;

	return {
		out: page.out,
		url: isNotFound ? '/__parity-404__/' : url,
		staticFile: page.out,
		kind: isNotFound ? '404' : 'page',
		expectStatus: isNotFound ? 404 : 200,
		title: page.title,
		description: page.description,
		nav: page.nav,
		navGroup: page.navGroup ?? null,
		navExact: page.navExact !== false,
		bodyClass: page.bodyClass,
		scripts: page.scripts ?? [],
		content: page.content,
	};
}

export const routes = manifest.map(toRoute);

/** Addressable URLs only — excludes the 404 template. */
export const pageRoutes = routes.filter((r) => r.kind === 'page');

export function routeByUrl(url) {
	return routes.find((r) => r.url === url);
}

export const require_ = createRequire(import.meta.url);
