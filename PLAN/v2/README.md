# adminata — plan v2: the IDCT identity (2026-09-12)

This is the second plan of the project. The first, [PLAN/](../README.md) (v3 of 2026-09-04 with
its amendments) and its task list [PROJECT_PLAN.md](../../PROJECT_PLAN.md), built adminata 1.0:
seven `sonata-project` packages forked into one bundle with a Tailwind CSS v4 / TailAdmin user
interface, green through milestone M6 and running a 46-admin production panel. That plan kept
every Sonata name on purpose — namespaces, bundle class, config roots, service ids, routes, Twig
namespaces, translation domain, markup hooks — so that the PHP side of an application carried over
untouched.

Plan v2 changes that. "v2" is the plan's number, not the package's: the first chapter lands before
adminata's first tag ([01](01-naming-decisions.md) N1), so there is no adminata 1.x to break.

## Owner directive (2026-09-12)

> Create a plan […] where first of all we drop any "sonata" references going towards naming
> Adminata under IDCT namespace (`IDCT\Adminata\`) yet keep in history, readme etc. references
> that it was once sonata and that we are grateful for the original project.

Read as three rules, which every document below applies:

1. **Nothing that names adminata says Sonata.** PHP namespaces and classes, the bundle, config
   roots, service ids, tags, parameters, events, routes, request attributes, console commands,
   Twig namespaces, functions, globals and block names, form type prefixes and options,
   translation domain and ids, markup hooks, Stimulus identifiers, data attributes, cookies, the
   published asset path, the npm and Composer metadata that describe the package.
2. **The history stays.** No git history is rewritten, the subtree imports and upstream remotes
   stay, the inherited file headers stay verbatim, and `NOTICE`, `LICENSE`, `UPSTREAM.md`,
   `CHANGELOG-sonata.md` and `changelog/` keep recording what was forked from where.
3. **The gratitude is written down.** `README.md`, the documentation site and `composer.json`
   name the Sonata Project, its authors and the seven packages, and say what adminata owes them.

"First of all" makes the rename chapter one of v2. Later chapters get documents of their own,
numbered from 06 on; nothing here presumes what they are.

## Reading order

| # | Document | What it answers |
|---|---|---|
| 0 | [00-executive-summary.md](00-executive-summary.md) | What changes, what stays, how, in what order, at what cost |
| 1 | [01-naming-decisions.md](01-naming-decisions.md) | Every naming decision (N1–N22) with rationale, alternatives and status |
| 2 | [02-rename-map.md](02-rename-map.md) | The complete old → new map per surface, the ordered rule file that performs it, exclusions and collisions |
| 3 | [03-attribution-and-history.md](03-attribution-and-history.md) | What is kept where, the README and docs "Origins" text, what the gate allows |
| 4 | [04-consumers.md](04-consumers.md) | The ORM layer, the MongoDB fork and recomaty-panel: their own renames, versions, order and Composer rules |
| 5 | [05-execution-plan.md](05-execution-plan.md) | Phases R0–R6 as PROJECT_PLAN tasks with accept criteria, exit gates, risks, open questions, effort |
| A | [appendix-A-inventory.md](appendix-A-inventory.md) | The measured inventory of 2026-09-12 that the "nothing left" gate is checked against |

## Conventions

- Decisions are `N1`, `N2`, … (naming); tasks are `R0-01`, `R1-02`, … (rename); open questions
  are `OQ1`, …. They do not collide with the `P`-ids of PLAN/ and PROJECT_PLAN.md.
- Path aliases: `ORM/` = `/home/bartosz/dev/idct/adminata-doctrine-orm-admin-bundle`
  (`idct/adminata-doctrine-orm-admin-bundle`, `main`, v1.0.0); `ODM/` =
  `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle` (`idct/sonata-admin-mongodb-bundle`,
  `6.x`, v6.0.0); `APP/` = `/home/bartosz/dev/r3/recomaty-panel-clean` (`develop`, on adminata
  `dev-main`, ORM `^1.0`, ODM `^6.0`); `S/` = upstream `sonata-project/admin-bundle` 4.43.0.
- "The engine" is `upstream/rename/apply.php` of [02 §1](02-rename-map.md): the one program that
  performs the rename, re-runs it on the consumers, and translates every future upstream diff.
  "The gate" is `make check-names` ([01](01-naming-decisions.md) N20): no left-hand side of the
  rule file may remain outside the allow-list.

## How this plan is worked

- Approval of this plan by the owner is the go. The tasks of [05](05-execution-plan.md) are then
  copied into `PROJECT_PLAN.md` as **milestone M7 — The IDCT identity (PLAN/v2)**, and worked the
  way AGENTS.md §8 says: top to bottom, `depends:` respected, ticked only when every **Accept**
  line passes, status log appended.
- The plan documents are not rewritten when a decision changes: the affected decision is marked
  superseded and the new one added with the next id, as PLAN/README.md does.
- Documents 00–05 are written against the inventory of appendix A. A number in the text that
  disagrees with a fresh run of the appendix's commands is a sign the tree moved, not the plan.

## Review log

- 2026-09-12, second pass against the tree: `sonata_help` does not exist in this fork (dropped
  from N9); `sonata_admin_sidebar` is the KnpMenu alias and `sonata_sidebar_open` a localStorage
  key, not cookies (N13); 51 Twig block names carry the prefix, not 10 (N8, appendix); 65 class
  and id hooks, not 203 (N11); two more path renames and a third explicit rule so that a page's
  file name and its `:doc:` references agree (02 §4.1); the two XML configuration namespaces
  (N15); `sonata-doctrine-extensions` protected from the hyphen rule (02 §2.2). Found and fixed
  in the design: an application's admin ids feed the `ROLE_*` names of both security handlers,
  so the engine gets an `--app` mode that renames only known names (02 §1) and the panel keeps
  its 30 ids and 73 roles (OQ5 flipped to *keep*); the adminata ↔ ORM dependency loop needed a
  stated order (04 §1, R3-03/R3-04); the rename reorders every sorted `use` block, so the
  mechanical pass is two reproducible commits and the upstream sync normalises both translated
  sides with php-cs-fixer first (R1-02, R0-04); `/upstream export-ignore` would have kept the
  engine out of the Composer archive (R2-05). `UPGRADE.md` written (03 §2.5).

## Status

- 2026-09-12, evening: **executed.** adminata `main` at `e76bd3fb9` (the mechanical pass is
  `f141c1105`, the last Sonata-named commit `d76c4818f`); ORM layer v2.0.0; MongoDB layer v7.0.0 as
  `idct/adminata-admin-mongodb-bundle`; recomaty-panel PR #492 (`adminata-idct`) awaiting the
  owner's merge. What differed from the plan is in PROJECT_PLAN.md milestone M7.

- 2026-09-12: plan written from a measured inventory (appendix A), reviewed once against the
  tree (review log above).
- 2026-09-12, later: **OQ2 answered by the owner** — the repository is
  `ideaconnect/adminata-admin-mongodb-bundle` (renamed on GitHub; the old address redirects), so
  the 7.0 package is `idct/adminata-admin-mongodb-bundle`. The `6.x` branch follows the URLs and
  keeps its package name for Packagist (commit 6fc2d1d there). Execution started the same day
  under the owner's instruction to decide the remaining questions without asking: OQ1 no, OQ3
  yes, OQ4 yes, OQ5 keep. Decisions marked *needs owner* in
  [01](01-naming-decisions.md) — the MongoDB fork's package name (OQ2), dropping the `ba` of
  `sonata-ba-*` (OQ3), folding the five config roots into one (OQ1), renaming the panel's own
  ids (OQ5) — have a recommended default, so execution can start without them and flip later at
  the cost noted beside each. `UPGRADE.md` at the repository root is the application-facing
  guide this plan ships (03 §2.5).
