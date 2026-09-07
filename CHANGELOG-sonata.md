# Upstream changelogs

adminata forked seven `sonata-project` packages. Their history up to the imported tag is kept in
this repository (`git log -- packages/<name>`) and in the changelog file each package brought with
it. This file is the index; entries are added when a package is synced to a newer upstream release
(the process is in [UPSTREAM.md](UPSTREAM.md)).

Five of them no longer have a tree of their own: `block-bundle`, `form-extensions` and
`twig-extensions` were merged into the admin bundle on 2026-09-06 and `exporter` and
`doctrine-extensions` on 2026-09-07, and their changelogs came with them. All the inherited
histories are under `changelog/`, named after the upstream package. Their subtree histories are
still reachable as `git log -- packages/<name>`, at the paths those trees had before the merges.

The seventh, `doctrine-orm-admin-bundle`, moved to
[a repository of its own](https://github.com/ideaconnect/adminata-doctrine-orm-admin-bundle) on
2026-09-07 and took its changelog with it.

| Package | Imported at | Upstream changelog in this repository | Upstream releases |
|---|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | [changelog/admin-bundle.md](changelog/admin-bundle.md) | <https://github.com/sonata-project/SonataAdminBundle/releases> |
| `sonata-project/block-bundle` | 5.4.0 | [changelog/block-bundle.md](changelog/block-bundle.md) | <https://github.com/sonata-project/SonataBlockBundle/releases> |
| `sonata-project/doctrine-extensions` | 2.6.0 | [changelog/doctrine-extensions.md](changelog/doctrine-extensions.md) | <https://github.com/sonata-project/sonata-doctrine-extensions/releases> |
| `sonata-project/exporter` | 3.4.0 | [changelog/exporter.md](changelog/exporter.md) | <https://github.com/sonata-project/exporter/releases> |
| `sonata-project/form-extensions` | 2.7.0 | [changelog/form-extensions.md](changelog/form-extensions.md) | <https://github.com/sonata-project/form-extensions/releases> |
| `sonata-project/twig-extensions` | 2.6.0 | [changelog/twig-extensions.md](changelog/twig-extensions.md) | <https://github.com/sonata-project/twig-extensions/releases> |

## Synced upstream releases

None yet — every package sits at its imported tag.

When a sync lands, add a section here with the upstream release notes that applied to adminata, the
paths that were excluded (adminata owns them) and the UI changes that were re-implemented by hand.

A `block-bundle`, `doctrine-extensions`, `exporter`, `form-extensions` or `twig-extensions` release
is the case that cannot be applied mechanically: `upstream/sync.sh` has no tree to land the diff
in, so each hunk is translated by hand through the class maps in [CHANGELOG.md](CHANGELOG.md). Say
so in the entry.
