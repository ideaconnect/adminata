# 04 — CSS architecture (Tailwind v4)

Condensed from `R/gap-css-architecture.md` (130 KB, complete recipes and the merged compat
inventory) and `R/js-assets.md` §7. Decisions D26–D30 apply.

## 1. The core problem and the layered answer

Tailwind v4 emits only the classes it finds in scanned sources. adminata's prebuilt `app.css` is
compiled from adminata's own templates; utilities that appear only in an app's overridden
templates, `row_attr`, `header_class` or PHP `'class' =>` options are not in it. Four layers
answer this:

| Layer | Mechanism | Purpose |
|---|---|---|
| (a) Semantic `.adm-*` API | `@utility adm-*` for single-selector primitives; `@layer components` for descendant selectors; every name safelisted with `@source inline()` | Stable classes users can write anywhere; `@apply`-able by users' own CSS |
| (b) Bootstrap-3/AdminLTE compat | `@layer components`, plain selectors, all scoped under `.sonata-bc`, written as `@apply adm-*`; separate build entry `compat-bootstrap3.css`, default-on, removable via `remove_stylesheets` | Markup the bundle never scans (user overrides, sibling bundles, login recipe) renders acceptably; never leaks into non-Sonata pages |
| (c) TailAdmin helpers | `@utility menu-item*`, `menu-dropdown-*`, `no-scrollbar`, `custom-scrollbar` verbatim, also safelisted | Copied partials and custom `knp_menu_template` overrides keep working |
| (d) PHP-emitted strings | Raw string rendered **and** translated by Twig filters `sonata_grid_class` / `sonata_box_class`; outputs (`{,sm:,md:,lg:,xl:}col-span-{1..12}`, `col-start-{2..12}`) safelisted | Keeps PHP defaults, tests and user CSS keyed on `.col-md-6` |

Apps wanting arbitrary utilities compile Tailwind themselves (§8).

## 2. Source layout (`assets/css`)

```
assets/css/
├── app.css                  # entry 1 → src/Resources/public/app.css
├── compat-bootstrap3.css    # entry 2 → compat-bootstrap3.css (@reference app.css; no CSS re-emitted)
├── fontawesome.css          # entry 3 → fontawesome.css (FA6 all + v4-shims + v4-font-face, woff2 only)
├── adminata.css             # importable aggregate for apps (no @source, no "tailwindcss" import)
├── theme.css                # @custom-variant dark + @theme static tokens
├── base.css                 # @layer base: border-color shim, body, .dark color-scheme, density vars
├── fonts.css                # @font-face Outfit Variable (self-hosted)
├── components/*.css         # card, button, badge, form, table, alert, dropdown, tabs, pagination,
│                            # sidebar, layout, modal, list, show, misc  → the .adm-* API
├── compat/*.css             # grid, box, buttons, labels, alerts, forms, tables, nav, dropdown,
│                            # modal, adminlte, helpers, glyphicons, overrides (@layer sonata-overrides)
├── vendor/                  # tom-select.css, flatpickr.css, sortable.css (themes, not vendor imports)
├── skins/skin-*.css         # 12 files: :root brand-token overrides → published as admin-lte-skins/skin-*.min.css
├── safelist.css             # @source inline() lists (partly generated)
└── contract.json            # GENERATED selector list asserted by CI
```

`app.css`:

```css
@import "tailwindcss";
@import "./adminata.css";
@source "../../src/Resources/views";
@source "../js";
@source not "../js/**/*.test.js";
@source not inline("collapse");   /* Tailwind's visibility:collapse would hide Bootstrap .collapse */
```

Published under `src/Resources/public/` (committed): `app.css`, `compat-bootstrap3.css`,
`fontawesome.css`, `admin-lte-skins/skin-*.min.css` (12 tiny files), `fonts/*.woff2`, `app.js`,
`app.esm.js`, `vendor/jquery.js`, `images/*`, `entrypoints.json`, `manifest.json`.

## 3. Tokens and dark mode

- `@custom-variant dark (&:where(.dark, .dark *));` — matches the `.dark` element itself (TailAdmin's
  `(&:is(.dark *))` does not).
- `@theme static { … }` copies TailAdmin's palette (brand, blue-light, gray, orange, success, error,
  warning, theme-pink/purple), type scale (`text-title-*`, `text-theme-*`), shadows, extra
  breakpoints (`2xsm`, `xsm`, `3xl`) verbatim from `T/src/css/style.css:8-166`; does **not** copy
  the `--font-*: initial` / `--breakpoint-*: initial` resets (they would delete `font-mono` and
  `sm..2xl` for user templates) nor the Google Fonts import.
- Additions: `--radius-control` (0.5 rem), `--radius-card` (1 rem), `--radius-panel`, `--radius-modal`;
  semantic z-index ladder `--z-index-{base:1, overlay:10, sticky:20, dropdown:30, sidebar:40,
  header:50, modal:60, popover:70, toast:80, loader:90}` (resolves TailAdmin's header/modal
  `z-99999` tie; modal above header, popovers above modal).
