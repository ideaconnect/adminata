# 01 — Architecture decisions

Status legend: **decided** = settled by evidence or by the owner; **owner** = recommended, wants a
one-line confirmation; **verify** = decided in principle, one fact to check before implementation.
Evidence references point to [research/](research/) reports and to appendix C. The v1 decision ids
(D01–D37) are kept in the "supersedes" column so the archived documents stay readable.

## Fork and packaging

| ID | Decision | Rationale | Supersedes | Status |
|---|---|---|---|---|
| P1 | Fork from tag **4.43.0**, not the 5.x branch | 5.x views/assets are byte-identical; only 3 PHP files differ. 4.x semantics keep the persistence bundles' `^4.39` floors satisfied. `R/php-compat.md` §5 | D01 | decided |
| P2 | Keep `Sonata\AdminBundle\` namespace, bundle class `SonataAdminBundle`, `@SonataAdmin`, `sonata.admin.*` service ids, `sonata_admin` config root, routes, translation domain, asset package and `bundles/sonataadmin/` path; publish as `idct/adminata` | Only shape that keeps the ORM bundle, the MongoDB fork and the app's admin classes untouched. `R/packaging.md` §1 | D02, D04 | decided |
| P3 | `"replace": {"sonata-project/admin-bundle": "4.43.0"}`, bumped on each upstream sync; own semver from **1.0.0** | Verified with Composer 2.9.7: the exact version resolves with ORM `^4.39.0` and MDB `^4.39`; `self.version` does not. `R/packaging.md` §0.6 | D03 | decided |
| P4 | Floors `php ^8.4`, `symfony ^7.4 \|\| ^8.0`; CI on PHP 8.4 and 8.5, Symfony 7.4 and 8.1 | Matches the MongoDB fork (`php ^8.4`, `symfony/form ^7.4 \|\| ^8.0`); the app runs PHP ≥ 8.5 and Symfony 8.1. Raising to `^8.5` is a one-line owner call (document 11 §2) | D05 | decided |
| P5 | `require` keeps every upstream entry; sibling floors raised to the latest releases (`block-bundle ^5.4`, `form-extensions ^2.7`, `twig-extensions ^2.6`, `exporter ^3.4`, `doctrine-extensions ^2.6`, `symfony/stimulus-bundle ^3.4`, `twig ^3.28`) | Owner directive: latest releases. Versions verified 2026-09-04 (document 07 §2) | D06 | decided |
| P6 | PHP changes are allowed but confined to what the UI forces: (a) `Configuration`: remove `options.skin`, `options.use_select2`, `options.use_icheck`, `options.use_bootlint`; change asset defaults; add `adminata.theme.mode`; (b) `BaseGroupedMapper`/`AbstractAdmin` default group class `col-md-12` → `col-span-12`, dashboard block default `col-md-4` → `md:col-span-4`, `box_class` default `box box-primary` → `''`; (c) `SonataAdminExtension`: drop the AdminLTE skin stylesheet append; (d) layout helpers: `sonata_theme` cookie → `theme` variable, `<html lang>`; (e) the `sonata-config` meta loses `SKIN`, `USE_SELECT2`, `USE_ICHECK`. Everything else stays byte-identical and is synced from upstream | No compatibility layers: unused options are removed rather than accepted-and-ignored; grid defaults become Tailwind classes instead of being translated by a filter. Tests pinning the old strings (`FormMapperTest`, `ShowMapperTest`, `ConfigurationTest`) are updated | D08, D10, D28 | decided |
| P7 | No `conflict` entries against ecosystem bundles, no compatibility tiers document; `suggest` lists the ORM bundle and the MongoDB fork | Owner directive: no compatibility layers. What breaks is fixed when it matters | D07 | decided |
| P8 | Option "theme overlay on top of the real Sonata package" and option "new `Adminata\` namespace" stay rejected | Overlay cannot change PHP coupling and contradicts the fork precedent; a new namespace breaks every persistence bundle | D02, D04 | decided |

## Scope

| ID | Decision | Rationale | Status |
|---|---|---|---|
| S1 | 1.0 scope = the Sonata features recomaty-panel-clean uses (appendix C). 98 templates are rewritten; the 33 templates the app never renders (association modals and inline forms, ACL, history and compare, preview, subclass selection, tree, mosaic, global search, tab menu, four dashboard blocks, flat rows) stay as inherited files with a tracked TODO list and are ported when first needed | Owner directive | decided |
| S2 | Acceptance = recomaty-panel migrated on a branch and exercised by its Behat suite plus the BrowserKit and Panther scenarios of document 08 §6; adminata's own demo app mirrors the app's feature usage | The app is the only 1.0 target; its test infrastructure (Behat, BrowserKit, docker) already exists | decided |
| S3 | The MongoDB fork's functional suite (Panther, association modals) is a post-1.0 gate; its nightly job is informational in 1.0 | Its scenarios need templates outside the 1.0 scope | decided |
| S4 | No `adminata:audit-overrides` command, no `COMPATIBILITY.md`, no generic upgrade tooling in 1.0; the migration is a written checklist executed once (document 10) | One app to migrate; tooling for hypothetical users is a compatibility layer | decided |

## Templates

| ID | Decision | Rationale | Supersedes | Status |
|---|---|---|---|---|
| T1 | Markup vocabulary = Tailwind utilities + adminata's `.adm-*` components + Sonata-owned hooks (`sonata-*`, `sonata-ba-*`, ids, `objectId`, `data-sonata-*`, button `name`s, strings the PHP layer emits). **No** Bootstrap or AdminLTE class names, no `.sonata-bc` scope, no dual tokens | Owner directive | D26 (b), D35 | decided |
| T2 | Block names: every upstream block name survives in a rewritten template unless it is AdminLTE-specific (`admin_lte_skin_class`, `bootlint` removed); additive blocks `list_after_table`, `sonata_overlay`, `sonata_header_search`, `sonata_top_nav_menu_dark_mode`, `sonata_script_attributes`, `sonata_sidebar_section_header` | Cheap, and the app's overrides depend on about 25 of them (appendix C §3) | — | decided |
| T3 | Flash messages rendered by an adminata-owned `FlashMessage/render.html.twig` included from the `notice` block; form-extensions' `sonata_type_datetime_picker_widget(_html)` blocks overridden in adminata's form theme; no other sibling template is overridden in 1.0 | twig-extensions and form-extensions ship Bootstrap markup; the ORM audit block and block-bundle blocks are outside the 1.0 scope | D33, D17 | decided |
| T4 | Window-scroll layout (React TailAdmin model): fixed sidebar, sticky header, page scrolls with the window | `sticky_controller.js` stays untouched; per-page `window.top` semantics; browser find/print behave. C10 | D23 | decided |
| T5 | Sidebar: collapse persisted in the existing `sonata_sidebar_hide` cookie; per-group open state in `localStorage`; `keep_open`/`on_top` semantics kept; a KnpMenu item carrying class `sidebar-section-header` (or extra `section_header: true`) renders as a TailAdmin `menu-group-title` | The app injects section headers through `ConfigureMenuEvent::SIDEBAR` (appendix C §2) | D24 | decided |
| T6 | Delete and batch confirmation stay full pages | No-JS and XHR JSON contracts unchanged | D32 | decided |
| T7 | Page actions render as a button row; the upstream "2+ buttons → Actions dropdown" heuristic is dropped | TailAdmin idiom; the app overrides `create_button` and adds a custom launch button in the same slot | — | owner |
| T8 | No tabs in 1.0 (the app uses none); underline tabs when first needed. Preloader dropped | Scope | D25, D31 | decided |
| T9 | Login and password pages: `sonata_header` renders nothing when `logo` and `sonata_nav` are empty; `empty_layout` kept; no login template shipped | The app owns its login/reset templates on the layout (appendix C §3) | — | decided |

## JavaScript

| ID | Decision | Rationale | Supersedes | Status |
|---|---|---|---|---|
| J1 | **Stimulus 3.2.2** (latest release, verified 2026-09-04), one `Application` exposed as `window.sonataApplication`; no Alpine, no jQuery, no `window.Admin`, no other globals | Sonata 4.43 already ships 9 Stimulus controllers; `symfony/stimulus-bundle` is a hard dependency; form-extensions registers into the same global; the app runs a second application without conflict (R1–R3) | D11, D12 | decided |
| J2 | 17 controllers in 1.0: 9 inherited (`sonata-collection`, `-confirm-exit`, `-edit`, `-filter`, `-filter-list`, `-per-page`, `-readmore`, `-revision`, `-sticky`) and 8 new (`sonata-layout`, `-menu`, `-dropdown`, `-modal`, `-theme`, `-dismiss`, `-batch`, `-autocomplete`). Post-1.0: `-association`, `-sortable`, `-choice-field-mask`, `-inline-row`, `-editable`, `-treeview`, `-tabs`, `-select`, `-search-shortcut`, `-datepicker` | Scope (appendix C) | D13 | decided |
| J3 | Identifiers `sonata-*`, explicit registry, no `stimulus-bridge`, no `require.context`; Twig uses `stimulus_controller()/stimulus_target()/stimulus_action()` | One namespace; ESM-friendly | D13 | decided |
| J4 | All scripts rendered with `defer`; no inline scripts in adminata templates except the 3-line `system`-theme pre-paint script under a nonce block | No globals to wait for; CSP-clean | D14 | decided |
| J5 | Autocomplete (`ModelAutocompleteType`, `ModelAutocompleteFilter`): a hand-written vanilla combobox (`sonata-autocomplete`), single and multiple, ARIA 1.2 combobox pattern, remote paging against `sonata_admin_retrieve_autocomplete_items`; the hidden-input submit shape is kept | Owner: reimplement in vanilla JS; the app uses one field and one filter; no Tom Select shipped (the app already runs its own through ux-autocomplete) | D15 | decided |
| J6 | No select enhancement: every plain `<select>` is native and styled by the form theme | The app runs `use_select2: false` and forbids turning it on; the option is removed (P6) | D15 | decided |
| J7 | Native `<dialog>` + `sonata-modal` for every modal, usable by apps on their own dialogs | Top layer, focus trap and ESC for free; the app's universal modal ports in one line | D18 | decided |
| J8 | Drag-and-drop, choice-field-mask, inline edit, tree view: not in 1.0; SortableJS (1.15.7) is the intended library for sortable collections when needed | Scope | D16, D19 | decided |
| J9 | Fetch-based form submissions fire a native `submit` event first (`requestSubmit()`), send `credentials: 'same-origin'` | The app uses Symfony's stateless CSRF tokens minted client-side on `submit` (document 06 §5) | — | decided |
| J10 | Events: native bubbling `CustomEvent`s via `this.dispatch()`; the inherited names (`sonata-admin-append-form-element`, `sonata-collection-item-added`, …) unchanged; no jQuery bridge | Nothing consumes them through jQuery any more | D20 | decided |
| J11 | Build: **Vite 8.2** + `@tailwindcss/vite` 4.3, **Vitest 5.0** + jsdom 30, **Node 24 LTS** (26 in CI matrix); outputs `app.js` (IIFE) and `app.css` with fixed names committed under `src/Resources/public`, CI freshness gate. ESM entry and AssetMapper mapping post-1.0 | Owner: latest releases; un-hashed names keep `remove_*` entries valid | D22 | decided |
| J12 | `qs` 6.16.0 is the only runtime dependency besides Stimulus (used by the inherited filter controller) | Tiny; replacing it is not worth a hand-written nested-name parser in 1.0 | — | decided |

## CSS and theming

| ID | Decision | Rationale | Supersedes | Status |
|---|---|---|---|---|
| C1 | **Tailwind 4.3.x** (latest), `@theme static` with TailAdmin's tokens verbatim (no `--font-*`/`--breakpoint-*` resets), `@custom-variant dark (&:where(.dark, .dark *))` | TailAdmin's palette and type scale; the variant must match the `.dark` element itself | D28, D29 | verify (T5, T6) |
| C2 | Dark mode: cookie `sonata_theme` (`light\|dark\|system`), server stamps `<html class="dark">`; `system` needs the 3-line pre-paint script; `adminata.theme.mode` config sets the default | No FOUC; CSP-clean | D29 | decided |
| C3 | `.adm-*` component classes (`@utility` for single-selector primitives, `@layer components` for descendant rules) as adminata's design-system layer, reused by apps in their own templates; safelist only for the grid classes the PHP layer and admin options emit (`{,sm:,md:,lg:,xl:}col-span-{1..12}`, `col-start-{2..12}`) | Readable templates; the app's 55 cell templates reuse `adm-badge`, `adm-btn`, `adm-callout`. Not a compatibility API | D26 (a) | verify (T1, T3) |
| C4 | **No** Bootstrap/AdminLTE compatibility stylesheet, no data-API shim, no AdminLTE skins, no `admin-lte-skins/` files; brand re-theming by redefining `--color-brand-*` | Owner directive | D20, D26 (b,c), D28 | decided |
| C5 | Apps compile Tailwind themselves with `@source` on adminata's views (recommended for recomaty-panel, which already runs Webpack Encore); adminata also ships prebuilt `app.css` for zero-config use | Utilities in app templates are only emitted by the app's own build | D26 (d) | decided |
| C6 | Icons: **Font Awesome 7.3 Free** `all.css` + solid/regular woff2; no v4/v5 shims, no brands unless needed; TailAdmin inline SVG only inside adminata's own chrome | All 76 icon names the app uses exist in FA7 Free except `clock-o` (appendix C §6); `parse_icon` unchanged | D30 | decided |
| C7 | Font: self-hosted **Outfit Variable** (`@fontsource-variable/outfit` 5.3, OFL), `font-display: swap` | TailAdmin's face; admin bundles must work offline | D34 | owner (system stack alternative) |
| C8 | Semantic z-index ladder and runtime density variables as in v1 (`--z-index-{overlay…loader}`, `--adm-control-h`, …) | Resolves TailAdmin's header/modal tie | — | decided |

## Dates, tests, attribution

| ID | Decision | Rationale | Supersedes | Status |
|---|---|---|---|---|
| F1 | Date and time fields are **native HTML5 inputs** (`date`, `datetime-local`, `time`), styled by the form theme, dark-mode via `color-scheme`; adminata overrides form-extensions' two widget blocks to choose the input type from `datepicker_options.display.components`; each app field must use an HTML5-compatible `format` (`yyyy-MM-dd`, `yyyy-MM-dd'T'HH:mm`, `HH:mm`) or switch to Symfony's core types. No picker library in 1.0; `vanilla-calendar-pro` (3.3.2, actively released) is the candidate if a popup calendar is wanted later | flatpickr's last release is from 2022 and Tempus Dominus ships Bootstrap-flavoured light-only CSS; native inputs need no JS, no theme and no format converter | D17 | decided |
| F2 | Range filters keep Symfony `DateType`/`DateTimeType` with `single_text` (already HTML5) rendered side by side by an additive `sonata_type_date_range_widget` | Sonata's filters never used form-extensions' pickers | — | decided |
| Q1 | Inherited tests are kept and re-baselined where markup changes; the `<td class="sonata-ba-list-field …" objectId>` envelope stays byte-identical | The app's 55 cell templates and its XHR accordion depend on it | D35 | decided |
| Q2 | Keep the Sonata file header on inherited files; combined header on new files; `LICENSE` with three copyright lines (Rabaix 2010, TailAdmin 2023, IDCT 2026); `NOTICE` lists Stimulus, Tailwind, qs, Font Awesome, Outfit | Clean cherry-picks; MIT/OFL/CC-BY obligations | D36 | decided |
| Q3 | Tree view removed from the roadmap (deprecated upstream, unused by the app) | Scope | D37 | decided |

## v1 decisions reversed by the owner directives

| v1 | Was | Now |
|---|---|---|
| D08, D10 | PHP default strings and deprecated options kept, translated by Twig filters | Defaults changed to Tailwind classes; options removed (P6) |
| D12 | jQuery 3.7 shipped as a deprecated separate file, jQuery bridge, jQuery-aware `Admin` facade | No jQuery anywhere; no `Admin` facade (J1) |
| D14 | Blocking scripts in `<head>` | `defer` (J4) |
| D15, D16, D17 | Tom Select, SortableJS, flatpickr shipped | Hand-written combobox; no drag-and-drop in 1.0; native date inputs (J5, J8, F1) |
| D20 | Bootstrap data-API delegate | Removed (C4) |
| D26 (b), (c) | Default-on `compat-bootstrap3.css`, TailAdmin helper safelist | Removed (C4) |
| D28 | 12 AdminLTE skins as token files at the old path | Removed; brand tokens only (C4) |
| D30 | FA6 + v4 shims + FA5 rename shims | FA7 Free, no shims (C6) |
| D35 | `label label-*` tokens kept beside badge classes | Sonata-owned hooks only (T1) |
| D37 | Tree view parity | Dropped (Q3) |
| 09 backlog | jQuery removal in 2.x | Done in 1.0 |
| 10, 11 | `adminata:audit-overrides`, compatibility tiers | Dropped (S4, P7) |
