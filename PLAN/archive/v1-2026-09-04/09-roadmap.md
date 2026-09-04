# 09 — Roadmap

Indicative effort for one senior engineer familiar with Sonata; weeks are calendar weeks of
focused work. Every phase ends only when the definition of done in document 07 §5 holds for the
code it touched. Owner decisions (document 11 §2) should be taken before phase 1 starts.

## Phase 0 — Bootstrap (week 1)

1. Full clone of `sonata-project/SonataAdminBundle`; `main` from tag `4.43.0`; read-only
   `pristine/4.x`; `upstream` remote.
2. `composer.json`: `idct/adminata`, `replace: 4.43.0`, floors (D05), scripts, authors;
   `LICENSE`, `NOTICE`, `README.md` hard-fork banner, `AGENTS.md` skeleton, `UPSTREAM.md`.
3. Tooling from the MongoDB fork: PHPUnit 12, PHPStan (level 8, trimmed baseline), Rector
   (`UP_TO_PHP_84`, `PHPUNIT_120`) as **one mechanical commit**, PHP-CS-Fixer, composer-normalize,
   yamllint, Makefile, `.github/workflows/{test,qa,lint,symfony-lint,stale}.yaml`, Dependabot.
4. All 155 inherited test files green on PHP 8.4/8.5 × Symfony 7.4/8.0 (unchanged templates).
5. Remove the `MopaBootstrapBundle` switch and the Symfony 6.4 shims; keep everything else.
6. Scratch app: `composer require idct/adminata` with the ORM bundle resolves (app1 scenario);
   `--no-plugins` migration path exercised on a copy of `APP/`'s `composer.json`.

Exit gate: `make lint phpstan rector test` green; scratch-app resolution proven.

## Phase 1 — Foundations: CSS, JS, contracts (weeks 2–3)

1. Tailwind v4 semantics fixture proving T1–T11 (document 04 §4); pin `tailwindcss` exactly.
2. Vite build with the three entries (`app.js`, `app.esm.js`, `vendor/jquery.js`) and CSS
   outputs; `frontend.yaml` with build-freshness and `size-limit`.
3. `theme.css`, `base.css`, `fonts.css` (Outfit), z-index ladder, 12 skin token files at the
   kept `admin-lte-skins/` paths, `fontawesome.css` (FA6 + shims, woff2 only).
4. `.adm-*` component files, safelist generator, `contract.json`, `css-contract` CI job.
5. Compat layer (`compat/*.css`, `.sonata-bc`-scoped) and `@layer sonata-overrides`.
6. JS core: `registry.js`, tolerant `Config.param`, `window.Admin` facade (16 members),
   `adminata` namespace, jQuery bridge, Bootstrap data-API delegate; shell controllers
   `sonata-layout`, `sonata-menu`, `sonata-dropdown`, `sonata-modal`, `sonata-tabs`,
   `sonata-theme`, `sonata-dismiss`, `sonata-search-shortcut`; Vitest harness + fixture dumper;
   JS contract snapshot.
7. PHP additions: `sonata_grid_class`/`sonata_box_class` Twig filters (tested), `adminata:`
   config root, new asset defaults (sonataform dropped, compat + FA + jQuery added), skin path
   kept, `sonata_script_attributes`, `<html lang dir>` helpers, `sonata_theme` cookie reading.
8. Tests: `ParityTest` (upstream templates vendored under `tests/Parity/upstream/4.43.0`),
   `HookContractTest`, `ConfigParityTest` scaffolds.

Exit gate: reproducible build; contract test suites exist and pass for the untouched
templates; shell controllers unit-tested; CSS budgets met by the empty shell.

## Phase 2 — Layout, navigation, dashboard, blocks (weeks 4–5)

Templates of document 03 §A (15 files + flash template + `Core/list_mode_buttons.html.twig`);
demo ORM app skeleton (dashboard, search, empty groups, stats/admin_list/search_result blocks);
`ajax_layout` wrapper; login page port (if in scope). Functional `MenuTest`, `DashboardActionTest`,
`SearchActionTest`, `TabMenuTest`, `BreadcrumbsRuntimeTest` green; Playwright baselines for
dashboard, search, empty layout in both themes; axe clean on the shell.

