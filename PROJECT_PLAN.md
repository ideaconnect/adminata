# adminata — PROJECT_PLAN.md

Trackable task list derived from [PLAN/](PLAN/README.md) (v3, 2026-09-04). Every task is sized for
one autonomous agent session (Claude Opus or similar): it names what to read, what to produce, and
the command that proves it is done. Tasks are grouped by milestone; a milestone is closed by its
last task, which pushes `main` to `git@github.com:ideaconnect/adminata.git`.

## How to use this file

- Work top to bottom inside a milestone; respect `depends:`.
- Tick `[x]` only when every line under **Accept** passes. Partial progress or blockers go in a
  dated bullet under the task (`- 2026-09-10: blocked on …`). Never delete a task; strike it
  through with a reason (`~~P1-03~~ superseded by P1-05`).
- New work discovered during a task becomes a new task in the same milestone with the next free
  number (`P3-12`) or, for defects found in phase 5, `P5-FIX-nn`.
- Sizes: **S** ≤ 2 h, **M** about half a day, **L** one to two days. Anything larger must be split
  before it is started.
- Append a line to the **Status log** at the bottom whenever a task is finished or a milestone is pushed.

## Ground rules for every task

1. The plan is the spec. Read the `Read:` references before touching code. If the plan is wrong,
   change the plan in the same commit and say so in the status log.
2. Owner directives (PLAN/README.md): no jQuery anywhere (`npm ls jquery` empty; no package that
   depends on it); no compatibility layers; no Bootstrap or AdminLTE class names in adminata markup
   (Sonata-owned hooks only, PLAN/02 §8); latest releases pinned exactly (PLAN/07 §2); no AJAX form
   submission; no inline scripts and no `onclick`; scripts `defer`; 1.0 scope is appendix C.
3. Do not touch upstream PHP outside the list in PLAN/01 P6. Do not rewrite deferred templates
   (PLAN/03 §E, §G); they only get the `{# adminata: not yet ported #}` marker.
4. Definition of done (PLAN/07 §6) before ticking a task: `make lint`, `make phpstan`, `make rector`,
   `make test`, `make test-contract`, `make lint-js`, `make test-js`, `make assets-check` all green
   (targets exist from P0-08 / P1-02 onward; before that, the tools named in the task).
5. New PHP follows the MongoDB fork's conventions (`/home/bartosz/dev/idct/sonata-admin-mongodb-bundle/AGENTS.md`):
   `declare(strict_types=1)`, `final` classes, readonly services, PHPStan level 8 clean, no
   `@phpstan-ignore`, tests for every behaviour. New JS is ES2022 modules, one Stimulus controller
   per file, Vitest test per controller. New CSS follows PLAN/04.
6. Git: one branch per task (`task/P0-01-import-packages`), commits `P0-01: <what>` ending with the
   `Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>` trailer, merged to `main`. Push
   `main` only in milestone tasks (`Pn-MS`) or after a plan revision.
7. Reference checkouts on this machine: the app `/home/bartosz/dev/r3/recomaty-panel-clean`
   (`APP/`), the MongoDB fork `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle` (`MDB/`), the
   vendored Sonata packages under `APP/vendor/sonata-project/`. TailAdmin v2.3.0 is fetched from
   GitHub (`TailAdmin/tailadmin-free-tailwind-dashboard-template`, tag `v2.3.0`) into a scratch
   directory when a task needs it; never vendored.

---

## Milestone M0 — Bootstrap (PLAN/09 phase 0)

- [x] **P0-01 · Import the seven upstream packages** · L · depends: —
  - Read: PLAN/07 §2 (tags), §4 (layout), §10 (remotes); PLAN/01 P1, P2, P9.
  - Do: create `upstream/remotes.txt` (name → GitHub URL for SonataAdminBundle, SonataBlockBundle,
    sonata-doctrine-extensions, SonataDoctrineORMAdminBundle, exporter, form-extensions,
    twig-extensions); `git remote add upstream-<name>`; `git fetch --tags`;
    `git subtree add --prefix=packages/<name> upstream-<name> <tag>` at 4.43.0, 5.4.0, 2.6.0,
    4.21.0, 3.4.0, 2.7.0, 2.6.0. Record tag + commit per package in `UPSTREAM.md`. Measure
    `du -sh .git`; if above 300 MB, re-import the small packages with `--squash` and note it.
  - Deliver: `packages/<name>/` × 7, `upstream/remotes.txt`, `UPSTREAM.md`.
  - Accept: `ls -d packages/*/src | wc -l` prints 7;
    `git log --merges --format=%B | grep -c "^git-subtree-dir: packages/"` prints 7;
    `jq -r .name packages/*/composer.json` lists the seven upstream names.

- [x] **P0-02 · Root `composer.json`** · M · depends: P0-01
  - Read: PLAN/07 §3; PLAN/01 P3–P5; PLAN/02 §1.
  - Do: compute the union of the seven `packages/*/composer.json` `require` lists (drop
    `sonata-project/*`, raise floors to `php ^8.4`, `symfony/* ^7.4 || ^8.0`, `twig ^3.28`,
    `doctrine/orm ^3.6`, `doctrine/doctrine-bundle ^3.0`, `symfony/stimulus-bundle ^3.4`); write
    `replace` × 7 at the exact versions; `autoload`/`autoload-dev` PSR-4 × 7 plus `Adminata\Tests\`
    → `tests/`; `require-dev` at PLAN/07 §2 versions; scripts; `suggest`; delete
    `packages/*/composer.json`. Run `composer install`, `composer normalize`.
  - Deliver: `composer.json`.
  - Accept: `composer validate --strict` ok; `composer install` ok; a `php -r` snippet instantiates all
    seven bundle classes (PLAN/02 §1 table); `composer show --self | grep -A7 replaces` lists seven entries.

- [x] **P0-03 · Repository documents** · S · depends: P0-02
  - Read: PLAN/07 §5, §8, §9; PLAN/00 "What drop-in means".
  - Do: `README.md` (hard-fork banner, seven packages, what is/is not preserved, install), `LICENSE`
    (MIT, three copyright lines), `NOTICE`, `AGENTS.md` (conventions + the ground rules above +
    package layout + library policy), `CONTRIBUTING.md` (semver contract), `CHANGELOG.md`
    (Unreleased), `CHANGELOG-sonata.md` (per-package upstream changelog pointers), `.editorconfig`,
    `.gitattributes` (`export-ignore` for `/tests`, `/packages/*/tests`, `/packages/*/docs`, `/PLAN`,
    `/.github`, `/upstream`), `.symfony.bundle.yaml`.
  - Accept: files exist; every relative link in `README.md` resolves; `composer validate --strict` still ok.

- [x] **P0-04 · PHPUnit 13 across the seven suites** · L · depends: P0-02
  - Read: PLAN/07 §6; PLAN/08 §1.
  - Do: root `phpunit.xml.dist` with test suites `admin`, `block`, `doctrine`, `orm`, `exporter`,
    `form`, `twig` (→ `packages/<name>/tests`) and `adminata-unit`, `adminata-functional`,
    `adminata-contract` (→ `tests/{Unit,Functional,Contract}`); merge the packages' bootstraps into
    `tests/bootstrap.php`; delete per-package `phpunit.xml.dist`; `KERNEL_CLASS` per test class
    (four of the seven packages ship their own test kernel); fix PHPUnit 13 API breaks (attributes,
    deprecations) — mechanical changes are allowed in tests; the test databases are **MySQL**
    (owner directive 9), served by `docker-compose.yml`, with the ORM package's `custom_bootstrap.php`
    replaced by a lazy PHPUnit extension.
  - Accept: `vendor/bin/phpunit` green for every suite on the local PHP 8.5; no skipped suite; no
    `KNOWN_FAILURES` file needed. Browser tests need geckodriver or `PANTHER_SELENIUM_HOST`.

- [x] **P0-05 · PHP-CS-Fixer 3.95** · M · depends: P0-04
  - Read: PLAN/07 §6; `MDB/.php-cs-fixer.dist.php`.
  - Do: the fork's rule set in `.php-cs-fixer.rules.php`, shared by two configurations, because
    php-cs-fixer applies one header per run and the two halves of the repository need different
    ones (PLAN/07 §9): `.php-cs-fixer.dist.php` over `packages/` with the upstream Sonata header,
    `.php-cs-fixer.adminata.php` over `tests/` and `bin/` with the combined header; run `fix`;
    **one commit per package** titled `P0-05: apply PHP-CS-Fixer to <package>`.
  - Accept: `vendor/bin/php-cs-fixer check` clean for both configurations; `vendor/bin/phpunit`
    still green.

- [x] **P0-06 · Rector 2.6** · M · depends: P0-05
  - Read: PLAN/07 §6; `MDB/rector.php`.
  - Do: `rector.php` (`UP_TO_PHP_84` plus `PHPUNIT_CODE_QUALITY` — Rector 2.6 no longer ships the
    versioned PHPUnit sets — the fork's skip list plus the mock-to-stub rules that change what the
    inherited suites assert, paths as above); run; **one commit per package**; re-run CS-Fixer
    afterwards.
  - Accept: `vendor/bin/rector process --dry-run` clean; phpunit green; cs check clean.

- [x] **P0-07 · PHPStan 2.2 level 8** · L · depends: P0-06
  - Read: PLAN/07 §6; `MDB/phpstan.neon.dist`.
  - Do: `phpstan.neon.dist` (level 8, bleedingEdge, strict rules, symfony + phpunit extensions,
    paths `packages/*/{src,tests}` and `tests`); root `phpstan-console-application.php`; import each
    package's baseline as `phpstan/baseline-<name>.neon` with its paths rewritten; trim entries that
    are cheap to fix in tests (never by ignoring); regenerate.
  - Accept: `vendor/bin/phpstan analyse --memory-limit=1G` clean; `grep -r "@phpstan-ignore" tests` empty.

- [x] **P0-08 · Makefile, lint tooling, `bin/console`** · M · depends: P0-07
  - Read: PLAN/07 §6, §7.
  - Do: `Makefile` targets `lint` (cs, composer-normalize, yamllint, xmllint over xml/xliff,
    `lint:twig packages tests`, `lint:container`, `lint:xliff`, `lint:yaml`), `cs-fix`, `phpstan`,
    `rector`, `rector-fix`, `test`, `test-unit`, `test-functional`, `test-contract`, `demo`,
    `upstream-diff PKG= FROM= TO=`, `upstream-sync PKG= TO=`, `services-up`, `services-down`
    (the MySQL of `docker-compose.yml`); `.yamllint`; `bin/console` booting
    `Sonata\AdminBundle\Tests\App\AppKernel` (replaced by the demo kernel in P1-09);
    `upstream/diff.sh`, `upstream/sync.sh`,
    `upstream/exclude/<name>.txt` per PLAN/07 §10.
  - Accept: `make lint phpstan rector test` green; `bin/console about` works;
    `upstream/diff.sh twig-extensions 2.5.0 2.6.0` prints a diff stat.

- [x] **P0-09 · GitHub Actions and Dependabot** · M · depends: P0-08
  - Read: PLAN/07 §7.
  - Do: `.github/workflows/{test,qa,lint,symfony-lint,stale,upstream-watch,versions-watch}.yaml`,
    `.github/dependabot.yml`; `bin/check-replace-versions.php` (compares `replace` with
    `UPSTREAM.md`); `versions.json` consumed by `versions-watch`; issue/PR templates.
  - Accept: `yamllint .github` clean; `php bin/check-replace-versions.php` exits 0; first CI run
    green after the M0 push (P0-MS).

- [x] **P0-10 · PHP change (a): admin configuration and theme node** · M · depends: P0-04
  - Read: PLAN/01 P6 (a); PLAN/02 §3.
  - Do: in `packages/admin-bundle/src/DependencyInjection/Configuration.php` remove
    `options.skin`, `options.use_select2`, `options.use_icheck`, `options.use_bootlint`; set
    `assets.stylesheets` default to `[bundles/sonataadmin/app.css, bundles/sonataadmin/fontawesome.css]`
    and `assets.javascripts` to `[bundles/sonataadmin/app.js]`; add `theme` node (`mode` enum
    `light|dark|system` default `system`, `logo_dark`, `logo_icon` nullable); in
    `SonataAdminExtension` remove the AdminLTE skin stylesheet append and the `sonataform`
    defaults, expose `theme.*` through `SonataConfiguration`; remove every PHP read of the four
    removed options; update `tests/DependencyInjection/*`, `ConfigurationTest`, `SonataConfigurationTest`.
  - Accept: `bin/console config:dump-reference sonata_admin` shows the new tree; admin suite green;
    `grep -rn "use_select2\|use_icheck\|use_bootlint\|'skin'" packages/admin-bundle/src --include=*.php` empty.

- [x] **P0-11 · PHP change (b): grid and box defaults** · S · depends: P0-04
  - Read: PLAN/01 P6 (b).
  - Do: `BaseGroupedMapper` `box_class` default `box box-primary` → `''`; `Configuration` dashboard
    block `class` default `col-md-4` → `md:col-span-4`; update `FormMapperTest`, `ShowMapperTest`,
    `ConfigurationTest`, `AbstractAdminTest` and DI tests. The group `class` default `col-md-12`
    is **not** in PHP — it is `|default('col-md-12')` in `CRUD/base_edit_form_macro.html.twig` and
    `CRUD/base_show.html.twig`, so it becomes `col-span-12` when P4-06 and P4-07 rewrite those.
  - Accept: `grep -rn "col-md-\|box box-primary" packages/*/src --include=*.php` empty; suites green.

- [x] **P0-12 · PHP change (d)/(e): theme cookie runtime and html helpers** · M · depends: P0-10
  - Read: PLAN/01 P6 (d,e); PLAN/04 §3; PLAN/06 §6.
  - Do: `Sonata\AdminBundle\Twig\ThemeRuntime` (+ extension registration in `Resources/config/twig.php`)
    with `sonata_theme()` (cookie `sonata_theme` validated against `light|dark|system`, fallback
    `sonata_admin.theme.mode`) and `sonata_html_dir(locale)` (RTL map `ar, fa, he, ur`); drop the
    `SKIN`, `USE_SELECT2`, `USE_ICHECK` entries from wherever the `sonata-config` meta values are
    assembled in PHP; unit tests.
  - Accept: admin suite green; `ThemeRuntimeTest` covers missing/invalid/valid cookie and both helpers.

- [x] **P0-13 · PHP change (f): form-extensions native date formats** · M · depends: P0-04
  - Read: PLAN/01 P6 (f); PLAN/06 §4.
  - Do: `packages/form-extensions/src/Type/BasePickerType.php`: derive the wire `format` from
    `datepicker_options.display.components` (`yyyy-MM-dd`; `HH:mm` / `HH:mm:ss`;
    `yyyy-MM-dd'T'HH:mm` / `…:ss`), reject a different user `format` with the same exception
    Symfony's `DateType` uses for `html5: true`; remove `JavaScriptFormatConverter` and the
    `localization.format` view variable; `DateRangePickerType`/`DateTimeRangePickerType` inherit;
    `SonataFormExtension` stops registering `bundles/sonataform/app.{js,css}`; delete
    `packages/form-extensions/assets/` and `src/Bridge/Symfony/Resources/public/`; update the type tests.
  - Accept: form suite green; a unit test asserts the three derived formats and the exception;
    `grep -rn sonataform packages/form-extensions/src` empty.

- [x] **P0-14 · Cleanups of upstream tooling and dead assets** · S · depends: P0-04
  - Read: PLAN/03 §F; PLAN/09 phase 0 item 5.
  - Do: delete twig-extensions `src/Bridge/Symfony/Resources/public/` (and any DI reference),
    obsolete cookbook recipes (`recipe_bootlint`, `recipe_icheck`, `recipe_jquery_ui`,
    `recipe_select2`) and their toctree entries, the admin- and form-extensions build tooling
    (`package.json`, `webpack.config.js`, `vite.config.js`, `postcss.config.js`, `.eslintrc*`,
    `.stylelintrc*`, `prettier*`, `.babelrc*`), `assets/scss/`, the packages' own `.github/`, and
    the prebuilt `admin-lte-skins/` and `select2-locale/` (keep `assets/js/controllers` and
    `assets/images` for P1-06). The rest of `packages/admin-bundle/src/Resources/public/`
    (`app.css`, `app.js`, `fonts/`, `images/`, the manifests) stays until P1-02 rebuilds it: three
    Panther tests drive the inherited UI and need its CSS and JavaScript. The
    `MopaBootstrapBundle` extension stays too — the form theme still reads the three `horizontal_*`
    view variables it defines, so it goes with P4-01. There are no Symfony 6.4 guards to remove.
  - Accept: suites green; `git status --short | grep -v '^ D'` empty apart from intended edits.

- [x] **P0-15 · `ReplaceTest` and migration dry run** · M · depends: P0-02, P0-13
  - Read: PLAN/02 §13; PLAN/07 §1; PLAN/10 §1 step 1.
  - Do: `tests/Contract/ReplaceTest.php` (`#[Group('network')]`): temp project with a `path`
    repository to this checkout requiring `idct/adminata @dev`, `idct/sonata-admin-mongodb-bundle ^5`,
    `doctrine/doctrine-bundle ^3`, `doctrine/mongodb-odm-bundle ^5`, `symfony/framework-bundle ^8.1`;
    `composer update --dry-run --no-plugins --no-scripts` must exit 0; second case copies
    `APP/composer.json` (path from env `ADMINATA_APP_DIR`, skipped when unset) and runs the
    PLAN/10 §1 step-1 commands in dry-run mode.
  - Accept: `vendor/bin/phpunit --group network` green locally.

