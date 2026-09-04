# 11 — Risks, owner decisions, verification list

## 1. Risk register

| # | Risk | Sev. | Mitigation | Where |
|---|---|---|---|---|
| R01 | Tailwind 4.3 semantics (T1–T11) assumed, not tested locally | high | phase 1 fixture; pin exact version | 04 §4 |
| R02 | Flex runs Sonata's recipe `unconfigure` on replacement and deletes user config | high | `--no-plugins --no-scripts` path; verify the recipe file list online before phase 5 | 07 §1, 10 §1 |
| R03 | recomaty-panel's 55 cell templates and 15 page templates carry Bootstrap/AdminLTE markup (`callout` ×316, `box` ×22, `btn` ×76, inline pastel colours) and render unstyled until ported | high | itemised in 10 §1 (about 25 of the 46 hours); `.adm-*` components designed for them; dark variants via tokens | 10 |
| R04 | Stateless CSRF: any future fetch-based POST is rejected after the session's first double-submit success | medium | 1.0 has no fetch POST; rule J9 for post-1.0 flows | 06 §5 |
| R05 | Native date/time inputs: fields whose `format` is not HTML5-compatible submit unparsable values | medium | per-field `format` list in 10 §1 step 13; PHPUnit widget tests; acceptance F3/F4 | 06 §4 |
| R06 | Hand-written combobox misses ARIA/keyboard details | medium | ARIA 1.2 pattern checklist, axe, keyboard Panther scenario; fallback: ux-autocomplete field for the one form usage | 06 §3 |
| R07 | `replace: 4.43.0` goes stale when a persistence bundle raises its floor | medium | weekly `upstream-watch`, sync within one minor | 07 §10 |
| R08 | Large one-time Rector/CS diff makes upstream cherry-picks conflict | medium | single mechanical commit; `git apply -3`; P6 files merged by hand | 07 §10 |
| R09 | MongoDB fork's Panther suite fails until association templates exist | medium | informational nightly; first backlog item | 08 §7, 09 |
| R10 | Sonata's tests pin Bootstrap markup (452 render expectations, form layout XPath) | medium | envelope frozen; deliberate re-baselining | 08 §1 |
| R11 | Dark mode regressions in app templates (hard-coded light colours) | low | tokens with `dark:` variants during the cell-template port; X1 scenario | 10 §1 step 10 |
| R12 | Two Stimulus applications; a `sonata-*` identifier registered twice | low | rule R2; `DeferredTemplateTest`-style guard in the app's Behat suite | 05 §5 |
| R13 | App classes named like Tailwind utilities (`mt-10`, `hidden`) change meaning once the app compiles Tailwind | low | rename `.mt-10`; `hidden` is fine once `col-md-*` is gone | 10 §1 step 11 |
| R14 | FA7 renames (one v4-only name in the app; aliases could be dropped in a future FA major) | low | `clock-o` renamed; CSS contract checks the shipped icon names against `icons.yml` at build time | 04 §8 |
| R15 | Popups clipped by overflow wrappers (combobox listbox, dropdowns inside cards) | low | z-index ladder; listbox positioned inside the wrapper with `overflow: visible` cards; Panther coverage | 04 §3 |
| R16 | Stimulus 3.2.2 is three years old; a 4.x could appear during the project | low | `versions-watch`; controllers use only documented 3.x API | 07 §2 |

## 2. Owner decisions (none blocks phase 0)

1. **PHP floor**: `^8.4` (fork convention, recommended) or `^8.5` (what the app runs).
2. **Font**: self-hosted Outfit Variable (+ about 160 KB for two axes, recommended, TailAdmin's face)
   or the system stack with Outfit opt-in.
3. **Dark-mode default**: `system` (recommended) or `light`.
4. **Action bar**: button row without the upstream "2+ buttons → dropdown" heuristic (recommended, T7)
   or keep the heuristic.
5. **Node line**: build on 24 LTS with 26 in the CI matrix (recommended) or 26 only.
6. **Autocomplete**: hand-written combobox for `ModelAutocompleteType` (recommended, J5) or swap the
   app's single form usage to its existing ux-autocomplete field and ship only the filter variant.
7. **Date fields**: keep form-extensions' picker types with HTML5 formats (recommended, one option
   per field) or move the app's twelve fields to Symfony core types.
8. **`qs`**: keep (recommended) or replace with a hand-written nested-name parser to reach zero
   runtime dependencies besides Stimulus.
9. **Cookie attributes**: `SameSite=Lax` + `Secure` (recommended) versus `__Host-` prefix (renames
   `sonata_sidebar_hide`, which the app never reads).
10. **Brands icon font**: not shipped (recommended) unless an app needs `fab` icons.

## 3. Verify before or during implementation

| Item | How |
|---|---|
| Tailwind 4.3 semantics T1–T11 | phase 1 fixture with pinned `tailwindcss` |
| Sonata's Flex recipe file list (`sonata-project/admin-bundle` in `symfony/recipes-contrib`) | online, before phase 5 |
| Vite 8 IIFE output with fixed names and CSS extraction into `app.css` | phase 1 spike |
| Stimulus 3.2.2 behaviour with two applications on one page (R1–R3) | already read in source; re-run the fixture in phase 1 |
| FA7 `icons.yml` metadata format for the build-time icon check | phase 1 (the package ships `icons.yml`, `icon-families.json`, `shims.yml`) |
| Native `datetime-local`/`time` value round trips with form-extensions' ICU formats (`yyyy-MM-dd'T'HH:mm`, `HH:mm`) | PHPUnit widget test in phase 4 |
| `@tailwindcss/postcss` 4.3.3 inside Webpack Encore 6 with Sass in the same pipeline | phase 5, first migration step |
| Behat/Mink BrowserKit driver in `tests/bdd/Panel` handles `defer` scripts (no JS) and cookies | phase 5 |
