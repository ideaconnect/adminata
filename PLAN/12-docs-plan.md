# 12 — Documentation plan

## 1. Repository documents (1.0)

`README.md` (hard-fork banner, what is and is not preserved, install, compile-your-own-Tailwind
pointer), `AGENTS.md` (fork's skeleton + "Template contract" and "Upstream sync" sections),
`UPSTREAM.md`, `MIGRATION.md` (the executed recomaty-panel checklist, document 10 §1),
`UPGRADE-1.0.md` (document 10 §3), `CHANGELOG.md` + `CHANGELOG-sonata.md`, `NOTICE`,
`CONTRIBUTING.md` (semver contract, template contract, sync policy, "not yet ported" policy).

## 2. Sphinx docs (one site from the seven `docs/` sets, pruned)

The seven upstream `docs/` trees are imported with their packages and merged into one Sphinx site
(`docs/`, sections per package, admin-bundle first). Pages are kept only when the feature is in
the 1.0 scope; pages for deferred features get a one-paragraph "not yet ported in adminata" banner
instead of being rewritten. form-extensions' date-picker page is rewritten for native inputs;
twig-extensions' flash-message page for the new markup.

| Page | Change |
|---|---|
| `reference/configuration.rst` | full tree dump: removed `options.*` nodes, asset defaults, `dashboard.blocks[].class` default, new `adminata:` root |
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
