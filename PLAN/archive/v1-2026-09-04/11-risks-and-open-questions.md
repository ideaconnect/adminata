# 11 — Risks, owner decisions, verification list

## 1. Risk register (consolidated, deduplicated)

| # | Risk | Sev. | Mitigation | Where |
|---|---|---|---|---|
| R01 | User overrides under `templates/bundles/SonataAdminBundle/` and sibling bundles (User, Media, Page, ORM `block_audit`, block-bundle RSS, form-extensions datepicker) render Bootstrap-3 markup in a Tailwind page | high | compat stylesheet on by default, block-name parity, data-API delegate, `adminata:audit-overrides`, `COMPATIBILITY.md` tiers, nightly compat jobs | 03 §E, 04 §6, 10 |
| R02 | Flex runs Sonata's recipe `unconfigure` on replacement and deletes user config | high | `--no-plugins --no-scripts` migration path; verify the recipe file list online; consider shipping a recipe that re-creates the files | 07 §1, 10 §1 |
| R03 | Tailwind purging: utilities present only in user templates or PHP options are absent from the prebuilt CSS | high | `.adm-*` API + safelist, compat layer, per-app compile recipe, CSS contract CI | 04 |
| R04 | Tailwind v4 semantics (T1–T11) assumed, not tested locally | high | phase 1 fixture; pin exact version | 04 §4 |
| R05 | User JS depends on jQuery plugins Sonata bundled (`select2`, `iCheck`, `editable`, `sortable`, `ajaxSubmit`, `modal`, `tab`) | high | jQuery global kept (deprecated), `window.Admin` facade, native events + bridge, UPGRADE table | 05 §9 |
| R06 | `fa*` icon strings everywhere (PHP constants, YAML, docs) | high | FA6 Free + v4 shims + FA5 rename shims; `parse_icon` unchanged | 01 D30 |
| R07 | `replace: 4.43.0` goes stale when a persistence bundle raises its floor | medium | weekly `upstream-watch`, sync within one minor, documented root-level `replace` escape hatch | 07 §9 |
| R08 | Stateless CSRF apps: fetch-based flows rejected after the first double-submit success | medium | `requestSubmit()` + capturing interception, `credentials: 'same-origin'`; UPGRADE U11 | 06 §5 |
| R09 | Sonata's tests pin Bootstrap markup (452 render expectations, form layout XPath, breadcrumbs) | medium | envelope frozen; deliberate re-baselining; breadcrumb templates untouched | 08 §1 |
| R10 | Popups clipped or mis-layered: Tom Select/flatpickr/editable popover inside `<dialog>` or overflow cards | medium | z-index ladder, inline `dropdownParent` inside dialogs, footer outside overflow wrappers, Panther coverage | 04 §3, 05 §5 |
| R11 | form-extensions `datepicker` identifier collision (last `register()` wins) | medium | `sonata-datepicker`; sonataform assets dropped from defaults; script-order rule | 01 D17 |
| R12 | Sidebar semantics (multiple open groups, `keep_open`, cookie) mis-ported from TailAdmin's single `selected` state | medium | per-group map, cookie seeding, `MenuTest` | 03 §A |
| R13 | Dashboard `class` values and app-copied templates use Bootstrap grid strings | medium | `sonata_grid_class` + compat grid | 04 §1 |
| R14 | Two Stimulus applications registering `sonata-*` twice | medium | rule R2, audit A-20, ESM `startAdminata` | 05 §5 |
| R15 | Large one-time Rector/CS diff makes upstream cherry-picks conflict | medium | single mechanical commit; `git apply -3` | 07 §9 |
| R16 | PHP `^8.4` / Symfony `^7.4` floors exclude many Sonata users | medium | owner decision; nothing technical requires 8.4 | 01 D05 |
| R17 | Tailwind v4 browser floor (Safari 16.4+, Chrome 111+, Firefox 128+) | medium | state in README/UPGRADE; no v3 fallback | 04 |
| R18 | Compat selectors collide with Tailwind utilities (`fixed`, `collapse`, `container`, `hidden`, `label`) | medium | not shimmed / `@layer sonata-overrides` / `.label:not(label)`; audit A-16 | 04 §6 |
| R19 | `remove_stylesheets` entries now must also list the two new default files | low | UPGRADE U17 | 02 §3 |
| R20 | Skins become palettes; CSS keyed on AdminLTE structure stops matching | low | class kept on `<body>`; mapping table documented | 04 §3 |
| R21 | Blocking `<head>` scripts remain (perf, CSP) | low | `defer` + `sonata:ready` in 2.x; nonce block now | 05 §7 |
| R22 | Sibling package citations in reports point at a pruned extract; some sibling templates (SonataUser, SonataIntl, runroom) unread | low | verify online in phase 5 | below |
| R23 | Adversarial verification of the packaging recommendation did not run | low | the empirical Composer/Flex facts stand; rerun the three review lenses before phase 0 if desired | README |

