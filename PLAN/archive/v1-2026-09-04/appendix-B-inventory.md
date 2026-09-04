# Appendix B — Inventory

## 1. Sonata Admin 4.43.0 (fork base)

| Item | Value |
|---|---|
| Release | 4.43.0, 2026-06-03 (5.x branch last merge 2026-06-04; views/assets byte-identical; 3 PHP files differ) |
| Floors | `php ^8.2`, Symfony `^6.4 \|\| ^7.3 \|\| ^8.0`, `twig ^3.15`, `symfony/stimulus-bundle ^2.22 \|\| ^3.0` |
| Sonata deps | `block-bundle ^5.0`, `doctrine-extensions ^2.0`, `exporter ^3.1.1`, `form-extensions ^2.0`, `twig-extensions ^2.0` |
| PHP | 242 files, 27,892 lines; 163 `final class`, 51 interfaces, 12 abstract classes |
| Templates | 131 files, 7,238 lines; 138 unique block names; 89 `@SonataAdmin/…` cross references in 61 templates |
| Config | 39 template keys + `form_theme`/`filter_theme`; 121 service ids; 8 routes; 34 XLIFF locales (126 ids) |
| JS/SCSS | 2,877 lines: `app.js`, `admin.js` (16 `Admin` members), `base.js`, `sidebar.js`, `treeview.js`, `stimulus.js`, `core/*`, 9 controllers, 7 SCSS files |
| npm deps | jquery 3.7, bootstrap 3.3, admin-lte 2.4, icheck, x-editable, select2 4 + bootstrap theme, jquery-form, jquery-ui, jquery.scrollto, jquery-slimscroll, masonry-layout, qs, @fontsource/source-sans-pro, fontawesome-free 5.15, @hotwired/stimulus 3.2, @symfony/stimulus-bridge; Encore 5 build |
| Prebuilt | `app.css` 345 KB, `app.js` 485 KB, fonts 2.1 MB (72 files), images 2.0 MB, select2 locales 240 KB (59), AdminLTE skins 56 KB (12); total 5.1 MB |
| Tests | 155 test files (Form 34, Twig 18, DI 12, Action 7, Util 7, Admin 6, Functional 5, …); stub `ModelManager` test app |

### Templates by directory (bytes)

- `standard_layout.html.twig` 19,601; `ajax_layout.html.twig` 2,830; `empty_layout.html.twig` 721
- `Block/` (5): block_admin_list 2,631; block_admin_preview 4,758; block_rss_dashboard 1,016; block_search_result 3,695; block_stats 1,172
- `Breadcrumb/` (2): breadcrumb 1,073; breadcrumb_title 624
- `Button/` (6): acl 654; create 1,093; edit 628; history 643; list 538; show 657
- `Core/` (5): add_block 3,277; dashboard 4,567; search 1,727; tab_menu_template 4,743; user_block 27
- `Form/` (3): form_admin_fields 31,096; filter_admin_fields 3,446; Type/sonata_type_model_autocomplete 12,202
- `Helper/` (2): render_form_dismissable_errors 239; short-object-description 301
- `Menu/` (1): sonata_menu 2,852
- `Pager/` (5): base_links 2,080; base_results 1,401; links 282; results 284; simple_pager_results 885
- `CRUD/Association/` (18): edit_many_script 22,174; edit_many_to_many 8,339; edit_many_to_one 7,255; edit_one_to_one 7,294; edit_one_to_many 5,370; edit_one_to_many_inline_table 3,307; edit_one_to_many_inline_tabs 3,235; edit_one_to_many_sortable_script_table 1,628; edit_one_to_many_sortable_script_tabs 1,768; edit_one_script 3,041; edit_modal 720; list_* (4) ≈ 1,100–1,500 each; show_* (4) ≈ 1,100–1,300 each
- `CRUD/` (81): base_list 27,092; base_edit_form 11,360; base_show 5,844; base_list_field 5,331; list_outer_rows_mosaic 4,986; tree 3,413; base_history 3,132; base_acl_macro 2,787; base_array_macro 2,789; batch_confirmation 2,667; base_edit_form_macro 2,436; base_show_field 2,146; delete 2,028; preview 1,817; base_list_flat_inner_row 1,497; dashboard__action_create 1,448; display_url 1,503; display_choice 1,420; select_subclass 1,336; base_edit 1,300; list_choice 1,221; list__action_* (4) ≈ 1,070–1,100; list_url 1,039; list_boolean 993; list_enum 982; base_list_inner_row 856; display_email 875; display_enum 819; display_html 817; show_choice 813; base_acl 756; list_trans 731; show_trans 726; action 716; display_boolean 685; show_enum 575; show_compare 567; list_array 554; display_date/datetime/time ≈ 530–540; list_date/datetime/time ≈ 535; show_date/datetime/time ≈ 531–535; list__select 535; show_array 536; list_email 586; list__action 492; show_boolean 480; show_currency 483; list_currency 487; display_trans 486; action_buttons 417; list_percent 417; show_percent 413; display_percent 403; list__batch 396; display_currency 392; list_outer_rows_list 372; show_email 356; dashboard__action 345; history_revision_timestamp 334; list_html 307; show_html 303; list_inner_row 290; list_string 290; show 280; edit 280; list 280; history 283; acl 279

