# 08 — Testing and QA

Sources: `R/packaging.md` §3, `R/js-assets.md` §10, `R/gap-real-app-audit.md` §3.4 (scenario
table, re-checked against `APP/` in appendix C), `MDB/AGENTS.md` §6–7 (quality bar).

## 1. What happens to Sonata's 155 test files

| Group | Renders templates? | Effect | Action |
|---|---|---|---|
| `tests/Controller/CRUDControllerTest.php` (4,640 lines) | No (Twig mocked; asserts template names) | none | reuse verbatim |
| `tests/Form/*` (34) incl. `AdminLayoutTest`, `Widget/*` | Yes; XPath on Bootstrap classes | fail by design | keep tests, rewrite expectations; still assert `sonata-ba-field-container-*`, `sonata-ba-field-help`, `required` |
| `tests/Twig/RenderElementRuntimeTest.php` + Extension twin (452 expectations) | Yes | `<td …objectId>` envelope unchanged; ~20 badge/x-editable expectations change | freeze envelope, re-baseline the rest (Q1) |
| `BreadcrumbsRuntimeTest`, `SonataAdminRuntimeTest`, `Menu/Integration/*`, `TabMenuTest` | Yes | breadcrumb unchanged; menu/tab markup changes | update menu/tab expectations; keep `active` |
| `tests/Functional/Controller/*` (5, stub `ModelManager`) | Yes, full pages | hooks kept (`.sonata-ba-list-field`, `.sonata-ba-collapsed-fields label`, `.sonata-ba-field-help`, `div[id$=_referenced]`, `.sidebar-menu … a`, `selectButton('OK')`) | reuse as-is |
| `tests/DependencyInjection/*` (12), `FormMapperTest`, `ShowMapperTest`, `ConfigurationTest` | No | P6 changes (removed nodes, new defaults) | update deliberately |
| everything else | No | none | reuse; keep green through the Rector/PHP 8.4 pass |
| `tests/App/*` (stub app) | — | kept as the lightweight kernel | add the ORM demo app alongside |

Tests for deferred templates (history, ACL, association widgets) keep asserting the inherited
markup until those templates are ported.

