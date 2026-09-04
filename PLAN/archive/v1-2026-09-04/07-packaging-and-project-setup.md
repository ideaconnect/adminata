# 07 — Packaging, repository setup, quality gates, CI, licensing, upstream sync

Source: `R/packaging.md` (options scoring, empirical Composer and Flex checks, skeleton).

## 1. Packaging options (summary of the analysis)

| Option | Shape | Verdict |
|---|---|---|
| A — theme overlay | `idct/adminata` requires `sonata-project/admin-bundle ^4.43`, overrides templates via `twig.paths`/`templates.*` and swaps assets | Cannot fix PHP coupling (skin CSS appended after config, `box_class` default, `final` `IconRuntime`), fragile against upstream template changes, contradicts the fork precedent. Score 27–29 |
| **B — hard fork, same identity, `replace`** | whole 4.43.0 tree, templates/assets rewritten, `replace: sonata-project/admin-bundle: 4.43.0` | Drop-in for persistence bundles and overrides; PHP coupling fixable; medium sync burden. **Score 35 — chosen** |
| C — new namespace | `Adminata\`, new bundle/Twig names | Breaks every persistence bundle and user admin class. Score 21 |
| D — B now, rename later with `class_alias` | | Permanent BC-layer tax, PHPStan friction, Flex bundle-name clash, no user benefit. Score 31 — rejected |

Composer `replace` facts (Composer 2.9.7, seven scenarios): exact `4.43.0` resolves with the ORM
bundle; `self.version` fails (1.0.0 ∉ `^4.39`); a root that still requires
`sonata-project/admin-bundle ^4.43` still gets adminata; adminata is never chosen unless
required; `^4.44` from a sibling fails loudly until `replace` is bumped; a range in `replace`
resolves but lies; `conflict` works as a knob. Mutual exclusion with the real package is automatic.

Flex facts (2.11.0 source): recipes are looked up by installed package name, so Sonata's contrib
recipe never applies to adminata (Flex auto-generates the `bundles.php` entry from the bundle
class, idempotent for existing apps); **uninstalling** the real package runs its recipe
`unconfigure`, which deletes recipe-copied files without a content-hash check. Migration command
therefore is `composer require idct/adminata --no-plugins --no-scripts` followed by
`composer install` (document 10). The recipe's exact file list must be verified online.

## 2. `composer.json` (key fields)

```json
{
  "name": "idct/adminata",
  "description": "Sonata Admin, re-skinned: the SonataAdminBundle PHP layer with a Tailwind CSS v4 / TailAdmin user interface. Drop-in replacement for sonata-project/admin-bundle 4.x.",
  "type": "symfony-bundle", "license": "MIT",
  "authors": [ IDCT (maintainer), Thomas Rabaix (original author), Sonata Community, TailAdmin (design system) ],
  "require": { "php": "^8.4", "symfony/*": "^7.4 || ^8.0", "...every entry of S/composer.json require, floors raised...": "" },
  "require-dev": { "doctrine/orm": "^3.3", "doctrine/doctrine-bundle": "^2.17 || ^3.0", "doctrine/doctrine-fixtures-bundle": "^4.0", "ext-pdo_sqlite": "*", "sonata-project/doctrine-orm-admin-bundle": "^4.20", "symfony/panther": "^2.4", "phpunit/phpunit": "^12.3.10", "phpstan/*": "^2.0", "rector/rector": "^2.0", "friendsofphp/php-cs-fixer": "^3.95", "infection/infection": "^0.33.1", "...": "" },
  "replace": { "sonata-project/admin-bundle": "4.43.0" },
  "conflict": { "symfony/security-acl": "<3.1 >=4.0" },
  "suggest": { "sonata-project/doctrine-orm-admin-bundle": "tier-1", "idct/sonata-admin-mongodb-bundle": "tier-1", "twig/extra-bundle": "Intl" },
  "autoload": { "psr-4": { "Sonata\\AdminBundle\\": "src/" } },
  "extra": { "branch-alias": { "dev-main": "1.x-dev" } },
  "scripts": { "test:cs": "…", "test:rector": "…", "test:phpstan": "…", "test:twig": "bin/console lint:twig src tests", "test:phpunit": "…", "test:infection": "…", "test:everything": ["@test:cs", "@test:rector", "@test:phpstan", "@test:twig", "@test:phpunit", "@test:infection"] }
}
```

Decisions: floors as D05 (owner); no `provide`; no trimming of `require`; author roster —
regenerate the 74-contributor list from a full clone like the MongoDB fork did (owner);
CI job asserting `replace` version equals `UPSTREAM.md`.

## 3. Repository skeleton

```
adminata/
├── assets/{css,js,icons,images}/       # sources (document 04, 05)
├── bin/console                          # boots tests/App kernel
├── docs/                                # Sphinx, forked from S/docs (document 12)
├── src/                                 # Sonata\AdminBundle\* — upstream-tracked PHP layer + Resources/{config,public,skeleton,translations,views}
├── tests/{App,Functional,Parity,Visual,...}  # document 08
├── upstream/{exclude.txt,diff.sh,sync.sh}    # sync tooling
├── AGENTS.md BEST_VERSION.md CHANGELOG.md CHANGELOG-sonata.md COMPATIBILITY.md CONTRIBUTING.md
├── LICENSE NOTICE README.md UPGRADE-1.0.md UPSTREAM.md
├── composer.json package.json package-lock.json vite.config.js eslint.config.js prettier.config.js stylelint.config.js playwright.config.ts
├── phpunit.xml.dist phpstan.neon.dist rector.php .php-cs-fixer.dist.php infection.json5.dist .yamllint .editorconfig .gitattributes .readthedocs.yaml .symfony.bundle.yaml codecov.yml docker-compose.yml
└── .github/{workflows,dependabot.yml,ISSUE_TEMPLATE,PULL_REQUEST_TEMPLATE.md}
```

`src/Resources/public` stays committed and prebuilt; file names `app.css`, `app.js`,
`entrypoints.json`, `manifest.json` kept. `assets/` ships in the dist (users compiling their own
CSS import it). Repository: `ideaconnect/adminata` on GitHub, `idct/adminata` on Packagist (owner).

## 4. Branching and versioning

- Default branch `main`; `1.x` created when 2.x work starts; tags `v1.0.0`.
- First release **1.0.0** (`5.0.0` would read as "Sonata 5"); pre-releases `1.0.0-alpha1 … rc1`
  while the parity checklist is incomplete.
- Semver contract (into `CONTRIBUTING.md`): PHP API breaks and Twig block-name removals are
  majors; markup changes keeping block names and `sonata-*` hooks are minors; CSS-only changes
  are patches; a BC upstream PHP sync is a minor and bumps `replace` in the same release.
- `.symfony.bundle.yaml`: `branches: [main]`, `current_branch: main`, `doc_dir: docs/`.

## 5. Quality gates

| Gate | Setting |
|---|---|
| PHPUnit | `^12.3.10`; suites `unit`, `functional`, `parity`; `failOnWarning/Risky`; Panther `ServerExtension`; `KERNEL_CLASS` = test kernel; `ignoreIndirectDeprecations` as the fork |
| PHPStan | level 8 + bleedingEdge + strict + symfony + phpunit extensions; baseline trimmed to the 3 upstream entries with the goal of zero; no `@phpstan-ignore` in new code; run on both Symfony lines |
| Rector | `UP_TO_PHP_84`, `PHPUNIT_120`, `PHPUNIT_CODE_QUALITY`, fork's skip list; one dedicated mechanical commit so upstream cherry-picks rebase cleanly |
| PHP-CS-Fixer | fork's rule set (`@PHP8x4Migration`, `@PHPUnit9x1Migration:risky`, `@Symfony(:risky)`, `@PSR12(:risky)`), Sonata header kept, `setUnsupportedPhpVersionAllowed(true)` |
| composer-normalize, yamllint, xmllint (xml/xliff), `lint:container`, `lint:twig src tests`, `lint:xliff`, `lint:yaml` | dev-kit Makefile targets kept verbatim; `lint:twig` is the first gate of the rewrite |
| Infection | `@default` mutators on `unit`, nightly, not PR-blocking |
| JS | ESLint 9 flat config (`@eslint/js` + prettier + header + import; no airbnb), Prettier 3 + `prettier-plugin-tailwindcss` (+ Twig plugin evaluated), Stylelint 16 with Tailwind v4 at-rules allowed + `stylelint-order`, Vitest + jsdom |
| Build freshness | `npm ci && npm run build && git diff --no-patch --exit-code -- src/Resources/public`; `size-limit` budgets |

Definition of done for any task: PHPUnit, PHPStan, Rector, PHP-CS-Fixer, `lint:twig`, ESLint,
Stylelint, Prettier, Vitest, build-freshness and the contract tests all green.

## 6. Makefile additions and GitHub Actions

Makefile (appended to the dev-kit file): `assets-install`, `assets-build`, `assets-check`,
`lint-js`, `lint-css`, `lint-prettier`, `test-js`, `test-unit`, `test-functional`,
`test-parity`, `test-visual`, `infection`, `upstream-diff FROM= TO=`, `upstream-sync TO=`, `demo`.

| Workflow | Jobs |
|---|---|
| `test.yaml` | PHP 8.4/8.5 × highest; 8.4 + lowest; 8.5 + `SYMFONY_REQUIRE=7.4.*` and `8.0.*`; unit and functional steps split; codecov v6 |
| `qa.yaml` | PHPStan + Rector on both Symfony lines |
| `lint.yaml` | php-cs-fixer, composer-normalize, yamllint, xmllint, **`replace`-version check** |
| `symfony-lint.yaml` | container, twig, xliff, yaml |
| `frontend.yaml` | Node 24: `npm ci`, eslint, stylelint, prettier, vitest, build, `git diff --exit-code`, `css-contract`, `size-limit` |
| `visual.yaml` | Playwright (Chromium/Firefox/WebKit) screenshots × viewports × themes against the demo app; PR + nightly |
| `compat.yaml` (nightly, dispatch) | adminata installed into the MongoDB fork's test app (Mongo + Firefox) and into an ORM checkout; optional private job for `APP/` acceptance |
| `documentation.yaml`, `mutation.yaml`, `stale.yaml` | as the fork |
| `upstream-watch.yaml` (weekly) | `gh api …/releases/latest`; opens an issue with `upstream/diff.sh` output when a newer tag exists |

Dependabot: composer weekly (groups symfony/doctrine/sonata/dev), github-actions weekly, npm
weekly (groups tailwind, dev-dependencies).

## 7. Documentation set

`README.md` (badges, "THIS IS A HARD FORK", "Drop-in guarantee" box listing what is and is not
preserved), `AGENTS.md` (fork's skeleton + "Template contract" and "Upstream sync" sections),
`BEST_VERSION.md` (severity-coded findings on the inherited PHP layer), `UPSTREAM.md` (last
synced tag, exclusion list, per-release log), `COMPATIBILITY.md` (ecosystem tiers),
`UPGRADE-1.0.md` (document 10), `CHANGELOG.md` (Keep-a-Changelog) plus `CHANGELOG-sonata.md`,
`NOTICE`, Sphinx docs (document 12).

## 8. Licensing and attribution

- Sonata MIT (2010 Thomas Rabaix); TailAdmin MIT (2023; `package.json` says ISC — a metadata
  slip, note in `NOTICE` and ask upstream); Tom Select **Apache-2.0** (ship its NOTICE); Font
  Awesome Free (CC-BY-4.0 icons, OFL fonts, MIT code); Outfit OFL; flatpickr, SortableJS,
  Stimulus, Tailwind MIT.
- `LICENSE`: MIT text with three copyright lines and a fork/design-system note. `NOTICE` lists
  every component. Keep the Sonata header on inherited files (clean cherry-picks); combined
  header on new files; Twig comment crediting TailAdmin on templates derived from its markup.
- Do not use the TailAdmin name/logo as adminata branding; `title_logo` default becomes a
  neutral adminata logo at the same path.

## 9. Upstream sync process

1. `upstream` remote + read-only branch `pristine/4.x` fast-forwarded to each upstream tag
   (requires a full clone; the research checkout is depth-1).
2. `upstream/exclude.txt`: `src/Resources/views/**`, `src/Resources/public/**`, `assets/**`,
   `package*.json`, build/lint configs, `.github/**`, `Makefile`, `*.md`, `phpunit.xml.dist`,
   `rector.php`, `.php-cs-fixer.dist.php`, `phpstan*.neon`, the three obsolete cookbook recipes.
3. `upstream/diff.sh FROM TO`: PHP-side diff stat, separate "UI changes to re-implement" diff for
   views/assets, changelog section between tags → pasted into the sync issue.
4. `upstream/sync.sh TO`: `git diff FROM TO -- <included paths> | git apply -3`, then
   `make cs-fix rector phpstan test`; commit "Sync upstream X.Y.Z (PHP layer)", then a commit
   bumping `replace` and `UPSTREAM.md`.
5. Template changes re-implemented by hand with a parity entry and a CHANGELOG line
   "Ported upstream #NNNN".
6. Policy: sync every upstream minor within one adminata minor; never merge 5.x before adminata
   2.0; carry upstream deprecations as-is. `git subtree` rejected.

## 10. Symfony Flex recipe

New app: `composer require idct/adminata sonata-project/doctrine-orm-admin-bundle`; Flex
auto-registers `Sonata\AdminBundle\SonataAdminBundle`; the two 10-line YAML files
(`sonata_admin.yaml` with the block context, `routes/sonata_admin.yaml`) are documented.
Medium term: contribute an `idct/adminata` recipe to `symfony/recipes-contrib` (owner).
Existing app: see document 10 §1 (the `--no-plugins` path).
