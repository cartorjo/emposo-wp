#!/usr/bin/env node
/**
 * Self-tests for the parity harness itself.
 *
 * The harness is the thing every other guarantee stands on, so its own
 * fidelity needs a gate. These three cases each failed before the fixes that
 * landed with them, and each would regress silently — the symptom is a wrong
 * verdict, not an error. Pure functions, asserted here rather than left as
 * comments, because a comment does not fail a build.
 */
import { canonicaliseTags } from './normalise.mjs';
import { resolveWithinRoot } from './static-server.mjs';
import { resultLabel } from './parity.mjs';
import path from 'node:path';

const failures = [];

function check(name, actual, expected) {
	if (actual !== expected) {
		failures.push(`${name}: expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`);
	}
}

// 1. Raw-text elements: a `<` inside <script>/<style> is content, not a tag.
// Before the fix, canonicaliseTags parsed `<b` as a tag and rewrote it.
check(
	'canonicaliseTags leaves script content verbatim',
	canonicaliseTags('<script>if (a<b) go()</script>'),
	'<script>if (a<b) go()</script>'
);
check(
	'canonicaliseTags leaves style content verbatim',
	canonicaliseTags('<style>a<b{color:red}</style>'),
	'<style>a<b{color:red}</style>'
);
// It must still canonicalise real tags around raw-text elements.
check(
	'canonicaliseTags still sorts attributes on ordinary tags',
	canonicaliseTags('<img src="a.jpg" alt="x">'),
	'<img alt="x" src="a.jpg">'
);

// 2. Path containment: a sibling sharing the root's name prefix must not escape.
// Before the fix, startsWith without a separator served /srv/foobar under /srv/foo.
const root = path.resolve('/srv/foo');
check(
	'resolveWithinRoot rejects a prefix-sharing sibling',
	resolveWithinRoot(root, '/../foobar/x.html'),
	null
);
check(
	'resolveWithinRoot allows a real child',
	resolveWithinRoot(root, '/about-us/'),
	path.join(root, 'about-us', 'index.html')
);

// 3. The whitespace-only bucket must be reachable. Before the fix, the caller
// tested problemCount === 0 first, so a ws-only route in non-strict mode
// printed "ok" and the distinction was lost.
check('resultLabel: ws-only non-strict', resultLabel('whitespace-only', 0, false), 'ws-only');
check('resultLabel: ws-only strict is a fail-path', resultLabel('whitespace-only', 1, true), 'fail');
check('resultLabel: clean route', resultLabel('same', 0, false), 'ok');
check('resultLabel: real problems', resultLabel('different', 2, false), 'fail');

if (failures.length) {
	console.error('Harness self-test failures:');
	for (const f of failures) {
		console.error(`  - ${f}`);
	}
	process.exit(1);
}
console.log('Harness self-tests OK (raw-text passthrough, path containment, result bucketing).');
