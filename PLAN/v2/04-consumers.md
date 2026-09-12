# 04 — Consumers: the ORM layer, the MongoDB fork, recomaty-panel

Three repositories speak adminata's names. Each is renamed with the same engine, in the order the
dependencies dictate, and each keeps its own attribution files the way [03](03-attribution-and-history.md)
keeps adminata's. Counts are from [appendix A §3](appendix-A-inventory.md).

## 1. Order and the dependency loop

adminata's own suites and demo application require the ORM layer (`require-dev`
`idct/adminata-doctrine-orm-admin-bundle`, resolved from the sibling checkout through the `path`
repository or from GitHub); the ORM layer requires adminata (`dev-main`); the MongoDB fork
requires adminata; the panel requires all three. So:

```
R0 engine ──► R1 adminata (green only with the renamed ORM beside it: paired branches)
                 │
                 ├──► R3 ORM: merge, tag 2.0.0; adminata require-dev ^2.0; adminata merges
                 │
                 ├──► R4 ODM: branch against adminata main; tag 7.0.0; mongo-compat job follows
                 │
                 └──► R5 panel: round 2 on a branch, lock pinned to the merged commits
```

Paired branches: `rename/idct` in adminata and `rename/idct` in ORM/, both green locally through
the `path` repository (`../{adminata-doctrine-orm-admin-bundle}` already in adminata's
`composer.json`). CI has no sibling checkout, so during R1 both branches name each other:
adminata's `require-dev` says `idct/adminata-doctrine-orm-admin-bundle: dev-rename/idct`, the ORM
branch's `require` says `idct/adminata: dev-rename/idct`, and the ORM branch is pushed first. The
loop is untied in this order (R3-03, R3-04): adminata merges to `main` still naming the ORM
branch; the ORM branch switches to `idct/adminata: dev-main` (now IDCT-named), merges and tags
2.0.0; adminata's `require-dev` becomes `^2.0` in the milestone's last commit. The MongoDB fork's
`7.x` branch does the same dance against adminata's branch, and adminata's `mongo-compat.yaml`
names that branch until 7.0.0 is tagged. The lock rules that cost a day in September apply
unchanged: a committed lock must resolve from GitHub, never from a `path` dist.

## 2. `ORM/` — `idct/adminata-doctrine-orm-admin-bundle` → v2.0.0

