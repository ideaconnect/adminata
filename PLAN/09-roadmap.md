# 09 — Roadmap

Indicative effort for one senior engineer familiar with Sonata; weeks are calendar weeks of
focused work. Every phase ends only when the definition of done in document 07 §6 holds for the
code it touched. Scope is fixed by appendix C; anything outside it goes to the backlog.

## Phase 0 — Bootstrap (week 1)

1. Full clone of `sonata-project/SonataAdminBundle`; `main` from tag `4.43.0`; read-only
   `pristine/4.x`; `upstream` remote.
2. `composer.json`: `idct/adminata`, `replace: 4.43.0`, floors (P4), sibling floors (P5), scripts,
   authors; `LICENSE`, `NOTICE`, `README.md` hard-fork banner, `AGENTS.md` skeleton, `UPSTREAM.md`.
3. Tooling at the versions of document 07 §2: PHPUnit 13, PHPStan 2.2 (level 8, trimmed baseline),
   Rector 2.6 as **one mechanical commit**, PHP-CS-Fixer 3.95, composer-normalize, yamllint,
   Makefile, `.github/workflows/{test,qa,lint,symfony-lint,stale,upstream-watch,versions-watch}.yaml`,
   Dependabot.
4. PHP changes of P6 (config nodes removed, grid/box defaults, asset defaults, skin append removed,
   `adminata.theme.mode`, theme cookie helper) with their tests updated.
5. All inherited test files green on PHP 8.4/8.5 × Symfony 7.4/8.1 (templates still untouched).
6. Remove the `MopaBootstrapBundle` switch and the Symfony 6.4 shims.
7. Scratch app: `composer require idct/adminata` with the ORM bundle resolves; the `--no-plugins`
   migration path exercised on a copy of `APP/`'s `composer.json`.

Exit gate: `make lint phpstan rector test` green; scratch-app resolution proven.

## Phase 1 — Foundations (weeks 2–3, 1.5 weeks)

1. Tailwind 4.3 semantics fixture proving T1–T11 (document 04 §4); pin `tailwindcss` exactly.
2. Vite 8 build with the entries of document 05 §7; `frontend.yaml` with build-freshness and
   `size-limit`; Node 24/26 matrix.
3. `theme.css`, `base.css`, `fonts.css` (Outfit), z-index ladder, `fontawesome.css` (FA7 Free,
   woff2 only).
4. `.adm-*` component files, safelist generator, `contract.json`, `css-contract` CI job.
5. JS core: `registry.js`, tolerant `Config.param`, the 9 inherited controllers (jQuery line removed
   from `sonata-edit`), Vitest harness + fixture dumper, JS contract snapshot.
6. Contract tests: `HookContractTest`, `TemplatePathTest`, `DeferredTemplateTest`,
   `ConfigContractTest` scaffolds; the 33 deferred templates get their marker comment.
7. Demo ORM app skeleton (kernel, sqlite, fixtures, first admins); Playwright + axe harness;
   Panther harness from the MongoDB fork.

Exit gate: reproducible build; contract suites pass for the untouched templates; CSS budgets met by
the empty shell.

## Phase 2 — Shell (weeks 4–5, 1.5 weeks)

Templates of document 03 §A: `standard_layout`, `ajax_layout`, `empty_layout`, sidebar menu with
section headers, add block, user block, dashboard + admin-list block + dashboard actions,
breadcrumb wrapper, flash template, `Core/list_mode_buttons`; controllers `sonata-layout`,
`sonata-menu`, `sonata-dropdown`, `sonata-modal`, `sonata-theme`, `sonata-dismiss`; login-style page
support (`sonata_header` collapse). Functional `MenuTest`, `DashboardActionTest`,
`BreadcrumbsRuntimeTest` green; Playwright baselines for dashboard, empty layout, login-style page
in both themes; axe clean on the shell.

## Phase 3 — List (weeks 6–7)

Templates of document 03 §B: `base_list`, `list`, `base_list_field`, rows, 14 typed list and 12
display templates, row actions (5), batch/select, pager (5), filter theme, batch confirmation,
association list templates (4); `list_after_table`; controllers `sonata-batch`,
`sonata-autocomplete` (filter context first); demo admins covering every filter and cell type;
Panther flows (filters, batch shift-range, per-page, combobox keyboard, XHR list). Exit:
`RenderElementRuntimeTest` re-baselined with the frozen envelope; hook contract complete for list
templates.

## Phase 4 — Forms and show (weeks 8–9)

Templates of document 03 §C and §D: form theme (30 blocks + native picker blocks + collection
rows), autocomplete widget (form context), edit chrome (groups grid, sticky actions), show pages,
typed show templates (13 + 4), buttons and action bar, delete, dismissable errors; `tests/Form/*`
rewritten; demo admins covering every form type of appendix C §2; Panther flows (collection
add/delete + sub-form pickers, confirm-exit, lock error, native date/time round trips, autocomplete
single and multiple, ux-autocomplete coexistence). Exit: `tests/Form` green; Playwright baselines
for every 1.0 page × viewport × theme; axe no serious violations; functional `CRUDControllerTest`
green.

## Phase 5 — recomaty-panel migration and acceptance (weeks 10–11)

1. Migrate `APP/` on a branch following document 10 §1–§2 (about 46 hours, itemised there).
2. Run the Behat suite and the added BrowserKit/Panther scenarios (document 08 §6); fix adminata
   findings; record the real effort.
3. `MIGRATION.md` finalised from the executed checklist; `UPGRADE-1.0.md` generic notes.
4. MongoDB fork nightly job wired (informational).

Exit gate: every scenario green in light and dark mode; no console errors; visual sign-off by the owner.

## Phase 6 — Release (week 12, half week)

Pruned Sphinx docs (document 12), `CHANGELOG.md`, `v1.0.0-rc1` → `v1.0.0`, Packagist publish,
announcement.

## Post-1.0 backlog (ordered by likely demand)

1. Association flows: `ModelListType`/`ModelType`/`AdminType` templates (11), `sonata-association`,
   `sonata-tabs`, `sonata-inline-row`, `sonata-sortable` (SortableJS 1.15.7) — also unblocks the
   MongoDB fork's suite.
2. `sonata-datepicker` progressive enhancement (`vanilla-calendar-pro`).
3. History and compare pages; ACL pages; preview; subclass selection; mosaic; global search;
   tab menu / child admins; the four dashboard blocks; `sonata-editable`; `sonata-treeview`;
   `sonata-choice-field-mask`.
4. Optional select enhancement (`sonata-select`, Tom Select 2.6.2).
5. ESM entry (`startAdminata`), AssetMapper mapping and docs; Flex recipe; tooltips; toast flash mode.
6. Monthly upstream sync cadence; Infection nightly; `BEST_VERSION.md` for the inherited PHP layer.

## Indicative effort

| Phase | Days |
|---|---|
| 0 Bootstrap | 5 |
| 1 Foundations | 7 |
| 2 Shell | 7 |
| 3 List | 10 |
| 4 Forms and show | 10 |
| 5 App migration and acceptance | 10 |
| 6 Release | 3 |
| **Total** | **≈ 52 days** |

Assumptions: free TailAdmin edition only; Stimulus-only; no PHP changes beyond P6; scope as
appendix C; owner decisions of document 11 §2 taken during phase 0.
