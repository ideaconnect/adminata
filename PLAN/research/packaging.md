# adminata — Packaging & distribution, project setup, quality gates, testing, CI, docs, licensing, roadmap skeleton

Research dimension report for the "adminata" project plan (drop-in replacement for SonataAdminBundle 4.43.0 with a Tailwind v4 / TailAdmin UI).

All paths are relative to the roots listed in the task unless absolute. Abbreviations used below:

| Abbrev | Root |
|---|---|
| `SA/` | `sonata-admin-4.43.0/` |
| `SA5/` | `sonata-admin-5.x/` |
| `ORM/` | `vendor-extract/doctrine-orm-admin-bundle/sonata-project-SonataDoctrineORMAdminBundle-2147390/` |
| `FE/` | `vendor-extract/form-extensions/` |
| `BB/` | `vendor-extract/block-bundle/` |
| `TA/` | `tailadmin-html/` |
| `MDB/` | `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle/` (the user's fork) |
| `FLEX/` | `/home/bartosz/dev/r3/recomaty-panel/vendor/symfony/flex/src/` (Flex v2.11.0 source used to verify recipe behaviour; not part of the scratchpad but present locally) |

---

## 0. Facts established from the local material

These are the load-bearing facts the rest of the report relies on. Each has a file:line citation.

### 0.1 Sonata Admin 4.43.0 packaging

| Fact | Evidence |
|---|---|
| Package `sonata-project/admin-bundle`, type `symfony-bundle`, MIT, keywords include `admin-lte` | `SA/composer.json:2-12` |
| Floors: `php ^8.2`, Symfony `^6.4 \|\| ^7.3 \|\| ^8.0`, `twig/twig ^3.15`, `symfony/stimulus-bundle ^2.22 \|\| ^3.0` | `SA/composer.json:26,38-61` |
| Runtime deps on the Sonata ecosystem: `block-bundle ^5.0`, `doctrine-extensions ^2.0`, `exporter ^3.1.1`, `form-extensions ^2.0`, `twig-extensions ^2.0` | `SA/composer.json:33-37` |
| Dev tooling: phpunit `^11.5.38`, rector `^1.1 \|\| ^2.0`, phpstan `^1.0 \|\| ^2.0`, php-cs-fixer `^3.4`, maker-bundle, security-acl, browser-kit/css-selector (no Panther) | `SA/composer.json:63-83` |
| PSR-4 `Sonata\AdminBundle\` → `src/`, tests `Sonata\AdminBundle\Tests\` | `SA/composer.json:87-95` |
| No `extra.branch-alias`, no `replace`/`provide`; one `conflict` (`symfony/security-acl <3.1 >=4.0`) | `SA/composer.json:84-86` |
| Bundle class `Sonata\AdminBundle\SonataAdminBundle` (final) registers 11 compiler passes; no `getPath()`/`getName()` override | `SA/src/SonataAdminBundle.php:31-46` |
| Extension does **not** implement `PrependExtensionInterface`; it auto-injects `user_block` template if `SonataUserBundle` is registered and `history_revision_timestamp` if `SonataIntlBundle` is registered | `SA/src/DependencyInjection/SonataAdminExtension.php:42-65` |
| Extension hard-appends `bundles/sonataadmin/admin-lte-skins/{skin}.min.css` to stylesheets **after** config processing (cannot be removed by `remove_stylesheets`) | `SA/src/DependencyInjection/SonataAdminExtension.php:94-100` |
| Extension hard-codes `sonata-medium-date` CSS class for Date/DateTime types and a `MopaBootstrapBundle` compatibility switch | `SA/src/DependencyInjection/SonataAdminExtension.php:198-215` |
| 103 service ids defined with `->set('sonata.admin.…')` across `src/Resources/config/*.php`; 7 string aliases (`sonata.admin.twig.extension`, `sonata.canonicalize.twig.extension`, …) + 10 FQCN aliases | grep over `SA/src/Resources/config/*.php` (list in §1.5) |
| Default `skin` enum (12 AdminLTE skins), `use_select2`, `use_icheck`, `use_bootlint`, `use_stickyforms`, `form_type: standard\|horizontal`, `default_icon: 'fas fa-folder'`, dashboard block `class: col-md-4` | `SA/src/DependencyInjection/Configuration.php:295-318,360,540` |
| Default asset lists: `bundles/sonataadmin/app.css` + `bundles/sonataform/app.css`; `bundles/sonataadmin/app.js` + `bundles/sonataform/app.js` | `SA/src/DependencyInjection/Configuration.php:631-634,666-669` |
| 39 template config keys (`templates.*`) with `@SonataAdmin/...` defaults, plus `form_theme`/`filter_theme` arrays | `SA/src/DependencyInjection/Configuration.php:567-614` |
| Asset package `sonata_admin` (`PathPackage`, `LastModifiedVersionStrategy`) | `SA/src/Resources/config/core.php:52-69` |
| Routes: `sonata_admin_redirect`, `sonata_admin_dashboard`, `sonata_admin_retrieve_form_element`, `sonata_admin_append_form_element`, `sonata_admin_short_object_information`, `sonata_admin_set_object_field_value`, `sonata_admin_search`, `sonata_admin_retrieve_autocomplete_items` | `SA/src/Resources/config/routing/sonata_admin.php:23-50` |
| Prebuilt, committed assets: `src/Resources/public` = 5.1 MB (`app.css` 345 KB, `app.js` 485 KB, 12 AdminLTE skins, `select2-locale/`, `fonts/`, `images/`, `entrypoints.json`, `manifest.json`) | `ls -la SA/src/Resources/public` |
| Source assets: `assets/js` (Stimulus controllers ×9, `admin.js`, `base.js`, `sidebar.js`, `treeview.js`), `assets/scss` (7 files), `assets/images` (3) | `find SA/assets` |
| Webpack Encore config outputs to `src/Resources/public`, manifest key prefix `bundles/sonataadmin`, copies AdminLTE skins + select2 locales | `SA/webpack.config.js:13-69` |
| Frontend toolchain: eslint 8 (airbnb-base + prettier + header plugin), prettier 2, stylelint 15 (scss), Encore 5, Node ≥18 | `SA/package.json:20-46,48-50`, `SA/.eslintrc.js:16-46` |
| `.gitattributes` export-ignores tests/docs/tooling **but ships `assets/`** (only `package.json`, `webpack.config.js`, etc. are ignored) | `SA/.gitattributes:5-20` |
| 131 Twig templates: CRUD 99, Button 6, Block 5, Core 5, Pager 5, Form 3 (`form_admin_fields`, `filter_admin_fields`, `Type/sonata_type_model_autocomplete`), Breadcrumb 2, Helper 2, Menu 1, 3 layouts | `find SA/src/Resources/views` |
| 142 distinct `{% block %}` names; `standard_layout.html.twig` alone defines 33 blocks (incl. `admin_lte_skin_class`, `bootlint`, `sonata_wrapper`, `sonata_left_side`, `side_bar_nav`, …) | grep over `SA/src/Resources/views` |
| Translation domain `SonataAdminBundle` referenced 117× in Twig and 42× in PHP; 34 XLIFF files `SonataAdminBundle.{locale}.xliff` | grep; `ls SA/src/Resources/translations` |
| PHP hard-codes 91 `@SonataAdmin/...` template paths: `TemplateRegistryInterface` defaults (`:27-66`), `RenderElementRuntime.php:68,91`, `BreadcrumbsRuntime.php:42,58`, `AdminPreviewBlockService.php:73`, `AdminStatsBlockService.php:76`, `CRUDController.php:544`, `ModelAutocompleteType.php:161`, `AddDependencyCallsCompilerPass.php:493-496` (pager_results ↔ simple_pager_results swap) | grep `@SonataAdmin/` in `SA/src --include=*.php` |
| PHP-side UI coupling to Bootstrap 3/AdminLTE/FontAwesome: `BaseGroupedMapper.php:83` (`'box_class' => 'box box-primary'`), `IconRuntime.php:27-35` (only `fa/fas/far/fab/fal/fad` prefixes accepted, else exception), `AbstractAdmin.php:1800,1809` (`fas fa-plus-circle`, `fas fa-list`), `AdminSearchBlockService.php:102`, `AdminStatsBlockService.php:69` (`fas fa-chart-line`), `TaggedAdminInterface.php:55,60` (`fas fa-list fa-fw`, `fas fa-th-large fa-fw`), `SonataAdminExtension.php:200-201` | grep |
| Class inventory: 242 PHP files; 163 `final class`, 51 `interface`, 12 `abstract class`, 0 traits, 0 enums, 0 attribute classes | grep over `SA/src` |
| Release cadence: 87 releases on 4.x; 17 releases between 4.36.0 (2025-03-11) and 4.43.0 (2026-06-03); 4.43.0 itself touched a **template** (`edit_many_script`), added stimulus-bundle 3 and collections 3 | `SA/CHANGELOG.md` headings; 4.43.0 entry |
| Sonata's stated branch policy: BC changes → `4.x`, BC breaks → `5.x`, `NEXT_MAJOR` markers | `SA/CONTRIBUTING.md:280-300` |
| 5.x branch = "Merge 4.x into 5.x" on 2026-06-04; `composer.json` identical constraints to 4.43.0; `src/` differs in only 3 files (`AbstractAdminExtension`, `AdminExtensionInterface`, `CRUDController`); `UPGRADE-5.0.md` only documents `preBatchAction`; views byte-identical | `git log -1` in `SA5/`; `diff -rq`; `SA5/UPGRADE-5.0.md:1-13` |
| Docs: Sphinx 6.2.1 + `sphinx_rtd_theme`, RTD config, DOCtor-RST in CI; install docs show `bundles.php` (8 bundles), `sonata_block` config, `sonata_admin` routes resource `@SonataAdminBundle/Resources/config/routing/sonata_admin.xml`, `assets:install` | `SA/docs/requirements.txt`, `SA/.readthedocs.yaml`, `SA/docs/getting_started/installation.rst:38-50,59-67,95-106,130-133` |

### 0.2 Ecosystem coupling (persistence bundles and siblings)

| Fact | Evidence |
|---|---|
| ORM bundle requires `sonata-project/admin-bundle ^4.39.0`; `provide: sonata-project/admin-bundle-persistency-layer 1.0.0`; dev: phpunit `^11.5.38 \|\| ^12.3.10`, Panther `dev-symfony-8-support` from a fork repo | `ORM/composer.json:28,52,60,73-75,76-81` |
| ORM ships 3 templates: `Form/form_admin_fields.html.twig` **extends `@SonataAdmin/Form/form_admin_fields.html.twig`** and includes `@SonataAdmin/CRUD/Association/edit_{one_to_one,many_to_many,many_to_one,one_to_many}.html.twig`; `Form/filter_admin_fields.html.twig` extends `@SonataAdmin/Form/filter_admin_fields.html.twig`; `Block/block_audit.html.twig` is raw Bootstrap 3 (`box box-primary`, `panel-group`, `data-toggle="collapse"`) | `ORM/src/Resources/views/Form/form_admin_fields.html.twig:12,17-29`, `.../filter_admin_fields.html.twig:12`, `.../Block/block_audit.html.twig:12-30` |
| ORM PHP hard-codes `@SonataAdmin/CRUD/Association/list_*.html.twig` and `@SonataAdmin/CRUD/list__action*.html.twig` | `ORM/src/Builder/ListBuilder.php:98-139` |
| ORM's compiler pass appends its form/filter themes to every `manager_type: orm` admin via `setFormTheme`/`setFilterTheme` | `ORM/src/DependencyInjection/Compiler/AddTemplatesCompilerPass.php:30-38` |
| ORM imports 41 distinct `Sonata\AdminBundle\…` classes; the MongoDB fork imports 34 | grep `use Sonata\\AdminBundle\\` |
| form-extensions 2.7.0 ships `Form/datepicker.html.twig` with Bootstrap 3 markup (`input-group date`, `input-group-addon`, `fas fa-calendar`), `app.scss` = `@import '@eonasdan/tempus-dominus'`, and `app.js` registers a Stimulus `datepicker` controller on **`global.sonataApplication`** | `FE/src/Bridge/Symfony/Resources/views/Form/datepicker.html.twig:12-40`, `FE/assets/scss/app.scss:10`, `FE/assets/js/app.js:14-18` |
| form-extensions uses **vitest + jsdom** for JS unit tests | `FE/vite.config.js:10-27` |
| block-bundle 5.4.0 templates use Bootstrap 3 (`panel panel-default`, `panel-heading`, `media`) | `BB/src/Resources/views/Block/block_core_rss.html.twig:15-35` |
| The MongoDB fork's own functional tests assert on Sonata DOM: `.sonata-ba-list-field-string[objectid=…] .sonata-link-identifier`, `.sonata-ba-view-container`, `.category_id` (form field class), button labels `Create and return to list`, `Update and close`, `Yes, delete`, and **Bootstrap's `.alert-success`** | `MDB/tests/Functional/CRUDTest.php:24,31,38-46,56-60,74-76` |

### 0.3 Sonata test-suite facts (relevant to template rewrite)

| Fact | Evidence |
|---|---|
| 155 `*Test.php` files; per dir: Form 34, Twig 18, DependencyInjection 12, Action 7, Util 7, Admin 6, Translator 6, Command 5, Datagrid 5, Functional 5, Menu 5, Route 5, Security 5, … | `find SA/tests -name '*Test.php'` |
| `tests/Controller/CRUDControllerTest.php` (4640 lines) **mocks** `Twig\Environment` → template-agnostic; it only asserts template *names* (`@SonataAdmin/CRUD/show.html.twig`, …) | `SA/tests/Controller/CRUDControllerTest.php:175-216,242-255,392-425` |
| Functional tests boot `tests/App/AppKernel.php` (MicroKernel, 10 bundles, `form_themes: ['@SonataAdmin/Form/form_admin_fields.html.twig']`) with a **stub `ModelManager`** (no Doctrine) over `Foo/Bar/Baz` repositories; 5 admins + a `CustomAdminExtension` | `SA/tests/App/AppKernel.php:37-51,104-108`, `SA/tests/App/Model/ModelManager.php:25-140`, `SA/tests/App/config/services.yml:14-106` |
| Functional `CRUDControllerTest` renders **full pages** and asserts CSS hooks: `.sonata-ba-list-field:contains("foo_name")`, `.sonata-ba-collapsed-fields label:contains("Name")`, `.help-block.sonata-ba-field-help`, `.sonata-ba-field li:contains(...)`, `div[id$=_referenced]`, `td:contains("foo_name")`, `selectButton('OK')` + `_sonata_csrf_token`, plus 11 URLs must return 200 | `SA/tests/Functional/Controller/CRUDControllerTest.php:38,50,54,69,83,98,113,130,143,155,176,194-208` |
| `MenuTest` asserts `.sidebar-menu .dynamic-menu a` (AdminLTE class) across 4 requests with `disableReboot()` | `SA/tests/Functional/Controller/MenuTest.php:32-45` |
| `DashboardActionTest` and `AdminAsParameterControllerTest` only assert HTTP 200 | `SA/tests/Functional/Controller/DashboardActionTest.php:29-35` |
| Form-theme tests render `src/Resources/views/Form/form_admin_fields.html.twig` through a real Twig `FilesystemLoader` and assert Bootstrap-3 XPath: `col-sm-3 control-label required`, `help-block sonata-ba-field-help help-text`, `form-group has-error`, `alert alert-danger` + `list-unstyled` + `fas fa-exclamation-circle`, `id="sonata-ba-field-container-name" class="form-group"` | `SA/tests/Form/AbstractLayoutTestCase.php:35-56`, `SA/tests/Form/AdminLayoutTest.php:31,113,148,163,178-191,216` |
| `RenderElementRuntimeTest` (and its `Extension` twin) load `src/Resources/views/CRUD` and assert exact `<td class="sonata-ba-list-field sonata-ba-list-field-{type}" objectId="12345">…</td>` output for every list/show field type (452 expectation lines) | `SA/tests/Twig/RenderElementRuntimeTest.php:92-97,240-682` |
| `BreadcrumbsRuntimeTest`, `SonataAdminRuntimeTest`, `Menu/Integration/*` also render real templates via `FilesystemLoader`/`StubFilesystemLoader` | `SA/tests/Twig/BreadcrumbsRuntimeTest.php:41-42`, `SA/tests/Twig/SonataAdminRuntimeTest.php:63-68`, `SA/tests/Menu/Integration/BaseMenuTestCase.php:27-32` |
| Files in `tests/` referencing Bootstrap/AdminLTE-specific classes: `btn` 8, `fa-` 13, `box` 6, `sonata-ba` 4, `dropdown` 4, `help-block` 2, `alert-` 1, `sidebar-menu` 1, `navbar` 1; `col-md`/`form-control` 0 | grep counts |

### 0.4 The user's MongoDB-fork conventions (precedent to match)

| Convention | Evidence |
|---|---|
| Hard fork, new package id, **no `composer replace`**, own version numbering starting at 5.0.0, "will keep diverging", upstream changes pulled selectively (not merged) | `MDB/README.md` "This is a HARD FORK" section; `MDB/AGENTS.md:71-90` |
| Floors `php ^8.4`, Symfony `^7.4 \|\| ^8.0`, `admin-bundle ^4.39`, persistence `^4.0`; PHPUnit 11/12 (suite written for 12) | `MDB/AGENTS.md:95-104`, `MDB/composer.json:313-330` |
| Author roster: maintainer first + Thomas Rabaix "Original author" + 72 upstream contributors, LICENSE with dual copyright + fork note | `MDB/composer.json:15-312`, `MDB/LICENSE:1-11` |
| Gates: PHPStan level 8 + bleedingEdge + strict rules (no baseline entries/ignores), Rector `UP_TO_PHP_84` + `PHPUNIT_120` + `PHPUNIT_CODE_QUALITY`, PHP-CS-Fixer `@PHP8x4Migration(:risky)`, `@PHPUnit9x1Migration:risky`, `@Symfony(:risky)`, `@PSR12(:risky)`, `setUnsupportedPhpVersionAllowed(true)`; Infection with `@default` mutators on the `unit` suite; "definition of done" = all four gates green | `MDB/AGENTS.md:106-111,356-380`, `MDB/rector.php:30-49`, `MDB/.php-cs-fixer.dist.php:23-85`, `MDB/phpstan.neon.dist`, `MDB/infection.json5.dist` |
| Composer scripts `test:cs`, `test:rector`, `test:phpstan`, `test:phpunit`, `test:infection`, `test:everything`, `infection`, `infection-ci` | `MDB/composer.json:381-406` |
| dev-kit Makefile kept verbatim (lint-*, cs-fix-*, test, coverage, docs, phpstan, rector) | `MDB/Makefile:1-121` |
| CI: `test.yaml` matrix PHP 8.4/8.5 × highest, plus Symfony `7.4.*`/`8.0.*` variants via Flex `SYMFONY_REQUIRE`; codecov-action v6 with token/slug/flags; `qa.yaml` runs PHPStan and Rector on **both** Symfony lines; `lint.yaml`, `symfony-lint.yaml`, `documentation.yaml` (Sphinx + DOCtor-RST), `mutation.yaml` nightly + `workflow_dispatch`, `stale.yaml`; actions pinned to `checkout@v6`, `composer-install@v4`, `setup-php@v2` | `MDB/.github/workflows/*.yaml` |
| Dependabot: composer weekly (groups symfony/doctrine/sonata/dev) + github-actions weekly | `MDB/.github/dependabot.yml` |
| Tests: `unit` and `functional` PHPUnit suites; Panther `ServerExtension`, `PANTHER_WEB_SERVER_DIR=./tests/App/public/`; Selenium-in-docker vs host Firefox switch via `PANTHER_SELENIUM_HOST`; fixtures + `assets:install --symlink` loaded in `tests/custom_bootstrap.php`; docker-compose with `mongo` + `selenium/standalone-firefox` | `MDB/phpunit.xml.dist:11-58`, `MDB/tests/Functional/BasePantherTestCase.php:27-53`, `MDB/tests/custom_bootstrap.php`, `MDB/docker-compose.yml` |
| Docs set: `README.md` (badges, HARD FORK banner), `AGENTS.md`, `BEST_VERSION.md` (severity-coded findings B/H/P/M + roadmap), `FIX.md` (referenced but absent in the checkout), `UPGRADE-5.0.md`, `CHANGELOG.md` (Keep-a-Changelog, semver), `WAR_AGAINST_THE_MUTANTS.md`, Sphinx `docs/` | `ls MDB/`; `MDB/CHANGELOG.md:1-60` |
| Style: `declare(strict_types=1)`, new classes `final` / `final readonly`, `#[\Override]`, Sonata header comment kept, no `@phpstan-ignore` | `MDB/AGENTS.md:403-419` |
| Fork's lock is on `sonata-project/admin-bundle 4.42.0` (one release behind 4.43.0) | `MDB/composer.lock` (queried) |
| Tool versions currently resolved there: phpunit 12.5.28, php-cs-fixer 3.95.3, rector 2.4.5, phpstan 2.2.1, infection 0.33.2, panther 2.4.0 | `MDB/composer.lock` (queried) |

### 0.5 TailAdmin facts

| Fact | Evidence |
|---|---|
| `LICENSE` = MIT, "Copyright (c) 2023 TailAdmin" (HTML, React and Next variants identical) | `TA/LICENSE:1-3`, `tailadmin-react/LICENSE.md:1-3`, `tailadmin-next/LICENSE:1-3` |
| **Discrepancy:** `TA/package.json` declares `"license": "ISC"` while `LICENSE` and README say MIT ("The community edition of TailAdmin is released under the MIT License") | `TA/package.json:20`, `TA/README.md` License section |
| v2.3.0 (2026-04-28); Tailwind v4 via `@tailwindcss/postcss`, `@tailwindcss/forms`, prettier 3 + `prettier-plugin-tailwindcss`; runtime deps Alpine 3.14 + `@alpinejs/persist`, flatpickr, apexcharts, chart.js, dropzone, fullcalendar, jsvectormap, swiper; Webpack 5 build with a custom `<include src>` HTML partial processor | `TA/package.json:3,23-48`, `TA/webpack.config.js:6-40`, `TA/postcss.config.js` |
| Free vs Pro: free = 1 dashboard, "30+ dashboard components, 50+ UI elements", community support; Pro = 7 dashboards, "500+ components", complete Figma system, email support | `TA/README.md` Feature Comparison |
| Free HTML template pages: 404, alerts, avatars, badge, bar-chart, basic-tables, blank, buttons, calendar, form-elements, images, index, line-chart, profile, sidebar, signin, signup, videos; partials: alert, avatar, badge, breadcrumb, buttons, calendar-event-modal, chart, header, overlay, preloader, sidebar, table, … | `ls TA/src`, `ls TA/src/partials` |
| React/Next free templates add `ui/{alert,avatar,badge,button,dropdown,images,modal,table}` components | `ls tailadmin-react/src/components/ui`, `ls tailadmin-next/src/components/ui` |

### 0.6 Composer `replace` semantics — empirically verified (Composer 2.9.7, offline path repositories)

Experiment directory: `scratchpad/replace-experiment/` (fake `sonata-project/admin-bundle 4.43.0`, fake `sonata-project/doctrine-orm-admin-bundle 4.20.0` requiring `sonata-project/admin-bundle ^4.39.0`, fake `sonata-project/user-bundle 5.15.0` requiring `^4.0`, and several `idct/adminata 1.0.0` variants).

| Case | adminata `replace` value | Root requires | Result |
|---|---|---|---|
| app1 | `"sonata-project/admin-bundle": "4.43.0"` | `idct/adminata ^1.0` + ORM bundle `^4.20` | **Resolves.** Lock = `idct/adminata 1.0.0 (replace 4.43.0)` + ORM bundle; real Sonata not installed. |
| app2 | `"self.version"` (adminata is 1.0.0) | same | **Fails**: "Only one of these can be installed: sonata-project/admin-bundle[4.43.0], idct/adminata[1.0.0]. idct/adminata replaces sonata-project/admin-bundle and thus cannot coexist with it." (1.0.0 does not satisfy `^4.39`). |
| app3 | `"4.43.0"` | `idct/adminata ^1.0` **and** `sonata-project/admin-bundle ^4.43` | **Resolves with adminata only** — an app that forgets to remove the old require still gets adminata as long as the constraint is satisfied by the replaced version. |
| app4 | `"4.43.0"` | only ORM bundle (adminata not required) | Composer installs the **real** Sonata; a replacer is never chosen unless something requires it. No accidental hijacking. |
| app5 | `"4.43.0"` | `idct/adminata ^1.0` + `sonata-project/admin-bundle ^4.44` | **Fails** (the replaced version 4.43.0 does not satisfy `^4.44`). This is what happens the day an ecosystem bundle bumps its floor above adminata's declared parity. |
| app6 | `"^4.43"` (a *range*) | `idct/adminata ^1.0` + ORM `^4.20` + `sonata-project/admin-bundle ^4.44` | **Resolves.** Composer accepts a constraint in `replace` and it satisfies any overlapping require — including versions adminata has not actually synced. `composer validate` accepts it (only warns about the `version` field used for the path repo). |
| app7 | `"4.43.0"` + `conflict: {"sonata-project/user-bundle": "*"}` | `idct/adminata ^1.0` + `sonata-project/user-bundle ^5.0` | **Fails** with a clear "idct/adminata 1.0.0 conflicts with sonata-project/user-bundle 5.15.0" — `conflict` is a usable knob to block unvalidated ecosystem bundles. |

Conclusions: an exact upstream version string in `replace` is the correct tool; `self.version` is only viable if adminata adopts Sonata's 4.x numbering; a range constraint "works" but lies to the resolver.

### 0.7 Symfony Flex behaviour with a replacing package — verified in Flex 2.11.0 source

| Behaviour | Evidence |
|---|---|
| Recipes are looked up by the **installed package's name** (`$package->getName()`) over the composer *operations*; a replaced package never becomes an install operation, so `sonata-project/admin-bundle`'s contrib recipe (if any) will **not** be applied when installing `idct/adminata` | `FLEX/Downloader.php:196-239`, `FLEX/Flex.php:645-719` |
| Flex auto-generates a recipe for any `type: symfony-bundle` package that has no published recipe: it scans PSR-4 autoload roots for classes ending in `Bundle` whose source contains `Symfony\Component\HttpKernel\Bundle\Bundle`/`AbstractBundle` and registers them in `config/bundles.php` | `FLEX/SymfonyBundle.php:14-35,76-94`; `FLEX/Downloader.php:210-231` (auto-generated recipe URL template) |
| When Composer **uninstalls** a package (which is exactly what happens to `sonata-project/admin-bundle` the moment adminata replaces it), Flex runs that package's recipe `unconfigure`, and `CopyFromRecipeConfigurator::unconfigure` deletes the recipe-copied files **without any content-hash check** (only files also owned by another recipe are spared) | `FLEX/Flex.php:680,514`, `FLEX/Configurator/CopyFromRecipeConfigurator.php:31-41`, `FLEX/Options.php:106-127` |

Consequence: if an existing app installed Sonata through the recipes-contrib recipe (the one that creates `config/packages/sonata_admin.yaml` and `config/routes/sonata_admin.yaml` — its existence and file list must be re-verified online), running `composer require idct/adminata` will delete those files (the user's customised config!) unless the command is run with `--no-plugins`/`--no-scripts` or the files are restored from git afterwards. This is a first-class migration hazard for Option B and must be documented (see §6).

---

## 1. Options analysis: how adminata is packaged

### 1.1 Option A — theme-overlay bundle

**Shape.** `idct/adminata` requires `sonata-project/admin-bundle ^4.43`. It ships:

- its own Twig templates and overrides Sonata's by registering `templates/` under the `@SonataAdmin` namespace with a higher priority (Symfony's TwigBundle registers bundle `Resources/views` under `@<BundleNameWithoutBundle>` — `normalizeBundleName()` strips `Bundle`, cf. `vendor/symfony/twig-bundle/DependencyInjection/TwigExtension.php:229-235` in `MDB/vendor`; an overlay can prepend `twig.paths: { '%kernel.project_dir%/vendor/idct/adminata/templates': SonataAdmin }` via `PrependExtensionInterface`, and paths added through config take precedence over bundle defaults, exactly like an app's `templates/bundles/SonataAdminBundle`), or — more robustly — rewrites `sonata_admin.templates.*` (all 39 keys, `SA/src/DependencyInjection/Configuration.php:570-608`) and `twig.form_themes` to point at `@Adminata/...`;
- its own compiled CSS/JS and prepends `sonata_admin.assets.{stylesheets,javascripts}` to `bundles/adminata/app.{css,js}`.

**What cannot be fixed from an overlay (PHP-side coupling), with citations:**

1. The AdminLTE skin stylesheet is appended by the extension *after* config processing (`SonataAdminExtension.php:94-100`) — it cannot be removed via `remove_stylesheets`; the overlay must ship a dummy `bundles/sonataadmin/admin-lte-skins/skin-black.min.css`... but that path belongs to Sonata's own public dir, which `assets:install` copies from Sonata. A compiler pass could rewrite the `sonata.admin.configuration` argument 2 (`options.stylesheets`) after the fact — hacky but possible.
2. `box_class` default `'box box-primary'` (`BaseGroupedMapper.php:83`) is emitted into every form/show group; an overlay must either map AdminLTE classes to Tailwind in CSS (`.box.box-primary { @apply … }` compat layer) or rewrite templates to ignore `box_class` (breaking users who set `box_class` on purpose).
3. `IconRuntime::parseIcon()` (`IconRuntime.php:27-35`) only accepts FontAwesome prefixes and produces `<i class="fas …">`; TailAdmin uses inline SVG. An overlay cannot change the runtime; it must keep FontAwesome (a 5.15 webfont is 1 MB+ of the current `app.css`/fonts) or decorate the `sonata.admin.twig.icon_runtime` service (service decoration is possible from another bundle — the runtime class is `final` but the *service* can be decorated with a new class implementing the same public method).
4. Hard-coded template paths in PHP (`RenderElementRuntime.php:68,91`, `BreadcrumbsRuntime.php:42,58`, `CRUDController.php:544`, `ModelAutocompleteType.php:161`, `TemplateRegistryInterface.php:27-66`, block services) all reference `@SonataAdmin/...` — fine for A *if* the overlay overrides the namespace path, but any Sonata patch release that adds a template or changes a block name silently breaks the overlay's copy (upstream changed `edit_many_script.html.twig` in 4.43.0 itself).
5. Every ecosystem bundle's own templates (`ORM/…/form_admin_fields.html.twig`, `FE/…/datepicker.html.twig`, `BB/…/block_core_rss.html.twig`) remain Bootstrap 3 regardless of A/B/C — see §1.7.
6. `use_icheck`, `use_select2`, `use_bootlint`, `use_stickyforms`, `skin`, `form_type: horizontal` config options (`Configuration.php:295-318`) remain accepted and either become no-ops or must be reinterpreted by the overlay's JS.

**Assessment.** Fidelity to the *PHP* API is perfect by construction (there is no PHP), upstream sync burden is lowest (Sonata keeps releasing; the overlay just re-tests), but the overlay is structurally fragile: template/block drift in upstream patch releases, the un-removable skin CSS, the `final` runtimes, and the fact that TailAdmin markup and Sonata's Bootstrap-3 form theme cannot coexist inside one `form_admin_fields` inheritance chain (the ORM bundle's theme `extends '@SonataAdmin/Form/form_admin_fields.html.twig'` and `include`s `@SonataAdmin/CRUD/Association/edit_*` — those includes would resolve to the overlay's rewritten versions, which is what we want, but the block *names* and *variables* they rely on become a frozen contract we do not control). It also contradicts the user's precedent (hard fork).

### 1.2 Option B — hard fork, same PHP namespace, `composer replace`

**Shape.** `idct/adminata` is the whole Sonata Admin 4.43.0 tree with templates and assets rewritten. `composer.json` keeps `Sonata\AdminBundle\` PSR-4, bundle class `Sonata\AdminBundle\SonataAdminBundle`, Twig namespace `@SonataAdmin`, service ids `sonata.admin.*`, config root `sonata_admin`, translation domain `SonataAdminBundle`, route names `sonata_admin_*`, asset package `sonata_admin` and public path `bundles/sonataadmin/` (because `assets:install` derives the directory from the bundle name: `SonataAdminBundle` → `sonataadmin`). It declares:

```json
"replace": { "sonata-project/admin-bundle": "4.43.0" }
```

**`replace` version string — decision (backed by §0.6):**

- `"self.version"` only works if adminata's own versions satisfy `^4.39` (app2 failed with 1.0.0). That would force adminata to number itself `4.43.x`/`4.44.x` forever, colliding with upstream numbers that mean something else, and it contradicts the fork precedent ("We do **not** sync release numbers with upstream", `MDB/README.md`).
- An exact upstream version (`"4.43.0"`) is the honest choice: it states "this package is API-equivalent to sonata-project/admin-bundle 4.43.0". It must be bumped **manually** every time the PHP layer is synced with a newer upstream tag (§4). It also means: the day `doctrine-orm-admin-bundle` requires `^4.44` and adminata has not synced, installs fail loudly (app5) — which is the *correct* failure mode, not a silent lie.
- A range (`"^4.43"`) resolves (app6) but claims parity with releases adminata has not merged; reject it, but keep it in mind as an emergency escape hatch users can apply in their root `composer.json` via a `replace` of their own (root-package `replace` is honoured by Composer too).
- Mutual exclusion with the real package is automatic ("cannot coexist", app2 output); no `conflict` entry against `sonata-project/admin-bundle` is needed. `conflict` *is* useful against ecosystem bundles adminata has not validated (app7).

**Packagist.** A package that `replace`s another is listed normally; Packagist shows a "Replaces" line on the adminata page. The Sonata page will not advertise adminata. Nothing prevents publishing (Composer's own docs use `replace` for forks; the user's precedent chose *not* to declare it — `MDB/README.md` — but that fork had no downstream bundles hard-requiring its name; adminata does: `ORM/composer.json:28`, `MDB/composer.json:320`).

**Flex.** Auto-generated recipe registers `Sonata\AdminBundle\SonataAdminBundle` (already present in existing apps' `bundles.php`, so idempotent). Existing apps' config/routes keep working (`sonata_admin:` root, `@SonataAdminBundle/Resources/config/routing/sonata_admin.xml`). Migration hazard: the uninstall of the real Sonata triggers its recipe's `unconfigure` (§0.7) — document `composer require idct/adminata --no-plugins` or "restore config from git".

**Drop-in fidelity.** PHP: 100 % at the tag we fork from. Templates: template *keys* and block names can be preserved (§3.4 parity checklist), CSS hooks that tests and downstream bundles depend on (`sonata-ba-*`, `sonata-link-identifier`, `sonata-ba-view-container`, form field `class` attributes, `alert-success`, `sidebar-menu`, button labels) must be deliberately kept as *semantic* classes in the Tailwind markup (§3.5). Anything that assumes Bootstrap 3 JS (`data-toggle="collapse"`, `.modal`, `.dropdown-menu`) in user overrides or ecosystem templates breaks unless a compat layer exists (§1.7).

**Ability to fix PHP-side coupling.** Full: change `box_class` default, teach `IconRuntime` to accept SVG/`heroicon:` names, drop the skin injection, retire `use_icheck`/`use_select2`/`use_bootlint`/`skin` (keep the config keys accepted but deprecated, so existing YAML does not explode — `Configuration.php:295-318` keys must stay parseable).

**User override path.** `templates/bundles/SonataAdminBundle/...` keeps working (bundle name unchanged). Users who overrode a Bootstrap-3 template get a broken page until they port it — inherent to any theme swap; mitigated by keeping block names and by a documented per-template diff.

**Upstream-sync burden.** Medium: Sonata releases ~monthly (17 releases in 15 months, `SA/CHANGELOG.md`); PHP changes are small and mostly mergeable (5.x differs from 4.43.0 in only 3 PHP files); template changes must be re-implemented by hand (§4).

**Consistency with precedent.** High: same hard-fork model, same gates, same doc set; the one deliberate deviation is declaring `replace`, which is required here because two other packages (one of them the user's own) `require` the upstream name.

### 1.3 Option C — hard fork, new namespace `Adminata\`, new bundle/Twig names

Breaks: `doctrine-orm-admin-bundle` (41 imported classes, `require sonata-project/admin-bundle ^4.39.0`), the MongoDB fork (34 imports), every `sonata.admin.*` service id lookup by compiler passes in persistence bundles (`MDB/AGENTS.md:57-63` explains they *must* use Sonata's ids), `@SonataAdmin` template `extends`/`include`s in ORM/form-extensions, `kernel.bundles['SonataAdminBundle']` checks in siblings (Sonata itself does this for `SonataUserBundle`/`SonataIntlBundle`, `SonataAdminExtension.php:49-65`, and siblings do the reverse), user code (`use Sonata\AdminBundle\Admin\AbstractAdmin` in every admin class), and the translation domain. It is not a drop-in; it would need adapter bundles for each persistence layer. Only worth it if the goal changes to "a new admin framework inspired by Sonata".

### 1.4 Option D — hybrid: B now, namespace migration to `Adminata\` later with `class_alias` BC layer

Honest feasibility analysis for ~240 symbols (`SA/src`: 163 final classes, 51 interfaces, 12 abstract classes, 0 traits/enums/attributes):

What works:
- `class_alias()` works for classes **and interfaces** (both are class entries in the engine); `instanceof`, type hints, `implements`, `extends` all resolve through the alias. `final` is irrelevant to aliasing.
- Aliases can be lazily created by an `spl_autoload_register` callback registered from a Composer `files` autoload entry that maps `Sonata\AdminBundle\X` → require the real `Adminata\X` then `class_alias('Adminata\X', 'Sonata\AdminBundle\X')`. This is the pattern used by e.g. Laminas' `laminas-zendframework-bridge`.

What does not work or costs a lot:
- `Foo::class` is a compile-time string: `Sonata\AdminBundle\Admin\Pool::class` stays `'Sonata\AdminBundle\Admin\Pool'`. Every DI FQCN alias (`->alias(Pool::class, …)`, `SA/src/Resources/config/core.php`), every autowiring by interface in user apps, every `#[Autowire]`/`service(FooInterface::class)` in persistence bundles needs a **duplicate DI alias** under the old name — 10 FQCN aliases today plus whatever users autowire (`AdminInterface`, `ModelManagerInterface`, `Pool`, `TemplateRegistryInterface`, …). Symfony's `registerForAutoconfiguration(ModelManagerInterface::class)` (`SonataAdminExtension.php:225-231`) keys by FQCN string; needs both.
- Serialized/cached data holding class names (the compiled container, route cache `sonata.admin.route.cache`, session-persisted filters) survive only if the name written is the one that exists after the flip; a cache clear is mandatory.
- PHPStan/Psalm/IDEs do not understand runtime `class_alias`; downstream bundles running level-8 PHPStan against `Sonata\AdminBundle\…` would need stub files. Rector's `RenameClassRector` config would be needed by every consumer.
- Every upstream file merged after the rename must be namespace-rewritten (sed-able, but every cherry-pick conflicts on the `namespace`/`use` lines).
- The bundle class: `Adminata\AdminataBundle` could override `Bundle::getName()` to return `'SonataAdminBundle'` so `kernel.bundles`, `@SonataAdminBundle/...` resource paths, `bundles/sonataadmin` and the `@SonataAdmin` Twig namespace stay — but Flex's auto-recipe would then insert `Adminata\AdminataBundle::class` next to the aliased `Sonata\AdminBundle\SonataAdminBundle::class` in `bundles.php`, and Symfony throws on two bundles with the same name. Solvable (docs + recipe), but it is one more trap.

Verdict: technically feasible, ~1–2 weeks of mechanical work plus a permanent BC-layer maintenance tax and permanent PHPStan friction for consumers, with **no user-visible benefit** while the PHP API is intentionally identical to Sonata's. Do not plan it. Revisit only if/when adminata's PHP layer diverges enough that "it is Sonata" stops being true (that is the point at which `replace` must be dropped anyway).

### 1.5 Scoring

Scale 1 (worst) – 5 (best).

| Criterion | A overlay | B fork + replace | C fork, new ns | D hybrid |
|---|---|---|---|---|
| Drop-in fidelity (PHP API, DI ids, config, template keys, Twig ns) | 5 (PHP untouched) / 3 (template drift, un-removable skin CSS) | 5 | 1 | 5 now, 3 after flip |
| Effort to first release | 3 (templates + assets + compat hacks) | 3 (templates + assets + fork plumbing) | 2 | 3 then +2 weeks |
| Upstream-sync burden (Sonata releases ~monthly) | 5 (just retest) | 3 (PHP merges easy, template changes re-done by hand) | 2 | 2 |
| Ability to fix PHP-side coupling (`box_class`, icons, skin injection, form_type) | 1–2 (service decoration only) | 5 | 5 | 5 |
| User override path (`templates/bundles/SonataAdminBundle`) | 4 (works, but precedence games with the overlay's own paths) | 5 | 2 (`templates/bundles/AdminataBundle`) | 4 |
| Ecosystem bundles keep installing (ORM, MongoDB fork, User, Media, Page, Classification, Block, FormExtensions) | 5 install / 2 render correctly | 5 install / 2 render correctly (same hazard) | 1 | 5 / 2 |
| Consistency with the MongoDB-fork precedent | 2 | 5 | 4 | 3 |
| Risk of silent breakage on Sonata patch releases | 2 | 4 (pinned by exact `replace`) | 4 | 4 |
| **Total** | 27–29 | **35** | 21 | 31 |

### 1.6 Recommendation

**Option B**: hard fork of `sonata-project/admin-bundle` 4.43.0 published as `idct/adminata`, keeping namespace, bundle class, Twig namespace, service ids, config root, routes, translation domain and asset package name, with `"replace": {"sonata-project/admin-bundle": "4.43.0"}` (exact upstream parity version, bumped on each sync), own semver starting at **1.0.0**, and an explicit, documented policy that the PHP API tracks upstream 4.x while the UI layer is independent. Keep Option D off the roadmap; mention it in `BEST_VERSION.md` as "considered and rejected" with the reasoning above.

Two refinements to B that come from the evidence:

1. Ship a **compat stylesheet** (`bundles/sonataadmin/compat-bootstrap3.css`, opt-in via `sonata_admin.assets.extra_stylesheets`) that maps the handful of Bootstrap-3/AdminLTE classes emitted by ecosystem templates (`box`, `box-header`, `box-body`, `panel*`, `input-group(-addon)`, `btn(-primary|-default|-danger)`, `alert-*`, `form-control`, `label-*`, `table(-bordered|-striped)`, `pull-right`, `text-danger`) to Tailwind `@apply` rules, plus a tiny Alpine/vanilla shim for `data-toggle="collapse|dropdown|modal"`. This is what lets SonataUserBundle/MediaBundle/PageBundle/ORM audit block pages *degrade gracefully* until each is themed.
2. Keep FontAwesome **as a supported icon syntax** in `IconRuntime` (return the same `<i class="fas …">` markup, but ship FA as SVG sprites or keep the webfont behind a config flag) and *add* a second syntax (`heroicon:` / raw `<svg>` already passes through, `IconRuntime.php:22-24`). Ecosystem bundles and user admins set `'icon' => 'fas fa-…'` in PHP (`AbstractAdmin.php:1800,1809`, `TaggedAdminInterface.php:55,60`, `AdminStatsBlockService.php:69`), so FA names must keep rendering something.

### 1.7 Ecosystem bundles: what ships Bootstrap-3 templates that extend `@SonataAdmin` layouts

Only the packages in the scratchpad could be read; the others are listed from Sonata's own docs (`SA/docs/index.rst:6-15`) and from how Sonata's extension detects them (`SonataAdminExtension.php:49-65`). Items marked "not in scratchpad" need online verification before the compat matrix is finalised.

| Bundle | Coupling seen | Matters for adminata? | Degradation plan |
|---|---|---|---|
| `sonata-project/doctrine-orm-admin-bundle` (read) | `Form/form_admin_fields.html.twig` extends `@SonataAdmin/Form/form_admin_fields.html.twig` and includes 4 `@SonataAdmin/CRUD/Association/edit_*` templates (`:12,17-29`); `Form/filter_admin_fields.html.twig` extends Sonata's; `Block/block_audit.html.twig` is raw Bootstrap 3 (`box box-primary`, `panel-group`, `data-toggle="collapse"`); `ListBuilder.php:98-139` hard-codes `@SonataAdmin/CRUD/Association/list_*` and `list__action*` paths; requires `admin-bundle ^4.39.0` | **Yes — tier 1.** Its two form themes are pure block-delegation (no Bootstrap markup of their own), so they keep working as long as adminata keeps block names `sonata_admin_orm_*_widget`, `sonata_type_model_widget`, `sonata_type_admin_widget`, `sonata_type_collection_widget`, the `sonata_admin.field_description.mappingtype` variable, and the four `edit_*` include paths. `block_audit` needs the compat stylesheet or an adminata-side override via `sonata_doctrine_orm_admin.templates`. | Keep block names + include paths; compat CSS for `block_audit`; add ORM bundle to the demo app matrix (§3). |
| `idct/sonata-admin-mongodb-bundle` (read) | Same pattern as ORM (form/filter themes appended by `AddTemplatesCompilerPass`, `MDB/AGENTS.md:313-317`); functional tests assert Sonata DOM hooks incl. `.alert-success` (`MDB/tests/Functional/CRUDTest.php:24,31,46`) | **Yes — tier 1** (user's own). | Same as ORM; run its functional suite against adminata in CI (§3.6). |
| `sonata-project/form-extensions` 2.7.0 (read) | `datepicker.html.twig` Bootstrap-3 `input-group`/`input-group-addon` + `fas fa-calendar` (`:12-40`); `app.css` = tempus-dominus; `app.js` registers a Stimulus controller on `global.sonataApplication` (`FE/assets/js/app.js:16-18`) | **Yes — tier 1**, it is a hard dependency (`SA/composer.json:36`) and its assets are in the default asset list (`Configuration.php:633,668`). | adminata must keep exporting `global.sonataApplication` (Stimulus app) so `bundles/sonataform/app.js` keeps registering; override `sonata_type_datetime_picker_widget*` blocks in adminata's `form_admin_fields.html.twig` with a flatpickr-based Tailwind widget and drop `bundles/sonataform/app.css` from the default stylesheet list (users can re-add). Long term: PR upstream or fork as `idct/form-extensions`. |
| `sonata-project/block-bundle` 5.4.0 (read) | `block_core_rss.html.twig` uses `panel panel-default` (`:15-35`); `block_base` uses neutral `cms-block` classes; Sonata's dashboard blocks extend `sonata_block.templates.block_base` (4 templates) | **Tier 2** — hard dependency, but the only Bootstrap-styled template is the RSS block. | Compat CSS covers `panel*`. |
| `sonata-project/twig-extensions` 2.6.0 (read) | No templates (`find` returned none) | No. | — |
| `sonata-project/user-bundle` (not in scratchpad) | Sonata auto-wires `templates.user_block` to `@SonataUser/Admin/Core/user_block.html.twig` when the bundle is registered (`SonataAdminExtension.php:49-56`); its login/reset pages are known to `extends '@SonataAdmin/standard_layout.html.twig'` / `empty_layout` and use AdminLTE `login-box` markup | **Tier 1** for most real apps (login screen). | Keep `empty_layout.html.twig` + `standard_layout` block names (`sonata_wrapper`, `sonata_page_content`, `sonata_header`, `logo`, …); ship an adminata `user_block` override and a TailAdmin-styled login page users can point `SonataUser` templates at; compat CSS for `login-box`. Verify exact template list online. |
| `sonata-project/intl-bundle` (not in scratchpad) | `history_revision_timestamp` auto-override (`SonataAdminExtension.php:58-65`) — a one-liner template | Tier 3. | Nothing; it just formats a date. |
| `sonata-project/media-bundle`, `page-bundle`, `classification-bundle` (not in scratchpad) | Historically ship admin templates (`Media` list/edit widgets, `Page` composer UI with jQuery UI, classification context list) extending `@SonataAdmin/CRUD/*` and using Bootstrap-3 grid/`btn`/modals | **Tier 2** (Media is common; Page is the worst because its "composer" UI is jQuery-heavy). | Compat CSS + keep jQuery available as an opt-in asset (`sonata_admin.assets.extra_javascripts`) for a transition period; document as "renders, not themed". |
| `sonata-project/entity-audit-bundle` (ORM dev dep) | Only via `block_audit` | Tier 3. | Compat CSS. |
| `symfony/acl-bundle` + Sonata ACL screens | Sonata's own `CRUD/acl.html.twig` (in scope of the rewrite) | n/a | — |

Policy proposal: a `COMPATIBILITY.md` matrix with tiers (1 = tested in CI, 2 = compat-CSS smoke-tested by screenshot, 3 = untested/known-Bootstrap), and `composer.json` `suggest` entries rather than `conflict`s, except where a bundle is *known* to fatally break (then `conflict` with the specific range).

---

## 2. Project skeleton proposal (matching the MongoDB-fork conventions)

### 2.1 `composer.json`

```json
{
    "name": "idct/adminata",
    "description": "Sonata Admin, re-skinned: the SonataAdminBundle PHP layer with a Tailwind CSS v4 / TailAdmin user interface. Drop-in replacement for sonata-project/admin-bundle 4.x.",
    "license": "MIT",
    "type": "symfony-bundle",
    "keywords": ["admin", "admin-generator", "sonata", "symfony", "tailwind", "tailadmin", "bundle"],
    "authors": [
        { "name": "IDCT Bartosz Pachołek", "email": "bartosz+github@idct.tech", "homepage": "https://idct.tech", "role": "Maintainer (idct/adminata fork)" },
        { "name": "Thomas Rabaix", "email": "thomas.rabaix@sonata-project.org", "homepage": "https://sonata-project.org", "role": "Original author (SonataAdminBundle)" },
        { "name": "Sonata Community", "homepage": "https://github.com/sonata-project/SonataAdminBundle/contributors", "role": "SonataAdminBundle contributors" },
        { "name": "TailAdmin", "email": "hello@tailadmin.com", "homepage": "https://tailadmin.com", "role": "Design system (TailAdmin free edition, MIT)" }
    ],
    "homepage": "https://github.com/ideaconnect/adminata",
    "require": {
        "php": "^8.4",
        "doctrine/collections": "^2.0 || ^3.0",
        "doctrine/common": "^3.0",
        "knplabs/knp-menu": "^3.6",
        "knplabs/knp-menu-bundle": "^3.0",
        "psr/container": "^1.0 || ^2.0",
        "psr/log": "^2.0 || ^3.0",
        "sonata-project/block-bundle": "^5.0",
        "sonata-project/doctrine-extensions": "^2.0",
        "sonata-project/exporter": "^3.1.1",
        "sonata-project/form-extensions": "^2.0",
        "sonata-project/twig-extensions": "^2.0",
        "symfony/asset": "^7.4 || ^8.0",
        "... every symfony/* entry from SA/composer.json:38-58 with ^7.4 || ^8.0 ...": "",
        "symfony/stimulus-bundle": "^2.22 || ^3.0",
        "twig/string-extra": "^3.0",
        "twig/twig": "^3.15"
    },
    "require-dev": {
        "doctrine/doctrine-bundle": "^2.17 || ^3.0",
        "doctrine/doctrine-fixtures-bundle": "^4.0",
        "doctrine/orm": "^3.3",
        "doctrine/persistence": "^4.0",
        "ext-pdo_sqlite": "*",
        "friendsofphp/php-cs-fixer": "^3.95",
        "infection/infection": "^0.33.1",
        "matthiasnoback/symfony-config-test": "^6.1",
        "matthiasnoback/symfony-dependency-injection-test": "^6.2",
        "phpstan/extension-installer": "^1.1",
        "phpstan/phpdoc-parser": "^1.0 || ^2.0",
        "phpstan/phpstan": "^2.0",
        "phpstan/phpstan-phpunit": "^2.0",
        "phpstan/phpstan-strict-rules": "^2.0",
        "phpstan/phpstan-symfony": "^2.0",
        "phpunit/phpunit": "^12.3.10",
        "psr/event-dispatcher": "^1.0",
        "rector/rector": "^2.0",
        "sonata-project/doctrine-orm-admin-bundle": "^4.20",
        "symfony/browser-kit": "^7.4 || ^8.0",
        "symfony/css-selector": "^7.4 || ^8.0",
        "symfony/filesystem": "^7.4 || ^8.0",
        "symfony/maker-bundle": "^1.25",
        "symfony/panther": "^2.4",
        "symfony/security-acl": "^3.1",
        "symfony/yaml": "^7.4 || ^8.0"
    },
    "replace": {
        "sonata-project/admin-bundle": "4.43.0"
    },
    "conflict": {
        "symfony/security-acl": "<3.1 >=4.0"
    },
    "suggest": {
        "sonata-project/doctrine-orm-admin-bundle": "Doctrine ORM persistence layer (tier-1 tested)",
        "idct/sonata-admin-mongodb-bundle": "Doctrine MongoDB ODM persistence layer (tier-1 tested)",
        "twig/extra-bundle": "Auto configures the Twig Intl extension"
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "autoload": { "psr-4": { "Sonata\\AdminBundle\\": "src/" } },
    "autoload-dev": { "psr-4": { "Sonata\\AdminBundle\\Tests\\": "tests/" } },
    "config": {
        "allow-plugins": {
            "composer/package-versions-deprecated": true,
            "infection/extension-installer": true,
            "phpstan/extension-installer": true
        },
        "sort-packages": true
    },
    "extra": { "branch-alias": { "dev-main": "1.x-dev" } },
    "scripts": { "...same test:* / infection scripts as MDB/composer.json:381-406, plus": "",
        "test:twig": "bin/console lint:twig src tests",
        "test:everything": ["@test:cs", "@test:rector", "@test:phpstan", "@test:twig", "@test:phpunit", "@test:infection"]
    }
}
```

Decisions embedded above, with rationale:

| Decision | Choice | Why |
|---|---|---|
| PHP floor | `^8.4` | Matches `MDB/composer.json:314`; Rector `UP_TO_PHP_84` and `@PHP8x4Migration` cannot run on a lower floor; Sonata's `^8.2` (`SA/composer.json:26`) would keep 2 extra CI columns for no design benefit. Adoption cost is real (Sonata users on 8.2/8.3 cannot migrate) — flagged as an open question in §7. |
| Symfony floor | `^7.4 \|\| ^8.0` | Matches the fork (`MDB/AGENTS.md:98`); dropping 6.4/7.3 removes the `method_exists(PropertyTypeExtractorInterface::class, 'getType')` shim (`SA/tests/App/AppKernel.php:91-95`) and the `stimulus-bundle ^2.22` compat path. Keeping 6.4 (LTS until late 2027) is the main adoption argument against — §7. |
| Author roster | Keep the 2 upstream entries + maintainer + TailAdmin | Sonata's `composer.json` lists only Thomas Rabaix + "Sonata Community" (`SA/composer.json:14-24`); the fork precedent enumerated 74 contributors from git history (`MDB/CHANGELOG.md` 5.0.0 entry). The scratchpad checkout is depth-1 (`git rev-list --count HEAD` = 1) so the roster cannot be regenerated locally; do it from a full clone with the same dedup script. |
| `require` trimming | Do **not** trim | Every `symfony/*` entry is used (`SA/composer.json:38-61`); `sonata-project/form-extensions` and `block-bundle` stay hard deps because Sonata templates extend `sonata_block.templates.block_base` (4 templates) and the form types are registered in `form_types.php`. |
| `provide` | none | The ORM bundle provides `sonata-project/admin-bundle-persistency-layer` (`ORM/composer.json:73-75`); adminata is not a persistence layer. |
| `replace` | exact `4.43.0` | §0.6/§1.2. Add a CI check that `replace` version == `UPSTREAM_VERSION` in `UPSTREAM.md`. |
| `branch-alias` | `dev-main: 1.x-dev` | Fork used `dev-master: 5.x-dev` (`MDB/composer.json:376-380`); adminata is a new line, so `main` + `1.x`. |
| Dev deps for the demo app | ORM + sqlite + fixtures + Panther | Sonata's own functional tests use a stub `ModelManager` (`SA/tests/App/Model/ModelManager.php`) which never exercises association widgets, autocomplete, exports, batch delete, or the real ORM form themes; the ORM bundle's own tests already run sqlite + Panther (`ORM/composer.json:47-49,60`). |

### 2.2 Directory layout

```
adminata/
├── assets/                     # source, built by Vite (§2.5)
│   ├── css/app.css             # @import "tailwindcss"; @theme {…}; @custom-variant dark; TailAdmin tokens; sonata-* semantic classes; compat layer as a separate entry
│   ├── css/compat-bootstrap3.css
│   ├── js/app.js               # Stimulus app (global.sonataApplication kept), Alpine bootstrap, controllers.json
│   ├── js/controllers/*_controller.js   # ported from SA/assets/js/controllers (9 controllers) + new ones (dropdown, modal, sidebar, theme)
│   ├── js/core/{config,translation,utils}.js
│   ├── icons/                  # SVG sprite sources (Heroicons/TailAdmin), FA compat map
│   └── images/
├── bin/console                 # dev-kit style, boots tests/App/AppKernel
├── docs/                       # Sphinx, forked from SA/docs, rewritten where UI differs
├── src/                        # Sonata\AdminBundle\* — PHP layer, upstream-tracked
│   └── Resources/{config,public,skeleton,translations,views}
├── tests/
│   ├── App/                    # demo/test app: AppKernel + ORM entities + fixtures + admins covering every field/filter/form type (§3.2)
│   ├── Functional/             # WebTestCase (BrowserKit) + Panther/Playwright visual suites
│   ├── Parity/                 # per-template parity tests (§3.4)
│   └── <mirrors of SA/tests/*>
├── upstream/                   # tooling only: sync script, exclusion list, last-synced tag (§4)
├── AGENTS.md, BEST_VERSION.md, CHANGELOG.md, COMPATIBILITY.md, CONTRIBUTING.md, LICENSE, NOTICE, README.md, UPGRADE-1.0.md (from Sonata), UPSTREAM.md
├── composer.json, package.json, package-lock.json, vite.config.js, tailwind config inside CSS (v4), eslint.config.js, prettier.config.js, stylelint.config.js, playwright.config.ts
├── phpunit.xml.dist, phpstan.neon.dist, rector.php, .php-cs-fixer.dist.php, infection.json5.dist, .yamllint, .editorconfig, .gitattributes, .readthedocs.yaml, .symfony.bundle.yaml, codecov.yml, docker-compose.yml
└── .github/{workflows,dependabot.yml,ISSUE_TEMPLATE,PULL_REQUEST_TEMPLATE.md}
```

Keep `src/Resources/public` **committed and prebuilt** exactly like Sonata (`SA/webpack.config.js:13`, `SA/.github/workflows/frontend.yaml:46-50` enforces "compiled output is up to date" with `git diff --exit-code`) so that `composer require` + `assets:install` is the whole install story and AssetMapper/Encore are not required in the consuming app. Keep the file names `app.css` / `app.js` / `entrypoints.json` / `manifest.json` (`SA/src/Resources/public/entrypoints.json`) because the default asset list references `bundles/sonataadmin/app.css` and `.js` (`Configuration.php:631-634,666-669`) and user configs may `remove_stylesheets` those exact paths.

`.gitattributes`: keep Sonata's list (`SA/.gitattributes:5-20`) and add `assets export-ignore`? Sonata ships `assets/` in the dist; keep shipping it (users who build their own theme want the source `app.css` with the `@theme` tokens).

### 2.3 Branch and versioning scheme

- Default branch `main`, release branches `1.x` when 2.x starts. Tags `v1.0.0` style (the fork tags `v5.2.0`, `MDB/CHANGELOG.md:5`).
- First release **1.0.0** with `replace: 4.43.0`. Do not start at 5.0.0: Sonata's own 5.x exists (`SA/.symfony.bundle.yaml:5-17`), and a `5.0.0` adminata would be read as "Sonata 5". The fork precedent's 5.0.0 made sense only because upstream Mongo was on 4.12.
- Pre-releases `1.0.0-alpha1 … -rc1` while the parity checklist is incomplete; `minimum-stability: dev` + `prefer-stable: true` as upstream.
- Semver contract (write into `CONTRIBUTING.md`): PHP API breaks and Twig **block-name** removals are majors; template markup changes that keep block names and the `sonata-*` hooks are minors; CSS-only changes are patches. Sync of upstream PHP that is BC → minor; bump `replace` in the same release.
- `.symfony.bundle.yaml`: `branches: [main]`, `maintained_branches: [main]`, `current_branch: main`, `doc_dir: docs/`.

### 2.4 PHP quality gates

| Gate | Setting | Notes / evidence |
|---|---|---|
| PHPUnit | `^12.3.10`; `phpunit.xml.dist` with `unit`, `functional`, `parity` suites, `failOnWarning/Risky`, `displayDetailsOnAllIssues`, `restrictNotices/Warnings`, Panther `ServerExtension`, `PANTHER_WEB_SERVER_DIR=./tests/App/public/`, `KERNEL_CLASS=\Sonata\AdminBundle\Tests\App\AppKernel` | Mirror `MDB/phpunit.xml.dist:11-58`; Sonata's tests already use PHPUnit 11 attributes (`#[DataProvider]`, `SA/tests/Functional/Controller/CRUDControllerTest.php:159`), so the 12 upgrade is mostly `PHPUNIT_120` Rector set + removing `restore_exception_handler()` teardown hacks if PHPUnit 12 no longer needs them. Sonata's `ignoreSuppressionOfDeprecations="true"` kept; add `ignoreIndirectDeprecations` as the fork did (`MDB/phpunit.xml.dist:21-46`) for `symfony/security-acl` Serializable deprecation on 8.5. |
| PHPStan | level **8** + `bleedingEdge.neon` + strict rules + symfony + phpunit extensions, `consoleApplicationLoader` (`SA/phpstan.neon.dist:5-32`), **baseline trimmed** to the 3 upstream entries (`SA/phpstan-baseline.neon:1-11`) with the goal of removing them; no `@phpstan-ignore` in new code (`MDB/AGENTS.md:365-367`). Level `max` is unrealistic for a 242-file upstream codebase with generics; keep 8 and revisit. | Also run on both Symfony lines as the fork does (`MDB/.github/workflows/qa.yaml:28-34`). |
| Rector | `UP_TO_PHP_84`, `PHPUNIT_120`, `PHPUNIT_CODE_QUALITY`, same skip list as `MDB/rector.php:39-49`. Expect a large one-time diff on `src/` (readonly promotion, `#[\Override]`) — do it as **one dedicated commit** so upstream cherry-picks can be rebased over it (§4). | `SA/rector.php:36-40` is at `UP_TO_PHP_82`/`PHPUNIT_100`. |
| PHP-CS-Fixer | `MDB/.php-cs-fixer.dist.php` rule set verbatim (`@PHP8x4Migration`, `@PHPUnit9x1Migration:risky`, `@Symfony(:risky)`, `@PSR12(:risky)`, header rule) with the **Sonata header kept** (`SA/.php-cs-fixer.dist.php:20-27`), `setUnsupportedPhpVersionAllowed(true)`; exclude `Resources/skeleton`, `Resources/public`, `node_modules`, `var`. | Header choice discussed in §5. |
| composer-normalize | `make lint-composer` (`SA/Makefile:12-15`). | |
| yamllint | `SA/.yamllint` verbatim. | |
| XML/XLIFF | `make lint-xml`, `lint-xliff` via `xmllint` (`SA/Makefile:22-44`). | Translations are 34 XLIFF files that adminata inherits. |
| Symfony lint | `lint:container`, `lint:twig src tests`, `lint:xliff`, `lint:yaml` via `bin/console` (`SA/Makefile:50-68`). `lint:twig` is the *first* gate for the template rewrite. | |
| Infection | `infection.json5.dist` as the fork (`@default` mutators, `unit` suite, `minMsi: 0` reporting mode) — nightly workflow, not PR-blocking. Exclude `Resources/`, `DependencyInjection/Compiler` like `MDB/infection.json5.dist:8-11`. | Sonata's 155 tests already give high coverage; Infection is a stretch goal, matches "WAR_AGAINST_THE_MUTANTS" culture. |

### 2.5 JS/CSS gates and build

Replace Encore/Babel/SCSS (`SA/package.json`, `SA/webpack.config.js`) with a Vite build (form-extensions already moved to vitest, `FE/vite.config.js`; Tailwind v4 has a first-class Vite plugin):

| Gate | Tool | Config |
|---|---|---|
| Build | `vite build` → `src/Resources/public/{app.css,app.js,compat-bootstrap3.css,images/,icons.svg,entrypoints.json,manifest.json}`; `@tailwindcss/vite`; `@tailwindcss/forms`; Alpine 3 + `@alpinejs/persist` (from `TA/package.json:29-30`), flatpickr (replaces tempus-dominus/x-editable datepickers), Stimulus 3 + `@symfony/stimulus-bridge` (kept — `global.sonataApplication` contract with form-extensions). Drop jQuery, Bootstrap 3, AdminLTE, iCheck, select2 (replace with a Tailwind-styled combobox controller; **but** `sonata_admin.options.use_select2` config key must stay accepted), x-editable, slimscroll, masonry, jquery-ui sortable (replace with SortableJS or Alpine sort plugin — required by `sonata_type_collection`/`ModelAutocomplete` sortable), jquery-form (replace with `fetch` + `FormData` in the edit/collection controllers). | `vite.config.js`, `assets/css/app.css` with `@source "../../src/Resources/views/**/*.twig"` so Tailwind sees the classes in Twig |
| Lint JS | ESLint 9 flat config (`eslint.config.js`): `@eslint/js` recommended + `eslint-config-prettier` + `eslint-plugin-header` (keep the Sonata header, as `SA/.eslintrc.js:27-41`, or the NOTICE-style combined header — §5) + `eslint-plugin-import`. airbnb-base has no maintained flat-config release; do not carry it. | |
| Format | Prettier 3 + `prettier-plugin-tailwindcss` (`TA/package.json:37-38`) with a Twig plugin (`@zackad/prettier-plugin-twig` or `prettier-plugin-twig-melody`) for `.twig` class ordering — evaluate; Twig formatting is optional but class ordering makes parity diffs readable. `printWidth: 100`, `singleQuote: true` (from `SA/prettier.config.js`). | |
| Lint CSS | Stylelint 16 with `stylelint-config-standard` + `stylelint-config-tailwindcss` (Tailwind v4 at-rules `@theme`, `@custom-variant`, `@apply`, `@source` must be allowed) + `stylelint-order` (`SA/.stylelintrc.js:20-24` had alphabetical order). | |
| Unit-test JS | vitest + jsdom for the Stimulus controllers (pattern in `FE/vite.config.js:13-27`, `FE/assets/js/controllers/datepicker_controller.test.js`). Controllers to cover: `collection`, `confirm_exit`, `edit`, `filter`, `filter_list`, `per_page`, `readmore`, `revision`, `sticky` (`SA/assets/js/controllers.json`). | |
| Build-freshness | CI job: `npm ci && npm run build && git diff --no-patch --exit-code -- src/Resources/public` (`SA/.github/workflows/frontend.yaml:46-50`); pin Node 24 (`engines.node: ">=22"`). | |
| Bundle-size budget | Optional `size-limit` on `app.css`/`app.js` (today 345 KB + 485 KB; Tailwind v4 output for 131 templates should be well under 100 KB). | |

### 2.6 Makefile targets

Keep the dev-kit Makefile verbatim (`SA/Makefile`, identical in `MDB/Makefile`) — the user kept it including its "auto-generated" banner — and append:

```
assets-install:        npm ci
assets-build:          npm run build
assets-check:          npm run build && git diff --no-patch --exit-code -- src/Resources/public
lint-js:               npx eslint assets
lint-css:              npx stylelint "assets/**/*.css"
lint-prettier:         npx prettier --check assets src/Resources/views
test-js:               npx vitest run
test-unit:             vendor/bin/phpunit --testsuite=unit
test-functional:       vendor/bin/phpunit --testsuite=functional
test-parity:           vendor/bin/phpunit --testsuite=parity
test-visual:           npx playwright test          (or: vendor/bin/phpunit --group=visual)
infection:             composer infection
upstream-diff:         upstream/diff.sh $(FROM) $(TO)
upstream-sync:         upstream/sync.sh $(TO)
demo:                  bin/console cache:clear && bin/console doctrine:schema:create --env=test; symfony serve / php -S 127.0.0.1:8000 -t tests/App/public
```

### 2.7 GitHub Actions

Mirror the fork's seven workflows (`MDB/.github/workflows/*`) with these matrices:

| Workflow | Jobs |
|---|---|
| `test.yaml` | PHP `8.4`, `8.5` × `highest`; include rows: `8.4` + `lowest`; `8.5` + `SYMFONY_REQUIRE=7.4.*`; `8.5` + `8.0.*` (`MDB/.github/workflows/test.yaml:46-69`). Services: none for unit (sqlite in-process). Coverage → codecov-action v6 with token/slug/flags (`:98-112`). Split into `unit` and `functional` steps so a Panther/browser failure does not hide unit results. |
| `qa.yaml` | PHPStan and Rector, each on Symfony `''` and `7.4.*` (`MDB/.github/workflows/qa.yaml:28-34,65-70`). |
| `lint.yaml` | php-cs-fixer, composer(-normalize), yamllint, xmllint (`MDB/.github/workflows/lint.yaml`). Add a `replace-version` job: `php -r` asserting `composer.json.replace["sonata-project/admin-bundle"] === trim(file_get_contents('UPSTREAM.md' parsed))`. |
| `symfony-lint.yaml` | container, twig, xliff, yaml (`MDB/.github/workflows/symfony-lint.yaml`). |
| `frontend.yaml` | Node 24: `npm ci`, eslint, stylelint, prettier --check, vitest, `npm run build`, `git diff --exit-code` (from `SA/.github/workflows/frontend.yaml`). |
| `visual.yaml` | Playwright (Chromium + Firefox + WebKit) screenshot suite against the demo app served by `symfony/panther`'s built-in server or `php -S`; uploads diff artifacts; runs on PR and nightly (§3.3). |
| `compat.yaml` (nightly + dispatch) | Installs adminata into (a) the MongoDB fork's test app and runs its functional suite, (b) the ORM bundle's test-suite equivalent in our demo app. Requires the `mongo` service + Firefox as in `MDB/.github/workflows/test.yaml:35-44`. |
| `documentation.yaml` | Sphinx build + DOCtor-RST (`MDB/.github/workflows/documentation.yaml`). |
| `mutation.yaml` | nightly Infection (`MDB/.github/workflows/mutation.yaml`). |
| `stale.yaml` | as fork. |
| `upstream-watch.yaml` (weekly) | `gh api repos/sonata-project/SonataAdminBundle/releases/latest`; if tag > `UPSTREAM.md`, open an issue with `upstream/diff.sh` output (PHP-only diff + list of touched templates) (§4). |

Dependabot: copy `MDB/.github/dependabot.yml` and add an `npm` ecosystem block grouped `tailwind` (`tailwindcss`, `@tailwindcss/*`), `alpine`, `dev-dependencies`.

### 2.8 Docs, RTD, changelog

- `README.md`: badges (Packagist, PHP 8.4|8.5, Symfony 7.4|8.0, Sonata parity `4.43.0`, codecov, workflows) and a "THIS IS A HARD FORK" section like `MDB/README.md`, plus a "Drop-in guarantee" box stating exactly what is preserved (namespace, bundle name, DI ids, config keys, template keys, block names, `sonata-*` CSS hooks, translation domain, routes) and what is not (Bootstrap/AdminLTE/jQuery classes and plugins).
- `AGENTS.md`: same skeleton as `MDB/AGENTS.md` (what/relationship/upstream/floors/gates/architecture/testing/definition-of-done/style) with a new "Template contract" section (block names, hooks, how to add a template, how to run parity + visual tests) and an "Upstream sync" section (§4).
- `BEST_VERSION.md`: severity-coded findings for the PHP layer we inherit (there will be some: e.g. `AddDependencyCallsCompilerPass.php:493-496` template swap by string comparison; `SonataAdminExtension.php:94-100` un-removable skin CSS; `IconRuntime` FA lock-in) + roadmap; `FIX.md` after 1.0.
- `UPSTREAM.md`: last synced tag, exclusion list, per-release log of what was cherry-picked / re-implemented / skipped.
- `COMPATIBILITY.md`: the ecosystem matrix (§1.7).
- `UPGRADE-1.0.md`: "from sonata-project/admin-bundle 4.43 to idct/adminata 1.0" — config keys that became no-ops, removed JS globals (`jQuery`, `$`, `Admin`), removed assets, template/blocks changed, migration commands (§6).
- `CHANGELOG.md`: Keep-a-Changelog like `MDB/CHANGELOG.md`, and carry Sonata's `CHANGELOG.md` as `CHANGELOG-sonata.md` (history + attribution).
- Sphinx docs: fork `SA/docs` (RTD config `SA/.readthedocs.yaml`, `docs/requirements.txt`), replace `cookbook/recipe_select2.rst`, `recipe_icheck.rst`, `recipe_jquery_ui.rst` (`SA/docs/index.rst:74-76`) with `recipe_alpine.rst`, `recipe_tailwind_theme.rst`, `recipe_icons.rst`; update `reference/dashboard.rst:266,358-361` (Bootstrap `col-md-6` block classes → Tailwind grid classes, with the old ones mapped by the compat layer). `conf.py` project name `adminata`, copyright "2010 Thomas Rabaix, 2026 IDCT".

---

## 3. Test strategy for a template rewrite

### 3.1 What can be reused from Sonata's 155 test files, and how each reacts to a UI rewrite

| Group (count) | Renders real templates? | Effect of the rewrite | Action |
|---|---|---|---|
| `tests/Controller/CRUDControllerTest.php` (1 file, 4640 lines) | No — Twig is mocked (`:175-216`); asserts template *names* (`:242-255`) | Unaffected as long as template keys/paths are kept | Reuse verbatim. |
| `tests/Form/*` (34) incl. `AdminLayoutTest`, `Widget/*` | Yes — `FilesystemLoader` on `src/Resources/views/Form` (`AbstractLayoutTestCase.php:35-56`) and XPath on Bootstrap classes (`AdminLayoutTest.php:31,113,148,163,178-191,216`) | **Will fail** by design | Keep the tests, **rewrite the expectations** to the Tailwind markup, and keep the *semantic* hooks the tests also check: `id="sonata-ba-field-container-{name}"`, `sonata-ba-field-help`, `sonata-ba-field-error` list, `required` label. These become the executable form-theme contract. |
| `tests/Twig/RenderElementRuntimeTest.php` + `Extension/RenderElementExtensionTest.php` (2) | Yes — `src/Resources/views/CRUD` (`:92-97`); 452 exact-HTML expectations `<td class="sonata-ba-list-field sonata-ba-list-field-{type}" objectId="…">` | Should **pass unchanged** if list/show field templates keep the `<td class="sonata-ba-list-field sonata-ba-list-field-{type}" objectId>` envelope (recommended: this envelope is what the MongoDB fork's tests and user CSS rely on, `MDB/tests/Functional/CRUDTest.php:24`) | Reuse; treat any diff as a parity failure. Where TailAdmin styling needs classes on `<td>`, add them *after* the Sonata ones. |
| `tests/Twig/BreadcrumbsRuntimeTest.php`, `SonataAdminRuntimeTest.php`, their `Extension/*` twins, `Menu/Integration/*` (BaseMenuTestCase renders knp-menu + `src/Resources/views`) | Yes | Breadcrumb/menu markup will change (`<li class="active">` etc.) | Rewrite expectations; keep `class="active"` on the active menu item and `sidebar-menu`/`dynamic-menu`-compatible hooks (`MenuTest.php:41`). |
| `tests/Functional/Controller/*` (5) | Yes — full pages via the stub-ModelManager app | Selectors `.sonata-ba-list-field`, `.sonata-ba-collapsed-fields label`, `.help-block.sonata-ba-field-help`, `.sonata-ba-field li`, `div[id$=_referenced]`, `.sidebar-menu .dynamic-menu a`, `selectButton('OK')` (`CRUDControllerTest.php:38-176`, `MenuTest.php:41`) | Keep every one of those hooks in the new templates **except** `.help-block` (Bootstrap) → keep it *as well* for one release? Decision: keep `help-block` as a dual class (`class="help-block sonata-ba-field-help …"`) — costless and keeps `ORM`/user selectors working. Reuse tests as-is. |
| `tests/DependencyInjection/*` (12), `ConfigurationTest` | No (config-tree assertions on defaults incl. `bundles/sonataadmin/app.css`, `skin-black`, `col-md-4`) | Fail wherever a default changes | Update expectations deliberately; each changed default is a documented UPGRADE item. |
| Everything else (`Admin`, `Datagrid`, `Filter`, `Route`, `Security`, `Util`, `Translator`, `Command`, `Maker`, …) | No | Unaffected | Reuse verbatim; keep them green through the Rector/PHP 8.4 pass. |
| `tests/App/*` (stub app) | — | Keep as the *lightweight* kernel for the inherited functional tests (no DB) | Keep; add the ORM demo app alongside (§3.2), do not replace. |

### 3.2 Demo / test app (Doctrine ORM + sqlite + fixtures)

Add `tests/App/OrmKernel` (or a second env of the same kernel) registering `DoctrineBundle`, `DoctrineFixturesBundle`, `SonataDoctrineORMAdminBundle`, and entities/admins that between them touch **every** renderable path:

- Field types (list + show): every `FieldDescriptionInterface::TYPE_*` in `TemplateRegistryInterface.php:27-66` (array, boolean, date, time, datetime, textarea, email, enum, trans, string, integer, float, identifier, currency, percent, choice, url, html, many_to_many, many_to_one, one_to_many, one_to_one) plus `_action` columns, editable (`x-editable` replacement) booleans/choices, `list_outer_rows_{list,mosaic,tree}` modes, `simple_pager_results`.
- Form types: `ModelType`, `ModelListType`, `ModelAutocompleteType`, `ModelHiddenType`, `ModelReferenceType`, `AdminType` (one_to_one/many_to_one sub-admin), `CollectionType` (one_to_many with sortable), `ImmutableArrayType` (used by `SA/tests/App/Admin/FooAdmin.php:61-85`), `TemplateType`, `ChoiceFieldMaskType`, `DatePickerType`/`DateTimePickerType`/`DateRangePickerType` (form-extensions), `BooleanType`, `TextType` with `help`, `FormTypeFieldExtension` `sonata_admin.class`, tabs + groups with `box_class`, `collapsed`, `description`, horizontal vs standard `form_type`.
- Filters: all `sonata.admin.form.filter.type.*` (choice, date, daterange, datetime, datetime_range, default, number) and ORM filters (string with operators, model, model_autocomplete, callback, boolean, class), `persist_filters`, `advanced` filters toggle.
- Pages: dashboard (blocks left/right/top/bottom, stats block, admin_list block, search_result block, empty groups), global search, list (batch actions + confirmation, export links, per-page, sorting, pager links/results variants, mosaic background), create/edit (with errors, ajax/preview mode, `sonata_type_admin` nested edit, `edit_many_script` modal flows), show (+ `show_compare`), history (+ revision compare), ACL, delete confirmation, `select_subclass`, `preview`, `ajax_layout`, `empty_layout`, child admin breadcrumbs + tab menus, `AdminAsParameter` custom action pages, 403/404/error pages.
- Fixtures: `doctrine/doctrine-fixtures-bundle` loaded in `tests/custom_bootstrap.php` like `MDB/tests/custom_bootstrap.php:24-28`, together with `assets:install --symlink` into `tests/App/public` (`:30-35`).

This app doubles as the **demo** (`make demo`) and as the visual-regression target.

### 3.3 Browser-level tests: Panther vs Playwright

- **Panther (Firefox/Selenium)** is the precedent (`MDB/tests/Functional/BasePantherTestCase.php`, `MDB/docker-compose.yml`) and the ORM bundle also uses it (`ORM/composer.json:60` — pinned to a fork branch `dev-symfony-8-support`, note). Use Panther for *behavioural* E2E in PHPUnit: collection add/remove, autocomplete, batch action confirm modal, confirm-exit dialog, filter add/remove, sidebar collapse/persist (Alpine `$persist`), dark-mode toggle, keyboard navigation of dropdowns.
- **Playwright** for *visual regression*: `@playwright/test` `toHaveScreenshot()` per page × viewport (375/768/1280) × theme (light/dark) against the demo app served by `php -S` (or Panther's server), with a committed baseline under `tests/Visual/__snapshots__/` and a `--update-snapshots` workflow input. Playwright's built-in screenshot diffing, multi-browser and trace viewer are better than hand-rolled Panther screenshots; it also runs **axe** via `@axe-core/playwright` for a11y (WCAG 2.1 AA: colour contrast in both themes, form labels, `aria-expanded` on dropdowns/sidebar, focus traps in modals — TailAdmin's README claims "accessible sidebar"). HTML validity: run `html-validate` (npm) over the Playwright-captured DOM or the WebTestCase responses; fail on duplicate ids (Sonata's templates generate `id="sonata-ba-field-container-{name}"`, and nested admins can duplicate them).
- Keep both behind separate suites so `make test-unit` stays fast and browser-free (`MDB/phpunit.xml.dist:11-19` precedent).

### 3.4 Parity checklist per Sonata page/template

Generate `tests/Parity/parity.yaml` from the 131 templates with, per template: (a) template config key (from `Configuration.php:570-608`) or "included by …", (b) block names defined upstream (`grep '{% block'`) — **all must exist** in the new template (a `ParityTest` parses both trees with `Twig\Environment::parse()` and asserts set-equality of block names, allowing an `additions` list), (c) required DOM hooks (`sonata-ba-*`, `sonata-link-identifier`, `sonata-ba-view-container`, `objectId` attribute, `data-controller`/`data-*-target` names used by `assets/js/controllers/*`, form `name`/`id` attributes), (d) JS behaviours to preserve (list below), (e) a11y notes, (f) status: `todo / draft / parity / visual-approved`.

JS behaviours that must survive the jQuery removal (from `SA/assets/js/admin.js`, `base.js`, `sidebar.js`, `treeview.js`, controllers): confirm-exit on dirty forms; sticky form action bar; collection add/remove/sort (`sonata_type_collection`, `sonata_type_native_collection`); `edit_many_script` modal create/edit/list/delete for associations (uses `jquery-form` ajax submit, x-editable, select2 refresh); ModelAutocomplete (select2 ajax → new combobox, keep the `_referenced` hidden input contract asserted in `CRUDControllerTest.php:130`); inline x-editable list cells (`sonata_admin_set_object_field_value` route, `XEditableRuntime` choices → new Alpine popover); filter show/hide + "advanced" toggle + per-page; batch checkbox all/none + confirmation; readmore truncation; revision compare; tree-view sidebar + menu keep-open; mosaic view masonry (replace with CSS grid); flash messages (`.alert-*` hooks kept); `js_debug`, `confirm_exit`, `html5_validate` config flags read from `sonata_config` (`GlobalVariablesCompilerPass.php:17`).

### 3.5 CSS-hook contract (the "drop-in" surface that tests and siblings depend on)

Must be preserved verbatim (evidence in §0.2/§0.3): `sonata-ba-list-field`, `sonata-ba-list-field-{type}`, `objectId` attribute, `sonata-link-identifier`, `sonata-ba-view-container`, `sonata-ba-collapsed-fields`, `sonata-ba-field`, `sonata-ba-field-help` (+ keep `help-block` alongside), `sonata-ba-field-container-{name}`, `sonata-ba-field-error`, `sonata-ba-tabs`, `sonata-ba-form-actions`, `sidebar-menu`, `dynamic-menu`, `active`, `alert`/`alert-success`/`alert-danger`/`alert-warning`/`alert-info`, `{form_name}_{field}` classes on inputs (from `FormTypeFieldExtension.php:164-166`), button labels `Create`, `Create and return to list`, `Update and close`, `Yes, delete`, `OK` (translation keys, not markup — keep the XLIFF), the `_sonata_csrf_token` hidden field. A `HookContractTest` greps the compiled templates for each hook.

### 3.6 Ecosystem compat jobs

Nightly `compat.yaml`: check out `ideaconnect/sonata-admin-mongodb-bundle`, `composer require idct/adminata:@dev` (path repo), run `make test` with Mongo + Firefox; expect green because its selectors are in the contract above. Same for a checkout of `SonataDoctrineORMAdminBundle` `4.x` (`composer.json` there has panther from a fork — may need `--ignore-platform-reqs`; treat red as informational until stabilised).

---

## 4. Upstream sync process

Constraints: Sonata ships ~1 release/month on 4.x (17 in 15 months); PHP diffs are small; template diffs cannot be merged (adminata's templates are a rewrite); the local checkout is depth-1 so history must come from a full clone.

Proposed mechanism ("pristine branch + filtered cherry-picks"):

1. **`upstream` remote** (`sonata-project/SonataAdminBundle`) and a local branch `pristine/4.x` that fast-forwards to each upstream tag. Never modified.
2. **Exclusion list** `upstream/exclude.txt`: `src/Resources/views/**`, `src/Resources/public/**`, `assets/**`, `package*.json`, `webpack.config.js`, `.babelrc.js`, `.eslintrc.js`, `.stylelintrc.js`, `postcss.config.js`, `prettier.config.js`, `docs/cookbook/recipe_{select2,icheck,jquery_ui}.rst`, `.github/**`, `Makefile`, `*.md` (handled manually), `phpunit.xml.dist`, `rector.php`, `.php-cs-fixer.dist.php`, `phpstan*.neon`.
3. **`upstream/diff.sh FROM TO`**: `git diff --stat FROM TO -- . ':(exclude)…'` for the PHP side, plus `git diff FROM TO -- src/Resources/views assets` printed separately as the "UI changes to re-implement" list, plus the changelog section between the tags. Output pasted into the sync issue (the weekly `upstream-watch.yaml` job creates it).
4. **`upstream/sync.sh TO`**: `git cherry-pick -x --no-commit` each upstream commit in `FROM..TO` restricted with `git checkout TO -- <paths not excluded>` semantics (simplest robust form: `git diff FROM TO -- <included paths> | git apply -3`), then `make cs-fix rector phpstan test`, then a commit `Sync upstream 4.44.0 (PHP layer)` and a separate commit bumping `replace` + `UPSTREAM.md`. Rector/CS-fixer reformatting of upstream files is expected; keeping adminata's "modernisation" commits small and mechanical (readonly/Override) minimises 3-way conflicts.
5. **Template changes** from the upstream diff are re-implemented by hand in the TailAdmin templates; each gets a parity-checklist entry and a CHANGELOG line "Ported upstream #NNNN". Translation XLIFF changes apply cleanly (not excluded).
6. **Policy** (into `AGENTS.md`/`UPSTREAM.md`): sync every upstream minor within one adminata minor; skip upstream commits that only touch excluded paths; never merge upstream 5.x (BC breaks) until adminata 2.0; deprecations introduced upstream are carried as-is so users get the same `NEXT_MAJOR` signals (`SA/CONTRIBUTING.md:304-419`).
7. `git subtree` was considered and rejected: adminata *is* the tree (not a subdirectory), and subtree merges would drag template changes in.

---

## 5. Licensing and attribution

- Sonata: MIT, "Copyright (c) 2010 Thomas Rabaix" (`SA/LICENSE:1-3`). Every PHP/JS/SCSS/Twig file carries the "This file is part of the Sonata Project package … (c) Thomas Rabaix" header, enforced by php-cs-fixer (`SA/.php-cs-fixer.dist.php:20-27,41`) and eslint (`SA/.eslintrc.js:27-41`). The MIT condition is that the copyright notice and permission notice are included in copies (`SA/LICENSE:12-13`).
- TailAdmin free: MIT, "Copyright (c) 2023 TailAdmin" (`TA/LICENSE:1-3`); same for React/Next. `TA/package.json:20` says `ISC` — a metadata slip; the repository `LICENSE` file governs, but note it in `NOTICE` and, ideally, ask TailAdmin to fix it (open question §7).
- Proposed `LICENSE`: the fork's pattern (`MDB/LICENSE:1-11`) — MIT text with three copyright lines: `2010 Thomas Rabaix`, `2023 TailAdmin (design system, portions of markup/CSS)`, `2026 IDCT Bartosz Pachołek`, and a note paragraph explaining the fork and the design-system origin.
- Add a `NOTICE` file listing third-party components and their licenses (Sonata Admin MIT, TailAdmin MIT, Alpine.js MIT, Tailwind CSS MIT, flatpickr MIT, Stimulus MIT, Heroicons MIT if used, FontAwesome Free — **CC BY 4.0 for icons + SIL OFL for fonts + MIT for code**: if FA is kept at all, its attribution requirements differ from MIT and must be listed; consider dropping the webfont and keeping only an FA-name → Heroicon mapping).
- Headers: keep the Sonata header **unchanged on files inherited from upstream** (`MDB/AGENTS.md:411-412` does exactly this) so cherry-picks stay clean; for new files use a combined header ("This file is part of the adminata package, a fork of the Sonata Project package. (c) Thomas Rabaix; (c) IDCT …"). Templates derived from TailAdmin markup get a Twig comment header crediting TailAdmin. php-cs-fixer's `header_comment` rule supports only one header per config — run two finders (inherited vs new paths) or accept the Sonata header everywhere plus the NOTICE (simplest; recommended).
- Composer `authors` keep upstream entries (§2.1). Docs `conf.py` copyright line updated.
- Do not use the TailAdmin name/logo as adminata's branding beyond attribution (the MIT licence covers the code, not the trademark); `title_logo` default (`Configuration.php:252`) should be an adminata/neutral logo.

---

## 6. Symfony Flex recipe / installation

Install story for a **new app**:

```
composer require idct/adminata sonata-project/doctrine-orm-admin-bundle
```
Flex auto-generates the bundle registration for `Sonata\AdminBundle\SonataAdminBundle` (`FLEX/SymfonyBundle.php:14-35,76-94`) — the same class name Sonata's docs tell users to register (`SA/docs/getting_started/installation.rst:45`). The other bundles in that list (`SonataBlockBundle`, `KnpMenuBundle`, `SonataDoctrineBundle`, `SonataFormBundle`, `SonataTwigBundle`, `StimulusBundle`) are auto-registered by their own recipes/auto-recipes as today. What Flex will **not** create without a recipe: `config/packages/sonata_admin.yaml` (the `sonata_block.blocks.sonata.admin.block.admin_list.contexts: [admin]` snippet, `installation.rst:59-67`) and `config/routes/sonata_admin.yaml` (`installation.rst:95-106`). Options:

1. Documentation only (the MongoDB fork's approach — no recipe). Cheapest; the two YAML files are 10 lines.
2. A private recipes repository (`ideaconnect/recipes`, `flex/main` branch built with `symfony/recipes` tooling) and users add `"extra": {"symfony": {"endpoint": ["https://api.github.com/repos/ideaconnect/recipes/contents/index.json", "flex://defaults"]}}`. Works, but every consumer must opt in — low value for a 10-line config.
3. Contribute a recipe for `idct/adminata` to `symfony/recipes-contrib` once 1.0 is out (public packages are accepted there). Recommended medium-term.

Migration story for an **existing Sonata app** (this is where the Flex behaviour bites, §0.7):

```
git status --porcelain | grep -q . && echo "commit first"
composer require idct/adminata --no-plugins --no-scripts   # prevents Flex from running sonata's recipe unconfigure
composer remove --no-update sonata-project/admin-bundle     # optional: cleanliness; adminata already replaces it (app3 result)
composer install                                             # runs Flex normally; adminata auto-recipe is idempotent on bundles.php
bin/console cache:clear && bin/console assets:install
git diff -- config/                                          # verify nothing was deleted
```
Document also the "without `--no-plugins`" variant: after the command, `git checkout -- config/packages/sonata_admin.yaml config/routes/sonata_admin.yaml config/bundles.php` and re-run `composer install`. The exact file list of Sonata's contrib recipe must be verified online (§7).

`assets:install`: unchanged — output dir is `public/bundles/sonataadmin` because the bundle name is unchanged; adminata's prebuilt `app.css`/`app.js` land where the default config expects them (`Configuration.php:631-669`). AssetMapper users: adminata should additionally ship an `importmap`-friendly ESM build (`app.esm.js`) and document `framework.asset_mapper.paths` — open question §7.

---

## 7. Risks and open questions for the owner

### Risks

| # | Risk | Severity | Mitigation |
|---|---|---|---|
| R1 | Ecosystem bundles (User/Media/Page/Classification, ORM `block_audit`, form-extensions datepicker, block-bundle RSS) render Bootstrap-3 markup inside a Tailwind layout → visibly broken pages for real apps | High | Compat stylesheet + `data-toggle` shim (§1.6), `COMPATIBILITY.md` tiers, keep `global.sonataApplication`, override form-extensions datepicker blocks in adminata's form theme, nightly compat jobs (§3.6) |
| R2 | Flex runs Sonata's recipe `unconfigure` when adminata replaces it, deleting `config/packages/sonata_admin.yaml`/routes (`FLEX/Configurator/CopyFromRecipeConfigurator.php:31-41`, no hash check) | High (data loss of user config) | `--no-plugins` migration command in README/UPGRADE; verify recipe file list; consider shipping a recipe that re-creates the files |
| R3 | `replace: "4.43.0"` goes stale; a persistence bundle bumps its floor (`^4.44`) and installs break (app5) | Medium | Weekly `upstream-watch` job; sync policy "within one minor"; documented root-level `replace` escape hatch |
| R4 | User template overrides in `templates/bundles/SonataAdminBundle/*` written against Bootstrap 3 break | Medium (inherent) | Keep block names (parity test), keep hooks, per-template migration notes in `UPGRADE-1.0.md`, `bin/console adminata:overrides` command listing app overrides and their upstream diff |
| R5 | Inherited PHPStan baseline/`final` classes + Rector 8.4 pass produce a large first diff that makes upstream cherry-picks conflict | Medium | Single mechanical commit; keep `readonly`/`#[\Override]` changes in a Rector-only commit; `git apply -3` in sync script |
| R6 | PHP `^8.4` / Symfony `^7.4` floors exclude a large share of current Sonata users (Sonata supports 8.2 + 6.4 LTS) | Medium (adoption) | Owner decision (below); technically nothing in the rewrite needs 8.4 |
| R7 | TailAdmin free covers only a subset of components (no data-table filter UI, no rich select/combobox, no tree view, no file-upload widget beyond dropzone); Pro is a paid license with its own terms | Medium | Build missing components ourselves from Tailwind primitives (React/Next free variants add modal/dropdown/table/badge), avoid Pro code entirely unless licensed and license terms permit redistribution in an MIT package (they almost certainly do not) |
| R8 | jQuery removal breaks user-land JS that hooks `jQuery`/`$`/`Admin.*` globals (Sonata exposes `window.Admin`, `SA/assets/js/admin.js`) and third-party admin bundles' inline scripts | Medium | Keep a documented `window.Admin` facade with the same method names (`setup_*`, `shared_setup`, `log`) implemented without jQuery; optional `sonata_admin.assets.extra_javascripts` jQuery shim for a transition |
| R9 | FontAwesome licensing/asset weight vs `IconRuntime` FA-only contract (`IconRuntime.php:27-35`) | Low/Medium | Accept FA names, render via an FA→SVG map; keep webfont opt-in |
| R10 | Sonata releases a template change (as 4.43.0 did to `edit_many_script`) that fixes a security/behaviour bug; adminata must re-implement it manually | Low/Medium | Upstream diff highlights `src/Resources/views` changes; CHANGELOG "Ported upstream #NNNN" discipline |
| R11 | TailAdmin `package.json` says ISC while LICENSE says MIT | Low | NOTICE entry; ask upstream to correct |
| R12 | Twig `strict_variables` + block-name parity: new templates referencing variables absent in ajax/embedded contexts (`ajax_layout`, `sonata_type_admin` nested render) | Low | `strict_variables: true` in test kernels (`SA/tests/App/AppKernel.php:106`, `MDB/tests/App/config/config.yaml`), functional coverage of ajax paths |

### Open questions (only the owner can answer)

1. **Floors**: keep Sonata's `php ^8.2` / Symfony `^6.4 || ^7.3 || ^8.0` for adoption, or the fork's `^8.4` / `^7.4 || ^8.0` for tooling consistency? (Recommendation in this report: match the fork; revisit if adoption is a goal.)
2. **TailAdmin Pro**: is a Pro license available/desired? If yes, what do its terms say about redistributing derived markup/CSS in an MIT package? Until answered, plan strictly on the free MIT edition.
3. **Ecosystem scope**: which Sonata bundles must be *tier 1* at 1.0 — ORM + MongoDB fork only, or also SonataUserBundle (login page) and SonataMediaBundle?
4. **AssetMapper**: ship an importmap-compatible ESM build and document AssetMapper usage, or Encore/plain `assets:install` only?
5. **jQuery**: hard removal at 1.0, or keep an opt-in jQuery shim asset for a transition release?
6. **Dark mode / theming API**: expose Tailwind `@theme` tokens as the public theming API (documented CSS variables) and drop `options.skin`, or map the 12 AdminLTE skin names onto colour presets for BC?
7. **Version numbering**: 1.0.0 with `replace 4.43.0` (recommended) vs 4.43.x with `self.version` (upstream-aligned numbers, no semver freedom)?
8. **Recipe**: contribute an `idct/adminata` recipe to `symfony/recipes-contrib`, or docs-only like the MongoDB fork?
9. **Header/attribution style**: keep the Sonata header on all files (simplest for sync) or dual headers on new files?
10. **Namespace future**: confirm Option D is off the table so docs can state "the PHP layer is Sonata Admin 4.x, and will stay API-identical" as a promise.
11. **Repository/org**: `ideaconnect/adminata` on GitHub (the fork lives under `ideaconnect/`, `MDB/README.md` badges) and `idct/adminata` on Packagist?
12. **Author roster**: enumerate all upstream contributors in `composer.json` like the fork did (needs a full clone), or keep Sonata's two-entry roster?

---

## 8. Roadmap skeleton

| Phase | Deliverables | Exit gate |
|---|---|---|
| 0. Bootstrap (week 1) | Repo from `sonata-project/SonataAdminBundle` tag `4.43.0` (full history, `pristine/4.x`), rename package, `replace`, floors, Rector/CS/PHPStan pass on `src/`+`tests/`, all inherited tests green on PHP 8.4/8.5 × Symfony 7.4/8.0, CI workflows from §2.7 (minus visual/compat), docs skeleton (`AGENTS.md`, `UPSTREAM.md`, `COMPATIBILITY.md`, `NOTICE`) | `make test phpstan rector lint` green; `composer require idct/adminata` in a scratch app resolves with ORM bundle (app1 scenario) |
| 1. Asset pipeline + layout (weeks 2–3) | Vite + Tailwind v4 + Alpine + Stimulus build to `src/Resources/public`; `standard_layout`, `ajax_layout`, `empty_layout`, `Core/*`, `Menu/*`, `Breadcrumb/*`, `Button/*`, `Pager/*` in TailAdmin markup with all 33 layout blocks; sidebar/header/user-block/search; dark mode; `IconRuntime` extension | Functional `DashboardActionTest`, `MenuTest` green; Playwright baseline for dashboard/list shells |
| 2. Form theme + filters (weeks 4–6) | `Form/form_admin_fields.html.twig` (30 blocks), `Form/filter_admin_fields.html.twig` (6 blocks), `Form/Type/sonata_type_model_autocomplete`, datepicker override, collection/admin/model widgets, `CRUD/Association/edit_*`, `edit_many_script` without jQuery | `tests/Form/*` rewritten and green; ORM demo app create/edit pages pass Panther flows; parity test green for Form/ |
| 3. CRUD pages (weeks 7–9) | `CRUD/*` (99 templates): list (+mosaic/tree, batch, export, x-editable replacement), show/show_compare, history, acl, delete, preview, select_subclass, list/show field templates keeping the `sonata-ba-list-field` envelope | `RenderElementRuntimeTest` unchanged and green; functional suite green; parity 131/131; a11y (axe) no serious violations |
| 4. Ecosystem compat (weeks 10–11) | Compat stylesheet + `data-toggle` shim; `COMPATIBILITY.md` matrix; nightly compat jobs against the MongoDB fork and ORM bundle; `UPGRADE-1.0.md`; migration command docs incl. Flex `--no-plugins` | MongoDB fork functional suite green against adminata |
| 5. Release 1.0.0 (week 12) | Sphinx docs updated, RTD, Packagist publish, CHANGELOG, tag `v1.0.0`, recipe decision executed | All gates + visual baseline approved |
| 6. Post-1.0 | Upstream sync cadence (monthly), Infection nightly, FIX.md, theme-token docs, AssetMapper build, SonataUserBundle-themed login page, contribute recipe | — |
