# 11 — Risks, owner decisions, verification list

## 1. Risk register

| # | Risk | Sev. | Mitigation | Where |
|---|---|---|---|---|
| R01 | Tailwind 4.3 semantics (T1–T11) assumed, not tested locally | high | phase 1 fixture; pin exact version | 04 §4 |
| R02 | Flex runs the six Sonata recipes' `unconfigure` when the real packages are uninstalled, deleting `config/packages/sonata_{admin,block,form}.yaml`, `config/routes/sonata_admin.yaml`, `src/Admin/.gitignore` and `bundles.php` lines | high | `--no-plugins --no-scripts` for every Composer call of the migration; `symfony.lock` cleaned by hand; `git diff -- config/` gate | 07 §1, 10 §1 |
| R03 | recomaty-panel's 55 cell templates and 15 page templates carry Bootstrap/AdminLTE markup (`callout` ×316, `box` ×22, `btn` ×76, inline pastel colours) and render unstyled until ported | high | itemised in 10 §1 (about 25 of the 45 hours); `.adm-*` components designed for them; dark variants via tokens | 10 |
| R04 | Seven-way `replace` plus the external MongoDB fork: a future floor raise in the fork or a dependant of any replaced package breaks resolution | medium | `ReplaceTest` in CI with the fork; weekly `upstream-watch` for the seven; sync within one minor | 02 §13, 07 §10 |
| R05 | Native date/time inputs: `BasePickerType` now rejects custom `format`s; apps that relied on locale display formats lose them (browsers render their own) | medium | documented in U6; `datepicker_options.display.components` still selects date/time/datetime; PHPUnit widget tests; acceptance F3/F4 | 06 §4 |
| R06 | Hand-written combobox misses ARIA/keyboard details | medium | ARIA 1.2 pattern checklist, axe, keyboard Panther scenario; fallback: ux-autocomplete field for the one form usage | 06 §3 |
| R07 | The union `require` pulls Doctrine ORM into MongoDB-only installs | low | accepted (P5); documented; the ORM bundle class is only loaded when registered | 01 P5 |
| R08 | Large one-time Rector/CS diffs across seven packages make upstream cherry-picks conflict | medium | one mechanical commit per package; `git apply -3 --directory`; P6 files merged by hand | 07 §10 |
| R09 | MongoDB fork's Panther suite fails until association widgets exist, and its modal scenarios assume AJAX submission that is gone for good | medium | unit suite in PR CI from phase 1; Panther nightly informational; the fork's scenarios are adapted to the non-AJAX flow when the widgets are ported (first backlog item) | 08 §7, 09 |
| R10 | Sonata's tests pin Bootstrap markup (452 render expectations, form layout XPath, flash tests) | medium | envelope frozen; deliberate re-baselining | 08 §1 |
| R11 | Flex's convention-based bundle auto-registration for a `symfony-bundle` package with several PSR-4 roots may register the wrong class or none in new apps | low | existing apps keep `bundles.php`; new apps register the six classes by hand (01 P10 removed the seventh); verify in §3 | 07 §11 |
| R12 | Dark mode regressions in app templates (hard-coded light colours) | low | tokens with `dark:` variants during the cell-template port; X1 scenario | 10 §1 step 10 |
| R13 | Two Stimulus applications; a `sonata-*` identifier registered twice | low | rule R2; guard in the app's Behat suite | 05 §5 |
| R14 | App classes named like Tailwind utilities (`mt-10`, `hidden`) change meaning once the app compiles Tailwind | low | rename `.mt-10`; `hidden` is fine once `col-md-*` is gone | 10 §1 step 11 |
| R15 | FA7 renames (one v4-only name in the app; aliases could be dropped in a future FA major) | low | `clock-o` renamed; CSS contract checks shipped icon names against `icons.yml` at build time | 04 §8 |
| R16 | Popups clipped by overflow wrappers (combobox listbox, dropdowns inside cards) | low | z-index ladder; `overflow: visible` cards; Panther coverage | 04 §3 |
| R17 | Stimulus 3.2.2 is three years old; a 4.x could appear during the project | low | `versions-watch`; controllers use only documented 3.x API | 07 §2 |
| R18 | Repository size after importing seven histories with `git subtree` | low | `--squash` option for the small packages (owner) | 07 §10 |

## 2. Owner decisions (none blocks phase 0)

1. **Seven bundle classes or one**: keep the seven upstream bundle classes and config roots
   (recommended; `bundles.php` and YAML of existing apps unchanged, MongoDB fork untouched) or
   merge into a single `AdminataBundle` with one config root (touches every app's `bundles.php`
   and five YAML files, and the fork's DI assumptions).
   **Answered:** seven on 2026-09-04; then **six** on 2026-09-06, when `SonataBlockBundle` was
   deleted and its extension moved into `SonataAdminBundle` (01 P10). The middle path won — one
   bundle class fewer, but every config root, service id and Twig name kept, so an app loses one
   `bundles.php` line and no YAML at all.
2. **Package layout**: `packages/<upstream-name>/{src,tests,docs}` mirroring the upstream trees
   (recommended; trivial `git subtree`/`git apply --directory` syncs) or a flattened `src/<Namespace>/`.
3. **Import history**: full history for all seven (recommended) or `--squash` for the small ones.
4. **PHP floor**: `^8.4` (fork convention, recommended) or `^8.5` (what the app runs).
5. **Font**: self-hosted Outfit Variable (recommended) or the system stack with Outfit opt-in.
6. **Dark-mode default**: `system` (recommended) or `light`.
7. **Action bar**: button row without the upstream dropdown heuristic (recommended, T7) or keep it.
8. **Node line**: build on 24 LTS with 26 in the CI matrix (recommended) or 26 only.
9. **Autocomplete**: hand-written combobox for `ModelAutocompleteType` (recommended, J5) or swap the
   app's single form usage to its existing ux-autocomplete field and ship only the filter variant.
10. **`qs`**: keep (recommended) or replace with a hand-written nested-name parser.
11. **Cookie attributes**: `SameSite=Lax` + `Secure` (recommended) versus `__Host-` prefix.
12. **Brands icon font**: not shipped (recommended) unless an app needs `fab` icons.

## 3. Verify before or during implementation

| Item | How |
|---|---|
| Tailwind 4.3 semantics T1–T11 | phase 1 fixture with pinned `tailwindcss` |
| Seven-way `replace` resolution together with `idct/sonata-admin-mongodb-bundle` and a plain ORM app | phase 0 scratch app (`ReplaceTest`) |
| Flex behaviour for a `symfony-bundle` package with several PSR-4 roots (auto-registration, `unconfigure` of the six recipes with `--no-plugins`) | phase 0 scratch app; recipe file lists confirmed from the app's `symfony.lock` on 2026-09-04 |
| `git subtree add` of seven upstream histories: repository size and clone time | phase 0, before pushing |
| Vite 8 IIFE output with fixed names and CSS extraction into `app.css` | phase 1 spike |
| Stimulus 3.2.2 behaviour with two applications on one page (R1–R3) | already read in source; re-run the fixture in phase 1 |
| FA7 metadata format for the build-time icon check | phase 1 (the package ships `icons.yml`, `icon-families.json`, `shims.yml`) |
| `BasePickerType` HTML5 enforcement against form-extensions' own type tests and `DateRangePickerType` | phase 0 (PHP change) and phase 4 (widget) |
| `@tailwindcss/postcss` 4.3.3 inside Webpack Encore 6 with Sass in the same pipeline | phase 5, first migration step |
| Behat/Mink BrowserKit driver in `tests/bdd/Panel` handles `defer` scripts (no JS) and cookies | phase 5 |
