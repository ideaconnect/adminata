# Upstream packages

adminata is a **hard fork** of seven `sonata-project` packages. Three of their trees live under
`packages/<name>/`, keeping the upstream namespaces, bundle classes, config roots, service ids and
Twig namespaces (PLAN/01 P1, P2). Composer `replace`s those three at the exact versions below, so
installing `idct/adminata` satisfies any dependant that requires them — including
`idct/sonata-admin-mongodb-bundle`.

The other four were **merged into `packages/admin-bundle`**: `block-bundle` on 2026-09-06 (PLAN/01
P10, P13), then `form-extensions` and `twig-extensions` the same day (PLAN/01 P14), then `exporter`
on 2026-09-07 (PLAN/01 P15). Their classes are `Sonata\AdminBundle\` — the exporter's under
`Sonata\AdminBundle\Exporter\` — the strings the first three shipped are in the
`SonataAdminBundle` translation domain and the exporter shipped none, there is no
`SonataBlockBundle`, `SonataExporterBundle`, `SonataFormBundle` or `SonataTwigBundle` — no class,
no domain — and `composer.json` `conflict`s with all four rather than replacing them.
Their rows stay in every table here because 5.4.0, 2.7.0, 2.6.0 and 3.4.0 are still the upstream
releases their sources sit at.

Each package was imported with `git subtree add`, so its full upstream history is part of this
repository and `upstream/sync.sh` can replay later upstream diffs onto `packages/<name>/`.

**Except for the four merged trees.** Their subtree histories are still in this repository and
`upstream/diff.sh` still reports what changed upstream, but there is no `packages/block-bundle/`,
`packages/exporter/`, `packages/form-extensions/` or `packages/twig-extensions/` for `git apply
--directory` to land a diff in, so `upstream/sync.sh` can no longer replay upstream releases onto
them. **Every future upstream change to those four is ported by hand** into
`packages/admin-bundle/`, against the class maps in CHANGELOG.md. That is the real cost of the
merges, and it is why `upstream/merged.txt` exists: the mechanical path is gone for those four
trees.

A package directory is a directory of this repository, not a repository of its own: it holds `src/`,
`tests/`, the upstream `LICENSE` (attribution, and the one file of the three that ships in the
Composer archive) and the upstream `CHANGELOG.md` (the inherited history CHANGELOG-sonata.md links).
The dev-kit scaffolding each fork arrived with — `Makefile`, `bin/console`, `README.md`,
`CONTRIBUTING.md`, `UPGRADE-*.md`, `.editorconfig`, `.yamllint`, `.gitattributes`, `.gitignore`,
`.php-cs-fixer.dist.php`, `.readthedocs.yaml`, `.symfony.bundle.yaml` — is gone: one root governs
the lot, and every exclusion list names these paths so a sync cannot bring them back.

## Imported versions

| Package directory | Upstream package | Namespace | Tag | Upstream commit | Released | Import commit |
|---|---|---|---|---|---|---|
| `packages/admin-bundle` | `sonata-project/admin-bundle` | `Sonata\AdminBundle\` | 4.43.0 | `3fbcc80282d7438855482240109725b1d3bcef3a` | 2026-06-03 | `0572910` |
| merged into `packages/admin-bundle` | `sonata-project/block-bundle` | `Sonata\AdminBundle\` | 5.4.0 | `6c7b38af2662ab81197fae2697f46bfa28db9384` | 2025-11-30 | `d47531c` |
| merged into `packages/admin-bundle` | `sonata-project/doctrine-extensions` | `Sonata\AdminBundle\Doctrine\` | 2.6.0 | `1a7ce987cf1761e88fa55dc157e941b44bf11e42` | 2025-11-23 | `d46b331` |
| `packages/doctrine-orm-admin-bundle` | `sonata-project/doctrine-orm-admin-bundle` | `Sonata\DoctrineORMAdminBundle\` | 4.21.0 | `214739047182fc85ab97ade350fdc461cb1d52cf` | 2026-01-05 | `622c9c2` |
| merged into `packages/admin-bundle` | `sonata-project/exporter` | `Sonata\AdminBundle\Exporter\` | 3.4.0 | `ff9f0c116d8cdf08a05ac98a6eb8fb4cd94f73d0` | 2025-11-23 | `3d079ef` |
| merged into `packages/admin-bundle` | `sonata-project/form-extensions` | `Sonata\AdminBundle\` | 2.7.0 | `f4f46206377a4eb4fcdef343698198bdca88c834` | 2025-11-23 | `bf26b6a` |
| merged into `packages/admin-bundle` | `sonata-project/twig-extensions` | `Sonata\AdminBundle\` | 2.6.0 | `bbc173ad144d30f87e94f544cbec4ab8de1b4b20` | 2025-11-23 | `6fae8f1` |

The `replace` block of the root `composer.json` must list exactly the *package directory* names
above at exactly these versions, and `conflict` must carry every merged tree —
`sonata-project/block-bundle`, `sonata-project/doctrine-extensions`, `sonata-project/exporter`,
`sonata-project/form-extensions` and `sonata-project/twig-extensions`;
`bin/check-replace-versions.php` (P0-09) asserts that against this table, against
`upstream/remotes.txt` and against `upstream/merged.txt`. It reads the **Upstream package** and
**Tag** columns by name, so a merged row keeps its tag recorded while its first cell no longer
names a directory.

## Remotes

`upstream/remotes.txt` holds the package → repository → tag mapping for all seven imported trees,
the merged ones included; `upstream/merged.txt` records which of them no longer have a
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
| `doctrine-orm-admin-bundle` | the views listed as rewritten or deferred in PLAN/03 §G |
| `block-bundle`, `doctrine-extensions`, `exporter`, `form-extensions`, `twig-extensions` | everything: they have no directory to sync into, so their lists are moot (see above) |

Every list additionally excludes the dev-kit repo scaffolding named above.

## Sync process

See PLAN/07 §10. Summary: `make upstream-diff PKG=<name> FROM=<tag> TO=<tag>` for the report,
`make upstream-sync PKG=<name> TO=<tag>` to apply the PHP-side diff with
`git apply -3 --directory=packages/<name>`, then `make cs-fix rector phpstan test`, one commit
"Sync `<pkg>` X.Y.Z" plus one commit bumping the `replace` entry and this file. UI changes are
re-implemented by hand and get a CHANGELOG line "Ported upstream `<pkg>`#NNNN".

For the four merged trees only the first half applies: `make upstream-diff PKG=form-extensions`
still reads, `make upstream-sync PKG=form-extensions` has nowhere to apply to. Read the diff,
translate each hunk through the class map into `packages/admin-bundle/`, and record the release in
the **Tag** column above and in `CHANGELOG-sonata.md` as usual.

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