## Phase 3 — Forms and filters (weeks 6–8)

Templates of document 03 §C (form theme 30 blocks, filter theme, autocomplete template, edit
chrome, 18 association templates, datepicker override + standalone theme); controllers
`sonata-select`, `sonata-autocomplete`, `sonata-association`, `sonata-sortable`,
`sonata-choice-field-mask`, `sonata-inline-row`, `sonata-datepicker`; `tests/Form/*` rewritten;
demo admins covering every form type and filter; Panther flows (modal create, list select,
inline add + sortable, autocomplete create-and-select, choice mask, tab errors, confirm-exit,
datepicker round trips, stateless-CSRF variant). Exit: `tests/Form` green, parity green for
`Form/` and `CRUD/Association/`.

## Phase 4 — CRUD pages (weeks 9–11)

Templates of document 03 §B and §D (list, fields, pager, batch, mosaic, tree parity, show,
compare, history, ACL, delete, preview, buttons, helpers); controllers `sonata-batch`,
`sonata-editable`, `sonata-treeview`; `RenderElementRuntimeTest` re-baselined with the frozen
envelope; parity 131/131; hook contract complete; Playwright baselines for every page × viewport
× theme; axe no serious violations; functional `CRUDControllerTest` green.

## Phase 5 — Ecosystem, migration tooling, acceptance (weeks 12–13)

1. `COMPATIBILITY.md` tiers; `compat.yaml` nightly (MongoDB fork, ORM checkout).
2. `bin/console adminata:audit-overrides` (rules A-01…A-22, document 10 §3) with `APP/` as the
   fixture; `--format=json`.
3. `UPGRADE-1.0.md` (U1–U16) and `BEST_VERSION.md` for the inherited PHP layer.
4. Migrate `APP/` on a branch following document 10 §1; run the 18 + 6 acceptance scenarios;
   fix adminata findings; record the real effort against the 24–27 h estimate.
5. Compat-layer visual smoke for ORM `block_audit`, block-bundle RSS, the login recipe and a
   Bootstrap user override.

## Phase 6 — Release (week 14)

Sphinx docs (document 12), RTD, `CHANGELOG.md`, `v1.0.0-rc1` → `v1.0.0`, Packagist publish,
recipe decision executed, announcement with the drop-in guarantee box.

## Post-1.0 backlog

Monthly upstream sync cadence and `upstream-watch`; Infection nightly and `FIX.md`; AssetMapper
polish and importmap docs; SonataUserBundle-themed login/reset pages; tooltips (Floating UI);
toast mode for flash messages; `defer` + `sonata:ready` (2.x); jQuery removal and `onclick` shim
removal (2.x); optional supported tree mode; density config key; contribute the Flex recipe.

## Work breakdown by template group

| Group | Files | Twig lines | Controllers involved | Phase |
|---|---|---|---|---|
| Layouts, menu, core, breadcrumb, blocks, flash | 18 | ≈ 1,300 | layout, menu, dropdown, theme, dismiss, search-shortcut, sticky | 2 |
| Form theme, filter theme, autocomplete, edit chrome, associations, datepicker | 27 | ≈ 2,900 | select, autocomplete, association, modal, sortable, choice-field-mask, inline-row, datepicker, tabs, edit, confirm-exit, collection | 3 |
| List, fields, pager, batch, mosaic, tree | 55 | ≈ 2,000 | batch, editable, filter, filter-list, per-page, readmore, select, dropdown, treeview | 4 |
| Show, history, ACL, delete, preview, buttons, helpers | 31 | ≈ 1,000 | tabs, revision, readmore, dropdown | 4 |

## Indicative effort

| Phase | Days |
|---|---|
| 0 Bootstrap | 5 |
| 1 Foundations | 10 |
| 2 Layout | 10 |
| 3 Forms | 15 |
| 4 CRUD | 15 |
| 5 Ecosystem/migration | 10 |
| 6 Release | 5 |
| **Total** | **≈ 70 days** |

Assumptions: free TailAdmin edition only; Stimulus-only; no new PHP features beyond the two
Twig filters, the `adminata:` config root and the audit command; owner decisions taken up front.
