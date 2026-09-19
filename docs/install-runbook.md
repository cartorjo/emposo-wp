# Install `emposo-wp` into the existing self-hosted WordPress site

Canonical copy. Derived from the plan approved 2026-09-17, corrected 2026-09-19
against the repository as it actually stands, and **rewritten the same day for
the confirmed access model: the host offers SFTP and wp-admin only — no SSH, no
WP-CLI**. Where a fact here differs from any older plan, this file is right —
differences are marked **[corrected]** and are consequences of work landed
since.

## Context

Deploy the port — theme `themes/emposo/`, mu-plugin
`client-mu-plugins/emposo-core/`, bundled content JSON — into the existing
self-hosted WordPress installation, replacing the current site at **emposo.de**.

- **[corrected] Access is SFTP + wp-admin, no shell.** The WP-CLI commands the
  original plan was built on cannot be typed on the host. Instead, the
  **no-shell installer** (`inc/installer.php`, an admin screen gated on the
  `EMPOSO_INSTALLER` constant) runs the same tested Scaffold/Import/Verify
  command classes through a WP_CLI shim (`cli/class-cli-shim.php`), one step
  per request. The admin-installer idea the first plan rejected is back because
  the premise of that rejection — available SSH — turned out to be false.
- **The new site replaces the old one entirely.** Legally mandatory exception:
  **Impressum**, **Datenschutzerklärung** and **[corrected, decided
  2026-09-19] Nutzungsbestimmungen** are not in the 41-route contract but keep
  rendering under the new theme via `parts/fallback.php` at their current URLs.
- **Rehearsal is local**, in this repo's wp-env, not on a staging server.

## Facts this runbook rests on

1. **Scaffold is not content-only.** It force-sets `show_on_front=page`,
   `page_on_front`, `page_for_posts=0`, `blog_public=0`
   (`cli/class-scaffold-command.php:340-357`) and permalinks to `/%postname%/`
   with a flush (`:136-138`). For a full replacement these are all desired;
   `blog_public=0` is the pre-launch de-index gate.
2. **Slug conflicts hard-abort scaffold mid-run** (`:238-260`). Recovery: find
   the squatter with the installer's **slug conflict finder** (all post types,
   all statuses), delete it from its edit screen, re-run — idempotent via
   `_emposo_source_slug`. Old content must be cleared *before* scaffold.
   **[corrected 2026-09-19]** A scaffold re-run no longer touches imported
   content: the update path used to reset title, content and menu_order to
   skeleton state, so running scaffold after import silently wiped the site
   (found in the no-shell rehearsal; fixed the same day). Recovery from a
   pre-fix wipe is `import all --force` — a plain re-import skips the wiped
   fields because empty-vs-fingerprint reads as an editor change.
3. **Import needs a user with `unfiltered_html`**
   (`cli/class-import-command.php:247-252`) and **scaffold must run first**.
   **[corrected]** On the installer the logged-in administrator satisfies the
   capability check naturally — the `--user=` flag was WP-CLI's way of getting
   the same thing. Locally, `npm run wp:bootstrap` does theme activation,
   scaffold and import in that order; all three are idempotent.
4. **No build step on the host.** `assets/css/site.css` is committed by design
   and CI gates drift. Ship the committed file.
5. **Packaging is not a trap.** Lenis is tracked (`d8cfb63`), so a fresh clone
   and `git archive HEAD` both contain it. Verify the md5 after upload
   regardless — without Lenis, every front-end script early-returns:
   `df4f0debbe0e2c502b6eeb46fae82ddd`. The installer's Preflight checks it.
