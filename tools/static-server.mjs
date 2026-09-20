/**
 * Minimal static server for the pinned reference build.
 *
 * Exists so the reference can be audited through a real HTTP stack, exactly as
 * WordPress is — file:// URLs behave differently for lazy loading, srcset
 * selection and fetchpriority, so measuring one over file:// and the other over
 * HTTP would not be a comparison.
 *
 * Hand-rolled rather than pulling in `serve`, for one substantive reason: the
 * AVIF content type. The reference ships a serve.json whose entire purpose is
 * to send `Content-Type: image/avif`, because the bundled mime table predates
 * AVIF and a wrong type makes browsers reject the <source type="image/avif">
 * and fall back to JPEG — which silently adds ~150 KB and blows the 200 KB
 * largest-image budget. Encoding that here keeps the requirement visible in
 * code rather than in a config file nobody reads, and production hosts must
 * send it too.
 */
import { createServer } from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import path from 'node:path';

const MIME = {
	'.html': 'text/html; charset=utf-8',
	'.css': 'text/css; charset=utf-8',
	'.js': 'text/javascript; charset=utf-8',
	'.mjs': 'text/javascript; charset=utf-8',
	'.json': 'application/json; charset=utf-8',
	'.svg': 'image/svg+xml',
	'.jpg': 'image/jpeg',
	'.jpeg': 'image/jpeg',
	'.png': 'image/png',
	'.webp': 'image/webp',
	'.avif': 'image/avif',
	'.woff2': 'font/woff2',
	'.woff': 'font/woff',
	'.ttf': 'font/ttf',
	'.ico': 'image/x-icon',
	'.txt': 'text/plain; charset=utf-8',
	'.xml': 'application/xml; charset=utf-8',
};

/**
 * Resolve a URL pathname to a file inside root, or null if it escapes.
 *
 * Containment needs the separator. A bare `resolved.startsWith(root)` lets a
 * sibling whose name shares the root's prefix escape — `/srv/foobar/x` starts
 * with `/srv/foo` — so `..` segments that land next to the root are served.
 * Comparing against `root + sep` (and allowing root itself) closes that.
 *
 * @param {string} root     Directory being served.
 * @param {string} pathname Decoded URL pathname.
 * @returns {string|null} Absolute path inside root, or null.
 */
export function resolveWithinRoot(root, pathname) {
	// Directory-per-page output: /about-us/ -> about-us/index.html.
	const withIndex = pathname.endsWith('/') ? `${pathname}index.html` : pathname;
	const rootResolved = path.resolve(root);
	const resolved = path.resolve(path.join(root, withIndex.replace(/^\/+/, '')));
	if (resolved !== rootResolved && !resolved.startsWith(rootResolved + path.sep)) {
		return null;
	}
	return resolved;
}

/**
 * @param {string} root Directory to serve.
 * @param {number} port
 * @returns {Promise<{url: string, close: () => Promise<void>}>}
 */
export async function serveStatic(root, port = 0) {
	const server = createServer(async (req, res) => {
		try {
			const url = new URL(req.url ?? '/', 'http://localhost');
			const pathname = decodeURIComponent(url.pathname);

			const resolved = resolveWithinRoot(root, pathname);
			if (!resolved) {
				res.writeHead(403).end('Forbidden');
				return;
			}

			const info = await stat(resolved).catch(() => null);
			if (!info || !info.isFile()) {
				// Mirror the static host's behaviour: 404.html with a real 404.
				const notFound = await readFile(path.join(root, '404.html')).catch(() => null);
				res.writeHead(404, { 'Content-Type': MIME['.html'] });
				res.end(notFound ?? 'Not found');
				return;
			}

			const body = await readFile(resolved);
			res.writeHead(200, {
				'Content-Type': MIME[path.extname(resolved).toLowerCase()] ?? 'application/octet-stream',
				'Content-Length': body.length,
				'Cache-Control': 'no-store',
			});
			res.end(body);
		} catch (error) {
			res.writeHead(500).end(String(error));
		}
	});

	await new Promise((resolve) => server.listen(port, '127.0.0.1', resolve));
	const { port: actual } = server.address();

	return {
		url: `http://127.0.0.1:${actual}`,
		close: () => new Promise((resolve) => server.close(resolve)),
	};
}
