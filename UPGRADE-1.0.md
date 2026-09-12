# Upgrading to adminata 1.0 — the interface

For an application on `sonata-project/admin-bundle` 4.43 that is not recomaty-panel. This guide is
the **interface** half of the upgrade: the markup, the JavaScript, the form widgets, the icons,
dark mode, the configuration nodes 1.0 removed, and the templates it has not ported yet. The
**names** half — Composer, `bundles.php`, the namespace, the configuration roots, routes, service
ids, Twig, translations, hooks, and the tool that makes those edits for you — is
[UPGRADE.md](UPGRADE.md), and it comes first: since 2026-09-12 nothing in adminata carries the
Sonata name, so every identifier below is written the way adminata spells it now.

There is **no compatibility layer**: nothing is aliased, shimmed or kept "for now". If your
application touched Bootstrap classes, jQuery or `window.Admin`, those places are edits.

A worked example, with real figures for each step, is [MIGRATION.md](MIGRATION.md) — its first
round moved a 46-admin panel from Sonata to the Sonata-named adminata, its second round to the
names.

## U1 — Install, the merged trees and every name

[UPGRADE.md](UPGRADE.md) §§1–10. In short: `composer require idct/adminata` and the storage layer
you use, `composer remove` every `sonata-project/*` package (adminata conflicts with the ones it
forked), `--no-plugins --no-scripts` on both so that Symfony Flex does not `unconfigure` your
configuration files, then `vendor/bin/adminata-rename --app --dry-run .` read in full and
`--app .` to make the mechanical edits. What the tool cannot do — delete the `bundles.php` lines
of the five bundles that no longer exist, choose between the two `CollectionType`s that
exchanged names, decide about your own admin service ids and the `ROLE_*` names derived from them
— is listed there, section by section.

## U2 — Configuration

Removed nodes — leaving one in `adminata.yaml` is a container build error:

| Node | Why |
|---|---|
| `options.skin` | AdminLTE skins are gone; the theme is light/dark/system |
| `options.use_select2` | no select library; every `<select>` is the browser's |
| `options.use_icheck` | native checkboxes and radios |
| `options.use_bootlint` | Bootstrap is gone |

Changed defaults: the asset lists, `dashboard.blocks[].class` and admin group `class` (Tailwind grid
classes now — `col-span-12`, `md:col-span-4`), and `box_class`, which defaults to an empty string.

New nodes:

| Node | Default | Meaning |
|---|---|---|
| `options.theme.mode` | `system` | `light`, `dark` or `system`; resolved server-side from the `adminata_theme` cookie, so a page never paints the wrong theme first |
| `options.list_row_link` | `true` | clicking a list row opens the object |

Two existing nodes are worth revisiting once you can see the result, because their defaults were
chosen for a different UI:

- `options.list_action_button_content` (`all`) renders the row actions as text buttons. If your
  application ships icon-only actions of its own, `icon` is what matches them.
- `options.default_admin_route` (`show`) is where the identifier column links **and** where a row
  click goes. If most of your admin classes do not define `configureShowFields()`, that is a blank
  page; use `edit`. adminata falls back to the other of `show`/`edit` for an admin that has only
  one of them.

## U3 — Markup vocabulary

Bootstrap and AdminLTE class names are gone from every rewritten template. The replacements are the
`.adm-*` components — `adm-btn`, `adm-card`, `adm-table`, `adm-input`, `adm-badge`, `adm-alert`,
`adm-callout`, `adm-dropdown`, `adm-dialog` and the rest. `assets/css/contract.json` is the
generated list of every one adminata ships, and `assets/css/components/*.css` is where each is
defined and commented.

adminata's own hooks are **frozen**: `adminata-list`, `adminata-list-field*`,
`adminata-link-identifier`, `edit_link`, `view_link`, `delete_link`, `adminata-filter-form`,
`adminata-toggle-filter`, `#list_batch_checkbox` and the rest of the contract in PLAN/02 §8 under
the names UPGRADE.md §9 gives them (the old `sonata-ba-*` family is `adminata-*`). Select on
those, not on `.adm-*`, if you are writing CSS or a test that has to keep working across releases.