6. **noindex is one gated line** (`parts/head.php`), wrapped in
   `'0' === get_option('blog_public')`. Core's `wp_robots` is removed
   (`inc/core-cleanup.php`), and `wp-sitemap.xml` is gated purely on
   `blog_public`. So Settings → Reading is the single launch lever, and
   post-launch there is deliberately **no** robots meta at all.
   **[corrected 2026-09-19]** The live site's robots.txt currently carries a
   Yoast block — if Yoast wrote a **physical** `robots.txt` file, WordPress's
   virtual one (and with it the launch lever's robots.txt effect) never
   applies. The installer's Preflight warns when a physical file exists;
   delete it over SFTP.
7. **`vip-config.php` is meant to be required from `wp-config.php`.** The
   mu-plugin fallback (`inc/environment.php`) `error_log()`s on every production
   request — *even when the file is absent*, because the log call sits outside
   the `is_readable` check. Skipping it means perpetual log spam. Upload
   `vip-config/` one level above the WP root and add, in `wp-config.php`:
   ```php
   define( 'DISALLOW_FILE_MODS', false );          // wp-admin is the only management surface — keep it able to install/update
   require_once __DIR__ . '/vip-config/vip-config.php';
   ```
   Every constant in the file is `if ( ! defined() )`-guarded, so the pre-define
   wins. With no shell on this host, `DISALLOW_FILE_MODS=false` is not optional
   hardening slack: it is what keeps plugin/theme management possible at all.
8. **Bare full verify asserts `blog_public === 0`**
   (`cli/class-verify-command.php`), so it passes pre-launch and fails after the
   flip by design. Post-launch use the installer's "routes, content, media"
   verify (the `--routes --content --media` equivalent).
9. **`verify --media` is export-driven but not a lock.** Missing image, empty
   alt, missing file: hard failures. Alt text that merely *differs* from the
   export: a warning, so an editor improving a description does not break the
   post-launch command.
10. **Media import sideloads 22 JPEGs from the active theme's
    `assets/supplied/`**, so the theme must be uploaded *and activated* before
    the import steps. AVIF and WebP variants are served from the theme
    directory, never from uploads.
11. **Uncontracted pages render correctly.** The `not_found` sentinel is behind
    `is_404()` and `parts/fallback.php` renders title plus content (`1809a1f`).
    This is what makes the three legal pages work. **[corrected 2026-09-19]**
    Their bodies on the live site are classic `post_content`, not
    Elementor-built (verified against the rendered pages), so they render
    cleanly without the old page builder.
12. **mu-plugin side effects are live the moment the loader is uploaded**: CSP,
    HSTS, X-Frame-Options, XML-RPC off, anonymous REST denied, author archives
    removed, application passwords off (`inc/security.php`). That is why the
    cutover stages `emposo-core/` **without** `plugin-loader.php` first —
    without the loader nothing loads, and dropping the loader in is the
    cutover switch. The still-live Elementor site would break under the CSP.
13. **The contact recipient is an option, not a code edit.**
    `parts/contact-form.php` reads `emposo_contact_recipient` (interest list
    from `emposo_interests`), with the literal address only as a fallback. Set
    it post-launch from the admin; the Emposo screen warns until it changes.
14. **[new 2026-09-19] The live stack, surveyed read-only:** WordPress 7.1.1 on
    `hello-elementor`; Elementor 4.2.4 + Pro 4.2.3, Essential Addons, Anywhere
    Elementor Pro, OoohBoi Steroids, Sticky Header Effects, Ivory Search, Site
    Kit, SMTP Mailer, Dynific Templates; **WPML 5.0** with an English tree at
    `/en/`; **Yoast** owns robots.txt content and `sitemap_index.xml`. The site
    sits behind **Cloudflare** plus a host cache (`x-scout-cache` header). It
    is **currently indexed** (empty `Disallow:`), and it publishes **27 unique
    URLs**, almost all of which die at cutover. Known contract-slug squatter:
    the live `/sitemap/` page. `kontakt`, `karriere`, `about-us` etc. are free
    (live uses `/contact/`, `/join-us/`, `/about/`).
15. **[new] WPML orders the cutover.** WP-admin list screens filter to one
    language by default; deleting old content must happen in the **"All
    languages"** view and **before** WPML is deactivated (afterwards the
    translation UI is gone while its rows linger). The CPT list screens
    (solutions, vacancies, …) likewise vanish once their registering plugin is
    deactivated — delete content first, deactivate second.

## Code edits: already applied

The three theme edits from the original plan are on `main` (`1809a1f`,
`9c973e3`): noindex gating, the `is_404()` guard, `parts/fallback.php`
rendering real content. The no-shell installer (`inc/installer.php`,
`cli/class-cli-shim.php`, `cli/wp-cli-utils.php`, `cli/class-halt-exception.php`)
is the only 2026-09-19 addition. There is no outstanding implementation work
before install day.

## A. One-time prep

- Package from a tag: `git archive install-<date>` (create it first:
  `git tag -a install-$(date +%F) -m "Uploaded to emposo.de" && git push origin
  install-$(date +%F)`), so rollback names an exact artefact.
- vip-config as described in fact 7 — the `wp-config.php` edit happens over
  SFTP **during the cutover window**, not before (fact 12's reasoning: keep
  every switch in one window).
- Preflight runs **on the installer screen** once the files are staged and the
  loader is in (C6): PHP ≥ 8.1, WP ≥ 6.7, GD/Imagick, theme present, Lenis
  md5, bundled JSON, `unfiltered_html`, physical robots.txt, stale drop-ins,
  audit-evidence baseline.
