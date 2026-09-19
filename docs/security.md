# Security posture

What is hardened, where it lives, what would catch a regression, and what no
code in this repository can do. File and line references verified at `9c973e3`;
for files with pending changes in flight, symbols are named alongside lines.

The starting point is the same fact the whole port rests on: the static build's
zero-third-party-request rule (a GDPR decision, not a technical one). Because no
visitor request ever leaves the host, a `default-src 'self'` Content-Security-
Policy is actually achievable here — not the long allowlist a site with
analytics or CDN assets needs (`client-mu-plugins/emposo-core/inc/security.php:5-8`).

Hardening lives in three layers, ordered by what survives what:

1. **The mu-plugin** (`client-mu-plugins/emposo-core/inc/security.php`) —
   headers and surface reduction. Survives a theme change; cannot be
   deactivated from wp-admin.
2. **`vip-config/vip-config.php`** — constants that must exist before WordPress
   finishes loading. Survives everything except a missing `require` in
   `wp-config.php` (see §3).
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
| CSP: `default-src 'self'`, `script-src 'self'` (no `unsafe-*`), `style-src 'unsafe-inline'` (core prints inline `<style>`), `img-src data:` (the SVG favicon), `form-action mailto:` (the contact form), `frame-ancestors 'none'`, `base-uri 'self'`, `object-src 'none'` | `inc/security.php:48-61` | CI: `tools/audit.mjs` (`requiredHeaders`) asserts the header exists and matches `default-src 'self'`, and separately fails if `script-src` gains `unsafe-inline`/`unsafe-eval`. **Individual directives beyond those two checks are not asserted** — a CSP that quietly lost `frame-ancestors` would pass. Dashboard probes the same pattern. |
| `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` | `inc/security.php:62-64` | CI (`requiredHeaders`) + dashboard |
| `X-Frame-Options: DENY` | `inc/security.php:65` | **Unguarded.** CSP `frame-ancestors 'none'` is the modern equivalent, but per the row above it is not individually asserted either. |
| HSTS, one year, `includeSubDomains` — sent only under `is_ssl()` | `inc/security.php:67-71` | **Unguarded** — wp-env has no TLS, so no local or CI request can ever exercise it. Host-only check, §4. |
| wp-admin exempt from the headers | `inc/security.php:35-37` | n/a — deliberate; core's admin needs its inline scripts. |
| AVIF `Content-Type` safety net | `inc/security.php:89-99` | Indirect (image budgets); the host config is the real fix — README, deployment requirements. |
| XML-RPC authenticated methods off; `X-Pingback` header dropped | `inc/security.php:110-118` | Dashboard only (`FORBIDDEN_HEADERS`, `inc/dashboard.php:65`). **The endpoint itself is unguarded — and not fully closed. See §2.** |
| Author enumeration: `/?author=N` and author archives 301 to `/`, at `template_redirect` priority **0** — core's `redirect_canonical` runs at 10 and wins ties on registration order, so running first is the whole mechanism (`inc/security.php:120-128`) | `inc/security.php:129-139`, rewrite rules removed outright `:143` | **Unguarded** |
| Application passwords off | `inc/security.php:146` | **Unguarded** |
| REST API closed to anonymous requests — 401 `WP_Error`, not switched off, because the block editor needs REST (`inc/security.php:148-155`) | `inc/security.php:156-173` | **Unguarded** — no request anywhere asserts the 401. |
| `DISALLOW_FILE_EDIT` — every environment, unconditional | `vip-config/vip-config.php:40-42` | **Unguarded** |
| `DISALLOW_FILE_MODS`, `FORCE_SSL_ADMIN`, `WP_DEBUG_DISPLAY=false`, `SCRIPT_DEBUG=false`, updaters off, revision/trash bounds — outside local/development only | `vip-config/vip-config.php:46-79` | **Unguarded** as assertions. The runtime tripwire is `EMPOSO_CONFIG_LOADED` (`:100-102`) plus the late-load fallback in `inc/environment.php`, which `error_log()`s when `wp-config.php` forgot the `require` (`:105`). |
| Secrets read from env/constant via `get_env_var()`, never stored in `wp_options` — so they never land in a database export or a backup | `client-mu-plugins/emposo-core/inc/environment.php:37-53` | `wp claude doctor` reports where the key came from. |
| Surface reduction: generator, RSD, shortlink (`<link>` **and** the HTTP `Link:` header — core emits it twice), REST discovery, oEmbed, feeds, emoji, comments and pings closed, avatars off (Gravatar is a third-party request) | `themes/emposo/inc/core-cleanup.php` (comments `:148`, pings `:149`, avatars `:150`) | The rendered-output side is CI-guarded — `tools/checks.mjs` (`HEAD_POLLUTION`) fails on `api.w.org`, `xmlrpc.php?rsd`, `wlwmanifest`, `rel="shortlink"`, `secure.gravatar.com` and friends in the document. The **header** side (`Link:`, `X-Pingback`) is dashboard-only. |
| **Temporary: the no-shell installer** — an admin screen that runs scaffold/import/verify on a host with no WP-CLI. Triple-gated: loads only while `EMPOSO_INSTALLER` is defined true in `wp-config.php` (an SFTP-level switch), screen and handler require `manage_options`, every action is a nonce-checked POST. Its WP_CLI shim aliases the global class name only when real WP-CLI is absent. | `inc/installer.php` (gates `:37-45`), `cli/class-cli-shim.php`, `cli/wp-cli-utils.php` | **Unguarded in CI** (admin-only code; parity/audit never see it). **Removal deadline: install day** — runbook D7. The removal check: `/wp-admin/admin.php?page=emposo-installer` answers "not permitted" for an administrator once the define is deleted. While the define is absent the file is inert, so the standing risk is the constant being left behind, not the code. |