- [x] **P0-16 · Port the three SQLite-only exporter tests to MySQL** · S · depends: P0-04
  - Read: PLAN/README directive 9.
  - Do: `packages/exporter/tests/Source/{DoctrineDBALConnectionSourceIteratorTest,DoctrineORMQuerySourceIteratorTest,PDOStatementSourceIteratorTest}.php`
    skip themselves because they were written against an in-memory SQLite database, which adminata
    does not support. Point them at `ADMINATA_TEST_DATABASE_URL` (their own database, created the
    way `Adminata\Tests\PHPUnit\OrmDatabaseExtension` creates the ORM ones) and drop the
    `extension_loaded('pdo_sqlite')` guards.
  - Accept: `vendor/bin/phpunit --testsuite exporter` green with no skipped test; the whole run
    reports at most the two Symfony-8 conditional skips of `InlineConstraintTest`.

- [x] **P0-MS · Milestone M0 push** · S · depends: P0-03, P0-09, P0-11, P0-12, P0-14, P0-15
  - Do: full definition of done; `CHANGELOG.md` Unreleased entries; merge to `main`; `git push origin main`;
    watch the first CI run and fix red jobs.
  - Accept: `main` on GitHub at the merge commit; every workflow green; status log updated.

---

## Milestone M1 — Foundations (PLAN/09 phase 1)

- [x] **P1-01 · Tailwind 4.3 semantics fixture (T1–T11)** · M · depends: P0-MS
  - Read: PLAN/04 §4.
  - Do: `assets/css/__fixture__/{fixture.css,fixture.html}` and `bin/tailwind-fixture.mjs` compiling
    with the pinned `tailwindcss` (`@tailwindcss/node` or `@tailwindcss/cli`) and asserting each
    rule with a regex on the output; `npm run fixture`; update the status column of PLAN/04 §4; if an
    assumption fails, adjust the affected design in PLAN/04 and record it in PLAN/11 §3.
  - Accept: `npm run fixture` exits 0 printing eleven `PASS` lines.

- [x] **P1-02 · `package.json`, Vite 8 build, Makefile asset targets** · M · depends: P0-MS
  - Read: PLAN/05 §7; PLAN/07 §2, §4; PLAN/01 J11.
  - Do: private root `package.json` with exact versions from PLAN/07 §2; `.nvmrc` = `24`;
    `vite.config.js` (IIFE entry `assets/js/app.js`; CSS entries `assets/css/app.css`,
    `assets/css/fontawesome.css`; fixed output names; `fonts/[name][extname]`; `outDir`
    `packages/admin-bundle/src/Resources/public`; explicit clean list; `define __ADMINATA_VERSION__`;
    plugin writing `entrypoints.json` and `manifest.json`); placeholder sources; Makefile
    `assets-install`, `assets-build`, `assets-check` (build + `git diff --exit-code` + `css:contract`
    + `size` + `check:jquery`), `lint-js`, `lint-css`, `lint-prettier`, `test-js`.
  - Accept: `npm run build` twice leaves `git diff --exit-code -- packages/admin-bundle/src/Resources/public` clean;
    outputs `app.js`, `app.css`, `fontawesome.css`, `entrypoints.json`, `manifest.json` exist.

- [x] **P1-03 · ESLint 10, Prettier 3.9, Stylelint 17, jQuery gate** · S · depends: P1-02
  - Read: PLAN/07 §6; PLAN/01 J13.
  - Do: `eslint.config.js` (flat; `@eslint/js` recommended, prettier, header plugin with the combined
    header, import plugin; `no-restricted-globals: ['$','jQuery','Alpine']`,
    `no-restricted-imports: ['jquery']`), `prettier.config.js` (+ `prettier-plugin-tailwindcss`),
    `stylelint.config.js` (Tailwind at-rules, `stylelint-order`), scripts `lint:js`, `lint:css`,
    `lint:prettier`, `check:jquery` (fails when `npm ls jquery --all` finds a package).
  - Accept: all four scripts pass; adding `jquery` to `package.json` in a scratch branch makes `check:jquery` fail.

- [x] **P1-04 · Tokens, base, fonts, Font Awesome 7, size budgets** · M · depends: P1-01, P1-02
  - Read: PLAN/04 §3, §8; PLAN/01 C1, C6, C7, C8.
  - Do: fetch TailAdmin v2.3.0 to a scratch dir; `assets/css/theme.css` (`@custom-variant dark`,
    `@theme static` tokens copied from `src/css/style.css` lines 8–166 without the font/breakpoint
    resets, radius tokens, z-index ladder); `base.css` (border-colour shim, `color-scheme`, density
    variables, body recipe); `fonts.css` + Outfit woff2 copied from `@fontsource-variable/outfit` into
    `assets/fonts/`; `fontawesome.css` importing FA7 `all.css` with `@font-face` paths pointing to
    `fonts/` and only `fa-solid-900.woff2` + `fa-regular-400.woff2` copied; `.size-limit.json`
    (`app.css` ≤ 120 KB, `fontawesome.css` ≤ 95 KB, `app.js` ≤ 100 KB); `npm run size`.
  - Accept: `npm run build && npm run size` green; `grep -c "fonts/fa-" packages/admin-bundle/src/Resources/public/fontawesome.css` ≥ 2;
    no `.ttf`/`.eot`/Google Fonts references in the built CSS.

- [x] **P1-05 · `.adm-*` component layer, safelist, CSS contract** · L · depends: P1-04
  - Read: PLAN/04 §1, §2, §5; `PLAN/research/tailadmin-catalog.md` §2; `PLAN/research/gap-css-architecture.md` §5.
  - Do: `assets/css/components/{button,btn-icon,input,select,textarea,checkbox,radio,badge,card,callout,alert,dropdown,table,pagination,sidebar,layout,dialog,list,show,misc}.css`
    (`@utility` for single-selector primitives, `@layer components` for descendant rules; recipes
    transcribed from TailAdmin partials with logical utilities); `adminata.css` aggregate;
    `safelist.css` (grid ranges + generated names) via `bin/build-css-safelist.mjs`;
    `assets/css/contract.json` generated; `bin/check-css-contract.mjs` (selectors present, forbidden
    `.btn`, `.box`, `.label`, `.col-md-*`, `.container{`, `.collapse{visibility` absent, budgets);
    `npm run css:contract`.
  - Accept: `npm run build && npm run css:contract` green; `contract.json` committed; every `.adm-*`
    name appears in the built `app.css`.

