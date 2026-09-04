# 02 — Compatibility contract ("drop-in" surface)

Everything listed here must exist in adminata 1.0 with the same name and semantics. The list is
derived from what persistence bundles, sibling bundles, Sonata's own tests, the MongoDB fork's
functional tests and the real app `APP/` actually touch (`R/php-compat.md` §2,
`R/packaging.md` §3.5, `R/gap-real-app-audit.md` §5). Each item becomes an executable test in
phase 1 (see document 08, "contract tests").

## 1. Package identity

| Item | Value kept | Why |
|---|---|---|
| Composer package | `idct/adminata` with `replace: sonata-project/admin-bundle: 4.43.0` | ORM bundle and MongoDB fork require `sonata-project/admin-bundle ^4.39` |
| Bundle class / name | `Sonata\AdminBundle\SonataAdminBundle` → `SonataAdminBundle` | Derives `@SonataAdmin`, `templates/bundles/SonataAdminBundle/`, `public/bundles/sonataadmin/` |
| PHP namespace | `Sonata\AdminBundle\` for all 242 files | Persistence bundles import 41 (ORM) / 34 (Mongo) classes |
| DI extension alias / XML ns | `sonata_admin`; `https://sonata-project.org/schema/dic/admin` | User `config/packages/sonata_admin.yaml` |
| `sonata.admin` tag attributes | `code, model_class, controller, manager_type, group, label, translation_domain, label_catalogue, icon, on_top, keep_open, show_in_dashboard, default, pager_type, label_translator_strategy, show_mosaic_button` + `default_admin_services` keys | Every admin definition |
| `manager_type`-derived ids | `sonata.admin.manager.%s`, `sonata.admin.data_source.%s`, `sonata.admin.field_description_factory.%s`, `sonata.admin.builder.%s_{form,show,list,datagrid}` | Persistence bundles register these |
| Compiler passes | 11 passes, same order/priorities | Mongo/ORM `AddTemplatesCompilerPass` hooks before `AddDependencyCallsCompilerPass` |
| Asset package | `sonata_admin` (`PathPackage` + `LastModifiedVersionStrategy`) | `asset(path, 'sonata_admin')` in layouts and user overrides |
| Routing resource | `@SonataAdminBundle/Resources/config/routing/sonata_admin.xml` (+ `.php`) | Installed apps import it |

## 2. Service ids and parameters

All 121 ids from `src/Resources/config/*.php` stay (103 `->set()`, 7 string aliases, 10 FQCN
aliases, 1 parameter). Deprecated-since-4.7 aliases (`sonata.admin.twig.extension`,
`sonata.canonicalize.twig.extension`, `sonata.render_element.twig.extension`,
`sonata.security.twig.extension`, `sonata.templates.twig.extension`,
`sonata.xeditable.twig.extension`, `sonata.admin.group.extension`) may be removed in 2.0 only.
Parameter `sonata.admin.twig.extension.x_editable_type_mapping` and every
`sonata.admin.configuration.*` parameter stay. Full list: `R/php-compat.md` §2.2.

## 3. Configuration tree verdicts

| Node(s) | Verdict |
|---|---|
| `security.*`, `title`, `title_logo`, `search`, `global_search.*`, `default_controller`, `breadcrumbs.*`, `options.{html5_validate,sort_admins,confirm_exit,js_debug,use_stickyforms,pager_links,form_type,default_admin_route,default_group,default_translation_domain,dropdown_number_groups_per_colums,logo_content,list_action_button_content,lock_protection,mosaic_background}`, `dashboard.*`, `default_admin_services.*`, `templates.*`, `assets.*`, `extensions.*`, `persist_filters`, `filter_persister`, `show_mosaic_button` | keep, same defaults |
| `options.skin` (enum of 12) | keep node and enum; semantics become brand-palette token files; class still emitted on `<body>` |
| `options.use_select2` | keep; `true` = Tom Select enhancement of plain selects |
| `options.use_icheck`, `options.use_bootlint` | keep, no-op, deprecation notice |
| `options.default_label_catalogue` | keep (already deprecated upstream) |
| `options.default_icon` (`fas fa-folder`), `dashboard.blocks[].class` (`col-md-4`) | keep node **and default** (D08) |
| `assets.stylesheets` default | `bundles/sonataadmin/app.css`, `bundles/sonataadmin/compat-bootstrap3.css`, `bundles/sonataadmin/fontawesome.css` (sonataform entry dropped) |
| `assets.javascripts` default | `bundles/sonataadmin/vendor/jquery.js`, `bundles/sonataadmin/app.js` (sonataform entry dropped) |
| new `adminata:` root | `theme.mode` (`light\|dark\|system`), `theme.logo_dark`, `theme.logo_icon`, `theme.density`, `flash_messages.template` (`adminata\|sonata_twig`) — additive |

