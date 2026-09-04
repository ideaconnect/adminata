# adminata research — gap G3: audit of the production app `recomaty-panel` as the drop-in acceptance case

Path aliases used throughout:

| Alias | Root |
|---|---|
| `APP/` | `/home/bartosz/dev/r3/recomaty-panel/` (the production app) |
| `S/` | `scratchpad/sonata-admin-4.43.0/` (upstream Sonata Admin, the fork base) |
| `R/` | `scratchpad/research/` (prior reports; decisions C1–C25 are from `R/critique-round-1.md` §3, coexistence rules R1–R12 from `R/gap-js-architecture.md` §4) |
| `MDB/` | `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle/` |
| `VENDOR/` | `APP/vendor/` (Symfony 8.1, ux-autocomplete 2.36, twig-extensions 2.6, form-extensions 2.7 — with views, unlike the pruned `vendor-extract/`) |

Classification vocabulary (used in every inventory row):

* **KEEPS-WORKING** — the artefact the app depends on is part of adminata's preserved contract (PHP API, service id, config key, template path, block name, `sonata-*` hook, Twig function, route, JS global/event).
* **COMPAT-LAYER** — works only because adminata ships `bundles/sonataadmin/compat-bootstrap3.css` (C21, `@layer components`, C13), the jQuery global `bundles/sonataadmin/vendor/jquery.js` (C2) or the `data-toggle`/`data-dismiss` delegate (`R/gap-js-architecture.md` §1.5).
* **MUST-PORT** — the user has to edit; the replacement is given.
* **BREAKS** — no mitigation inside adminata; explained.

---

## 0. Facts, method and headline findings

### 0.1 Versions (from `APP/composer.lock` / `APP/package.json`)

| Package | Version | Evidence |
|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | `APP/composer.lock` (`"name": "sonata-project/admin-bundle"` → `"version": "4.43.0"`) |
| `idct/sonata-admin-mongodb-bundle` | v5.2.2 (one admin: `App\Document\DebugRequest`, `manager_type: doctrine_mongodb`) | `APP/config/services/admin/debug.yaml:9`; `APP/src/Admin/Debug/DebugRequestAdmin.php:15-17` |
| `sonata-project/doctrine-orm-admin-bundle` | 4.21.0 (35 ORM admins) | `APP/composer.lock` |
| `sonata-project/form-extensions` / `twig-extensions` / `block-bundle` / `exporter` | 2.7.0 / 2.6.0 / 5.4.0 / 3.4.0 | `APP/composer.lock` |
| `symfony/framework-bundle`, `form`, `security-bundle`, `twig-bundle` | v8.1.0 | `APP/composer.lock` |
| `symfony/ux-autocomplete` | v2.36.0 (`tom-select` ^2.2.2 → 2.5.2 installed) | `APP/composer.lock`; `APP/package.json:20` |
| `symfony/stimulus-bundle` 3.1 + `@symfony/stimulus-bridge` ^4 + `@hotwired/stimulus` ^3 | second Stimulus `Application` | `APP/package.json:7-8`; `APP/assets/bootstrap.js:4-8` |
| `symfony/webpack-encore-bundle` 2.4 / `@symfony/webpack-encore` ^6 | Encore build, `enableVersioning(false)` ("sonata being retarted in that topic", sic) | `APP/webpack.config.js:63` |
| `knplabs/knp-menu-bundle` 3.7 | sidebar | `APP/composer.lock` |
| PHP `>=8.5` | | `APP/composer.json:7` |

### 0.2 Method

Every file named in the brief was read in full (config, 8 `config/packages/*` + 3 `config/routes/*`, all 96 templates under `APP/templates/`, all 40 admin classes' option keys, the 11 `CRUDController` subclasses plus `AdminSecurityController`/`DashboardStatsController`/`LocaleController`, every file under `APP/assets/`, `webpack.config.js`, `package.json`, the 14 admin service YAMLs) and compared with the upstream template each override copies. Vendor code that decides behaviour (Symfony's `SameOriginCsrfTokenManager`, ux-autocomplete's form extension and Stimulus controller, twig-extensions' flash template and type map) was read in `VENDOR/`.

### 0.3 Headline findings

