# 05 — JavaScript architecture

Condensed from `R/gap-js-architecture.md` (controller cards with every target, value, class,
action and event) and `R/js-assets.md`. Decisions J1–J12 apply. Versions verified 2026-09-04:
`@hotwired/stimulus` 3.2.2 (dist-tag `latest`; no newer line exists), `qs` 6.16.0, `vite` 8.2.2,
`vitest` 5.0.0, `jsdom` 30.0.1, Node 24.20.0 LTS (26.8.1 current).

## 1. Runtime and conventions

- **Stimulus 3.2.2 only**; one `Application` owns every `sonata-*` controller and is exposed as
  `window.sonataApplication` (apps may register into it). No Alpine, no jQuery, no `window.Admin`,
  no `window.stimulus`. ESLint `no-restricted-globals: ['$', 'jQuery', 'Alpine']` everywhere.
- Identifiers `sonata-<name>`, files `assets/js/controllers/<name>_controller.js`, explicit
  registration in `assets/js/registry.js` (no `stimulus-bridge`, no `require.context`).
- Twig always uses `stimulus_controller()/stimulus_target()/stimulus_action()` from
  `symfony/stimulus-bundle` 3.4 (already required). No `onclick`, no inline scripts.
- Events are dispatched with `this.dispatch()` → `sonata-<name>:<event>`, bubbling, cancelable;
  the inherited names (`sonata-admin-append-form-element`, `sonata-collection-item-*`,
  `sonata.add_element`) keep `prefix: ''` exactly as `collection_controller.js` does today.
- Show/hide uses the `hidden` **attribute**, never a class.
- `Config.param()` returns `null` for a missing `<meta name="sonata-config">` and keeps throwing on
  malformed JSON.
- Runtime dependencies: `qs` (BSD-3) only.
- **Library policy (J13)**: a package that depends on jQuery is rejected outright — `npm ls jquery`
  must be empty in CI and ESLint bans the import. When a behaviour needs more than plain DOM code,
  first check whether Tailwind/TailAdmin already covers it in CSS (dropdown panels, native
  `<dialog>`, `<details>` accordions, CSS tooltips); otherwise choose a modern, popular, actively
  released vanilla library (SortableJS, vanilla-calendar-pro, Tom Select are the vetted candidates).

## 2. Inherited controllers (9)

| Identifier | Change |
|---|---|
| `sonata-collection` | none |
| `sonata-confirm-exit` | none (tolerate missing meta) |
| `sonata-edit` | the one jQuery line (`jQuery(tab).tab('show')`, `edit_controller.js:49`) becomes `this.dispatch('show', {prefix: 'sonata-tabs'})`; a no-op until tabs exist |
| `sonata-filter` | none (`qs` kept; public `toggleFilter(id, state)`); must tolerate ux-autocomplete-wrapped selects in the panel (it only disables empty *inputs*) |
| `sonata-filter-list` | none (public `disable(id)`) |
| `sonata-per-page` | none |
| `sonata-readmore` | none (markup pinned by tests) |
| `sonata-revision` | none (already `fetch`; page deferred) |
| `sonata-sticky` | none (window-scroll model keeps its viewport observers valid); `.stuck` CSS rewritten |

The rest of Sonata's JS (`admin.js`, `base.js`, `sidebar.js`, `treeview.js`, the inline scripts
of `base_list`, `form_admin_fields`, the association templates) is deleted.

## 3. New controllers in 1.0 (8)

