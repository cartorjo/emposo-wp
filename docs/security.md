# Security posture

What is hardened, where it lives, what would catch a regression, and what no
code in this repository can do. Citations name the file and the symbol, not a
line number: this document drifted 25–50 lines out of date within sixteen
commits when it pinned lines, so `tools/check-docs.mjs` now fails the build if
any file named here stops existing, and the symbols are the durable anchor.

The starting point is the same fact the whole port rests on: the static build's
zero-third-party-request rule (a GDPR decision, not a technical one). Because no
visitor request ever leaves the host, a `default-src 'self'` Content-Security-
Policy is actually achievable here — not the long allowlist a site with
analytics or CDN assets needs (`client-mu-plugins/emposo-core/inc/security.php`).

Hardening lives in three layers, ordered by what survives what:

1. **The mu-plugin** (`client-mu-plugins/emposo-core/inc/security.php`) —
   headers and surface reduction. Survives a theme change; cannot be
   deactivated from wp-admin.
2. **`wp-config.php`** — the hardening constants that must exist before
   WordPress finishes loading (locally supplied by wp-env's own config block;
   on the host, set directly in `wp-config.php` — see §2).
3. **The theme cleanup** (`themes/emposo/inc/core-cleanup.php`) — the
   presentation surface. Gone if the theme is swapped, which is why nothing
   that matters *only* for security lives there.

Repository- and CI-level security (branch protection, CodeQL, Dependabot) is a
different subject with different trade-offs: see `docs/review-2026-09-19.md`.

## 1. Implemented measures, and what guards each one

The gate column is the point of this table. This repository's working rule is
that *a comment does not fail a build* — so a measure listed here as
**unguarded** is a measure that can silently regress, and §4 is the manual
routine that compensates.

| Control | Where | Guarded by |
|---|---|---|
| CSP: `default-src 'self'`, `script-src 'self'` (no `unsafe-*`), `style-src 'unsafe-inline'` (core prints inline `<style>`), `img-src data:` (the SVG favicon), `form-action mailto:` (the contact form), `frame-ancestors 'none'`, `base-uri 'self'`, `object-src 'none'` | `inc/security.php` | CI: `tools/audit.mjs` (`requiredHeaders`) asserts the header exists and matches `default-src 'self'`, and separately fails if `script-src` gains `unsafe-inline`/`unsafe-eval`. **Individual directives beyond those two checks are not asserted** — a CSP that quietly lost `frame-ancestors` would pass. Dashboard probes the same pattern. |
| `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` | `inc/security.php` | CI (`requiredHeaders`) + dashboard |
| `X-Frame-Options: DENY` | `inc/security.php` | CI (`requiredHeaders`) + dashboard. CSP `frame-ancestors 'none'` is the modern equivalent but is not individually asserted (see the CSP row). |
| HSTS, one year, `includeSubDomains` — sent only under `is_ssl()` | `inc/security.php` | **Unguarded** — wp-env has no TLS, so no local or CI request can ever exercise it. Host-only check, §4. |
| wp-admin exempt from the headers | `inc/security.php` | n/a — deliberate; core's admin needs its inline scripts. |
| AVIF `Content-Type` safety net | `inc/security.php` | Indirect (image budgets); the host config is the real fix — README, deployment requirements. |
| XML-RPC authenticated methods off; `X-Pingback` header dropped | `inc/security.php` | CI: `verify --security` asserts the filter's final outcome; dashboard asserts the header absence (`FORBIDDEN_HEADERS`, `inc/dashboard.php`). **The endpoint itself is a web-server matter — and not fully closed. See §2.** |
| Author enumeration: `/?author=N` and author archives 301 to `/`, at `template_redirect` priority **0** — core's `redirect_canonical` runs at 10 and wins ties on registration order, so running first is the whole mechanism (`inc/security.php`); plus the **users sitemap provider dropped** (`wp_sitemaps_add_provider`), because with `blog_public=1` core's `wp-sitemap-users-1.xml` would publish the login name the other measures hide (added 2026-09-20) | `inc/security.php` (redirect, rewrite-rule removal, sitemap provider filter) | CI: `verify --security` asserts the rules are emptied, the compiled ruleset carries no author rule, the priority-0 slot is occupied, and the users sitemap provider is gone. |
| Application passwords off | `inc/security.php` | CI (`verify --security`) |
| REST API closed to anonymous requests — 401 `WP_Error`, not switched off, because the block editor needs REST (`inc/security.php`) | `inc/security.php` | CI: `verify --security` evaluates `rest_authentication_errors` as user 0 and asserts the 401. |
| `DISALLOW_FILE_EDIT` — every environment | `wp-config.php` (host) / `.wp-env.json` (local) | CI (`verify --security`) |
| `DISALLOW_FILE_MODS`, `FORCE_SSL_ADMIN`, `WP_DEBUG_DISPLAY=false`, `SCRIPT_DEBUG=false`, updaters off, revision/trash bounds | `wp-config.php` (host) / `.wp-env.json` (local) | **Unguarded** as assertions beyond `DISALLOW_FILE_EDIT` above — they are standard `wp-config.php` constants; §2 lists the block to set, and §4's `wp eval` spot-checks them on the host. |
| Secrets read from env/constant via `get_env_var()`, never stored in `wp_options` — so they never land in a database export or a backup | `client-mu-plugins/emposo-core/inc/environment.php` | `wp claude doctor` reports where the key came from. |
| Surface reduction: generator, RSD, shortlink (`<link>` **and** the HTTP `Link:` header — core emits it twice), REST discovery, oEmbed, feeds, emoji, comments and pings closed, avatars off (Gravatar is a third-party request) | `themes/emposo/inc/core-cleanup.php` (`comments_open`, `pings_open`, `option_show_avatars`) | The rendered-output side is CI-guarded — `tools/checks.mjs` (`HEAD_POLLUTION`) fails on `api.w.org`, `xmlrpc.php?rsd`, `wlwmanifest`, `rel="shortlink"`, `secure.gravatar.com` and friends in the document. The **header** side (`Link:`, `X-Pingback`) is dashboard-only. |
| **Temporary: the no-shell installer** — an admin screen that runs scaffold/import/verify on a host with no WP-CLI. Triple-gated: loads only while `EMPOSO_INSTALLER` is defined true in `wp-config.php` (an SFTP-level switch), screen and handler require `manage_options`, every action is a nonce-checked POST. Its WP_CLI shim aliases the global class name only when real WP-CLI is absent. | `inc/installer.php` (the `EMPOSO_INSTALLER` gate), `cli/class-cli-shim.php`, `cli/wp-cli-utils.php` | **Unguarded in CI** (admin-only code; parity/audit never see it). **Removal deadline: install day** — runbook D7. The removal check: `/wp-admin/admin.php?page=emposo-installer` answers "not permitted" for an administrator once the define is deleted. While the define is absent the file is inert, so the standing risk is the constant being left behind, not the code. |

