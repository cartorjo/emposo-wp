# Launch plan: WordPress on emposo.de, without a staging subdomain

**Owner decision, 2026-09-27:** WordPress launches on emposo.de. There is no
staging subdomain. This plan replaces sections C to E of
`docs/install-runbook.md` (the in-place cutover), which failed and was rolled
back on 2026-09-20.

## Why this is safe when the in-place cutover was not

The 2026-09-20 attempt ran **inside the live install**. Our theme was
activated among the old plugins; Elementor Pro's Theme Builder kept the old
header and footer; and switching the plugin stack off and on over WP-CLI
crashed on PHP 8.2 (Posimyth SDK, WPML). Rollback needed a database restore,
plus an Elementor CSS rebuild.

This plan never touches the old install:

- **A second WordPress on the same host**, in `~/site/emposo-next/`, outside
  the webroot, so nothing public can reach it.
- **Same database, different table prefix** (`emp_`). No new database is
  needed, and the old tables are never written.
- **Preview over an SSH tunnel** (`php -S` on the host, `localhost:8099` on the
  Mac): every gate runs against the real host before launch.
- **Cutover is two directory renames** (`public_html` ↔ `emposo-next`), and
  so is rollback. The old theme, plugins, WPML content and database stay
  exactly as they are.

## Who does what

The agent has **no read access to production**: host reads were denied by
the permission policy on 2026-09-27. So Jose runs every host command, with
`!` in the Claude session or in his own terminal, and pastes the output. The
agent prepares the commands, runs every gate from the Mac through the tunnel,
and says go or no-go. If Jose later grants SSH to the agent, the same steps
apply with the agent typing them and Jose approving each one.

## Never run these on the shared database

Both installs share one database. These commands reach the other install's
tables:

| Never | Why |
|---|---|
| `wp db reset`, `wp db drop`, `wp db clean` | Drops every table: the old site too. |
| `wp db import <file>` | Replaces tables from a dump that may carry the other prefix. |
| `wp search-replace … --all-tables` / `--all-tables-with-prefix` without `--path` scoping checked | Rewrites the old site's data. |
| `npm run wp:bootstrap` / `wp:clean` on the host | Local wp-env scripts; the host is nginx, not Apache. |

**Rollback is the rename, never the database.**

---

## Phase 0: Owner decisions and prerequisites (before build day)

1. **Merge PR #39** (the 42a7c6d resync) and this plan's PR. Tag the release:
   `git tag launch-2026-MM-DD && git push origin launch-2026-MM-DD`.
2. **Legal texts.** The new install has none of the old pages, so the static
   build's Impressum, Datenschutzerklärung and Nutzungsbestimmungen are what
   launches (#42). Compare them with the live pages (Geschäftsführung, HRB,
   controller details) and confirm.
3. **Cloudflare plan tier.** The redirect map has 37 rules
   (`docs/redirect-map.md`), and Cloudflare's free tier allows 10 single
   redirects. Either check that the plan covers them (Bulk Redirects, or a
   paid tier), or decide on the fallback: the same map served by the
   mu-plugin on `template_redirect`. That fallback is new code and needs
   approval first.
4. **Search Console.** Verification rides on Site Kit's meta tag, which
   disappears with the old site. Add **DNS TXT verification** now.
5. **Content freeze** on the old site from build day until launch.
6. **The window.** Pick a low-traffic slot of about 30 minutes, with Jose and
   the agent both available. Build day is the day before.
7. **Backups after launch.** Old site: ManageWP. New site: decide the
   replacement (host panel backup, or a nightly `wp db export` cron) before
   the old stack is retired.

## Phase A: Read-only probes (Jose runs, pastes output)

Nothing here changes the live site, except A3, which writes and deletes one
file.

**A1. Host layout**