- Plan the AVIF MIME mechanism: Apache → add `AddType image/avif .avif` to
  `.htaccess` over SFTP; nginx without config access → a **Cloudflare Transform
  Rule** setting `Content-Type: image/avif` on `*.avif` responses. Without it
  browsers reject the AVIF sources, fall back to JPEG, and the largest-image
  budget breaks.
- Cloudflare prep: confirm **Email Obfuscation, Rocket Loader and Auto Minify
  are OFF before the smoke test** — obfuscation injects a `/cdn-cgi/` script
  the CSP blocks and garbles the mailto contact path, the site's only contact
  channel. Draft the redirect map (D3). Optionally prepare a WAF rule limiting
  the site to the operator's IP for the cutover window — there is no
  `.maintenance`-file option, it would lock wp-admin too.
- Backup capability: the hosting panel's DB tool if it exists, else a
  temporary backup plugin (e.g. UpdraftPlus) installed via wp-admin. Decide
  before install day.

## B. Local rehearsal (wp-env, from `emposo-wp/`)

Rehearse the **web path**, not the CLI path — the buttons are what install day
uses.

1. `npm run wp:start`, and define the gate in `.wp-env.override.json`'s
   `config` block: `"EMPOSO_INSTALLER": true`.
2. Simulate host conditions: create `impressum`, `datenschutzerklaerung` and
   `nutzungsbestimmungen` pages with the **real** `post_content` copied from
   the live editor (Pages → edit → code editor view), not dummy text. Create
   squatter pages on `kontakt` **and `sitemap`** — the live site really has a
   `/sitemap/` page, so the abort will happen on the day.
3. Activate the theme (Appearance → Themes), then drive the **Emposo →
   Installer** screen in runbook order: Preflight → Scaffold dry-run →
   Scaffold (**expect the squatter abort**; rehearse the slug-conflict finder
   → delete → re-run) → Import dry-run → the six import steps → Verify full.
4. Eyeball at `localhost:8888`: home, nav routes, one case study; the three
   legal pages showing title and real content at HTTP 200 **with no CSP errors
   in the console**; a garbage URL → 404; Lenis smooth scrolling alive;
   noindex present; flip test via Settings → Reading (uncheck "Discourage
   search engines") → meta gone and `wp-sitemap.xml` serving → flip back; the
   Emposo admin screen green.
5. Negative test: remove `EMPOSO_INSTALLER` from the override and confirm the
   Installer page is gone and its POST endpoint refuses.
6. Work through `docs/launch-checklist.md` — the viewport and reduced-motion
   matrix nothing automated checks.

## C. Live install (SFTP + wp-admin, no shell)

0. **Backup first — cutover does not start until this is downloaded and
   spot-checked.** DB export via the hosting panel's tool (or the temporary
   backup plugin), plus an SFTP download of `wp-content/`. Record for
   rollback: the active theme name and the values of `show_on_front
   page_on_front page_for_posts blog_public permalink_structure` (Settings →
   Reading and Permalinks show all five).
1. **Stage files over SFTP (all inert):** `themes/emposo/` →
   `wp-content/themes/emposo/`; `client-mu-plugins/emposo-core/` →
   `wp-content/mu-plugins/emposo-core/` **without**
   `client-mu-plugins/plugin-loader.php`; `vip-config/` one level above the WP
   root; `audit-evidence/static-baseline.json` → `wp-content/audit-evidence/`
   (the Emposo screen otherwise reports "no baseline" forever;
   `audit-evidence/wp/` stays behind). Upload nothing else from the repo root.
   Verify the Lenis md5 by downloading the uploaded file back and hashing it.
2. Optional: enable the Cloudflare WAF cutover shield (A).
3. **Delete old content in wp-admin — before touching plugins** (fact 15).
   Every list screen (Pages, Posts, Solutions, Vacancies, Industries, Case
   studies, Team, Insights) in **"All languages"** view: bulk Trash, then
   empty trash. Keep ONLY the three German legal pages; their `/en/`
   translation rows go too. Trashing frees slugs (`__trashed` suffix), which
   is what unblocks scaffold.
4. **Deactivate the old plugin stack** on the Plugins screen: WPML, Elementor
   + Pro + the four Elementor add-ons, Yoast, Site Kit, Ivory Search, Dynific,
   and anything else the survey marked as drop. **Keep SMTP Mailer** —
   password-reset mail still has to send. Over SFTP: delete a physical
   `robots.txt` if present (fact 6) and any stale `advanced-cache.php` /
   `object-cache.php` drop-ins.
5. **Flip the switch over SFTP:** edit `wp-config.php` — add the
   `DISALLOW_FILE_MODS` define, the vip-config require (fact 7) and
   `define( 'EMPOSO_INSTALLER', true );` — then upload
   `client-mu-plugins/plugin-loader.php` → `wp-content/mu-plugins/`. The
   mu-plugin and its hardening are now live; wp-admin keeps working.
