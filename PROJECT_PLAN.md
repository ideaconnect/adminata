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

- [ ] **P0-01 · Import the seven upstream packages** · L · depends: —
  - Read: PLAN/07 §2 (tags), §4 (layout), §10 (remotes); PLAN/01 P1, P2, P9.
  - Do: create `upstream/remotes.txt` (name → GitHub URL for SonataAdminBundle, SonataBlockBundle,
    sonata-doctrine-extensions, SonataDoctrineORMAdminBundle, exporter, form-extensions,
    twig-extensions); `git remote add upstream-<name>`; `git fetch --tags`;
    `git subtree add --prefix=packages/<name> upstream-<name> <tag>` at 4.43.0, 5.4.0, 2.6.0,
    4.21.0, 3.4.0, 2.7.0, 2.6.0. Record tag + commit per package in `UPSTREAM.md`. Measure
    `du -sh .git`; if above 300 MB, re-import the small packages with `--squash` and note it.
  - Deliver: `packages/<name>/` × 7, `upstream/remotes.txt`, `UPSTREAM.md`.
  - Accept: `ls -d packages/*/src | wc -l` prints 7; `git log --oneline --merges | grep -c "packages/"` ≥ 7;
    `jq -r .name packages/*/composer.json` lists the seven upstream names.

