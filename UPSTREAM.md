# Upstream packages

adminata is a **hard fork** of seven `sonata-project` packages. What is left in this repository is
one of them: `sonata-project/admin-bundle`, whose tree *is* this repository — `src/` and `tests/`,
the same layout upstream uses, which is what lets `upstream/sync.sh` merge an upstream release
with no directory prefix at all. Until 2026-09-12 it kept the upstream namespace, bundle class,
config roots, service ids and Twig namespaces (PLAN/01 P1, P2) and Composer `replace`d the package
at the exact version below. Since that day every one of those names is adminata's own —
`IDCT\Adminata\`, `AdminataBundle`, `adminata`, `adminata.*`, `@Adminata` (PLAN/v2 N2–N15) —
and `composer.json` `conflict`s with the package instead: adminata provides the same behaviour
under its own names, so an installation cannot hold both, and nothing that requires the upstream
package would work with this one. Its dependants, `idct/adminata-doctrine-orm-admin-bundle` and
`idct/adminata-admin-mongodb-bundle`, require `idct/adminata` by name.

The rename was performed by `upstream/rename/apply.php`, an ordered rule file applied over every
file and path, and that engine is permanent infrastructure: it is what translates an upstream
release before it is merged here (see *Sync process*), and `make check-names` runs it as the gate
that nothing named after Sonata comes back.

Five more were **merged into it**: `block-bundle` on 2026-09-06 (PLAN/01 P10, P13), then
`form-extensions` and `twig-extensions` the same day (PLAN/01 P14), then `exporter` (PLAN/01 P15)
and `doctrine-extensions` (PLAN/01 P16) on 2026-09-07. Their classes are `Sonata\AdminBundle\` —
the exporter's under `Sonata\AdminBundle\Exporter\`, doctrine-extensions' under
`Sonata\AdminBundle\Doctrine\` — the strings the first three shipped are in the
`SonataAdminBundle` translation domain and the other two shipped none, there is no
`SonataBlockBundle`, `SonataDoctrineBundle`, `SonataExporterBundle`, `SonataFormBundle` or
`SonataTwigBundle` — no class, no domain — and `composer.json` `conflict`s with all five rather
than replacing them. Their rows stay in every table here because 5.4.0, 2.6.0, 3.4.0, 2.7.0 and
2.6.0 are still the upstream releases their sources sit at.

The seventh, `doctrine-orm-admin-bundle`, was **split out into a repository of its own** on
2026-09-07 (PLAN/01 P17): [ideaconnect/adminata-doctrine-orm-admin-bundle][orm]. `git subtree
split` took its 2169 commits with it, so its upstream history went along and it records its own
provenance in its own `UPSTREAM.md`. It has no row anywhere here.

[orm]: https://github.com/ideaconnect/adminata-doctrine-orm-admin-bundle

Each package was imported with `git subtree add`, so its full upstream history is part of this
repository and `upstream/sync.sh` can replay later upstream diffs onto `src/` and `tests/`.

**Except for the five merged trees.** Their subtree histories are still in this repository and
`upstream/diff.sh` still reports what changed upstream, but they have no tree of their own for a
diff to land in, so `upstream/sync.sh` can no longer replay upstream releases onto them. **Every
future upstream change to those five is ported by hand**, against the class maps in CHANGELOG.md.
That is the real cost of the merges, and it is why `upstream/merged.txt` exists: the mechanical
path is gone for those five trees.

The dev-kit scaffolding each fork arrived with — `Makefile`, `bin/console`, `README.md`,
`CONTRIBUTING.md`, `UPGRADE-*.md`, `.editorconfig`, `.yamllint`, `.gitattributes`, `.gitignore`,
`.php-cs-fixer.dist.php`, `.readthedocs.yaml`, `.symfony.bundle.yaml` — is gone: one root governs
the lot, and every exclusion list names these paths so a sync cannot bring them back. The inherited
upstream changelogs, the history CHANGELOG-sonata.md links, are under `changelog/`.

## Imported versions

| Where it lives | Upstream package | Namespace | Tag | Upstream commit | Released | Import commit |
|---|---|---|---|---|---|---|
| `src/` + `tests/` | `sonata-project/admin-bundle` | `Sonata\AdminBundle\` | 4.43.0 | `3fbcc80282d7438855482240109725b1d3bcef3a` | 2026-06-03 | `0572910` |
| merged into the admin bundle | `sonata-project/block-bundle` | `Sonata\AdminBundle\` | 5.4.0 | `6c7b38af2662ab81197fae2697f46bfa28db9384` | 2025-11-30 | `d47531c` |
| merged into the admin bundle | `sonata-project/doctrine-extensions` | `Sonata\AdminBundle\Doctrine\` | 2.6.0 | `1a7ce987cf1761e88fa55dc157e941b44bf11e42` | 2025-11-23 | `d46b331` |
| merged into the admin bundle | `sonata-project/exporter` | `Sonata\AdminBundle\Exporter\` | 3.4.0 | `ff9f0c116d8cdf08a05ac98a6eb8fb4cd94f73d0` | 2025-11-23 | `3d079ef` |
| merged into the admin bundle | `sonata-project/form-extensions` | `Sonata\AdminBundle\` | 2.7.0 | `f4f46206377a4eb4fcdef343698198bdca88c834` | 2025-11-23 | `bf26b6a` |
| merged into the admin bundle | `sonata-project/twig-extensions` | `Sonata\AdminBundle\` | 2.6.0 | `bbc173ad144d30f87e94f544cbec4ab8de1b4b20` | 2025-11-23 | `6fae8f1` |

The `conflict` block of the root `composer.json` must carry all six trees at `*` —
`sonata-project/admin-bundle` and the five merged ones — and there must be no `replace` block at
all; `bin/check-upstream-versions.php` asserts that against this table, against
`upstream/remotes.txt` and against `upstream/merged.txt`. It reads the **Upstream package** and
**Tag** columns by name, so a merged row keeps its tag recorded while its first cell no longer
names a tree. The tag is still the record of the upstream release each tree sits at — the base of
the next translated diff.

## Remotes

`upstream/remotes.txt` holds the package → repository → tag mapping for the six imported trees
that are still here, the merged ones included; `upstream/merged.txt` records which of them no longer have a
directory of their own and what they were merged into. Recreate the remotes in a fresh clone with:

```bash
grep -v '^#' upstream/remotes.txt | while read -r name url tag; do
    git remote add "upstream-$name" "$url"
