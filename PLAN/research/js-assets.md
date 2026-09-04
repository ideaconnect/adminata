# adminata research — JavaScript, CSS, asset pipeline, third-party libraries, Tailwind v4 build strategy

Dimension: everything under `assets/`, `src/Resources/public/`, `src/Asset/`, the JS-related Twig (inline `<script>`, Stimulus attributes, `sonata-config` bootstrap), the `sonata_admin.assets` / `sonata_admin.options` config, and the TailAdmin front-end stack — mapped onto a Tailwind v4 + TailAdmin rebuild that stays drop-in compatible with Sonata Admin 4.43.0.

Path prefixes used below (all relative to the scratchpad root unless absolute):

- `S` = `sonata-admin-4.43.0`
- `S5` = `sonata-admin-5.x`
- `T` = `tailadmin-html`
- `TR` = `tailadmin-react`, `TN` = `tailadmin-next`
- `FE` = `vendor-extract/form-extensions`, `ORM` = `vendor-extract/doctrine-orm-admin-bundle/sonata-project-SonataDoctrineORMAdminBundle-2147390`, `BB` = `vendor-extract/block-bundle`
- `M` = `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle`

---

## 0. Ground truth (verified by reading, not assumed)

| Fact | Evidence |
|---|---|
| `S5/assets`, `S5/package.json`, `S5/webpack.config.js` are byte-identical to 4.43.0; `S5/src/Resources/public/app.{js,css}` are the same size (476K / 340K). The 5.x branch brings nothing new on the front-end. | `diff -rq S/assets S5/assets` → empty; `diff S/package.json S5/package.json` → empty |
| Sonata's JS is one Webpack Encore entry (`app`) that bundles jQuery 3.7, jquery.scrollto, jQuery UI widget+sortable, Bootstrap 3 JS, jquery-form, x-editable (bootstrap3 build), select2 *full*, AdminLTE 2.4 JS, iCheck, slimscroll, masonry, then Sonata's own `admin.js`, `treeview.js`, `sidebar.js`, `base.js`, and Stimulus. It then exposes `global.$`, `global.jQuery`, `global.stimulus`, `global.sonataApplication`. | `S/assets/js/app.js:11-54` |
| The CSS is one SCSS entry that `@import`s Bootstrap 3 CSS, Font Awesome 5 `all.css` + `v4-shims.css`, seven Source Sans Pro `@fontsource` faces, `AdminLTE-without-plugins.css`, iCheck square-blue skin, jQuery UI `sortable.css`, `select2.css`, `select2-bootstrap.css`, x-editable bootstrap3 CSS, and six Sonata partials. | `S/assets/scss/app.scss:10-47` |
| Prebuilt, committed output: `app.js` 485,088 B (476K), `app.css` 345,335 B (340K, 53 `@font-face` rules), `fonts/` 2.1M (72 files: FA5 eot/ttf/woff/woff2, glyphicons, Source Sans Pro), `images/` 2.0M (FA SVG fonts, glyphicons SVG, iCheck `blue.png`, `ajax-loader.gif`, `default_mosaic_image.png`, `logo_title.png`, select2 `loading.gif`), `select2-locale/` 240K (59 `*.js`), `admin-lte-skins/` 56K (12 `skin-*.min.css`), `entrypoints.json`, `manifest.json` keyed `bundles/sonataadmin/...`. | `du -sh S/src/Resources/public/*`; `S/src/Resources/public/entrypoints.json`; `S/src/Resources/public/manifest.json` |
| The dist tarball strips `package.json`, `package-lock.json`, `postcss.config.js`, `prettier.config.js`, `webpack.config.js` (`export-ignore`) but ships `src/Resources/public`. Apps never build Sonata's assets; `bin/console assets:install` copies them to `public/bundles/sonataadmin`. | `S/.gitattributes:16-20`; `S/docs/getting_started/installation.rst:130-133` |
| CI builds with Node 20, runs eslint / stylelint / prettier / `encore production`, then fails if `git diff` is non-empty ⇒ the committed build must be reproducible. | `S/.github/workflows/frontend.yaml:30-49` |
| There are **no JS tests** in Sonata Admin. `form-extensions` has Vitest (jsdom, `@testing-library/dom`, `@testing-library/user-event`) for its datepicker controller. | `S/package.json` (no test script); `FE/vite.config.js:13-27`; `FE/assets/js/controllers/datepicker_controller.test.js:10-14` |
| `sonata-project/form-extensions` **depends on Sonata's globals**: its `app.js` does `const { sonataApplication } = global; sonataApplication.register('datepicker', DatePicker);` — so `window.sonataApplication` must survive, and its controller identifier is `datepicker` (un-prefixed). | `FE/assets/js/app.js:14-18`; `FE/src/Bridge/Symfony/Resources/views/Form/datepicker.html.twig:16-18` |
| TailAdmin free HTML v2.3.0: Tailwind `^4.0.0` via `@tailwindcss/postcss`, Alpine `^3.14.1` + `@alpinejs/persist`, flatpickr, apexcharts, chart.js, dropzone, fullcalendar, jsvectormap, swiper; built with plain Webpack 5 + `html-loader` `<include>` preprocessor. | `T/package.json:21-53`; `T/webpack.config.js`; `T/postcss.config.js` |
| TailAdmin `style.css` is 751 lines: Google-Fonts `@import` of Outfit (line 1), `@import "tailwindcss"` (4), `@custom-variant dark (&:is(.dark *))` (6), `@theme` (8-166), a v3 border-color compat `@layer base` (176-188), `@utility menu-item*` family (190-244), `no-scrollbar`/`custom-scrollbar` (246-271), `body` base (273-277), `.sidebar:hover` collapsed-sidebar hover rules (290-319), `.tableCheckbox` (321-326), then third-party overrides for apexcharts (329-375), jsvectormap (377-385), swiper (387-405, 677-723), flatpickr (407-535), fullcalendar (537-669), misc checkbox/task rules (726-751). | `T/src/css/style.css` |
| TailAdmin wires page state with **inline** Alpine expressions: `<body x-data="{ page:…, darkMode:false, sidebarToggle:false … }" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', …)" :class="{'dark bg-gray-900': darkMode === true}">`. The multi-select demo uses an inline `<script>function dropdown(){…}</script>` at the end of the page. | `T/src/index.html:14-18`; `T/src/form-elements.html:1522-1576` |

---

## 1. Inventory of Sonata's front-end source

### 1.1 `assets/js/*`

| File | Lines | What it does | Notes for adminata |
|---|---|---|---|
| `app.js` | 54 | Imports SCSS + all vendor libs + Sonata modules; sets `global.$`, `global.jQuery`, `global.stimulus`, `global.sonataApplication`. | `S/assets/js/app.js:11-54`. The **only** entry. |
| `admin.js` | 390 | Defines `window.Admin` object (§4.1) and three DOM-ready hooks: `jQuery('html').removeClass('no-js'); Admin.shared_setup(document)` (375-379), a `sonata-admin-append-form-element` listener that re-runs select2 + iCheck on the event target (381-384), and a hack that forces a native `change` after select2's `change.select2` on `select.per-page` (386-390). | Contains a real bug: `indexedDB > currentIndex` at line 173 (should be `index`), i.e. the shift-range selection only works top-down. |
| `base.js` | 36 | `SonataCore`: destroys iCheck on `.read-more-state` inputs and toggles `.more/.less` labels with `.hide` inside flash messages rendered by SonataTwig. | `S/assets/js/base.js:10-36`. Depends on markup from `@SonataTwig/FlashMessage/render.html.twig` (included at `S/src/Resources/views/standard_layout.html.twig:297`). |
| `sidebar.js` | 20 | On `.sidebar-toggle` click flips cookie `sonata_sidebar_hide=1/0;path=/`. The server reads it to add `sidebar-collapse` on `<body>`. | `S/assets/js/sidebar.js:10-20`; `standard_layout.html.twig:96-98`. |
| `treeview.js` | 100 | jQuery plugin `$.fn.treeView` for `ul.js-treeview` (CRUD tree page): togglers `[data-treeview-toggler]`, states `is-toggled`/`is-active`, `[data-treeview-toggled]`, `data-treeview-instance`. Uses `slideToggle`. | `S/assets/js/treeview.js:10-100`; consumed by `Admin.setup_tree_view` (`admin.js:262-266`). |
| `stimulus.js` | 29 | `startStimulusApp()` from `@symfony/stimulus-bridge`; loads all `controllers/*` lazily via `require.context` and **prefixes every identifier with `sonata-`** (`definition.identifier = \`sonata-${…}\``). | `S/assets/js/stimulus.js:11-29`. Webpack-only (`require.context`, lazy-controller-loader). |
| `controllers.json` | 4 | Empty (`controllers: [], entrypoints: []`). No Symfony UX packages. | |
| `core/config.js` | 34 | `Config.param(key)` lazily parses `<meta name="sonata-config">` JSON. | `S/assets/js/core/config.js:15-31` |
| `core/translation.js` | 34 | `Translation.trans(key)` from `<meta name="sonata-translations">`. | `S/assets/js/core/translation.js:15-31` |
| `core/utils.js` | 88 | `wrap`, `activateScriptElement` (re-executes `<script>` in collection prototypes), `createDocumentFragment`, `getMetaContent`, `controlReset`, `controlValue`, `convertQueryStringToObject`. | Pure DOM, framework-free; keep verbatim. |

### 1.2 Stimulus controllers (9) — identifier is `sonata-<file>` (stimulus.js:26)

