# adminata — implementation plan

`idct/adminata` is a drop-in replacement for `sonata-project/admin-bundle` 4.43.0 whose user
interface is rebuilt on Tailwind CSS v4 using the TailAdmin (free, MIT) design system. The PHP
layer stays Sonata Admin; the Twig templates, CSS and JavaScript are rewritten.

This folder is the plan. It was produced on 2026-09-04 from a full read of Sonata Admin 4.43.0
(and its 5.x branch), TailAdmin v2.3.0 (HTML, React and Next.js editions), the related Sonata
packages (ORM admin bundle, form-extensions, block-bundle, twig-extensions), the conventions of
`idct/sonata-admin-mongodb-bundle`, and a production Sonata app on this machine used as the
acceptance fixture. The raw research reports (about 1.2 MB of Markdown with file:line evidence)
are kept under [research/](research/) and are cited throughout.

## Reading order

| # | Document | What it answers |
|---|---|---|
| 0 | [00-executive-summary.md](00-executive-summary.md) | What we build, how, in what order, and what is not yet verified |
| 1 | [01-architecture-decisions.md](01-architecture-decisions.md) | Every architectural decision (D01–D34) with rationale and status |
| 2 | [02-compatibility-contract.md](02-compatibility-contract.md) | The exact surface that must stay identical for "drop-in" to hold |
| 3 | [03-template-migration-map.md](03-template-migration-map.md) | Template-by-template mapping from Bootstrap/AdminLTE to TailAdmin |
| 4 | [04-css-architecture.md](04-css-architecture.md) | Tailwind v4 file layout, tokens, dark mode, compat layer, build |
| 5 | [05-js-architecture.md](05-js-architecture.md) | Stimulus controller registry, `window.Admin` facade, jQuery policy |
| 6 | [06-forms-datepicker-security.md](06-forms-datepicker-security.md) | Form theme, association modals, autocomplete, datepicker, CSRF/CSP |
| 7 | [07-packaging-and-project-setup.md](07-packaging-and-project-setup.md) | Composer `replace`, repository skeleton, quality gates, CI, licensing, upstream sync |
| 8 | [08-testing-and-qa.md](08-testing-and-qa.md) | Reuse of Sonata's tests, demo app, Panther/Playwright, parity and contract tests |
| 9 | [09-roadmap.md](09-roadmap.md) | Phases, tasks, exit gates, indicative effort |
| 10 | [10-migration-guide-outline.md](10-migration-guide-outline.md) | `UPGRADE-1.0.md` outline and the `adminata:audit-overrides` command |
| 11 | [11-risks-and-open-questions.md](11-risks-and-open-questions.md) | Risk register, owner decisions, things to verify before coding |
| 12 | [12-docs-plan.md](12-docs-plan.md) | Which Sonata docs pages change and which new pages are needed |
| A | [appendix-A-twig-blocks.md](appendix-A-twig-blocks.md) | All 138 Twig block names per template (generated from source) |
| B | [appendix-B-inventory.md](appendix-B-inventory.md) | Inventory of Sonata 4.43.0 and TailAdmin material |

## Path aliases used in the documents

| Alias | Meaning |
|---|---|
| `S/` | `sonata-project/admin-bundle` 4.43.0 source tree (tag `4.43.0`, released 2026-06-03) |
| `T/` | TailAdmin free HTML/Alpine template v2.3.0 (`TailAdmin/tailadmin-free-tailwind-dashboard-template`, `main`) |
| `TR/`, `TN/` | TailAdmin free React and Next.js templates (component references) |
| `ORM/`, `FE/`, `BB/`, `TW/` | `doctrine-orm-admin-bundle` (latest), `form-extensions` 2.7.0, `block-bundle` 5.4.0, `twig-extensions` 2.6.0 |
| `MDB/` | `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle` (the user's hard fork; conventions and Panther harness) |
| `APP/` | `/home/bartosz/dev/r3/recomaty-panel` (production Sonata 4.43.0 app used as the drop-in acceptance fixture) |
| `R/` | [research/](research/) reports |

## Status

- Research: complete for all 131 templates, the PHP coupling surface, packaging, CSS and JS
  architecture, and the real-app audit. One critique round reconciled 25 contradictions.
- Not completed (session limit): the second critique round, six narrower gap reports, and the
  three adversarial reviews of the packaging recommendation. Their subjects are covered inline in
  the plan with explicit "verify before implementation" flags (see document 11).
- No code has been written. The `adminata` repository does not exist yet.
