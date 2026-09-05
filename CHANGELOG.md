# Changelog

All notable changes to `idct/adminata` are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html) — see the versioning contract in
[CONTRIBUTING.md](CONTRIBUTING.md).

Changes inherited from the forked Sonata packages are listed separately in
[CHANGELOG-sonata.md](CHANGELOG-sonata.md).

## [Unreleased]

adminata is pre-1.0 and under construction; [PROJECT_PLAN.md](PROJECT_PLAN.md) tracks what is done.
Milestone M0 (bootstrap) is complete: the seven packages are imported, replaced and green under
adminata's own tooling, and the PHP changes the Tailwind interface forces are in. Milestone M1
(foundations) is complete: the Tailwind and Stimulus build, the demo application, and every gate
the template rewrites of M2 to M4 will be measured against.

Milestone M2 (shell) is complete: the layout, the sidebar, the header, the dashboard and the flash
messages are adminata's own, and the pages they own — the dashboard, a login page, an empty layout,
a dialog — have no accessibility and no markup findings in either theme.

Milestone M3 (list) is complete: list pages, filters, batch selection, pagers, row and batch
actions, export and the autocomplete combobox are adminata's own. select2 is replaced by a
hand-written ARIA 1.2 combobox with no dependency, and the request and response the
`sonata_admin_retrieve_autocomplete_items` action speaks are unchanged.

Milestone M4 (forms and show) is complete: the form theme, the collection widget, native date and
time inputs, the edit chrome, the show page, the delete page and the action buttons are adminata's.
**Every page the demo renders passes WCAG 2.1 AA in both themes, validates as HTML and does not
scroll sideways** — `tests/Visual/support/findings.json`, the ledger of what the inherited
interface still owed, is empty.

Milestone M5 (migration and acceptance) is complete: a 46-admin production panel was migrated onto
adminata on a branch, in thirteen commits — 140 files, +2,188 / −3,023 — and the checklist as
executed is [MIGRATION.md](MIGRATION.md). Its §3 is the part worth reading: the suites passed, and
**seventeen defects came from looking at the panel**, every one of which had passed every automated
gate. Two more came out of the owner's later review. All nineteen are recorded as `P5-FIX-nn` in
[PROJECT_PLAN.md](PROJECT_PLAN.md); twelve were adminata's and are fixed here.

Milestone M6 begins with the documentation: `docs/` is one Sphinx site built from the six packages'
trees plus adminata's own pages, and `make docs` builds it with warnings as errors.

What 1.0 inherits unported is written down rather than forgotten: 37 templates carrying an
`adminata: not yet ported` marker, listed in `tests/Contract/deferred-templates.txt` and asserted
by `DeferredTemplateTest`. They are the association edit flows, history and compare, ACL, preview,
mosaic and tree list modes, global search, the tab menu and four dashboard blocks.

### Added

- Hard fork of seven `sonata-project` packages into one Composer package `idct/adminata`, imported
  with `git subtree` at `admin-bundle` 4.43.0, `block-bundle` 5.4.0, `doctrine-extensions` 2.6.0,
  `doctrine-orm-admin-bundle` 4.21.0, `exporter` 3.4.0, `form-extensions` 2.7.0 and
  `twig-extensions` 2.6.0 (see [UPSTREAM.md](UPSTREAM.md)).
- Root `composer.json` replacing all seven package names at those exact versions, with the union of
  their requirements on PHP `^8.4` and Symfony `^7.4 || ^8.0`.
- `sonata_admin.theme` with `mode` (`light`, `dark` or `system`), `logo_dark` and `logo_icon`, and
  the Twig functions `sonata_theme()` and `sonata_html_dir()`. The mode is resolved server-side
  from the `sonata_theme` cookie so a page never paints the wrong theme first.
- One PHPUnit configuration for the seven imported suites and adminata's own, a `ReplaceTest` that
  proves the seven-way `replace` resolves next to `idct/sonata-admin-mongodb-bundle`, and seven CI
  workflows including a weekly watch on both the forked packages and the pinned dependency versions.
- Repository documents: `README.md`, `LICENSE`, `NOTICE`, `AGENTS.md`, `CONTRIBUTING.md`,
  `CHANGELOG-sonata.md`, `UPSTREAM.md`, `Makefile`, `upstream/{diff,sync}.sh`.