Prepended config from siblings must keep working: `SonataIntlBundle` →
`templates.history_revision_timestamp`, `SonataUserBundle` → `templates.user_block`.

## 4. Template registry keys and file paths

- 39 scalar keys (`user_block … button_show`) plus `form_theme[]`, `filter_theme[]`, defaults
  unchanged (`@SonataAdmin/...` paths).
- `TemplateRegistryInterface::SHOW_TEMPLATES` (21) and `LIST_TEMPLATES` (22) constants: same
  keys, same paths — ORM/Mongo `ListBuilder`/`ShowBuilder` receive them through DI.
- All **131 template files** keep their path; contents change. PHP hard-codes 91 `@SonataAdmin/…`
  paths (`RenderElementRuntime`, `BreadcrumbsRuntime`, block services, `CRUDController`,
  `ModelAutocompleteType`, the `pager_results ↔ simple_pager_results` swap in
  `AddDependencyCallsCompilerPass`), and ORM/Mongo `ListBuilder`s hard-code
  `@SonataAdmin/CRUD/list__action.html.twig` and `list__action_%s.html.twig`.
- Persistence bundles' form themes `extends '@SonataAdmin/Form/form_admin_fields.html.twig'` and
  `include '@SonataAdmin/CRUD/Association/edit_{one_to_one,many_to_many,many_to_one,one_to_many}.html.twig'`
  through blocks `sonata_type_model_widget`, `sonata_type_admin_widget`,
  `sonata_type_collection_widget` and read `sonata_admin.field_description.mappingtype`.
- `outer_list_rows_tree` default keeps pointing at the non-existent
  `list_outer_rows_tree.html.twig` (upstream parity, D37).

## 5. Twig blocks and layout-scope variables

- All **138** block names (appendix A) survive with the same nesting. New blocks are additive:
  `sonata_overlay`, `sonata_header_search`, `sonata_top_nav_menu_dark_mode`,
  `sonata_top_nav_menu_notifications`, `sonata_sidebar_title`, `list_after_table`,
  `sonata_script_attributes`, `sonata_type_date_range_widget`.
- `standard_layout.html.twig` keeps the 12 captured child blocks
  (`_preview, _form, _show, _list_table, _list_filters, _tab_menu, _content, _title, _breadcrumb,
  _actions, _navbar_title, _list_filters_actions`) and the layout-scope variables `_skin`,
  `_use_select2`, `_use_icheck` (the real app's `admin_lte_skin_class` override reads `_skin`
  under `strict_variables`).
- `admin_lte_skin_class` keeps rendering onto `<body>` (used as a generic body-class hook).
- `user_block`/`add_block`/`Button/*` includes remain `<li>` pass-through inside a `<ul>`;
  the layout's "2+ buttons → Actions dropdown" heuristic (`_actions|split('</a>')`) stays.
- `sonata_header` renders nothing when both `logo` and `sonata_nav` are empty (login-page pattern).

## 6. Twig functions, filters, globals

Functions: `render_breadcrumbs`, `render_breadcrumbs_for_title`, `canonicalize_locale_for_moment`
(deprecated no-op, still called by real layouts), `canonicalize_locale_for_select2`,
`get_sonata_dashboard_groups_with_creatable_admins`, `is_granted_affirmative`,
`get_admin_template`, `get_global_template`. Filters: `render_list_element`,
`render_view_element`, `render_view_element_compare`, `render_relation_element`,
`sonata_urlsafeid`, `parse_icon`, `sonata_xeditable_type`, `sonata_xeditable_choices`.
Global: `sonata_config`; controller globals `admin`, `base_template`. New filters (additive):
`sonata_grid_class`, `sonata_box_class`.

## 7. Routes and JSON contracts (PHP untouched)

| Route | Contract |
|---|---|
| `sonata_admin_redirect`, `sonata_admin_dashboard`, `sonata_admin_search` | pages; `ajax_layout` when `X-Requested-With` or `_xml_http_request` |
| `sonata_admin_retrieve_form_element` | POST outer form + `_sonata_admin`, `elementId`, `subclass`, `objectId`, `uniqid`; returns the re-rendered field HTML |
| `sonata_admin_append_form_element` | POST outer form; appends a collection item (`_delete` keys stripped/restored); returns field HTML |
| `sonata_admin_short_object_information` | `{result: {id, label}}` with `OBJECT_ID` substitution |
| `sonata_admin_set_object_field_value` | XHR-only POST (`405` otherwise); `_sonata_admin, context=list, field, objectId, value`; `403/400/404` with JSON string errors; `200` JSON-encoded `<td>` HTML |
| `sonata_admin_retrieve_autocomplete_items` | `q, _per_page, _page, uniqid, _sonata_admin, field (+_context=filter)`; `{status, more, items:[{id,label}]}`; `403` below `minimum_input_length` |
| CRUD create/edit via XHR | `Accept: application/json` + `_xml_http_request` → `{result:'ok', objectId, objectName}`; `400` → `{title, violations[{propertyPath, message}]}`; `406` if Accept lacks JSON |
| CRUD batch | POST `action`, `idx[]`, `all_elements`, `_sonata_csrf_token`; confirmation re-posts `confirmation=ok`, `data` JSON, `form_rest` of the datagrid |
| CRUD delete | POST `_method=DELETE`, `_sonata_csrf_token`; XHR returns `{"result":"ok"}` |