For anything the components do not cover, compile Tailwind yourself: import
`vendor/idct/adminata/assets/css/adminata.css` from your own entry and point `@source` at your
templates. You then get arbitrary utilities in your own markup, and adminata's tokens and
components come with them.

## U4 — Layout overrides

The blocks an application may override are listed in PLAN/02 §5 and are unchanged, with two
exceptions: `admin_lte_skin_class` and `_skin` no longer exist, and a hard-coded `notice` include
should become `{% block notice %}{{ parent() }}{% endblock %}`.

One structural difference bites the sign-in screens: `adminata_nav` is nested **inside**
`adminata_wrapper`. A login template that replaces `adminata_wrapper` wholesale — which is the usual
shape — will find its `adminata_nav` override never renders. Draw that content at the top of your own
wrapper instead.

## U5 — JavaScript

No jQuery, no jquery-ui, no jquery-form, no `window.Admin`. The single entry point is
`window.adminataApplication`, a Stimulus 3 application with an explicit registry; the identifiers,
targets, values and outlets it promises are in `assets/js/__contract__/controllers.json`.

- Modals are the native `<dialog>` element driven by `adminata-modal`. The top layer, the focus trap,
  the backdrop and Escape are the browser's.
- **There is no AJAX form submission anywhere.** `ajaxSubmit` is gone and is not coming back;
  forms post.
- Register your own controllers on `window.adminataApplication`, or run a second Stimulus
  application of your own — both work, and unknown identifiers are ignored by each.

## U6 — Forms

Native selects, and native HTML5 date and time inputs. The stylesheet and script that upstream's
form-extensions published are gone.

The form types are `IDCT\Adminata\Form\Type\` now — the map, and the `CollectionType` /
`NativeCollectionType` swap that is the one silent breakage of this release, are in UPGRADE.md §6.

`datepicker_options.display.components` is still honoured — it is what decides whether a field is a
`date`, `time` or `datetime-local` input — but `format` is no longer configurable: the type fixes
the wire format, the way Symfony does for `DateType` with `html5: true`. Delete the `format`
options you have; a leftover one is rejected with a message that says which components it saw.

## U7 — Icons

Font Awesome 7 Free, with no v4 or v5 shim. Most v5 names carry over; the v4-only ones do not —
`fa-clock-o` is now `fa-clock`, and similar. Grep your templates for names ending in `-o`.

## U8 — Dark mode

`html.dark` is stamped by the server before the first byte, from the `adminata_theme` cookie, so
there is no flash of the wrong theme. Anything in your own templates or stylesheets that hard-codes
a colour needs a `dark:` variant beside it.

If you write your own dark rules against adminata's `dark` variant, note that it is defined as
`&:where(.dark, .dark *)` — deliberately zero-specificity, so that your rules can override
adminata's. The cost is that a dark rule and the light rule it must beat are an exact specificity
tie, and **source order decides**: put the dark block *after* the declaration it overrides.

## U9 — Templates not yet ported

Thirty-seven templates are inherited from upstream unported and still render Bootstrap markup, which
is now unstyled. Each carries a `{# adminata: not yet ported #}` marker as its first line, they are
listed in `tests/Contract/deferred-templates.txt`, and `DeferredTemplateTest` checks that the list,
the markers and the files on disk agree — so one cannot quietly appear or disappear.

What is in there:

- the eleven `CRUD/Association/edit_*` flows and the `ModelListType` widget that drives them, which
  are deferred because redesigning them without AJAX submission is a design job, not a port;
- the audit trail (`history`, `show_compare` and their bases);
- the ACL screen;
- the mosaic list view and the flat list;
- `preview`, `select_subclass`, `tree`, `search`, the tab menu, the short-object-description
  helper, and seven `Block/*` templates.

They are ported on demand rather than speculatively. Open an issue when you need one.