- [x] **P1-06 · JS core, inherited controllers, Vitest, contract snapshot** · L · depends: P1-03
  - Read: PLAN/05 §1, §2, §7, §9; PLAN/02 §9.
  - Do: move `packages/admin-bundle/assets/js/controllers/*_controller.js` and `core/*` to
    `assets/js/`, images to `assets/images/`, then delete `packages/admin-bundle/assets/`;
    `assets/js/app.js` (start `Application`, `window.sonataApplication`, remove `html.no-js`);
    `registry.js` (explicit nine); `sonata-edit`: replace the jQuery tab line with
    `this.dispatch('show', {prefix: 'sonata-tabs'})`; `Config.param` null-tolerant;
    `vitest.config.js` (jsdom); a Vitest test per inherited controller, each naming the template its
    markup mirrors; `assets/js/__contract__/controllers.json` + snapshot test introspecting the
    built `app.js`. The fixtures are hand-written here rather than dumped from Twig: the templates
    are still the inherited Bootstrap ones, so a dumper would snapshot markup M2 to M4 replaces.
    Task **P1-13** adds `JsFixtureDumperTest` once the templates are adminata's.
  - Accept: `npm run test` green; `grep -rn "jQuery\|\$(" assets/js` empty; built `app.js` registers
    nine identifiers (snapshot test).

- [x] **P1-07 · `frontend.yaml` workflow** · S · depends: P1-05, P1-06
  - Do: Node 24 and 26 matrix running `npm ci`, `lint:js`, `lint:css`, `lint:prettier`, `check:jquery`,
    `fixture`, `test`, `build`, `git diff --exit-code -- packages/admin-bundle/src/Resources/public`,
    `css:contract`, `size`.
  - Accept: green on the M1 push.

