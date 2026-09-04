# adminata research — Show view, History/Revisions, ACL, Delete, Preview, Action buttons, Display helpers, Misc

Scope: everything in Sonata Admin 4.43.0 that is neither the list/datagrid nor the edit form nor the global layout/nav: the show page and its field templates, the shared `display_*` helpers, association show templates, history + revision compare, the ACL editor, delete + batch confirmation pages, preview mode, the action-button system (page header buttons and dashboard actions), the short-object-description helper, and the ORM bundle's audit block.

Path roots used in citations:

| Prefix | Root |
|---|---|
| `admin/` | `scratchpad/sonata-admin-4.43.0` |
| `admin5/` | `scratchpad/sonata-admin-5.x` |
| `orm/` | `scratchpad/vendor-extract/doctrine-orm-admin-bundle/sonata-project-SonataDoctrineORMAdminBundle-2147390` |
| `twig-ext/` | `scratchpad/vendor-extract/twig-extensions` |
| `block/` | `scratchpad/vendor-extract/block-bundle` |
| `ta-html/` | `scratchpad/tailadmin-html` |
| `ta-react/` | `scratchpad/tailadmin-react` |
| `ta-next/` | `scratchpad/tailadmin-next` |
| `mongo/` | `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle` |

Verified up front: every file in this dimension is byte-identical between 4.43.0 and the 5.x dev branch (diff of `RenderElementRuntime.php`, `ShowMapper.php`, `AclMatrixType.php`, `AdminObjectAclManipulator.php`, `AdminObjectAclData.php`, `BaseGroupedMapper.php`, `revision_controller.js`, `readmore_controller.js`, `base_show.html.twig` is empty; `CRUDController.php` differs only by the removal of the `method_exists($extension, 'preBatchAction')` guard). Nothing here has to be reconciled with 5.x.

---

## 0. Executive summary

1. **Everything in this dimension is server-rendered Twig with almost no JS.** The only JS behaviours are: the Stimulus `sonata-revision` controller (AJAX-load a revision into a `<div>`), the Stimulus `sonata-readmore` controller (collapse long values), Bootstrap tab switching on the show page (`data-toggle="tab"`), Bootstrap dropdowns (page actions dropdown, dashboard "create subclass" dropdown), Bootstrap alert dismiss (`data-dismiss="alert"`), and iCheck skinning of ACL checkboxes. All of these are trivially replaceable by Alpine.js (`x-data`/`x-show`/`@click.outside`) plus keeping the two Stimulus controllers (they are framework-agnostic — `revision_controller.js` is pure `fetch`, `readmore_controller.js` is pure DOM + `ResizeObserver`).
2. **The PHP side is untouched.** `CRUDController` passes exactly the variables the templates need (`object`, `elements`, `revisions`, `currentRevision`, `object_compare`, `permissions`, `aclUsersForm`, `aclRolesForm`, `csrf_token`, `action`, `action_label`, `batch_translation_domain`, `data`, `form`). Template *keys* (`show`, `show_compare`, `history`, `history_revision_timestamp`, `acl`, `delete`, `batch_confirmation`, `preview`, `action`, `short_object_description`, `button_*`, `action_create`) and template *file paths* (`@SonataAdmin/CRUD/show_boolean.html.twig` …) are referenced from PHP constants, DI defaults and the persistence bundles' DI extensions, so **all 131 file paths must be kept** and only their contents rewritten.
3. **Two hard contracts leak markup into user land** and are the main drop-in risks: (a) action-button templates (`Button/*.html.twig` and any user `configureActionButtons()` template) must emit `<li><a class="sonata-action-element">…</a></li>` because `standard_layout.html.twig` post-processes the concatenated block output with string filters (`replace({'<li>':''})`, `split('</a>')|length > 2`) to decide between an inline list and a dropdown (`admin/src/Resources/views/standard_layout.html.twig:265-280`); (b) dashboard action templates (`dashboard__action.html.twig` and user copies per the docs) emit `<a class="btn btn-link btn-flat">` inside a `div.btn-group` (`admin/src/Resources/views/Block/block_admin_list.html.twig:43-47`). Both are documented as user-extension points (`admin/docs/cookbook/recipe_custom_action.rst:274-298`, `admin/docs/reference/dashboard.rst:370-386`).
4. **Unit tests pin exact HTML** of the display helpers: `<span class="label label-success">yes</span>` for booleans, `<th>Data</th> <td>…</td>` for every show field, `<th class="diff">` for the compare diff, and the full `sonata-readmore` Stimulus attribute set (`admin/tests/Twig/RenderElementRuntimeTest.php:1553-1568, 1396-1405, 2005`). Changing the boolean badge classes means rewriting ~20 test expectations; keeping `label label-success|danger` as *hook* classes alongside Tailwind classes avoids that.
5. **Group options are Bootstrap-flavoured public API**: `ShowMapper::with('X', ['class' => 'col-md-8', 'box_class' => 'box box-solid box-danger'])` (`admin/src/Mapper/BaseGroupedMapper.php:76-85`, `admin/docs/reference/action_show.rst:31-66`). User admins pass `col-md-*` and `box box-*` strings which are echoed verbatim into `class=""`. adminata needs a **compat translation layer** (Twig filter or CSS `@utility` definitions) for the Bootstrap 3 grid/box vocabulary or existing admins will render single-column, unstyled boxes.
6. **Currency/percent are *not* Intl-formatted in core.** `display_currency` is `{{ currency }} {{ value }}` and `display_percent` is `{{ value * 100 }} %` (`admin/src/Resources/views/CRUD/display_currency.html.twig:16`, `display_percent.html.twig:16-17`). Only when `SonataIntlBundle` is installed do `show_date/datetime/integer/float/currency/percent` get swapped for `@SonataIntl/CRUD/show_*.html.twig` (`admin/src/DependencyInjection/AbstractSonataAdminExtension.php:59-67`). adminata must keep the raw core behaviour (tests assert `EUR 10.746135`, `1074.6135 %`) and must not require `twig/intl-extra`; it does require `twig/string-extra` for `u.truncate` (`admin/composer.json:66`).
7. **Icons**: every icon in this dimension is a FontAwesome 5 `<i class="fas fa-…">` and `parse_icon` only accepts `fa|fas|far|fab|fal|fad ` prefixes or a raw string starting with `<` (`admin/src/Twig/IconRuntime.php:20-38`). TailAdmin has no icon font. Recommendation: a `sonata_icon()`/extended `parse_icon` that maps a curated FA class list to inline SVG (TailAdmin icon set) and passes through raw `<svg>`; keep an optional FA CSS include for unknown classes so user `configureDashboardActions(['icon' => 'fas fa-magic'])` still renders.

---

## 1. Show page — element-by-element mapping

### 1.1 Controller and data flow

| Concern | Evidence |
|---|---|
| `showAction` renders template key `show` with `action='show'`, `object`, `elements` (= `$admin->getShow()` FieldDescriptionCollection) | `admin/src/Controller/CRUDController.php:643-670` |
| `historyViewRevisionAction` reuses the **same** `show` template with a revisioned object (`action` is still `'show'`) | `admin/src/Controller/CRUDController.php:715-762` |
| `historyCompareRevisionsAction` renders template key `show_compare` with `object`, `object_compare`, `elements` | `admin/src/Controller/CRUDController.php:765-823` |
| `base_template` is `templates.layout` for normal requests and `templates.ajax` (`@SonataAdmin/ajax_layout.html.twig`) when `X-Requested-With: XMLHttpRequest` | `admin/src/Controller/CRUDController.php:958-968` (`setTwigGlobals`), `admin/src/DependencyInjection/Configuration.php:572-573` |
| `ajax_layout.html.twig` exposes a bare `{% block show %}` — this is what the history page fetches into its right column | `admin/src/Resources/views/ajax_layout.html.twig:20` (file line; block `show` inside `content`) |
| `render_view_element` filter → `RenderElementRuntime::renderViewElement()`; the default template is **hard-coded** to `@SonataAdmin/CRUD/base_show_field.html.twig` (not a registry key) | `admin/src/Twig/RenderElementRuntime.php:61-80` |
| `renderViewElementCompare()` renders the field template twice (base and compare object), string-compares the output to compute `is_diff`, then renders once more with `value_compare`, `object_compare`, `is_diff` | `admin/src/Twig/RenderElementRuntime.php:83-126` |
| In Twig debug mode every rendered element is wrapped in `<!-- START fieldName: … template: … --> … <!-- END -->` HTML comments | `admin/src/Twig/RenderElementRuntime.php:205-233` |
| Per-type default templates: `TemplateRegistryInterface::SHOW_TEMPLATES` (string/integer/float → `base_show_field.html.twig`; no `textarea`/`identifier` entry for show) | `admin/src/Templating/TemplateRegistryInterface.php:26-47` |
| Persistence bundles inject those defaults via `AbstractSonataAdminExtension::fixTemplatesConfiguration()` → `sonata_doctrine_orm_admin.templates.types.show` / `sonata_doctrine_mongodb_admin.templates.types.show`; the ORM/Mongo `ShowBuilder::fixFieldDescription()` calls `setTemplate($this->templates[$type])` | `admin/src/DependencyInjection/AbstractSonataAdminExtension.php:36-45`, `orm/src/DependencyInjection/SonataDoctrineORMAdminExtension.php:31,63-64`, `orm/src/Builder/ShowBuilder.php` (`fixFieldDescription`), `mongo/src/DependencyInjection/SonataDoctrineMongoDBAdminExtension.php:30,47-48` |
| `ShowMapper::add()` forces `'safe' => false` default and label from the label translator strategy (`'show'` context) | `admin/src/Show/ShowMapper.php:87-91` |
| Group/tab defaults: `collapsed=false, class=false, description=false, label=<strategy>, translation_domain=null, name, box_class='box box-primary', empty_message, empty_message_translation_domain` | `admin/src/Mapper/BaseGroupedMapper.php:76-85` |
| Tabs: a `default` tab is auto-created (`auto_created => true`) when groups are added without a tab; group codes are prefixed `tab.group` for non-default tabs | `admin/src/Mapper/BaseGroupedMapper.php:129-152` |
| Show block events `sonata.admin.show.top` / `sonata.admin.show.bottom` with `{admin, object}` context | `admin/src/Resources/views/CRUD/base_show.html.twig:36,88` |