| Surface | Today | After |
|---|---|---|
| Namespace | `Sonata\DoctrineORMAdminBundle\` (+ `…\Tests\`) | `IDCT\Adminata\DoctrineORM\` (+ `…\Tests\`) |
| Bundle class | `SonataDoctrineORMAdminBundle` | `AdminataDoctrineORMBundle` |
| Extension, alias, root | `SonataDoctrineORMAdminExtension`, `sonata_doctrine_orm_admin` | `AdminataDoctrineORMExtension`, `adminata_doctrine_orm` (derived) |
| Twig namespace | `@SonataDoctrineORMAdmin` (6 refs) | `@AdminataDoctrineORM` |
| Service ids | `sonata.admin.manager.orm`, `sonata.admin.doctrine_orm.*`, … (63 `sonata.admin` + 1 `sonata.admin_doctrine_orm`) | `adminata.admin.…` by rule 31 |
| References into adminata | 79 of 171 PHP files name `Sonata\AdminBundle\`; 29 `@SonataAdmin` | rules 2 and 14 |
| Composer | `replace: sonata-project/doctrine-orm-admin-bundle 4.21.0`; `require idct/adminata dev-main` | `conflict: sonata-project/doctrine-orm-admin-bundle: *`; `require idct/adminata dev-rename/idct` on the branch, `dev-main` again at the merge (§1; adminata stays untagged, N1) |
| Package name | `idct/adminata-doctrine-orm-admin-bundle` | unchanged |
| Version | v1.0.0 (+3 CI-only commits) | **2.0.0**; `extra.branch-alias.dev-main: 2.x-dev` |
| Docs, README, NOTICE, UPSTREAM.md, CHANGELOG | its own | same treatment as [03](03-attribution-and-history.md): headers, NOTICE, UPSTREAM untouched but for the namespace sentence; README says "a hard fork of `sonata-project/doctrine-orm-admin-bundle` 4.21.0"; CHANGELOG entry with the last Sonata-named sha |

Tasks: R3-01 (engine run, `git mv`, hand edits, its gates green against the adminata branch),
R3-02 (docs and attribution), R3-03 (merge, tag, adminata's `require-dev` to `^2.0`). Its
`upstream/` tooling, if it has one of its own, gets the same translated three-way sync (N16) —
the engine and rule file are copied, not re-derived; the rules that matter to it are 3, 4, 7, 8,
11, 17 and the generic ones.

## 3. `ODM/` — `idct/sonata-admin-mongodb-bundle` 6.x → 7.0.0 (name per OQ2)

| Surface | Today | After |
|---|---|---|
| Namespace | `Sonata\DoctrineMongoDBAdminBundle\` (+ `…\Tests\`) | `IDCT\Adminata\DoctrineMongoDB\` (+ `…\Tests\`) |
| Bundle class | `SonataDoctrineMongoDBAdminBundle` | `AdminataDoctrineMongoDBBundle` |
| Extension, alias, root | `SonataDoctrineMongoDBAdminExtension`, `sonata_doctrine_mongo_db_admin` | `AdminataDoctrineMongoDBExtension` with `getAlias(): 'adminata_doctrine_mongodb'` (the derived alias would be `adminata_doctrine_mongo_db`) |
| Twig namespace | `@SonataDoctrineMongoDBAdmin` (6 refs) | `@AdminataDoctrineMongoDB` |
| Form themes and `ListBuilder` (the coupling AGENTS.md §4 names) | extend `@SonataAdmin/Form/{form,filter}_admin_fields.html.twig`; hard-code `@SonataAdmin/CRUD/list__action*.html.twig` | `@Adminata/…` by rule 14; the `sonata_type_*` theme blocks by rule 26 |
| References into adminata | 55 of 106 PHP files; 54 `@SonataAdmin` | rules 2 and 14 |
| Composer | no `replace`; `require idct/adminata dev-main` | `conflict: sonata-project/doctrine-mongodb-admin-bundle: *` (it provides that API under another name now, like adminata does for its six); `require idct/adminata dev-main` |
| Package and repository name | `idct/sonata-admin-mongodb-bundle`, `ideaconnect/sonata-admin-mongodb-bundle`, on Packagist | **OQ2**, default: `idct/adminata-admin-mongodb-bundle`, `ideaconnect/adminata-admin-mongodb-bundle`; the old Packagist package marked abandoned with the new one as replacement; GitHub redirects the old URL |
| Version | v6.0.0 on `6.x` | **7.0.0** on `7.x` (branch alias `dev-7.x: 7.x-dev`); 6.x stays as the Sonata-named line and is not maintained |
| Its `AGENTS.md`, `README.md`, `UPGRADE-6.0.md`, badges | its own | rewritten to the new names; a new `UPGRADE-7.0.md` with the map; badges follow the package name; `BEST_VERSION.md` and `WAR_AGAINST_THE_MUTANTS.md` are its history and stay |

The owner's directive of 2026-09-04 (#7) said the fork "must keep working"; 7.0.0 is how it
keeps working. The package rename is the owner's account work (Packagist submission, abandonment
notice, GitHub rename) and sits in R4-03 as an owner action with the developer steps around it.
If OQ2 is answered "keep the name", R4-03 is skipped and `sonata-admin-mongodb-bundle` stays on
the allowed-phrase list for good.

adminata's side of R4: `.github/workflows/mongo-compat.yaml` (`FORK_REPOSITORY`, the checked-out
branch `7.x`), `assets/css/tailwind.css`'s `@source '../../../<package dir>/src/Resources/views'`,
`composer.json` `suggest`, `README.md`'s storage-layer table, `.php-cs-fixer.rules.php`'s
comment, `docs/index.rst`.

## 4. `APP/` — recomaty-panel, round 2

The panel is private, on `develop`, and pins adminata's commit through its lock (`dev-main`),
ORM `^1.0`, ODM `^6.0`. Round 1 (MIGRATION.md) moved it from Sonata to adminata in thirteen
commits; round 2 moves it to the IDCT names on a branch `adminata-idct` off `develop`, reviewed
the way P5 was, and merged when the owner signs it off.

| What | Measured | How |
|---|---|---|
| `composer.json` | `idct/adminata: dev-main`, ORM `^1.0`, ODM `^6.0` | ORM `^2.0`, ODM `^7.0` under its new name (or `^7.0` under the old), `composer update idct/*` against the merged commits; the lock committed from GitHub resolution |
| `config/bundles.php` | 3 Sonata lines | `IDCT\Adminata\AdminataBundle`, `IDCT\Adminata\DoctrineORM\AdminataDoctrineORMBundle`, `IDCT\Adminata\DoctrineMongoDB\AdminataDoctrineMongoDBBundle` |
| Config roots | `config/packages/sonata_admin.yaml`, `sonata_block.yaml`, `sonata_form.yaml`, `sonata_doctrine_orm_admin.yaml` (4 roots) | files renamed to `adminata.yaml`, `adminata_block.yaml`, `adminata_form.yaml`, `adminata_doctrine_orm.yaml`; keys by the R-root rules and 17; the ODM root if it has one |
| Routes | `config/routes/sonata_admin.yaml` | `adminata.yaml` with `@AdminataBundle/Resources/config/routing/adminata.xml` |
| Admin classes and services | 74 of 701 PHP files name `Sonata\…` (265 `Sonata\AdminBundle`, 92 `Sonata\DoctrineORMAdminBundle`, 7 `Sonata\DoctrineMongoDBAdminBundle`); 44 `sonata.admin` tags, 3 `sonata.admin.request.fetcher`, 2 `sonata.admin.extension` | rules 2, 4, 6 and the known lists (`--app`): the tags and adminata's ids change. The panel's own 30 ids (`sonata.admin.user.partner`, …) are **kept**: `sonata_admin.security.handler` is `sonata.admin.security.handler.role`, which derives `ROLE_<CODE>_<PERMISSION>` from each admin code, and 73 distinct `ROLE_SONATA_ADMIN_*` names live in `security.yaml`'s hierarchy, in admin classes, in templates and in migrations — renaming the ids is a data migration of users' roles (OQ5) |
| Names the panel persists | `ROLE_SONATA_ADMIN_*` (73) in the database's user roles and in migrations; session keys `<code>.filter.parameters` for persisted filters | untouched while the ids are kept; if OQ5 is ever flipped: a migration that rewrites the roles column and `security.yaml` in one deploy, and persisted filters reset once |
| Templates | 16 files under `templates/bundles/SonataAdminBundle/`; 41 `@SonataAdmin`; 14 overridden block names (`sonata_admin_content` ×9, `sonata_wrapper`, `sonata_left_side`, `sonata_breadcrumb`, `sonata_form_actions`, `sonata_head_title`, `sonata_page_content_header`, `sonata_pre_fieldsets`, `sonata_post_fieldsets`, `sonata_top_nav_menu_add_block`, `sonata_user_login_*`); 8 `sonata_config` | directory `git mv`'d to `templates/bundles/AdminataBundle/`; rules 14, 21, 29, 30 |
| Hooks in the panel's own CSS and templates | ~80 usages: `sonata-ba-list-field` (39), `sonata-dropdown` (7), `sonata-modal-trigger` (6), `sonata-ba-form` (6), `sonata-ba-collapsed-fields` (6), `sonata-ba-list` (5), `sonata-ba-field*` (20), `sonata-action-element` (3), `sonata-action-btn` (3), `sonata-overrides` (3, the panel's own) | the known hook list (rules 33 and 32 in `--app` mode); `sonata-overrides` is reported, not rewritten, and the panel decides |
| Flash types and routes in PHP | `sonata_flash_error` (10), `sonata_flash_success` (6), `sonata_flash_info` (6); `sonata_admin_redirect` (3), `sonata_admin_dashboard` (2); `sonata_admin_edit_own_password` (1, the panel's own route) | rules 27 and 19; the panel's own route is reported, not rewritten |
| Translations | none named `SonataAdminBundle` | nothing to move |
| `symfony.lock` | `idct/adminata`, `idct/adminata-doctrine-orm-admin-bundle`, `idct/sonata-admin-mongodb-bundle` | the ODM entry follows OQ2; Flex has no recipe for any of the three, so no `unconfigure` runs and the `--no-plugins --no-scripts` precaution of round 1 is not needed |
| The panel's Tailwind entry | `@import "@idct/adminata"` plus `@source` lines | unchanged; the linked `node_modules/@idct/adminata` is the same package |
| Cookies | users' `sonata_theme` / sidebar cookies | reset once (N13); no data migration |

The pass is `vendor/bin/adminata-rename --app --dry-run .` read in full, then `--app .`, then the
hand edits above, in that order — the report of the dry run is the list of the panel's own names
to decide about before anything is rewritten.

Review: the same tooling as P5 (memory: review server on 9078, the Playwright container,
`var/review.mjs`, the owner's own server on 8000), both themes, the pages that carry panel-side
hook overrides first (lists with `sonata-ba-list-field` styling, the collapsed-fields forms, the
white-label pages). The visual ledger must be empty of hook-related findings before the branch is
offered for merge. `MIGRATION.md` round 2 is appended with the measured figures (files, lines,
commits) the way round 1 was.

## 5. Composer facts to keep in mind

- `conflict` on `sonata-project/*` in all three packages means an application cannot have any
  `sonata-project` bundle beside them; `sonata-project/entity-audit-bundle` requires none and is
  unaffected.
- A `path` repository whose wildcard matches nothing is skipped only when the directory before
  the wildcard exists; `../{name}` from a repository root is safe.
- `^1.0@dev` expands to `>=1.0.0-dev`; with `v1.0.0-rc1` deleted (N1) it no longer resolves to
  the stale tag, but consumers keep pinning `dev-main` until adminata is tagged.
- Consumers ignore a dependency's `repositories`; the panel names its three `vcs` repositories
  itself, and the ODM's URL changes there if OQ2 renames the repository.
