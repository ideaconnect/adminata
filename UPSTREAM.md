# Upstream packages

adminata is a **hard fork** of seven `sonata-project` packages. Their trees live under
`packages/<name>/`, keeping the upstream namespaces, bundle classes, config roots, service ids and
Twig namespaces (PLAN/01 P1, P2). Composer `replace`s all seven at the exact versions below, so
installing `idct/adminata` satisfies any dependant that requires them — including
`idct/sonata-admin-mongodb-bundle`.

Each package was imported with `git subtree add`, so its full upstream history is part of this
repository and `upstream/sync.sh` can replay later upstream diffs onto `packages/<name>/`.

A package directory is a directory of this repository, not a repository of its own: it holds `src/`,
`tests/`, the upstream `LICENSE` (attribution, and the one file of the four that ships in the
Composer archive) and the upstream `CHANGELOG.md` (the inherited history CHANGELOG-sonata.md links).
The dev-kit scaffolding each fork arrived with — `Makefile`, `bin/console`, `README.md`,
`CONTRIBUTING.md`, `UPGRADE-*.md`, `.editorconfig`, `.yamllint`, `.gitattributes`, `.gitignore`,
`.php-cs-fixer.dist.php`, `.readthedocs.yaml`, `.symfony.bundle.yaml` — is gone: one root governs
the lot, and every exclusion list names these paths so a sync cannot bring them back.

## Imported versions

| Package directory | Upstream package | Namespace | Tag | Upstream commit | Released | Import commit |
|---|---|---|---|---|---|---|
| `packages/admin-bundle` | `sonata-project/admin-bundle` | `Sonata\AdminBundle\` | 4.43.0 | `3fbcc80282d7438855482240109725b1d3bcef3a` | 2026-06-03 | `0572910` |
| `packages/block-bundle` | `sonata-project/block-bundle` | `Sonata\BlockBundle\` | 5.4.0 | `6c7b38af2662ab81197fae2697f46bfa28db9384` | 2025-11-30 | `d47531c` |
| `packages/doctrine-extensions` | `sonata-project/doctrine-extensions` | `Sonata\Doctrine\` | 2.6.0 | `1a7ce987cf1761e88fa55dc157e941b44bf11e42` | 2025-11-23 | `d46b331` |
| `packages/doctrine-orm-admin-bundle` | `sonata-project/doctrine-orm-admin-bundle` | `Sonata\DoctrineORMAdminBundle\` | 4.21.0 | `214739047182fc85ab97ade350fdc461cb1d52cf` | 2026-01-05 | `622c9c2` |
| `packages/exporter` | `sonata-project/exporter` | `Sonata\Exporter\` | 3.4.0 | `ff9f0c116d8cdf08a05ac98a6eb8fb4cd94f73d0` | 2025-11-23 | `3d079ef` |
| `packages/form-extensions` | `sonata-project/form-extensions` | `Sonata\Form\` | 2.7.0 | `f4f46206377a4eb4fcdef343698198bdca88c834` | 2025-11-23 | `bf26b6a` |
| `packages/twig-extensions` | `sonata-project/twig-extensions` | `Sonata\Twig\` | 2.6.0 | `bbc173ad144d30f87e94f544cbec4ab8de1b4b20` | 2025-11-23 | `6fae8f1` |

The `replace` block of the root `composer.json` must list exactly these seven names at exactly these
versions; `bin/check-replace-versions.php` (P0-09) asserts it against this table.

## Remotes

`upstream/remotes.txt` holds the package → repository → tag mapping. Recreate the remotes in a fresh
clone with:

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
| `form-extensions` | `assets/**`, `src/Bridge/Symfony/Resources/{views,public}/**`, `src/Type/BasePickerType.php` |
| `twig-extensions` | `src/Bridge/Symfony/Resources/{views,public}/**` |
| `block-bundle`, `doctrine-orm-admin-bundle` | the views listed as rewritten or deferred in PLAN/03 §G |
| `doctrine-extensions`, `exporter` | tooling files only |

Every list additionally excludes the dev-kit repo scaffolding named above.

## Sync process

See PLAN/07 §10. Summary: `make upstream-diff PKG=<name> FROM=<tag> TO=<tag>` for the report,
`make upstream-sync PKG=<name> TO=<tag>` to apply the PHP-side diff with
`git apply -3 --directory=packages/<name>`, then `make cs-fix rector phpstan test`, one commit
"Sync `<pkg>` X.Y.Z" plus one commit bumping the `replace` entry and this file. UI changes are
re-implemented by hand and get a CHANGELOG line "Ported upstream `<pkg>`#NNNN".

Policy: every upstream minor is synced within one adminata minor; `admin-bundle` 5.x is not merged
before adminata 2.0; upstream deprecations are carried as-is.

## Sync log

| Date | Package | From | To | Notes |
|---|---|---|---|---|
| 2026-09-04 | all seven | — | see table above | Initial import (P0-01), full history via `git subtree add`. `.git` 65 MB, so no `--squash` was needed. |