- Brand re-theming: utilities compile to `var(--color-brand-500)`, so any later stylesheet that
  redefines `--color-brand-*` re-themes without a rebuild. Skins map: `skin-blue*` → brand
  (empty file), `skin-black*` → gray-800 ladder, `skin-green*` → success, `skin-red*` → error,
  `skin-yellow*` → warning, `skin-purple*` → generated purple ladder; `*-light` → light sidebar
  via `--adm-sidebar-bg`. The `skin-*` class stays on `<body>`.
- `base.css` restores Tailwind v3's default border colour (`gray-200` / dark `gray-800`), sets
  `color-scheme`, defines runtime density variables (`--adm-control-h`, `--adm-control-px/py`,
  `--adm-cell-px/py`, `--adm-card-p`, sidebar widths, header height) and an
  `html[data-density="compact"]` override; body recipe from `T/src/index.html`.
- Dark mode is stamped server-side: `<html class="no-js{% if _theme == 'dark' %} dark{% endif %}"
  data-theme="…">` from the `sonata_theme` cookie; `.dark` never goes on `<body>`.

## 4. Emission rules that must hold (verify first)

| # | Assumption | Consequence if wrong |
|---|---|---|
| T1 | `@utility` classes are emitted only when seen in scanned sources or `@source inline()` | safelist strategy for `.adm-*` |
| T2 | Plain rules in `@layer components` are always emitted | compat layer viability |
| T3 | `@apply` accepts `@utility` names but not `@layer components` classes | `.adm-*` must be `@utility` |
| T4 | `@source inline("…")` supports brace expansion with ranges (v4.1+) and `@source not inline()` | grid safelist, `.collapse` exclusion |
| T5 | `@theme static` emits all variables; utilities reference `var(--…)` | runtime brand override, skins |
| T6 | `@custom-variant dark (&:where(.dark, .dark *))` semantics | dark selectors |
| T7 | A layer declared after `@import "tailwindcss"` sorts after `utilities` | `@layer sonata-overrides` collision fixes |
| T8 | `@reference "./app.css"` gives tokens/variants/utilities without re-emitting CSS | `compat-bootstrap3.css` standalone build |
| T9 | Bare `z-99999`, `h-(--var)`, `max-sm:` syntax | copied partials, density |
| T10 | Multi-property custom utilities sort before single-property core utilities | `class="adm-input px-2"` lets `px-2` win |
| T11 | Preflight keeps `[hidden]{display:none!important}` | Sonata toggles the `hidden` attribute |
| T12 | Unscoped preflight is acceptable (page is adminata's; real apps already mix Flowbite) | decision, not a fact |

First task of phase 1: a 20-line fixture built with the pinned Tailwind version asserting T1–T11.

## 5. Safelist (`safelist.css`)

- Grid outputs of `sonata_grid_class`: `{,sm:,md:,lg:,xl:}col-span-{1..12}`, `{sm:,md:,lg:,xl:}col-start-{2..12}`.
- `{,sm:,md:,lg:}hidden`, `{sm:,md:,lg:}block` for `row_attr`/`attr` users.
- Generated: every `adm-*` and `menu-*` utility name (from `components/*.css` by
  `bin/build-css-safelist.mjs`), `no-scrollbar`, `custom-scrollbar`.
- Cost: about 104 grid rules (≈4 KB) plus utilities the templates emit anyway.

## 6. Compat layer inventory (`compat/*.css`, scoped `.sonata-bc`)

Families and their target (full selector lists in `R/gap-css-architecture.md` §3):

| Family | Covered | Notes |
|---|---|---|
| Grid/helpers | `row`, `col-{xs,sm,md,lg}-{1..12}`, `col-*-offset-*`, `container-fluid`, `pull-*`, `text-{center,right,left,muted,success,danger,warning,info}`, `hidden` (`!important`), `hidden-{xs,sm,md,lg}`, `visible-*`, `clearfix`, `list-unstyled`, `list-inline`, `well`, `lead`, `small`, `img-responsive`, `img-thumbnail`, `close`, `caret`, `nopadding` | `.row` becomes `grid grid-cols-12`; `.container`, `.fixed`, `.collapse` are **not** shimmed (Tailwind collisions) |
| Boxes/panels/AdminLTE widgets | `box`, `box-{primary,info,success,warning,danger,default,solid}`, `box-header/-body/-footer/-title/-tools`, `with-border`, `collapsed-box`, `panel*`, `small-box`, `info-box`, `callout`, `bg-{aqua,green,red,yellow,blue,purple,teal,gray,light-blue}`, `user-header`, `login-page`, `login-box*`, `progress(-bar)` | maps onto `.adm-card*` and metric card |
| Buttons | `btn`, `btn-{primary,success,danger,warning,info,default,link,secondary}`, `btn-{xs,sm,lg}`, `btn-flat`, `btn-block`, `btn-group`, `btn-app` | `.sonata-ba-action.btn:not(:hover)` transparency documented |
| Labels/badges/alerts | `label(-*)` via `.label:not(label)`, `badge`, `alert(-*)`, `alert-dismissible` | |
| Forms | `form-control`, `form-group`, `input-group(-addon)`, `input-sm`, `help-block`, `has-error`, `control-label`, `checkbox`, `radio`, `form-inline`, `form-horizontal`, `form-control-feedback` | |
| Tables | `table`, `table-{bordered,striped,hover,condensed,responsive}` | `.table` display semantics compatible |
| Navs/dropdowns/tabs/modal | `nav`, `nav-tabs`, `nav-pills`, `navbar*`, `tab-content`, `tab-pane`, `active`, `in`, `dropdown*`, `divider`, `modal`, `modal-dialog/content/header/body/footer`, `fade`, `modal-lg` | behaviour from the data-API delegate (document 05) |
| Glyphicons | `glyphicon glyphicon-*` map (owner: keep ≈30 KB or drop) | login recipe only |
| Overrides (`@layer sonata-overrides`) | `body.fixed` neutralised; `.collapse` visibility fix; `.hidden` importance | |

Sonata's own SCSS (`styles.scss`, `layout.scss`, `tree.scss`, `flashmessage.scss`,
`readmore.scss`, `admin-lte-fas.scss`) has a rule-by-rule disposition in
`R/gap-css-architecture.md` §5; kept rules become `components/*.css` entries.