### 1.2 `base_show.html.twig` structure (file lines are exact)

```
{% extends base_template %}                                   :12
block title        → 'title_show' (toString truncated 15)     :14-16
block navbar_title → 'title_show' (toString truncated 100)    :18-20
block actions      → include CRUD/action_buttons.html.twig     :22-24
block tab_menu     → knp_menu_render(admin.sidemenu(action), currentClass 'active', tab_menu_template) :26-31
block show                                                     :33-89
  div.sonata-ba-view                                           :34
    sonata_block_render_event('sonata.admin.show.top')         :36
    has_tab = (1 tab and key != 'default') or >1 tabs          :38-39
    if has_tab:
      tab_prefix = 'tab_' ~ admin.uniqid ~ '_' ~ random()      :42
      tab_query_index = app.request.query.get('_tab',0)|split('_')|last :43
      div.nav-tabs-custom > ul.nav.nav-tabs[role=tablist]      :44-45
        li(.active) > a.changer-tab[href=#id aria-controls data-toggle=tab] label|trans :46-57
      div.tab-content > div.tab-pane.fade(.in.active)#id       :60-66
        div.box-body.container-fluid > div.sonata-ba-collapsed-fields :67-68
          <p>{{ show_tab.description|raw }}</p> if set         :69-71
          {{ block('show_groups') }}                           :73-74
    elseif showtabs['default'] defined → block('show_groups')  :81-84
    sonata_block_render_event('sonata.admin.show.bottom')      :88
block show_groups                                              :91-131
  div.row                                                      :92
  block field_row                                              :93-128
    for code in groups: show_group = admin.showgroups[code]    :94-95
      div.{{ show_group.class|default('col-md-12') }}.{{ no_padding ? 'nopadding' }} :97
        div.{{ show_group.box_class }}                         :98
          div.box-header > h4.box-title > block show_title (label|trans or raw when translation_domain === false) :99-108
          div.box-body.table-responsive.no-padding > table.table > tbody :110-112
            for show_field_name in show_group.fields:
              block show_field                                 :114-120
                tr.sonata-ba-view-container                    :115
                  {{ elements[show_field_name]|render_view_element(object) }} if defined :116-118
```

Observations that matter for the rewrite:

* Tab pane ids embed `random()`, so they cannot be deep-linked; the `_tab` query parameter is read as `…|split('_')|last` and compared to `loop.index` (`base_show.html.twig:43,48,64`). The form page's `sonata-edit` Stimulus controller writes `_tab=<aria-controls>` into the URL (`admin/assets/js/controllers/edit_controller.js:63-66`); the show page has no such controller, it only *reads* `_tab`. Keep this exact reading logic so `?_tab=tab_xxx_2` links from custom code still open the second tab.
* `no_padding` is an undefined variable in stock templates (always false) — a hook for people who `{% set no_padding = true %}` before `{{ block('show_groups') }}`; harmless to keep.
* Groups are rendered inside `div.row` with a per-group `class` (defaults to `col-md-12`). The docs explicitly tell users to pass `'class' => 'col-md-8'` (`admin/docs/reference/action_show.rst:31-37,55-57`).
* The functional test asserts `td:contains("foo_name")` on `/admin/tests/app/foo/test_id/show` (`admin/tests/Functional/Controller/CRUDControllerTest.php:138-144`), and the unit tests assert `<th>Data</th> <td>…</td>` for every field type. **Keep `<table><tr><th><td>`**; do not switch to a `<dl>` description list even though TailAdmin's profile page uses label/value stacks. A Tailwind-styled table gives the same look with zero API risk.

### 1.3 `base_show_field.html.twig` (row template; file lines exact)

```
<th{% if is_diff|default(false) %} class="diff"{% endif %}>         :12
  block name  → label|trans (or raw if translationDomain === false; nothing if label === false) :13-21
</th>
<td>                                                                :23
  block field                                                       :24-45
    collapse = field_description.option('collapse')                 :25
    if collapse: div.sonata-readmore[stimulus_controller('sonata-readmore', {collapsedHeight: collapse.height|default(40), moreText: collapse.more|default('read_more')|trans, lessText: collapse.less|default('read_less')|trans})] :27-31
       div.sonata-readmore-content[stimulus_target content] > block field_value :32-36
       button.sonata-readmore-btn.btn-link[stimulus_target button][stimulus_action click→toggle] :37-40
    else block('field_value')                                       :43
  block field_value → safe ? value|raw : value|default('')|nl2br    :33-35
</td>
block field_compare → if value_compare defined: set value=value_compare, object=object_compare; <td>{{ block('field') }}</td> :48-54
```

Every `show_*.html.twig` extends this and overrides **only `block field`** (so the collapse/readmore wrapper is *lost* for typed fields — only string/integer/float and custom templates that call `parent()` get it). The compare test fixture overrides `field` and calls `parent()` (`admin/tests/Fixtures/Resources/views/custom_show_field.html.twig` — found via `find`), so `field` must keep its name and semantics.

**TailAdmin mapping for the row**

| Sonata element | TailAdmin/Tailwind replacement | Notes |
|---|---|---|
| `div.box.box-primary` (group) | `div.rounded-2xl.border.border-gray-200.bg-white.dark:border-gray-800.dark:bg-white/[0.03]` (`ta-html/src/partials/table/table-01.html:1-3`, `ta-html/src/profile.html:54`) | Keep `box`/`box-*` in the class list as well (see §1.4 compat layer) |
| `div.box-header > h4.box-title` | `div.px-5.py-4.sm:px-6 > h3.text-lg.font-semibold.text-gray-800.dark:text-white/90` (`ta-html/src/partials/table/table-01.html:7-11`) | Keep `box-title` hook class |
| `div.box-body.table-responsive.no-padding` | `div.border-t.border-gray-100.dark:border-gray-800.overflow-x-auto` (`ta-html/src/profile.html:64`, `table-01.html:64`) | `overflow-x-auto` replaces `.table-responsive` |
| `table.table` | `table.min-w-full` with `tbody.divide-y.divide-gray-100.dark:divide-gray-800` (`table-01.html:65,109`) | |
| `tr.sonata-ba-view-container` | keep class; add `hover:bg-gray-50 dark:hover:bg-white/[0.02]` | stock CSS stripes even rows (`admin/assets/scss/layout.scss:281-286,292-297`) and sets `th{width:130px}` (`layout.scss:254-257`) |
| `th` (label) | `th.w-1/4.px-5.py-3.text-left.align-top.text-theme-xs.font-medium.text-gray-500.dark:text-gray-400` (`table-01.html:69-77`) | keep `th.diff` hook for compare |
| `td` (value) | `td.px-5.py-3.text-theme-sm.text-gray-800.dark:text-white/90` (`table-01.html:111-120`) | |
| tabs `ul.nav.nav-tabs` + `div.tab-content` | TailAdmin has no HTML tab component; use the React `ChartTab` segmented pattern: `div.flex.items-center.gap-0.5.rounded-lg.bg-gray-100.p-0.5.dark:bg-gray-900 > button.px-3.py-2.font-medium.rounded-md.text-theme-sm` with active `shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800` (`ta-react/src/components/common/ChartTab.tsx:8-41`); or an underline tab bar. Behaviour via Alpine `x-data="{tab: <index>}"` + `x-show`. Keep `nav-tabs-custom`, `changer-tab`, `tab-pane`, `active` classes as hooks and the `_tab` query-param read. | Same pattern must be used for the edit form tabs (`admin/src/Resources/views/CRUD/base_edit_form.html.twig:46-58`) and inline one-to-many tabs — coordinate with the form dimension |
| `p` tab description (`|raw`) | `p.mb-4.text-sm.text-gray-500.dark:text-gray-400` | |
| `.sonata-readmore` | keep markup and Stimulus controller verbatim; port `admin/assets/scss/readmore.scss:1-26` to a Tailwind `@layer components` block (`.sonata-readmore-content{overflow:hidden} .expanded{max-height:none!important}`, hide `.sonata-readmore-btn` unless `.truncated`) and style the button as TailAdmin text link `text-sm font-medium text-brand-500 hover:text-brand-600` | unit tests pin every attribute (`admin/tests/Twig/RenderElementRuntimeTest.php:1396-1405`) |

### 1.4 Group `class` / `box_class` compatibility layer (drop-in risk, HIGH)

User admins and the docs use Bootstrap 3 vocabulary as *values*:

* `class`: `col-md-12` (default, `base_show.html.twig:97`), docs example `col-md-8` (`action_show.rst:56`), same for form groups (`admin/docs/reference/action_create_edit.rst:112` area).
* `box_class`: default `box box-primary` (`BaseGroupedMapper.php:83`), docs example `box box-solid box-danger` (`action_show.rst:57`). AdminLTE 2 defines `box`, `box-solid`, `box-default|primary|info|warning|success|danger`, `collapsed-box`, `box-header with-border`, `box-body`, `box-footer`.

Options:

