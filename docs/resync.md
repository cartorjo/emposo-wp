# Re-pinning the static reference

How to move `reference/static` to a newer weave-clone commit and bring the
WordPress port back to parity. Done this way for 3371afb → 42a7c6d on
2026-09-27 (188 upstream commits, one working day). Every step ends in a gate;
run each with its real exit code, never piped through `tail` or `grep`.

## 1. Pin and re-measure

```bash
git -C reference/static fetch origin && git -C reference/static checkout <sha>
npm run baseline                 # re-measures the static site into audit-evidence/static-baseline.json
node tools/parity.mjs --target=static   # the reference must pass its own invariants
```

If `--target=static` fails, a check has gone stale against a legitimate upstream
change. Fix the check before touching WordPress. At 42a7c6d, examples were the
privacy page's scrollable `.legal-table` (the clip probe now skips horizontal
scroll regions) and the verbatim Hays legal text (the branding rule skips it).

## 2. Regenerate the contracts

```bash
node tools/export-routes.mjs     # routes.json: routes, titles, descriptions, head block
node tools/export-content.mjs    # site-export.json: terms, cases, people, options
node tools/sync-assets.mjs       # js, fonts, vendor, supplied images, icons, brand SVGs
```

The head block (canonical, OG/Twitter, JSON-LD) is copied from the reference
documents into `routes.json` (`headMeta`), because its dates come from the
static repository's git history. If `export-content.mjs` throws, upstream moved
a data source. Its errors name the file.

## 3. Re-port the templates with a three-way merge

The templates in `themes/emposo/parts/` are port-partial.mjs output plus a few
hand edits (the contact form reads options; kontakt and the sales CTA include
the form through `emposo_part_html()`). To keep the edits, port the OLD pin
into a scratch tree as the merge base, port the NEW pin as theirs, and merge:

```bash
git -C reference/static worktree add --detach /tmp/static-old <old-sha>
PORT_STATIC_ROOT=/tmp/static-old PORT_THEME_ROOT=/tmp/base node tools/port-partial.mjs <src> <target>
PORT_THEME_ROOT=/tmp/theirs node tools/port-partial.mjs <src> <target>
git merge-file themes/emposo/<target> /tmp/base/<target> /tmp/theirs/<target>
```

`parts/head.php` is hand-maintained and is not re-ported. Delete the templates
whose source is gone, and port new sources directly. Afterwards run
`npm run phpcbf`: it re-indents with tabs, and parity ignores inter-tag
whitespace.

## 4. Port the render.mjs diff

`client-mu-plugins/emposo-core/inc/fragments.php` is a function-for-function port
of `content/render.mjs`, in the same order. Read
`git -C reference/static diff <old> <new> -- content/render.mjs` and apply each
hunk to its PHP twin. Data comes from the database (posts, terms, options),
never from the export.

## 5. Styles and scripts

Copy `styles/*.css` into `themes/emposo/src/styles/`, byte-identical, except:

- `main.css` keeps its WordPress `@source` block in place of the reference's.
- `00-fonts.css` uses `url('../fonts/…')`, because site.css ships in
  `assets/css/`.

Then run `npm run build:css && npm run lint:css && npm run class-inventory`.

## 6. Gates

```bash
npm run wp:clean && npm run wp:start && npm run wp:bootstrap
npm run wp -- emposo verify
npm run check                    # includes parity --strict and the drift check
npm run phpcs && npm run phpstan
npm run behaviours
npm run audit
```

Then update the counts that prose carries: README, audit-evidence/README.md,
launch-checklist, workflow comments, and the docblocks that name route or case
counts.

## Known limits

- `npm run audit` whole-page byte totals are not reproducible run to run, even
  for the reference against itself (lazy images fetched during the width sweep
  can still be in flight when the sum is taken). A single-route failure on the
  2 % whole-page tolerance needs a second measurement before it counts as a
  regression.
- The head block names `https://emposo.de/assets/share/*.jpg` and
  `/assets/brand/emposo-logo-organization.png`. These are not theme assets, so
  the launch webroot must serve them at those root paths (copy them in at
  cutover, or add a rewrite).
