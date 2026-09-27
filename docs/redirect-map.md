# Redirect map: old emposo.de URLs → new routes

Generated from the static build's shipped 301 map (`reference/static/serve.json`
`redirects`, pinned at 42a7c6d), which the reference's own `check:content` gate
keeps resolvable; this file is its WordPress form. `tools/check-docs.mjs`
verifies every target here is a real contract route. Implemented as
**Cloudflare Redirect Rules** (301): host-agnostic, no server config, editable
without a deploy. Re-generate it after every re-pin (docs/resync.md).

One WordPress deviation: the static build serves `/sitemap.xml`, WordPress
serves its core sitemap at `/wp-sitemap.xml`, so the two sitemap rows point
there.

## 301 rules, in the reference's order

Specific rules first, patterns (`*`) after them, `/en/*` last: Cloudflare
evaluates rules in order, so keep this order when entering them.

| Old | New (301) |
|---|---|
| `/case-studies/technische-dokumentation/` | `/case-studies/technische-dokumentation-halbleiter/` |
| `/case-studies/fahrzeugfunktionen/` | `/case-studies/funktionsbetreuung-infotainment/` |
| `/case-studies/` | `/branchen/` |
| `/about/` | `/about-us/` |
| `/contact/` | `/kontakt/` |
| `/join-us/` | `/karriere/` |
| `/vacancies/` | `/karriere/` |
| `/vacancies/*` | `/karriere/` |
| `/industries/` | `/branchen/` |
| `/industries/*` | `/branchen/` |
| `/insights/` | `/branchen/` |
| `/insights/*` | `/branchen/` |
| `/solutions/` | `/portfolio/` |
| `/solutions/*` | `/portfolio/` |
| `/team-member/` | `/about-us/#management` |
| `/team-member/*` | `/about-us/#management` |
| `/sitemap_index.xml` | `/wp-sitemap.xml` (the static build serves /sitemap.xml) |
| `/wp-sitemap.xml` | `/wp-sitemap.xml` (the static build serves /sitemap.xml) |
| `/en/accessibility/` | `/barrierefreiheit/` |
| `/en/privacy-policy/` | `/datenschutzerklaerung/` |
| `/en/terms/` | `/nutzungsbestimmungen/` |
| `/en/disclaimer/` | `/impressum/` |
| `/en/about/` | `/about-us/` |
| `/en/contact/` | `/kontakt/` |
| `/en/industries/` | `/branchen/` |
| `/en/industries/*` | `/branchen/` |
| `/en/insights/` | `/branchen/` |
| `/en/insights/*` | `/branchen/` |
| `/en/join-us/` | `/karriere/` |
| `/en/solutions/` | `/portfolio/` |
| `/en/solutions/*` | `/portfolio/` |
| `/en/vacancies/` | `/karriere/` |
| `/en/vacancies/*` | `/karriere/` |
| `/en/team-member/` | `/about-us/#management` |
| `/en/team-member/*` | `/about-us/#management` |
| `/en/` | `/` |
| `/en/*` | `/` |

## No redirect needed

- `/` stays the front page.
- `/impressum/`, `/datenschutzerklaerung/`, `/nutzungsbestimmungen/` keep their
  URLs (contract routes since the 42a7c6d re-pin).
- `/sitemap/`: the old page is deleted at cutover, and the contract creates a
  new page at the same URL.