So, honestly, the gate coverage now stands at three layers. **Response
headers**: five (`tools/audit.mjs` `requiredHeaders`, inside its
`TARGET !== 'static'` guard — static is excluded because
`tools/static-server.mjs` sends none of these, and asserting them there once
made every static route fail and blocked re-baselining) plus script-src purity.
**Document output**: the head-pollution markers in `tools/checks.mjs`, run by
`npm run parity`, which CI runs strict. **Behavior**: `wp emposo verify
--security` (in `parity.yml`'s verify step) asserts the filter outcomes and
registry state — XML-RPC, application passwords, the REST 401, author
enumeration, the users sitemap, and `DISALLOW_FILE_EDIT`. The wp-admin panel (`inc/dashboard.php`,
`REQUIRED_HEADERS` / `FORBIDDEN_HEADERS`) probes the header side manually, with
a five-minute transient cache. What still regresses silently until §4 runs:
HSTS (no TLS anywhere CI reaches), the `/xmlrpc.php` endpoint block (a
web-server rule PHP cannot see), and everything the cached edge path might do
to generation-time headers — which is why §4's outside-the-host curls exist.

## 2. What the code cannot do: the host checklist

Server access is **SFTP plus wp-admin only — no SSH, no WP-CLI on the host**
(decided 2026-09-19). That splits these items into two kinds: `wp-config.php`
edits, which SFTP can do, and web-server/network configuration, which only the
hosting provider's panel or support can do. Sequence and sign-off live in the
install runbook (§C) and launch checklist (§4); this section records what each
item is and why it cannot live in this repository.

- **Block `/xmlrpc.php` at the web server.** Be precise about the residual
  risk: `xmlrpc_enabled => false` disables *authenticated* methods only. The
  endpoint still answers unauthenticated calls —

  ```sh
  curl -sd '<methodCall><methodName>demo.sayHello</methodName></methodCall>' https://<host>/xmlrpc.php
  ```

  returns a value until the server blocks the path. Pingback abuse and
  brute-force amplification ride on exactly this. Provider request: deny all
  requests to `/xmlrpc.php`.

- **Login rate limiting.** Nothing in this repository throttles
  `wp-login.php`, deliberately — PHP-level throttling is the wrong layer.
  Provider request: Nginx `limit_req` or the WAF equivalent. WordPress VIP's
  own production threshold is a usable spec: 10 requests per 30 seconds →
  1-hour IP block.

- **Security headers at the edge, too.** PHP already sends them (§3 has the
  argument); an edge that also sets them is welcome, and identical duplicates
  are harmless. What matters is the cache path — see §3.

- **Filesystem and database.** 755 directories / 644 files, the deploy path
  not writable by the web server user, and a DB account limited to `SELECT,
  INSERT, UPDATE, DELETE`. All provider-side; none verifiable from wp-admin.

- **The `wp-config.php` hardening block** — the constants go directly in
  `wp-config.php` on the host (this is a self-hosted, non-VIP site; there is no
  separate config file to require). Add, above the "That's all, stop editing"
  line:

  ```php
  define( 'DISALLOW_FILE_EDIT', true );   // no theme/plugin editors in wp-admin
  define( 'DISALLOW_FILE_MODS', true );   // no install/update from wp-admin
  define( 'FORCE_SSL_ADMIN', true );
  define( 'WP_DEBUG', false );
  define( 'WP_DEBUG_DISPLAY', false );    // never print errors to visitors
  define( 'WP_DEBUG_LOG', '/path/outside/webroot/emposo.log' );
  define( 'SCRIPT_DEBUG', false );
  define( 'AUTOMATIC_UPDATER_DISABLED', true );
  define( 'WP_AUTO_UPDATE_CORE', false );
  define( 'WP_POST_REVISIONS', 20 );
  define( 'EMPTY_TRASH_DAYS', 14 );
  define( 'WP_ENVIRONMENT_TYPE', 'production' );
  // Optional: force noindex on a staging clone regardless of Settings → Reading.
  // define( 'EMPOSO_FORCE_NOINDEX', true );
  // The Anthropic key, if wp claude is used — a constant, never the options table.
  // define( 'ANTHROPIC_API_KEY', '...' );
  ```

  Two notes: regenerate the **eight salts** (`AUTH_KEY` … `NONCE_SALT`) at
  install; and `WP_DEBUG_DISPLAY` matters most here — `wp_debug_mode()` runs
  early, so this must be a real `wp-config.php` constant, not set later.
  For php-fpm, prefer the constant over `getenv()` for the API key (a
  pool's `clear_env` hides process-environment variables).

- **Deferred, with the reason on record: 2FA / SSO / shortened sessions.**
  One admin account, no second editor yet, and every privileged path already
  behind that single login. The `two-factor` plugin and an
  `auth_cookie_expiration` filter are the shape when it changes — revisit the
  day a second account is created, not later.

## 3. Deliberate divergences and non-bugs

Read this before filing a finding; each of these looks wrong on purpose.

- **Headers are sent from PHP, not from the web server.** The original plan
  wanted them at Nginx/CDN with PHP as fallback. The code inverts that on
  purpose: sent from PHP they exist in local development and on any host, "a
  misconfigured host degrades to 'protected' rather than 'unprotected'"
  (`inc/security.php`). The caveat the plan was guarding against is
  real, though: a full-page cache is not guaranteed to replay
  generation-time headers on cache hits. That is why the post-install check
  is a **curl from outside the host** (§4) — the dashboard's loopback probe
  may never traverse the edge cache.

- **`EMPOSO_FORCE_NOINDEX` is the staging backstop, now wired.** `blog_public=0`
  is the primary, admin-visible noindex lever; the optional `wp-config.php`
  constant forces noindex even if `blog_public` is flipped on a staging clone.
  It went unread from inception until `parts/head.php` was wired to honour it —
  a no-op for parity, since the preview environment already noindexes via
  `blog_public=0`.

- **`xmlrpc_enabled` is filtered once**, in `inc/security.php` (authoritative:
  survives a theme swap). The theme's `core-cleanup.php` used to duplicate it;
  the duplicate was removed as it added nothing over the mu-plugin's filter.

## 4. How to re-verify

Local (wp-env running):

- `npm run audit` — the five `requiredHeaders` (CSP, nosniff, referrer,
  permissions, X-Frame-Options), script-src purity, zero console messages,
  zero off-host requests.
- `npm run wp -- emposo verify --security` — the filter-outcome and registry
  assertions listed in §1. Runs in CI (parity workflow); also runnable on the
  host over SSH (`wp emposo verify --security --path=<webroot>`).
- `wp eval 'var_dump( DISALLOW_FILE_EDIT, WP_ENVIRONMENT_TYPE );'` — confirms the
  hardening constants took effect (locally via `wp-env run cli`, on the host over
  SSH).

wp-admin (works on the host — it is one of the two access paths):

- Emposo → the header panel: the five required headers green, `Link:` and
  `X-Pingback` absent. "Re-check" clears the five-minute transient. Remember
  it probes over loopback — it proves PHP sends the headers, not that the
  edge delivers them.

From outside the host (the only true test of the cached path):

```sh
curl -sI https://<host>/ | grep -iE 'strict-transport|x-frame|content-security'
curl -si https://<host>/wp-json/ | head -1        # expect 401
curl -sI 'https://<host>/?author=1' | head -3      # expect 301, Location: /
curl -sd '<methodCall><methodName>demo.sayHello</methodName></methodCall>' \
  https://<host>/xmlrpc.php                        # expect the server block, not an XML answer
```

And the debug log (over SSH, or fetched via SFTP): it should be clean. If the
hardening constants are missing at runtime, `wp-config.php` did not receive the
§2 block — re-check that it was applied above the "stop editing" line.
