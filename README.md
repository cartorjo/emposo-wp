# Emposo WordPress

WordPress theme + site plugin for Emposo. A port of the `weave-clone` static
site, built to WordPress VIP standards on self-hosted WordPress.

## Run locally

```bash
colima start --cpu 4 --memory 8   # Docker daemon; nothing works without it
npm ci
npm run composer:install          # runs inside php:8.3-cli — there is no host PHP
npm run wp:start                  # http://localhost:8888
npm run wp:bootstrap              # scaffold, then import (both idempotent)
```

`wp:bootstrap` is three steps and every one is required on a fresh database.
`wp:theme` activates the theme — nothing else does, and an unactivated theme
means WordPress serves its own default, whose inline scripts the CSP blocks and
whose markup fails every parity check. `wp:scaffold` creates the 41 route
objects and sets what makes the contract's paths exist at all — postname
permalinks, the front page, `blog_public=0` — then flushes the rewrite rules.
`wp:seed` fills those objects with content. All three are idempotent.

Each of those was invisible for as long as this project only ever ran on
machines that already had the state: the README documented `wp:seed` alone, and
the gap surfaced the day CI became the first environment to start from an empty
database.

`npm run wp -- <args>` runs WP-CLI in the container, e.g.
`npm run wp -- emposo verify --routes`.

## The Anthropic key

`wp claude` reads `ANTHROPIC_API_KEY` from the environment or a `wp-config`
constant, and never from the options table — an options-table secret ends up in
every database export and backup. Locally, that means the gitignored
`.wp-env.override.json`:

```json
{ "config": { "ANTHROPIC_API_KEY": "sk-ant-..." } }
```

Then `npm run wp:start` and `npm run wp -- claude doctor`, which reports the key
source without printing the key and proves the round trip. Without a key, every
command still assembles correctly: `wp claude prompt "…" --dry-run` prints the
exact request — URL, headers, body — and sends nothing, which is how the wire
format is reviewed on a machine that has no credential.

## Gates

| Command | Asserts |
|---|---|
| `npm run check` | lint, CSS build, class inventory, DOM parity on all 41 routes, no drift |
| `npm run parity -- --strict` | Every route matches `reference/static/` after normalisation |
| `npm run class-inventory` | The built CSS emits a superset of the reference's 239 classes |
| `npm run behaviours` | Interaction, filter, deep-link, count-up and no-JS contracts |
| `npm run audit` | Layout/header probes, axe, budgets, HTTP 200 not 301 |
| `npm run phpcs` / `npm run phpstan` | WordPress + VIP standards; static analysis at level 8 |
| `npm run audit:deps` | Clean production tree; no high dev advisories |

## Layout

Presentation lives in `themes/emposo/`. Everything stateful — content types,
taxonomies, meta, blocks, image helpers, settings, the dashboard and the CLI
commands — lives in `client-mu-plugins/emposo-core/`, so content survives a
theme change. `reference/static/` is the audited static build, pinned to
`c471ef0`; treat it as read-only.

## Things that look optional and are not

- **No `theme.json`.** Core's global-styles output is inline and *unlayered*,
  and unlayered CSS beats every layered rule regardless of order — it would
  outrank the theme's whole `@layer` cascade. It is emitted even without a
  `theme.json`, so it is dequeued explicitly too.
- **`wp_head()` prints last**, where the static build's `{{SCRIPTS}}` token sat.
  Anything that must come earlier is literal in `parts/head.php`. This is why
  `add_theme_support( 'title-tag' )` is not used and the font preloads are not
  emitted through `wp_preload_resources`: both print at `wp_head` priority 1,
  which here lands *after* the render-blocking CSS.
- **Every script needs `'strategy' => 'defer'`.** `WP_Scripts` intersects a
  script's strategy with its dependents', so one omission silently makes Lenis
  and `00-core.js` render-blocking on every page.
- **`has_archive => false`** on `emposo_case_study`. `true` emits a
  `case-studies/?$` rule above the page rules and 404s the hub page.
- **`html { font-size: 100% }`** is an accessibility fix, not a default. Never
  reintroduce a `vw`-scaled root.
- **Zero third-party requests** from a visitor. A hard GDPR rule, which is also
  what makes a `default-src 'self'` CSP achievable.
- **Production expects a persistent object cache: Memcached, not Redis** —
  matching VIP, where the platform supplies the drop-in; self-hosted it has to
  be installed. No code depends on the backend, because derived lists invalidate
  through a bumpable version salt in the cache key rather than
  `wp_cache_flush_group()`, which VIP does not support. wp-env has no persistent
  object cache, so locally every request takes the miss path — which is why the
  query counts inside `inc/fragments.php` are worth keeping honest.
- **The web server must serve `.avif` as `image/avif`.** This is the only thing
  the static build's `serve.json` exists to do. With the wrong content type
  browsers reject `<source type="image/avif">`, fall back to the JPEG, and add
  roughly 150 KB to a page — which alone breaks the 200 KB largest-image budget.
  `inc/security.php` filters `wp_headers` as a safety net, but a static file
  normally never reaches PHP, so the host config is the real fix.
- **Self-hosted `wp-config.php` must `require` `vip-config/vip-config.php`.** On
  VIP the platform loads it; self-hosted, nothing does. `inc/environment.php`
  loads it late as a fallback and logs that it had to, because `wp_debug_mode()`
  runs before mu-plugins and `WP_DEBUG_DISPLAY` is the one constant a late load
  cannot rescue — a production site would print errors to visitors.

The measured performance bars are only reachable behind a full-page cache,
measured logged out. Nothing in CI measures LCP or TTFB — a shared runner cannot
reproduce host timings — so they are measured on the host at install time; see
`docs/launch-checklist.md`.

## Documentation

Everything needed to install and sign off this site is in `docs/`, so a clone is
sufficient and nothing lives only in someone's notes:

| File | What it is |
|---|---|
| `docs/install-runbook.md` | The canonical install procedure, sections A–E. Corrected against the repository as it stands; where it disagrees with any older plan, it is right. |
| `docs/launch-checklist.md` | The manual pass — the viewport and reduced-motion matrix, the host-only checks, and the launch flip. Nothing automated covers these. |
| `docs/review-2026-09-19.md` | Dated snapshot of the readiness review: what was deliberately left undone, which earlier claims were corrected, and the measurement caveats. |
| `docs/security.md` | The hardening reference: every implemented measure with its location and gate status, the host-level items no code here can do, and the deliberate divergences. |
| `docs/redirect-map.md` | The 27 retired live URLs mapped to their nearest new routes, for the Cloudflare Redirect Rules published at launch (runbook D3). |
