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

## Locales

Since 1a4ddaf (2026-09-27) the reference publishes an American English twin of
every page under `/en/`, and each step above covers both locales:

- **Routes:** `tools/routes.mjs` adds the English entries from `pages.en.mjs`
  whenever the reference's `PUBLISHED` list includes `en`. Every route carries
  `locale`, and English bodies come from `pages/en/` and `sections/en/`. The
  contract is 70 routes plus two 404s (`/__parity-404__/`, `/en/__parity-404__/`).
- **Records:** English Pages sit under the `en` parent Page. English case
  studies are the `emposo_case_study_en` type (rewrite slug `en/case-studies`),
  linked to their German record by `_emposo_translation_of`, and they share its
  terms. English term, person, image and option text lives in `_en` meta and
  `_en` options (`emposo_facts_en`, `emposo_jobs_en`, `emposo_interests_en`). No
  translation plugin is used.
- **Strings:** `export-content.mjs` also writes
  `client-mu-plugins/emposo-core/data/i18n.json` (UI strings, the English path
  map, the English case-study slugs) from `content/i18n.mjs`. `inc/i18n.php`
  reads it. `t()` and `localize_path()` are lookups, so there is no logic to
  port.
- **Templates:** port-partial.mjs localizes hrefs in English sources and
  expands the page hero with the English locale. It turns three reference
  tokens into PHP: `{{t:key}}` → `emposo_t()`, `{{href:/path/}}` →
  `emposo_href()`, and `{{LANGSWITCH:slot}}` → `emposo_lang_switch()`.
- **Assets:** sync-assets.mjs also scans `assemble.mjs` for the icons it
  inlines itself, such as the switch's globe.
- **Gates:** `npm run behaviours` runs every scenario in both locales and tests
  the language switch. The reference's own `--target=static` run currently
  fails "console clean" under `npx serve` because of a cross-document
  view-transition abort. WordPress is not affected. The defect is
  cartorjo/weave-clone#40.

## Where WordPress deliberately differs: the contact form

Since 2026-09-27 the contact form posts to WordPress
(`client-mu-plugins/emposo-core/inc/contact.php`); the reference keeps its
mailto: handoff, because the static build has no backend. Two mechanisms keep
that one difference from spreading on a re-pin:

- **`tools/sync-assets.mjs` `LOCAL_OVERRIDES`** never overwrites
  `assets/js/06-work.js`. When the reference changes its copy, sync reports
  drift (and `--check` fails) until the change is merged by hand and the new
  hash pinned there. Keep the local submit handler: it validates and lets the
  browser post, with no mailto: redirect.
- **`tools/parity.config.json` `allowedDeltas`** carves the
  `<form … data-contact-form>` region out of the diff on `/`, `/kontakt/`,
  `/en/` and `/en/contact/`, and only there. The region must still appear
  exactly once on both sides. Everything around it stays strict. Changes to the
  reference's form fields (labels, interests, ids) are therefore not caught by
  parity: port them into `themes/emposo/parts/contact-form.php` by hand.

## Known limits

- `npm run audit` whole-page byte totals used to vary run to run on the
  reference itself (lazy images and srcset candidates fetched during the width
  sweep were still being counted). Since #40 the total is snapshotted after a
  paced scroll pass, once every image has settled, and before the width sweep,
  so repeat runs are byte-identical. Initial-load totals still race on
  `/branchen/` and `/en/industries/`: two images at the fold may or may not be
  fetched before `networkidle`. They only feed the absolute budgets.
- The head block names `https://emposo.de/assets/share/*.jpg` and
  `/assets/brand/emposo-logo-organization.png`. These are not theme assets, so
  the launch webroot must serve them at those root paths (copy them in at
  cutover, or add a rewrite; #41).
- The three legal pages are ported templates, not editable in wp-admin, and
  their slugs collide with the live legal pages kept at cutover (#42). The
  install runbook's keep-list and redirect prose predate the re-pin: follow
  docs/redirect-map.md, not the runbook's inline examples.
- Industry filter tokens and labels are meta alongside the terms, terms need
  `_emposo_term_order` to render, and JSON-LD dates are frozen at the pin (#43).
