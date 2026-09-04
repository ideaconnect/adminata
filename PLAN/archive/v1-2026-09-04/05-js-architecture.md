# 05 — JavaScript architecture

Condensed from `R/gap-js-architecture.md` (controller cards with every target, value, class,
action and event) and `R/js-assets.md`. Decisions D11–D22 apply.

## 1. Runtime and conventions

- **Stimulus 3 only**; one `Application` owns every `sonata-*` controller and is exposed as
  `window.sonataApplication` (form-extensions registers into it). No Alpine, no jQuery in adminata
  code (ESLint `no-restricted-globals: ['$', 'jQuery', 'Alpine']` outside the two compat files).
- Identifiers `sonata-<name>`, files `assets/js/controllers/<name>_controller.js`, explicit
  registration in `assets/js/registry.js` (no `stimulus-bridge`, no `require.context`).
- Twig always uses `stimulus_controller()/stimulus_target()/stimulus_action()` from
  `symfony/stimulus-bundle` (already required).
- Events are dispatched with `this.dispatch()` → `sonata-<name>:<event>`, bubbling, cancelable;
  legacy names (`sonata-admin-append-form-element`, …) with `prefix: ''` exactly as
  `collection_controller.js` does today. Every controller declares `static events = […]`.
- Show/hide uses the `hidden` **attribute**, never a `hide`/`hidden` class.
- Legacy class hooks are toggled alongside the new ones (`<li class="active">` + `aria-selected`).
- `Config.param()` must return `null` for a missing `<meta name="sonata-config">` (ESM path may
  run on pages without it) and keep throwing on malformed JSON.
- Third-party libraries: `tom-select` (Apache-2.0), `sortablejs` (MIT), `flatpickr` (MIT),
  `qs` (BSD-3). No Floating UI in 1.0.

## 2. Kept controllers (9)

| Identifier | Change |
|---|---|
| `sonata-collection` | none |
| `sonata-confirm-exit` | none (tolerate missing meta) |
| `sonata-edit` | one line: `jQuery(tab).tab('show')` → dispatch `sonata-tabs:show` |
| `sonata-filter` | none (`qs` kept; public `toggleFilter(id, state)`) |
| `sonata-filter-list` | none (public `disable(id)`) |
| `sonata-per-page` | none; select2 change hack in `admin.js:386-390` deleted |
| `sonata-readmore` | none (markup pinned by tests) |
| `sonata-revision` | none (already `fetch`) |
| `sonata-sticky` | none (window-scroll model keeps its viewport observers valid); `.stuck` CSS rewritten |

## 3. New controllers (18)

