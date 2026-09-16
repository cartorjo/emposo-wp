# Audit evidence

## `static-baseline.json`

The measured state of the **pinned static reference** (`reference/static`,
`c471ef0`), produced by:

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
