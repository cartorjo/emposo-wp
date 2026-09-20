# Redirect map: old emposo.de URLs → new routes

Draft for runbook D3, built 2026-09-19 from the live Yoast sitemaps against the
41-route contract: twenty-three one-to-one 301s and one `/en/*` catch-all, with
a handful of URLs that keep their paths and need no redirect (the legal pages,
the front page, and the rebuilt `/sitemap/` — listed at the end).
`tools/check-docs.mjs` verifies every target here is a real contract route.
Implemented as **Cloudflare Redirect Rules** (301) — host-agnostic, no server
config, editable without a deploy.

The one-to-one page moves are mechanical. The `/solutions/*` rows are
**editorial judgment calls — review before publishing**: the old solution pages
have no exact successors, so each maps to the closest new expertise page rather
than dumping everything on the homepage.

## Exact moves

| Old | New (301) |
|---|---|
| `/about/` | `/about-us/` |
| `/contact/` | `/kontakt/` |
| `/join-us/` | `/karriere/` |
| `/industries/` | `/branchen/` |
| `/vacancies/` | `/karriere/` |
| `/vacancies/business-manager/` | `/karriere/` |
| `/insights/` | `/` (no successor) |
| `/en/accessibility/` | `/barrierefreiheit/` |
| `/sitemap_index.xml` | `/wp-sitemap.xml` |

## Solutions → expertise (review these)

| Old | Proposed new (301) |
|---|---|
| `/solutions/` | `/expertise/` |
| `/solutions/autonomous-drive/` | `/expertise/engineering/` |
| `/solutions/cloud-infrastructure/` | `/expertise/software-cloud/` |
| `/solutions/cyber-security/` | `/expertise/cyber-compliance/` |
| `/solutions/end-user-productivity-and-service-management/` | `/expertise/enterprise-services/` |
| `/solutions/industry-4-0/` | `/expertise/produktion-industrialisierung/` |
| `/solutions/intelligent-automation/` | `/expertise/ai-daten/` |
| `/solutions/ki-datenloesungen/` | `/expertise/ai-daten/` |
| `/solutions/production/` | `/expertise/produktion-industrialisierung/` |
| `/solutions/project-management/` | `/expertise/engineering-services/` |
| `/solutions/softwareentwicklung/` | `/expertise/software-cloud/` |
| `/solutions/supplier-quality-management/` | `/expertise/produktion-industrialisierung/` |
| `/solutions/technische-kommunikation/` | `/expertise/engineering-services/` |
| `/solutions/vernetzte-systeme/` | `/expertise/system-engineering/` |

## Catch-all, ordered after the rows above

| Old | New (301) |
|---|---|
| `/en/*` | `/` |

## No redirect needed

- `/` — stays the front page.
- `/impressum/`, `/datenschutzerklaerung/`, `/nutzungsbestimmungen/` — the
  three preserved legal pages keep their URLs.
- `/sitemap/` — the old page is deleted at cutover, but the contract creates a
  new page at the same URL.

Cloudflare ordering note: the specific `/en/accessibility/` rule must sort
before the `/en/*` catch-all; specific rules first, catch-all last.
