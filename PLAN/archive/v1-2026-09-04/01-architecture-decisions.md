# 01 — Architecture decisions

Status legend: **decided** = supported by evidence and consistent across reports; **owner** =
recommended, needs the owner's confirmation; **verify** = decided in principle, one fact must be
checked before implementation. Evidence references point to [research/](research/) reports and the
`R/critique-round-1.md` contradiction ids (C1–C25).

## Fork and packaging

| ID | Decision | Rationale | Status |
|---|---|---|---|
| D01 | Fork from tag **4.43.0**, not the 5.x branch | 5.x views/assets are byte-identical; only 3 PHP files differ (`preBatchAction` made mandatory, a return type, a removed guard). 4.x semantics let persistence bundles keep `^4.39`. `R/php-compat.md` §5 | decided |
| D02 | **Option B** packaging: keep `Sonata\AdminBundle\` namespace, bundle class `SonataAdminBundle`, `@SonataAdmin`, `sonata.admin.*` ids, `sonata_admin` root, routes, translation domain, asset package and `bundles/sonataadmin/` path; publish as `idct/adminata` | Only option that keeps ORM/Mongo bundles, `templates/bundles/SonataAdminBundle/` overrides and `remove_stylesheets` entries working. Scores 35/40 vs overlay 27–29, new namespace 21. `R/packaging.md` §1 | owner |
| D03 | `"replace": {"sonata-project/admin-bundle": "4.43.0"}` (exact upstream version, bumped on each sync); own semver starting **1.0.0** | Verified with Composer 2.9.7: exact version resolves with ORM `^4.20`; `self.version` fails (1.0.0 does not satisfy `^4.39`); a range resolves but lies. `R/packaging.md` §0.6 | decided |
| D04 | Option D (later `Adminata\` namespace with `class_alias`) is **rejected**; documented as considered | `::class` strings, DI FQCN aliases, PHPStan, cache and Flex bundle-name clashes; no user-visible benefit while the PHP API is identical. `R/packaging.md` §1.4 | decided |
| D05 | Floors `php ^8.4`, `symfony ^7.4 \|\| ^8.0` | Matches `MDB/` conventions and Rector/CS sets; drops the Symfony 6.4 shim. Adoption cost is real (Sonata supports 8.2 + 6.4 LTS). | owner |
| D06 | `require` list not trimmed; `sonata-project/form-extensions`, `block-bundle`, `twig-extensions` stay hard dependencies | Templates extend `sonata_block.templates.block_base`; form types registered in `form_types.php`; flash template comes from twig-extensions. | decided |
| D07 | No `conflict` against ecosystem bundles at 1.0; `suggest` + `COMPATIBILITY.md` tiers; `conflict` only when a bundle is proven to fatally break | Verified `conflict` works as a knob (`R/packaging.md` §0.6 app7). | decided |
| D08 | Keep **every PHP default string** (`box box-primary`, `col-md-12`, `col-md-4`, `bg-aqua`, `nav navbar-nav`, `fas fa-folder`, `sonata-medium-date`) and every config node; translate in Twig (`sonata_grid_class`, `sonata_box_class` filters) and CSS | `FormMapperTest`/`ShowMapperTest`/`ConfigurationTest` pin them; `config:dump-reference` parity; `APP/` passes `col-md-*` in 17 places. C7 | decided |
| D09 | `parse_icon` keeps throwing for unknown strings; FA prefixes kept; new syntaxes (`svg:`, raw `<svg>`) are additive | `IconRuntimeTest` pins the exception; no existing config can contain unknown strings. C8 | decided |
| D10 | Deprecate-but-accept `options.skin`, `use_icheck`, `use_bootlint`, `use_select2` (the latter keeps meaning: enhanced selects on/off); new options live under an additive `adminata:` root (theme mode, dark/icon logos, density) | Config schema is public; YAML must keep parsing. | decided |

## JavaScript

| ID | Decision | Rationale | Status |
|---|---|---|---|
| D11 | **Stimulus-only** runtime; Alpine is not shipped | Alpine needs `'unsafe-eval'` or forbids TailAdmin's inline expressions under its CSP build; AJAX-injected fragments need re-init (Stimulus' MutationObserver does it); form-extensions hard-depends on `window.sonataApplication`; the real app already runs a second Stimulus app and jQuery. C1 | decided |
| D12 | jQuery 3.7 shipped as a separate default-listed file `bundles/sonataadmin/vendor/jquery.js`, never imported by adminata code, removable via `remove_javascripts`, deprecated for 2.0; **no** `$.fn.*` adapters | Docs instruct users to write jQuery; Sonata re-added `jquery-form` after a removal broke users; `APP/` externalises `jquery` to the global. C2 | decided |
| D13 | All controller identifiers `sonata-*`; explicit registry file; no `@symfony/stimulus-bridge`, no `require.context` | Existing 9 controllers already prefixed; one namespace for users; ESM build needs explicit definitions. C14 | decided |
| D14 | Scripts stay **blocking in `<head>`** in 1.x; `defer` + `sonata:ready` event is the 2.x path | Inline scripts and users' `extra_javascripts` assume `jQuery`/`Admin`/`sonataApplication` at parse time. C15 | decided |
| D15 | **Tom Select** (Apache-2.0) for autocomplete, multi and tags; `use_select2: true` ⇒ enhance plain selects with Tom Select, `false` ⇒ native styled selects; skip `[data-sonata-select2="false"]`, `[data-controller*="autocomplete"]`, `el.tomselect` | Keeps the option's intent; ux-autocomplete coexistence proven necessary by `APP/`. C3, C4 | decided |
| D16 | **SortableJS** replaces jQuery UI sortable (collections, sortable multi-select via Tom Select `drag_drop`) | Vanilla, MIT, handle/ghost class parity. | decided |
| D17 | **flatpickr** via a `sonata-datepicker` controller; adminata overrides form-extensions' `sonata_type_datetime_picker_widget(_html)` blocks; `bundles/sonataform/app.{js,css}` dropped from default asset lists; never register an identifier named `datepicker` | Stimulus' last-`register()`-wins semantics (verified in stimulus.js) would let form-extensions' Tempus Dominus controller silently replace ours. C11, V14 | decided |
| D18 | Native **`<dialog>`** + `sonata-modal`; keep `id="field_dialog_{id}"`, `.modal-content/.modal-title/.modal-body` hooks; move dialogs to `document.body` on first open | Top layer solves cascaded modals and z-index ties; focus trap and ESC for free; `MDB/` Panther test selects `.modal-content button[name="btn_create"]`. C23 | decided |
| D19 | Inline edit: `sonata-editable` controller with a Tailwind popover, same `x-editable` data attributes and the untouched `sonata_admin_set_object_field_value` endpoint; `list_boolean` `data-source` becomes JSON | Contract documented in `R/list-datagrid.md` §4. | decided |
| D20 | Bootstrap 3 data-API delegate (`data-toggle="dropdown\|tab\|collapse\|modal"`, `data-dismiss="alert\|modal"`) at document level for user markup | Cheap; unblocks overridden templates and ORM `block_audit`. | decided |
| D21 | Fix the upstream shift-range bug (`indexedDB` typo, `admin.js:173`) and move the mosaic batch checkbox out of the tile link | Documented behaviour promises what the fix delivers; note in CHANGELOG. | owner |
| D22 | Build with **Vite** + `@tailwindcss/vite` + **Vitest**; Node ≥ 22; outputs committed under `src/Resources/public` with a CI freshness gate; two JS outputs: `app.js` (IIFE, globals) and `app.esm.js` (`startAdminata({application})`) plus AssetMapper path registration | form-extensions already uses Vitest; un-hashed names keep `remove_*` entries valid; ESM enables single-application apps. `R/js-assets.md` §8–9 | decided |
| D23 | Window-scroll layout model (React TailAdmin), not the HTML template's inner-scrolling column | `sticky_controller.js` stays untouched; per-page `window.top` semantics; browser find/print behave. C10 | decided |
| D24 | Sidebar collapse persisted in the existing `sonata_sidebar_hide` cookie; per-group open state in `localStorage` (`sonata_sidebar_open`); `keep_open`/`on_top` semantics preserved | Server-side first paint without flicker; `MenuTest` selector `.sidebar-menu .dynamic-menu a` kept. | decided |
| D25 | Preloader dropped; no `sonata_preloader` block | 500 ms perceived latency for nothing. C17 | decided |

## CSS and theming

| ID | Decision | Rationale | Status |
|---|---|---|---|
| D26 | Layered CSS strategy: (1) prebuilt `app.css` from adminata templates; (2) semantic `.adm-*` API as `@utility` + generated safelist; (3) Bootstrap-3/AdminLTE compat layer in `@layer components`, all selectors scoped under `.sonata-bc`, shipped as a separate default-on `compat-bootstrap3.css`; (4) documented per-app compile recipe with `@source` globs | `@utility` classes are purged unless scanned; `@layer components` rules always ship — the compat layer must survive markup the bundle never scans. C13, C21 | verify (T1–T3) |
| D27 | No `@tailwindcss/forms` | TailAdmin uses none; its base reset restyles third-party markup the compat layer controls. C12 | decided |
| D28 | `@theme static` with TailAdmin's tokens copied verbatim (no `--font-*`/`--breakpoint-*` resets); brand re-theming by redefining `--color-brand-*`; the 12 `skin-*` values become token files at the kept path `admin-lte-skins/skin-*.min.css` | Utilities compile to `var(--color-*)`, so runtime override needs no rebuild; kept path preserves `remove_stylesheets` semantics and the DI extension's unconditional append. | verify (T5) |
| D29 | Dark mode: cookie `sonata_theme` (`light\|dark\|system`), server stamps `<html class="dark">`; `@custom-variant dark (&:where(.dark, .dark *))`; only `system` mode needs a 3-line pre-paint script rendered with a `sonata_script_attributes` nonce block | Mirrors the sidebar cookie; no FOUC; CSP-clean. TailAdmin's `(&:is(.dark *))` does not match the `.dark` element itself. C9 | decided |
| D30 | Icons: Font Awesome 6 Free + `v4-shims` + `v4-font-face`, woff2 only, separate removable `fontawesome.css`; TailAdmin inline SVG only inside adminata's own chrome | `APP/` uses FA6-only names and FA4 names via shims; Sonata itself uses one FA4 name. C20 | decided |
| D31 | Tabs: underline tabs for page/form/show tabs; segmented control only for the list-mode switch | Many child-admin tabs must wrap/scroll. C18 | decided |
| D32 | Delete and batch confirmation stay full pages (no-JS and XHR JSON contracts unchanged) | C25 | decided |
| D33 | Flash messages: adminata-owned copy of twig-extensions' `FlashMessage/render.html.twig` included from the `notice` block; `alert-{type}` marker classes and the collapse variable kept; config switch to fall back to `@SonataTwig` | twig-extensions ships the template with Bootstrap markup; the layout includes it at `standard_layout.html.twig:296-298`. V5/V6 | decided |
| D34 | Font: self-host **Outfit Variable** (`@fontsource-variable/outfit`, OFL) — no Google Fonts request | Admin bundles must work offline. | owner (system stack alternative) |

## Tests and attribution

| ID | Decision | Rationale | Status |
|---|---|---|---|
| D35 | Freeze the `<td class="sonata-ba-list-field sonata-ba-list-field-{type}" objectId="…">` envelope as a regression guard; re-baseline the 20 boolean-badge and x-editable inner-markup expectations; keep `label label-success\|danger` tokens alongside TailAdmin badge classes | V15/C16/C22 | decided |
| D36 | Keep the Sonata file header on inherited files; new files get a combined header; `LICENSE` with three copyright lines (Rabaix 2010, TailAdmin 2023, IDCT 2026); `NOTICE` lists Tom Select (Apache-2.0), FA (CC-BY-4.0/OFL/MIT), Outfit (OFL), flatpickr, SortableJS, Stimulus, Tailwind | Keeps cherry-picks clean; MIT/Apache obligations. | decided |
| D37 | Tree view stays deprecated with upstream parity (no `outer_list_rows_tree` file, dangling include kept); `treeview.js` ported to `sonata-treeview` for third-party bundles | Adding files would silently diverge from Sonata. | owner |
