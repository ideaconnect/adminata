# adminata research — gap G1: one reconciled JavaScript architecture

Stimulus-only runtime, complete controller registry, `window.Admin` facade, globals/events contract, jQuery policy, coexistence with a second Stimulus `Application` and `symfony/ux-autocomplete`, build entries, contract tests, and the user-JS breakage table.

Path aliases:

| Alias | Root |
|---|---|
| `S/` | `scratchpad/sonata-admin-4.43.0/` |
| `T/` | `scratchpad/tailadmin-html/src/` |
| `TR/` | `scratchpad/tailadmin-react/src/` |
| `FE/` | `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle/vendor/sonata-project/form-extensions/` (the extract under `vendor-extract/` has no views; this copy has them) |
| `APP/` | `/home/bartosz/dev/r3/recomaty-panel/` (production Sonata 4.43.0 app) |
| `STIM/` | `APP/node_modules/@hotwired/stimulus/dist/stimulus.js` (Stimulus 3.2.2) |
| `TS/` | `APP/node_modules/tom-select/` (2.5.2) |
| `R/` | `scratchpad/research/` (prior reports) |

---

## 0. Baseline (decisions inherited from `R/critique-round-1.md` §3, not reopened)

| Decision | Source | Consequence used below |
|---|---|---|
| Stimulus-only; Alpine is not shipped | C1 | Every `x-data`/`:class`/`@click` in `R/layout-nav.md` §1–§4 becomes a `sonata-*` controller (§1.4). |
| All controller identifiers are `sonata-*` | C14 | Matches `S/assets/js/stimulus.js:25-27` which already prefixes the nine upstream controllers. |
| jQuery 3.7 ships as `bundles/sonataadmin/vendor/jquery.js`, default-listed, never imported by adminata code, removable via `remove_javascripts`, deprecated for 2.0 | C2 | §3.1, §5.1. Verified need: `APP/webpack.config.js:16` externalises `jquery` to the global `jQuery` — the real app's own `import $ from 'jquery'` (`APP/assets/app.js:3`) resolves to Sonata's global; removing it would crash `APP/assets/admin/*.js`. |
| Scripts stay blocking in `<head>` in 1.x | C15 | `S/src/Resources/views/standard_layout.html.twig:61-65` loop unchanged; inline scripts (`edit_one_to_many_sortable_script_table.html.twig:12`) run at parse time and expect `Admin`/`sonataApplication`. |
| Window-scroll layout model | C10 | `sticky_controller.js` unchanged (§1.2). |
| Native `<dialog>` modals | C23 | `sonata-modal` (§1.3.4). |
| Tom Select for autocomplete/multi and, when `use_select2` is true, for plain selects | C4 | `sonata-select`, `sonata-autocomplete` (§1.3.12, §1.3.13); skip rules in §4.3. Apache-2.0 (C3). |
| adminata's datepicker controller must not be named `datepicker` | C11 / V14 | `sonata-datepicker` (§1.3.18); form-extensions registers `datepicker` on `window.sonataApplication` (`FE/assets/js/app.js:16-18`) and `STIM:2014-2016` unloads an existing identifier on `register()` — last wins. |

Facts verified in this report that the plan should treat as hard constraints:

1. **Stimulus resolves identifiers, actions and outlets per `Application`.** `Router` keeps `modulesByIdentifier` per application (`STIM:1983-1989`); `ScopeObserver.parseValueForElementAndIdentifier` creates a `Scope` for *every* `data-controller` token it sees, registered or not (`STIM:1948-1956`), but `Router.scopeConnected` only connects a context when a module for that identifier exists in *that* router (`STIM:2050-2056`). Outlets are resolved with `this.application.getControllerForElementAndIdentifier` (`STIM:1447-1449`) — same application only. See §4.1.
2. **`Application.start()` awaits DOM ready** (`STIM:2108-2114`), so the order in which two applications are created does not matter, and anything registered synchronously before DOMContentLoaded is present when observation starts.
3. **`Controller.dispatch` emits native bubbling, cancelable `CustomEvent`s** with `${prefix}:${eventName}` (`STIM:2546-2551`); `prefix: ''` gives an un-namespaced name (used by `collection_controller.js:34-65`). jQuery `.on()` receives native events; jQuery `.trigger()` does **not** produce native events (§3.3).
4. **`Config.param()` throws when the meta tag is absent** (`S/assets/js/core/config.js:15-24`, `JSON.parse(undefined)`), and two controllers evaluate it at registration time via `static get shouldLoad()` (`confirm_exit_controller.js:20-22`, `sticky_controller.js:17-19`). On the ESM path (§4.2) adminata controllers may be registered on pages without `<meta name="sonata-config">`; `Config.param` must return `null` for a missing tag (keep the throw for malformed JSON).
5. **Tom Select 2.5.2** ships `drag_drop` (native `draggable`/`dragstart`, no jQuery UI — `TS/dist/js/plugins/drag_drop.js:149-220`), `virtual_scroll` (`firstUrl`/`setNextUrl`/`clearPagination`, `TS/dist/js/plugins/virtual_scroll.js:133-182`), `clear_button`, `remove_button`, `dropdown_input`, `checkbox_options`; options `dropdownParent`, `maxItems`, `allowEmptyOption`, `controlInput`, `create`; events `change`, `item_add`, `item_remove`, `dropdown_open`, `dropdown_close`, `load` (grep over `TS/dist/js/tom-select.complete.js`). Tom Select also accepts jQuery objects in its DOM helper (`drag_drop.js:71-77`) — irrelevant to adminata but harmless.
6. **`symfony/ux-autocomplete` 2.36** attaches `data-controller="symfony--ux-autocomplete--autocomplete"` plus `data-symfony--ux-autocomplete--autocomplete-*-value` attributes in `AutocompleteChoiceTypeExtension::finishView` (`APP/vendor/symfony/ux-autocomplete/src/Form/AutocompleteChoiceTypeExtension.php:57-103`), dispatches `autocomplete:pre-connect`, `autocomplete:connect`, `autocomplete:before-reset` (`APP/vendor/symfony/ux-autocomplete/assets/dist/controller.js:86-100,328-334`), and its values are `url, optionsAsHtml, loadingMoreText, noResultsFoundText, noMoreResultsText, createOptionText, minCharacters, tomSelectOptions, preload, resetOnFocus` (`:345-356`). Its form theme is registered globally (`AutocompleteExtension.php:49`).
7. Neither `.sonata-ba-field-inline-table` nor `ul.js-treeview` is emitted by any template in Sonata 4.43, the ORM bundle, the Mongo fork or form-extensions (grep over `S/src/Resources/views`, `vendor-extract/`, MDB `src/` and `vendor/sonata-project`): they are hooks for third-party bundles (SonataPage/Classification tree, Sonata-3-era inline tables) and only reach adminata through `Admin.shared_setup(subject)` (`S/assets/js/admin.js:26-27`). They stay as facade-tagged controllers (§2) but need no Twig call sites.

---

## 1. Controller registry

### 1.1 Conventions

| Rule | Detail |
|---|---|
| Identifier | `sonata-<name>`; file `assets/js/controllers/<name>_controller.js`; explicit registration in `assets/js/registry.js` (no `require.context`, no `@symfony/stimulus-bridge`, no `@hotwired/stimulus-webpack-helpers`; `S/assets/js/stimulus.js:11-29` is deleted). |
| Attribute schema | Stimulus defaults (`STIM:2076-2084`): `data-controller`, `data-action`, `data-<id>-target`, `data-<id>-<name>-value`, `data-<id>-<name>-class`, `data-<id>-<outlet>-outlet`, `data-<id>-<name>-param`. Twig always uses `stimulus_controller()/stimulus_target()/stimulus_action()` from `symfony/stimulus-bundle` (already a Composer requirement, `S/composer.json`), whose outlet syntax is the 4th argument (`S/src/Resources/views/CRUD/base_list.html.twig:260,300`; renderer `APP/vendor/symfony/stimulus-bundle/src/Dto/StimulusAttributes.php:62-65`). |
| Events | Dispatched with `this.dispatch(name, {detail})` ⇒ `sonata-<name>:<event>`, bubbling, cancelable (`STIM:2546-2551`). Legacy names (`sonata-admin-append-form-element`, …) are dispatched with `prefix: ''` exactly as `collection_controller.js:34-39` does today. Every controller declares `static events = [...]` (adminata convention, introspected by the contract test §5.4). |
| Value types | Stimulus value types `String | Number | Boolean | Array | Object`; defaults noted per controller. |
| `hidden` attribute, not `.hide` | All show/hide toggling uses the `hidden` attribute (already the convention of `filter_controller.js:24,30,81,88-89`, `edit_controller.js:53,55`); `[hidden]{display:none!important}` is kept from `S/assets/scss/styles.scss:572-574` (Tailwind preflight has the same rule). |
| Legacy class hooks | Class names that user CSS/JS/tests select on are kept as inert markers (`active`, `stuck`, `truncated`, `expanded`, `sonata-ba-list-row-selected`, `dropdown-menu`, `modal-body`, …); controllers toggle **both** the legacy class and the TailAdmin class where the two differ (e.g. `<li class="active">` + `aria-selected="true"` on tabs). |
| No jQuery, no Alpine | ESLint `no-restricted-globals: ['$', 'jQuery', 'Alpine']` in `assets/js/**` except `assets/js/vendor/jquery-entry.js` and `assets/js/compat/jquery-bridge.js` (§3.3). |
| Third-party libs | `tom-select` (Apache-2.0), `sortablejs` (MIT), `flatpickr` (MIT), `qs` (BSD-3). No Floating UI in 1.0 (§1.3.19). |

### 1.2 Kept controllers (9) — verbatim except one line

| Identifier | File (`S/assets/js/controllers/`) | Targets | Values / classes / outlets | Actions in Twig (`S/src/Resources/views/`) | Dispatched events | Change in adminata |
|---|---|---|---|---|---|---|
| `sonata-collection` | `collection_controller.js` | `item` | value `numItems:Number` | `stimulus_controller('sonata-collection')` merged into widget attrs (`Form/form_admin_fields.html.twig:401`), `stimulus_target('sonata-collection','item')` (`:374`), `stimulus_action('sonata-collection','delete','click')` (`:378`), `…'add','click'` (`:410`) | `sonata-admin-append-form-element` `{item}`, `sonata-collection-item-added` `{item}`, `sonata-collection-item-deleted` `{item}`, `sonata-collection-item-deleted-successful` (all `prefix:''`, bubbling; `:34-65`) | **None.** Reads `data-prototype`/`data-prototype-name` (`:82-88`), re-activates `<script>`s (`:26-30`). |
| `sonata-confirm-exit` | `confirm_exit_controller.js` | — | values `snapshot:String`, `skip:Boolean`; `static get shouldLoad(){return Config.param('CONFIRM_EXIT')}` (`:20-22`) | `CRUD/base_edit_form.html.twig:19,23-24` (`confirm` on `beforeunload@window`, `skip` on `submit`) | — | **None** (but see §0 fact 4: `Config.param` must tolerate a missing meta tag). |
| `sonata-edit` | `edit_controller.js` | `tab`, `tabStore` | — | `base_edit_form.html.twig:20,25-26,51,85` (`prepareSubmit` on `submit`, `checkValidity:capture` on `invalid`, `changeTab` on `click`; targets `tab` on `a.changer-tab`, `tabStore` on `input[name=_tab]`) | — | **One line.** `:49` `jQuery(tab).tab('show')` → `tab.dispatchEvent(new CustomEvent('sonata-tabs:show', { bubbles: true, cancelable: true }))`. `sonata-tabs` (§1.3.5) handles it. `:78` (`tab.parentElement.classList.contains('active')`) stays valid because `sonata-tabs` keeps `.active` on the `<li>`. |
| `sonata-filter` | `filter_controller.js` | `form`, `group`, `advanced`, `submitter` | value `defaultValues:Object`; outlet `sonata-filter-list` | `CRUD/base_list.html.twig:300` (controller + outlet `#filter-list-{uniqid}`), `:308-309` (`form` target, `prepareSubmit` on `submit`), `:319` (`group`), `:330` (`advanced`), `:341` (`hideFilter` with `id` param), `:362` (`submitter`), `:373` (`toggleAdvanced`) | — | **None.** Public methods `toggleFilter(id, state)` (`:85-91`) and the `qs` dependency (`:10,34`) kept. |
| `sonata-filter-list` | `filter_list_controller.js` | `counter`, `field` | class `active`; outlet `sonata-filter` | `base_list.html.twig:260` (controller, class map `{active:'active'}`, outlet `#filter-container-{uniqid}`), `:266` (`counter`), `:275` (`field` + `toggle:prevent:stop` on `click`) | — | **None.** Public method `disable(id)` (`:25-31`). The wrapping `<li class="dropdown">` gains `sonata-dropdown` (§1.3.3); the two controllers coexist on different elements. |
| `sonata-per-page` | `per_page_controller.js` | — | — | `Pager/base_results.html.twig:24` (`reload` on `change`) | — | **None.** The select2 hack `S/assets/js/admin.js:386-390` is deleted; `sonata-select` re-dispatches a native `change` (§1.3.12) so this still fires when `use_select2` is on. |
| `sonata-readmore` | `readmore_controller.js` | `content`, `button` | values `collapsedHeight:Number`, `moreText:String`, `lessText:String` | `CRUD/base_list_field.html.twig:25-34`, `CRUD/base_show_field.html.twig:27-40` | — | **None.** Markup pinned by `S/tests/Twig/RenderElementRuntimeTest.php:1396-1427`. |
| `sonata-revision` | `revision_controller.js` | `preview` | — | `CRUD/base_history.html.twig:19,39,44,54` (`showPreview:prevent:stop` on `click`) | — | **None** (already `fetch` + `X-Requested-With`, `:21-33`). |
| `sonata-sticky` | `sticky_controller.js` | `topNavbar`, `navbar`, `action` | `static get shouldLoad(){return Config.param('USE_STICKYFORMS')}` (`:17-19`) | `standard_layout.html.twig:99` (on `<body>`), `:125` (`topNavbar`), `:232` (`navbar`), `base_edit_form.html.twig:100` (`action`) | — | **None** — window-scroll model (C10) keeps its viewport-relative `IntersectionObserver` valid (`:65-70,86-91`). CSS for `.stuck`/`.navbar-sentinel`/`.action-sentinel` is rewritten (G2). |

### 1.3 New controllers

Summary table first; detail cards follow. "Legacy" cites what each replaces.