Form button names read server-side: `btn_update`, `btn_update_and_edit`, `btn_update_and_list`,
`btn_create`, `btn_create_and_edit`, `btn_create_and_list`, `btn_create_and_create`,
`btn_delete`, `btn_preview`, `btn_preview_approve`, `btn_preview_decline`, `btn_update_acl`.
Form names `acl_users_form`, `acl_roles_form`; query params `_tab`, `filter[...]`, `filters=reset`.

## 8. CSS hooks, ids and data attributes

Keep verbatim on the new markup (style through CSS selectors, append Tailwind utilities after
the legacy tokens):

- Body/html: `sonata-bc`, `skin-*`, `sonata-select2`, `sonata-icheck`, `sidebar-collapse`,
  `html.no-js`, `login-page` (user login recipe).
- Shell: `main-header`, `main-sidebar`, `sidebar`, `sidebar-menu`, `dynamic-menu`, `treeview`,
  `treeview-menu`, `keep-open`, `active`, `content-wrapper`, `content-header`, `content`,
  `navbar navbar-default` (toolbar), `nav navbar-nav navbar-right sonata-actions`,
  `dropdown-user`, `dropdown-add`, `breadcrumb`, `noscript-warning`.
- List: `sonata-ba-list`, `sonata-list-table`, `sonata-ba-list-field`, `sonata-ba-list-field-{type}`,
  `sonata-ba-list-field-header(-{type}|-order-asc|-order-desc|-select|-batch|-label-icon)`,
  `sonata-ba-list-field-order-active`, `sonata-ba-list-row-selected`, `sonata-link-identifier`,
  `sonata-ba-list-field-batch`, `objectId` attribute, `#list_batch_checkbox`,
  `#{uniqid}_all_elements`, `mosaic-box*`, `label label-primary`, `x-editable` + `data-type/value/title/pk/url/source/format`,
  `sonata-readmore*`, `truncated`, `expanded`, `sonata-filter-form`, `sonata-toggle-filter`,
  `advanced-filter`, `#filter-list-{uniqid}`, `#filter-container-{uniqid}`, `#filter-{uniqid}-{name}`,
  `ul.pagination > li(.active) > a`, `select.per-page`, `sonata-search-result-{show,hide,fade}`,
  `js-treeview`, `data-treeview-*`, `is-toggled`, `is-active`.
- Forms: `sonata-ba-form`, `sonata-ba-field`, `sonata-ba-field-error`, `sonata-ba-field-{edit}-{inline}`,
  `#sonata-ba-field-container-{id}`, `form-group`, `has-error`, `help-block sonata-ba-field-help`,
  `sonata-ba-field-error-messages`, `control-label__text`, `required`, `sonata-ba-tabs`,
  `nav-tabs-custom`, `changer-tab`, `tab-pane`, `sonata-ba-form-actions form-actions`,
  `sonata-ba-collapsed-fields`, `field-container`, `field-actions`, `field-short-description`,
  `sonata-ba-action`, `#field_container_{id}`, `#field_widget_{id}`, `#field_actions_{id}`,
  `#field_dialog_{id}`, `modal-content/-title/-body`, `sonata-collection-row/-add/-delete`,
  `data-prototype`, `data-prototype-name`, `sonata-ba-tbody`, `sonata-ba-td-{id}-{field}`,
  `sonata-ba-sortable-handler`, `{form}_{field}` input classes, `data-sonata-select2*`,
  `data-sonata-icheck`, `data-placeholder`, `sonata-preview-form-container`.
- Show/misc: `sonata-ba-view`, `sonata-ba-view-container`, `sonata-ba-view-title`, `history-audit-compare`,
  `th.diff`, `#revisions`, `#revision-detail`, `current-revision`, `revision-link`,
  `revision-compare-link`, `sonata-action-element`, `sonata-ba-delete`, `sonata-ba-preview`,
  `inner-field-short-description`, `alert alert-{success,danger,warning,info}`, `read-more-*`.

## 9. JavaScript contract

