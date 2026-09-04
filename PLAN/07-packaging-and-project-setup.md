# 07 — Packaging, versions, repository setup, quality gates, CI, licensing, upstream sync

Source: `R/packaging.md` (options scoring, empirical Composer and Flex checks, skeleton). Decisions
P1–P8, J11 apply.

## 1. Packaging (summary)

Hard fork with the same identity and Composer `replace` (v1 "Option B"; overlay and new-namespace
options rejected, P8). Composer facts (2.9.7, seven scenarios): exact `4.43.0` resolves with the
ORM bundle; `self.version` fails; a range in `replace` resolves but lies; mutual exclusion with
the real package is automatic. Flex facts (2.11 source): recipes are looked up by installed package
name, so Sonata's contrib recipe never applies to adminata; **uninstalling** the real package runs
its recipe `unconfigure`, which deletes recipe-copied files without a hash check. Migration command
is therefore `composer require idct/adminata --no-plugins --no-scripts` followed by
`composer install` (document 10). The recipe's exact file list must be verified online.

## 2. Verified versions (2026-09-04)

Owner directive: latest releases everywhere; nothing inherits Sonata's pins. Pin exactly in lock
files; Dependabot bumps weekly.

| Package | Latest | Released | Role |
|---|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | 2026-06-03 | fork base and `replace` target |
| `sonata-project/doctrine-orm-admin-bundle` | 4.21.0 | 2026-01-05 | requires `admin-bundle ^4.39.0`, `php ^8.2` |
| `idct/sonata-admin-mongodb-bundle` | v5.2.2 | — | requires `admin-bundle ^4.39`, `php ^8.4`, `symfony/form ^7.4 \|\| ^8.0` |
| `sonata-project/form-extensions` / `block-bundle` / `twig-extensions` / `exporter` / `doctrine-extensions` | 2.7.0 / 5.4.0 / 2.6.0 / 3.4.0 / 2.6.0 | 2025-11 | hard dependencies (floors raised to these) |
| `symfony/framework-bundle` | v8.1.6 | 2026-08-30 | CI target with 7.4 LTS |
| `symfony/stimulus-bundle` | v3.4.0 | 2026-07-25 | Twig helpers |
| `symfony/panther` | v2.4.0 | 2026-01-08 | browser tests |
| `twig/twig` | v3.28.0 | 2026-07-03 | |
| `phpunit/phpunit` | 13.3.2 | 2026-08-27 | replaces the fork's PHPUnit 12 convention |
| `phpstan/phpstan` | 2.2.13 | 2026-09-03 | level 8 + strict |
| `rector/rector` | 2.6.6 | 2026-09-02 | `UP_TO_PHP_84`, PHPUnit 13 sets |
| `friendsofphp/php-cs-fixer` | v3.95.24 | 2026-08-31 | |
| `infection/infection` | 0.35.4 | 2026-09-02 | nightly |
| `doctrine/orm` / `doctrine-bundle` / `mongodb-odm-bundle` | 3.6.8 / 3.3.1 / 5.6.0 | 2026 | demo app and compat jobs |
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
| Not shipped (post-1.0 candidates) | `sortablejs` 1.15.7, `tom-select` 2.6.2, `vanilla-calendar-pro` 3.3.2 | | |
| Rejected as stale | `flatpickr` 4.6.13 (2022-06), `@eonasdan/tempus-dominus` 6.10.4 (2025-05, Bootstrap-flavoured CSS) | | |

A CI job (`versions-watch`, weekly) lists newer releases of every entry above and opens an issue.

## 3. `composer.json` (key fields)

```json
{
  "name": "idct/adminata",
  "description": "Sonata Admin, re-skinned: the SonataAdminBundle PHP layer with a Tailwind CSS v4 / TailAdmin user interface. Installs in place of sonata-project/admin-bundle 4.x.",
  "type": "symfony-bundle", "license": "MIT",
  "authors": [ IDCT (maintainer), Thomas Rabaix (original author), Sonata Community, TailAdmin (design system) ],
  "require": { "php": "^8.4", "symfony/*": "^7.4 || ^8.0", "sonata-project/block-bundle": "^5.4", "sonata-project/form-extensions": "^2.7", "sonata-project/twig-extensions": "^2.6", "sonata-project/exporter": "^3.4", "sonata-project/doctrine-extensions": "^2.6", "symfony/stimulus-bundle": "^3.4", "twig/twig": "^3.28", "...every other entry of S/composer.json require...": "" },
  "require-dev": { "doctrine/orm": "^3.6", "doctrine/doctrine-bundle": "^3.3", "doctrine/doctrine-fixtures-bundle": "^4.0", "ext-pdo_sqlite": "*", "sonata-project/doctrine-orm-admin-bundle": "^4.21", "symfony/panther": "^2.4", "phpunit/phpunit": "^13.3", "phpstan/*": "^2.2", "rector/rector": "^2.6", "friendsofphp/php-cs-fixer": "^3.95", "infection/infection": "^0.35" },
  "replace": { "sonata-project/admin-bundle": "4.43.0" },
  "conflict": { "symfony/security-acl": "<3.1 >=4.0" },
  "suggest": { "sonata-project/doctrine-orm-admin-bundle": "Doctrine ORM admins", "idct/sonata-admin-mongodb-bundle": "MongoDB ODM admins" },
  "autoload": { "psr-4": { "Sonata\\AdminBundle\\": "src/" } },
  "extra": { "branch-alias": { "dev-main": "1.x-dev" } },
  "scripts": { "test:cs": "…", "test:rector": "…", "test:phpstan": "…", "test:twig": "bin/console lint:twig src tests", "test:phpunit": "…", "test:everything": ["@test:cs", "@test:rector", "@test:phpstan", "@test:twig", "@test:phpunit"] }
}
```