## 7. Third-party themes, icons, fonts, budget

- **Tom Select**: no vendor CSS imported; adminata themes the same hooks (`.ts-wrapper`, `.ts-control`,
  `.ts-dropdown`, `.plugin-remove_button`, …) at specificity ≤ (0,2,0) so app skins (the real app
  has them) win; dropdown at `z-popover`.
- **flatpickr**: vendor `flatpickr.css` imported into `layer(components)` + TailAdmin's theme
  verbatim with `#465fff` → `var(--color-brand-500)` and `z-index: var(--z-index-popover)`.
- **SortableJS**: `sortable-ghost/-chosen/-drag` + `.sonata-ba-sortable-handler`.
- **Font Awesome 6 Free**: `all.css` + `v4-shims.css` + `v4-font-face.css`, woff2 only
  (`fa-solid-900`, `fa-regular-400`, `fa-brands-400`, `fa-v4compatibility`), plus a short
  `fa5-shims` section for the ≈20 FA5→FA6 renames Sonata/docs use. Removable file.
- **Outfit Variable** self-hosted (`@fontsource-variable/outfit`, latin + latin-ext), `font-display: swap`.

| File | Sonata 4.43 | adminata target (min / br) |
|---|---|---|
| `app.css` | 345 KB | ≤ 140 KB / ≤ 22 KB |
| `compat-bootstrap3.css` | — | ≤ 60 KB / ≤ 10 KB |
| `fontawesome.css` | — | ≤ 130 KB / ≤ 20 KB |
| fonts | 2.1 MB | ≤ 400 KB |
| skins | 56 KB | 12 × < 1 KB |

CI job `css-contract`: parse built CSS, assert every `contract.json` selector exists, assert
absence of `.collapse{visibility:collapse}`, `.container{`, Google Fonts URLs, ttf/eot refs;
size budgets; dead-class lint over `class="…"` literals in templates; `contract.json` snapshot
must be committed deliberately.

## 8. Per-app recipe (apps compiling Tailwind themselves)

```css
/* assets/styles/admin.css */
@import "tailwindcss";
@import "../../vendor/idct/adminata/assets/css/adminata.css";
@import "../../vendor/idct/adminata/assets/css/compat/index.css";      /* optional */
@import "../../vendor/idct/adminata/assets/css/skins/skin-green.css";  /* optional */
@source "../../vendor/idct/adminata/src/Resources/views";
@source "../../vendor/idct/adminata/assets/js";
@source "../../templates";
@source "../../src/Admin";
@source "../../vendor/sonata-project/form-extensions/src/Bridge/Symfony/Resources/views";
@source not inline("collapse");
:root { --color-brand-500: #0ea5e9; }
```

```yaml
sonata_admin:
    assets:
        remove_stylesheets: [bundles/sonataadmin/app.css, bundles/sonataadmin/compat-bootstrap3.css]
        extra_stylesheets: [{ path: 'build/admin.css', package_name: null }]
```

Toolchains documented in this order: AssetMapper + `symfonycasts/tailwind-bundle` (standalone
CLI, no Node plugins needed because adminata's CSS uses none), Webpack Encore
(`@tailwindcss/postcss`), Vite (`@tailwindcss/vite`).

## 9. Flags carried into the risk register

F1 missing safelist entry silently drops an `.adm-*` class (CI contract); F2 Tailwind semantics
unverified locally; F3 `body.fixed`; F4 `.collapse` collision when apps scan `vendor/`;
F5 `remove_stylesheets` now must also list the two new files; F6 skins are palettes, not
AdminLTE structure; F8 `.label` vs `<label>`; F9 app classes named like utilities (`mt-10`,
`hidden`) change meaning page-wide; F10 FA5→FA6 renames.