- [ ] **P0-02 · Root `composer.json`** · M · depends: P0-01
  - Read: PLAN/07 §3; PLAN/01 P3–P5; PLAN/02 §1.
  - Do: compute the union of the seven `packages/*/composer.json` `require` lists (drop
    `sonata-project/*`, raise floors to `php ^8.4`, `symfony/* ^7.4 || ^8.0`, `twig ^3.28`,
    `doctrine/orm ^3.3`, `doctrine/doctrine-bundle ^3.0`, `symfony/stimulus-bundle ^3.4`); write
    `replace` × 7 at the exact versions; `autoload`/`autoload-dev` PSR-4 × 7 plus `Adminata\Tests\`
    → `tests/`; `require-dev` at PLAN/07 §2 versions; scripts; `suggest`; delete
    `packages/*/composer.json`. Run `composer install`, `composer normalize`.
  - Deliver: `composer.json`.
  - Accept: `composer validate --strict` ok; `composer install` ok; a `php -r` snippet instantiates all
    seven bundle classes (PLAN/02 §1 table); `composer show --self | grep -A7 replaces` lists seven entries.

- [ ] **P0-03 · Repository documents** · S · depends: P0-02
  - Read: PLAN/07 §5, §8, §9; PLAN/00 "What drop-in means".
  - Do: `README.md` (hard-fork banner, seven packages, what is/is not preserved, install), `LICENSE`
    (MIT, three copyright lines), `NOTICE`, `AGENTS.md` (conventions + the ground rules above +
    package layout + library policy), `CONTRIBUTING.md` (semver contract), `CHANGELOG.md`
    (Unreleased), `CHANGELOG-sonata.md` (per-package upstream changelog pointers), `.editorconfig`,
    `.gitattributes` (`export-ignore` for `/tests`, `/packages/*/tests`, `/packages/*/docs`, `/PLAN`,
    `/.github`, `/upstream`), `.symfony.bundle.yaml`.
  - Accept: files exist; every relative link in `README.md` resolves; `composer validate --strict` still ok.

- [ ] **P0-04 · PHPUnit 13 across the seven suites** · L · depends: P0-02
  - Read: PLAN/07 §6; PLAN/08 §1.
  - Do: root `phpunit.xml.dist` with test suites `admin`, `block`, `doctrine`, `orm`, `exporter`,
    `form`, `twig` (→ `packages/<name>/tests`) and `adminata-unit`, `adminata-functional`,
    `adminata-contract` (→ `tests/{Unit,Functional,Contract}`); merge the packages' bootstraps into
    `tests/bootstrap.php`; delete per-package `phpunit.xml.dist`; `KERNEL_CLASS` for the admin
    functional tests; fix PHPUnit 13 API breaks (attributes, deprecations) — mechanical changes are
    allowed in tests; `ext-pdo_sqlite` for the ORM suite.
  - Accept: `vendor/bin/phpunit` green for every suite on the local PHP 8.5; no skipped suite; no
    `KNOWN_FAILURES` file needed.

- [ ] **P0-05 · PHP-CS-Fixer 3.95** · M · depends: P0-04
  - Read: PLAN/07 §6; `MDB/.php-cs-fixer.dist.php`.
  - Do: `.php-cs-fixer.dist.php` (fork rule set, Sonata header kept, paths `packages/*/src`,
    `packages/*/tests`, `tests`); run `fix`; **one commit per package** titled `P0-05: apply
    PHP-CS-Fixer to <package>`.
  - Accept: `vendor/bin/php-cs-fixer check` clean; `vendor/bin/phpunit` still green.

- [ ] **P0-06 · Rector 2.6** · M · depends: P0-05
  - Read: PLAN/07 §6; `MDB/rector.php`.
  - Do: `rector.php` (`UP_TO_PHP_84`, PHPUnit 13 and code-quality sets, fork's skip list, paths as
    above); run; **one commit per package**; re-run CS-Fixer afterwards.
  - Accept: `vendor/bin/rector process --dry-run` clean; phpunit green; cs check clean.

- [ ] **P0-07 · PHPStan 2.2 level 8** · L · depends: P0-06
  - Read: PLAN/07 §6; `MDB/phpstan.neon.dist`.
  - Do: `phpstan.neon.dist` (level 8, bleedingEdge, strict rules, symfony + phpunit extensions,
    paths `packages/*/src`, `tests`); import each package's baseline as `phpstan/baseline-<name>.neon`;
    trim entries that are cheap to fix in tests (never by ignoring); regenerate.
  - Accept: `vendor/bin/phpstan analyse --memory-limit=1G` clean; `grep -r "@phpstan-ignore" tests` empty.

- [ ] **P0-08 · Makefile, lint tooling, `bin/console`** · M · depends: P0-07
  - Read: PLAN/07 §6, §7.
  - Do: `Makefile` targets `lint` (cs, composer-normalize, yamllint, xmllint over xml/xliff,
    `lint:twig packages tests`, `lint:container`, `lint:xliff`, `lint:yaml`), `cs-fix`, `phpstan`,
    `rector`, `rector-fix`, `test`, `test-unit`, `test-functional`, `test-contract`, `demo`,
    `upstream-diff PKG= FROM= TO=`, `upstream-sync PKG= TO=`; `.yamllint`; `bin/console` booting
    `Sonata\AdminBundle\Tests\App\AppKernel` (until P1-09); `upstream/diff.sh`, `upstream/sync.sh`,
    `upstream/exclude/<name>.txt` per PLAN/07 §10.
  - Accept: `make lint phpstan rector test` green; `bin/console about` works;
    `upstream/diff.sh twig-extensions 2.5.0 2.6.0` prints a diff stat.

- [ ] **P0-09 · GitHub Actions and Dependabot** · M · depends: P0-08
  - Read: PLAN/07 §7.
  - Do: `.github/workflows/{test,qa,lint,symfony-lint,stale,upstream-watch,versions-watch}.yaml`,
    `.github/dependabot.yml`; `bin/check-replace-versions.php` (compares `replace` with
    `UPSTREAM.md`); `versions.json` consumed by `versions-watch`; issue/PR templates.
  - Accept: `yamllint .github` clean; `php bin/check-replace-versions.php` exits 0; first CI run
    green after the M0 push (P0-MS).

- [ ] **P0-10 · PHP change (a): admin configuration and theme node** · M · depends: P0-04
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

- [ ] **P0-11 · PHP change (b): grid and box defaults** · S · depends: P0-04
  - Read: PLAN/01 P6 (b).
  - Do: `BaseGroupedMapper` / `AbstractAdmin`: group `class` default `col-md-12` → `col-span-12`,
    `box_class` default `box box-primary` → `''`; `Configuration` dashboard block `class` default
    `col-md-4` → `md:col-span-4`; update `FormMapperTest`, `ShowMapperTest`, `ConfigurationTest`,
    `AbstractAdminTest` and DI tests.
  - Accept: `grep -rn "col-md-\|box box-primary" packages/*/src --include=*.php` empty; suites green.

- [ ] **P0-12 · PHP change (d)/(e): theme cookie runtime and html helpers** · M · depends: P0-10
  - Read: PLAN/01 P6 (d,e); PLAN/04 §3; PLAN/06 §6.
  - Do: `Sonata\AdminBundle\Twig\ThemeRuntime` (+ extension registration in `Resources/config/twig.php`)
    with `sonata_theme()` (cookie `sonata_theme` validated against `light|dark|system`, fallback
    `sonata_admin.theme.mode`) and `sonata_html_dir(locale)` (RTL map `ar, fa, he, ur`); drop the
    `SKIN`, `USE_SELECT2`, `USE_ICHECK` entries from wherever the `sonata-config` meta values are
    assembled in PHP; unit tests.
  - Accept: admin suite green; `ThemeRuntimeTest` covers missing/invalid/valid cookie and both helpers.

- [ ] **P0-13 · PHP change (f): form-extensions native date formats** · M · depends: P0-04
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

- [ ] **P0-14 · Cleanups of upstream tooling and dead assets** · S · depends: P0-04
  - Read: PLAN/03 §F; PLAN/09 phase 0 item 5.
  - Do: delete twig-extensions `src/Bridge/Symfony/Resources/public/` (and any DI reference), the
    admin-bundle `MopaBootstrapBundle` switch, Symfony 6.4 guards, obsolete cookbook recipes
    (`recipe_bootlint`, `recipe_icheck`, `recipe_jquery_ui`, `recipe_select2`), admin-bundle
    `package.json`, `webpack.config.js`, `.eslintrc*`, `.stylelintrc*`, `.prettierrc*`,
    `.browserslistrc`, `assets/scss/`, and the prebuilt contents of
    `packages/admin-bundle/src/Resources/public/` except `images/{ajax-loader.gif,default_mosaic_image.png,logo_title.png}`
    (keep `assets/js/controllers` and `assets/images` for P1-06).
  - Accept: suites green; `git status --short | grep -v '^ D'` empty apart from intended edits.

- [ ] **P0-15 · `ReplaceTest` and migration dry run** · M · depends: P0-02, P0-13
  - Read: PLAN/02 §13; PLAN/07 §1; PLAN/10 §1 step 1.
  - Do: `tests/Contract/ReplaceTest.php` (`#[Group('network')]`): temp project with a `path`
    repository to this checkout requiring `idct/adminata @dev`, `idct/sonata-admin-mongodb-bundle ^5`,
    `doctrine/doctrine-bundle ^3`, `doctrine/mongodb-odm-bundle ^5`, `symfony/framework-bundle ^8.1`;
    `composer update --dry-run --no-plugins --no-scripts` must exit 0; second case copies
    `APP/composer.json` (path from env `ADMINATA_APP_DIR`, skipped when unset) and runs the
    PLAN/10 §1 step-1 commands in dry-run mode.
  - Accept: `vendor/bin/phpunit --group network` green locally.

- [ ] **P0-MS · Milestone M0 push** · S · depends: P0-03, P0-09, P0-11, P0-12, P0-14, P0-15
  - Do: full definition of done; `CHANGELOG.md` Unreleased entries; merge to `main`; `git push origin main`;
    watch the first CI run and fix red jobs.
  - Accept: `main` on GitHub at the merge commit; every workflow green; status log updated.

---

## Milestone M1 — Foundations (PLAN/09 phase 1)

- [ ] **P1-01 · Tailwind 4.3 semantics fixture (T1–T11)** · M · depends: P0-MS
  - Read: PLAN/04 §4.
  - Do: `assets/css/__fixture__/{fixture.css,fixture.html}` and `bin/tailwind-fixture.mjs` compiling
    with the pinned `tailwindcss` (`@tailwindcss/node` or `@tailwindcss/cli`) and asserting each
    rule with a regex on the output; `npm run fixture`; update the status column of PLAN/04 §4; if an
    assumption fails, adjust the affected design in PLAN/04 and record it in PLAN/11 §3.
  - Accept: `npm run fixture` exits 0 printing eleven `PASS` lines.

- [ ] **P1-02 · `package.json`, Vite 8 build, Makefile asset targets** · M · depends: P0-MS
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

- [ ] **P1-03 · ESLint 10, Prettier 3.9, Stylelint 17, jQuery gate** · S · depends: P1-02
  - Read: PLAN/07 §6; PLAN/01 J13.
  - Do: `eslint.config.js` (flat; `@eslint/js` recommended, prettier, header plugin with the combined
    header, import plugin; `no-restricted-globals: ['$','jQuery','Alpine']`,
    `no-restricted-imports: ['jquery']`), `prettier.config.js` (+ `prettier-plugin-tailwindcss`),
    `stylelint.config.js` (Tailwind at-rules, `stylelint-order`), scripts `lint:js`, `lint:css`,
    `lint:prettier`, `check:jquery` (fails when `npm ls jquery --all` finds a package).
  - Accept: all four scripts pass; adding `jquery` to `package.json` in a scratch branch makes `check:jquery` fail.

- [ ] **P1-04 · Tokens, base, fonts, Font Awesome 7, size budgets** · M · depends: P1-01, P1-02
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

- [ ] **P1-05 · `.adm-*` component layer, safelist, CSS contract** · L · depends: P1-04
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

- [ ] **P1-06 · JS core, inherited controllers, Vitest, contract snapshot** · L · depends: P1-03
  - Read: PLAN/05 §1, §2, §7, §9; PLAN/02 §9.
  - Do: move `packages/admin-bundle/assets/js/controllers/*_controller.js` and `core/*` to
    `assets/js/`, images to `assets/images/`, then delete `packages/admin-bundle/assets/`;
    `assets/js/app.js` (start `Application`, `window.sonataApplication`, remove `html.no-js`);
    `registry.js` (explicit nine); `sonata-edit`: replace the jQuery tab line with
    `this.dispatch('show', {prefix: 'sonata-tabs'})`; `Config.param` null-tolerant;
    `vitest.config.js` (jsdom); `tests/Unit/Fixture/JsFixtureDumperTest.php` rendering the relevant
    Twig templates through the stub kernel into `tests/fixtures/js/*.html`; a Vitest test per
    inherited controller; `assets/js/__contract__/controllers.json` + snapshot test introspecting
    the built `app.js`.
  - Accept: `npm run test` green; `grep -rn "jQuery\|\$(" assets/js` empty; built `app.js` registers
    nine identifiers (snapshot test).

- [ ] **P1-07 · `frontend.yaml` workflow** · S · depends: P1-05, P1-06
  - Do: Node 24 and 26 matrix running `npm ci`, `lint:js`, `lint:css`, `lint:prettier`, `check:jquery`,
    `fixture`, `test`, `build`, `git diff --exit-code -- packages/admin-bundle/src/Resources/public`,
    `css:contract`, `size`.
  - Accept: green on the M1 push.

- [ ] **P1-08 · Contract tests and deferred-template markers** · M · depends: P0-MS
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

- [ ] **P1-09 · Demo ORM application** · L · depends: P0-MS
  - Read: PLAN/08 §2; appendix C §2.
  - Do: `tests/App/Kernel.php` (Framework, Twig, Security with in-memory users, Doctrine sqlite in
    `var/`, FixturesBundle, KnpMenu, the seven Sonata bundles), `tests/App/config/*`
    (`sonata_admin` with three groups, raw `<i>` icons, `theme`, `use_stickyforms`,
    `lock_protection`; `sonata_block`; `sonata_form`), an event subscriber injecting a
    `sidebar-section-header` item, first entities and admins (`Category`, `Product`: string, int,
    datetime, boolean, enum, many-to-one; filters; a native collection), fixtures; `bin/console`
    switched to this kernel; `make demo` (`php -S 127.0.0.1:8000 -t tests/App/public` or Symfony CLI)
    with `assets:install --symlink`; `tests/Functional/DemoSmokeTest.php` (BrowserKit: dashboard,
    list, create, edit → 200).
  - Accept: `make demo` serves `/admin/dashboard`; `vendor/bin/phpunit --testsuite adminata-functional` green.

- [ ] **P1-10 · Playwright, axe, html-validate harness** · M · depends: P1-09
  - Read: PLAN/08 §3, §8.
  - Do: `playwright.config.ts` (Chromium/Firefox/WebKit; viewports 375/768/1280; themes via the
    `sonata_theme` cookie; web server = demo app), `tests/Visual/dashboard.spec.ts` skeleton with
    `toHaveScreenshot`, `@axe-core/playwright` helper, `html-validate` config and run over captured
    DOM; `visual.yaml` workflow; `make test-visual`.
  - Accept: `npx playwright test` passes and writes baselines under `tests/Visual/__snapshots__` (committed).

- [ ] **P1-11 · Panther harness** · M · depends: P1-09
  - Read: PLAN/08 §3; `MDB/tests/Functional/` base classes; `MDB/docker-compose.yml`.
  - Do: `tests/Functional/BasePantherTestCase.php` (from the fork, with the console-error assertion
    helper and `PANTHER_SELENIUM_HOST` switch), `docker-compose.yml` with `selenium/standalone-firefox`,
    first test (dashboard loads, console empty); `make test-functional`.
  - Accept: `make test-functional` green locally (docker or geckodriver).

- [ ] **P1-12 · `mongo-compat.yaml`** · M · depends: P1-09
  - Read: PLAN/08 §7; PLAN/02 §1 "MongoDB fork contract".
  - Do: PR job — check out `ideaconnect/sonata-admin-mongodb-bundle`, add a `path` repository to the
    adminata checkout, `composer require idct/adminata:@dev --no-plugins`, `composer validate`, run its
    unit suite (blocking); nightly job — its Panther suite with a Mongo service (`continue-on-error: true`).
    Any change the fork itself needs is a separate PR in the fork repository (note it in the status log).
  - Accept: the PR job is green on the M1 push.

- [ ] **P1-MS · Milestone M1 push** · S · depends: P1-07, P1-08, P1-10, P1-11, P1-12
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

---

## Status log

- 2026-09-04 — Plan v3 committed and pushed (`8430fae`); PROJECT_PLAN.md created; no task started.
