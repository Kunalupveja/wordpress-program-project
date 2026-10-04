# How to test everything

Every task, in the order they are easiest to check, with the exact thing to
look at and the result you should see.

Base URL in these examples: `http://wordpress-project.local`
(change it to whatever Local assigned to your site).

---

## 0. Before you start

1. Start the site in Local (it must be **Running**, not stopped).
2. Confirm the plugin is active: **WP Admin → Plugins → Programs Module**.
   If it is not, activate it. Activation is what seeds the 10 sample
   programs, the 2 program types and the featured images.
3. Flush permalinks once: **Settings → Permalinks → Save Changes**.
   (The plugin already flushes on activation; this just guarantees it.)
4. Confirm the theme is **project** (Appearance → Themes).

Quick sanity check that everything is up:

```bash
for u in / /programmes/ /programmes/undergraduate/ /programmes/postgraduate/ /programmes/computer-science-bsc/; do
  printf "%-40s %s\n" "$u" "$(curl -s -o /dev/null -w '%{http_code}' http://wordpress-project.local$u)"
done
```

Expected: `200 200 200 200 200`.

---

## 1. Task 2 — Content structure (CPT + taxonomy + seed data)

### Where to look
**WP Admin → Programs** and **WP Admin → Programs → Program Types**.

### What to check
- [ ] 10 programs are listed, 5 under each type.
- [ ] Every program has a **featured image** (the list screen shows a
      thumbnail; open a program and check the *Program image* box).
- [ ] Every program has an **Academic year** and a **Duration** value.
- [ ] **Program Types** contains exactly `Undergraduate` and `Postgraduate`.
- [ ] Permalinks read `/programmes/{slug}/` — hover *View* under a title.

### Command line (optional)
```bash
cd app/public
wp programs count            # -> 10 sample programs
wp programs seed --force     # re-seed (idempotent: does not duplicate)
wp programs remove           # delete only the sample data
wp post list --post_type=program --fields=ID,post_title,post_name
wp term list program-type --fields=term_id,name,slug,count
```

---

## 2. Task 3 — Custom fields and the React admin UI

### Where to look
**WP Admin → Programs → edit any program**. The *Program details* panel sits
below the editor.

### What to check
- [ ] The panel is **custom styled** (own colours, cards, pill buttons) — it
      must not look like the default WordPress meta boxes.
- [ ] **Academic year** and **Duration** text inputs persist after *Update*.
- [ ] **Content sections**: *Add section* appends a row.
- [ ] Each row can be **collapsed/expanded**.
- [ ] Each row can be **moved up / moved down** and **removed**.
- [ ] *Choose image* opens the **WordPress media library** (`wp.media`) and
      the chosen image thumbnail appears in the row.
- [ ] Change the order, save, reload — the new order persisted.
- [ ] The default *Custom Fields* box is gone (it was removed so there is one
      place to edit these values).

### Progressive enhancement
- [ ] Open the same screen with JavaScript disabled (or use
      *View source*): the panel falls back to plain text/textarea inputs and
      the program still saves correctly.

### Command line (optional)
```bash
wp post meta get <program-id> program_academic_year
wp post meta get <program-id> program_duration
wp post meta get <program-id> program_content_sections
```

---

## 3. Task 4 — Routing

Open each URL in a browser:

| URL | What you should see |
| --- | --- |
| `/programmes/` | All 10 programmes, heading **Programmes** |
| `/programmes/undergraduate/` | Only the 5 undergraduate programmes, heading **Undergraduate programmes** |
| `/programmes/postgraduate/` | Only the 5 postgraduate programmes |
| `/programmes/computer-science-bsc/` | The single program page |

- [ ] None of them 404s.
- [ ] The `<title>` changes per page (e.g. *Undergraduate programmes – …*).
- [ ] A **breadcrumb** trail is present and the links work.
- [ ] The "All programmes" heading is used on `/programmes/`.