| # | Identifier | Mounts on | Replaces | Key behaviour |
|---|---|---|---|---|
| 1 | `sonata-layout` | `<body>` | AdminLTE push-menu, `sidebar.js`, TailAdmin `sidebarToggle`/`menuToggle` | values `collapsed`, `mobileOpen`, `headerMenuOpen`, `breakpoint` (1024), `cookieName` (`sonata_sidebar_hide`); targets `sidebar, overlay, toggle, headerMenu, headerMenuToggle, collapseOnly`; writes the cookie (`SameSite=Lax`); `inert` on content while the mobile drawer is open; events `sonata-layout:sidebar-changed`, `header-menu-changed`; tolerates missing targets (`empty_layout`) |
| 2 | `sonata-menu` | KnpMenu `<nav>` root | AdminLTE `data-widget="tree"` | per-group open map in `localStorage` (`sonata_sidebar_open`) seeded from server `active`/`keep-open`; `aria-expanded` on group buttons; several groups may stay open; section-header items are inert |
| 3 | `sonata-dropdown` | any wrapper | Bootstrap `data-toggle="dropdown"` | targets `toggle, menu`; click-outside, ESC, arrow keys, `aria-expanded`; used by user menu, add block, export, language dropdown the app appends |
| 4 | `sonata-modal` | `<dialog>` | Bootstrap `.modal()` | `open()/close()` actions, sizes `sm/md/lg/list`, moves to `document.body` on first open, closes on backdrop click when `closable`, dispatches `sonata-modal:opened/closed`; usable by apps on their own dialogs |
| 4a | `sonata-modal-trigger` | `<dialog>` | Bootstrap `data-toggle="modal"` | `open()` action on any element; values `title`, `text` (set as text), `content` (id of an element whose markup is copied), `size` (for that opening), `target` (defaults to the layout's shared `sonata-dialog`, rendered by the `sonata_dialog` block from `Core/dialog.html.twig`); fills the dialog and opens it through its `sonata-modal` instance (added 2026-09-07, after the panel had written its own) |
| 4b | `sonata-reveal` | `hidden` attribute | the panel's `SectionSlider.js` | on the control; values `target` (selector) and `when` (a value or a JSON list); applies on connect and on `change`; removes a server-rendered `hidden` class when it takes over so a saved value starts its section in the right state without a flash (added 2026-09-07) |
| 5 | `sonata-theme` | header dark-mode button | TailAdmin `darkMode` localStorage | toggles `html.dark`, writes `sonata_theme` cookie, dispatches `sonata-theme:changed` |
| 6 | `sonata-dismiss` | alerts | `data-dismiss="alert"` | removes/hides the element |
| 7 | `sonata-batch` | list `<form>` | inline `batch_javascript` + `Admin.setup_checkbox_range_selection` | select-all with indeterminate state, row highlight (`sonata-ba-list-row-selected`), shift-range (upstream `indexedDB` typo fixed) |
| 8 | `sonata-autocomplete` | the `sonata_type_model_autocomplete` widget wrapper | select2 ajax script | hand-written combobox (document 06 §3): debounce, min length, remote paging against `sonata_admin_retrieve_autocomplete_items`, single and multiple (chips), ARIA 1.2 combobox pattern, `<template>` blocks for item and selection rendering, hidden-input submit shape kept, `_context=filter` for filters; events `sonata-autocomplete:selected/removed/cleared` |

Tooltips are out of 1.0 (`title` stays native). No search shortcut (`search: false` in the app).

## 4. Post-1.0 controllers (10)

`sonata-association` (list selection in a `<dialog>` loaded with `fetch` GET, create/edit as full
pages with a return parameter — **no AJAX form submission**, S5/J9; the v1 `fetch` + `FormData`
design is withdrawn), `sonata-sortable` (SortableJS 1.15.7), `sonata-choice-field-mask`, `sonata-inline-row`,
`sonata-editable` (Tailwind popover, JSON `data-source`), `sonata-treeview`, `sonata-tabs`
(underline tabs, `role="tablist"`, `sonata-tabs:show`), `sonata-select` (Tom Select 2.6.2, only if
select enhancement is ever wanted), `sonata-search-shortcut` (⌘/Ctrl+K), `sonata-datepicker`
(`vanilla-calendar-pro` 3.3.2 as progressive enhancement over the native inputs). Each has a
controller card in `R/gap-js-architecture.md` §1.

## 5. Coexistence with the app's own Stimulus application (verified against Stimulus 3.2.2 source)

| Rule | Statement |
|---|---|
| R1 | An app may keep its own `Application` and controllers; identifiers are resolved per application; unknown identifiers are ignored silently |
| R2 | Never register a `sonata-*` identifier in the app's application while `bundles/sonataadmin/app.js` is loaded |
| R3 | Outlets resolve within one application, so all adminata controllers live in exactly one application |
| R4 | adminata never touches elements whose `data-controller` contains `autocomplete` (ux-autocomplete) and never dispatches `autocomplete:*` events; ux-autocomplete's Tom Select CSS is the app's business |
| R5 | Script order: adminata's `app.js` first, the app's `extra_javascripts` after it, all `defer`; Stimulus applications start on `DOMContentLoaded` independently |
| R6 | Elements inserted by adminata (collection rows, XHR list fragments) carry the app's `data-controller` attributes untouched; the app's MutationObserver connects them |

## 6. Removed behaviours and their replacement in the app

select2, iCheck, x-editable, jQuery UI sortable, jquery-form and its `ajaxSubmit` feature (the
in-modal submission of association forms, dropped for good), Bootstrap JS (`.modal()`, `.tab()`,
`.dropdown()`, `.collapse()`, `.popover()`, `.tooltip()`, `.alert()`), slimscroll, masonry,
scrollTo, `treeView`, the per-field global functions of the association templates, `window.Admin`,
`window.jQuery`. recomaty-panel's three jQuery files and one inline script are rewritten in plain
DOM code (document 10 §2); `jquery-ui-bundle` is removed from its `package.json`.

## 7. Build entries and outputs

```
assets/js/app.js            # IIFE → packages/admin-bundle/src/Resources/public/app.js: starts the application, sets
                            #   window.sonataApplication, removes html.no-js
assets/js/registry.js       # explicit definitions (9 inherited + 8 new)
assets/js/core/{config,translation,utils,dom}.js
assets/js/controllers/*_controller.js
assets/js/__contract__/controllers.json   # committed snapshot
assets/css/app.css, fontawesome.css       # CSS entries (document 04)
```

- Vite 8: `iife` build with fixed names (`app.js`, `app.css`, `fontawesome.css`,
  `fonts/[name][extname]`); `define __ADMINATA_VERSION__`; no source maps in production;
  `entrypoints.json`/`manifest.json` written by a tiny plugin. ESM library build (`startAdminata`)
  and AssetMapper path registration are post-1.0.
- CI: `npm ci && npm run build && git diff --exit-code -- packages/admin-bundle/src/Resources/public`; `size-limit`
  13: `app.js` ≤ 100 KB minified (Stimulus + qs + 17 controllers).
- Default `sonata_admin.assets.javascripts`: `bundles/sonataadmin/app.js`, rendered
  `<script src defer>`; the app's `extra_javascripts` follow, also `defer`.

## 8. CSP and i18n

- No inline scripts in adminata templates except the 3-line `system`-theme pre-paint script, rendered
  as `<script{{ block('sonata_script_attributes') }}>` for a nonce. No `onclick`.
- Translations flow through `<meta name="sonata-translations">` (keys extended additively: combobox
  messages `SELECT_NO_RESULTS`, `SELECT_LOADING`, `SELECT_TYPE_TO_SEARCH`, `SELECT_INPUT_TOO_SHORT`,
  `MODAL_CLOSE`, `THEME_TOGGLE`, …), all from the `SonataAdminBundle` XLIFF domain.
- `<html lang="{{ app.request.locale }}" dir="…">` with a small RTL locale map.

## 9. Tests

- Vitest 5 (jsdom 30) per controller on HTML fixtures **dumped from the real Twig templates** by a
  PHPUnit test (`tests/fixtures/js/*.html`).
- Contract snapshot test: executes the built `app.js` in jsdom and compares identifiers, targets,
  values, outlets and events with `__contract__/controllers.json`.
- Panther smoke suite (document 08) asserts an empty browser console on every page.