- [x] **P1-08 · Contract tests and deferred-template markers** · M · depends: P0-MS
  - Read: PLAN/02 §8, §10, §13; PLAN/03 §E, §G; PLAN/08 §4.
  - Do: `tests/Contract/hooks.yaml` (every hook, id, data attribute and link text of PLAN/02 §8/§10)
    and `HookContractTest` (static scan of template sources, per template group);
    `TemplatePathTest` (registry defaults, `@Sonata*/…` strings in `packages/*/src/**/*.php`, and the
    MongoDB fork's hard-coded paths, all resolvable through the Twig loader);
    `tests/Contract/deferred-templates.txt` (36 paths) + `DeferredTemplateTest` (marker present;
    none rendered by the demo app) and add `{# adminata: not yet ported #}` to those 36 files;
    `ConfigContractTest` (dump-reference of the seven roots against
    `tests/Contract/config-reference/<root>.yaml` captured from the pristine import commit, with an
    allowlist of the PLAN/02 §3 differences).
  - Accept: `make test-contract` green (hook checks for not-yet-rewritten templates are marked
    `@todo` per group and enabled in later milestones).

- [x] **P1-09 · Demo ORM application** · L · depends: P0-MS
  - Read: PLAN/08 §2; appendix C §2.
  - Do: `tests/App/Kernel.php` (Framework, Twig, Security with in-memory users, Doctrine on the
    MySQL service of `docker-compose.yml` — owner directive 9, no SQLite — FixturesBundle, KnpMenu,
    the seven Sonata bundles), `tests/App/config/*`
    (`sonata_admin` with three groups, raw `<i>` icons, `theme`, `use_stickyforms`,
    `lock_protection`; `sonata_block`; `sonata_form`), an event subscriber injecting a
    `sidebar-section-header` item, first entities and admins (`Category`, `Product`: string, int,
    datetime, boolean, enum, many-to-one; filters; a native collection), fixtures; `bin/console`
    switched to this kernel; `make demo` (`php -S 127.0.0.1:8000 -t tests/App/public` or Symfony CLI)
    with `assets:install --symlink`; `tests/Functional/DemoSmokeTest.php` (BrowserKit: dashboard,
    list, create, edit → 200).
  - Accept: `make demo` serves `/admin/dashboard`; `vendor/bin/phpunit --testsuite adminata-functional` green.

- [x] **P1-10 · Playwright, axe, html-validate harness** · M · depends: P1-09
  - Read: PLAN/08 §3, §8.
  - Do: `playwright.config.ts` (Chromium/Firefox/WebKit; viewports 375/768/1280; themes via the
    `sonata_theme` cookie; web server = demo app), `tests/Visual/dashboard.spec.ts` skeleton with
    `toHaveScreenshot`, `@axe-core/playwright` helper, `html-validate` config and run over captured
    DOM; `visual.yaml` workflow; `make test-visual`.
  - Accept: `npx playwright test` passes and writes baselines under `tests/Visual/__snapshots__` (committed).

- [x] **P1-11 · Panther harness** · M · depends: P1-09
  - Read: PLAN/08 §3; `MDB/tests/Functional/` base classes; `MDB/docker-compose.yml`.
  - Do: `tests/Functional/BasePantherTestCase.php` (from the fork, with the console-error assertion
    helper and `PANTHER_SELENIUM_HOST` switch), `docker-compose.yml` with `selenium/standalone-firefox`,
    first test (dashboard loads, console empty); `make test-functional`.
  - Accept: `make test-functional` green locally (docker or geckodriver).

- [x] **P1-12 · `mongo-compat.yaml`** · M · depends: P1-09
  - Read: PLAN/08 §7; PLAN/02 §1 "MongoDB fork contract".
  - Do: PR job — check out `ideaconnect/sonata-admin-mongodb-bundle`, add a `path` repository to the
    adminata checkout, `composer require idct/adminata:@dev --no-plugins`, `composer validate`, run its
    unit suite (blocking); nightly job — its Panther suite with a Mongo service (`continue-on-error: true`).
    Any change the fork itself needs is a separate PR in the fork repository (note it in the status log).
  - Accept: the PR job is green on the M1 push.

- [ ] **P1-13 · Dump the JavaScript test fixtures from the real templates** · M · depends: P2-MS
  - Read: PLAN/05 §9.
  - Do: `tests/Unit/Fixture/JsFixtureDumperTest.php` renders the templates the controllers attach
    to through the demo kernel into `tests/fixtures/js/*.html`; the Vitest suites load those
    instead of their inline markup. Only worth doing once a template is adminata's, which is why
    it follows M2 rather than sitting in P1-06.
  - Accept: `npm run test` green against dumped fixtures; a template change that breaks a
    controller fails the JavaScript suite.

- [x] **P1-MS · Milestone M1 push** · S · depends: P1-07, P1-08, P1-10, P1-11, P1-12
  - Accept: definition of done green; all workflows green on GitHub; status log updated.

---

## Milestone M2 — Shell (PLAN/09 phase 2)

- [ ] **P2-01 · `standard_layout.html.twig`** · L · depends: P1-MS
  - Read: PLAN/03 §A row 1; PLAN/02 §5, §9; PLAN/04 §3; PLAN/05 §8; `PLAN/research/layout-nav.md` §2.
  - Do: rewrite with the TailAdmin shell (window-scroll model: fixed `aside.main-sidebar`, sticky
    header, `lg:ml-[290px]` content, overlay); keep all 33 blocks and the 12 captured child blocks;
    remove `admin_lte_skin_class` and `bootlint`; add `sonata_overlay`, `sonata_header_search`
    (empty when `search` is off), `sonata_top_nav_menu_dark_mode`, `sonata_script_attributes`;
    `<html lang dir class="no-js …dark" data-theme>` from `sonata_theme()`; meta `sonata-config`
    (`CONFIRM_EXIT`, `USE_STICKYFORMS`, `DEBUG`) and `sonata-translations`; stylesheet/script loops
    with `defer`; `<body>` with `sonata-layout` and `sonata-sticky`; `notice` includes
    `@SonataTwig/FlashMessage/render.html.twig`; the actions row (T7); noscript warning; `user_block`
    and `add_block` wrappers as `<ul>` pass-through.
  - Accept: `make lint`; admin functional tests green after expectation updates; `HookContractTest`
    shell group enabled and green; demo dashboard shows the shell in light and dark.

- [ ] **P2-02 · `sonata-layout` controller** · M · depends: P2-01
  - Read: PLAN/05 §3 row 1.
  - Do: controller (values `collapsed`, `mobileOpen`, `headerMenuOpen`, `breakpoint`, `cookieName`;
    targets; `sonata_sidebar_hide` cookie `SameSite=Lax`; `inert` on content while the drawer is open;
    events; tolerant of missing targets); Vitest; Panther: collapse persists after reload.
  - Accept: tests green; `assets/js/__contract__/controllers.json` updated.

- [ ] **P2-03 · `sonata-theme`, dark-mode toggle and pre-paint script** · M · depends: P2-01
  - Read: PLAN/01 C2; PLAN/04 §3; PLAN/05 §3 row 5, §8.
  - Do: controller (toggle `html.dark`, cookie, event), header button in
    `sonata_top_nav_menu_dark_mode`, the 3-line `system` pre-paint script under
    `sonata_script_attributes`; Vitest; Panther (cookie `dark` → `html.dark` after reload).
  - Accept: tests green; no other inline script in the layout (`grep -c "<script>" …` = 1).

- [ ] **P2-04 · Sidebar menu template and `sonata-menu`** · L · depends: P2-02
  - Read: PLAN/03 §A row 4; PLAN/01 T5; PLAN/05 §3 row 2; appendix C §2 "Menu".
  - Do: rewrite `Menu/sonata_menu.html.twig` (KnpMenu blocks, group `<button aria-expanded>`,
    TailAdmin `menu-item*` classes, `keep-open`, `on_top`, raw `<i>` icons through `parse_icon`,
    items with class `sidebar-section-header` or extra `section_header` rendered as
    `menu-group-title`); controller with the `sonata_sidebar_open` map; update
    `tests/Menu/Integration/*`; demo subscriber injects a header.
  - Accept: admin menu tests green; `.sidebar-menu … a` selector still matches; Panther: group state persists.

- [ ] **P2-05 · `sonata-dropdown`, user block, add block** · M · depends: P2-01
  - Read: PLAN/05 §3 row 3; PLAN/03 §A rows 5–6.
  - Do: controller (targets `toggle`, `menu`; click-outside, ESC, arrow keys, `aria-expanded`);
    `Core/user_block.html.twig` wrapper with initials avatar and `dropdown-user` list;
    `Core/add_block.html.twig` panel with `grid grid-cols-{column_count}`; Vitest; Panther keyboard navigation.
  - Accept: tests green; `add_block` functional test green.

- [ ] **P2-06 · `sonata-modal` and dialog styles** · M · depends: P1-05, P2-01
  - Read: PLAN/05 §3 row 4; PLAN/01 J7.
  - Do: controller (`open`/`close`, sizes, move to `document.body` on first open, backdrop click when
    `closable`, events), `adm-dialog` CSS, a demo page with a dialog; Vitest; Panther (focus trap, ESC).
  - Accept: tests green; documented usage snippet in `docs/` (or README until P6-01).

- [ ] **P2-07 · Flash-message template (twig-extensions) and `sonata-dismiss`** · M · depends: P2-01
  - Read: PLAN/03 §A "FlashMessage" row; PLAN/03 §G; PLAN/01 T3.
  - Do: rewrite `packages/twig-extensions/src/Bridge/Symfony/Resources/views/FlashMessage/render.html.twig`
    (TailAdmin alert recipes, `alert alert-{type}` marker classes, dismiss button, CSS-only read-more
    toggle), `sonata-dismiss` controller, alert CSS; re-baseline twig-extensions tests.
  - Accept: twig suite green; demo shows a flash after saving; Vitest for dismiss.

- [ ] **P2-08 · Breadcrumbs and page header** · S · depends: P2-01
  - Read: PLAN/03 §A "Breadcrumb" row.
  - Do: layout `<ol>` styling with CSS chevrons, `aria-label`, `aria-current`; page header with
    title, breadcrumb and the actions row; leave `Breadcrumb/*.html.twig` byte-identical.
  - Accept: `BreadcrumbsRuntimeTest` untouched and green; visual check in both themes.

- [ ] **P2-09 · Dashboard, admin-list block, dashboard actions** · M · depends: P2-01
  - Read: PLAN/03 §A rows 7, 9, 10.
  - Do: `Core/dashboard.html.twig` (12-column grid, block `class` verbatim),
    `Block/block_admin_list.html.twig` (cards), `CRUD/dashboard__action.html.twig`,
    `CRUD/dashboard__action_create.html.twig`; verify block-bundle `block_base` needs nothing;
    update `DashboardActionTest` and block tests.
  - Accept: tests green; Playwright dashboard baseline updated.

- [ ] **P2-10 · Ajax and empty layouts, list-mode buttons, login-style pages** · S · depends: P2-01
  - Read: PLAN/03 §A rows 2–3; PLAN/01 T9.
  - Do: `ajax_layout.html.twig` (no `<html>`, keeps list hooks), `empty_layout.html.twig`, new
    `Core/list_mode_buttons.html.twig` (only when `show_mosaic_button`), `sonata_header` renders
    nothing when `logo` and `sonata_nav` are empty; demo login-style page.
  - Accept: functional test: list request with `X-Requested-With` returns no `<html>` and contains
    `table.sonata-ba-list`; login-style page shows no header bar.

- [ ] **P2-11 · Shell visual, accessibility and Panther coverage** · M · depends: P2-03 … P2-10
  - Read: PLAN/08 §3, §8.
  - Do: Playwright baselines (dashboard, empty layout, login-style page × 3 viewports × 2 themes);
    axe clean on the shell; Panther suite (sidebar collapse/persist, dark toggle, dropdown keyboard,
    dialog open/close) asserting an empty console.
  - Accept: `make test-visual` and `make test-functional` green.

- [ ] **P2-MS · Milestone M2 push** · S · depends: P2-11
  - Accept: definition of done green; workflows green; status log updated.

---

## Milestone M3 — List (PLAN/09 phase 3)

- [ ] **P3-01 · `base_list.html.twig` and `list.html.twig`** · L · depends: P2-MS
  - Read: PLAN/03 §B rows 1–2; PLAN/02 §8 "List"; `PLAN/research/list-datagrid.md`.
  - Do: card header (title, actions), filter panel card (rows `grid grid-cols-12 gap-3`, existing
    `sonata-filter`/`sonata-filter-list` hooks and ids), table wrapper `max-w-full overflow-x-auto`,
    footer outside the wrapper (batch select + submit, export `sonata-dropdown`, per-page, pager),
    new `list_after_table` block, empty `batch_javascript`, `no_result_content`; all 22 blocks;
    `name="action"`, `idx[]`, `all_elements`, `_sonata_csrf_token`.
  - Accept: `make lint`; admin functional list tests green; demo list renders; `HookContractTest`
    list group enabled.

- [ ] **P3-02 · Filter theme** · M · depends: P3-01
  - Read: PLAN/03 §B "filter_admin_fields" row; PLAN/06 §1 "Filter theme"; PLAN/01 F2.
  - Do: rewrite `Form/filter_admin_fields.html.twig` (native operator selects, TailAdmin inputs,
    `type=date` filters, additive `sonata_type_date_range_widget`, untouched pass-through of
    `data-controller` attributes); confirm the ORM and MongoDB filter themes (copied unchanged) render.
  - Accept: filter functional tests green; Panther: add/remove/reset filters; `prepareSubmit` keeps working.

- [ ] **P3-03 · `sonata-batch`, `list__batch`, `list__select`** · M · depends: P3-01
  - Read: PLAN/05 §3 row 7; PLAN/03 §B "list__batch" row.
  - Do: controller (select-all with indeterminate, row highlight, shift-range with the upstream
    typo fixed), native `appearance-none` checkbox recipe in both templates; Vitest; Panther shift-range.
  - Accept: tests green; contract snapshot updated.

- [ ] **P3-04 · Cell envelope and row templates** · M · depends: P3-01
  - Read: PLAN/03 §B rows 3, 7; PLAN/01 Q1.
  - Do: `base_list_field.html.twig` (envelope byte-identical, readmore, editable span only when
    `editable`), `base_list_inner_row`, `list_inner_row`, `list_outer_rows_list`; hover/selected rows via CSS.
  - Accept: envelope assertions of `RenderElementRuntimeTest` unchanged and green.

- [ ] **P3-05 · Typed list and display templates** · M · depends: P3-04
  - Read: PLAN/03 §B "list_*" row.
  - Do: 14 `list_*` and 12 `display_*` templates (`adm-badge` booleans, `target="_blank"
    rel="noopener"` URLs, plain currency/percent); re-baseline the affected expectations of
    `RenderElementRuntimeTest` and its Extension twin without touching envelope assertions.
  - Accept: admin twig suite green; diff of the test file touches only badge/url/boolean expectations.

- [ ] **P3-06 · Row actions and association list templates** · S · depends: P3-04
  - Read: PLAN/03 §B "list__action" and "Association/list" rows.
  - Do: `list__action.html.twig`, four `list__action_*` (`adm-btn-icon`, `*_link` hooks, `sr-only`,
    `list_action_button_content`), four `Association/list_*`.
  - Accept: tests green; ORM `ListBuilder` path renders in the demo.

- [ ] **P3-07 · Pager templates** · S · depends: P3-01
  - Read: PLAN/03 §B "Pager" rows.
  - Do: five `Pager/*` templates on the same `ul.pagination > li(.active) > a` structure with
    `aria-label`s; native per-page select with URL option values.
  - Accept: pager tests green; Panther per-page reload.

- [ ] **P3-08 · Batch confirmation page** · S · depends: P3-01
  - Do: `CRUD/batch_confirmation.html.twig` (danger card, same POST fields).
  - Accept: functional batch flow with `confirmation=ok` green.

- [ ] **P3-09 · `sonata-autocomplete` combobox (filter context)** · L · depends: P3-02
  - Read: PLAN/06 §3; PLAN/05 §3 row 8; PLAN/01 J5.
  - Do: controller (single value first: debounce, min length, remote paging, `403`, keyboard,
    `aria-activedescendant`, click-outside, `safe_label`, hidden inputs, events);
    `Form/Type/sonata_type_model_autocomplete.html.twig` rewritten (blocks kept, `<template>`
    elements, `_context=filter`); Vitest; axe; Panther keyboard flow on the demo filter.
  - Accept: tests green; `ModelAutocompleteFilter` works in the demo.

- [ ] **P3-10 · Demo list coverage, Panther and visual baselines** · M · depends: P3-03 … P3-09
  - Read: PLAN/08 §2, §3; appendix C §2.
  - Do: demo admins with every filter type, every cell type (including custom cell templates
    extending `base_list_field` and emitting raw `<td>`), `header_class`, `sort_field_mapping`,
    custom `list__action_*`, a custom batch action with confirmation, export, a `templates.list`
    override using `list_after_table`, an XHR child list, and a select carrying
    `data-controller="symfony--ux-autocomplete--autocomplete"` in a filter; Panther flows; Playwright
    baselines for list pages; axe.
  - Accept: all suites green; baselines committed.

- [ ] **P3-MS · Milestone M3 push** · S · depends: P3-10
  - Accept: definition of done green; workflows green; status log updated.

---

## Milestone M4 — Forms and show (PLAN/09 phase 4)

- [ ] **P4-01 · Form theme: rows, labels, help, errors, simple widgets** · L · depends: P3-MS
  - Read: PLAN/06 §1; PLAN/03 §C row 1; PLAN/02 §8 "Forms".
  - Do: in `Form/form_admin_fields.html.twig`: `form_row`, `form_label`, `form_help` (`help_html`
    raw), `form_errors`, `form_widget_simple`, `textarea_widget`, `widget_attributes` (append only),
    `money_widget`, `percent_widget`, `date_widget`/`datetime_widget`/`time_widget` (native);
    horizontal mode grid; `row_class`/`widget_class`/`label_class`/`help_class`/`error_item_class`.
  - Accept: `make lint`; the row/label/help/error cases of `AdminLayoutTest` green.

- [ ] **P4-02 · Form theme: choices, checkboxes, radios** · M · depends: P4-01
  - Do: `choice_widget_collapsed` (native `adm-select`), `choice_widget_expanded`, `checkbox_*`,
    `radio_*`, `checkbox_radio_label`; a test proving `attr.data-controller` passes through untouched.
  - Accept: `tests/Form/Widget/*` rewritten and green.

- [ ] **P4-03 · Form theme: collections and remaining Sonata blocks** · M · depends: P4-02
  - Read: PLAN/06 §2; PLAN/03 §C row 1.
  - Do: `sonata_type_native_collection_widget(_row)` (card rows, remove/add buttons, prototype),
    `sonata_type_immutable_array_widget(_row)`, `sonata_type_template_widget`,
    `sonata_type_choice_field_mask_widget` (markup only), `sonata_type_choice_multiple_sortable`
    (plain multiple select), `sonata_type_model_list_widget` (renders the inherited include), file
    input recipe; re-dump JS fixtures; Vitest for `sonata-collection` on the new markup.
  - Accept: form suite green; Panther collection add/delete.

- [ ] **P4-04 · Native date/time template (form-extensions)** · M · depends: P0-13, P4-01
  - Read: PLAN/06 §4; PLAN/03 §C "datepicker" row.
  - Do: rewrite `packages/form-extensions/src/Bridge/Symfony/Resources/views/Form/datepicker.html.twig`
    (`type` from components, `step`, `min`/`max`, `adm-input`); widget tests in the form suite; demo
    fields (date, datetime, time-only, ranges).
  - Accept: form suite green; Panther round trips `07:30` and `2026-09-04T10:15`.

- [ ] **P4-05 · `sonata-autocomplete` form context (single and multiple)** · M · depends: P3-09, P4-01
  - Read: PLAN/06 §3.
  - Do: chips for `multiple`, `name[]` hidden inputs, selection `<template>`, Backspace removal,
    `sonata_type_model_autocomplete_widget` block; Vitest; axe; Panther single and multiple.
  - Accept: tests green.

- [ ] **P4-06 · Edit chrome** · L · depends: P4-03
  - Read: PLAN/03 §C "base_edit" row; PLAN/02 §7 button names; PLAN/01 T7, T8.
  - Do: `base_edit`, `edit`, `base_edit_form` (16 blocks; groups grid with class verbatim and
    `box_class` extras; tabs rendered sequentially with `<h2>`; sticky action bar with the `btn_*`
    names; `form_rest`), `base_edit_form_macro`, `Helper/render_form_dismissable_errors`,
    `base_array_macro`; `sonata-sticky` `action` target and `.stuck` CSS; `sonata-confirm-exit` wiring.
  - Accept: functional edit tests green; Panther: confirm-exit prompt, sticky bar, lock error flash
    (demo `lock_protection`).

- [ ] **P4-07 · Show pages** · M · depends: P4-01
  - Read: PLAN/03 §D rows 1–2.
  - Do: `base_show`, `show`, `base_show_field` (`<table><tr><th><td>` kept, readmore), 13 `show_*`,
    4 `Association/show_*`; groups grid.
  - Accept: show tests green (`EUR 10.746135`-style expectations untouched); Playwright baseline.

- [ ] **P4-08 · Buttons, action bar, delete page** · S · depends: P4-01
  - Read: PLAN/03 §D rows 3, and §C "delete" row.
  - Do: six `Button/*`, `action_buttons.html.twig` (`<ul class="sonata-actions">` flex row),
    `delete.html.twig` (danger card, `_method=DELETE`, `_sonata_csrf_token`).
  - Accept: functional delete test green.

- [ ] **P4-09 · ORM and MongoDB form themes verified** · S · depends: P4-03
  - Read: PLAN/03 §G; PLAN/02 §1 "MongoDB fork contract".
  - Do: render the ORM form and filter themes and the fork's themes in the demo and the
    `mongo-compat` job; confirm no Bootstrap class leaks from copied templates; the association blocks
    still include the deferred (marked) files.
  - Accept: ORM suite and `mongo-compat` PR job green; grep of the two ORM themes for `btn\|form-control\|col-md` empty.

- [ ] **P4-10 · Demo form coverage, Panther, visual, contract completion** · M · depends: P4-04 … P4-09
  - Read: PLAN/08 §2, §3, §6; appendix C §2 "Forms".
  - Do: demo admins with every form type of appendix C §2 (including collections with sub-forms and
    time-only pickers, ranges, autocomplete single/multiple, a `data-controller` pass-through select,
    `help_html`, `col-span-*` groups, lock protection, sticky forms); Panther flows; Playwright
    baselines for every 1.0 page × viewport × theme; axe no serious violations; `tests/Form` green;
    `HookContractTest` all groups enabled; `BlockNameTest` (138 − 2 admin block names present).
  - Accept: everything green.

- [ ] **P4-MS · Milestone M4 push** · S · depends: P4-10
  - Accept: definition of done green; workflows green; status log updated.

---

## Milestone M5 — recomaty-panel migration and acceptance (PLAN/09 phase 5)

These tasks run in the app checkout `APP/` on branch `adminata`; adminata defects found on the way
become `P5-FIX-nn` tasks here. Step numbers refer to PLAN/10 §1.

- [ ] **P5-01 · Composer, lock file and configuration (steps 1–3)** · S · depends: P4-MS
  - Accept: `git diff -- config/` empty except the removed `use_select2` line; `composer validate`;
    `bin/console cache:clear` and `assets:install public` succeed; `bundles.php` untouched.

- [ ] **P5-02 · Layout override (step 4)** · S · depends: P5-01
  - Accept: the override keeps only `stylesheets`, `sonata_head_title`, `sonata_top_nav_menu_add_block`,
    `sonata_wrapper` (dialog), `sonata_page_content_header` (`adm-alert`), `content`; pages render.

- [ ] **P5-03 · Login, password-reset layout, user block (step 5)** · M · depends: P5-02
  - Accept: login and reset pages render on the TailAdmin sign-in recipe; `user_block` links present.

- [ ] **P5-04 · Page templates and `notice` includes (steps 6–7)** · M · depends: P5-02
  - Accept: the ten page templates use `adm-*` components; dead templates deleted; nine `notice`
    overrides call `{{ parent() }}`.

- [ ] **P5-05 · Bundle overrides (step 8)** · M · depends: P5-01
  - Accept: 16 `list__action*`, `create_button`, `list_enum`, `list_many_to_one`, `list__select`
    ported; `Association/base_list_inner_row.html.twig` deleted; `grep -rn "btn btn-\|label label-" templates/bundles` empty.

- [ ] **P5-06 · List summaries block (step 9)** · S · depends: P5-01
  - Accept: `crud/list_with_summaries.html.twig` overrides `list_after_table` only.

- [ ] **P5-07a · Cell templates, first third (step 10)** · M · depends: P5-01
  - Do: `templates/field/` files `accentColor` … `enum` (alphabetical), plus `component/adminUser`.
  - Accept: no Bootstrap/AdminLTE class in the ported files; envelope kept; pages render in both themes.

- [ ] **P5-07b · Cell templates, second third (step 10)** · M · depends: P5-01
  - Do: `imagePreviewPromoPromoted` … `rvmTaskType`.
  - Accept: as P5-07a.

- [ ] **P5-07c · Cell templates, last third (step 10)** · M · depends: P5-01
  - Do: `shortUidWithBranding` … `whiteLabelLogo`.
  - Accept: as P5-07a; `grep -rn "callout\|label label-\|btn btn-\|box-" templates/field` empty.

- [ ] **P5-08 · CSS and build (step 11)** · M · depends: P5-02
  - Accept: Encore builds `build/admin.css` with `@tailwindcss/postcss`; `admin-theme.scss` gone;
    `sonata-overrides.scss` ≤ 160 lines; `.mt-10` renamed; FA family updated.

- [ ] **P5-09 · Admin classes, date formats, icons (steps 12–14)** · S · depends: P5-01
  - Accept: `grep -rn "col-md-" src` empty; `grep -rn "'format' =>" src/Admin src/Form | grep -i "yyyy\|dd\." ` empty;
    `grep -rn "clock-o" templates` empty.

- [ ] **P5-10 · JavaScript port (step 15)** · S · depends: P5-02
  - Accept: `grep -rnE "jQuery|\\$\\(" assets templates` empty; `jquery-ui*` gone from `package.json`;
    `npm ls jquery` empty; universal modal, section slider and accordion work in the browser.

- [ ] **P5-11 · Acceptance run (step 16)** · L · depends: P5-03 … P5-10
  - Read: PLAN/08 §6.
  - Do: run the Behat suite; add the BrowserKit and Panther scenarios (A1–A2, D1–D3, L1–L9, F1–F10,
    S1, X1, X2); every adminata defect becomes a `P5-FIX-nn` task here and is fixed in adminata
    (not worked around in the app).
  - Accept: all scenarios green in light and dark; console empty; owner visual sign-off recorded in the status log.

- [ ] **P5-12 · `MIGRATION.md` and `UPGRADE-1.0.md`** · S · depends: P5-11
  - Accept: `MIGRATION.md` is the executed checklist with real hours; `UPGRADE-1.0.md` has U1–U9.

- [ ] **P5-MS · Milestone M5 push and `v1.0.0-rc1`** · S · depends: P5-12
  - Accept: `git tag v1.0.0-rc1` pushed; workflows green; status log updated.

---

## Milestone M6 — Release (PLAN/09 phase 6)

- [ ] **P6-01 · Documentation site** · L · depends: P5-MS
  - Read: PLAN/12.
  - Do: merge `packages/*/docs` into `docs/` (one Sphinx site, sections per package), prune to the 1.0
    scope, "not yet ported" banners, new pages (theming, icons, JavaScript API, compiling Tailwind
    yourself, porting status), `.readthedocs.yaml`, `documentation.yaml` workflow.
  - Accept: `make docs` builds without warnings; `documentation.yaml` green.

- [ ] **P6-02 · Release `v1.0.0`** · S · depends: P6-01
  - Do: `CHANGELOG.md`, `NOTICE`, `README.md` final; bump `replace` check; tag `v1.0.0`; Packagist
    submission (owner action); announcement text under `docs/`.
  - Accept: tag pushed; Packagist shows `idct/adminata 1.0.0`.

- [ ] **P6-MS · Milestone M6 push** · S · depends: P6-02

---

## Backlog (unscheduled; each becomes tasks when first needed — PLAN/09 backlog)

- [ ] **B-01** Association widgets without AJAX submission (11 templates, `sonata-association`, `sonata-tabs`), then the MongoDB fork's Panther scenarios adapted.
- [ ] **B-02** `sonata-datepicker` progressive enhancement with `vanilla-calendar-pro`.
- [ ] **B-03** History and compare pages; ORM `block_audit`.
- [ ] **B-04** ACL pages.
- [ ] **B-05** Preview, subclass selection, mosaic and tree list modes, `CRUD/action.html.twig`.
- [ ] **B-06** Global search page and block; tab menu and child admins.
- [ ] **B-07** Admin dashboard blocks (stats, RSS, search result, admin preview); block-bundle RSS and side-menu templates.
- [ ] **B-08** `sonata-editable`, `sonata-treeview`, `sonata-choice-field-mask`, sortable collections (SortableJS).
- [ ] **B-09** Optional select enhancement (`sonata-select`, Tom Select).
- [ ] **B-10** ESM entry (`startAdminata`), AssetMapper mapping and docs, Flex recipe, tooltips, toast flash mode.
- [ ] **B-11** Monthly upstream sync of the seven packages; Infection nightly; `BEST_VERSION.md`.
- [ ] **B-12** Give the 188 `expects(static::any())` call sites P0-07 introduced a real invocation
  count. PHPUnit 13 deprecates `any()` and PHPUnit 14 removes it, and the 627 deprecations the suite
  reports are the same sites. Each needs a judgement about how often the tested code should call the
  mocked method, so it is not a mechanical change.
- [ ] **B-13** Install `phpstan/phpstan-doctrine`. It finds real mapping mismatches (a nullable
  property behind a `JoinColumn(nullable: false)`, twice in the demo alone) and would let the
  entities drop the `setId()` they only carry to give `?int $id` an assignment PHPStan can see. It
  also surfaces 14 findings in inherited package code — `ProxyQuery`'s covariant template,
  `ModelFilter` and `SmartPaginatorFactory`'s unresolved `T`, `DoctrineORMQuerySourceIterator`,
  and four inherited test entities — each of which needs a decision rather than a baseline entry.
- [ ] **B-14** Finish the `FormErrorIterator` generic. `skipCheckGenericClasses` removed the
  deterministic failure — fifteen consecutive runs clean since — but one parallel run has still
  reported it, so PHPStan can in principle still flake on an incremental analysis. The durable fix
  is either upstream in PHPStan or a signature change to
  `FormErrorIteratorToConstraintViolationList::transform()`, which handles only a flattened
  iterator today and would raise a TypeError on the nested one its parameter type nominally
  permits. That is a change to inherited public API and belongs in its own task.

---

## Status log

- 2026-09-04 — Plan v3 committed and pushed (`8430fae`); PROJECT_PLAN.md created; no task started.
- 2026-09-04 — **P0-01 done.** Seven upstream packages imported with `git subtree add` at the PLAN/07
  §2 tags (full history, no `--squash`: `.git` is 65 MB against the 300 MB threshold).
  `upstream/remotes.txt` and `UPSTREAM.md` written. Plan change in the same commit: the P0-01 accept
  command grepped `git log --oneline`, which never contains the subtree footer; it now counts
  `git-subtree-dir: packages/` over full commit messages (`--merges` alone also matches the imported
  upstream merge commits, so the footer is the precise check).
- 2026-09-04 — **P0-02 done.** Root `composer.json` written (union `require`, `require-dev` union at
  the PLAN/07 §2 versions, `replace` × 7, PSR-4 × 10 + `Adminata\Tests\`), the seven per-package
  `composer.json` files deleted, `composer update` and `composer normalize` clean, all seven bundle
  classes instantiate. Plan changes in the same commit (PLAN/07 §3): upstream's unsatisfiable
  `symfony/security-acl: "<3.1 >=4.0"` becomes a disjunction (`composer validate --strict` rejects
  the conjunction); the three upstream guards that survive the raised floors are kept in `conflict`;
  the contracts packages and the dev-dependency union are spelled out.
- 2026-09-04 — **Owner directive 9: MySQL, MariaDB and Percona only; no SQLite.** Raised when the
  ORM suite turned out to need `ext-pdo_sqlite`, which this machine does not have. Recorded in
  PLAN/README, and PLAN/07 §3, PLAN/08 §2, PLAN/09 phase 1 and the tasks below were changed to match:
  `ext-pdo_sqlite` is out of `require-dev`, the test databases are MySQL, and `docker-compose.yml`
  ships one (`mysql:8.4` on 127.0.0.1:7010, `tmpfs` data directory).
- 2026-09-04 — **P0-04 done.** One `phpunit.xml.dist` with the ten suites, `tests/bootstrap.php`
  merged from the seven identical dev-kit bootstraps, and two PHPUnit 13 extensions:
  `KernelClassExtension` sets `KERNEL_CLASS` per test class (four packages ship their own kernel and
  a single environment variable cannot serve them), `OrmDatabaseExtension` replaces the ORM
  package's `custom_bootstrap.php` — it creates both MySQL databases and loads the fixtures once,
  on `TestSuite\Loaded` rather than per test, because dama/doctrine-test-bundle wraps every test in
  a transaction, and it restores the error and exception handler stack the console leaves behind so
  the admin functional tests do not turn risky. Twelve fixes to inherited tests, all of them either
  layout-driven or upstream-version-driven, are listed in the commit message.
  **2604 tests green** (5 skips: 3 SQLite-only exporter tests → P0-16, 2 Symfony-8 conditional).
  Browser tests need a driver: `PANTHER_SELENIUM_HOST=http://127.0.0.1:4444/wd/hub` was used here
  because another container holds port 4444; `PANTHER_FIREFOX_PORT` moves a spawned geckodriver.
  On this machine the Selenium route is the only one that works: the snap Firefox cannot be driven
  by geckodriver under WSL ("Failed to read marionette port"), so `make test` locally needs
  `PANTHER_SELENIUM_HOST=http://localhost:4444`. CI spawns its own driver and needs neither.
- 2026-09-04 — **P0-16 done.** The three exporter tests run against MySQL through a new
  `Adminata\Tests\Support\TestDatabase` helper (URL resolution, per-suite database name, create or
  recreate, plain PDO handle), which `OrmDatabaseExtension` and `TestEntityManagerFactory` now use
  too. **2604 tests green, 2 skips** — both of them upstream's own "skip on Symfony 8" guards.
- 2026-09-04 — **P0-05 done.** Two php-cs-fixer configurations sharing one rule set (see the task);
  16 of 948 package files needed fixing, committed per package (admin-bundle 11, exporter 1,
  form-extensions 3). `modern_serialization_methods` is disabled: it renames `__wakeup()` to
  `__unserialize(array $data)` without moving the restore logic, which broke form-extensions'
  `InlineConstraint` — caught by its own test.
- 2026-09-04 — **P0-06 done.** 113 files modernised, one commit per package (admin-bundle 64,
  block-bundle 15, doctrine-orm-admin-bundle 16, exporter 8, doctrine-extensions 5,
  form-extensions 5, twig-extensions 0) plus adminata's own. Five rules of `PHPUNIT_CODE_QUALITY`
  are skipped because they weaken or break the inherited tests — the first run produced 29 failures
  and 1 error, and each rule is named in `rector.php` with the test that caught it. Rector 2.6 has
  no versioned PHPUnit sets any more, so `PHPUNIT_CODE_QUALITY` accompanies `UP_TO_PHP_84` alone.
- 2026-09-05 — **P0-07 done.** PHPStan level 8 with bleedingEdge is clean over the seven packages
  (src *and* tests) and adminata's own PHP. 267 findings, all fallout from the raised floors, were
  fixed at the cause — the commit message lists them. Two stand out: 188 `->method()->with()` chains
  became `->expects(static::any())->method()->with()`, which is what PHPUnit's own `method()` does
  internally (forward work tracked as B-12); and relaxing `Pool`'s `Item` shape from two alternative
  array shapes to one with optional keys resolved 31 findings at once, because that is what the
  configuration produces and what `GroupMenuProvider` checks for. One baseline entry was added, for
  `InlineConstraint`'s `?? null` guards, which its own test proves are load-bearing. Two more Rector
  rules are skipped because they delete the type information these fixes add.
- 2026-09-05 — **P0-08 done.** `Makefile` (with `make help`), `.yamllint`, `bin/console` on the
  admin bundle's test kernel until P1-09, `upstream/{diff,sync}.sh` and the seven
  `upstream/exclude/*.txt`. `make lint phpstan rector test` is green. Two notes: the exclusion
  lists also cover the files this milestone changed for the MySQL switch and the PHPUnit 13 fixes,
  so upstream syncs will not clobber them; and `xmllint` is not installed on this machine, so
  `lint-xml`/`lint-xliff` print a skip line — P0-09's `lint.yaml` installs `libxml2-utils` so the
  check really runs in CI.
- 2026-09-05 — **P0-09 done.** Seven workflows, `dependabot.yml`, PR and issue templates,
  `bin/check-replace-versions.php` (it cross-checks `replace`, `UPSTREAM.md` and
  `upstream/remotes.txt`, and exits 0) and `versions.json` for the weekly `versions-watch`.
  `test.yaml` runs a MySQL 8.4 service on the port `docker-compose.yml` uses, so
  `phpunit.xml.dist` needs no CI-specific configuration, and it drives Panther through the
  runner's own Firefox rather than a Selenium container, which could not reach the loopback
  Panther serves on. `lint.yaml` installs `libxml2-utils` so the XML checks actually run, and its
  jQuery job greps adminata's sources today and calls `npm run check:jquery` from P1-02 onward.
  The first real CI run happens on the M0 push (P0-MS).
- 2026-09-05 — **P0-10 done.** `options.{skin,use_select2,use_icheck,use_bootlint}` are gone, the
  asset defaults are `app.css` + `fontawesome.css` and `app.js`, the AdminLTE skin append is out of
  `SonataAdminExtension`, and `sonata_admin.theme.{mode,logo_dark,logo_icon}` exists — visible in
  `config:dump-reference`, exposed as `sonata_config.getOption('theme')` and as three container
  parameters so P0-12's runtime can inject the default without depending on `SonataConfiguration`.
  The four skin tests became three theme tests. One Panther test needed a real fix rather than a
  re-baseline: `CollectionTypeTest` clicked the `ins` element **iCheck** injects next to the delete
  checkbox, which no longer exists, so it clicks the checkbox itself.
  Templates still read the removed options; `getOption()` returns null for them and P2-01 rewrites
  those templates.
- 2026-09-05 — **P0-11 done.** `box_class` defaults to an empty string and the dashboard block class
  to `md:col-span-4`. Plan correction in the same commit: the group `class` default `col-md-12` the
  task attributed to `BaseGroupedMapper`/`AbstractAdmin` is actually a Twig `|default()` in
  `base_edit_form_macro.html.twig` and `base_show.html.twig`, so it belongs to P4-06 and P4-07.
- 2026-09-05 — **P0-12 done.** `Sonata\AdminBundle\Twig\ThemeRuntime` and `ThemeExtension` provide
  `sonata_theme()` (the `sonata_theme` cookie when it holds one of the three modes, else the
  configured `sonata_admin.theme.mode`, else `system`) and `sonata_html_dir(locale)` (RTL for ar,
  fa, he, ur, matched on the language subtag), registered in `Resources/config/twig.php` with the
  theme-mode parameter injected. 21 unit tests cover a missing request, a missing cookie, each
  accepted mode, four rejected cookie values, an unknown configured mode, and both helpers.
  Part (e) of P6 turned out to have nothing in PHP: the `SKIN`, `USE_SELECT2` and `USE_ICHECK`
  entries of the `sonata-config` meta are assembled in `standard_layout.html.twig`, so they go with
  P2-01, which rewrites that template. They already render as `null` because the options are gone.
- 2026-09-05 — Note for whoever sees it next: PHPStan's **result cache** intermittently reports a
  bogus `FormErrorIterator` generics error (in `CRUDController` or its test) that disappears after
  `vendor/bin/phpstan clear-result-cache`. CI starts cold, so it only affects local runs.
- 2026-09-05 — **P0-13 done.** `BasePickerType` derives the wire format from
  `datepicker_options.display.components` — `yyyy-MM-dd`, `HH:mm`, `HH:mm:ss`,
  `yyyy-MM-dd'T'HH:mm` or `…:ss` — and refuses an explicit pattern with a `LogicException` shaped
  like the one Symfony's `DateType` throws under `html5`. An `IntlDateFormatter` constant is still
  accepted and ignored, because that is what the picker types default to; a *string* format is the
  thing an application has to delete, and that is exactly the twelve usages PLAN/10 asks
  recomaty-panel to clean up. `JavaScriptFormatConverter`, its service, its test, the
  `localization.format` view variable, `packages/form-extensions/assets/` and the package's
  `Resources/public/` are deleted. Two upstream tests were re-baselined onto the HTML5 formats and
  seven new ones cover the five derived formats and the two exceptions.
  Watch out when running suites after a DI change: the imported test apps compile into
  `/tmp/sonata-*` and a stale container produced 102 "class not found" errors until it was removed.
- 2026-09-05 — **P0-14 done.** 185 files deleted, no file modified beyond the docs toctree. Two
  items of the task were moved rather than done, each because something still depends on them, and
  the task text and PLAN/09 phase 0 item 5 now say so: the prebuilt `app.css`/`app.js` stay until
  P1-02 rebuilds them (deleting them made three ORM Panther tests fail, since they drive the
  inherited UI), and the `MopaBootstrapBundle` extension stays until P4-01 rewrites the form theme
  that reads its `horizontal_*` view variables. The deleted `assets/scss/` is dispositioned
  rule-by-rule in `PLAN/research/gap-css-architecture.md` §5, and `git show` recovers the files if
  P1-05 wants them.
- 2026-09-05 — **P0-15 done.** `tests/Contract/ReplaceTest.php` has four cases: adminata alone,
  adminata next to `idct/sonata-admin-mongodb-bundle`, an assertion that **no real
  `sonata-project` package is installed** alongside it, and the first step of PLAN/10 §1 against
  the real application (`ADMINATA_APP_DIR=/home/bartosz/dev/r3/recomaty-panel-clean`), which
  resolves — its dependencies work with adminata today. `phpunit.xml.dist` excludes the `network`
  group by default, so `make test` stays offline; `--group network` overrides that.
  `symfony/process` joined `require-dev`.
- 2026-09-05 — **Milestone M0 complete.** Definition of done green: `make lint`, `make phpstan`,
  `make rector`, `make test` (2631 tests, 2 skips), `make test-contract` and
  `bin/check-replace-versions.php`. `CHANGELOG.md` records the milestone. `main` pushed to
  `ideaconnect/adminata`. The first CI run found four things the local environment could not:
  `tests/{Unit,Functional}` were empty, so git never created them and PHPUnit refused to start
  (`.gitkeep` added); `make lint-composer` called `composer normalize`, which exists only when the
  plugin is installed and not when CI installs the phar (the target now tries both); PHPStan on
  Symfony 7.4 tripped over `@phpstan-ignore` comments that only match on 8.x, which
  `phpstan-lts.neon.dist` tolerates on that line alone while the highest line keeps reporting
  unmatched ignores; and the console `add()`/`addCommand()` bridge in five test files was dead
  weight — every supported Symfony has `addCommand()` — so it is gone along with `CommandHelper`.
  The second run found three more: PHPUnit 13 validates coverage targets and
  `#[CoversMethod(AbstractAdmin::class, '__construct')]` names a constructor that class does not
  have, which warned once per test (195 of them) and, with `failOnWarning`, failed the coverage
  run; the exporter's ODM source iterator wants a real MongoDB, so `test.yaml` gained a service for
  it; and the `lowest` row installed `doctrine/orm` 3.3 and `symfony/property-info` 6.4, neither of
  which has the API the code calls, so the ORM floor moved to `^3.6` (PLAN/01 P5 and PLAN/07 §3
  updated) and `symfony/property-info` is declared in `require-dev`. The third run showed the same
  shape again — a Symfony component resolving to 6.4 next to 8.x siblings — so every component
  adminata's code paths reach is now constrained to `^7.4 || ^8.0` in `require-dev`
  (`clock`, `http-client`, `password-hasher`, `property-info`, `type-info`, `var-dumper`,
  `var-exporter`), and `composer update --prefer-lowest --dry-run` no longer downgrades any Symfony
  package below 7.4. Two more floors followed from the same row: `doctrine/mongodb-odm` in
  `require-dev` moved to `^2.17` (`setUseNativeLazyObject()` is newer than 2.6, and P0-07 had
  removed the guard as a PHP-version check when it was really an ODM-version one), and
  `TestDatabase` quotes database names itself because `Connection::quoteSingleIdentifier()` arrived
  in doctrine/dbal 4.3. The row's last complaint — 48 tests "did not remove their own exception
  handlers" — is Symfony-minor bookkeeping rather than a floor problem, so that row alone runs with
  `--do-not-fail-on-risky`; every other row keeps `failOnRisky`.
- 2026-09-05 — **All four workflows green on `main`** (run 33930365756 and siblings): Test (five
  matrix rows against MySQL and MongoDB services), Quality assurance (PHPStan and Rector on both
  Symfony lines), Lint and Symfony lint. Milestone M0 is closed.

### Milestone M1

- 2026-09-05 — **P1-02 done.** `package.json` at the PLAN/07 §2 versions, `.nvmrc`, `vite.config.js`
  and the build scripts; `npm run build` is reproducible (identical output across runs) and
  publishes `app.js`, `app.css`, `fontawesome.css`, `entrypoints.json` and `manifest.json` into the
  admin bundle. Three versions in PLAN/07 §2 do not exist and were corrected against the registry:
  `@eslint/js` 10.0.1 (not 10.10.0 — that is eslint's own version), `globals` 17.12.0,
  `stylelint-order` 8.1.1 (7.0.0 does not support Stylelint 17). `eslint-plugin-import` is dropped:
  it supports ESLint 9 at most, and its successor pulls in the TypeScript tooling for rules
  adminata does not need — `no-restricted-imports` is core ESLint and does the jQuery half.
  This is the point where the inherited `app.css`/`app.js` are finally replaced (P0-14 deferred
  it), which makes six ORM browser scenarios unable to interact with the page: they click through
  the Bootstrap interface whose CSS and JavaScript are now gone. They carry `#[Group('legacy-ui')]`,
  excluded like `network`, and M3 and M4 drop the group as they rewrite those templates.
  `npm run css:contract` exists and already refuses Bootstrap selectors, but it is not in
  `assets-check` yet: Tailwind emits `.container` and `.collapse{visibility}` for class names it
  finds in the not-yet-ported templates, so the check can only pass once P1-05 has the component
  layer and M2-M4 have the markup.
- 2026-09-05 — **P1-01 done.** `assets/css/__fixture__/fixture.css` and `bin/tailwind-fixture.mjs`
  assert all eleven rules (T9 is three) against tailwindcss 4.3.3; every one holds, and PLAN/04 §4
  now carries a status column and the evidence. Two things the fixture settled:
  `container` and `collapse` **are** Tailwind v4 utilities, so their presence in the built CSS says
  nothing about Bootstrap — PLAN/04 §8's forbidden list drops them, and the remaining entries are
  matched as whole selectors so an arbitrary variant like `[&>.btn]:rounded-l-none` in a
  not-yet-ported template does not trip the check. And `@import "tailwindcss"` scans the *whole
  repository* by default, which had `app.css` at 68 kB of utilities generated from the plan and the
  build scripts; `source(none)` with the explicit `@source` list of PLAN/04 §2 brings it to 8.8 kB.
  `css:contract` is back in `assets-check`, and `make fixture` runs the assertions.
- 2026-09-05 — **P1-03 done.** `eslint.config.js` (flat, `@eslint/js` recommended + prettier, with
  `no-restricted-globals` for `$`/`jQuery`/`Alpine` and `no-restricted-imports` for `jquery` and
  `jquery-ui`), `prettier.config.js` + `.prettierignore` (the forked packages keep upstream's
  formatting so upstream diffs still apply), `stylelint.config.js` with Tailwind v4's at-rules.
  All four scripts pass, and the jQuery gate was proved by installing jQuery: it fails with
  `jquery@3.7.1` and passes again once removed. There is no header-comment plugin: the one the plan
  named does not support ESLint 10, so the header rule stays with php-cs-fixer for PHP and is a
  convention for JavaScript.
- 2026-09-05 — **P1-04 done.** `theme.css` (TailAdmin v2.3.0's tokens verbatim, minus its
  `--font-*`/`--breakpoint-*` resets and its Google Fonts import, plus adminata's radius tokens and
  the semantic z-index ladder), `base.css` (border-colour shim including a dark variant,
  `color-scheme` — which is what makes the native date and time popups follow the theme — the nine
  density variables and a compact override, body recipe), `fonts.css` and `adminata.css`, the
  aggregate an application imports when it compiles Tailwind itself.
  `fontawesome.css` composes Font Awesome's core with `solid.css` and `regular.css` rather than
  importing `all.css`: that also carries Brands and the v4/v5 shims, whose `@font-face` rules point
  at files adminata does not ship. The built stylesheet references exactly `./fonts/fa-solid-900.woff2`
  and `./fonts/fa-regular-400.woff2`, no brands, no shims, no ttf/eot and no Google Fonts, and Vite
  emits the two Outfit faces the same way. Vite's `base` is `./` so those URLs survive being
  published anywhere. Sizes: app.js 44.8 kB, app.css 15.2 kB, fontawesome.css 70.0 kB, all inside
  budget. Stylelint exempts the copied token block from the cosmetic rules, so it stays diffable
  against the upstream template.
- 2026-09-05 — **P1-05 done.** Twenty component files under `assets/css/components/` — 95
  `@utility` primitives plus the descendant rules that style what the PHP layer emits
  (`table.sonata-ba-list`, `sonata-ba-list-row-selected`, `ul.pagination > li.active`, the show
  page's `<th>/<td>` pairs, an application's raw `<li><a>` user-block markup). No Bootstrap or
  AdminLTE class is defined anywhere; the recipes come from TailAdmin v2.3.0 and the research
  catalogue. `bin/build-css-safelist.mjs` generates `safelist.css` and `contract.json` from those
  files, so a component cannot disappear because the one template using it is not ported yet, and
  `npm run css:contract` checks all 101 selectors against the built stylesheet — it passes.
  `npm run build` regenerates the safelist first and `assets-check` diffs it, so the two cannot
  drift. app.css is 46.3 kB against a 120 kB budget.
- 2026-09-05 — **P1-06 done.** The nine inherited controllers, `core/` and the three images moved
  out of `packages/admin-bundle/assets/` into `assets/`, which is now deleted; `registry.js`
  registers all nine explicitly and `app.js` starts the one application as
  `window.sonataApplication`. Ten Vitest files, 45 tests, each naming the template its markup
  mirrors. Three changes to the controllers themselves: the single jQuery call in `sonata-edit`
  became a `sonata-tabs:show` event (a no-op until that controller exists), `Config.param()`
  returns null on a page without the meta tag instead of throwing, and the relative imports got
  their `.js` extensions so the modules are valid ESM outside a bundler.
  **`qs` is gone** — owner decision 10 in PLAN/11, taken because the bundle was 95.6 kB of a 100 kB
  budget with 9 of 17 controllers, and `qs` was 41 kB of it for a single `stringify` call.
  `buildQueryString()` in `core/utils.js` replaces it and produces byte-identical output for every
  shape the filter controller passes, checked against the real `qs` before removing it. app.js is
  54.3 kB. `bin/build-js-contract.mjs` records the identifiers, targets, values, classes and
  outlets, and `contract.test.js` checks the registry and the built bundle against it — including
  that no jQuery reaches the output.
- 2026-09-05 — **P1-07 done.** `frontend.yaml` on Node 24 and 26: `npm ci`, the three linters, the
  jQuery gate, the Tailwind fixture, Vitest, a build, a diff of the committed output against it,
  the CSS contract and the size budgets. Its first run found two things: Stimulus swallows a
  controller error into `console.error`, where Node's inspector then trips over jsdom's DOM objects
  — the helper now rethrows instead, which immediately exposed a **false positive**, a filter test
  whose fixture was missing the `submitter` target the controller requires; and the jQuery grep has
  to be scoped to `assets/` excluding the tests, because the inherited templates still reference
  jQuery until M2 to M4 rewrite them.
- 2026-09-04 — **P0-03 done.** `README.md`, `LICENSE`, `NOTICE`, `AGENTS.md`, `CONTRIBUTING.md`,
  `CHANGELOG.md`, `CHANGELOG-sonata.md`, `.editorconfig`, `.gitattributes`, `.symfony.bundle.yaml`.
  `MIGRATION.md` and `UPGRADE-1.0.md` were added as placeholders pointing at PLAN/10 so the README
  links resolve; P5-12 replaces them with the executed checklist. `.gitattributes` deliberately
  keeps `assets/` in the dist archive (PLAN/07 §4) and export-ignores tests, docs, PLAN, CI and the
  build tooling.

- 2026-09-05 — **P1-08 done.** Five contract tests, 162 cases, on a shared `ContractTestCase`
  (`root()`, `directories()`) that reads the packages off disk rather than through a kernel, so a
  broken container cannot make a frozen-interface check pass by not running.
  `deferred-templates.txt` lists the 36 templates 1.0 inherits unported, each now carrying
  `{# adminata: not yet ported #}`; `DeferredTemplateTest` holds the list, the markers and the disk
  in agreement, so porting one and forgetting to delist it fails. `TemplatePathTest` resolves every
  registry default, every `@Sonata*/…` string under `packages/*/src` and the MongoDB fork's
  hard-coded paths. `HookContractTest` scans template *sources* (not rendered output — a hook in a
  branch the demo never takes is still contract) for the PLAN/02 §8 hooks, and writing it corrected
  four of them in the plan: upstream emits `view_link`, not `show_link`/`history_link`;
  `sonata-ba-view-title` belongs to the deferred `preview.html.twig`; `sidebar-menu` lives in
  `Menu/sonata_menu.html.twig`; `sonata-action-element` in `Button/*`; and the pager templates are
  `Pager/*`, not `CRUD/Pager/*`. One upstream default is dangling — `templates.outer_list_rows_tree`
  names `CRUD/list_outer_rows_tree.html.twig`, absent from 4.43.0 — and is excluded as `NOT_SHIPPED`
  with that reason.

  Two deviations from the task text. `ConfigContractTest` dumps the `Configuration` classes through
  `YamlReferenceDumper` instead of `bin/console config:dump-reference`: the admin test kernel
  registers neither doctrine-extensions, the ORM admin nor the exporter, so the console resolves
  only four of the seven roots. And it freezes **six** roots, not seven — doctrine-extensions
  declares no `Configuration` class at all (`SonataDoctrineExtension::load()` only loads service
  files and there is no `getConfiguration()`), so `sonata_doctrine` has no tree to capture. The
  baseline is the post-P0-10 tree rather than the pristine import, which is what an application
  actually configures against; the PLAN/02 §3 differences are asserted positively instead of
  allowlisted — two extra cases check that `skin`/`use_select2`/`use_icheck`/`use_bootlint` are gone
  and that the `theme` node is present. The captures are byte-for-byte dumper output, whose column
  padding yamllint rejects, so `tests/Contract/config-reference/` is in the `.yamllint` ignore list;
  reformatting them would make the test compare against something the dumper cannot produce.

  PHPStan needed one config change. `FormErrorIterator` declares its own name inside its own
  template bound (`@template T of FormError|FormErrorIterator`), and PHPStan resolves that
  recursion inconsistently: at `CRUDController::handleXmlHttpRequestErrorResponse()` `dumpType`
  reports exactly the `FormErrorIterator<FormError>` that
  `FormErrorIteratorToConstraintViolationList::transform()` asks for, and the same analysis then
  rejects it against that bound rendered as
  `iterable<FormError>&FormErrorIterator`. It fires every time single-process and intermittently
  under parallel workers, which is what made it look like a result-cache artifact. The class joins
  the five Symfony form classes already in `skipCheckGenericClasses` — a statement about a vendor
  class's generics, not a baseline entry or an ignore comment.

  Definition of done green: `make lint`, `make phpstan`, `make rector`, `make test`
  (**2787 tests, 2 skips**), `make test-contract` (162 + 4), `make lint-js`, `make test-js`,
  `make assets-check`.

- 2026-09-05 — **P1-09 done.** `tests/App` is adminata's own demo application: `Kernel` registering
  the seven bundles plus DoctrineBundle, FixturesBundle, KnpMenu and SecurityBundle;
  `config/{packages,packages_test,sonata,routes,services}`; `Category`, `Product` and
  `ProductVariant` on MySQL; `CategoryAdmin` and `ProductAdmin` covering string, integer, datetime,
  boolean, enum and many-to-one in the list, the show and the filters, plus one native Symfony
  `CollectionType` with `allow_add`/`allow_delete`; `SidebarSectionHeaderListener` injecting the
  `sidebar-section-header` items recomaty-panel's own subscriber adds; deterministic fixtures
  (4 categories, 42 products, 84 variants, a fixed epoch — P1-10 diffs screenshots of these pages,
  so nothing may depend on the clock). Security is in-memory `http_basic` with a role hierarchy;
  there is no user entity and no SonataUserBundle (owner directive 6). `bin/console` and `make demo`
  now boot this kernel, `make demo-db` creates the database and loads the fixtures, and
  `tests/Functional/DemoSmokeTest.php` drives 13 cases through BrowserKit — every page, the raw
  `<i>` group icons rendering unescaped, the injected sidebar headers, a real create through the
  form and the enum round trip.

  Four things the first run found. **dama/doctrine-test-bundle keys its static connection by the
  connection *name*, not by its parameters** — every test application here calls its connection
  `default`, so the demo was handed whichever database the suite that ran first had opened
  (`adminata_orm_test`) and every query looked for a table that was not there; the demo now
  declares `dama_doctrine_test.connection_keys: {default: adminata_demo}`. `framework.test` has to
  be on for `createClient()`, so the test-only settings moved into `config/packages_test.yaml`,
  which `make demo` never loads. doctrine-bundle 3 removed `dbal.use_savepoints`,
  `orm.auto_generate_proxy_classes` and `orm.report_fields_where_declared` and deprecated
  `orm.enable_native_lazy_objects`. And Symfony 8 writes an IDE helper, `config/reference.php`,
  into the kernel's config directory on every debug build: gitignored, and excluded from the
  php-cs-fixer finder, which walks the filesystem rather than the index.

  Two decisions worth recording. The entities carry `setId()` even though nothing calls it: PHPStan
  at level 8 reports `?int $id = null` as "never assigned int" without `phpstan/phpstan-doctrine`,
  and the inherited `Tests\App\Entity\Base` answers it the same way. Installing that extension
  was tried and backed out — it finds two real mapping mismatches in the demo, but also 14 findings
  in inherited package code that would have to live in baselines forever; it belongs in a task of
  its own, filed as **B-13**. And a shared `ConsoleRunner` now carries the error/exception-handler
  bookkeeping that `OrmDatabaseExtension` had inline, so the new `DemoDatabaseExtension` — which
  creates `adminata_demo`, its schema and its fixtures once per run — gets it too.

  Definition of done green: `make lint`, `make phpstan`, `make rector`, `make test`
  (**2800 tests, 2 skips**), `make test-contract`, `make lint-js`, `make test-js`,
  `make assets-check`; `make demo` serves `/admin/dashboard`.

- 2026-09-05 — **P1-10 done.** `playwright.config.js`, three specs and a runner. Nine projects —
  Chromium, Firefox and WebKit × 375/768/1280 — over the demo application, authenticated with the
  same in-memory credentials, theme set through the `sonata_theme` cookie so one run can compare
  light against dark. `dashboard.spec.js` takes a full-page screenshot per page, viewport and
  theme; `accessibility.spec.js` runs axe against WCAG 2.1 A and AA; `markup.spec.js` runs
  html-validate over the DOM the browser built rather than the Twig source, because that is where
  a tag closed in one branch and left open in another shows up. **99 tests green.**

  Four decisions. **Screenshots are Chromium-only.** PLAN/08 §3 asks for a baseline per page,
  viewport and theme — eighteen images, 2.1 MB — and capturing the same eighteen in three engines
  would triple what the repository carries and triple it again on every rewrite from M2 to M4, to
  catch differences that are antialiasing far more often than they are bugs; the accessibility and
  markup suites do run in all three, which is where an engine actually disagrees about the DOM.
  **The browsers run in a container.** Screenshots are pixels, so `bin/visual.sh` serves the demo
  with the host's PHP and drives it from `mcr.microsoft.com/playwright:v1.63.0-noble`; `make
  test-visual` and the `visual` workflow are then literally the same command, and changing the
  image means regenerating every baseline. `ADMINATA_PLAYWRIGHT_LOCAL=1` skips the container for
  writing a spec. **`.js`, not `.ts`** (the plan wrote `playwright.config.ts`): nothing else in the
  repository is TypeScript, and a `.ts` file would sit outside `eslint.config.js` and prettier.

  The fourth is the one that shapes M2 to M4. The inherited Bootstrap interface **does not pass**
  either check — the dashboard alone is missing `lang`, pins the viewport scale, has unnamed
  buttons and links, and puts non-`<li>` children in a `<ul>`; the product list adds `label`,
  `select-name`, `listitem` and `aria-required-children`. Skipping the suites until M2 would mean
  noticing none of that until the end, so every finding is written down in
  `tests/Visual/support/findings.json` and asserted **exactly**: a new violation fails, and fixing
  one also fails until it is struck from the file in the same commit. That is 6 accessibility
  entries and 3 markup entries today, and it is meant to end M4 as `{}`.
  `make visual-findings` regenerates it.

  Three smaller things. `html-validate` 11.13 declares an optional peer on vitest 3 or 4 and the
  repository is on 5, for a matcher integration nothing here uses — resolved with an npm
  `overrides` entry rather than `--legacy-peer-deps`. The validator is constructed from
  `.htmlvalidate.json` by hand because `validateString()` resolves configuration relative to the
  filename it is given, and a document that came from a browser has no file on disk. And Prettier
  writes `{ a: b }` in YAML where yamllint's default forbids the spaces, so `.yamllint` now allows
  either and Prettier stays the only formatter; the generated `config-reference/` captures are
  ignored by both.

  Definition of done green: `make lint`, `make phpstan`, `make rector`, `make test`
  (**2800 tests, 2 skips**), `make test-contract`, `make lint-js`, `make test-js`,
  `make assets-check`, `make test-visual` (**99 tests**).

- 2026-09-05 — **P1-11 done.** `tests/Functional/BasePantherTestCase.php` drives a real browser
  against the demo, with the fork's `PANTHER_SELENIUM_HOST` switch and the `PANTHER_FIREFOX_PORT`
  escape hatch; `docker-compose.yml` gained a `selenium/standalone-firefox` with
  `host.docker.internal` mapped to the host gateway; `DashboardPantherTest` is the first test and
  `make test-functional` runs the suite. **15 tests green** (13 BrowserKit, 2 Panther).

  Four things had to be solved to get there, none of them obvious from the plan.

  **Panther manages one web server per process, and the ORM suite has already claimed it.**
  `startWebServer()` returns as soon as it finds a manager — before it looks at
  `external_base_uri` — so the option is silently ignored and Panther's base URI still points at
  `packages/doctrine-orm-admin-bundle/tests/App`. The symptom was an empty product list and a
  missing console recorder, neither of which says anything about the cause. adminata's browser
  tests therefore serve the demo themselves through a new `Adminata\Tests\Support\DemoServer`
  and request **absolute** URLs. `DemoServer` also refuses to start on a port that already
  answers: Panther's own default is 9080, so the demo took 9088.

  **geckodriver implements no log endpoint** — `manage()->getLog('browser')` is a Chrome extension
  to WebDriver — so "every test asserts an empty browser console" (PLAN/08 §3) needs the page to
  keep the record. `BrowserConsoleRecorderListener`, registered only in the test environment, puts
  a recorder first in the `<head>` of every HTML response from a `kernel.response` listener rather
  than from a template, so the M2 to M4 rewrites cannot lose it. It has to treat an **absent**
  `Content-Type` as HTML: `Response::prepare()` fills that header in after the event.

  **Firefox accepts a top-level navigation to a URL carrying credentials**, which is what spares
  the demo a login form; the base URI is `http://admin:admin@host:9088`. And a Selenium in a
  container reaches the host through the gateway, so `DemoServer` dials `host.docker.internal`
  whenever `PANTHER_SELENIUM_HOST` is set — `ADMINATA_DEMO_BROWSER_HOST` overrides it.

  The first run also caught exactly what it is for: the product list logs
  `ReferenceError: jQuery is not defined`, because the inherited `CRUD/list.html.twig` still emits
  Sonata's inline jQuery and adminata ships none (owner directive 2). The test asserts that
  message **exactly** rather than skipping, so M3's rewrite will fail it and the fix is to replace
  it with `assertConsoleIsEmpty()`.

  One thing left open, filed as **B-14**: `skipCheckGenericClasses` removed the deterministic
  `FormErrorIterator` failure of P1-08, but one parallel PHPStan run since has still reported it.

  Definition of done green: `make lint`, `make phpstan`, `make rector`, `make test`
  (**2802 tests, 2 skips**), `make test-contract`, `make test-functional`, `make lint-js`,
  `make test-js`, `make assets-check`.

- 2026-09-05 — **P1-12 done.** `mongo-compat.yaml` has two jobs. The blocking one checks out
  `ideaconnect/sonata-admin-mongodb-bundle@5.x`, points it at this checkout with a `path`
  repository, `composer require idct/adminata:@dev`, validates the manifest, asserts that the
  replaced packages really came from adminata (`vendor/idct/adminata` present,
  `vendor/sonata-project/admin-bundle` absent) and runs its unit suite. Rehearsed locally against a
  clone: **342 tests, 1031 assertions, green**, and `vendor/sonata-project/` does not exist at all —
  every one of the fork's four Sonata requirements is satisfied by the `replace` block.

  `composer validate` runs **without** `--strict`, because a path install pins `@dev` and an
  unbound constraint is the one thing strict mode objects to; the fork's own CI validates its
  manifest properly.

  The nightly browser job is `continue-on-error: true`, and rehearsing it showed exactly why:
  **5 of 9 pass, 4 error** with `ElementNotInteractableException: could not be scrolled into view`.
  That is P1-02 landing — the Bootstrap CSS is gone, so an element Bootstrap used to position is
  no longer where a click can reach it — on scenarios that click through association modals
  adminata has not rewritten. M4 is where that job is expected to come back; until then it is a
  report, not a gate.

  Definition of done green: `make lint`, `make phpstan`, `make rector`, `make test`
  (**2802 tests, 2 skips**), `make test-contract`, `make lint-js`, `make test-js`,
  `make assets-check`.

- 2026-09-05 — **Milestone M1 complete.** Twelve of the thirteen M1 tasks are done; P1-13 depends
  on P2-MS by design and stays open. Definition of done green: `make lint`, `make phpstan`,
  `make rector`, `make test` (**2802 tests, 2 skips**), `make test-contract` (162 + 4),
  `make test-functional` (15), `make test-visual` (**99**), `make lint-js`, `make test-js`,
  `make assets-check`. `CHANGELOG.md` records the milestone. `main` pushed to
  `ideaconnect/adminata`.

  What M1 actually leaves behind is a set of gates, and the honest summary is that **the interface
  is currently worse than upstream's and every bit of that is measured**. P1-02 removed the
  Bootstrap CSS and JavaScript; the templates that used them are still Sonata's. So the demo
  renders unstyled, six axe rules and nine to fifteen html-validate rules fire per page, the
  product list throws `ReferenceError: jQuery is not defined`, six ORM browser tests carry
  `#[Group('legacy-ui')]`, and four of the MongoDB fork's nine browser scenarios cannot click what
  they need to. None of that is a surprise and none of it is silent: it is in
  `tests/Contract/deferred-templates.txt` (36 templates), `tests/Visual/support/findings.json`
  (6 accessibility and 3 markup entries), an exact assertion in `DashboardPantherTest`, an excluded
  PHPUnit group, and a `continue-on-error` job. Every one of those is written so that **fixing it
  fails the build** until the record is updated in the same commit, which is what turns M2 to M4
  from a rewrite into a checklist that empties itself.