## 2. Owner decisions (deduplicated across all reports)

Packaging and scope

1. Confirm Option B and `replace: sonata-project/admin-bundle: 4.43.0` with own semver 1.0.0.
2. Floors: `php ^8.4` + `symfony ^7.4 || ^8.0` versus Sonata's `^8.2` + `^6.4 || ^7.3 || ^8.0`.
3. Tier-1 ecosystem at 1.0: ORM + MongoDB fork only, or also SonataUserBundle (login/reset pages).
4. Repository/organisation (`ideaconnect/adminata`, `idct/adminata`) and whether to regenerate
   the 74-contributor author roster from a full clone.
5. Flex recipe: docs-only or contribute to `symfony/recipes-contrib` after 1.0.
6. TailAdmin Pro: not planned; confirm free-edition-only.

Front-end

7. Font: self-hosted Outfit (+80 KB) or system stack with Outfit opt-in.
8. Keep both `onclick` shims and `data-action` on adminata's association buttons, or drop `onclick`.
9. AssetMapper first-class (ESM build + docs + CI smoke) at 1.0 or 1.1.
10. `options.density` as a config key or documented override only.
11. Glyphicons compat (≈30 KB) for the login recipe, or rewrite the docs and drop it.
12. Notifications/avatar in the header: empty blocks only, or a new extension point.
13. Boolean badges: keep `label label-*` tokens alongside (recommended) — confirm.
14. Behaviour fixes: shift-range bug, mosaic checkbox placement, dropping the level>1 child icon.
15. Tree view: parity-only (deprecated) or a supported `sonata-treeview` mode with a real
    `list_outer_rows_tree` template.
16. Compare view: whole-cell highlighting (planned) or a text diff.
17. `use_select2` after migration of the real app: enable Tom Select on plain selects or keep native.

Testing and process

18. Where the real-app acceptance suite lives (app CI vs adminata nightly with a deploy key).
19. Panther/Selenium for the six JS scenarios in the real app, or BrowserKit only there.
20. Attribution header style: Sonata header everywhere (recommended) or dual headers on new files.
21. Cookie attributes: `SameSite=Lax` + `Secure` (planned) versus `__Host-` prefix (renames cookies).
22. Dark-mode default: user-toggleable with `system` initial value (planned) or `light` only.
23. Whether adminata may override third-party templates (`@SonataDoctrineORMAdmin/Block/block_audit`,
    `@SonataIntl/CRUD/show_*`) from inside the package, or compat CSS + upstream PRs only (planned).

## 3. Verify before or during implementation

| Item | How |
|---|---|
| Tailwind v4 semantics T1–T11 | phase 1 fixture with pinned `tailwindcss` |
| Sonata's Flex recipe file list (`sonata-project/admin-bundle` in `symfony/recipes-contrib`) | online; drives the `--no-plugins` note and a possible adminata recipe |
| SonataUserBundle admin templates (`user_block`, login, reset) | online; compat CSS list |
| `symfonycasts/tailwind-bundle` version that defaults to a v4 binary | online |
| FA 6.7 woff2 sizes and the FA5→FA6 rename list | from the npm package during phase 1 |
| Tom Select 2.5 on `<input type="hidden">` for the sortable multi-select | phase 3 spike |
| `runroom/sortable-behavior-bundle` templates (`recipe_sortable_listing`) | online |
| Three adversarial review lenses (composer/ecosystem, maintenance, user migration) | optional rerun; inputs are `R/packaging.md`, `R/php-compat.md`, `R/js-assets.md` |
| Sibling template citations in `R/layout-nav.md`/`R/packaging.md` that reference the pruned `vendor-extract/` | re-point to `MDB/vendor/sonata-project/...` (content identical) |