- Globals: `window.$`, `window.jQuery` (from `vendor/jquery.js`), `window.stimulus`,
  `window.sonataApplication` (the application owning `sonata-*` controllers; form-extensions
  registers into it), `window.Admin`, new `window.adminata`.
- `window.Admin` members (exact 16): `shared_setup, get_config, get_translations,
  setup_list_modal, setup_select2, setup_icheck, setup_checkbox_range_selection,
  setup_xeditable, log, setup_inline_form_errors, switch_inline_form_errors, setup_tree_view,
  get_select2_width, setup_sortable_select2, setup_sticky_elements, setup_readmore_elements`;
  `subject` may be a jQuery object, selector string, Element or document.
- Global function shims emitted by association templates: `start_field_dialog_form_add_{id}`,
  `start_field_dialog_form_edit_{id}`, `start_field_dialog_form_list_{id}`,
  `remove_selected_element_{id}`, `start_field_retrieve_{id}` (deprecated, removed in 2.0).
- Kept controllers (identifier / targets / values): `sonata-collection` (item; numItems),
  `sonata-confirm-exit` (snapshot, skip), `sonata-edit` (tab, tabStore), `sonata-filter`
  (form, group, advanced, submitter; defaultValues; outlet sonata-filter-list),
  `sonata-filter-list` (counter, field; class active; outlet sonata-filter), `sonata-per-page`,
  `sonata-readmore` (content, button; collapsedHeight, moreText, lessText), `sonata-revision`
  (preview), `sonata-sticky` (topNavbar, navbar, action).
- Events (native, bubbling `CustomEvent`): `sonata-admin-append-form-element`,
  `sonata-admin-setup-list-modal`, `sonata-collection-item-added`, `sonata-collection-item-deleted`,
  `sonata-collection-item-deleted-successful`, `sonata.add_element`; iCheck's `ifChanged`/
  `ifChecked` become native `change`.
- `<meta name="sonata-config">` keys `SKIN, CONFIRM_EXIT, USE_SELECT2, USE_ICHECK,
  USE_STICKYFORMS, DEBUG`; `<meta name="sonata-translations">` keys (extended additively).
- Cookies `sonata_sidebar_hide` (kept) and new `sonata_theme`.

## 10. Link text, titles and labels (Panther/BrowserKit contracts)

Filter-toggle entries are `<a>` elements whose text is the translated filter label; "Filters"
link text; buttons "Create", "Create and return to list", "Update and close", "Yes, delete",
"OK"; `title="Add new"` on the association add button; `.modal-content button[name="btn_create"]`;
`.field-container .sonata-ba-action[title="Add new"]`; `.alert-success` after CRUD flows.

## 11. Translations

Domain `SonataAdminBundle` with all 126 ids in 34 XLIFF locales unchanged; new UI strings get
new ids in the same domain (dark-mode toggle, close, select-all, Tom Select messages, editable
save/cancel/empty, pager aria labels, skip link). `sonata.admin.translation_extractor` keeps
working.

## 12. Tests that pin markup and how they are treated

| Test | Treatment |
|---|---|
| `tests/Twig/RenderElementRuntimeTest.php` (+ Extension twin), 452 expectations | `<td …objectId>` envelope frozen; ~20 badge/x-editable expectations re-baselined |
| `tests/Form/AdminLayoutTest.php`, `Widget/*` | Rewritten to the new markup; semantic hooks (`sonata-ba-field-container-*`, `sonata-ba-field-help`, `required`) still asserted |
| `tests/Twig/BreadcrumbsRuntimeTest.php` | `Breadcrumb/*.html.twig` kept byte-identical; only the layout wrapper changes |
| `tests/Menu/Integration/*`, `TabMenuTest` | Expectations updated; `active`, `treeview`, `nav navbar-nav` kept |
| `tests/Functional/Controller/*` (stub ModelManager) | Reused; hooks kept incl. `help-block` as a dual class |
| `tests/DependencyInjection/*` | Updated only where a default changes (asset lists); each change is an UPGRADE item |
| `MDB/tests/Functional/*` (Panther) | Run nightly against adminata; selectors in §8/§10 |

## 13. Enforcement

- `ParityTest`: parses upstream and adminata templates, asserts block-name set equality (with an
  additions allowlist) for all 131 files.
- `HookContractTest`: greps compiled templates for every hook in §8 and every id/data attribute.
- JS contract snapshot (`assets/js/__contract__/controllers.json`): identifiers, targets, values,
  outlets, events, `window.Admin` members, introspected from the built `app.js`.
- CSS contract (`assets/css/contract.json`): every `.adm-*`, compat and styled hook selector must
  exist in the built CSS; dead-class lint over `class="…"` literals in templates.
- Config parity: `config:dump-reference sonata_admin` diff against 4.43.0 except documented lines.
