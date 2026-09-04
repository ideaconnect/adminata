# 08 — Testing and QA

Sources: `R/packaging.md` §3, `R/js-assets.md` §10, `R/gap-css-architecture.md` §6.6,
`R/gap-real-app-audit.md` §3.4, `MDB/AGENTS.md` §6–7 (quality bar).

## 1. What happens to Sonata's 155 test files

| Group | Renders templates? | Effect | Action |
|---|---|---|---|
| `tests/Controller/CRUDControllerTest.php` (4,640 lines) | No (Twig mocked; asserts template names) | none | reuse verbatim |
| `tests/Form/*` (34) incl. `AdminLayoutTest`, `Widget/*` | Yes; XPath on Bootstrap classes | fail by design | keep tests, rewrite expectations; still assert `sonata-ba-field-container-*`, `sonata-ba-field-help`, `required` |
| `tests/Twig/RenderElementRuntimeTest.php` + Extension twin (452 expectations) | Yes | `<td …objectId>` envelope unchanged; ~20 badge/x-editable expectations change | freeze envelope, re-baseline the rest (D35) |
| `BreadcrumbsRuntimeTest`, `SonataAdminRuntimeTest`, `Menu/Integration/*`, `TabMenuTest` | Yes | breadcrumb unchanged; menu/tab markup changes | update menu/tab expectations; keep `active`, `treeview`, `nav navbar-nav` |
| `tests/Functional/Controller/*` (5, stub `ModelManager`) | Yes, full pages | hooks kept (`.sonata-ba-list-field`, `.sonata-ba-collapsed-fields label`, `.help-block.sonata-ba-field-help`, `div[id$=_referenced]`, `.sidebar-menu .dynamic-menu a`, `selectButton('OK')`) | reuse as-is |
| `tests/DependencyInjection/*` (12) | No | defaults change (asset lists) | update deliberately; each change is an UPGRADE item |
| everything else | No | none | reuse; keep green through the Rector/PHP 8.4 pass |
| `tests/App/*` (stub app) | — | kept as the lightweight kernel | add the ORM demo app alongside |

## 2. Demo / test application (Doctrine ORM + sqlite + fixtures)

`tests/App/OrmKernel` (or a second env) registering DoctrineBundle, FixturesBundle and
`SonataDoctrineORMAdminBundle`, with entities and admins covering every renderable path:

- Field types (list + show): every `TYPE_*` in `TemplateRegistryInterface` incl. associations,
  `_action` columns, editable booleans/choices, list/mosaic modes, `simple_pager_results`.
- Form types: `ModelType`, `ModelListType`, `ModelAutocompleteType`, `ModelHiddenType`,
  `ModelReferenceType`, `AdminType`, `CollectionType` (sortable), `ImmutableArrayType`,
  `TemplateType`, `ChoiceFieldMaskType`, `DatePickerType`/`DateTimePickerType`/
  `DateRangePickerType`, `BooleanType`, help texts, tabs + groups with `box_class`, `collapsed`,
  `description`, horizontal vs standard `form_type`.
- Filters: all `sonata.admin.form.filter.type.*` and ORM filters, `persist_filters`, advanced toggle.
- Pages: dashboard (all positions, stats/admin_list/search_result blocks, empty groups), global
  search, list (batch + confirmation, export, per-page, sorting, pager variants, mosaic), create/edit
  (errors, ajax/preview, nested `sonata_type_admin`, modal flows), show (+ compare), history,
  ACL, delete, `select_subclass`, `preview`, `ajax_layout`, `empty_layout`, child admin
  breadcrumbs + tab menus, custom action pages, error pages.
- **Override fixtures**: a `templates/bundles/SonataAdminBundle/…` copy of an old Bootstrap
  template and a `box_class`/`col-md-*` admin so the compat layer is exercised in CI.
- Fixtures loaded in `tests/custom_bootstrap.php` together with `assets:install --symlink`.
- Doubles as `make demo` and as the visual-regression target.

## 3. Browser-level tests

