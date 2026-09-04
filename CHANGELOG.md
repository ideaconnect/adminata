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
adminata's own tooling, and the PHP changes the Tailwind interface forces are in.

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

### Changed

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
