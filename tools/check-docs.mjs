#!/usr/bin/env node
/**
 * Keep the documentation's references resolvable.
 *
 * docs/ carries file citations (`inc/security.php`, `tools/audit.mjs`) and
 * docs/redirect-map.md maps retired URLs onto contract routes. Both rot in
 * silence: a file gets renamed and the citation still reads plausibly, a
 * redirect points at a route that was never created and nobody notices until
 * launch day. security.md pinned line numbers and drifted 25–50 lines out of
 * date within sixteen commits — so this checks what can be checked mechanically:
 *
 *   1. Every repo path cited in backticks in docs/*.md and README.md exists.
 *      Where a citation includes :line or :line-range, the lines are in bounds.
 *   2. Every redirect target in docs/redirect-map.md is a real contract route
 *      (or one of the few core-generated targets that are not routes).
 *
 * It deliberately does NOT assert a cited line contains a given symbol — the
 * docs cite by file and symbol name now, precisely so line drift cannot break
 * them, and a symbol-substring check would fight legitimate rewording.
 */
import { readFileSync, existsSync, readdirSync } from 'node:fs';
import path from 'node:path';
import { REPO_ROOT } from './routes.mjs';

const DOCS_DIR = path.join(REPO_ROOT, 'docs');
const ROUTES_JSON = path.join(REPO_ROOT, 'client-mu-plugins', 'emposo-core', 'data', 'routes.json');

// Base directories a short citation may be relative to, most specific first.
const CITATION_BASES = ['.', 'client-mu-plugins/emposo-core', 'themes/emposo'];

// A file extension makes a backtick token a path worth resolving. Namespaces
// (Emposo\Core), header values (default-src 'self') and versions (6.15.1) have
// none and are skipped.
const CODE_EXTENSIONS = new Set([
	'php', 'mjs', 'js', 'css', 'json', 'md', 'yml', 'yaml', 'neon', 'xml', 'dist', 'sh', 'nvmrc',
]);

// Redirect targets that are legitimately not contract routes: core generates
// the sitemap, and it has no entry in routes.json.
const NON_ROUTE_TARGETS = new Set(['/wp-sitemap.xml']);

/**
 * Resolve a cited path against the repo, trying each known base.
 * Returns the absolute path if found, else null.
 */
function resolveCitation(cited) {
	for (const base of CITATION_BASES) {
		const candidate = path.join(REPO_ROOT, base, cited);
		if (existsSync(candidate)) {
			return candidate;
		}
	}
	return null;
}

/** Every backtick-delimited span in a Markdown string. */
function inlineCodeSpans(text) {
	return [...text.matchAll(/`([^`]+)`/g)].map((m) => m[1]);
}

/** Does a token look like a repo path (has a slash and a known code extension)? */
function looksLikePath(token) {
	if (token.includes('://') || token.includes('@') || token.includes(' ')) {
		return false;
	}
	// Repo citations are relative; a leading slash marks a runtime URL
	// (/xmlrpc.php, /wp-sitemap.xml), which redirect-target checking handles.
	if (token.startsWith('/')) {
		return false;
	}
	const withoutLine = token.replace(/:[0-9]+(-[0-9]+)?$/, '');
	if (!withoutLine.includes('/')) {
		return false;
	}
	const ext = withoutLine.split('.').pop();
	return CODE_EXTENSIONS.has(ext);
}

function checkPathCitations(failures) {
	const docs = readdirSync(DOCS_DIR)
		.filter((f) => f.endsWith('.md'))
		.map((f) => path.join(DOCS_DIR, f));
	docs.push(path.join(REPO_ROOT, 'README.md'));

	for (const doc of docs) {
		if (!existsSync(doc)) {
			continue;
		}
		const rel = path.relative(REPO_ROOT, doc);
		const text = readFileSync(doc, 'utf8');

		for (const span of inlineCodeSpans(text)) {
			if (!looksLikePath(span)) {
				continue;
			}

			const lineMatch = span.match(/:([0-9]+)(?:-([0-9]+))?$/);
			const citedPath = span.replace(/:[0-9]+(-[0-9]+)?$/, '');
			const resolved = resolveCitation(citedPath);

			if (!resolved) {
				failures.push(`${rel}: cited path does not exist: ${span}`);
				continue;
			}

			if (lineMatch) {
				const maxCited = Number(lineMatch[2] ?? lineMatch[1]);
				const lineCount = readFileSync(resolved, 'utf8').split('\n').length;
				if (maxCited > lineCount) {
					failures.push(`${rel}: ${span} cites line ${maxCited}, but the file has ${lineCount}`);
				}
			}
		}
	}
}

function checkRedirectTargets(failures) {
	const map = path.join(DOCS_DIR, 'redirect-map.md');
	if (!existsSync(map)) {
		return;
	}

	const routes = JSON.parse(readFileSync(ROUTES_JSON, 'utf8')).routes;
	const contractUrls = new Set(routes.map((r) => r.url));

	for (const raw of readFileSync(map, 'utf8').split('\n')) {
		const line = raw.trim();
		// Table rows only: "| `/old/` | `/new/` |". Skip headers and separators.
		if (!line.startsWith('|') || line.includes('---') || line.includes('Old')) {
			continue;
		}
		const targets = inlineCodeSpans(line).filter((t) => t.startsWith('/'));
		if (targets.length < 2) {
			continue;
		}
		// The last path in the row is the redirect target; the first is the old URL.
		const target = targets[targets.length - 1];
		if (target.includes('*')) {
			continue; // A pattern, not a single URL.
		}
		if (!contractUrls.has(target) && !NON_ROUTE_TARGETS.has(target)) {
			failures.push(`redirect-map.md: target is not a contract route: ${target}`);
		}
	}
}

function main() {
	const failures = [];
	checkPathCitations(failures);
	checkRedirectTargets(failures);

	if (failures.length) {
		console.error('Documentation reference violations:');
		for (const f of failures) {
			console.error(`  - ${f}`);
		}
		process.exit(1);
	}
	console.log('Documentation references OK (all cited paths resolve; redirect targets are contract routes).');
}

main();