### No-slug-conflict check
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://wordpress-project.local/programmes/nope/
# -> 404
```
A program whose slug happens to be `undergraduate` would still win over the
type archive, because the type rules are registered at the **top** of the
rewrite stack. To prove it, rename a program's slug to `undergraduate` and
reload `/programmes/undergraduate/` — you get the program, and
`/programmes/` still lists it.

### Flush on activation
Deactivate and reactivate the plugin, then load `/programmes/` immediately —
it must work without touching *Settings → Permalinks*.

---

## 4. Task 5 — Reusable program card

### Where to look
`/programmes/`, `/programmes/undergraduate/`, the home page, and the
*Related programmes* section of a single program.

### What to check
- [ ] Each card shows **image**, **title**, **academic year**, **duration**.
- [ ] Resize the window: **3 columns** wide → **2 columns** on tablet →
      **1 column** on a phone.
- [ ] Tab through the cards: a **visible focus ring** appears on every card
      link (never removed).
- [ ] Right-click a card image → *Inspect*: it has a non-empty `alt` (the
      program title when the attachment has no alt text).
- [ ] The same card markup is used in all four places (one component,
      `template-parts/program-card.php`).

---

## 5. Task 6 — Program detail page

Open `/programmes/computer-science-bsc/`.

- [ ] Hero **featured image**.
- [ ] Title.
- [ ] **Program type** shown as a link that goes to
      `/programmes/undergraduate/`.
- [ ] **Academic year** and **Duration** in the fact strip.
- [ ] Main body content from the editor.
- [ ] The **repeater content sections** render (image + title + description).
- [ ] **Back to programmes** link → `/programmes/`.
- [ ] **Apply now** link (URL comes from `programs_apply_url`, no hardcoded
      address; defaults to the page whose slug is `apply`).
- [ ] **Enquire now** button opens the enquiry pop-up.

---

## 6. Task 7 — Interactivity API filter

### In the browser
1. Go to `/programmes/`.
2. Click **Undergraduate**.
   - [ ] The page **does not reload** (no spinner in the tab).
   - [ ] Only the 5 undergraduate cards remain.
   - [ ] The URL becomes `/programmes/undergraduate/`.
   - [ ] The clicked pill gets `aria-current="page"`.
3. Click **Postgraduate**, then **All programmes**.
   - [ ] The visible set follows each click with no reload.
4. Press the browser **Back** button.
   - [ ] The filter follows the history entry.
5. Open DevTools → Network and click a filter.
   - [ ] A request to `/wp-json/wp/v2/programs?…` is made (the REST bonus).
6. Throttle the network to *Slow 3G* and click a filter.
   - [ ] A **Loading programmes…** status appears, then the cards update.

### Confirm there is no jQuery
```bash
curl -s http://wordpress-project.local/programmes/ | grep -c jquery
# -> 0
```

### No-JavaScript fallback
Disable JavaScript (DevTools → *Settings → Debugger → Disable JavaScript*)
and reload `/programmes/undergraduate/`:

- [ ] Exactly the 5 undergraduate cards are visible — server-rendered with
      the `hidden` attribute, so the correct subset shows before any script
      runs.
- [ ] Clicking a filter pill navigates to the matching page (a real `<a href>`).

### Automated store test (optional)
The filter's Interactivity store is also covered by a headless check that
drives the store outside a browser: visibility, REST fetch, empty state,
`pushState` and `aria-current`. See `docs/pull-requests.md` for the results.

---

## 7. Task 8 — Click tracking

### Where to look
Seven element types carry a `data-track` payload. View source on a program
page and on the listing and look for `data-track="{…}"`:

| Element | `label` | `sectionName` |
| --- | --- | --- |
| Program card link | `program-card` | `programme-listing` / `programme-related` |
| Filter item | `filter-all`, `filter-undergraduate`, `filter-postgraduate` | `programme-filter` |
| Breadcrumb link | `breadcrumb-home`, `breadcrumb-programmes`, … | `breadcrumbs` |
| Program type link | `programme-type` | `programme-overview` |
| Back link | `back-to-programmes` | `programme-footer` |
| Apply link | `apply-now` | `programme-actions` |
| Enquire button | `enquire-now`, `enquire-now-sticky` | `programme-actions`, `sticky-cta` |

The payload shape is always:

```json
{"type":"click","event":"link","link":{"label":"…"},
 "content_data":{"sectionName":"…","uiComponent":"componentLink"}}
```

### Watch it fire
1. Open `/programmes/`, DevTools → Network → filter `track`.
2. Click a card, a filter pill, a breadcrumb.
   - [ ] Each click sends a `POST /wp-json/programs/v1/track`.
   - [ ] Requests use `sendBeacon` (shown as type *beacon/ping*) with a
         `fetch(keepalive)` fallback.
3. Clicking a tracked element never navigates away or errors — tracking is
   non-blocking and never throws.

### Inspect the log
```bash
tail -f app/public/wp-content/uploads/programs-tracking/tracking-$(date +%F).log
```

Each line is one JSON object:

```json
{"timestamp":"2026-10-03T16:38:51+00:00","page_url":"/programmes/","payload":{"type":"click","event":"link","link":{"label":"program-card"},"content_data":{"sectionName":"programme-listing","uiComponent":"componentLink"}}}
```

- [ ] One JSON object per line (JSON Lines).
- [ ] One file per day: `tracking-YYYY-MM-DD.log`.
- [ ] `page_url` is a **path only** — no query string, no personal data.

### The log is private
```bash
curl -s -o /dev/null -w "%{http_code}\n" \
  http://wordpress-project.local/wp-content/uploads/programs-tracking/tracking-$(date +%F).log