1. **CSS compat layer** (recommended): ship a small `@layer components` file that defines `.row` (flex-wrap grid, negative gutters), `.col-md-1…12` / `.col-sm-*` / `.col-lg-*` / `.col-xs-*` as Tailwind `@apply md:w-x/12` rules, and `.box`, `.box-solid`, `.box-<color>` (border-top colour / header colour using the TailAdmin `brand`, `blue-light`, `warning`, `success`, `error`, `gray` tokens). This keeps every existing admin rendering correctly without code changes and costs ~150 lines of CSS.
2. **Twig translation filter**: `{{ show_group.class|sonata_grid_class }}` mapping `col-md-N` → `md:col-span-N` on a `grid grid-cols-12` row. Cleaner Tailwind output, but unknown user classes pass through and silently do nothing.
3. Both: use the filter for the stock defaults and the compat layer as a safety net.

The template should render `class="{{ show_group.class|default('col-md-12') }}"` unchanged so custom templates that already override `show_groups` continue to work.

### 1.5 Per-type show templates → display helpers → TailAdmin

All `show_*.html.twig` files extend `base_show_field.html.twig` and override `block field` with an `include '@SonataAdmin/CRUD/display_*.html.twig' with {...} only`. File lines below are exact (header comment occupies lines 1-10).

| Type (`FieldDescriptionInterface`) | Show template | Options passed | Display helper output (4.43.0) | TailAdmin/Tailwind replacement | Preserve |
|---|---|---|---|---|---|
| `string`, `integer`, `float` | `base_show_field.html.twig` (registry) | `safe` | `value|nl2br` or `value|raw` | text in `td` | exact `<th>…</th> <td>…</td>` (tests `RenderElementRuntimeTest.php:1533-1534`) |
| `array` | `show_array.html.twig:11-20` → `base_array_macro.html.twig` `render_array` | `inline`, `display` (`both|keys|values`), `key_translation_domain`, `value_translation_domain` (true/false/null/string), `default_translation_domain=admin.translationDomain` | non-inline: `<ul><li>key&nbsp;=>&nbsp;val</li></ul>` (nested recursive); inline: `[k&nbsp;=>&nbsp;v, …]` (`base_array_macro.html.twig:12-86`) | `ul.list-disc.pl-5.space-y-1`; inline as `code`-ish `text-theme-sm` | test pins `<ul><li>1&nbsp;=>&nbsp;First</li>…` (`RenderElementRuntimeTest.php:1540-1550`). Docs say show default `inline=true` but template default is `false` — docs bug (`field_types.rst:76-78` vs macro line 14) |
| `boolean` | `show_boolean.html.twig:11-19` → `display_boolean.html.twig` | `inverse` | `<span class="label label-success|label-danger">yes|no</span>` (`display_boolean.html.twig:13-25`; text keys `label_type_yes/no` → "yes"/"no" `SonataAdminBundle.en.xliff:233-239`) | TailAdmin badge: success `inline-flex items-center justify-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500`; error `bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500` (`ta-html/src/partials/badge/badge-01.html:10-21`, `ta-react/src/components/ui/badge/Badge.tsx:43-46`); optionally prefix inline check/close SVG | tests pin `<span class="label label-success">yes</span>` (`RenderElementRuntimeTest.php:1553-1568`, `RenderElementExtensionTest.php:805-877`). Decision needed (§10 Q1). Note docs claim a "check mark" (`action_show.rst:125-127`) — not true in 4.43, it is a text label |
| `date` | `show_date.html.twig:11-20` → `display_date.html.twig` | `format` (default `'F j, Y'`), `timezone` | `<time datetime="Y-m-d(UTC)" title="Y-m-d(UTC)">formatted</time>` or `&nbsp;` when empty (`display_date.html.twig:13-19`) | unchanged; `time.whitespace-nowrap` | attribute set pinned by compare tests (`RenderElementRuntimeTest.php:2006-2010`) |
| `datetime` | `show_datetime.html.twig:11-20` → `display_datetime.html.twig` | `format` (default: Twig global date format), `timezone` | `<time datetime="c(UTC)" title="c(UTC)">…</time>` (`display_datetime.html.twig:13-19`) | unchanged | also used by `history_revision_timestamp.html.twig:12-14` |
| `time` | `show_time.html.twig:11-20` → `display_time.html.twig` | `format` (default `'H:i:s'`), `timezone` | `<time datetime="H:i:sP(UTC)" …>` (`display_time.html.twig:13-19`) | unchanged | |
| `email` | `show_email.html.twig:11-19` → `display_email.html.twig` | `as_string`, `subject`, `body` | `<a href="mailto:x?subject=..&body=..">x</a>` (`display_email.html.twig:12-31`) | `a.text-brand-500.hover:underline` | |
| `enum` | `show_enum.html.twig:11-20` → `display_enum.html.twig` | `use_value`, `enum_translation_domain` | `value|trans` if `TranslatableInterface`, else `value.value` or `value.name` (translated if domain) (`display_enum.html.twig:13-27`) | text; optional light badge | |
| `trans` | `show_trans.html.twig:11-21` → `display_trans.html.twig` | `format` (`value_format`, default `%s`), `value_translation_domain` (fallback `catalogue`, then `admin.translationDomain`), `safe` | `value_format|format(value)|trans({}, domain)` (`display_trans.html.twig:13-20`) | text | NEXT_MAJOR comment: `catalogue` fallback |
| `currency` | `show_currency.html.twig:11-19` → `display_currency.html.twig` | `currency` | `{{ currency }} {{ value }}` or `&nbsp;` if null (`display_currency.html.twig:13-17`) | text, `tabular-nums` | **no Intl**; test pins `EUR 10.746135` (`RenderElementRuntimeTest.php:1538-1540`) |
| `percent` | `show_percent.html.twig:11-16` → `display_percent.html.twig` | – | `{{ value * 100 }} %` (`display_percent.html.twig:13-18`) | text | test pins `1074.6135 %` and `0 %` |
| `choice` | `show_choice.html.twig:11-23` → `display_choice.html.twig` | `choices`, `multiple`, `delimiter` (default `, `), `choice_translation_domain` (fallback `catalogue`), `safe` | maps value(s) through `choices`, translates if domain, joins with delimiter (`display_choice.html.twig:13-49`) | text (multiple could become a badge list but string output is pinned by tests) | |
| `url` | `show_url.html.twig:11-29` → `display_url.html.twig` | `url`, `route` (`name`, `parameters`, `absolute`, `identifier_parameter_name` → injects `admin.normalizedidentifier(object)`), `hide_protocol`, `attributes` (escaped `html_attr`), `safe` | `<a href="…" attr=…>value</a>`; route names `edit`/`show` are ignored (value used as href) (`display_url.html.twig:13-50`) | `a.text-brand-500.hover:underline` (+ external-link SVG optional) | |
| `html` | `show_html.html.twig:11-19` → `display_html.html.twig` | `truncate` (`length` 30, `cut` true, `ellipsis` `...`), `strip` | `value|striptags|u.truncate(...)` or `value|raw` (`display_html.html.twig:13-28`) | wrap in `div.prose.prose-sm.dark:prose-invert.max-w-none` (needs `@tailwindcss/typography`; optional) | requires `twig/string-extra` |
| `many_to_many` | `Association/show_many_to_many.html.twig:11-33` | `route.name` (default `sonata_config.getOption('default_admin_route')` = `show`), `route.parameters`, `associated_property` (via `render_relation_element`) | `<ul class="sonata-ba-show-many-to-many"><li><a href=…>label</a></li>` (link only if association admin has route + access) | `ul.flex.flex-wrap.gap-2 > li > a.badge-light` or plain list; keep `sonata-ba-show-many-to-many` class | `render_relation_element` throws if no `__toString` and no `associated_property` (`RenderElementRuntime.php:129-160`) |
| `one_to_many` | `Association/show_one_to_many.html.twig:11-33` | same | `<ul class="sonata-ba-show-one-to-many">` | same | |
| `many_to_one` | `Association/show_many_to_one.html.twig:11-28` | same | `<a href>label</a>` or plain text | `a.text-brand-500` | |
| `one_to_one` | `Association/show_one_to_one.html.twig:11-31` | same + `associationadmin.id(value) is not null` guard | same as many_to_one | same | |

`display_*.html.twig` files are wrapped in `{%- apply trim|raw %}…{% endapply -%}` (e.g. `display_boolean.html.twig:12,26`) so their output is whitespace-trimmed and marked safe; list templates include the *same* helpers (`admin/src/Resources/views/CRUD/list_boolean.html.twig:24-29`), so any change here is shared with the list dimension.

### 1.6 Icon strategy for this dimension

Icons used (all FA5 solid):

| Where | Icon | File:line |
|---|---|---|
| Button/acl_button | `fas fa-users` | `admin/src/Resources/views/Button/acl_button.html.twig:20` |
| Button/create_button | `fas fa-plus-circle` | `create_button.html.twig:16,24` |
| Button/edit_button | `fas fa-edit` | `edit_button.html.twig:19` |
| Button/history_button | `fas fa-archive` | `history_button.html.twig:19` |
| Button/list_button | `fas fa-list` | `list_button.html.twig:15` |
| Button/show_button | `fas fa-eye` | `show_button.html.twig:20` |
| delete.html.twig | `fas fa-trash-alt`, `fas fa-pencil-alt` | `delete.html.twig:40,45` |
| batch_confirmation | `fas fa-list` | `batch_confirmation.html.twig:59` |
| preview.html.twig | `fas fa-check`, `fas fa-times` | `preview.html.twig:22,26` |
| base_edit_form btn_preview | (icon inside `btn_preview` block) | `base_edit_form.html.twig:120-125` |
| dashboard actions (PHP) | `'fas fa-plus-circle'`, `'fas fa-list'` via `parse_icon` | `admin/src/Admin/AbstractAdmin.php:1795-1815` |
| ORM audit block | `fa fa-history` (FA4-style class!) | `orm/src/Resources/views/Block/block_audit.html.twig:17` |
| layout actions dropdown | `<b class="caret">` | `standard_layout.html.twig:270` |