1. **The app is a near-ideal drop-in fixture**: 17 bundle overrides, a custom `templates.layout`/`list`/`user_block`, 52 field templates, 9 page templates extending `@SonataAdmin/standard_layout.html.twig`, 17 `col-md-*` group classes, raw `<i class="fas …">` dashboard icons, a second Stimulus app, ux-autocomplete inside Sonata filters **and** inside one admin form, stateless CSRF, `use_select2: false`, `lock_protection: true`, and 301 + 360 lines of CSS that fight AdminLTE. Only **two** things in it have no mitigation: `$('#universal-modal').modal()` (`APP/assets/admin/UniversalModal.js:8,18`) and the already-dead Flowbite alert embed (`APP/templates/layout/standard_layout_override.html.twig:72` — `templates/flowbite/` does not exist, `import 'flowbite'` is commented out at `APP/assets/app.js:18`).
2. **Three overrides are dead today**: `templates/bundles/SonataAdminBundle/CRUD/Association/base_list_inner_row.html.twig` (upstream file lives at `CRUD/base_list_inner_row.html.twig`, `S/src/Resources/views/CRUD/` listing; the override also reads an undefined `rendered` variable at `:20`), the `javascripts` block's moment-locale branch (`canonicalize_locale_for_moment()` is a deprecated no-op returning `null`, `S/src/Twig/CanonicalizeRuntime.php:35-45`; `public/bundles/sonataadmin/moment-locale/` does not exist), and the login page's `glyphicon` spans (Sonata ships no Glyphicons font: `S/src/Resources/public/fonts/` contains only `fa-*`). `templates/generic_create.html.twig`, `templates/message/*.html.twig`, `templates/recomat/confirm_archive.html.twig` and `templates/promo_code/upload.html.twig` are referenced by no controller/config (grep over `APP/src`, `APP/config`).
3. **The app's own "modern admin theme"** (`APP/assets/styles/admin-theme/*.scss`: slate-800 sidebar, white header with shadow, blue active item, 8 px radii) is what TailAdmin ships natively. Under adminata these 360 `!important`-laden lines become dead weight that will *fight* the new sidebar through the kept marker classes (`main-sidebar`, `sidebar-menu`, `treeview-menu`, `main-header`); the recommendation is deletion, not porting.
4. **Stateless CSRF matters more than the reports assumed**: with `framework.form.csrf_protection.token_id: submit` and `stateless_token_ids: [submit, authenticate, logout]` (`APP/config/packages/csrf.yaml:3-11`) every Sonata edit/create form token is stateless. `SameOriginCsrfTokenManager::isTokenValid` accepts an origin-only request **until** the session has once recorded a double-submit success, after which a request without the cookie/token pair is rejected ("double-submit info was used in a previous request but is now missing", `VENDOR/symfony/security-csrf/SameOriginCsrfTokenManager.php:151-156`). Any adminata `fetch()`-based form POST must therefore run the same client-side minting the app's `csrf_protection_controller.js` does (or dispatch a real `submit`). Sonata's `sonata.delete`/`sonata.batch` tokens stay session-based (not in the stateless list) — unchanged.
5. **FontAwesome**: Sonata 4.43 itself loads `v4-shims.css` (`S/assets/scss/app.scss:16`); the app relies on it for `fa fa-clock-o` (`APP/templates/field/rvmTaskState.html.twig:6`) and on the CDN FA 6.7.2 link (`standard_layout_override.html.twig:6-8`) for 10 FA6-only names (`fa-gauge-high`, `fa-cart-shopping`, `fa-table-list`, `fa-file-lines`, `fa-gear`, `fa-up-right-from-square`, `fa-screwdriver-wrench`, `fa-triangle-exclamation`, `fa-circle-check`, `fa-check-double`). C20 (FA6 Free + v4 shims) covers both; the CDN link can then go.
6. **Dark mode is the biggest cosmetic regression source**: 24 hard-coded pastel `background-color`s in cell templates (`templates/field/*State*.html.twig`), zebra rows `#f7f9ff/#fcfcff` (`sonata-overrides.scss:113-119`), `body{background:lightgray}` (`app.scss:1-3`), accordion greys (`transaction-items-accordion.scss:8,20,26`). All keep working in light mode; in `.dark` they produce light cells with light text. This is an `UPGRADE-1.0.md` section, not an adminata bug.
7. **Two Tailwind utility-name collisions** exist in app code: `.mt-10 {margin-top:10px}` (`sonata-overrides.scss:13-15`, used by `PasswordChangeForm.php:66` `row_attr.class = 'text-right mt-10'`) versus Tailwind's `mt-10` = 2.5 rem, and `class="hidden"` toggled by jQuery (`SectionSlider.js:10-12`) plus `'section-geolocation col-md-12 hidden'` group classes (`PartnerPromoAdmin.php:290,300`) which need Bootstrap's `!important`. `img.modal-content` (`templates/field/imagePreviewPromoPromoted.html.twig:12`; `image-preview.css:26-31`) collides with compat `.modal-content` exactly as it collides with Bootstrap today.
8. **ux-autocomplete coexistence is real**: `RecomatGroupAdmin::configureFormFields` puts a multiple `RecomatAutocompleteField` (ux-autocomplete `BaseEntityAutocompleteType`) inside a Sonata admin form (`APP/src/Admin/Devices/RecomatGroupAdmin.php:57-64`), three `CallbackFilter`s use ux-autocomplete field types (`RecomatAdmin.php:203`, `RecomatManagerAdmin.php:114`, `TransactionAdmin.php:106`, `SmartLoginAdmin.php:69`), and two `ModelFilter`s use Sonata's own `ModelAutocompleteType` (`RecomatAdmin.php:213`, `RecomatManagerAdmin.php:135`). Rule R5 (skip `[data-controller*="autocomplete"]`, `[data-sonata-select2="false"]`, `el.tomselect`) is exercised by all of them.
9. **The app has no modal-create, no inline (`sonata_type_collection` `edit: inline`) and no `editable` cells** (grep of `'editable'`, `ModelListType`, `'edit' => 'inline'` over `APP/src` returns nothing). The brief's "create via modal with ux-autocomplete field" scenario cannot be run against this app; §3.4 substitutes "create page with ux-autocomplete field" (`transaction/create_manual`, `CreateManualTransactionType.php:57`) and "Sonata `CollectionType` add/remove" (`RecomatAdmin.php:344-357`).
10. **Contract additions adminata must adopt because of this app** (all cheap; §5): keep the layout's top-level `_skin`/`_use_select2`/… variables (the app's `admin_lte_skin_class` block reads `_skin` and `when@test: twig.strict_variables: true`, `APP/config/packages/twig.yaml:6-8`); keep `nav navbar-nav` marker classes on the actions/filters `<ul>` and `navbar navbar-default` on the toolbar; ship `.hidden{display:none!important}` in the compat layer; keep `table.sonata-ba-list` in the `ajax_layout` list (the app's XHR accordion parses it, `TransactionItemsAccordion.js:38`); keep `<li>` pass-through for `user_block` and `Button/*` includes; keep `canonicalize_locale_for_moment()`.

---

## 1. Coupling inventory

### 1.1 Configuration keys

| # | Item | File:line | Upstream artefact | Class | Note / replacement |
|---|---|---|---|---|---|
| C1 | `sonata_admin.title_logo: build/images/r3-logo.png` | `APP/config/packages/sonata_admin.yaml:2` | `Configuration.php:252`, `sonata_config.logo` (`S/src/SonataConfiguration.php:64`) | KEEPS-WORKING | Logo is a PNG made white by `filter: brightness(0) invert(1)` (`_header.scss:38`); under TailAdmin's white sidebar the filter must go (see CSS rows). |
| C2 | `title: 'Panel R3'` | `:3` | `sonata_config.title` | KEEPS-WORKING | |
| C3 | `search: false` | `:4` | `Configuration.php:253`, `standard_layout.html.twig:192` | KEEPS-WORKING | header search hidden. |
| C4 | `show_mosaic_button: false` | `:5` | `Configuration.php` show.mosaic.button param | KEEPS-WORKING | list-mode switcher never rendered. |
| C5 | `security.handler: sonata.admin.security.handler.role`, `role_admin: ROLE_GENERAL_ADMIN` | `:7-9` | service ids (`R/php-compat.md` §2.2) | KEEPS-WORKING | PHP only. |
| C6 | `templates.layout: layout/standard_layout_override.html.twig` | `:11` | registry key `layout` (`Configuration.php:570-616`) | KEEPS-WORKING | template content: §2a. |
| C7 | `templates.user_block: security/user_block.html.twig` | `:12` | key `user_block`; included at `standard_layout.html.twig:165` into `<ul class="dropdown-menu dropdown-user">` (`:171-173`) | KEEPS-WORKING (key) / COMPAT-LAYER (content) | §2b. |
| C8 | `templates.list: crud/list_with_summaries.html.twig` | `:13` | key `list` | KEEPS-WORKING (key) | content: §2c. |
| C9 | `dashboard.blocks: [{type: sonata.admin.block.admin_list, position: left}]` | `:16` | `AdminListBlockService`, default `class: col-md-4` (`Configuration.php:540`) | COMPAT-LAYER | default block `class` is a Bootstrap grid class translated by `sonata_grid_class` / compat grid (C7, `R/layout-nav.md` R5). Nothing to edit. |
| C10 | 19 `dashboard.groups.*.icon: '<i class="fas fa-…"></i>'` (raw HTML) | `:21,28,32,36,40,44,48,57,62,66,76,80,85,89,93,97,101,105,111` | `parse_icon` `<` pass-through (`S/src/Twig/IconRuntime.php:22-24`), rendered by `Menu/sonata_menu.html.twig:27-50` | KEEPS-WORKING | Requires FA6 names (`fa-gauge-high`, `fa-cart-shopping`, `fa-table-list`, `fa-file-lines`, `fa-gear`) → C20 FA6. §2i. |
| C11 | `dashboard.groups.*.items` with `route: admin_dashboard_stats` / `admin_app_addedean_awaiting` / `admin_app_transaction_create` + `roles` | `:23,52-54,70-72` | `GroupMenuProvider` | KEEPS-WORKING | PHP only. |
| C12 | `options.use_select2: false  # DO NOT TURN ON!` | `:113` | `Configuration.php:312`; body class `sonata-select2` (`standard_layout:94`), `Admin.setup_select2` | KEEPS-WORKING | §2g. |
| C13 | `options.use_stickyforms: true` | `:114` | `sonata-sticky` controller kept (`R/list-datagrid.md` §8.4) | KEEPS-WORKING | |
| C14 | `options.lock_protection: true` | `:115` | `LockExtension` (PHP), `_lock_version` hidden field via `form_rest` | KEEPS-WORKING | §2g. |
| C15 | `assets.extra_stylesheets: [build/app.css]` | `:118` | `SonataAdminExtension.php:236-285` merge; rendered after defaults in `stylesheets` block (`standard_layout:51-55`) | KEEPS-WORKING | ordering guarantees app CSS wins on equal specificity (R7). |
| C16 | `assets.extra_javascripts: [build/runtime.js, build/app.js, build/image-preview.js]` | `:120-122` | `sonata_javascript_pool` (`:61-65`), blocking scripts (C15 of critique) | KEEPS-WORKING | `build/app.js` needs `window.jQuery` first — provided by `bundles/sonataadmin/vendor/jquery.js` in adminata's default list (C2). See J1. |
| C17 | `sonata_block.blocks.sonata.admin.block.admin_list.contexts: [admin]`, `sonata_block.http_cache: true` | `:123-126`; `sonata_block.yaml:1-2` | block-bundle | KEEPS-WORKING | |
| C18 | `sonata_doctrine_orm_admin.templates.types.list.datetime: field/local_datetime.html.twig` | `sonata_doctrine_orm_admin.yaml:5` | ORM `Configuration.php:50-53` → `TemplateRegistryInterface::LIST_TEMPLATES` override | KEEPS-WORKING | the template extends `get_admin_template('base_list_field', admin.code)` (`templates/field/local_datetime.html.twig:1`). |
| C19 | `sonata_form.form_type: standard` | `sonata_form.yaml:2` | form-extensions | KEEPS-WORKING | |
| C20 | `twig.form_themes: ['@SonataForm/Form/datepicker.html.twig']` (global) | `twig.yaml:4` | form-extensions global theme; JS `bundles/sonataform/app.js` (Tempus Dominus) which adminata **drops from the defaults** (C11) | MUST-PORT | Non-admin pages (`transaction/create_manual` → `CreateManualTransactionType.php:79-81`, `promo_code/create`) render `DateTimePickerType` through this theme and get no picker once `sonataform/app.js` is gone. Replace with adminata's standalone datepicker theme (G5 must ship one that works outside `form_admin_fields`) **or** re-add `bundles/sonataform/app.{js,css}` via `extra_*` *after* `bundles/sonataadmin/app.js` (R12). |
| C21 | `twig.strict_variables: true` (test env) | `twig.yaml:6-8` | — | (risk) | any block override reading a parent variable that adminata renames fatals in tests (see `_skin`, L3). |
| C22 | `framework.form.csrf_protection.token_id: submit`; `csrf_protection.stateless_token_ids: [submit, authenticate, logout]` | `csrf.yaml:3-11` | Symfony `SameOriginCsrfTokenManager` | KEEPS-WORKING (today) / contract for adminata | §2f. |
| C23 | `framework.session.handler_id: redis://…`, `cookie_samesite: lax`; test `mock_file` | `framework.yaml:11-21,45-49` | `sonata.delete`/`sonata.batch` session tokens | KEEPS-WORKING | acceptance suite runs with `mock_file` sessions. |
| C24 | `webpack_encore.script_attributes.defer: true` | `webpack_encore.yaml:8-9` | only affects `encore_entry_script_tags` (unused in admin pages — assets are listed in `sonata_admin.assets`) | KEEPS-WORKING | |
| C25 | Firewall `admin` (`pattern ^/admin`, `form_login` `admin_login`, `enable_csrf: true`, `default_target_path: admin_dashboard_stats`, `logout.path: admin_logout`) | `security.yaml:163-176` | app routes | KEEPS-WORKING | |
| C26 | Routes: `admin_area` → `@SonataAdminBundle/Resources/config/routing/sonata_admin.php`, `_sonata_admin` type `sonata_admin`, prefix `/admin` | `config/routes/sonata_admin.yaml:1-8` | `S/src/Resources/config/routing/sonata_admin.php:36-63` | KEEPS-WORKING | bundle name unchanged (C6 of critique). |
| C27 | `ux_autocomplete` routes prefix `/autocomplete` | `config/routes/ux_autocomplete.yaml:1-3` | — | KEEPS-WORKING | |
| C28 | `home_home: / → admin_dashboard_stats`; `admin_dashboard_home_redirect: /admin/dashboard → admin_dashboard_stats` (priority 10) | `config/routes.yaml:3-10`; `DashboardStatsController.php:48-51` | shadows `sonata_admin_dashboard` (`/admin/dashboard`) | KEEPS-WORKING | acceptance: `sonata_admin_dashboard` redirects; test the Sonata dashboard via `?` — it is unreachable; scenario D2 uses the stats page. |

### 1.2 Layout, login, user block, password change

| # | Item | File:line | Upstream artefact | Class | Replacement |
|---|---|---|---|---|---|
| L1 | `{% extends '@!SonataAdmin/standard_layout.html.twig' %}` | `templates/layout/standard_layout_override.html.twig:1` | `@!` original-template syntax + bundle name | KEEPS-WORKING | |
| L2 | `{% block stylesheets %}{{ parent() }}<link … font-awesome/6.7.2/css/all.min.css …>` | `:4-9` | block `stylesheets` (`S/…/standard_layout.html.twig:51-55`) | KEEPS-WORKING | Drop the CDN link once adminata ships FA ≥ 6 (C20); until then keep (10 FA6-only names, §2i). Double font-face definitions are harmless. |
| L3 | `{% block admin_lte_skin_class %}w-full {{ _skin }}{% endblock %}` | `:11-13` | block at `standard_layout:93`; **`_skin` is a parent-template `{% set %}`** (`:24`) | KEEPS-WORKING **iff** adminata keeps `{% set _skin = sonata_config.getOption('skin') %}` at layout top level (contract addition §5) | `w-full` is now a real Tailwind utility on `<body>` — harmless. The intent ("full width") is met by TailAdmin's window-scroll model (C10); the override can be deleted. |
| L4 | `{% block logo %}` — verbatim copy of upstream `:114-123` (`<a class="logo">`, `sonata_config.getOption('logo_content')`, `asset(sonata_config.logo)`) | `:15-26` | block `logo` | COMPAT-LAYER | Adminata's `logo` block renders TailAdmin sidebar-logo markup (`R/layout-nav.md` §2 row 10); the copy re-emits AdminLTE `<a class="logo">` which only looks right with the app's `_header.scss:14-40` rules. **Delete the override** (it changes nothing versus upstream). |
| L5 | `{% block javascripts %}` re-implements `sonata_javascript_config` (empty) and `sonata_javascript_pool` (identical to upstream `:57-65`) | `:27-35` | blocks kept (`R/layout-nav.md` §8) | KEEPS-WORKING | Delete: identical to parent. |
| L6 | `{% set localeForMoment = canonicalize_locale_for_moment() %}{% if localeForMoment %}<script src="{{ asset('bundles/sonataadmin/moment-locale/pl.js') }}">` | `:38-41` | `canonicalize_locale_for_moment()` — deprecated no-op returning `null` (`S/src/Twig/CanonicalizeRuntime.php:35-45`); `moment-locale/` dir absent from `S/src/Resources/public/` and `APP/public/bundles/sonataadmin/` | KEEPS-WORKING (dead branch; adminata keeps the function, `R/php-compat.md` §2.7) | Delete; it only emits a deprecation per page. Audit rule A-11. |
| L7 | select2 locale script guarded by `sonata_config.getOption('use_select2')`, `canonicalize_locale_for_select2()` | `:44-49` | function kept; `select2-locale/*` files **removed** by adminata (`R/gap-js-architecture.md` §6 row 1) | KEEPS-WORKING (guard is `false`) | Delete with L5. |
| L8 | `{% block sonata_wrapper %}{{ parent() }}` + Bootstrap 3 `#universal-modal` (`.modal > .modal-dialog > .modal-content > .modal-header/.modal-body/.modal-footer`, `btn btn-secondary` (a Bootstrap **4** class), `data-dismiss="modal"`) | `:51-66` | block `sonata_wrapper` (`:186-331`) | COMPAT-LAYER for markup (hidden `.modal` + `[data-dismiss=modal]` delegate) but **BREAKS** as a feature because it is opened by `$('#universal-modal').modal()` (J5) | MUST-PORT → §2a snippet (native `<dialog>` + `sonata-modal`). |
| L9 | `{% block sonata_page_content_header %}` + `{% embed 'flowbite/components/alert.html.twig' with {'type': admin.deprecationLevel.value} %}` guarded by `admin.deprecationMessage` | `:68-80` | block `sonata_page_content_header` (`:225-290`); `BaseAdmin::getDeprecationMessage()` (`APP/src/Admin/BaseAdmin.php:47-48,83-91`) | **BREAKS today** (latent): `templates/flowbite/` does not exist; no admin sets `DEPRECATION_MESSAGE` (grep) so the branch never runs | MUST-PORT: replace the embed with `<div class="alert alert-{{ admin.deprecationLevel.value }}">…</div>` (compat) or adminata's alert partial. |
| L10 | `{% trans_default_domain 'ui' %}` | `:2` | — | KEEPS-WORKING | |
| L11 | Login page extends the custom layout; empties `sonata_nav`, `logo`, `sonata_left_side`; `body_attributes` = `class="sonata-bc login-page"` (drops `stimulus_controller('sonata-sticky')` and `_skin`) | `templates/security/login_form.html.twig:1-12` | blocks kept; upstream `body_attributes` `:92-100` | KEEPS-WORKING (blocks) / COMPAT-LAYER (look) | §2b. Note `sonata_header` (`<header class="main-header">`, `:106`) still renders with only the `<noscript>` inside — under TailAdmin's sticky bordered header this yields an empty bar above the login box unless adminata guards it (§5 item 11). |
| L12 | AdminLTE login markup: `login-box`, `login-box-body`, `login-box-msg`, `login-logo`, `form-group has-feedback`, `form-control`, `glyphicon glyphicon-user/lock form-control-feedback`, `row > col-xs-4`, `btn btn-primary btn-block btn-flat`, `alert alert-danger`, `alert alert-{{ label }}` | `:16-71` (glyphicons `:39,44`) | recipe `S/docs/cookbook/recipe_sonata_admin_without_user_bundle.rst:332-368` | COMPAT-LAYER (`login-*`, `btn-*`, `form-control`, `alert`, `col-xs-4`); glyphicons already render nothing (no font shipped) | MUST-PORT recommended → TailAdmin sign-in (§2b). |
| L13 | `<form action="{{ path("admin_login") }}" method="post">` + `<input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">` (stateless id) | `:34-35` | Symfony | KEEPS-WORKING | Hand-written input has no `data-controller="csrf-protection"`, so the lazy csrf module (`csrf_protection_controller.js:78`) is not loaded on this page; the token stays `= cookie name` and the server accepts on origin (`Sec-Fetch-Site`/`Referer`) alone (`SameOriginCsrfTokenManager.php:220-245`). Fine for adminata; relevant for the BrowserKit scenario (send `Referer`). |
| L14 | `/static/r3_rvm_logo.png`, `/locale/set?t=…` links, `supportedLocales` | `:58,62-68` | app (`APP/public/static/`, `LocaleController.php:26-38`, `AdminSecurityController.php:35`) | KEEPS-WORKING | |
| L15 | `user_block`: `<li class="user-header bg-light-blue">`, `btn btn-default btn-flat`, `fa fa-lock`, `fa fa-sign-out fa-fw`, `path('sonata_admin_edit_own_password')`, `url('admin_logout')` | `templates/security/user_block.html.twig:8-28` | included into `<ul class="dropdown-menu dropdown-user">` (`standard_layout:171-173`); adminata keeps `<ul>` container + `dropdown-user` class (`R/layout-nav.md` §4.3) | COMPAT-LAYER (`.user-header`, `.bg-light-blue`, `.btn-default.btn-flat`) | Routes are the app's own (`AdminSecurityController.php:39,46`) → KEEPS-WORKING. Port → §2b. |
| L16 | `password_change.html.twig`: extends custom layout; overrides `sonata_admin_content` and nests `{% block notice %}{% include ['@SonataCore/FlashMessage/render.html.twig', '@SonataTwig/FlashMessage/render.html.twig'] %}` | `templates/security/password_change.html.twig:1-7` | `notice` block (`standard_layout:296-298`) includes `@SonataTwig/FlashMessage/render.html.twig` (Bootstrap alerts + `data-dismiss="alert"`, `VENDOR/sonata-project/twig-extensions/src/Bridge/Symfony/Resources/views/FlashMessage/render.html.twig:17-21,45-53`) | COMPAT-LAYER (`.alert.alert-*`, `.close`, delegate) | MUST-PORT (1 line): `{% block notice %}{{ parent() }}{% endblock %}` so adminata's own flash partial is used. Same pattern in 8 more page templates (P-rows). |
| L17 | `password_change` markup `sonata-ba-form > row > col-md-12 > box box-primary > box-header/box-title/box-body > sonata-ba-collapsed-fields > form-group > {{ form(form) }}` | `:10-32` | `.sonata-ba-*` hooks kept; `.box*`, `.row`, `.col-md-12`, `.form-group` = compat | COMPAT-LAYER | Optional port to `.adm-card` (G2 semantic API). |

### 1.3 `templates/bundles/SonataAdminBundle/**` (17 files)

| # | Override | Line refs | Copies / depends on | Class | Note |
|---|---|---|---|---|---|
| B1 | `Button/create_button.html.twig` — `<li><a class="sonata-action-element sonata-action-btn" title="…"><i class="fas fa-plus">` (icon-only, no label) | `:1-17` | upstream `S/…/Button/create_button.html.twig:12-30` (`sonata-action-element`, `fas fa-plus-circle` + label); consumed by `sonata_admin_content_actions_wrappers` heuristics `_actions|replace({'<li>':''})`, `_actions|split('</a>')|length > 2` (`standard_layout:266-268`) | KEEPS-WORKING (contract) / COMPAT-LAYER (look) | Styled only by `sonata-overrides.scss:218-242` whose selector requires `.nav.navbar-nav > li > a` — see S17. `admin.subClasses`, `admin.generateUrl('create', {subclass})`, `link_action_create` trans → unchanged. |
| B2 | `CRUD/list__action.html.twig` — drops the `<div class="btn-group">` wrapper | `:12-18` | upstream `:12-20`; `get_admin_template('base_list_field', admin.code)` | KEEPS-WORKING | |
| B3–B6 | `CRUD/list__action_{edit,show,delete,history}.html.twig` — `btn btn-action-icon btn-action-*`, `fas fa-pen/eye/trash-alt/clock`, ignore `list_action_button_content` | each `:1-9` | upstream `list__action_edit.html.twig:12-30` (`btn btn-sm btn-default edit_link`, `fas fa-pencil-alt`, `sr-only` label); `admin.hasAccess/hasRoute/generateObjectUrl`, `actions.link_parameters` | COMPAT-LAYER (light: `.btn` base; everything else is app CSS `sonata-overrides.scss:121-215`) | Drop `view_link`/`edit_link`/`delete_link` hook classes → user JS/tests can no longer find them (none do in this app). Accessibility regression (no `sr-only` text) pre-exists. |
| B7–B13 | 7 custom `CRUD/list__action_{approve_ean,reject_ean,cancel_rvm_task,close_support_thread,open_support_thread,upload,upload_job}.html.twig` — same button recipe + `path('admin_app_*')` | each `:1-11` | `@SonataAdmin/CRUD/list__action_[ACTION].html.twig` naming convention (`S/docs/reference/action_list.rst:169`), `'actions' => ['approve_ean' => []]` in admins (`AddedEanAdmin.php:127`, `RvmTaskAdmin.php:111`, `SupportAdmin.php:77`, `PlaylistAdmin.php:79`, `AcmContentJobAdmin.php:113`) | KEEPS-WORKING / COMPAT-LAYER | |
| B14 | `CRUD/list__select.html.twig` — adds `{% if not app.request.isXmlHttpRequest %}`, `btn btn-primary`, `fas fa-check` | `:1-10` | upstream `:12-19` | KEEPS-WORKING / COMPAT-LAYER (`.btn-primary`) | only used by `ModelListType` modals, which the app does not use. |
| B15 | `CRUD/list_enum.html.twig` — `value.description()` | `:12-20` | upstream `CRUD/list_enum.html.twig` exists (listing) | KEEPS-WORKING | |
| B16 | `CRUD/Association/list_many_to_one.html.twig` — adds "Usunięto [#id]" / "Brak danych" | `:12-38` | upstream `:14-32`; `field_description.option('route')`, `hasAssociationAdmin`, `associationadmin.*`, `render_relation_element` | KEEPS-WORKING | |
| B17 | `CRUD/Association/base_list_inner_row.html.twig` | `:1-26` | **no such upstream file** (`CRUD/Association/` has only `edit_*`, `list_*`, `show_*`); also uses undefined `rendered` (`:20`) | dead (never resolved) | MUST-PORT: delete. Audit rule A-01. |

### 1.4 Custom field / cell / form-theme templates (`templates/field/*` 52 files, `templates/component/*`, `templates/crud/list__action_*`)

| # | Pattern (files) | Evidence | Upstream artefact | Class | Note |
|---|---|---|---|---|---|
| F1 | `{% extends '@SonataAdmin/CRUD/base_list_field.html.twig' %}{% block field %}` — 27 files (`archived`, `auditOperation`, `clientMarketing`, `clientUserState`, `descriptiveName`, `deviceLevel`, `enum`, `fill`, `hw`, `imagePreviewPromoPromoted`, `location`, `messageReadRatio`, `note`, `notes`, `operatorLogo`, `partnerPromoGeo`, `partnerPromoPromoted`, `playlist`, `playlistUploadState`, `rvmTaskState`, `rvmTaskType`, `shortUidWithBranding`, `smartLoginNotes`, `supportListClient`, `transactionAwaitsRecording`, `transactionListClient`, `uploadState`, `usage`, `ver`, `virtualStatus`, `eanNote`) + `component/adminUser.html.twig:1` | e.g. `templates/field/archived.html.twig:1-7` | `<td class="sonata-ba-list-field sonata-ba-list-field-{{type}}" objectId>` envelope + block `field` (`S/…/CRUD/base_list_field.html.twig:12,22`) — frozen by C16 | KEEPS-WORKING | |
| F2 | Raw `<td class="sonata-ba-list-field sonata-ba-list-field-{{ field_description.type }}" objectId="{{ admin.id(object) }}" style="background-color: …">` — 13 files (`cautionBagStatus:29`, `deviceContactState:26`, `deviceFaultState:22`, `deviceLevelWithTime:30`, `deviceSmartLogin:33`, `deviceSystem:19`, `eanReportStatus:29`, `enabledIndicator:14`, `exactMaterial:54`, `playlistState:24`, `recomatMap:13`, `depositInZloty:3`, `weightInGrams:3`) | as listed | row template includes the cell template (`base_list_inner_row.html.twig`) | KEEPS-WORKING (light) | Hard-coded pastel `bg_color`/`icon_color` pairs → in `.dark` the cell stays pastel while adminata's row text turns light: still readable for icon cells (they set their own colour) but visually loud. UPGRADE §U10. |
| F3 | `{% extends get_admin_template('base_list_field', admin.code) %}` (`local_datetime.html.twig:1`) + `data-controller="local-datetime"` (`:12-16`) + `class="label label-danger|label-default"` (`:11`) | | `get_admin_template`, app Stimulus, `label label-*` tokens kept by adminata's boolean cells (C22) | KEEPS-WORKING | |
| F4 | `{% extends '@SonataAdmin/CRUD/show_html.html.twig' %}{% block field %}` (`supportShowClient.html.twig:1-7`) | | `show_html` → `base_show_field` block `field` | KEEPS-WORKING | |
| F5 | Form-theme blocks `json_editor_widget` (`json_editor.html.twig:1-10`), `location_map_widget` (`location_map.html.twig:1-29`, `form-control`, `btn btn-sm btn-default mt-2`, `fas fa-map-marker-alt`), `chunked_file_asset_widget` (`chunked_file_asset.html.twig:12-69`, `btn btn-success fileinput-button dz-clickable`, `progress progress-striped active`, `progress-bar progress-bar-success`, `text-danger`) registered via `setFormTheme` (`drs.yaml:44,53`, `devices.yaml:21`, `media.yaml:12`) | | Symfony form-theme mechanics, `AddTemplatesCompilerPass` merge (`R/php-compat.md` §2.9) | KEEPS-WORKING (mechanics) / COMPAT-LAYER (classes) | `mt-2` starts meaning something (Tailwind 0.5 rem) — harmless. |
| F6 | `label label-success|danger|warning|default` badges — 9 files (`archived:5`, `clientMarketing:5,7`, `clientUserState:5,7`, `deviceLevel:5-11`, `fill:6-25`, `partnerPromoGeo:5-7`, `partnerPromoPromoted:5-7`, `transactionAwaitsRecording:9,16`, `local_datetime:11`) | | adminata styles `.label.label-*` for its own boolean cells (C22, `R/show-misc.md` Q1) | KEEPS-WORKING | |
| F7 | `btn btn-sm btn-default edit_link` links (`eanNote:5-8`, `transactionAwaitsRecording:12`, `transactionListClient:19`), `btn` bare (`notes:10-13`, `smartLoginNotes:10-13`, `note:8`) | | compat `.btn`, `.btn-sm`, `.btn-default` | COMPAT-LAYER | |
| F8 | `a.trigger-modal[data-um-title][data-um-body]` (`eanNote:7`) and `a.contents-modal[data-title][data-rel]` (`note:8`, `notes:10`, `smartLoginNotes:10`) → `UniversalModal.js` → `$('#universal-modal').modal()` | | Bootstrap 3 `$.fn.modal` (not shipped) | **BREAKS** | MUST-PORT with L8 (§2a). |
| F9 | `text-success/text-warning/text-danger/text-muted/text-info` on `<i>` (`uploadState:6-12`, `playlistUploadState:6-12`, `rvmTaskState:6-14`, `rvmTaskType:6-8`, `recomat/edit:42`) | | Bootstrap text colours; TailAdmin's theme defines `--color-success-500` etc. (`text-success-500`), not `text-success` | COMPAT-LAYER | compat must map `.text-success→success-500`, `.text-warning→warning-500`, `.text-danger→error-500`, `.text-muted→gray-500`, `.text-info→blue-light-500`. |
| F10 | `image-preview-container-list > a.admin-preview-link` + `.image-modal-container > span.modal-close-button + img.modal-content` (`imagePreviewPromoPromoted.html.twig:7-14`) | | app CSS `image-preview.css:12-53` + vanilla `image-preview.js:1-21` | KEEPS-WORKING | `img.modal-content` picks up compat `.modal-content` (background/border/radius) exactly as it picks up Bootstrap's today. Rename to `admin-preview-image` to be safe (A-16). |
| F11 | `td.sonata-ba-list-field-VIRTUAL-STATUS .virtual-status-*` (`virtualStatus.html.twig:4-5`; CSS `sonata-overrides.scss:41-62`) | | `sonata-ba-list-field-{type}` hook | KEEPS-WORKING | |
| F12 | App classes `nonbreak`, `note-view`, `note-mgmt`, `transaction-client(-branding)`, `operator-logo(-missing)` | `ver:5`, `note:4`, `transactionListClient:4`, `operatorLogo:8,11`; CSS `transaction-list.scss`, `sonata-overrides.scss:284-293` | app-owned | KEEPS-WORKING | |
| F13 | `crud/list__action_dropdown.html.twig` — `a.btn.btn-action-icon.btn-action-expand.btn-toggle-items[data-transaction-id][data-list-url][data-empty-message]` + `path(actions.child_route ~ '_list', {'filter[…][value]': object.id})` (`:1-23`); `crud/list__action_{map,transakcje,showTransaction(s),showPackageReturnItems,showGarbageBagTransactions,assignSmartLogin}.html.twig` | | route naming + `filter[x][value]` query contract (`R/php-compat.md` §2.8) | KEEPS-WORKING (contract) / COMPAT-LAYER (`.btn`) | J3 covers the XHR. `actions.child_route` etc. are the app's own `actions` option payload (`DrsTransactionAdmin.php:64-66`, `DrsBagAdmin.php:94-96`). |
| F14 | `templates/crud/list_with_summaries.html.twig` | see §2c | | | |

### 1.5 Controller-rendered page templates

| # | Template | Rendered by | Depends on | Class | Note |
|---|---|---|---|---|---|
| P1 | `promo_code/create.html.twig` | `PromoCodeController.php:47` (`CreatePromoCodesFormType` with ux-autocomplete `OnlinePromoAutocompleteField`, `CreatePromoCodesFormType.php:25`) | `@SonataAdmin/standard_layout.html.twig` (`:1`), block `sonata_admin_content`, hard-coded `notice` include (`:3-5`), `.box`/`.form-group`/`.has-error`/`.help-block`/`.sonata-ba-field-standard-natural`, `btn btn-success` + `fa fa-save` (`:35`) | KEEPS-WORKING (blocks) / COMPAT-LAYER (classes) | `notice` → `{{ parent() }}` (L16). ux-autocomplete field on a Sonata-layout page: R5 applies. |
| P2 | `recomapp/legacyEans/create.html.twig`, `reject.html.twig` | `LegacyEansController.php:134,192` | same skeleton (`create:1-52`, `reject:1-53`) | same | |
| P3 | `transaction/create_manual.html.twig` | `TransactionController.php:68` (`CreateManualTransactionType`: ux-autocomplete `client` `:57`, `DateTimePickerType created` `:79-81` via the **global** datepicker theme C20) | extends **`layout/standard_layout_override.html.twig`** (`:1`) | same + C20 | acceptance scenario F3. |
| P4 | `form/smart-login-assign.html.twig` | `RecomatManagerController.php:68` (`SmartLoginAssignForm`, submit `attr.class = 'btn btn-success'` `:37-41`) | `@SonataAdmin/standard_layout.html.twig` (`:1`), `{{ form(form) }}` (`:22`) | same | |
| P5 | `recomat/edit.html.twig` — `{% extends '@SonataAdmin/CRUD/edit.html.twig' %}{% block form %}{{ parent() }}` + activation `<div class="box box-default">` with two inline POST forms (`csrf_token('recomat-activate-' ~ id)`) and `alert alert-info` | `devices.yaml:20` (`setTemplate edit`), `RecomatController.php:57` (`isCsrfTokenValid`) | `CRUD/edit.html.twig` → `base_edit.html.twig` block `form` (`R/forms-edit.md` §10.1) | KEEPS-WORKING (block) / COMPAT-LAYER (`.box*`, `.btn-success/danger`, `.alert-info`, `.text-muted`) | token ids are session-based (not stateless) — fine. |
| P6 | `recomat/list.html.twig` — `{% extends '@SonataAdmin/CRUD/list.html.twig' %}{% block actions %}{{ parent() }}` | `devices.yaml:19,28,35,43` (`setTemplate list` on 4 admins) | block `actions` | KEEPS-WORKING | no-op override; delete. |
| P7 | `admin/dashboard_stats.html.twig` — extends standard layout, empties `sonata_breadcrumb`, AdminLTE `box box-success/warning/danger`, `box-header with-border`, `box-tools pull-right`, `btn btn-box-tool`, `col-md-4/6/12`, `data-controller="chart"` (app Stimulus, Chart.js) | `DashboardStatsController.php:25-36` | blocks `title`, `sonata_breadcrumb`, `sonata_admin_content`; compat `.box*`, grid, `pull-right` | KEEPS-WORKING / COMPAT-LAYER | Port to TailAdmin metric cards (`tailadmin-html/src/partials/metric-group`) is optional. Home page of the app (`routes.yaml:9`, `security.yaml:172`) → acceptance D2. |
| P8 | `security/password_change.html.twig` | `AdminSecurityController.php:74` | L16/L17 | | |
| P9 | Dead: `generic_create.html.twig` (nothing extends it), `message/{edit,create,confirm_sending}.html.twig` (no `MessageAdmin`/controller; `admin_app_message_list` route does not exist), `recomat/confirm_archive.html.twig`, `promo_code/upload.html.twig` (`BulkRvmCodesType` unused outside `src/Form`) | grep over `APP/src`, `APP/config` | — | dead | Delete (A-15). `promo_code/upload.html.twig:68-77` is the only inline `<script>` using `$` in the app. |

### 1.6 PHP option values

| # | Item | File:line | Rendered by | Class | Note |
|---|---|---|---|---|---|
| O1 | `->with('…', ['class' => 'col-md-6'])` ×7, `col-md-12` ×5, `col-md-9` ×3, `col-md-3` ×2, `col-md-4`, `col-md-8` | `CollectionPointAdmin.php:43,57`; `ReverseVendingOperatorAdmin.php:47,56,85`; `RecomatAdmin.php:250,279,289,313,343,352,361,382,391,429,437`; `PartnerPromoAdmin.php:290,300`; `AuditLogAdmin.php:57,73,80` (show) | `base_edit_form_macro.html.twig:7` `<div class="{{ form_group.class|default('col-md-12') }}">`; `base_show.html.twig:97` | COMPAT-LAYER | C7: defaults stay; adminata's group container is `grid grid-cols-12`, compat maps `.col-md-N → col-span-12 md:col-span-N`. |
| O2 | `'class' => 'section-geolocation col-md-12 hidden'` / `'section-fiat col-md-12 hidden'` (toggled by `SectionSlider.js`) | `PartnerPromoAdmin.php:290,300` | same container | COMPAT-LAYER — **requires `.hidden{display:none!important}`** in compat (Tailwind's `.hidden` has no `!important` and compat `.box{display:block}` could out-cascade it) | §5 item 3. |
| O3 | `box_class` never set → default `box box-primary` | `BaseGroupedMapper` defaults (`R/php-compat.md` §2.9) | `base_edit_form_macro.html.twig:8` | COMPAT-LAYER / handled by adminata's own translation | nothing to do. |
| O4 | `'header_class' => 'content-width'` | `DrsBagAdmin.php:55`, `DrsProductAdmin.php:44` | `base_list.html.twig:81` (`<th class="… {{ header_class }}">`) | KEEPS-WORKING | app CSS `th.content-width` (`sonata-overrides.scss:295-298`). |
| O5 | `'template' => '/field/….html.twig'` ×36 (leading slash) and `'template' => 'field/….html.twig'` ×7; `'template' => '/crud/list__action_*.html.twig'` inside `actions` | e.g. `RecomatAdmin.php:503-571`, `RecomatManagerAdmin.php:186-236`, `DrsBagAdmin.php:62,96` | `FieldDescription::setTemplate()` / `RenderElementRuntime` (`R/php-compat.md` §2.4) | KEEPS-WORKING | |
| O6 | `'label_attr' => ['class' => 'pt-overrides datagrid-filter']` | `RecomatAdmin.php:205-207`, `RecomatManagerAdmin.php:116-118`, `TransactionAdmin.php:108-110` | no CSS defines these (grep) | KEEPS-WORKING (no-op) | |
| O7 | `'attr' => ['class' => 'section-master', 'data-section', 'data-trigger']` on `EnumType`/`BooleanType` selects | `PartnerPromoAdmin.php:273-283` | native `<select>` (`use_select2: false`) + `SectionSlider.js:2` | KEEPS-WORKING | if `use_select2` is ever enabled, Tom Select still fires native `change` on the original `<select>`. |
| O8 | `'attr' => ['data-controller' => 'operators-id-toggle', 'data-operators-id-toggle-source-name-value' => 'collectionPoint']` | `RecomatAdmin.php:443-446` | app Stimulus (`operators_id_toggle_controller.js`) — reads `[name$="[collectionPoint]"]` `<select>` options | KEEPS-WORKING | R1 (two applications). |
| O9 | `'attr' => ['class' => 'form-control']` (`NoteForm.php:22-23`, `PasswordChangeForm.php:27-29`), `'password-field form-control'` (`:47-56`), submit `'btn btn-success'` (`:62-63`, `SmartLoginAssignForm.php:40-41`), `row_attr.class = 'text-right mt-10'` (`PasswordChangeForm.php:65-66`) | | non-admin forms rendered with `form_div_layout` | COMPAT-LAYER; **`mt-10` collides** with Tailwind (`sonata-overrides.scss:13-15` = 10 px vs Tailwind 2.5 rem) | MUST-PORT: rename the app utility to `app-mt-10` (A-16). |
| O10 | `DateTimePickerType` with `datepicker_options.display.components = {calendar:false, clock:true, seconds:false}` + `'format' => 'H:mm'` (time-only picker) | `RecomatAdmin.php:314-341`; `RecomatDateHoursForm.php:25-52`, `RecomatWeekdayHoursForm.php:25-50` (collection entries) | form-extensions `datepicker.html.twig` + Tempus Dominus options | KEEPS-WORKING **only if** G5's flatpickr mapping handles `display.components.calendar=false → noCalendar:true, enableTime:true, enableSeconds:false` and ICU `H:mm → H:i` | verify in acceptance F4; risk R-3. |
| O11 | `DatePickerType` `'format' => 'dd-MM-yyyy HH:mm'` (`PartnerPromoAdmin.php:146-170`), `'yyyy-MM-dd'` (`RecomatDateHoursForm.php:22`), `DateTimePickerType 'dd-MM-yyyy HH:mm'` (`CreateManualTransactionType.php:81`) | | ICU → picker token conversion (G5) | same | |
| O12 | Filters: `DateRangePickerType` (`TransactionAdmin.php:137-145` `'dd.MM.yyyy'`, `PartnerPromoAdmin.php:80,89`, `PromoCodeAdmin.php:41`), `DateTimeRangePickerType` (`RvmStateAdmin.php:60-64` `'dd.MM.yyyy H:i:s'`, `RvmFaultStateAdmin.php:63-67` `'dd.MM.yyyy H:m:s'`) | | form-extensions range types (G5) | same | |
| O13 | `ModelAutocompleteType` as filter `field_type` (`'property' => 'name'`) | `RecomatAdmin.php:213-219`, `RecomatManagerAdmin.php:135-141`; ORM `ModelAutocompleteFilter` (`PackageReturnItemsAdmin.php:67`) | `Form/Type/sonata_type_model_autocomplete.html.twig` (select2 inline script, `data-sonata-select2="false"` `:11`, `:64-164`) → adminata `sonata-autocomplete` (Tom Select), server route `sonata_admin_retrieve_autocomplete_items` unchanged | KEEPS-WORKING | acceptance L3. |
| O14 | ux-autocomplete field types as filter `field_type` (`RecomatGroupAutocompleteField`, `PartnerPromoAutocompleteField`, `RecomatAutocompleteField`) | `RecomatAdmin.php:203`, `RecomatManagerAdmin.php:114`, `TransactionAdmin.php:106`, `SmartLoginAdmin.php:69` | global `@Autocomplete/autocomplete_form_theme.html.twig` (`VENDOR/symfony/ux-autocomplete/src/DependencyInjection/AutocompleteExtension.php:49`) + `data-controller="symfony--ux-autocomplete--autocomplete"` (`…/Form/AutocompleteChoiceTypeExtension.php:57-58`) inside Sonata's filter panel (`sonata-filter` controller) | KEEPS-WORKING given R5 | acceptance L4. |
| O15 | ux-autocomplete field **inside an admin form**: `RecomatAutocompleteField` (`multiple`, `by_reference: false`) | `RecomatGroupAdmin.php:57-64` | admin form theme (`form_admin_fields`) wraps the widget; the `ux_entity_autocomplete_widget` block of the global theme renders the inner `<select>` | KEEPS-WORKING given R5 | acceptance F2. |
| O16 | Sonata `CollectionType` (`sonata_type_native_collection`, `allow_add/allow_delete`, `by_reference: false`) ×7 | `RecomatAdmin.php:344-357,383-394`, `ClientProviderConfigAdmin.php:283-290`, `AcmContentJobAdmin.php:43-63`, `StaticContentsAdmin.php:35-40`, `AdminUserAdmin.php:82-89` | `sonata_type_native_collection_widget` + `sonata-collection` Stimulus controller (kept, `R/js-assets.md` §1.2) — no XHR | KEEPS-WORKING | Symfony-core `CollectionType` (`PlaylistAdmin.php:47`, `PartnerPromoAdmin.php:220,240,251`, `PartnerAdmin.php:71`, `RecomatGroupAdmin.php`) renders plain rows without buttons today and tomorrow. |
| O17 | `BooleanType` (form-extensions) ×12, `CheckboxType` ×2 | e.g. `ClientProviderConfigAdmin.php:127-246`, `ClientSystemConnectorAdmin.php:33` | native select / checkbox styled by adminata's theme; iCheck gone (row 2 of `R/gap-js-architecture.md` §6) | KEEPS-WORKING | |
| O18 | `'help_html' => true` ×6 with `<img>`/`<code>` snippets | `ClientProviderConfigAdmin.php:150,185,199,212`, `PartnerPromoAdmin.php:218`, lib `ChunkedFileAssetType.php:166` | `form_help` block keeps `|raw` when `help_html` | KEEPS-WORKING | |
| O19 | `setListActions: [[…]]` / `setRemovedRoutes` / `setTemplate` / `setFormTheme` service `calls` | `devices.yaml:19-21,28,35,43`, `drs.yaml:11,18,25,32,44-46,53-54`, `media.yaml:12,17`, `clients.yaml:10,17`, `promos.yaml:22,29,36` | `AbstractAdmin::setTemplate()` (per-admin `MutableTemplateRegistry`), `setFormTheme` merged by `AddTemplatesCompilerPass` | KEEPS-WORKING | |
| O20 | `sonata.admin` tag attributes `model_class`, `controller`, `manager_type: orm|doctrine_mongodb`, `group`, `label`, `default: true` | all `config/services/admin/*.yaml` | tag contract (`R/php-compat.md` §2.1) | KEEPS-WORKING | |
| O21 | `BaseAdmin::preRemove()` → `sonata_flash_success` + `RedirectException('/admin')`; `failUpdate()` → `sonata_flash_error` | `BaseAdmin.php:216-226,360-364` | twig-extensions flash types map `sonata_flash_success→success`, `sonata_flash_error→danger`, plus plain `success`/`error` (`VENDOR/sonata-project/twig-extensions/src/Bridge/Symfony/DependencyInjection/SonataTwigExtension.php:48-63`); adminata's flash partial iterates `sonata_flashmessages_types()` (`R/layout-nav.md` §6) | KEEPS-WORKING | Controllers also use `addFlash('error'|'success')` (`PlaylistController.php:31`, `SupportThreadController.php:42`) — both in the type map. |
| O22 | `BaseAdmin::getSummary()/hasSummary()` used by the list template | `BaseAdmin.php:119-214`; `list_with_summaries.html.twig:4-5` | app | KEEPS-WORKING | §2c. |
| O23 | `SidebarMenuSubscriber` injects KnpMenu items with `'class' => 'sidebar-section-header'` | `src/Listener/Menu/SidebarMenuSubscriber.php:113` | `ConfigureMenuEvent::SIDEBAR` + `Menu/sonata_menu.html.twig` `item` block passes `attributes.class` through | KEEPS-WORKING (class emitted) / MUST-PORT (its styling lives in `_sidebar.scss:192-219`, which should be deleted) | replacement: `menu-group-title` classes from TailAdmin (`tailadmin-html/src/partials/sidebar.html`). |

### 1.7 CSS selectors (`APP/assets/styles/`)

`sonata-overrides.scss` (302 lines), rule by rule:

| Lines | Selector | Targets | Class | Disposition |
|---|---|---|---|---|
| 1-7 | `div.login-logo`, `div.login-logo span` | app class (login page) | KEEPS-WORKING | keep until login port |
| 9-11 | `body .main-header .logo img {max-width:50px}` | `main-header` marker (kept, `R/layout-nav.md` §1.3) + `a.logo` from the app's own `logo` override (L4) | COMPAT-LAYER | delete with L4 |
| 13-15 | `.mt-10 {margin-top:10px}` | app utility; **collides** with Tailwind `mt-10` (2.5 rem) — app rule loads later and wins, shrinking every TailAdmin `mt-10` to 10 px | MUST-PORT | rename `app-mt-10` (used by `PasswordChangeForm.php:66`) |
| 17-24 | `table th.sonata-ba-list-field-header-batch, …-integer {width:1%; white-space:nowrap}` | hooks kept (`R/list-datagrid.md` §8.3) | KEEPS-WORKING | keep |
| 25-40 | `table tbody tr td {vertical-align:middle!important}`, `td.sonata-ba-list-field.content-width`, `td.sonata-ba-list-field-batch/-integer/-actions` | global `td` rule + hooks | KEEPS-WORKING | keep (scope the `td` rule to `.sonata-ba-list` to avoid hitting TailAdmin tables) |
| 41-62 | `td.sonata-ba-list-field-VIRTUAL-STATUS .virtual-status(-safe/-expired/-unsafe)` | hook + app | KEEPS-WORKING | keep |
| 68-104 | `body .ts-wrapper.single/.multi(.has-items) .ts-control {display:flex; padding:0; border:0; background:none…}`, `body .ts-wrapper.form-control {padding:5px 12px; height:unset}`, `body .ts-dropdown {…border-color:rgb(210,214,222)}` | Tom Select skin (ux-autocomplete `tom-select.default.css`, `controllers.json:8`); relies on Bootstrap `.form-control` giving the wrapper its border | COMPAT-LAYER (R7: adminata's Tom Select theme uses the same selectors at ≤(0,2,0); the app's (0,3,0) wins) | **Delete** — adminata's `.ts-*` theme already renders TailAdmin inputs and dark mode; the app rule hard-codes a light border colour. |
| 107-110 | `.content-header .navbar.navbar-default {background:#f7f9ff; border-color}` | `content-header` marker kept; `navbar navbar-default` only if adminata keeps it as a marker on the toolbar (§5 item 2) | COMPAT-LAYER | delete (TailAdmin toolbar is already a card) |
| 113-119 | `.sonata-ba-list tbody tr:nth-child(even|odd) {background:#f7f9ff|#fcfcff}` | hook kept | KEEPS-WORKING in light; **breaks dark mode** | wrap in `html:not(.dark) &` or delete (TailAdmin has hover rows) |
| 121-215 | `.btn.btn-action-icon` + 12 `.btn-action-*:hover` variants (self-contained: size, radius, colours) | `.btn` base from compat | COMPAT-LAYER (light) | keep; add `dark:` counterparts or accept light chips |
| 218-242 | `.nav.navbar-nav > li > a.sonata-action-element.sonata-action-btn {…!important}` | requires the `nav navbar-nav` wrapper of `sonata_admin_content_actions_wrappers` (`standard_layout:267`) — adminata's proposed wrapper is `<ul class="flex items-center gap-2 sonata-actions">` (`R/layout-nav.md` §2 row 29) | COMPAT-LAYER **only with** §5 item 2 (keep `nav navbar-nav` markers); otherwise silently no-op | MUST-PORT selector to `.sonata-actions > li > a.sonata-action-btn`, or delete and let adminata style `a.sonata-action-element` |
| 244-282 | `.navbar-nav > .dropdown.sonata-actions > .dropdown-toggle.sonata-ba-action {font-size:0; …}` + `> i.fas.fa-filter`, `> .badge`, `> .caret` | filter button markup of `base_list.html.twig:260-267` — adminata redesigns it as a TailAdmin dropdown (`R/list-datagrid.md` §2.2); `sonata-ba-action`, `data-filter`, `active` kept; `dropdown-toggle`/`caret`/`badge` not guaranteed | MUST-PORT (delete) | the rule exists only to hide Bootstrap's text; adminata's button is already icon+badge |
| 284-293 | `.operator-logo`, `.operator-logo-missing` | app | KEEPS-WORKING | keep |
| 295-298 | `th.content-width` | `header_class` value (O4) | KEEPS-WORKING | keep |
| 300-302 | `td.sonata-ba-list-field.sonata-ba-list-field-boolean i {margin-right:0}` | adminata boolean cell keeps `label label-*` + `<i>` (C22) | KEEPS-WORKING | keep or delete |

`admin-theme/_sidebar.scss` (243 lines) and `admin-theme/_header.scss` (118 lines): every rule is scoped `body[class*="skin-"]` (the skin class **stays** on `<body>`: `admin_lte_skin_class` block, `R/layout-nav.md` §7) and targets `.left-side`, `.main-sidebar`, `.wrapper`, `.sidebar`, `.sidebar-menu > li(.header|.active|.menu-open|.sidebar-section-header) > a`, `.treeview-menu > li > a`, `.pull-right-container`, `.sidebar p.text-center.small`, `.main-header .logo`, `.main-header .navbar(.sidebar-toggle|.nav > li > a|.navbar-custom-menu .navbar-nav > li > a|.navbar-right > li > a|.dropdown-menu)`, `.breadcrumb > li + li::before`. Adminata keeps `main-header`, `main-sidebar`, `sidebar`, `sidebar-menu`, `treeview`, `treeview-menu`, `active`, `sidebar-toggle`, `breadcrumb` as markers (`R/layout-nav.md` §1.3, §3.4, row 12) — so **most of these `!important` rules will still match and repaint TailAdmin's sidebar/header** with the app's slate/blue palette (which is, by design, a TailAdmin look-alike). Classification: COMPAT-LAYER (works via markers) → **MUST-PORT = delete both partials and `admin-theme.scss`**; keep only a 10-line replacement for `li.sidebar-section-header` (O23) and, if wanted, the logo `filter` (`_header.scss:38`) as `.dark & img{filter:…}`.

Other files: `app.scss:1-3` `body{background:lightgray}` → delete (dark mode; adminata paints body). `transaction-items-accordion.scss:1-78` → app-owned + `table.sonata-ba-list` hook → KEEPS-WORKING; light greys at `:8,20,26` → dark-mode port. `transaction-list.scss`, `partner-promo.scss`, `recomat-preview.css` → KEEPS-WORKING. `image-preview.css:26-31` `.modal-content` → see F10. `enums/deprecationLevel.scss` is empty. `jquery-ui-bundle/jquery-ui.css` import (`app.js:17`) → only `.ui-*` selectors → harmless, but unused (J2).

### 1.8 JS calls

| # | Call | File:line | Depends on | Class | Replacement |
|---|---|---|---|---|---|
| J1 | `import $ from 'jquery'` with `.addExternals({ jquery: 'jQuery' })` → resolves to `window.jQuery` at runtime; `build/app.js` is loaded after Sonata's list (C16) | `app.js:3`; `webpack.config.js:53` | `window.jQuery` from `bundles/sonataadmin/app.js` today (`S/assets/js/app.js:51-52`); from `bundles/sonataadmin/vendor/jquery.js` under adminata (C2; deprecated, removed in 2.0) | KEEPS-WORKING (1.x) | MUST-PORT before 2.0: `npm i jquery`, remove `addExternals`, so the app owns its jQuery; then `remove_javascripts: [bundles/sonataadmin/vendor/jquery.js]`. |
| J2 | `import 'jquery-ui-bundle'` + its CSS | `app.js:16-17`; `package.json:34-35` | `window.jQuery`; nothing in `APP/assets` calls `.sortable(`/`.draggable(` (grep) | KEEPS-WORKING (dead) | delete both imports and the two npm deps. |
| J3 | `TransactionItemsAccordion.js`: `$(document).on('click', '.btn-toggle-items')` (`:2`), `$.ajax({url, headers:{'X-Requested-With':'XMLHttpRequest'}})` (`:32-34`), parses `table.sonata-ba-list` from the response (`:38`), injects `<tr class="transaction-items-accordion-row"><td colspan>` (`:19-27`), spinner `fas fa-spinner fa-spin` (`:23`) | | jQuery global; Sonata renders the child list through `ajax_layout.html.twig` when `isXmlHttpRequest` (`CRUDController.php:962,1016-1018`), whose table keeps `sonata-ba-list` (`R/list-datagrid.md` §8.3) | KEEPS-WORKING given §5 item 6 | acceptance L6. |
| J4 | `SectionSlider.js`: `$('select.section-master').on('change')`, `$("div.section-" + …).removeClass/addClass('hidden')` | `:2-13` | native selects (O7), `.hidden` (O2) | KEEPS-WORKING given `.hidden!important` | optional Stimulus rewrite. |
| J5 | `UniversalModal.js`: `$('a.trigger-modal'|'a.contents-modal').on('click')` → `$("#universal-modal").modal()` | `:2-19` (`.modal()` at `:8,18`) | Bootstrap 3 `$.fn.modal` — **not shipped** by adminata (row 3 of `R/gap-js-architecture.md` §6) | **BREAKS** | MUST-PORT: §2a snippet. |
| J6 | `promo_code/upload.html.twig` inline script `Dropzone.options.jobsuploader`, `$("#bulk_rvm_codes_csv").val()`, `.slideUp()/.slideDown()` | `:68-77` | jQuery core + `window.Dropzone` | KEEPS-WORKING (but template is dead, P9) | delete |
| J7 | `csrf_protection_controller.js`: capture-phase `document.addEventListener('submit', …, true)` mints token + cookie; `turbo:*` hooks; `/* stimulusFetch: 'lazy' */` | `:5-7,23-43,78` | loaded lazily when an element with `data-controller="csrf-protection"` exists — Symfony adds that attribute to every form `_token` field (`VENDOR/symfony/framework-bundle/DependencyInjection/Configuration.php:272`, `form.csrf_protection.field_attr` default) | KEEPS-WORKING | contract for adminata fetch flows: §2f. |
| J8 | Own Stimulus controllers `chart`, `chunked-file-asset`, `chunked-upload`, `json-editor`, `local-datetime`, `location-map`, `operators-id-toggle` in a **second** `Application` (`startStimulusApp`) | `bootstrap.js:4-8`; `controllers/*.js` | R1–R4 (`R/gap-js-architecture.md` §4.1): identifiers are per application; none starts with `sonata-` | KEEPS-WORKING | keep; optionally adopt `startAdminata({application: app})` (single app, §4.2) — not required. |
| J9 | `location_map_controller._buildAddressFromForm()` reads `.ts-control .item[data-value]` (Tom Select DOM of the `city` field) | `:150-154` | Tom Select markup (same for ux-autocomplete and adminata's Tom Select) | KEEPS-WORKING | |
| J10 | `chunked_file_asset_controller._setSubmitDisabled()` disables `form button[type=submit]` during upload | `:207-212` | Sonata's `sonata_form_actions` buttons remain `<button type="submit" name="btn_update_and_edit">` (`R/forms-edit.md` §10.1) | KEEPS-WORKING | |
| J11 | `local_datetime_controller` toggles `label-danger`/`label-default` | `:50-51` | C22 tokens | KEEPS-WORKING | |
| J12 | `image-preview.js` (vanilla, `DOMContentLoaded`, `.image-modal-container`, `.modal-close-button`) | `:1-21` | app | KEEPS-WORKING | |
| J13 | `Dropzone.discover()` + `Dropzone.autoDiscover = false` inside controllers | `app.js:21`; `chunked_*_controller.js:52,62` | app | KEEPS-WORKING | |
| J14 | ux-autocomplete controller (`fetch: eager`, auto-imports `tom-select.default.css`) | `controllers.json:3-13` | R5–R9 | KEEPS-WORKING | adminata must **not** import `tom-select.default.css` a second time with higher-specificity overrides than the app's (R7). |

### 1.9 Icon strings

Complete inventory (grep over `APP/templates`, `APP/src`, `APP/config`, `APP/assets/admin`, `APP/assets/controllers`; counts = occurrences):

| Name | Count | Where | Status in FA 5.15 + v4-shims (Sonata today, `S/assets/scss/app.scss:16`) | Status in FA 6.7 CSS (`all.min.css`, CDN `L2`) | Status in adminata (C20: FA6 Free + v4-shims + v5 font-face) |
|---|---|---|---|---|---|
| `fa fa-save` (11), `fa fa-check-circle` (6), `fa fa-eye` (3), `fa fa-cloud-upload` (3), `fa fa-external-link-square` (3), `fa fa-plus-circle` (2), `fa fa-minus-circle` (2), `fa fa-list` (2), `fa fa-unlink`, `fa fa-times-circle`, `fa fa-spinner`, `fa fa-sign-out`, `fa fa-plus`, `fa fa-plug`, `fa fa-map-marker`, `fa fa-lock`, `fa fa-globe`, `fa fa-edit`, `fa fa-download`, `fa fa-desktop`, `fa fa-cogs`, `fa fa-cog` | 45 | `templates/field/*`, `user_block`, page templates | v4 names, all exist as **aliases** in FA ≥ 5 solid set (verified against `APP/node_modules/@fortawesome/free-solid-svg-icons/index.d.ts` 7.2.0: `faSignOut`, `faExternalLinkSquare`, `faCloudUpload`, `faUnlink`, `faMapMarker`, `faCog`, `faSave`, `faEdit`, `faDesktop` all exported) | OK (aliases) | OK |
| `fa fa-clock-o` | 1 | `templates/field/rvmTaskState.html.twig:6` | **only via v4-shims** (`faClockO` is not exported) | **missing** from the CDN file (already rendered by Sonata's shims today) | OK only because adminata keeps v4-shims (C20) — **regression test A-13** |
| `fas fa-map-marker-alt` (5), `fa-exchange-alt` (4), `fa-trash-alt`, `fa-cloud-upload-alt` (2), `fa-external-link-alt`, `fa-wine-glass-alt`, `fa-shield-alt`, `fa-pencil-alt` (upstream) | 15 | field/crud templates | FA5 names | OK (aliases) | OK |
| `fas fa-plus` (3), `fa-times` (2), `fa-play-circle` (2), `fa-stop-circle`, `fa-pause-circle`, `fa-hourglass-half`, `fa-power-off`, `fa-times-circle`, `fa-question-circle`, `fa-exclamation-circle`, `fa-check-circle`, `fa-check`, `fa-check-double`, `fa-chevron-down`, `fa-ban`, `fa-pen`, `fa-clock`, `fa-eye`, `fa-folder`, `fa-box`, `fa-box-open`, `fa-cube`, `fa-ring`, `fa-ruler`, `fa-recycle`, `fa-layer-group`, `fa-puzzle-piece`, `fa-shopping-bag`, `fa-spray-can`, `fa-star`, `fa-laptop`, `fa-cogs`, `fa-spinner`, `fa-lock`, `fa-barcode`, `fa-bell`, `fa-bug`, `fa-cloud`, `fa-coins`, `fa-credit-card`, `fa-gamepad`, `fa-headset`, `fa-image`, `fa-tag`, `fa-trash`, `fa-user`, `fa-users`, `fa-wine-bottle`, `fa-filter` (CSS) | ~55 | dashboard groups, field/crud templates, `sonata-overrides.scss:263` | OK | OK | OK |
| `fas fa-gauge-high`, `fa-cart-shopping`, `fa-table-list`, `fa-file-lines`, `fa-gear`, `fa-up-right-from-square`, `fa-screwdriver-wrench`, `fa-triangle-exclamation`, `fa-circle-check`, `fa-check-double` | 10 | `sonata_admin.yaml:21,48,85,89,93`; `admin/dashboard_stats.html.twig:15-17,47`; `list__action_close_support_thread.html.twig:9` | **missing** (FA6-only) — rendered today only thanks to the CDN link | OK | OK iff adminata ships FA ≥ 6.0 (C20 says 6.x) |
| `glyphicon glyphicon-user`, `glyphicon-lock` | 2 | `login_form.html.twig:39,44` | never shipped | — | never; delete |

Implications for an FA→SVG mapping (§2i): 19 icons enter as **raw HTML** through `parse_icon`'s `<` pass-through (`IconRuntime.php:22-24`), 2 more as `settings.icon`-style raw strings would; a mapping must therefore operate on the rendered `<i class="fa* fa-…">` element (CSS `mask-image` per class, or a client-side decorator), never on the string form only.

### 1.10 Routes, names, variables the app assumes

| Item | Where | Class |
|---|---|---|
| `path('sonata_admin_dashboard')` | `standard_layout_override:17`, `login_form:57` | KEEPS-WORKING (`sonata_admin.php:43`) |
| `sonata_admin_redirect` | `PlaylistController.php:33,53` | KEEPS-WORKING |
| `admin_app_<admin>_<action>` names (`admin_app_addedean_approve/reject`, `admin_app_rvmtask_cancel`, `admin_app_supportthread_open/close`, `admin_app_recomatplaylist_upload`, `admin_app_acmcontentjob_upload_job`, `admin_app_recomat_list/edit`, `admin_app_transaction_list/edit/send`, `admin_app_packagereturnitem_list`, `admin_app_cautiontransaction_list`, `admin_app_clientuser_list`, `admin_app_recomatnote_list/create`, `admin_app_smartloginnote_list/create`, `admin_app_smartlogin_edit`, `admin_app_reversevendingoperator_edit`, `admin_recomatmanager_smartLogin`) + `filter[x][value]`, `filter[_sort_by]`, `filter[_sort_order]` query keys | bundle overrides, `templates/crud/*`, `templates/field/*`, `admin/dashboard_stats.html.twig:11-13` | KEEPS-WORKING (PHP route generation unchanged) |
| Custom admin routes via `configureRoutes` (`activate`, `deactivate`, `approve`, `reject`, `accepted`, …) | `RecomatAdmin.php:582-584`, `AddedEanAdmin.php:137-141` | KEEPS-WORKING |
| `admin.hasSensitiveDataAccess()`, `admin.canSeeClientUsername()`, `admin.hasSummary()`, `admin.getSummary()`, `admin.deprecationMessage`, `admin.deprecationLevel` — app methods reached through the `admin` Twig global | `transactionListClient.html.twig:6,10`; `list_with_summaries:4-5`; layout `:70-72` | KEEPS-WORKING (`CRUDController::setTwigGlobals`, `R/php-compat.md` §2.7) |
| Twig filters `toAdminUser`, function `file_asset_url()`, `oneup_uploader_endpoint()` | `component/adminUser:5`, `operatorLogo:6`, `chunked_file_asset:18` | app/lib — KEEPS-WORKING |
| `_skin` (parent variable) | L3 | contract addition §5 |
| `sonata_config.logo`, `sonata_config.title`, `sonata_config.getOption('logo_content'|'javascripts'|'use_select2')` | layout override `:18-22,31,44` | KEEPS-WORKING (`R/php-compat.md` §2.7) |
| `csrf_token` variable of `delete.html.twig` / `_sonata_csrf_token` field | `MediaFileController.php:57` (`validateCsrfToken($request, 'sonata.delete')`) | KEEPS-WORKING (`CRUDController.php:1268-1294`; `delete.html.twig:38`) |
| `sonata_flash_success`, `sonata_flash_error`, `success`, `error` flash keys | O21 | KEEPS-WORKING |
| Translation domain `SonataAdminBundle` keys `link_action_create`, `action_edit`, `action_show`, `action_delete`, `link_action_history`, `list_select` | bundle overrides | KEEPS-WORKING (`R/php-compat.md` §2.10) |

### 1.11 Totals

| Class | Rows (approx. artefacts) |
|---|---|
| KEEPS-WORKING | 71 rows (~190 artefacts incl. 45 route names, 39 template options, 19 icons) |
| COMPAT-LAYER | 31 rows (all Bootstrap/AdminLTE class families, Tom Select skin, `nav navbar-nav` selectors) |
| MUST-PORT | 19 rows (modal → dialog ×2 files + 4 field templates, login page, user block, `notice` include ×9, admin-theme deletion, 5 CSS rules, `mt-10`, Flowbite embed, datepicker global theme, dead override/templates, jQuery ownership) |
| BREAKS | 2 (`$.fn.modal`; Flowbite embed — both already have MUST-PORT rows) |

---

## 2. Specific analyses

### 2a. The custom layout (`templates/layout/standard_layout_override.html.twig`)

What each override needs from adminata's layout, block by block:

| Block | Needs from adminata | Verdict |
|---|---|---|
| `stylesheets` (`:4-9`) | block name; `{{ parent() }}` emitting the default + `extra_stylesheets` list in order (`SonataAdminExtension.php:236-285`) | keep; CDN link becomes redundant with FA6 (C20) but harmless. |
| `admin_lte_skin_class` (`:11-13`) | block name **and** the parent-scope variable `_skin` (`standard_layout.html.twig:24`); output must land inside `<body class="…">` | keep. Contract addition: adminata's layout keeps `{% set _skin = sonata_config.getOption('skin') %}` (and the other `_*` sets) above `<html>` (§5). With `strict_variables: true` in the app's test env (`twig.yaml:6-8`) renaming it would fatal the whole acceptance suite. |
| `logo` (`:15-26`) | block name; `sonata_config.logo/title/getOption('logo_content')` | verbatim copy of `S/…/standard_layout.html.twig:114-123`; delete. If kept, compat CSS must style `a.logo > img/span` inside the TailAdmin sidebar header. |
| `javascripts` (`:27-50`) | blocks `javascripts`, `sonata_javascript_config`, `sonata_javascript_pool`; functions `canonicalize_locale_for_moment()` (deprecated no-op, keep), `canonicalize_locale_for_select2()`; `sonata_config.getOption('javascripts')` list with `{path, package_name}` items (`asset(javascript.path, javascript.package_name)`, `:32`) | functionally identical to upstream → delete. Note the app omits the `'sonata_admin'` package name on the select2-locale asset (`:47` vs upstream `:71`) — dead branch anyway. |
| `sonata_wrapper` (`:51-66`) | block name; parent renders sidebar + content; the appended `#universal-modal` must stay hidden (`.modal{display:none}` compat) and be openable | port to `<dialog>`: |

```twig
{# after: templates/layout/standard_layout_override.html.twig #}
{% block sonata_wrapper %}
    {{ parent() }}
    <dialog id="universal-modal" class="modal adm-dialog" {{ stimulus_controller('sonata-modal') }}>
        <div class="modal-content adm-dialog__panel">
            <div class="modal-header adm-dialog__header">
                <h5 id="universal-modal-title" class="modal-title">title</h5>
            </div>
            <div class="modal-body" id="universal-modal-body"></div>
            <div class="modal-footer adm-dialog__footer">
                <button type="button" class="btn btn-default" {{ stimulus_action('sonata-modal', 'close') }}>Zamknij</button>
            </div>
        </div>
    </dialog>
{% endblock %}
```

```js
// after: assets/admin/UniversalModal.js (no jQuery, works with either Stimulus app)
const dialog = () => document.getElementById('universal-modal');
function open(title, bodyHtml) {
    dialog().querySelector('#universal-modal-title').textContent = title;
    dialog().querySelector('#universal-modal-body').innerHTML = bodyHtml;
    dialog().showModal();
}
document.addEventListener('click', (e) => {
    const t = e.target.closest('a.trigger-modal, a.contents-modal');
    if (!t) return;
    e.preventDefault();
    if (t.classList.contains('trigger-modal')) open(t.dataset.umTitle, t.dataset.umBody);
    else open(t.dataset.title, document.getElementById(t.dataset.rel)?.innerHTML ?? '');
});
```

(`sonata-modal` is the controller proposed in `R/gap-js-architecture.md` §1.3; the `modal-content/-title/-body` hooks are kept per C23. `data-um-body` carries HTML from `object.note` (`eanNote.html.twig:7`) — unchanged XSS profile.)

| `sonata_page_content_header` (`:68-80`) | block name; `admin` global; the Flowbite embed target does not exist | replace the embed with `<div class="alert alert-{{ admin.deprecationLevel.value }}"><span class="font-medium">{{ admin.deprecationMessage }}</span></div>` (compat alert) — or adminata's `@SonataAdmin/Core/alert.html.twig` if G2 ships one. `DeprecationLevel` values must map to `success|info|warning|danger` (`APP/src/Entity/Enum/DeprecationLevel.php`, not read here). |

Everything else in the layout (sidebar, breadcrumb, actions bar, user dropdown, flash `notice`) is inherited from adminata unchanged, so the app gets the TailAdmin shell for free; the app's `admin-theme.scss` then fights it (§1.7) and should be removed.

### 2b. Login page and `user_block`

**Login** (`templates/security/login_form.html.twig`). It is the cookbook recipe (`S/docs/cookbook/recipe_sonata_admin_without_user_bundle.rst:332-368`) verbatim plus a logo and a language switcher. Under adminata:

* The four block overrides keep working (`sonata_nav`, `logo`, `sonata_left_side`, `body_attributes`, `sonata_wrapper` — all in the contract, `R/layout-nav.md` §8). `body_attributes` drops `sonata-sticky` (fine) and `_skin`.
* `sonata_header` still renders. Upstream that is `<header class="main-header">` containing `noscript` + the emptied `logo`/`sonata_nav` (`standard_layout:105-184`), invisible under AdminLTE's `.login-page` rules. Under TailAdmin the header is `sticky top-0 z-99999 … border-b` (`R/layout-nav.md` row 145) → an empty bordered bar above the login box. Adminata should either render the header only when `logo`/`sonata_nav` produce output, or ship `@SonataAdmin/empty_layout.html.twig`-based `Security/login.html.twig` (G7). Until then the app overrides `sonata_header` to empty (one more line).
* Compat CSS needed for the AdminLTE login family: `.login-page` (body background), `.login-box` (centered 360 px column), `.login-box-body`, `.login-box-msg`, `.login-logo`, `.form-group.has-feedback`, `.form-control-feedback` (absolute icon slot — with no glyph font it is an empty 34 px box; safe to drop), `.btn-flat`, `.btn-block`, `.col-xs-4`.
* Port (2 h): TailAdmin `signin.html` (`tailadmin-html/src/signin.html`) — `flex min-h-screen items-center justify-center` shell, `w-full max-w-md rounded-2xl border bg-white p-8 dark:…` card, inputs `h-11 w-full rounded-lg border border-gray-300 …`, primary button `w-full rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600`; keep `name="_username"`, `name="_password"`, `name="_csrf_token"` (`csrf_token('authenticate')`), the `error.messageKey|trans(error.messageData, 'security')` line and the flash loop; render language links as TailAdmin text links. Keep `class="sonata-bc login-page"` on `<body>` so any remaining compat rules still scope.

**`user_block`** (`templates/security/user_block.html.twig:8-28`). Adminata renders the include verbatim inside a `<ul class="… dropdown-user">` panel (`R/layout-nav.md` §4.3, R2). The app's `<li class="user-header bg-light-blue">` therefore renders as one list item containing a `<p>`, two `<a class="btn btn-default btn-flat">` and an `<hr>`. With compat `.btn-default`/`.btn-flat` it is usable but ugly (AdminLTE's `.user-header` is a 175 px coloured banner). Port (0.5 h):

```twig
{# after: templates/security/user_block.html.twig #}
{% block user_block %}
    {% if app.user %}
        <li class="user-header adm-dropdown__header">
            <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-400">{{ app.user }}</p>
        </li>
        <li><a class="adm-dropdown__item" href="{{ path('sonata_admin_edit_own_password') }}">
            <i class="fa fa-lock fa-fw" aria-hidden="true"></i> <span>Zmień hasło</span></a></li>
        <li><a class="adm-dropdown__item" href="{{ url('admin_logout') }}">
            <i class="fa fa-sign-out fa-fw" aria-hidden="true"></i> Wyloguj</a></li>
    {% endif %}
{% endblock %}
```

(`adm-dropdown__item` = TailAdmin `flex items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5`, the semantic class proposed for G2. The FA4 names `fa-lock`/`fa-sign-out` resolve as FA6 aliases — §1.9.) The route `sonata_admin_edit_own_password` is the **app's** (`AdminSecurityController.php:46`), which vindicates critique §5 item 2: adminata's default user menu must not hard-code SonataUser route names.

### 2c. `templates.list` override — `crud/list_with_summaries.html.twig`

```twig
{% extends '@!SonataAdmin/CRUD/base_list.html.twig' %}   {# :1 #}
{% block list_footer %}{{ parent() }}                     {# :2-3 #}
{% if admin.hasSummary is defined and admin.hasSummary() %}
  … </div>  {# :6 — closes the parent's .box early #}
  <div class="box box-primary" style="margin-bottom: 100px;"><table class="table table-bordered table-striped table-hover sonata-ba-list"> … {# :8-9 #}
```

Upstream `list_footer` (`S/…/CRUD/base_list.html.twig:144-248`) sits inside `<div class="box box-primary"><div class="box-body …">` opened at `:45-46` and closed at `:249`. The override's stray `</div>` (`:6`) closes the `.box` prematurely, then appends a sibling `.box` with a summary table; the template's own `</div>` then closes an outer wrapper one level too early. Browsers tolerate it, which is why it "works". Under adminata the `.box` becomes a TailAdmin card; the same stray `</div>` closes the card, the summary renders as a sibling card (compat `.box` + `.table*` + the kept `sonata-ba-list` hook), and the mismatched close hits adminata's `<div class="col-xs-12 col-md-12">`-equivalent wrapper → still tolerated, still fragile. Classification: COMPAT-LAYER now, MUST-PORT recommended:

* Adminata adds an **additive** block `list_after_table` (rendered after the card, inside `list_table`, before the batch `</form>`), so the app can write `{% block list_after_table %}{{ parent() }}{% if admin.hasSummary … %}<div class="adm-card">…` without closing tags. Cost for adminata: one empty block; cost for the app: remove `:6`, rename the block.
* Until then the override keeps working through compat.

### 2d. The 17 bundle overrides and the field templates

See §1.3/§1.4. Summary of what adminata must preserve for them: `Button/create_button.html.twig` include path + the `_actions` string heuristics (`standard_layout:266-268`); `get_admin_template('base_list_field', admin.code)` and the `field` block with the `<td … objectId>` envelope; `CRUD/list_enum.html.twig`, `CRUD/Association/list_many_to_one.html.twig`, `CRUD/list__select.html.twig` paths; `actions.link_parameters`, `field_description.option('actions')`, `app.request.isXmlHttpRequest`; `CRUD/show_html.html.twig` extending `base_show_field` with block `field`; the `list__action_[ACTION]` naming convention; the `render_relation_element` filter; the `label label-*` tokens; and the ability of a cell template to emit its own `<td>` (row templates must include, not wrap). All are already in the contract (`R/list-datagrid.md` §8, `R/show-misc.md`); nothing new except the three items in §5.

Effort: zero for the 13 action buttons and 45 cell templates (they are the app's own visual language and keep working); delete `Association/base_list_inner_row.html.twig`; convert the 4 modal-triggering templates with J5.

### 2e. Two Stimulus applications and Tom Select

Facts: `bootstrap.js:4-8` starts a second `Application` via `startStimulusApp(require.context('./controllers'))`; `controllers.json:3-13` registers ux-autocomplete's controller `symfony--ux-autocomplete--autocomplete` eagerly and auto-imports `tom-select.default.css`; `webpack.config.js:52` (and again `:57`) enables the bridge. Sonata's `sonataApplication` (`S/assets/js/stimulus.js`) runs alongside — R1–R4 verified in `R/gap-js-architecture.md` §4.1.

What adminata's pieces must do to coexist with this app:

| Adminata piece | Rule | Exercised by |
|---|---|---|
| `sonata-select` enhancer (only when `use_select2: true`; off here) | skip `[data-sonata-select2="false"]`, `[data-controller*="autocomplete"]`, `el.tomselect`, `select.per-page` (R5) | O14 filters, O15 form field, O13 autocomplete widget's hidden select |
| `sonata-autocomplete` (replacement of `sonata_type_model_autocomplete.html.twig` select2 script) | own Tom Select instance on `#{{ id }}_autocomplete_input`; must not touch ux-autocomplete's `<select>`; keep the `hidden_inputs_wrap` contract and `_context=filter` param | O13 (`RecomatAdmin.php:213`) |
| Tom Select CSS | selectors exactly `.ts-wrapper/.ts-control/.ts-dropdown/…` at ≤ (0,2,0) (R7) so the app's `body .ts-wrapper …` (`sonata-overrides.scss:68-104`) can override — and so the app can simply delete its rules | O14/O15 |
| `sonata-modal` (`<dialog>`) | Tom Select `dropdownParent` default `null` inside dialogs (R8) | not exercised (no modal forms in this app) |
| `sonata-filter` controller | must tolerate Tom Select-wrapped selects in the filter panel: `prepareSubmit` disables empty *inputs* (`filter_controller.js`), Tom Select keeps the original `<select>` | O14 (`RecomatAdmin.php:203`) |
| Bundle JS | never registers into the app's application; identifiers `sonata-*` only (R2) | J8 |
| `startAdminata({application})` ESM path | optional for this app; script order already correct (`build/app.js` is in `extra_javascripts`, after `bundles/sonataadmin/app.js`) | — |

The app's `use_select2: false # DO NOT TURN ON!` comment (`sonata_admin.yaml:113`) documents the failure mode adminata must design against: double enhancement of ux-autocomplete's selects. With R5 in place the flag could be turned on safely; the acceptance suite should include one run with `use_select2: true` (scenario X1) to prove it.

### 2f. Stateless CSRF versus adminata's fetch flows

Configuration (`csrf.yaml:3-11`): form token id `submit` (all Symfony forms, including every Sonata admin form built by `AbstractAdmin::getFormBuilder`), `authenticate`, `logout` are stateless; `check_header` default `false`, cookie name `csrf-token` (`framework-bundle/…/Configuration.php:233-237`).

Client side (`csrf_protection_controller.js`): on `submit` (capture, `:5-7`) it finds `input[data-controller="csrf-protection"], input[name="_csrf_token"]` (`:24`); if the field's value is the 4–22 char cookie name it replaces it with a random 24-char token and writes cookie `csrf-token_<token>=csrf-token; path=/; samesite=strict` (`__Host-` + `secure` on https) (`:33-42`). The module is loaded lazily (`:78`) when a `data-controller="csrf-protection"` element exists — Symfony puts that attribute on every form `_token` (`framework-bundle/…/Configuration.php:272`).

Server side (`SameOriginCsrfTokenManager::isTokenValid`, `VENDOR/symfony/security-csrf/SameOriginCsrfTokenManager.php:116-186`): (1) token ids not in the stateless list fall back to the session manager (`:118-120`) — this is where `sonata.delete`/`sonata.batch` (`CRUDController.php:138,203,263,393`) and the app's `recomat-activate-<id>` (`RecomatController.php:57`) live; (2) origin check via `Sec-Fetch-Site`, else `Origin`/`Referer` prefix (`:220-245`); (3) double-submit check = cookie `csrf-token_<token>` present (`:250-260`); (4) **stickiness**: the strategy that succeeded is persisted in the session (`:170-186`) and a later request that lacks a previously-used strategy is refused (`:151-156`).

Consequences for adminata (all G6 items, now concrete):

1. `sonata-association` modal create/edit (replacing `edit_many_script.html.twig:92,290,316` `ajaxSubmit`), `sonata-collection` inline add (`edit_one_script.html.twig:34`), `sonata-editable` (`SetObjectFieldValueAction`, no token but `X-Requested-With` — `R/critique-round-1.md` V9) and any future `fetch(form.action, {method:'POST', body:new FormData(form)})` must (a) fire a real `submit` event first (`form.requestSubmit()` from a submit button, or `form.dispatchEvent(new SubmitEvent('submit', {cancelable:true}))` then read `FormData`), so the app's capture-phase listener mints the token — otherwise the request passes only while the session has never seen a double-submit, i.e. it works in a fresh session and fails after the user's first normal save; (b) send `credentials: 'same-origin'` (cookie) and let the browser add `Sec-Fetch-Site: same-origin`; (c) never rebuild the body from a serialised snapshot taken before the `submit` event.
2. `AppendFormFieldElementAction`/`RetrieveFormFieldElementAction` call `handleRequest()` but never check validity (`S/src/Admin/AdminHelper.php:124`, `S/src/Action/RetrieveFormFieldElementAction.php:68`) — a wrong token only adds a form error that is not rendered; unaffected.
3. Sonata's session tokens are unaffected, but **`hasPreviousSession()`** is required: acceptance clients must keep cookies between requests (BrowserKit does).
4. The BrowserKit acceptance suite must send a `Referer` (BrowserKit does from history) or an `Origin` header for the login POST (no JS minting there, L13), otherwise `isValidOrigin` returns `null` and the request is refused (`:139-143`).

### 2g. `use_select2: false` and `lock_protection: true`

* `use_select2: false`: body lacks `sonata-select2`, `Admin.setup_select2` is a no-op, all plain `<select>`s are native (`BooleanType`, `EnumType`, `EntityType`, filter operator selects, per-page). Adminata: `sonata-select` not started; native selects styled by the form theme (TailAdmin select recipe). The `ModelAutocompleteType` widget is independent of the flag today (`sonata_type_model_autocomplete.html.twig:64-164` always runs) and stays independent (`sonata-autocomplete`). Nothing to change; the `select2-locale` branch of the layout override is dead (L7).
* `lock_protection: true`: `LockExtension` (PHP) adds the `_lock_version` hidden field and compares on update, raising `flash_lock_error` (`R/php-compat.md` §2.10) rendered through `notice`. Adminata's `base_edit_form` must keep `form_rest(form)` so the hidden field is emitted, and the flash partial must render `sonata_flash_error`. KEEPS-WORKING; acceptance F5 covers it (two clients editing the same Recomat).

### 2h. jQuery, jquery-ui-bundle, Dropzone, `.modal()`

* jQuery: the app does not bundle it (`addExternals`, J1); it relies on Sonata's global. Adminata keeps a global via `bundles/sonataadmin/vendor/jquery.js` in the default `javascripts` list (C2, deprecated). 32 `$(` call sites remain valid jQuery-core calls. The **only** plugin call is `.modal()` (J5) → BREAKS → port (§2a). No `.select2`, `.iCheck`, `.editable`, `.sortable`, `.ajaxSubmit`, `.tab`, `.dropdown` calls exist (grep) — the app is already almost plugin-free.
* `jquery-ui-bundle` (`app.js:16-17`): imported, never used → delete (saves ~250 KB).
* Dropzone 6 (`app.js:12,21`; controllers) → independent of Sonata; unaffected. Its CSS classes (`dz-*`) are its own; the `progress`/`progress-bar` Bootstrap classes in `chunked_file_asset.html.twig:32-35,56-59` need compat.
* `.addExternals({jquery:'jQuery'})` also means the app's build **breaks at runtime** if the global is removed (`remove_javascripts`) — UPGRADE §U8.

### 2i. Raw `<i class="fas fa-…">` icons and FA→SVG mapping

* `parse_icon` returns any string starting with `<` unchanged (`IconRuntime.php:22-24`) and throws for non-FA prefixes (`:27-37`; C8 keeps this). All 19 dashboard group icons and both `list__action_*` templates rely on the pass-through or on hand-written `<i>`; so do 70+ cell templates. An icon strategy that only recognises `fas fa-x` *strings* (e.g. mapping in `parse_icon`) would miss every one of them.
* Therefore: (1) keep the webfont (C20) as the default rendering path — this app needs FA6 names *and* the v4 shim for `fa-clock-o`; (2) any SVG "decorator" must key on the rendered `<i>` classes (`i.fa-gauge-high::before { mask-image:url(…) }` or a client-side swap), and must not touch `<i>` elements inside user templates unless opted in; (3) the CDN `all.min.css` (L2) is not a shim source — v4 names missing from it are rendered today only by Sonata's own `app.css`; when the app removes Sonata's `app.css`-equivalent (it will not; adminata's `app.css` keeps the same path), nothing changes.
* Dark mode: FA glyphs inherit `color`; the app's inline `style="color: #155724"` (F2) keeps them readable.

### 2j. `sonata-overrides.scss` disposition

From §1.7: **unnecessary under adminata** — `:68-104` (Tom Select skin: adminata ships a themed one), `:107-110` (toolbar background), `:113-119` (zebra: TailAdmin has hover rows and it breaks dark mode), `:218-242` and `:244-282` (they existed to hide Bootstrap's navbar text/caret); **target hooks adminata keeps** — `:17-62`, `:295-302` (`sonata-ba-list-field-*`, `header_class` value, boolean cell); **target removed Bootstrap/AdminLTE markup** — `:9-11` (`.main-header .logo`), `:107-110` (`.navbar.navbar-default`), `:218-282` (`.nav.navbar-nav`, `.dropdown-toggle`, `.caret`, `.badge`); **app-owned, keep** — `:1-7`, `:121-215`, `:284-293`; **collision** — `:13-15` (`.mt-10`). Net: 302 → ~120 lines.

`admin-theme.scss` + partials (361 lines): delete entirely (§1.7). Their goal — "modern dashboard look" (`_header.scss:4-5`) — is adminata's default.

---

## 3. Migration procedure, effort, acceptance suite

### 3.1 Step by step

| Step | Command / edit | Why |
|---|---|---|
| 0 | `git status --porcelain \| grep -q . && echo "commit first"` | Flex may touch `config/` (`R/packaging.md` §0.7). |
| 1 | `composer require idct/adminata --no-plugins --no-scripts` then `composer install` (runs Flex normally; adminata's auto-recipe is idempotent) | `--no-plugins` prevents Flex from running `sonata-project/admin-bundle`'s recipe *unconfigure*, which would delete `config/packages/sonata_admin.yaml`, `config/routes/sonata_admin.yaml` and the bundle line (`R/packaging.md` §6). `composer.lock` ends with `idct/adminata` replacing `sonata-project/admin-bundle 4.43.0`; `doctrine-orm-admin-bundle ^4.7` and `idct/sonata-admin-mongodb-bundle ^5` (`^4.39` constraint) stay satisfied. |
| 2 | `bin/console cache:clear && bin/console assets:install public` | `public/bundles/sonataadmin/` now contains adminata's `app.css`, `app.js`, `compat-bootstrap3.css`, `vendor/jquery.js`, fonts; `admin-lte-skins/`, `select2-locale/` disappear. `git diff -- config/` must be empty. |
| 3 | `bin/console adminata:audit-overrides` | produces the checklist of §4.1 for this app (expected hits listed there). |
| 4 | Config diff (`config/packages/sonata_admin.yaml`): nothing mandatory. Optional now: `adminata: {theme: system}` (new root). Later: `assets.remove_javascripts: [bundles/sonataadmin/vendor/jquery.js]` after step 9. | keys preserved (§1.1). |
| 5 | `config/packages/twig.yaml:4`: replace `@SonataForm/Form/datepicker.html.twig` with adminata's standalone theme (name to be fixed by G5, e.g. `@SonataAdmin/Form/datepicker.html.twig`) **or** add `sonata_admin.assets.extra_javascripts: [bundles/sonataform/app.js]` + `extra_stylesheets: [bundles/sonataform/app.css]` after the app's entries | C20 of §1.1. |
| 6 | Templates — delete: `bundles/SonataAdminBundle/CRUD/Association/base_list_inner_row.html.twig`, `generic_create.html.twig`, `message/*`, `recomat/confirm_archive.html.twig`, `promo_code/upload.html.twig`, `recomat/list.html.twig` (+ 4 `setTemplate list` calls in `devices.yaml`), the `logo` and `javascripts` blocks of the layout override. | dead code (§1.2, §1.3, §1.5). |
| 7 | Templates — port: layout `sonata_wrapper` modal + `UniversalModal.js` (§2a); `sonata_page_content_header` alert (L9); `notice` → `{{ parent() }}` in 9 page templates (L16, P1–P5, P8); `user_block` (§2b); login page (§2b); `list_with_summaries` to `list_after_table` once adminata ships it (§2c). | |
| 8 | CSS: delete `admin-theme.scss` + `admin-theme/`, `app.scss` body rule; prune `sonata-overrides.scss` per §2j; rename `.mt-10`; add `html:not(.dark)` guards or `dark:` variants to remaining hard-coded colours (`transaction-items-accordion.scss`, cell templates F2). Remove the FA CDN link (L2). | |
| 9 | JS/build: `npm remove jquery-ui jquery-ui-bundle`; `npm i jquery`; drop `.addExternals({jquery:'jQuery'})` (`webpack.config.js:53`) and the duplicate `enableStimulusBridge` (`:52` vs `:57`); rebuild (`npm run build`). Optionally switch to the single-application path (`startAdminata({application: app})`, `R/gap-js-architecture.md` §4.2). | |
| 10 | Run the acceptance suite (§3.4) in light and dark mode; run `composer test:static:ci`, `composer test:unit` (unchanged), `composer test:bdd:*` (API suites, unaffected). | `.github/workflows/ci.yml:177-211` gates. |

### 3.2 Effort per item (hours, one senior engineer who knows the app)

| Item | Effort | Class |
|---|---|---|
| Steps 0–4 (install, audit, config) | 1.0 | mechanical |
| Datepicker theme decision + verifying the 3 time-only pickers (`RecomatAdmin.php:314-341`, collection entries) and 5 range filters | 1.5 | depends on G5 quality |
| Delete dead templates/blocks/overrides | 0.5 | |
| Universal modal → `<dialog>` + 4 field templates | 1.5 | |
| Flowbite alert replacement | 0.25 | |
| `notice` include → `parent()` ×9 | 0.5 | |
| `user_block` port | 0.5 | |
| Login page port | 2.0 | |
| `list_with_summaries` → `list_after_table` | 0.5 | needs adminata block |
| CSS pruning + dark-mode guards + `.mt-10` rename + visual QA of the 13 pastel cell templates | 4.0 | largest single item |
| Delete `admin-theme`, remove FA CDN, verify all 70+ icon names render (A-13 script) | 1.0 | |
| Build changes (jQuery ownership, drop jquery-ui) | 0.75 | |
| Acceptance suite (BrowserKit, 18 scenarios) incl. an `AdminUser` fixture | 10–12 | reusable as adminata's external acceptance job |
| **Total** | **≈ 24–27 h** | |

### 3.3 Test environment facts

`APP/r3-panel-docker/docker-compose.yaml` provides Percona 8.4 (`:23`), Mongo 7 (`:42`), Valkey (`:63`), NATS (`:78`); CI boots it (`ci.yml:148-171`) and runs PHPUnit suites `Project Test Suite`, `libTests`, `integration` (`phpunit.xml.dist:42-55`). `symfony/browser-kit` and `css-selector` are dev deps (`composer.json:209-210`); Panther is **not** installed (the Mongo fork's `tests/Functional/BasePantherTestCase.php:20-48` shows the Selenium-grid pattern to copy if JS scenarios are wanted). Test sessions use `mock_file` (`framework.yaml:45-49`). There is **no** fixture bundle data (`src/DataFixtures` absent) — the suite needs a `tests/acceptance/Fixtures/AdminUserFixture` creating an `App\Entity\AdminUser` with `ROLE_SUPER_ADMIN` (`security.yaml:15-26`) and `$client->loginUser($user, 'admin')`.

### 3.4 Acceptance scenarios (adminata's external acceptance suite, run against this app)

Selectors below are contract hooks (`R/packaging.md` §3.5, `R/list-datagrid.md` §8.3, `R/forms-edit.md` §10.4). "BK" = BrowserKit (`WebTestCase`), "P" = Panther/Selenium (JS).

| ID | Scenario | Steps | Assertions (contract) | Driver |
|---|---|---|---|---|
| A1 | Login page renders and authenticates | GET `/admin/login`; submit `_username`, `_password`, `_csrf_token` (from the page) with `Referer` | 200; `form[action$="/admin/login"]`, `input[name="_csrf_token"]`; after POST: 302 → `/admin/stats` (`security.yaml:172`) | BK |
| A2 | Login error | wrong password | `.alert.alert-danger` (compat) contains the translated error | BK |
| D1 | Sonata dashboard (`/admin/dashboard` is shadowed → assert the redirect; call `sonata_admin_dashboard` through the layout logo link) | GET `/admin/dashboard` | 302 → `/admin/stats` | BK |
| D2 | Stats dashboard + shell | GET `/admin/stats` as admin | `body.sonata-bc`; `.main-sidebar .sidebar-menu li.treeview` count = 19 groups minus role-gated; every group `<i>` has `fa-*` class from `sonata_admin.yaml`; `li.sidebar-section-header` present; `[data-controller="chart"]` ×4; `.box.box-success` etc. (compat) | BK |
| D3 | User dropdown | GET any page | `.dropdown-user` contains `a[href$="/admin/gopher/edit-password"]` and `a[href$="/admin/logout"]` | BK |
| L1 | Recomat list | GET `/admin/app/recomat/list` | `table.sonata-ba-list`; `th.content-width`? (no — that is DRS) `th.sonata-ba-list-field-header-batch`; cells `td.sonata-ba-list-field-VIRTUAL-STATUS`? (PartnerPromo) — use `td.sonata-ba-list-field[objectId]`, 13 pastel cell templates present (`td[style*="background-color"]`), action cell `.btn.btn-action-icon.btn-action-edit`, `.btn-action-map`, `.btn-action-link` | BK |
| L2 | Filters panel + text filter | GET `…/list?filter[postcode][value]=00-001` (+ `filter[_page]`, `_per_page`) | `#filter-list-<uniqid>` links (`a.sonata-toggle-filter`), filter form `.sonata-filter-form`, result rows filtered, `.sonata-ba-list-field-header-order-*` on sortable headers | BK |
| L3 | Sonata `ModelAutocompleteType` filter (`city`) | GET `/admin/core/get-autocomplete-items?admin_code=sonata.admin.devices.recomat&field=city&q=War&_context=filter&_page=1&_per_page=10&uniqid=…` (XHR header) | JSON `{status:'OK', items:[{id,label}]}`; page markup contains `#…_autocomplete_input[data-sonata-select2="false"]` and adminata's `data-controller="sonata-autocomplete"` | BK (+P for the dropdown) |
| L4 | ux-autocomplete filter (`groups`) coexists | GET list page | exactly one `select[data-controller~="symfony--ux-autocomplete--autocomplete"]` for `filter[groups]` and **no** `sonata-select` on it | BK; P: one `.ts-wrapper` per field |
| L5 | Sorting, per-page, pager | GET `…?filter[_sort_by]=id&filter[_sort_order]=DESC&filter[_per_page]=10` | `.pagination` / `select.per-page`, first row id max | BK |
| L6 | XHR child list (accordion) | GET `/admin/app/packagereturnitem/list?filter[transaction][value]=<id>` with `X-Requested-With` | response contains `table.sonata-ba-list`, no `<html>`; `.sonata-list-table` wrapper | BK |
| L7 | Export | GET `/admin/app/clientuser/export?format=csv` (`ClientUserAdmin.php:81`) | 200 CSV | BK |
| L8 | Batch delete | POST `/admin/app/city/batch` (`static_content.city` admin has default `delete` batch) with `_sonata_csrf_token`, `action=delete`, `idx[]` → confirmation page `.sonata-ba-delete`/`batch_confirmation` → POST `confirmation=ok` | flash `.alert-success`, row gone | BK |
| L9 | List summaries (`templates.list`) | GET `/admin/app/transaction/list?filter[created][value][start]=…&…[end]=…` (`SUMMARY_FIELDS` admin) | summary table present after the list card (`.box.box-primary table.sonata-ba-list` or `list_after_table`) | BK |
| F1 | Edit page structure (Recomat) | GET `/admin/app/recomat/<id>/edit` | `form.sonata-ba-form`? (`.sonata-ba-form` wrapper), group containers with `col-md-9`/`col-md-3` classes (compat) inside a 12-col grid, `.box.box-primary` per group, `#sonata-ba-field-container-*`, `input[name="_lock_version"]`? (`lock_protection`), `input[name$="[_token]"][data-controller="csrf-protection"]`, activation `.box.box-default` with `input[name="_token"]` (P5), `[data-controller="operators-id-toggle"]`, `[data-controller="location-map"]` | BK |
| F2 | Admin form with ux-autocomplete (RecomatGroup) | GET `/admin/app/recomatgroup/create`; POST with `devices[]` ids | `select[multiple][data-controller~="symfony--ux-autocomplete--autocomplete"]`; no `sonata-select`; save succeeds | BK |
| F3 | Non-admin page with ux-autocomplete + `DateTimePickerType` (`transaction/create_manual`) | GET `/admin/app/transaction/create` (`sonata_admin.yaml:70`) | layout shell present; `client` autocomplete select; `created` input carries adminata's `data-controller="sonata-datepicker"` **or** form-extensions' `datepicker` per step 5 | BK (+P: picker opens) |
| F4 | Time-only pickers (`turnsOnAt`/`turnsOffAt`) | GET Recomat edit | picker options translated (`noCalendar`/`enableTime`), value `07:30` round-trips on POST | P |
| F5 | Optimistic lock | two clients edit the same Recomat; second POST | `.alert-danger` with `flash_lock_error` text | BK |
| F6 | Sonata `CollectionType` add/remove (weekdayHours) | Recomat edit | `[data-controller="sonata-collection"]`, `data-prototype`, add → new `.sonata-collection-row`, delete | P |
| F7 | Create/update flow buttons | POST edit with `btn_update_and_edit` / `btn_update_and_list` | redirects as today; flash `sonata_flash_success` rendered | BK |
| F8 | Delete via `sonata.delete` (MediaFile) | GET `/admin/app/mediafile/<id>/delete` → POST `_method=DELETE`, `_sonata_csrf_token` | `MediaFileController::deleteAction` reached; redirect to list; flash | BK |
| F9 | Password change page | GET `/admin/gopher/edit-password`; POST | `form-control` inputs (compat), `.btn.btn-success`; flash `success` rendered by adminata's partial via `{{ parent() }}` | BK |
| F10 | Custom list action pages | GET `/admin/app/addedean/<id>/approve` etc. from the `list__action_*` links | redirect + flash | BK |
| S1 | Show page (`supportShowClient`) | GET `/admin/app/supportthread/<id>/show` | `.sonata-ba-view-container`, custom show field link | BK |
| X1 | `use_select2: true` run | same as L4/F2 with the option flipped in a test-only config | still exactly one Tom Select per ux-autocomplete field (`window.TomSelect` instance count via P) | P |
| X2 | Dark mode | set `sonata_theme=dark` cookie | `html.dark`; cells with inline `background-color` still have readable text (contrast check on the 13 F2 templates) | P |
| X3 | Contract smoke | every page above: no `[data-toggle]`/`[data-dismiss]` outside the compat delegate scope; `window.Admin`, `window.sonataApplication`, `window.jQuery` defined; `sonata-config` meta present | P |

---

## 4. Derived rules for `bin/console adminata:audit-overrides` and `UPGRADE-1.0.md`

### 4.1 Audit rules

The command walks `templates/` (all Twig), `assets/` (`*.js`, `*.ts`, `*.scss`, `*.css`), `config/packages/sonata_admin.yaml`, `config/packages/twig.yaml`, `webpack.config.js`/`importmap.php`, and `src/` (PHP string literals). Each rule: id, detector, severity, message, and the hits it produces on this app (which doubles as the command's fixture).

| Id | Detector | Severity | Message | Hits in `APP/` |
|---|---|---|---|---|
| A-01 | File under `templates/bundles/SonataAdminBundle/**` whose relative path is not in adminata's `Resources/views` | error | "Overrides a template that does not exist (`%s`); it is never rendered." | `CRUD/Association/base_list_inner_row.html.twig` |
| A-02 | Any override under `templates/bundles/SonataAdminBundle/**` | info | "Override of `%s` — diff against adminata's version: %d lines changed; blocks overridden: …" (list `{% block %}` names not present in the adminata file → error "block `%s` no longer exists") | 16 files; no missing blocks |
| A-03 | Twig block names in files extending `@SonataAdmin/...` or `@!SonataAdmin/...` that are not defined in the parent chain (resolve `extends` recursively) | error | "Block `%s` is not defined by `%s`." | none (all 33 layout blocks + `list_footer`, `field`, `form`, `actions`, `notice`, `user_block` exist) |
| A-04 | Regex `\b(btn(-[a-z]+)?|box(-[a-z]+)?|col-(xs|sm|md|lg)-\d+|form-group|form-control|has-error|help-block|well|pull-(left|right)|label-(default|primary|success|info|warning|danger)|alert(-[a-z]+)?|navbar(-[a-z]+)?|dropdown(-[a-z]+)?|caret|glyphicon|login-(box|page)|user-header|bg-(light-blue|aqua|green|red|yellow)|table-(bordered|striped|hover)|nav-tabs|input-group|progress(-bar)?|text-(muted|success|danger|warning|info)|hidden-(xs|sm|md|lg))\b` in Twig class attributes and PHP `'class' =>` strings | warning | "Bootstrap 3/AdminLTE class `%s` (%d×) — rendered through `compat-bootstrap3.css`; port to adminata classes or keep the compat stylesheet." | 96 templates: ~410 tokens (top: `form-group` 26, `col-md-12` 24, `btn-success` 14, `box-body` 13, `label-*` 22, `btn-default` 9); PHP: 17 `col-md-*`, 3 `form-control`, 2 `btn btn-success` |
| A-05 | `data-toggle=`, `data-dismiss=`, `data-widget=` | warning | "Bootstrap data-API attribute `%s` — handled by the compatibility delegate; `data-toggle=\"modal\"` targets must become `<dialog>`." | `standard_layout_override.html.twig:61`, `generic_create.html.twig:19` |
| A-06 | `\.(modal|tab|dropdown|collapse|popover|tooltip|select2|iCheck|editable|sortable|ajaxSubmit|slimScroll|masonry)\(` in JS and inline `<script>` | error | "jQuery plugin `$.fn.%s` is not shipped by adminata." | `UniversalModal.js:8,18` (`.modal(`) |
| A-07 | `\$\(` / `jQuery(` in `assets/**` and `<script>` blocks | info | "%d jQuery call sites — jQuery is provided by `bundles/sonataadmin/vendor/jquery.js` (deprecated, removed in 2.0)." | 32 |
| A-08 | `addExternals\(\{\s*jquery` in `webpack.config.js` or `importmap` entries mapping `jquery` to a global | warning | "Build expects a global jQuery; bundle your own before removing `vendor/jquery.js`." | `webpack.config.js:53` |
| A-09 | Regex `\bfa fa-[a-z0-9-]+` where the name is in the FA4→FA6 rename table (`sign-out`, `clock-o`, `external-link-square`, `cloud-upload`, `map-marker`, `cog`, `cogs`, `save`, `edit`, `unlink`, `desktop`, …) | info / warning if in the shim-only set (`clock-o`, `calendar-o`, `file-o`, …) | "FontAwesome 4 name `%s` resolves through the v4 shim; prefer `%s`." | 24 names, 1 shim-only (`rvmTaskState.html.twig:6`) |
| A-10 | `glyphicon` | error | "Glyphicons were never shipped by Sonata 4; the element renders empty." | `login_form.html.twig:39,44` |
| A-11 | `canonicalize_locale_for_moment`, `moment-locale/`, `select2-locale/`, `bundles/sonataadmin/admin-lte-skins` | warning | "`%s` is a no-op / the asset no longer exists." | `standard_layout_override.html.twig:38,40,47` |
| A-12 | `{% embed|include '…' %}` whose target does not resolve (Twig loader `exists()`) | error | "Template `%s` not found." | `standard_layout_override.html.twig:72` (`flowbite/components/alert.html.twig`) |
| A-13 | Icon names (`fa[srb]? fa-x`, raw `<i class>` in `sonata_admin.yaml`, `'icon' =>`) checked against adminata's shipped FA metadata | error for unknown | "Icon `%s` is not in FontAwesome %s Free." | 0 errors with FA 6.7 + shims; 10 errors if adminata shipped FA5 |
| A-14 | `twig.form_themes` containing `@SonataForm/Form/datepicker.html.twig` or `extra_javascripts` containing `bundles/sonataform/app.js` before `bundles/sonataadmin/app.js` | warning | "form-extensions' datepicker theme/JS is no longer loaded by default; see UPGRADE §U9." | `twig.yaml:4` |
| A-15 | Twig templates under `templates/` not referenced by any `render(`, `setTemplate`, `templates.*` config, `extends`/`include`/`embed` | info | "Possibly unused template `%s`." | 8 (§1.5 P9 + `recomat/list.html.twig` no-op) |
| A-16 | App CSS class names that are also Tailwind v4 utilities emitted by adminata's `app.css` (`mt-\d+`, `hidden`, `flex`, `grid`, `container`, `fixed`, `collapse`, `modal-content`?) | warning | "Selector `.%s` collides with a Tailwind utility; rename (e.g. `app-%s`)." | `.mt-10` (`sonata-overrides.scss:13`), `.hidden` toggled in `SectionSlider.js:10-12` |
| A-17 | Hard-coded light colours (`#fff`, `#f7f9ff`, `lightgray`, `background(-color)?:\s*#[ef]`) in app CSS/inline styles inside `templates/field/**` | info | "Light-only colour — add a `.dark` variant or scope under `html:not(.dark)`." | 24 cell templates, 6 CSS rules |
| A-18 | `{% block notice %}` overrides that `include` `@SonataTwig/FlashMessage/render.html.twig` or `@SonataCore/…` directly | warning | "Replace with `{{ parent() }}` to use adminata's flash partial." | 9 templates |
| A-19 | `body_attributes`/`admin_lte_skin_class` overrides referencing `_skin`, `_use_select2`, `_use_icheck` | info | "Relies on layout-scope variables; supported, but prefer `sonata_config.getOption('skin')`." | `standard_layout_override.html.twig:12` |
| A-20 | Second Stimulus application (`startStimulusApp(`) registering an identifier starting with `sonata-` (scan `assets/controllers/*` file names and `controllers.json`) | error | "Controller `%s` collides with adminata's namespace." | none |
| A-21 | `'editable' => true` list options, `ModelListType`, `'edit' => 'inline'` | info | "Uses fetch-based flows; verify CSRF with stateless token ids (UPGRADE §U11)." | none |
| A-22 | `framework.csrf_protection.stateless_token_ids` present | info | "Stateless CSRF detected — adminata's fetch flows mint tokens through the `submit` event; keep `assets/controllers/csrf_protection_controller.js`." | `csrf.yaml:8-11` |

Output: a Markdown/console table plus `--format=json` for CI; exit code 1 on any `error`.

### 4.2 `UPGRADE-1.0.md` sections this audit implies (with snippets from the app)

**U1 — Layout overrides.** Keep extending `@!SonataAdmin/standard_layout.html.twig`; all 33 block names survive. Delete copied `logo`/`javascripts` blocks (they were identical to upstream). `canonicalize_locale_for_moment()` still exists and still returns `null`; `bundles/sonataadmin/{moment-locale,select2-locale,admin-lte-skins}/` are gone.

```twig
{# before (standard_layout_override.html.twig:37-41) #}
{% set localeForMoment = canonicalize_locale_for_moment() %}
{% if localeForMoment %}<script src="{{ asset('bundles/sonataadmin/moment-locale/pl.js') }}"></script>{% endif %}
{# after #}
{# (delete) #}
```

**U2 — Modals.** Bootstrap's `$.fn.modal` is not shipped. Markup with `data-dismiss="modal"` keeps closing through the compat delegate, but opening must use a `<dialog>` (§2a snippet, before/after). Custom `.modal` blocks appended to `sonata_wrapper` stay hidden by `compat-bootstrap3.css`.

**U3 — Flash messages.** `{% block notice %}` should call `{{ parent() }}`; including `@SonataTwig/FlashMessage/render.html.twig` directly still works (compat alerts + `data-dismiss="alert"` delegate) but renders Bootstrap markup.

```twig
{# before (security/password_change.html.twig:5-7) #}
{% block notice %}{% include ['@SonataCore/FlashMessage/render.html.twig', '@SonataTwig/FlashMessage/render.html.twig'] %}{% endblock notice %}
{# after #}
{% block notice %}{{ parent() }}{% endblock notice %}
```

**U4 — `user_block` / `add_block`.** Still included verbatim into a `<ul>`; `<li>` items keep working. AdminLTE `user-header`/`bg-light-blue`/`btn-flat` render through compat; see §2b for the recommended markup. adminata does not hard-code SonataUser routes.

**U5 — Login page.** The cookbook recipe keeps rendering through compat (`login-box`, `btn-flat`, `col-xs-4`); Glyphicons were never shipped. Port to the TailAdmin sign-in form (§2b) or extend adminata's `Security/login.html.twig` (G7).

**U6 — Bootstrap 3 classes and `col-md-*` groups.** Group `class`/`box_class` options, `header_class`, dashboard block `class` keep their PHP defaults and are rendered through the compat grid (`.row`/`.col-*`) and `.box*` families. `.hidden` is honoured with `!important`. `remove_stylesheets: [bundles/sonataadmin/compat-bootstrap3.css]` drops the layer once ported.

**U7 — Icons.** FontAwesome 6 Free + v4 shims are bundled (`fa-clock-o` etc. keep working); FA6-only names now work without a CDN. Raw `<i>` HTML in `dashboard.groups.*.icon` is unchanged. `parse_icon` still throws for non-FA strings.

**U8 — jQuery.** Provided as `bundles/sonataadmin/vendor/jquery.js` (deprecated). If your build declares jQuery as an external (`APP/webpack.config.js:53` `.addExternals({ jquery: 'jQuery' })`), bundle your own copy before removing it. jQuery UI, select2, iCheck, x-editable, jquery-form and Bootstrap JS are gone (`R/gap-js-architecture.md` §6).

**U9 — form-extensions date pickers.** `bundles/sonataform/app.{js,css}` are no longer in the default asset lists; admin forms use `sonata-datepicker` (flatpickr). Apps that registered `@SonataForm/Form/datepicker.html.twig` in `twig.form_themes` for non-admin forms must switch to adminata's standalone theme or re-add the sonataform assets **after** `bundles/sonataadmin/app.js`. Tempus Dominus `datepicker_options` (`display.components.calendar/clock/seconds`) are translated; verify time-only fields.

**U10 — Dark mode.** adminata stamps `html.dark`; user CSS with hard-coded light backgrounds (zebra rows, `body{background:lightgray}`, inline `background-color` in cell templates) should add `.dark` variants or scope under `html:not(.dark)`.

**U11 — Stateless CSRF.** Sonata's `sonata.delete`/`sonata.batch` tokens are unchanged (session). Form `_token`s under `framework.csrf_protection.stateless_token_ids` are minted by Symfony's `csrf_protection_controller.js` on the `submit` event; adminata's fetch flows trigger that event and send cookies, so keep the controller installed.

**U12 — `use_select2` and ux-autocomplete.** `sonata-select` never touches `[data-controller*="autocomplete"]`, `[data-sonata-select2="false"]` or elements already carrying a Tom Select instance; `use_select2: true` is now safe next to `symfony/ux-autocomplete`. Custom `.ts-*` skins keep precedence (specificity ≤ (0,2,0) in adminata).

**U13 — Two Stimulus applications.** Supported; never register `sonata-*` identifiers in your own application. Single-application setup: `startAdminata({ application })`.

**U14 — Utility-name collisions.** adminata's `app.css` emits Tailwind utilities; app classes named like utilities (`mt-10`, `hidden`, `fixed`, `collapse`, `container`) now carry Tailwind semantics. Rename app-specific ones.

**U15 — Non-existent override paths.** `templates/bundles/SonataAdminBundle/CRUD/Association/base_list_inner_row.html.twig` never matched a Sonata file; the row template is `CRUD/base_list_inner_row.html.twig`. `adminata:audit-overrides` lists such files.

**U16 — Marker classes kept for user CSS.** `sonata-bc`, skin class, `main-header`, `main-sidebar`, `sidebar`, `sidebar-menu`, `treeview`, `treeview-menu`, `content-wrapper`, `content-header`, `content`, `navbar navbar-default` (toolbar), `nav navbar-nav` (actions/filters lists), `dropdown-user`, `breadcrumb`, `sonata-ba-*`. Rules written against them still match; rules written for AdminLTE's look (`_sidebar.scss`, `_header.scss` in this app) should simply be deleted.

---

## 5. Contract additions adminata must adopt (derived from this app)

1. **Layout-scope variables**: keep `{% set _skin %}`, `_use_select2`, `_use_icheck`, and the `_preview/_form/_show/_list_table/_list_filters/_list_filters_actions/_tab_menu/_content/_title/_breadcrumb/_actions/_navbar_title` captures exactly as `standard_layout.html.twig:12-27` defines them (child blocks read them; `strict_variables` in test envs).
2. **Marker classes on toolbar containers**: `<ul class="nav navbar-nav navbar-right sonata-actions">` for `sonata_admin_content_actions_wrappers` and the filters list (`base_list.html.twig:260`), `<nav class="navbar navbar-default …">` for the content-header toolbar — zero cost, keeps `sonata-overrides.scss:107-110,218-282`-style user CSS matching and the `sonata-sticky` `navbar` target semantics unchanged.
3. **Compat layer**: `.hidden { display: none !important }`; `.text-success/.text-warning/.text-danger/.text-muted/.text-info`; the AdminLTE login family (`login-page`, `login-box`, `login-box-body`, `login-box-msg`, `login-logo`); `.user-header`, `.bg-light-blue`; `.progress/.progress-bar(-success)`; `.btn-secondary` (Bootstrap 4 name seen in `standard_layout_override.html.twig:61`) — all `@layer components` under `.sonata-bc`.
4. **`sonata-modal` is usable on user dialogs** (`data-controller="sonata-modal"` on any `<dialog>`, `open()/close()` actions), so `$('#x').modal()` ports are one-liners.
5. **`list_after_table` block** in `CRUD/base_list.html.twig` (additive), for footer-adjacent user content (§2c).
6. **`ajax_layout` list rendering keeps `table.sonata-ba-list`** and the `.sonata-list-table` wrapper (`ajax_layout.html.twig:25`) — the app parses XHR list pages.
7. **`sonata_header` renders nothing** when `logo` and `sonata_nav` are both empty (login-page pattern), or adminata ships `Security/login.html.twig` on `empty_layout`.
8. **Standalone datepicker form theme** registrable in `twig.form_themes` for non-admin forms (G5), plus the Tempus Dominus `display.components` → flatpickr mapping.
9. **FA6 Free + v4 shims** (C20) — confirmed necessary (`fa-clock-o`, 10 FA6-only names).
10. **CSRF for fetch flows** per §2f (dispatch `submit`, `credentials: 'same-origin'`).
11. **`Button/*` and `user_block` includes remain `<li>` pass-through**; `_actions` heuristics unchanged.
12. **`canonicalize_locale_for_moment()`** kept (deprecated) — critique §5 item 3 confirmed.

---

## 6. Open questions for the project owner

1. Is `templates/message/*` (a `MessageAdmin` that no longer exists), `generic_create.html.twig`, `recomat/confirm_archive.html.twig` and `promo_code/upload.html.twig` safe to delete, or are they kept for a planned feature?
2. Should the acceptance suite live in the app (`tests/acceptance`, BrowserKit, run in the app's CI) or in adminata's repo as a nightly job that checks out the app (private repo → needs a deploy key)? `R/packaging.md` §3.6 assumed the latter for the Mongo fork.
3. Panther/Selenium for the 6 JS scenarios (F4, F6, L3-dropdown, X1, X2, X3): reuse the Mongo fork's `BasePantherTestCase` + a `selenium/standalone-firefox` service in `r3-panel-docker/docker-compose.yaml`, or accept BrowserKit-only coverage in the app and run JS scenarios only in adminata's demo app?
4. `DeprecationLevel` → alert type mapping for L9 (values not read here).
5. Whether to keep the app's action-button palette (`.btn-action-*`, `sonata-overrides.scss:121-215`) or adopt adminata's list-action buttons once they exist — a design choice, not a compatibility one.
6. Turning `use_select2` on after migration (X1) — desired, or keep native selects?
