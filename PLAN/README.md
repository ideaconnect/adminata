# adminata — implementation plan (v2, 2026-09-04)

`idct/adminata` is a hard fork of `sonata-project/admin-bundle` 4.43.0 whose Twig templates, CSS and
JavaScript are replaced by a Tailwind CSS v4 / TailAdmin user interface. The PHP layer stays Sonata
Admin and the package installs in place of the original (Composer `replace`), so
`sonata-project/doctrine-orm-admin-bundle` and `idct/sonata-admin-mongodb-bundle` keep working.

## Owner directives (2026-09-04)

The v1 plan (archived under [archive/v1-2026-09-04/](archive/v1-2026-09-04/)) was revised under
four directives from the owner:

1. **Hard fork, not overlay.** Bootstrap Twig templates are replaced by Tailwind templates. No
   Bootstrap compatibility stylesheet, no Bootstrap data-API shim, no dual class vocabulary.
2. **No jQuery.** Not shipped, not bridged, not accommodated. Plain DOM code inside Stimulus controllers.
3. **Latest releases** of every library and tool, verified on Packagist, npm and nodejs.org on
   2026-09-04 ([07 §2](07-packaging-and-project-setup.md)). Nothing inherits Sonata's pinned versions.
4. **No compatibility layers; the first release supports recomaty-panel.** 1.0 covers exactly the
   Sonata features the production app `~/dev/r3/recomaty-panel-clean` uses ([appendix C](appendix-C-recomaty-panel-scope.md)).
   Everything else is built when first needed.

## Reading order

| # | Document | What it answers |
|---|---|---|
| 0 | [00-executive-summary.md](00-executive-summary.md) | What we build, in what order, what is not yet verified |
| 1 | [01-architecture-decisions.md](01-architecture-decisions.md) | Every decision with rationale and status, and what v1 decisions were reversed |
| 2 | [02-compatibility-contract.md](02-compatibility-contract.md) | The interfaces that stay identical (PHP, config, paths, hooks, JS) |
| 3 | [03-template-migration-map.md](03-template-migration-map.md) | The 98 templates rewritten for 1.0 and the 33 deferred ones |
| 4 | [04-css-architecture.md](04-css-architecture.md) | Tailwind v4 layout, tokens, dark mode, icons, fonts, build |
| 5 | [05-js-architecture.md](05-js-architecture.md) | Stimulus controllers, events, build, coexistence with the app's own Stimulus |
| 6 | [06-forms-datepicker-security.md](06-forms-datepicker-security.md) | Form theme, collections, autocomplete, native date inputs, CSRF/CSP |
| 7 | [07-packaging-and-project-setup.md](07-packaging-and-project-setup.md) | Composer `replace`, verified versions, repository, quality gates, CI, licensing, upstream sync |
| 8 | [08-testing-and-qa.md](08-testing-and-qa.md) | Inherited tests, demo app, Panther/Playwright, acceptance in the app |
| 9 | [09-roadmap.md](09-roadmap.md) | Phases, exit gates, effort |
| 10 | [10-migration-guide-outline.md](10-migration-guide-outline.md) | Step-by-step migration of recomaty-panel; generic upgrade notes |
| 11 | [11-risks-and-open-questions.md](11-risks-and-open-questions.md) | Risk register, remaining owner decisions, things to verify first |
| 12 | [12-docs-plan.md](12-docs-plan.md) | Repository documents and what happens to Sonata's Sphinx docs |
| A | [appendix-A-twig-blocks.md](appendix-A-twig-blocks.md) | All 138 Twig block names per template (generated from source) |
| B | [appendix-B-inventory.md](appendix-B-inventory.md) | Inventory of Sonata 4.43.0 and TailAdmin material |
| C | [appendix-C-recomaty-panel-scope.md](appendix-C-recomaty-panel-scope.md) | What recomaty-panel uses, and the resulting 1.0 scope |

## Path aliases used in the documents

| Alias | Meaning |
|---|---|
| `S/` | `sonata-project/admin-bundle` 4.43.0 source tree (vendored copy: `MDB/vendor/sonata-project/admin-bundle`) |
| `T/` | TailAdmin free HTML/Alpine template v2.3.0 (`TailAdmin/tailadmin-free-tailwind-dashboard-template`, `main`) |
| `TR/`, `TN/` | TailAdmin free React and Next.js templates (component references) |
| `ORM/`, `FE/`, `BB/`, `TW/` | `doctrine-orm-admin-bundle` 4.21.0, `form-extensions` 2.7.0, `block-bundle` 5.4.0, `twig-extensions` 2.6.0 |
| `MDB/` | `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle` (the owner's hard fork, v5.2.2; conventions and Panther harness) |
| `APP/` | `/home/bartosz/dev/r3/recomaty-panel-clean` (production Sonata 4.43.0 app; the 1.0 acceptance target) |
| `R/` | [research/](research/) reports (written against the older `recomaty-panel` checkout; appendix C re-verifies against `-clean`) |

## Status

- Research: complete (131 templates, PHP coupling surface, packaging, CSS and JS architecture,
  real-app audit). Appendix C re-audited the `-clean` checkout on 2026-09-04.
- Versions: all library and tool versions verified online on 2026-09-04.
- Not verified: the twelve Tailwind v4 emission semantics ([04 §4](04-css-architecture.md)) — first
  task of phase 1.
- No code has been written. The `adminata` repository does not exist yet.