`parse_icon` contract (`admin/src/Twig/IconRuntime.php:20-38`): returns the string as-is if it starts with `<`, wraps FA prefixes in `<i class="…" aria-hidden="true"></i>`, throws otherwise. User code passes strings like `'fas fa-magic'` (`admin/docs/reference/dashboard.rst:271,317`) and (docs, arguably a bug) `'import'` / `'level-up-alt'` (`dashboard.rst:381`, `recipe_custom_action.rst:307`) — the latter would throw today.

Recommendation:

* Extend `IconRuntime::parseIcon()` (same service id, same filter name) to (1) pass through `<svg…>`/`<i…>`, (2) look up a map `fa(s)? fa-<name>` → inline SVG (TailAdmin icons: `ta-react/src/icons/*.svg`, `ta-next/src/icons/*.svg`), (3) fall back to the FA `<i>` markup for unknown names. Ship FontAwesome 5 CSS as an *optional* asset (config flag) so unknown icons still render for users who enable it.
* Replace hard-coded `<i class="fas fa-…">` in the bundle templates with `{{ 'fas fa-…'|parse_icon }}` so the mapping is centralised (docs and user templates keep working either way).
* Never rely on `.caret`; use TailAdmin's chevron SVG (`ta-html/src/partials/header.html:622` rotates it with `:class="dropdownOpen && 'rotate-180'"`).

---

## 2. History and revisions

### 2.1 History page

`base_history.html.twig` (file lines: printed+10):

```
extends base_template                                                   :12
block actions → include action_buttons                                   :14-16
block content                                                            :18-57
  div.row[stimulus_controller('sonata-revision')]                        :19
    div.col-md-5 > div.box.box-primary > div.box-body.table-responsive.no-padding :20-22
      table.table#revisions                                              :23
        thead: td_revision | td_timestamp | td_username | td_action | td_compare :24-32
        tbody: for revision in revisions
          tr(.current-revision if revision.rev == currentRevision.rev)   :35
            td revision.rev                                              :36
            td include get_admin_template('history_revision_timestamp', admin.code) :37
            td revision.username|default('label_unknown_user'|trans)     :38
            td a.revision-link[href=history_view_revision url][rel=rev][stimulus_action showPreview:prevent:stop] 'label_view_revision' :39
            td '/' if current, else a.revision-compare-link[href=history_compare_revisions(baseRevision=current.rev, compareRevision=rev)][stimulus_action showPreview:prevent:stop] 'label_compare_revision' :40-46
    div#revision-detail.col-md-7.revision-detail[stimulus_target('sonata-revision','preview')] :54
```

Data: `revisions` = `AuditReaderInterface::findRevisions()` result (objects with `rev`, `timestamp`, `username`), `currentRevision = current($revisions)` (may be `false` when empty — template compares `currentRevision != false`) (`CRUDController.php:676-712`, test `CRUDControllerTest.php:3192-3195`).

`revision_controller.js` (`admin/assets/js/controllers/revision_controller.js:12-35`): on click, clears `previewTarget`, `fetch(link.href, {headers: {'X-Requested-With': 'XMLHttpRequest'}})`, injects `response.text()` as `innerHTML`. Because the request is XHR, `CRUDController::setTwigGlobals` picks `ajax_layout.html.twig`, so `show.html.twig`/`show_compare.html.twig` render only their `show` block into the right column (`CRUDController.php:958-968`, `ajax_layout.html.twig:20`).

Preserve: `data-controller="sonata-revision"`, `data-sonata-revision-target="preview"`, `data-action="click->sonata-revision#showPreview:prevent:stop"`, the `#revisions`/`#revision-detail` ids, `revision-link`/`revision-compare-link`/`current-revision`/`revision-detail` classes (users hook CSS/JS on them), the `rel="{{ revision.rev }}"` attribute, `history_revision_timestamp` registry key (overridden to `@SonataIntl/CRUD/history_revision_timestamp.html.twig` when SonataIntlBundle is present, `admin/src/DependencyInjection/SonataAdminExtension.php:58-65`).

TailAdmin mapping:

| Sonata | Tailwind/TailAdmin |
|---|---|
| `div.row > col-md-5 + col-md-7` | `div.grid.grid-cols-1.gap-6.lg:grid-cols-12` with `lg:col-span-5` / `lg:col-span-7` (stack on mobile) |
| `box box-primary` | card (`rounded-2xl border … bg-white dark:bg-white/[0.03]`) with header "Revisions" |
| `table.table#revisions` | `table.min-w-full` + `thead tr.border-y.border-gray-100.dark:border-gray-800 th.py-3.text-theme-xs.font-medium.text-gray-500` + `tbody.divide-y` (`ta-html/src/partials/table/table-01.html:65-109`) |
| `tr.current-revision` | keep class + `bg-brand-50 dark:bg-brand-500/10` |
| `a.revision-link` / `.revision-compare-link` | `text-sm font-medium text-brand-500 hover:underline`; add `aria-current`/active state via Alpine (`x-data="{active:null}"` on the row container) to highlight the loaded revision — a UX improvement not present today |
| `#revision-detail` empty column | card with placeholder text `text-gray-500` until loaded; the injected content is the show `sonata-ba-view` markup, so its cards nest inside — render the injected fragment without an outer card, or give `.revision-detail .sonata-ba-view` a flat style |
| loading state | add a spinner via Alpine `x-show="loading"`? The Stimulus controller has no hook — either extend `revision_controller.js` to toggle a `loading` class on the element or leave as-is. Keep the controller identifier `sonata-revision` |

### 2.2 Compare view (`show_compare` → `base_show_compare.html.twig`)

`base_show_compare.html.twig:12-20` extends `base_show.html.twig` and overrides only `block show_field`: `tr.sonata-ba-view-container.history-audit-compare` containing `render_view_element_compare(object, object_compare)`. The compare row = `<th class="diff">` (when `is_diff`) + `<td>base</td><td>compare</td>` from `base_show_field.html.twig:48-54`.

Stock CSS: `.history-audit-compare th {width:10%} th.diff {background: pink} td {width:40%}` (`admin/assets/scss/layout.scss:265-276`). Tests pin `<th class="diff">Data</th> <td>…</td><td>…</td>` (`RenderElementRuntimeTest.php:2005`).

TailAdmin mapping: keep the 3-column table; header row (add one — stock has none) with "Field / Revision N / Revision M" using `text-theme-xs text-gray-500`; `th.diff` → keep class and style as `bg-warning-50 dark:bg-warning-500/15` with a left border `border-l-4 border-warning-500`; diff cells `td` widths `w-2/5` each. Optional enhancement (no API change): add class `diff` on the two `td`s as well so the whole row can be highlighted — but `field_compare` only marks `th`; adding `td.diff` requires editing `base_show_field.html.twig` `field_compare` block, safe because tests only pin `th`. A proper word-level diff (`<ins>/<del>`) would need a PHP diff lib — out of scope; note as optional.

`show_compare.html.twig:12` is a pure `extends`.

### 2.3 ORM audit dashboard block (`@SonataDoctrineORMAdmin/Block/block_audit.html.twig`)

Lives in the ORM bundle, not in adminata (`orm/src/Block/AuditBlockService.php:61`). Markup: `div.box.box-primary > div.box-header.with-border > h3.box-title > i.fa.fa-history` + `div.box-body > div.panel-group#accordion > div.panel.panel-default > div.panel-heading > h4.panel-title > a[data-toggle=collapse][data-parent=#accordion][href=#collapseN]` + `div#collapseN.panel-collapse.collapse(.in)` + `ul>li entity / revisionType / className - id` (`block_audit.html.twig:14-49`). It extends `@SonataBlock/Block/block_base.html.twig` which is just `div#cms-block-{{id}}.cms-block.cms-block-element` (`block/src/Resources/views/Block/block_base.html.twig:12-14`).

adminata cannot rewrite it in place; options: (a) ship an override at `templates/bundles/SonataDoctrineORMAdminBundle/Block/block_audit.html.twig`-equivalent inside adminata via a Twig namespace path override (`@SonataDoctrineORMAdmin` namespace registered by the ORM bundle; adminata can add a path to that namespace with higher priority via `twig.paths` in its prepend extension), or (b) include the AdminLTE compat CSS for `.box`, `.panel-group`, `.panel-collapse.collapse.in` and an Alpine polyfill for `data-toggle="collapse"`. Recommend (a) with an Alpine accordion (`x-data="{open: 1}"`, `x-show="open === N"`) and the `.box` compat CSS as fallback for (b). Same choice applies to the Mongo fork (no audit block there — `mongo/src/Resources/views` has only form themes).

---

## 3. ACL editor

### 3.1 Data and forms