| # | Identifier | Mounts on | Replaces (legacy) |
|---|---|---|---|
| 1 | `sonata-layout` | `<body>` | AdminLTE push-menu (`standard_layout.html.twig:126` `data-toggle="push-menu"`), `S/assets/js/sidebar.js:10-20` cookie toggle, TailAdmin `sidebarToggle`/`menuToggle` Alpine state (`T/partials/header.html:2,13-15,82-83,143`, `T/partials/sidebar.html:2`, `T/partials/overlay.html`) |
| 2 | `sonata-menu` | `<nav>` root of the KnpMenu sidebar | AdminLTE `data-widget="tree"` (`Menu/sonata_menu.html.twig:4`), TailAdmin `x-data="{selected: $persist('Dashboard')}"` (`T/partials/sidebar.html:34,68-70,117`) |
| 3 | `sonata-dropdown` | any `li.dropdown` / `div.btn-group` / `div.relative` wrapper | Bootstrap `data-toggle="dropdown"` (`standard_layout.html.twig:156,168,270`, `base_list.html.twig:211,263`, `tree.html.twig:53`, `dashboard__action_create.html.twig:11`, `Core/tab_menu_template.html.twig:111`), TailAdmin `x-data="{dropdownOpen:false}" @click.outside` (`T/partials/header.html:186-191,607-613`) |
| 4 | `sonata-modal` | `<dialog id="field_dialog_{id}" class="modal">` | Bootstrap `.modal()`/`.modal('hide')`/`data-dismiss="modal"` (`edit_many_script.html.twig:75,143,185,227,303`; `edit_modal.html.twig:12-23`; `form_admin_fields.html.twig:676-687`), `Admin.setup_list_modal` inline styles (`admin.js:39-54`), TailAdmin modal (`TR/components/ui/modal/index.tsx:57-90`) |
| 5 | `sonata-tabs` | `.nav-tabs-custom` wrapper | Bootstrap `data-toggle="tab"` (`base_edit_form.html.twig:51`, `base_show.html.twig:49`, `edit_one_to_many_inline_tabs.html.twig:20`), `jQuery(tab).tab('show')` (`edit_controller.js:49`) |
| 6 | `sonata-theme` | the dark-mode button (header) | TailAdmin `darkMode` Alpine state + `localStorage` (`T/index.html:14-19`, `T/partials/header.html:150`), React `ThemeContext` (`TR/context/ThemeContext.tsx:21-43`) |
| 7 | `sonata-dismiss` | `.alert` / any dismissable box | Bootstrap `data-dismiss="alert"` (`Helper/render_form_dismissable_errors.html.twig:3`, `edit_many_script.html.twig:366,392`) |
| 8 | `sonata-search-shortcut` | header search `<form role="search">` | `T/js/index.js:91-118` (⌘/Ctrl+K and `/`) |
| 9 | `sonata-batch` | list `<form>` (or table) | inline script `base_list.html.twig:152-181` (select-all, row class) + `Admin.setup_checkbox_range_selection` (`admin.js:141-189`, incl. the `indexedDB` bug at `:173`) |
| 10 | `sonata-editable` | `span.x-editable` | x-editable + `Admin.setup_xeditable` (`admin.js:191-212`) |
| 11 | `sonata-sortable` | `div#field_container_{id}` | jQuery-UI sortable scripts (`edit_one_to_many_sortable_script_table.html.twig:12-40`, `…_tabs.html.twig:12-44`) |
| 12 | `sonata-select` | `<select>` | select2 + `Admin.setup_select2` (`admin.js:58-110`), per-page hack (`admin.js:386-390`) |
| 13 | `sonata-autocomplete` | `div#{id}_autocomplete` wrapper | select2 ajax script `Form/Type/sonata_type_model_autocomplete.html.twig:62-264` |
| 14 | `sonata-association` | `div#field_container_{id}.field-container` | `edit_many_script.html.twig:25-602`, `edit_one_script.html.twig:23-99`, `document.on('sonata-admin-append-form-element')` re-setup (`admin.js:381-384`) |
| 15 | `sonata-choice-field-mask` | wrapper around `block('choice_widget')` | inline script `form_admin_fields.html.twig:465-535` |
| 16 | `sonata-inline-row` | `.sonata-ba-field-inline-table` | `Admin.setup_inline_form_errors` / `switch_inline_form_errors` (`admin.js:231-260`) |
| 17 | `sonata-treeview` | `ul.js-treeview` | `S/assets/js/treeview.js` jQuery plugin + `Admin.setup_tree_view` (`admin.js:262-266`) |
| 18 | `sonata-datepicker` | `div#{id}_controller.input-group.date` (adminata's override of `FE/src/Bridge/Symfony/Resources/views/Form/datepicker.html.twig:12-38`) | form-extensions `datepicker` (Tempus Dominus, `FE/assets/js/controllers/datepicker_controller.js`) — not registered by adminata |
| 19 | *(none)* tooltip | — | Not shipped in 1.0: Sonata templates never use `data-toggle="tooltip"`; `title` attributes stay native; `select.data('popover')` (`admin.js:65,105-107`) is undocumented and dropped. Revisit with Floating UI post-1.0. |
| — | `sonata-compat` (not a controller) | document-level delegate | Bootstrap 3 data-API for overridden user templates (§1.5) |

Sortable-select2 (`Admin.setup_sortable_select2`, `form_admin_fields.html.twig:538-549`) is implemented inside `sonata-select` (multi + `drag_drop`) and exposed through the facade (§2, member 14) — no separate controller.

#### 1.3.1 `sonata-layout`

| Aspect | Specification |
|---|---|
| Mount | `<body … {{ stimulus_controller('sonata-layout', {collapsed: sidebar_hidden, breakpoint: 1024}) }} {{ stimulus_controller('sonata-sticky') }}>` inside `body_attributes` (`standard_layout.html.twig:92-100`); `sidebar_hidden` = `app.request.cookies.get('sonata_sidebar_hide')` truthiness (`:96-98`). The server keeps emitting `sidebar-collapse` on `<body>` for first paint; the controller reads the value, not the class. |
| Targets | `sidebar` (the `<aside class="sidebar main-sidebar …">`), `overlay` (`T/partials/overlay.html` port), `toggle` (hamburger `<button class="sidebar-toggle">`, keeps class + `title`/`aria-label` `toggle_navigation`), `headerMenu` (right cluster `div`, TailAdmin `menuToggle` panel `T/partials/header.html:143`), `headerMenuToggle` (mobile "…" button `:82-83`), `collapseOnly` (any element that should get `lg:hidden` when collapsed: logo text, `menu-item-text`, arrows — `T/partials/sidebar.html:11,40,90,97,120`). |
| Values | `collapsed:Boolean` (default `false`; desktop 90 px rail), `mobileOpen:Boolean` (`false`), `headerMenuOpen:Boolean` (`false`), `breakpoint:Number` (`1024`, Tailwind `lg`), `cookieName:String` (`'sonata_sidebar_hide'`), `cookiePath:String` (`'/'`). |
| Classes (`data-sonata-layout-*-class`) | `collapsed` → `lg:w-[90px]` on `sidebar` + `lg:ml-[90px]` on content column (React model `TR/layout/AppLayout.tsx:16-19`), `expanded` → `lg:w-[290px]`/`lg:ml-[290px]`, `mobileOpen` → `translate-x-0` (else `-translate-x-full`, `T/partials/sidebar.html:2`), `overlayVisible` → `block lg:hidden` (else `hidden`, `T/partials/overlay.html:3`), `headerMenuOpen` → `flex` (else `hidden`, `T/partials/header.html:143`), `toggleActive` → `bg-gray-100 dark:bg-gray-800` (`:13`). Legacy body classes `sidebar-collapse` (desktop collapsed) and `sidebar-open` (AdminLTE mobile) are toggled too. |
| Actions | `click->sonata-layout#toggleSidebar` on `toggle` (`@click.stop="sidebarToggle = !sidebarToggle"`, `header.html:15`); `click->sonata-layout#closeMobile` on `overlay` (`overlay.html:2`); `click->sonata-layout#toggleHeaderMenu` on `headerMenuToggle` (`header.html:83`); `keydown.esc@window->sonata-layout#closeMobile`; `resize@window->sonata-layout#syncBreakpoint` (React `SidebarContext.tsx:36-51` closes mobile drawer when crossing the breakpoint). |
| Behaviour | `toggleSidebar()`: if `innerWidth >= breakpoint` flip `collapsedValue` else flip `mobileOpenValue` (TailAdmin's single `sidebarToggle` is desktop-collapse *and* mobile-open at once, `R/layout-nav.md` §2 row 18; splitting it is what the React version does, `TR/context/SidebarContext.tsx:53-59`). `collapsedValueChanged()` writes cookie `sonata_sidebar_hide=1|0;path=/` (exact string of `sidebar.js:13,18`, plus `SameSite=Lax;max-age=31536000` — G6 decides `__Host-`), toggles classes, sets `aria-expanded` on `toggle`. `mobileOpenValueChanged()` toggles `overlayVisible`, sets `inert` on the content column while open, restores focus to `toggle` on close. Collapsed-rail hover expansion stays pure CSS (`T/css/style.css` `.sidebar:hover` rules). |
| Events | `sonata-layout:sidebar-changed` `{collapsed, mobileOpen}` (charts/maps that need a resize listen here), `sonata-layout:header-menu-changed` `{open}`. |
| Twig call sites | `standard_layout.html.twig` blocks `body_attributes`, `sonata_nav` (hamburger), `sonata_left_side` (`sidebar` target), new `sonata_overlay` block (inside `sonata_wrapper`), `sonata_top_nav_menu` (`headerMenu`, `headerMenuToggle`), `logo` (`collapseOnly`). `empty_layout.html.twig` blanks `sonata_left_side`/`sonata_nav` — the controller must tolerate missing targets (`hasSidebarTarget` guards). |

#### 1.3.2 `sonata-menu`

| Aspect | Specification |
|---|---|
| Mount | `Menu/sonata_menu.html.twig` block `root`: `<nav class="sonata-sidebar-nav" {{ stimulus_controller('sonata-menu', {storageKey: 'sonata_sidebar_open'}) }}><ul class="sidebar-menu …">` (keep `sidebar-menu`: `S/tests/Functional/Controller/MenuTest.php:41` selects `.sidebar-menu .dynamic-menu a`). `item.extra('request')` line kept (`sonata_menu.html.twig:5`). |
| Targets | `group` (each `li.treeview`, attr `data-sonata-menu-key="{{ item.name }}"`, `data-sonata-menu-keep-open="1"` when `item.extra('keep_open')`, `data-sonata-menu-active="1"` when the KnpMenu matcher marked it ancestor/current — `options.ancestorClass/currentClass = 'active'` kept, `:16`), `toggle` (the `<button type="button" class="menu-item group w-full">` replacing `<a href="#">` in `spanElement`, `:35-46`), `submenu` (the `div.overflow-hidden` wrapper around `ul.treeview-menu.menu-dropdown`, `T/partials/sidebar.html:115-122`), `arrow` (chevron `<svg class="menu-item-arrow">`, `:95-111`; omitted for `keep_open`, `sonata_menu.html.twig:42-44`). |
| Values | `storageKey:String` (`'sonata_sidebar_open'`), `multiple:Boolean` (`true` — Sonata allows several open groups, unlike TailAdmin's single `selected`; `R/layout-nav.md` §3.4 row 2). |
| Classes | `open` → `menu-item-active` on `toggle` + `menu-item-arrow-active` (`rotate-180`) on `arrow` + `block` on `submenu`; `closed` → `menu-item-inactive`, `menu-item-arrow-inactive`, `hidden` (`T/partials/sidebar.html:70,97,117`). Legacy `li.menu-open` (AdminLTE) toggled as marker. |
| Actions | `click->sonata-menu#toggle` on `toggle` with `data-sonata-menu-key-param`; `keydown.esc->sonata-menu#closeAll` optional. |
| Behaviour | `connect()`: state = `JSON.parse(localStorage[storageKey] ?? '{}')` (try/catch, `localStorage` may throw); for each `group`: `open = keepOpen || (key in state ? state[key] : active)` — i.e. server-side `active` seeds the default like AdminLTE auto-opening the active treeview, user toggles persist. `toggle(key)`: flip, persist, `aria-expanded` on `toggle`, `aria-controls` → `submenu` id. Never persists `keep_open` groups. |
| Events | `sonata-menu:toggled` `{key, open}`. |
| Twig call sites | `Menu/sonata_menu.html.twig` blocks `root`, `item`, `spanElement`, new `arrowElement`/`iconElement`/`children` blocks (`R/layout-nav.md` §3.4 skeleton, with `x-data`/`:class`/`@click` replaced by the attributes above). |

#### 1.3.3 `sonata-dropdown`

| Aspect | Specification |
|---|---|
| Mount | The positioned wrapper (`li.dropdown`, `div.btn-group`, `li.relative`): `{{ stimulus_controller('sonata-dropdown') }}`. |
| Targets | `toggle` (the trigger `<a class="dropdown-toggle">`/`<button>`; keeps `dropdown-toggle` class — the React dropdown also special-cases `.dropdown-toggle` for outside-click, `TR/components/ui/dropdown/Dropdown.tsx:24`), `menu` (the panel; keeps `dropdown-menu` class + `role="menu"`). |
| Values | `open:Boolean` (`false`), `closeOnSelect:Boolean` (`true`), `align:String` (`'end'`; `absolute right-0` / `left-0`). |
| Classes | `open` (added to root, e.g. `open` — Bootstrap 3 also used `.open` on the parent so legacy CSS keeps working), `toggleOpen` (e.g. `rotate-180` on a chevron, `T/partials/header.html:622`). The menu is shown with `hidden` attribute removal (TailAdmin `x-show`, `:220,642`). |
| Actions | `click->sonata-dropdown#toggle:prevent` on `toggle`; `click@window->sonata-dropdown#closeOutside` (`@click.outside`, `:187`); `keydown.esc@window->sonata-dropdown#close`; `focusout->sonata-dropdown#closeIfFocusLeft`; `click->sonata-dropdown#itemSelected` on `menu` (closes when `closeOnSelect` and the click target is `a, button`). Arrow-key roving focus among `[role=menuitem], a, button` inside `menu`. |
| Behaviour | `openValueChanged`: `menu.hidden = !open`, `toggle.setAttribute('aria-expanded', open)`, `aria-haspopup="true"` set on connect. Only one dropdown open at a time: on open, dispatch `sonata-dropdown:opened` on `document`; other instances listen (`sonata-dropdown:opened@document->sonata-dropdown#closeOther`) and close unless it is themselves. |
| Events | `sonata-dropdown:opened`, `sonata-dropdown:closed` (`{}`; bubbling). |
| Twig call sites (replacing `data-toggle="dropdown"`) | `standard_layout.html.twig:155-160` (add block), `:167-174` (user block), `:269-274` (`li.dropdown.sonata-actions`); `CRUD/base_list.html.twig:207-236` (export `btn-group`), `:262-290` (`li.dropdown.sonata-actions` filter list — the `<ul id="filter-list-…">` keeps `sonata-filter-list`, the inner `<li>` gets `sonata-dropdown`); `CRUD/tree.html.twig:52-70`; `CRUD/dashboard__action_create.html.twig:11-32`; `Core/tab_menu_template.html.twig:105-119` (`dropdownElement`: replace the `data-toggle` merge at `:111` with `stimulus_target('sonata-dropdown','toggle').toArray()` merged into `attributes`, and put the controller on the `<li>` in the `item` block); `Core/add_block.html.twig` (panel is the `menu` target). |

#### 1.3.4 `sonata-modal`

| Aspect | Specification |
|---|---|
| Mount | `CRUD/Association/edit_modal.html.twig` and the inline copy `form_admin_fields.html.twig:676-687` become one include: `<dialog id="field_dialog_{{ id }}" class="modal sonata-modal" aria-labelledby="field_dialog_{{ id }}_title" {{ stimulus_controller('sonata-modal') }}><div class="modal-dialog modal-lg"><div class="modal-content …"><div class="modal-header …"><h4 class="modal-title" id="…_title" {{ stimulus_target('sonata-modal','title') }}></h4><button type="button" class="close" data-dismiss="modal" aria-label="{{ 'close'|trans }}" {{ stimulus_action('sonata-modal','close','click') }}>…</button></div><div class="modal-body" {{ stimulus_target('sonata-modal','body') }}></div></div></div></dialog>`. Classes `modal`, `modal-dialog`, `modal-lg`, `modal-content`, `modal-header`, `close`, `modal-title`, `modal-body` are the DOM contract (MDB test `tests/Functional/ReferenceMappingTest.php:61` uses `.modal-content button[name="btn_create"]`; `edit_many_script.html.twig:447-448` selects `.modal-body`, `.modal-title`). Visuals: TailAdmin modal panel (`TR/components/ui/modal/index.tsx:54-72`: `relative w-full rounded-3xl bg-white dark:bg-gray-900`, close button `absolute right-3 top-3 … rounded-full bg-gray-100`), backdrop via `dialog::backdrop { @apply bg-gray-400/50 backdrop-blur-[32px] }` (`:60`). |
| Targets | `title`, `body`, `dialogBox` (`.modal-dialog`, for size classes). |
| Values | `moveToBody:Boolean` (`true` — the nested-form reason in `edit_many_script.html.twig:450-451` still applies to `<dialog>` content injected inside an outer `<form>`), `closeOnBackdrop:Boolean` (`true`), `size:String` (`'lg'`; `'list'` = 90 vw / 85 vh, the `Admin.setup_list_modal` geometry from `admin.js:39-54`). |
| Classes | `sizeLg` (`max-w-[700px]`), `sizeList` (`w-[90vw] max-w-none h-[85vh]`), `bodyScroll` (`custom-scrollbar overflow-y-auto`). |
| Actions | `close` (click on `.close`/`[data-dismiss="modal"]`), `click->sonata-modal#backdropClick` on the `<dialog>` itself (closes when `event.target === this.element`, i.e. the backdrop), native `cancel` (Esc) → `close`, `close@self` → `closed` event. |
| Public methods (used by `sonata-association` and the facade) | `open()` → `showModal()` (+ move to `document.body` once when `moveToBody`); `close()`; `setTitle(text)`; `setContent(html)` → `body.innerHTML = html` then `activateScriptElement` on every `<script>` (jQuery `.html()` executed scripts; `innerHTML` does not — `S/assets/js/core/utils.js:17-27` exists for this) then dispatch `sonata-modal:content-loaded`; `setSize('list')`. |
| Events | `sonata-modal:opened`, `sonata-modal:closed`, `sonata-modal:content-loaded` `{body}`; legacy `sonata-admin-setup-list-modal` (`prefix:''`) is dispatched by `Admin.setup_list_modal` (§2). |
| Cascaded modals | Each nested field container in injected HTML brings its own `<dialog>`; `<dialog>` top layer stacks in `showModal()` order, so no z-index ladder is needed (C23). `dropdownParent` for Tom Select inside a dialog must be `null` (render inline) — `'body'` would render under the top layer (§4.3). |

#### 1.3.5 `sonata-tabs`

| Aspect | Specification |
|---|---|
| Mount | `div.nav-tabs-custom` (`base_edit_form.html.twig:46`, `base_show.html.twig:44`, `edit_one_to_many_inline_tabs.html.twig:14`) `{{ stimulus_controller('sonata-tabs') }}`; `ul.nav.nav-tabs[role=tablist]` inside, panes `div.tab-pane` with ids. |
| Targets | `tab` (`a.changer-tab[href="#id"][aria-controls]`, `role="tab"`), `pane` (`.tab-pane`, `role="tabpanel"`). Keep `data-toggle="tab"` out of adminata templates; `sonata-compat` (§1.5) maps it for overridden user templates. |
| Values | `initial:String` (optional pane id; default = the `<li class="active">` rendered by Twig from `?_tab=`, `base_edit_form.html.twig:45,50`). |
| Classes | `activeTab` (`border-brand-500 text-brand-500 dark:text-brand-400`), `inactiveTab` (`border-transparent text-gray-500 …`), `activePane` (`block`). Legacy: `li.active`, `pane.active.in` kept as markers (`edit_controller.js:78` reads `li.active`). |
| Actions | `click->sonata-tabs#select:prevent` on `tab` (in `base_edit_form` this coexists with `sonata-edit#changeTab`, `:51`); `keydown.left/right/home/end->sonata-tabs#move`; `sonata-tabs:show->sonata-tabs#showFromEvent` on the root (receives the event `sonata-edit` dispatches on a tab, §1.2). |
| Behaviour | `show(tab)`: for every tab set `aria-selected`, `tabindex` (0/-1), toggle `li.active` + class pair; for every pane `hidden = pane.id !== target` + `active in` marker; dispatch `sonata-tabs:shown`. No `pushState` — that stays in `sonata-edit#changeTab` (`edit_controller.js:60-74`). |
| Events | `sonata-tabs:shown` `{tab, pane}`, `sonata-tabs:show` (consumed). |

#### 1.3.6 `sonata-theme`

| Aspect | Specification |
|---|---|
| Mount | The header dark-mode `<button>` in a new block `sonata_top_nav_menu_theme_toggle` (`R/layout-nav.md` §4.5): `{{ stimulus_controller('sonata-theme', {theme: _theme, cookieName: 'sonata_theme'}) }} {{ stimulus_action('sonata-theme','toggle','click') }}` with TailAdmin's two SVGs (`T/partials/header.html:148-181`, `hidden dark:block` / `dark:hidden`). |
| Values | `theme:String` (`'light'|'dark'|'system'`, from `Config.param('THEME')` when the value is absent), `cookieName:String` (`'sonata_theme'`), `root:String` (`'html'`). |
| Behaviour | `toggle()`: `dark = !root.classList.contains('dark')`; `root.classList.toggle('dark', dark)`; cookie `sonata_theme=dark|light;path=/;max-age=31536000;SameSite=Lax` (`R/js-assets.md` §7.6, C9 — server stamps `<html class="dark">` on next request, no FOUC); `aria-pressed`. `connect()` with `theme === 'system'`: apply `matchMedia('(prefers-color-scheme: dark)')` and follow changes (the one pre-paint inline script for `system` mode is outside this controller, `R/js-assets.md` §7.6). |
| Events | `sonata-theme:changed` `{theme: 'dark'|'light'}` on `document` (bubbles from the button; charts re-render on it). |

#### 1.3.7 `sonata-dismiss`

| Aspect | Specification |
|---|---|
| Mount | `div.alert.alert-dismissable` (`Helper/render_form_dismissable_errors.html.twig:2`), flash messages (adminata's override of `@SonataTwig/FlashMessage/render.html.twig`, G7), and the alerts created by `sonata-association` for JSON errors (`edit_many_script.html.twig:365-368,391-394` markup, now built in JS with the same classes). |
| Actions | `click->sonata-dismiss#dismiss` on the `button.close` (keep `class="close"`; `data-dismiss="alert"` removed from adminata templates, mapped by `sonata-compat`). |
| Values | `remove:Boolean` (`true` → `element.remove()`; `false` → `hidden`). |
| Events | `sonata-dismiss:dismissed`. |

#### 1.3.8 `sonata-search-shortcut`

| Aspect | Specification |
|---|---|
| Mount | Header search `<form action="{{ path('sonata_admin_search') }}" method="GET" role="search" {{ stimulus_controller('sonata-search-shortcut') }}>` (new block `sonata_header_search`, `R/layout-nav.md` §2 row 20). |
| Targets | `input` (`#search-input`, `name="q"`), `hint` (`#search-button` "⌘ K" badge, `T/partials/header.html:126-131`). |
| Actions | `keydown@window->sonata-search-shortcut#handle`, `click->sonata-search-shortcut#focus` on `hint`. |
| Behaviour | `(meta|ctrl)+k` → focus + `preventDefault`; `/` → focus only when `document.activeElement` is not editable (`input, textarea, select, [contenteditable], .ts-control input` — fixes the TailAdmin bug noted in `R/tailadmin-catalog.md` §3.27; `T/js/index.js:112-116` only checks `!== searchInput`). Renders nothing when `sonata_config.getOption('search')` is false — the block is absent, not the controller. |
| Events | none. |

#### 1.3.9 `sonata-batch`

| Aspect | Specification |
|---|---|
| Mount | The list `<form>` in `base_list.html.twig` (the one wrapping the table + batch footer; `admin.hasRoute('batch') and batchactions|length > 0`, `:150`), `{{ stimulus_controller('sonata-batch', {}, {selected: 'sonata-ba-list-row-selected'}) }}`. `{% block batch %}` / `{% block batch_javascript %}` (`:151-181`) are kept, now rendering nothing by default (user overrides of `batch_javascript` still work). |
| Targets | `selectAll` (`#list_batch_checkbox`), `checkbox` (`td.sonata-ba-list-field-batch input[type=checkbox], div.sonata-ba-list-field-batch input[type=checkbox]` — mosaic cells included, `:159,170`). |
| Classes | `selected` (`sonata-ba-list-row-selected`, `:174`; Tailwind `bg-brand-25 dark:bg-brand-500/[0.08]` in `@layer components`). |
| Actions | `change->sonata-batch#toggleAll` on `selectAll`; `change->sonata-batch#rowChanged` and `click->sonata-batch#rangeSelect` on each `checkbox`. |
| Behaviour | `toggleAll`: set every `checkbox.checked = selectAll.checked` and dispatch `change` on each (so `rowChanged` runs, replacing `ifChanged`). `rowChanged`: `closest('tr, div.sonata-ba-list-field-batch').classList.toggle(selectedClass, checked)`; `connect()` applies it to the initial state (`.trigger('ifChanged')`, `:177`) and sets `selectAll.indeterminate` when partially selected. `rangeSelect`: same semantics as `admin.js:151-187` using `checkboxTargets` order, with the `:173` bug fixed (`index > currentIndex && index < previousIndex`); shift-click applies the clicked box's state to the range. |
| Events | `sonata-batch:changed` `{selected: string[]}` (values of `idx[]`), `sonata-batch:range` `{from, to, checked}`. |
| Facade | `Admin.setup_checkbox_range_selection(subject)` tags legacy tables (§2). |

#### 1.3.10 `sonata-editable`

| Aspect | Specification |
|---|---|
| Mount | `CRUD/base_list_field.html.twig:77-91` `field_span_attributes` block: `class="x-editable" {{ stimulus_controller('sonata-editable') }} {{ stimulus_action('sonata-editable','open','click') }} data-type=… data-value=… data-title=… data-format=… data-pk=… data-url=…` (all existing `data-*` names kept — user overrides append `data-source`, `list_boolean.html.twig`, `list_choice.html.twig`; the controller reads `this.element.dataset`, so overrides that know nothing about Stimulus still work). `RenderElementRuntimeTest.php:812-866,1023-1119,1445` pins are re-baselined (C16). |
| Values | none required; optional overrides `type/value/title/format/pk/url/source` mirror the dataset. |
| Targets | none (the popover is created on open and appended to `document.body`, mirroring `container:'body'`, `admin.js:196`). |
| Actions | `open` on click; inside the popover: `save` (Enter / OK), `cancel` (Esc / Cancel / outside click). |
| Behaviour | Editors by `data-type` (`text, number, email, url, textarea, select, checklist, date` — `S/src/Twig/XEditableRuntime.php:22-35` + `checklist` from `base_list_field.html.twig:46`); `data-source` parsed as JSON (`list_boolean` switches to JSON per `R/list-datagrid.md` §4.3). Submit: `fetch(data-url, {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, body: URLSearchParams({name, pk, value | value[]})})` — the x-editable payload `SetObjectFieldValueAction` expects (`S/src/Action/SetObjectFieldValueAction.php:68-83,130-149`). 200 → `td.replaceWith(createDocumentFragment(JSON.parse(text)))` (`admin.js:198-201`; Stimulus auto-connects the new cell); 4xx JSON string → error line under the input (`:203-210`). |
| Events | `sonata-editable:opened`, `sonata-editable:saved` `{td, html}`, `sonata-editable:error` `{status, message}`, `sonata-editable:cancelled`. |
| Facade | `Admin.setup_xeditable(subject)` tags `.x-editable` (§2). |

#### 1.3.11 `sonata-sortable`

| Aspect | Specification |
|---|---|
| Mount | `div#field_container_{id}` when `sonata_admin.field_description.option('sortable')` (`edit_one_to_many.html.twig:73-79`): `{{ stimulus_controller('sonata-sortable', {fieldId: id, positionField: sortable, mode: sonata_admin.inline == 'table' ? 'table' : 'tabs'}) }}`; the two `*_sortable_script_*.html.twig` templates become empty-by-default include shims (kept so user overrides/includes do not fatal). The controller shares the element with `sonata-association` (`data-controller="sonata-association sonata-sortable"`). |
| Values | `fieldId:String`, `positionField:String`, `mode:String` (`'table'|'tabs'`), `handle:String` (`'.sonata-ba-sortable-handler'`), `animation:Number` (`150`). |
| Classes | `ghost` (`opacity-60`, from `opacity: 0.6`, `…_table.html.twig:14`), `dragging` (`cursor-move`). |
| Actions | `sonata.add_element->sonata-sortable#refresh` and `sonata:add-element->sonata-sortable#refresh` (both names, §3.2) on the root; `sonata-collection-item-added->sonata-sortable#refresh`. |
| Behaviour | `Sortable.create(list, {handle, animation, ghostClass, onEnd: () => this.renumber()})` where `list` = `tbody.sonata-ba-tbody` (`:12`) or `.sonata-ba-tabs` (`…_tabs.html.twig:12`). `renumber()` keeps the exact legacy DOM: in each `td.sonata-ba-td-{id}-{positionField}` (table) / `.sonata-ba-field-{id}-{positionField}` (tabs) remove and re-append `span.sonata-ba-sortable-handler` (table, `:23-24`) or `li.sonata-ba-sortable-handler.pull-right` in the row's `ul.nav-tabs` (`…_tabs:23-28`) with an inline grip SVG (was `i.fas.fa-grip-lines`), hide the position `input` (`hidden`), write `index+1` (`:28-30`). `refresh()` = `renumber()` (SortableJS needs no `refresh`). |
| Events | `sonata-sortable:reordered` `{order: string[]}`. |

#### 1.3.12 `sonata-select`

| Aspect | Specification |
|---|---|
| Mount | Added by the form theme to `<select>` widgets when `use_select2` is on: `form_admin_fields.html.twig:212-214` (`choice_widget_collapsed`) → `<select {{ block('widget_attributes') }} … {% if use_select2 and attr['data-sonata-select2']|default('true') != 'false' %}{{ stimulus_controller('sonata-select') }}{% endif %}>`; `filter_admin_fields.html.twig` likewise for filter value selects (operator selects stay native — `R/list-datagrid.md` §7.3). Also tagged at runtime by `Admin.setup_select2(subject)` for legacy markup (§2). |
| Values (all optional; dataset attributes are the contract, `S/docs/cookbook/recipe_select2.rst:31-133`) | `allowClear:Boolean`, `allowTags:Boolean`, `maxItems:Number`, `minimumResultsForSearch:Number` (`10`, `admin.js:67`), `placeholder:String`, `sortable:Boolean` (`false`), `options:Object` (raw Tom Select overrides). Precedence: value attribute → `data-sonata-select2-*` attribute → derived defaults. |
| Derived config (`admin.js:62-103` mapping) | `allowClear` = `option[value=""]` exists **or** non-empty `data-placeholder` **or** `data-sonata-select2-allow-clear="true"`, unless `="false"` → plugin `clear_button` + `allowEmptyOption:true`; `data-sonata-select2-allow-tags="true"` → `create:true`; `data-sonata-select2-maximumSelectionLength` → `maxItems`; option count `< minimumResultsForSearch` → `controlInput:null` (no search box); `data-placeholder` → `placeholder`; `multiple` → plugin `remove_button`; `sortable` → plugin `drag_drop`; `dropdownParent: this.element.closest('dialog') ? null : 'body'`; strings from `Translation.trans('SELECT_*')` (§3.4). Width: Tom Select is block-level (`.ts-wrapper` 100 %); `Admin.get_select2_width()` result applied as inline `width` on `.ts-wrapper` only when the select had an explicit `style="width:…"` (parity with `admin.js:269-291`). |
| Skip rules (§4.3) | Do not initialise when `data-sonata-select2="false"`, when `this.element.tomselect` is already set (Tom Select sets `input.tomselect`), when the `data-controller` attribute contains `autocomplete` (covers `symfony--ux-autocomplete--autocomplete` and `sonata-autocomplete`), or when `Config.param('USE_SELECT2')` is falsy (the theme never adds the controller in that case; the guard protects facade-tagged legacy markup). |
| Behaviour | `connect()` → `pre-connect` event (users can mutate `detail.options`, the ux-autocomplete pattern) → `new TomSelect(el, options)` → `connect` event. `tomSelect.on('change')` → if no native `change` was just dispatched by Tom Select, `el.dispatchEvent(new Event('change', {bubbles:true}))` guarded by a re-entrancy flag, so `sonata-per-page#reload` and user listeners keep working (replaces `admin.js:386-390`). `disconnect()` → `tomSelect.destroy()` (Turbo/AJAX safe). |
| Events | `sonata-select:pre-connect` `{options}`, `sonata-select:connect` `{tomSelect}`, `sonata-select:disconnect`. |
| Sortable multi (`sonata_type_choice_multiple_sortable`, `form_admin_fields.html.twig:538-549`) | Implemented by `Admin.setup_sortable_select2(el, choices, options)` (§2 member 14) which mounts Tom Select on the hidden `input#{id}` (comma-delimited value, `delimiter:','`, `options: choices.map(c => ({value:c.data, text:c.label}))`, `items` in stored order, plugins `remove_button, drag_drop`) and keeps the submit expansion into `name[0..n]` hidden inputs + removal of the original (`admin.js:343-361`). The `sonata_type_model_autocomplete_select2_options_js` Twig block (`:545`) keeps its meaning: JS that mutates `options` before the call; document that the object is now a Tom Select config. **To verify during implementation:** Tom Select on `<input type="hidden">` (Selectize supported it; if not, the shim flips `type` to `text` and hides it). |

#### 1.3.13 `sonata-autocomplete`

| Aspect | Specification |
|---|---|
| Mount | `Form/Type/sonata_type_model_autocomplete.html.twig`: wrap lines `11-60` in `<div id="{{ id }}_autocomplete" class="sonata-autocomplete" {{ stimulus_controller('sonata-autocomplete', {...}) }}>`; inside: `<select id="{{ id }}_autocomplete_input" data-sonata-select2="false" … {{ stimulus_target('sonata-autocomplete','select') }}>` (id kept — `ChoiceFieldMaskType` falls back to it, `form_admin_fields.html.twig:492-494`), `<div id="{{ id }}_hidden_inputs_wrap" {{ stimulus_target('sonata-autocomplete','hiddenInputs') }}>` (kept, `:21-29`), `div#field_actions_{{ id }}` with the add button + `edit_modal` + `edit_many_script` includes (`:31-60`; the add button gets `data-action="click->sonata-association#openAdd"` and the field container semantics of §1.3.14 — for autocomplete the association controller is mounted on the same wrapper `div` with `mode: 'autocomplete'`). |
| Values (from the select2 config table, `R/forms-edit.md` §6.2; template `:67-162`) | `url:String` (`url ?: path(route.name, route.parameters)`), `fieldName:String` (`name`, or the un-wrapped filter name + `context:'filter'`, `:115-120`), `context:String`, `adminCode:String` (`sonata_admin.admin.baseCodeRoute` or `admin_code`), `uniqid:String`, `reqParams:Object`, `paramNames:Object` (`{search: req_param_name_search, perPage: req_param_name_items_per_page, page: req_param_name_page_number}`), `itemsPerPage:Number`, `minimumInputLength:Number`, `delay:Number` (the `quiet_millis` fallback `:85` resolved in Twig), `cache:Boolean`, `multiple:Boolean`, `required:Boolean`, `disabled:Boolean`, `placeholder:String`, `safeLabel:Boolean`, `dropdownItemCssClass:String`, `containerCssClass:String`, `dropdownCssClass:String`, `dropdownAutoWidth:Boolean`, `fullName:String`, `createUrl:String` (for auto-select after modal create, `:225-235`). |
| Targets | `select`, `hiddenInputs`. |
| Twig blocks kept | `sonata_type_model_autocomplete_ajax_request_parameters` (`:96-130`), `sonata_type_model_autocomplete_dropdown_item_format` (`:140-146`), `sonata_type_model_autocomplete_selection_format` (`:154-160`). They are JS snippets by contract, so they are rendered — only when a child template overrides them (`block('…') != default`) — into a small inline `<script>` that assigns `window.adminata.autocompleteHooks['{{ id }}'] = { requestParameters(params){…}, renderOption(item, escape){…}, renderItem(item, escape){…} }`, which the controller consults in `connect()`. Default (un-overridden) blocks emit no script. Escaping stays `{% autoescape 'js' %}` (`:63`). |
| Behaviour | Tom Select config: `valueField:'id'`, `labelField:'label'`, `searchField:[]`, `maxItems: multiple ? null : 1`, `plugins: multiple ? ['remove_button','virtual_scroll'] : (required ? ['virtual_scroll'] : ['clear_button','virtual_scroll'])`, `shouldLoad: q => q.length >= minimumInputLength`, `loadThrottle: delay`, `firstUrl: q => url + '?' + params(q, 1)`, `load(q, cb)` → `fetch(this.getUrl(q))` → `{status, more, items}` (`RetrieveAutocompleteItemsAction`, `R/forms-edit.md` §5.2) → `setNextUrl(q, more ? params(q, page+1) : null)`; `cb(items)`; 403 `{status:'KO'}` → `cb()`; `render.option/item` = `safeLabel ? item.label : escape(item.label)` in `div.{dropdownItemCssClass}` (`:140-160`); `dropdownParent: closest('dialog') ? null : 'body'`; `placeholder` incl. the single-not-required `' '` trick (`:68-69`) is unnecessary with `clear_button` — omit. Sync hidden inputs on `item_add`/`item_remove` (`:166-215`): multiple → add/remove `input[type=hidden][name=fullName[]]`; single → set the single hidden input value. `submit` on the enclosing form → remove the visible select before submission (`:218-222`) — done in a `submit` listener registered on `form` (capture phase not needed). Disabled → `tomSelect.disable()`. |
| Auto-select after modal create (`:224-261`, previously `$(document).ajaxSuccess`) | Listen `sonata-association:created@document->sonata-autocomplete#addCreated`; act when `event.detail.fieldId === this.element.dataset.fieldId` (or `createUrl` matches `event.detail.createUrl`): `tomSelect.addOption({id, label: textContent-of-parsed(objectName)})`, `addItem(id)`, hidden input sync. `objectName` is decoded through `DOMParser` exactly as `:237`. |
| Events | `sonata-autocomplete:pre-connect` `{options}`, `sonata-autocomplete:connect` `{tomSelect}`, `sonata-autocomplete:changed` `{value}`. |

#### 1.3.14 `sonata-association`

| Aspect | Specification |
|---|---|
| Mount | `div#field_container_{{ id }}.field-container` in `form_admin_fields.html.twig:553` (`sonata_type_model_list_widget`), `CRUD/Association/edit_many_to_one.html.twig:19`, `edit_one_to_one.html.twig`, `edit_one_to_many.html.twig:17`, `edit_many_to_many.html.twig`, and the autocomplete wrapper (§1.3.13). `{{ stimulus_controller('sonata-association', {fieldId: id, mode: sonata_admin.edit, multiple: …, retrieveUrl: path('sonata_admin_retrieve_form_element', {...}), appendUrl: path('sonata_admin_append_form_element', {...}), shortObjectUrl: path('sonata_admin_short_object_information', {..., objectId: 'OBJECT_ID', ...}), editUrl: associationadmin.generateUrl('edit', {(idParameter): 'OBJECT_ID'}), title: associationadmin.label|trans(...), placeholderText: 'short_object_description_placeholder'|trans, loadingText: 'loading_information'|trans, loaderImage: asset('bundles/sonataadmin/images/ajax-loader.gif')}) }}`. The URL expressions are lifted verbatim from `edit_many_script.html.twig:317-328,573-583,591-593` and `edit_one_script.html.twig:35-46` (root admin code, `elementId`, `subclass`, `objectId`, `uniqid`, `_route_params`, `link_parameters`, `app.request.query.all`). |
| Values | `fieldId:String`, `mode:String` (`'list'|'standard'|'inline'|'autocomplete'|'admin'`), `multiple:Boolean`, `retrieveUrl:String`, `appendUrl:String`, `shortObjectUrl:String` (contains literal `OBJECT_ID`, `:575,583`), `editUrl:String` (`OBJECT_ID`, `:592-593`), `title:String`, `placeholderText:String`, `loadingText:String`, `loaderImage:String`, `hasEditButton:Boolean`. |
| Targets | `widget` (`#field_widget_{id}`), `actions` (`#field_actions_{id}`), `input` (the hidden/select `#{id}` inside `<span style="display:none">`, `:671-674`), `editButton` (`a.btn-warning`, `:623-642`; `hidden` attribute replaces the `.hidden` class of `:627`), `dialog` (the `<dialog>` — captured in `initialize()` into `this.dialog` because `moveToBody` detaches it from the controller's subtree, §1.3.4), `listButton`, `addButton`, `deleteButton`. |
| Actions (adminata templates carry both the legacy `onclick` shim *and* `data-action` in 1.x; `onclick` is dropped in 2.0) | `click->sonata-association#openList:prevent` (`:577`), `click->sonata-association#openAdd:prevent` (`:601`, `sonata_type_model_autocomplete.html.twig:39`, `edit_one_to_many.html.twig:92`, `edit_many_to_many.html.twig:124`, `edit_many_to_one.html.twig:83`, `edit_one_to_one.html.twig:83`), `click->sonata-association#openEdit:prevent` (`:626`), `click->sonata-association#remove:prevent` (`:649`, `edit_many_to_one.html.twig:110`, `edit_one_to_one.html.twig:110`), `click->sonata-association#append:prevent` (`edit_one_to_many.html.twig:52`, `edit_many_to_many.html.twig:94`), `change->sonata-association#inputChanged` on `input` (`:553-597`). |
| Behaviour — modal flows (`edit_many_script.html.twig`) | `openList/openAdd/openEdit(event)`: `href = event.currentTarget.href`; `modal = this.modal()` (the `sonata-modal` controller of `this.dialog`, via `application.getControllerForElementAndIdentifier`); `fetch(href, {headers:{'X-Requested-With':'XMLHttpRequest', Accept:'text/html'}, credentials:'same-origin'})` → `modal.setTitle(title)` (`:173-175`), `modal.setContent(html)` (scripts re-executed — cascaded field scripts define their shims), `Admin.shared_setup(this.dialog)` (`:177`, keeps facade parity), bind delegated `click` (on `a`) and `submit` (on `form`) inside `dialog.body` to `dialogAction` (`:181-182,223-224`) or to `listAction` for list mode (`:84-107`), `modal.open()`, `Admin.setup_list_modal(this.dialog)` (`:187`). `dialogAction` (`:235-437`): skip anchors with empty/`#`/`javascript:` href (`:240-250`) and elements with `.sonata-ba-action` (`:270-273`); `FORM` → `url=action, method=method`; `A` → `url=href, GET` (`:259-268`); remove old `div.alert-danger` (`:283-287`); body `FormData(form)` + `_xml_http_request=1` (`:275-277`), `Accept: application/json` (`:293-295`; `CRUDController::createAction` returns JSON only when `Accept` includes `application/json`/`*/*`, `S/src/Controller/CRUDController.php:1373-1398`); response `application/json` + `result==='ok'` (`:302`) → `modal.close()`; list mode → `input.value = objectId` + native `change` (`:310-311`); other modes → POST the outer form (`this.element.closest('form')`) as `FormData` + `_xml_http_request=1` to `retrieveUrl` (`:316-331`), `this.element.outerHTML = html` (`:333`; Stimulus reconnects a fresh controller on the new `#field_container_{id}`), mark the new value selected/checked (`:334-339`), dispatch legacy `sonata-admin-append-form-element` on the new container (`:341`) **and** `sonata-association:created {fieldId, objectId, objectName, createUrl}` on `document`; 200 HTML → `modal.setContent(html)` + rebind (`:351-356`); 400 JSON → violation renderer (`:361-423`: alert with `title`, per-violation lookup `[name="propertyPath"]` → `.form-group` → `.help-block.sonata-ba-field-error-messages > ul.list-unstyled > li`, `has-error` class, unmatched violations as alerts; re-enable `button`s); non-JSON error → `responseText` prepended (`:424-426`); always re-enable `button[type=submit]` (`:432`). Alerts use `sonata-dismiss`. `listAction` (`:33-107`): click inside `#field_dialog_{id} .sonata-ba-list-field` → `input.value = td.getAttribute('objectid')` (attribute is `objectId` in Twig, lower-cased in DOM; `base_list_field.html.twig:12`), native `change`, `modal.close()`; otherwise GET the href / GET-submit the form (filters/pager/sort) with `_xml_http_request=1` and re-inject (`:53-65,92-106`). |
| Behaviour — `inputChanged` (`:553-597`) | empty → `widget.innerHTML = placeholderText`, `editButton.hidden = true`; else `widget.innerHTML = <img loaderImage> loadingText` (`:570`), `fetch(shortObjectUrl.replace('OBJECT_ID', encodeURIComponent(value)))` → `widget.innerHTML = html`; `editButton.href = editUrl.replace('OBJECT_ID', value)`, `editButton.hidden = false` (`:590-596`). |
| Behaviour — `remove` (`:524-547`) | if `input` is a `<select>`: `selectedIndex = -1` and unselect options; `input.value = ''`; native `change`. |
| Behaviour — `append` (`edit_one_script.html.twig:26-78`) | POST `FormData(closest('form'))` + `_xml_http_request=1` to `appendUrl` → parse with `createDocumentFragment` → move existing `input[type=file]` elements by id into the new fragment (`:58-62`) → `this.element.replaceWith(fragment)` → the new container's controller connects; set `enctype`/`encoding=multipart/form-data` on the form when a file input exists (`:68-71`); dispatch `sonata.add_element` on `#sonata-ba-field-container-{id}` and `#field_container_{id}` (`:72-73`) plus `sonata:add-element` and `sonata-admin-append-form-element` (§3.2). |
| Events | `sonata-association:opened` `{mode, url}`, `sonata-association:selected` `{fieldId, objectId}`, `sonata-association:created` `{fieldId, objectId, objectName, createUrl}` (dispatched on `document`), `sonata-association:appended` `{container}`, `sonata-association:removed` `{fieldId}`, `sonata-association:error` `{status, body}`; legacy `sonata-admin-append-form-element`, `sonata.add_element`, `sonata-admin-setup-list-modal` (via facade). |
| Stateless CSRF interplay (G6 owns the details) | Because `APP/assets/controllers/csrf_protection_controller.js:5-7` mints the `_token` in a **capture-phase `submit` listener on `document`**, adminata's modal submit must let a real `submit` event fire before reading `FormData`: intercept `submit` (bubble phase — capture listeners on `document` have already run), `preventDefault()`, then build `FormData`. Never submit via a synthetic path that skips the event (no direct `fetch` on button click). This rule is recorded here because it constrains the controller design. |
| Global function shims | See §2.3. |

#### 1.3.15 `sonata-choice-field-mask`

| Aspect | Specification |
|---|---|
| Mount | `form_admin_fields.html.twig:454-536` block `sonata_type_choice_field_mask_widget`: `<div class="sonata-choice-field-mask" {{ stimulus_controller('sonata-choice-field-mask', {allFields: all_fields, map: map, mainFormName: main_form_name, inlineTable: form.parent.vars.sonata_admin.inline == 'table', value: value}) }} {{ stimulus_action('sonata-choice-field-mask','update','change') }}>{{ block('choice_widget') }}</div>` (block name and view vars `all_fields`/`map` unchanged, `S/src/Form/Type/ChoiceFieldMaskType.php`). |
| Values | `allFields:Array`, `map:Object`, `mainFormName:String`, `inlineTable:Boolean`, `value:String`. |
| Behaviour | `update()` reads the current value from the `<select>` or the checked radio (`:458-464`, no iCheck event needed — native `change` bubbles from radios); `containerFor(field)` tries, in order, `#sonata-ba-field-container-{main}_{field}`, `#sonata-ba-field-container-{main}-{field}`, `#{main}_{field}`, `#{main}_{field}_autocomplete_input` (`:477-497`); hide all (`hidden`; in `inlineTable` also the parent `td` and the `thead th` at the same column index, `:502-508`), move `required` → `data-required` (`:509`); show mapped, restore `required` (`:512-525`); `connect()` runs with `value` or the current control value (`:528-532`). |
| Events | `sonata-choice-field-mask:updated` `{value, shown: string[]}`. |

#### 1.3.16 `sonata-inline-row`

| Aspect | Specification |
|---|---|
| Mount | Any `.sonata-ba-field-inline-table` (no adminata template emits it, §0 fact 7; tagged by `Admin.setup_inline_form_errors(subject)`). |
| Actions | `change->sonata-inline-row#switch` (delegated: acts when `event.target.matches('[id$="_delete"][type="checkbox"]')`, `admin.js:234`). |
| Behaviour | `switch(checkbox)` = `admin.js:248-260`: checked → every `[required]` in the row becomes `data-required`, `.sonata-ba-field-error-messages` hidden; unchecked → restore. `connect()` applies to existing checked boxes (`:236-238`). Exported as a plain function `switchInlineFormErrors(checkbox)` reused by `Admin.switch_inline_form_errors` (§2). |

#### 1.3.17 `sonata-treeview`

| Aspect | Specification |
|---|---|
| Mount | `ul.js-treeview` (third-party bundles; tagged by `Admin.setup_tree_view`). |
| Values | `togglerSelector:String` (`'[data-treeview-toggler]'`), `toggledClass:String` (`'is-toggled'`), `activeClass:String` (`'is-active'`), `defaultToggledSelector:String` (`'[data-treeview-toggled]'`) — the `treeview.js:11-17` defaults. |
| Behaviour | `connect()`: `setAttribute('data-treeview-instance', true)` (`:52`); bind `click` on togglers (they carry no `data-action`); show ancestors of `.is-active` and mark their preceding sibling toggled (`:75-81`); show `[data-treeview-toggled]` (`:86-89`). `toggle(event)`: parent toggles `is-toggled`, next `<ul>` toggles `hidden` with a CSS `grid-template-rows` transition instead of `slideToggle` (`:65-70`). |
| Facade | `$.fn.treeView` is **not** re-created (jQuery plugins are out, C2); `Admin.setup_tree_view(subject)` tags instead. |

#### 1.3.18 `sonata-datepicker` (interface only; G5 owns the mapping)

| Aspect | Specification |
|---|---|
| Mount | adminata's `Form/datepicker.html.twig` overriding `sonata_type_datetime_picker_widget_html`: `<div id="{{ id }}_controller" class="input-group date" {{ stimulus_controller('sonata-datepicker', {options: datepicker_options, locale: app.request.locale}, {}, linked_to|default(false) ? {'sonata-datepicker': '#' ~ linked_to ~ '_controller'} : {}) }}>` — same element id and wrapper classes as `FE/…/Form/datepicker.html.twig:13-21`; `data-td-*` attributes dropped. |
| Values / outlets | `options:Object` (Tempus Dominus-shaped, converted to flatpickr in the controller), `locale:String`; outlet `sonata-datepicker` (range linking = `datepickerOutletConnected`, `FE/assets/js/controllers/datepicker_controller.js:62-78`). |
| Events | `sonata-datepicker:pre-connect` `{options, locale}`, `sonata-datepicker:connect` `{flatpickr}`, `sonata-datepicker:change` `{date}` — same three-phase shape as form-extensions (`:44,55,59,139-140`) so user code written against `datepicker:*` can be ported by renaming. |
| Coexistence | `datepicker` (Tempus Dominus) may still be registered by `bundles/sonataform/app.js` on `window.sonataApplication`; distinct identifier ⇒ no clash (§4.4). |

#### 1.3.19 Not shipped in 1.0

`sonata-tooltip` (no Sonata template uses tooltips; `title` is native), `sonata-collapse` (only ORM `block_audit.html.twig:27` uses `data-toggle="collapse"`; handled by `sonata-compat`), `sonata-masonry` (search results use CSS columns, `R/js-assets.md` §2), `sonata-preloader` (C17).

### 1.4 layout-nav §1–§4: every Alpine expression re-expressed as Stimulus attributes

The markup and class recipes in `R/layout-nav.md` stay valid; only the behaviour attributes change.

| layout-nav location | Alpine in TailAdmin / proposed | Stimulus replacement (adminata) |
|---|---|---|
| §1.2 / §2 row 8 `<body x-data="{page, loaded, darkMode, stickyMenu, sidebarToggle, scrollTop}" x-init="darkMode = JSON.parse(localStorage…)" :class="{'dark bg-gray-900': darkMode}">` (`T/index.html:14-19`) | — | `<body class="sonata-bc {{ _skin }} … {% if sidebar_hidden %}sidebar-collapse{% endif %}" {{ stimulus_controller('sonata-layout', {collapsed: sidebar_hidden}) }} {{ stimulus_controller('sonata-sticky') }}>`. `dark` lives on `<html>` from the `sonata_theme` cookie (C9) — no Alpine, no `x-init`. `page` is not needed (server-side `active` classes). `loaded`/preloader dropped (C17). `stickyMenu`/`scrollTop` unused by Sonata. |
| §2 row 9 header `x-data="{menuToggle:false}"` (`T/partials/header.html:2`) | — | `menuToggle` folded into `sonata-layout` (`headerMenuOpen` value, `headerMenu`/`headerMenuToggle` targets). |
| §2 row 12 hamburger `@click.stop="sidebarToggle = !sidebarToggle"` + `:class="sidebarToggle ? 'lg:bg-transparent … bg-gray-100 …' : ''"` (`header.html:13-15`) | — | `<button type="button" class="sidebar-toggle …" title="{{ 'toggle_navigation'|trans }}" aria-label="…" aria-controls="sonata-sidebar" {{ stimulus_target('sonata-layout','toggle') }} {{ stimulus_action('sonata-layout','toggleSidebar','click') }}>`; the two icon SVGs use `toggleActive` class swapping (`header.html:34,52` `:class="sidebarToggle ? 'hidden' : 'block lg:hidden'"` → the controller toggles `hidden` on the two SVG targets `iconOpen`/`iconClose`). |
| §2 row 14 right cluster `:class="menuToggle ? 'flex' : 'hidden'"` (`header.html:143`), mobile "…" button `@click.stop="menuToggle = !menuToggle"` `:class="menuToggle ? 'bg-gray-100 dark:bg-gray-800' : ''"` (`:82-83`) | — | `{{ stimulus_target('sonata-layout','headerMenu') }}` on the cluster; `{{ stimulus_target('sonata-layout','headerMenuToggle') }} {{ stimulus_action('sonata-layout','toggleHeaderMenu','click') }}` on the button; classes via `headerMenuOpen` class pair. |
| §2 row 15 add-block `<li class="relative" x-data="{dropdownOpen:false}" @click.outside="dropdownOpen=false">` + `x-show="dropdownOpen"` | — | `<li class="relative dropdown" {{ stimulus_controller('sonata-dropdown') }}><button … class="dropdown-toggle …" {{ stimulus_target('sonata-dropdown','toggle') }} {{ stimulus_action('sonata-dropdown','toggle','click') }}>…</button><div class="dropdown-menu … " hidden {{ stimulus_target('sonata-dropdown','menu') }}>{{ addBlock|raw }}</div></li>`. |
| §2 row 16 user block (same pattern, `header.html:607-642`, chevron `:class="dropdownOpen && 'rotate-180'"`) | — | Same as above; chevron gets `{{ stimulus_target('sonata-dropdown','chevron') }}` and the `toggleOpen` class `rotate-180`. `<ul class="dropdown-menu dropdown-user">{{ userBlock|raw }}</ul>` is the `menu` target. |
| §2 row 18 `<aside class="sidebar …" :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'">` (`T/partials/sidebar.html:2`) | — | `<aside id="sonata-sidebar" class="sidebar main-sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] … -translate-x-full lg:translate-x-0" {{ stimulus_target('sonata-layout','sidebar') }}>`; `sonata-layout` toggles `collapsed` (`lg:w-[90px]`) and `mobileOpen` (`translate-x-0`) classes. Server pre-applies the collapsed class when the cookie is set so the first paint is right. |
| §2 row 19 / §3.3 `<nav x-data="{selected: $persist('Dashboard')}">` (`sidebar.html:34`) and §3.4 proposal `x-data="{ open: $persist({}).as('sonata_sidebar_open'), toggle(k){…}, isOpen(k, keep, active){…} }"` | — | `<nav class="sonata-sidebar-nav" {{ stimulus_controller('sonata-menu', {storageKey: 'sonata_sidebar_open'}) }}>` (§1.3.2); `toggle(k)`/`isOpen()` are controller methods; `$persist` → `localStorage` in the controller. |
| §3.3 group header `<a href="#" @click.prevent="selected = (selected === 'X' ? '' : 'X')" class="menu-item group" :class="… ? 'menu-item-active' : 'menu-item-inactive'">` (`sidebar.html:66-70`) and §3.4 `<button … @click="toggle(name)" :class="isOpen(name) || active ? …" aria-expanded>` | — | `<button type="button" class="menu-item group w-full" aria-expanded="{{ open ? 'true' : 'false' }}" aria-controls="{{ submenu_id }}" {{ stimulus_target('sonata-menu','toggle') }} {{ stimulus_action('sonata-menu','toggle','click', {key: item.name}) }}>` inside `<li class="treeview {% if active %}active{% endif %} {% if keep_open %}keep-open{% endif %}" data-sonata-menu-key="{{ item.name }}" data-sonata-menu-active="{{ active ? 1 : 0 }}" data-sonata-menu-keep-open="{{ keep_open ? 1 : 0 }}" {{ stimulus_target('sonata-menu','group') }}>`. |
| §3.3 icon `:class="… ? 'menu-item-icon-active' : 'menu-item-icon-inactive'"` (`sidebar.html:73`) | — | Wrapper `<span class="menu-item-icon">` around `parse_icon` output; colour follows the parent via `group-[.menu-item-active]:text-brand-500` (CSS only, no JS). |
| §3.3 text `<span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">` (`:88-93`) | — | `<span class="menu-item-text" {{ stimulus_target('sonata-layout','collapseOnly') }}>` — `sonata-layout` toggles `lg:hidden` on all `collapseOnly` targets (they are descendants of `<body>`, so a body-level controller can target them). |
| §3.3 arrow `:class="[(selected === 'X') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '']"` (`:95-111`) | — | `<svg class="menu-item-arrow menu-item-arrow-inactive" {{ stimulus_target('sonata-menu','arrow') }} {{ stimulus_target('sonata-layout','collapseOnly') }}>` — two controllers, two concerns, two targets on one element (Stimulus allows it). |
| §3.3 submenu `<div class="overflow-hidden" :class="(selected === 'X') ? 'block' : 'hidden'"><ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="… menu-dropdown pl-9">` (`:115-122`) | — | `<div id="{{ submenu_id }}" class="overflow-hidden" {% if not open %}hidden{% endif %} {{ stimulus_target('sonata-menu','submenu') }}><ul class="treeview-menu menu-dropdown mt-2 flex flex-col gap-1 pl-9" {{ stimulus_target('sonata-layout','collapseOnly') }}>` — server renders the initial `hidden` from `keep_open || active` so no flash; the controller reconciles with `localStorage` on connect. |
| §3.4 overlay `@click="sidebarToggle = false" :class="sidebarToggle ? 'block lg:hidden' : 'hidden'"` (`T/partials/overlay.html`) | — | `<div class="fixed inset-0 z-9 bg-gray-900/50" hidden {{ stimulus_target('sonata-layout','overlay') }} {{ stimulus_action('sonata-layout','closeMobile','click') }}></div>` in new block `sonata_overlay`. |
| §2 row 20 / §4.4 search ⌘K + `/` (`T/js/index.js:91-118`) | — | `sonata-search-shortcut` on the header form (§1.3.8). |
| §4.2 add-block panel (`role="menu"`, `aria-haspopup`, `:aria-expanded="dropdownOpen"`) | — | `sonata-dropdown` sets `aria-expanded` on `toggle`; panel keeps `role="menu"`, items `role="menuitem" tabindex="-1"` (`Core/add_block.html.twig`). |
| §4.5 dark-mode `@click.prevent="darkMode = !darkMode"` (`header.html:150`) | — | `sonata-theme` (§1.3.6). |
| §4.5 notifications `x-data="{ dropdownOpen:false, notifying:true }"` | — | Not rendered; empty block `sonata_top_nav_menu_notifications`. If an app pastes TailAdmin markup there it must add its own Alpine via `extra_javascripts` (documented). |
| `R/show-misc.md` §6.1 actions dropdown `x-data="{open:false}" @click.outside="open=false"` + `:class="open && 'rotate-180'"` | — | `<li class="dropdown sonata-actions relative" {{ stimulus_controller('sonata-dropdown') }}><button type="button" class="dropdown-toggle …" {{ stimulus_target('sonata-dropdown','toggle') }} {{ stimulus_action('sonata-dropdown','toggle','click') }}>{{ 'link_actions'|trans }} <svg {{ stimulus_target('sonata-dropdown','chevron') }}>…</svg></button><ul class="dropdown-menu …" role="menu" hidden {{ stimulus_target('sonata-dropdown','menu') }}>{{ _actions|raw }}</ul></li>` — the `_actions|split('</a>')|length > 2` heuristic (`standard_layout.html.twig:266-279`) is unchanged. |
| `R/list-datagrid.md` §2.2, §11 (filter list, export dropdowns "Alpine or `sonata-dropdown`") | — | `sonata-dropdown` everywhere. |

### 1.5 `sonata-compat`: Bootstrap 3 data-API delegate (not a controller)

`assets/js/compat/bootstrap-data-api.js`, loaded by `app.js`, one delegated `click` listener on `document`, acting **only** when the clicked element's nearest `[data-controller]` ancestor does not already provide the corresponding `sonata-*` controller (so adminata's own templates never take this path; user-overridden templates copied from Sonata 4.x do). Kept because V19 counts `data-toggle=` in 13 upstream files that users have copied, `APP/templates/layout/standard_layout_override.html.twig:61` still emits `data-dismiss="modal"`, `APP/templates/generic_create.html.twig:19` emits `data-dismiss="alert"`, and ORM `block_audit.html.twig:27` emits `data-toggle="collapse"`.

| Attribute | Delegate behaviour |
|---|---|
| `[data-toggle="dropdown"]` | Toggle `hidden` on the next sibling `.dropdown-menu` (or `parent.querySelector('.dropdown-menu')`), toggle `.open` on the parent; outside click / Esc closes. |
| `[data-toggle="tab"][href="#id"]` | Activate the pane `#id` and `li.active` among siblings inside the nearest `.nav-tabs`/`.tab-content` pair (same DOM rules as `sonata-tabs`). |
| `[data-toggle="modal"][data-target]` / `[data-toggle="modal"][href="#id"]` | If the target is a `<dialog>` → `showModal()`; if it is a legacy `div.modal` → add `.in`, remove `hidden`, add `modal-open` on body (compat CSS makes `.modal.in` visible). |
| `[data-dismiss="modal"]` | `closest('dialog')?.close()` or hide the legacy `div.modal`. |
| `[data-dismiss="alert"]` | `closest('.alert')?.remove()`. |
| `[data-toggle="collapse"][data-target]`/`[href]` | Toggle `hidden` + `.in` on the target. |
| `[data-toggle="push-menu"]` (`.sidebar-toggle` from copied layouts) | Call the `sonata-layout` controller's `toggleSidebar()` on `<body>`. |
| `[data-widget="collapse"]` / `[data-widget="remove"]` (AdminLTE boxes in user dashboards) | Toggle `.collapsed-box` + hide `.box-body`/`.box-footer`; remove the `.box`. |

`$('#universal-modal').modal()` (`APP/assets/admin/UniversalModal.js:8,18`) is **not** covered — it calls a jQuery plugin adminata does not ship (§6, row 6).

### 1.6 Twig call-site index (template → controllers)

| Template (`S/src/Resources/views/`) | Controllers / attributes after the rewrite |
|---|---|
| `standard_layout.html.twig` | `<body>`: `sonata-layout` + `sonata-sticky`; hamburger: `sonata-layout` target/action; header: `sonata-sticky` `topNavbar`; search form: `sonata-search-shortcut`; theme button: `sonata-theme`; add-block `<li>`: `sonata-dropdown`; user-block `<li>`: `sonata-dropdown`; `li.sonata-actions`: `sonata-dropdown`; page toolbar: `sonata-sticky` `navbar`; sidebar `<aside>`: `sonata-layout` `sidebar`; overlay: `sonata-layout` `overlay`; `bootlint` block empty. |
| `ajax_layout.html.twig` | No controllers of its own; the list-mode switcher and `_list_filters_actions` fragments it re-renders (`:12-46`) carry `sonata-filter-list` + `sonata-dropdown` exactly like `base_list`. |
| `Menu/sonata_menu.html.twig` | `sonata-menu` root/group/toggle/submenu/arrow; `sonata-layout` `collapseOnly` on texts/arrows/child lists. |
| `Core/tab_menu_template.html.twig` | `sonata-dropdown` on dropdown items (`dropdownElement`, `:105-119`). |
| `Core/add_block.html.twig` | Panel is the `sonata-dropdown` `menu` target (controller lives in `standard_layout`). |
| `CRUD/base_list.html.twig` | list `<form>`: `sonata-batch`; export `div.btn-group`: `sonata-dropdown`; `ul#filter-list-*`: `sonata-filter-list` (unchanged) with inner `li.dropdown`: `sonata-dropdown`; `#filter-container-*`: `sonata-filter` (unchanged); `{% block batch_javascript %}` empty. |
| `CRUD/base_list_field.html.twig` | `sonata-readmore` (unchanged); `span.x-editable`: `sonata-editable`. |
| `CRUD/base_show_field.html.twig` | `sonata-readmore` (unchanged). |
| `CRUD/base_edit_form.html.twig` | `<form>`: `sonata-confirm-exit` + `sonata-edit` (unchanged); `div.nav-tabs-custom`: `sonata-tabs`; tabs: `sonata-edit` `tab` target + `changeTab` (unchanged) + `sonata-tabs` `tab` target + `select`; `.sonata-ba-form-actions`: `sonata-sticky` `action` (unchanged); `render_form_dismissable_errors`: `sonata-dismiss`. |
| `CRUD/base_show.html.twig` | `div.nav-tabs-custom`: `sonata-tabs`. |
| `CRUD/base_history.html.twig` | `sonata-revision` (unchanged). |
| `CRUD/tree.html.twig` | `sonata-dropdown` on the context `btn-group` (`:52-70`). |
| `CRUD/dashboard__action_create.html.twig` | `sonata-dropdown` (`:11-32`, wrapper element added). |
| `Pager/base_results.html.twig` | `sonata-per-page` (unchanged); `sonata-select` when `use_select2`. |
| `Helper/render_form_dismissable_errors.html.twig` | `sonata-dismiss`. |
| `Form/form_admin_fields.html.twig` | `choice_widget_collapsed`: `sonata-select`; `sonata_type_native_collection_widget(_row)`: `sonata-collection` (unchanged); `sonata_type_choice_field_mask_widget`: `sonata-choice-field-mask`; `sonata_type_choice_multiple_sortable`: inline `<script>` kept, calls `Admin.setup_sortable_select2`; `sonata_type_model_list_widget`: `sonata-association` + `sonata-modal`. |
| `Form/filter_admin_fields.html.twig` | `sonata-select` on value selects (not operator selects); `sonata-autocomplete` via the included autocomplete template (`:87-89`). |
| `Form/Type/sonata_type_model_autocomplete.html.twig` | `sonata-autocomplete` + `sonata-association` (mode `autocomplete`) + `sonata-modal`; inline script reduced to the optional hooks object + the shim (§2.3). |
| `CRUD/Association/edit_modal.html.twig` | `sonata-modal`. |
| `CRUD/Association/edit_many_script.html.twig` | Shim only (§2.3). |
| `CRUD/Association/edit_one_script.html.twig` | Shim only (§2.3). |
| `CRUD/Association/edit_one_to_many_sortable_script_{table,tabs}.html.twig` | Empty (kept as files). |
| `CRUD/Association/edit_{many_to_one,one_to_one,one_to_many,many_to_many}.html.twig` | `sonata-association` on `#field_container_{id}`; `sonata-sortable` when sortable; buttons carry `onclick` shim + `data-action`. |
| `CRUD/Association/edit_one_to_many_inline_tabs.html.twig` | `sonata-tabs` on each `.nav-tabs-custom`. |
| adminata `Form/datepicker.html.twig` (new) | `sonata-datepicker`. |
| adminata `FlashMessage/render.html.twig` (new, G7) | `sonata-dismiss`; read-more toggle CSS-only (`:checked ~ label .more/.less`), `S/assets/js/base.js` deleted. |

---

## 2. The `window.Admin` facade

### 2.1 Subject normalisation

Every `subject`/`modal`/`element` argument goes through one helper:

```js
// assets/js/core/dom.js
export function toElement(subject, fallback = document) {
  if (subject == null) return fallback;
  if (subject.jquery) return subject[0] ?? fallback;                 // jQuery collection (edit_many_script.html.twig:63,104,138,177,219,353,500)
  if (typeof subject === 'string') return document.querySelector(subject) ?? fallback; // "html selector" (admin.js:139)
  if (subject instanceof Element || subject instanceof Document || subject instanceof DocumentFragment) return subject;
  return fallback;
}
```

Tagging helper used by the `setup_*` members: `tag(root, selector, identifier, {filter})` adds the identifier token to `data-controller` of every match (idempotent; never removes tokens; Stimulus' `TokenListObserver` picks up attribute changes, `STIM:912-931`). `Admin.log` is called first in every member exactly as today.

### 2.2 The 16 members (`S/assets/js/admin.js:20-370`)

| # | Member (signature unchanged) | Legacy (`admin.js`) | adminata implementation |
|---|---|---|---|
| 1 | `shared_setup(subject)` | `:20-28` runs select2, icheck, checkbox range, xeditable, inline form errors, tree view | `root = toElement(subject)`; calls members 5, 6 (silent variant), 7, 8, 10, 12 in that order; then `root.dispatchEvent(new CustomEvent('sonata:setup', {bubbles:true, detail:{root}}))` (new hook for user code that used to piggy-back on `shared_setup`). Stimulus connects any `data-controller` in `root` on its own; this member exists to *tag* legacy markup. |
| 2 | `get_config(key)` | `:29-31` | `Config.param(key)` (missing meta → `null`, §0 fact 4). |
| 3 | `get_translations(key)` | `:32-34` | `Translation.trans(key)`. |
| 4 | `setup_list_modal(modal)` | `:35-57` inline-styles `.modal-dialog/.modal-content/.modal-body` to 90 %/85 %, jQuery-triggers `sonata-admin-setup-list-modal` | `el = toElement(modal)`; `ctrl = adminata.controller(el, 'sonata-modal')`; if `ctrl` → `ctrl.setSize('list')`; else (legacy `div.modal` from a copied template) add class `modal-list` (compat CSS gives the same geometry); then `el.dispatchEvent(new CustomEvent('sonata-admin-setup-list-modal', {bubbles:true, detail:{modal: el}}))` — documented event (`S/docs/reference/form_types.rst:110-113`). |
| 5 | `setup_select2(subject)` | `:58-110` | if `!Config.param('USE_SELECT2')` return; `tag(root, 'select', 'sonata-select', {filter: el => el.dataset.sonataSelect2 !== 'false' && !el.tomselect && !/autocomplete/.test(el.getAttribute('data-controller') ?? '')})`. Option mapping lives in the controller (§1.3.12). |
| 6 | `setup_icheck(subject)` | `:111-132` | No-op. Direct external calls emit **one** `console.warn('Admin.setup_icheck() is deprecated since adminata 1.0 and does nothing: iCheck is no longer bundled; native checkboxes are styled by CSS. It will be removed in 2.0.')` per page (`shared_setup` calls the silent internal variant). `data-sonata-icheck="false"` is accepted and ignored (`S/docs/cookbook/recipe_icheck.rst:27-67`). |
| 7 | `setup_checkbox_range_selection(subject)` | `:141-189` (contains the `indexedDB` bug at `:173`) | For every `table` in `root` that has `tbody input[type="checkbox"]` and no `sonata-batch` ancestor: tag `table.closest('form') ?? table` with `sonata-batch` (§1.3.9). |
| 8 | `setup_xeditable(subject)` | `:191-212` | `tag(root, '.x-editable', 'sonata-editable')`; the controller reads `data-*`. Legacy `success` handler semantics (`:198-201`) live in the controller. |
| 9 | `log(...args)` | `:218-229` | Unchanged (gated by `DEBUG`; `window.opera` branch dropped). |
| 10 | `setup_inline_form_errors(subject)` | `:231-243` | `tag(root, '.sonata-ba-field-inline-table', 'sonata-inline-row')`. |
| 11 | `switch_inline_form_errors(subject)` | `:248-260` (`subject` = the `_delete` checkbox, jQuery) | `switchInlineFormErrors(toElement(subject))` — the exported function shared with `sonata-inline-row`. |
| 12 | `setup_tree_view(subject)` | `:262-266` (`$.fn.treeView`) | `tag(root, 'ul.js-treeview', 'sonata-treeview')`. |
| 13 | `get_select2_width(element)` | `:269-291` | Pure port: `el = toElement(element)`; parse `el.getAttribute('style')` with the same regex (`:270`); else `getComputedStyle(el).width` if it contains `%`; else `'100%'`. Kept because user overrides of `sonata_type_model_autocomplete.html.twig` call it (`:74-76`). |
| 14 | `setup_sortable_select2(subject, data, customOptions)` | `:293-362` (select2 + jQuery-UI sortable on the hidden input) | `el = toElement(subject)` (the hidden `input#{id}`); build `{value: item.data, text: item.label}` options, warn on duplicate labels (`:300-304`), `items` = current comma-separated value order (`:297,312-317`); `new TomSelect(el, {options, items, delimiter: ',', plugins: ['remove_button','drag_drop'], maxItems: null, create: false, ...customOptions})`; on the enclosing form's `submit`: expand `el.value.split(',')` into hidden `name[i]` inputs (`baseName` = `name` minus trailing `]`, `:347-354`) and remove `el` (`:360`). `customOptions` is now a Tom Select config (documented change). |
| 15 | `setup_sticky_elements()` | `:363-366` deprecated warning | Unchanged. |
| 16 | `setup_readmore_elements()` | `:367-370` deprecated warning | Unchanged. |

`Object.keys(window.Admin).length === 16` is asserted by the contract test (§5.4). New helpers go on `window.adminata`, never on `Admin`:

| `window.adminata` member | Purpose |
|---|---|
| `version:String` | Package version (from `package.json` at build time). |
| `application` | The Stimulus `Application` adminata registered into (same object as `window.sonataApplication`). |
| `controller(element, identifier)` | `application.getControllerForElementAndIdentifier(toElement(element), identifier)` — the only sanctioned way for inline scripts and user code to reach a controller instance. |
| `association(id)` | `controller(document.getElementById('field_container_' + id) ?? document.getElementById(id + '_autocomplete'), 'sonata-association')` — used by the shims (§2.3). |
| `autocompleteHooks:Object` | Per-field hook objects rendered from the three autocomplete Twig blocks (§1.3.13). |
| `Admin` | Same object as `window.Admin`. |
| `startAdminata(options)` | ESM entry (§4.2); exposed on the IIFE too so an app can re-run registration against another application. |
| `bridgeJQuery(jq)` | Installs the jQuery ↔ native event bridge (§3.3) for a jQuery instance that arrived after `app.js` (the real app loads jQuery from Sonata's global, but a user removing `vendor/jquery.js` and loading their own later would call this). |

### 2.3 Global function shims emitted by the association templates

The templates are kept as files and still included from the same places (`form_admin_fields.html.twig:690`, `sonata_type_model_autocomplete.html.twig:56-57`, `edit_many_to_one.html.twig:130-131`, `edit_one_to_many.html.twig:82,109-110`, `edit_many_to_many.html.twig`, `edit_one_to_one.html.twig`), because user form themes and the ORM/Mongo association templates include them by path. Their body shrinks to:

`CRUD/Association/edit_many_script.html.twig` (replaces `:25-602`; still `{% autoescape false %}`, ids come from Symfony form ids and are `[A-Za-z0-9_]`):

```twig
{% autoescape false %}
<script{{ block('sonata_script_attributes')|default('') }}>
(function (id) {
    var A = function (link) { return window.adminata && window.adminata.association(id); };
    // Same names, same "return false", same single argument as Sonata 4.x (edit_many_script.html.twig:462-534).
    window['start_field_dialog_form_add_' + id]  = function (link) { var c = A(); if (c) c.openAdd({ currentTarget: link, preventDefault: function () {}, stopPropagation: function () {} }); return false; };
    window['start_field_dialog_form_edit_' + id] = function (link) { var c = A(); if (c) c.openEdit({ currentTarget: link, preventDefault: function () {}, stopPropagation: function () {} }); return false; };
    {% if sonata_admin.edit == 'list' %}
    window['start_field_dialog_form_list_' + id] = function (link) { var c = A(); if (c) c.openList({ currentTarget: link, preventDefault: function () {}, stopPropagation: function () {} }); return false; };
    window['remove_selected_element_' + id]      = function (link) { var c = A(); if (c) c.remove({ currentTarget: link, preventDefault: function () {} }); return false; };
    {% endif %}
})('{{ id }}');
</script>
{% endautoescape %}
```

`CRUD/Association/edit_one_script.html.twig` (replaces `:23-99`): defines `window['start_field_retrieve_' + id]` delegating to `c.append(...)`, `return false`.

Rules:

1. The shims are `function` *assignments on `window`* rather than `function` declarations, so re-executing the script (cascaded modals inject the same field twice, `edit_many_script.html.twig:499-501`) simply overwrites — no "already declared" errors, and `let/const` are avoided for the same reason.
2. When the controller is not (yet) connected — e.g. a click before `DOMContentLoaded`, or on the ESM path without adminata registered — the shim returns `false` and logs via `Admin.log`; the link's `href` is not followed (same as today).
3. The `var field_dialog_{{ id }}`, `field_dialog_content_{{ id }}`, `field_dialog_title_{{ id }}`, `field_widget_{{ id }}`, `apply_position_value_{{ id }}`, `initialize_popup_{{ id }}`, `field_dialog_form_*_{{ id }}` globals (`:33,79,111,151,193,235,439-455`; `edit_one_script.html.twig:26,80`; sortable scripts `:19`) are **not** re-created — they were never documented and referenced only inside these scripts (§6, row 10).
4. `sonata_script_attributes` is an empty block for CSP nonces (`R/js-assets.md` §8.5).

Because adminata's own association templates emit `data-action` **and** `onclick`, Stimulus and the shim both see the click; the controller must be idempotent per click: `openAdd(event)` calls `event.preventDefault()` and marks the event (`event.sonataHandled = true`); the shim path receives a synthetic object, never the DOM event, so no double-open occurs (the `onclick` handler returns `false` first and the Stimulus `data-action` — bound with `:prevent` — still runs; the controller de-duplicates opens within the same task via a `this.opening` flag).

---

## 3. Globals and events contract

### 3.1 Globals

| Global | Defined by | Consumers | 1.x status |
|---|---|---|---|
| `window.$`, `window.jQuery` | `bundles/sonataadmin/vendor/jquery.js` (separate IIFE: `import $ from 'jquery'; window.$ = window.jQuery = $;` — nothing else; no `$.fn.*` plugins) | `APP/webpack.config.js:16` externals, `APP/assets/admin/*.js`, `S/docs/cookbook/recipe_jquery_ui.rst:20`, user overrides of Sonata inline scripts | Default `javascripts` list: `['bundles/sonataadmin/vendor/jquery.js', 'bundles/sonataadmin/app.js']` (replacing `Configuration.php` defaults `bundles/sonataadmin/app.js`, `bundles/sonataform/app.js`, `S/src/DependencyInjection/Configuration.php:666-669`). Removable with `sonata_admin.assets.remove_javascripts: [bundles/sonataadmin/vendor/jquery.js]`. `console.info` once when loaded with `DEBUG`: deprecated, removed in 2.0. |
| `window.stimulus` | `app.js` (`import * as stimulus from '@hotwired/stimulus'`, `S/assets/js/app.js:46,53`) | user code `class extends stimulus.Controller` | Keep. |
| `window.sonataApplication` | `app.js` (`S/assets/js/app.js:54`) | `FE/assets/js/app.js:16-18`, user `sonataApplication.register(...)` | Keep; set synchronously during script evaluation (form-extensions' script is evaluated right after, blocking). On the ESM path set by `startAdminata({application})` unless `exposeGlobals:false`. |
| `window.Admin` | `admin.js:373` | inline scripts, user JS | Keep, 16 members (§2.2). |
| `window.adminata` | new | shims, user code | New namespace (§2.2). |
| `window.Alpine` | TailAdmin `T/js/index.js:19` | — | **Never defined** by adminata (C1). |
| `$.fn.treeView` (`S/assets/js/treeview.js:94-100`) | — | — | **Removed** (§6 row 7). |

### 3.2 Native events (all bubbling `CustomEvent`s; jQuery `.on()` receives them)

| Event type | Dispatched on | `detail` | Emitter | Legacy emitter |
|---|---|---|---|---|
| `sonata-admin-append-form-element` | new `.sonata-collection-row` (`collection_controller.js:32-39`); new `#field_container_{id}` after retrieve/append | `{item}` / `{container}` | `sonata-collection`, `sonata-association` | `admin.js:381-384` listener (now unnecessary — Stimulus connects `sonata-select` in the fragment), `edit_many_script.html.twig:341` jQuery trigger |
| `sonata-admin-setup-list-modal` | the `<dialog>` | `{modal}` | `Admin.setup_list_modal` | `admin.js:56` jQuery trigger |
| `sonata-collection-item-added`, `sonata-collection-item-deleted`, `sonata-collection-item-deleted-successful` | collection root | `{item}` (first two) | `sonata-collection` (unchanged) | same |
| `sonata.add_element` (**legacy spelling kept**) and `sonata:add-element` (canonical) | `#sonata-ba-field-container-{id}` and `#field_container_{id}` (`edit_one_script.html.twig:72-73`) | `{container}` | `sonata-association#append`; also after `sonata-collection-item-added` inside a sortable container | jQuery `.trigger('sonata.add_element')` — note jQuery parses the dot as a **namespace** (`type: 'sonata'`, ns `add_element`), see §3.3 |
| `sonata:setup` | the root passed to `Admin.shared_setup` | `{root}` | facade | — |
| `sonata-layout:sidebar-changed`, `sonata-layout:header-menu-changed` | `<body>` | `{collapsed, mobileOpen}` / `{open}` | `sonata-layout` | — |
| `sonata-menu:toggled` | `<nav>` | `{key, open}` | `sonata-menu` | — |
| `sonata-dropdown:opened`, `sonata-dropdown:closed` | dropdown root | `{}` | `sonata-dropdown` | Bootstrap `show.bs.dropdown` etc. (jQuery-only, gone) |
| `sonata-modal:opened`, `sonata-modal:closed`, `sonata-modal:content-loaded` | `<dialog>` | `{}` / `{body}` | `sonata-modal` | Bootstrap `shown.bs.modal`, `hidden.bs.modal` (gone) |
| `sonata-tabs:show` (command), `sonata-tabs:shown` | tab / `.nav-tabs-custom` | `{tab, pane}` | `sonata-edit` / `sonata-tabs` | Bootstrap `shown.bs.tab` (gone) |
| `sonata-theme:changed` | button (bubbles to `document`) | `{theme}` | `sonata-theme` | — |
| `sonata-dismiss:dismissed` | the alert | `{}` | `sonata-dismiss` | Bootstrap `closed.bs.alert` (gone) |
| `sonata-batch:changed`, `sonata-batch:range` | list form | `{selected}` / `{from,to,checked}` | `sonata-batch` | iCheck `ifChanged/ifChecked` (gone) |
| `sonata-editable:opened/saved/error/cancelled` | `span.x-editable` | `{td, html}` / `{status, message}` | `sonata-editable` | x-editable `save`/`shown` (gone) |
| `sonata-sortable:reordered` | `#field_container_{id}` | `{order}` | `sonata-sortable` | jQuery-UI `sortupdate` (gone) |
| `sonata-select:pre-connect/connect/disconnect` | `<select>` | `{options}` / `{tomSelect}` | `sonata-select` | select2 `select2:*` (gone) |
| `sonata-autocomplete:pre-connect/connect/changed` | wrapper div | `{options}` / `{tomSelect}` / `{value}` | `sonata-autocomplete` | select2 `select2:select/unselect` (gone), `$(document).ajaxSuccess` (gone) |
| `sonata-association:opened/selected/created/appended/removed/error` | container (`created` on `document`) | see §1.3.14 | `sonata-association` | — |
| `sonata-choice-field-mask:updated` | wrapper | `{value, shown}` | — | — |
| `sonata-datepicker:pre-connect/connect/change` | `#{id}_controller` | `{options, locale}` / `{flatpickr}` / `{date}` | `sonata-datepicker` | form-extensions `datepicker:*` (still emitted by form-extensions' own controller if loaded) |
| `sonata:ready` | `document` | `{application, version}` | `app.js` after registration and DOM ready | — (2.x `defer` migration hook, C15) |

### 3.3 jQuery ↔ native bridge (`assets/js/compat/jquery-bridge.js`)

Installed by `app.js` when `window.jQuery` exists at evaluation time, and again on `DOMContentLoaded` if it appeared later; also callable via `adminata.bridgeJQuery(jq)`. Native → jQuery needs nothing (jQuery's `.on` uses `addEventListener`). jQuery → native:

```js
const BRIDGED = ['sonata-admin-append-form-element', 'sonata-admin-setup-list-modal',
  'sonata-collection-item-added', 'sonata-collection-item-deleted', 'sonata-collection-item-deleted-successful',
  'sonata.add_element'];
export function bridgeJQuery(jq) {
  if (!jq || jq.fn?.sonataBridged) return;           // idempotent
  jq.fn.sonataBridged = true;
  BRIDGED.forEach((name) => {
    jq(document).on(name, (e, ...args) => {
      if (e.originalEvent) return;                    // native event echoed into jQuery — do not re-dispatch (re-entrancy guard)
      const type = name;                              // 'sonata.add_element' is delivered to handlers bound as 'sonata.add_element' (type 'sonata', ns 'add_element')
      e.target.dispatchEvent(new CustomEvent(type, { bubbles: true, cancelable: true, detail: { fromJquery: true, args } }));
    });
  });
}
```

Guard rationale: jQuery sets `event.originalEvent` only for events that came from the DOM; `.trigger()`-created `jQuery.Event`s have none. So a native dispatch (by adminata) reaches jQuery handlers with `originalEvent` set and is not echoed back; a jQuery `.trigger()` (by legacy user code such as a copied `edit_many_script.html.twig:341`) has no `originalEvent` and is re-dispatched natively once. For `sonata.add_element`, adminata dispatches the native event with the literal dotted type (so `el.addEventListener('sonata.add_element')` works) **and**, when jQuery is present, also `jq(el).trigger('sonata.add_element')` with a `sonataBridged` flag in the event data — because jQuery listeners bound to `'sonata.add_element'` listen for native type `sonata`, not `sonata.add_element`, and would otherwise miss it. The bridge handler ignores triggers carrying that flag.

### 3.4 `<meta>` key lists

`<meta name="sonata-config">` (`standard_layout.html.twig:37-45`, read by `core/config.js:18`):

| Key | Status | Value |
|---|---|---|
| `SKIN` | existing | `_skin` (kept for user JS reading it; drives `data-skin`, G2) |
| `CONFIRM_EXIT` | existing | `options.confirm_exit` |
| `USE_SELECT2` | existing | `options.use_select2` — now "enhance plain selects with Tom Select" |
| `USE_ICHECK` | existing | emitted, ignored |
| `USE_STICKYFORMS` | existing | `options.use_stickyforms` |
| `DEBUG` | existing | `options.js_debug` |
| `THEME` | **new** | `'light'|'dark'|'system'` resolved from cookie `sonata_theme` and `options.default_theme` (new option, G2/G8) |
| `LOCALE` | **new** | `app.request.locale` (flatpickr/Tom Select strings) |
| `SIDEBAR_COLLAPSED` | **new** | boolean from cookie `sonata_sidebar_hide` |
| `SEARCH` | **new** | `options.search` (lets `sonata-search-shortcut` no-op when search is disabled and a layout override keeps the form) |
| `VERSION` | **new** | adminata version string |

`<meta name="sonata-translations">` (`:46-49`, `core/translation.js:18`), all from the `SonataAdminBundle` XLIFF domain (G8 owns ids):

| Key | Status | Used by |
|---|---|---|
| `CONFIRM_EXIT` | existing | `sonata-confirm-exit` |
| `SELECT_NO_RESULTS`, `SELECT_LOADING`, `SELECT_LOADING_MORE`, `SELECT_NO_MORE_RESULTS`, `SELECT_TYPE_TO_SEARCH`, `SELECT_INPUT_TOO_SHORT` (`%count%`), `SELECT_CREATE_OPTION` (`%input%`), `SELECT_CLEAR`, `SELECT_REMOVE` | new | `sonata-select`, `sonata-autocomplete` (Tom Select `render.no_results/loading/option_create`, plugin labels) |
| `EDITABLE_SAVE`, `EDITABLE_CANCEL`, `EDITABLE_EMPTY`, `EDITABLE_ERROR` | new | `sonata-editable` |
| `MODAL_CLOSE` | new | `sonata-modal` close button `aria-label` when built in JS |
| `THEME_TOGGLE` | new | `sonata-theme` `aria-label` |
| `SIDEBAR_TOGGLE` | new | `sonata-layout` `aria-label` (same string as `toggle_navigation`) |
| `SEARCH_SHORTCUT` | new | `sonata-search-shortcut` hint text |
| `ASSOCIATION_ERROR` | new | generic failure text in `sonata-association` when the response is neither JSON nor HTML |

`loading_information` / `short_object_description_placeholder` remain per-field controller values (they were per-field literals in `edit_many_script.html.twig:561,570`), not meta keys.

---

## 4. Coexistence rules

### 4.1 Two Stimulus `Application`s on one page (the real app's situation)

`APP/assets/bootstrap.js:4-8` calls `startStimulusApp(require.context(...))`, which does `Application.start()` on `document.documentElement` (`APP/node_modules/@symfony/stimulus-bridge/dist/index.js:26-36`) — a second application next to adminata's `sonataApplication`. Verified consequences (`STIM` line refs in §0):

| Question | Answer (from source) | Rule |
|---|---|---|
| Do both applications observe the same DOM? | Yes — each has its own `ScopeObserver` → `ValueListObserver` → `TokenListObserver` → `AttributeObserver` → `ElementObserver` with its own `MutationObserver` on `documentElement` (`STIM:426-434,1926-1931,1983-1986,2089-2101`). | Fine; two observers cost little. |
| Are identifiers per application? | Yes. A `data-controller="foo"` token creates a `Scope` in every application (`STIM:1948-1956`) but a `Context`/controller instance only in applications whose `Router.modulesByIdentifier` has `foo` (`STIM:2050-2056`). Unknown identifiers are silently ignored (no error; the `console.error` at `STIM:2039-2041` is only for outlet proposals). | **R1** An app may keep its own application and controllers (`chart`, `json-editor`, `local-datetime`, `csrf-protection`…, `APP/assets/controllers/`) untouched. |
| What if both register the same identifier? | Two controller instances on the same element (each router connects its own module). | **R2** Never register a `sonata-*` identifier in the app's application while `bundles/sonataadmin/app.js` is loaded (double Tom Select, double dialogs). The contract test's identifier list is the reserved namespace. |
| Do `data-action` descriptors cross applications? | No. Actions are bound per controller context (`BindingObserver`, `STIM:1037-1100`) and matched by identifier; `sonata-edit#prepareSubmit` binds only where `sonata-edit` is connected. | — |
| Do outlets cross applications? | No. `OutletObserver.getOutlet` → `this.application.getControllerForElementAndIdentifier` (`STIM:1447-1449`). | **R3** `sonata-filter` ⇄ `sonata-filter-list` (outlets, `filter_controller.js:16`, `filter_list_controller.js:14`) and `sonata-datepicker` ⇄ `sonata-datepicker` must live in the **same** application — i.e. all adminata controllers are registered into exactly one application. |
| Does start order matter? | No — `Application.start()` awaits `domReady()` (`STIM:2108-2114`) and registration before that is honoured; registration after start is also honoured (`loadDefinition` → `connectModule` connects already-observed scopes, `STIM:2064-2068`). | — |
| `shouldLoad` | Evaluated in `Application.load` (`STIM:2127-2133`) at registration time; `sonata-sticky`/`sonata-confirm-exit` read `Config.param` then. | §0 fact 4: tolerate a missing meta tag. |
| `application.debug` | stimulus-bridge sets `debug = true` in development for the app's application only (`index.js:28`). adminata's IIFE sets `sonataApplication.debug = Config.param('DEBUG') === true`. | — |
| `window.sonataApplication` | Must be the application that owns the `sonata-*` controllers *and* the one third parties register into (`FE/assets/js/app.js:16-18`). | **R4** With two applications, `sonataApplication` is adminata's; the app's `app` export is separate. Third-party `datepicker` lands in adminata's application — correct, because its Twig (`FE/…/datepicker.html.twig:16`) and adminata's forms are rendered in the same DOM. |
| Non-Stimulus "controllers" | `APP/assets/controllers/csrf_protection_controller.js` has no default export; stimulus-bridge's `definitionForModuleAndIdentifier` skips it (`index.js:12-17`), it just runs its document listeners. | Unaffected. |

### 4.2 One application: `app.esm.js` and `startAdminata({application})`

```js
// assets/js/app.esm.js  (built to src/Resources/public/app.esm.js, format 'es', no side effects at import time)
import * as stimulus from '@hotwired/stimulus';
import { definitions } from './registry';
import { Admin } from './admin';
import { bridgeJQuery } from './compat/jquery-bridge';
import { installBootstrapDataApi } from './compat/bootstrap-data-api';
import { Config } from './core/config';

export { definitions, Admin, stimulus };
export const version = __ADMINATA_VERSION__;

export function startAdminata({ application, exposeGlobals = true, jQuery = globalThis.jQuery, bootstrapDataApi = true } = {}) {
  if (!application) { application = stimulus.Application.start(); }
  application.load(definitions);                       // explicit; honours static shouldLoad
  application.debug ||= Config.param('DEBUG') === true;
  if (exposeGlobals) {
    globalThis.sonataApplication = application;         // form-extensions contract (FE/assets/js/app.js:16-18)
    globalThis.stimulus ??= stimulus;
    globalThis.Admin = Admin;
    globalThis.adminata = { version, application, Admin, controller, association, autocompleteHooks: {}, startAdminata, bridgeJQuery };
  }
  if (jQuery) bridgeJQuery(jQuery);
  if (bootstrapDataApi) installBootstrapDataApi();
  document.documentElement.classList.remove('no-js'); // admin.js:376
  Promise.resolve().then(() => document.dispatchEvent(new CustomEvent('sonata:ready', { detail: { application, version } })));
  return application;
}
```

App recipes:

| App toolchain | Steps |
|---|---|
| Encore (the real app) | `sonata_admin.assets.remove_javascripts: [bundles/sonataadmin/app.js]` (keep `vendor/jquery.js` while `assets/admin/*.js` exist); `package.json`: `"@idct/adminata": "file:vendor/idct/adminata/assets"` (the pattern the app already uses for `@symfony/ux-autocomplete`, `APP/package.json:9`; adminata ships `assets/package.json` with `"main": "js/app.esm.js"` and `"exports"`); `assets/bootstrap.js`: `import { startAdminata } from '@idct/adminata'; export const app = startStimulusApp(require.context(...)); startAdminata({ application: app });`. Script order still matters: `build/app.js` is in `extra_javascripts` (after Sonata's list), so `window.sonataApplication` exists only after it — **`bundles/sonataform/app.js` must not precede it** (adminata drops it from the defaults; if re-added it must go into `extra_javascripts` after the app bundle, else `FE/assets/js/app.js:16-18` throws `TypeError: Cannot read properties of undefined (reading 'register')`). |
| AssetMapper | `importmap.php`: `'@idct/adminata' => ['path' => '@idct/adminata/app.esm.js']` (path registered by the extension, `R/js-assets.md` §9 item 2); `assets/bootstrap.js`: `import { startStimulusApp } from '@symfony/stimulus-bundle'; import { startAdminata } from '@idct/adminata'; const app = startStimulusApp(); startAdminata({ application: app });`. |
| Vite | same as Encore with an alias to `vendor/idct/adminata/assets/js/app.esm.js`. |

`app.esm.js` never imports jQuery, Tom Select CSS, or Tailwind CSS; CSS stays in `bundles/sonataadmin/app.css` (or the app's own build importing `assets/css/*.css`, G2).

### 4.3 `symfony/ux-autocomplete` (Tom Select owned by another controller)

Facts: the real app sets `use_select2: false # DO NOT TURN ON!` (`APP/config/packages/sonata_admin.yaml:113`) precisely because select2 would double-enhance ux-autocomplete's `<select>`s; it skins Tom Select itself (`APP/assets/styles/sonata-overrides.scss:68-104`: `body .ts-wrapper.single/.multi .ts-control`, `.ts-wrapper.form-control`, `.ts-dropdown`) and auto-imports `tom-select.default.css` (`APP/assets/controllers.json:8`).

| Rule | Detail |
|---|---|
| **R5 skip list** | `sonata-select` (controller and facade tagging) never touches: `select[data-sonata-select2="false"]`; any element whose `data-controller` contains `autocomplete` (ux-autocomplete's `symfony--ux-autocomplete--autocomplete`, `AutocompleteChoiceTypeExtension.php:57-58`; adminata's own `sonata-autocomplete`); any element with `el.tomselect` set (Tom Select's instance marker — the final guard against any third-party Tom Select); `select.per-page` when `use_select2` is off. The form theme applies the same rule at render time (`attr['data-controller']` containing `autocomplete` → no `sonata-select`). |
| **R6 one Tom Select copy** | adminata bundles Tom Select inside `app.js`; the app bundles another for ux-autocomplete. Two copies coexist (no globals); CSS is shared because both emit the same `.ts-*` classes. The ESM path lets an app dedupe by aliasing `tom-select` (documented, optional). |
| **R7 CSS selectors** | adminata's Tom Select theme is written in `@layer components` against exactly `.ts-wrapper`, `.ts-control`, `.ts-dropdown`, `.ts-dropdown-content`, `.ts-wrapper.single`, `.ts-wrapper.multi`, `.ts-wrapper.has-items`, `.ts-wrapper.focus`, `.ts-wrapper.dropdown-active`, `.ts-wrapper .item`, `.ts-wrapper .option`, `.ts-wrapper .active`, `.plugin-remove_button .item .remove`, `.plugin-clear_button .clear-button`, `.ts-dropdown .no-results`, `.ts-dropdown .spinner` (the hooks of `tom-select.default.css`), with specificity ≤ (0,2,0), so the app's `body .ts-wrapper …` (0,3,0) overrides and its later-loaded `build/app.css` (`extra_stylesheets`) win. Dark mode via the `.dark` variant on the same selectors. |
| **R8 `<dialog>`** | Tom Select's default `dropdownParent` is `null` (inline) — correct inside `<dialog>`. adminata sets `'body'` only when the select is *not* inside a dialog and *is* inside an `overflow` clipping ancestor (tables in cards); ux-autocomplete fields inside adminata modals keep the default and work; apps that set `tom_select_options: {dropdownParent: 'body'}` on a field rendered inside an adminata modal will see the dropdown under the top layer — documented. |
| **R9 events** | ux-autocomplete's `autocomplete:pre-connect/connect` and adminata's `sonata-select:*`/`sonata-autocomplete:*` are distinct; user code may listen to both. adminata never dispatches `autocomplete:*`. |
| **R10 `ChoiceFieldMaskType` fallback** | `#{main}_{field}_autocomplete_input` (`form_admin_fields.html.twig:492-494`) only exists for adminata's autocomplete; ux-autocomplete fields are found by the earlier `#sonata-ba-field-container-…` lookups — unchanged. |

### 4.4 form-extensions' `datepicker` controller still loaded

| Situation | What happens | Rule |
|---|---|---|
| Default adminata config | `bundles/sonataform/app.{js,css}` are **not** in the defaults; adminata's `Form/datepicker.html.twig` renders `sonata-datepicker` (flatpickr). Form-extensions' PHP types and view vars are untouched (G5). | — |
| App re-adds `bundles/sonataform/app.js` via `javascripts`/`extra_javascripts` (after `bundles/sonataadmin/app.js`) | `sonataApplication.register('datepicker', DatePicker)` succeeds (`FE/assets/js/app.js:16-18`); identifier `datepicker` ≠ `sonata-datepicker`, so admin forms keep flatpickr; non-admin forms rendered with SonataForm's global theme (`SonataFormExtension` prepends `@SonataForm/Form/datepicker.html.twig` to `twig.form_themes`) get Tempus Dominus. `sonataform/app.css` (Bootstrap-flavoured, `FE/assets/scss/app.scss:10`) may restyle `.input-group` — the compat layer scopes its own rules to `.sonata-bc` (G2). | **R11** Never name any adminata controller `datepicker`; never `unload('datepicker')`. |
| App re-adds it **before** adminata's script or on the ESM path before `startAdminata` ran | `TypeError` in `FE/assets/js/app.js:18` (`sonataApplication` undefined). Same failure exists in Sonata 4.43 if the order is wrong. | **R12** UPGRADE note: keep `bundles/sonataadmin/app.js` (or the ESM `startAdminata` call) before `bundles/sonataform/app.js`. |
| Both `datepicker` and `sonata-datepicker` on one element | Cannot happen from adminata templates; a user theme could add `data-controller="datepicker"` to a field rendered by adminata's block → two pickers. | Documented as unsupported. |
| User code listening to `datepicker:connect` (`FE/…/datepicker_controller.js:139-140`) | Not fired for adminata pickers. | UPGRADE: rename to `sonata-datepicker:connect`; `detail.flatpickr` instead of `detail.datePicker`. |

---

## 5. Build entries, registration code, contract tests

### 5.1 Entry layout

```
assets/
  js/
    app.js                 # IIFE entry → src/Resources/public/app.js (globals; blocking <head> script)
    app.esm.js             # ES entry   → src/Resources/public/app.esm.js (no side effects; exports startAdminata)
    vendor/jquery-entry.js # IIFE entry → src/Resources/public/vendor/jquery.js (window.$ / window.jQuery only)
    registry.js            # explicit Stimulus definitions (below)
    admin.js               # window.Admin facade (16 members) — no jQuery
    adminata.js            # window.adminata namespace helpers
    core/{config,translation,utils,dom}.js
    compat/{jquery-bridge,bootstrap-data-api}.js
    controllers/*_controller.js   # 9 kept + 18 new (§1)
    __contract__/controllers.json # committed snapshot (§5.4)
  css/…                    # G2
src/Resources/public/
  app.js  app.esm.js  app.css  compat-bootstrap3.css  vendor/jquery.js  fonts/  images/  entrypoints.json  manifest.json
```

`assets/js/app.js`:

```js
import '../css/app.css';                       // Tailwind entry (Vite emits app.css)
import * as stimulus from '@hotwired/stimulus';
import { startAdminata } from './app.esm';
globalThis.stimulus = stimulus;                // S/assets/js/app.js:53
startAdminata({ application: stimulus.Application.start() });   // sets window.sonataApplication synchronously, before bundles/sonataform/app.js can run
```

`assets/js/vendor/jquery-entry.js`:

```js
import $ from 'jquery';
globalThis.$ = globalThis.jQuery = $;          // S/assets/js/app.js:51-52 — nothing else, no plugins
```

Vite: two configs (`vite.config.js` reads `mode`): `iife` build with `rollupOptions.input = { app: 'assets/js/app.js', 'vendor/jquery': 'assets/js/vendor/jquery-entry.js' }`, `output.entryFileNames: '[name].js'`, `format: 'iife'`, `cssCodeSplit: false`, `assetFileNames` → `app.css`/`fonts/[name][extname]`; `es` build with `lib.entry = 'assets/js/app.esm.js'`, `formats: ['es']`, `fileName: 'app.esm'`, `rollupOptions.external: []` (self-contained; Stimulus is bundled so `instanceof Controller` checks are not an issue — adminata never does them). `define: { __ADMINATA_VERSION__: JSON.stringify(pkg.version) }`. Node ≥ 22. CI gate: `npm ci && npm run build && git diff --exit-code -- src/Resources/public` (`S/.github/workflows/frontend.yaml:46-49`) plus `size-limit` budgets (`app.js` ≤ 220 KB with Tom Select + SortableJS + flatpickr; `vendor/jquery.js` ≈ 87 KB).

### 5.2 Registration (`assets/js/registry.js`)

```js
import CollectionController from './controllers/collection_controller';
import ConfirmExitController from './controllers/confirm_exit_controller';
import EditController from './controllers/edit_controller';
import FilterController from './controllers/filter_controller';
import FilterListController from './controllers/filter_list_controller';
import PerPageController from './controllers/per_page_controller';
import ReadmoreController from './controllers/readmore_controller';
import RevisionController from './controllers/revision_controller';
import StickyController from './controllers/sticky_controller';
import LayoutController from './controllers/layout_controller';
import MenuController from './controllers/menu_controller';
import DropdownController from './controllers/dropdown_controller';
import ModalController from './controllers/modal_controller';
import TabsController from './controllers/tabs_controller';
import ThemeController from './controllers/theme_controller';
import DismissController from './controllers/dismiss_controller';
import SearchShortcutController from './controllers/search_shortcut_controller';
import BatchController from './controllers/batch_controller';
import EditableController from './controllers/editable_controller';
import SortableController from './controllers/sortable_controller';
import SelectController from './controllers/select_controller';
import AutocompleteController from './controllers/autocomplete_controller';
import AssociationController from './controllers/association_controller';
import ChoiceFieldMaskController from './controllers/choice_field_mask_controller';
import InlineRowController from './controllers/inline_row_controller';
import TreeviewController from './controllers/treeview_controller';
import DatepickerController from './controllers/datepicker_controller';

/** @type {Array<{identifier: string, controllerConstructor: typeof import('@hotwired/stimulus').Controller}>} */
export const definitions = [
  ['sonata-collection', CollectionController],
  ['sonata-confirm-exit', ConfirmExitController],
  ['sonata-edit', EditController],
  ['sonata-filter', FilterController],
  ['sonata-filter-list', FilterListController],
  ['sonata-per-page', PerPageController],
  ['sonata-readmore', ReadmoreController],
  ['sonata-revision', RevisionController],
  ['sonata-sticky', StickyController],
  ['sonata-layout', LayoutController],
  ['sonata-menu', MenuController],
  ['sonata-dropdown', DropdownController],
  ['sonata-modal', ModalController],
  ['sonata-tabs', TabsController],
  ['sonata-theme', ThemeController],
  ['sonata-dismiss', DismissController],
  ['sonata-search-shortcut', SearchShortcutController],
  ['sonata-batch', BatchController],
  ['sonata-editable', EditableController],
  ['sonata-sortable', SortableController],
  ['sonata-select', SelectController],
  ['sonata-autocomplete', AutocompleteController],
  ['sonata-association', AssociationController],
  ['sonata-choice-field-mask', ChoiceFieldMaskController],
  ['sonata-inline-row', InlineRowController],
  ['sonata-treeview', TreeviewController],
  ['sonata-datepicker', DatepickerController],   // never 'datepicker' (V14)
].map(([identifier, controllerConstructor]) => ({ identifier, controllerConstructor }));
```

`application.load(definitions)` is used instead of 27 `register()` calls (same code path, `STIM:2121-2133`). No lazy loading: every controller is small and the page is server-rendered; the `@symfony/stimulus-bridge/lazy-controller-loader` used by `S/assets/js/stimulus.js:19` and `FE/assets/js/app.js:14` is Webpack-only and dropped.

### 5.3 Vitest layout

`vite.config.js` `test` block mirrors `FE/vite.config.js:13-27` (jsdom, `setupFiles` with `@testing-library/jest-dom` matchers and the `matchMedia` mock from `FE/assets/js/setup.test.js:10-27`; add `ResizeObserver`/`IntersectionObserver`/`HTMLDialogElement.prototype.showModal` polyfills for jsdom). Fixtures: HTML rendered from the real Twig templates by a PHPUnit dumper into `tests/fixtures/js/*.html` (`R/js-assets.md` §10 item 1).

### 5.4 Contract-snapshot test (`assets/js/__contract__/contract.test.js`)

```js
import { describe, it, expect } from 'vitest';
import { definitions } from '../registry';
import { Admin } from '../admin';

const describeController = ({ identifier, controllerConstructor: C }) => ({
  identifier,
  targets: [...(C.targets ?? [])].sort(),
  values: Object.fromEntries(Object.entries(C.values ?? {}).map(([k, v]) => [k, typeof v === 'function' ? v.name : { type: v.type.name, default: v.default }])),
  classes: [...(C.classes ?? [])].sort(),
  outlets: [...(C.outlets ?? [])].sort(),
  events: [...(C.events ?? [])].sort(),                 // adminata convention: static events = [...]
  actions: [...(C.actions ?? [])].sort(),               // adminata convention: static actions = ['toggle', 'close', …] (public action methods)
  shouldLoad: Object.getOwnPropertyDescriptor(C, 'shouldLoad') !== undefined,
});

describe('adminata JS contract', () => {
  it('controller registry', async () => {
    const snapshot = definitions.map(describeController);
    await expect(JSON.stringify(snapshot, null, 2)).toMatchFileSnapshot('./controllers.json');
    expect(new Set(snapshot.map((c) => c.identifier)).size).toBe(snapshot.length);
    expect(snapshot.every((c) => c.identifier.startsWith('sonata-'))).toBe(true);
    expect(snapshot.map((c) => c.identifier)).not.toContain('datepicker');
    for (const c of snapshot) for (const a of c.actions) expect(typeof definitions.find((d) => d.identifier === c.identifier).controllerConstructor.prototype[a]).toBe('function');
  });

  it('window.Admin facade has exactly the 16 Sonata 4.43 members', () => {
    expect(Object.keys(Admin).sort()).toEqual([
      'get_config', 'get_select2_width', 'get_translations', 'log', 'setup_checkbox_range_selection',
      'setup_icheck', 'setup_inline_form_errors', 'setup_list_modal', 'setup_readmore_elements', 'setup_select2',
      'setup_sortable_select2', 'setup_sticky_elements', 'setup_tree_view', 'setup_xeditable', 'shared_setup',
      'switch_inline_form_errors',
    ]);
  });

  it('every member accepts document, an Element, a selector and a jQuery-like object', () => {
    const fake = { jquery: '3.7.1', 0: document.body, length: 1 };
    for (const name of ['shared_setup', 'setup_select2', 'setup_icheck', 'setup_checkbox_range_selection', 'setup_xeditable', 'setup_inline_form_errors', 'setup_tree_view']) {
      for (const subject of [undefined, document, document.body, 'body', fake]) expect(() => Admin[name](subject)).not.toThrow();
    }
  });
});
```

A second test executes the **built** `src/Resources/public/app.js` and `vendor/jquery.js` in jsdom (`vm.runInContext` with a `window`) and asserts: `window.Admin`, `window.sonataApplication`, `window.stimulus`, `window.adminata.version` exist; `[...window.sonataApplication.router.modulesByIdentifier.keys()]` equals the snapshot identifiers (minus those whose `shouldLoad` returned false for the fixture meta); `window.jQuery` is **undefined** after `app.js` alone and defined after `vendor/jquery.js`; a jQuery-triggered `sonata-admin-append-form-element` reaches a native listener exactly once, and a native dispatch reaches a jQuery listener exactly once (bridge + guard); the legacy event names table (§3.2) is asserted from a committed `events.json`. A Twig-side guard (`make lint-stimulus`) greps every `stimulus_controller('...')` / `data-controller="..."` token in `src/Resources/views` and fails on identifiers absent from `controllers.json`.

---

## 6. User JS that will break — risk table with `UPGRADE-1.0.md` wording

| # | What breaks | Where users have it | Severity | `UPGRADE-1.0.md` wording |
|---|---|---|---|---|
| 1 | `$(el).select2(...)`, `.select2('data')`, `select2:select/unselect` events, `select2-locale/*.js`, `.select2-container` CSS | custom form themes, `sonata_type_model_autocomplete_select2_options_js` overrides, `recipe_select2.rst` users | High | "select2 is no longer bundled. Plain `<select>` widgets are enhanced with **Tom Select** when `sonata_admin.options.use_select2` is `true`; the `data-sonata-select2*` attributes keep their meaning. `$.fn.select2` does not exist. Read the instance via `element.tomselect` or listen to `sonata-select:connect` (`event.detail.tomSelect`). The `sonata_type_model_autocomplete_select2_options_js` block now receives a Tom Select configuration object. `bundles/sonataadmin/select2-locale/*.js` files are gone; remove any `<script>` referencing them." |
| 2 | iCheck: `.iCheck('check')`, `ifChanged`/`ifChecked`/`ifUnchecked`, `.iCheck-helper`, `icheckbox_square-blue` | copied `batch_javascript` blocks (`base_list.html.twig:153-180`), `recipe_icheck.rst` users | High | "iCheck is removed. Checkboxes and radios are native inputs styled by CSS. Replace `ifChanged`/`ifChecked` listeners with the native `change` event and `.iCheck('check')` with `input.checked = true; input.dispatchEvent(new Event('change', {bubbles: true}))`. `use_icheck`, `data-sonata-icheck="false"` and `Admin.setup_icheck()` are accepted and ignored (deprecated, removed in 2.0)." |
| 3 | Bootstrap 3 JS: `$('#x').modal()`, `.modal('hide')`, `.tab('show')`, `.dropdown()`, `.collapse()`, `.popover()`, `.tooltip()`, `.alert('close')`; events `shown.bs.*` | `APP/assets/admin/UniversalModal.js:8,18`, copied association scripts, user dashboards | High | "Bootstrap 3 JavaScript is removed. Sonata's own modals are native `<dialog>` elements driven by the `sonata-modal` controller (`dialog.showModal()` / `dialog.close()`); dropdowns, tabs and alerts use the `sonata-dropdown`, `sonata-tabs`, `sonata-dismiss` controllers. Markup that still carries `data-toggle="dropdown|tab|modal|collapse"` or `data-dismiss="alert|modal"` keeps working through a compatibility delegate. `$.fn.modal` and friends are **not** provided: replace `$('#universal-modal').modal()` with `document.getElementById('universal-modal').showModal()` after turning the element into a `<dialog>`, or ship Bootstrap's JS yourself via `extra_javascripts` (it will operate on the compat-styled `.modal` markup)." |
| 4 | x-editable: `.editable(...)`, `editable-*` classes, `save`/`shown` events | custom list field templates | Medium | "x-editable is replaced by the `sonata-editable` controller. The `x-editable` class and `data-type/value/title/pk/url/source/format` attributes are unchanged and remain the way to declare inline-editable cells; `data-source` must be JSON. Listen to `sonata-editable:saved` instead of x-editable events." |
| 5 | jQuery UI sortable: `.sortable(...)`, `sortable('refresh')`, `tbody.ui-sortable` | copied sortable scripts, `recipe_jquery_ui.rst`/`recipe_sortable_*` users | Medium | "jQuery UI is removed (Sonata only bundled its `sortable` widget). Sortable collections use **SortableJS** through the `sonata-sortable` controller. If your own code used `$.fn.sortable`, import `jquery-ui` yourself (`extra_javascripts`, after `bundles/sonataadmin/vendor/jquery.js`) — including `jquery-ui/ui/widget`, which Sonata used to provide." |
| 6 | `jquery-form`: `$(form).ajaxSubmit(...)`, `$(document).ajaxSuccess(...)` | copied `edit_many_script`, integrations that watched the autocomplete's `ajaxSuccess` hook (`sonata_type_model_autocomplete.html.twig:233`) | Medium | "`jquery-form` is removed and adminata uses `fetch()`; jQuery global AJAX events (`ajaxSuccess`, `ajaxComplete`) are therefore never fired by adminata. Listen to `sonata-association:created` (`detail: {fieldId, objectId, objectName, createUrl}`) to react to objects created from a modal." |
| 7 | `$.fn.treeView`, `data-treeview-*` init by hand | SonataPage/Classification-style tree templates | Low | "`$.fn.treeView` is removed. `ul.js-treeview` elements are handled by the `sonata-treeview` controller; `Admin.setup_tree_view(subject)` still enables it on AJAX-loaded fragments." |
| 8 | `jquery.scrollto`, `jquery-slimscroll`, `masonry-layout`, `data-masonry` | SonataPage/Article/Dashboard (3.x era), search page overrides | Low | "`jquery.scrollTo`, `jquery-slimscroll` and Masonry are no longer bundled; `data-masonry` attributes are ignored (search results use CSS columns). Add these libraries via `extra_javascripts` if your own templates need them." |
| 9 | jQuery itself absent when the app removes `vendor/jquery.js` but still has `.addExternals({jquery:'jQuery'})` | `APP/webpack.config.js:16` | High (only if removed) | "jQuery 3.7 is still shipped, as a separate file `bundles/sonataadmin/vendor/jquery.js` listed by default in `sonata_admin.assets.javascripts`. adminata itself never uses it. It is **deprecated** and will be removed in adminata 2.0: bundle your own copy before then. To drop it now: `sonata_admin.assets.remove_javascripts: ['bundles/sonataadmin/vendor/jquery.js']` — check that nothing in `extra_javascripts` or your Webpack `externals` still expects `window.jQuery`." |
| 10 | Undocumented globals from association scripts: `field_dialog_{id}`, `field_dialog_content_{id}`, `initialize_popup_{id}()`, `field_dialog_form_*_{id}`, `apply_position_value_{id}()`, `field_widget_{id}` | apps that poke the modal from custom scripts | Low | "The per-field helper variables and functions defined by `edit_many_script.html.twig`, `edit_one_script.html.twig` and the sortable scripts (`field_dialog_<id>`, `initialize_popup_<id>()`, `apply_position_value_<id>()`, …) no longer exist. The `onclick` entry points `start_field_dialog_form_add_<id>()`, `start_field_dialog_form_edit_<id>()`, `start_field_dialog_form_list_<id>()`, `remove_selected_element_<id>()` and `start_field_retrieve_<id>()` are kept as thin wrappers (deprecated, removed in 2.0). Use `adminata.association('<id>')` to obtain the `sonata-association` controller." |
| 11 | jQuery `.trigger('sonata-admin-append-form-element')` expected to reach adminata | copied `edit_many_script.html.twig:341` | Low | "adminata listens to native DOM events. If your code triggers Sonata events with jQuery (`$(el).trigger('sonata-admin-append-form-element')`), they are forwarded to native listeners automatically while `bundles/sonataadmin/vendor/jquery.js` is loaded; otherwise dispatch `el.dispatchEvent(new CustomEvent('sonata-admin-append-form-element', {bubbles: true}))`." |
| 12 | `Admin.setup_list_modal($modal)` restyling `.modal-dialog` with inline styles | custom modal templates | Low | "`Admin.setup_list_modal()` no longer sets inline sizes; it asks the `sonata-modal` controller for the wide 'list' size (or adds the `modal-list` class on legacy markup) and dispatches `sonata-admin-setup-list-modal` as a native event." |
| 13 | `Admin.setup_sortable_select2($el, data, {theme:'bootstrap', …})` passing select2 options | overrides of `sonata_type_choice_multiple_sortable` | Low | "`Admin.setup_sortable_select2(subject, data, options)` keeps its signature but `options` is passed to Tom Select; select2-specific keys (`theme`, `dropdownAutoWidth`, `width`) are ignored." |
| 14 | `edit_controller`-style code calling `jQuery(tab).tab('show')` in custom edit templates | copied `base_edit_form.html.twig` | Low | "Tabs are switched by dispatching `tab.dispatchEvent(new CustomEvent('sonata-tabs:show', {bubbles: true}))` or by calling `adminata.controller(tabsRoot, 'sonata-tabs').show(tab)`." |
| 15 | form-extensions `datepicker:*` events / `detail.datePicker` (Tempus Dominus) on admin forms | apps that customised Sonata date pickers via JS | Medium | "Admin forms render date pickers with **flatpickr** through the `sonata-datepicker` controller. The `datepicker` (Tempus Dominus) controller from `sonata-project/form-extensions` is no longer loaded by default (`bundles/sonataform/app.js` / `app.css` were removed from `sonata_admin.assets.*` defaults). Rename listeners to `sonata-datepicker:connect` (`event.detail.flatpickr`). If you re-add `bundles/sonataform/app.js`, list it **after** `bundles/sonataadmin/app.js` (it requires `window.sonataApplication`)." |
| 16 | Two Stimulus applications registering `sonata-*` twice | apps that copied Sonata controllers into their own `assets/controllers/` | Low | "Do not register controllers whose identifier starts with `sonata-` in your own Stimulus application while `bundles/sonataadmin/app.js` is loaded — the element would get two instances. To run a single application, remove `bundles/sonataadmin/app.js` and call `startAdminata({ application })` from `@idct/adminata`." |
| 17 | `Alpine`/`x-data` markup pasted from TailAdmin into overridden blocks | new users | Low | "adminata does not ship Alpine.js. TailAdmin snippets that use `x-data` need Alpine added through `sonata_admin.assets.extra_javascripts` (and a CSP that allows it), or should be rewritten with the `sonata-dropdown`/`sonata-modal`/`sonata-tabs` controllers." |
| 18 | `use_bootlint` inline script; `window.opera` logging | — | None | "`use_bootlint` is accepted and ignored; the `bootlint` block renders nothing." |

---

## 7. Open questions for the project owner

1. **Cookie attributes** for `sonata_sidebar_hide`/`sonata_theme` (`SameSite=Lax` + `Secure` vs `__Host-` prefix, which would change the cookie *name* users may read server-side) — G6 decides; the controllers only need the final string.
2. **`onclick` shims in 1.x**: keep both `onclick` and `data-action` on adminata's own association buttons (this report's choice, so overridden user templates and CSP-strict apps are both served), or drop `onclick` from adminata templates immediately and keep only the global functions for user templates?
3. **`sonata-tooltip`**: confirmed out of 1.0? (No Sonata template needs it; the real app's `title` attributes are native.)
4. **Tom Select on `<input type="hidden">`** for `setup_sortable_select2` must be verified against 2.5.2 during implementation; the fallback (switch to `type="text"` + `hidden` wrapper) changes nothing observable but should be agreed.
5. **`@idct/adminata` npm distribution**: `assets/package.json` for `file:vendor/idct/adminata/assets` installs (zero-publish, like ux-autocomplete) only, or also publish to npm for Vite/importmap users?
6. **`sonata:ready` + `defer`** are prepared here as the 2.x path; should 1.x already document `defer` as opt-in for apps with no inline scripts?
