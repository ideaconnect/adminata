# 00 — Executive summary

## Goal

Ship `idct/adminata` 1.0.0: the Sonata Admin PHP stack (seven packages, unchanged in API) with its
Twig templates, CSS and JavaScript replaced by a Tailwind CSS v4 / TailAdmin user interface (light
and dark mode, collapsible sidebar, cards, modern forms), in one repository and one Composer
package. The first release supports everything the production app recomaty-panel uses; the
remaining Sonata features are ported when first needed. The owner's separate
`idct/sonata-admin-mongodb-bundle` keeps working on top of it.

## What "drop-in" means here

The PHP surface that persistence bundles, admin classes and configuration touch stays identical:

- Composer identity via `replace` of the seven packages at their latest versions
  (`sonata-project/admin-bundle` 4.43.0, `block-bundle` 5.4.0, `doctrine-extensions` 2.6.0,
  `doctrine-orm-admin-bundle` 4.21.0, `exporter` 3.4.0, `form-extensions` 2.7.0, `twig-extensions`
  2.6.0), so `idct/sonata-admin-mongodb-bundle` v5.2.2 (`admin-bundle ^4.39`, `exporter ^3.0`,
  `form-extensions ^2.0`) resolves.
- The seven PHP namespaces, the seven bundle classes (`SonataAdminBundle`, `SonataBlockBundle`,
  `SonataDoctrineBundle`, `SonataDoctrineORMAdminBundle`, `SonataExporterBundle`, `SonataFormBundle`,
  `SonataTwigBundle`), the seven config roots, the Twig namespaces (`@SonataAdmin`, `@SonataBlock`,
  `@SonataForm`, `@SonataTwig`, `@SonataDoctrineORMAdmin`), all 121 admin-bundle service ids, the 8
  `sonata_admin_*` routes and their JSON contracts, the translation domains, all 148 template file
  paths and the 39 template registry keys.

What is **not** preserved: Bootstrap and AdminLTE class names, AdminLTE skins, jQuery and every
jQuery plugin (including the `ajaxSubmit` feature of association widgets), iCheck, select2,
x-editable, Tempus Dominus, the `options.skin`, `use_select2`, `use_icheck` and `use_bootlint`
config nodes, and the `window.Admin` facade. An app that relied on those ports its overrides once
(document 10).

## Approach in one paragraph

Import the seven upstream repositories at their latest tags into `packages/<name>/` with
`git subtree` (history kept), autoload them from one `composer.json`, and sync PHP fixes from
upstream monthly. Change PHP only where the UI forces it (config nodes, grid/box default strings,
asset defaults, a theme cookie, HTML5 date formats in form-extensions). Rewrite the 98 admin-bundle
templates recomaty-panel renders plus the flash-message and date-picker templates of
twig-extensions and form-extensions, group by group, in the TailAdmin visual language; copy the
12 Bootstrap-free templates of block-bundle and the ORM bundle unchanged; leave the 36 templates
the app never renders as inherited files with a tracked TODO. Replace the JavaScript with 17
Stimulus controllers written in plain DOM code, no jQuery, no third-party widgets except the `qs`
query-string parser; modals are native `<dialog>`; date and time fields are native HTML5 inputs;
the autocomplete widget is a hand-written combobox. Build with Vite 8 and Tailwind 4.3, commit the
outputs under the admin bundle's public directory, and document how an app compiles Tailwind itself
with `@source` on adminata's views. Ship Font Awesome 7 Free without shims and the Outfit variable
font. Stamp dark mode server-side from a cookie. Push `main` at every milestone.

## Key numbers

| Item | Value |
|---|---|
| Packages shipped | 7 (replacing `sonata-project/*` at their latest versions); the MongoDB fork stays external |
| Templates rewritten / copied unchanged / deferred | 100 / 12 / 36 of 148 (appendix C §4, document 03 §G) |
| Twig block names | 138 admin-bundle names kept in rewritten templates except `admin_lte_skin_class` and `bootlint` |
| PHP files changed in the fork | about 9 (document 01 P6); everything else is synced from upstream |
| Stimulus controllers in 1.0 | 17 (9 inherited, 8 new); 10 more planned post-1.0 |
| Third-party JS shipped | Stimulus 3.2.2, qs 6.16.0 — nothing else, and nothing that depends on jQuery |
| Asset budgets (minified) | `app.css` ≤ 120 KB, `fontawesome.css` ≤ 80 KB, fonts ≤ 320 KB, `app.js` ≤ 100 KB |
| recomaty-panel surface | about 45 admin classes, 21 bundle overrides, 55 cell templates, 15 page templates, 31 `col-md-*` group classes, 3 jQuery files (appendix C) |
| Translations | admin-bundle's 34 XLIFF locales and the other packages' catalogues inherited unchanged |

## Phases (one senior engineer)

| Phase | Weeks | Outcome |
|---|---|---|
| 0 Bootstrap | 1.5 | Repo with the seven packages imported, one `composer.json` with `replace`, latest tooling, PHP changes, inherited tests green, first milestone push |
| 1 Foundations | 1.5 | Vite + Tailwind build, tokens, dark mode, FA7, fonts, controller registry, demo app skeleton, Playwright/Panther harness, MongoDB fork CI job |
| 2 Shell | 1.5 | Layout, sidebar with section headers, header, user menu, breadcrumbs, flash messages (in place), dashboard admin list, login/password page support |
| 3 List | 2 | List page, filters, typed cells, row actions, pager, batch + confirmation, export, XHR list, `list_after_table` |
| 4 Forms and show | 2 | Form theme, collections, native date/time inputs (in place), autocomplete combobox, edit chrome, show, delete |
| 5 App migration and acceptance | 2 | recomaty-panel on a branch, Behat + BrowserKit + Panther scenarios, fixes |
| 6 Release | 0.5 | Docs, Packagist, `v1.0.0` |

About 11 weeks (document 09).

## Headline risks

1. Tailwind v4 emission semantics assumed from knowledge; verified by a fixture in phase 1.
2. Symfony Flex may run the Sonata packages' recipe `unconfigure` when the real packages are
   uninstalled. Migration uses `--no-plugins --no-scripts`; the recipe list is verified in document 11 §3.
3. recomaty-panel's 55 cell templates carry Bootstrap and AdminLTE classes (`callout` alone appears
   316 times); porting them is the largest app-side task and has no shortcut by design.
4. The MongoDB fork's functional suite exercises association modals, which are post-1.0 and will be
   redesigned without AJAX submission; its Panther job stays informational until then, its unit
   suite runs in PR CI from phase 1.
5. A hand-written autocomplete combobox must meet the ARIA combobox pattern; budgeted and tested.

## Owner decisions still open

Listed in document 11 §2 (seven bundle classes versus one, package layout, font, dark-mode
default, action-bar layout, Node line, PHP floor). None blocks phase 0.