* `aclAction` (`CRUDController.php:865-940`): 404 unless `admin.isAclEnabled()`; builds `AdminObjectAclData(admin, object, aclUsers, maskBuilderClass, aclRoles)`; two forms named `acl_users_form` and `acl_roles_form` (`AdminObjectAclManipulator::ACL_USERS_FORM_NAME/ACL_ROLES_FORM_NAME`, `admin/src/Util/AdminObjectAclManipulator.php:37-38`); on POST the presence of either form name in the request chooses which to handle; success → flash `flash_acl_edit_success` + redirect to the `acl` URL. View vars: `action='acl'`, `permissions` (= `getUserPermissions()`), `object`, `users`, `roles`, `aclUsersForm`, `aclRolesForm` (`CRUDController.php:930-939`; test `CRUDControllerTest.php:3323-3328`).
* `getAclUsers()` returns `[]` unless `sonata.admin.security.acl_user_manager` exists; `getAclRoles()` derives roles from every admin's security information + `security.role_hierarchy.roles` (`CRUDController.php:1215-1260`). Both are `protected` and documented as overridable (`admin/docs/reference/security.rst:681-716`).
* Permissions: `getPermissions()` = security handler `getObjectPermissions()` (config `object_permissions`, default `[VIEW, EDIT, HISTORY, DELETE, UNDELETE, OPERATOR, MASTER, OWNER]`, `security.rst:97`); `MASTER` and `OWNER` are removed unless the current user `isGranted('OWNER', object)` (`admin/src/Util/AdminObjectAclData.php:32,159-172,186-190`).
* Each row is an `AclMatrixType` (block prefix `sonata_type_acl_matrix`, `admin/src/Form/Type/AclMatrixType.php:34-58`): one `HiddenType` child named `user` (for `UserInterface`, data = `getUserIdentifier()`) or `role` (string), plus one `CheckboxType` per permission with `required=false`, `data=checked`, `disabled` when the role already has the permission through the admin's security information (`AdminObjectAclManipulator.php:174-226`, `disabled` at 199-213).
* `AdminAclManipulator` (`admin/src/Util/AdminAclManipulator.php`) is the CLI `sonata:admin:setup-acl` helper — no UI impact.

### 3.2 Markup (`base_acl.html.twig`, `base_acl_macro.html.twig`; file lines = printed+10)

```
base_acl: extends base_template :12; block actions :14-16; import macro :18
  block form :20-27
    block form_acl_roles → acl.render_form(aclRolesForm, permissions, 'td_role', admin, sonata_config, object) :21-23
    block form_acl_users → acl.render_form(aclUsersForm, permissions, 'td_username', …) :24-26
macro render_form(form, permissions, td_type, admin, admin_configuration, object) :12
  form.form-horizontal[action=admin.generateUrl('acl', {id, uniqid, subclass})][enctype if multipart][method=POST][novalidate unless html5_validate] :13-18
    include Helper/render_form_dismissable_errors.html.twig :20
    div.box.box-success > div.body.table-responsive.no-padding > table.table :22-24
      colgroup: col[width:100%] + one col per permission :25-30
      for child in form.children|filter(name != '_token'):
        every 10 rows (loop.index0 % 10 == 0) a header tr: th td_type|trans + th.text-right per permission :32-40
        tr > td {{ typeChild.vars.value }} {{ form_widget(typeChild) }} (hidden input) :42-47
             td.text-right {{ form_widget(child[permission], {label:false}) }} per permission :48-50
    {{ form_row(form._token) }} :57
    div.well.well-small.form-actions > input.btn.btn-primary[type=submit][name=btn_create_and_edit][value='btn_update_acl'|trans] :59-61
```

Note `acl.html.twig:12` is a pure `extends`. Note the `layout` places the `form` block in `div.sonata-ba-form` (`standard_layout.html.twig:312-314`).

The checkbox widget comes from the admin form theme: `checkbox_widget` wraps `form_label(form, null, {widget: parent()})` in `div.checkbox` (`admin/src/Resources/views/Form/form_admin_fields.html.twig:76-85`); with `label: false` Symfony still renders the `<label>` wrapper around the input. Then `Admin.setup_icheck()` converts every `input[type=checkbox]:not([data-sonata-icheck="false"])` into an iCheck `icheckbox_square-blue` widget when `sonata_admin.options.use_icheck` is true (`admin/assets/js/admin.js:111-130`, config `Configuration.php:313`, body class `sonata-icheck`, `standard_layout.html.twig:95`, meta `USE_ICHECK` `standard_layout.html.twig:37-45`).

### 3.3 TailAdmin mapping

| Sonata | Tailwind/TailAdmin | Notes |
|---|---|---|
| two stacked forms (roles, users) | two cards, each with header "Roles" / "Users" (`td_role` → "Role", `td_username`) and an `overflow-x-auto` table body | keep block names `form`, `form_acl_roles`, `form_acl_users` and the macro signature `render_form(form, permissions, td_type, admin, admin_configuration, object)` (users import the macro) |
| `form.form-horizontal` | `form.space-y-6`; keep `action`, `method`, `novalidate` logic verbatim | `html5_validate` option must still be honoured |
| `box box-success` | card; optionally a `border-t-4 border-success-500` accent to keep the "success" semantic | |
| `colgroup` + repeated header every 10 rows | keep (it is a usability feature for long user lists); style header `tr.border-y.border-gray-100.bg-gray-50.dark:bg-gray-900` and make it `sticky top-0` inside a `max-h-[70vh] overflow-y-auto` wrapper instead — then the every-10-rows repeat can stay or go (keep for parity) | |
| first column `td` with `typeChild.vars.value` + hidden widget | `td.py-3.px-5.text-theme-sm.font-medium.text-gray-800.dark:text-white/90.whitespace-nowrap` | hidden input untouched |
| permission `td.text-right` + checkbox | `td.text-center` with a native checkbox styled TailAdmin-style: `input[type=checkbox].h-5.w-5.appearance-none.rounded-md.border.border-gray-300.checked:border-transparent.checked:bg-brand-500.disabled:opacity-60.dark:border-gray-700` + CSS `checked` tick via background SVG (pattern from `ta-next/src/components/form/input/Checkbox.tsx:27-35`). The HTML template's Alpine-driven checkbox (`ta-html/src/form-elements.html:1052-1089`, `sr-only` input + fake box) is **not** suitable for a Symfony form because the visual state would desync on form re-render; use the appearance-none native approach (works with `disabled`, keyboard, no JS) | `text-right` → `text-center` is a visual change; keep `text-right` class as hook if desired |
| iCheck | drop entirely; `use_icheck` config key must remain accepted (no-op) for drop-in; keep the `data-sonata-icheck` attribute contract ignored | coordinate with the forms dimension: `checkbox_widget` block in the form theme decides the final markup |
| `form_row(form._token)` | unchanged | |
| `div.well.well-small.form-actions > input.btn.btn-primary` | `div.flex.justify-end.gap-3.border-t.border-gray-100.px-5.py-4 > button.inline-flex.items-center.gap-2.rounded-lg.bg-brand-500.px-4.py-3.text-sm.font-medium.text-white.shadow-theme-xs.hover:bg-brand-600` (`ta-html/src/partials/buttons/button-01.html:2-6`) | keep `name="btn_create_and_edit"` (harmless but present) and value text `btn_update_acl` |
| `render_form_dismissable_errors.html.twig` (`div.alert.alert-danger.alert-dismissable > button.close[data-dismiss=alert]`) (`admin/src/Resources/views/Helper/render_form_dismissable_errors.html.twig:1-6`) | TailAdmin error alert `div.rounded-xl.border.border-error-500.bg-error-50.p-4.dark:border-error-500/30.dark:bg-error-500/15` with icon + `h4` + `p` (`ta-html/src/partials/alert/alert-error.html:1-41`), dismiss via `x-data="{show:true}" x-show="show"` + close button | shared with edit form (`base_edit_form.html.twig:30`) and batch/delete; keep the file name |
| "OWNER"/"MASTER" columns hidden for non-owners | unchanged (PHP) | add a hint row? optional |
| add-row UX: none exists (users list is fixed) | keep | |

Accessibility improvement worth doing while here: give each checkbox an `aria-label="{{ permission }} – {{ typeChild.vars.value }}"` (currently `label: false` yields an empty label).

---

## 4. Delete confirmation and batch confirmation

### 4.1 `delete.html.twig` (printed+10)

```
extends base_template :12; block actions :14-16; block tab_menu (knp sidemenu) :18-23
block content :25-51
  div.sonata-ba-delete > div.box.box-danger :26-28
    div.box-header > h3.box-title 'title_delete' ("Confirm deletion") :29-31
    div.box-body 'message_delete_confirmation' with %object% = admin.toString(object) :32-34
    div.box-footer.clearfix > form[method=POST][action=admin.generateObjectUrl('delete', object)] :35-36
      input[hidden name=_method value=DELETE], input[hidden name=_sonata_csrf_token value=csrf_token] :37-38
      button.btn.btn-danger[type=submit name=btn_delete] fa-trash-alt 'btn_delete' ("Yes, delete") :40
      if edit route+access: 'delete_or' ("or") + a.btn.btn-success[href=edit] fa-pencil-alt 'link_action_edit' :41-47
```

Controller: GET renders; POST/DELETE validates `sonata.delete` CSRF, deletes, flashes, `redirectTo`; XHR returns `{"result":"ok"}` / `{"result":"error"}` (`CRUDController.php:187-270`). View vars: `object`, `action='delete'`, `csrf_token` (may be `null` when CSRF is disabled — test `CRUDControllerTest.php:1138-1140`; template must not choke on `null`).

### 4.2 `batch_confirmation.html.twig` (printed+10)

```
extends base_template :12; block actions :14-16; block tab_menu :18-23
block content :25-66
  div.sonata-ba-delete > div.box.box-danger :26-27
    div.box-header > h4.box-title 'title_batch_confirmation' %action% = action_label (translated with batch_translation_domain unless false) :28-32
    div.box-body: 'message_batch_all_confirmation' if data.all_elements else 'message_batch_confirmation' %count% = data.idx|length :34-39
    div.box-footer.clearfix > form[action=admin.generateUrl('batch', {filter: admin.filterParameters})][method=POST] :41-42
      hidden confirmation=ok, data={{ data|json_encode }}, _sonata_csrf_token :43-45
      div[style=display:none] {{ form_rest(form) }} (the datagrid filter form, so filters survive the round-trip) :47-49
      button.btn.btn-danger[type=submit] 'btn_execute_batch_action' ("Yes, execute") :51-53
      if list route+access: 'delete_or' + a.btn.btn-success[href=list] fa-list 'link_action_list' :55-61
```

