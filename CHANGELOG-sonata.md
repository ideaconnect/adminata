# Upstream changelogs

adminata forked seven `sonata-project` packages. Their history up to the imported tag is kept in
this repository (`git log -- packages/<name>`) and in the changelog file each package brought with
it. This file is the index; entries are added when a package is synced to a newer upstream release
(the process is in [UPSTREAM.md](UPSTREAM.md)).

Four of them no longer have a package directory: `block-bundle`, `form-extensions` and
`twig-extensions` were merged into `packages/admin-bundle` on 2026-09-06 and `exporter` on
2026-09-07, and their changelogs came with them under names that do not collide with the admin
bundle's own. Their subtree histories are still reachable as `git log -- packages/<name>`.

| Package | Imported at | Upstream changelog in this repository | Upstream releases |
|---|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | [packages/admin-bundle/CHANGELOG.md](packages/admin-bundle/CHANGELOG.md) | <https://github.com/sonata-project/SonataAdminBundle/releases> |
| `sonata-project/block-bundle` | 5.4.0 | [packages/admin-bundle/CHANGELOG-block.md](packages/admin-bundle/CHANGELOG-block.md) | <https://github.com/sonata-project/SonataBlockBundle/releases> |
| `sonata-project/doctrine-extensions` | 2.6.0 | [packages/doctrine-extensions/CHANGELOG.md](packages/doctrine-extensions/CHANGELOG.md) | <https://github.com/sonata-project/sonata-doctrine-extensions/releases> |
| `sonata-project/doctrine-orm-admin-bundle` | 4.21.0 | [packages/doctrine-orm-admin-bundle/CHANGELOG.md](packages/doctrine-orm-admin-bundle/CHANGELOG.md) | <https://github.com/sonata-project/SonataDoctrineORMAdminBundle/releases> |
| `sonata-project/exporter` | 3.4.0 | [packages/admin-bundle/CHANGELOG-exporter.md](packages/admin-bundle/CHANGELOG-exporter.md) | <https://github.com/sonata-project/exporter/releases> |
| `sonata-project/form-extensions` | 2.7.0 | [packages/admin-bundle/CHANGELOG-form.md](packages/admin-bundle/CHANGELOG-form.md) | <https://github.com/sonata-project/form-extensions/releases> |
| `sonata-project/twig-extensions` | 2.6.0 | [packages/admin-bundle/CHANGELOG-twig.md](packages/admin-bundle/CHANGELOG-twig.md) | <https://github.com/sonata-project/twig-extensions/releases> |

## Synced upstream releases

None yet — every package sits at its imported tag.

When a sync lands, add a section here with the upstream release notes that applied to adminata, the
paths that were excluded (adminata owns them) and the UI changes that were re-implemented by hand.

A `block-bundle`, `exporter`, `form-extensions` or `twig-extensions` release is the case that
cannot be applied mechanically: `upstream/sync.sh` has no directory to land the diff in, so each
hunk is translated by hand into `packages/admin-bundle/` through the class maps in
[CHANGELOG.md](CHANGELOG.md). Say so in the entry.
