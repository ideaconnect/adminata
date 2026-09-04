# 12 — Documentation plan

Sphinx docs are forked from `S/docs` (RTD config, DOCtor-RST in CI). Source of the page-level
findings: `R/php-compat.md` §4, `R/packaging.md` §2.8.

## 1. Pages that change

| Page | Change |
|---|---|
| `reference/configuration.rst` | full tree dump: `skin` semantics, `use_select2/use_icheck/use_bootlint` notes, asset defaults (compat, FA, jQuery; sonataform dropped), new `adminata:` root |
| `reference/action_create_edit.rst`, `action_show.rst` | option lists (Select2/iCheck/Bootlint links removed); group `class`/`box_class` examples keep working but show the Tailwind equivalents; new `box_class.png` |
| `reference/action_list.rst` | `header_style/header_class/row_align/label_icon` still valid; icon syntax note; custom action templates with `adm-btn`; `list_action_button_content`; custom cell template example |
| `reference/dashboard.rst` | block `class` (`col-md-6` mapped, `md:col-span-6` preferred), `icon`, `color` mapping table; dashboard actions |
| `reference/advanced_configuration.rst` | tab menu `dropdown` attribute (now `sonata-dropdown`), custom action button markup, remove "no-stretch" |
| `reference/form_types.rst` | Tom Select options for `ModelAutocompleteType`, `..._select2_options_js` deprecated, native events instead of jQuery, sortable select |
| `reference/field_types.rst` | `editable` semantics unchanged, new implementation, JSON `data-source` |
| `reference/templates.rst` | add override audit, compat layer, `templates/bundles/SonataAdminBundle` |
| `reference/architecture.rst` | templates section; Tailwind/Stimulus assets |
| `reference/preview_mode.rst`, `troubleshooting.rst` | button classes; vanilla JS snippet for the filter form |
| `reference/search.rst`, `getting_started/*` | screenshots regenerated; `col-md-9/3` examples with Tailwind equivalents |
| `getting_started/installation.rst` | routing import, `assets:install`, migration from Sonata (`--no-plugins`), Tailwind self-compile pointer |
| `cookbook/recipe_bootlint.rst`, `recipe_icheck.rst`, `recipe_jquery_ui.rst` | **deleted** (feature removed) |
| `cookbook/recipe_select2.rst` | rewritten for Tom Select (`data-sonata-select2*` semantics kept) |
| `cookbook/recipe_customizing_a_mosaic_list.rst` | badge classes; CSS grid instead of masonry; checkbox placement |
| `cookbook/recipe_custom_action.rst` | button markup; correct icon syntax |
| `cookbook/recipe_row_templates.rst`, `recipe_sortable_listing.rst`, `recipe_image_previews.rst`, `recipe_knp_menu.rst`, `recipe_workflow_integration.rst` | compat notes, icon syntax, screenshots |
| `cookbook/recipe_sonata_admin_without_user_bundle.rst` | login template based on the sign-in page |
| `cookbook/recipe_sortable_sonata_type_model.rst` | Tom Select `drag_drop` |
| `UPGRADE-4.x.md` "Datetime picker assets" | superseded by `UPGRADE-1.0.md` U9 |

## 2. New pages

- Theming: light/dark/system, brand colour via `--color-brand-*`, skins mapping, density, radius,
  fonts.
- Icons: FA6 + shims, `parse_icon` contract, SVG syntax roadmap.
- Bootstrap compatibility layer: what is mapped, what is not, how to remove it.
- Migrating overridden templates: `adminata:audit-overrides`, per-template diff notes.
- JavaScript API: controllers, events, `window.Admin` facade, `startAdminata`, coexistence rules.
- Compiling Tailwind yourself: AssetMapper, Encore, Vite recipes.
- Compatibility matrix (`COMPATIBILITY.md` rendered in docs).

## 3. Repository documents

`README.md` (hard-fork banner, drop-in guarantee), `AGENTS.md`, `BEST_VERSION.md`, `UPSTREAM.md`,
`COMPATIBILITY.md`, `UPGRADE-1.0.md`, `CHANGELOG.md` + `CHANGELOG-sonata.md`, `NOTICE`,
`CONTRIBUTING.md` (semver contract, template contract, sync policy).
