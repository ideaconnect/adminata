# Appendix C — recomaty-panel usage and the resulting 1.0 scope

Audited on 2026-09-04 against `/home/bartosz/dev/r3/recomaty-panel-clean` (branch head
`cb6baf01`). The older `recomaty-panel` checkout audited in `R/gap-real-app-audit.md` differs
(different branch; e.g. no `RecomatManagerAdmin`, new DRS and PocketRVM admins), so this appendix
is the reference for scope.

## 1. Stack

| Item | Value |
|---|---|
| PHP / Symfony | `>=8.5` / 8.1 (`symfony/framework-bundle` v8.1.4 in the lock) |
| Sonata | `admin-bundle` 4.43.0, `doctrine-orm-admin-bundle` 4.21.0, `idct/sonata-admin-mongodb-bundle` v5.2.2 (one admin: `DebugRequestAdmin`, `manager_type: doctrine_mongodb`), `form-extensions` 2.7.0, `block-bundle` 5.4.0, `twig-extensions` 2.6.0, `exporter` 3.4.0 |
| UX | `symfony/stimulus-bundle` 3.4.0, `symfony/ux-autocomplete` 2.36.2 (Tom Select via `@symfony/ux-autocomplete`), `symfony/ux-leaflet-map`, `@symfony/stimulus-bridge` 4, second Stimulus application in `assets/bootstrap.js` with seven controllers |
| Build | Webpack Encore 6, Sass, Babel; `.addExternals({ jquery: 'jQuery' })`; `enableVersioning(false)` |
| Other | KnpMenu, KnpPaginator, Oneup uploader + Dropzone 6, `vanilla-jsoneditor`, Chart.js, Leaflet, flag-icons |
| Config | `search: false`, `show_mosaic_button: false`, `security.handler: role`, `options.use_select2: false`, `use_stickyforms: true`, `lock_protection: true`, `templates.layout/user_block/list` overrides, dashboard = one `admin_list` block, 19 groups with raw `<i class="fas fa-…">` icons, `sonata_doctrine_orm_admin.templates.types.list.datetime` override, global `twig.form_themes: ['@SonataForm/Form/datepicker.html.twig']`, `csrf` stateless token ids `submit`, `authenticate`, `logout` |
| Tests | Behat (`tests/bdd/Panel`, Mink over BrowserKit; six panel features), PHPUnit unit/integration, docker compose for services |

## 2. Sonata features used (in the 1.0 scope)

