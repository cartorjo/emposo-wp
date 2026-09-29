/**
 * The route contract: client-mu-plugins/emposo-core/data/routes.json.
 *
 * Until 2026-09-29 this read the pinned static reference (pages.mjs), so the
 * route list could not drift from what parity compared against. The owner
 * took weave-clone off that day ("i took off weave-clone"); WordPress is now
 * the source, and routes.json is its own hand-maintained contract. The
 * reference/static submodule stays frozen at its last pin for history only.
 */
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const here = path.dirname(fileURLToPath(import.meta.url));

export const REPO_ROOT = path.resolve(here, '..');
export const STATIC_ROOT = path.join(REPO_ROOT, 'reference', 'static');

const contract = JSON.parse(readFileSync(path.join(REPO_ROOT, 'client-mu-plugins', 'emposo-core', 'data', 'routes.json'), 'utf8'));

/**
 * `out` keeps the static build's file naming (`about-us/index.html`) for the
 * tools that key reports by it. The two not_found entries are carried with
 * `kind: '404'` and probed by requesting a path that cannot exist.
 */
function toRoute(entry) {
	const isNotFound = entry.objectType === 'not_found';
	const out = isNotFound
		? (entry.locale === 'en' ? 'en/404.html' : '404.html')
		: `${entry.url.replace(/^\//, '')}index.html`;

	return {
		out,
		url: entry.url,
		locale: entry.locale ?? 'de',
		twinUrl: entry.twinUrl ?? null,
		staticFile: out,
		kind: isNotFound ? '404' : 'page',
		expectStatus: isNotFound ? 404 : 200,
		title: entry.title,
		description: entry.description,
		nav: entry.nav,
		navGroup: entry.navGroup ?? null,
		navExact: entry.navExact !== false,
		bodyClass: entry.bodyClass,
		scripts: entry.scripts ?? [],
		content: entry.content,
	};
}

export const routes = contract.routes.map(toRoute);

/** Addressable URLs only — excludes the 404 template. */
export const pageRoutes = routes.filter((r) => r.kind === 'page');

export function routeByUrl(url) {
	return routes.find((r) => r.url === url);
}

export const require_ = createRequire(import.meta.url);
