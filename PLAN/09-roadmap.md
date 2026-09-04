# 09 — Roadmap

Indicative effort for one senior engineer familiar with Sonata; weeks are calendar weeks of
focused work. Every phase ends only when the definition of done in document 07 §6 holds for the
code it touched, and every phase exit is a **milestone**: `main` is pushed to
`ideaconnect/adminata` (P9). Scope is fixed by appendix C; anything outside it goes to the backlog.

## Phase 0 — Bootstrap (weeks 1–2, 1.5 weeks)

1. Repository already initialised with the plan (2026-09-04). Add the seven `upstream-<name>`
   remotes; `git subtree add --prefix=packages/<name>` at the tags of document 07 §2 (history kept).
2. Root `composer.json` (document 07 §3): `idct/adminata`, `replace` × 7, autoload × 7, union
   `require`, floors (P4); delete the seven per-package `composer.json` files; `LICENSE`, `NOTICE`,
   `README.md` hard-fork banner, `AGENTS.md` skeleton, `UPSTREAM.md` (seven entries).
3. Tooling at the versions of document 07 §2: PHPUnit 13 (per-package suites), PHPStan 2.2
   (level 8, baselines imported and trimmed), Rector 2.6 as **one mechanical commit per package**,
   PHP-CS-Fixer 3.95, composer-normalize, yamllint, Makefile,
   `.github/workflows/{test,qa,lint,symfony-lint,stale,upstream-watch,versions-watch}.yaml`, Dependabot.
4. PHP changes of P6 (admin config nodes removed, grid/box defaults, asset defaults, skin append
   removed, `adminata.theme.mode`, theme cookie helper, form-extensions `BasePickerType` HTML5
   formats, `SonataFormExtension` asset registration removed) with their tests updated.
5. Delete form-extensions' `assets/` and `Resources/public/`, twig-extensions' `Resources/public/`,
   the `MopaBootstrapBundle` switch and the Symfony 6.4 shims.
6. All inherited test files of the seven packages green on PHP 8.4/8.5 × Symfony 7.4/8.1 (templates
   still untouched).
7. Scratch app: `composer require idct/adminata idct/sonata-admin-mongodb-bundle
   sonata-project/doctrine-orm-admin-bundle`-style resolution proven (`ReplaceTest`); the
   `--no-plugins` migration path exercised on a copy of `APP/`'s `composer.json`.

Exit gate: `make lint phpstan rector test` green for all suites; scratch-app resolution proven;
**milestone push**.

## Phase 1 — Foundations (weeks 2–3, 1.5 weeks)

1. Tailwind 4.3 semantics fixture proving T1–T11 (document 04 §4); pin `tailwindcss` exactly.
2. Vite 8 build with the entries of document 05 §7 writing into
   `packages/admin-bundle/src/Resources/public`; `frontend.yaml` with build-freshness,
   `size-limit` and the `npm ls jquery` gate; Node 24/26 matrix.
3. `theme.css`, `base.css`, `fonts.css` (Outfit), z-index ladder, `fontawesome.css` (FA7 Free,
   woff2 only).
4. `.adm-*` component files, safelist generator, `contract.json`, `css-contract` CI job.
5. JS core: `registry.js`, tolerant `Config.param`, the 9 inherited controllers (jQuery line removed
   from `sonata-edit`), Vitest harness + fixture dumper, JS contract snapshot.
6. Contract tests: `HookContractTest`, `TemplatePathTest`, `DeferredTemplateTest`,
   `ConfigContractTest`, `ReplaceTest`; the 36 deferred templates get their marker comment.
7. Demo ORM app skeleton (kernel, sqlite, fixtures, first admins); Playwright + axe harness;
   Panther harness from the MongoDB fork; `mongo-compat.yaml` PR job (resolution + unit suite).

Exit gate: reproducible build; contract suites pass for the untouched templates; CSS budgets met by
the empty shell; MongoDB fork unit suite green; **milestone push**.

## Phase 2 — Shell (weeks 4–5, 1.5 weeks)