- Tailwind CSS v4 stylesheet built from TailAdmin v2.3.0's tokens, with a `.adm-*` component layer,
  a generated safelist and a CSS contract test; Stimulus 3.2 with an explicit controller registry,
  a Vite 8 build and a JavaScript contract snapshot. **No jQuery**, anywhere.
- A demo application under `tests/App`: the seven bundles wired the way an application wires them,
  two admins over MySQL and deterministic fixtures. `make demo` serves it; the functional, browser,
  visual and accessibility suites all drive it.
- Contract tests that freeze what a fork may not move: the markup hooks of every template group,
  every `@Sonata*/…` template path including the ones the MongoDB fork hard-codes, the
  configuration tree of six of the seven roots, and the list of templates 1.0 inherits unported.
- Browser and visual gates: Playwright screenshots per page, viewport and theme from a pinned
  container; `@axe-core/playwright` against WCAG 2.1 AA in both themes; `html-validate` over the
  rendered DOM; and a Panther harness that asserts an empty browser console.
- A CI job that installs `idct/adminata` into `idct/sonata-admin-mongodb-bundle` and runs its
  suite, so owner directive 7 is checked on every pull request rather than remembered.
- The TailAdmin shell: a fixed sidebar with collapsible groups, a sticky header with search, a
  light/dark/system theme toggle that never flashes the wrong theme, a skip link, a `<main>`
  landmark and a breadcrumb — plus the dashboard, its admin-list cards and the flash messages.
- Seven new Stimulus controllers: `sonata-layout`, `sonata-menu`, `sonata-theme`,
  `sonata-dropdown`, `sonata-modal`, `sonata-dismiss` and the shared list-mode partial they serve.
  Dialogs are native `<dialog>`, so the focus trap and Escape are the browser's.
- `Core/list_mode_buttons.html.twig`, shared by the standard and ajax layouts.
- The list: a card with a filter panel, a scrolling table, batch selection with an indeterminate
  header checkbox and shift-range selection, five pager templates, a native per-page select, an
  export menu, row and batch actions, and a batch confirmation page. A new additive block
  `list_after_table` replaces the footer an application used to hang its summaries off.
- `sonata-autocomplete`: a hand-written ARIA 1.2 combobox for `sonata_type_model_autocomplete`,
  single and multiple, with debouncing, remote paging, keyboard navigation and
  `aria-activedescendant`. select2 and jQuery are gone; the request parameters, the JSON response,
  `#{id}_autocomplete_input` and `#{id}_hidden_inputs_wrap` are not.
- `sonata-batch`, replacing the JavaScript upstream printed into every list page.
- The form theme: rows, labels, help (`help_html` kept), errors, and native widgets for text,
  textarea, select, checkbox, radio, file, money and percent — each recipe *appended* to whatever
  the application passed through `attr`, so a `data-controller` reaches the page untouched.
  Horizontal mode is a twelve-column grid. A control with errors carries `aria-invalid`.
- Native `<input type="date|time|datetime-local">` in place of Tempus Dominus, with the format
  derived from `datepicker_options.display.components` on both sides of the wire.
- The edit chrome: groups as cards in a twelve-column grid, a sticky action bar, the optimistic-lock
  conflict as a dismissible alert; the show page, the delete page and the action buttons.
- `tests/fixtures/js/*.html`, dumped from the demo application: every Stimulus controller is mounted
  against the markup the templates actually render, so a renamed target fails the JavaScript suite.
- **Clicking a list row opens the object.** `sonata-row-link` is one controller on the `<tbody>`,
  delegating, with the destination on each `<tr>`; the route is `default_admin_route`, then the
  other of `show`/`edit`, then nothing, checked per object. A click on a control, on a cell that
  exists only to hold controls, or one that ends a text selection is left alone; a middle or
  modified click opens a new tab. The row gets no `tabindex` and no `role="link"` — the accessible
  name would be the whole row, and the destination is already one Tab away. New option
  `sonata_admin.options.list_row_link`, default `true`.
