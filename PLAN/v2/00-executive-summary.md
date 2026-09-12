# 00 — Executive summary

## Goal

adminata stops naming itself after Sonata. The bundle becomes `IDCT\Adminata\AdminataBundle`, and
every identifier an application, a storage layer or a template touches follows: `@Adminata`,
`adminata:` and `adminata_{block,form,twig,exporter}:` configuration roots, `adminata.*` service
ids and tags, `adminata_*` routes, Twig functions and block names, `adminata_type_*` form types,
the `AdminataBundle` translation domain, `adminata-*` markup hooks and Stimulus controllers,
`adminata_*` cookies, `public/bundles/adminata/`. The two storage layers and the production panel
are renamed with it. The git history, the upstream file headers, `NOTICE`, `LICENSE`,
`UPSTREAM.md`, `CHANGELOG-sonata.md` and `changelog/` are untouched, and `README.md`, the
documentation site and `composer.json` say plainly what was forked from whom and that we are
grateful for it.

## What changes

| Surface | Today | After | Decision |
|---|---|---|---|
| PHP namespace | `Sonata\AdminBundle\` → `src/` | `IDCT\Adminata\` → `src/` | N2 |
| Bundle class | `Sonata\AdminBundle\SonataAdminBundle` | `IDCT\Adminata\AdminataBundle` | N3 |
| Classes named after Sonata (10 in `src/`, 12 in `tests/`) | `SonataAdminExtension`, `SonataConfiguration`, … | `AdminataExtension`, `AdminataConfiguration`, … | N4 |
| Config roots (5) | `sonata_admin`, `sonata_block`, `sonata_form`, `sonata_twig`, `sonata_exporter` | `adminata`, `adminata_block`, `adminata_form`, `adminata_twig`, `adminata_exporter` | N5 |
| Service ids, parameters, tags, events, flash types | `sonata.admin.pool`, `sonata.admin`, `sonata.admin.event.*`, `sonata_flash_*` | `adminata.admin.pool`, `adminata.admin`, `adminata.admin.event.*`, `adminata_flash_*` | N6 |
| Routes (8), request attributes (3), console commands (6) | `sonata_admin_dashboard`, `_sonata_admin`, `sonata:admin:list` | `adminata_dashboard`, `_adminata_admin`, `adminata:list` | N7 |
| Twig namespace, aliases, functions, globals, block names | `@SonataAdmin` (+ `@SonataBlock`, `@SonataForm`, `@SonataTwig`), `sonata_block_render`, `sonata_config`, `sonata_wrapper` (51 block names) | `@Adminata` (aliases gone), `adminata_block_render`, `adminata_config`, `adminata_wrapper` | N8 |
| Form type prefixes (32) and options | `sonata_type_model`, `sonata_help` | `adminata_type_model`, `adminata_help` | N9 |
| Translation domain (35 catalogues) and 6 ids | `SonataAdminBundle`, `sonata_administration` | `AdminataBundle`, `adminata_administration` | N10 |
| Markup hooks (65 class and id tokens, 33 of them `ba`) | `sonata-ba-list-field`, `sonata-actions`, `#sonata-content` | `adminata-list-field`, `adminata-actions`, `#adminata-content` | N11 |
| Stimulus identifiers (21), data attributes, events, JS global | `sonata-modal`, `data-sonata-modal-target`, `sonata-modal:opened`, `window.sonataApplication` | `adminata-modal`, `data-adminata-modal-target`, `adminata-modal:opened`, `window.adminataApplication` | N12 |
| Cookies (2), the sidebar's localStorage key, the KnpMenu alias | `sonata_theme`, `sonata_sidebar_hide`, `sonata_sidebar_open`, `sonata_admin_sidebar` | `adminata_theme`, `adminata_sidebar_hide`, `adminata_sidebar_open`, `adminata_sidebar` | N13 |
| Published assets | `public/bundles/sonataadmin/` | `public/bundles/adminata/` | N14 |
| Composer identity | `replace: sonata-project/admin-bundle 4.43.0`, keyword `sonata` | `conflict: sonata-project/admin-bundle: *` (six conflicts, no replace), keyword gone | N15 |
| XML configuration namespaces | `https://sonata-project.org/schema/dic/admin`, `http://sonata-project.com/schema/dic/block` | `https://idct.tech/schema/dic/adminata`, `…/adminata_block` | N15 |
| ORM layer | `Sonata\DoctrineORMAdminBundle\`, `SonataDoctrineORMAdminBundle`, `sonata_doctrine_orm_admin` | `IDCT\Adminata\DoctrineORM\`, `AdminataDoctrineORMBundle`, `adminata_doctrine_orm`; v2.0.0 | N19, [04](04-consumers.md) |
| MongoDB fork | `Sonata\DoctrineMongoDBAdminBundle\`, `idct/sonata-admin-mongodb-bundle` 6.x | `IDCT\Adminata\DoctrineMongoDB\`, package name per OQ2; v7.0.0 | N19, [04](04-consumers.md) |
| recomaty-panel | 74 PHP files, 4 config roots, 16 template overrides, 44 admin tags, ~80 hook usages | migrated on a branch, round 2 of MIGRATION.md; its own admin ids, and the 73 `ROLE_*` names derived from them, are kept | [04](04-consumers.md) |

## What stays

- The git history: every subtree import, every upstream remote and private tag ref, every commit.
  Renames go through `git mv` so `git log --follow` crosses them (N18).
- The inherited file headers, verbatim: "This file is part of the Sonata Project package. (c)
  Thomas Rabaix" on upstream files, "Forked from the Sonata Project" in adminata's combined header
  (N17). They are the copyright notice MIT requires us to keep.
- `NOTICE`, `LICENSE`, `UPSTREAM.md`, `CHANGELOG-sonata.md`, `changelog/`, `upstream/remotes.txt`
  and `upstream/merged.txt`: provenance, edited only where they state something that stops being
  true (N17).
- `composer.json` `authors`: Thomas Rabaix and the Sonata Community stay listed as original
  authors (N15).
- Prose that names the origin: "a hard fork of Sonata Admin", "sonata-project/admin-bundle
  4.43.0". What goes is the *identifier*, never the acknowledgement ([03](03-attribution-and-history.md)).
- The PHP design. Every class keeps its shape, methods and semantics; only names move. The 1.0
  contract of PLAN/02 is re-issued under the new names, not weakened.
- `PLAN/` and `PROJECT_PLAN.md` as they are: the record of how 1.0 was built, Sonata names
  included.

## The principle that makes it mechanical

Three kinds of text, three treatments ([02 §1](02-rename-map.md)):

1. **Identifier-shaped tokens** — `Sonata\AdminBundle\`, `SonataAdminBundle`, `sonata_admin`,
   `sonata.admin.pool`, `sonata-ba-list-field`, `@SonataAdmin/`, `sonata:admin:list` — are
   rewritten by an ordered rule file applied by one program, `upstream/rename/apply.php`. Rules
   are anchored so that `sonata-project`, `thomas.rabaix@sonata-project.org` and the package
   name of the MongoDB fork are never touched.
2. **Prose** — "Sonata Admin", "the Sonata Project", "forked from" — is never rewritten by the
   engine. Where a sentence describes adminata's *current* identity with a Sonata name, a person
   rewrites it; where it describes the origin, it stays.
3. **Attribution and history files** are skipped by the engine and by the gate.

The same rule file then serves three more times: run against the ORM layer, the MongoDB fork and
the panel, and — for as long as adminata syncs `admin-bundle` from upstream — to translate every
upstream release before it is merged (N16): the sync becomes a three-way merge of our file, the
*translated* upstream base and the *translated* upstream head, so a Sonata-named upstream patch
lands on IDCT-named sources without hand work beyond what it needs today.

On an application the engine runs in `--app` mode: it renames only the names adminata, the ORM
layer and the MongoDB fork own — from lists generated out of their trees — and reports, without
touching, the application's own `sonata…` names. A service id renamed in an application renames
the `ROLE_*` names the security handlers derive from it, which is a data migration and not a
rename ([04 §4](04-consumers.md)).

## Approach in one paragraph

Build the engine and its gate first, with a unit suite of the tricky cases (R0). Run it once over
adminata and commit the result as **one mechanical commit** that a reviewer can reproduce by
re-running the engine on its parent — the proof that the rule file is complete (R1). Then the hand
work in separate commits: the config roots and Twig aliases that a rule cannot decide, the
compiler pass that dies, the deleted `replace`, the contract suites re-based on the new names, the
rebuilt assets, the documentation prose, `README.md`'s Origins section, `AGENTS.md`, the upgrade
guide (R1–R2). Every gate of AGENTS.md §5 plus `make check-names` green before the milestone push.
Then the ORM layer on a paired branch — adminata's own suites need it — tagged 2.0.0 (R3); the
MongoDB fork, tagged 7.0.0 under whatever name OQ2 settles (R4); the panel's second migration
round on a branch, reviewed the way P5 was, and merged when the owner signs it off (R5); release
bookkeeping (R6).

## Order and effort

| Phase | What | Size |
|---|---|---|
| R0 | Engine, rule file, known-name lists and `--app` mode, gate, translated three-way sync, unit suite | 2 days |
| R1 | adminata: mechanical commit, hand commits, contracts, assets, suites green | 2 days |
| R2 | adminata: README Origins, docs pass, AGENTS.md, UPGRADE/MIGRATION, CHANGELOG, CI, milestone push | 1 day |
| R3 | ORM layer: rename, suites, v2.0.0; adminata's require-dev follows | 0.5 day |
| R4 | MongoDB fork: rename, package name, suites, v7.0.0; mongo-compat job | 1 day |
| R5 | recomaty-panel: round 2, review on the owner's server, merge | 1.5 days + review rounds |
| R6 | Release bookkeeping: tags, stale rc1, GitHub metadata, memory | 0.5 day |

About nine working days for one engineer who knows the tree, plus the owner's review rounds on
the panel. The critical path is R0 → R1 → R3 → R5; R4 runs beside R3.

## Not in this chapter

Folding the five configuration roots into one `adminata:` tree (OQ1 — a follow-up that touches
only the DI layer once the names are done); merging `tests-adminata/` into `tests/`; restructuring
`docs/admin-bundle/`; renaming the GitHub organisation or the `idct/` Composer vendor; touching
anything the engine does not name.
