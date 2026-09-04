# 02 — Stable interfaces (what 1.0 keeps identical)

Everything listed here exists in adminata 1.0 with the same name and semantics. The list is
derived from what the persistence bundles, the app's admin classes and templates, Sonata's own
tests and the inherited JavaScript actually touch (`R/php-compat.md` §2, `R/packaging.md` §3.5,
appendix C). It is a contract with the code adminata must run with, not a compatibility layer for
hypothetical users: anything not listed may change. Each item becomes an executable test in
phase 1 (document 08 §4).

## 1. Package identity

| Item | Value kept | Why |
|---|---|---|
| Composer package | `idct/adminata` with `replace: sonata-project/admin-bundle: 4.43.0` | ORM bundle 4.21.0 requires `^4.39.0`; MongoDB fork v5.2.2 requires `^4.39` |
| Bundle class / name | `Sonata\AdminBundle\SonataAdminBundle` → `SonataAdminBundle` | Derives `@SonataAdmin`, `templates/bundles/SonataAdminBundle/`, `public/bundles/sonataadmin/` |
| PHP namespace | `Sonata\AdminBundle\` for all 242 files | Persistence bundles import 41 (ORM) / 34 (Mongo) classes |
| DI extension alias / XML ns | `sonata_admin`; `https://sonata-project.org/schema/dic/admin` | User `config/packages/sonata_admin.yaml` |
| `sonata.admin` tag attributes | `code, model_class, controller, manager_type, group, label, translation_domain, label_catalogue, icon, on_top, keep_open, show_in_dashboard, default, pager_type, label_translator_strategy, show_mosaic_button` + `default_admin_services` keys | Every admin definition (`APP/config/services/admin/*.yaml`) |
| `manager_type`-derived ids | `sonata.admin.manager.%s`, `sonata.admin.data_source.%s`, `sonata.admin.field_description_factory.%s`, `sonata.admin.builder.%s_{form,show,list,datagrid}` | Persistence bundles register these |
| Compiler passes | 11 passes, same order/priorities | Mongo/ORM `AddTemplatesCompilerPass` hooks before `AddDependencyCallsCompilerPass` |
| Asset package | `sonata_admin` (`PathPackage` + `LastModifiedVersionStrategy`) | `asset(path, 'sonata_admin')` in layouts |
| Routing resource | `@SonataAdminBundle/Resources/config/routing/sonata_admin.php` (+ `.xml`) | `APP/config/routes/sonata_admin.yaml` |

## 2. Service ids and parameters

All 121 ids from `src/Resources/config/*.php` stay (103 `->set()`, 7 string aliases, 10 FQCN
aliases, 1 parameter), including the deprecated-since-4.7 Twig extension aliases. Full list:
`R/php-compat.md` §2.2.

## 3. Configuration tree

| Node(s) | Verdict |
|---|---|
| `security.*`, `title`, `title_logo`, `search`, `global_search.*`, `default_controller`, `breadcrumbs.*`, `options.{html5_validate,sort_admins,confirm_exit,js_debug,use_stickyforms,pager_links,form_type,default_admin_route,default_group,default_translation_domain,dropdown_number_groups_per_colums,logo_content,list_action_button_content,lock_protection,mosaic_background,default_label_catalogue}`, `dashboard.*`, `default_admin_services.*`, `templates.*`, `assets.*`, `extensions.*`, `persist_filters`, `filter_persister`, `show_mosaic_button` | keep, same defaults |
| `options.skin`, `options.use_select2`, `options.use_icheck`, `options.use_bootlint` | **removed** (unknown-node error); recomaty-panel deletes its `use_select2: false` line |
| `options.default_icon` | keep node; default `fas fa-folder` still valid in FA7 |
| `dashboard.blocks[].class` default | `col-md-4` → `md:col-span-4` |
| `assets.stylesheets` default | `bundles/sonataadmin/app.css`, `bundles/sonataadmin/fontawesome.css` |
| `assets.javascripts` default | `bundles/sonataadmin/app.js` (rendered with `defer`) |
| new `adminata:` root | `theme.mode` (`light\|dark\|system`, default `system`), `theme.logo_dark`, `theme.logo_icon` |

`sonata_doctrine_orm_admin.templates.types.list.*` and the MongoDB fork's equivalent keep
overriding `TemplateRegistryInterface::LIST_TEMPLATES` entries (the app maps `datetime` to its own
cell template).

## 4. Template registry keys and file paths

- 39 scalar keys (`user_block … button_show`) plus `form_theme[]`, `filter_theme[]`, defaults
  unchanged (`@SonataAdmin/...` paths).
- `TemplateRegistryInterface::SHOW_TEMPLATES` (21) and `LIST_TEMPLATES` (22): same keys, same paths.
- All **131 template files** keep their path. PHP hard-codes 91 `@SonataAdmin/…` paths
  (`RenderElementRuntime`, `BreadcrumbsRuntime`, block services, `CRUDController`,
  `ModelAutocompleteType`, the `pager_results ↔ simple_pager_results` swap), and the ORM/Mongo
  `ListBuilder`s hard-code `@SonataAdmin/CRUD/list__action.html.twig` and `list__action_%s.html.twig`.
- The app's `list__action_[ACTION].html.twig` naming convention for custom actions (11 custom overrides)
  and `get_admin_template('base_list_field', admin.code)` keep working.
- Persistence bundles' form themes `extends '@SonataAdmin/Form/form_admin_fields.html.twig'` and
  include `@SonataAdmin/CRUD/Association/edit_*.html.twig`; those association files exist (inherited,
  not rewritten in 1.0) and `sonata_admin.field_description.mappingtype` is still exposed.
