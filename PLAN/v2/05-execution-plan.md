# 05 — Execution plan

Seven phases, each ending in a gate, three of them in a milestone push. Tasks are written in the
PROJECT_PLAN.md format (Read / Do / Deliver / Accept, size S ≤ 2 h, M half a day, L one to two
days) so that they can be copied into `PROJECT_PLAN.md` as milestone M7 unchanged. Every task
ends with the definition of done of AGENTS.md §5 plus `make check-names` once R0-03 exists;
"green" below means that. Branches: `task/R0-01-…` off `main`, commit subjects `R0-01: <what>`,
the `Co-Authored-By` trailer, `main` pushed only in `R*-MS` tasks.

## Phase R0 — The engine (2 days)

- [ ] **R0-01 · Rule file and path file** · M · depends: —
  - Read: PLAN/v2/02 §§2–4; appendix A.
  - Do: write `upstream/rename/rules.txt` and `paths.txt` exactly as 02 lists them, ordered;
    `allow.txt` with the skipped files and allowed phrases of 02 §5; `upstream/rename/README.md`.
    Re-run the appendix A commands and reconcile every count that moved since 2026-09-12.
  - Deliver: the three files, the README.
  - Accept: every row of 02 §2 and §4 has a line in the files; `grep -c` of rules prints ≥ 45;
    the two collision rules of 02 §3.4 are present (with the R1-01 placeholder marked `TODO` if
    R1-01 has not decided yet).

- [ ] **R0-02 · `apply.php`, `build-lists.php`, the launcher** · L · depends: R0-01
  - Read: 02 §1; AGENTS.md §6 (PHP conventions).
  - Do: implement the modes of 02 §1 (`--tree`, `--app`, `--dry-run`, `--stdin`, `--path`,
    `--check`); `build-lists.php` generating `known.txt` from the sources 02 §1 lists, run
    against adminata, `ORM/` and `ODM/`; `bin/adminata-rename` launcher and the `bin` entry in
    `composer.json`; backslash-run-agnostic matching; per-rule file globs; protected
    phrases restored after the pass; idempotence; `--check` output as `file:line: <match>`.
    Unit suite `tests-adminata/Unit/Rename/ApplyTest.php` with fixtures for: rules 15/16 against
    `Sonata Project` and `SonataUserBundle`; rule 31 against a sentence ending in "sonata."; rule
    32 against `sonata-project.org` and `sonata-admin-mongodb-bundle`; the R-root rules against a
    Twig global (`sonata_admin.adminPool` → `adminata_admin.adminPool`) and a config path in a
    `.md` (`sonata_admin.options.x` → `adminata.options.x`); `\\` and `\\\\` runs; a path through
    the path rules; an `--app` fixture in which `sonata.admin.acme.post`, `sonata_admin_edit_own_password`
    and `sonata-overrides` are reported and untouched while `sonata.admin` (tag) and
    `@SonataAdmin/` are rewritten; a full-file fixture holding every rule, whose expected output
    is committed.
  - Deliver: `upstream/rename/{apply,build-lists}.php`, `known.txt`, `bin/adminata-rename`, the
    test, the `make check-names` target (`--check .`).
  - Accept: `vendor/bin/phpunit tests-adminata/Unit/Rename` green; running `--tree` twice on a
    copy of `src/` gives an empty second diff; `known.txt` regenerated is byte-identical to the
    committed one; PHPStan level 8 clean; CS-Fixer clean.

- [ ] **R0-03 · The gate in the tooling** · S · depends: R0-02
  - Do: `make check-names` = `php upstream/rename/apply.php --check .`; add to `make lint` and to
    the `lint.yaml` workflow; add to AGENTS.md §5 (the AGENTS.md rewrite itself is R2-04).
  - Accept: on `main` before R1, `make check-names` **fails** listing thousands of lines — the
    proof it sees what R1 must remove; the failure is not committed to CI until R1-MS.