- `adm-dropdown__menu-start`, for a dropdown whose button sits on the left of the page.
- `docs/`: one Sphinx site out of the six the packages carried, with five pages a fork has to
  write — theming, icons, the JavaScript API, compiling Tailwind yourself, and the porting status.
  `make docs` builds it with `-W`, `.readthedocs.yaml` sets `fail_on_warning`, and `bin/docs.sh`
  falls back to a container so the only thing a contributor needs installed is Docker.
- [MIGRATION.md](MIGRATION.md) and [UPGRADE-1.0.md](UPGRADE-1.0.md), written from what was actually
  executed rather than from the plan.
- `TranslationContractTest`: every key the English catalogue defines exists in every other. It
  found fourteen of adminata's own keys shipping English-only — including four `aria-label`s and
  the skip link, so a Polish screen-reader user heard `pager_navigation` — and seven more gaps
  inherited from upstream.
- A guard in `bin/check-css-contract.mjs` against the ordering trap the `dark` variant's
  zero specificity creates: a `:where(.dark…)` rule written before the plain rule it must override
  is dead CSS, and now fails the build.

### Changed

- `Menu/sonata_menu.html.twig`, `Core/{dashboard,add_block,user_block}.html.twig`,
  `Block/block_admin_list.html.twig`, `CRUD/dashboard__action*.html.twig`, both layouts and
  twig-extensions' `FlashMessage/render.html.twig` are rewritten. Every Twig block name, every
  markup hook of the compatibility contract and the `<li>` pass-through of `user_block`,
  `add_block` and `Button/*` are kept; `admin_lte_skin_class` and `bootlint` are gone.
- The header's user menu shows who is signed in even when `user_block` is empty, which is its
  default; upstream hid the menu entirely until an application wrote one.
- Page actions render as a button row. The upstream heuristic that folded two or more into an
  "Actions" dropdown by counting `</a>` in a captured string is gone.
- Font Awesome is imported into a `vendor` cascade layer, so adminata's utilities win over it.
- Block ids no longer contain the dot `uniqid('', true)` puts in: they reach the page as
  `id="cms-block-…"`, where a dot makes a selector nobody can write without escaping it.
- Date and time pickers exchange their value in the format a native HTML5 input uses, derived from
  `datepicker_options.display.components`. An explicit `format` is refused, the way Symfony's
  `DateType` refuses one when `html5` is enabled.
- Default assets are `bundles/sonataadmin/app.css`, `bundles/sonataadmin/fontawesome.css` and
  `bundles/sonataadmin/app.js`; the AdminLTE skin stylesheet is no longer appended.
- A form group's `box_class` defaults to an empty string, and a dashboard block's `class` to
  `md:col-span-4`.
- The test suites, the demo application and CI run on **MySQL**; adminata 1.0 supports MySQL,
  MariaDB and Percona, and not SQLite.
- The admin content column is no longer capped at 1536px. TailAdmin's cap is written for
  dashboards; an admin list is sixteen columns of data, and past the cap the table scrolled inside
  a box with empty page beside it.
- A list's batch, select and action columns take their content width, and the row actions no longer
  wrap. On a sixteen-column list the action column had been squeezed to a fraction of the row and
  five icon buttons stacked into five lines — 221px rows, four to a screen.
- A wide table scrolls inside its own box rather than scrolling the page, which needed three things
  to be true: the table inside `adm-table-scroll`, `min-w-0` on the card, and `position: relative`
  on the scroll box so the `sr-only` labels stop being positioned against the page.
- `adm-card-body` draws no rule when it is the card's first child, and `adm-table-wrap` no longer
  draws a second border inside the card's.
- Row actions have a resting surface inside the actions column: a bare glyph in a table cell reads
  as content rather than as a control.
- Filters and the list share one grid, so the `gap` puts air between them.

### Removed

- `sonata_admin.options.skin`, `use_select2`, `use_icheck` and `use_bootlint`. They are removed, not
  deprecated: leaving them in `sonata_admin.yaml` is a container build error.
- `Sonata\Form\Date\JavaScriptFormatConverter`, form-extensions' `assets/` and its published
  `Resources/public/`, twig-extensions' `flashmessage.css`, the prebuilt AdminLTE skins and select2
  locales, the packages' Webpack/Babel/ESLint/Stylelint/Prettier configuration, and the cookbook
  recipes for bootlint, iCheck, jQuery UI and select2.

[Unreleased]: https://github.com/ideaconnect/adminata/commits/main
