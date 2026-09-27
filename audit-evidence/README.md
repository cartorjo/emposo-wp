# Audit evidence

## `static-baseline.json`

The measured state of the **pinned static reference** (`reference/static`,
`42a7c6d`, weave-clone main on 2026-09-27), produced by:

```bash
node tools/audit.mjs --target=static --write-baseline
```

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

Re-measured at the 1a4ddaf bilingual re-pin (2026-09-27): the static
baseline covers **70/70 routes** (35 German, 35 English), and WordPress passes
**70/72** (both 404s included). The two failures are whole-page totals on
`/case-studies/software-planung-antriebssteuergeraete/` (363 vs 345 KB) and
`/case-studies/qualitaetsarbeit-pharma-diagnostik/` (365 vs 349 KB). WordPress
measures the same on a second run. Two more static runs measure 359 and 361
KB, so the baseline caught a low sample, and WordPress is within 1.1 % of the
repeat measurements. Per #40 these are not regressions. Initial-load bytes,
CLS and axe pass on every route.

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