- [ ] **R0-04 · Translated three-way sync** · M · depends: R0-02
  - Read: `upstream/sync.sh`, `upstream/diff.sh`, UPSTREAM.md "Sync process"; N16.
  - Do: rewrite `upstream/sync.sh`: for each file in `git diff --name-status FROM TO` of the
    upstream remote (exclusions applied): renamed path via `--path`; `M` → `git merge-file
    <ours> <(git show FROM:<path> | apply.php --stdin) <(git show TO:<path> | apply.php --stdin)`;
    `A` → translated content written; `D` → `git rm`; `R` (upstream renamed a file, `-M`) →
    our `git mv` then the merge. Both translated sides are passed through
    `php-cs-fixer fix --config=.php-cs-fixer.dist.php` before the merge, because the rename moves
    `IDCT\…` imports to another place in every sorted `use` block and an unsorted upstream side
    would conflict on every synced file. Conflict markers are left for the hand. A rehearsal mode
    runs against a scratch worktree and prints a summary.
  - Deliver: `upstream/sync.sh`, a rehearsal script `upstream/rehearse-sync.sh`, UPSTREAM.md
    "Sync process" rewritten.
  - Accept: rehearsal of `admin-bundle` 4.43.0 → 4.43.0 (identity) on the renamed tree of R1
    changes nothing; rehearsal of an artificial upstream commit (one `src/` file edited in a
    scratch clone of the upstream tag: a method renamed, a `Sonata\AdminBundle\…` import added)
    produces a merge with no conflict and the change under the new names. (Runs after R1-02;
    listed here because the code is R0's.)

Exit gate: R0-02 suite green; `make check-names` runs; **no push** (the gate fails on `main`).

## Phase R1 — adminata, the code (2 days)

- [ ] **R1-01 · Decide the two collisions and the one-off spellings** · S · depends: R0-01
  - Read: 02 §3.4; `src/Resources/views/standard_layout.html.twig`, `CRUD/base_edit_form.html.twig`,
    `tests-adminata/Contract/hooks.yaml`.
  - Do: read what `sonata-ba-content` / `#sonata-content` and `sonata-ba-tabs` / `sonata-tabs`
    mark; write the two rules; record the decision in 01 N11 (superseding the proposal if it
    differs).
  - Accept: `rules.txt` has no `TODO`; `hooks.yaml` (after R1-04) lists the chosen tokens.

- [ ] **R1-02 · The two mechanical commits** · M · depends: R1-01, R0-02
  - Do: on `task/R1-02-mechanical`: `php upstream/rename/apply.php --tree .`; nothing else in
    the first commit ("Rename the bundle to IDCT\Adminata: mechanical pass"). Then `make cs-fix`
    alone in a second commit ("…: reorder imports"): the rename moves every `IDCT\…` import to
    another place in the sorted `use` blocks, and the fixer is the only thing that may touch
    that.
  - Accept: re-running the engine on the parent of the first commit reproduces it byte for byte
    (02 §6), and `make cs-fix` on the first reproduces the second; `composer dump-autoload -o`
    prints no PSR-4 warning; `git diff --stat HEAD~2 | tail -1` is recorded in the status log
    (expected: ~1,200 files).

- [ ] **R1-03 · The hand edits: DI, Twig aliases, Composer** · M · depends: R1-02, R3-01
  - Do: delete `TwigNamespaceAliasCompilerPass` and its registration and test; `composer.json`
    (N15: `replace` gone, six `conflict`s, description, keywords, suggest, `bin`, `require-dev`
    on the ORM branch — 04 §1); the two `getNamespace()` URIs; `bin/check-upstream-versions.php`;
    confirm the five roots (`debug:config adminata`, `adminata_block`, `adminata_form`,
    `adminata_twig`, `adminata_exporter`); `upstream/exclude/admin-bundle.txt` renamed paths;
    `phpstan/baseline-admin-bundle.neon` re-generated if the message text moved; `rector.php`,
    `phpstan-console-application.php` kernels resolve.
  - Accept: `make lint phpstan rector test` green (the ORM branch beside); `composer validate
    --strict`; `composer why-not sonata-project/admin-bundle` names the conflict; the six
    `debug:` commands of 02 §6 print what they should.

- [ ] **R1-04 · Contract suites re-based** · M · depends: R1-03
  - Read: PLAN/02; `tests-adminata/Contract/*`; AGENTS.md §4.
  - Do: `ReplaceTest` → `ConflictTest` (all six conflicts, no `replace`, no `Sonata\` class
    autoloadable); `hooks.yaml`, `config-reference/*.yaml`, `deferred-templates.txt`,
    `TranslationContractTest` (domain `AdminataBundle`, 35 files, the six renamed ids),
    `TemplatePathTest` (`@Adminata` only; the three aliases must **not** resolve),
    `BlockNameTest` (the `adminata_*` block names), `HookContractTest`; a new
    `NamespaceContractTest`: `src/` has no `DoctrineORM/` or `DoctrineMongoDB/` directory (N19),
    `AdminataBundle::getName()` is `AdminataBundle`, the Twig loader has exactly one adminata
    namespace, `assets:install` publishes to `bundles/adminata`.
  - Accept: `make test-contract` green; `bin/console debug:twig` shows no `@Sonata*`.

- [ ] **R1-05 · Assets, fixtures, snapshots** · M · depends: R1-03
  - Do: `bin/write-manifests.mjs`, `vite.config.js`, `.size-limit.json`, `playwright.config.js`
    (cookie names); `npm run build`; `make js-fixtures` (regenerates the 2,347-hit HTML fixtures
    from the renamed templates); `node bin/build-js-contract.mjs`; `make visual-update` only if
    a screenshot legitimately changed (a class rename must not change pixels — a diff here is a
    defect); `tests-adminata/App/public/bundles/adminata` symlink.
  - Accept: `make lint-js test-js assets-check test-visual test-functional` green
    (`PANTHER_SELENIUM_HOST=http://127.0.0.1:4445/wd/hub` on this machine); `git status` clean
    after `make assets-check`; zero visual diffs.

- [ ] **R1-06 · Demo application and both test apps** · S · depends: R1-03
  - Do: `tests-adminata/App/config/adminata.yaml` (roots), `tests/App/config/*`, `Kernel.php`
    and `AppKernel.php` bundle lines; `make demo` renders the dashboard, a list, an edit form and
    the question dialog in both themes.
  - Accept: `make test` green; `curl -s http://127.0.0.1:8123/admin/dashboard | grep -c
    'adminata-'` > 0 and `| grep -c 'sonata-'` = 0.

Exit gate: all of AGENTS.md §5 green with the ORM branch beside, `make check-names` green on
`src/`, `tests/`, `tests-adminata/`, `assets/`, `bin/`, configs (docs and root documents are R2's);
**no push yet** — R2 finishes the documents first so `main` never shows a half-renamed README.

## Phase R2 — adminata, the documents (1 day)

- [ ] **R2-01 · README, NOTICE, UPSTREAM, CONTRIBUTING, composer description** · M · depends: R1-06
  - Read: 03 §§1–2.
  - Do: the Origins section verbatim from 03 §2.1 with the date filled in; the first paragraph
    and tables rewritten; the storage-layer table with the ORM 2.0 / ODM 7.0 names; NOTICE's one
    sentence; UPSTREAM.md's namespace paragraph and a sync-log row; CONTRIBUTING's versioning
    table row that mentions `sonata-*` hooks.
  - Accept: `make check-names` green on the root `*.md` it scans (README, CONTRIBUTING, AGENTS,
    NOTICE); the Origins section names no old identifier; every relative link in the four files
    resolves (Prettier does not format Markdown here — `.prettierignore` lists `*.md`).

- [ ] **R2-02 · Documentation site** · L · depends: R1-02
  - Read: 01 N21; 03 §2.2.
  - Do: the engine already rewrote identifiers in `docs/` in R1-02; now read every page's diff
    and prose: rewrite sentences that describe the current identity with a Sonata name, keep
    origin sentences, decide per cookbook page whether a recipe that depends on a bundle
    adminata does not ship stays (with its foreign names on the allowed list) or goes;
    `docs/origins.rst`; `docs/index.rst`; `docs/upgrading.rst` gains the names section (and is
    on the skip list); rename the recipe page and the three images.
  - Accept: `make docs` builds with warnings as errors; `make check-names` green on `docs/`; the
    built HTML has no `<hr class="docutils" />` (the heading-loss defect noted in the status log).

- [ ] **R2-03 · UPGRADE.md, UPGRADE-1.0.md, MIGRATION.md, CHANGELOG.md** · M · depends: R1-06
  - Read: 03 §§2.4–2.5; the draft `UPGRADE.md` written with this plan on 2026-09-12.
  - Do: `UPGRADE.md` checked line by line against the tree as renamed (every *after* name
    exists; the status line at its top removed); `UPGRADE-1.0.md` U1 reduced to a pointer;
    MIGRATION.md's round-1 note; CHANGELOG's Changed entry with the last Sonata-named sha (the
    parent of R1-02).
  - Accept: `UPGRADE.md`'s appendix has a row for every rule of 02 §2 and every path of 02 §4.1
    that an application can meet; the four files are on the gate's skip list and the gate is
    green.

- [ ] **R2-04 · AGENTS.md** · S · depends: R1-06
  - Do: §1, §3, §4, §6, §7 rewritten to the new names and to the engine and gate; §2 gains
    "no Sonata-named identifier, ever — the gate says so"; one origin paragraph kept (03 §4).
  - Accept: `make check-names` green on AGENTS.md; every command in §5 exists in the Makefile.

- [ ] **R2-05 · CI and repository metadata in the tree** · S · depends: R1-05, R4-01
  - Do: `.github/workflows/*.yaml` (paths, `mongo-compat.yaml` per 04 §3 — naming the ODM's
    `7.x` branch until 7.0.0 is tagged), `.github/PULL_REQUEST_TEMPLATE.md` checklist line,
    issue templates' prose reviewed, `.symfony.bundle.yaml`, `.gitattributes` (per-file
    `export-ignore` lines instead of `/upstream`, so that `upstream/rename/` ships — the upgrade
    guide tells applications to run it from `vendor/bin/adminata-rename`).
  - Accept: every workflow green on the branch; `git archive HEAD | tar t | grep -E
    'upstream/rename|bin/adminata-rename'` lists the engine and the launcher.

- [ ] **R1/R2-MS · Milestone push** · S · depends: R2-01..05
  - Do: merge `rename/idct` into `main` (the two mechanical commits and the hand commits kept
    apart, N18), `require-dev` still naming the ORM branch (04 §1); push; `PLAN/README.md`
    amendment row; `PROJECT_PLAN.md` status log.
  - Accept: `main` green on every workflow; `make check-names` green from a fresh clone.

## Phase R3 — The ORM layer (0.5 day, runs beside R1)

- [ ] **R3-01 · Rename `ORM/`** · M · depends: R1-02
  - Do: copy `upstream/rename/` (engine + rules + `known.txt`) into `ORM/`; `--tree .`; hand
    edits of 04 §2 (`conflict`, bundle class check, alias check, `branch-alias 2.x-dev`,
    `require idct/adminata dev-rename/idct`); its gates green against adminata's `rename/idct`
    through the `path` repository; the branch pushed so that adminata's CI can resolve it.
  - Accept: `make lint phpstan rector test` in `ORM/` green; `make check-names` green there.
- [ ] **R3-02 · Its documents** · S · depends: R3-01 — README, NOTICE sentence, UPSTREAM.md
  paragraph, CHANGELOG entry with the last Sonata-named sha, UPGRADE-2.0.md with the map.
- [ ] **R3-03 · Merge and tag 2.0.0** · S · depends: R3-02, R1/R2-MS — `require idct/adminata
  dev-main` again, merge to `main`, tag `v2.0.0`, push.
- [ ] **R3-04 · adminata follows** · S · depends: R3-03 — adminata's `require-dev` → `^2.0`,
  lock from GitHub, push; CI green with no sibling checkout.

## Phase R4 — The MongoDB fork (1 day, after R1/R2-MS)

- [ ] **R4-01 · Rename `ODM/` on `7.x`** · M · depends: R1-02 — engine, `getAlias()`,
  `conflict`, branch alias, `require idct/adminata dev-rename/idct` until adminata merges, its
  gates green (Selenium route on 4445); the branch pushed for adminata's `mongo-compat` job.
- [ ] **R4-02 · Its documents** · S · depends: R4-01 — README, AGENTS.md, UPGRADE-7.0.md,
  CHANGELOG, badges.
- [ ] **R4-03 · Package and repository name (OQ2, owner action)** · S · depends: R4-02 —
  GitHub rename, Packagist submission of the new name, abandonment of the old with replacement;
  then the developer steps: `composer.json` `name`, `homepage`, `support`, README links,
  adminata's `mongo-compat.yaml`, `tailwind.css` `@source`, `suggest`, `README.md`,
  `.php-cs-fixer.rules.php` comment, `docs/index.rst`, the allowed-phrase list entry removed.
- [ ] **R4-04 · Tag 7.0.0** · S · depends: R4-03, R1/R2-MS — `require idct/adminata dev-main`
  again, tag, push; adminata's `mongo-compat` job green against `7.x`.

## Phase R5 — recomaty-panel, round 2 (1.5 days plus review)

- [ ] **R5-01 · Branch `adminata-idct`, mechanical pass** · M · depends: R3-04, R4-04 —
  `composer require` the three at their new constraints (lock from GitHub);
  `vendor/bin/adminata-rename --app --dry-run .` read in full and its report of the panel's own
  names answered (04 §4: ids kept, `sonata-overrides` and `sonata_admin_edit_own_password` the
  panel's call); `--app .`; `git mv` of `templates/bundles/SonataAdminBundle/`, the four config
  files and the routes file; bundles.php; `bin/console cache:clear`, `lint:container`,
  `lint:twig`, the panel's own suites.
- [ ] **R5-02 · Review rounds** · L · depends: R5-01 — both themes on the review server and on
  the owner's server; the hook-override pages first; ledger empty of hook findings; the owner's
  sign-off.
- [ ] **R5-03 · MIGRATION.md round 2, merge** · S · depends: R5-02 — measured figures appended
  to adminata's MIGRATION.md; the panel's `docs/INSTALL.md` "The admin interface" section; merge
  to `develop`, dev deploy green.

## Phase R6 — Release bookkeeping (0.5 day)

- [ ] **R6-01 · Stale tag** · S · depends: — (owner action, before R1/R2-MS): `git push --delete
  origin v1.0.0-rc1`; local tag deleted.
- [ ] **R6-02 · GitHub metadata** · S · depends: R1/R2-MS (owner action): repository description,
  topics (03 §4).
- [ ] **R6-03 · Memory and plan status** · S · depends: R5-03: the project memory notes of the
  maintainer's tooling updated (namespace, package names, branches, tags); PLAN/v2/README.md
  status; PROJECT_PLAN.md M7 closed.
- [ ] **R6-MS · Milestone push** · S · depends: R6-03.

## Exit gates in one table

| Gate | Proves |
|---|---|
| R0 | the engine is idempotent, unit-tested, and sees everything (`--check` fails on `main`) |
| R1 | the two mechanical commits are reproducible; every PHP, JS, CSS, visual and functional suite is green under the new names; no old identifier in code, config, assets, tests |
| R2 | the documents tell the truth under the new names and keep the acknowledgement; docs build; `main` pushed |
| R3 | ORM 2.0.0 tagged; adminata resolves it from GitHub |
| R4 | ODM 7.0.0 tagged under its decided name; mongo-compat green |
| R5 | the production panel runs the IDCT names; MIGRATION.md round 2 measured; `develop` deployed |
| R6 | nothing tagged, described or remembered still says the old names |

## Risks

| Risk | Mitigation |
|---|---|
| A rule rewrites something it should not (prose, a foreign bundle, a URL) | protected phrases; the unit fixtures of R0-02; the mechanical commit is read as a diff before anything else lands on it; the docs diff is read page by page in R2-02 |
| A rule misses something (a token shape not in appendix A) | the gate's left-hand sides are the rules', so a missed *shape* is invisible to it — R1-06's `grep -c 'sonata-'` on rendered pages and a final `grep -rniE 'sonata' src assets tests tests-adminata` read by a person (expected: only headers and the protected phrases) close that hole |
| The ORM pairing breaks adminata CI mid-way | both branches name each other (`dev-rename/idct`), the ORM branch pushed first; untied in the order of 04 §1 (adminata merges, ORM re-targets `dev-main` and tags, adminata's `require-dev` follows) |
| An application's admin ids are renamed and its roles stop matching | `--app` mode never rewrites an application's own ids; UPGRADE.md §6 says what renaming them would mean (a migration of the roles column, `security.yaml`, ACL identities); the panel keeps its 30 ids and 73 roles (OQ5) |
| The upstream three-way merge conflicts on every `use` block | both translated sides go through php-cs-fixer before `git merge-file` (R0-04) |
| The three-way sync produces conflicts on the next upstream release | that is the state today for hand-merged files; the rehearsal of R0-04 is repeated on the first real sync and the conflict count recorded in UPSTREAM.md |
| The panel's own CSS keyed on `sonata-ba-*` renders differently | the review rounds of R5-02 on the pages that carry overrides; nothing merges without the owner's sign-off |
| Packagist cannot rename | it cannot; the new-package-plus-abandonment path of 04 §3 is the supported one, and OQ2's "keep the name" answer costs nothing |
| A user's theme cookie resets | documented in U0; one visit |

## Open questions

| Id | Question | Default | Cost of flipping later |
|---|---|---|---|
| OQ1 | Fold `adminata_{block,form,twig,exporter}` under `adminata:`? | No (one-to-one now) | about a day, DI layer only, any time |
| OQ2 | Rename the MongoDB fork's package and repository? | Yes, `idct/adminata-admin-mongodb-bundle` | none if decided before R4-03; a second Packagist/GitHub round after |
| OQ3 | Drop the `ba` of `sonata-ba-*`? | Yes | a second hook rename in every consumer if flipped after R1 |
| OQ4 | Delete `v1.0.0-rc1` on origin? | Yes, before R1/R2-MS | a stale tag that resolves for `^1.0@dev` |
| OQ5 | Rename the panel's own `sonata.admin.<x>` service ids (and `sonata-overrides`, `sonata_admin_edit_own_password`)? | **No**: 73 `ROLE_SONATA_ADMIN_*` names in `security.yaml`, code, templates, migrations and the users table derive from those ids | a role migration deployed in one step with the config change; persisted filters reset |

## Effort

R0 2 d · R1 2 d · R2 1 d · R3 0.5 d · R4 1 d · R5 1.5 d + review · R6 0.5 d — about nine
working days for one engineer who knows the tree, the ORM and ODM checkouts beside it and the
review tooling of the P5 rounds. The critical path is R0 → R1-02 → R3-01 → R1-03 … R2 → R1/R2-MS → R3-03 → R3-04 → R5.