Decisions: floors as P4; no `provide`; author roster regenerated from a full clone like the MongoDB
fork did; CI job asserting the `replace` version equals `UPSTREAM.md`.

## 4. Repository skeleton

```
adminata/
├── assets/{css,js,images}/              # sources (documents 04, 05)
├── bin/console                          # boots tests/App kernel
├── docs/                                # Sphinx, forked from S/docs and pruned (document 12)
├── src/                                 # Sonata\AdminBundle\* — upstream-tracked PHP + Resources/{config,public,skeleton,translations,views}
├── tests/{App,Functional,Contract,Visual,...}   # document 08
├── upstream/{exclude.txt,diff.sh,sync.sh}       # sync tooling
├── AGENTS.md CHANGELOG.md CHANGELOG-sonata.md CONTRIBUTING.md LICENSE NOTICE README.md
├── MIGRATION.md UPGRADE-1.0.md UPSTREAM.md
├── composer.json package.json package-lock.json vite.config.js vitest.config.js eslint.config.js prettier.config.js stylelint.config.js playwright.config.ts
├── phpunit.xml.dist phpstan.neon.dist rector.php .php-cs-fixer.dist.php infection.json5.dist .yamllint .editorconfig .gitattributes .readthedocs.yaml .symfony.bundle.yaml docker-compose.yml
└── .github/{workflows,dependabot.yml,ISSUE_TEMPLATE,PULL_REQUEST_TEMPLATE.md}
```

`src/Resources/public` stays committed and prebuilt; file names `app.css`, `app.js`,
`fontawesome.css`, `entrypoints.json`, `manifest.json`. `assets/` ships in the dist so apps can
import `adminata.css`. Repository `ideaconnect/adminata` on GitHub, `idct/adminata` on Packagist.

## 5. Branching and versioning

- Default branch `main`; `1.x` created when 2.x work starts; tags `v1.0.0`; pre-releases
  `1.0.0-alpha1 … rc1` while the acceptance checklist is incomplete.
- Semver contract (`CONTRIBUTING.md`): PHP API breaks and removals of blocks listed in document 02
  §5 are majors; markup changes keeping those blocks and the `sonata-*` hooks are minors; CSS-only
  changes are patches; a BC upstream PHP sync is a minor and bumps `replace` in the same release.

## 6. Quality gates

| Gate | Setting |
|---|---|
| PHPUnit 13 | suites `unit`, `functional`, `contract`; `failOnWarning/Risky`; Panther `ServerExtension`; `KERNEL_CLASS` = test kernel |
| PHPStan 2.2 | level 8 + bleedingEdge + strict + symfony + phpunit extensions; baseline trimmed to the 3 upstream entries; no `@phpstan-ignore` in new code; both Symfony lines |
| Rector 2.6 | `UP_TO_PHP_84`, PHPUnit 13 sets, code-quality sets; one dedicated mechanical commit so upstream cherry-picks rebase cleanly |
| PHP-CS-Fixer 3.95 | fork's rule set (`@PHP8x4Migration`, `@PHPUnit100Migration:risky`, `@Symfony(:risky)`, `@PSR12(:risky)`), Sonata header kept |
| composer-normalize, yamllint, xmllint (xml/xliff), `lint:container`, `lint:twig src tests`, `lint:xliff`, `lint:yaml` | dev-kit Makefile targets kept; `lint:twig` is the first gate of the rewrite |
| Infection 0.35 | `@default` mutators on `unit`, nightly, not PR-blocking |
| JS | ESLint 10 flat config (`@eslint/js` + prettier + header + import), Prettier 3.9 + `prettier-plugin-tailwindcss` 0.8, Stylelint 17 with Tailwind v4 at-rules allowed + `stylelint-order`, Vitest 5 + jsdom 30 |
| Build freshness | `npm ci && npm run build && git diff --no-patch --exit-code -- src/Resources/public`; `size-limit` 13 budgets |

Definition of done for any task: PHPUnit, PHPStan, Rector, PHP-CS-Fixer, `lint:twig`, ESLint,
Stylelint, Prettier, Vitest, build-freshness and the contract tests all green.

