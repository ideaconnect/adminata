# 10 — Migration guide outline (`UPGRADE-1.0.md`) and the audit command

Derived from the production-app audit (`R/gap-real-app-audit.md` §3–§4) and the JS breakage
table (`R/gap-js-architecture.md` §6). The real app (Sonata 4.43.0, Symfony 8.1, ORM bundle,
MongoDB fork, second Stimulus app, ux-autocomplete, stateless CSRF, 17 bundle overrides,
17 `col-md-*` group classes, raw `<i>` icons, 660 lines of AdminLTE-fighting CSS) is the
reference case; its estimated migration effort is 24–27 hours, of which 10–12 are the reusable
acceptance suite.

## 1. Migration procedure (existing Sonata 4.43 app)

| Step | Command / edit | Why |
|---|---|---|
| 0 | commit or stash everything | Flex may touch `config/` |
| 1 | `composer require idct/adminata --no-plugins --no-scripts` then `composer install` | `--no-plugins` prevents Sonata's recipe `unconfigure` from deleting `config/packages/sonata_admin.yaml`, `config/routes/sonata_admin.yaml` and the `bundles.php` line; adminata's auto-recipe is idempotent |
| 2 | `bin/console cache:clear && bin/console assets:install public` | new `bundles/sonataadmin/` contents; `git diff -- config/` must be empty |
| 3 | `bin/console adminata:audit-overrides` | per-app checklist (§3) |
| 4 | config: nothing mandatory; optional `adminata: { theme: { mode: system } }`; later `assets.remove_javascripts: [bundles/sonataadmin/vendor/jquery.js]` | keys preserved |
| 5 | `twig.form_themes`: replace `@SonataForm/Form/datepicker.html.twig` with `@SonataAdmin/Form/datepicker.html.twig` **or** re-add `bundles/sonataform/app.{js,css}` after adminata's entries | U9 |
| 6 | delete dead overrides the audit flags (non-existent paths, copied-verbatim blocks, moment-locale branches) | |
| 7 | port: Bootstrap modals to `<dialog>` + `sonata-modal`, `notice` overrides to `{{ parent() }}`, `user_block`, login page, custom list footers to `list_after_table` | U2–U5 |
| 8 | CSS: delete AdminLTE-fighting themes; rename classes colliding with Tailwind utilities; add `.dark` variants to hard-coded light colours; drop the FA CDN link | U10, U14 |
| 9 | JS/build: own the jQuery import (drop `.addExternals({jquery: 'jQuery'})`), remove jQuery UI, optionally single-application `startAdminata({application})` | U8, U13 |
| 10 | run the acceptance suite in light and dark mode | |

## 2. `UPGRADE-1.0.md` sections