### Routes

`sonata_admin_redirect`, `sonata_admin_dashboard`, `sonata_admin_retrieve_form_element`,
`sonata_admin_append_form_element`, `sonata_admin_short_object_information`,
`sonata_admin_set_object_field_value`, `sonata_admin_search`,
`sonata_admin_retrieve_autocomplete_items`.

### PHP strings coupled to markup (all kept)

`BaseGroupedMapper.php:83` `'box box-primary'`; `Configuration.php:540` `'col-md-4'`, `:360`
`'fas fa-folder'`; `TaggedAdminInterface.php:55,60` list-mode icons; `AdminStatsBlockService.php:69,72`;
`AdminSearchBlockService.php:102`; `AbstractAdmin.php:1800,1809,2595`; `GroupMenuProvider.php:70`
`'keep-open'`; `SonataAdminExtension.php:94-100` skin CSS path, `:200-201` `'sonata-medium-date'`;
`IconRuntime.php:20-38` FA-only `parse_icon`.

## 2. TailAdmin free edition v2.3.0 (2026-04-28)

| Item | Value |
|---|---|
| Stack | Tailwind v4 (`@tailwindcss/postcss`), Alpine 3.14 + `@alpinejs/persist`, flatpickr, apexcharts, chart.js, dropzone, fullcalendar, jsvectormap, swiper; Webpack 5 with `<include>` partials |
| License | MIT (`LICENSE`); `package.json` says ISC (metadata slip) |
| Pages | index, blank, basic-tables, form-elements, buttons, badge, alerts, avatars, images, videos, profile, calendar, line-chart, bar-chart, signin, signup, 404, sidebar |
| Partials | sidebar, header, breadcrumb, overlay, preloader, alert/{error,info,success,warning}, avatar/01-04, badge/01-06, buttons/01-06, table/{01,06}, profile modals, calendar modal, metric group, media card, datepicker, grid images, videos, social links |
| CSS | `style.css`: Google Fonts import (Outfit), `@custom-variant dark (&:is(.dark *))`, `@theme` tokens (fonts, breakpoints 2xsm/xsm/3xl, title/theme type scale, brand/blue-light/gray/orange/success/error/warning/pink/purple palettes, shadows, z-index), `@utility` `menu-item*`, `menu-dropdown-*`, `no-scrollbar`, `custom-scrollbar`, flatpickr/apexcharts/jsvectormap overrides |
| React/Next extras | `ui/{alert,avatar,badge,button,dropdown,modal,table}`, `form/{Input,Select,Checkbox,Radio,Switch,TextArea,FileInput,DatePicker,MultiSelect,Label}`, `ChartTab` segmented control, `Pagination.tsx` (Next), `AppSidebar/AppHeader/Backdrop`, `SidebarContext/ThemeContext`, `src/icons/*.svg` |

### Gap analysis summary (Sonata need → source)

Table with sortable headers, batch, row actions → TailAdmin table + buttons; filters panel →
card + grid + dropdown; pagination → Next `Pagination.tsx`; mosaic → image grid; tree view →
new; tabs → new underline tabs (segmented for list mode); collapsible groups → card;
sticky action bar → new; association modals → React Modal semantics on `<dialog>`;
select2 → Tom Select (3P); iCheck → CSS checkboxes; x-editable → new popover; collections →
SortableJS (3P); date pickers → flatpickr (3P, themed); breadcrumb dropdowns → dropdown panel;
add-block mega-dropdown → panel + grid; user block → user dropdown; flash → alerts + dismiss;
confirm pages → danger card; ACL matrix → sticky table; history diff → table highlight;
dashboard grid → 12-col grid + metric card; readmore → kept; spinner → CSS spinner;
tooltips → post-1.0; keyboard shortcuts → ⌘K kept; density and RTL → tokens/logical utilities.
Full table: `R/tailadmin-catalog.md` §3.