## 7. Makefile and GitHub Actions

Makefile (appended to the dev-kit file): `assets-install`, `assets-build`, `assets-check`,
`lint-js`, `lint-css`, `lint-prettier`, `test-js`, `test-unit`, `test-functional`, `test-contract`,
`test-visual`, `infection`, `upstream-diff FROM= TO=`, `upstream-sync TO=`, `demo`.

| Workflow | Jobs |
|---|---|
| `test.yaml` | PHP 8.4/8.5 × highest; 8.4 + lowest; 8.5 + `SYMFONY_REQUIRE=7.4.*` and `8.1.*`; unit, functional and contract steps split; codecov |
| `qa.yaml` | PHPStan + Rector on both Symfony lines |
| `lint.yaml` | php-cs-fixer, composer-normalize, yamllint, xmllint, **`replace`-version check** |
| `symfony-lint.yaml` | container, twig, xliff, yaml |
| `frontend.yaml` | Node 24 and 26: `npm ci`, eslint, stylelint, prettier, vitest, build, `git diff --exit-code`, `css-contract`, `size-limit` |
| `visual.yaml` | Playwright 1.62 (Chromium/Firefox/WebKit) screenshots × viewports × themes against the demo app; PR + nightly |
| `mongo-compat.yaml` (nightly, informational until post-1.0) | adminata installed into the MongoDB fork's test app (Mongo + Firefox) |
| `documentation.yaml`, `mutation.yaml`, `stale.yaml` | as the fork |
| `upstream-watch.yaml` (weekly) | `gh api …/releases/latest`; opens an issue with `upstream/diff.sh` output when a newer tag exists |
| `versions-watch.yaml` (weekly) | newer releases of the §2 table |

Dependabot: composer weekly (groups symfony/doctrine/sonata/dev), github-actions weekly, npm
weekly (groups tailwind, vite, dev-dependencies).

## 8. Documentation set

`README.md` (badges, "THIS IS A HARD FORK", what is and is not preserved), `AGENTS.md` (fork's
skeleton + "Template contract" and "Upstream sync" sections), `UPSTREAM.md` (last synced tag,
exclusion list, per-release log), `MIGRATION.md` (recomaty-panel checklist, document 10 §1–§2),
`UPGRADE-1.0.md` (generic notes, document 10 §3), `CHANGELOG.md` (Keep-a-Changelog) plus
`CHANGELOG-sonata.md`, `NOTICE`, Sphinx docs (document 12).

## 9. Licensing and attribution

- Sonata MIT (2010 Thomas Rabaix); TailAdmin MIT (2023; its `package.json` says ISC — note in
  `NOTICE`); Stimulus MIT; Tailwind MIT; qs BSD-3; Font Awesome Free (CC-BY-4.0 icons, OFL fonts,
  MIT code); Outfit OFL.
- `LICENSE`: MIT text with three copyright lines and a fork/design-system note. `NOTICE` lists every
  component. Keep the Sonata header on inherited files; combined header on new files; Twig comment
  crediting TailAdmin on templates derived from its markup.
- The TailAdmin name/logo is not used as adminata branding; `title_logo` default becomes a neutral
  adminata logo at the same path.

## 10. Upstream sync process

1. `upstream` remote + read-only branch `pristine/4.x` fast-forwarded to each upstream tag.
2. `upstream/exclude.txt`: `src/Resources/views/**`, `src/Resources/public/**`, `assets/**`,
   `package*.json`, build/lint configs, `.github/**`, `Makefile`, `*.md`, `phpunit.xml.dist`,
   `rector.php`, `.php-cs-fixer.dist.php`, `phpstan*.neon`, the three obsolete cookbook recipes,
   plus the handful of PHP files changed under P6 (`Configuration.php`, `SonataAdminExtension.php`,
   `BaseGroupedMapper.php`, `AbstractAdmin.php` group defaults) which are merged by hand.
3. `upstream/diff.sh FROM TO`: PHP-side diff stat, separate "UI changes to re-implement" diff for
   views/assets, changelog section between tags → pasted into the sync issue.
4. `upstream/sync.sh TO`: `git diff FROM TO -- <included paths> | git apply -3`, then
   `make cs-fix rector phpstan test`; commit "Sync upstream X.Y.Z (PHP layer)", then a commit
   bumping `replace` and `UPSTREAM.md`.
5. Template changes re-implemented by hand with a CHANGELOG line "Ported upstream #NNNN".
6. Policy: sync every upstream minor within one adminata minor; never merge 5.x before adminata
   2.0; carry upstream deprecations as-is. `git subtree` rejected.

## 11. Symfony Flex

New app: `composer require idct/adminata sonata-project/doctrine-orm-admin-bundle`; Flex
auto-registers `Sonata\AdminBundle\SonataAdminBundle`; the two 10-line YAML files are documented.
Existing app: document 10 §1 (the `--no-plugins` path). A contrib recipe is post-1.0.
