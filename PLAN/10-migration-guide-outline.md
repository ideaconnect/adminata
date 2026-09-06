# 10 — Migrating recomaty-panel (`MIGRATION.md`) and generic upgrade notes

Derived from the production-app audit (`R/gap-real-app-audit.md`, re-checked against
`~/dev/r3/recomaty-panel-clean` in appendix C). There is no compatibility layer, so every item
below is a real edit in the app; the total is about 45 hours for an engineer who knows the app.

## 1. Procedure (executed once, on a branch, during phase 5)

| Step | Edit | Hours |
|---|---|---|
| 0 | commit everything; `git checkout -b adminata` | — |
| 1 | `composer require idct/adminata --no-plugins --no-scripts` (until adminata is published, a path repository: `composer config repositories.adminata path ../../idct/adminata` then `composer require idct/adminata:@dev`), then `composer remove --no-plugins --no-scripts sonata-project/admin-bundle sonata-project/doctrine-orm-admin-bundle` (the seven real packages are replaced; the two explicit requires become redundant), `composer install`; delete the seven `sonata-project/*` entries from `symfony.lock` by hand (Flex bookkeeping; with `--no-plugins` their `unconfigure` never ran, so `config/packages/sonata_{admin,block,form}.yaml` and `config/routes/sonata_admin.yaml` are untouched — `git diff -- config/packages config/routes` must be empty); `bin/console cache:clear && bin/console assets:install public`; `bundles.php` keeps its Sonata bundle classes plus the MongoDB one, minus `SonataBlockBundle`, which the merge of 2026-09-06 deleted (01 P10) | 0.5 |
| 2 | `config/packages/sonata_admin.yaml`: delete `options.use_select2`; optional `theme: { mode: system }`; `assets.remove_stylesheets: [bundles/sonataadmin/app.css]` once the app compiles Tailwind (step 11) | 0.5 |
| 3 | `config/packages/twig.yaml`: unchanged (`@SonataForm/Form/datepicker.html.twig` still exists and now renders native inputs); optionally add `@SonataAdmin/Form/form_admin_fields.html.twig` so the plain Symfony forms on admin pages share the admin look | 0.2 |
| 4 | Layout override `templates/layout/standard_layout_override.html.twig`: delete the `admin_lte_skin_class`, `javascripts`, `sonata_javascript_config`, `sonata_javascript_pool` blocks (a dead skin class and dead moment/select2 branches); **port** `logo` rather than deleting it — it is not a verbatim upstream copy, it renders the signed-in administrator's White Label, and deleting it would cost every branded customer their mark; drop the Font Awesome CDN link; replace the Flowbite alert embed in `sonata_page_content_header` with an `adm-alert` div; rewrite the `#universal-modal` in `sonata_wrapper` as a `<dialog class="adm-dialog" {{ stimulus_controller('sonata-modal') }}>` | 2 |
| 5 | Login page, password-reset layout, `user_block`: TailAdmin sign-in recipe on the same blocks (`sonata_nav`, `logo`, `sonata_left_side`, `body_attributes`, `sonata_wrapper`); drop `glyphicon` spans; user dropdown items as `adm-dropdown__item` | 3 |
| 6 | Nine page templates with hard-coded `notice` includes → `{% block notice %}{{ parent() }}{% endblock %}` (the included `@SonataTwig/FlashMessage/render.html.twig` is adminata's now, so even the old includes render correctly; the parent call is still cleaner) | 0.5 |
| 7 | Page templates: `admin/dashboard_stats` (AdminLTE boxes → `adm-card`/metric cards, keep `data-controller="chart"`), `transaction/create_manual`, `promo_code/create`, `recomapp/legacyEans/{create,reject}`, `pocket_rvm/reject`, `recomat/clone`, `recomat/edit` (activation box → `adm-card`, buttons → `adm-btn`), `test_runner/launch`, `security/password_change`; delete dead `generic_create`, `message/*`, `recomat/confirm_archive`, `promo_code/upload` | 6 |
| 8 | Bundle overrides under `templates/bundles/SonataAdminBundle/`: 16 `list__action*` templates (base, four standard, eleven custom) → `adm-btn-icon adm-btn-icon-{edit,show,delete,…}`, keep the `*_link` hooks and add `sr-only` labels; `Button/create_button` → same slot, `adm-btn`; `crud/button_launch_test` likewise; `list_enum`, `Association/list_many_to_one`, `list__select`: replace `label label-*`/`btn` with `adm-badge`/`adm-btn`; delete `Association/base_list_inner_row.html.twig` (never resolved) | 3 |
| 9 | `crud/list_with_summaries.html.twig`: move the summary table into `{% block list_after_table %}` (no stray `</div>`), `adm-card` + `adm-table` | 1 |
| 10 | 55 cell templates under `templates/field/` (1,041 lines): `callout` (316 uses), `label label-*`, `btn*`, `box*`, `table*`, `text-*`, `progress*`, inline pastel `background-color`s → `adm-callout-*`, `adm-badge-*`, `adm-btn-*`, Tailwind utilities with `dark:` variants (tokens instead of hex); keep the `<td class="sonata-ba-list-field …" objectId>` envelope and `{% extends '@SonataAdmin/CRUD/base_list_field.html.twig' %}` | 12 |
| 11 | CSS/build: add `assets/styles/admin.css` per document 04 §7 (`@tailwindcss/postcss` 4.3.3 in Encore); delete `admin-theme.scss` + partials (453 lines) and the AdminLTE-fighting parts of `sonata-overrides.scss` (658 → about 150 lines); rename `.mt-10` → `.app-mt-10` (and `PasswordChangeForm` `row_attr`); update the `font-family: 'Font Awesome …'` rule to `"Font Awesome 7 Free"` | 4 |
| 12 | Admin classes: 31 group classes `col-md-N` → `col-span-12 md:col-span-N` (`'section-geolocation col-md-12 hidden'` → `'section-geolocation col-span-12 hidden'`); remove `'class' => 'form-control'`/`'btn btn-success'` attrs in `NoteForm`, `PasswordChangeForm`, `SmartLoginAssignForm` | 1 |
| 13 | Date/time fields (12 usages, document 06 §4): **delete** the `format` options (`dd-MM-yyyy HH:mm`, `dd.MM.yyyy`, `dd.MM.yyyy H:i:s`, `H:mm`, `yyyy-MM-dd`) — adminata's `BasePickerType` now fixes the HTML5 wire format from `display.components`; keep the `datepicker_options` | 1 |
| 14 | Icons: `fa fa-clock-o` → `fa fa-clock` (`rvmTaskState.html.twig`); nothing else (75 of 76 names resolve in FA7 Free) | 0.3 |
| 15 | JS: port instead of re-adding jQuery: `UniversalModal.js` → `dialog.showModal()` on the new `<dialog>` (19 lines), `SectionSlider.js` → toggle the `hidden` attribute (14 lines), `TransactionItemsAccordion.js` → `fetch` + `insertAdjacentHTML` (56 lines); delete `import $ from 'jquery'`, `jquery-ui-bundle` imports and the two npm deps; remove `.addExternals({ jquery: 'jQuery' })`; `npm ls jquery` must be empty | 2 |
| 16 | Tests: run the Behat suite; add the BrowserKit and Panther scenarios of document 08 §6; fix what breaks (adminata or app) | 8 |
| **Total** | | **≈ 45** |

## 2. What the app keeps unchanged

`bundles.php` **except one line** — `SonataBlockBundle` goes, since the merge of 2026-09-06
(01 P10); admin classes and their `configure*` methods; `config/services/admin/*.yaml` tags
and calls (`setTemplate`, `setFormTheme`, `setListActions`); `sonata_doctrine_orm_admin.templates.types`;
`sonata_block.yaml`, `sonata_form.yaml`, `twig.yaml`; custom controllers and routes;
`SidebarMenuSubscriber` (its `sidebar-section-header` items render as TailAdmin group titles);
`BaseAdmin` summaries and deprecation messages; the second Stimulus application and its seven
controllers; ux-autocomplete fields and filters; Dropzone; `csrf.yaml` stateless tokens;
`lock_protection`; `use_stickyforms`; flash keys; all `admin_app_*` route names; the
`idct/sonata-admin-mongodb-bundle` dependency and its `DebugRequestAdmin`.

## 3. `UPGRADE-1.0.md` (generic notes for other Sonata 4.43 apps)

| § | Topic |
|---|---|
| U1 | Install: `composer require idct/adminata --no-plugins --no-scripts`, remove explicit `sonata-project/*` requires (including `block-bundle`, `exporter`, `form-extensions` and `twig-extensions`, which adminata now `conflict`s with), clean `symfony.lock`, `composer install`, `cache:clear`, `assets:install`; `bundles.php` loses its `SonataBlockBundle`, `SonataFormBundle`, `SonataTwigBundle` and `SonataExporterBundle` lines and nothing else, and the `Sonata\BlockBundle\` → `Sonata\AdminBundle\` (01 P10), `Sonata\Form\`/`Sonata\Twig\` → `Sonata\AdminBundle\` (01 P14) and `Sonata\Exporter\` → `Sonata\AdminBundle\Exporter\` (01 P15) class maps apply to any of those classes the app names — **including the `CollectionType` ↔ `NativeCollectionType` swap, the one break that compiles**; strings the app overrides move from `translations/Sonata{Block,Form,Twig}Bundle.<locale>.xliff` into `translations/SonataAdminBundle.<locale>.xliff`, ids unchanged (01 P13, P14) |
| U2 | Removed config nodes (`options.skin`, `use_select2`, `use_icheck`, `use_bootlint`); changed defaults (asset lists, `dashboard.blocks[].class`, group `class`, `box_class`); `sonata_form` picker `format` fixed by the type |
| U3 | Markup vocabulary: Bootstrap/AdminLTE classes are gone; `.adm-*` components; compile Tailwind yourself for arbitrary utilities (document 04 §7) |
| U4 | Layout overrides: blocks kept (document 02 §5), `admin_lte_skin_class` and `_skin` gone, `notice` → `{{ parent() }}` |
| U5 | JavaScript: no jQuery, no `window.Admin`; `window.sonataApplication` only; controllers and events (document 05); modals are `<dialog>` + `sonata-modal`; no AJAX form submission anywhere |
| U6 | Forms: native selects; native date/time inputs (`bundles/sonataform/*` gone; `datepicker_options.display.components` still honoured; `format` no longer configurable); the form types are `Sonata\AdminBundle\Form\Type\` and the `CollectionType` swap is in U1 |
| U7 | Icons: Font Awesome 7 Free, no v4/v5 shims; rename v4-only names |
| U8 | Dark mode: `html.dark` is stamped; add `dark:` variants to hard-coded colours |
| U9 | Not yet ported templates (document 03 §E, §G) still render their Bootstrap markup unstyled; open an issue when you need one |