```bash
ssh -p2223 emposodelive@176.74.18.89
ls -la ~/site ~/site/public_html | head -60
stat ~/site/public_html
df -h ~
wp config get table_prefix --path=$HOME/site/public_html
wp config get DB_NAME --path=$HOME/site/public_html
wp core version --path=$HOME/site/public_html
ls /usr/bin/php* /usr/local/bin/php* /opt/*/bin/php* 2>/dev/null
php -v | head -1
stat -c '%U:%G' ~/site/public_html/wp-content/uploads
cat ~/site/public_html/.user.ini 2>/dev/null
```

What to read from it:

- `public_html` must be a plain directory, not a symlink, and `~/site` must be
  writable (the rename).
- List the non-WordPress files in the webroot (`.well-known/`,
  `google*.html`, `BingSiteAuth.xml`, anything else). They are carried over.
- Note the old table prefix; `emp_` must differ from it.
- Note the PHP binary matching FPM's version (A3).

**A2. From the Mac: caching and MIME type**

```bash
curl -sI https://emposo.de/ | grep -iE '^(server|x-scout|cache-control|age|cf-cache-status)'
curl -sI https://emposo.de/wp-content/themes/emposo/assets/supplied/data2ai-640.avif | grep -iE '^(HTTP|content-type)'
```

The second URL is one of the inert files staged on 2026-09-20. If it isn't
`image/avif`, the launch needs the **Cloudflare Transform Rule** for `*.avif`.
The first line tells us how the host cache (`x-scout-cache`) is purged, or its
TTL.

**A3. The PHP-FPM probe** (one file, deleted straight after)

```bash
cat > ~/site/public_html/_probe-7f3c.php <<'PHP'
<?php header('Content-Type: text/plain');
echo PHP_VERSION, "\n", PHP_SAPI, "\n", $_SERVER['DOCUMENT_ROOT'], "\n", realpath('.'), "\n";
foreach (['opcache.enable','opcache.validate_timestamps','opcache.revalidate_freq','realpath_cache_ttl','user_ini.cache_ttl','open_basedir','auto_prepend_file','max_execution_time','memory_limit'] as $k) echo $k,'=',ini_get($k),"\n";
echo get_current_user(), ' uid=', getmyuid(), "\n";
PHP
curl -s https://emposo.de/_probe-7f3c.php; rm ~/site/public_html/_probe-7f3c.php
```

What it answers:

- **FPM's PHP version.** Site Health reported 8.5.10 but CLI `php -v` said
  8.2.33. The preview and WP-CLI must use the FPM version's binary.
- **Opcache.** With `validate_timestamps=0` the swap needs an opcache reset
  (C5).
- **`auto_prepend_file`**, i.e. Wordfence (C3).
- **The FPM user.** It must be able to write the new `wp-content/uploads`.

**A4. Can the host run the preview?**

```bash
cd ~ && echo '<?php echo "ok";' > /tmp/emp-probe.php && (php -S 127.0.0.1:8099 -t /tmp & sleep 1; curl -s 127.0.0.1:8099/emp-probe.php; kill %1); rm /tmp/emp-probe.php
```

If the host refuses the bind or kills the process, phase B's gates run after
the swap instead (see "Fallback" at the end).

## Phase B: Build day (the day before, about 2 to 3 hours, nothing public)

