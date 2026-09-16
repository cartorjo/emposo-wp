#!/usr/bin/env bash
# Supply-chain gate.
#
# Nothing from npm reaches the WordPress server: Tailwind and sharp are
# build-time only, wp-env and Playwright are local/CI only. So the gate that
# actually matters is the production tree, which must be spotless. Dev findings
# are gated at "high" — below that they are reported, not blocking, because
# @wordpress/env vendors a local dev server (@wp-playground/cli -> express)
# whose transitive advisories we cannot fix without dropping wp-env itself and
# which never runs anywhere a visitor can reach.
set -uo pipefail

fail=0

echo "== production dependencies (must be clean) =="
if npm audit --omit=dev; then
	echo "OK: no advisories in the production tree"
else
	echo "FAIL: advisory in a production dependency"
	fail=1
fi

echo
echo "== dev dependencies (gated at high) =="
if npm audit --audit-level=high; then
	echo "OK: no high or critical advisories in the dev tree"
else
	echo "FAIL: high/critical advisory in the dev tree"
	fail=1
fi

echo
echo "== dev advisories below the gate (informational) =="
npm audit --audit-level=moderate || true

exit "${fail}"
