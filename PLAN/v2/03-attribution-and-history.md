# 03 — Attribution and history

The owner's second and third rules ([README](README.md)): the history stays, and the gratitude
is written down. This document is the list of what stays where, the text that is added, and the
line the gate draws between an acknowledgement and a name.

## 1. What stays, and in what state

| Where | State after the rename | Why |
|---|---|---|
| Git history | Untouched. No rewrite, no force push, no squash. Renames via `git mv`. The subtree imports of 2026-09-04 (`git log -- packages/<name>`), the seven `upstream-*` remotes, the private `refs/upstream/<name>/<tag>` refs stay. | The history *is* the record that this was Sonata. |
| Upstream file header on inherited PHP (`This file is part of the Sonata Project package. (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>`) | Verbatim, on every file that has it today, including files the engine renames. `.php-cs-fixer.dist.php` keeps enforcing it. | MIT: the copyright notice is retained in all copies. A renamed class is still their work. |
| adminata's combined header (`Forked from the Sonata Project (c) Thomas Rabaix …`) on adminata's own files and `.php-cs-fixer.adminata.php` | Verbatim. New files written during the rename get it, like any new file. | Same. |
| `LICENSE` | Untouched — three copyright lines and the fork paragraph. | Legal record. |
| `NOTICE` | Untouched except one sentence: "Their PHP sources keep the upstream file headers" gains "under the `IDCT\Adminata\` namespace since <date>". The table of forked packages and versions stays. | Provenance. |
| `src/Resources/meta/LICENSE` | Untouched. | Inherited licence text. |
| `UPSTREAM.md` | Edited where it states what stops being true: "keeps the upstream namespace, bundle class, config roots, service ids and Twig namespaces (PLAN/01 P1, P2), and Composer `replace`s it" becomes a paragraph saying the names moved to `IDCT\Adminata\` on <date> (PLAN/v2 N2–N15), adminata `conflict`s with the six it forked and the ORM layer with the seventh, and syncs go through `upstream/rename/` (N16). The tables, the sync log and every `sonata-project/*` row stay; the log gains a row for the rename. | It is the document about the upstream. It must name it. |
| `CHANGELOG-sonata.md`, `changelog/*.md` | Untouched. | Inherited histories. |
| `upstream/remotes.txt`, `upstream/merged.txt`, `upstream/exclude/*.txt` | Untouched (they name upstream trees and paths). `exclude/admin-bundle.txt` gains the renamed hand-merged paths (`src/DependencyInjection/AdminataExtension.php`, `src/AdminataConfiguration.php`) — those are our paths, not names. | Sync infrastructure. |
| `composer.json` `authors` | Thomas Rabaix ("Original author of the forked packages") and the Sonata Community stay. | Attribution. |
| `docs/conf.py` copyright | `2026, IDCT Bartosz Pachołek; 2010-2025 Thomas Rabaix and the Sonata contributors` stays. | Attribution. |
| `PLAN/` (v3 and its archive), `PROJECT_PLAN.md` | Untouched, Sonata names included. `PLAN/README.md` gains one amendment row pointing at `PLAN/v2/`; `PROJECT_PLAN.md` gains milestone M7 when execution starts. | They record how 1.0 was built. |
| `MIGRATION.md` | Round 1 (the Sonata → adminata migration as executed) stays as it is, with a note at the top that round 2 follows and the names in round 1 are the names of the time. Round 2 (adminata → IDCT names on the panel) is appended by R5-03 with measured figures. | A migration record is history the day it is written. |
| `.github/ISSUE_TEMPLATE/config.yml` ("Sonata Admin itself" → upstream issues), `feature_request.yml` ("Missing Sonata feature") | Kept; prose about the origin and a redirect for upstream bugs. | Not a name. |
| `.github/dependabot.yml` ignore of `sonata-project/*` | Kept (it governs the third-party `entity-audit-bundle`). | Not a name. |

## 2. What is added

### 2.1 `README.md` — an "Origins" section, and the banner reworded

The first paragraph stops calling adminata "Sonata Admin, re-skinned" — that sentence names the
product after Sonata — and says what it is: an admin bundle for Symfony with a Tailwind CSS v4 /
TailAdmin interface, a hard fork of the Sonata Admin stack. The tables of replaced packages
become tables of *forked* packages with the new namespaces. And a section is added, near the top,
above Installation:

> ## Origins
>
> adminata began on 2026-09-04 as a hard fork of the Sonata Admin stack. Seven `sonata-project`
> packages — `admin-bundle` 4.43.0, `block-bundle` 5.4.0, `doctrine-extensions` 2.6.0,
> `doctrine-orm-admin-bundle` 4.21.0, `exporter` 3.4.0, `form-extensions` 2.7.0 and
> `twig-extensions` 2.6.0 — were imported with their full git history, and their PHP design is
> the foundation of everything here: the admin classes, the mappers, the datagrid, the routing,
> the security handlers and the exporter are their work under new names. Their sources keep the
> upstream copyright headers.
>
> Since <date> the code lives under the `IDCT\Adminata\` namespace and nothing in the API carries
> the Sonata name any more. The debt does. We are grateful to Thomas Rabaix, to the Sonata
> Project and to its contributors for the years of work since 2010 that made this project possible —
> and for the MIT licence that let it happen. The original lives on at
> <https://sonata-project.org>; bugs in it belong there, and fixes to the PHP we share still flow
> from there through the process in [UPSTREAM.md](UPSTREAM.md).
>
> What was forked from where, at which commit, and what has been synced since:
> [UPSTREAM.md](UPSTREAM.md), [NOTICE](NOTICE), [CHANGELOG-sonata.md](CHANGELOG-sonata.md) and
> the inherited changelogs under [changelog/](changelog/).

The section names packages, people and the project — never the old class names, so the gate can
scan `README.md` (N20). Its links are relative to the repository root, where `README.md` lives.
The Licence section at the bottom stays as it is.

### 2.2 `docs/origins.rst` and `docs/index.rst`

A page with the same content as the README section, in the adminata toctree after `upgrading`.
`docs/index.rst`'s first paragraph is rewritten like the README's; the sentence "If you are
coming from Sonata Admin 4.43, read :doc:`upgrading` first" stays.

### 2.3 `composer.json`

`description`: "An admin bundle for Symfony with a Tailwind CSS v4 / TailAdmin user interface —
a hard fork of the Sonata Admin stack (admin, block, doctrine, exporter, form and twig extensions)
under the IDCT\Adminata namespace. Conflicts with the sonata-project packages it forked."
`authors`: unchanged. `keywords`: `sonata` removed; `admin`, `admin-generator`, `bundle`,
`symfony`, `symfony-ux`, `tailadmin`, `tailwindcss` stay.

### 2.4 `CHANGELOG.md`

Under `Unreleased`, a **Changed** entry that names the change in one paragraph, links
`UPGRADE-1.0.md` for the map, and records the last Sonata-named commit by sha ("everything up to
`<sha>` speaks the Sonata names; nothing after it does"). The ORM layer's and the MongoDB fork's
changelogs get the same entry for their 2.0.0 and 7.0.0.

### 2.5 `UPGRADE.md` and `UPGRADE-1.0.md`

`UPGRADE.md`, at the repository root, is the names guide: it takes an application from the
Sonata-named adminata, or from `sonata-project/admin-bundle` 4.x, to the IDCT names — Composer,
`bundles.php`, configuration roots, routes, PHP, Twig, translations, markup, JavaScript, the
engine in `--app` mode, and the one thing that is a data migration rather than a rename (an
application's own admin ids and the `ROLE_*` names derived from them). It ships with the rename
(R2-03) and is skipped by the gate: it is the one place outside history where the old identifiers
are meant to appear. `UPGRADE-1.0.md` stays the interface guide for an application coming from
upstream — templates, JavaScript, CSS, form themes, the removed configuration nodes — and its
U1 section is reduced to a pointer at `UPGRADE.md` for everything that is a name.

## 3. The line the gate draws

An **acknowledgement** says who made what: "a hard fork of Sonata Admin", "the Sonata Project",
"`sonata-project/admin-bundle` 4.43.0", "Thomas Rabaix". It is allowed anywhere.

A **name** is an identifier adminata answered to: `Sonata\AdminBundle`, `SonataAdminBundle`,
`@SonataAdmin`, `sonata_admin`, `sonata.admin.pool`, `sonata-ba-list-field`, `sonata-modal`,
`sonata_theme`. It is allowed only in the files of [02 §5](02-rename-map.md) — history,
provenance and the upgrade documents (`UPGRADE.md`, `UPGRADE-1.0.md`, `MIGRATION.md`) — and
nowhere else.

The test is mechanical: the gate's left-hand sides are the rule file's, and the rule file only
matches identifier shapes. A sentence in the README that wants to say "the class was called
SonataAdminBundle" fails the gate on purpose; it says "the bundle class of
`sonata-project/admin-bundle`" instead, or it moves to `UPGRADE-1.0.md`.

## 4. Where the acknowledgement must also appear

- `AGENTS.md` §1 keeps one paragraph on the origin (the seven packages, the dates of the merges,
  the split of the ORM layer) so that an agent knows why the tree looks the way it does; the rest
  of the file is rewritten to the new names in R2-04.
- `CONTRIBUTING.md`'s "Upstream Sonata bugs" bullet stays.
- The GitHub repository description (owner's action, R6-02) says "hard fork of Sonata Admin" and
  keeps the `sonata-admin` topic if the owner wants it found by people looking for one; the topic
  is discoverability, not a name in the code.
