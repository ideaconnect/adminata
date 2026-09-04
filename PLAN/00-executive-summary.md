# 00 — Executive summary

## Goal

Ship `idct/adminata` 1.0.0: the SonataAdminBundle 4.43.0 PHP layer with its Twig templates, CSS
and JavaScript replaced by a Tailwind CSS v4 / TailAdmin user interface (light and dark mode,
collapsible sidebar, cards, modern forms). The first release supports everything the production
app recomaty-panel uses; the remaining Sonata features are ported when first needed.

## What "drop-in" means here

The PHP surface that persistence bundles, admin classes and configuration touch stays identical:

- Composer identity via `replace: {"sonata-project/admin-bundle": "4.43.0"}` so
  `doctrine-orm-admin-bundle` 4.21.0 (`^4.39.0`) and `idct/sonata-admin-mongodb-bundle` v5.2.2
  (`^4.39`) resolve.
- PHP namespace `Sonata\AdminBundle`, bundle class `SonataAdminBundle`, Twig namespace
  `@SonataAdmin`, override directory `templates/bundles/SonataAdminBundle/`, public assets under
  `bundles/sonataadmin/`, config root `sonata_admin`, all 121 service ids, the 8 `sonata_admin_*`
  routes and their JSON contracts, the `SonataAdminBundle` translation domain, all 131 template
  file paths and the 39 template registry keys.

What is **not** preserved: Bootstrap and AdminLTE class names, AdminLTE skins, jQuery and every
jQuery plugin, iCheck, select2, x-editable, Tempus Dominus, the `options.skin`, `use_select2`,
`use_icheck` and `use_bootlint` config nodes, and the `window.Admin` facade. An app that relied on
those ports its overrides once (document 10).

## Approach in one paragraph

Fork the `4.43.0` tag with full history. Keep the PHP layer and sync it from upstream monthly.
Change PHP only where the UI forces it (config nodes, grid/box default strings, asset defaults, a
theme cookie). Rewrite the 98 templates recomaty-panel renders, group by group, in the TailAdmin
visual language; leave the 33 templates it never renders as inherited files with a tracked TODO.
Replace the JavaScript with 17 Stimulus controllers written in plain DOM code, no jQuery, no
third-party widgets except the `qs` query-string parser; modals are native `<dialog>`; date and time
fields are native HTML5 inputs; the autocomplete widget is a hand-written combobox. Build with
Vite 8 and Tailwind 4.3, commit the outputs under `src/Resources/public`, and document how an app
compiles Tailwind itself with `@source` on adminata's views. Ship Font Awesome 7 Free without
shims and the Outfit variable font. Stamp dark mode server-side from a cookie.

## Key numbers

| Item | Value |
|---|---|
| Templates rewritten in 1.0 / deferred | 98 files, 4,739 Twig lines / 33 files, 2,499 lines (appendix C §4) |
| Twig block names | 138 upstream; all kept in rewritten templates except `admin_lte_skin_class` and `bootlint` (document 02 §5) |
| PHP files changed in the fork | about 8 (document 01 P6); the other 234 are synced from upstream |
| Stimulus controllers in 1.0 | 17 (9 inherited, 8 new); 10 more planned post-1.0 |
| Third-party JS shipped | Stimulus 3.2.2, qs 6.16.0 — nothing else |
| Asset budgets (minified) | `app.css` ≤ 120 KB, `fontawesome.css` ≤ 80 KB, fonts ≤ 320 KB, `app.js` ≤ 100 KB |
| recomaty-panel surface | about 45 admin classes, 21 bundle overrides, 55 cell templates, 15 page templates, 31 `col-md-*` group classes, 3 jQuery files (appendix C) |
| Translations | 34 XLIFF locales inherited unchanged |

## Phases (one senior engineer)

| Phase | Weeks | Outcome |
|---|---|---|
| 0 Bootstrap | 1 | Repo from tag 4.43.0, `replace`, latest tooling, PHP changes, inherited tests green |
| 1 Foundations | 1.5 | Vite + Tailwind build, tokens, dark mode, FA7, fonts, controller registry, demo app skeleton, Playwright/Panther harness |
| 2 Shell | 1.5 | Layout, sidebar with section headers, header, user menu, breadcrumbs, flash messages, dashboard admin list, login/password page support |
| 3 List | 2 | List page, filters, typed cells, row actions, pager, batch + confirmation, export, XHR list, `list_after_table` |
| 4 Forms and show | 2 | Form theme, collections, native date/time inputs, autocomplete combobox, edit chrome, show, delete |
| 5 App migration and acceptance | 2 | recomaty-panel on a branch, Behat + BrowserKit + Panther scenarios, fixes |
| 6 Release | 0.5 | Docs, Packagist, `v1.0.0` |

About 10.5 weeks (document 09).

## Headline risks

1. Tailwind v4 emission semantics assumed from knowledge; verified by a fixture in phase 1.
2. Symfony Flex runs Sonata's recipe `unconfigure` when the real package is uninstalled and
   deletes `config/packages/sonata_admin.yaml`. Migration uses `--no-plugins --no-scripts`.
3. recomaty-panel's 55 cell templates carry Bootstrap and AdminLTE classes (`callout` alone appears
   316 times); porting them is the largest app-side task and has no shortcut by design.
4. The `idct/sonata-admin-mongodb-bundle` functional suite exercises association modals, which are
   post-1.0; its nightly job stays informational until they exist.
5. A hand-written autocomplete combobox must meet the ARIA combobox pattern; budgeted and tested.

## Owner decisions still open

Listed in document 11 §2 (font, dark-mode default, action-bar layout, Node line, PHP floor).
None blocks phase 0.
