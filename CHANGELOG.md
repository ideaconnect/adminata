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

The list and the form are still Sonata's Bootstrap templates and render unstyled, which is
deliberate, written down and asserted: see `tests/Contract/deferred-templates.txt`,
`tests/Visual/support/findings.json` and the `legacy-ui` PHPUnit group.

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

### Removed

- `sonata_admin.options.skin`, `use_select2`, `use_icheck` and `use_bootlint`. They are removed, not
  deprecated: leaving them in `sonata_admin.yaml` is a container build error.
- `Sonata\Form\Date\JavaScriptFormatConverter`, form-extensions' `assets/` and its published
  `Resources/public/`, twig-extensions' `flashmessage.css`, the prebuilt AdminLTE skins and select2
  locales, the packages' Webpack/Babel/ESLint/Stylelint/Prettier configuration, and the cookbook
  recipes for bootlint, iCheck, jQuery UI and select2.

[Unreleased]: https://github.com/ideaconnect/adminata/commits/main