6. **Activate the theme**: Appearance → Themes → Emposo. Never before step 5 —
   the templates use `EMPOSO_CORE_DIR` and `\Emposo\Core\…` from the mu-plugin
   and fatal without it.
7. **Emposo → Installer**, in order: Preflight → Scaffold dry-run → review →
   Scaffold. On abort: the slug-conflict finder names the squatter (the live
   `/sitemap/` page is the known one if step 3 missed it) → delete → re-run.
8. Installer: Import dry-run (review) → Import 1/6 … 6/6 in order. Media
   failures are warnings and skips: fix the cause, re-run Import 2/6
   (idempotent).
9. Installer: **Verify — full**. Passes while `blog_public=0`.
10. Apply the AVIF MIME mechanism (A); verify
    `curl -sI https://emposo.de/wp-content/themes/emposo/assets/supplied/<any>-1600.avif`
    → `content-type: image/avif`.
11. Purge the host cache (per the survey's finding of what `x-scout-cache` is)
    and Cloudflare. If styles look stale, the SFTP client preserved mtimes
    (asset `?ver=` uses `filemtime`) — re-upload without mtime preservation and
    purge again.
12. Remove the WAF shield if used.
13. **Smoke test while still noindexed**: front page, nav routes, a case
    study, the three legal pages (real content, 200, no CSP console errors), a
    garbage URL → 404, the contact form opening a mail client to the
    configured recipient, the dashboard green, JS alive, and exactly one
    robots meta and one `<title>` in the page source — a missed SEO plugin
    would add a second. Work through `docs/launch-checklist.md`.

## D. Launch flip

1. Settings → Reading → **uncheck "Discourage search engines from indexing
   this site"** (`blog_public=1`) — one option, three effects: the noindex
   meta disappears, robots.txt stops disallowing, `wp-sitemap.xml` turns on.
2. Sanity: `curl -s https://emposo.de/ | grep -i robots` → no output;
   `robots.txt` is WordPress's virtual one with no `Disallow: /` and no stale
   Yoast block; `/wp-sitemap.xml` → 200; the legal pages still render. Purge
   Cloudflare once more.
3. **Publish the Cloudflare Redirect Rules**: each of the 27 retired URLs →
   its nearest German route (`/contact/` → `/kontakt/`, `/join-us/` →
   `/karriere/`, `/solutions/*` → the matching `/expertise/` page, …);
   `/en/*` → `/`; `/sitemap_index.xml` → `/wp-sitemap.xml`. The legal pages
   kept their URLs and need no redirect. Spot-check the top rows.
4. Post-launch verify: Installer → **Verify — routes, content, media** (bare
   full verify now fails by design, fact 8).
5. **Measure LCP and TTFB on the host.** Nothing in CI does this — a shared
   runner cannot reproduce host timings. The published performance bars are
   only reachable behind a full-page cache; if the host has none, the bars
   need renegotiating rather than quiet acceptance.
6. Submit `/wp-sitemap.xml` in Google Search Console (the property already
   exists via the old Site Kit setup). Set the real contact recipient
   (fact 13).
7. **Remove the installer**: delete `define( 'EMPOSO_INSTALLER', true );` from
   `wp-config.php` over SFTP. The check that it is gone:
   `/wp-admin/admin.php?page=emposo-installer` answers "not permitted" for an
   admin. `docs/security.md` tracks this as a standing item.

## E. Rollback

- **Before step C3** (old-content deletion): pure file rollback — remove
  `mu-plugins/plugin-loader.php` and the three `wp-config.php` lines over
  SFTP, reactivate the old theme and plugins in wp-admin. No DB restore.
- **After C3**: the C0 dump is the rollback artefact — restore it through the
  same panel tool or backup plugin that made it, then the file rollback above,
  then reactivate the old theme and plugins.
- The `install-<date>` tag from A identifies exactly which commit is on the
  host, so a re-upload after rollback is unambiguous.
- After a 1–2 week stable window: delete the old theme and deactivated plugin
  directories over SFTP, uninstall the temporary backup plugin if one was
  used, and clean out `wp-content/uploads/elementor/`.

## Verification summary

- Local rehearsal must pass the full installer sequence (B3), the eyeball list
  (B4), the negative gate test (B5) and `docs/launch-checklist.md` before
  anything touches the host.
- Live: full verify pre-launch (C9), routes+content+media post-launch (D4),
  the Emposo admin screen as the ongoing health view, plus the manual checks
  in C13 and D2.
- Host-only measurement: LCP and TTFB (D5).
- Teardown proof: the installer answers "not permitted" after D7.