| Feature | Evidence (counts over `src/`, `templates/`, `config/`) |
|---|---|
| Admin classes | about 45 (`src/Admin/**`), all extending `BaseAdmin`; no child admins (`addChild` 0), no `configureTabMenu`, no `->tab()`, no subclasses, no ACL, no preview |
| List | `configureListFields` ×44; field types `TYPE_STRING` 106, `TYPE_INTEGER` 75, `TYPE_DATETIME` 41, `TYPE_ENUM` 31, `TYPE_MANY_TO_ONE` 28, `TYPE_BOOLEAN` 24, `TYPE_IDENTIFIER` 8, `TYPE_TEXTAREA` 7, `TYPE_ARRAY` 6, `TYPE_MANY_TO_MANY` 3, `TYPE_HTML` 3; `'template' =>` ×23 files (55 cell templates); `header_class`/`row_align` ×5; sortable columns with `sort_field_mapping`; `_action` with standard (`edit`, `show`, `delete`) and 11 custom actions; `configureDefaultSortValues` ×5; `configureQuery` ×26 |
| Filters | `configureDatagridFilters` ×42: `StringFilter` 48, `NumberFilter` 28, `ChoiceFilter` 25, `CallbackFilter` 9, `ModelFilter` 6, `DateRangeFilter` 6, `BooleanFilter` 4, `DateTimeRangeFilter` 2, `ModelAutocompleteFilter` 1, `DateTimeFilter` 1; ux-autocomplete field types as filter `field_type` ×3; Sonata `ModelAutocompleteType` as filter `field_type` ×1 (`RecomatAdmin` city) |
| Batch | `configureBatchActions` ×2 (`RecomatAdmin` custom `archive` with `ask_confirmation`; default delete elsewhere); `->remove('batch')` ×1 |
| Export | `configureExportFields` ×4 (PocketRVM admins, `AdminUserAdmin`); `->remove('export')` ×1 |
| Show | `configureShowFields` ×9; four show cell templates extend `base_show_field`, one extends `show_html` |
| Forms | `TextType` 55, `EntityType` 32 (native selects), `EnumType` 29, `CollectionType` 14 (`allow_add`/`allow_delete`, entry types incl. `EntityType`, `EmailType` and eight sub-forms), form-extensions `BooleanType` 14, `ChoiceType` 13, `IntegerType` 11, `TextareaType` 10, `CheckboxType` 10, `NumberType` 4, `DateRangePickerType` 4, `UrlType` 3, `EmailType` 3, `DateTimePickerType` 3, `DateTimeRangePickerType` 2, `DatePickerType` 2, `PasswordType` 1, `ModelAutocompleteType` 1; app types (`FileAssetType`, `ChunkedFileAssetType`, `JsonEditorType`, `LocationMapType`, `HashedPasswordType`, `OperatorSupport*Type`, `DRSFractionType`, `PlaylistEntryType`) with three per-admin form themes via `setFormTheme`; ux-autocomplete fields inside admin forms ×2 (`RecomatGroupAdmin`, `ManagementPoolAdmin`); `->with()` groups ×10 files with 31 `col-md-*` classes (`col-md-6` 15, `col-md-12` 8, `col-md-9` 3, `col-md-3` 2, `col-md-4`, `col-md-8`, one `… col-md-12 hidden`); `'description' =>` ×3; `help_html`; `attr.data-controller` pass-through to the app's controllers |
| Date/time usages (12) | `PartnerPromoAdmin` (`DatePickerType` `dd-MM-yyyy HH:mm`, `DateRangePickerType` ×2 with `display.components` calendar+clock), `PromoCodeAdmin` (`DateRangePickerType`), `TransactionAdmin` (`DateRangePickerType` `dd.MM.yyyy`), `RecomatAdmin` (time-only `DateTimePickerType`, `calendar: false`), `RvmStateAdmin`/`RvmFaultStateAdmin` (`DateTimeRangePickerType`), `AcmContentJobAdmin`, plain forms `RecomatDateHoursForm`, `RecomatWeekdayHoursForm` (collection entries), `CreateManualTransactionType` |
| Custom action buttons | `configureActionButtons` ×1 (`TestRunAdmin` adds `crud/button_launch_test.html.twig`); `Button/create_button.html.twig` overridden (icon-only) |
| Routes | `configureRoutes` ×4, `->remove('edit'\|'delete'\|'create')` ×6; custom routes handled by app controllers |
| Menu | `SidebarMenuSubscriber` injects non-link items with class `sidebar-section-header` before given groups; `DashboardMenuListener` scopes the dashboard group |
| XHR list | `TransactionItemsAccordion.js` fetches a child list with `X-Requested-With` and parses `table.sonata-ba-list` from the `ajax_layout` response |
| Flash | `sonata_flash_success`/`sonata_flash_error` from `BaseAdmin`, `success`/`error` from controllers |
| Lock protection, sticky forms | `lock_protection: true` (needs `form_rest`), `use_stickyforms: true` (`sonata-sticky`) |
| Login and account pages | app controllers render `security/login_form`, `password_reset_*`, `password_change` on the layout override with `sonata_nav`/`logo`/`sonata_left_side` emptied and `body_attributes` overridden |

## 3. Templates the app owns (must keep working after their port)

