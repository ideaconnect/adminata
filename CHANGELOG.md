# Changelog

All notable changes to `idct/adminata` are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html) — see the versioning contract in
[CONTRIBUTING.md](CONTRIBUTING.md).

Changes inherited from the forked Sonata packages are listed separately in
[CHANGELOG-sonata.md](CHANGELOG-sonata.md).

## [Unreleased]

### Added

- Hard fork of seven `sonata-project` packages into one Composer package `idct/adminata`, imported
  with `git subtree` at `admin-bundle` 4.43.0, `block-bundle` 5.4.0, `doctrine-extensions` 2.6.0,
  `doctrine-orm-admin-bundle` 4.21.0, `exporter` 3.4.0, `form-extensions` 2.7.0 and
  `twig-extensions` 2.6.0 (see [UPSTREAM.md](UPSTREAM.md)).
- Root `composer.json` replacing all seven package names at those exact versions, with the union of
  their requirements on PHP `^8.4` and Symfony `^7.4 || ^8.0`.
- Repository documents: `README.md`, `LICENSE`, `NOTICE`, `AGENTS.md`, `CONTRIBUTING.md`,
  `CHANGELOG-sonata.md`, `UPSTREAM.md`.

### Changed

- Nothing released yet. adminata is pre-1.0 and under construction; follow
  [PROJECT_PLAN.md](PROJECT_PLAN.md).

[Unreleased]: https://github.com/ideaconnect/adminata/commits/main
