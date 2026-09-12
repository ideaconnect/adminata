# 02 — The rename map

The map is the specification of `upstream/rename/`: one ordered rule file, one path-rule file,
one allow-list, one program. The tables below are what those files must contain, with the
measured counts of [appendix A](appendix-A-inventory.md) so that a reviewer can tell a complete
run from a partial one. Section 1 is the engine, 2 the token rules, 3 the context rules a plain
token cannot decide, 4 the path renames and deletions, 5 the exclusions and the gate, 6 the
verification commands.

## 1. The engine

```
upstream/rename/
├── rules.txt        ordered content rules: <regex> <tab> <replacement> [<tab> <file-glob>]
├── paths.txt        path exceptions (paths otherwise take the content rules, §4.1)
├── known.txt        generated: every name adminata, the ORM layer and the MongoDB fork own
├── build-lists.php  regenerates known.txt from the trees (run in R0-01, and on every sync)
├── allow.txt        files the engine and the gate skip; phrases the gate allows (section 5)
├── apply.php        the program
└── README.md        how to run it, what a rule may look like, how to add one
```

`php upstream/rename/apply.php` — also installed into an application as `vendor/bin/adminata-rename`
through `composer.json`'s `bin` — has these modes:

| Mode | Does |
|---|---|
| `--tree <dir>` | applies the path rules with `git mv` (or `mv` outside a repository) and `rules.txt` to every text file under `<dir>` (text = no NUL byte, whatever the extension), skipping `allow.txt`, `.git`, `vendor`, `node_modules`, `var`, binaries. Idempotent: a second run changes nothing (Accept line of R0-02). |
| `--app <dir>` | `--tree` for an application: only the **known lists** apply (below); every other `sonata…` token is printed as the application's own name with file and line, and left alone. |
| `--dry-run` | with either: print the would-be diff and the report, write nothing. |
| `--stdin` | rewrites text from standard input (used by `upstream/sync.sh` on file contents, N16) |
| `--path <p>` | prints the renamed path (used by `upstream/sync.sh` on patch headers) |
| `--check <dir>` | the gate (N20): exits non-zero and lists every match of any rule's left-hand side outside `allow.txt`, with file and line. `make check-names` = `--check .`; with `--app`, only the known lists count. |