| Set | Files | Blocks / parents they depend on |
|---|---|---|
| Layout override | `templates/layout/standard_layout_override.html.twig` (168 lines) extends `@!SonataAdmin/standard_layout.html.twig` | `stylesheets`, `sonata_head_title`, `admin_lte_skin_class` (deleted in the port), `logo`, `sonata_top_nav_menu_add_block` (appends a language dropdown), `javascripts`, `sonata_javascript_config`, `sonata_javascript_pool`, `sonata_wrapper` (appends `#universal-modal`), `sonata_page_content_header` (deprecation alert), `content` |
| List override | `templates/crud/list_with_summaries.html.twig` extends `@!SonataAdmin/CRUD/base_list.html.twig` | `list_footer` (moves to `list_after_table`) |
| Page templates on the layout | `admin/dashboard_stats`, `admin/pocket_rvm/reject`, `promo_code/create`, `recomapp/legacyEans/{create,reject}`, `recomat/{clone,edit,list}`, `test_runner/launch`, `transaction/create_manual`, `security/{login_form,password_change,password_reset_layout}` (+ dead `generic_create`, `message/edit`, `recomat/confirm_archive`, `promo_code/upload`) | `sonata_admin_content`, `notice`, `title`, `sonata_breadcrumb`, `sonata_nav`, `logo`, `sonata_left_side`, `body_attributes`, `sonata_wrapper`; `recomat/edit` extends `@SonataAdmin/CRUD/edit.html.twig` and overrides `form`, `sonata_pre_fieldsets`, `sonata_post_fieldsets`, `sonata_form_actions`; `recomat/list` extends `CRUD/list` and overrides `actions` |
| Bundle overrides (21) | `templates/bundles/SonataAdminBundle/`: `Button/create_button`, `CRUD/list__action`, `list__action_{edit,show,delete,history}`, 11 custom `list__action_*`, `list__select`, `list_enum`, `Association/list_many_to_one`, `Association/base_list_inner_row` (dead) | `field` block in `list__action`, `list__select`, `list_enum`, `list_many_to_one`; `get_admin_template('base_list_field', admin.code)`; `actions.link_parameters`; `render_relation_element` |
| Cell templates (55, 1,041 lines) | `templates/field/*.html.twig`, `templates/component/adminUser.html.twig` | 27 extend `@SonataAdmin/CRUD/base_list_field.html.twig` block `field`; 4 extend `base_show_field`, 1 extends `show_html`; the rest emit `<td class="sonata-ba-list-field sonata-ba-list-field-{type}" objectId="…">` themselves |
| `user_block` | `templates/security/user_block.html.twig` | `<li>` pass-through inside the user dropdown; app routes `sonata_admin_edit_own_password`, `admin_logout` |

Bootstrap/AdminLTE class occurrences across `templates/`: `callout` 316, `btn` 76, `row` 29,
`form-group` 24, `box` 22, `box-body` 21, `col-md-12` 20, `box-title`/`box-header` 17, `box-primary`
14, `has-error` 13, `btn-default` 13, `glyphicon` 12, `btn-success` 12, `alert` 12, `text-muted`
11, `help-block` 10, `form-control` 10, `label-default`/`label-danger` 6, `label-success` 5,
`progress` 6, `well` 4, `modal` 4. All become `.adm-*` classes or utilities in the port (document
10 §1 steps 4–10).

## 4. Template scope derived from §2

