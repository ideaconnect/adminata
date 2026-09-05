# 03 — Template migration map

Rule for every rewritten template: **path, block names, ids, `sonata-*` hooks, `objectId`, data
attributes and Stimulus attributes are as in document 02**; every Bootstrap and AdminLTE class is
removed; `<td>`/`<th>` cells are styled through CSS selectors so their attribute strings stay
byte-identical. Element-level tables with file:line evidence live in `R/layout-nav.md`,
`R/list-datagrid.md`, `R/forms-edit.md`, `R/show-misc.md` and the TailAdmin recipes in
`R/tailadmin-catalog.md` §2. Controller names refer to document 05.

Scope (appendix C §4): of the 148 templates in the seven packages, 100 are rewritten in 1.0 (98
admin-bundle templates, 4,739 Twig lines, plus form-extensions' datepicker theme and
twig-extensions' flash template), 12 are copied unchanged (block-bundle and ORM templates without
Bootstrap markup) and 37 are deferred (§E, §G).

## A. Layouts, navigation, dashboard (phase 2)

| Template | Today | Target (TailAdmin source) | Controllers | Notes |
|---|---|---|---|---|
| `standard_layout.html.twig` | AdminLTE 2 shell (`body.sonata-bc.skin-*.fixed`, `header.main-header`, `aside.main-sidebar`, `div.content-wrapper`) | `T/index.html` shell, window-scroll model: fixed `<aside class="main-sidebar …">` (290 px / 90 px rail), content column `lg:ml-[290px]`, sticky header (`T/partials/header.html`), overlay; all 33 blocks and 12 captured blocks kept; `admin_lte_skin_class` and `bootlint` blocks gone; scripts `defer` | `sonata-layout` + `sonata-sticky` on `<body>`; `sonata-theme`; `sonata-dropdown` on add-block and user-block | `<meta name="sonata-config">`, `sonata_sidebar_hide` cookie seeding, `html.no-js`, `html.dark` from the `sonata_theme` cookie, `<html lang dir>`; `user-scalable=no` removed; the app appends a language dropdown to `sonata_top_nav_menu_add_block` and reads `admin.deprecationMessage` in `sonata_page_content_header` |
| `ajax_layout.html.twig` | navbar + `container`/`row` | flex layout; list-mode switcher only when `show_mosaic_button`; keeps `table.sonata-ba-list` and `.sonata-list-table` (the app's XHR accordion parses them) | as in `base_list` | no `<html>`; no wrapper scope class |
| `empty_layout.html.twig` | blanks header/sidebar with inline style | same overrides; inline style → body class | — | |
| `Menu/sonata_menu.html.twig` | KnpMenu renderer forcing `treeview`, `data-widget="tree"` | `T/partials/sidebar.html` groups (`menu-item`, `menu-item-active`, `menu-item-icon-*`, `menu-dropdown-item`); group header as `<button aria-expanded>`; per-group open map; `keep_open` forced open; `on_top` single items; items with class `sidebar-section-header` render as `menu-group-title` headers (app's `SidebarMenuSubscriber`); raw `<i>` icons from `parse_icon` pass-through | `sonata-menu`; `sonata-layout` `collapseOnly` targets | `.sidebar-menu … a` (functional `MenuTest`), `active`, `keep-open`; KnpMenu blocks `compressed_root, root, list, children, item, linkElement, spanElement, label`; role gate and translation chain verbatim |
| `Core/add_block.html.twig` | multi-column mega-dropdown | dropdown panel with `grid grid-cols-{column_count}`; group headers with icon | `sonata-dropdown` | `dropdown_number_groups_per_colums` maths, `role="menuitem"`, `role_admin` gate |
| `Core/user_block.html.twig` | empty; layout wraps in `ul.dropdown-user` | TailAdmin user dropdown (initials from `app.user.userIdentifier`) containing the same `<ul>` | `sonata-dropdown` | `<li>` pass-through; the app supplies its own `user_block` template |
| `Core/dashboard.html.twig` | `row` + `col-md-*` | `grid grid-cols-12 gap-4 md:gap-6`; block `class` used verbatim (default now `md:col-span-4`) | — | `is_granted_affirmative`, block positions unchanged; the app shadows the dashboard route with its own stats page |
| `Breadcrumb/breadcrumb.html.twig`, `breadcrumb_title.html.twig` | `<li><a>`, `<li class="active"><span>` | **unchanged files**; layout `<ol>` gets TailAdmin classes, chevrons via CSS `::after`, `aria-label`, `aria-current` | — | `BreadcrumbsRuntimeTest` pins the markup |
| `Block/block_admin_list.html.twig` | AdminLTE `box` per group + table + `btn-group` | TailAdmin card per group with a rows list; icon buttons | — | `extends sonata_block.templates.block_base`; `dashboard__action*.html.twig` includes |
| `CRUD/dashboard__action.html.twig`, `dashboard__action_create.html.twig` | `btn btn-link btn-flat`, Bootstrap dropdown for subclasses | link buttons; subclass dropdown | `sonata-dropdown` | `action_create` key; icons via `parse_icon` |
| `@SonataTwig/FlashMessage/render.html.twig` (twig-extensions, **rewritten in place**; included from `notice`) | Bootstrap alerts + CSS-checkbox collapse + `flashmessage.css` | `T/partials/alert/*` recipes + dismiss button; the read-more toggle stays CSS-only; `Resources/public/css/flashmessage.css` deleted, styles live in adminata's CSS | `sonata-dismiss` | `alert alert-{type}` kept (PHP-emitted type map), `read-more-*` kept; `sonata_flashmessages_types()` loop unchanged; the app's nine `notice` overrides become `{{ parent() }}` |
| Login / password pages | app-owned templates on the layout | no adminata template; `sonata_header` collapses when `logo` and `sonata_nav` are empty | — | T9 |

## B. List, filters, pager, batch (phase 3)

| Template | Today | Target | Controllers | Notes |
|---|---|---|---|---|
| `CRUD/base_list.html.twig` (390 lines) | title/actions, filter dropdown + collapsible panel (`form-inline`), `table.sonata-ba-list` in `box-body.table-responsive`, batch column, per-row `btn-group`, export dropdown, pager | Card with header; filter panel as a card whose rows are `grid grid-cols-12 gap-3 items-center`; table wrapper `max-w-full overflow-x-auto` (`T/partials/table/table-06.html`); footer (batch select + submit, export dropdown, per-page, pager) outside the overflow wrapper; new `list_after_table` block after the card and before `</form>` (the app's summaries) | `sonata-batch` on the form, `sonata-dropdown` on export, `sonata-filter`/`sonata-filter-list` unchanged | 22 blocks; `batch_javascript` block kept but empty; ids `filter-*`, `list_batch_checkbox`, `{uniqid}_all_elements`; `name="action"`, `idx[]`, `all_elements`, `_sonata_csrf_token`; `prepareSubmit` needs real `<select name="filter[...]">` |
| `CRUD/list.html.twig` | extends `base_list` | unchanged | — | the app overrides `actions` |
| `CRUD/base_list_field.html.twig` | `<td class="sonata-ba-list-field sonata-ba-list-field-{type}" objectId>` + readmore + x-editable span | **cell attributes byte-identical**; padding/typography via CSS; the x-editable span is emitted only when `editable` (post-1.0 controller) | `sonata-readmore` | 55 app cell templates extend it |
| `list_*.html.twig` (14 typed), `display_*.html.twig` (12) | `label label-success\|danger` booleans; plain markup otherwise | `adm-badge adm-badge-{success,error}` booleans; plain markup otherwise; `target="_blank" rel="noopener"` on URLs | — | ~20 render-element expectations re-baselined |
| `list__action.html.twig`, `list__action_{edit,show,delete,history}.html.twig` | `a.btn.btn-sm.btn-default.{edit,show,delete,history}_link` in `btn-group` | `inline-flex` icon-button group (`adm-btn-icon`), `*_link` hooks kept, `list_action_button_content` honoured, `sr-only` label | — | ORM/Mongo `ListBuilder`s hard-code these paths; the app overrides all five plus 11 custom actions |
| `list__batch.html.twig`, `list__select.html.twig` | checkbox + iCheck | native `appearance-none` checkbox (`TN` `Checkbox.tsx`) | `sonata-batch` (shift-range, select-all with indeterminate; upstream `indexedDB` typo fixed) | `sonata-ba-list-field-batch`, `sonata-ba-list-row-selected` |
| `list_outer_rows_list.html.twig`, `base_list_inner_row.html.twig`, `list_inner_row.html.twig` | `<tr>` templates | unchanged structure; hover/selected row classes | — | `inner_list_row` key; `row_class` |
| `Pager/base_links.html.twig` | `ul.pagination` | `TN/components/tables/Pagination.tsx` classes on the **same** `ul.pagination > li(.active) > a` structure; `aria-label`s | — | `pager_links` option |
| `Pager/base_results.html.twig`, `simple_pager_results.html.twig`, `links.html.twig`, `results.html.twig` | `select.per-page` | native select recipe (`w-auto`), URL option values unchanged | `sonata-per-page` | compiler-pass swap to `simple_pager_results` |
| `Form/filter_admin_fields.html.twig` (6 blocks) | `form_div_layout` + `sonata_type_filter_*` rows | operator selects native (styled chevron), value inputs TailAdmin recipes, native `type=date` for date filters; additive `sonata_type_date_range_widget` (side by side); ux-autocomplete `<select data-controller="symfony--ux-autocomplete--autocomplete">` rendered untouched | `sonata-autocomplete` via the included autocomplete template | ORM/Mongo filter themes extend this file |
| `CRUD/batch_confirmation.html.twig` | box page | centred card with danger accent, same POST fields | — | the app's `archive` batch action asks confirmation |
| `CRUD/Association/list_{many_to_many,many_to_one,one_to_many,one_to_one}.html.twig` | `sonata-link-identifier` links | links/badges | — | `relation_link`, `relation_value` blocks; the app overrides `list_many_to_one` |

## C. Forms and edit chrome (phase 4)

| Template | Today | Target | Controllers | Notes |
|---|---|---|---|---|
| `Form/form_admin_fields.html.twig` (695 lines, 30 blocks, extends `form_div_layout`) | Bootstrap row/label/widget/help/errors; `form-horizontal` `col-sm-3/9`; iCheck; select2 | Same block set; row keeps `id="sonata-ba-field-container-{id}"` + `sonata-ba-field …`; widgets get `adm-input/adm-select/adm-textarea/adm-checkbox/adm-radio` (`T/src/form-elements.html`, `TR/components/form/*`); horizontal mode = `grid grid-cols-12` with label `col-span-3`, field `col-span-9`; `row_class`/`widget_class`/`label_class`/`help_class`/`error_item_class` variables; native selects; `form-extensions` picker blocks → native date/time inputs; the widget must not touch elements carrying `data-controller` (ux-autocomplete, the app's own controllers) | `sonata-collection` (unchanged), `sonata-autocomplete` | see document 06 §1; `AdminLayoutTest` rewritten; `help_html` kept |
| `Form/Type/sonata_type_model_autocomplete.html.twig` (12 KB) | select2 ajax script with 4 overridable JS blocks | `<input role="combobox">` + listbox markup + the same hidden inputs; blocks `…_widget`, `…_ajax_request_parameters`, `…_dropdown_item_format`, `…_selection_format` kept; `…_select2_options_js` removed | `sonata-autocomplete` | request/response contract unchanged (document 06 §3) |
| `CRUD/base_edit.html.twig`, `edit.html.twig`, `base_edit_form.html.twig` (16 blocks), `base_edit_form_macro.html.twig` | `nav-tabs-custom`, `box box-primary` groups in `row` + `col-md-12`, `well.form-actions` sticky bar | groups as cards in a `grid grid-cols-12 gap-6` container using the group `class` verbatim (default `col-span-12`) and `box_class` as extra card classes; sticky bottom action bar (`sticky bottom-0 … backdrop-blur`, `.stuck` shadow); no tabs in 1.0 (a `tab` set renders groups sequentially with an `<h2>` until `sonata-tabs` exists) | `sonata-confirm-exit`, `sonata-edit`, `sonata-sticky` `action` target, `sonata-dismiss` | `btn_*` names, `_tab` param, `sonata-ba-form-actions`, `sonata-ba-collapsed-fields`, `form_rest` (lock protection `_lock_version`); the app overrides `form`, `sonata_pre_fieldsets`, `sonata_post_fieldsets`, `sonata_form_actions` |
| `CRUD/base_array_macro.html.twig`, `Helper/render_form_dismissable_errors.html.twig` | `alert alert-danger` + `data-dismiss` | error alert recipe (`T/partials/alert/alert-error.html`) | `sonata-dismiss` | |
| `CRUD/delete.html.twig` | `sonata-ba-delete` > `box box-danger` | centred `max-w-[600px]` card with the danger-alert body | — | `_method=DELETE`, `_sonata_csrf_token`, "Yes, delete", "or edit" link |
| `@SonataForm/Form/datepicker.html.twig` (form-extensions, **rewritten in place**) | Tempus Dominus `input-group date` + `fas fa-calendar` + inline options | native `<input type="date\|datetime-local\|time">` chosen from `datepicker_options.display.components`, `adm-input`, `step` for seconds, `min`/`max` from restrictions; `BasePickerType` (P6 f) fixes the wire format | — | blocks `sonata_type_datetime_picker_widget(_html)` kept; theme still prepended globally by `SonataFormBundle`; `bundles/sonataform/*` deleted (document 06 §4) |
| `@SonataDoctrineORMAdmin/Form/form_admin_fields.html.twig`, `filter_admin_fields.html.twig` (ORM bundle) | extend the admin themes; ORM-specific blocks delegate to admin blocks and association includes; no Bootstrap classes | **copied unchanged**; verified in phases 3–4 | — | the MongoDB fork's two themes have the same shape |

## D. Show, buttons, helpers (phase 4)

| Template | Today | Target | Controllers | Notes |
|---|---|---|---|---|
| `CRUD/base_show.html.twig`, `show.html.twig`, `base_show_field.html.twig` | `sonata-ba-view` > `row` > `col-md-12` > `box box-primary` > `table.table` rows `tr.sonata-ba-view-container > th/td` | cards in a 12-column grid using the group `class` verbatim; **row stays `<table><tr><th><td>`** styled as a TailAdmin table; readmore kept; no tabs in 1.0 | `sonata-readmore` | blocks `name, field, field_value, field_compare`; the app extends `base_show_field` and `show_html` |
| `show_*.html.twig` (13), `Association/show_*.html.twig` (4) | plain values, `label label-*` booleans | same structure; `adm-badge` booleans; currency/percent stay plain concatenation (tests pin `EUR 10.746135`) | — | SHOW_TEMPLATES paths via DI |
| `CRUD/action_buttons.html.twig`, `Button/*.html.twig` (6) | `<li><a class="sonata-action-element"><i class="fas fa-*">` in `ul.nav.navbar-nav.navbar-right` | same `<li><a>` structure in a `flex gap-2` `<ul class="sonata-actions">`; anchors styled by descendant selectors as `adm-btn`; no dropdown heuristic (T7) | — | the app overrides `create_button` and adds `crud/button_launch_test.html.twig` in the same slot |
| `Helper/short-object-description.html.twig` | `inner-field-short-description` | unchanged (deferred) | — | JSON contract of `GetShortObjectDescriptionAction` |

## E. Deferred templates (inherited as-is, ported when first needed)

| Group | Files | Trigger |
|---|---|---|
| Association edit flows | `CRUD/Association/edit_{many_to_many,many_to_one,one_to_many,one_to_one,modal,many_script,one_script,one_to_many_inline_table,one_to_many_inline_tabs,one_to_many_sortable_script_table,one_to_many_sortable_script_tabs}.html.twig` (11) and `Form/Type/sonata_type_model_list.html.twig`, the widget that opens them (lifted out of the form theme in P4-03 so that the theme itself carries no Bootstrap) | first `ModelListType`, `ModelType` or `AdminType` field; redesigned **without AJAX form submission** (S5, J9): list selection in a `<dialog>` loaded with `fetch` GET, create/edit as full pages, `edit_*_script` files emptied; controllers `sonata-association`, `sonata-modal` (exists), `sonata-tabs`, later `sonata-sortable`, `sonata-inline-row` (also unlocks the MongoDB fork's Panther suite once its scenarios follow the new flow) |
| History and compare | `CRUD/base_history.html.twig`, `history.html.twig`, `history_revision_timestamp.html.twig`, `base_show_compare.html.twig`, `show_compare.html.twig` | first admin with an audit reader |
| ACL | `CRUD/base_acl.html.twig`, `base_acl_macro.html.twig`, `acl.html.twig` | `security.handler: acl` |
| Other CRUD pages | `CRUD/preview.html.twig`, `select_subclass.html.twig`, `tree.html.twig`, `list_outer_rows_mosaic.html.twig`, `base_list_flat_field.html.twig`, `base_list_flat_inner_row.html.twig`, `action.html.twig` | preview mode, subclasses, tree/mosaic list modes, custom `CRUDController` actions rendering `action.html.twig` |
| Global search and tab menu | `Core/search.html.twig`, `Core/tab_menu_template.html.twig` | `search: true`; child admins or `configureTabMenu()` |
| Dashboard blocks | `Block/block_admin_preview.html.twig`, `block_rss_dashboard.html.twig`, `block_search_result.html.twig`, `block_stats.html.twig` | first use of the block type |
| Helper | `Helper/short-object-description.html.twig` | association flows |
| Other packages | ORM `Block/block_audit.html.twig` (`panel-group`, `data-toggle="collapse"`); block-bundle `Block/block_core_rss.html.twig` (`panel panel-default`, `media`), `Block/block_side_menu_template.html.twig` | first audit reader; first RSS or side-menu block |

A PHPUnit test lists these 37 paths and fails if one is rendered by the demo app without having
been ported (the file keeps a `{# adminata: not yet ported #}` marker).

## F. New or removed files

- New: `Core/list_mode_buttons.html.twig` (shared by `standard_layout` and `ajax_layout`).
- Removed admin-bundle public assets: `admin-lte-skins/`, `select2-locale/`, `moment-locale/`,
  eot/ttf/woff/svg font variants, Source Sans Pro, `vendor/`.
- Removed from the other packages: form-extensions `assets/` (Tempus Dominus controller, SCSS) and
  `src/Bridge/Symfony/Resources/public/` (`app.js`, `app.css`, manifests); twig-extensions
  `src/Bridge/Symfony/Resources/public/css/flashmessage.css`.
- Kept public assets: `images/ajax-loader.gif`, `images/default_mosaic_image.png`,
  `images/logo_title.png` (neutral adminata logo replaces the Sonata one).

## G. Templates of the six other packages (17 files)

| Package | Template | Bootstrap? | Disposition |
|---|---|---|---|
| twig-extensions | `FlashMessage/render.html.twig` | yes (alerts, `close`, `data-dismiss`) | rewritten in place (phase 2) |
| form-extensions | `Form/datepicker.html.twig` | yes (`input-group`, `form-control`) | rewritten in place, native inputs (phase 4) |
| ORM | `Form/form_admin_fields.html.twig`, `Form/filter_admin_fields.html.twig` | no | copied unchanged |
| ORM | `Block/block_audit.html.twig` | yes (`panel-group`, collapse) | deferred |
| block-bundle | `Block/block_base.html.twig` (extended by admin blocks), `block_container`, `block_template`, `block_core_text`, `block_core_menu`, `block_core_action`, `block_exception`, `block_exception_debug`, `block_no_page_available`, `Profiler/block.html.twig` | no | copied unchanged (10) |
| block-bundle | `Block/block_core_rss.html.twig`, `Block/block_side_menu_template.html.twig` | yes | deferred |