Controller: `batchAction` renders this when `ask_confirmation` (default true) and `confirmation != 'ok'`, with vars `action='list'`, `action_label`, `batch_translation_domain`, `datagrid`, `form` (filter form view with filter theme applied), `data`, `csrf_token` (`CRUDController.php:479-500`). Per-batch-action `template` override is supported (`batchAction['template']`, `docs/reference/batch_actions.rst:169-170`).

### 4.3 TailAdmin mapping

Both pages are full-page confirmations reached by a GET route (delete) or a POST round-trip (batch). They must stay pages (no JS dependency; XHR delete is also used by the list's inline delete). A modal is a *list-page* concern (client-side confirm before POSTing) — noted for the list dimension; it does not replace these templates.

| Sonata | Tailwind/TailAdmin |
|---|---|
| `div.sonata-ba-delete > box box-danger` | keep `sonata-ba-delete` wrapper; card `max-w-2xl` with an error accent: `rounded-2xl border border-error-200 bg-white dark:border-error-500/30 dark:bg-white/[0.03]`; header row with an error icon circle (`ta-html/src/partials/alert/alert-error.html:5-21` SVG, `text-error-500`) and `h3.text-lg.font-semibold` |
| `box-body` message | `p.text-sm.text-gray-500.dark:text-gray-400` — message contains the object name (already escaped by `trans` param interpolation? no: `admin.toString(object)` is passed as a parameter and output escaped by Twig autoescape since `trans` result is escaped) |
| `box-footer > form` | `div.flex.flex-wrap.items-center.gap-3.border-t.border-gray-100.px-5.py-4.dark:border-gray-800` |
| `button.btn.btn-danger` | `button.inline-flex.items-center.gap-2.rounded-lg.bg-error-500.px-4.py-3.text-sm.font-medium.text-white.shadow-theme-xs.hover:bg-error-600` (derive from `button-01.html:3` with error tokens) + trash SVG |
| "or" text | `span.text-sm.text-gray-500` |
| `a.btn.btn-success` (edit / list) | outline button `inline-flex … rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700` (`ta-html/src/partials/buttons/button-05.html:3`) |
| hidden `form_rest(form)` | keep `hidden` attribute (`<div hidden>`) |
| `name="btn_delete"` | keep (some controllers/tests check it) |

---

## 5. Preview mode

Flow: `supportsPreviewMode` (`AbstractAdmin::supportsPreviewMode()`, `AbstractAdmin.php:1670`) adds a `btn_preview` submit button in the edit form (`base_edit_form.html.twig:120-125`, class `btn btn-info persist-preview`). `createAction`/`editAction` switch `$templateKey = 'preview'` when the form is valid and `btn_preview` is in the request (`CRUDController.php:299-300,355-357,568-569,618-620`); approve/decline are detected by `btn_preview_approve` / `btn_preview_decline` request keys (`CRUDController.php:1177-1210`).

`preview.html.twig` (printed+10):

```
extends '@SonataAdmin/CRUD/edit.html.twig' :12
block actions → empty :14-15
block side_menu → empty :17-18      (note: base layout has no `side_menu` block; it is dead — `tab_menu` is what edit uses)
block formactions → button.btn.btn-success[type=submit name=btn_preview_approve] fa-check 'btn_preview_approve' ("Approve") + button.btn.btn-danger[name=btn_preview_decline] fa-times 'btn_preview_decline' ("Decline") :20-29
block preview :31-53
  div.sonata-ba-view
    for name, view_group in admin.showgroups:
      table.table.table-bordered
        tr.sonata-ba-view-title > td[colspan=2] name|trans(admin.translationdomain) if name :36-41
        for show_field_name in view_group.fields: tr.sonata-ba-view-container > admin.show[show_field_name]|render_view_element(object) :43-49
block form → div.sonata-preview-form-container > parent() :55-59
```

Layout: `preview` block is emitted first as `div.sonata-ba-preview` then `form` as `div.sonata-ba-form` (`standard_layout.html.twig:300-314`). CSS hides the form body: `.sonata-preview-form-container fieldset, .sonata-preview-form-container .tabbable {display:none}` (`layout.scss:353-356`) — so only the hidden inputs and the `formactions` buttons remain visible. Docs tell users to hide `.sonata-preview-form-container .row` themselves in custom layouts (`preview_mode.rst:128-146`) and to override `formactions` (`preview_mode.rst:117-126`).

Observations: the preview block iterates `admin.showgroups` directly (ignores tabs, ignores `box_class`, uses `name|trans` as title rather than `label`) — it is a simpler, older rendering. `tr.sonata-ba-view-title` styled at `layout.scss:242-252`.

TailAdmin mapping: keep block names `preview`, `form`, `formactions`, `actions`; render `preview` as the same card-per-group layout as §1 (reuse `show_groups` markup via an include of a shared partial — but be careful: `preview` extends `edit`, not `base_show`, so it cannot call `block('show_groups')`; factor the group card into `@SonataAdmin/CRUD/_show_group.html.twig`? That is a new file — allowed, but keep the old inline markup structure available for people who override `preview`). Keep `.sonata-ba-preview`, `.sonata-ba-view`, `.sonata-ba-view-title`, `.sonata-preview-form-container` classes and re-implement the hide rule in Tailwind components CSS — importantly the selector must hide whatever wrapper the new edit form uses for its fieldsets/tabs (coordinate with the form dimension; the safest is to give the form body a stable class like `sonata-ba-form-body` and hide that). Buttons: approve = success solid (`bg-success-500 hover:bg-success-600`), decline = error solid, both `inline-flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-theme-xs`.

---

## 6. Action buttons system

### 6.1 Page-header buttons

* `AbstractAdmin::getActionButtons($action, $object)` = defaults → `configureActionButtons()` (protected hook, `AbstractAdmin.php:2125`) → each extension's `configureActionButtons($admin, $list, $action, $object)` (`admin/src/Admin/AdminExtensionInterface.php:205-210`) (`AbstractAdmin.php:1773-1783`).
* Defaults (`AbstractAdmin.php:2325-2404`) keyed `create`, `edit`, `history`, `acl`, `show`, `list`, each `['template' => registry 'button_<x>']`, gated by bitmasks per current action (`INTERNAL_ACTIONS` `AbstractAdmin.php:106-115`; masks 116-122): e.g. on `show` you get edit+history+acl+list(+create); on `list` you get create only; `history` shows show+edit+acl+list; `acl` shows edit+history+list. Non-internal (custom) actions get an empty default list.
* `CRUD/action_buttons.html.twig:11-15` loops `admin.getActionButtons(action, object ?? null)` and `include item.template` when `template` is defined (entries without `template` are silently skipped).
* Button templates emit `<li><a class="sonata-action-element" href><i class="fas fa-…"></i> label</a></li>` with access/route guards (`Button/*.html.twig`, cited in §1.6 table; `show_button` additionally requires `admin.show|length > 0`).
* Layout (`standard_layout.html.twig:265-280`): if `_actions|replace({'<li>':'','</li>':''})|trim` not empty → `ul.nav.navbar-nav.navbar-right`; if `_actions|split('</a>')|length > 2` (i.e. two or more `<a>`) → `li.dropdown.sonata-actions > a.dropdown-toggle[data-toggle=dropdown] 'link_actions' ("Actions") + b.caret > ul.dropdown-menu[role=menu] {{ _actions|raw }}`; else `{{ _actions|raw }}` inline. CSS: `.sonata-actions{float:right}` (`layout.scss:69-71`), `.sonata-action-element.btn-group{display:inline-block;padding:4px 10px}` (`layout.scss:303-307`) — the latter supports user templates that emit a `div.btn-group` instead of `li > a`.
* The same navbar also hosts the list-mode switcher (`standard_layout.html.twig:249-263`, list dimension) and `_list_filters_actions`.

Compatibility contract to keep: user button templates (docs `recipe_custom_action.rst:274-278`) produce `<li><a class="sonata-action-element" href="…"><i class="fas fa-level-up-alt"></i> label</a></li>`. The wrapper must therefore remain a `<ul>` and accept arbitrary `<li>` children, and the "more than one → dropdown" heuristic should be preserved (it is behaviour people rely on: one action renders as a button, several collapse into "Actions"). Suggested Tailwind implementation of `sonata_admin_content_actions_wrappers`:

* Inline case: `<ul class="flex items-center gap-2">` and style children with descendant selectors in the components layer: `.sonata-page-actions > li > a { @apply inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]; }` (from `ta-html/src/partials/table/table-01.html:15`). Descendant styling is the only way to style user-supplied `<a>` markup without touching it.
* Dropdown case: Alpine `x-data="{open:false}" @click.outside="open=false"` on `li.dropdown.sonata-actions`; trigger button = outline button + chevron SVG with `:class="open && 'rotate-180'"`; menu `ul.dropdown-menu` → `absolute right-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark` (`ta-react/src/components/ui/dropdown/Dropdown.tsx:41`), items styled via descendant selector `.sonata-actions .dropdown-menu > li > a { @apply flex items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5 }` (TailAdmin `menu-dropdown-item` utility, `ta-html/src/css/style.css:222-232`). Keep `x-show` + `x-transition` from `header.html:220`.
* Because user templates may also drop a raw `div.btn-group` (that is why `.sonata-action-element.btn-group` CSS exists), include `.btn-group`/`.btn` in the Bootstrap compat layer (§6.3).
* `<b class="caret">` removed; `link_actions` label retained.

Also touched: `Button/*` icons via `parse_icon` (§1.6), `create_button` with subclasses renders one `<li>` per subclass (`create_button.html.twig:20-29`) — fine.

### 6.2 Dashboard actions

* `AbstractAdmin::getDashboardActions()` (`AbstractAdmin.php:1790-1825`): `create` (label `link_add`, domain `SonataAdminBundle`, template registry `action_create`, url, icon `fas fa-plus-circle`) if route+access; `list` (label `link_list`, url, icon `fas fa-list`, **no template**); then `configureDashboardActions()` + extensions' `configureDashboardActions()`.
* Rendered by `Block/block_admin_list.html.twig` (registry key `list_block`): per group `div.box > box-header h3.box-title (label|trans) > box-body > table.table.table-hover > tr > td.sonata-ba-list-label[width=40%] admin label + td > div.btn-group > for action: include action.template|default('@SonataAdmin/CRUD/dashboard__action.html.twig') with {action}` (`block_admin_list.html.twig:19-57`; note `label_catalogue` NEXT_MAJOR fallback at 26-28).
* `dashboard__action.html.twig:1-8`: `a.btn.btn-link.btn-flat[href=action.url] {{ action.icon|parse_icon }} label|trans(action.translation_domain|default('SonataAdminBundle'))` (raw label when `translation_domain === false`).
* `dashboard__action_create.html.twig:1-33`: same when no subclasses; with subclasses `a.btn.btn-link.btn-flat.dropdown-toggle[data-toggle=dropdown] icon label caret` + `ul.dropdown-menu > li > a[href=create?subclass=X] subclass|trans`.
* Docs show user-supplied dashboard templates emitting `<a class="btn btn-link btn-flat">` (`recipe_custom_action.rst:295-297`) and the array form with `icon`, `template` keys (`dashboard.rst:376-386`, `recipe_custom_action.rst:301-311`). Functional test `DashboardActionTest` only requests `/admin/dashboard` (`admin/tests/Functional/Controller/DashboardActionTest.php:32`).

TailAdmin mapping: dashboard block table → card with `table.min-w-full` / `divide-y` rows; `td.sonata-ba-list-label` → `td.py-3.px-5.text-theme-sm.font-medium.text-gray-800.dark:text-white/90`; `div.btn-group` → `div.flex.flex-wrap.items-center.gap-2` (keep `btn-group` class); each action link → ghost button `inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-theme-sm font-medium text-brand-500 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/[0.12]` (from `menu-item-active` tokens, `style.css:194-196`); keep `btn btn-link btn-flat` as hook classes and style them through the compat layer so user templates look identical. Subclass dropdown → Alpine dropdown as in §6.1; since the wrapper `li` for the dropdown does not exist here (the `<a>` and `<ul>` are siblings inside `div.btn-group`), wrap both in `div.relative[x-data]` — this changes the DOM slightly but only inside adminata's own template.

### 6.3 Bootstrap compat layer (cross-cutting recommendation)

Because the documented extension points hand adminata **raw Bootstrap-3-classed HTML** (`btn btn-link btn-flat`, `btn btn-sm btn-default`, `btn-group`, `col-md-8`, `box box-solid box-danger`, `label label-success`, `alert alert-danger`, `nav-tabs`, `dropdown-menu`), ship an opt-out `bootstrap-compat.css` in `@layer components` that maps this closed vocabulary onto TailAdmin tokens. It is the single most effective drop-in measure in this dimension and should be listed in the plan as its own deliverable with an explicit class inventory (this report's §8.2 list is the starting point).

---

## 7. Misc helpers

### 7.1 `Helper/short-object-description.html.twig`

Rendered by `GetShortObjectDescriptionAction` for `_format=html` with `admin`, `description` (`admin.toString(object)`), `object`, `link_parameters` (`admin/src/Action/GetShortObjectDescriptionAction.php:74-82`). Markup `span.inner-field-short-description > a[href=edit url][target=new] description` or plain text (`short-object-description.html.twig:1-7`). It is injected by the model-list/autocomplete form widgets (form dimension), so styling lives with them: `span.inline-flex.items-center.gap-1.rounded-lg.border.border-gray-200.bg-gray-50.px-3.py-1.5.text-theme-sm.dark:border-gray-800.dark:bg-white/[0.03]`; keep `inner-field-short-description` (JS in form widgets targets it) and `target="new"`. Stock CSS for the sibling `.field-short-description` box is at `layout.scss:358-370`.

### 7.2 `CRUD/action.html.twig`

Generic custom-action page: extends base, `actions` include, `tab_menu` guarded by `action is defined`, `content` placeholder text (`action.html.twig:12-31`). No markup to migrate; keep block names.

### 7.3 `history_revision_timestamp.html.twig`

`include display_datetime with {value: revision.timestamp} only` (`:12-14`); registry key overridden by SonataIntlBundle. Unchanged.

### 7.4 Export

`exportAction` has no template (`CRUDController.php:826-862`); the export UI is the list page's format links (list dimension). Nothing here.

### 7.5 Flash messages

`standard_layout.html.twig:296-298` includes `@SonataTwig/FlashMessage/render.html.twig`, which emits Bootstrap `div.alert.alert-{type}.alert-dismissable > button.close[data-dismiss=alert]` and a "collapsed" variant with a `.read-more-state` checkbox (`twig-ext/src/Bridge/Symfony/Resources/views/FlashMessage/render.html.twig:12-58`). Sonata's `base.js` destroys iCheck on `.read-more-state` (`admin/assets/js/base.js:11-12`). adminata should override this template in its own `templates/bundles/SonataTwigBundle/…` path (or via the `notice` block) with TailAdmin alerts (`ta-next/src/components/ui/alert/Alert.tsx:22-40` variant map: success/error/warning/info) — the delete/ACL success flashes land here. Belongs to the layout dimension but is the visible outcome of every action in this one.

---

## 8. Compatibility inventory to preserve

### 8.1 Template registry keys (config `sonata_admin.templates.*`, `Configuration.php:567-607`)

`show`, `show_compare`, `preview`, `history`, `acl`, `history_revision_timestamp`, `action`, `short_object_description`, `delete`, `batch_confirmation`, `base_list_field` (shared), `tab_menu_template`, `action_create`, `button_acl`, `button_create`, `button_edit`, `button_history`, `button_list`, `button_show`, `list_block`, `layout`, `ajax`.

### 8.2 Template file paths that PHP or other bundles reference by string

* `@SonataAdmin/CRUD/base_show_field.html.twig` — hard-coded in `RenderElementRuntime.php:68,91` and in docs (`field_types.rst:421-430`).
* All `SHOW_TEMPLATES` values (`TemplateRegistryInterface.php:26-47`) — consumed by ORM/Mongo `ShowBuilder` through DI.
* `@SonataAdmin/CRUD/dashboard__action.html.twig` — default in `block_admin_list.html.twig:45` and docs.
* `@SonataAdmin/CRUD/base_array_macro.html.twig` (imported by `show_array`, `list_array`).
* `@SonataAdmin/CRUD/base_acl_macro.html.twig` (imported by `base_acl`; users may import it).
* `@SonataAdmin/CRUD/display_*.html.twig` — documented as includable with explicit options (`CHANGELOG.md:1063-1073`).
* `@SonataAdmin/Helper/render_form_dismissable_errors.html.twig`.
* `@SonataAdmin/CRUD/edit.html.twig` (preview extends it), `@SonataAdmin/CRUD/base_show.html.twig` / `base_show_compare.html.twig` / `base_history.html.twig` / `base_acl.html.twig` (user templates extend them).

### 8.3 Twig block names

| Template | Blocks |
|---|---|
| `base_show.html.twig` | `title`, `navbar_title`, `actions`, `tab_menu`, `show`, `show_groups`, `field_row`, `show_title`, `show_field` |
| `base_show_field.html.twig` | `name`, `field`, `field_value`, `field_compare` |
| `base_show_compare.html.twig` | `show_field` |
| `base_history.html.twig` | `actions`, `content` |
| `base_acl.html.twig` | `actions`, `form`, `form_acl_roles`, `form_acl_users` (+ macro `render_form`) |
| `delete.html.twig` / `batch_confirmation.html.twig` / `action.html.twig` | `actions`, `tab_menu`, `content` |
| `preview.html.twig` | `actions`, `side_menu`, `formactions`, `preview`, `form` |
| layout blocks consumed by these pages | `sonata_admin_content_actions_wrappers`, `sonata_page_content_nav`, `notice`, `sonata_admin_content` (`standard_layout.html.twig:226-329`), plus the implicit `preview`/`form`/`show`/`content`/`title`/`navbar_title`/`actions`/`tab_menu` blocks captured at `standard_layout.html.twig:12-23` |
| `ajax_layout.html.twig` | `content`, `preview`, `form`, `list`, `show` |

### 8.4 CSS hook classes / ids (keep on the new markup)

`sonata-ba-view`, `sonata-ba-view-container`, `sonata-ba-view-title`, `sonata-ba-collapsed-fields`, `sonata-ba-show` (layout wrapper), `sonata-ba-content`, `sonata-ba-preview`, `sonata-ba-form`, `sonata-preview-form-container`, `sonata-ba-delete`, `sonata-ba-show-many-to-many`, `sonata-ba-show-one-to-many`, `sonata-readmore`, `sonata-readmore-content`, `sonata-readmore-btn`, `truncated`, `expanded`, `history-audit-compare`, `diff`, `#revisions`, `#revision-detail`, `revision-detail`, `revision-link`, `revision-compare-link`, `current-revision`, `sonata-action-element`, `sonata-actions`, `dropdown`, `dropdown-menu`, `nav-tabs-custom`, `changer-tab`, `tab-content`, `tab-pane`, `active`, `box`, `box-header`, `box-title`, `box-body`, `box-footer`, `nopadding`, `label label-success|label-danger`, `btn btn-link btn-flat`, `btn-group`, `inner-field-short-description`, `sonata-ba-list-label`, `form-actions`, `persist-preview`.

### 8.5 Data attributes / JS identifiers

Stimulus: `sonata-revision` (targets `preview`; action `showPreview`), `sonata-readmore` (targets `content`, `button`; values `collapsedHeight`, `moreText`, `lessText`; action `toggle`) — registered in `admin/assets/js/controllers.json`/`stimulus.js`. `Admin.setup_readmore_elements()` is deprecated no-op (`admin.js:367-370`). Request headers: `X-Requested-With: XMLHttpRequest` selects the ajax layout. Form field names: `_method=DELETE`, `_sonata_csrf_token`, `btn_delete`, `confirmation=ok`, `data` (JSON), `btn_preview`, `btn_preview_approve`, `btn_preview_decline`, `btn_create_and_edit`, `acl_users_form[...]`, `acl_roles_form[...]`, `_tab` query param.

### 8.6 Translation keys (domain `SonataAdminBundle`)

`title_show`, `title_delete`, `message_delete_confirmation`, `btn_delete`, `delete_or`, `link_action_create|list|show|edit|history|acl`, `link_actions`, `link_add`, `link_list`, `label_type_yes|no`, `td_revision|timestamp|username|action|compare|role`, `label_view_revision`, `label_compare_revision`, `label_unknown_user`, `btn_update_acl`, `title_batch_confirmation`, `message_batch_confirmation`, `message_batch_all_confirmation`, `btn_execute_batch_action`, `btn_preview`, `btn_preview_approve`, `btn_preview_decline`, `read_more`, `read_less`, `flash_acl_edit_success`, `flash_delete_success|error`, `flash_batch_*` (all in `admin/src/Resources/translations/SonataAdminBundle.en.xliff`, e.g. lines 57-94, 205-239, 309-347, 385-423, 445-491). No new keys are needed for a pure re-skin; if you add sr-only labels or a compare header row, add keys with sane English defaults.

### 8.7 Config options read by these templates

`sonata_admin.options.html5_validate` (`base_acl_macro.html.twig:17`), `default_admin_route` (Association templates), `use_icheck` (layout; becomes no-op), `list_action_button_content` (list action links, not show). Keep all keys accepted.

---

## 9. Responsive and dark-mode notes

* TailAdmin's dark mode is class-based: `@custom-variant dark (&:is(.dark *))` (`ta-html/src/css/style.css:6`) with the `dark` class toggled on `<html>`/`<body>` from Alpine `darkMode` persisted in `localStorage` (`ta-html/src/index.html:15-19`). Every replacement class above carries a `dark:` pair from the template partials; tables use `dark:border-gray-800`, `dark:bg-white/[0.03]`, `dark:text-white/90`, `dark:text-gray-400`.
* Bootstrap-compat colours (`box-danger` red top border, `label-success` etc.) need explicit `dark:` variants in the compat layer — TailAdmin tokens `success-500/15`, `error-500/15` at low opacity are the pattern (`badge-01.html:11,18`).
* Show page groups: `col-md-*` → responsive `md:` widths, single column below 768px; tables inside cards get `overflow-x-auto` (never let the page scroll horizontally). Long values: `td.break-words` + `max-w-0`? Prefer `td.whitespace-pre-wrap.break-words` for `nl2br` output.
* Show tabs: make the tab strip horizontally scrollable (`overflow-x-auto no-scrollbar`, `ta-html/src/css/style.css:246-253`) for many tabs.
* History: 5/7 split only from `lg:`; the injected revision fragment may itself contain tabs and cards — ensure nested cards drop the outer border (`.revision-detail .sonata-ba-view > … { @apply border-0 shadow-none }`).
* ACL matrix: 8 permission columns + name; on mobile use `overflow-x-auto` and `sticky left-0 bg-white dark:bg-gray-900` on the first column; the repeated header row every 10 entries can become `sticky top-0` within a scroll container.
* Delete/batch cards: `max-w-2xl`, buttons wrap (`flex-wrap`), full-width buttons under `sm:` (`w-full sm:w-auto`).
* Dropdowns (actions, subclass create): right-aligned `absolute right-0`, `z-99` token (`style.css:161`), close on `@click.outside` and `@keydown.escape.window`.
* `prefers-reduced-motion`: Alpine `x-transition` is fine; nothing animated otherwise.
* Print: show page should print cleanly — add `print:border-0 print:shadow-none` on cards (cheap win).

---

## 10. Risks and open questions

### Risks

| # | Risk | Severity | Mitigation |
|---|---|---|---|
| R1 | User admins pass Bootstrap classes (`col-md-*`, `box box-*`) in `ShowMapper::with()` options and get unstyled/one-column output | High | Bootstrap compat CSS layer (§1.4, §6.3) + keep echoing the raw values |
| R2 | User `configureActionButtons()` / `configureDashboardActions()` templates emit `<li><a class="sonata-action-element">` / `<a class="btn btn-link btn-flat">`; layout string heuristics (`replace`, `split('</a>')`) decide dropdown vs inline | High | Keep `<ul>` wrapper + heuristic; style via descendant selectors; compat layer for `.btn*` |
| R3 | Unit tests pin `label label-success|danger` badge HTML and exact `<th>/<td>` strings | Medium | Either keep the class names alongside Tailwind classes, or update ~20 expectations in `RenderElementRuntimeTest`/`RenderElementExtensionTest` (adminata owns its tests, so this is a decision, not a blocker) |
| R4 | `render_view_element` default template path is hard-coded in PHP (`RenderElementRuntime.php:68`) | Low | Keep the file path |
| R5 | Persistence bundles (ORM, Mongo fork) inject `SHOW_TEMPLATES` paths and their `ShowBuilder` sets them on field descriptions; any renamed show template breaks them | High | Never rename/move the 24 show/display/association templates |
| R6 | ORM `block_audit.html.twig` and SonataTwig flash template ship Bootstrap markup from other packages | Medium | Override via Twig namespace paths inside adminata + compat CSS fallback |
| R7 | `parse_icon` throws on non-FA strings; docs already show invalid values (`'import'`, `'level-up-alt'`) | Low | Extended `parse_icon` with SVG map + lenient fallback |
| R8 | iCheck removal changes checkbox markup in ACL page; `use_icheck` config must stay accepted | Low | Keep config key as no-op; document |
| R9 | Preview mode hides the form via CSS selectors tied to the old form markup (`fieldset`, `.tabbable`) | Medium | Give the new form body a stable class and hide it under `.sonata-preview-form-container` |
| R10 | `random()`-based tab ids and `_tab` index logic are copy-pasted in show, edit form and inline collection tabs | Low | Reimplement once (Twig macro or shared include) but keep the query-param semantics |
| R11 | SonataIntlBundle overrides six show templates with its own Bootstrap-era markup (`@SonataIntl/CRUD/show_*.html.twig`) | Medium | Out of adminata's tree; either provide overrides for `@SonataIntl` templates or document that Intl templates inherit `base_show_field` and only override `field`, so they still render inside the new table row (they do: they extend `base_show_field`) — verify when Intl is in the test matrix |
| R12 | Debug-mode HTML comments around each field (`RenderElementRuntime.php:211-231`) are inside `<tr>`; harmless, but any move away from `<tr>` would produce invalid HTML | Low | Keep `<tr>` |

### Open questions for the project owner

1. **Boolean badge classes**: keep `label label-success|danger` as hook classes (and update no tests) or switch to pure TailAdmin badge classes and rewrite the pinned test expectations? Related: should booleans show an icon (check/close SVG) in addition to the yes/no text?
2. **Bootstrap compat layer scope**: is a `bootstrap-compat.css` (grid `row/col-*`, `box*`, `btn*`, `label*`, `alert*`, `dropdown-menu`) acceptable as a permanent, opt-out part of adminata, or should compatibility be limited to a Twig-level translation of the documented option values (`class`, `box_class`) only?
3. **Dropdown heuristic**: keep Sonata's "2+ buttons → 'Actions' dropdown" behaviour exactly, or always render buttons inline (TailAdmin style) and only collapse on small screens? The former is safer for muscle memory; the latter is nicer.
4. **Icons**: ship inline-SVG mapping only (curated FA name list, unknown → text-less), or also bundle FontAwesome 5 CSS as an optional asset for user-supplied icon names?
5. **Delete UX**: keep delete as a page only, or additionally offer an Alpine confirm modal from the list/show action buttons that POSTs the same form (page remains as no-JS fallback)?
6. **Compare view**: is a real text diff (`<ins>/<del>`) desired, or is the current "whole cell highlighted when different" enough?
7. **ORM audit block & SonataIntl templates**: should adminata override third-party bundle templates (`@SonataDoctrineORMAdmin/Block/block_audit.html.twig`, `@SonataIntl/CRUD/*`) from within its own package, or leave them to a companion package / documentation?
8. **Tabs UI**: segmented control (TailAdmin `ChartTab`) vs. underline tabs for show/edit — a single decision for both dimensions.
9. **Preview block**: keep the legacy flat table (`table-bordered` with title rows) or render preview groups exactly like the show page (cards, tabs ignored)?
10. **Typography plugin**: allow `@tailwindcss/typography` for `display_html` output (`prose`), or hand-write minimal rich-text CSS?
