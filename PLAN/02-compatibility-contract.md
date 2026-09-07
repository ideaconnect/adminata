# 02 — Stable interfaces (what 1.0 keeps identical)

Everything listed here exists in adminata 1.0 with the same name and semantics. The list is
derived from what `idct/sonata-admin-mongodb-bundle`, the app's admin classes and templates, the
packages' own tests and the inherited JavaScript actually touch (`R/php-compat.md` §2,
`R/packaging.md` §3.5, appendix C). It is a contract with the code adminata must run with, not a
compatibility layer for hypothetical users: anything not listed may change. Each item becomes an
executable test in phase 1 (document 08 §4).

**Amendment, 2026-09-06 (01 P10).** The block bundle is merged into the admin bundle, and its PHP
namespace is the one item this contract gives up: `Sonata\BlockBundle\*` is now
`Sonata\AdminBundle\*` and `SonataBlockBundle` no longer exists. Everything else about blocks that
this document lists — the `sonata_block` config root, the `sonata.block.*` service ids, the five
`sonata_block_*` Twig functions, the `@SonataBlock` Twig namespace, the twelve block template paths
~~and the `SonataBlockBundle` translation domain~~ — is unchanged and still under contract. The rows
below are annotated where they are affected rather than rewritten.

**Amendment, 2026-09-06, later the same day (01 P13).** The `SonataBlockBundle` translation domain
is given up as well: the block strings are units of `SonataAdminBundle` (§11). The config root, the
service ids, the Twig functions, `@SonataBlock` and the template paths stay under contract.

**Amendment, 2026-09-06, third of the day (01 P14).** `form-extensions` and `twig-extensions` are
merged into the admin bundle on the same terms, and this contract gives up two more PHP namespaces
and two more translation domains: `Sonata\Form\*` and `Sonata\Twig\*` are `Sonata\AdminBundle\*`,
`SonataFormBundle` and `SonataTwigBundle` no longer exist as classes or as domains, and their eight
units are `SonataAdminBundle`'s (§11). Everything else those two trees contribute stays under
contract: the `sonata_form` and `sonata_twig` config roots, the `sonata.form.*` and `sonata.twig.*`
service ids, the three `sonata_flashmessages_*` Twig functions, the `sonata_status_class` filter,
the `sonata.status.renderer` tag, the `@SonataForm` and `@SonataTwig` Twig namespaces and the two
template paths. **One name is not preserved even though it survives:**
`Sonata\AdminBundle\Form\Type\CollectionType` is now form-extensions' collection type, and the
admin bundle's own is `NativeCollectionType` — the block prefixes `sonata_type_collection` and
`sonata_type_native_collection` are unchanged, so templates and Twig block overrides are not
affected, but a PHP import is. The rows below are annotated where they are affected rather than
rewritten.

## 1. Package identity