**B0. Local rehearsal on the host's FPM PHP version.** Set `"phpVersion":
"<A3 version>"` in `.wp-env.override.json`, then run `npm run wp:clean`,
`npm run wp:start` and `npm run wp:bootstrap`, then `npm run check`, `npm run
behaviours` and `npm run wp -- emposo verify`. A deprecation notice printed
into the page would break parity and the CSP, and it would only show up here.

**B1. Fresh backup** of the whole database (both prefixes) and the old
webroot. Pull a copy off the host.

```bash
mkdir -p ~/backups && cd ~/backups
wp db export db-pre-launch-$(date +%Y%m%d-%H%M).sql --path=$HOME/site/public_html
tar czf files-pre-launch-$(date +%Y%m%d-%H%M).tgz -C ~/site public_html
```

**B2. Core, in the new directory.** Use the version the parity work was
proven on (`.wp-env.json` `core`: the 7.1 branch). Match its exact patch
release.

```bash
mkdir ~/site/emposo-next && cd ~/site/emposo-next
wp core download --version=<7.1.x> --locale=de_DE
```

**B3. `wp-config.php` for the new install.** Same DB credentials as the old
one (A1), and `$table_prefix = 'emp_';`. Fresh salts come from
`wp config shuffle-salts`. Add the hardening block from `docs/security.md` §2,
plus:

```php
// Preview over php -S (tunnel) vs production; no edit needed at the swap.
$emposo_origin = PHP_SAPI === 'cli-server' ? 'http://127.0.0.1:8099' : 'https://emposo.de';
define( 'WP_HOME', $emposo_origin );
define( 'WP_SITEURL', $emposo_origin );
```

**B4. Install and ship the code** from the release tag. On the Mac, run
`git archive launch-<date> themes/emposo client-mu-plugins | gzip >
launch.tgz`, upload it, then on the host:

```bash
cd ~/site/emposo-next
tar xzf ~/launch.tgz
mv themes/emposo wp-content/themes/emposo
mkdir -p wp-content/mu-plugins && cp -r client-mu-plugins/* wp-content/mu-plugins/ && rm -rf themes client-mu-plugins
wp core install --url=https://emposo.de --title='Emposo' --admin_user=<not admin> --admin_email=<jose> --skip-email
wp theme activate emposo
wp option update blog_public 0
wp --user=1 emposo scaffold
wp --user=1 emposo import all
wp emposo verify
```

Also delete core's default themes and plugins (`wp theme delete
twentytwenty…`, `wp plugin delete akismet hello`).

**B5. Stubs that make the rename safe.** Without them, a cached PHP setting
would fatal for up to 5 minutes.

```bash
cd ~/site/emposo-next
printf '<?php\n// Placeholder: FPM may still prepend the old Wordfence WAF for user_ini.cache_ttl after the swap.\n' > wordfence-waf.php
printf '<?php\n// Placeholder: the realpath cache may still report the old object-cache.php for ~2 min after the swap.\n' > wp-content/object-cache.php
cp -a ~/site/public_html/.well-known . 2>/dev/null   # plus the verification files listed in A1
```

The object-cache stub defines no `wp_cache_init`, so core uses its built-in
cache. The Wordfence stub is a no-op. Neither is ever meant to run.

**B6. Share images and the Organization logo (#41).** The head names them at
the site root:

```bash
mkdir -p assets/share assets/brand
# upload reference/static/assets/share/*.jpg and assets/brand/emposo-logo-organization.png into them
```

**B7. Preview and gates.** On the host, run `cp <router> .preview-router.php`
(the file is `scripts/preview-router.php` from the tag), then:

```bash
PHP_CLI_SERVER_WORKERS=8 <fpm-php> -S 127.0.0.1:8099 .preview-router.php
```

On the Mac, in a second terminal:

```bash
ssh -p2223 -N -L 8099:127.0.0.1:8099 emposodelive@176.74.18.89
```

Then the agent runs, from the Mac:

```bash
node tools/parity.mjs --strict --base=http://127.0.0.1:8099
node tools/behaviours.mjs --target=wp --base=http://127.0.0.1:8099
node tools/audit.mjs --base=http://127.0.0.1:8099     # one red route = measurement noise (#40), re-measure
```

**Go for the window only if** strict parity passes 36/36, behaviours pass
9/9, and verify passes. Jose also clicks through the preview in a browser at
`http://127.0.0.1:8099` and checks the legal pages. Then stop the server
and delete `.preview-router.php`.

**B8. Cloudflare, prepared but not live:**

- Redirect rules entered **disabled**, in the order of `docs/redirect-map.md`.
- The AVIF Transform Rule, if A2 said so.
- Note the current state of Email Obfuscation, Rocket Loader and Auto Minify.

## Phase C: The window (about 15 to 20 minutes)

Rollback criteria are decided now, not during the window: **any 5xx, any route
failing strict parity, or any CSP error in the browser console → roll back.**

1. **Fresh database export** (both prefixes), as in B1, into `~/backups/`.
2. **Cloudflare:** Email Obfuscation, Rocket Loader and Auto Minify **OFF**.
   Obfuscation injects a script the CSP blocks, and it garbles the mailto
   contact path.
3. **Make the new install indexable**, still private: run `wp option update
   blog_public 1 --path=$HOME/site/emposo-next`. The live site is indexed
   today, so a noindex gap after the swap would only hurt.
4. **The swap:**

   ```bash
   cd ~/site && mv public_html public_html-old-$(date +%Y%m%d) && mv emposo-next public_html
   ```

5. **Opcache reset,** only if A3 showed `validate_timestamps=0`:

   ```bash
   echo '<?php opcache_reset(); echo "reset";' > ~/site/public_html/_oc-9d2a.php
   curl -s https://emposo.de/_oc-9d2a.php; rm ~/site/public_html/_oc-9d2a.php
   ```

6. **Purge** Cloudflare (everything) and the host cache (mechanism from A2).
7. **Smoke test,** from the Mac:

   ```bash
   node tools/parity.mjs --strict --indexable --base=https://emposo.de
   node tools/behaviours.mjs --target=wp --base=https://emposo.de
   curl -s https://emposo.de/robots.txt; curl -sI https://emposo.de/wp-sitemap.xml | head -1
   ```

   `--indexable` checks that the preview `X-Robots-Tag` header is gone.
   On the host, run `wp emposo verify --routes --content --media --invariants
   --security --path=$HOME/site/public_html`. A bare verify asserts
   `blog_public=0` and fails by design.
   Jose opens `/`, `/branchen/` and one case in a browser with the console
   open.
8. **Go live on redirects:** enable the Cloudflare redirect rules and
   spot-check `/about/`, `/solutions/`, `/en/` and `/sitemap_index.xml`.
9. **Search Console:** submit `/wp-sitemap.xml`.
10. **Mail:** run `wp eval 'var_dump( wp_mail( "<jose>", "emposo test", "ok" ) );'`.
    If it fails, password resets won't mail: set passwords with `wp user
    update`, and add SMTP later.

## Rollback (under a minute, at any point in C)

```bash
cd ~/site && mv public_html emposo-next && mv public_html-old-<date> public_html
```

Then purge Cloudflare and the host cache, disable the redirect rules, and
(only if C5 ran) reset opcache again. The old site comes back exactly as it
was, because nothing of it was changed.

## After launch

- **T+1 h:** run parity, behaviours and verify again. Check the nginx error
  log for PHP notices (`~/logs/`).
- **T+1 day:** check Search Console coverage and the 404s from the old URLs.
  Measure LCP and TTFB on the host (CI cannot).
- **T+14 days** (the rollback window), if all is well:
  - Delete `~/site/public_html-old-<date>`, and remove the two stubs from B5
    (they have done their job once the caches rolled over).
  - Retire the old tables: list them with `wp db tables '<old-prefix>*' --all-tables`
    and drop them one by one, after a final export. **Never** with `wp db reset`.
- **Replacements** for what the old stack provided:
  - Backups (Phase 0 item 7).
  - Wordfence: the mu-plugin hardening and `docs/security.md` §2 cover the
    surface. Add login rate-limiting at Cloudflare if wanted.
  - SMTP (C10).

## Fallback if the host will not run the preview (A4 fails)

Build B0 to B6 the same way, then do the window with the gates moved into C7
against the live site. The swap and rollback each take under a minute, so a
failed gate costs one minute of the new site being visible. Keep the old
directory's name ready in the terminal, so the rollback command is one keystroke
away.