The suites of the six other packages are imported with them (`packages/<name>/tests`) and run as
separate PHPUnit suites: doctrine-extensions, exporter and the ORM bundle unchanged;
block-bundle's too, but since the merge of 2026-09-06 (01 P10) they live in
`packages/admin-bundle/tests/` under `Sonata\AdminBundle\Tests\` and run inside the `admin`
suite — with the names that would have collided disambiguated (`BlockConfigurationTest`,
`BlockTweakCompilerPassTest`, `BlockGlobalVariablesTest`, ~~`BlockFunctionalTest`, `tests/BlockApp/`~~
— since 01 P13 the block test application is admin-bundle's own `tests/App`, and the block render
test is `Functional/Controller/BlockDemoControllerTest` beside the other functional tests);
twig-extensions' flash-message tests re-baselined to the rewritten template; form-extensions' type
tests updated for the derived HTML5 formats (P6 f), its widget tests rewritten for the native
inputs, and its Vitest suite (`datepicker_controller.test.js`, `setup.test.js`) deleted with the
controller.

**Amended, 2026-09-06 (01 P14).** Those last two suites moved as well, on the same terms:
form-extensions' `Bridge/Symfony/*WidgetTest` are `packages/admin-bundle/tests/Form/Widget/`, its
type tests `tests/Form/Type/`, its validator tests `tests/Validator/`, and the two DI
`ConfigurationTest`s are disambiguated as `FormConfigurationTest` and `TwigConfigurationTest`
beside admin's own; twig-extensions' runtime tests are `tests/Twig/`, its test application
`tests/TwigApp/`, and its functional test `tests/Functional/TwigFunctionalTest.php`.
form-extensions' duplicate `Foo` entity fixture is dropped in favour of admin's
`tests/Fixtures/Bundle/Entity/Foo.php`, and `SonataFormBundle`/`SonataTwigBundle` come out of the
three test kernels.

**Amended, 2026-09-07 (01 P15).** The exporter's suite moved on the same terms and for the same
reason: `packages/admin-bundle/tests/Exporter/{ExporterTest,HandlerTest,Source/*,Writer/*}` under
`Sonata\AdminBundle\Tests\`, inside the `admin` suite, with the three DI tests beside admin's own
as `tests/DependencyInjection/{ExporterConfigurationTest,SonataExporterExtensionTest}` and
`tests/DependencyInjection/Compiler/ExporterCompilerPassTest` — the last move also settling a PSR-4
warning the file had carried upstream, where the class declared a `…\Compiler\` namespace from one
directory up. `SonataExporterBundle` comes out of the test kernels. So `doctrine-extensions` and
the ORM bundle are the only suites still imported under a package directory of their own.

## 2. Demo / test application (Doctrine ORM + MySQL + fixtures)

`tests/App/OrmKernel` registering DoctrineBundle 3.3, FixturesBundle and
`SonataDoctrineORMAdminBundle` 4.21, with entities and admins that mirror recomaty-panel's usage
(appendix C §2):

- Field types (list + show): string, integer, datetime, date, time, boolean, enum, array, html,
  identifier, many_to_one, many_to_many, `_action` with standard and custom actions, custom cell
  templates (extending `base_list_field` and emitting raw `<td>`), `header_class`, sortable
  columns with `sort_field_mapping`.
- Form types: Text, Textarea, Integer, Number, Email, Url, Password, Choice, Enum, Entity (native
  select), Checkbox, form-extensions `BooleanType`, `CollectionType` with `allow_add/allow_delete`
  and sub-form entries (including time-only and datetime fields), `DatePickerType`,
  `DateTimePickerType` (calendar+clock, clock only), `DateRangePickerType`, `DateTimeRangePickerType`,
  `ModelAutocompleteType` (single, multiple, as filter field type), a ux-autocomplete field inside an
  admin form and as a filter field type, `help_html`, `attr.data-controller` pass-through, groups
  with `col-span-*` classes, `lock_protection`, `use_stickyforms`.
- Filters: String, Number, Choice, Callback, Model, Boolean, DateRange, DateTimeRange, DateTime,
  ModelAutocomplete; `persist_filters`.
- Pages: dashboard (admin_list block, groups with raw `<i>` icons and section headers injected
  through `ConfigureMenuEvent::SIDEBAR`), list (filters, sorting, pager variants, per-page, batch
  delete and a custom batch action with confirmation, export, XHR list through `ajax_layout`,
  `list_after_table` content, `templates.list` override), create/edit (errors, redirects for every
  `btn_*`, lock error), show, delete, custom `list__action_*` templates, `configureActionButtons`
  extra button, custom controller page on the layout with a plain Symfony form, login-style page
  with empty `logo`/`sonata_nav`, `empty_layout`, error pages; flash messages of every type.
- Fixtures loaded in `tests/custom_bootstrap.php` together with `assets:install --symlink`.
- Doubles as `make demo` and as the visual-regression target.

## 3. Browser-level tests

- **Panther 2.4** (Firefox/Selenium, the fork's harness with `PANTHER_SELENIUM_HOST` switch and
  docker-compose) for behavioural E2E in PHPUnit: sidebar collapse/persist, dark toggle, filters
  add/remove/reset, batch select-all and shift-range, per-page reload, collection add/delete,
  confirm-exit, autocomplete keyboard flow (single and multiple), dropdown keyboard navigation,
  `<dialog>` open/close, native date/time round trips; every test asserts an empty browser console.
- **Playwright 1.62** for visual regression: `toHaveScreenshot()` per page × viewport (375/768/1280)
  × theme (light/dark) against the demo app; committed baselines under `tests/Visual/__snapshots__`;
  `@axe-core/playwright` 4.13 for WCAG 2.1 AA (contrast in both themes, labels, `aria-expanded`,
  focus traps, combobox pattern); `html-validate` 11 over captured DOM.
- Separate suites so `make test-unit` stays browser-free.

## 4. Contract tests (phase 1 deliverables)

| Test | What it asserts |
|---|---|
| `HookContractTest` | every hook, id, data attribute and link text of document 02 §8/§10 appears in the compiled templates |
| `TemplatePathTest` | every registry path, every `@SonataAdmin/…` string in PHP and every `SHOW_TEMPLATES`/`LIST_TEMPLATES` path resolves |
| `DeferredTemplateTest` | the 33 deferred templates still carry the `{# adminata: not yet ported #}` marker and none is rendered by the demo app |
| `ConfigContractTest` | `config:dump-reference sonata_admin` equals 4.43.0 except the documented lines |
| JS contract snapshot (Vitest) | identifiers, targets, values, outlets, events from the built `app.js` equal `__contract__/controllers.json` |
| CSS contract (`bin/check-css-contract.mjs`) | every `contract.json` selector exists; forbidden selectors absent; size budgets; dead-class lint; `.dark` sanity |

## 5. JavaScript unit tests

Vitest 5 + jsdom 30 per controller on fixtures dumped from the real Twig templates (a PHPUnit test
writes `tests/fixtures/js/*.html`): collection add/delete + events; confirm-exit; edit `_tab`
restore; filter `prepareSubmit` (name stripping, `filters=reset`); filter-list counter/outlets;
per-page; readmore; revision (mocked `fetch`); sticky (mocked observers); layout cookie and
`inert`; menu open-state map; dropdown keyboard; modal lifecycle; theme cookie; dismiss; batch
select-all/indeterminate/shift-range; autocomplete (debounce, paging, `403`, keyboard, chips,
hidden inputs, `safe_label`).

## 6. Acceptance in recomaty-panel (phase 5)

Lives in the app: its Behat suite (`tests/bdd/Panel`, BrowserKit-driven Mink) already covers
sidebar sections, device edit/archive, batch archive, PocketRVM review, pool access and promotion
archive. Added on the migration branch:

| ID | Scenario | Driver |
|---|---|---|
| A1/A2 | login page renders and authenticates; login error alert | BrowserKit |
| D1/D2/D3 | `/admin/dashboard` redirect; stats page shell with sidebar groups, section headers, icons; user dropdown links | BrowserKit |
| L1–L9 | Recomat list with custom cells and row actions; text filter; `ModelAutocompleteType` filter JSON + combobox markup; ux-autocomplete filter untouched; sorting/per-page/pager; XHR child list keeps `table.sonata-ba-list`; CSV export; batch delete and custom batch archive with confirmation; list summaries in `list_after_table` | BrowserKit (+ Panther for the combobox) |
| F1–F10 | edit page structure with `col-span-*` groups and `_lock_version`; admin form with ux-autocomplete field; custom page with a plain form and native datetime input; time-only `07:30` round trip; optimistic lock error; collection add/remove; `btn_*` redirects; delete via `sonata.delete`; password change page; custom list-action pages | BrowserKit (+ Panther for F4, F6) |
| S1 | show page with custom show field | BrowserKit |
| X1 | dark mode: `sonata_theme=dark` cookie stamps `html.dark`; cell templates readable | Panther |
| X2 | contract smoke on every page: no `[data-toggle]`/`[data-dismiss]`, no `window.jQuery`, `window.sonataApplication` defined, empty console | Panther |

## 7. MongoDB fork job

`mongo-compat.yaml`, from phase 1: on every PR, check out `ideaconnect/sonata-admin-mongodb-bundle`,
install `idct/adminata:@dev` (path repo), `composer validate`, run its unit suite (blocking).
Nightly: its functional Panther suite with Mongo + Firefox (informational until the association
widgets exist and its modal scenarios are adapted to the non-AJAX flow).

## 8. Accessibility and responsive checks (definition of done items)

`<html lang dir>`; `<button>` toggles with `aria-expanded`/`aria-controls`; `aria-current` on
active links; `aria-hidden` on decorative SVGs; focus-visible rings; `aria-label`s on pager and
icon-only buttons; combobox pattern on autocomplete; skip link; no `user-scalable=no`; paired
`dark:` classes on every element; tables scroll inside their own `overflow-x-auto` wrapper;
dropdowns never clipped by `overflow-hidden` cards.