| # | Identifier | Mounts on | Replaces | Key behaviour |
|---|---|---|---|---|
| 1 | `sonata-layout` | `<body>` | AdminLTE push-menu, `sidebar.js` cookie, TailAdmin `sidebarToggle`/`menuToggle` | values `collapsed`, `mobileOpen`, `headerMenuOpen`, `breakpoint` (1024), `cookieName` (`sonata_sidebar_hide`); targets `sidebar, overlay, toggle, headerMenu, headerMenuToggle, collapseOnly`; writes the cookie (`SameSite=Lax`); toggles legacy `sidebar-collapse`/`sidebar-open` body classes; `inert` on content while the mobile drawer is open; events `sonata-layout:sidebar-changed`, `header-menu-changed`; tolerates missing targets (`empty_layout`) |
| 2 | `sonata-menu` | KnpMenu `<nav>` root | AdminLTE `data-widget="tree"`, TailAdmin `$persist('selected')` | per-group open map in `localStorage` (`sonata_sidebar_open`) seeded from server `active`/`keep-open`; `aria-expanded` on group buttons; multiple groups may stay open |
| 3 | `sonata-dropdown` | any wrapper (`li.dropdown`, `div.btn-group`, `div.relative`) | Bootstrap `data-toggle="dropdown"`, TailAdmin `@click.outside` | targets `toggle, menu`; click-outside, ESC, arrow keys, `aria-expanded`; keeps `.open` legacy class |
| 4 | `sonata-modal` | `<dialog class="modal">` | Bootstrap `.modal()`, `Admin.setup_list_modal` inline sizes | `open()/close()` actions, sizes `sm/md/lg/list`, moves to `document.body` on first open, `data-dismiss="modal"` support, dispatches `sonata-modal:opened/closed` |
| 5 | `sonata-tabs` | `.nav-tabs-custom` wrapper | Bootstrap `data-toggle="tab"`, `$.fn.tab` | `role="tablist"`, `aria-selected`, `hidden` panes, listens to `sonata-tabs:show`; error badge class |
| 6 | `sonata-theme` | header dark-mode button | TailAdmin `darkMode` localStorage | toggles `html.dark`, writes `sonata_theme` cookie, dispatches `sonata-theme:changed` |
| 7 | `sonata-dismiss` | alerts | `data-dismiss="alert"` | removes/hides the element |
| 8 | `sonata-search-shortcut` | header search form | `T/js/index.js:91-118` | ⌘/Ctrl+K and `/` (ignores editable targets) |
| 9 | `sonata-batch` | list `<form>` | inline `batch_javascript` + `Admin.setup_checkbox_range_selection` | select-all with indeterminate state, row highlight (`sonata-ba-list-row-selected`), shift-range (upstream `indexedDB` bug fixed) |
| 10 | `sonata-editable` | `span.x-editable` | x-editable + `Admin.setup_xeditable` | Tailwind popover; reads `data-type/value/title/pk/url/source/format`; POST with `X-Requested-With`; replaces closest `<td>`; `sonata-editable:saved` |
| 11 | `sonata-sortable` | `#field_container_{id}` | jQuery UI sortable scripts | SortableJS with `handle: '.sonata-ba-sortable-handler'`, renumbers position inputs, listens to `sonata.add_element` |
| 12 | `sonata-select` | `<select>` | select2 + `Admin.setup_select2` | Tom Select honouring `data-sonata-select2*`, `data-placeholder`; skip rules (§5); re-dispatches native `change`; multi + `drag_drop` implements `setup_sortable_select2` |
| 13 | `sonata-autocomplete` | `#{id}_autocomplete` wrapper | select2 ajax script | Tom Select `load` with pagination against `sonata_admin_retrieve_autocomplete_items`; `render.option/item` from the Twig blocks; `dropdownParent` = closest dialog or body |
| 14 | `sonata-association` | `#field_container_{id}.field-container` | `edit_many_script`, `edit_one_script`, `admin.js` re-setup | modes list/add/edit/remove/append/autocomplete; `fetch` + `FormData`; contracts in document 06 §2; events `sonata-association:created/selected/removed/appended`; injects violations into `.form-group.has-error .help-block.sonata-ba-field-error-messages` |
| 15 | `sonata-choice-field-mask` | wrapper around `choice_widget` | inline script `form_admin_fields.html.twig:465-535` | same 4-step id ladder, `required`↔`data-required` swap, inline-table column hiding |
| 16 | `sonata-inline-row` | `.sonata-ba-field-inline-table` | `Admin.setup_inline_form_errors/switch_inline_form_errors` | delete-checkbox toggles required/error state |
| 17 | `sonata-treeview` | `ul.js-treeview` | `treeview.js` jQuery plugin | `data-treeview-toggler/toggled`, `is-toggled/is-active` |
| 18 | `sonata-datepicker` | `div#{id}_controller.input-group.date` | form-extensions `datepicker` (Tempus Dominus) — **never named `datepicker`** | flatpickr; translates `datepicker_options` and TD format tokens; range linking via outlet `sonata-datepicker`; `sonata-datepicker:connect` (`detail.flatpickr`) |
| — | `sonata-compat` (document delegate, not a controller) | `document` | Bootstrap 3 data-API | `data-toggle="dropdown|tab|collapse|modal"`, `data-dismiss="alert|modal"` on user markup |

Tooltips are out of 1.0 (no Sonata template uses them; `title` stays native).

## 4. `window.Admin` facade and globals

- All 16 members exist with the same signatures; `subject` is normalised by
  `toElement()` (jQuery collection → `[0]`, selector string, Element, Document).
  `setup_select2`, `setup_xeditable`, `setup_tree_view`, `setup_inline_form_errors` tag elements
  for the corresponding controllers; `setup_icheck` is a logging no-op; `setup_list_modal` asks
  `sonata-modal` for the `list` size and dispatches `sonata-admin-setup-list-modal` natively;
  `setup_sortable_select2(subject, data, options)` keeps its signature (select2-only keys ignored).
- `window.adminata = { version, application, Admin, controller(el, id), association(id),
  autocompleteHooks, startAdminata, bridgeJQuery }`.
- Global function shims from association templates (`start_field_dialog_form_add_{id}` etc.)
  forward to `sonata-association`; deprecated, removed in 2.0.
- jQuery bridge (`assets/js/compat/jquery-bridge.js`): when `window.jQuery` exists, re-dispatch
  jQuery-`trigger`ed legacy event names as native events (jQuery `.on()` already receives native
  events; `.trigger()` does not produce them).
- Meta keys: `sonata-config` unchanged; `sonata-translations` extended (`SELECT_NO_RESULTS`,
  `SELECT_LOADING`, `SELECT_TYPE_TO_SEARCH`, `SELECT_INPUT_TOO_SHORT`, `EDITABLE_SAVE`,
  `EDITABLE_CANCEL`, `EDITABLE_EMPTY`, `MODAL_CLOSE`, `THEME_TOGGLE`, …), all from the
  `SonataAdminBundle` XLIFF domain.

## 5. Coexistence rules (verified against Stimulus 3.2.2 source)