done
```

The imported tags are kept as private refs (`refs/upstream/<name>/<tag>`), which are **not** pushed
by default. Re-fetch one with:

```bash
git fetch --no-tags upstream-<name> "+refs/tags/<tag>:refs/upstream/<name>/<tag>"
```

## Exclusion lists

`upstream/exclude/<name>.txt` lists the paths a sync must never take from upstream because adminata
owns them (PLAN/07 §10). They are created in P0-08 together with `upstream/diff.sh` and
`upstream/sync.sh`; in short:

| Package | Excluded from syncs |
|---|---|
| `admin-bundle` | `src/Resources/views/**`, `src/Resources/public/**`, `assets/**`, `package*.json`, build and lint configs, `.github/**`, `Makefile`, `*.md`, `phpunit.xml.dist`, `rector.php`, `.php-cs-fixer.dist.php`, `phpstan*.neon`, the obsolete cookbook recipes, and the PLAN/01 P6 PHP files (merged by hand) |
| `block-bundle`, `doctrine-extensions`, `exporter`, `form-extensions`, `twig-extensions` | everything: they have no tree to sync into, so their lists are moot (see above) |

Every list additionally excludes the dev-kit repo scaffolding named above.

## Sync process

See PLAN/07 §10 and PLAN/v2 N16. Upstream speaks the Sonata names and this repository does not, so
a release is never applied as a patch. `make upstream-diff PKG=<name> FROM=<tag> TO=<tag>` prints
the report, with the part adminata owns translated to this repository's names;
`make upstream-rehearse PKG=<name> TO=<tag>` runs the sync in a scratch worktree and prints what it
would do; `make upstream-sync PKG=<name> TO=<tag>` does it: for every file the release touched
outside the exclusion list, both upstream versions — the tag we sit at and the tag we move to —
go through `upstream/rename/apply.php --stdin` and php-cs-fixer (the rename reorders every sorted
`use` block, and an unnormalised side would conflict on every file), and `git merge-file` merges
them three-way onto ours; added files are translated in, deleted files removed, renamed paths
followed. Conflict markers are left for the hand. Then `make cs-fix rector phpstan test
check-names`, one commit "Sync `<pkg>` X.Y.Z" plus one commit bumping the row in this file (the
sync bumps `upstream/remotes.txt` itself). UI changes are re-implemented by hand and get a
CHANGELOG line "Ported upstream `<pkg>`#NNNN".

For the five merged trees only the report applies: `make upstream-diff PKG=form-extensions` still
reads, translated, and `make upstream-sync PKG=form-extensions` has nowhere to apply to. Read the
translated diff, port each hunk into this bundle by hand, and record the release in the **Tag**
column above and in `CHANGELOG-sonata.md` as usual.

Policy: every upstream minor is synced within one adminata minor; `admin-bundle` 5.x is not merged
before adminata 2.0; upstream deprecations are carried as-is.

## Sync log

| Date | Package | From | To | Notes |
|---|---|---|---|---|
| 2026-09-04 | all seven | — | see table above | Initial import (P0-01), full history via `git subtree add`. `.git` 65 MB, so no `--squash` was needed. |
| 2026-09-06 | `block-bundle` | 5.4.0 | 5.4.0 | Not a sync: `packages/block-bundle/` was merged into `packages/admin-bundle/` and deleted (owner directive; PLAN/01 P10). No upstream code changed — the sources moved namespace. Later upstream releases are ported by hand. |
| 2026-09-06 | `form-extensions` | 2.7.0 | 2.7.0 | Not a sync: `packages/form-extensions/` was merged into `packages/admin-bundle/` and deleted (owner directive; PLAN/01 P14). No upstream code changed — the sources moved namespace, and `Sonata\AdminBundle\Form\Type\CollectionType` was renamed `NativeCollectionType` to make room for form-extensions' own. Later upstream releases are ported by hand. |
| 2026-09-06 | `twig-extensions` | 2.6.0 | 2.6.0 | Not a sync: `packages/twig-extensions/` was merged into `packages/admin-bundle/` and deleted (owner directive; PLAN/01 P14). No upstream code changed — the sources moved namespace. Later upstream releases are ported by hand. |
| 2026-09-07 | `exporter` | 3.4.0 | 3.4.0 | Not a sync: `packages/exporter/` was merged into `packages/admin-bundle/` and deleted (owner directive; PLAN/01 P15). No upstream code changed — the sources moved namespace, under `Sonata\AdminBundle\Exporter\` because the admin bundle already had an `Exporter\` directory, and the DI service file was renamed `exporter_services.php` to clear the admin bundle's own `exporter.php`. Later upstream releases are ported by hand. |
| 2026-09-07 | `doctrine-extensions` | 2.6.0 | 2.6.0 | Not a sync: `packages/doctrine-extensions/` was merged into `packages/admin-bundle/` and deleted (owner directive; PLAN/01 P16). No upstream code changed — the sources moved namespace, under `Sonata\AdminBundle\Doctrine\`, and the two compiler passes took the concern prefix the block and exporter passes beside them use. `SonataDoctrineExtension` went with `SonataDoctrineBundle`: the `sonata_doctrine` root had no `Configuration` class and took no options, so `SonataAdminExtension` loads the services instead. Later upstream releases are ported by hand. |
| 2026-09-07 | `doctrine-orm-admin-bundle` | 4.21.0 | 4.21.0 | Not a sync, and the last entry for this package here: `packages/doctrine-orm-admin-bundle/` was split into [a repository of its own][orm] with `git subtree split` (owner directive; PLAN/01 P17). No upstream code changed. Its remote, its exclusion list and its row in the tables above went with it; it is a dev dependency of this repository now. |
| 2026-09-07 | `admin-bundle` | 4.43.0 | 4.43.0 | Not a sync: with one tree left, `packages/` was removed and `packages/admin-bundle/{src,tests}` became `{src,tests}` at the repository root (owner directive; PLAN/01 P18). No upstream code changed. This is the layout upstream itself uses, so `upstream/sync.sh` no longer passes `--directory` at all. |
| 2026-09-12 | all six | — | — | Not a sync: the rename to adminata's own names (PLAN/v2). Every upstream release from here on is translated through `upstream/rename/` before it is merged; the mechanical commit is `f141c1105`, the last Sonata-named commit `d76c4818f`. |