**Amendment, 2026-09-07 (01 P15).** The exporter is merged into the admin bundle on the same terms,
and this contract gives up a fourth PHP namespace: `Sonata\Exporter\*` is
`Sonata\AdminBundle\Exporter\*` and `SonataExporterBundle` no longer exists. Everything else the
exporter contributes stays under contract — the `sonata_exporter` config root, the
`sonata.exporter.*` service ids, the `sonata.exporter.writer` tag and the
`sonata.exporter.writer.<format>.<setting>` parameters — and there is nothing else to give up: the
exporter ships no templates, no Twig namespace and no translation domain. The admin bundle's own
`Sonata\AdminBundle\Exporter\DataSourceInterface` — `Sonata\AdminBundle\` from the start, and
what `sonata.admin.data_source.%s` resolves to — does not move. The rows below are annotated where
they are affected rather than rewritten.

One Composer package, `idct/adminata`, replaces three and merges four:

| Upstream package | Version replaced | PHP namespace(s) | Bundle class | Config root | Twig namespace |
|---|---|---|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | `Sonata\AdminBundle\` | `Sonata\AdminBundle\SonataAdminBundle` | `sonata_admin` | `@SonataAdmin` |
| `sonata-project/block-bundle` | 5.4.0, **merged, not replaced** (01 P10) | ~~`Sonata\BlockBundle\`~~ → `Sonata\AdminBundle\` | ~~`Sonata\BlockBundle\SonataBlockBundle`~~ → none; `SonataAdminBundle` registers `SonataBlockExtension` | `sonata_block` (kept) | `@SonataBlock` (kept, as an alias of admin-bundle's views) |
| `sonata-project/doctrine-extensions` | 2.6.0 | `Sonata\Doctrine\` (+ `Bridge\Symfony`) | `Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle` | `sonata_doctrine` | — |
| `sonata-project/doctrine-orm-admin-bundle` | 4.21.0 | `Sonata\DoctrineORMAdminBundle\` | `Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle` | `sonata_doctrine_orm_admin` | `@SonataDoctrineORMAdmin` |
| `sonata-project/exporter` | 3.4.0, **merged, not replaced** (01 P15) | ~~`Sonata\Exporter\` (+ `Bridge\Symfony`)~~ → `Sonata\AdminBundle\Exporter\` | ~~`Sonata\Exporter\Bridge\Symfony\SonataExporterBundle`~~ → none; `SonataAdminBundle` registers `SonataExporterExtension` | `sonata_exporter` | — |
| `sonata-project/form-extensions` | 2.7.0, **merged, not replaced** (01 P14) | ~~`Sonata\Form\`~~ → `Sonata\AdminBundle\` | ~~`Sonata\Form\Bridge\Symfony\SonataFormBundle`~~ → none; `SonataAdminBundle` registers `SonataFormExtension` | `sonata_form` (kept) | `@SonataForm` (kept, as an alias of admin-bundle's views) |
| `sonata-project/twig-extensions` | 2.6.0, **merged, not replaced** (01 P14) | ~~`Sonata\Twig\` (+ `Bridge\Symfony`)~~ → `Sonata\AdminBundle\` | ~~`Sonata\Twig\Bridge\Symfony\SonataTwigBundle`~~ → none; `SonataAdminBundle` registers `SonataTwigExtension` | `sonata_twig` (kept) | `@SonataTwig` (kept, as an alias of admin-bundle's views) |

Consequences kept: `templates/bundles/SonataAdminBundle/` (and the other bundles') override
directories, `public/bundles/sonataadmin/`, the `sonata.admin` tag attributes, the
`manager_type`-derived service ids (`sonata.admin.manager.%s`, `sonata.admin.data_source.%s`,
`sonata.admin.field_description_factory.%s`, `sonata.admin.builder.%s_{form,show,list,datagrid}`),
the 11 admin compiler passes in order, the `sonata_admin` asset package, and the routing resource
`@SonataAdminBundle/Resources/config/routing/sonata_admin.php`. `bundles.php` of an existing app
loses exactly four lines — `SonataBlockBundle` (01 P10), `SonataFormBundle` and `SonataTwigBundle`
(01 P14) and `SonataExporterBundle` (01 P15); every other line is unchanged.

**MongoDB fork contract.** Settled: `idct/sonata-admin-mongodb-bundle` **v6.0.0** (2026-09-07) is
built against `idct/adminata ^1.0@dev` and requires no `sonata-project` package at all. What that
release had to change is exactly what this contract predicted — its four `Sonata\Form\` imports
(`BooleanType` twice, `DateRangeType` and `DateTimeRangeType` in its filters) became
`Sonata\AdminBundle\Form\Type\`, as did the `CollectionType` in its test application; its one
`Sonata\Exporter\` import, `Source\DoctrineODMQuerySourceIterator` in `src/Exporter/DataSource.php`,
became `Sonata\AdminBundle\Exporter\Source\`, beside the
`Sonata\AdminBundle\Exporter\DataSourceInterface` that file already implements; and its test
kernel stopped registering `SonataBlockBundle`, `SonataFormBundle`, `SonataTwigBundle` and
`SonataDoctrineBundle`. Every one of those classes kept its short name, so they are namespace edits
and not behaviour changes. Its 5.x line stays on `sonata-project/admin-bundle ^4.39` and is not
installable beside adminata, which is what the `conflict` entries say.

The rest of the surface is unchanged and still resolves against adminata: it imports
`Sonata\AdminBundle\` in 62 files; its two form themes extend
`@SonataAdmin/Form/form_admin_fields.html.twig` and `@SonataAdmin/Form/filter_admin_fields.html.twig`
and include `@SonataAdmin/CRUD/Association/edit_{many_to_one,many_to_many,one_to_many}.html.twig`;
its `ListBuilder` hard-codes `@SonataAdmin/CRUD/list__action.html.twig` and
`list__action_%s.html.twig`. `.github/workflows/mongo-compat.yaml` installs its `6.x` against this
checkout nightly and runs both its suites as gates.

## 2. Service ids and parameters

Every service id, alias and parameter of the seven packages stays, including admin-bundle's 121
ids (103 `->set()`, 7 string aliases, 10 FQCN aliases, 1 parameter) and its deprecated-since-4.7
Twig extension aliases. Full admin list: `R/php-compat.md` §2.2.

## 3. Configuration trees

| Root / node(s) | Verdict |
|---|---|
| `sonata_admin`: `security.*`, `title`, `title_logo`, `search`, `global_search.*`, `default_controller`, `breadcrumbs.*`, `options.{html5_validate,sort_admins,confirm_exit,js_debug,use_stickyforms,pager_links,form_type,default_admin_route,default_group,default_translation_domain,dropdown_number_groups_per_colums,logo_content,list_action_button_content,lock_protection,mosaic_background,default_label_catalogue}`, `dashboard.*`, `default_admin_services.*`, `templates.*`, `assets.*`, `extensions.*`, `persist_filters`, `filter_persister`, `show_mosaic_button` | keep, same defaults |
| `sonata_admin.options.{skin,use_select2,use_icheck,use_bootlint}` | **removed** (unknown-node error); recomaty-panel deletes its `use_select2: false` line |
| `sonata_admin.options.default_icon` | keep; `fas fa-folder` still valid in FA7 |
| `sonata_admin.dashboard.blocks[].class` default | `col-md-4` → `md:col-span-4` |
| `sonata_admin.assets.stylesheets` default | `bundles/sonataadmin/app.css`, `bundles/sonataadmin/fontawesome.css` |
| `sonata_admin.assets.javascripts` default | `bundles/sonataadmin/app.js` (rendered with `defer`) |
| new `sonata_admin.theme` node | `mode` (`light\|dark\|system`, default `system`), `logo_dark`, `logo_icon` — a node under the admin root, not an eighth bundle |
| `sonata_block`, `sonata_doctrine`, `sonata_doctrine_orm_admin`, `sonata_exporter`, `sonata_form`, `sonata_twig` | unchanged trees and defaults (`sonata_form` keeps `form_type`; `sonata_doctrine_orm_admin.templates.types.*` keeps overriding `LIST_TEMPLATES`/`SHOW_TEMPLATES` entries). `sonata_block` stays a root of its own and is registered by `SonataAdminBundle`, so `config/packages/sonata_block.yaml` is unchanged (01 P10) |

## 4. Template registry keys and file paths

- 39 scalar keys (`user_block … button_show`) plus `form_theme[]`, `filter_theme[]`, defaults
  unchanged (`@SonataAdmin/...` paths).
- `TemplateRegistryInterface::SHOW_TEMPLATES` (21) and `LIST_TEMPLATES` (22): same keys, same paths.
- All **148 template files** of the seven packages keep their `@Sonata*/…` path: admin-bundle 131,
  block-bundle 12, ORM 3 (`Block/block_audit`, `Form/form_admin_fields`, `Form/filter_admin_fields`),
  form-extensions 1 (`Form/datepicker`), twig-extensions 1 (`FlashMessage/render`). PHP hard-codes
  91 `@SonataAdmin/…` paths; the ORM bundle and the MongoDB fork hard-code `list__action*`;
  block-bundle's `sonata_block.templates.block_base` default and its profiler template are
  referenced by admin-bundle and the profiler. The twelve block files moved on disk into
  `packages/admin-bundle/src/Resources/views/` (01 P10) — none of them collided with an admin-bundle
  name — and `SonataBlockExtension::prepend()` registers `@SonataBlock` as a Twig namespace over that
  directory, so every `@SonataBlock/…` path above still resolves. Every default *inside* adminata,
  though, says `@SonataAdmin/…` (01 P13, refined): the block services' `template` settings,
  `sonata_block.templates.block_base`/`block_container`, `sonata_block.profiler.template` and the
  exception renderers. The alias is a plain `twig.paths` entry with no `templates/bundles/`
  directory, so a default addressed through it is never overridden there; addressed as
  `@SonataAdmin/Block/…` it is, like every other admin template. **The one thing that does not
  carry over is `templates/bundles/SonataBlockBundle/`**: Symfony builds those override paths from
  registered bundles, and there is no `SonataBlockBundle` any more. Nothing in the repository, the
  demo or the app has such a directory, so nothing is broken today; an application that has one
  moves its files to `templates/bundles/SonataAdminBundle/Block/`, where the shipped defaults pick
  them up, or points `sonata_block.templates.*` at its own template.
- The app's `list__action_[ACTION].html.twig` naming convention for custom actions (11 custom
  overrides) and `get_admin_template('base_list_field', admin.code)` keep working.
- ~~`SonataFormBundle`~~ **`SonataFormExtension`** (01 P14) keeps prepending the datepicker theme to
  `twig.form_themes` (now a native-input theme), as `@SonataAdmin/Form/datepicker.html.twig` so that
  `templates/bundles/SonataAdminBundle/` can override it; `@SonataForm/Form/datepicker.html.twig`
  is the same file through the kept alias. `bundles/sonataform/*` public assets no longer exist.
- `outer_list_rows_tree` keeps its (dangling) default; nothing renders it.

## 5. Twig blocks

- Kept: all 138 admin-bundle block names (appendix A) in every rewritten template, minus
  `admin_lte_skin_class`, `bootlint` and `sonata_type_model_autocomplete_select2_options_js` — the
  first two belong to AdminLTE and to a Bootstrap linter, the third configured select2 (§C of
  document 03). `BlockNameTest` asserts the list. The layout keeps the 12 captured child blocks
  (`_preview, _form, _show, _list_table, _list_filters, _tab_menu, _content, _title, _breadcrumb,
  _actions, _navbar_title, _list_filters_actions`).
- Removed layout-scope variables: `_skin`, `_use_select2`, `_use_icheck`.
- Additive: `sonata_overlay`, `sonata_header_search`, `sonata_top_nav_menu_dark_mode`,
  `sonata_sidebar_section_header`, `list_after_table`, `sonata_script_attributes`,
  `sonata_type_date_range_widget`.
- Other packages: block-bundle's `block` block set and `sonata_block_render_event` hooks unchanged
  (the templates moved directory but not path — §4);
  form-extensions' `sonata_type_datetime_picker_widget(_html)` block names unchanged (content
  rewritten); twig-extensions' flash template keeps its `sonata_flashmessages_types()` loop and the
  `alert alert-{type}` marker classes (PHP-emitted type map); ORM's `sonata_admin_orm_*_widget`,
  `sonata_type_model_widget`, `sonata_type_admin_widget`, `sonata_type_collection_widget` unchanged.
- Blocks the app overrides today and therefore must keep their semantics: layout — `stylesheets`,
  `sonata_head_title`, `logo`, `sonata_top_nav_menu_add_block`, `javascripts`,
  `sonata_javascript_config`, `sonata_javascript_pool`, `sonata_wrapper`,
  `sonata_page_content_header`, `content`, `sonata_admin_content`, `notice`, `title`,
  `sonata_breadcrumb`, `sonata_nav`, `sonata_left_side`, `body_attributes`; list — `list_footer`,
  `actions`; edit — `form`, `sonata_pre_fieldsets`, `sonata_post_fieldsets`, `sonata_form_actions`;
  cells — `field` in `base_list_field`, `base_show_field`, `show_html`, `list_enum`,
  `list__action`, `list__select`, `Association/list_many_to_one`.
- `user_block`, `add_block` and `Button/*` includes remain `<li>` pass-through inside a `<ul>`;
  the `_actions|split('</a>')` heuristic is dropped (T7).
- `sonata_header` renders nothing when both `logo` and `sonata_nav` are empty.

## 6. Twig functions, filters, globals

Unchanged PHP across the packages: admin-bundle's `render_breadcrumbs`,
`render_breadcrumbs_for_title`, `canonicalize_locale_for_moment` (deprecated no-op),
`canonicalize_locale_for_select2`, `get_sonata_dashboard_groups_with_creatable_admins`,
`is_granted_affirmative`, `get_admin_template`, `get_global_template`, filters
`render_list_element`, `render_view_element`, `render_view_element_compare`,
`render_relation_element`, `sonata_urlsafeid`, `parse_icon`, `sonata_xeditable_type`,
`sonata_xeditable_choices`, global `sonata_config`, controller globals `admin`, `base_template`;
block-bundle's `sonata_block_render`, `sonata_block_render_event`, `sonata_block_exists`,
`sonata_block_include_stylesheets/javascripts` and the `sonata_block` global (the class behind it is
`Sonata\AdminBundle\Twig\BlockGlobalVariables` now — 01 P10 — but the Twig-side name is unchanged);
twig-extensions' `sonata_flashmessages_get`, `sonata_flashmessages_types`, `sonata_status_class`,
`sonata_template_deprecate`, `sonata_template_box`; form-extensions' form types and options.
Adminata's templates stop calling the two `canonicalize_*` functions.

## 7. Routes and JSON contracts (PHP untouched)

| Route | Contract |
|---|---|
| `sonata_admin_redirect`, `sonata_admin_dashboard`, `sonata_admin_search` | pages; `ajax_layout` when `X-Requested-With` or `_xml_http_request` |
| `sonata_admin_retrieve_form_element`, `sonata_admin_append_form_element` | kept in PHP; **nothing in adminata calls them** (AJAX form submission dropped, S5/J9) |
| `sonata_admin_short_object_information` | `{result: {id, label}}` (post-1.0 list selection) |
| `sonata_admin_set_object_field_value` | XHR-only POST; JSON-encoded `<td>` (post-1.0 inline edit) |
| `sonata_admin_retrieve_autocomplete_items` | `q, _per_page, _page, uniqid, _sonata_admin, field (+_context=filter)`; `{status, more, items:[{id,label}]}`; `403` below `minimum_input_length` |
| CRUD create/edit via XHR | contract kept in PHP, unused by adminata |
| CRUD batch | POST `action`, `idx[]`, `all_elements`, `_sonata_csrf_token`; confirmation re-posts `confirmation=ok`, `data`, `form_rest` |
| CRUD delete | POST `_method=DELETE`, `_sonata_csrf_token`; XHR returns `{"result":"ok"}` |
| CRUD list via XHR | `ajax_layout` renders `table.sonata-ba-list` without `<html>` (the app's accordion parses it) |

Form button names read server-side: `btn_update`, `btn_update_and_edit`, `btn_update_and_list`,
`btn_create`, `btn_create_and_edit`, `btn_create_and_list`, `btn_create_and_create`, `btn_delete`,
`btn_preview`, `btn_preview_approve`, `btn_preview_decline`, `btn_update_acl`. Query params `_tab`,
`filter[...]`, `filters=reset`, `filter[_sort_by]`, `filter[_sort_order]`, `filter[_page]`,
`filter[_per_page]`.

## 8. CSS hooks, ids and data attributes kept on the new markup

Sonata-owned names only. Style them through CSS selectors; never add Bootstrap tokens next to them.

- Shell: `main-header`, `main-sidebar`, `sidebar-menu`, `active`, `keep-open` (PHP-emitted),
  `sidebar-section-header` (app-injected), `content-wrapper`, `content-header`, `sonata-actions`,
  `dropdown-user`, `breadcrumb`, `noscript-warning`, `html.no-js`, `html.dark`.
- List: `sonata-ba-list`, `sonata-list-table`, `sonata-ba-list-field`, `sonata-ba-list-field-{type}`,
  `sonata-ba-list-field-header(-{type}|-order-asc|-order-desc|-select|-batch|-label-icon)`,
  `sonata-ba-list-field-order-active`, `sonata-ba-list-row-selected`, `sonata-link-identifier`,
  `objectId` attribute (**cell envelope byte-identical**), `#list_batch_checkbox`,
  `#{uniqid}_all_elements`, `sonata-readmore*`, `sonata-filter-form`, `sonata-toggle-filter`,
  `advanced-filter`, `#filter-list-{uniqid}`, `#filter-container-{uniqid}`, `#filter-{uniqid}-{name}`,
  `ul.pagination > li(.active) > a`, `select.per-page`, `sonata-action-element`,
  `{edit,delete}_link` and `view_link` (which the show *and* history row actions both carry) on row-action anchors, `header_class` values passed through.
- Forms: `sonata-ba-form`, `sonata-ba-field`, `sonata-ba-field-error`, `sonata-ba-field-{edit}-{inline}`,
  `#sonata-ba-field-container-{id}`, `sonata-ba-field-help`, `sonata-ba-field-error-messages`,
  `control-label__text`, `required`, `sonata-ba-form-actions`, `sonata-ba-collapsed-fields`,
  `sonata-collection-row/-add/-delete`, `data-prototype`, `data-prototype-name`, `{form}_{field}`
  input classes, `#{id}_autocomplete_input`, `#{id}_hidden_inputs_wrap`, `data-sonata-*`.
- Show/misc: `sonata-ba-view`, `sonata-ba-view-container`, `sonata-ba-delete` (`sonata-ba-view-title`
  belongs to the deferred `CRUD/preview.html.twig`, not to the show page),
  `inner-field-short-description`, `alert alert-{success,danger,warning,info}` on flash messages
  (flash type names are PHP-emitted by twig-extensions), `read-more-*`.

Dropped (Bootstrap/AdminLTE): `sonata-bc`, `skin-*`, `sonata-select2`, `sonata-icheck`, `treeview*`,
`navbar*`, `nav navbar-nav`, `box*`, `btn*`, `label label-*`, `form-group`, `has-error`,
`help-block`, `col-*`, `dropdown-menu`, `modal-*`, `x-editable`, `sidebar-collapse`, `input-group`
(datepicker).

## 9. JavaScript contract

- Global: `window.sonataApplication` (the Stimulus `Application` owning every `sonata-*` controller;
  apps may register into it). Nothing else; no jQuery in the page from adminata.
- Controllers (identifier / targets / values) for 1.0: `sonata-collection` (item; numItems),
  `sonata-confirm-exit` (snapshot, skip), `sonata-edit` (tab, tabStore), `sonata-filter`
  (form, group, advanced, submitter; defaultValues; outlet sonata-filter-list), `sonata-filter-list`
  (counter, field; class active; outlet sonata-filter), `sonata-per-page`, `sonata-readmore`
  (content, button; collapsedHeight, moreText, lessText), `sonata-revision` (preview),
  `sonata-sticky` (topNavbar, navbar, action); new controllers per document 05 §3.
- Events (native, bubbling): `sonata-admin-append-form-element`, `sonata-collection-item-added`,
  `sonata-collection-item-deleted`, `sonata-collection-item-deleted-successful`, `sonata.add_element`,
  plus `sonata-<controller>:<event>` for new controllers.
- `<meta name="sonata-config">` keys `CONFIRM_EXIT, USE_STICKYFORMS, DEBUG`; `<meta name="sonata-translations">`
  keys extended additively.
- Cookies `sonata_sidebar_hide` (kept) and `sonata_theme` (new); `localStorage` key `sonata_sidebar_open`.

## 10. Link text, titles and labels (test contracts)

Filter-toggle entries are `<a>` elements whose text is the translated filter label; "Filters" link
text; buttons "Create", "Create and return to list", "Update and close", "Yes, delete", "OK";
`.alert-success` after CRUD flows; `title="Add new"` on the association add button (inherited
templates).

## 11. Translations

Admin-bundle's `SonataAdminBundle` domain (126 ids, 34 locales), ~~block-bundle's `SonataBlockBundle`
domain (its catalogues moved into `packages/admin-bundle/src/Resources/translations/`, keeping the
domain name — 01 P10)~~ — **amended (01 P13):** the twenty block ids (`sonata.block.service.*`,
`form.label_*`) are units of the `SonataAdminBundle` catalogues and there is no `SonataBlockBundle`
domain, so an application's `translations/SonataBlockBundle.<locale>.xliff` moves into
`translations/SonataAdminBundle.<locale>.xliff`; ~~form-extensions' `SonataFormBundle`~~ —
**amended (01 P14):** the `SonataFormBundle` (27 catalogues) and `SonataTwigBundle` (7) domains go
the same way, their eight units (`link_add`, `label_type_yes`, `label_type_no`, `date_range_start`,
`date_range_end`, `message_close`, `more`, `less`) becoming `SonataAdminBundle`'s with their ids
unchanged — the ORM bundle's catalogues unchanged; new UI strings get new ids in the
`SonataAdminBundle` domain.
`sonata.admin.translation_extractor` keeps working.

## 12. Inherited tests that pin markup

| Test | Treatment |
|---|---|
| admin `tests/Twig/RenderElementRuntimeTest.php` (+ Extension twin), 452 expectations | `<td …objectId>` envelope frozen; badge and x-editable expectations re-baselined |
| admin `tests/Form/AdminLayoutTest.php`, `Widget/*` | Rewritten to the new markup; `sonata-ba-field-container-*`, `sonata-ba-field-help`, `required` still asserted |
| admin `tests/Twig/BreadcrumbsRuntimeTest.php` | `Breadcrumb/*.html.twig` kept byte-identical |
| admin `tests/Menu/Integration/*`, `TabMenuTest` | Expectations updated; `active` kept |
| admin `tests/Functional/Controller/*` (stub ModelManager) | Reused; hooks in §8 kept |
| admin `tests/DependencyInjection/*`, `FormMapperTest`, `ShowMapperTest`, `ConfigurationTest` | Updated for P6 (removed nodes, new defaults) |
| form-extensions picker type and widget tests | Updated for P6 (f) and the native template. Moved into `packages/admin-bundle/tests/Form/{Type,Widget}/` and `tests/Validator/` under `Sonata\AdminBundle\Tests\` (01 P14) |
| twig-extensions flash tests, block-bundle, exporter, doctrine-extensions, ORM tests | Reused; flash expectations re-baselined. The block suite moved into `packages/admin-bundle/tests/` under `Sonata\AdminBundle\Tests\` and runs in the `admin` testsuite (01 P10); the twig-extensions runtime, application and functional tests moved the same way (01 P14). The exporter suite moved into `packages/admin-bundle/tests/Exporter/` and `tests/DependencyInjection/` under `Sonata\AdminBundle\Tests\` and runs in the `admin` testsuite (01 P15). |
| `MDB/tests/Unit/*` (PR CI) and `MDB/tests/Functional/*` (Panther, nightly) | Unit green from phase 1; functional post-1.0 |

## 13. Enforcement

- `HookContractTest`: greps compiled templates for every hook, id and data attribute in §8.
- `ConfigContractTest`: `config:dump-reference` of the seven roots equals upstream except the lines in §3.
- `TemplatePathTest`: every registry path, every `@Sonata*/…` string in PHP of the seven packages and
  the MongoDB fork's hard-coded paths resolve.
- `ReplaceTest`: a scratch `composer.json` requiring `idct/adminata` + `idct/sonata-admin-mongodb-bundle`
  resolves (`composer update --dry-run`).
- JS contract snapshot (`assets/js/__contract__/controllers.json`): identifiers, targets, values,
  outlets, events introspected from the built `app.js`; `npm ls jquery` empty.
- CSS contract (`assets/css/contract.json`): every `.adm-*` and every styled hook selector exists in
  the built CSS; forbidden selectors (`.btn`, `.box`, `.label`, `.col-md-*`, `.container`,
  `.collapse{visibility`) absent; size budgets.