So, honestly: **four headers plus script-src purity are all that CI asserts
about response headers** (`tools/audit.mjs`, the `requiredHeaders` block inside
its `TARGET !== 'static'` guard — static is excluded because
`tools/static-server.mjs` sends none of these, and asserting them there once
made every static route fail and blocked re-baselining). The other CI-side
assertion is the document itself: the head-pollution markers in
`tools/checks.mjs` run inside `npm run parity`, which CI runs strict. The wp-admin panel (`inc/dashboard.php`, `REQUIRED_HEADERS`
`:51-56` / `FORBIDDEN_HEADERS` `:65`) probes more, but it is a screen someone
must open, and its result is cached in a five-minute transient. Everything else
in the table regresses silently until §4 runs. Extending the gates was
considered and deliberately not done in this pass — this document is the
compensating control, and it says so out loud so the gap stays a decision
rather than an accident.

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

- **The `wp-config.php` production block** — applied over SFTP. An earlier
  plan wanted a committed sample file; this list deliberately supersedes it,
  because `vip-config/vip-config.php` now owns every hardening constant and a
  sample would just drift. What `wp-config.php` must still carry:

  - `require_once __DIR__ . '/vip-config/vip-config.php';` — **the one line
    everything in layer 2 depends on.** On VIP the platform loads it;
    self-hosted, nothing does. The fallback loader cannot rescue
    `WP_DEBUG_DISPLAY` because `wp_debug_mode()` runs before mu-plugins
    (README, "Things that look optional and are not").
  - `WP_ENVIRONMENT_TYPE` set to `production` — the switch the vip-config
    gates read.
  - The eight salts (`AUTH_KEY` … `NONCE_SALT`), freshly generated.
  - `WP_DEBUG_LOG` pointed outside the webroot.
  - The Anthropic key as a constant, not an option — and mind php-fpm's
    `clear_env`: `getenv()` only sees pool-config `env[NAME]=` lines, so a
    constant in `wp-config.php` is the reliable form here.

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
  (`inc/security.php:26-33`). The caveat the plan was guarding against is
  real, though: a full-page cache is not guaranteed to replay
  generation-time headers on cache hits. That is why the post-install check
  is a **curl from outside the host** (§4) — the dashboard's loopback probe
  may never traverse the edge cache.

- **`EMPOSO_FORCE_NOINDEX` is a dead constant.** Defined at
  `vip-config/vip-config.php:90-93`, set by the wp-env configs, read by
  nothing: `1809a1f` gated the noindex meta on `blog_public` instead, so that
  Settings → Reading stays the single launch lever and the output stays
  byte-identical for parity. Either wire it into `parts/head.php` as a
  staging override or delete it — recorded here so nobody "fixes" the wrong
  side of it.

- **`xmlrpc_enabled` is filtered twice** — `inc/security.php:110`
  (authoritative: survives a theme swap) and `themes/emposo/inc/core-cleanup.php:151`
  (part of the theme's parity-motivated cleanup). Redundant, harmless, and
  cheaper than a cross-file dependency.

## 4. How to re-verify

Local (wp-env running):

- `npm run audit` — the four `requiredHeaders`, script-src purity, zero
  console messages, zero off-host requests. It does **not** cover anything
  the table above marks unguarded.
- `wp-env run cli wp eval 'var_dump( DISALLOW_FILE_EDIT, defined("EMPOSO_CONFIG_LOADED") );'`
  — constants and config loading. Local-only: there is no WP-CLI on the host.

wp-admin (works on the host — it is one of the two access paths):

- Emposo → the header panel: four required headers green, `Link:` and
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

And the debug log (fetched over SFTP, since there is no shell): the
`inc/environment.php` fallback warning appearing there means `wp-config.php`
lost its `require` of `vip-config/vip-config.php` — which silently reverts
every constant in layer 2.
