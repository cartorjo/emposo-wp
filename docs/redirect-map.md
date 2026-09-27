# Redirect map: old emposo.de URLs → new routes

Generated from the static build's shipped 301 map (`reference/static/serve.json`
`redirects`, pinned at f2e0a91), which the reference's own `check:content` gate
keeps resolvable; this file is its WordPress form. `tools/check-docs.mjs`
verifies every target here is a real contract route. Implemented as
**Cloudflare Redirect Rules** (301): host-agnostic, no server config, editable
without a deploy. Re-generate it after every re-pin (docs/resync.md).

One WordPress deviation: the static build serves `/sitemap.xml`, WordPress
serves its core sitemap at `/wp-sitemap.xml`, so the two sitemap rows point
there.

## 301 rules, in the reference's order

Specific rules first, patterns (`*`) after them: Cloudflare evaluates rules in
order, so keep this order when entering them.

Since 1a4ddaf the old English URLs point to their English successors, and
there is **no `/en/*` catch-all**. Cloudflare applies these rules before the
request reaches WordPress, so a catch-all (or a rule for `/en/`, `/en/contact/`,
`/en/industries/`, `/en/privacy-policy/` or `/en/accessibility/`, which are
live English routes now) would send English visitors to German pages. Unknown
`/en/…` paths get the English 404.

**Loop note:** in Cloudflare wildcard patterns `*` also matches the empty
string, so `/en/industries/*` would match `/en/industries/` itself and loop.
Enter that one rule as an expression instead:
`starts_with(http.request.uri.path, "/en/industries/") and http.request.uri.path ne "/en/industries/"`.
The German `/industries/*` rule is safe, because its target is a different path.

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
| `/en/terms/` | `/en/terms-of-use/` |
| `/en/disclaimer/` | `/en/legal-notice/` |
| `/en/about/` | `/en/about-us/` |
| `/en/industries/*` | `/en/industries/` (see the loop note below) |
| `/en/insights/` | `/en/industries/` |
| `/en/insights/*` | `/en/industries/` |
| `/en/join-us/` | `/en/careers/` |
| `/en/solutions/` | `/en/services/` |
| `/en/solutions/*` | `/en/services/` |
| `/en/vacancies/` | `/en/careers/` |
| `/en/vacancies/*` | `/en/careers/` |
| `/en/team-member/` | `/en/about-us/#management` |
| `/en/team-member/*` | `/en/about-us/#management` |

## No redirect needed

- `/` stays the front page.
- `/impressum/`, `/datenschutzerklaerung/`, `/nutzungsbestimmungen/` keep their
  URLs (contract routes since the 42a7c6d re-pin).
- `/sitemap/`: the old page is deleted at cutover, and the contract creates a
  new page at the same URL.
- `/en/`, `/en/contact/`, `/en/privacy-policy/` and `/en/accessibility/` keep
  their URLs: they are English contract routes since the 1a4ddaf re-pin.
