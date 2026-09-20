# Deep-review prompt

Paste this into a fresh Claude Code session started at the repository root to run
an independent, adversarial review of the whole codebase. It exists so a reviewer
starts with the repo's non-obvious facts instead of rediscovering them, and so
settled decisions are not re-litigated. Run it in a session that did NOT write the
code. For maximum depth, prefix the prompt with the word `ultracode` to opt into
multi-agent orchestration. Update the commit hash and "never reviewed" list as the
repository moves.

---

Do a deep, adversarial code review of this entire repository (cartorjo/emposo-wp,
main @ HEAD — record `git rev-parse HEAD`) before it is installed on the
production site emposo.de. Goal: find real defects that would bite on install day
or after launch — not style notes. A prior run of this prompt is recorded in
`docs/review-2026-09-20.md` (8 confirmed findings, all since fixed) — read it,
verify its fixes held, and spend your budget on angles it did not take.

## What this repo is

A WordPress port (theme `themes/emposo` + mu-plugin `client-mu-plugins/emposo-core`)
of the static site pinned as a submodule at `reference/static/`. The contract: 41
routes, whitespace-exact DOM parity against that static build, byte-budget audits,
and a frozen content export (`data/routes.json`, `data/site-export.json`). Built to
WordPress VIP standards without VIP. Read `docs/install-runbook.md`,
`docs/security.md` and `docs/review-2026-09-19.md` first — but treat every claim in
them as UNVERIFIED until you've checked it against code. Docs claim file:line
references; spot-check each one you rely on.

## Hard rules

1. Run every gate with REAL exit codes — capture `${pipestatus[1]}`, never `$?`
   after a pipe. This repo's history includes red gates masked by `| tail` for
   three commits.
2. Never trust a comment, docblock, or doc that says something is
   guarded/idempotent/safe. Prove it by running it or reading the exact code path.
   A prior review's biggest finding class was "gates that can't fail"; the newest
   bug found (fixed `f69462a`) was a docblock claiming idempotence while re-runs
   wiped content.
3. Verify each candidate finding adversarially before reporting: try to refute it,
   reproduce it concretely, and report only with evidence (command + output, or
   file:line trace). Rank by severity.

## Environment (all local, Docker via colima — start colima first)

- `npm ci && npm run composer:install`; `git submodule update --init`
- `npm run wp:start`, then `npm run wp:bootstrap` (theme activate + scaffold +
  seed — without it every route 404s)
- Gates: `npm run check` (lint, build, sources, class inventory, parity --strict,
  drift), `npm run behaviours`, `npm run audit`,
  `./scripts/php-docker.sh phpcs`,
  `./scripts/php-docker.sh phpstan analyse --memory-limit=3G`
- wp-env quirks: after `wp db reset` + `wp core install`, the container
  `.htaccess` may lack the WordPress rewrite block (pretty URLs 404 at Apache
  before WP runs) — restore it manually. The installer screen needs
  `"EMPOSO_INSTALLER": true` in `.wp-env.override.json`'s `config` block
  (gitignored).

## Priority 1 — code that has NEVER had a deep review (added 2026-09-19)

- `client-mu-plugins/emposo-core/inc/installer.php` — a TEMPORARY admin screen
  that runs scaffold/import/verify on a no-shell host (SFTP + wp-admin only).
  Review as hostile-input privileged code: the `EMPOSO_INSTALLER` constant gate,
  capability checks, nonce handling (note: WP nonces stay valid ~12–24h — what
  can a stale nonce replay do?), the step whitelist, output escaping, the
  transient result channel, the slug-conflict finder's WP_Query,
  `set_time_limit` / partial-completion behavior if PHP-FPM or the gateway kills
  a step mid-import (production `time_limit=60`, nginx in front).
- `cli/class-cli-shim.php` + `cli/wp-cli-utils.php` +
  `cli/class-halt-exception.php` — a WP_CLI façade class_alias'd into the global
  namespace on web requests. What else in WordPress core, the mu-plugin, or any
  plugin checks `class_exists('WP_CLI')` or `defined('WP_CLI')` and would change
  behavior when the alias exists? Confirm the alias can never load under real
  WP-CLI, and that PHPStan's stub (`tests/php/stubs/wp-cli.php`) still matches
  the real call surface.
- `cli/class-scaffold-command.php` update path (`f69462a`) — updates send only
  status/slug/parent via `wp_update_post`. Prove re-run safety yourself:
  bootstrap, import, re-run scaffold, then parity --strict must stay 42/42. Also
  probe interactions: what does a scaffold re-run do to a page an EDITOR renamed
  or re-parented after import?
- `cli/class-verify-command.php` `verify_media()` — alt-divergence is now a
  warning, not a failure. Check nothing else silently rides on that codepath.
- `.github/workflows/*` — actions are SHA-pinned, CodeQL removed (private repo),
  Dependabot skips on parity/audit. Look for gaps: can any workflow pass while a
  gate it claims to run actually failed?

## Priority 2 — install-day failure modes (think like the runbook's operator)

For each step in `docs/install-runbook.md` sections C–E, ask: what state is the
site in if this step dies halfway, and does the documented recovery actually
recover? Especially: import steps interrupted mid-request (media sideloads),
scaffold abort recovery, the three preserved legal pages rendering via
`parts/fallback.php` with large real content (the live Datenschutzerklärung is
~292KB), and the `blog_public` launch lever (`parts/head.php` gating +
`wp-sitemap.xml` + robots.txt interactions once Yoast is gone).

## Priority 3 — standing invariants

- The cascade rule: anything WordPress injects unlayered beats ALL layered CSS —
  verify nothing new enqueues/inlines styles (no theme.json, global-styles
  dequeued).
- `inc/security.php` headers vs `docs/security.md`'s table — the table marks
  which controls are CI-guarded vs unguarded; verify the CI-guarded claims are
  true.
- The object cache (`inc/cache.php` version-salt) under a PERSISTENT object
  cache — the production host has an `object-cache.php` drop-in; local wp-env
  has none, so staleness bugs are invisible locally. Reason through (or
  simulate) cache behavior across the import and the launch flip with
  persistence on.
- `data/routes.json` + `data/site-export.json` internal consistency (every
  route's content present, every asset in the media manifest exists at every
  declared size).

## Do NOT re-litigate (decided, documented)

Private repo → no CodeQL/branch protection (accepted 2026-09-19); no PHPUnit
suite (deferred by decision); noindex-until-launch and the mailto contact form
(port decisions); 41 routes not 42; `verify --media` warning semantics; the
installer's existence (required: the host has no shell).

## Deliverable

Findings ranked most-severe first, each with: file:line, one-sentence defect
statement, a concrete failure scenario (inputs/state → wrong outcome), and your
verification evidence. Then a final section: gate results (with real exit codes)
and an explicit GO / NO-GO verdict for installing this on emposo.de, listing any
finding you consider install-blocking. If everything survives your attack, say so
plainly — do not invent findings to seem thorough.
