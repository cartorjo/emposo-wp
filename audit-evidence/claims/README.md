# Claims audit

`wp claude audit-claims` fetches each route's **rendered** copy over HTTP and
checks its assertions against the approved facts sheet (`data/facts.md`). This
directory is where a dated report lands. It has two parts; one is done, one is
pending a credential.

## Alt-text pass — complete, nothing to do

`wp claude alt-text` writes alt text for editor-uploaded images; it never
touches the imported set, and never overwrites without `--force`. As of
2026-09-20 the library holds **22 attachments, 0 missing alt text** — every
image carries the description imported from the audited static build (and
`wp emposo verify --media` asserts that). There is nothing for the command to
generate until an editor uploads a new image, so no report is produced here.

## Claims audit — pipeline verified, findings pending a key

The extraction half runs without any credential, and on 2026-09-20 it succeeded
for **all 41 content routes** (`audit-claims --dry-run`), from `/` at 3,716
chars down to `/cookies/` at 501 — so the fetch-and-parse path is sound. The
graded pass, which sends that copy to the Anthropic API, needs
`ANTHROPIC_API_KEY`, which this environment does not have (`.wp-env.override.json`
carries no key; `wp claude doctor` reports "not configured"). No findings can be
produced without it, and none are invented here.

To produce the report once a key is in `.wp-env.override.json` (see the README's
"The Anthropic key"), from `emposo-wp/` with wp-env running:

```bash
# Non-obvious: the cli container cannot reach the site at localhost:8888,
# its own public URL — pass the container hostname instead.
npm run wp -- claude audit-claims --base=http://wordpress \
  > audit-evidence/claims/claims-YYYY-MM-DD.txt
```

`audit-claims` exits 0 even when it flags copy, unless `--strict` is passed —
it is a reconnaissance pass, not a gate. Triage its output into one follow-up
issue per unsupported claim. Do **not** edit copy in place: the copy is DOM-
parity-checked against the frozen reference, so a wording change has to go
through the exporter/importer and a re-baseline, not a direct template edit.
