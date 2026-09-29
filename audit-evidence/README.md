# Audit evidence

## `static-baseline.json`

Since 2026-09-29 this is the **last accepted WordPress state**. The owner took
weave-clone off that day ("i took off weave-clone"), so there is no static
reference to measure against any more. The file name stays for its citations.
It is written after a reviewed change with:

```bash
node tools/audit.mjs --target=wp --write-baseline
```

The first WordPress baseline (2026-09-29, PR #50) covers 74 entries: 72 routes
and both 404s, including the SOP/Curricula case. Before writing it, the numbers
were diffed against the static baseline: pages were a uniform +1.1–1.7 % (the
existing WordPress markup overhead, inside the old 2 % tolerance), plus the
intended growth (one more card on /branchen/ and /en/industries/, a new related
card on the two pharma cases).

Until then it was the measured state of the pinned static reference
(`reference/static`, `9545bc3`), written with `--target=static`.

This file exists because the reference's own committed evidence does not cover
the whole site. `reference/static/audit-evidence/final/` was measured on
**8 September 2026**, when the site had 17 pages; the 10 September feedback wave
grew it to 42 documents. Twenty-five routes — the 9 generated case-study
details, the 7 generated discipline details, the 5 industry details, and
`/cookies/`, `/barrierefreiheit/`, `/sitemap/`, `/zertifizierungen/` — had
never been measured at all. You cannot claim "no regression" on a route nobody
measured, so the port is gated against this instead.

Result on first run: **41/41 routes pass, with zero axe violations at both
1440 px and 390 px.** The previously unmeasured routes are clean.

Re-measured at the 42a7c6d re-pin (2026-09-27): **35/35 routes pass.** The
whole-page ("scrolled") byte totals are not reproducible run to run on the
reference itself (533 vs 573 KB on one case study, 974 vs 1054 KB on `/`), so a
route can fail the 2 % whole-page tolerance on measurement noise alone; see
#40.

Re-measured at the f2e0a91 bilingual re-pin (2026-09-27), after the #40 fix
(the whole-page total is snapshotted after a paced scroll pass, before the width
sweep; three consecutive runs gave byte-identical totals): the static baseline
covers **70/70 routes** (35 German, 35 English), and WordPress passes **72/72** (both 404s included) on the first run.

## What the numbers are, and are not

`tools/audit.mjs` records **uncompressed** resource bytes. The 194–295 KB
figures in the reference's Lighthouse reports, and the ≤800 KB page budget in
its `01-audit.md`, are **compressed transfer** sizes — `css/site.css` alone is
64 KB raw and ~12.5 KB gzipped. Comparing the two reads roughly 2× over and
manufactures failures on routes the reference passes.

So this file is used for **relative** gating: WordPress must not grow bytes or
requests against the reference. Absolute byte budgets belong to the Lighthouse
step, which measures the same thing the original evidence did. Request count,
CLS and individual image sizes are compression-independent and are gated
absolutely.

`fullBytes` / `fullRequests` record the cost after scrolling the entire page to
force every lazy image. They are informational: the budgeted figures are the
initial load, which is what Lighthouse and the original evidence describe.