| Rule | Statement |
|---|---|
| R1 | An app may keep its own Stimulus `Application` and controllers; identifiers are resolved per application; unknown identifiers are ignored silently |
| R2 | Never register a `sonata-*` identifier in the app's application while `bundles/sonataadmin/app.js` is loaded (two instances on one element) |
| R3 | Outlets resolve within one application, so all adminata controllers must live in exactly one application |
| R4 | With two applications, `window.sonataApplication` is adminata's; third-party `datepicker` registers into it |
| R5 | `sonata-select` skips `select[data-sonata-select2="false"]`, any element whose `data-controller` contains `autocomplete`, any element with `el.tomselect`, and `select.per-page` when `use_select2` is off |
| R6 | Two Tom Select copies may coexist (adminata's + ux-autocomplete's); CSS is shared through the same `.ts-*` hooks |
| R7 | adminata's Tom Select theme uses specificity ≤ (0,2,0) so app skins override |
| R8 | Tom Select `dropdownParent` defaults inline inside `<dialog>`; `'body'` only outside dialogs inside overflow-clipping ancestors |
| R9 | adminata never dispatches `autocomplete:*` events |
| R10 | `ChoiceFieldMaskType` fallback ids unchanged |
| R11 | Never name a controller `datepicker`; never `unload('datepicker')` |
| R12 | `bundles/sonataform/app.js`, if re-added, must load after `bundles/sonataadmin/app.js` (or after `startAdminata`) |

## 6. Build entries and outputs

```
assets/js/app.js            # IIFE → src/Resources/public/app.js: imports CSS, starts the application,
                            #   sets window.{stimulus, sonataApplication, Admin, adminata}, removes html.no-js
assets/js/app.esm.js        # ES  → app.esm.js: no side effects; exports startAdminata({application, exposeGlobals,
                            #   jQuery, bootstrapDataApi}), definitions, Admin, stimulus, version
assets/js/vendor/jquery-entry.js  # IIFE → vendor/jquery.js: window.$ / window.jQuery only, no plugins
assets/js/registry.js       # explicit definitions (9 kept + 18 new)
assets/js/admin.js          # facade
assets/js/core/{config,translation,utils,dom}.js
assets/js/compat/{jquery-bridge,bootstrap-data-api}.js
assets/js/__contract__/controllers.json   # committed snapshot
```

- Vite: `iife` build with fixed names (`app.js`, `vendor/jquery.js`, `app.css`,
  `fonts/[name][extname]`), `es` library build for `app.esm.js`; `define __ADMINATA_VERSION__`;
  no source maps in production; `entrypoints.json`/`manifest.json` written by a tiny plugin.
- CI: `npm ci && npm run build && git diff --exit-code -- src/Resources/public`; `size-limit`
  budgets (`app.js` ≤ 220 KB incl. Tom Select + SortableJS + flatpickr; `vendor/jquery.js` ≈ 87 KB).
- Default `sonata_admin.assets.javascripts`: `bundles/sonataadmin/vendor/jquery.js`,
  `bundles/sonataadmin/app.js` (blocking, in `<head>`); `sonataform` entries dropped.
- App recipes: Encore (`remove_javascripts: [bundles/sonataadmin/app.js]`, `"@idct/adminata":
  "file:vendor/idct/adminata/assets"`, `startAdminata({ application: app })`), AssetMapper
  (`importmap.php` entry via the registered path `@idct/adminata`), Vite (alias). Script order
  still matters for `bundles/sonataform/app.js` (R12).

## 7. CSP and i18n

- No Alpine ⇒ no `'unsafe-eval'`. Remaining inline scripts (the `system`-theme pre-paint script,
  Twig JS blocks users hook into, the association shims) render `<script{{ block('sonata_script_attributes') }}>`
  for a nonce. adminata templates use `data-action` and keep `onclick` shims only on association
  buttons (owner: keep both or drop `onclick` from adminata's own markup).
- `canonicalize_locale_for_select2()` stays registered; `select2-locale/*.js` files are gone;
  flatpickr locale loaded by `app.request.locale` via dynamic import inside `sonata-datepicker`.

## 8. Tests

- Vitest (jsdom) per controller on HTML fixtures **dumped from the real Twig templates** by a
  PHPUnit test (`tests/fixtures/js/*.html`).
- Contract snapshot test: executes the built `app.js` in jsdom and compares identifiers, targets,
  values, outlets, events and `window.Admin` members with `__contract__/controllers.json`.
- Panther smoke suite (document 08) asserts an empty browser console.

## 9. User JS that breaks (UPGRADE table, wording in `R/gap-js-architecture.md` §6)

select2 (`$.fn.select2`, events, locales) → Tom Select + `sonata-select:connect`; iCheck →
native inputs and `change`; Bootstrap 3 JS (`.modal()`, `.tab()`, `.dropdown()`, `.collapse()`,
`.popover()`, `.tooltip()`, `.alert()`) → controllers + compat delegate, `$.fn.*` not provided;
x-editable → `sonata-editable` (JSON `data-source`); jQuery UI sortable → SortableJS
(`$.fn.sortable` gone, incl. `jquery-ui/ui/widget`); `jquery-form`/`ajaxSuccess` →
`sonata-association:created`; `$.fn.treeView` → `sonata-treeview`; scrollTo/slimscroll/masonry
gone; per-field helper globals gone except the five `onclick` entry shims; jQuery `.trigger()`
forwarded only while `vendor/jquery.js` is loaded; form-extensions `datepicker:*` events not
fired for admin forms; Alpine snippets pasted from TailAdmin need Alpine added by the app.
