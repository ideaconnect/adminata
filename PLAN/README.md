# adminata — implementation plan (v3, 2026-09-04)

`idct/adminata` is a hard fork of the Sonata Admin stack whose Twig templates, CSS and JavaScript
are replaced by a Tailwind CSS v4 / TailAdmin user interface. One repository and one Composer
package ship the seven Sonata packages the first release needs (`admin-bundle`, `block-bundle`,
`doctrine-extensions`, `doctrine-orm-admin-bundle`, `exporter`, `form-extensions`,
`twig-extensions`), copied one-to-one except for their Bootstrap templates. The PHP layer stays
Sonata's and the package installs in place of the originals (Composer `replace`), so the owner's
`idct/sonata-admin-mongodb-bundle` keeps working unchanged.

Repository: `git@github.com:ideaconnect/adminata.git` (branch `main`; pushed after each milestone).

## Owner directives (2026-09-04)

The v1 plan (archived under [archive/v1-2026-09-04/](archive/v1-2026-09-04/); v2 is the previous
git commit) was revised under these directives:

1. **Hard fork, not overlay.** Bootstrap Twig templates are replaced by Tailwind templates. No
   Bootstrap compatibility stylesheet, no Bootstrap data-API shim, no dual class vocabulary.
2. **No jQuery at all.** Not part of the bundle, not used by it, not bridged. Whenever a library
   would pull in jQuery, first check whether Tailwind/TailAdmin already covers the need in CSS; if
   not, use a modern, popular vanilla library. Plain DOM code inside Stimulus controllers.
3. **Latest releases** of every library and tool, verified on Packagist, npm and nodejs.org on
   2026-09-04 ([07 §2](07-packaging-and-project-setup.md)). Nothing inherits Sonata's pinned versions.
4. **No compatibility layers; the first release supports recomaty-panel.** 1.0 covers exactly the
   Sonata features the production app `~/dev/r3/recomaty-panel-clean` uses
   ([appendix C](appendix-C-recomaty-panel-scope.md)). Everything else is built when first needed.
5. **The `ajaxSubmit` feature is dropped.** No AJAX submission of association forms, now or later.
6. **Seven Sonata packages in one project.** No SonataUserBundle. `admin-bundle`, `block-bundle`,
   `doctrine-extensions`, `doctrine-orm-admin-bundle`, `exporter`, `form-extensions` and
   `twig-extensions` are shipped by adminata itself, copied one-to-one; their Bootstrap templates
   are adapted to Tailwind.
7. **`idct/sonata-admin-mongodb-bundle` must keep working** against adminata.
8. **Git**: the project lives in `ideaconnect/adminata`; changes are pushed after milestones.

## Reading order

| # | Document | What it answers |
|---|---|---|
| 0 | [00-executive-summary.md](00-executive-summary.md) | What we build, in what order, what is not yet verified |
| 1 | [01-architecture-decisions.md](01-architecture-decisions.md) | Every decision with rationale and status, and what earlier decisions were reversed |
| 2 | [02-compatibility-contract.md](02-compatibility-contract.md) | The interfaces that stay identical (packages, PHP, config, paths, hooks, JS) |
| 3 | [03-template-migration-map.md](03-template-migration-map.md) | The 100 templates rewritten for 1.0, the 12 copied unchanged and the 36 deferred |
| 4 | [04-css-architecture.md](04-css-architecture.md) | Tailwind v4 layout, tokens, dark mode, icons, fonts, build |
| 5 | [05-js-architecture.md](05-js-architecture.md) | Stimulus controllers, library policy, events, build, coexistence with the app's own Stimulus |
| 6 | [06-forms-datepicker-security.md](06-forms-datepicker-security.md) | Form theme, collections, autocomplete, native date inputs, CSRF/CSP |
| 7 | [07-packaging-and-project-setup.md](07-packaging-and-project-setup.md) | One package replacing seven, verified versions, repository layout, git workflow, quality gates, CI, licensing, upstream sync |
| 8 | [08-testing-and-qa.md](08-testing-and-qa.md) | Inherited suites of all seven packages, demo app, Panther/Playwright, MongoDB fork job, acceptance in the app |
| 9 | [09-roadmap.md](09-roadmap.md) | Phases, exit gates, milestone pushes, effort |
| 10 | [10-migration-guide-outline.md](10-migration-guide-outline.md) | Step-by-step migration of recomaty-panel; generic upgrade notes |
| 11 | [11-risks-and-open-questions.md](11-risks-and-open-questions.md) | Risk register, remaining owner decisions, things to verify first |
| 12 | [12-docs-plan.md](12-docs-plan.md) | Repository documents and what happens to the seven Sphinx doc sets |
| A | [appendix-A-twig-blocks.md](appendix-A-twig-blocks.md) | All 138 admin-bundle Twig block names per template (generated from source) |
| B | [appendix-B-inventory.md](appendix-B-inventory.md) | Inventory of the seven Sonata packages and the TailAdmin material |
| C | [appendix-C-recomaty-panel-scope.md](appendix-C-recomaty-panel-scope.md) | What recomaty-panel uses, and the resulting 1.0 scope |

## Path aliases used in the documents

| Alias | Meaning |
|---|---|
| `S/` | `sonata-project/admin-bundle` 4.43.0 source tree (vendored copy: `MDB/vendor/sonata-project/admin-bundle`); in adminata: `packages/admin-bundle/` |
| `ORM/`, `FE/`, `BB/`, `TW/`, `EX/`, `DE/` | `doctrine-orm-admin-bundle` 4.21.0, `form-extensions` 2.7.0, `block-bundle` 5.4.0, `twig-extensions` 2.6.0, `exporter` 3.4.0, `doctrine-extensions` 2.6.0 (vendored under `APP/vendor/sonata-project/`); in adminata: `packages/<name>/` |
| `T/` | TailAdmin free HTML/Alpine template v2.3.0 (`TailAdmin/tailadmin-free-tailwind-dashboard-template`, `main`) |
| `TR/`, `TN/` | TailAdmin free React and Next.js templates (component references) |
| `MDB/` | `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle` (the owner's hard fork, v5.2.2; stays a separate package) |
| `APP/` | `/home/bartosz/dev/r3/recomaty-panel-clean` (production Sonata 4.43.0 app; the 1.0 acceptance target) |
| `R/` | [research/](research/) reports (written against the older `recomaty-panel` checkout; appendix C re-verifies against `-clean`) |

## Status

- Research: complete (131 admin-bundle templates, the 17 templates of the other six packages, PHP
  coupling surface, packaging, CSS and JS architecture, real-app audit). Appendix C re-audited the
  `-clean` checkout on 2026-09-04.
- Versions: all library and tool versions verified online on 2026-09-04.
- Not verified: the Tailwind v4 emission semantics ([04 §4](04-css-architecture.md)); Flex's
  handling of a multi-bundle package (document 11 §3).
- Repository initialised on 2026-09-04 with the plan; no code has been written.
