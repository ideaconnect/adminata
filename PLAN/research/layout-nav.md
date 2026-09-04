# adminata research — Layout, Navigation, Dashboard, Blocks

Dimension: `standard_layout.html.twig`, `ajax_layout.html.twig`, `empty_layout.html.twig`, `Menu/sonata_menu.html.twig`, `Core/*`, `Breadcrumb/*`, `Block/*`, the SonataBlock base templates, and the PHP that feeds them — mapped onto TailAdmin v2.3.0 (Tailwind v4 + Alpine.js 3).

All paths are relative to the scratchpad roots:

- `S` = `sonata-admin-4.43.0`
- `S5` = `sonata-admin-5.x`
- `T` = `tailadmin-html/src`
- `TR` = `tailadmin-react/src`, `TN` = `tailadmin-next/src`
- `B` = `vendor-extract/block-bundle`, `TW` = `vendor-extract/twig-extensions`
- `M` = `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle`

---

## 0. Ground truth established before mapping

| Fact | Evidence |
|---|---|
| The 5.x branch is **identical** to 4.43.0 for this dimension. `diff -rq` of `src/` shows only `Admin/AbstractAdminExtension.php`, `Admin/AdminExtensionInterface.php`, `Controller/CRUDController.php` differ; `src/Resources/views` and `assets/` are byte-identical. | `diff -rq S/src S5/src` (3 files), `diff -rq S/assets S5/assets` (empty) |
| `standard_layout.html.twig` is 344 lines and defines 33 named blocks (enumerated in §7). | `S/src/Resources/views/standard_layout.html.twig` |
| Sonata's JS entry imports jQuery, jquery-ui sortable, Bootstrap 3, jquery-form, x-editable, select2 full, admin-lte, icheck, slimscroll, masonry, then its own `admin.js`, `treeview.js`, `sidebar.js`, `base.js`, Stimulus. | `S/assets/js/app.js:50-90` |
| Sonata's `admin.js` removes `no-js` from `<html>` on DOM ready. | `S/assets/js/admin.js:375-377` |
| `masonry` is **only** used by the search page (`data-masonry` attribute); the dashboard uses plain Bootstrap columns. | `S/src/Resources/views/Core/search.html.twig:21`; `Core/dashboard.html.twig:56-126` has no masonry |
| The `sonata_config` Twig global is the `sonata.admin.configuration` service (`SonataConfiguration`), exposing `title`, `logo`, `getOption()`. | `S/src/DependencyInjection/Compiler/GlobalVariablesCompilerPass.php:30`; `S/src/SonataConfiguration.php:59-71` |
| The skin CSS file is appended to the stylesheet list by the DI extension, not by config defaults: `bundles/sonataadmin/admin-lte-skins/%s.min.css`. | `S/src/DependencyInjection/SonataAdminExtension.php:94-100` |
| Default asset lists: `bundles/sonataadmin/app.css`, `bundles/sonataform/app.css`, `bundles/sonataadmin/app.js`, `bundles/sonataform/app.js`. | `S/src/DependencyInjection/Configuration.php` (assets node, lines ~631-670) |
| TailAdmin free HTML: Tailwind `^4.0.0`, Alpine `^3.14.1`, `@alpinejs/persist`, flatpickr, apexcharts. `stickyMenu` and `scrollTop` are declared on `<body>` but never referenced anywhere else in the free template. | `tailadmin-html/package.json:21-53`; `grep -rn stickyMenu\|scrollTop T` returns only the `x-data` declarations |
| The TailAdmin HTML template has **no tabs component**; React/Next only have `ChartTab.tsx`, a segmented-control style toggle. | `ls TR/components/ui` (alert, avatar, badge, button, dropdown, images, modal, table, videos); `TR/components/common/ChartTab.tsx` |
| Sonata functional test relies on selector `.sidebar-menu .dynamic-menu a`. | `S/tests/Functional/Controller/MenuTest.php:41` |
| `BreadcrumbsRuntimeTest` asserts exact HTML: `<li><span>…</span></li>`, `<li><a href="…">…</a></li>`, `<li class="active"><span>…</span></li>`. | `S/tests/Twig/BreadcrumbsRuntimeTest.php:139-146` |
| Persistence bundles (doctrine-orm-admin-bundle, the user's MongoDB fork) ship only `Form/*` themes and `Block/block_audit.html.twig`; none extend or override layout blocks. | `find vendor-extract/doctrine-orm-admin-bundle -name '*.twig'`; `find M/src -name '*.twig'` |

---

## 1. Layout skeleton: AdminLTE 2 → TailAdmin

### 1.1 What Sonata renders today

```
<html class="no-js">                                     (S standard_layout:29)
  <head> meta sonata-config / sonata-translations, stylesheets, javascripts, <title>
  <body class="sonata-bc {skin} fixed [sonata-select2] [sonata-icheck] [sidebar-collapse]"
        data-controller="sonata-sticky">                 (:91-101)
    <div class="wrapper">                                (:103)
      <header class="main-header">                       (:106)  ← block sonata_header
        <noscript>…</noscript>                           (:107-113)
        <a class="logo">img + span</a>                   (:114-123) ← block logo
        <nav class="navbar navbar-static-top">           (:125)  ← block sonata_nav
          <a class="sidebar-toggle" data-toggle="push-menu">   (:126-129)
          <div class="navbar-left"><ol class="breadcrumb">…</ol></div>   (:131-147)
          <div class="navbar-custom-menu"><ul class="nav navbar-nav">     (:150-179)
            <li class="dropdown"> add_block </li>
            <li class="dropdown user-menu"><ul class="dropdown-menu dropdown-user"> user_block </ul></li>
      <aside class="main-sidebar"><section class="sidebar">  (:188-218) ← block sonata_left_side
        <form class="sidebar-form" role="search">        (:193-202)  ← block sonata_sidebar_search
        {{ knp_menu_render('sonata_admin_sidebar') }}    (:208)      ← block side_bar_nav
        <p class="text-center small">…</p>               (:211-214)  ← block side_bar_after_nav
      <div class="content-wrapper">                      (:221)
        <section class="content-header">                 (:223)
          <nav class="navbar navbar-default" data-sonata-sticky-target="navbar">  (:232)
            navbar-header (navbar_title) | navbar-left (tab_menu) | list-mode btn-group | navbar-right (actions dropdown) | list_filters_actions
        <section class="content">                        (:293)
          flash messages, .sonata-ba-preview, .sonata-ba-content, .sonata-ba-show, .sonata-ba-form, .row list_filters, .row list_table
    [bootlint script]                                    (:334-341)
```

Key runtime behaviours attached to this skeleton:

| Behaviour | Implementation | Evidence |
|---|---|---|
| Sidebar collapse persisted server-side via cookie `sonata_sidebar_hide` so the first paint already has `sidebar-collapse`. | Twig reads the cookie; `sidebar.js` toggles the cookie on `.sidebar-toggle` click; AdminLTE's `push-menu` plugin does the actual class toggle. | `S standard_layout:96-98`; `S/assets/js/sidebar.js:11-19` |
| Sticky sub-navbar (`.navbar.stuck`) and sticky form actions, via `sonata-sticky` Stimulus controller using `ResizeObserver` + `IntersectionObserver`; enabled only when `USE_STICKYFORMS` is true. | targets `topNavbar` (main navbar), `navbar` (content-header navbar), `action` (form actions). | `S/assets/js/controllers/sticky_controller.js:134-213`; CSS `S/assets/scss/layout.scss` (`.navbar.stuck`, `.form-actions.stuck`, width `calc(100% - 230px)`, `.sidebar-collapse … width: 100%`) |
| `no-js` class removal; `.no-js .sonata-collection-add` hidden. | `admin.js:375-377`; `layout.scss` `.no-js .sonata-collection-add` |
| `<meta name="sonata-config">` JSON with `SKIN, CONFIRM_EXIT, USE_SELECT2, USE_ICHECK, USE_STICKYFORMS, DEBUG` and `<meta name="sonata-translations">` with `CONFIRM_EXIT`, read by `core/config.js` / `core/translation.js`. | `S standard_layout:37-49`; `S/assets/js/core/config.js` |
| select2 locale script injected from `bundles/sonataadmin/select2-locale/{locale}.js` using `canonicalize_locale_for_select2()`. | `S standard_layout:67-73`; `S/src/Twig/CanonicalizeRuntime.php` |
| Bootlint bookmarklet injection when `use_bootlint`. | `S standard_layout:334-341` |

### 1.2 What TailAdmin renders

```
<html lang="en">
  <body x-data="{ page: 'blank', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: false, scrollTop: false }"
        x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', v => localStorage.setItem('darkMode', JSON.stringify(v)))"
        :class="{'dark bg-gray-900': darkMode === true}">                      (T/blank.html:14-20)
    <preloader>  fixed full-screen spinner, hidden 500ms after DOMContentLoaded (T/partials/preloader.html:1-9)
    <div class="flex h-screen overflow-hidden">                                 (T/blank.html:26)
      <aside class="sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] … lg:static lg:translate-x-0"
             :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'">   (T/partials/sidebar.html:1-4)
        sidebar-header (logo / logo-icon)                                        (:5-28)
        <nav x-data="{selected: $persist('Dashboard')}"> menu groups            (:34)
        bottom promo widget                                                      (:515-540)
      <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">   (T/blank.html:32-34)
        <overlay> @click="sidebarToggle=false" :class="sidebarToggle ? 'block lg:hidden' : 'hidden'" fixed bg-gray-900/50 (T/partials/overlay.html)
        <header x-data="{menuToggle:false}" class="sticky top-0 z-99999 flex w-full border-gray-200 bg-white lg:border-b dark:…">  (T/partials/header.html:1-4)
          hamburger (@click.stop="sidebarToggle = !sidebarToggle")  (:12-67)
          mobile logo (:70-77), mobile "…" menu toggle (:80-100)
          search form #search-input + #search-button "⌘ K"        (:103-137)
          right cluster (hidden on mobile unless menuToggle): dark-mode button, notifications dropdown, user dropdown (:139-758)
        <main><div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          breadcrumb partial (h2 x-text="pageName" + <ol> Home > pageName)     (T/partials/breadcrumb.html)
          card: rounded-2xl border border-gray-200 bg-white px-5 py-7 dark:border-gray-800 dark:bg-white/[0.03]  (T/blank.html:52-54)
```

The collapsed-sidebar hover-expand is pure CSS (`.sidebar:hover { width: 290px }` and friends, `T/css/style.css` lines ~264-290 in the file, shown as 125-151 in the extracted listing). Sidebar menu group state is `selected` persisted in localStorage through `$persist` (`T/partials/sidebar.html:34`); active-page highlighting is driven by the `page` string on `<body>` (`T/partials/sidebar.html:70`).

### 1.3 Recommended adminata skeleton (preserving Sonata block names)

The migration keeps every `{% block %}` name and nesting of `standard_layout.html.twig`, but replaces the wrapper markup:

```twig
<html {% block html_attributes %}lang="{{ app.request.locale }}" class="no-js"{% endblock %}>
<body {% block body_attributes %}
      class="sonata-bc {{ _skin }} {% if _use_select2 %}sonata-select2{% endif %} … {% if sidebar_hidden %}sidebar-collapse{% endif %}"
      x-data="sonataLayout({ sidebarToggle: {{ sidebar_hidden ? 'true' : 'false' }}, darkMode: … })"
      :class="{'dark bg-gray-900': darkMode}"
      {{ stimulus_controller('sonata-sticky') }}
      {% endblock %}>
  {% block sonata_preloader %}…{% endblock %}   {# new, optional #}
  <div class="flex h-screen overflow-hidden">      {# was .wrapper #}
    {% block sonata_wrapper %}
      {% block sonata_left_side %}<aside class="sidebar main-sidebar …">…{% endblock %}
      <div class="content-wrapper relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
        {% block sonata_overlay %}…{% endblock %}   {# new #}
        {% block sonata_header %}<header class="main-header sticky top-0 …">…{% endblock %}
        <main>{% block sonata_page_content %}…{% endblock %}</main>
```

Important ordering caveat: in Sonata, `sonata_header` is rendered **before** `sonata_wrapper`, outside it (`S standard_layout:105,186`). In TailAdmin the header lives inside the content column, to the right of the sidebar. Twig block *names* can stay, but their DOM position changes; `empty_layout.html.twig` empties `sonata_header`, `sonata_left_side`, `sonata_nav`, `sonata_breadcrumb` and re-declares `sonata_wrapper` to contain only `sonata_page_content` (`S/src/Resources/views/empty_layout.html.twig:14-34`) — this still works if `sonata_header` is *inside* `sonata_wrapper`'s content column only when `sonata_wrapper` still calls `block('sonata_header')`; the rewritten `empty_layout` must be adjusted accordingly (it overrides `sonata_wrapper` to `{% block sonata_page_content %}{{ parent() }}{% endblock %}`, so the header will vanish automatically — good).

Keep the legacy hook classes `sonata-bc`, `main-header`, `main-sidebar`, `sidebar`, `sidebar-menu`, `content-wrapper`, `content-header`, `content`, `sonata-ba-preview/-content/-show/-form` as *marker classes* (they carry no Tailwind styling but user CSS/JS/tests target them; see §10).

---

## 2. Block-by-block map of `standard_layout.html.twig`

Line numbers refer to `S/src/Resources/views/standard_layout.html.twig`.

| # | Block (line) | What it renders today | AdminLTE/Bootstrap structure | TailAdmin replacement | JS behaviour to preserve |
|---|---|---|---|---|---|
| 1 | (pre-block `set`s, :12-26) | Captures child blocks `preview, form, show, list_table, list_filters, tab_menu, content, title, breadcrumb, actions, navbar_title, list_filters_actions` into `_x` vars with `|trim`; reads `skin`, `use_select2`, `use_icheck`. | n/a | Keep verbatim — child templates (`CRUD/base_list:14-40`, `base_edit:14-45`, `base_show:14-33`, `Core/dashboard:14-16`, `Core/search:14-16`) depend on these names. Add `_use_alpine`/`_dark_mode` variables if needed. | — |
| 2 | `html_attributes` (:29) | `class="no-js"` | — | `lang` attr + `class="no-js"`; `admin.js:376` removes `no-js`. Do **not** put `dark` here (Alpine toggles `dark` on `<body>` in TailAdmin; `@custom-variant dark (&:is(.dark *))` works from any ancestor, `T/css/style.css:6`). | keep no-js removal |
| 3 | `meta_tags` (:31-35) | X-UA-Compatible, charset, viewport (`user-scalable=no`) | — | Same meta set as `T/index.html:4-9` (`charset`, viewport incl. `minimum-scale`, X-UA-Compatible). Recommend dropping `user-scalable=no` for accessibility (see §8) — but keep the block. | — |
| 4 | *(unnamed)* `<meta name="sonata-config">` / `sonata-translations` (:37-49) | JSON config for JS | — | Keep exactly; add new keys (`DARK_MODE_DEFAULT`, `SIDEBAR_COLLAPSED`) only additively. `SKIN` must remain present because `core/config.js` consumers may read it. | `core/config.js` |
| 5 | `stylesheets` (:51-55) | loop over `sonata_config.getOption('stylesheets')` | — | Same loop; default list becomes `bundles/adminata/app.css` (Tailwind build) instead of `app.css` + `sonataform/app.css` + `admin-lte-skins/*.css`. See §6 for the skin file. | — |
| 6 | `javascripts` (:57-74) → `sonata_javascript_config` (:58-59), `sonata_javascript_pool` (:61-65) | loop over `javascripts`; then select2 locale script | — | Keep both nested blocks (users override `sonata_javascript_config` to inject JS globals). The select2 locale include should stay only while select2 is retained; otherwise guard it with `use_select2`. Script tags at the end of `<head>` block rendering — TailAdmin loads a bundled `index.js` that boots Alpine (`T/js/index.js:18-20`); if Alpine is loaded in `<head>` it must be `defer`'d (Alpine requires `defer` or being placed at end of body), otherwise `x-data` on `<body>` is evaluated before the body exists. Recommend a single deferred `app.js`. | Alpine boot order |
| 7 | `sonata_head_title` (:77-88) | `Admin` + title block or `render_breadcrumbs_for_title(admin, action)` | — | Unchanged. `Breadcrumb/breadcrumb_title.html.twig` output is plain text (`S/…/Breadcrumb/breadcrumb_title.html.twig:1-16`). | — |
| 8 | `body_attributes` (:92-100) → `admin_lte_skin_class` (:93) | `class="sonata-bc {skin} fixed [sonata-select2] [sonata-icheck] [sidebar-collapse]"` + `stimulus_controller('sonata-sticky')` | AdminLTE `fixed` layout, skin class | `class="sonata-bc {{ _skin }} …"` **plus** `x-data="{ page, loaded, darkMode, sidebarToggle, stickyMenu, scrollTop }"`, `x-init` (dark-mode localStorage sync, `T/blank.html:16-18`), `:class="{'dark bg-gray-900': darkMode === true}"`. Seed `sidebarToggle` from the `sonata_sidebar_hide` cookie so SSR and Alpine agree. Keep `admin_lte_skin_class` as a block that now outputs a theme class (e.g. `theme-{{ _skin }}`), see §6. | `sonata-sticky` controller stays attached to body |
| 9 | `sonata_header` (:105-184) | `<header class="main-header">` | AdminLTE `.main-header` (50px, `styles.scss:61-63`) | TailAdmin `<header class="main-header sticky top-0 z-99999 flex w-full border-gray-200 bg-white lg:border-b dark:border-gray-800 dark:bg-gray-900">` (`T/partials/header.html:1-4`) with inner `x-data="{menuToggle:false}"`. Move logo out (see row 11). | header `menuToggle` (mobile) |
| 10 | `sonata_header_noscript_warning` (:107-113) | `<noscript><div class="noscript-warning">` | custom red bar (`layout.scss` `.noscript-warning`) | Same `<noscript>` wrapper; TailAdmin alert-error styling: `rounded-xl border border-error-500 bg-error-50 p-4 dark:border-error-500/30 dark:bg-error-500/15` (`T/partials/alert/alert-error.html`). Keep class `noscript-warning`. | — |
| 11 | `logo` (:114-123) | `<a class="logo" href="dashboard">` with `<img src="{{ asset(sonata_config.logo) }}">` and/or `<span>{{ sonata_config.title }}</span>` according to `logo_content` (`icon|text|all`). | AdminLTE `.logo` in header (60px img, 200px span, `styles.scss:37-55`). | Goes to the **sidebar header** (`T/partials/sidebar.html:5-28`): full logo shown when expanded (`<span class="logo" :class="sidebarToggle ? 'hidden' : ''">`), `logo-icon` when collapsed. Map: `logo_content: all` → img + text; `icon` → img only (also used as collapsed icon); `text` → text only; collapsed state shows the first letter or the img. Also render a compact logo in the header for mobile (`T/partials/header.html:70-77` `lg:hidden`). TailAdmin has separate `logo.svg`/`logo-dark.svg` (`T/images/logo`), Sonata has one `title_logo` — see §6. | — |
| 12 | `sonata_nav` (:124-182) | `<nav class="navbar navbar-static-top" data-sonata-sticky-target="topNavbar">` with sidebar toggle anchor | Bootstrap 3 navbar; AdminLTE `data-toggle="push-menu"` | TailAdmin header inner flex row (`T/partials/header.html:5-10`). The toggle becomes the hamburger `<button @click.stop="sidebarToggle = !sidebarToggle">` (`:12-67`) — keep `class="sidebar-toggle"` and `title/aria-label = 'toggle_navigation'|trans` so `sidebar.js` cookie logic still binds (rewrite `sidebar.js` without jQuery: set cookie on click). Keep `stimulus_target('sonata-sticky','topNavbar')` on the header element (sticky controller computes rootMargin from `topNavbarTarget.offsetHeight`, `sticky_controller.js:207`). | cookie + sticky target |
| 13 | `sonata_breadcrumb` (:132-146) | `<div class="hidden-xs"><ol class="nav navbar-top-links breadcrumb">` + `render_breadcrumbs(admin, action)` or `_breadcrumb` override | Bootstrap breadcrumb inside navbar, left | Two options. (a) Keep in header left (after hamburger), styled as `T/partials/breadcrumb.html:7-38` `<nav><ol class="flex items-center gap-1.5">`; (b) TailAdmin convention puts breadcrumb + page title in `<main>` above the card (`T/blank.html:46-50`). Recommend (b) for fidelity, but keep the block name and keep rendering inside `sonata_nav`'s DOM order for override compatibility — i.e. define `sonata_breadcrumb` where the content header is and leave an empty hook in the navbar. `hidden-xs` → `hidden sm:flex`. The `<li>` markup produced by `Breadcrumb/breadcrumb.html.twig` is asserted by tests (§0) — keep `<li>`/`<li class="active">` and only style via a parent class (`.sonata-bc .breadcrumb li`) and Tailwind `@utility`. Chevron separators: TailAdmin uses inline SVG after each link (`T/partials/breadcrumb.html:15-30`); do it with `li:not(:last-child)::after` CSS to avoid touching the tested markup. | — |
| 14 | `sonata_top_nav_menu` (:149-180) | `<div class="navbar-custom-menu"><ul class="nav navbar-nav">` | AdminLTE right navbar | TailAdmin right cluster `<div :class="menuToggle ? 'flex' : 'hidden'" class="shadow-theme-md w-full items-center justify-between gap-4 px-5 py-4 lg:flex lg:justify-end lg:px-0 lg:shadow-none">` (`T/partials/header.html:139-142` in file; shown at lines 19-22 of the stripped listing). Contains, in order: dark-mode toggle (new), `sonata_top_nav_menu_add_block`, (notifications — not in Sonata, skip or expose as an empty block `sonata_top_nav_menu_notifications`), `sonata_top_nav_menu_user_block`. Keep the `<ul class="nav navbar-nav">` wrapper? No — but custom `user_block`/`add_block` templates emit `<li>` (see §3), so the wrapper must remain a `<ul>`. | `menuToggle` |
| 15 | `sonata_top_nav_menu_add_block` (:152-162) | `include(get_global_template('add_block'))`; if non-empty wraps in `<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown"><i class="fas fa-plus-square"> <i class="fas fa-caret-down">` + the included HTML (which is itself a `<div class="dropdown-menu multi-column dropdown-add">`). | Bootstrap 3 dropdown, mega-menu CSS (`styles.scss:274-285` `.dropdown-menu .dropdown-menu { position: static … }`) | `<li class="relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">` + round icon button (`T/partials/header.html` notification button classes: `relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 … dark:border-gray-800 dark:bg-gray-900`) + panel `x-show="dropdownOpen"` `absolute right-0 mt-[17px] rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark`. The included `add_block` output must be rewritten (see §3.2). The `|trim is not empty` guard must be kept so apps overriding `add_block` with an empty template still hide the button. | Alpine dropdown replaces Bootstrap `data-toggle="dropdown"` |
| 16 | `sonata_top_nav_menu_user_block` (:164-176) | only `{% if app.user %}`; `include(get_global_template('user_block'))`; wraps in `<li class="dropdown user-menu"><a class="dropdown-toggle"><i class="fas fa-user">…</a><ul class="dropdown-menu dropdown-user">{{ userBlock }}</ul>` | Bootstrap dropdown | TailAdmin user area (`T/partials/header.html` "User Area": avatar 44px round, name, chevron; panel `w-[260px] … rounded-2xl … p-3`; a header line with name/email, a `<ul class="flex flex-col gap-1 border-b … pt-4 pb-3">` of links, and a sign-out button). Because the default `user_block` is an empty comment (`S/src/Resources/views/Core/user_block.html.twig:1`) and downstream bundles (SonataUserBundle) inject `<li><a>` items, the panel must contain a `<ul>` that receives `{{ userBlock|raw }}` verbatim; style children through `.dropdown-user > li > a` in CSS. Keep `app.user` guard. | Alpine dropdown |
| 17 | `sonata_wrapper` (:186-331) | wraps sidebar + `.content-wrapper` | AdminLTE | `<div class="flex h-screen overflow-hidden">` (`T/blank.html:26`) containing `sonata_left_side` and the content column. | — |
| 18 | `sonata_left_side` (:187-219) | `<aside class="main-sidebar"><section class="sidebar">` | AdminLTE fixed 230px sidebar, slimscroll | `<aside class="sidebar main-sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-black lg:static lg:translate-x-0" :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'">` (`T/partials/sidebar.html:1-4`) with inner scroll container `flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar` (`:30-32`). Note TailAdmin's `sidebarToggle` semantics are inverted between breakpoints: on `lg` it means *collapsed to 90px*, on mobile it means *open*. This matches Sonata's single cookie only on desktop; on mobile Sonata's `sidebar-collapse` cookie doesn't apply (AdminLTE uses `sidebar-open`). | slimscroll dropped (native overflow) |
| 19 | `sonata_side_nav` (:190-216) | container for search, before/after nav hooks and the menu | — | Same nesting: `<nav>` element with `x-data="{ selected: $persist('') }"` (`T/partials/sidebar.html:34`), but see §2 note on multiple open groups. | Alpine `$persist` |
| 20 | `sonata_sidebar_search` (:191-204) | `<form action="{{ path('sonata_admin_search') }}" method="GET" class="sidebar-form" role="search">` with input `name="q"` prefilled from `app.request.attributes/query/request 'q'`, submit button | AdminLTE `.sidebar-form` | Move to the header search (`T/partials/header.html:103-137`): `<form action=… method="GET" role="search"><input type="text" name="q" id="search-input" placeholder="{{ 'search_placeholder'|trans }}" class="… h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pr-14 pl-12 text-sm … xl:w-[430px] dark:…"><button id="search-button">⌘ K</button>`. Keep the block name `sonata_sidebar_search` (empty by default in the sidebar) **and** add `sonata_header_search` in the header; apps that override `sonata_sidebar_search` to hide search will still hide the sidebar one, so make the header search *also* honour `sonata_config.getOption('search')` and render nothing if `sonata_sidebar_search` was overridden to empty (capture with `block('sonata_sidebar_search')|trim`). `T/js/index.js:91-118` provides Cmd/Ctrl+K and `/` focus shortcuts — port into a Stimulus controller. The `search_placeholder` translation ("Search") is at `S/src/Resources/translations/SonataAdminBundle.en.xliff:433-435`. | Cmd+K focus |
| 21 | `side_bar_before_nav` (:206) | empty hook | — | keep | — |
| 22 | `side_bar_nav` (:207-209) | `knp_menu_render('sonata_admin_sidebar', {template: get_global_template('knp_menu_template')})` | KnpMenu → `ul.sidebar-menu[data-widget=tree]` | unchanged call; template rewritten (§2/§3 of the sidebar section below). | — |
| 23 | `side_bar_after_nav` (:210-215) → `side_bar_after_nav_content` (:212-213) | `<p class="text-center small" style="border-top:1px solid #444; padding-top:10px">` | inline style | TailAdmin sidebar bottom widget slot (`T/partials/sidebar.html:515-540`: `mx-auto mb-10 w-full max-w-60 rounded-2xl bg-gray-50 px-4 py-5 text-center dark:bg-white/[0.03]`). Render only if `side_bar_after_nav_content` is non-empty (today the empty `<p>` with border always renders). Hide text when collapsed (`:class="sidebarToggle ? 'lg:hidden' : ''"`). | — |
| 24 | `sonata_page_content` (:222-329) | `<section class="content-header">` + `<section class="content">` | AdminLTE content header/content | `<main><div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">` (`T/blank.html:44-45`). Keep `content-header` / `content` classes as markers. | — |
| 25 | `sonata_page_content_header` (:225-290) → `sonata_page_content_nav` (:226-289) | If any of `_navbar_title, _tab_menu, _actions, _list_filters_actions` non-empty: `<nav class="navbar navbar-default" data-sonata-sticky-target="navbar"><div class="container-fluid">…` | Bootstrap navbar as page toolbar; `.navbar.stuck` fixed at `top:50px; width: calc(100% - 230px)` (`layout.scss`) | TailAdmin page-header row: `<div class="mb-6 flex flex-wrap items-center justify-between gap-3">` (`T/partials/breadcrumb.html:1`) holding: title `<h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">`, tab menu, list-mode buttons, actions dropdown, filters dropdown. Keep `stimulus_target('sonata-sticky','navbar')`; when stuck, apply `sticky top-[header-height] z-99 bg-gray-50 dark:bg-gray-900` instead of AdminLTE's fixed-width hack (the sticky controller only toggles the `stuck` class; the CSS is ours). The visibility condition must be preserved verbatim. | sonata-sticky `stuck` class |
| 26 | `tab_menu_navbar_header` (:234-240) | `<div class="navbar-header"><a class="navbar-brand" href="#">{{ _navbar_title|raw }}</a>` | Bootstrap navbar-brand | `<h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ _navbar_title|raw }}</h2>` — drop the `href="#"` anchor (a11y: link to nowhere). Note `_navbar_title` is `|raw` — child templates may contain markup. | — |
| 27 | *(unnamed)* tab menu container (:243-247) | `<div class="navbar-left">{{ _tab_menu|raw }}</div>` | — | wrapper `<div class="sonata-tab-menu">`; markup produced by `Core/tab_menu_template.html.twig` (§9). | — |
| 28 | *(unnamed)* list-mode switcher (:249-263) | `<div class="nav navbar-right btn-group">` of `<a class="btn btn-default navbar-btn btn-sm [active]">` per `admin.listModes`, icon via `settings.icon|parse_icon` (deprecated `settings.class` path with `{% deprecated %}`) | Bootstrap btn-group | TailAdmin segmented control from `TR/components/common/ChartTab.tsx:14-20`: `<div class="flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">` with items `px-3 py-2 font-medium rounded-md text-theme-sm` + active `shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800`. Keep the `{% deprecated %}` branch and the `active` class. Icons: `parse_icon` still emits `<i class="fas …">` (§8/§10 icon strategy). Duplicated in `ajax_layout.html.twig:28-42`; factor into a shared `Core/list_mode_buttons.html.twig` partial included by both. | — |
| 29 | `sonata_admin_content_actions_wrappers` (:265-280) | If `_actions` minus `<li>` tags is non-empty: `<ul class="nav navbar-nav navbar-right">`; if more than one `</a>` present → `<li class="dropdown sonata-actions"><a data-toggle="dropdown">{{ 'link_actions'|trans }} <b class="caret"></b></a><ul class="dropdown-menu">{{ _actions }}</ul>`, else `_actions` inline. `_actions` is produced by `CRUD/action_buttons.html.twig` → `Button/*_button.html.twig`, each emitting `<li><a class="sonata-action-element" href><i class="fas …"></i> label</a></li>` (`S/…/Button/create_button.html.twig:12-19`). | Bootstrap navbar + dropdown | Same string heuristics (`|replace({'<li>':''})`, `|split('</a>')|length > 2`) must stay — apps override `actions`. Wrapper: `<ul class="flex items-center gap-2 sonata-actions">`; multi → Alpine dropdown (`x-data="{open:false}" @click.outside`), button styled as TailAdmin secondary button (`T/partials/table/table-01.html:16`: `inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 … dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400`), panel from top-card dropdown (`T/partials/top-card-group.html`: `absolute right-0 top-full z-40 w-40 space-y-1 rounded-2xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark`, items `flex w-full rounded-lg px-3 py-2 text-left text-theme-xs font-medium text-gray-500 hover:bg-gray-100 …`). Style `<li><a class="sonata-action-element">` children through CSS so `Button/*` templates (owned by the CRUD dimension) render correctly either way. | Alpine dropdown |
| 30 | *(unnamed)* `_list_filters_actions` (:282-284) | raw include of `CRUD/base_list.html.twig:256-293` markup: `<ul class="nav navbar-nav navbar-right" id="filter-list-{{ uniqid }}" data-controller="sonata-filter-list"><li class="dropdown sonata-actions"><a class="dropdown-toggle sonata-ba-action" data-toggle="dropdown"><i class="fas fa-filter"> Filters <span class="badge" data-sonata-filter-list-target="counter"></span> <b class="caret"></b></a><ul class="dropdown-menu dropdown-menu-scrollable">…` | Bootstrap dropdown, `.dropdown-menu-scrollable { max-height: 75vh }` (`styles.scss:69-72`) | Rendered by the list dimension; layout must only give it a flex slot. Coordinate: the `sonata-filter-list` Stimulus controller (`S/assets/js/controllers/filter_list_controller.js`) must keep working after the Bootstrap dropdown is replaced by Alpine. | Stimulus `sonata-filter-list` |
| 31 | `sonata_admin_content` (:294-327) | flash messages (`notice`), then `.sonata-ba-preview`, `.sonata-ba-content`, `.sonata-ba-show`, `.sonata-ba-form`, `.row` list_filters, `.row` list_table | Bootstrap rows | Keep the five marker divs. `.row` → `grid grid-cols-12 gap-4 md:gap-6` (`T/index.html:46`), since `base_list.html.twig:37` opens with `<div class="col-xs-12 col-md-12">` (list dimension will change that to `col-span-12`). | — |
| 32 | `notice` (:296-298) | `{% include '@SonataTwig/FlashMessage/render.html.twig' %}` | Bootstrap alerts | see §5 | readmore |
| 33 | `bootlint` (:335-340) | inline script loading bootlint from maxcdn | — | Render nothing; keep the block name (empty) and deprecate `use_bootlint` (§6). | — |

`ajax_layout.html.twig` (`S/src/Resources/views/ajax_layout.html.twig:12-63`) defines `content` and nested empty `preview, form, list, show`; it re-implements the list-mode switcher and `list_filters_actions` inside `<div class="container-fluid"><div class="row"><div class="navbar navbar-default sonata-list-table">`. It is used for `X-Requested-With` requests (modal lists from `sonata_type_model_list`, `DashboardAction:48-50`, `SearchAction:35-37`). Replacement: `<div class="sonata-list-table flex flex-wrap items-center justify-between gap-3 mb-4">` + `grid` rows; note `body.fixed .sonata-list-table` margins (`styles.scss:397-402`) are legacy. `Admin.setup_list_modal` (`admin.js:35-56`) sizes `div.modal-dialog/.modal-content/.modal-body` — modal markup belongs to the form dimension but the ajax layout must not add wrappers that break `.modal-body` height maths.

`empty_layout.html.twig` (`:12-34`) extends `get_global_template('layout')`, blanks `sonata_header`, `sonata_left_side`, `sonata_nav`, `sonata_breadcrumb`, adds `<style>.content{margin:0;padding:0}</style>` and reduces `sonata_wrapper` to `sonata_page_content`. In the new layout: keep the same overrides; since `sonata_header` will live inside the content column, blanking it still works; replace the inline style with a `sonata-empty-layout` body class that zeroes `<main>` padding.

---

## 3. Sidebar menu (KnpMenu → TailAdmin collapsible groups)

### 3.1 Data model produced by PHP

| Layer | Behaviour | Evidence |
|---|---|---|
| `MenuBuilder::createSidebarMenu()` creates `root`, one child per `Pool::getAdminGroups()` via the provider named in `group['provider']` (default `sonata_group_menu`), then copies extras `icon`, `translation_domain`, `label_catalogue`, `roles`, `sonata_admin=true` onto the group item and dispatches `ConfigureMenuEvent::SIDEBAR` (`sonata.admin.event.configure.menu.sidebar`). | `S/src/Menu/MenuBuilder.php:42-72` |
| `GroupMenuProvider::get()`: if `on_top === false` adds one child per item (admins → `admin->generateMenuUrl('list', route_params, abs)` with extras `translation_domain`, `label_catalogue`, `admin`; routes → `route`, `routeParameters`, `routeAbsolute`, extras `translation_domain`); hides the group if no children; if `keep_open` sets **attribute** `class="keep-open"` and **extra** `keep_open`. If `on_top` and exactly one displayable item, the group item *is* that item with extra `on_top`. Label = `group['label']`. | `S/src/Menu/Provider/GroupMenuProvider.php:60-86,144-176` |
| Item visibility: admins need `hasRoute('list') && hasAccess('list')`; plain routes need any of item roles AND any of group roles. | `GroupMenuProvider.php:102-138` |
| Group defaults come from the compiler pass: `default_group`, `default_translation_domain` (fallback `default_label_catalogue`), `default_icon` (`'fas fa-folder'` in docs), `on_top`, `keep_open`, `sort_admins`. | `S/src/DependencyInjection/Compiler/AddDependencyCallsCompilerPass.php:61-76,177-211,217`; `S/docs/reference/configuration.rst:88` |
| Current-item matching: `AdminVoter` (matches `_sonata_admin` request attr against admin's `getBaseCodeRoute()` and recursively its children; or `_route` extra) and `ActiveVoter` (explicit `active` extra, only for `sonata_admin` items). | `S/src/Menu/Matcher/Voter/AdminVoter.php:73-113`, `ActiveVoter.php:26-38` |
| Service ids and KnpMenu alias: `sonata.admin.menu_builder`, `sonata.admin.sidebar_menu` tagged `knp_menu.menu alias=sonata_admin_sidebar`, `sonata.admin.menu.group_provider`, `sonata.admin.menu.matcher.voter.admin/active`. | `S/src/Resources/config/menu.php:25-55` |
| Documented user contract: items via config (`route`, `route_params`, `roles`, `label`), `provider`, `keep_open`, `on_top`, `show_in_dashboard`, event listener `addChild(...)->setExtras(['icon' => 'fas fa-bar-chart'])` ("html is also supported"), custom `knp_menu_template`. | `S/docs/cookbook/recipe_knp_menu.rst` (whole file) |
| Test app adds a dynamic child with `setAttribute('class','dynamic-menu')` and the functional test selects `.sidebar-menu .dynamic-menu a`. | `S/tests/App/EventListener/ConfigureMenu.php:32`; `S/tests/Functional/Controller/MenuTest.php:41` |

### 3.2 Current template (`S/src/Resources/views/Menu/sonata_menu.html.twig`)

| Block | Lines | Does |
|---|---|---|
| `root` | 3-7 | `listAttributes = childrenAttributes + {class:'sidebar-menu', 'data-widget':'tree'}`; `request = item.extra('request') ?: app.request`; renders `list`. |
| `item` | 9-21 | Role gate: `item.extra('roles') is empty or is_granted(role_super_admin) or any role granted`; then `options = options|merge({branch_class:'treeview', currentClass:'active', ancestorClass:'active'})`; `setChildrenAttribute('class', … 'active treeview-menu')`; `parent()`. |
| `linkElement` | 23-33 | `translation_domain` fallback chain `translation_domain → label_catalogue → messages`; icon: `item.extra('icon')` defaulting to `'fa fa-angle-double-right'` for level>1 items whose group is not `on_top`; `is_link=true`; `parent()` (KnpMenu `<a href>{label}</a>`). |
| `spanElement` | 35-46 | `<a href="#">{icon}{label}` + (unless `keep_open`) `<span class="pull-right-container"><i class="fas pull-right fa-angle-left"></i></span></a>` — group headers are `<a href="#">` (uri empty). |
| `label` | 48-61 | prints icon when `is_link`, then label: raw if `options.allow_safe_labels and extra('safe_label')`, else `trans(label_translation_parameters, translation_domain)`. Uses `item.getLabel()` (ArrayAccess caveat). |

Resulting DOM (AdminLTE treeview): `ul.sidebar-menu[data-widget=tree] > li.treeview[.active][.keep-open] > a[href=#] > i.icon + label + span.pull-right-container>i.fa-angle-left` then `ul.treeview-menu.active > li[.active] > a[href] > i + label`. AdminLTE's `tree` widget (imported via `admin-lte`, `app.js:69`) toggles `li.menu-open` and slides `.treeview-menu`. Sonata's own `treeview.js` is a *different* plugin used for `ul.js-treeview` in list tree mode (`admin.js:262-265`), not for the sidebar. `keep_open` is enforced by CSS `.sidebar-menu li.keep-open > .treeview-menu { display:block !important; height:auto !important }` (`styles.scss:305-308`).

### 3.3 TailAdmin target structure (`T/partials/sidebar.html`)

```
<nav x-data="{selected: $persist('Dashboard')}">                (:34)
  <div>                                                          (group section)
    <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
      <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">MENU</span>
      <svg class="menu-group-icon" :class="sidebarToggle ? 'lg:block hidden' : 'hidden'">…</svg>   (:37-61)
    </h3>
    <ul class="flex flex-col gap-4 mb-6">                        (:63)
      <li>                                                       (collapsible item)
        <a href="#" @click.prevent="selected = (selected === 'Dashboard' ? '' : 'Dashboard')"
           class="menu-item group" :class="(selected === 'Dashboard') || (page === '…') ? 'menu-item-active' : 'menu-item-inactive'">   (:66-70)
          <svg :class="… ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">              (:72-86)
          <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Dashboard</span>  (:88-93)
          <svg class="menu-item-arrow" :class="[(selected === 'Dashboard') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '']">  (:95-111)
        </a>
        <div class="overflow-hidden transform translate" :class="(selected === 'Dashboard') ? 'block' : 'hidden'">   (:115-118)
          <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">   (:119-122)
            <li><a href="index.html" class="menu-dropdown-item group" :class="page === 'ecommerce' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">eCommerce</a></li>
      <li> (leaf item)  <a href="calendar.html" class="menu-item group" :class="… ? 'menu-item-active' : 'menu-item-inactive'"> svg + span.menu-item-text </a>   (sidebar.html ~:140-155)
```

Utilities are defined with `@utility` in `T/css/style.css` (`menu-item`, `menu-item-active/inactive`, `menu-item-icon-active/inactive`, `menu-item-arrow[-active/-inactive]`, `menu-dropdown-item[-active/-inactive]`, `menu-dropdown-badge…`, `no-scrollbar`, `custom-scrollbar`) — lines 46-106 of the extracted listing (file lines ~185-245). Collapsed hover-expansion is CSS (`.sidebar:hover …`, style.css listing lines 125-151).

### 3.4 Mapping table

| Sonata concept | TailAdmin equivalent | Notes / what changes in the KnpMenu template |
|---|---|---|
| `ul.sidebar-menu[data-widget=tree]` | `<nav x-data="{ open: $persist({}).as('sonata-sidebar') }"><ul class="sidebar-menu flex flex-col gap-1">` | Keep class `sidebar-menu` (functional test). Drop `data-widget`. Sonata has no group *sections* ("MENU"/"OTHERS"); render one `<h3 class="menu-group-title …">` only if a `sonata_sidebar_title` block/option supplies one, else omit. |
| `li.treeview` group with `a[href=#]` header | `<li class="treeview"><button type="button" class="menu-item group w-full" @click="toggle(name)" :class="isOpen(name) || active ? 'menu-item-active' : 'menu-item-inactive'" aria-expanded>` | Use `<button>` not `<a href="#">` (a11y). Alpine state must be **a map keyed by group name**, not TailAdmin's single `selected` string, because Sonata allows any number of open groups and `keep_open` groups must always be open. Suggest `x-data` on `<nav>`: `{ open: $persist({}).as('sonata_sidebar_open'), toggle(k){ this.open[k] = !this.open[k] }, isOpen(k, keep, active){ return keep || (this.open[k] ?? active) } }`. Default open = `matcher.isAncestor(item)` → `active`, mirroring AdminLTE which auto-opens the active treeview. |
| `ancestorClass: 'active'` / `currentClass: 'active'` | Server-side `active` class stays on `<li>`; Alpine reads it via a `data-active` attribute to seed the default. | Preserve `options|merge({branch_class:'treeview', currentClass:'active', ancestorClass:'active'})` so third-party KnpMenu overrides keep working. |
| `keep_open` → `li.keep-open` + no caret | `keep_open` group renders its `<ul>` unconditionally (`class="block"`), no arrow, header non-interactive (or still a button that does nothing). | Keep the `keep-open` class on `<li>` and the `keep_open` extra check (`sonata_menu.html.twig:42-44`). |
| `on_top` single item → root-level leaf `li > a[href]` | leaf `menu-item` anchor (as TailAdmin Calendar item). | Keep the `on_top` extra logic in `linkElement` (`:26-30`), including *not* injecting the default `fa fa-angle-double-right` icon for top items. |
| Child item default icon `fa fa-angle-double-right` (level>1) | TailAdmin children have no icon (`menu-dropdown-item` text only, `pl-9` indent). | Recommend: keep emitting the icon only when `item.extra('icon')` is explicitly set; drop the angle default for level>1 (visible change; document it). |
| Icon strings `'fas fa-*'` via `parse_icon` (`S/src/Twig/IconRuntime.php:20-38`; accepts `fa|fas|far|fab|fal|fad ` prefixes or raw `<…>` HTML, throws otherwise) | Inline SVG 24×24 `fill-current` (`T/partials/sidebar.html:72-86`) | Two-tier strategy: (1) keep FontAwesome (Free 6 or 5) as a bundled webfont so *every* existing `fas fa-*` in user config, `configureDashboardActions`, `dashboard.blocks.settings.icon`, `group.icon` keeps rendering; (2) add an `icon_map` (FA class → TailAdmin SVG symbol) consulted by an extended `parse_icon` that emits `<svg><use href="#…"></svg>` for known names and falls back to `<i class>`. Must keep the exception on unsupported prefixes (tested in `S/tests/Twig/IconRuntimeTest.php`). Apply `menu-item-icon-active/inactive` to the wrapper `<span>` rather than the `<svg>` so both `<i>` and `<svg>` inherit colour (`fill-current`/`text-*`). |
| Caret `span.pull-right-container > i.fa-angle-left` (rotates to `fa-angle-down` when open via AdminLTE CSS) | `<svg class="menu-item-arrow" :class="open ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive'">` chevron (`T/partials/sidebar.html:95-111`; `menu-item-arrow-active` adds `rotate-180`). | Keep a `pull-right-container` class on the wrapper for CSS-only overrides. |
| `ul.treeview-menu` (children) | `<div class="overflow-hidden" x-show="isOpen(...)" x-collapse?>` + `<ul class="treeview-menu menu-dropdown flex flex-col gap-1 mt-2 pl-9">` | TailAdmin uses `block/hidden` toggling; a slide animation needs `@alpinejs/collapse` (not in TailAdmin's deps) or CSS `grid-template-rows` trick. Keep `treeview-menu` class. |
| Child `li[.active] > a` | `<a class="menu-dropdown-item [menu-dropdown-item-active|-inactive]">` | Active decided server-side (matcher) — no `page` string needed. |
| Label translation: `translation_domain` extra with `label_catalogue` fallback, `label_translation_parameters`, `safe_label` + `allow_safe_labels` | unchanged | Keep the whole `label` block. `S/docs/cookbook/recipe_knp_menu.rst` says HTML icons are allowed; `safe_label` needs `allow_safe_labels` renderer option (default false). |
| Role gate in `item` block (`item.extra('roles')`, `role_super_admin`) | unchanged | Must stay; also gates children added via events with `roles` extra. |
| Collapsed sidebar (`body.sidebar-collapse` cookie) | `sidebarToggle` state → `lg:w-[90px]`, text/arrows `lg:hidden`, hover expand CSS | Seed from cookie; `sidebar.js` rewritten to plain JS writing `sonata_sidebar_hide=1|0; path=/`. Persist via cookie **and** `localStorage` so SSR has no flash. Collapsed mode with text hidden means group headers become icon-only: TailAdmin hides submenus (`:class="sidebarToggle ? 'lg:hidden' : 'flex'"`) and relies on `.sidebar:hover` to expand. |
| Mobile: AdminLTE `sidebar-open` slide-in | `-translate-x-full` ↔ `translate-x-0`, plus overlay partial (`T/partials/overlay.html`) with `@click="sidebarToggle=false"`. | New `sonata_overlay` block. |
| Scroll: slimscroll | `overflow-y-auto no-scrollbar` | drop `jquery-slimscroll`. |
| `item.extra('request')` in `root` (`sonata_menu.html.twig:5`) | unused downstream in this template; keep the line for BC with overriding templates. | — |

Recommended new `Menu/sonata_menu.html.twig` skeleton (blocks preserved: `root`, `item`, `linkElement`, `spanElement`, `label`; add `iconElement`, `arrowElement`, `children` overrides):

```twig
{% extends 'knp_menu.html.twig' %}
{% block root %}
  {%- set listAttributes = item.childrenAttributes|merge({'class': ('sidebar-menu flex flex-col gap-1 ' ~ item.childrenAttribute('class'))|trim}) -%}
  {%- set request = item.extra('request') ?: app.request -%}
  <nav class="sonata-sidebar-nav" x-data="sonataSidebarMenu()">{{ block('list') }}</nav>
{% endblock %}
{% block item %}
  … same role gate …
  {%- set options = options|merge({branch_class: 'treeview', currentClass: 'active', ancestorClass: 'active'}) -%}
  {%- do item.setChildrenAttribute('class', (item.childrenAttribute('class') ~ ' treeview-menu menu-dropdown mt-2 flex flex-col gap-1 pl-9')|trim) -%}
  {%- do item.setAttribute('data-menu-key', item.name) -%}
  {{ parent() }}
{% endblock %}
{% block spanElement %}
  <button type="button" class="menu-item group w-full" :class="…" @click="toggle('{{ item.name|e('js') }}')" aria-expanded="…">
    {{ block('iconElement') }}<span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ block('label') }}</span>
    {% if not item.extra('keep_open') %}{{ block('arrowElement') }}{% endif %}
  </button>
{% endblock %}
```

(`item.setChildrenAttribute('class', …'active')` at `sonata_menu.html.twig:17` unconditionally adds `active` to every child `<ul>` — an AdminLTE artefact; drop it.)

---

## 4. Top navbar

### 4.1 Breadcrumbs

- `render_breadcrumbs(admin, action)` → `Breadcrumb/breadcrumb.html.twig` (`S/src/Twig/BreadcrumbsRuntime.php:37-45`): for each KnpMenu item: `translation_domain` extra (default `messages`, `false` disables translation), `translation_params`; non-last items `<li><a href>` (raw if `safe_label` extra true, default **true** — `breadcrumb.html.twig:13-17`) or `<li><span>` when no uri; last `<li class="active"><span>`. Labels truncated to 100 chars via `|u.truncate(100, '...')`.
- `BreadcrumbsBuilder::buildBreadcrumbs()` produces: dashboard root (`extras.translation_domain = SonataAdminBundle`), admin list, subject (`translation_domain:false`, `safe_label:false`), child admin chains using `breadcrumbs.child_admin_route` (default `show`) (`S/src/Admin/BreadcrumbsBuilder.php:43-136`; `S/docs/reference/breadcrumbs.rst`).
- Tests pin the exact `<li>` markup (`S/tests/Twig/BreadcrumbsRuntimeTest.php:139-150`). **Do not change `Breadcrumb/breadcrumb.html.twig` markup**; change only the `<ol>` wrapper class in the layout (`standard_layout:135`) from `nav navbar-top-links breadcrumb` to `breadcrumb flex items-center gap-1.5 text-sm` and style `li a { @apply inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400 }`, `li.active span { @apply text-gray-800 dark:text-white/90 }`, separator via `li:not(:last-child)::after` (chevron SVG as `mask-image` or the `›` glyph). TailAdmin markup reference: `T/partials/breadcrumb.html:7-38`.
- Page `<h2>` title: TailAdmin shows `pageName` next to the breadcrumb. Sonata has `_navbar_title` (edit/show/list child) — use it as the `<h2>`; on dashboard/search use `title` block (`Core/dashboard.html.twig:14`, `Core/search.html.twig:14,17` — search already prints `<h2 class="page-header">`).

### 4.2 Add ("+") dropdown — `Core/add_block.html.twig`

Current logic (`S/src/Resources/views/Core/add_block.html.twig:1-72`):

1. `items_per_column = sonata_config.getOption('dropdown_number_groups_per_colums')` (default 2, `Configuration.php:363`, note the typo in the option name is public API).
2. `groups = get_sonata_dashboard_groups_with_creatable_admins()` (`S/src/Twig/GroupRuntime.php:48-63`: dashboard groups containing at least one admin with `create` route+access).
3. `column_count = ceil(groups|length / items_per_column)`; wrapper `<div class="dropdown-menu multi-column dropdown-add" style="width: {column_count*140}px">`, `container-fluid > row`, one `<ul class="dropdown-menu col-md-{12/column_count}">` per column, `<li class="divider">` between groups in a column, `<li class="dropdown-header">{icon}{label|trans(translation_domain|default(label_catalogue))}</li>`, then per admin `<li><a role="menuitem" tabindex="-1" href="{{ admin.generateUrl('create') }}">{{ admin.label|trans({}, admin.translationdomain) }}</a></li>` or one link per `subClasses` key with `{'subclass': key}`.
4. Groups iterated `|reverse`; role gate `group.roles is empty or is_granted(role_admin) or any`. Note `role_admin` here vs `role_super_admin` in the sidebar/search/list blocks (`Configuration.php:224-232` documents `role_admin` as "will see the top nav bar and dropdown groups").

TailAdmin replacement: panel `absolute right-0 mt-[17px] rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark` (`T/partials/header.html` notification panel classes), inside a CSS grid: `grid gap-4` with `grid-template-columns: repeat({{ column_count }}, minmax(10rem, 1fr))` (replace the inline `width` px computation; keep `column_count`/`items_per_column` maths so the option still has a visible effect). Group header → `<li class="dropdown-header px-3 pb-1 text-theme-xs uppercase text-gray-400 flex items-center gap-2">`; items → `<a class="flex items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5">` (user-dropdown item classes, `T/partials/header.html` user area). Keep `role="menuitem"`, `tabindex="-1"` and the `dropdown-add` / `multi-column` / `dropdown-header` / `divider` classes as markers. Button icon: `fas fa-plus-square` → TailAdmin round icon button with a "+" SVG. Wrap panel with `role="menu"` and add `aria-haspopup="true" :aria-expanded="dropdownOpen"` on the trigger.

### 4.3 User block — `Core/user_block.html.twig`

Default content is one Twig comment (`S/src/Resources/views/Core/user_block.html.twig:1`, "Customize this value"); the layout only renders the `<li class="dropdown user-menu">` when the include is non-empty (`standard_layout:165-175`). Third-party bundles (SonataUserBundle) provide a template emitting `<li>` items into `<ul class="dropdown-menu dropdown-user">`. Migration: TailAdmin user dropdown (avatar 44px `h-11 w-11 overflow-hidden rounded-full`, name `text-theme-sm font-medium`, chevron; panel `w-[260px] rounded-2xl border … p-3 shadow-theme-lg`; the `<ul class="flex flex-col gap-1 border-b … pt-4 pb-3">` receives `{{ userBlock|raw }}`; `<a>` styling by CSS on `.dropdown-user > li > a`). Sonata has no avatar/name concept in the layout — show `app.user.userIdentifier` as name and an initial-letter avatar; expose `sonata_top_nav_menu_user_block_trigger` sub-block for customization. Keep `dropdown-user` and `user-menu` classes.

### 4.4 Search

- Sonata: sidebar form (`standard_layout:192-202`), disabled by `sonata_admin.search: false` (`Configuration.php:253`; copied into `options.search` by `SonataAdminExtension.php:106`), submits `GET /admin/search?q=` to `SearchAction` (`S/src/Action/SearchAction.php:32-41`) which renders `templates.search` with `query` and `groups = pool.getDashboardGroups()`, choosing `ajax` vs `layout` base template.
- `Core/search.html.twig` (`:12-45`): `<h2 class="page-header">{{ 'title_search_results'|trans({'%query%': query}) }}</h2>`, then `<div class="row" data-masonry='{ "itemSelector": ".search-box-item" }'>` and for each group (role gate `role_super_admin`) and each admin with create or list access: `sonata_block_render({type:'sonata.admin.block.search_result'}, {query, admin_code, page:0, per_page:10, icon: group.icon})`. Masonry auto-initialises from the `data-masonry` attribute (`masonry-layout` imported in `app.js:38` — the "HTML init" path).
- `block_search_result.html.twig` (`:14-82`): `<div class="col-lg-4 col-md-6 search-box-item sonata-search-result-{show|fade|hide}">` (`show_empty_boxes` from `global_search.empty_boxes`, `Configuration.php:258-263`; CSS `styles.scss:550-560`: hide=`display:none`, fade=`opacity:.6`), `<div class="box box-solid">` with header (icon, `admin.label|trans`, `box-tools`: `<span class="badge">{count}</span>` or create `btn-box-tool`, list `btn-box-tool`), `<div class="matches">` of `<a class="label label-primary" href="list?filter[formName][value]=term">` per searchable filter, body `<ul class="nav nav-stacked sonata-search-result-list">` of `<a href="{{ admin.generateObjectUrl(admin_route, result) }}">{{ admin.toString(result) }}</a>` (`admin_route` from `global_search.admin_route`, default `show`) or "no_results_found".
- `AdminSearchBlockService` (`S/src/Block/AdminSearchBlockService.php:48-106`): settings `admin_code` (required), `query`, `page`, `per_page`, `icon` (default `fas fa-list`); 204 empty response when `searchHandler->search()` returns null; template from `templateRegistry->getTemplate('search_result_block')`.

TailAdmin replacement:
- Search input moves to the header (`T/partials/header.html:103-137`), keeps `name="q"`, form `action="{{ path('sonata_admin_search') }}"`, `role="search"`; Cmd/Ctrl+K and `/` shortcuts ported from `T/js/index.js:91-118`. "Search or type command…" → `'search_placeholder'|trans`.
- Results grid: replace `data-masonry` with CSS `grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 md:gap-6` (or `columns-1 md:columns-2 xl:columns-3` for masonry-like flow without JS). Drop `masonry-layout`. Keep `search-box-item` and `sonata-search-result-*` classes; implement `hide`→`hidden`, `fade`→`opacity-60`, `show`→`block` in CSS.
- Result card: TailAdmin card `rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]` (`T/partials/metric-group/metric-group-01.html`), header with `<h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">`, count badge `inline-flex items-center justify-center gap-1 rounded-full bg-brand-50 px-2.5 py-0.5 text-sm font-medium text-brand-500 dark:bg-brand-500/15 dark:text-brand-400` (`T/partials/badge/badge-01.html`), filter "matches" chips as light gray badges, results as `<ul class="sonata-search-result-list divide-y divide-gray-100 dark:divide-gray-800">`. Keep `box-tools` icon-button anchors as `inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-white/5`.
- TailAdmin's header search has no AJAX suggestions; Sonata's search is full-page. No behaviour is lost.

### 4.5 Dark-mode toggle & notifications (TailAdmin features without Sonata equivalent)

- Dark mode: TailAdmin toggles `darkMode` (`@click.prevent="darkMode = !darkMode"`, `T/partials/header.html`), persisted in localStorage key `darkMode` (`T/blank.html:16-18`). Add a `sonata_top_nav_menu_dark_mode` block (rendered before add/user) and a config option (see §6). Render the toggle button with `aria-pressed` and `aria-label`.
- Notifications dropdown: no Sonata concept; do not render, but add an empty `sonata_top_nav_menu_notifications` block so apps can fill it with TailAdmin markup (`T/partials/header.html` notification area, `x-data="{ dropdownOpen:false, notifying:true }"`).

---

## 5. Dashboard

### 5.1 PHP

| Piece | Behaviour | Evidence |
|---|---|---|
| Config `dashboard.blocks[]`: `type` (required), `roles[]`, `settings{}` (variable prototype), `position` (default `right`), `class` (default `col-md-4`); default set `[ {position:left, type:sonata.admin.block.admin_list, settings:[], roles:[]} ]`. | `S/src/DependencyInjection/Configuration.php:520-542` |
| Config `dashboard.groups{id}`: `label`, `translation_domain` (`label_catalogue` deprecated), `icon`, `on_top` (scalar!), `keep_open` (scalar!), `provider`, `items[]` (`admin` | `{route,label,route_params,route_absolute,roles}`), `item_adds[]`, `roles[]`; `provider` cannot coexist with `items`/`label`. | `Configuration.php:389-518` |
| `DashboardAction` buckets blocks into `top,left,center,right,bottom` by `position` (unknown positions raise a PHP warning/undefined key), passes `base_template` (ajax vs layout) and `blocks`. | `S/src/Action/DashboardAction.php:33-55` |
| `Core/dashboard.html.twig`: computes `has_{left,center,right,top,bottom}` honouring per-block `roles` via `is_granted_affirmative`; `sonata_block_render_event('sonata.admin.dashboard.top')`; top row `<div class="row"><div class="{{ block.class }}">block</div>`; middle row widths: 4/4/4, no center → 6/6, no left & no right → center 12; left/right columns omitted when both empty; bottom row like top; `sonata_block_render_event('sonata.admin.dashboard.bottom')`. | `S/src/Resources/views/Core/dashboard.html.twig:16-130` |
| Docs describe the zones (`TOP TOP TOP / LEFT CENTER RIGHT / BOTTOM BOTTOM BOTTOM`) and that `class` applies to top/bottom (`col-md-6`, `col-lg-3 col-xs-6`). | `S/docs/reference/dashboard.rst:328-366` |

### 5.2 Dashboard grid mapping

TailAdmin dashboard (`T/index.html:44-60`) uses `<div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6"><div class="grid grid-cols-12 gap-4 md:gap-6"><div class="col-span-12 space-y-6 xl:col-span-7">…</div><div class="col-span-12 xl:col-span-5">…</div>`. Mapping:

| Sonata | adminata |
|---|---|
| `.row` (top/bottom) | `<div class="grid grid-cols-12 gap-4 md:gap-6">` |
| `block.class` default `col-md-4` | Keep the option; emit `{{ block.class }}` **and** translate known Bootstrap column classes to Tailwind via a small Twig filter `sonata_grid_class` (`col-md-4`→`col-span-12 md:col-span-4`, `col-lg-3 col-xs-6`→`col-span-6 lg:col-span-3`, `col-md-6`→`col-span-12 md:col-span-6`, `col-md-12`→`col-span-12`). Unknown classes pass through untouched. Also change the config default to a Tailwind-native value? No — keep `col-md-4` as the default so existing config dumps stay identical; the filter handles it. |
| middle row `col-md-{4|6|12}` | `col-span-12 lg:col-span-{4|6|12}` with `space-y-4 md:space-y-6` for stacked blocks in a column |
| `sonata_block_render_event` hooks | unchanged |

### 5.3 Blocks

Base: `B/src/Resources/views/Block/block_base.html.twig:12-14` wraps every block in `<div id="cms-block-{{ block.id }}" class="cms-block cms-block-element">{% block block %}…{% endblock %}</div>`; `sonata_block.templates.block_base` is configurable (`B/src/DependencyInjection/Configuration.php:82-86`). Sonata's block templates all `{% extends sonata_block.templates.block_base %}` and override `block` — the extends line must stay so a custom block_base still applies.

| Template | Current markup | Settings (PHP) | TailAdmin replacement |
|---|---|---|---|
| `Block/block_admin_list.html.twig` (:14-58) | per group (role gate `role_super_admin`): `<div class="box"><div class="box-header"><h3 class="box-title">{label|trans(translation_domain|default(label_catalogue))}</h3></div><div class="box-body"><table class="table table-hover"><tbody>` rows `<td class="sonata-ba-list-label" width="40%">{admin.label|trans}</td><td><div class="btn-group">` + `{% include action.template|default('@SonataAdmin/CRUD/dashboard__action.html.twig') %}` per `admin.dashboardActions`. | `AdminListBlockService` settings `groups: false|string[]` filters `pool.getDashboardGroups()` by name; template from `templateRegistry->getTemplate('list_block')` (`S/src/Block/AdminListBlockService.php:37-64`). `getDashboardActions()` yields `create` (`label:link_add`, `translation_domain:SonataAdminBundle`, `template: action_create`, `url`, `icon: fas fa-plus-circle`) and `list` (`link_list`, `fas fa-list`), then `configureDashboardActions()` + extensions (`S/src/Admin/AbstractAdmin.php:1790-1820`). `dashboard__action.html.twig` = `<a class="btn btn-link btn-flat" href>{icon} {label|trans(translation_domain|default('SonataAdminBundle'))}`; `dashboard__action_create.html.twig` adds a subclass dropdown (`S/…/CRUD/dashboard__action*.html.twig`). | Card per group: `rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6` with header `<h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">` (`T/partials/table/table-01.html:1-10`); rows → `<table class="table-hover w-full">` or a `<ul class="divide-y divide-gray-100 dark:divide-gray-800">` list with `flex items-center justify-between py-3`; actions as TailAdmin link buttons `inline-flex items-center gap-1 rounded-lg px-2 py-1 text-theme-sm font-medium text-brand-500 hover:bg-brand-50 dark:hover:bg-brand-500/10`. Keep `sonata-ba-list-label`, `box`, `box-header`, `box-title`, `box-body` marker classes (users style them; SonataUser/Media dashboards rely on `.box`). Keep the `action.template|default(...)` include so custom dashboard actions work; the subclass dropdown in `dashboard__action_create` becomes an Alpine dropdown. |
| `Block/block_stats.html.twig` (:16-36) | `<div class="small-box {{ settings.color }}"><div class="inner"><h3>{count}</h3><p>{text|trans({'%count%':count}, translation_domain)}</p></div><div class="icon">{icon}</div><a class="small-box-footer" href="list?filter=…">{{ 'stats_view_more'|trans }} <i class="fas fa-arrow-circle-right">` | `AdminStatsBlockService` (`S/src/Block/AdminStatsBlockService.php:36-78`): `icon: fas fa-chart-line`, `text: Statistics`, `translation_domain: null`, `color: bg-aqua`, `code: false`, `filters: []`, `limit: 1000`, `template: @SonataAdmin/Block/block_stats.html.twig`; applies filters to the admin datagrid, builds pager, passes `pager`, `datagrid`, `admin`. Docs mention colours `bg-green, bg-red, bg-aqua, bg-yellow` (`dashboard.rst:266-274`). Note `translation_domain` falls back to `admin.translationDomain` in the template (`:14`). | TailAdmin metric card (`T/partials/metric-group/metric-group-01.html`): `rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6`, icon tile `flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800`, label `text-sm text-gray-500 dark:text-gray-400`, number `mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90`, footer link. Map AdminLTE colours to TailAdmin tokens: `bg-aqua`→`blue-light`, `bg-green`→`success`, `bg-red`→`error`, `bg-yellow`→`warning`, `bg-blue`→`brand`, `bg-purple`→`theme-purple`, `bg-orange`→`orange`; apply as icon tile tint (`bg-{c}-50 text-{c}-500 dark:bg-{c}-500/15`) and keep the raw `settings.color` string as an extra class for user CSS. Keep the `settings.template` default path (asserted in `S/tests/Block/AdminStatsBlockServiceTest.php:48`). |
| `Block/block_admin_preview.html.twig` (:16-93) | `<div class="box box-primary" id="{inlineAnchor}">` header (icon, `<h3 class="box-title"><a href="#anchor">{text|trans}</a>`), body `table-responsive no-padding` when results: `sonata_block_render_event('sonata.admin.list.table.top')`, blocks `list_header`, `table_header` (`<thead><tr class="sonata-ba-list-field-header">` with `sonata-ba-list-field-header-{type}`, `header_class`, `header_style`, `label_icon`, label translation), `table_body` (includes `get_admin_template('outer_list_rows_' ~ admin.getListMode(), admin.code)`), `table_footer`, `<div class="box-footer"><a class="btn btn-primary btn-block" href="list?filter=…"><i class="fas fa-list"> {{ 'preview_view_more'|trans }}`; else `no_result_content` block with nested `info-box`; `sonata_block_render_event('sonata.admin.list.table.bottom')`. | `AdminPreviewBlockService` (`S/src/Block/AdminPreviewBlockService.php:39-129`): settings `text: Preview`, `filters: []`, `icon: false`, `limit: 10`, `code: false`, `template`, `remove_list_fields: [ListMapper::NAME_ACTIONS]`; handles `_sort_by`/`_sort_order` by injecting a fake Request; `checkAccess('list')`. | Card with header row (`T/partials/table/table-01.html:1-22`) and TailAdmin table (`table-01.html` body: `<table class="min-w-full"><thead class="border-y border-gray-100 dark:border-gray-800"><th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500">`), wrapper `max-w-full overflow-x-auto custom-scrollbar`. The `<tbody>` rows come from the list dimension's `outer_list_rows_*` templates — the `sonata-ba-list` table class must remain so those rows/fields render identically to the list page. Footer "View more" as TailAdmin secondary button full width. Keep all five inner block names (`list_header`, `table_header`, `table_body`, `table_footer`, `no_result_content`). |
| `Block/block_rss_dashboard.html.twig` (:12-31) | extends `@SonataBlock/Block/block_core_rss.html.twig` (`B/src/Resources/views/Block/block_core_rss.html.twig:12-46`, Bootstrap `panel`), overrides `block`: `<div class="box box-warning">` header `<h3 class="box-title sonata-feed-title"><i class="fas fa-rss"> {{ settings.title }}</h3>`, `<div class="sonata-feeds-container list-group">` of `<a class="list-group-item" href rel="nofollow" title><strong>{title}</strong><div>{description|raw}</div></a>` or "No feeds available." | `sonata.block.service.rss` settings (`title`, `url`, `translation_domain`, `class`, `icon`) from block-bundle; Sonata docs example `dashboard.rst:176-191`. This template is *not* wired to any Sonata service by default — block-bundle's RSS service uses its own template; this file exists for apps that set `settings.template`. | Card + `divide-y` list of `<a class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5">` with `<strong class="text-gray-800 dark:text-white/90">` and `<div class="text-sm text-gray-500 dark:text-gray-400 prose-sm">`. Keep `sonata-feeds-container`, `sonata-feed-title`, `list-group-item` classes. Untranslated literal "No feeds available." exists upstream — keep or add a translation key (behaviour change: none). |
| `Block/block_search_result.html.twig` | see §4.4 | `AdminSearchBlockService` | see §4.4 |
| Third-party: `sonata.block.service.text` (`B/…/block_core_text.html.twig:12-16`, raw `settings.content`) is the documented second block (`dashboard.rst:176-185`). Since it emits user HTML with no wrapper, adminata's dashboard column should wrap **each** rendered block in a card *only if* the block doesn't already render one — impossible to detect generically. Recommendation: don't wrap; document that text blocks should use TailAdmin classes; optionally add a `sonata.admin.block.card` wrapper block type. | | | |

### 5.4 The `.box` family and `Admin.setup_*`

AdminLTE `.box`, `.box-header`, `.box-body`, `.box-footer`, `.box-tools`, `.btn-box-tool`, `.info-box`, `.small-box`, `.collapsed-box` appear across all block templates, and elsewhere in CRUD templates. Provide a `@layer components` shim in adminata's CSS that maps these classes onto TailAdmin card styles (`.box { @apply rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] }`, `.box-header { @apply flex items-center justify-between px-5 pt-5 }`, `.box-title { @apply text-lg font-semibold text-gray-800 dark:text-white/90 }`, `.box-body { @apply p-5 }` …). This keeps third-party dashboard blocks (SonataUser, SonataMedia, SonataPage, doctrine-orm `block_audit.html.twig`) visually acceptable without edits — the single highest-leverage compatibility measure for this dimension.

---

## 6. Flash messages

Source: `TW/src/Bridge/Symfony/Resources/views/FlashMessage/render.html.twig:12-58`, included from `standard_layout:297` inside block `notice`.

Behaviour:
- Iterates `sonata_flashmessages_types()` (handled types) → `sonata_flashmessages_get(type)`; CSS class from `sonata_flashmessages_class(type, 'default')`. Default types/classes: `success` ← `success, sonata_flash_success, sonata_user_success`; `warning` ← `warning, sonata_flash_info`; `danger` ← `error, sonata_flash_error, sonata_user_error`; class defaults to the type key unless `css_class` configured (`TW/src/Bridge/Symfony/DependencyInjection/SonataTwigExtension.php:47-70`; config tree `sonata_twig.flashmessage.{name}.{css_class,types}` at `TW/…/Configuration.php:54-64`).
- `collapse` (default 1): if more messages than `collapse`, renders **one** `.alert.alert-{class}.alert-dismissible.collapsed-box` with a hidden checkbox `.read-more-state#toggle-more-{i}`, `.read-more-wrap` containing the first message inline and the rest inside `<span class="read-more-target">`, plus `<label for=… class="read-more-trigger"><span class="more">more ▼</span><span class="less hide">less ▲</span><span class="badge badge-default">{n}</span></label>` — pure CSS toggle (`:checked ~ .read-more-wrap .read-more-target`, `TW/…/public/css/flashmessage.css:1-29`, duplicated in `S/assets/scss/flashmessage.scss`). `base.js:14-30` swaps `.more/.less` visibility on change and destroys iCheck on `.read-more-state` (`remove_iCheck_in_flashmessage`).
- Otherwise one `.alert.alert-{class}.alert-dismissable` per message with `<button class="close" data-dismiss="alert" aria-label="{{ 'message_close'|trans({}, 'SonataTwigBundle') }}">&times;</button>` (Bootstrap alert dismiss JS).
- Messages are printed `|raw` (HTML allowed).
- Unrelated: the `sonata-readmore` Stimulus controller (`S/assets/js/controllers/readmore_controller.js`) is for long list cells (`.sonata-readmore-content`, `layout.scss`), not for flash messages.

adminata plan: the render template is owned by `sonata-project/twig-extensions`, which adminata cannot replace. Options: (a) override `@SonataTwig/FlashMessage/render.html.twig` in adminata's `Resources/views/bundles/SonataTwigBundle/…` — impossible from a bundle (only the app can override `templates/bundles/*`); (b) ship `@Adminata/FlashMessage/render.html.twig` and include it from the layout's `notice` block instead of `@SonataTwig/...`. Choose (b); keep the `notice` block so apps can revert. New markup per TailAdmin alert (`T/partials/alert/alert-success.html`, `TR/components/ui/alert/Alert.tsx:22-42`):

```html
<div class="alert alert-success rounded-xl border border-success-500 bg-success-50 p-4 dark:border-success-500/30 dark:bg-success-500/15" role="alert" x-data="{ show: true }" x-show="show">
  <div class="flex items-start gap-3">
    <div class="-mt-0.5 text-success-500">[svg]</div>
    <div class="grow text-sm text-gray-800 dark:text-white/90">{{ message|raw }}</div>
    <button type="button" class="close …" @click="show = false" aria-label="{{ 'message_close'|trans({}, 'SonataTwigBundle') }}">×</button>
```

Class map: `success`→`success-*`, `warning`→`warning-*`, `danger`→`error-*`, `info`/`default`→`blue-light-*`; keep `alert alert-{class}` marker classes because `sonata_twig.flashmessage.*.css_class` users expect their class name in the DOM. Collapse mode: reimplement with Alpine (`x-data="{ expanded: false }"`) but keep the `read-more-state/-wrap/-target/-trigger` classes and the `more`/`less` translation keys from `SonataTwigBundle` domain; drop the iCheck workaround. Keep `collapse` variable.

---

## 7. Config options: skin, logo, title, and the rest

| Option (path, default) | Where it is consumed | Recommendation |
|---|---|---|
| `sonata_admin.title` (`'Sonata Admin'`, `Configuration.php:251`) → `sonata_config.title` | `<title>`? no — only `logo` block `<span>` and `<img alt>` (`standard_layout:117,120`) | Keep. Also use as `<title>` suffix? Upstream uses `'Admin'|trans` (`:78`); keep upstream behaviour. |
| `sonata_admin.title_logo` (`bundles/sonataadmin/images/logo_title.png`, `:252`) → `sonata_config.logo` | `logo` block `<img src="{{ asset(sonata_config.logo) }}">` | Keep; ship `bundles/adminata/images/logo_title.svg` but keep the default *string* pointing at the Sonata path only if adminata also publishes `bundles/sonataadmin/images/logo_title.png` (it should, since adminata replaces the package and its `public/` dir). Add optional `title_logo_dark` and `title_logo_icon` (new keys, default null → reuse `title_logo`) to support TailAdmin's `logo-dark.svg`/`logo-icon.svg` (`T/partials/sidebar.html:12-25`, `dark:hidden` / `hidden dark:block`). |
| `options.logo_content` (`all|icon|text`, `:364-368`) | `logo` block | Keep semantics: `all` = img + text in expanded sidebar, icon when collapsed; `icon` = img only; `text` = text only (collapsed → first letter). |
| `options.skin` (enum of 12 AdminLTE skins, default `skin-black`, `:295-311`) | body class (`standard_layout:93`), `<meta sonata-config SKIN>` (`:38`), `SonataAdminExtension.php:94-100` appends `admin-lte-skins/{skin}.min.css` to stylesheets | Keep the enum (config validation must not break existing YAML) and keep emitting the class on `<body>` (`admin_lte_skin_class` block). Map skins to TailAdmin theme variants via CSS custom properties in `@theme`: `skin-black*`→neutral brand (gray-900 accents), `skin-blue*`→`brand` (default TailAdmin `#465fff`), `skin-green*`→`success`, `skin-purple*`→`theme-purple`, `skin-red*`→`error`, `skin-yellow*`→`warning`; `*-light` variants → light sidebar (`bg-white`), non-light → dark sidebar (`dark:bg-black` colours forced). Implement as `.skin-blue { --color-brand-500: … }` overrides in a single `app.css`; **stop** appending a per-skin stylesheet in the DI extension (or append an empty/optional file to preserve `remove_stylesheets` semantics — see risk R6). Add new option `options.theme` (`light|dark|system`, default `system`) to seed `darkMode`; deprecate `skin` in docs but keep it functional. |
| `options.dropdown_number_groups_per_colums` (2, `:363`) | `Core/add_block.html.twig:1,5,19,27` | Keep as the column-split factor of the add-dropdown grid (§4.2). |
| `options.use_bootlint` (false, `:314`) | `standard_layout:334-341` | Keep node; render nothing; log a deprecation when true. Remove `docs/cookbook/recipe_bootlint.rst`. |
| `options.use_select2`, `use_icheck`, `use_stickyforms`, `confirm_exit`, `js_debug` | body classes `sonata-select2`/`sonata-icheck` (`:94-95`), meta config (`:38-43`), select2 locale (`:68-73`), sticky controller (`shouldLoad` reads `USE_STICKYFORMS`, `sticky_controller.js:137-139`) | Layout keeps emitting classes/meta. iCheck is dead with Tailwind forms — keep the option (no-op) and class. select2 is the form dimension's call; the layout must keep the `sonata-select2` class and locale script guarded by the option. |
| `assets.{stylesheets,extra_stylesheets,remove_stylesheets,javascripts,extra_javascripts,remove_javascripts}` with `{path, package_name}` normalisation (`Configuration.php:618-690`, `SonataAdminExtension.php:236-285` merge/remove) | `stylesheets`/`sonata_javascript_pool` blocks | Keep the config shape and the merge algorithm exactly. Change **defaults** to `bundles/adminata/app.css` / `bundles/adminata/app.js` (plus keep `bundles/sonataform/app.*` only if `form-extensions` assets are still needed). Apps that `remove_stylesheets: ['bundles/sonataadmin/app.css']` will silently stop matching — document in UPGRADE. Consider publishing under `bundles/sonataadmin/` (same asset package alias `sonata_admin`, `core.php:64`) so existing `extra_*`/`remove_*` paths and `asset('…','sonata_admin')` calls keep resolving. |
| `search` (true, `:253`) → `options.search` | `standard_layout:192` | Keep; gates the header search. |
| `global_search.empty_boxes` (`show|fade|hide`), `global_search.admin_route` (`show`) | `AdminSearchBlockService` ctor args (`block.php:41-42`), `block_search_result.html.twig:18,61-63` | unchanged |
| `security.role_admin`, `role_super_admin` → `options.role_admin/role_super_admin` (`SonataAdminExtension.php:105-106`) | `add_block:12` (role_admin); `sonata_menu:12`, `search:23`, `block_admin_list:16` (role_super_admin) | unchanged |
| `templates.{layout, ajax, dashboard, search, user_block, add_block, list_block, search_result_block, tab_menu_template, knp_menu_template, action_create}` (`Configuration.php:567-608`; asserted in `S/tests/DependencyInjection/SonataAdminExtensionTest.php:315-347`) | `TemplateRegistry` via `get_global_template()` | Keep every key **and** the default `@SonataAdmin/...` paths. Since adminata replaces the package, `@SonataAdmin` namespace resolves to adminata's `Resources/views` — defaults stay byte-identical, which is the core of the drop-in promise. |
| `breadcrumbs.child_admin_route` (`show`, `:279-286`) | `BreadcrumbsBuilder` | unchanged |
| `dashboard.blocks[].class` (`col-md-4`, `:540`) | `Core/dashboard.html.twig:59,120` | see §5.2 |

---

## 8. Twig block-name compatibility contract

All names below must exist with the same nesting (a child template may override any of them with `{{ parent() }}`).

**`standard_layout.html.twig`** (33 blocks): `html_attributes`, `meta_tags`, `stylesheets`, `javascripts`, `sonata_javascript_config`, `sonata_javascript_pool`, `sonata_head_title`, `body_attributes`, `admin_lte_skin_class`, `sonata_header`, `sonata_header_noscript_warning`, `logo`, `sonata_nav`, `sonata_breadcrumb`, `sonata_top_nav_menu`, `sonata_top_nav_menu_add_block`, `sonata_top_nav_menu_user_block`, `sonata_wrapper`, `sonata_left_side`, `sonata_side_nav`, `sonata_sidebar_search`, `side_bar_before_nav`, `side_bar_nav`, `side_bar_after_nav`, `side_bar_after_nav_content`, `sonata_page_content`, `sonata_page_content_header`, `sonata_page_content_nav`, `tab_menu_navbar_header`, `sonata_admin_content_actions_wrappers`, `sonata_admin_content`, `notice`, `bootlint`.

**Child-provided blocks captured by the layout via `block('…') is defined`** (`standard_layout:12-23`; must keep being captured **before** `<html>`): `preview`, `form`, `show`, `list_table`, `list_filters`, `tab_menu`, `content`, `title`, `breadcrumb`, `actions`, `navbar_title`, `list_filters_actions`. Also `side_menu` and `formactions` are overridden by `CRUD/preview.html.twig:17-21` although the layout never defines `side_menu` (harmless; keep tolerant).

**`ajax_layout.html.twig`**: `content`, `preview`, `form`, `list`, `show` (`:12-20`).

**`empty_layout.html.twig`**: overrides `sonata_header`, `sonata_left_side`, `sonata_nav`, `sonata_breadcrumb`, `stylesheets`, `sonata_wrapper`, `sonata_page_content`.

**`Menu/sonata_menu.html.twig`** (KnpMenu renderer blocks): `root`, `item`, `linkElement`, `spanElement`, `label` (plus inherited `compressed_root`, `list`, `children` from `knp_menu.html.twig`, `M/vendor/knplabs/knp-menu/src/Knp/Menu/Resources/views/knp_menu.html.twig:11-101`).

**`Core/tab_menu_template.html.twig`**: `item`, `dividerElement`, `linkElement`, `spanElement`, `dropdownElement`, `label` (`:3-132`).

**`Core/dashboard.html.twig`** / **`Core/search.html.twig`**: `title`, `breadcrumb`, `content`.

**`Block/*`**: `block` (all); `block_admin_preview` adds `list_header`, `table_header`, `table_body`, `table_footer`, `no_result_content`.

**Block-bundle base** (`block_base.html.twig`): `block`; `block_container.html.twig`: `block_class`, `block_role`, `block`, `block_child_render` (`B/src/Resources/views/Block/block_container.html.twig:15-29`).

**New blocks proposed (additive only)**: `sonata_preloader`, `sonata_overlay`, `sonata_header_search`, `sonata_top_nav_menu_dark_mode`, `sonata_top_nav_menu_notifications`, `sonata_top_nav_menu_user_block_trigger`, `sonata_sidebar_title`, `sonata_page_title`, `iconElement`/`arrowElement` in the menu template.

**Twig functions/filters relied upon by these templates** (must keep names & signatures): `sonata_config` global, `get_global_template`, `get_admin_template`, `render_breadcrumbs`, `render_breadcrumbs_for_title`, `get_sonata_dashboard_groups_with_creatable_admins`, `parse_icon`, `canonicalize_locale_for_select2`, `is_granted_affirmative`, `sonata_block_render`, `sonata_block_render_event`, `sonata_flashmessages_*`, `knp_menu_render`, `stimulus_controller/target/action`, `|u.truncate`.

---

## 9. Tab menu (`Core/tab_menu_template.html.twig`) → TailAdmin tabs

### 9.1 Current behaviour

- `AbstractAdmin::getSideMenu($action, $childAdmin)` delegates to the parent admin when the admin is a child (`S/src/Admin/AbstractAdmin.php:925-931`); `buildTabMenu()` creates `root` with children attribute `class="nav navbar-nav"`, extra `translation_domain = admin translation domain`, calls `configureTabMenu()` and every extension's `configureTabMenu()` (`:2586-2612`). Documented usage adds children with `generateMenuUrl(...)` (`S/docs/reference/child_admin.rst:38-55`).
- CRUD templates render it in block `tab_menu` with `knp_menu_render(admin.sidemenu(action), {currentClass:'active', template: get_global_template('tab_menu_template')}, 'twig')` (`CRUD/base_list.html.twig:18-23`, `base_edit.html.twig:34-39`, `base_show.html.twig:26-31`, `action.html.twig:18-25`, `delete`, `batch_confirmation`, `tree.html.twig:38`).
- The template (`S/src/Resources/views/Core/tab_menu_template.html.twig`):
  - `item` (:3-75): reads item **attributes** `dropdown`, `divider_prepend`, `divider_append` (then nulls them out); classes `currentClass`/`ancestorClass` (supports KnpMenu 1.x/2.x matcher), `firstClass`/`lastClass`; children get `menu_level_{level}`; `dropdown` adds `dropdown` to `<li>` and `dropdown-menu` to the child `<ul>`; renders `dropdownElement` | `linkElement` | `spanElement` then `list`.
  - `dividerElement` (:77-83): `<li class="divider-vertical">` at level 1, `<li class="divider">` deeper.
  - `linkElement`/`spanElement` (:85-103): optional `item.attribute('icon')|parse_icon` + label.
  - `dropdownElement` (:105-119): `<a href="#" class="dropdown-toggle" data-toggle="dropdown">{icon}{label}<b class="caret"></b>`.
  - `label` (:121-132): `item.getLabel()|trans(extra translation_params, extra translation_domain ?? parent's translation_domain)` — tested by `S/tests/Menu/Integration/TabMenuTest.php:32-89` (nominal, params, domain override) via `renderMenu()` with a *mocked matcher* (`BaseMenuTestCase.php:65-69`), so the template must keep rendering when `matcher` is defined but `isCurrent/isAncestor` return null.
- The DOM sits in `standard_layout:243-247` (`<div class="navbar-left">`) inside the Bootstrap page navbar.

### 9.2 Target

The TailAdmin HTML template has **no tabs component**. React/Next provide `ChartTab.tsx` (`TR/components/common/ChartTab.tsx`, identical in `TN`), a segmented control:

```html
<div class="flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
  <button class="px-3 py-2 font-medium w-full rounded-md text-theme-sm hover:text-gray-900 dark:hover:text-white
                 shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800">Monthly</button>   <!-- active -->
  <button class="px-3 py-2 font-medium w-full rounded-md text-theme-sm text-gray-500 dark:text-gray-400">Quarterly</button>
</div>
```

Proposed `Core/tab_menu_template.html.twig`: keep all six blocks and the attribute contract; root `<ul>` gets `nav navbar-nav flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900 sonata-tab-menu` (keep `nav navbar-nav` because `buildTabMenu` hard-codes it as the children class — `AbstractAdmin.php:2594` — and the template *merges* it); `li.active > a` → `rounded-md px-3 py-2 text-theme-sm font-medium shadow-theme-xs bg-white text-gray-900 dark:bg-gray-800 dark:text-white`; inactive → `text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white`; `dropdown` items → Alpine dropdown (`x-data="{open:false}" @click.outside="open=false"`; keep `dropdown-toggle` class and `<b class="caret">`→chevron svg); dividers → `<li class="divider-vertical h-6 w-px bg-gray-300 dark:bg-gray-700" role="separator">`. Add `role="tablist"`/`role="tab"`/`aria-current="page"` on the active link. For many tabs (child admin with 6+ entries) the segmented control must wrap: use `flex-wrap`, or an underline-tab variant (`border-b border-gray-200 dark:border-gray-800`, active `border-b-2 border-brand-500 text-brand-500`) — recommend underline tabs for the page toolbar because they scale, and use the segmented control for the list-mode switcher (§2 row 28).

---

## 10. Accessibility, dark mode, responsive requirements

**Accessibility (current gaps and what TailAdmin adds/needs)**

- Sonata: sidebar toggle is `<a href="#" role="button">` with `sr-only` text (`standard_layout:126-129`); group headers are `<a href="#">`; dropdown toggles `<a href="#" data-toggle="dropdown">`; caret `<b>`; navbar-brand `href="#"`; no `aria-expanded`, no `aria-current`. Flash close buttons have `aria-label` (`render.html.twig:23`). Icons have `aria-hidden="true"` (`IconRuntime.php:37`).
- TailAdmin: uses `<button>` for toggles but ships **no** `aria-*` attributes (`T/partials/header.html:12-16`, `T/partials/sidebar.html:66-70`), decorative SVGs lack `aria-hidden`, and the viewport meta sets `user-scalable=no, maximum-scale=1.0` (`T/index.html:5-8`) — a WCAG 1.4.4 failure that Sonata also has (`standard_layout:34`).
- Requirements for adminata: `<html lang>`; `<button type="button">` for all toggles with `aria-expanded`/`aria-controls`; `aria-current="page"` on active menu links; `role="navigation"`+`aria-label` on sidebar/tab menus; `role="search"` on the search form (already there); `aria-hidden="true"` + `focusable="false"` on inline SVGs; focus-visible rings (`focus:ring-3 focus:ring-brand-500/10` pattern from the search input, `T/partials/header.html`); overlay `aria-hidden`; skip-to-content link before the sidebar; keep colour contrast: TailAdmin `text-gray-500` on white is ~4.6:1 (`#667085`), OK; `text-gray-400` (`#98a2b3`) on white is ~2.9:1 — avoid for text (TailAdmin uses it for group titles). Keep `<noscript>` warning. Remove `user-scalable=no`.
- `x-show`-based dropdowns need `x-cloak` styling (`[x-cloak]{display:none}`) to avoid flashing open panels before Alpine starts; TailAdmin doesn't define it — add it. Also Escape-to-close and focus return (TailAdmin only has `@click.outside`; the React Modal handles Escape, `TR/components/ui/modal/index.tsx:22-35`).

**Dark mode**

- Mechanism: class strategy `@custom-variant dark (&:is(.dark *))` (`T/css/style.css:6`); `dark` toggled on `<body>` by Alpine from `localStorage.darkMode` (`T/blank.html:15-19`); React variant toggles `document.documentElement` and stores `theme` (`TR/context/ThemeContext.tsx:23-37`). Choose the Alpine `<body>` variant to match the HTML template's partials.
- Flash-of-wrong-theme: `x-init` runs after Alpine boots; add an inline `<script>` in `<head>` (block `sonata_javascript_config`) that applies `dark` from localStorage/cookie before first paint, and honour `prefers-color-scheme` when no preference stored (new `options.theme: system`).
- Every element in the layout needs paired `dark:` classes (sidebar `dark:bg-black`, header `dark:bg-gray-900`, cards `dark:bg-white/[0.03] dark:border-gray-800`, body `bg-gray-50` → `dark:bg-gray-900`). Third-party markup (`.box`, `.alert`, `.table`) is covered by the compat shim (§5.4) which must include `dark:` variants.
- Logo: support light/dark logos (`title_logo_dark`), TailAdmin pattern `class="dark:hidden"` / `class="hidden dark:block"` (`T/partials/sidebar.html:12-17`).

**Responsive**

- Breakpoints are redefined by TailAdmin: `2xsm 375, xsm 425, sm 640, md 768, lg 1024, xl 1280, 2xl 1536, 3xl 2000` (`T/css/style.css:12-20`). Sidebar: off-canvas below `lg`, static at `lg` (`T/partials/sidebar.html:3`); header collapses right cluster behind `menuToggle` below `lg` (`T/partials/header.html:80-100, 139-142`); content `max-w-(--breakpoint-2xl)` centred (`T/blank.html:45`).
- Sonata's responsive rules to replace: `@media (width <= 768px)` making header relative, `.navbar.stuck` non-fixed, sidebar absolute (`layout.scss` lines ~375-400 of file); `hidden-xs` breadcrumb (`standard_layout:133`).
- Sticky toolbar: with TailAdmin the content column is the scroll container (`overflow-y-auto` on the content div, not `window`), so the existing `IntersectionObserver` in `sticky_controller.js:185-211` (which observes relative to the viewport) still works, but `position: fixed; width: calc(100% - 230px)` CSS must be replaced with `position: sticky; top: <header height>` inside the scrolling column. Also `ResizeObserver` logic is unaffected.
- Tables/preview blocks need `overflow-x-auto` wrappers (`T/partials/table/table-01.html` uses `max-w-full overflow-x-auto custom-scrollbar`).

---

## 11. "Drop-in" compatibility risk register (this dimension)

| ID | Risk | Severity | Mitigation |
|---|---|---|---|
| R1 | Apps override layout blocks (`sonata_top_nav_menu`, `sonata_left_side`, `logo`, `sonata_breadcrumb`, `side_bar_after_nav_content`, `sonata_admin_content_actions_wrappers`, `notice`) with **Bootstrap 3/AdminLTE markup** (`<li class="dropdown">`, `data-toggle`, `.box`). Their HTML will render unstyled/broken. | high | Keep block names & nesting; ship a CSS compat shim for `.box*`, `.alert*`, `.btn*`, `.dropdown-menu`, `.label`, `.badge`, `.nav-tabs`; ship a tiny JS shim that upgrades `[data-toggle="dropdown"]`, `[data-dismiss="alert"]`, `[data-toggle="collapse"]` to Alpine-free vanilla handlers. Document in UPGRADE. |
| R2 | `user_block`/`add_block` custom templates emit `<li>` for Bootstrap `.dropdown-menu`. | high | Always render a `<ul>` container that receives the include verbatim; style descendants via CSS. Keep the `|trim is not empty` guards. |
| R3 | Custom `knp_menu_template` (documented in `recipe_knp_menu.rst`) extends `@SonataAdmin/Menu/sonata_menu.html.twig` and overrides `spanElement`/`linkElement` producing AdminLTE treeview DOM; AdminLTE JS is gone so groups won't expand. | medium | Keep the `treeview`/`treeview-menu`/`active`/`keep-open` classes and add a vanilla-JS fallback that toggles `.treeview > a[href="#"]` siblings when Alpine attributes are absent. |
| R4 | Functional tests / Panther selectors: `.sidebar-menu .dynamic-menu a` (`S/tests/Functional/Controller/MenuTest.php:41`), `.breadcrumb`, `.alert-success`, `.sonata-ba-*`. The user's own fork tests target `.modal-content button[name="btn_create"]`, `.field-container .sonata-ba-action[title="Add new"]` (`M/tests/Functional/*.php`). | medium | Preserve marker classes listed in §1.3/§2; keep `Breadcrumb/breadcrumb.html.twig` byte-identical. |
| R5 | `dashboard.blocks[].class` values are Bootstrap grid classes in every existing config. | medium | `sonata_grid_class` translation filter + pass-through (§5.2). |
| R6 | `assets.remove_stylesheets: [bundles/sonataadmin/app.css]` / `remove_javascripts` and `extra_*` relying on jQuery globals (`global.$`, `global.jQuery`, `global.stimulus`, `global.sonataApplication`, `app.js:87-90`). Third-party bundles' JS (SonataMedia, SonataPage, custom `extra_javascripts`) call `jQuery(...)`, `$.fn.select2`, `Admin.setup_*`. | high | Keep publishing under the same paths (`bundles/sonataadmin/app.css|app.js`) and keep exposing `window.$`, `window.jQuery`, `window.Admin`, `window.stimulus`, `window.sonataApplication` from adminata's bundle (jQuery can stay as a small dependency even if the UI no longer uses Bootstrap JS). Decide explicitly (open question). |
| R7 | `sonata-config` meta `SKIN` value and `options.skin` enum. | low | Keep both; map to theme. |
| R8 | The `sonata_sidebar_hide` cookie name/semantics (`sidebar.js:12-18`, `standard_layout:96`). | low | Keep; mirror into Alpine state. |
| R9 | `sonata-sticky` Stimulus controller identifiers/targets (`topNavbar`, `navbar`, `action`) used by CRUD form templates (`base_edit_form.html.twig` `sonata_form_actions`). | medium | Keep identifiers; rewrite the CSS for `.stuck`. |
| R10 | Flash message template is owned by `sonata-project/twig-extensions`; apps that override `@SonataTwig/FlashMessage/render.html.twig` in `templates/bundles/SonataTwigBundle/` will be bypassed if adminata includes its own copy. | medium | In the `notice` block, prefer the app override when it exists: `{% include ['@!SonataTwig/FlashMessage/render.html.twig' if overridden … ] %}` is not detectable in Twig; simplest: include `@SonataTwig/...` if `sonata_config.getOption('legacy_flash_template')`, else adminata's. Document. |
| R11 | `parse_icon` throws for non-FA strings (`IconRuntime.php:26-35`, tested). Users' `icon: 'fas fa-magic'` everywhere (`dashboard.rst:271,317`, group icons, `configureDashboardActions`). | high | Keep FontAwesome webfont in the bundle (5.15 free is already a dependency, `package.json`) *and* map common names to SVG. Never change `parse_icon`'s exception contract. |
| R12 | `empty_layout.html.twig` inline `<style>.content{margin:0;padding:0}</style>` relied upon by iframe/preview users. | low | Keep the rule under `.content` marker class too. |
| R13 | `Core/dashboard.html.twig` and `Core/search.html.twig` are commonly copied into apps (`templates.dashboard`/`templates.search` overrides) and contain `<div class="row">`/`col-md-*`. | medium | Compat shim: minimal `.row { @apply grid grid-cols-12 gap-4 }` + `.col-md-N { @apply col-span-12 md:col-span-N }` utilities generated for N=1..12 and `col-xs/sm/lg/xl`. Cheap and removes most breakage. |
| R14 | `AdminStatsBlock` `color: bg-aqua` etc. (`AdminStatsBlockService.php:71`; docs). | low | Colour map (§5.3), pass-through of the raw class. |
| R15 | Twig `deprecated` tag usage for `listModes settings.class` (`standard_layout:253-256`, `ajax_layout:32-35`) must be retained — removing it changes deprecation output that apps' tests (`symfony/phpunit-bridge` deprecation thresholds) may count. | low | Keep. |
| R16 | Search page `data-masonry` and `search-box-item` columns; third-party `search_result_block` overrides use `col-lg-4 col-md-6` and `.box`. | low | CSS grid + shim. |
| R17 | Persistence bundles: doctrine-orm-admin-bundle's `Block/block_audit.html.twig` and the user's MongoDB fork's `Form/*` themes do not touch the layout — no risk from this dimension; their form themes are the form dimension's concern. | none | — |

---

## 12. Concrete migration checklist for this dimension

1. `standard_layout.html.twig`: rewrite wrapper markup per §1.3/§2 keeping all 33 blocks and the 12 captured child blocks; move logo into sidebar header; move search into header; add Alpine `x-data` on body seeded from cookie/localStorage; add `sonata_preloader`, `sonata_overlay`, `sonata_header_search`, `sonata_top_nav_menu_dark_mode`, `sonata_top_nav_menu_notifications`; keep `<meta name="sonata-config">`, select2 locale include, `sonata-sticky` targets; `bootlint` block empty.
2. `ajax_layout.html.twig`: same content blocks; replace navbar/container/row with flex/grid; extract shared `Core/list_mode_buttons.html.twig`.
3. `empty_layout.html.twig`: same overrides; replace inline style with a body class.
4. `Menu/sonata_menu.html.twig`: per §3.4 (buttons, per-group Alpine state map with `$persist`, `keep_open`, `on_top`, role gate, translation chain, `sidebar-menu` class, `treeview` classes).
5. `Core/add_block.html.twig`: grid columns from `dropdown_number_groups_per_colums`; Alpine dropdown in the layout; keep role gate and subclass links.
6. `Core/user_block.html.twig`: keep empty; layout provides TailAdmin trigger + `<ul>`.
7. `Core/dashboard.html.twig`: grid rows/cols per §5.2, `sonata_grid_class` filter, unchanged role/has_* logic and render events.
8. `Core/search.html.twig`: TailAdmin page header + CSS grid instead of masonry.
9. `Core/tab_menu_template.html.twig`: per §9.2.
10. `Breadcrumb/*.html.twig`: unchanged (tests); layout wrapper class change only.
11. `Block/block_admin_list`, `block_stats`, `block_admin_preview`, `block_rss_dashboard`, `block_search_result`: TailAdmin cards per §5.3/§4.4, all inner block names retained, `extends sonata_block.templates.block_base` retained.
12. `CRUD/dashboard__action.html.twig`, `dashboard__action_create.html.twig`: TailAdmin link buttons; Alpine subclass dropdown.
13. Flash messages: adminata-owned copy of `FlashMessage/render.html.twig` included from `notice` (§6).
14. JS: drop `admin-lte`, `bootstrap`, `jquery-slimscroll`, `masonry-layout`, `icheck` (decision on jQuery/select2/x-editable belongs to the form/list dimensions but the layout must keep `window.$` if they stay); add `alpinejs` + `@alpinejs/persist`; rewrite `sidebar.js` (cookie) in vanilla; add Stimulus controllers `sonata-search-shortcut` (Cmd+K), `sonata-theme` (dark mode pre-paint), keep `sonata-sticky` with new CSS.
15. CSS: Tailwind v4 `app.css` importing `@import "tailwindcss"`, `@custom-variant dark`, TailAdmin `@theme` tokens (`T/css/style.css:8-160`), TailAdmin `@utility` menu classes, adminata `@layer components` compat shim for `.box*`, `.alert*`, `.row/.col-*`, `.badge`, `.label`, `.btn*`, `.dropdown-menu`, `.nav-tabs`, `.small-box`, `.info-box`, `.noscript-warning`, `.sonata-search-result-*`, `.stuck`, `.read-more-*`.
16. Config: keep every node; add `options.theme`, `title_logo_dark`, `title_logo_icon`; deprecate `use_bootlint`, `use_icheck`, `skin` (still functional); change asset defaults (decide path, R6).
17. Tests to port/keep green: `SonataAdminExtensionTest` template map, `BreadcrumbsRuntimeTest` HTML, `TabMenuTest` rendering with mocked matcher, `GroupMenuProviderTest`, `MenuBuilderTest`, `DashboardActionTest`, `SearchActionTest`, `Admin*BlockServiceTest` (settings defaults incl. template paths), functional `MenuTest` (`.sidebar-menu .dynamic-menu a`). Add new Twig integration tests that render `standard_layout` with a stub `sonata_config` and assert the presence of every block-name marker and Alpine attributes.
