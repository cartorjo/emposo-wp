# Launch checklist

The manual pass. Everything here is something no gate in this repository
checks — either because it needs eyes (does the megamenu feel right?) or
because it needs the real host (does LCP hold behind the real cache?).

Automated coverage for context, so this list stays short: `npm run check`
proves DOM parity on all 41 routes, `npm run behaviours` proves 11 interaction
contracts, `npm run audit` proves budgets, axe and console cleanliness at 42
routes, and `wp emposo verify` proves the route, content and media contracts.
None of them looks at two viewports with reduced motion, and none of them runs
on the live host.

## 1. Route matrix — six routes × two viewports × two motion settings

Run at **390 px** and **1440 px**, each with `prefers-reduced-motion: no-preference`
and `reduce` (Chrome DevTools → Rendering → Emulate CSS media feature).

| Route | Why this one |
|---|---|
| `/` | Hero, count-up, every homepage section |
| `/expertise/` | Megamenu group landing, discipline grid |
| `/portfolio/` | The heaviest route (largest byte total) |
| `/case-studies/data2ai-platform/` | Detail template, metric, results list |
| `/branchen/health-pharma/` | Industry **with** related case studies |
| `/branchen/aerospace-defense/` | Industry with **none** — the related section must be absent, not empty |
| `/kontakt/` | The contact form and its mailto handoff |

At each combination, confirm:

- [ ] No horizontal scrolling; nothing clipped or overlapping.
- [ ] The sticky header sits on the content container, and the skip link is the
      first thing that takes focus.
- [ ] Megamenu (1440) and mobile menu (390) open, trap focus sensibly, and close
      on Escape.
- [ ] Count-up on `/` animates once with motion allowed, and shows final values
      immediately with `reduce` — never mid-animation.
- [ ] Smooth scrolling is active with motion allowed and off with `reduce`.
- [ ] Filters on `/portfolio/` and `/branchen/` change the visible cards, the
      count text, and the empty state.
- [ ] `/branchen/aerospace-defense/` shows no related-cases section at all.
- [ ] The contact form opens the mail client addressed to the configured
      recipient (see `emposo_contact_recipient`).
- [ ] Browser console clean — no errors, no CSP violations.

## 2. Keyboard and screen reader

- [ ] Tab from page load: skip link → header nav → main content, in that order.
- [ ] Every interactive control is reachable and has a visible focus ring.
- [ ] Filter buttons announce their pressed state.

## 3. Legal pages (the uncontracted routes)

- [ ] `/impressum/` and `/datenschutzerklaerung/` return **200**, show their
      real title and content, and are styled — not the 404 body.
- [ ] A garbage URL returns a real **404** with the 404 body.

## 4. On the host only

- [ ] AVIF is served as `image/avif`
      (`curl -sI .../assets/supplied/<any>-1600.avif`). Without this, browsers
      fall back to JPEG and the image budget breaks.
- [ ] **LCP and TTFB measured** on the live host, logged out, warmed. Nothing in
      CI measures these; the published bars assume a full-page cache. If the
      host has none, renegotiate the bars rather than quietly missing them.
- [ ] Exactly one `<title>` and, pre-launch, exactly one robots meta in the page
      source. Two of either means a plugin is still injecting through
      `wp_head()`.
- [ ] The Emposo admin screen: content counts match the contract, the security
      headers panel is green, and the contact-recipient warning reflects the
      address you intend to ship.

## 5. Launch flip (after everything above)

- [ ] `wp option update blog_public 1`.
- [ ] Robots meta gone, robots.txt no longer disallowing, `wp-sitemap.xml`
      returning 200.
- [ ] `wp emposo verify --routes --content --media` passes. Bare `verify` fails
      by design after launch — it asserts `blog_public === 0`.