Across the seven packages adminata ships (148 templates): 100 rewritten in 1.0 (the 98
admin-bundle files below plus form-extensions' `Form/datepicker.html.twig` and twig-extensions'
`FlashMessage/render.html.twig`), 12 copied unchanged (ten Bootstrap-free block-bundle templates
and the two ORM form themes), 36 deferred (the 33 admin-bundle files below plus the ORM
`block_audit` and block-bundle's `block_core_rss` and `block_side_menu_template`); document 03 §G.

**Admin-bundle templates rewritten in 1.0 (98 files, 4,739 Twig lines):** `standard_layout`, `ajax_layout`,
`empty_layout`; `Menu/sonata_menu`; `Core/{add_block,dashboard,user_block}`; `Breadcrumb/*` (2);
`Block/block_admin_list`; `Button/*` (6); `Pager/*` (5); `Form/{form_admin_fields,filter_admin_fields}`,
`Form/Type/sonata_type_model_autocomplete`; `Helper/render_form_dismissable_errors`;
`CRUD/`: `base_list`, `list`, `base_list_field`, `base_list_inner_row`, `list_inner_row`,
`list_outer_rows_list`, `list__action`, `list__action_{edit,show,delete,history}`, `list__batch`,
`list__select`, 14 `list_*` typed, 12 `display_*`, `base_show`, `show`, `base_show_field`,
13 `show_*` typed, `base_edit`, `edit`, `base_edit_form`, `base_edit_form_macro`,
`base_array_macro`, `delete`, `batch_confirmation`, `action_buttons`, `dashboard__action`,
`dashboard__action_create`; `CRUD/Association/list_*` (4) and `show_*` (4).

**Admin-bundle templates deferred (33 files, 2,499 lines), inherited as-is with a `{# adminata: not yet ported #}` marker:**
`CRUD/Association/edit_*` (11), `CRUD/{base_history,history,history_revision_timestamp}`,
`CRUD/{base_show_compare,show_compare}`, `CRUD/{base_acl,base_acl_macro,acl}`, `CRUD/preview`,
`CRUD/select_subclass`, `CRUD/tree`, `CRUD/list_outer_rows_mosaic`, `CRUD/{base_list_flat_field,
base_list_flat_inner_row}`, `CRUD/action`, `Core/{search,tab_menu_template}`,
`Block/{block_admin_preview,block_rss_dashboard,block_search_result,block_stats}`,
`Helper/short-object-description`.

Features deliberately outside 1.0 because the app does not use them: `ModelType`, `ModelListType`,
`ModelHiddenType`, `AdminType` (inline nested admins), `ChoiceFieldMaskType`, `ImmutableArrayType`,
`TemplateType`, sortable collections, `editable` cells (x-editable), ACL editor, history/audit,
compare, preview, subclass selection, tree and mosaic list modes, global search and its block,
stats/RSS/search-result/admin-preview blocks, child admins and tab menus, form tabs, select
enhancement (`use_select2`), tooltips.

## 5. JavaScript and CSS the app must port (document 10)

| Item | Size | Replacement |
|---|---|---|
| `assets/admin/UniversalModal.js` (`$('#universal-modal').modal()`) | 19 lines | native `<dialog>` + `sonata-modal` |
| `assets/admin/SectionSlider.js` (`addClass/removeClass('hidden')`) | 14 lines | toggle the `hidden` attribute |
| `assets/admin/TransactionItemsAccordion.js` (`$.ajax`, parses `table.sonata-ba-list`) | 56 lines | `fetch` + `insertAdjacentHTML` |
| `templates/promo_code/upload.html.twig` inline script | 3 jQuery calls | dead template; delete |
| `import $ from 'jquery'`, `jquery-ui-bundle` (+ CSS), `.addExternals({ jquery })` | | delete |
| `assets/styles/admin-theme.scss` + partials | 453 lines | delete (TailAdmin provides the look) |
| `assets/styles/sonata-overrides.scss` | 658 lines | prune to about 150 (`th.content-width`, image preview, optional `.btn-action-*` palette) |
| `.mt-10 {margin-top:10px}` | 1 rule | rename `.app-mt-10` |

## 6. Icons

76 distinct `fa-*` names across `templates/`, `src/` and `config/`. Checked against Font Awesome
7.3.1 Free metadata (`icon-families.json`): 75 resolve (56 canonical names, 19 through built-in
aliases such as `check-circle → circle-check`, `save → floppy-disk`, `sign-out →
arrow-right-from-bracket`, `times → xmark`, `map-marker-alt → location-dot`, `edit →
pen-to-square`, `trash-alt → trash-can`, `exchange-alt → right-left`, `cloud-upload →
cloud-arrow-up`). Only `clock-o` (`templates/field/rvmTaskState.html.twig`) needs the v4 shim and is
renamed to `clock`. Two `glyphicon` spans on the login page render nothing today and are deleted.
