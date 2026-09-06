# 12 — Documentation plan

## 1. Repository documents (1.0)

`README.md` (hard-fork banner, what is and is not preserved, install, compile-your-own-Tailwind
pointer), `AGENTS.md` (fork's skeleton + "Template contract" and "Upstream sync" sections),
`UPSTREAM.md`, `MIGRATION.md` (the executed recomaty-panel checklist, document 10 §1),
`UPGRADE-1.0.md` (document 10 §3), `CHANGELOG.md` + `CHANGELOG-sonata.md`, `NOTICE`,
`CONTRIBUTING.md` (semver contract, template contract, sync policy, "not yet ported" policy).

## 2. Sphinx docs (one site from the six `docs/` sets, pruned)

The six upstream `docs/` trees are imported with their packages (`doctrine-extensions` brought none)
and merged into one Sphinx site (`docs/`, sections per package, admin-bundle first). The block
pages are the admin bundle's (01 P13): `docs/admin-bundle/reference/block_*.rst` under a *Blocks*
caption of its index, the rapid-prototyping page in its cookbook, and the former installation page
rewritten as `block_configuration.rst`; there is no `docs/block-bundle/`. Pages are kept only when
the feature is in the 1.0 scope; pages for deferred features get a
one-paragraph "not yet ported in adminata" banner instead of being rewritten. form-extensions'
date-picker page is rewritten for native inputs; twig-extensions' flash-message page for the new
markup.

**Amended, 2026-09-06 (01 P14).** `docs/form-extensions/` and `docs/twig-extensions/` are folded in
the same way, so the site is four sections rather than six. Under the admin bundle's index:
a *Forms* caption with `reference/form_types` (moved out of the Reference Guide, because it now
documents every `Sonata\AdminBundle\Form\Type\*` there is), `form_configuration`,
`form_inline_validation` and `form_testing`; and a *Twig helpers* caption with
`twig_configuration`, `twig_status_helper` and `twig_flash_messages`. The two installation pages
are rewritten as `form_configuration.rst` and `twig_configuration.rst` — configuring forms and the
Twig helpers *inside* the admin bundle — the way the block one became `block_configuration.rst`.
form-extensions' own `form_types.rst` is **merged into** the admin bundle's page rather than moved
beside it: two pages titled *Form Types*, both documenting a `Sonata\AdminBundle\Form\Type\
CollectionType`, would have been the site's own version of the trap the rename creates. Images go
to `docs/admin-bundle/images/`.

**Amended, 2026-09-07 (01 P15).** `docs/exporter/` is folded in the same way, so the site is three
sections rather than four: adminata's own pages, the admin bundle, and the ORM storage bundle. The
exporter's pages are an *Exporter* caption of the admin bundle's index —
`reference/exporter_introduction` (its old `introduction.rst`, rewritten around
`Sonata\AdminBundle\Exporter\` and the export action), `exporter_sources`, `exporter_outputs`, and
`exporter_configuration`, which is its `symfony.rst` rewritten as configuration *inside* the admin
bundle, the way the block one became `block_configuration.rst`. Its installation page is not moved:
it described installing a separate library, so what survives of it — the PhpSpreadsheet requirement
the `xlsx` writer has — is a section of `exporter_configuration.rst`. No images, no
`docs/exporter/`, and `action_export.rst` links to the new pages rather than to the upstream
exporter site.

| Page | Change |
|---|---|
| `reference/configuration.rst` | full tree dump: removed `options.*` nodes, asset defaults, `dashboard.blocks[].class` default, new `theme` node |
| `reference/action_create_edit.rst`, `action_show.rst` | group `class`/`box_class` examples in Tailwind terms; tabs marked "not yet ported" |
| `reference/action_list.rst` | `header_class`/`row_align`/`label_icon` still valid; custom action templates with `adm-btn-icon`; custom cell template example; `editable` marked "not yet ported" |
| `reference/dashboard.rst` | block `class` (`md:col-span-6`), `icon` raw HTML, section headers via `ConfigureMenuEvent` |
| `reference/form_types.rst` | `ModelAutocompleteType` combobox options; native date/time formats; `ModelListType`/`ModelType`/`AdminType` marked "not yet ported" |
| `reference/field_types.rst` | typed templates; badge classes |
| `reference/templates.rst`, `architecture.rst` | override policy, `.adm-*` layer, Tailwind/Stimulus assets |
| `reference/advanced_configuration.rst`, `search.rst`, `preview_mode.rst`, `troubleshooting.rst` | pruned to what exists; vanilla JS snippet for the filter form |
| `getting_started/installation.rst` | routing import, `assets:install`, migration from Sonata (`--no-plugins`), compile-your-own-Tailwind |
| `cookbook/recipe_bootlint.rst`, `recipe_icheck.rst`, `recipe_jquery_ui.rst`, `recipe_select2.rst` | **deleted** |
| other cookbook pages | screenshots regenerated where the feature is in scope; otherwise banner |

## 3. New pages

- Theming: light/dark/system, brand colour via `--color-brand-*`, density, radius, fonts.
- Icons: FA7 Free, `parse_icon` contract, raw `<i>` pass-through.
- JavaScript API: controllers, events, `window.sonataApplication`, coexistence rules, `<dialog>` modals.
- Compiling Tailwind yourself: Encore recipe (AssetMapper and Vite post-1.0).
- Porting status: the list of deferred templates and how to request one.