Rules are PCRE, applied in file order, each over the whole file content, and each may be limited
to a file glob (`*.twig`, `*.rst,*.md,*.yaml,*.yml`). Backslash runs are matched agnostically:
a rule written against `Sonata\AdminBundle\` is compiled so that `\`, `\\` and `\\\\` (PHP
source, JSON/YAML strings, PHPStan baseline regexes) all match, and the run length seen is the run
length emitted.

**Known lists and catch-alls.** The lower-case families (rules 26–34) exist in two forms. The
*known list* enumerates the exact names adminata, the ORM layer and the MongoDB fork own —
`build-lists.php` generates it from `src/Resources/config` (ids, parameters, tags), the compiler
passes and event classes (tags, events), the routing files (routes), the Twig extensions
(functions, filters, globals), the templates (block names, class and id hooks), `getBlockPrefix()`
(form types), the form extensions (options), the catalogues (ids), the controllers (identifiers,
data attributes), the cookies and storage keys, and the file names of §4.1. The *catch-all* is the
regex in the table. `--tree` applies both, because on adminata's own trees every `sonata…` token
is ours (docs samples and test fixtures included); `--app` applies the known lists only, because
an application's own `sonata.admin.<x>` id, `sonata_admin_<x>` route or `sonata-<x>` class is its
own name — and a renamed admin id renames the `ROLE_*` names derived from it ([04 §4](04-consumers.md)).

Written in PHP, under PHPStan level 8 and the adminata header, with a unit suite in
`tests-adminata/Unit/Rename/` of input → expected pairs for every row marked ⚠ below.

## 2. Token rules, in order

Order matters: longer and more specific first, so that `Sonata\AdminBundle\Tests\` is not
half-eaten by `Sonata\AdminBundle\`, and the explicit route names win over the general
`sonata_admin` token. `Σ` is the order of magnitude of occurrences on 2026-09-12 in `src/`,
`tests/`, `tests-adminata/`, `assets/`, `docs/`, `bin/` and the root configs — a reviewer's
yardstick, not a count; `--check` prints the exact figure before the pass. The consumers are in
[04](04-consumers.md).

### 2.1 Namespaces and classes (N2, N4, N19)

| # | Left-hand side | Right-hand side | Notes | Σ |
|---|---|---|---|---|
| 1 | `Sonata\AdminBundle\Tests\` | `IDCT\Adminata\Tests\` | | ~600 |
| 2 | `Sonata\AdminBundle\` | `IDCT\Adminata\` | every remaining FQCN, PSR-4 key, PHPStan baseline, docs sample | ~4,500 |
| 2a–2e | `Sonata\BlockBundle\`, `Sonata\Form\`, `Sonata\Twig\` → `IDCT\Adminata\`; `Sonata\Exporter\` → `IDCT\Adminata\Exporter\`; `Sonata\Doctrine\` → `IDCT\Adminata\Doctrine\` | | the 1.0 merges, for an application coming straight from `sonata-project/*` (UPGRADE.md); nothing in adminata's trees matches. `Sonata\Doctrine\` needs its trailing backslash so that rule 4's `Sonata\DoctrineORMAdminBundle\` is not touched | app |
| 3 | `Sonata\DoctrineORMAdminBundle\Tests\` | `IDCT\Adminata\DoctrineORM\Tests\` | ORM/ and APP/ | ORM |
| 4 | `Sonata\DoctrineORMAdminBundle\` | `IDCT\Adminata\DoctrineORM\` | ORM/, APP/, adminata's docs and `tailwind.css` comment | ~100 |
| 5 | `Sonata\DoctrineMongoDBAdminBundle\Tests\` | `IDCT\Adminata\DoctrineMongoDB\Tests\` | ODM/ | ODM |
| 6 | `Sonata\DoctrineMongoDBAdminBundle\` | `IDCT\Adminata\DoctrineMongoDB\` | ODM/, APP/ | ~10 |
| 7 | `SonataDoctrineORMAdminBundle` | `AdminataDoctrineORMBundle` | bundle class, before the generic rule | ORM, APP |
| 8 | `SonataDoctrineORMAdminExtension` | `AdminataDoctrineORMExtension` | derived alias `adminata_doctrine_orm` | ORM |
| 9 | `SonataDoctrineMongoDBAdminBundle` | `AdminataDoctrineMongoDBBundle` | | ODM, APP |
| 10 | `SonataDoctrineMongoDBAdminExtension` | `AdminataDoctrineMongoDBExtension` | plus a hand-written `getAlias()` (N19) | ODM |
| 11 | `@SonataDoctrineORMAdmin/` | `@AdminataDoctrineORM/` | Twig namespace of the ORM bundle | 6 |
| 12 | `@SonataDoctrineMongoDBAdmin/` | `@AdminataDoctrineMongoDB/` | | 6 |
| 13 | `@SonataBlock/`, `@SonataForm/`, `@SonataTwig/` | `@Adminata/` | the retired aliases (N8) | 23 |
| 14 | `@SonataAdmin/` | `@Adminata/` | | 658 |
| 15 | `SonataAdmin` (followed by `[A-Z]` or end of token) | `Adminata` | `SonataAdminBundle` → `AdminataBundle` (class, bundle name, translation domain, `templates/bundles/…` path), `SonataAdminExtension` → `AdminataExtension`, `SonataAdminRuntime` → `AdminataRuntime` | ~700 |
| 16a | `SonataBlockBundle`, `SonataDoctrineBundle`, `SonataExporterBundle`, `SonataFormBundle`, `SonataTwigBundle` | *reported, not rewritten* | classes, translation domains and override directories that stopped existing in 1.0: the `bundles.php` line is deleted, the catalogue and the override directory move to `AdminataBundle` (UPGRADE.md) | app |
| 16 | `Sonata(?=[A-Z])` ⚠ | `Adminata` | `SonataConfiguration`, `SonataBlockExtension`, `SonataExporterException`, `defaultSonataDoctrineConfig`, `FormSonataFilterChoiceWidgetTest`; **not** `Sonata Project`, `Sonata Admin` (space follows) | ~150 |

⚠ Rule 16 would also rewrite the names of *other* Sonata bundles that the inherited cookbook
pages mention (`SonataUserBundle`, `SonataPageBundle`, `SonataMediaBundle`, `SonataSeoBundle`,
`SonataIntlBundle`, `@SonataIntl`, `@SonataPage`, …, 30 occurrences in `docs/`). Those are
third-party names and must not change: `allow.txt` lists them as protected phrases the engine
restores after the pass (and the gate allows). R2-02 decides page by page whether a cookbook
recipe that depends on a bundle adminata does not ship is kept at all.

### 2.2 Lower-case identifiers (N5–N13)

| # | Left-hand side | Right-hand side | Notes | Σ |
|---|---|---|---|---|
| 17 | `sonata_doctrine_orm_admin` | `adminata_doctrine_orm` | ORM config root, before the generic rules | ORM, APP |
| 18 | `sonata_doctrine_mongo_db_admin` | `adminata_doctrine_mongodb` | ODM config root | ODM, APP |
| 19 | `sonata_admin_dashboard`, `sonata_admin_search`, `sonata_admin_redirect`, `sonata_admin_retrieve_form_element`, `sonata_admin_append_form_element`, `sonata_admin_short_object_information`, `sonata_admin_set_object_field_value`, `sonata_admin_retrieve_autocomplete_items` | `adminata_<name>` | the eight routes (N7), one rule each, whole-token | 9 in src, more in docs/tests |
| 20 | `sonata_admin_sidebar` | `adminata_sidebar` | cookie (N13) | 2 |
| 21 | `sonata_admin_content` (prefix) | `adminata_content` | the Twig blocks `sonata_admin_content` and `sonata_admin_content_actions_wrappers` (N8) | src 6, APP 9 |
| 21a | `sonata.admin.twig.sonata_admin_extension`, `sonata.admin.twig.sonata_admin_runtime` | `adminata.admin.twig.adminata_extension`, `…adminata_runtime` | the two ids that carry a class name (N6) | 4 |
| 21b | `recipe_sonata_admin_without_user_bundle` | `recipe_adminata_without_user_bundle` | the cookbook page, so that its `:doc:` references and its file name agree (§4.1) | 3 |
| 22 | `sonata:admin:` | `adminata:` | the four console commands (N7) | 4 |
| 23 | `debug:sonata:block` | `debug:adminata:block` | | 1 |
| 24 | `make:sonata:admin` | `make:adminata:admin` | | 1 |
| 25 | `sonataadmin` | `adminata` | published asset path segment (N14): `bundles/sonataadmin`, the symlink | ~12 |
| 26 | `sonata_type_` | `adminata_type_` | 32 block prefixes, their `_widget`/`_row` theme blocks, two template file names (N9) | ~300 |
| 27 | `sonata_flash_` | `adminata_flash_` | flash types (N6) | ~20 |
| 28 | `_sonata_` | `_adminata_` | `_sonata_admin`, `_sonata_name`, `_sonata_csrf_token` (N7) | 16 |
| 29 | `sonata_admin` (whole token, not in a root context of §3) | `adminata_admin` | form option, form view variable, Twig global (N8, N9) | ~120 |
| 30 | `sonata_` | `adminata_` | everything else with the underscore: `sonata_block`, `sonata_form`, `sonata_twig`, `sonata_exporter`, `sonata_doctrine`, Twig functions, block names, `sonata_theme` and the sidebar cookies, `sonata_administration`, `sonata_help`, `sonata_field_description`, `sonata_config` | ~900 |
| 31 | `sonata.` (followed by `[a-z]`) ⚠ | `adminata.` | 199 ids/parameters, tags, events, six xliff ids; **not** a sentence ending in "sonata." — case-sensitive and requires a letter after the dot | ~2,600 |
| 32 | `sonata-(?!project)(?!admin-mongodb-bundle)(?!doctrine-extensions)` ⚠ | `adminata-` | the class and id hooks (65, 33 of them `ba`), the 21 Stimulus identifiers, the 73 data-attribute names, dispatched events, and the classes the 36 deferred templates still carry (`sonata-medium-date`, `sonata-tree`, …); excludes the vendor `sonata-project`, the package name of OQ2 and the upstream repository name `sonata-doctrine-extensions` | ~1,800 |
| 33 | `sonata-ba-` — ordered **before** 32 | `adminata-` | the `ba` hooks (N11); the two collisions of §3.4 are explicit rules before it | ~400 |
| 34 | `sonata(?=[A-Z])` ⚠ | `adminata` | camel case: `sonataAdmin`, `sonataConfiguration`, `sonataAutocompleteId`, `sonataApplication`, `sonataRowLinkUrl` | ~50 |

The table groups the rules by meaning; the file order is what keeps a general rule from eating a
specific one: 1–16 (namespaces and classes, in the order shown), then 17, 18, 25, 26, 27, 28,
the eight of 19, 20, 21, 22, 23, 24, the collision rules of §3.4, 33, 32, then the R-root rules
of §3.1, then 29, 30, 31, 34 — 21a and 21b go with the other explicit lower-case rules, 2a–2e
right after 2, 16a right before 16. R0-02's unit suite pins that order with one fixture that
contains every rule.

## 3. Context rules

A plain token cannot tell the configuration root `sonata_admin` (→ `adminata`) from the form
option `sonata_admin` (→ `adminata_admin`). The root has a small, enumerable set of contexts, so
the engine matches those first; everything left is the option.

### 3.1 The `sonata_admin` root (N5)

| # | Left-hand side | Right-hand side | Files | Notes |
|---|---|---|---|---|
| R-root-1 | `new TreeBuilder('sonata_admin')` | `new TreeBuilder('adminata')` | `*.php` | `Configuration.php` (hand-merged on sync anyway) |
| R-root-2 | `prependExtensionConfig('sonata_admin'`, `loadFromExtension('sonata_admin'`, `getExtensionConfig('sonata_admin'`, `hasExtension('sonata_admin'`, `->load(['sonata_admin'` | same with `'adminata'` | `*.php` | extension and its tests |
| R-root-3 | `^(\s*)sonata_admin:` | `$1adminata:` | `*.yaml,*.yml,*.rst,*.md` | a YAML root key at column 0 or inside an indented docs sample |
| R-root-4 | `sonata_admin\.(?=[a-z_]+)` | `adminata.` | every file type **except** `*.twig` | dotted config paths in prose and comments (`sonata_admin.options.x` in `.rst`, `.md`, `.php`, `.mjs`, `.css`, …); in Twig the same shape is the global `sonata_admin.adminPool` and takes rule 29. Checked on 2026-09-12: the eight dotted occurrences in `docs/` are config paths or example ids, none is the Twig global |
| R-root-5 | bare `sonata_admin` | `adminata` | `*.rst,*.md,*.yaml,*.yml` | prose and YAML name the root far more often than the option; the docs diff is read for the few option mentions in R2-02 |

The container extension's alias is not a rule: `AdminataExtension` derives `adminata` by itself.
The same five patterns exist for `sonata_doctrine_orm_admin` and `sonata_doctrine_mongo_db_admin`
in the consumers, and are covered by rules 17 and 18 since those tokens have no second meaning.

### 3.2 Twig block `sonata_admin_content` and the panel's own `sonata_admin_*` names

Rule 21 covers the block. The panel's own route `sonata_admin_edit_own_password` is the panel's
name and is the panel's choice; [04 §3](04-consumers.md) lists it.

### 3.3 The three Twig aliases

Rule 13 rewrites the references; the alias registration itself is deleted by hand
(`TwigNamespaceAliasCompilerPass` and its line in the bundle class, its test, and the
`twig.paths`-style mentions in `docs/`), R1-03.

### 3.4 The two `ba` collisions (N11)

| Left-hand side | Right-hand side | Kind | Decided in |
|---|---|---|---|
| `sonata-ba-content` | `adminata-content` | class on the content section | R1-01 after reading `standard_layout.html.twig` |
| `id="sonata-content"` | `id="adminata-content"` | id of the main content element | R1-01 |
| `sonata-ba-tabs` | `adminata-tabs` (or `adminata-tab-nav` if it is a different element from `sonata-tabs`) | class on the edit form's tab bar | R1-01 after reading `CRUD/base_edit_form.html.twig` |

The rules are added above rule 33 so the general `ba` rule never sees them.

## 4. Path rules, deletions and hand edits

### 4.1 Path renames (applied with `git mv`, and to the `diff --git` headers of a sync)

A path takes the **same content rules** as text, segment by segment, so that a reference and the
file it names always agree (`{% include '@Adminata/Menu/adminata_menu.html.twig' %}` and the file
`Menu/adminata_menu.html.twig` come out of one rule). `paths.txt` holds only the exceptions —
today none are known; the table is the expected result the unit suite checks.

| From | To |
|---|---|
| `src/SonataAdminBundle.php` | `src/AdminataBundle.php` |
| `src/SonataConfiguration.php` | `src/AdminataConfiguration.php` |
| `src/DependencyInjection/{Abstract,}SonataAdminExtension.php` | `…/{Abstract,}AdminataExtension.php` |
| `src/DependencyInjection/Sonata{Block,Form,Twig,Exporter}Extension.php` | `…/Adminata{Block,Form,Twig,Exporter}Extension.php` |
| `src/Twig/Extension/SonataAdminExtension.php`, `src/Twig/SonataAdminRuntime.php` | `…/AdminataExtension.php`, `…/AdminataRuntime.php` |
| `src/Exporter/Exception/SonataExporterException.php` | `…/AdminataExporterException.php` |
| `src/Resources/config/routing/sonata_admin.{php,xml}` | `…/adminata.{php,xml}` |
| `src/Resources/translations/SonataAdminBundle.<35 locales>.xliff` | `…/AdminataBundle.<locale>.xliff` |
| `src/Resources/views/Form/Type/sonata_type_model_{autocomplete,list}.html.twig` | `…/adminata_type_model_{autocomplete,list}.html.twig` |
| `src/Resources/views/Menu/sonata_menu.html.twig` | `…/Menu/adminata_menu.html.twig` |
| `tests/SonataAdminBundleTest.php`, `tests/SonataConfigurationTest.php`, `tests/DependencyInjection/Sonata*ExtensionTest.php`, `tests/Twig/**/SonataAdmin*Test.php`, `tests/Form/Widget/FormSonata*WidgetTest.php` | the `Adminata*` names |
| `tests/App/templates/bundles/SonataAdminBundle/` | `…/bundles/AdminataBundle/` |
| `tests-adminata/App/config/sonata.yaml` | `…/adminata.yaml` |
| `tests-adminata/App/public/bundles/sonataadmin` (symlink) | `…/bundles/adminata` |
| `tests-adminata/Contract/config-reference/sonata_{admin,block,doctrine_orm_admin,exporter,form,twig}.yaml` | `adminata.yaml`, `adminata_block.yaml`, `adminata_doctrine_orm.yaml`, `adminata_exporter.yaml`, `adminata_form.yaml`, `adminata_twig.yaml` |
| `tests-adminata/Contract/ReplaceTest.php` | `ConflictTest.php` (content rewritten by hand, N15) |
| `bin/check-replace-versions.php` | `bin/check-upstream-versions.php` (content by hand, N15) |
| `docs/admin-bundle/cookbook/recipe_sonata_admin_without_user_bundle.rst` | `recipe_adminata_without_user_bundle.rst` (rule 21b) |
| `docs/admin-bundle/cookbook/recipe_sortable_sonata_type_model.rst` | `recipe_sortable_adminata_type_model.rst` (rule 26) |
| `docs/admin-bundle/images/{sonata_inline_row,sonata_type_immutable_array,getting_started_sonata_model_type}.png` | `adminata` spellings (rules 26, 30), with their `.. figure::` references |
| `phpstan/baseline-admin-bundle.neon` | unchanged name; content by rule 2 |

### 4.2 Deleted

`src/DependencyInjection/Compiler/TwigNamespaceAliasCompilerPass.php` and its test; the
`registerExtension`-adjacent `addCompilerPass(new TwigNamespaceAliasCompilerPass())` line; the
`replace` block of `composer.json`; the `sonata` keyword.

### 4.3 Hand edits the engine cannot make (each an R1 task)

- `composer.json`: `conflict` gains `sonata-project/admin-bundle: *`; `description`, `suggest`,
  `keywords` reworded; `autoload` keys are rule 2's work but are re-read (R1-03).
- The five `Configuration` roots and the extension tests' expectations (R-root rules do the
  tokens; a person confirms `bin/console debug:config adminata` and the four others print).
- `AdminataDoctrineMongoDBExtension::getAlias()` in ODM/ (N19).
- `getNamespace()` of `AdminataExtension` and `AdminataBlockExtension`: the XML configuration
  namespaces of N15.
- `composer.json` `bin: ["bin/adminata-rename"]` (a launcher for `upstream/rename/apply.php`),
  and `.gitattributes`: `/upstream export-ignore` becomes per-file lines for `diff.sh`,
  `sync.sh`, `rehearse-sync.sh`, `exclude/`, `remotes.txt` and `merged.txt`, so that
  `upstream/rename/` ships in the Composer archive (an `export-ignore` on a directory cannot be
  undone for a child).
- `bin/write-manifests.mjs`, `vite.config.js`: rule 25 does the token; `npm run build`
  regenerates `src/Resources/public/{entrypoints,manifest}.json` (R1-05).
- The contract suites: `hooks.yaml`, `config-reference/*.yaml`, `deferred-templates.txt`,
  `assets/css/contract.json`, `assets/js/__contract__/controllers.json`,
  `tests-adminata/Visual/support/findings.json` — regenerated by their generators where one
  exists, engine-rewritten and re-read where not (R1-04).
- The JS fixtures under `tests-adminata/fixtures/` (2,347 `.html` hits): `make js-fixtures`
  regenerates them from the renamed templates; nothing is hand-edited there (R1-05).
- `README.md`, `AGENTS.md`, `CONTRIBUTING.md`, `UPSTREAM.md`, `UPGRADE-1.0.md`, `MIGRATION.md`,
  `CHANGELOG.md`, `docs/**/*.rst` prose, `.github/ISSUE_TEMPLATE/*`,
  `.github/PULL_REQUEST_TEMPLATE.md`, `.github/workflows/mongo-compat.yaml`
  ([03](03-attribution-and-history.md), R2).

## 5. Exclusions and the gate (N20)

`allow.txt` has two parts.

**Skipped files** (the engine never rewrites them, the gate never scans them): `LICENSE`,
`NOTICE`, `src/Resources/meta/LICENSE`, `UPSTREAM.md`, `CHANGELOG.md`, `CHANGELOG-sonata.md`,
`changelog/**`, `MIGRATION.md`, `UPGRADE.md`, `UPGRADE-*.md`, `docs/upgrading.rst`, `PLAN/**`,
`PROJECT_PLAN.md`, `upstream/remotes.txt`, `upstream/merged.txt`, `upstream/exclude/**`,
`upstream/rename/**` (the rule file names the old tokens), `.git/**`, `vendor/**`,
`node_modules/**`, `var/**`, `.review-shots/**`, `*.cache`, `composer.lock`, `package-lock.json`,
binaries. `PLAN/v2/**` is skipped too: it is this plan.

**Allowed phrases** (the engine leaves them, the gate does not count them): `sonata-project`,
`sonata-project.org`, `sonata-admin-mongodb-bundle` (until OQ2 renames it, then removed from the
list and from the tree), `sonata-doctrine-extensions` (the upstream repository name),
`Sonata Project`, `Sonata Admin`, `Sonata Community`, `Sonata` followed by a space or
punctuation (prose), and the foreign bundle names of §2.1 ⚠.

The gate is `php upstream/rename/apply.php --check .`, wired as `make check-names`, added to
`make lint` and to AGENTS.md §5. It is also run against `ORM/`, `ODM/` and `APP/` at the end of
their tasks, with their own `allow.txt` additions (each repository's `CHANGELOG.md`, `UPGRADE-*.md`
and `MIGRATION.md`).

## 6. Verification commands

```bash
# The mechanical commit is reproducible (R1-02 Accept):
git stash -u; git checkout <parent>; php upstream/rename/apply.php --tree .; git diff <mechanical-commit> --stat | tail -1   # prints "0 files changed" or nothing

# Nothing left (R1 exit gate):
make check-names

# Symfony derives what N3 says it derives:
bin/console debug:twig | grep -E '^\s*@Adminata'                # one namespace, no @Sonata*
bin/console debug:config adminata >/dev/null && bin/console debug:config adminata_block >/dev/null
bin/console debug:router | grep -c '^\s*adminata_'               # 8
bin/console debug:container --tag=adminata.admin | grep -c adminata.admin   # the demo's admins
bin/console assets:install var/assets-check && test -d var/assets-check/bundles/adminata
bin/console debug:translation en --domain=AdminataBundle | grep -c missing  # 0 (after --only-missing)
bin/console list adminata                                        # four commands
bin/console debug:twig | grep -E 'adminata_(block_render|flashmessages_get|status_class|theme|html_dir)'

# Hooks and controllers:
grep -rhoE '\b(sonata|adminata)-[a-z0-9-]+' src/Resources/views assets | grep -c '^sonata-'   # 0
node bin/build-js-contract.mjs && git diff --exit-code assets/js/__contract__/controllers.json

# The consumers still resolve (04 §5):
composer why-not sonata-project/admin-bundle     # conflict, not replace

# An application, before and after (UPGRADE.md §10):
vendor/bin/adminata-rename --app --dry-run .     # the diff it would make, and the names it leaves to you
vendor/bin/adminata-rename --check --app .       # nothing of adminata's left
```
