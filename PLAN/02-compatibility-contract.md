# 02 — Stable interfaces (what 1.0 keeps identical)

Everything listed here exists in adminata 1.0 with the same name and semantics. The list is
derived from what `idct/sonata-admin-mongodb-bundle`, the app's admin classes and templates, the
packages' own tests and the inherited JavaScript actually touch (`R/php-compat.md` §2,
`R/packaging.md` §3.5, appendix C). It is a contract with the code adminata must run with, not a
compatibility layer for hypothetical users: anything not listed may change. Each item becomes an
executable test in phase 1 (document 08 §4).

## 1. Package identity

One Composer package, `idct/adminata`, replaces seven:

| Upstream package | Version replaced | PHP namespace(s) | Bundle class | Config root | Twig namespace |
|---|---|---|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | `Sonata\AdminBundle\` | `Sonata\AdminBundle\SonataAdminBundle` | `sonata_admin` | `@SonataAdmin` |
| `sonata-project/block-bundle` | 5.4.0 | `Sonata\BlockBundle\` | `Sonata\BlockBundle\SonataBlockBundle` | `sonata_block` | `@SonataBlock` |
| `sonata-project/doctrine-extensions` | 2.6.0 | `Sonata\Doctrine\` (+ `Bridge\Symfony`) | `Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle` | `sonata_doctrine` | — |
| `sonata-project/doctrine-orm-admin-bundle` | 4.21.0 | `Sonata\DoctrineORMAdminBundle\` | `Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle` | `sonata_doctrine_orm_admin` | `@SonataDoctrineORMAdmin` |
| `sonata-project/exporter` | 3.4.0 | `Sonata\Exporter\` (+ `Bridge\Symfony`) | `Sonata\Exporter\Bridge\Symfony\SonataExporterBundle` | `sonata_exporter` | — |
| `sonata-project/form-extensions` | 2.7.0 | `Sonata\Form\` | `Sonata\Form\Bridge\Symfony\SonataFormBundle` | `sonata_form` | `@SonataForm` |
| `sonata-project/twig-extensions` | 2.6.0 | `Sonata\Twig\` (+ `Bridge\Symfony`) | `Sonata\Twig\Bridge\Symfony\SonataTwigBundle` | `sonata_twig` | `@SonataTwig` |

Consequences kept: `templates/bundles/SonataAdminBundle/` (and the other bundles') override
directories, `public/bundles/sonataadmin/`, the `sonata.admin` tag attributes, the
`manager_type`-derived service ids (`sonata.admin.manager.%s`, `sonata.admin.data_source.%s`,
`sonata.admin.field_description_factory.%s`, `sonata.admin.builder.%s_{form,show,list,datagrid}`),
the 11 admin compiler passes in order, the `sonata_admin` asset package, and the routing resource
`@SonataAdminBundle/Resources/config/routing/sonata_admin.php`. `bundles.php` of an existing app
does not change.

**MongoDB fork contract.** `idct/sonata-admin-mongodb-bundle` v5.2.2 requires
`sonata-project/admin-bundle ^4.39`, `exporter ^3.0`, `form-extensions ^2.0` (and `block-bundle
^5.0` in dev); imports `Sonata\AdminBundle\` (62 files), `Sonata\Form\` (4), `Sonata\Exporter\` (1);
its two form themes extend `@SonataAdmin/Form/form_admin_fields.html.twig` and
`@SonataAdmin/Form/filter_admin_fields.html.twig` and include
`@SonataAdmin/CRUD/Association/edit_{many_to_one,many_to_many,one_to_many}.html.twig`; its
`ListBuilder` hard-codes `@SonataAdmin/CRUD/list__action.html.twig` and `list__action_%s.html.twig`.
All of these resolve against adminata.

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
| `sonata_block`, `sonata_doctrine`, `sonata_doctrine_orm_admin`, `sonata_exporter`, `sonata_form`, `sonata_twig` | unchanged trees and defaults (`sonata_form` keeps `form_type`; `sonata_doctrine_orm_admin.templates.types.*` keeps overriding `LIST_TEMPLATES`/`SHOW_TEMPLATES` entries) |

## 4. Template registry keys and file paths

- 39 scalar keys (`user_block … button_show`) plus `form_theme[]`, `filter_theme[]`, defaults
  unchanged (`@SonataAdmin/...` paths).
- `TemplateRegistryInterface::SHOW_TEMPLATES` (21) and `LIST_TEMPLATES` (22): same keys, same paths.
- All **148 template files** of the seven packages keep their path: admin-bundle 131, block-bundle
  12, ORM 3 (`Block/block_audit`, `Form/form_admin_fields`, `Form/filter_admin_fields`),
  form-extensions 1 (`Form/datepicker`), twig-extensions 1 (`FlashMessage/render`). PHP hard-codes
  91 `@SonataAdmin/…` paths; the ORM bundle and the MongoDB fork hard-code `list__action*`;
  block-bundle's `sonata_block.templates.block_base` default and `@SonataBlock/Profiler/block.html.twig`
  are referenced by admin-bundle and the profiler.
- The app's `list__action_[ACTION].html.twig` naming convention for custom actions (11 custom
  overrides) and `get_admin_template('base_list_field', admin.code)` keep working.
- `SonataFormBundle` keeps prepending `@SonataForm/Form/datepicker.html.twig` to `twig.form_themes`
  (now a native-input theme); `bundles/sonataform/*` public assets no longer exist.
- `outer_list_rows_tree` keeps its (dangling) default; nothing renders it.

## 5. Twig blocks

- Kept: all 138 admin-bundle block names (appendix A) in every rewritten template, minus
  `admin_lte_skin_class` and `bootlint`. The layout keeps the 12 captured child blocks
  (`_preview, _form, _show, _list_table, _list_filters, _tab_menu, _content, _title, _breadcrumb,
  _actions, _navbar_title, _list_filters_actions`).
- Removed layout-scope variables: `_skin`, `_use_select2`, `_use_icheck`.
- Additive: `sonata_overlay`, `sonata_header_search`, `sonata_top_nav_menu_dark_mode`,
  `sonata_sidebar_section_header`, `list_after_table`, `sonata_script_attributes`,
  `sonata_type_date_range_widget`.
- Other packages: block-bundle's `block` block set and `sonata_block_render_event` hooks unchanged;
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
block-bundle's `sonata_block_render`, `sonata_block_render_event`, `sonata_block_include_stylesheets/javascripts`;
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
  `{show,edit,delete,history}_link` on row-action anchors, `header_class` values passed through.
- Forms: `sonata-ba-form`, `sonata-ba-field`, `sonata-ba-field-error`, `sonata-ba-field-{edit}-{inline}`,
  `#sonata-ba-field-container-{id}`, `sonata-ba-field-help`, `sonata-ba-field-error-messages`,
  `control-label__text`, `required`, `sonata-ba-form-actions`, `sonata-ba-collapsed-fields`,
  `sonata-collection-row/-add/-delete`, `data-prototype`, `data-prototype-name`, `{form}_{field}`
  input classes, `#{id}_autocomplete_input`, `#{id}_hidden_inputs_wrap`, `data-sonata-*`.
- Show/misc: `sonata-ba-view`, `sonata-ba-view-container`, `sonata-ba-view-title`, `sonata-ba-delete`,
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

Admin-bundle's `SonataAdminBundle` domain (126 ids, 34 locales), block-bundle's `SonataBlockBundle`,
form-extensions' `SonataFormBundle` and the ORM bundle's catalogues unchanged; new UI strings get new
ids in the `SonataAdminBundle` domain. `sonata.admin.translation_extractor` keeps working.

## 12. Inherited tests that pin markup

| Test | Treatment |
|---|---|
| admin `tests/Twig/RenderElementRuntimeTest.php` (+ Extension twin), 452 expectations | `<td …objectId>` envelope frozen; badge and x-editable expectations re-baselined |
| admin `tests/Form/AdminLayoutTest.php`, `Widget/*` | Rewritten to the new markup; `sonata-ba-field-container-*`, `sonata-ba-field-help`, `required` still asserted |
| admin `tests/Twig/BreadcrumbsRuntimeTest.php` | `Breadcrumb/*.html.twig` kept byte-identical |
| admin `tests/Menu/Integration/*`, `TabMenuTest` | Expectations updated; `active` kept |
| admin `tests/Functional/Controller/*` (stub ModelManager) | Reused; hooks in §8 kept |
| admin `tests/DependencyInjection/*`, `FormMapperTest`, `ShowMapperTest`, `ConfigurationTest` | Updated for P6 (removed nodes, new defaults) |
| form-extensions picker type and widget tests | Updated for P6 (f) and the native template |
| twig-extensions flash tests, block-bundle, exporter, doctrine-extensions, ORM tests | Reused; flash expectations re-baselined |
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