- **Panther** (Firefox/Selenium, the fork's harness with `PANTHER_SELENIUM_HOST` switch and
  docker-compose) for behavioural E2E in PHPUnit: sidebar collapse/persist, dark toggle,
  filters add/remove/reset, edit tabs with errors + `_tab`, ModelListType modal create/select,
  autocomplete search/select/create-and-select, collection add/delete + sortable renumbering,
  choice field mask, confirm-exit, batch shift-range, per-page reload, inline edit round trip,
  XHR list inside a modal, keyboard navigation of dropdowns; every test asserts an empty browser
  console.
- **Playwright** for visual regression: `toHaveScreenshot()` per page × viewport (375/768/1280)
  × theme (light/dark) against the demo app; committed baselines under `tests/Visual/__snapshots__`;
  `@axe-core/playwright` for WCAG 2.1 AA (contrast in both themes, labels, `aria-expanded`,
  focus traps); `html-validate` over captured DOM (duplicate ids from nested admins).
- Separate suites so `make test-unit` stays browser-free.

## 4. Contract and parity tests (phase 1 deliverables)

| Test | What it asserts |
|---|---|
| `ParityTest` (`tests/Parity`) | for all 131 templates, block-name set equality between upstream 4.43.0 and adminata (additions allowlisted); every `SHOW_TEMPLATES`/`LIST_TEMPLATES` path exists |
| `HookContractTest` | every hook, id, data attribute and link text of document 02 §8/§10 appears in the compiled templates |
| `ConfigParityTest` | `config:dump-reference sonata_admin` equals 4.43.0 except documented lines |
| JS contract snapshot (Vitest) | identifiers, targets, values, outlets, events, `window.Admin` members from the built `app.js` equal `__contract__/controllers.json` |
| CSS contract (`bin/check-css-contract.mjs`) | every `contract.json` selector exists; forbidden selectors absent; size budgets; dead-class lint; `.dark` sanity |
| `tests/Parity/parity.yaml` | per template: key, blocks, hooks, JS behaviours, a11y notes, status `todo/draft/parity/visual-approved` |

## 5. JavaScript unit tests

Vitest + jsdom per controller on fixtures dumped from the real Twig templates (PHPUnit test
writes `tests/fixtures/js/*.html`): collection add/delete + events + script re-activation;
confirm-exit; edit tabs; filter `prepareSubmit` (name stripping, `filters=reset`); filter-list
counter/outlets; per-page; readmore; revision (mocked `fetch`); sticky (mocked observers); every
new controller; `window.Admin` surface; jQuery bridge; Tom Select option mapping from
`data-sonata-select2-*`; editable payload shape; datepicker option/format conversion.

## 6. Real-app acceptance suite (`APP/`)

18 BrowserKit scenarios (login, dashboard shell and user dropdown, list/filter/autocomplete/
ux-autocomplete coexistence/sorting/XHR child list/export/batch delete/list summaries, edit
structure with `col-md-*` groups and lock protection, admin form with ux-autocomplete, non-admin
page with datepicker, time-only pickers, optimistic lock, collection add/remove, create/update
buttons, delete, password change, custom actions, show page) plus 6 Panther scenarios (pickers,
collections, autocomplete dropdown, `use_select2: true`, dark mode contrast, contract smoke).
Full table: `R/gap-real-app-audit.md` §3.4. Where it lives (app CI or adminata nightly with a
deploy key) is an owner decision.

## 7. Ecosystem compat jobs

Nightly: check out `ideaconnect/sonata-admin-mongodb-bundle`, `composer require idct/adminata:@dev`
(path repo), run `make test` with Mongo + Firefox; same for an ORM bundle 4.x checkout
(informational until stable).

## 8. Accessibility and responsive checks (definition of done items)

`<html lang dir>`; `<button>` toggles with `aria-expanded`/`aria-controls`; `aria-current` on
active links; `aria-hidden` on decorative SVGs; focus-visible rings; `role="tablist"`/`tab`;
`aria-label`s on pager, ACL checkboxes and icon-only buttons; skip link; no `user-scalable=no`;
paired `dark:` classes on every element; tables scroll inside their own `overflow-x-auto`
wrapper; dropdowns and popovers never clipped by `overflow-hidden` cards.