Templates of document 03 §A: `standard_layout`, `ajax_layout`, `empty_layout`, sidebar menu with
section headers, add block, user block, dashboard + admin-list block + dashboard actions,
breadcrumb wrapper, twig-extensions' flash template rewritten in place, block-bundle's `block_base`
verified, `Core/list_mode_buttons`; controllers `sonata-layout`, `sonata-menu`, `sonata-dropdown`,
`sonata-modal`, `sonata-theme`, `sonata-dismiss`; login-style page support (`sonata_header`
collapse). Functional `MenuTest`, `DashboardActionTest`, `BreadcrumbsRuntimeTest`, twig-extensions
flash tests green; Playwright baselines for dashboard, empty layout, login-style page in both
themes; axe clean on the shell. **Milestone push.**

## Phase 3 — List (weeks 6–7)

Templates of document 03 §B: `base_list`, `list`, `base_list_field`, rows, 14 typed list and 12
display templates, row actions (5), batch/select, pager (5), filter theme (admin + ORM copy
verified), batch confirmation, association list templates (4); `list_after_table`; controllers
`sonata-batch`, `sonata-autocomplete` (filter context first); demo admins covering every filter and
cell type; Panther flows (filters, batch shift-range, per-page, combobox keyboard, XHR list). Exit:
`RenderElementRuntimeTest` re-baselined with the frozen envelope; hook contract complete for list
templates; **milestone push**.

## Phase 4 — Forms and show (weeks 8–9)

Templates of document 03 §C and §D: form theme (30 blocks + collection rows), form-extensions'
`datepicker.html.twig` rewritten in place (native inputs), ORM form theme copy verified,
autocomplete widget (form context), edit chrome (groups grid, sticky actions), show pages, typed show
templates (13 + 4), buttons and action bar, delete, dismissable errors; `tests/Form/*` and
form-extensions' widget tests rewritten; demo admins covering every form type of appendix C §2;
Panther flows (collection add/delete + sub-form pickers, confirm-exit, lock error, native date/time
round trips, autocomplete single and multiple, ux-autocomplete coexistence). Exit: `tests/Form`
green; Playwright baselines for every 1.0 page × viewport × theme; axe no serious violations;
functional `CRUDControllerTest` green; **milestone push**.

## Phase 5 — recomaty-panel migration and acceptance (weeks 10–11)

1. Migrate `APP/` on a branch following document 10 §1–§2 (about 45 hours, itemised there).
2. Run the Behat suite and the added BrowserKit/Panther scenarios (document 08 §6); fix adminata
   findings; record the real effort.
3. `MIGRATION.md` finalised from the executed checklist; `UPGRADE-1.0.md` generic notes.
4. MongoDB fork nightly Panther job wired (informational).

Exit gate: every scenario green in light and dark mode; no console errors; visual sign-off by the
owner; **milestone push** and `v1.0.0-rc1` tag.

## Phase 6 — Release (week 12, half week)

Pruned Sphinx docs (document 12), `CHANGELOG.md`, `v1.0.0-rc1` → `v1.0.0`, Packagist publish,
announcement. **Milestone push and tag.**

## Post-1.0 backlog (ordered by likely demand)

1. Association widgets **without AJAX submission** (S5, J9): list selection in a `<dialog>` loaded
   with `fetch` GET, create/edit as full pages with a return parameter; templates
   `CRUD/Association/edit_*` (11), `sonata-association`, `sonata-tabs`; the ORM `block_audit`;
   this unblocks the MongoDB fork's Panther suite once its scenarios are adapted to the new flow.
2. `sonata-datepicker` progressive enhancement (`vanilla-calendar-pro`).
3. History and compare pages; ACL pages; preview; subclass selection; mosaic; global search;
   tab menu / child admins; the four admin dashboard blocks and block-bundle's RSS/side-menu
   templates; `sonata-editable`; `sonata-treeview`; `sonata-choice-field-mask`; sortable
   collections (SortableJS).
4. Optional select enhancement (`sonata-select`, Tom Select 2.6.2).
5. ESM entry (`startAdminata`), AssetMapper mapping and docs; Flex recipe; tooltips; toast flash mode.
6. Monthly upstream sync cadence for the seven packages; Infection nightly; `BEST_VERSION.md`.

## Indicative effort

| Phase | Days |
|---|---|
| 0 Bootstrap | 7 |
| 1 Foundations | 7 |
| 2 Shell | 7 |
| 3 List | 10 |
| 4 Forms and show | 10 |
| 5 App migration and acceptance | 10 |
| 6 Release | 3 |
| **Total** | **≈ 54 days** |

Assumptions: free TailAdmin edition only; Stimulus-only; no PHP changes beyond P6; scope as
appendix C; owner decisions of document 11 §2 taken during phase 0.