| § | Topic | Content |
|---|---|---|
| U1 | Layout overrides | keep extending `@!SonataAdmin/standard_layout.html.twig`; all 33 blocks survive; delete copied `logo`/`javascripts` blocks; `canonicalize_locale_for_moment()` still exists and returns `null`; `moment-locale/`, `select2-locale/`, `admin-lte-skins/` (as AdminLTE CSS) are gone |
| U2 | Modals | `$.fn.modal` not shipped; `data-dismiss="modal"` keeps closing through the delegate; opening needs a `<dialog>` (one-line port with `sonata-modal`); custom `.modal` blocks stay hidden via compat CSS |
| U3 | Flash messages | `{% block notice %}{{ parent() }}{% endblock %}`; direct includes of `@SonataTwig/FlashMessage/render.html.twig` still render (compat) |
| U4 | `user_block` / `add_block` | still `<li>` pass-through; AdminLTE `user-header`, `bg-light-blue`, `btn-flat` render through compat; no hard-coded SonataUser routes |
| U5 | Login page | cookbook recipe keeps rendering (compat `login-box*`); Glyphicons were never shipped; port to the sign-in template |
| U6 | Bootstrap classes and `col-md-*` groups | PHP defaults unchanged; compat grid/box families; `.hidden` honoured with `!important`; `remove_stylesheets: [bundles/sonataadmin/compat-bootstrap3.css]` once ported |
| U7 | Icons | FA6 Free + v4 shims bundled; FA6-only names work without a CDN; raw `<i>` HTML in `dashboard.groups.*.icon` unchanged; `parse_icon` still throws for non-FA strings |
| U8 | jQuery | shipped as `bundles/sonataadmin/vendor/jquery.js` (deprecated, removed in 2.0); bundle your own before removing; jQuery UI, select2, iCheck, x-editable, jquery-form, Bootstrap JS are gone (table from document 05 §9) |
| U9 | form-extensions date pickers | `bundles/sonataform/app.{js,css}` no longer default; admin forms use `sonata-datepicker` (flatpickr); Tempus Dominus options translated; verify time-only fields; script order rule |
| U10 | Dark mode | `html.dark` is stamped; add `.dark` variants or `html:not(.dark)` scopes to hard-coded light colours |
| U11 | Stateless CSRF | `sonata.delete`/`sonata.batch` unchanged (session); form `_token`s under `stateless_token_ids` are minted by Symfony's `csrf_protection_controller.js` on `submit`; adminata's fetch flows trigger that event and send cookies — keep the controller installed |
| U12 | `use_select2` and ux-autocomplete | `sonata-select` skips `[data-controller*="autocomplete"]`, `[data-sonata-select2="false"]`, existing Tom Select instances; `use_select2: true` is now safe next to ux-autocomplete; custom `.ts-*` skins keep precedence |
| U13 | Two Stimulus applications | supported; never register `sonata-*` identifiers in your own app; single-app setup via `startAdminata({ application })` |
| U14 | Utility-name collisions | `app.css` emits Tailwind utilities; app classes named `mt-10`, `hidden`, `fixed`, `collapse`, `container` change meaning — rename |
| U15 | Non-existent override paths | the audit lists files under `templates/bundles/SonataAdminBundle/` that match no adminata template |
| U16 | Marker classes kept for user CSS | `sonata-bc`, skin class, `main-header`, `main-sidebar`, `sidebar`, `sidebar-menu`, `treeview(-menu)`, `content(-wrapper|-header)`, `navbar navbar-default`, `nav navbar-nav`, `dropdown-user`, `breadcrumb`, `sonata-ba-*`; rules written for AdminLTE's look should be deleted |
| U17 | Config defaults that changed | asset lists; `skin` semantics; `use_icheck`/`use_bootlint` no-ops; `outer_list_rows_tree` unchanged |
| U18 | Behaviour changes | shift-range selection fixed; mosaic checkbox outside the tile link; preloader none; tabs underline style; per-page unchanged |

## 3. `bin/console adminata:audit-overrides`

Walks `templates/`, `assets/`, `config/packages/{sonata_admin,twig}.yaml`, `webpack.config.js` /
`importmap.php` and `src/` (PHP string literals). Output: console table or `--format=json`; exit
code 1 on any `error`. Rules (detector → severity → message):

| Id | Detector | Sev. |
|---|---|---|
| A-01 | override under `templates/bundles/SonataAdminBundle/**` whose path is not an adminata template | error |
| A-02 | any override: diff size vs adminata's version; overridden `{% block %}` names that no longer exist → error | info |
| A-03 | block names in templates extending `@SonataAdmin/…` not defined in the parent chain | error |
| A-04 | Bootstrap 3/AdminLTE class regex in Twig `class=` and PHP `'class' =>` strings | warning |
| A-05 | `data-toggle=`, `data-dismiss=`, `data-widget=` | warning |
| A-06 | `.(modal\|tab\|dropdown\|collapse\|popover\|tooltip\|select2\|iCheck\|editable\|sortable\|ajaxSubmit\|slimScroll\|masonry)(` in JS/inline scripts | error |
| A-07 | `$(` / `jQuery(` call-site count | info |
| A-08 | build declares jQuery as an external / importmap maps `jquery` to a global | warning |
| A-09 | FA4 names resolved through shims (`sign-out`, `clock-o`, …) | info / warning |
| A-10 | `glyphicon` | error |
| A-11 | `canonicalize_locale_for_moment`, `moment-locale/`, `select2-locale/`, `admin-lte-skins` references | warning |
| A-12 | `include`/`embed` targets that do not resolve | error |
| A-13 | icon names not in the shipped FA metadata | error |
| A-14 | `twig.form_themes` with `@SonataForm/Form/datepicker.html.twig`, or `sonataform/app.js` before adminata's script | warning |
| A-15 | templates referenced by nothing | info |
| A-16 | app CSS selectors that are Tailwind utility names | warning |
| A-17 | hard-coded light colours in app CSS / field templates | info |
| A-18 | `notice` overrides including `@SonataTwig/…` or `@SonataCore/…` directly | warning |
| A-19 | `body_attributes`/`admin_lte_skin_class` overrides reading `_skin`, `_use_*` | info |
| A-20 | a second Stimulus application registering a `sonata-*` identifier | error |
| A-21 | `'editable' => true`, `ModelListType`, `'edit' => 'inline'` present | info (CSRF note) |
| A-22 | `framework.csrf_protection.stateless_token_ids` present | info |

The real app is the command's test fixture (expected hits per rule are recorded in
`R/gap-real-app-audit.md` §4.1).
