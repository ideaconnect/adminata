# 07 — Packaging, versions, repository, git workflow, quality gates, CI, licensing, upstream sync

Source: `R/packaging.md` (options scoring, empirical Composer and Flex checks). Decisions P1–P9,
J11, J13 apply.

## 1. Packaging (summary)

One repository and one Composer package, `idct/adminata`, containing the seven Sonata packages
copied one-to-one under `packages/<upstream-name>/` and replacing them on Packagist terms
(`replace` × 7 at exact upstream versions). Overlay and new-namespace options stay rejected (P8).
The MongoDB fork stays a separate package and resolves against adminata through `replace`.

Composer facts (2.9.7, seven scenarios in `R/packaging.md` §0.6): an exact `replace` version
satisfies caret constraints of dependants; `self.version` does not; a range resolves but lies;
mutual exclusion with the real packages is automatic. A phase-0 scratch app proves the seven-way
`replace` together with the MongoDB fork (`ReplaceTest`, document 02 §13).

Flex facts (2.11 source, plus the app's `symfony.lock`): recipes are looked up by installed package
name, so Sonata's recipes never apply to adminata. Six of the seven packages have a contrib recipe
(`admin-bundle` copies `config/packages/sonata_admin.yaml`, `config/routes/sonata_admin.yaml`,
`src/Admin/.gitignore`; `block-bundle` copies `config/packages/sonata_block.yaml`; `form-extensions`
copies `config/packages/sonata_form.yaml`; `doctrine-extensions`, `exporter`, `twig-extensions`
copy nothing; `doctrine-orm-admin-bundle` has none). **Uninstalling** them runs `unconfigure`, which
deletes those files without a hash check. Migration therefore runs Composer with
`--no-plugins --no-scripts` (document 10 §1) and removes the stale `sonata-project/*` entries from
`symfony.lock` by hand.

## 2. Verified versions (2026-09-04)

Owner directive: latest releases everywhere; nothing inherits Sonata's pins. Pin exactly in lock
files; Dependabot bumps weekly.

| Package | Latest | Released | Role |
|---|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | 2026-06-03 | `packages/admin-bundle`, replaced |
| `sonata-project/block-bundle` | 5.4.0 | 2025-11-30 | `packages/block-bundle`, replaced |
| `sonata-project/doctrine-extensions` | 2.6.0 | 2025-11-23 | `packages/doctrine-extensions`, replaced |
| `sonata-project/doctrine-orm-admin-bundle` | 4.21.0 | 2026-01-05 | `packages/doctrine-orm-admin-bundle`, replaced |
| `sonata-project/exporter` | 3.4.0 | 2025-11-23 | `packages/exporter`, replaced |
| `sonata-project/form-extensions` | 2.7.0 | 2025-11-23 | `packages/form-extensions`, replaced |
| `sonata-project/twig-extensions` | 2.6.0 | 2025-11-23 | `packages/twig-extensions`, replaced |
| `idct/sonata-admin-mongodb-bundle` | v5.2.2 | — | external; requires `admin-bundle ^4.39`, `exporter ^3.0`, `form-extensions ^2.0` |
| `symfony/framework-bundle` | v8.1.6 | 2026-08-30 | CI target with 7.4 LTS |
| `symfony/stimulus-bundle` | v3.4.0 | 2026-07-25 | Twig helpers |
| `symfony/panther` | v2.4.0 | 2026-01-08 | browser tests |
| `twig/twig` | v3.28.0 | 2026-07-03 | |
| `doctrine/orm` / `doctrine-bundle` / `dbal` / `mongodb-odm-bundle` | 3.6.8 / 3.3.1 / 4.x / 5.6.0 | 2026 | ORM bundle requirements; MongoDB job |
| `phpunit/phpunit` | 13.3.2 | 2026-08-27 | replaces the fork's PHPUnit 12 convention |
| `phpstan/phpstan` | 2.2.13 | 2026-09-03 | level 8 + strict |
| `rector/rector` | 2.6.6 | 2026-09-02 | `UP_TO_PHP_84`, PHPUnit 13 sets |
| `friendsofphp/php-cs-fixer` | v3.95.24 | 2026-08-31 | |
| `infection/infection` | 0.35.4 | 2026-09-02 | nightly |
| `symfonycasts/tailwind-bundle` | v1.0.0 | 2026-07-23 | AssetMapper recipe (post-1.0 docs) |
| Node.js | 24.20.0 LTS (Krypton); 26.8.1 current | 2026-08-26 | build; CI matrix 24 + 26 |
| `@hotwired/stimulus` | 3.2.2 | 2023-08-07 (still `latest`) | runtime |
| `qs` | 6.16.0 | 2026-08-29 | runtime (filter controller) |
| `tailwindcss`, `@tailwindcss/vite`, `@tailwindcss/postcss` | 4.3.3 | 2026-07-16 | CSS |
| `vite` / `vitest` / `jsdom` | 8.2.2 / 5.0.0 / 30.0.1 | 2026-09 | build and unit tests |
| `@fortawesome/fontawesome-free` | 7.3.1 | 2026-07-15 | icons (no shims) |
| `@fontsource-variable/outfit` | 5.3.0 | 2026-07-19 | font |
| `eslint` / `prettier` / `prettier-plugin-tailwindcss` / `stylelint` | 10.10.0 / 3.9.6 / 0.8.1 / 17.15.0 | 2026-09 | lint |
| `size-limit` + `@size-limit/file` | 13.0.3 | 2026-07-30 | budgets |
| `@playwright/test` / `@axe-core/playwright` / `html-validate` | 1.62.1 / 4.13.0 / 11.13.0 | 2026-09 | visual and a11y |
| Not shipped (post-1.0 candidates, all jQuery-free) | `sortablejs` 1.15.7, `vanilla-calendar-pro` 3.3.2, `tom-select` 2.6.2 | | |
| Rejected | `flatpickr` 4.6.13 (stale, 2022-06); `@eonasdan/tempus-dominus` 6.10.4 (Bootstrap-flavoured CSS); anything depending on jQuery | | |

A CI job (`versions-watch`, weekly) lists newer releases of every entry above and opens an issue.

## 3. `composer.json` (key fields)

```json
{
  "name": "idct/adminata",
  "description": "Sonata Admin, re-skinned: the Sonata Admin PHP stack (admin, block, doctrine, ORM admin, exporter, form and twig extensions) with a Tailwind CSS v4 / TailAdmin user interface. Installs in place of the sonata-project packages.",
  "type": "symfony-bundle", "license": "MIT",
  "authors": [ IDCT (maintainer), Thomas Rabaix (original author), Sonata Community, TailAdmin (design system) ],
  "require": {
    "php": "^8.4",
    "symfony/{asset,config,console,dependency-injection,doctrine-bridge,event-dispatcher,expression-language,form,framework-bundle,http-foundation,http-kernel,options-resolver,property-access,routing,security-bundle,security-core,security-csrf,serializer,string,translation,twig-bridge,twig-bundle,validator}": "^7.4 || ^8.0",
    "symfony/stimulus-bundle": "^3.4", "symfony/deprecation-contracts": "^3.6",
    "twig/twig": "^3.28", "twig/string-extra": "^3.0",
    "knplabs/knp-menu": "^3.6", "knplabs/knp-menu-bundle": "^3.0",
    "doctrine/collections": "^2.0 || ^3.0", "doctrine/common": "^3.0", "doctrine/dbal": "^4.0", "doctrine/persistence": "^4.0",
    "doctrine/orm": "^3.6", "doctrine/doctrine-bundle": "^3.0",
    "psr/container": "^2.0", "psr/log": "^3.0"
  },
  "require-dev": { "doctrine/doctrine-fixtures-bundle": "^4.0", "dama/doctrine-test-bundle": "^8.6", "symfony/panther": "^2.4", "phpunit/phpunit": "^13.3", "phpstan/*": "^2.2", "rector/rector": "^2.6", "friendsofphp/php-cs-fixer": "^3.95", "infection/infection": "^0.35", "phpoffice/phpspreadsheet": "^5.0" },
  "replace": {
    "sonata-project/admin-bundle": "4.43.0", "sonata-project/block-bundle": "5.4.0",
    "sonata-project/doctrine-extensions": "2.6.0", "sonata-project/doctrine-orm-admin-bundle": "4.21.0",
    "sonata-project/exporter": "3.4.0", "sonata-project/form-extensions": "2.7.0", "sonata-project/twig-extensions": "2.6.0"
  },
  "conflict": { "symfony/security-acl": "<3.1 || >=4.0", "doctrine/mongodb-odm": "<2.4", "phpoffice/phpspreadsheet": "<1.23", "sonata-project/entity-audit-bundle": ">=2.0" },
  "suggest": { "idct/sonata-admin-mongodb-bundle": "MongoDB ODM admins", "phpoffice/phpspreadsheet": "XLS/XLSX export", "twig/extra-bundle": "Intl", "sonata-project/entity-audit-bundle": "history pages (post-1.0)" },
  "autoload": { "psr-4": {
    "Sonata\\AdminBundle\\": "packages/admin-bundle/src/", "Sonata\\BlockBundle\\": "packages/block-bundle/src/",
    "Sonata\\Doctrine\\": "packages/doctrine-extensions/src/", "Sonata\\DoctrineORMAdminBundle\\": "packages/doctrine-orm-admin-bundle/src/",
    "Sonata\\Exporter\\": "packages/exporter/src/", "Sonata\\Form\\": "packages/form-extensions/src/", "Sonata\\Twig\\": "packages/twig-extensions/src/"
  } },
  "autoload-dev": { "psr-4": { "Sonata\\AdminBundle\\Tests\\": "packages/admin-bundle/tests/", "…one per package…": "", "Adminata\\Tests\\": "tests/" } },
  "extra": { "branch-alias": { "dev-main": "1.x-dev" } },
  "scripts": { "test:cs": "…", "test:rector": "…", "test:phpstan": "…", "test:twig": "bin/console lint:twig packages tests", "test:phpunit": "…", "test:everything": ["@test:cs", "@test:rector", "@test:phpstan", "@test:twig", "@test:phpunit"] }
}
```

The `require` list is the union of the seven upstream lists (floors raised, internal
`sonata-project/*` entries dropped); it also declares `symfony/event-dispatcher-contracts` and
`symfony/translation-contracts`, whose interfaces the admin and form packages import directly.
`require-dev` is likewise the union of the seven upstream dev lists at the §2 versions, because the
seven imported suites all have to run (`dama/doctrine-test-bundle`, `doctrine/mongodb-odm`,
`symfony/maker-bundle`, `sonata-project/entity-audit-bundle`, `phpoffice/phpspreadsheet` … are each
exercised by tests). The `conflict` block keeps the upstream guards that survive the raised floors
and drops those the floors make redundant (`knplabs/knp-menu-bundle <3.0`,
`doctrine/doctrine-bundle <2.7`, `doctrine/orm <2.16`, `sonata-project/block-bundle <4.2`);
upstream's `symfony/security-acl: "<3.1 >=4.0"` is an unsatisfiable conjunction and becomes a
disjunction. Decisions: floors as P4; no `provide` (the ORM bundle's virtual
`sonata-project/admin-bundle-persistency-layer` is dropped so a persistence bundle installed
alongside can provide it); author roster regenerated from the full clones; CI job asserting each
`replace` version equals `UPSTREAM.md`.

## 4. Repository layout

```
adminata/                                  git@github.com:ideaconnect/adminata.git, branch main
├── PLAN/                                  # this plan
├── packages/                              # upstream trees, imported with git subtree (history kept)
│   ├── admin-bundle/{src,tests,docs}          Sonata\AdminBundle\      (views rewritten; public/ = build output)
│   ├── block-bundle/{src,tests,docs}          Sonata\BlockBundle\
│   ├── doctrine-extensions/{src,tests,docs}   Sonata\Doctrine\
│   ├── doctrine-orm-admin-bundle/{src,tests,docs}  Sonata\DoctrineORMAdminBundle\
│   ├── exporter/{src,tests,docs}              Sonata\Exporter\
│   ├── form-extensions/{src,tests,docs}       Sonata\Form\             (assets/ and public/ deleted; datepicker view rewritten)
│   └── twig-extensions/{src,tests,docs}       Sonata\Twig\             (public/css deleted; flash view rewritten)
├── assets/{css,js,images}/                # adminata's UI sources (documents 04, 05)
├── tests/{App,Contract,Visual,Compat}/    # adminata-level suites (document 08)
├── docs/                                  # single Sphinx site (document 12)
├── bin/console
├── upstream/{remotes.txt,exclude/<name>.txt,diff.sh,sync.sh}
├── AGENTS.md CHANGELOG.md CHANGELOG-sonata.md CONTRIBUTING.md LICENSE NOTICE README.md
├── MIGRATION.md UPGRADE-1.0.md UPSTREAM.md
├── composer.json package.json package-lock.json vite.config.js vitest.config.js eslint.config.js prettier.config.js stylelint.config.js playwright.config.js
├── phpunit.xml.dist phpstan.neon.dist rector.php .php-cs-fixer.dist.php infection.json5.dist .yamllint .editorconfig .gitattributes .readthedocs.yaml .symfony.bundle.yaml docker-compose.yml
└── .github/{workflows,dependabot.yml,ISSUE_TEMPLATE,PULL_REQUEST_TEMPLATE.md}
```

Build outputs are committed under `packages/admin-bundle/src/Resources/public/` (`app.css`,
`fontawesome.css`, `app.js`, `fonts/`, `images/`, `entrypoints.json`, `manifest.json`) so
`assets:install` publishes them as `bundles/sonataadmin/`. `assets/` ships in the dist so apps can
import `adminata.css`. Layout alternative (flattened `src/` per namespace) is an owner call; it costs
path rewriting in the sync script and buys nothing for Composer, which sees one package either way.

## 5. Branching, versioning, git workflow

- Repository `ideaconnect/adminata`; default branch `main`; work on short-lived branches merged to
  `main`; `1.x` created when 2.x work starts.
- **Milestone pushes**: `main` is pushed after every phase exit gate (document 09), after every plan
  revision, and at every tag. Commits end with the co-author trailer used in this repository.
- First release **1.0.0**; pre-releases `1.0.0-alpha1 … rc1` while the acceptance checklist is
  incomplete.
- Semver contract (`CONTRIBUTING.md`): PHP API breaks and removals of blocks listed in document 02
  §5 are majors; markup changes keeping those blocks and the `sonata-*` hooks are minors; CSS-only
  changes are patches; a BC upstream PHP sync is a minor and bumps the corresponding `replace`
  version in the same release.

## 6. Quality gates

| Gate | Setting |
|---|---|
| PHPUnit 13 | one `phpunit.xml.dist`; suites `admin`, `block`, `doctrine`, `orm`, `exporter`, `form`, `twig` (imported upstream suites), `adminata-unit`, `adminata-functional`, `adminata-contract`; `failOnWarning/Risky`; Panther `ServerExtension` |
| PHPStan 2.2 | level 8 + bleedingEdge + strict + symfony + phpunit extensions over `packages/*/src` and `tests`; per-package baselines imported from upstream and trimmed; no `@phpstan-ignore` in new code; both Symfony lines |
| Rector 2.6 | `UP_TO_PHP_84`, PHPUnit 13 sets, code-quality sets; one mechanical commit per package so upstream cherry-picks rebase cleanly |
| PHP-CS-Fixer 3.95 | fork's rule set, Sonata header kept |
| composer-normalize, yamllint, xmllint (xml/xliff), `lint:container`, `lint:twig packages tests`, `lint:xliff`, `lint:yaml` | dev-kit Makefile targets kept |
| Infection 0.35 | `@default` mutators on adminata's unit suite, nightly, not PR-blocking |
| JS | ESLint 10 flat config (`@eslint/js` + prettier + header + import; `no-restricted-imports` for `jquery`), Prettier 3.9 + `prettier-plugin-tailwindcss` 0.8, Stylelint 17 with Tailwind v4 at-rules, Vitest 5 + jsdom 30; `npm ls jquery` must be empty (J13) |
| Build freshness | `npm ci && npm run build && git diff --no-patch --exit-code -- packages/admin-bundle/src/Resources/public`; `size-limit` 13 budgets |

Definition of done for any task: PHPUnit, PHPStan, Rector, PHP-CS-Fixer, `lint:twig`, ESLint,
Stylelint, Prettier, Vitest, build-freshness and the contract tests all green.

## 7. Makefile and GitHub Actions

Makefile (appended to the dev-kit file): `assets-install`, `assets-build`, `assets-check`,
`lint-js`, `lint-css`, `lint-prettier`, `test-js`, `test-unit`, `test-functional`, `test-contract`,
`test-visual`, `test-mongo`, `infection`, `upstream-diff PKG= FROM= TO=`, `upstream-sync PKG= TO=`, `demo`.

| Workflow | Jobs |
|---|---|
| `test.yaml` | PHP 8.4/8.5 × highest; 8.4 + lowest; 8.5 + `SYMFONY_REQUIRE=7.4.*` and `8.1.*`; package suites and adminata suites as separate steps; codecov |
| `qa.yaml` | PHPStan + Rector on both Symfony lines |
| `lint.yaml` | php-cs-fixer, composer-normalize, yamllint, xmllint, **`replace`-versions check**, `npm ls jquery` |
| `symfony-lint.yaml` | container, twig, xliff, yaml |
| `frontend.yaml` | Node 24 and 26: `npm ci`, eslint, stylelint, prettier, vitest, build, `git diff --exit-code`, `css-contract`, `size-limit` |
| `visual.yaml` | Playwright 1.62 (Chromium/Firefox/WebKit) screenshots × viewports × themes against the demo app; PR + nightly |
| `mongo-compat.yaml` | **PR CI**: check out the MongoDB fork, `composer require idct/adminata:@dev` (path repo), `composer validate`, its unit suite; **nightly**: its Panther suite (informational until the association templates exist) |
| `documentation.yaml`, `mutation.yaml`, `stale.yaml` | as the fork |
| `upstream-watch.yaml` (weekly) | for each of the seven upstreams: `gh api …/releases/latest`; opens an issue with `upstream/diff.sh` output when a newer tag exists |
| `versions-watch.yaml` (weekly) | newer releases of the §2 table |

Dependabot: composer weekly (groups symfony/doctrine/dev), github-actions weekly, npm weekly
(groups tailwind, vite, dev-dependencies).

## 8. Documentation set

`README.md` (badges, "THIS IS A HARD FORK of seven Sonata packages", what is and is not
preserved), `AGENTS.md` (fork's skeleton + "Template contract", "Packages and upstream sync",
"Library policy" sections), `UPSTREAM.md` (per package: last synced tag, exclusion list, sync log),
`MIGRATION.md` (recomaty-panel checklist, document 10 §1–§2), `UPGRADE-1.0.md` (document 10 §3),
`CHANGELOG.md` (Keep-a-Changelog) plus `CHANGELOG-sonata.md` (upstream changelog excerpts per
package), `NOTICE`, Sphinx docs (document 12).

## 9. Licensing and attribution

- All seven Sonata packages: MIT (2010 Thomas Rabaix / Sonata Project); TailAdmin MIT (2023; its
  `package.json` says ISC — note in `NOTICE`); Stimulus MIT; Tailwind MIT; qs BSD-3; Font Awesome
  Free (CC-BY-4.0 icons, OFL fonts, MIT code); Outfit OFL.
- `LICENSE`: MIT text with three copyright lines and a fork/design-system note. `NOTICE` lists every
  component and upstream package. Keep the Sonata header on inherited files; combined header on new
  files; Twig comment crediting TailAdmin on templates derived from its markup.
- The TailAdmin name/logo is not used as adminata branding; `title_logo` default becomes a neutral
  adminata logo at the same path.

## 10. Upstream sync process (per package)

1. Seven `upstream-<name>` remotes; one-time import with
   `git subtree add --prefix=packages/<name> upstream-<name> <tag>` (history kept; `--squash` is an
   owner option for the small packages).
2. `upstream/exclude/<name>.txt`: for `admin-bundle` — `src/Resources/views/**`,
   `src/Resources/public/**`, `assets/**`, `package*.json`, build/lint configs, `.github/**`,
   `Makefile`, `*.md`, `phpunit.xml.dist`, `rector.php`, `.php-cs-fixer.dist.php`, `phpstan*.neon`,
   the obsolete cookbook recipes, and the P6 PHP files (merged by hand); for `form-extensions` —
   `assets/**`, `src/Bridge/Symfony/Resources/{views,public}/**`, `BasePickerType.php`; for
   `twig-extensions` — `src/Bridge/Symfony/Resources/{views,public}/**`; for `block-bundle` and
   `doctrine-orm-admin-bundle` — the views listed as deferred/rewritten in document 03 §G; for the
   rest — tooling files only.
3. `upstream/diff.sh PKG FROM TO`: PHP-side diff stat, separate "UI changes to re-implement" diff for
   views/assets, changelog section between tags → pasted into the sync issue.
4. `upstream/sync.sh PKG TO`: `git diff FROM TO -- <included paths> | git apply -3 --directory=packages/PKG`,
   then `make cs-fix rector phpstan test`; commit "Sync <pkg> X.Y.Z"; commit bumping the `replace`
   entry and `UPSTREAM.md`.
5. Template changes re-implemented by hand with a CHANGELOG line "Ported upstream <pkg>#NNNN".
6. Policy: sync every upstream minor within one adminata minor; never merge admin-bundle 5.x before
   adminata 2.0; carry upstream deprecations as-is.

## 11. Symfony Flex

New app: `composer require idct/adminata`, then register the seven bundle classes in `bundles.php`
by hand (Flex's convention-based auto-registration is not relied upon for a multi-bundle package;
verify in document 11 §3) and copy the documented `sonata_admin.yaml`, `sonata_block.yaml`,
`routes/sonata_admin.yaml`. Existing app: document 10 §1 (the `--no-plugins` path). A contrib
recipe for `idct/adminata` is post-1.0.
