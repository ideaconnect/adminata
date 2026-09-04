# Upstream changelogs

adminata forked seven `sonata-project` packages. Their history up to the imported tag is kept in
this repository (`git log -- packages/<name>`) and in the changelog file each package brought with
it. This file is the index; entries are added when a package is synced to a newer upstream release
(the process is in [UPSTREAM.md](UPSTREAM.md)).

| Package | Imported at | Upstream changelog in this repository | Upstream releases |
|---|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | [packages/admin-bundle/CHANGELOG.md](packages/admin-bundle/CHANGELOG.md) | <https://github.com/sonata-project/SonataAdminBundle/releases> |
| `sonata-project/block-bundle` | 5.4.0 | [packages/block-bundle/CHANGELOG.md](packages/block-bundle/CHANGELOG.md) | <https://github.com/sonata-project/SonataBlockBundle/releases> |
| `sonata-project/doctrine-extensions` | 2.6.0 | [packages/doctrine-extensions/CHANGELOG.md](packages/doctrine-extensions/CHANGELOG.md) | <https://github.com/sonata-project/sonata-doctrine-extensions/releases> |
| `sonata-project/doctrine-orm-admin-bundle` | 4.21.0 | [packages/doctrine-orm-admin-bundle/CHANGELOG.md](packages/doctrine-orm-admin-bundle/CHANGELOG.md) | <https://github.com/sonata-project/SonataDoctrineORMAdminBundle/releases> |
| `sonata-project/exporter` | 3.4.0 | [packages/exporter/CHANGELOG.md](packages/exporter/CHANGELOG.md) | <https://github.com/sonata-project/exporter/releases> |
| `sonata-project/form-extensions` | 2.7.0 | [packages/form-extensions/CHANGELOG.md](packages/form-extensions/CHANGELOG.md) | <https://github.com/sonata-project/form-extensions/releases> |
| `sonata-project/twig-extensions` | 2.6.0 | [packages/twig-extensions/CHANGELOG.md](packages/twig-extensions/CHANGELOG.md) | <https://github.com/sonata-project/twig-extensions/releases> |

## Synced upstream releases

None yet — every package sits at its imported tag.

When a sync lands, add a section here with the upstream release notes that applied to adminata, the
paths that were excluded (adminata owns them) and the UI changes that were re-implemented by hand.