| Identifier | Targets | Values / classes / outlets | Actions used in Twig | Behaviour | Framework deps |
|---|---|---|---|---|---|
| `sonata-collection` (`collection_controller.js`) | `item` | value `numItems:Number` | `add`, `delete` (`form_admin_fields.html.twig:378,410`) | Clones `data-prototype` replacing `data-prototype-name` (`__name__`) with index, re-activates `<script>` tags, inserts before the add button, dispatches **native** `sonata-admin-append-form-element`, `sonata-collection-item-added`, `sonata-collection-item-deleted`, `sonata-collection-item-deleted-successful` (prefix `''`). | none |
| `sonata-confirm-exit` | — | values `snapshot:String`, `skip:Boolean`; `static shouldLoad = Config.param('CONFIRM_EXIT')` | `confirm` on `beforeunload@window`, `skip` on `submit` (`base_edit_form.html.twig:23-24`) | Serialises `FormData` on connect; on unload compares; message from `Translation.trans('CONFIRM_EXIT')`. | none |
| `sonata-edit` | `tab`, `tabStore` | — | `prepareSubmit` on submit, `checkValidity:capture` on invalid, `changeTab` on click (`base_edit_form.html.twig:25-26,51`) | Shows first tab with `.sonata-ba-field-error` / `:invalid`, toggles `.has-errors` icon, disables all `button`s after submit, stores `aria-controls` into hidden `_tab` input, pushes `?_tab=` to history. **Calls `jQuery(tab).tab('show')` (line 49) and detects the active tab via `tab.parentElement.classList.contains('active')` (line 78)** — the only jQuery/Bootstrap coupling inside the controllers. | jQuery + Bootstrap tab |
| `sonata-filter` | `form`, `group`, `advanced`, `submitter` | value `defaultValues:Object`; outlet `sonata-filter-list` | `prepareSubmit` submit, `toggleAdvanced` click, `hideFilter` click with param `id` (`base_list.html.twig:300-373`) | Strips unchanged filters from submission (removes `name`), converts empty multi-selects to empty hidden inputs, adds `filters=reset` when nothing changed, hides hidden groups. Uses `qs.stringify` to flatten `defaultValues`. | `qs` |
| `sonata-filter-list` | `counter`, `field` | class `active`; outlet `sonata-filter` | `toggle` click (`base_list.html.twig:275`) | Counter badge + toggling filter visibility through the outlet; `data-filter` attribute on fields. | none |
| `sonata-per-page` | — | — | `reload` on change (`Pager/base_results.html.twig:24`) | Disables submit buttons and navigates `window.top.location.href` to the selected option value. | none (but `admin.js:386-390` hacks select2's event) |
| `sonata-readmore` | `content`, `button` | values `collapsedHeight:Number`, `moreText:String`, `lessText:String` | `toggle` click (`base_list_field.html.twig:25-34`, `base_show_field.html.twig:27-40`) | `ResizeObserver` adds `.truncated`; toggle `.expanded`; CSS in `readmore.scss`. | none |
| `sonata-revision` | `preview` | — | `showPreview:prevent:stop` (`base_history.html.twig:19-54`) | `fetch()` with `X-Requested-With: XMLHttpRequest`, injects HTML. | none |
| `sonata-sticky` | `topNavbar`, `navbar`, `action` | `static shouldLoad = Config.param('USE_STICKYFORMS')` | controller on `<body>` (`standard_layout.html.twig:99`), targets at `:125,:232`, `base_edit_form.html.twig:100` | Wraps targets in `.navbar-sentinel`/`.action-sentinel`, `IntersectionObserver` toggles `.stuck`; CSS `layout.scss:385-411`. | none |

### 1.3 `assets/scss/*`

| File | Lines | Content | Disposition |
|---|---|---|---|
| `app.scss` | 47 | Vendor imports (§0) + 6 partials. | Replaced by `assets/css/app.css` (Tailwind v4 entry). |
| `styles.scss` | 574 | "SB Admin v2" era theme: `html{font-size:initial!important}` (15-19), `footer`, `.logo` sizing (37-59), dropdown/mega-menu (65-72, 201-284), buttons `.btn-outline` (74-87), sidebar `.navbar-static-side` (89-133, 300-336), mosaic view (135-189, 507-537), breadcrumb (256-266), admin table (338-357), side filter `h4.filter_legend`, `tr.filter.active` (359-402), x-editable overrides (410-441), checkbox label weight + iCheck spacing (448-498), select2 radius reset (500-505), `.sonata-toggle-filter i::before` FA glyph swap `\f0c8`/`\f14a` (562-570), **`[hidden]{display:none!important}` (572-574)**. | Rewrite; keep `[hidden]` rule (Stimulus filter controller relies on the `hidden` attribute — Tailwind preflight already has `[hidden]:where(:not([hidden=until-found])){display:none!important}`). |
| `layout.scss` | 452 | Login box, `.sonata-ba-field-inline-table`, `.sonata-ba-modal-edit-one-to-one` hides batch/action columns (73-83), sort-arrow pseudo-elements on `th.sonata-ba-list-field-header-order-*` (86-124), pager, boolean/currency alignment (143-182), `.required::after{content:'*'}` (371-373), `.noscript-warning` (375-383), `.no-js .sonata-collection-*` hidden (314-317), `.navbar.stuck` / `.form-actions.stuck` fixed positioning with `width: calc(100% - 230px)` and `.sidebar-collapse` variant (385-411), `<768px` rules (413-451). | Rewrite; the `.stuck` rules are what the `sonata-sticky` controller expects — re-implement with Tailwind `sticky`/`fixed` and CSS vars for sidebar width. |
| `flashmessage.scss` | 38 | `.read-more-state` checkbox hack for SonataTwig flash messages. | Keep semantics (markup comes from twig-extensions). |
| `readmore.scss` | 27 | `.sonata-readmore-content{overflow:hidden}`, `.expanded{max-height:none!important}`, hide button unless `.truncated`. | Keep as `@layer components`. |
| `tree.scss` | 153 | `.sonata-tree`, `.sonata-tree__item`, `--small`, `--toggleable`, `.is-toggled` rotates `.fa-caret-right`. | Rewrite with Tailwind; keep class names (BEM, referenced by `treeview.js`). |
| `admin-lte-fas.scss` | 72 | Makes AdminLTE's `.fa`-only selectors accept `.fas/.far/.fab/.fal/.fad`. | Drop (AdminLTE goes away). |

### 1.4 Build & lint configuration

| File | Key settings | Evidence |
|---|---|---|
| `webpack.config.js` | output `./src/Resources/public`, `setPublicPath('.')`, `setManifestKeyPrefix('bundles/sonataadmin')`, `cleanupOutputBeforeBuild`, Sass + PostCSS, **`enableVersioning(false)`**, **`enableSourceMaps(false)`**, `autoProvidejQuery()`, `disableSingleRuntimeChunk()`, `enableStimulusBridge('./assets/js/controllers.json')`, css-minimizer drops comments, images → `images/[name][ext]`, fonts → `fonts/[name][ext]`, stylelint plugin, terser strips comments, `copyFiles` for `assets/images`, AdminLTE skins, select2 i18n; single entry `app`. | `S/webpack.config.js:13-71` |
| `postcss.config.js` | `postcss-scss` syntax, `autoprefixer` only. | `S/postcss.config.js:16-21` |
| `.babelrc.js` | `@babel/preset-env`. | `S/.babelrc.js:16-18` |
| `.eslintrc.js` | `airbnb-base` + `prettier`, env `browser` + **`jquery`**, `eslint-plugin-header` enforcing the Sonata licence block, `import/no-webpack-loader-syntax: off`. | `S/.eslintrc.js:16-46` |
| `.stylelintrc.js` | `stylelint-config-standard-scss`, `stylelint-order` (custom-properties then declarations; alphabetical properties), `selector-class-pattern: null`. | `S/.stylelintrc.js:16-25` |
| `prettier.config.js` | `singleQuote`, `printWidth: 100`. | `S/prettier.config.js:10-13` |
| `package.json` | `engines.node >= 18`; `browserslist: defaults`; scripts `dev-server`, `dev`, `watch`, `build` (= `encore production`). devDeps: Encore ^5.2, webpack ^5.98, sass-embedded, sass-loader 16, postcss-loader 7, eslint 8, prettier 2, stylelint 15. | `S/package.json` |

### 1.5 PHP side of the asset pipeline

| Piece | Behaviour | Evidence |
|---|---|---|
| `Sonata\AdminBundle\Asset\LastModifiedVersionStrategy` | `applyVersion()` appends `?v=<filemtime>` computed from `%kernel.project_dir%/public/<path>`; empty when file missing. | `S/src/Asset/LastModifiedVersionStrategy.php:19-52` |
| Asset package `sonata_admin` | `sonata.admin.assets.package` = `PathPackage('/', version_strategy, assets.context)` tagged `assets.package: sonata_admin`; params `sonata.admin.assets.public_dir=/public`, `sonata.admin.assets.base_path=/`. | `S/src/Resources/config/core.php:52-68` |
| Config `sonata_admin.assets.{stylesheets,extra_stylesheets,remove_stylesheets,javascripts,extra_javascripts,remove_javascripts}` | Each item is a string or `{path, package_name}`; `package_name` defaults to `Configuration::DEFAULT_PACKAGE` (`sonata_admin`). Defaults: `bundles/sonataadmin/app.css`, `bundles/sonataform/app.css`, `bundles/sonataadmin/app.js`, `bundles/sonataform/app.js`. (Cosmetic bug: the `remove_*` normalisers pass the node name `'extra_javascripts'` to `normalizeAssetList`, lines 652 & 693.) | `S/src/DependencyInjection/Configuration.php:618-700`, `825-839` |
| DI extension | Appends `bundles/sonataadmin/admin-lte-skins/<skin>.min.css` to stylesheets (94-100), then merges `extra_*`/`remove_*` (`mergeArray`, removal matches both `path` and `package_name`) and stores the final lists in `options.stylesheets` / `options.javascripts` (104-105). `lock_protection: false` removes `sonata.admin.lock.extension` (115). | `S/src/DependencyInjection/SonataAdminExtension.php:92-117, 240-290` |
| `sonata_admin.options` JS-relevant flags | `html5_validate` (default true → adds `novalidate` when false at `base_edit_form.html.twig:17`, `base_acl_macro.html.twig:17`), `confirm_exit` (true), `js_debug` (false), `skin` enum of 12 AdminLTE skins (default `skin-black`), `use_select2` (true), `use_icheck` (true), `use_stickyforms` (true), `lock_protection` (false; pure PHP), `use_bootlint` (renders an inline bootlint loader script, `standard_layout.html.twig:334-341`). | `S/src/DependencyInjection/Configuration.php:288-315, 374`; `docs/reference/action_create_edit.rst:18-24` |
| `CanonicalizeRuntime::getCanonicalizedLocaleForSelect2()` | `en*` → `null`; `pt`→`pt-PT`, `ug`→`ug-CN`, `zh`→`zh-CN`; other regional locales collapse to language unless in `[pt-BR, pt-PT, ug-CN, zh-CN, zh-TW]`. `getCanonicalizedLocaleForMoment()` is deprecated no-op since 4.40. | `S/src/Twig/CanonicalizeRuntime.php:35-77` |
| `standard_layout` emission | `<meta name="sonata-config" content='{SKIN, CONFIRM_EXIT, USE_SELECT2, USE_ICHECK, USE_STICKYFORMS, DEBUG}'>` (37-45); `<meta name="sonata-translations" content='{CONFIRM_EXIT}'>` (46-49); `{% block stylesheets %}` loops `sonata_config.getOption('stylesheets')` → `<link href="{{ asset(path, package_name) }}">` (51-55); `{% block javascripts %}` contains empty `{% block sonata_javascript_config %}`, `{% block sonata_javascript_pool %}` looping `javascripts` into `<script src>` **in `<head>`, synchronous, no `defer`** (57-65), then `select2-locale/<locale>.js` if `use_select2` (67-73). There is no block named `sonata_javascripts`/`sonata_stylesheets`; the names are `stylesheets`, `javascripts`, `sonata_javascript_config`, `sonata_javascript_pool`. | `S/src/Resources/views/standard_layout.html.twig:37-74` |
| `<html class="no-js">` / `<body class="sonata-bc {skin} fixed sonata-select2 sonata-icheck [sidebar-collapse]" data-controller="sonata-sticky">` | `no-js` removed by `admin.js:376`; `.no-js` hides collection add/delete buttons (`layout.scss:314-317`). | `standard_layout.html.twig:29, 91-101` |

---

## 2. Dependency-by-dependency disposition

Legend: **Keep** (same lib), **Drop** (no replacement needed), **Replace** (with named lib), **Shim** (keep global for BC only).

| npm dependency (version) | Where used (file:line) | Behaviour it provides | Proposed disposition |
|---|---|---|---|
| `jquery ^3.7` | `app.js:14,51-52` (global); `admin.js` throughout; `base.js`, `sidebar.js`, `treeview.js`; `edit_controller.js:49`; inline scripts `edit_many_script.html.twig` (all 600 lines), `edit_one_script.html.twig:31-74`, `edit_one_to_many_sortable_script_{table,tabs}.html.twig`, `sonata_type_model_autocomplete.html.twig:64-200`, `form_admin_fields.html.twig:466-535, 542-549`, `base_list.html.twig:154-178`; `docs/cookbook/recipe_jquery_ui.rst:18-30` documents `.addExternals({ jquery: 'jQuery' })` for apps. | DOM helper, event bus (`.trigger/.on` for `sonata-*` events), AJAX (`$.ajax`, `.ajaxSubmit`), plugin host for select2/iCheck/x-editable/sortable/treeView/masonry/slimscroll/Bootstrap. | **Shim (keep global, stop using internally).** See §3.2. adminata's own code is written jQuery-free (Stimulus + `fetch`), but `app.js` still `import $ from 'jquery'; window.$ = window.jQuery = $` for the 4.x line, and every adminata event is a native bubbling `CustomEvent` so `$(document).on('sonata-…')` keeps working (jQuery's `.on` receives native events; the reverse is not true). Provide config `adminata.assets.expose_jquery: true|false` (default true in 1.x, flip in 2.x). |
| `bootstrap ^3.3` (JS: modal, dropdown, tab, collapse, tooltip, popover, alert) | `app.js:23`; modal: `edit_modal.html.twig:12-23`, `form_admin_fields.html.twig:677-687`, `edit_many_script.html.twig:75,143,185,227,303` (`.modal()`, `.modal('hide')`), `admin.js:35-57` (`setup_list_modal` restyles `.modal-dialog/.modal-content/.modal-body`); dropdown: `data-toggle="dropdown"` at `standard_layout.html.twig:156,168,270`, `base_list.html.twig:211,263`, `tree.html.twig:53`, `dashboard__action_create.html.twig:11`, `tab_menu_template.html.twig:111`; tab: `data-toggle="tab"` at `base_edit_form.html.twig:51`, `base_show.html.twig:49`, `edit_one_to_many_inline_tabs.html.twig:20`, `edit_controller.js:49`; alert: `data-dismiss="alert"` at `render_form_dismissable_errors.html.twig:3`, `edit_many_script.html.twig:366,392`; collapse: only in `ORM/src/Resources/views/Block/block_audit.html.twig:27`; popover: `admin.js:65,105-107` (`select.data('popover')` → `.popover(options)`); tooltip: not used by Sonata templates. CSS: whole grid/forms/buttons/labels. | Modal open/close/backdrop/ESC, dropdown toggling + outside click, tab switching (`.active` on `li`, `aria-controls`), alert dismiss, collapse, popover. | **Replace.** Modal → native `<dialog>` wrapped in a Stimulus `adm-modal` controller (TailAdmin's modal is `fixed inset-0 … z-99999` + backdrop, `TR/src/components/ui/modal/index.tsx:57-70`; port that markup, but use `<dialog>` for focus trap/ESC/`inert` for free). Dropdown → Stimulus `adm-dropdown` (click toggle, outside click, Escape) using TailAdmin dropdown markup (`TR/src/components/ui/dropdown/Dropdown.tsx:39-47`); Floating UI only if flip/shift is needed — Sonata's dropdowns are all right-aligned navbar/button-group menus, so pure CSS `absolute right-0 mt-2` suffices; skip Popper. Tabs → Stimulus `adm-tabs` (ARIA `role=tablist/tab/tabpanel`, keeps `aria-controls`, exposes `show(tab)` so `sonata-edit` calls `this.tabsOutlet.show(tab)` instead of `jQuery(tab).tab('show')`). Alert dismiss → tiny `adm-dismiss` controller. Collapse → `<details>`/Stimulus toggle. Popover on select → drop (undocumented, no template sets `data-popover`). **Keep a Bootstrap-3-compat data-API shim** (`data-toggle="dropdown|tab|modal|collapse"`, `data-dismiss="alert|modal"`) mapped onto the new controllers via a delegated listener, so user overrides that still carry BS3 attributes keep working in 1.x. |
| `admin-lte ^2.4` (JS: push-menu, tree, layout, box widget) | `app.js:33`; `data-toggle="push-menu"` on `.sidebar-toggle` (`standard_layout.html.twig:126`); `data-widget="tree"` on `ul.sidebar-menu` (`Menu/sonata_menu.html.twig:4`) with `.treeview`/`.treeview-menu`/`.active` (`:16-18`); `body.fixed` layout (`standard_layout.html.twig:93`); skins `skin-*` (Configuration.php:295-311, SonataAdminExtension.php:94-100); `data-widget="collapse|remove"` boxes not used in Sonata templates (`grep` = 0) but common in user dashboards. CSS: `.box`, `.box-header`, `.box-body`, `.box-footer`, `.box-tools`, `.info-box`, `.content-wrapper`, `.main-header`, `.main-sidebar`, `.sidebar-menu`, `.navbar-custom-menu`, `.user-menu`, `.sidebar-collapse`, `.nav-tabs-custom`. | Sidebar push/collapse with `sidebar-collapse` body class + slimscroll, expandable sidebar tree menus, fixed layout, skins. | **Replace** with TailAdmin layout: `<aside class="sidebar …" :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'">` (`T/src/partials/sidebar.html:1-4`), overlay (`T/src/partials/overlay.html`), header hamburger (`T/src/partials/header.html:12-16`). Implement as Stimulus `adm-layout` controller on `<body>` (state `sidebarOpen`, `sidebarCollapsed`, persisted in cookie `sonata_sidebar_hide` — keep the cookie name and server-side class so first paint is correct without JS, `standard_layout.html.twig:96-98`). Sidebar tree → Stimulus `adm-menu` mirroring TailAdmin `menu-item*`/`menu-dropdown*` utilities (`T/src/css/style.css:190-244`) and `x-data="{selected: $persist('Dashboard')}"` (`T/src/partials/sidebar.html:35`) — persist open group in `localStorage`. **Skins**: keep the `skin` config key (enum unchanged so config validation doesn't break) but map it to a `data-skin` attribute / CSS-variable palette (`--color-brand-*` overrides) and stop emitting `admin-lte-skins/*.css`; keep `admin_lte_skin_class` block name. |
| `icheck ^1.0` | `app.js:34`; `admin.js:111-132` (`setup_icheck`, classes `icheckbox_square-blue`/`iradio_square-blue`), `admin.js:148-176` (`.iCheck-helper`, `iCheck('check')`), `base.js:12`, `base_list.html.twig:156-177` (`ifChanged` events, `.iCheck(...)`), `form_admin_fields.html.twig:460` (`ifChecked`), `styles.scss:456-486, 535`, docs `recipe_icheck.rst` (`data-sonata-icheck="false"`), `use_icheck` option, body class `sonata-icheck`. | Skinned checkbox/radio; fires jQuery-only `ifChanged/ifChecked/ifUnchecked` events. | **Drop** the library. Use Tailwind `@tailwindcss/forms`-style native inputs (TailAdmin styles checkboxes with a `sr-only` input + custom box driven by Alpine `checkboxToggle`, `T/src/form-elements.html:1052-1090` — replace with pure CSS `peer-checked:` so no JS is needed). Keep `use_icheck` config key (deprecated, no-op → still emits `USE_ICHECK` in meta and `sonata-icheck` body class for BC), keep `Admin.setup_icheck()` as a no-op that logs, keep honouring `data-sonata-icheck="false"` (irrelevant now). Replace `ifChanged`/`ifChecked` with native `change`. |
| `x-editable ^1.5` (bootstrap3 build) | `app.js:28`; `admin.js:191-212` (`setup_xeditable` on `.x-editable`, `emptytext` FA pencil, `container: body`, success replaces the `<td>` with returned HTML); template `base_list_field.html.twig:41-95` (`data-type`, `data-value`, `data-title`, `data-format`, `data-pk`, `data-url`, `data-source` via `sonata_xeditable_choices`); server `SetObjectFieldValueAction` requires XHR, returns `JsonResponse(<rendered td html>)` or 4xx with message (`S/src/Action/SetObjectFieldValueAction.php:68,167-170`); type map `XEditableRuntime::FIELD_DESCRIPTION_MAPPING` (`select`, `textarea`, `email`, `text`, `number`, `url`, plus `checklist`, `date`) (`S/src/Twig/XEditableRuntime.php:22-35`); CSS `styles.scss:410-441`. | Inline editing popover in list view with per-type editors, posts `name/pk/value` form-encoded. | **Replace** with an in-house Stimulus `adm-editable` controller (popover built from TailAdmin dropdown/card markup, editors: text/number/email/url/textarea/select/checklist/date(`<input type=date>`)) that posts the **same** payload (`name`, `pk`, `value`, `value[]` for checklist) to `data-url` with `X-Requested-With`, and swaps the `<td>` with the JSON string response exactly like `admin.js:198-201`. Keep the `x-editable` class and all `data-*` attribute names so user field templates overriding `field_span_attributes` still work. Keep `sonata_xeditable_type` / `sonata_xeditable_choices` Twig filters untouched. |
| `select2 ^4.0` (full build) + `select2-bootstrap-theme` + 59 locale files | `app.js:32`; `admin.js:58-110` (`setup_select2`: every `select` unless `data-sonata-select2="false"`, options from `data-sonata-select2-allow-clear/allow-tags/maximumSelectionLength/minimumResultsForSearch`, `data-placeholder`, `theme:'bootstrap'`, width from inline style/`%`), `admin.js:293-362` (`setup_sortable_select2` + jQuery UI sortable on `ul.select2-selection__rendered`), `sonata_type_model_autocomplete.html.twig:62-200` (ajax autocomplete with `processResults`, `templateResult/Selection`, `dropdownParent` inside `.modal`, `language`, `containerCssClass`, `dropdownCssClass`, `escapeMarkup`, `select2:select/unselect` → hidden inputs), `form_admin_fields.html.twig:206-215` (`data-placeholder`), `standard_layout.html.twig:67-73` (locale script), `CanonicalizeRuntime`, `use_select2` option, body class `sonata-select2`, docs `recipe_select2.rst`, `admin.js:386-390` per-page hack, `styles.scss:500-505`. | Searchable single/multi select, tags, clear, remote data with paging, i18n, sortable multi. | **Replace with Tom Select 2.x** (vanilla, MIT, ~50 KB min, plugins `remove_button`, `clear_button`, `drag_drop` (needs SortableJS or its own), `dropdown_input`, `virtual_scroll`; remote via `load(query, callback)` with `firstUrl` for paging; i18n by passing strings). Mapping: `allowClear`→`plugins.clear_button`; `tags`→`create:true`; `maximumSelectionLength`→`maxItems`; `minimumResultsForSearch`→ show/hide `dropdown_input` when option count ≥ N; `data-placeholder`→`placeholder`; `dropdownParent:'body'` when inside `<dialog>`; sortable multi → `drag_drop` plugin (replaces `setup_sortable_select2` + jQuery UI). Keep **all** `data-sonata-select2-*` attribute names, the `use_select2` flag, `USE_SELECT2` meta key, body class, `Admin.setup_select2()` name, and the `sonata_type_model_autocomplete_select2_options_js` / `_ajax_request_parameters` / `_dropdown_item_format` / `_selection_format` Twig blocks (they inject JS into the init script — keep them but document that the object is now a Tom Select config). Locale: Tom Select has no locale packs; render the handful of strings (`no_results`, `loading`, `input_too_short`, `type_to_search`) into `<meta name="sonata-translations">` and stop shipping `select2-locale/`; `canonicalize_locale_for_select2()` stays as a Twig function (deprecated, still returns the value) so overridden layouts don't fatal. Alternative evaluated: keep select2 4.1 (jQuery-bound, unmaintained since 2021, Bootstrap theme would need rewriting for Tailwind) — rejected; native `<select>` — rejected (loses autocomplete, tags, multi UX; TailAdmin's own multi-select is an Alpine toy, `T/src/form-elements.html:424-560`). |
| `jquery-form ^4.3` | `edit_many_script.html.twig:92,290,316`, `edit_one_script.html.twig:34` (`.ajaxSubmit({url,type,data:{_xml_http_request:true},dataType,headers})`). Re-added in 4.x after removal broke users (CHANGELOG #8237/#8232, `S/CHANGELOG.md:144,151`). | Serialises a form incl. files and submits via XHR. | **Replace** with `fetch(url, {method, body: new FormData(form), headers:{'X-Requested-With':'XMLHttpRequest', Accept}})`. Append `_xml_http_request=1` to the `FormData` to keep `CRUDController::isXmlHttpRequest()` (`:1016-1020`) working for non-XHR proxies. Note the CHANGELOG history: user apps may call `$(form).ajaxSubmit` themselves — the jQuery shim does **not** include jquery-form; document it (`extra_javascripts` can add it). |
| `jquery-ui ^1.13` (widget + sortable only) | `app.js:21-22`; `admin.js:330-341` (select2 sortable), `edit_one_to_many_sortable_script_table.html.twig:12-40` (`tbody.sonata-ba-tbody` rows, `.sonata-ba-sortable-handler`, `sortable('refresh')` on `sonata.add_element`), `…_tabs.html.twig:12-44` (`.sonata-ba-tabs > div`), `app.scss:34` (sortable.css), `layout.scss:336-338` (`tbody.ui-sortable tr{cursor:move}`), docs `recipe_jquery_ui.rst`, `recipe_sortable_listing.rst`, `recipe_sortable_sonata_type_model.rst`. | Drag-to-reorder collection rows/tabs and update hidden position inputs. | **Replace with SortableJS** (vanilla, ~44 KB, handle option, `onEnd`), wrapped in Stimulus `adm-sortable` with values `handle`, `positionField`; re-index on `sonata-collection-item-added` / `sonata.add_element`. Keep class `sonata-ba-sortable-handler` and the `sortable` field option semantics. Users who followed `recipe_jquery_ui.rst` and import jQuery UI widgets themselves must now also import jQuery UI core — document in UPGRADE. |
| `jquery.scrollto ^2.1` | `app.js:16-18` only ("not directly used in SonataAdmin but used on SonataPage, SonataArticle and SonataDashboard"). | `$(el).scrollTo()` for other Sonata bundles. | **Drop**; native `Element.scrollIntoView({behavior:'smooth'})`. SonataPage/Article/Dashboard are 3.x-era bundles not compatible with Admin 4 anyway. |
| `jquery-slimscroll ^1.3` | `app.js:37`; used internally by AdminLTE for `.sidebar` scrolling; `layout.scss:438-443` disables it under 768px. | Custom scrollbar in sidebar. | **Drop**; TailAdmin uses `overflow-y-auto no-scrollbar` / `custom-scrollbar` utilities (`T/src/css/style.css:246-271`, `T/src/partials/sidebar.html:30-32`). |
| `masonry-layout ^4.2` | `app.js:38`; auto-initialised by `data-masonry='{ "itemSelector": ".search-box-item" }'` on the search page (`Core/search.html.twig:21`); items are `.col-lg-4.col-md-6.search-box-item` (`Block/block_search_result.html.twig:20`). | Pinterest-style packing of search result boxes. | **Drop**; CSS `columns-1 md:columns-2 lg:columns-3 gap-6` with `break-inside-avoid` on items (or `grid` with `auto-rows-min`). Keep `data-masonry` attribute removal out of user concern (attribute becomes inert). |
| `qs ^6.13` | `filter_controller.js:10,34` to flatten `defaultValues` into `filter[x][value]=…` keys. | Nested query-string serialisation. | **Keep** (tiny, tree-shakes to `stringify`) or replace with a 20-line recursive `URLSearchParams` builder — keep for exact key-format parity with `convertQueryStringToObject` (`utils.js:72-88`). |
| `@fontsource/source-sans-pro ^4.5` | `app.scss:20-26`, `styles.scss:57-59`; 37 font files in `public/fonts`. | AdminLTE's typeface. | **Replace** with self-hosted **Outfit** (TailAdmin default, `T/src/css/style.css:1-2,10`) via `@fontsource-variable/outfit` — **never** the Google Fonts `@import url(...)` TailAdmin uses (admin panels run on intranets; external font requests leak IPs). Offer Inter as alternative? Not needed; one variable font (≈100 KB woff2) is fine. Expose `--font-sans` token so apps can swap. |
| `@fortawesome/fontawesome-free ^5.15` (+ `v4-shims.css`) | `app.scss:15-16`; 87 `fa*`/`parse_icon` usages in templates; `IconRuntime::parseIcon()` **only accepts** `fa `, `fas `, `far `, `fab `, `fal `, `fad ` prefixes or raw markup starting with `<` (`S/src/Twig/IconRuntime.php:20-37`); dashboard groups / menu items / list modes / actions are configured by users with strings like `'icon' => 'fa fa-cog'` (`docs/reference/dashboard.rst`, `sonata_menu.html.twig:27` uses `fa fa-angle-double-right`); `styles.scss:314-320,562-570`, `tree.scss:45-57` depend on FA glyph codes. | Icon font incl. v4 class names. | **Keep Font Awesome, upgrade to 6 Free** (webfont build with `fontawesome.css` + `solid/regular/brands` + `v4-shims.css` + `v5-font-face.css`) so **every** user-configured `fa fa-*`, `fas fa-*` string keeps rendering — this is the single biggest drop-in compatibility lever. Ship only woff2 (drop eot/ttf/woff/svg: saves ~1.7 MB of the 2.1 MB fonts dir). Use inline SVG (TailAdmin style, `stroke-current`/`fill-current`) **only for chrome icons authored inside adminata templates** (sidebar arrows, hamburger, close, sort arrows), never for anything user-configurable. Lucide/Heroicons as a *second* icon source can be offered later via `parse_icon` accepting `<svg>` (already allowed) or a new `lucide:` prefix — out of scope for 1.0. |
| `@hotwired/stimulus ^3.2` + `@symfony/stimulus-bridge ^3.2` (+ `@hotwired/stimulus-webpack-helpers`) | `stimulus.js:11-29`, `app.js:46-54` (`global.stimulus`, `global.sonataApplication`); `stimulus_controller()/stimulus_target()/stimulus_action()` Twig helpers from `symfony/stimulus-bundle ^2.22||^3.0` (`S/composer.json:56`; installation.rst:49). | Controller registry, lazy loading, `data-controller` DOM API. | **Keep Stimulus 3; drop stimulus-bridge** (it exists only for Webpack `require.context` lazy loading). Register controllers explicitly: `sonataApplication.register('sonata-collection', CollectionController)` etc. Keep the `sonata-*` identifiers, keep `window.sonataApplication` and `window.stimulus` (form-extensions needs the former, `FE/assets/js/app.js:16-18`). Keep `symfony/stimulus-bundle` as a composer requirement (Twig functions). |
| *(new)* `alpinejs` | TailAdmin only. | See §3.1 — **do not add**. |
| *(new)* `tom-select`, `sortablejs`, `@fontsource-variable/outfit`, `@fortawesome/fontawesome-free@6`, `tailwindcss@4`, `@tailwindcss/vite` or `@tailwindcss/postcss`, `@tailwindcss/forms` (TailAdmin devDep, `T/package.json:24`) | — | — | Add. `flatpickr` is **not** adminata's concern: date pickers come from `sonata-project/form-extensions` (Tempus Dominus 6 + its own `bundles/sonataform/app.{js,css}`, `FE/assets/scss/app.scss:10`, `FE/assets/js/controllers/datepicker_controller.js:29`); adminata must only style `.input-group.date` / Tempus Dominus popups for dark mode, or — better — ship an optional `adminata/form-extensions` theme later. |

Bundle-size expectation (minified, uncompressed, rough): today `app.js` 476 KB + `app.css` 340 KB + 2.1 MB fonts + 240 KB select2 locales. Target: `app.js` ≈ 200 KB with jQuery shim (Stimulus ~35 KB, Tom Select ~60 KB, SortableJS ~45 KB, adminata ~40 KB, jQuery 87 KB) / ≈ 115 KB without; `app.css` ≈ 120–180 KB (Tailwind output for adminata's templates + FA6 CSS ~ 100 KB → consider a separate `icons.css` so apps that switch to SVG icons can `remove_stylesheets` it); fonts ≈ 400 KB (FA6 woff2 ×3 + Outfit variable). Set hard CI budgets (`size-limit`): `app.js` ≤ 220 KB, `app.css` ≤ 200 KB.

---

## 3. Framework decision and the jQuery question

### 3.1 Alpine.js vs Stimulus vs both

| Criterion | Alpine 3 (TailAdmin's choice) | Stimulus 3 (Sonata's existing choice) | Both |
|---|---|---|---|
| Already a dependency / idiom | New. | Yes: 9 controllers, `symfony/stimulus-bundle` in composer, `sonataApplication` consumed by form-extensions (`FE/assets/js/app.js:16-18`). Symfony UX ecosystem idiom. | — |
| Markup coupling | State and behaviour live in HTML attributes (`x-data`, `:class`, `@click`), copied straight from TailAdmin partials — fastest to port pixel-exact. | Behaviour in JS, markup carries only `data-controller/target/action`; TailAdmin's `:class="sidebarToggle ? 'a' : 'b'"` must be rewritten as class toggling in a controller. | Two mental models in one code base; two ways to attach behaviour to server-rendered HTML. |
| Server-rendered Twig + Symfony Form themes | Works, but Alpine expressions inside form-theme blocks that users override become part of the "template contract" (users must copy `x-data` blobs). | Attributes are generated by `stimulus_controller()` helpers — the contract is already the documented Sonata contract (`base_edit_form.html.twig:19-26`). | — |
| CSP | Default build needs `'unsafe-eval'` (expressions are evaluated); the `@alpinejs/csp` build forbids inline expressions like TailAdmin's, so TailAdmin markup would need rewriting anyway. | No eval; only inline `<script>` blocks need nonces (already true for Sonata). | Worst of both. |
| AJAX-injected fragments (modal lists, `append_form_element`, collection prototypes) | `Alpine.initTree(el)` must be called after every injection; `x-data` in `data-prototype` strings survives cloning but nested `x-data` in select2/tom-select rendered nodes gets messy. | Stimulus `MutationObserver` connects controllers automatically on any inserted subtree — exactly what `Admin.shared_setup(subject)` needs (`admin.js:20-28`). | — |
| Persisted UI state | `$persist` plugin (`T/src/partials/sidebar.html:35`). | Hand-written 5 lines with `localStorage`/cookie. | — |
| Size | ~45 KB min. | ~35 KB min. | ~80 KB. |
| User extension story | Users already know how to add Stimulus controllers to Sonata (Symfony UX docs). | Same. | Confusing. |

**Recommendation: Stimulus-only.** Port TailAdmin's *markup and CSS* but express every `x-data`/`:class`/`@click`/`x-show` as a small Stimulus controller (`adm-layout`, `adm-menu`, `adm-dropdown`, `adm-modal`, `adm-tabs`, `adm-theme`, `adm-dismiss`, `adm-editable`, `adm-sortable`, `adm-select`, `adm-checkbox-range`). TailAdmin's Alpine usage is shallow (booleans toggled by clicks, `T/src/partials/header.html:2,13-15,82-83,143`, `T/src/partials/sidebar.html:2-4,35,69`), so the port is mechanical. Do **not** ship Alpine; document how a user can add it themselves if they paste TailAdmin components (`extra_javascripts`).

### 3.2 The jQuery question

Facts: (1) Sonata's own inline scripts are 100 % jQuery today (§1, §5). (2) The public docs tell users to write jQuery: "`A jQuery event is fired after a row has been added (sonata-admin-append-form-element)`" (`S/docs/reference/form_types.rst:660-696`), "`Admin.setup_list_modal()`" jQuery trigger (`:110-113`), `recipe_jquery_ui.rst` tells apps to externalise `jquery` to Sonata's global. (3) Ecosystem bundles: `form-extensions` is jQuery-free (Stimulus only, verified), `doctrine-orm-admin-bundle` ships no JS (only a `data-toggle="collapse"` in `block_audit.html.twig:27`), `block-bundle` ships no JS; but SonataMedia / SonataUser / SonataClassification / SonataPage templates and a decade of app-level `admin.js` overrides do use `$` and `Admin.*`.

**Recommendation:**

- adminata 1.x: **keep a global jQuery 3.7** (`window.$`/`window.jQuery`) loaded *before* adminata's own code, exclusively as a BC shim; adminata's own code never imports it (lint rule `no-restricted-globals: ['$', 'jQuery']` in `assets/`). Make it removable: `adminata.assets.expose_jquery: false` (or `sonata_admin.assets.remove_javascripts: ['bundles/adminata/jquery.js']` if shipped as a separate file — **shipping it as a separate file `bundles/adminata/vendor/jquery.js` listed in the default `javascripts` is the cleanest**, because `remove_javascripts` already exists and needs no new config key). Without jQuery, `Admin.setup_xeditable` etc. still work because they are rewritten.
- Keep no jQuery *plugins* (no jquery-form, jQuery UI, select2, iCheck, x-editable). Apps that need them add them via `extra_javascripts` after `jquery.js`.
- All adminata events are native `CustomEvent`s with `bubbles: true` (already the case for the Stimulus `dispatch` calls in `collection_controller.js:34-65`), so jQuery listeners keep firing. The reverse (jQuery `.trigger('sonata-admin-append-form-element')` from user code, or from Sonata's own `edit_many_script.html.twig:341`) does **not** reach `addEventListener` — adminata must therefore listen with jQuery *when the shim is present* for the three documented events, or (simpler) document that user code must `el.dispatchEvent(new CustomEvent(...))`. Implement the former in the shim file: `if (window.jQuery) jQuery(document).on('sonata-admin-append-form-element', e => e.target.dispatchEvent(new CustomEvent(..., {bubbles:true, detail:{fromJquery:true}})))` with a re-entrancy guard.
- Deprecation horizon: announce in 1.0 UPGRADE that the jQuery global is deprecated and will be removed in 2.0 (mirror Sonata's own `NEXT_MAJOR` convention and the fork's "BC breaks queued for next major" rule in `M/AGENTS.md` §3).

---

## 4. The DOM / JS contract that must survive

Everything below is either called by Sonata's own templates (which users copy/override), documented in `docs/`, or exposed as a global. "Keep" means same name **and** same observable semantics.

### 4.1 Globals

| Global | Defined | Consumers | Keep? |
|---|---|---|---|
| `window.$`, `window.jQuery` | `app.js:51-52` | user JS, `recipe_jquery_ui.rst:20`, inline Sonata scripts | Keep (shim, §3.2) |
| `window.stimulus` (the whole `@hotwired/stimulus` module namespace) | `app.js:53` | user code doing `class extends stimulus.Controller` | Keep |
| `window.sonataApplication` (Stimulus `Application`) | `app.js:54`, `stimulus.js:15` | `form-extensions` (`FE/assets/js/app.js:16-18`), user controllers | Keep — **must be started and global before `bundles/sonataform/app.js` executes** (script order in `javascripts` list). |
| `window.Admin` | `admin.js:373` | inline scripts (`Admin.shared_setup`, `Admin.log`, `Admin.get_config`, `Admin.setup_list_modal`, `Admin.get_select2_width`, `Admin.setup_sortable_select2`), user JS | Keep object with all 16 members: `shared_setup(subject)`, `get_config(key)`, `get_translations(key)`, `setup_list_modal(modal)`, `setup_select2(subject)`, `setup_icheck(subject)`, `setup_checkbox_range_selection(subject)`, `setup_xeditable(subject)`, `log(...)`, `setup_inline_form_errors(subject)`, `switch_inline_form_errors(subject)`, `setup_tree_view(subject)`, `get_select2_width(el)`, `setup_sortable_select2(subject, data, options)`, `setup_sticky_elements()` (deprecated warn), `setup_readmore_elements()` (deprecated warn). `subject` must accept `document`, an `Element`, **or a jQuery object** (inline scripts pass `field_dialog_{{ id }}` which is a jQuery collection, `edit_many_script.html.twig:63,104,138,177,219,353,500`) — normalise with `subject?.jquery ? subject[0] : subject`. |
| `$.fn.treeView` | `treeview.js:94-100` | `Admin.setup_tree_view` | Keep as thin wrapper over the new `adm-tree` controller when jQuery shim present. |
| Per-field global functions: `start_field_dialog_form_add_{id}`, `start_field_dialog_form_edit_{id}`, `start_field_dialog_form_list_{id}`, `remove_selected_element_{id}`, `start_field_retrieve_{id}`, and the `var field_dialog_{id}`, `field_dialog_content_{id}`, `field_dialog_title_{id}`, `field_widget_{id}`, `apply_position_value_{id}`, `initialize_popup_{id}` | `edit_many_script.html.twig:443-547`, `edit_one_script.html.twig:26-98`, sortable scripts | `onclick="return start_field_dialog_form_add_{{ id }}(this)"` attributes in `form_admin_fields.html.twig:577,601,626,649`, `sonata_type_model_autocomplete.html.twig:39`, `Association/edit_one_to_one.html.twig:62,83,110`, `edit_one_to_many.html.twig:52,92`, `edit_many_to_many.html.twig:94,124`, `edit_many_to_one.html.twig:62,83,110` | Replace with a Stimulus `sonata-model-list` / `sonata-model` controller on `#field_container_{id}` and `data-action` attributes; **but keep the templates `edit_many_script.html.twig` / `edit_one_script.html.twig` as thin shims that define the same global function names delegating to the controller**, because user form themes override the association templates and still emit `onclick="return start_field_dialog_form_add_…"`. |

### 4.2 Meta tags, cookies, body/html classes

| Item | Evidence | Keep |
|---|---|---|
| `<meta name="sonata-config">` JSON keys `SKIN`, `CONFIRM_EXIT`, `USE_SELECT2`, `USE_ICHECK`, `USE_STICKYFORMS`, `DEBUG` | `standard_layout.html.twig:37-45`; read by `config.js:18` | Keep all; add `THEME` (`light|dark|system`), `LOCALE`. |
| `<meta name="sonata-translations">` key `CONFIRM_EXIT` | `:46-49`; `translation.js:18` | Keep; add Tom Select / editable strings. |
| cookie `sonata_sidebar_hide` | `sidebar.js:12-18`; `standard_layout.html.twig:96-98` | Keep (name + `path=/`). |
| `<html class="no-js">`; `.no-js` removed on ready | `:29`; `admin.js:376` | Keep (`.no-js .sonata-collection-add{display:none}`). |
| `<body class="sonata-bc {{skin}} fixed sonata-select2 sonata-icheck sidebar-collapse">` + `data-controller="sonata-sticky"` | `:91-101` | Keep `sonata-bc`, skin class (block `admin_lte_skin_class`), `sonata-select2`, `sonata-icheck`, `sidebar-collapse`; `fixed` can stay as inert marker. Add `dark`. |
| Twig blocks `stylesheets`, `javascripts`, `sonata_javascript_config`, `sonata_javascript_pool`, `html_attributes`, `body_attributes`, `admin_lte_skin_class`, `bootlint`, `meta_tags`, `sonata_head_title` | `:29-101, 334-341` | Keep all names; `bootlint` becomes empty block. |

### 4.3 Data attributes and selectors used by JS

| Attribute / selector | Used by | Keep |
|---|---|---|
| `data-sonata-select2="false"`, `data-sonata-select2-allow-clear`, `data-sonata-select2-allow-tags`, `data-sonata-select2-maximumSelectionLength`, `data-sonata-select2-minimumResultsForSearch`, `data-placeholder` | `admin.js:62-103`; docs `recipe_select2.rst:31-137`; `sonata_type_model_autocomplete.html.twig:11` | Keep (read by `adm-select`). |
| `data-sonata-icheck="false"` | `admin.js:116`; `recipe_icheck.rst:164-207` | Accept, no-op. |
| `data-prototype`, `data-prototype-name` | `collection_controller.js:82-88`; `form_admin_fields.html.twig:401-403` | Keep. |
| `data-filter` on `.sonata-toggle-filter` | `filter_list_controller.js:26`; `base_list.html.twig:275` | Keep. |
| `data-treeview-toggler`, `data-treeview-toggled`, `data-treeview-instance`, `ul.js-treeview`, `.is-toggled`, `.is-active` | `treeview.js:11-17` | Keep. |
| `class="x-editable"`, `data-type`, `data-value`, `data-title`, `data-format`, `data-pk`, `data-url`, `data-source` | `base_list_field.html.twig:77-91`; `admin.js:193` | Keep. |
| `objectId="…"` attribute on list cells | `edit_many_script.html.twig:72`; `base_list_flat_inner_row.html.twig:23,29`, `base_list_inner_row.html.twig` | Keep (modal list selection reads `element.attr('objectId')`). |
| `#field_dialog_{id}` (+ `.modal-body`, `.modal-title`), `#field_container_{id}`, `#field_widget_{id}`, `#field_actions_{id}` (+ `a.btn-warning`), `#sonata-ba-field-container-{id}`, `#{id}_autocomplete_input`, `#{id}_hidden_inputs_wrap`, `#{id}_controller` (form-extensions) | association scripts; `form_admin_fields.html.twig:552-687`; `sonata_type_model_autocomplete.html.twig:11-29`; `FE/…/datepicker.html.twig:14` | Keep ids. Modal internals: keep `.modal-body`/`.modal-title` class hooks inside the `<dialog>`. |
| `#list_batch_checkbox`, `td.sonata-ba-list-field-batch input[type=checkbox]`, `div.sonata-ba-list-field-batch`, `.sonata-ba-list-row-selected`, `tbody input[type=checkbox]` (shift-range) | `base_list.html.twig:156-178`; `admin.js:151-186` | Keep. |
| `.sonata-ba-field-inline-table [id$="_delete"][type=checkbox]`, `.sonata-ba-field-error-messages`, `[required]`/`data-required` swap | `admin.js:234-260` | Keep. |
| `.sonata-ba-field-error`, `.has-errors`, `a.changer-tab[aria-controls][href="#tab"]`, parent `li.active`, hidden `input[name=_tab]`, `?_tab=` | `edit_controller.js:17-79`; `base_edit_form.html.twig:51,85` | Keep `aria-controls`, `href`, `.changer-tab`, `_tab`; the "active" marker moves from `li.active` to `aria-selected="true"` — update `sonata-edit` accordingly and keep `.active` on the `<li>` too for user CSS. |
| `.sonata-ba-action` (links the modal script must *not* intercept), `.help-block.sonata-ba-field-error-messages`, `.has-error`, `.form-group` (violation renderer), `.alert-danger` | `edit_many_script.html.twig:270, 283, 375-417` | Keep the class names in adminata's form theme even though Bootstrap is gone (they are Sonata's, not Bootstrap's, except `.form-group/.has-error/.help-block/.alert-danger` which become **semantic compat classes** in `@layer components`). |
| `.sonata-ba-tbody`, `.sonata-ba-tabs`, `.sonata-ba-td-{id}-{field}`, `.sonata-ba-field-{id}-{field}`, `.nav-tabs-custom > .nav-tabs`, `.sonata-ba-sortable-handler` | sortable scripts | Keep. |
| `select.per-page` | `admin.js:387`; `Pager/base_results.html.twig:24` | Keep. |
| `.sidebar-toggle` | `sidebar.js:11`; `standard_layout.html.twig:126` | Keep. |
| `.read-more-state`, `label[for]`, `.more`, `.less`, `.hide`, `.read-more-trigger`, `.read-more-target`, `.read-more-wrap` | `base.js:12-28`; `flashmessage.scss` | Keep (markup owned by SonataTwig). |
| `.sonata-readmore`, `.sonata-readmore-content`, `.sonata-readmore-btn`, `.truncated`, `.expanded` | `readmore_controller.js`, `readmore.scss` | Keep. |
| `.stuck`, `.action-sentinel`, `.navbar-sentinel`, `.form-actions`, `.sonata-ba-form-actions` | `sticky_controller.js:47-91`; `layout.scss:385-411` | Keep. |
| `.sonata-search-result-show|fade|hide`, `.search-box-item`, `.matches` | `block_search_result.html.twig:18-21`; `styles.scss:550-560` | Keep. |
| `.sonata-ba-modal-edit-one-to-one` (hides batch/actions inside modal) | `layout.scss:73-83` | Keep. |
| `data-toggle="dropdown|tab|modal|push-menu"`, `data-dismiss="alert|modal"`, `data-widget="tree|collapse|remove"` | Sonata + ORM templates (§2) | Support via compat delegate in 1.x (§2 Bootstrap row). |

### 4.4 Events (all must be native, bubbling `CustomEvent`s)

| Event | Emitted at | Documented |
|---|---|---|
| `sonata-admin-append-form-element` | `collection_controller.js:34` (native), `admin.js:381` (listener), `edit_many_script.html.twig:341` (jQuery trigger) | `form_types.rst:112, 660, 689` |
| `sonata-admin-setup-list-modal` | `admin.js:56` (jQuery trigger) | `form_types.rst:112` |
| `sonata-collection-item-added`, `sonata-collection-item-deleted`, `sonata-collection-item-deleted-successful` | `collection_controller.js:41-65` | `form_types.rst:694-696` |
| `sonata.add_element` | `edit_one_script.html.twig:72-73` (jQuery trigger on `#sonata-ba-field-container-{id}` and `#field_container_{id}`), listened by sortable scripts (`.bind`) | — |
| iCheck `ifChanged`, `ifChecked` | `base_list.html.twig:156,171,177`, `form_admin_fields.html.twig:460` | — (drop; native `change`) |
| Stimulus outlet API: `sonata-filter`.`toggleFilter(id, state)`, `sonata-filter-list`.`disable(id)` | `filter_controller.js:85-96`, `filter_list_controller.js:25-31` | — (keep method names; user controllers may outlet them) |
| form-extensions `datepicker:pre-connect`, `datepicker:connect`, `datepicker:post-connect-changed-locale` | `FE/…/datepicker_controller.js:62-77` | — (not adminata's; must not clash) |

---

## 5. Inline `<script>` inventory and migration plan

| Template : lines | What it does today | Migration |
|---|---|---|
| `standard_layout.html.twig:334-341` | `use_bootlint` loader from `maxcdn.bootstrapcdn.com`. | Remove body; keep `{% block bootlint %}` empty; config key stays (deprecated). |
| `standard_layout.html.twig:63,71` | External `<script src>` per `javascripts` item, plus select2 locale. | Keep loop; add `defer` only if `sonataApplication` remains available synchronously to inline scripts that run later — **Sonata's inline scripts run at parse time and call `jQuery(...)`/`Admin.*` immediately** (e.g. `edit_one_to_many_sortable_script_table.html.twig:12` runs synchronously), so scripts in `<head>` must stay blocking or every inline script must be wrapped in `DOMContentLoaded`. Recommendation: keep head, blocking, no `defer` in 1.x; move to `defer` + `sonata:ready` event in 2.x. |
| `Form/form_admin_fields.html.twig:465-535` (`sonata_type_choice_field_mask_widget`) | jQuery show/hide of fields per selected value, incl. table-inline column hiding and `required`↔`data-required` swap; listens `change` + iCheck `ifChecked`. | Stimulus `sonata-choice-field-mask` controller with values `allFields`, `map`, `mainFormName`, `inline`; container id lookups (`#sonata-ba-field-container-…`, `_autocomplete_input` fallback) kept verbatim. Twig block name kept. |
| `Form/form_admin_fields.html.twig:541-549` (`sonata_type_choice_multiple_sortable`) | `Admin.setup_sortable_select2($('#id'), choices, options)` with `{% block sonata_type_model_autocomplete_select2_options_js %}`. | Keep the call (function re-implemented on Tom Select `drag_drop`), keep block. Hidden-input expansion on submit (`admin.js:343-361`) kept. |
| `Form/Type/sonata_type_model_autocomplete.html.twig:62-200` | select2 ajax config, 4 override blocks, hidden-input sync on select/unselect. | Rewrite as `data-controller="sonata-autocomplete"` with a JSON `data-…-options-value` for the static part; keep the 4 Twig blocks by rendering them into a `<script type="application/json">`-free inline script *only when non-empty* (they are JS snippets by contract). Escape via `|e('js')` as today (`{% autoescape 'js' %}` line 63). |
| `CRUD/base_list.html.twig:152-180` (`batch` / `batch_javascript` blocks) | Select-all checkbox, row `.sonata-ba-list-row-selected` class. | Stimulus `sonata-batch` controller on the list `<form>`; keep `{% block batch_javascript %}` (users override it) rendering nothing by default. |
| `CRUD/Association/edit_many_script.html.twig:25-602` | Modal list/add/edit popups for `ModelListType`/`ModelType btn_add`: ajax GET of list/create/edit pages rendered with `ajax_layout`, intercepts `<a>` and `<form>` inside the modal, POSTs with `Accept: application/json` + `_xml_http_request`, handles `{result:'ok', objectId, objectName}` (`CRUDController.php:1394-1398`) → sets hidden input + `change`, or reloads the widget through `sonata_admin_retrieve_form_element`; renders JSON violations (`title`, `violations[{propertyPath,title}]`) into `.form-group` (`:361-423`); `on change` fetches `sonata_admin_short_object_information` and toggles the edit button href. | One Stimulus controller `sonata-model-dialog` (values: `id`, `editMode` (`list`/`standard`), URLs, label) using `fetch` + `<dialog>`; `Admin.setup_list_modal` keeps firing `sonata-admin-setup-list-modal`. Keep the template files as shims exporting the global function names (§4.1). The modal element is moved to `document.body` on init (`:451`) to escape nested forms — `<dialog>` makes this unnecessary but keep the move for id-uniqueness. |
| `CRUD/Association/edit_one_script.html.twig:23-99` | `btn_add` for inline one-to-many/many-to-many: `ajaxSubmit` the parent form to `sonata_admin_append_form_element`, replaces `#field_container_{id}`, preserves file inputs, sets `enctype`, fires `sonata.add_element`. | Stimulus `sonata-append-form-element`; `fetch` + `FormData`; keep both `sonata.add_element` dispatches (native). |
| `CRUD/Association/edit_one_to_many_sortable_script_{table,tabs}.html.twig` | jQuery UI sortable on rows/tabs, rewrites position inputs, refresh on `sonata.add_element`. | `adm-sortable` (SortableJS) with `data-adm-sortable-position-field-value="{{ sortable }}"`; keep class hooks. |
| `onclick="return start_field_…(this)"` ×15 | inline handlers | Keep in 1.x (backed by shim functions); add `data-action` alongside so CSP-strict apps can drop `unsafe-inline` for `script-src-attr` once they override nothing. |

---

## 6. `ajax_layout` and XHR behaviours

| Mechanism | Evidence | adminata |
|---|---|---|
| `base_template` = `templates.ajax` (`@SonataAdmin/ajax_layout.html.twig`) when `CRUDController::isXmlHttpRequest()` — true for `X-Requested-With: XMLHttpRequest` **or** `_xml_http_request` in POST/GET. Dashboard and Search actions do the same. | `S/src/Controller/CRUDController.php:1016-1020, 1048-1058`; `Action/DashboardAction.php:48`; `Action/SearchAction.php:35`; `Configuration.php:573` | Keep both signals; adminata's `fetch` calls always send the header **and** the param. |
| `ajax_layout.html.twig` renders only `content`/`preview`/`form`/`list`/`show` blocks plus a `.navbar.sonata-list-table` with list-mode switcher and `list_filters_actions`, then filters and table — no `<html>`, no assets. | `S/src/Resources/views/ajax_layout.html.twig:12-63` | Same block structure; swap Bootstrap classes for TailAdmin card/toolbar markup; the injected fragment relies on the parent page's CSS/JS (so `Admin.shared_setup(fragment)` must initialise Tom Select, editable, tree, checkbox range inside the fragment — Stimulus does it automatically for `data-controller` nodes, but `Admin.shared_setup` must still exist for legacy callers). |
| Inside XHR list rendering: batch column hidden, `_actions` column hidden, `ajax_hidden` list option hides columns, mosaic colspan recomputed; `create` button hidden when no results. | `base_list.html.twig:64-67, 148`; `base_list_inner_row.html.twig:13-16`; `list_outer_rows_mosaic.html.twig:20-27`; `base_list_flat_inner_row.html.twig:15,27` | Keep verbatim. |
| Modal list click: any `<a>` inside `#field_dialog_{id}` that is inside `.sonata-ba-list-field` selects the row's `objectId`; any other link/form (pager, filters, sort) is re-fetched into the modal. Links with class `.sonata-ba-action` are skipped. | `edit_many_script.html.twig:33-108, 235-273` | Keep semantics in `sonata-model-dialog`. |
| Successful create in modal: JSON `{result:'ok', objectId, objectName}` when `Accept` includes `application/json`; error: JSON with `title`/`violations` (`handleXmlHttpRequestErrorResponse`) or HTML form re-render. | `CRUDController.php:1373-1398` | Keep PHP untouched; JS mirrors. |
| `sonata_admin_retrieve_form_element` (POST whole form, returns the field widget HTML), `sonata_admin_append_form_element` (POST whole form, returns re-rendered field container), `sonata_admin_short_object_information` (GET, HTML), `sonata_admin_set_object_field_value` (POST, requires XHR, returns JSON-encoded HTML). | routes referenced in the scripts; `SetObjectFieldValueAction.php:68,167-170` | Keep. |
| `sonata-revision` uses `fetch` + `X-Requested-With` → history revision view rendered with `ajax_layout`. | `revision_controller.js:21-33` | Keep. |
| `.sonata-ba-modal-edit-one-to-one` hides batch/action columns in the modal by CSS. | `layout.scss:73-83` | Keep class + rule. |

---

## 7. Tailwind v4 build & distribution strategy

Tailwind v4 emits only the utilities it *sees* in the files matched by automatic source detection (cwd, excluding `.gitignore`d paths such as `vendor/` and `node_modules/`) plus explicit `@source` globs. Consequences for a bundle that ships prebuilt CSS:

### 7.1 Option (a) — prebuilt `app.css` compiled from adminata's own templates

- Works out of the box exactly like Sonata today (`assets:install`, no Node in the app). This is what the Sonata ecosystem, `docs/getting_started/installation.rst:130-133`, and the fork's users expect.
- The CSS contains every utility used in `src/Resources/views/**` and `assets/js/**` (controllers add classes like `stuck`, `truncated`, `hidden`, `dark`).
- **Gap**: a utility the user writes in an overridden template (`templates/bundles/AdminataBundle/...`), in `configureListFields(['row_attr' => ['class' => 'bg-red-50']])`, or in `admin.list.modes` config does not exist unless adminata's build already produced it.
- Mitigation inside (a): compile with a deliberately broad `@source inline()` safelist for the *documented* extension surface (see 7.3) and ship semantic classes (7.4).

### 7.2 Option (b) — apps compile their own CSS (documented recipe)

This is the only way for arbitrary utilities in user templates to exist. Provide a first-class recipe, and make adminata's CSS **importable as source**, not only as a compiled artefact. Layout of adminata's `assets/css/`:

```
assets/css/
  app.css          # bundle entry: imports below + @source for bundle templates (used for the prebuilt build)
  theme.css        # @theme tokens (TailAdmin palette, fonts, shadows, z-index, breakpoints) + @custom-variant dark
  base.css         # @layer base: border-color compat, body, [hidden], .no-js rules
  components.css   # @layer components: .adm-* semantic classes + Sonata legacy class hooks
  vendor.css       # tom-select, sortable, fontawesome imports
```

App-side entry (`assets/styles/admin.css`):

```css
@import "tailwindcss";

/* adminata design tokens + components as *source*, not compiled CSS */
@import "../../vendor/idct/adminata/assets/css/theme.css";
@import "../../vendor/idct/adminata/assets/css/base.css";
@import "../../vendor/idct/adminata/assets/css/components.css";
@import "../../vendor/idct/adminata/assets/css/vendor.css";

/* scan adminata's templates + your own overrides + PHP admins */
@source "../../vendor/idct/adminata/src/Resources/views";
@source "../../vendor/idct/adminata/assets/js";
@source "../../templates";
@source "../../src/Admin";
/* other Sonata bundles you override (form-extensions, media, ...) */
@source "../../vendor/sonata-project/form-extensions/src/Bridge/Symfony/Resources/views";
```

Then swap the stylesheet:

```yaml
# config/packages/sonata_admin.yaml
sonata_admin:
    assets:
        remove_stylesheets:
            - bundles/sonataadmin/app.css          # adminata keeps this default path for BC (see §8.3)
        extra_stylesheets:
            - { path: 'build/admin.css', package_name: null }   # Encore/Vite output, default asset package
```

(`package_name: null` is explicitly allowed since 4.x, `Configuration.php:816-820`; with AssetMapper use the logical path instead, §9.)

Toolchains:

- **Symfony AssetMapper + `symfonycasts/tailwind-bundle`**: `composer require symfonycasts/tailwind-bundle`, `bin/console tailwind:init`, `symfonycasts_tailwind: { input_css: ['assets/styles/admin.css'] }`, `bin/console tailwind:build --minify`; the bundle downloads the standalone Tailwind CLI binary — Tailwind v4 support lands via its `binary_version` setting (verify the minimum bundle version that ships a v4 default; pin `binary_version: v4.1.x`). Because the standalone CLI has no `@tailwindcss/forms` plugin resolution from `node_modules`, adminata must **inline** the forms reset it needs into `base.css` rather than `@plugin "@tailwindcss/forms"`.
- **Webpack Encore**: `.enablePostCssLoader()` + `postcss.config.js` `{ plugins: { '@tailwindcss/postcss': {} } }` (TailAdmin's own setup, `T/postcss.config.js`).
- **Vite (`pentatrion/vite-bundle`)**: `@tailwindcss/vite` plugin.

### 7.3 Option (c) — safelist / "utility-complete" build

- Tailwind v4 has no `safelist` config key; the mechanisms are `@source inline("…")` with brace expansion (v4.1+) and `@source not`. A "complete" build (every utility × every variant) is impossible/multi-MB; v3-style `safelist: [{pattern:/.*/}]` is gone.
- Use `@source inline()` **narrowly** in adminata's own build for the surface that PHP generates from user config and that no template literally contains: grid spans (`{sm:,md:,lg:,}col-span-{1..12}` — `FormMapper::with(['class' => 'col-md-6'])` today maps to Bootstrap grid), badge/label colours (`{bg,text}-{brand,success,error,warning,blue-light,gray}-{50,500,600}`), button variants used by `ListMapper` action templates, `hidden`, `dark:` variants of the same. Keep the list in `assets/css/safelist.css` with a comment per entry explaining which PHP option produces it.
- Everything else falls under (b) or (d).

### 7.4 Option (d) — semantic component classes (`@layer components` / `@utility`)

Give user templates and PHP-generated markup **stable class names** so they never need to know utilities:

```css
/* assets/css/components.css */
@layer components {
  .adm-card        { @apply rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]; }
  .adm-card-header { @apply flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-800; }
  .adm-card-body   { @apply p-4 sm:p-6; }
  .adm-btn         { @apply inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium shadow-theme-xs transition disabled:opacity-50; }
  .adm-btn-primary { @apply adm-btn bg-brand-500 text-white hover:bg-brand-600; }
  .adm-btn-outline { @apply adm-btn border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400; }
  .adm-badge       { @apply inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-sm font-medium; }
  .adm-badge-success { @apply adm-badge bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500; }
  .adm-input       { @apply h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90; }
  .adm-table       { @apply w-full text-left text-theme-sm; }
  /* … alert, form-group, help, tabs, dropdown-menu, modal, pagination, sidebar … */
}
```

(The utility strings above are lifted from `T/src/partials/table/table-01.html:1-15`, `T/src/partials/buttons/button-01.html:2-6`, `T/src/partials/badge/badge-01.html:3-6`, `T/src/partials/header.html:127`, `T/src/partials/alert/alert-success.html:1-3`, `TR/src/components/form/Select.tsx:33`.)

Note Tailwind v4 semantics: classes defined in `@layer components` are **not** tree-shaken by source scanning — they always ship — which is exactly what a bundle wants; `@utility` classes *are* only emitted when used, so reserve `@utility` for TailAdmin-style helpers (`menu-item*`, `no-scrollbar`, `custom-scrollbar`, `T/src/css/style.css:190-267`) and use `@layer components` for the `.adm-*` API.

**Bootstrap-3 compat layer (strongly recommended, optional file).** Sonata users have ten years of `'class' => 'btn btn-danger'`, `label label-success`, `col-md-6`, `table table-striped`, `box box-primary`, `alert alert-warning`, `form-control`, `pull-right`, `hidden-xs` in their admins, dashboard blocks, and overridden templates (Sonata's own `base_list_field.html.twig`, `block_search_result.html.twig:20-21`, `block_admin_list.html.twig:18-29`, block-bundle `panel panel-default` in `BB/src/Resources/views/Block/block_core_rss.html.twig:15-31`, ORM `block_audit.html.twig`). Ship `assets/css/compat-bootstrap3.css` in `@layer components` mapping the ~60 most common BS3/AdminLTE classes onto `.adm-*` equivalents (`.btn{@apply adm-btn}`, `.btn-primary{@apply adm-btn-primary}`, `.label-success{@apply adm-badge-success}`, `.col-md-6{@apply md:col-span-6}` inside a `.row{@apply grid grid-cols-12 gap-4}`, `.box{@apply adm-card}`, `.box-header{@apply adm-card-header}`, `.form-control{@apply adm-input}`, `.hidden-xs{@apply max-sm:hidden}`, `.pull-right{@apply float-right}`, `.text-danger`, `.alert-*`, `.table*`, `.nav-tabs`, `.dropdown-menu`, `.modal*`). Enabled by default in 1.x (`adminata.assets.bootstrap_compat: true` adds `bundles/adminata/compat-bootstrap3.css` to the stylesheet list), removable via `remove_stylesheets`. This is the second biggest drop-in lever after Font Awesome.

### 7.5 Recommended layered approach

1. **Ship prebuilt** (`a`): `bundles/adminata/app.css` (= tokens + base + components + compat + utilities scanned from adminata templates + narrow `@source inline()` safelist) and `bundles/adminata/app.js`. Zero-Node install path preserved.
2. **Semantic classes** (`d`) are the public styling API for user templates and PHP options; document `.adm-*` in `docs/reference/styling.rst`; keep BS3 compat layer for 1.x.
3. **Recipe** (`b`) for teams that want arbitrary utilities or their own brand: AssetMapper + tailwind-bundle first, Encore second, Vite third. Ship `assets/css/*.css` as importable source (they must be `@reference`-free plain Tailwind v4 CSS; anything using `@apply` inside is fine because they are imported into the app's root stylesheet, not compiled separately).
4. **Safelist** (`c`) only for PHP-generated grid/colour classes, maintained next to the PHP that emits them.

### 7.6 Exact snippets

`assets/css/theme.css` (derived from `T/src/css/style.css:6-166`, with fonts self-hosted):

```css
@import "@fontsource-variable/outfit";           /* self-hosted; replaces TailAdmin's Google Fonts @import (style.css:1-2) */

@custom-variant dark (&:where(.dark, .dark *));  /* Tailwind's documented class strategy; TailAdmin uses (&:is(.dark *)) which does not match the .dark element itself */

@theme {
  --font-sans: "Outfit Variable", ui-sans-serif, system-ui, sans-serif;

  --breakpoint-2xsm: 375px;  --breakpoint-xsm: 425px;  --breakpoint-3xl: 2000px;

  --text-title-2xl: 72px; --text-title-2xl--line-height: 90px;
  --text-title-xl: 60px;  --text-title-xl--line-height: 72px;
  --text-title-lg: 48px;  --text-title-lg--line-height: 60px;
  --text-title-md: 36px;  --text-title-md--line-height: 44px;
  --text-title-sm: 30px;  --text-title-sm--line-height: 38px;
  --text-theme-xl: 20px;  --text-theme-xl--line-height: 30px;
  --text-theme-sm: 14px;  --text-theme-sm--line-height: 20px;
  --text-theme-xs: 12px;  --text-theme-xs--line-height: 18px;

  --color-brand-25: #f2f7ff;  --color-brand-50: #ecf3ff;  --color-brand-100: #dde9ff; --color-brand-200: #c2d6ff;
  --color-brand-300: #9cb9ff; --color-brand-400: #7592ff; --color-brand-500: #465fff; --color-brand-600: #3641f5;
  --color-brand-700: #2a31d8; --color-brand-800: #252dae; --color-brand-900: #262e89; --color-brand-950: #161950;
  /* blue-light-*, gray-* (incl. --color-gray-dark: #1a2231), orange-*, success-*, error-*, warning-*, theme-pink-500, theme-purple-500: copy verbatim from style.css:57-138 */

  --shadow-theme-xs: 0px 1px 2px 0px rgba(16,24,40,.05);
  --shadow-theme-sm: 0px 1px 3px 0px rgba(16,24,40,.1), 0px 1px 2px 0px rgba(16,24,40,.06);
  --shadow-theme-md: 0px 4px 8px -2px rgba(16,24,40,.1), 0px 2px 4px -2px rgba(16,24,40,.06);
  --shadow-theme-lg: 0px 12px 16px -4px rgba(16,24,40,.08), 0px 4px 6px -2px rgba(16,24,40,.03);
  --shadow-theme-xl: 0px 20px 24px -4px rgba(16,24,40,.08), 0px 8px 8px -4px rgba(16,24,40,.03);
  --shadow-focus-ring: 0px 0px 0px 4px rgba(70,95,255,.12);

  --z-index-1: 1; --z-index-9: 9; --z-index-99: 99; --z-index-999: 999; --z-index-9999: 9999; --z-index-99999: 99999; --z-index-999999: 999999;
}
```

Do **not** copy TailAdmin's `--font-*: initial;` / `--breakpoint-*: initial;` resets (`style.css:9,12`): they delete Tailwind's default `font-mono` and `sm..2xl` breakpoints, which user templates will expect.

`assets/css/app.css` (bundle build entry):

```css
@import "tailwindcss";
@import "./theme.css";
@import "./base.css";
@import "./components.css";
@import "./compat-bootstrap3.css";
@import "./vendor.css";
@import "./safelist.css";

@source "../../src/Resources/views";
@source "../js";
@source not "../js/**/*.test.js";
```

`assets/css/safelist.css`:

```css
/* Classes emitted by PHP from user config; keep in sync with FormMapper::with(), ListMapper, Admin::getListModes(). */
@source inline("{sm:,md:,lg:,xl:,}col-span-{1,2,3,4,5,6,7,8,9,10,11,12}");
@source inline("{,dark:}{bg,text,border}-{brand,success,error,warning,blue-light,gray}-{50,100,500,600}");
@source inline("hidden");
```

Dark mode toggling — recommendation differs from TailAdmin: keep the theme in a **cookie** so Twig can stamp `<html class="dark">` server-side (no FOUC, no inline script, CSP-clean), mirroring how `sonata_sidebar_hide` already works (`standard_layout.html.twig:96-98`):

```twig
{# standard_layout.html.twig #}
{% set _theme = app.request.cookies.get('sonata_theme', sonata_config.getOption('default_theme')) %}
<html {% block html_attributes %}class="no-js{% if _theme == 'dark' %} dark{% endif %}" data-theme="{{ _theme }}"{% endblock %}>
```

```js
// controllers/theme_controller.js  (identifier adm-theme)
toggle() {
  const dark = !document.documentElement.classList.contains('dark');
  document.documentElement.classList.toggle('dark', dark);
  document.cookie = `sonata_theme=${dark ? 'dark' : 'light'};path=/;max-age=31536000;SameSite=Lax`;
  this.dispatch('changed', { detail: { dark } });   // adm-theme:changed for user charts etc.
}
```

`system` mode: emit `class="dark"` only when the cookie says `dark`; when the cookie is `system`, include a 3-line inline script with the CSP nonce block (§8.5) that reads `matchMedia('(prefers-color-scheme: dark)')` before first paint — this is the one inline script worth keeping.

---

## 8. Build tool for the bundle itself

### 8.1 Choice

| Option | Pros | Cons |
|---|---|---|
| Keep Webpack Encore + `@tailwindcss/postcss` | Zero-change for Sonata contributors; stimulus-bridge lazy loading; `entrypoints.json`/`manifest.json` identical. | Slow, Babel, sass toolchain no longer needed; `require.context` ties controllers to Webpack; Encore's manifest is pointless for a bundle (apps do not read it). |
| **Vite 6 (library/`build.rollupOptions` with fixed names) + `@tailwindcss/vite` + Vitest** | Same toolchain for build and tests (form-extensions already uses Vitest, `FE/vite.config.js`); native ESM; fast; Tailwind v4 first-class; can emit both `app.js` (IIFE, globals) and `app.esm.js` (for importmap, §9). | Need `build.rollupOptions.output.entryFileNames: 'app.js'`, `assetFileNames`, `cssCodeSplit: false` to get stable, un-hashed names; no `entrypoints.json` (write a tiny plugin or a static file). |
| Tailwind CLI + esbuild | Simplest; two commands. | Two configs, no single dev server, fewer plugins (esbuild has no CSS `@import` resolution of `node_modules` fonts without plugins). |

**Recommendation: Vite + `@tailwindcss/vite`, Vitest for tests**, matching form-extensions' direction and the fork's preference for modern, minimal tooling. Node `>=22` (Tailwind v4 needs 20+; the machine has 24).

### 8.2 What is committed under `src/Resources/public`

```
src/Resources/public/
  app.js                 # IIFE, exposes window.{Admin,stimulus,sonataApplication,adminata}
  app.esm.js             # ESM build for importmap (§9)
  app.css                # prebuilt Tailwind (§7.5)
  compat-bootstrap3.css  # optional BS3/AdminLTE class layer
  vendor/jquery.js       # BC shim, removable via remove_javascripts
  fonts/                 # fa-solid-900.woff2, fa-regular-400.woff2, fa-brands-400.woff2, outfit-variable.woff2
  images/                # ajax-loader.gif, default_mosaic_image.png, logo_title.png (+ TailAdmin logo svgs if used as defaults)
  entrypoints.json       # keep for parity ({"entrypoints":{"app":{"css":["./app.css"],"js":["./app.js"]}}})
  manifest.json          # keep keyed 'bundles/adminata/…' (cheap, some apps configure json_manifest_path per bundle)
```

Drop: `admin-lte-skins/`, `select2-locale/`, eot/ttf/woff/svg font variants, glyphicons.

### 8.3 Public path naming — the drop-in decision

`sonata_admin.assets.stylesheets` defaults contain the literal string `bundles/sonataadmin/app.css` (`Configuration.php:631-634, 665-668`) and users' `remove_stylesheets`/`remove_javascripts` config match on that string (`SonataAdminExtension.php:276-286`; tested in `tests/DependencyInjection/SonataAdminExtensionTest.php:133-164`). If adminata replaces the bundle class but keeps the bundle *name* `SonataAdminBundle` (which the task implies: same service ids, `@SonataAdmin/...` template namespace), `assets:install` will still create `public/bundles/sonataadmin/` and the default paths stay valid. If the bundle is renamed `AdminataBundle`, the default asset paths must change to `bundles/adminata/…` **and** user `remove_*` entries referencing `bundles/sonataadmin/app.css` silently stop matching — flag in UPGRADE. Recommendation: whatever the bundle name, keep `getPath()`/`assets:install` target `sonataadmin` (override `Bundle::getName()`? no — `assets:install` uses `preg_replace('/bundle$/', '', strtolower($bundle->getName()))`; so the bundle class must be named `SonataAdminBundle` to get `sonataadmin`). This is a project-owner decision (open question).

### 8.4 Versioning, source maps

- Keep `LastModifiedVersionStrategy` (`?v=mtime`) and the `sonata_admin` asset package unchanged — it is what makes un-hashed file names cache-safe.
- Sonata ships **no source maps** (`webpack.config.js:21`). adminata: emit `app.js.map`/`app.css.map` **only in dev builds**; commit production without maps (keeps the repo small; Vite `build.sourcemap: false` for `production`, `'hidden'` optional).
- CI: same "build then `git diff --exit-code`" gate as `S/.github/workflows/frontend.yaml:46-49`, plus `size-limit` budgets.

### 8.5 CSP

- Sonata today needs `script-src 'unsafe-inline'` (7 inline `<script>` blocks, 15 `onclick=` attributes, §5) — it is not CSP-strict.
- adminata: Stimulus needs nothing special; Tom Select/SortableJS no eval; **no Alpine ⇒ no `'unsafe-eval'`**. Remaining inline scripts (Twig blocks users hook into, the `system` theme pre-paint script) should render `<script{{ block('sonata_script_attributes') }}>` where `sonata_script_attributes` is an empty block users override to add `nonce="{{ csp_nonce('script') }}"` (NelmioSecurityBundle). Replace `onclick=` with `data-action` in adminata's own templates; keep the global functions for overridden templates.
- Inline `style=` attributes: Sonata uses a few (`base_list.html.twig:190`, `standard_layout.html.twig:211`); Tailwind removes the need — but `Admin.setup_list_modal` sets inline styles (`admin.js:39-54`) → replace with classes.

### 8.6 i18n of JS strings

- Keep the `<meta name="sonata-translations">` JSON mechanism (`standard_layout.html.twig:46-49`, `translation.js`) and extend the keys: `CONFIRM_EXIT`, `SELECT_NO_RESULTS`, `SELECT_LOADING`, `SELECT_TYPE_TO_SEARCH`, `SELECT_INPUT_TOO_SHORT`, `EDITABLE_SAVE`, `EDITABLE_CANCEL`, `EDITABLE_EMPTY`, `MODAL_CLOSE`, `THEME_TOGGLE`. All come from `SonataAdminBundle` XLIFF domain via `|trans` so existing translation overrides keep working.
- Locale-specific vendor files: none remain (select2 locales dropped; Tom Select is string-configured; Tempus Dominus locales are form-extensions' business, loaded by its controller via dynamic import, `FE/…/datepicker_controller.js:67`). `canonicalize_locale_for_select2()` stays registered (returns same value) for overridden layouts.

---

## 9. Symfony AssetMapper / importmap compatibility

Facts:

- Sonata 4.43 is **Encore-shaped but app-agnostic**: it never uses `encore_entry_*` Twig functions; it emits `<link>`/`<script>` with `asset(path, package)` from a plain list (`standard_layout.html.twig:52-64`). Its `manifest.json`/`entrypoints.json` are unused by-products. The only hard requirement is that `public/bundles/sonataadmin/app.{js,css}` exist — `assets:install` provides that regardless of AssetMapper. `src/Asset/LastModifiedVersionStrategy` is a plain `symfony/asset` strategy.
- AssetMapper apps work with Sonata 4.43 today as long as `framework.asset_mapper` does not disable classic `asset()` paths; the bundle's JS is an IIFE with globals, which AssetMapper serves unchanged from `public/bundles/`.
- `form-extensions` expects `window.sonataApplication` — an importmap-based app cannot `import` Sonata's controllers because `app.js` is not ESM and `stimulus-bridge`'s lazy loader is Webpack-only (`stimulus.js:17-23`).

Recommendation for adminata:

1. Keep the classic path (globals IIFE + `assets:install`) as the default — that is what "drop-in" means.
2. Additionally ship `app.esm.js` (same code, ESM, no auto-start) and register an AssetMapper path so `importmap.php` can do `'@idct/adminata' => ['path' => '@idct/adminata/app.esm.js']`, exposing `startAdminata({ application })` so an app using `@symfony/stimulus-bundle`'s `bootstrap.js` can register adminata's controllers into **its** Stimulus application instead of a second one. Register the path in the extension: `$container->prependExtensionConfig('framework', ['asset_mapper' => ['paths' => [__DIR__.'/../Resources/public' => '@idct/adminata']]])` guarded by `isset($bundles['FrameworkBundle'])` and `class_exists(AssetMapper::class)`.
3. Document that a page must have exactly one Stimulus `Application`: if the app already runs one, set `sonata_admin.assets.remove_javascripts: [bundles/sonataadmin/app.js]` and import the ESM build; adminata then sets `window.sonataApplication = application` (needed by form-extensions).
4. Tailwind under AssetMapper: `symfonycasts/tailwind-bundle` recipe (§7.2). The prebuilt `app.css` still works untouched for AssetMapper users who do not want Node.

---

## 10. Test strategy for JS

Sonata has none. form-extensions has the right shape (Vitest + jsdom + testing-library, `FE/vite.config.js`, `FE/assets/js/controllers/datepicker_controller.test.js`). The fork's bar is "regression test from the public API surface" (`M/AGENTS.md` §7).

1. **Vitest unit tests (jsdom)** per Stimulus controller, mounting HTML fixtures rendered *from the real Twig templates* (dump fixtures via a PHPUnit test that renders `base_edit_form`, `base_list`, collection widgets with the test kernel, `S/tests/App/AppKernel.php`, and writes them to `tests/fixtures/js/*.html`; JS tests load them). Cover: collection add/delete + events + script re-activation; confirm-exit snapshot/skip; edit tab-with-errors + `_tab`; filter `prepareSubmit` name-stripping and `filters=reset`; filter-list counter/outlets; per-page; readmore ResizeObserver (polyfill/mocked); revision fetch (mock `fetch`); sticky observers (mock); new `adm-*` controllers; `Admin` global surface (every method exists, accepts `document`/Element/jQuery-like, no-ops log); event compatibility (jQuery `.on` receives native events when shim loaded); Tom Select option mapping from `data-sonata-select2-*`; editable payload shape against a mocked `sonata_admin_set_object_field_value`.
2. **Contract snapshot test**: a JSON list of globals, controller identifiers, targets, values, events and data attributes (§4) checked by a Vitest test against the built `app.js` (execute the IIFE in jsdom, introspect `window.Admin`, `sonataApplication.router.modules`), so a refactor cannot silently drop a name.
3. **CSS tests**: PostCSS-parse `app.css` and assert the presence of the `.adm-*` API, the safelist classes, `.dark` variant rules, and size budget.
4. **Panther smoke (PHP)** — the fork already runs Panther with Firefox/Selenium (`M/tests/Functional/BasePantherTestCase.php`, `M/composer.json:349`, `M/.github/workflows/test.yaml`): dashboard renders without console errors; sidebar toggle persists cookie; dark toggle; list filter show/hide + submit; edit form tabs with validation error; `ModelListType` modal open → select → hidden input updated; collection add/delete; sortable reorder updates positions; inline editable save; per-page change; ajax `_xml_http_request` list inside modal hides batch/action columns. Assert `window.console` has no errors via `$client->getWebDriver()->manage()->getLog('browser')`.
5. **Twig lint** already in Makefile (`lint:twig src tests`) — keep; add a `stimulus_controller` attribute linter (grep that every `data-controller` in templates has a registered controller).
6. Run eslint (flat config, `airbnb-base`→`@eslint/js` + `prettier`, keep the licence-header rule but with adminata's header), stylelint with a Tailwind-aware config (`stylelint-config-tailwindcss` or `at-rule-no-unknown` ignore list for `@theme @utility @custom-variant @source @variant @apply @reference`), prettier with `prettier-plugin-tailwindcss` (TailAdmin uses it, `T/package.json:33`).

---

## 11. Risks

| # | Risk | Severity | Mitigation |
|---|---|---|---|
| R1 | **Bootstrap/AdminLTE class strings in user config and overridden templates** (`btn btn-*`, `col-md-*`, `label-*`, `box`, `form-control`, `hidden-xs`, `pull-right`) render unstyled. | High | BS3 compat layer (§7.4) on by default in 1.x; `.adm-*` semantic API; document the mapping table. |
| R2 | **`fa fa-*` / `fas fa-*` icon strings** in dashboard groups, menus, list modes, actions. `parse_icon` rejects anything else (`IconRuntime.php:27-34`). | High | Keep Font Awesome 6 Free webfont + v4-shims; do not move `parse_icon` to SVG in 1.x. |
| R3 | **User JS depending on jQuery plugins Sonata used to bundle** (`.select2()`, `.iCheck()`, `.editable()`, `.sortable()`, `.ajaxSubmit()`, `.modal()`, `.tab()`), or on iCheck events `ifChanged`. | High | jQuery global shim + UPGRADE table listing each plugin and its replacement; `Admin.setup_*` names kept as facades; BS3 data-API delegate for `data-toggle`/`data-dismiss`. |
| R4 | **Scripts execute synchronously in `<head>`**; user `extra_javascripts` and inline scripts assume `jQuery`/`Admin`/`sonataApplication` exist at parse time. | Medium | Keep blocking head loading in 1.x; provide `sonata:ready` event and `defer` migration in 2.x. |
| R5 | **form-extensions coupling**: needs `window.sonataApplication` and `bundles/sonataform/app.{js,css}` (Tempus Dominus, Bootstrap-ish `.input-group.date` markup, FA5 icons `faFiveIcons`, `FE/…/datepicker_controller.js:30,127`). Its CSS is Bootstrap-flavoured and light-only. | Medium | Keep the global; ship `compat` rules for `.input-group`/`.input-group-addon`/`.tempus-dominus-widget` incl. dark mode; longer term propose an `idct/form-extensions` theme or a flatpickr-based `datepicker` controller with the same identifier/values/events. |
| R6 | **Tailwind purging vs user templates** (utilities not in prebuilt CSS). | Medium | §7 layered strategy; loud documentation; `bin/console adminata:tailwind:check` (optional) that scans `templates/` for utilities missing from `app.css` and prints the recipe. |
| R7 | **Tailwind v4 browser floor** (Safari 16.4+, Chrome 111+, Firefox 128+ — `@property`, `color-mix()`, cascade layers). Admin users on older enterprise browsers get broken CSS. | Medium | State the floor in README; offer no fallback (v3 is not an option with TailAdmin v2.3). |
| R8 | **Bundle rename vs `bundles/sonataadmin/*` default paths and `remove_*` matching** (§8.3). | Medium | Keep bundle name/asset dir `sonataadmin` or ship a config normaliser that rewrites `bundles/sonataadmin/` → `bundles/adminata/` in `assets.*` with a deprecation. |
| R9 | **`skin` enum and `admin-lte-skins/*.css`**: users set `skin: skin-blue`; `SonataAdminExtension.php:94-100` unconditionally appends the skin CSS path. Removing the file yields a 404 `<link>` (harmless) but the enum must stay for config validation. | Low | Keep enum; map to `data-skin`/`--color-brand-*` palettes; stop appending the CSS path; tests in `SonataAdminExtensionTest` that assert the skin path must be updated. |
| R10 | **Events emitted with jQuery `.trigger()` by user code** are invisible to adminata's native listeners. | Low | Shim re-dispatch (§3.2) + docs. |
| R11 | **CSP**: apps with strict CSP break on remaining inline scripts/`onclick`. Same as Sonata today, so not a regression. | Low | Nonce block (§8.5). |
| R12 | **Google Fonts / external requests** if TailAdmin's `@import url(fonts.googleapis.com)` is copied. | Low | Self-host Outfit (`@fontsource-variable/outfit`). |
| R13 | **Shift-click range bug** (`admin.js:173`) is a latent behaviour users may have "adapted" to; fixing it changes behaviour. | Low | Fix it; mention in CHANGELOG. |
| R14 | **Tempus Dominus and Tom Select both render popups that must escape `<dialog>`/overflow containers**; `dropdownParent` logic (`sonata_type_model_autocomplete.html.twig:81`) must target the open `<dialog>`. | Medium | `adm-select` sets `dropdownParent: el.closest('dialog') ?? 'body'`; Tempus Dominus uses `container` option — form-extensions passes options through `datepicker_options` so apps can set it; adminata can prepend a global default via a form type extension. |
| R15 | **Third-party Sonata bundles' templates** (Media, User, Classification, Page) still use BS3/AdminLTE markup and jQuery inline scripts. | Medium | Compat layer + jQuery shim cover most; publish an "ecosystem status" page. |

---

## 12. Open questions for the project owner

1. Bundle class/name: keep `SonataAdminBundle` (so `@SonataAdmin/…`, `bundles/sonataadmin/…`, `sonata_admin:` stay literally identical) or rename to `AdminataBundle` with aliases? (§8.3, R8)
2. Is the global jQuery shim acceptable in 1.x, and is 2.x the agreed removal point? (§3.2)
3. Should the Bootstrap-3 compat CSS layer be on by default (recommended) or opt-in? (§7.4)
4. Font Awesome 6 webfont (BC, ~100 KB CSS + ~300 KB woff2) vs SVG-only icons for adminata chrome plus FA for user config — accept the hybrid? (§2)
5. Dark-mode persistence via cookie (server-rendered, recommended) vs TailAdmin's `localStorage` — cookie adds a cookie to every request; acceptable?
6. Which app toolchain gets first-class docs and a CI smoke test: AssetMapper + tailwind-bundle (recommended), Encore, or Vite?
7. Do we own a dark-mode/Tailwind theme for `sonata-project/form-extensions` (Tempus Dominus) inside adminata, or fork form-extensions too?
8. Node version floor for contributors (22 vs 24) and whether prebuilt assets are committed (recommended, mirrors Sonata) or published as a release artefact.
