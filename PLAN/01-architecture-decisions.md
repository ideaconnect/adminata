# 01 — Architecture decisions

Status legend: **decided** = settled by evidence or by the owner; **owner** = recommended, wants a
one-line confirmation; **verify** = decided in principle, one fact to check before implementation.
Evidence references point to [research/](research/) reports and to appendix C. The v1 decision ids
(D01–D37) are kept in the "supersedes" column so the archived documents stay readable.

## Fork and packaging

| ID | Decision | Rationale | Supersedes | Status |
|---|---|---|---|---|
| P1 | Fork the **seven** Sonata packages at their latest tags: `admin-bundle` 4.43.0 (not the 5.x branch), `block-bundle` 5.4.0, `doctrine-extensions` 2.6.0, `doctrine-orm-admin-bundle` 4.21.0, `exporter` 3.4.0, `form-extensions` 2.7.0, `twig-extensions` 2.6.0 | Owner directive; these are the packages the app and the MongoDB fork depend on. 5.x admin views are byte-identical; 4.x semantics keep the fork's `^4.39` floor satisfied. `R/php-compat.md` §5 | D01 | decided |
| P2 | One repository, one Composer package `idct/adminata`, the packages copied one-to-one under `packages/<upstream-name>/{src,tests,docs}`; every upstream namespace (`Sonata\AdminBundle\`, `Sonata\BlockBundle\`, `Sonata\Doctrine\`, `Sonata\DoctrineORMAdminBundle\`, `Sonata\Exporter\`, `Sonata\Form\`, `Sonata\Twig\`), bundle class, config root, service id, Twig namespace and asset path kept | "No separate sub-bundles like in the original Sonata project" while staying a drop-in for the MongoDB fork and the app's `bundles.php`; mirroring the upstream trees keeps `git subtree`/`git apply --directory` syncs trivial. `R/packaging.md` §1 | D02, D04 | decided (layout: owner) |
| P3 | `replace` for all seven package names at the exact upstream versions above, bumped on each sync; own semver from **1.0.0** | Verified with Composer 2.9.7 that an exact `replace` version satisfies caret constraints; the MongoDB fork requires `admin-bundle ^4.39`, `exporter ^3.0`, `form-extensions ^2.0` (and `block-bundle ^5.0` in dev) | D03 | decided |
| P4 | Floors `php ^8.4`, `symfony ^7.4 \|\| ^8.0`; CI on PHP 8.4 and 8.5, Symfony 7.4 and 8.1 | Matches the MongoDB fork; the app runs PHP ≥ 8.5 and Symfony 8.1 | D05 | decided |
| P5 | `require` = union of the seven packages' requirements (Symfony components, Twig, KnpMenu, `doctrine/orm ^3.3`, `doctrine/doctrine-bundle ^3`, `doctrine/dbal ^4`, `doctrine/persistence ^4`, `symfony/stimulus-bundle ^3.4`); no internal `sonata-project/*` entries; `suggest` keeps the upstream optional ones (`phpoffice/phpspreadsheet`, `twig/extra-bundle`, `sonata-project/entity-audit-bundle`, `idct/sonata-admin-mongodb-bundle`) | Composer semantics of `replace`; the ORM bundle's dependencies become adminata's (a MongoDB-only app installs Doctrine ORM unused — accepted) | D06 | decided |
| P6 | PHP changes confined to what the UI forces: (a) admin `Configuration`: remove `options.skin`, `options.use_select2`, `options.use_icheck`, `options.use_bootlint`; change asset defaults; add `adminata.theme.mode`; (b) `BaseGroupedMapper`/`AbstractAdmin` default group class `col-md-12` → `col-span-12`, dashboard block default `col-md-4` → `md:col-span-4`, `box_class` default `box box-primary` → `''`; (c) `SonataAdminExtension`: drop the AdminLTE skin stylesheet append; (d) layout helpers: `sonata_theme` cookie → `theme` variable, `<html lang>`; (e) `sonata-config` meta loses `SKIN`, `USE_SELECT2`, `USE_ICHECK`; (f) form-extensions `BasePickerType`: HTML5 wire formats derived from `datepicker_options.display.components` (`html5: true` semantics), custom `format` rejected like Symfony's `DateType`; `SonataFormExtension` no longer registers its JS/CSS assets. Everything else stays byte-identical and is synced from upstream | No compatibility layers: unused options are removed; grid defaults become Tailwind classes; native date inputs need no per-field format strings in apps | D08, D10, D17, D28 | decided |
| P7 | No `conflict` entries, no compatibility tiers document; `suggest` lists the MongoDB fork | Owner directive: no compatibility layers | D07 | decided |
| P8 | Overlay and new-namespace options stay rejected; SonataUserBundle and every other Sonata package are out of scope | Overlay cannot change PHP coupling; a new namespace breaks the MongoDB fork; the app has its own security | D02, D04 | decided |
| P9 | Git: repository `ideaconnect/adminata`, branch `main`, the plan under `PLAN/`; upstream histories imported with `git subtree add --prefix=packages/<name>` in phase 0; work on short-lived branches merged to `main`; `main` pushed after each milestone (phase exit gate, plan revision, release); tags `v1.0.0-alpha1 … v1.0.0` | Owner directive | — | decided |

## Scope

| ID | Decision | Rationale | Status |
|---|---|---|---|
| S1 | 1.0 scope = the Sonata features recomaty-panel-clean uses (appendix C). 100 templates rewritten, 12 copied unchanged, 36 deferred as inherited files with a tracked TODO (document 03 §E, §G) | Owner directive | decided |
| S2 | Acceptance = recomaty-panel migrated on a branch and exercised by its Behat suite plus the BrowserKit and Panther scenarios of document 08 §6; adminata's own demo app mirrors the app's feature usage | The app is the only 1.0 target; its test infrastructure already exists | decided |
| S3 | `idct/sonata-admin-mongodb-bundle` must resolve and its unit suite must pass against adminata from phase 1 (PR CI); its Panther suite (association modals) is a post-1.0 gate | Owner directive; the modal scenarios need templates outside the 1.0 scope | decided |
| S4 | No `adminata:audit-overrides` command, no `COMPATIBILITY.md`, no generic upgrade tooling in 1.0 | One app to migrate | decided |
| S5 | The `ajaxSubmit` feature (submitting association create/edit forms inside a modal and re-rendering the parent field, plus the server-rendered inline-collection add) is removed for good, not deferred | Owner directive | decided |

## Templates

| ID | Decision | Rationale | Supersedes | Status |
|---|---|---|---|---|
| T1 | Markup vocabulary = Tailwind utilities + adminata's `.adm-*` components + Sonata-owned hooks (`sonata-*`, `sonata-ba-*`, ids, `objectId`, `data-sonata-*`, button `name`s, strings the PHP layer emits). **No** Bootstrap or AdminLTE class names, no `.sonata-bc` scope, no dual tokens | Owner directive | D26 (b), D35 | decided |
| T2 | Block names: every upstream block name survives in a rewritten template unless it is AdminLTE-specific (`admin_lte_skin_class`, `bootlint` removed); additive blocks `list_after_table`, `sonata_overlay`, `sonata_header_search`, `sonata_top_nav_menu_dark_mode`, `sonata_script_attributes`, `sonata_sidebar_section_header` | Cheap, and the app's overrides depend on about 25 of them (appendix C §3) | — | decided |
| T3 | Templates of the other packages are edited **in place**: twig-extensions' `FlashMessage/render.html.twig` and form-extensions' `Form/datepicker.html.twig` are rewritten; block-bundle's ten Bootstrap-free templates and the ORM bundle's two form themes are copied unchanged; `block_core_rss`, `block_side_menu_template` and the ORM `block_audit` are deferred. No adminata-owned copies, no block overrides across packages | The packages are ours now | D33, D17 | decided |
| T4 | Window-scroll layout (React TailAdmin model): fixed sidebar, sticky header, page scrolls with the window | `sticky_controller.js` stays untouched. C10 | D23 | decided |
| T5 | Sidebar: collapse persisted in the existing `sonata_sidebar_hide` cookie; per-group open state in `localStorage`; `keep_open`/`on_top` semantics kept; a KnpMenu item carrying class `sidebar-section-header` renders as a TailAdmin `menu-group-title` | The app injects section headers through `ConfigureMenuEvent::SIDEBAR` | D24 | decided |
| T6 | Delete and batch confirmation stay full pages | No-JS and XHR JSON contracts unchanged | D32 | decided |
| T7 | Page actions render as a button row; the upstream "2+ buttons → Actions dropdown" heuristic is dropped | TailAdmin idiom | — | owner |
| T8 | No tabs in 1.0; underline tabs when first needed. Preloader dropped | Scope | D25, D31 | decided |
| T9 | Login and password pages: `sonata_header` renders nothing when `logo` and `sonata_nav` are empty; `empty_layout` kept; no login template shipped | The app owns its login/reset templates | — | decided |

## JavaScript

| ID | Decision | Rationale | Supersedes | Status |
|---|---|---|---|---|
| J1 | **Stimulus 3.2.2** (latest release), one `Application` exposed as `window.sonataApplication`; no Alpine, no jQuery, no `window.Admin`, no other globals | Sonata 4.43 already ships 9 Stimulus controllers; `symfony/stimulus-bundle` is a hard dependency; the app runs a second application without conflict (R1–R3) | D11, D12 | decided |
| J2 | 17 controllers in 1.0: 9 inherited (`sonata-collection`, `-confirm-exit`, `-edit`, `-filter`, `-filter-list`, `-per-page`, `-readmore`, `-revision`, `-sticky`) and 8 new (`sonata-layout`, `-menu`, `-dropdown`, `-modal`, `-theme`, `-dismiss`, `-batch`, `-autocomplete`). Post-1.0: `-association` (without AJAX submit), `-sortable`, `-choice-field-mask`, `-inline-row`, `-editable`, `-treeview`, `-tabs`, `-select`, `-search-shortcut`, `-datepicker` | Scope (appendix C) | D13 | decided |
| J3 | Identifiers `sonata-*`, explicit registry, no `stimulus-bridge`, no `require.context`; Twig uses `stimulus_controller()/stimulus_target()/stimulus_action()` | One namespace; ESM-friendly | D13 | decided |
| J4 | All scripts rendered with `defer`; no inline scripts in adminata templates except the 3-line `system`-theme pre-paint script under a nonce block | No globals to wait for; CSP-clean | D14 | decided |
| J5 | Autocomplete: a hand-written vanilla combobox (`sonata-autocomplete`), single and multiple, ARIA 1.2 pattern, remote paging against `sonata_admin_retrieve_autocomplete_items`; hidden-input submit shape kept | Owner: reimplement in vanilla JS; no Tom Select shipped | D15 | decided |
| J6 | No select enhancement: every plain `<select>` is native and styled by the form theme | The app runs without select2; the option is removed (P6) | D15 | decided |
| J7 | Native `<dialog>` + `sonata-modal` for every modal, usable by apps on their own dialogs | Top layer, focus trap and ESC for free | D18 | decided |
| J8 | Drag-and-drop, choice-field-mask, inline edit, tree view: not in 1.0 | Scope | D16, D19 | decided |
| J9 | No fetch-based form POST in any phase: 1.0 has none; post-1.0 association widgets use list selection in a `<dialog>` loaded with `fetch` GET and open create/edit as full pages; `sonata_admin_retrieve_form_element`/`append_form_element` stay in PHP but nothing calls them | Owner: `ajaxSubmit` feature dropped (S5); also sidesteps the app's stateless-CSRF double-submit rule (document 06 §5) | — | decided |
| J10 | Events: native bubbling `CustomEvent`s via `this.dispatch()`; inherited names unchanged; no jQuery bridge | Nothing consumes them through jQuery any more | D20 | decided |
| J11 | Build: **Vite 8.2** + `@tailwindcss/vite` 4.3, **Vitest 5.0** + jsdom 30, **Node 24 LTS** (26 in CI matrix); outputs `app.js` (IIFE), `app.css`, `fontawesome.css` with fixed names committed under `packages/admin-bundle/src/Resources/public`, CI freshness gate. ESM entry and AssetMapper mapping post-1.0 | Owner: latest releases; un-hashed names keep `remove_*` entries valid | D22 | decided |
| J12 | `qs` 6.16.0 is the only runtime dependency besides Stimulus | Tiny; used by the inherited filter controller | — | decided |
| J13 | **Library policy**: any package that depends on jQuery is rejected outright (CI fails if `npm ls jquery` finds it, ESLint bans the import). When a behaviour needs more than plain DOM code, first check whether Tailwind/TailAdmin already covers it in CSS (dropdown panels, native `<dialog>`, `<details>` accordions, CSS tooltips); otherwise pick a modern, popular, actively released vanilla library (candidates: SortableJS 1.15.7, vanilla-calendar-pro 3.3.2, Tom Select 2.6.2) | Owner directive | — | decided |

## CSS and theming

| ID | Decision | Rationale | Supersedes | Status |
|---|---|---|---|---|
| C1 | **Tailwind 4.3.x**, `@theme static` with TailAdmin's tokens verbatim, `@custom-variant dark (&:where(.dark, .dark *))` | TailAdmin's palette and type scale | D28, D29 | verify (T5, T6) |
| C2 | Dark mode: cookie `sonata_theme` (`light\|dark\|system`), server stamps `<html class="dark">`; `adminata.theme.mode` sets the default | No FOUC; CSP-clean | D29 | decided |
| C3 | `.adm-*` component classes as adminata's design-system layer, reused by apps; safelist only for the grid classes the PHP layer and admin options emit | Readable templates; the app's 55 cell templates reuse `adm-badge`, `adm-btn`, `adm-callout` | D26 (a) | verify (T1, T3) |
| C4 | **No** Bootstrap/AdminLTE compatibility stylesheet, no data-API shim, no AdminLTE skins; brand re-theming by redefining `--color-brand-*` | Owner directive | D20, D26 (b,c), D28 | decided |
| C5 | Apps compile Tailwind themselves with `@source` on adminata's `packages/*/src/**/Resources/views` (recommended for recomaty-panel); adminata also ships prebuilt CSS | Utilities in app templates are only emitted by the app's own build | D26 (d) | decided |
| C6 | Icons: **Font Awesome 7.3 Free** `all.css` + solid/regular woff2; no shims | All app icon names resolve except `clock-o` (appendix C §6) | D30 | decided |
| C7 | Font: self-hosted **Outfit Variable** | TailAdmin's face; offline | D34 | owner |
| C8 | Semantic z-index ladder and runtime density variables | Resolves TailAdmin's header/modal tie | — | decided |

## Dates, tests, attribution

| ID | Decision | Rationale | Supersedes | Status |
|---|---|---|---|---|
| F1 | Date and time fields are **native HTML5 inputs**: form-extensions' `Form/datepicker.html.twig` is rewritten in place to emit `type="date\|datetime-local\|time"` from `datepicker_options.display.components`, and `BasePickerType` (P6 f) fixes the wire format to the matching HTML5 pattern; form-extensions' Tempus Dominus JS/CSS and `bundles/sonataform/*` are deleted. No picker library in 1.0; `vanilla-calendar-pro` is the candidate for a later progressive enhancement | flatpickr's last release is from 2022 and Tempus Dominus ships Bootstrap-flavoured light-only CSS; native inputs need no JS, no theme and no format converter; apps drop their `format` options instead of rewriting them | D17 | decided |
| F2 | Range filters keep Symfony `DateType`/`DateTimeType` with `single_text` rendered side by side by an additive `sonata_type_date_range_widget` | Already HTML5 | — | decided |
| Q1 | Inherited tests of all seven packages are kept and re-baselined where markup changes; the `<td class="sonata-ba-list-field …" objectId>` envelope stays byte-identical | The app's 55 cell templates and its XHR accordion depend on it | D35 | decided |
| Q2 | Sonata file headers kept on inherited files; combined header on new files; `LICENSE` with three copyright lines (Rabaix 2010, TailAdmin 2023, IDCT 2026); `NOTICE` lists Stimulus, Tailwind, qs, Font Awesome, Outfit | Clean cherry-picks; MIT/OFL/CC-BY obligations | D36 | decided |
| Q3 | Tree view removed from the roadmap | Scope | D37 | decided |

## Earlier decisions reversed by the owner directives

| Earlier | Was | Now |
|---|---|---|
| D02/D03 (v2 P2/P3) | One package replacing `admin-bundle` only; the other six stayed external dependencies | One package shipping and replacing all seven (P1–P3, P5) |
| D08, D10 | PHP default strings and deprecated options kept, translated by Twig filters | Defaults changed to Tailwind classes; options removed (P6) |
| D12 | jQuery shipped as a deprecated file with a bridge and a jQuery-aware `Admin` facade | No jQuery anywhere; library policy J13 |
| D14 | Blocking scripts in `<head>` | `defer` (J4) |
| D15, D16, D17 | Tom Select, SortableJS, flatpickr shipped; datepicker blocks overridden from the admin theme | Hand-written combobox; nothing in 1.0; native inputs by rewriting form-extensions in place (J5, J8, F1) |
| D20, D26 (b,c), D28, D30, D35, D37 | Compatibility stylesheet, data-API shim, skins, FA shims, dual tokens, tree view | Removed (C4, C6, T1, Q3) |
| D33 (v2 T3) | adminata-owned copy of the flash template | twig-extensions' template rewritten in place (T3) |
| v1 document 06 §2 | Association modal flows re-implemented with `fetch` + `FormData` | AJAX form submission dropped for good (S5, J9) |
| v1 09 backlog, 10, 11 | jQuery removal in 2.x; audit command; compatibility tiers | Done in 1.0 / dropped (S4, P7) |