- `outer_list_rows_tree` keeps its (dangling) default; nothing renders it.

## 5. Twig blocks

- Kept: all 138 upstream block names (appendix A) in every rewritten template, minus
  `admin_lte_skin_class` and `bootlint` (AdminLTE-specific). The layout keeps the 12 captured child
  blocks (`_preview, _form, _show, _list_table, _list_filters, _tab_menu, _content, _title,
  _breadcrumb, _actions, _navbar_title, _list_filters_actions`).
- Removed layout-scope variables: `_skin`, `_use_select2`, `_use_icheck` (the app deletes the one
  override that reads `_skin`, appendix C §3).
- Additive: `sonata_overlay`, `sonata_header_search`, `sonata_top_nav_menu_dark_mode`,
  `sonata_sidebar_section_header`, `list_after_table`, `sonata_script_attributes`,
  `sonata_type_date_range_widget`.
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
- `sonata_header` renders nothing when both `logo` and `sonata_nav` are empty (the app's login and
  password-reset pages empty both).

## 6. Twig functions, filters, globals

Unchanged PHP: `render_breadcrumbs`, `render_breadcrumbs_for_title`,
`canonicalize_locale_for_moment` (deprecated no-op), `canonicalize_locale_for_select2`,
`get_sonata_dashboard_groups_with_creatable_admins`, `is_granted_affirmative`,
`get_admin_template`, `get_global_template`; filters `render_list_element`, `render_view_element`,
`render_view_element_compare`, `render_relation_element`, `sonata_urlsafeid`, `parse_icon`,
`sonata_xeditable_type`, `sonata_xeditable_choices`; global `sonata_config`; controller globals
`admin`, `base_template`. Adminata's templates stop calling the two `canonicalize_*` functions.

## 7. Routes and JSON contracts (PHP untouched)

| Route | Contract |
|---|---|
| `sonata_admin_redirect`, `sonata_admin_dashboard`, `sonata_admin_search` | pages; `ajax_layout` when `X-Requested-With` or `_xml_http_request` |
| `sonata_admin_retrieve_form_element`, `sonata_admin_append_form_element` | POST outer form; return re-rendered field HTML (used by post-1.0 association flows) |
| `sonata_admin_short_object_information` | `{result: {id, label}}` |
| `sonata_admin_set_object_field_value` | XHR-only POST; JSON-encoded `<td>` (post-1.0 inline edit) |
| `sonata_admin_retrieve_autocomplete_items` | `q, _per_page, _page, uniqid, _sonata_admin, field (+_context=filter)`; `{status, more, items:[{id,label}]}`; `403` below `minimum_input_length` |
| CRUD create/edit via XHR | `Accept: application/json` + `_xml_http_request` → `{result:'ok', objectId, objectName}`; `400` → `{title, violations[]}` |
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
  (flash type names are PHP-emitted), `read-more-*`.

Dropped (Bootstrap/AdminLTE): `sonata-bc`, `skin-*`, `sonata-select2`, `sonata-icheck`, `treeview*`,
`navbar*`, `nav navbar-nav`, `box*`, `btn*`, `label label-*`, `form-group`, `has-error`,
`help-block`, `col-*`, `dropdown-menu`, `modal-*`, `x-editable`, `sidebar-collapse`.

## 9. JavaScript contract

- Global: `window.sonataApplication` (the Stimulus `Application` owning every `sonata-*` controller;
  apps may register into it). Nothing else.
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

Domain `SonataAdminBundle` with all 126 ids in 34 XLIFF locales unchanged; new UI strings get new
ids in the same domain (dark-mode toggle, close, select-all, combobox messages, pager labels, skip
link). `sonata.admin.translation_extractor` keeps working.

## 12. Inherited tests that pin markup

| Test | Treatment |
|---|---|
| `tests/Twig/RenderElementRuntimeTest.php` (+ Extension twin), 452 expectations | `<td …objectId>` envelope frozen; badge and x-editable expectations re-baselined |
| `tests/Form/AdminLayoutTest.php`, `Widget/*` | Rewritten to the new markup; `sonata-ba-field-container-*`, `sonata-ba-field-help`, `required` still asserted |
| `tests/Twig/BreadcrumbsRuntimeTest.php` | `Breadcrumb/*.html.twig` kept byte-identical |
| `tests/Menu/Integration/*`, `TabMenuTest` | Expectations updated; `active` kept |
| `tests/Functional/Controller/*` (stub ModelManager) | Reused; hooks in §8 kept |
| `tests/DependencyInjection/*`, `FormMapperTest`, `ShowMapperTest`, `ConfigurationTest` | Updated for P6 (removed nodes, new defaults) |

## 13. Enforcement

- `HookContractTest`: greps compiled templates for every hook, id and data attribute in §8.
- `ConfigContractTest`: `config:dump-reference sonata_admin` equals 4.43.0 except the lines in §3.
- `TemplatePathTest`: every registry path and every `@SonataAdmin/…` string in PHP resolves.
- JS contract snapshot (`assets/js/__contract__/controllers.json`): identifiers, targets, values,
  outlets, events introspected from the built `app.js`.
- CSS contract (`assets/css/contract.json`): every `.adm-*` and every styled hook selector exists in
  the built CSS; forbidden selectors (`.btn`, `.box`, `.label`, `.col-md-*`, `.container`,
  `.collapse{visibility`) absent; size budgets.