# -> 403 on Apache (deny rules written by the plugin)
```
The plugin writes `.htaccess`, `index.php` and `web.config` into the folder.
On nginx, add the snippet in the README — Local's nginx does not read
`.htaccess`.

### Reject bad payloads
```bash
B=http://wordpress-project.local

# valid -> 200 {"logged":true}
curl -s -X POST "$B/wp-json/programs/v1/track" -H "Content-Type: application/json" \
  -d '{"type":"click","event":"link","link":{"label":"program-card"},"content_data":{"sectionName":"programme-listing","uiComponent":"componentLink"}}'

# wrong type -> 400
curl -s -X POST "$B/wp-json/programs/v1/track" -H "Content-Type: application/json" \
  -d '{"type":"submit","event":"link","link":{"label":"x"}}'

# unknown event -> 400
curl -s -X POST "$B/wp-json/programs/v1/track" -H "Content-Type: application/json" \
  -d '{"type":"click","event":"hover","link":{"label":"x"}}'

# empty label -> 400
curl -s -X POST "$B/wp-json/programs/v1/track" -H "Content-Type: application/json" \
  -d '{"type":"click","event":"link","link":{"label":""}}'

# oversized -> 413
curl -s -X POST "$B/wp-json/programs/v1/track" -H "Content-Type: application/json" \
  --data-binary "{\"type\":\"click\",\"event\":\"link\",\"link\":{\"label\":\"$(printf 'a%.0s' {1..5000})\"}}"
```

Unknown keys (for example an injected `email` inside `content_data`) are
**dropped**, not stored.

---

## 8. Enquiry pop-up and thank-you screen

1. On a program page, click **Enquire now**.
   - [ ] The dialog opens; focus moves into the **Full name** field.
   - [ ] The background does not scroll; **Escape** and the ✕ close it.
   - [ ] Tab stays inside the dialog (focus trap).
   - [ ] After closing, focus returns to the button you opened it from.
2. Submit with an **empty name** and a **bad email**.
   - [ ] Inline errors appear next to the fields; nothing is sent.
3. Submit correctly.
   - [ ] A short **loading** state, then the **thank-you screen** replaces
         the form — no page reload.
4. Check the lead file:
   ```bash
   cat app/public/wp-content/uploads/programs-tracking/leads-$(date +%F).log
   ```
5. With JavaScript disabled, the *Enquire now* button does not dead-end: the
   page shows a plain contact block instead.

---

## 9. Mobile sticky Enquire CTA (Task 1.1)

1. Open a program page and narrow the window below **768px** (or use
   DevTools device toolbar).
   - [ ] A bar is pinned to the bottom with **Enquire now**.
   - [ ] It stays put while you scroll.
   - [ ] Tapping it opens the pop-up; while the pop-up is open the bar is
         hidden.
   - [ ] The footer is not covered by the bar.
2. Widen past 768px — the bar disappears (the in-page button takes over).

---

## 10. Home page and related programmes

- [ ] `/` shows the hero, three **featured programmes** (real cards), the two
      program types and links into `/programmes/`.
- [ ] A single program shows a **Related programmes** section with up to 3
      other programmes of the same type, never including itself.

---

## 11. Accessibility sweep

- [ ] Every interactive element is reachable by **Tab** and shows a visible
      focus ring.
- [ ] Filter pills expose `aria-current` for the active one.
- [ ] The dialog has `role="dialog"`, `aria-modal="true"`, is labelled, traps
      focus and closes on **Escape**.
- [ ] Images have `alt` text; decorative marks are `aria-hidden`.
- [ ] The loading/empty region is a `role="status"` `aria-live="polite"`
      region, so screen readers announce filter results.
- [ ] Headings run in order (`h1` per page, `h2`/`h3` beneath).

---

## 12. Code-level checks

```bash
cd app/public/wp-content/plugins/programs-module
npm install
npm run build          # admin bundle + native ES modules
npm run lint:js
npm run lint:css
```

- [ ] No `query_posts()` anywhere:
      ```bash
      grep -rn "query_posts" wp-content/ || echo "none"
      ```
- [ ] No jQuery on the front end:
      ```bash
      grep -rn "jquery" wp-content/plugins/programs-module/src || echo "none"
      ```
- [ ] No hardcoded site URLs (all routes come from `home_url()` /
      `rest_url()` / `Programs_Routing`):
      ```bash
      grep -rn "http://\|https://" wp-content/plugins/programs-module/includes wp-content/themes/project/*.php
      ```
      Only `https://wordpress.org/`-style references and doc links should
      appear.
