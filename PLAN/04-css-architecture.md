# 04 — CSS architecture (Tailwind 4.3)

Condensed from `R/gap-css-architecture.md` (recipes and rule-by-rule disposition of Sonata's SCSS)
minus the compatibility layer. Decisions C1–C8 apply. Versions: `tailwindcss` 4.3.3,
`@tailwindcss/vite` 4.3.3 (verified 2026-09-04; pin exactly, bump with Dependabot).

## 1. Layers

| Layer | Mechanism | Purpose |
|---|---|---|
| (a) Prebuilt `app.css` | compiled from adminata's own templates and JS (`@source`) | zero-config install (demo app, MongoDB fork tests, small apps) |
| (b) `.adm-*` components | `@utility adm-*` for single-selector primitives (`adm-btn`, `adm-btn-icon`, `adm-input`, `adm-select`, `adm-badge`, `adm-card`, `adm-callout`, `adm-alert`, …); `@layer components` for descendant rules (tables, sidebar, pagination); every name safelisted | readable templates; the design-system vocabulary the app's own cell and page templates reuse |
| (c) Grid safelist | `@source inline()` for `{,sm:,md:,lg:,xl:}col-span-{1..12}` and `{sm:,md:,lg:,xl:}col-start-{2..12}` | group `class` values from admin classes and the PHP defaults reach the prebuilt CSS |
| (d) Per-app compile | the app imports `adminata.css` and adds `@source` globs for adminata's views and its own templates (§7) | arbitrary utilities in app templates; **recommended for recomaty-panel** |

No Bootstrap/AdminLTE compatibility layer, no `.sonata-bc` scoping, no skins (C4).

## 2. Source layout (`assets/css`)

```
assets/css/
├── app.css                  # entry 1 → packages/admin-bundle/src/Resources/public/app.css
├── fontawesome.css          # entry 2 → fontawesome.css (FA7 Free all.css, woff2 only)
├── adminata.css             # importable aggregate for apps (no @source, no "tailwindcss" import)
├── theme.css                # @custom-variant dark + @theme static tokens
├── base.css                 # @layer base: border-color shim, body, .dark color-scheme, density vars
├── fonts.css                # @font-face Outfit Variable (self-hosted)
├── components/*.css         # card, button, badge, callout, form, table, alert, dropdown, pagination,
│                            # sidebar, layout, modal, list, show, misc  → the .adm-* layer
├── safelist.css             # @source inline() lists (grid; generated adm-* names)
└── contract.json            # GENERATED selector list asserted by CI
```

`app.css`:

```css
@import "tailwindcss";
@import "./adminata.css";
@source "../../packages";               /* every package directory: views and the PHP defaults */
@source not "../../packages/*/tests";
@source not "../../packages/*/docs";
@source "../js";
@source not "../js/**/*.test.js";
```

Published under `packages/admin-bundle/src/Resources/public/` (committed): `app.css`,
`fontawesome.css`, `fonts/*.woff2`, `app.js`, `images/*`, `entrypoints.json`, `manifest.json`.
form-extensions' and twig-extensions' public directories are deleted (their styles live here).

## 3. Tokens and dark mode

- `@custom-variant dark (&:where(.dark, .dark *));` — matches the `.dark` element itself (TailAdmin's
  `(&:is(.dark *))` does not).
- `@theme static { … }` copies TailAdmin's palette (brand, blue-light, gray, orange, success, error,
  warning, theme-pink/purple), type scale (`text-title-*`, `text-theme-*`), shadows, extra
  breakpoints (`2xsm`, `xsm`, `3xl`) verbatim from `T/src/css/style.css:8-166`; does **not** copy
  the `--font-*: initial` / `--breakpoint-*: initial` resets nor the Google Fonts import.
- Additions: `--radius-control` (0.5 rem), `--radius-card` (1 rem), `--radius-panel`, `--radius-modal`;
  semantic z-index ladder `--z-index-{base:1, overlay:10, sticky:20, dropdown:30, sidebar:40,
  header:50, modal:60, popover:70, toast:80, loader:90}`.
- Brand re-theming: utilities compile to `var(--color-brand-500)`, so any later stylesheet that
  redefines `--color-brand-*` re-themes without a rebuild (documented for the app).
- `base.css` restores Tailwind v3's default border colour (`gray-200` / dark `gray-800`), sets
  `color-scheme` (also makes native date/time inputs dark), defines runtime density variables
  (`--adm-control-h`, `--adm-control-px/py`, `--adm-cell-px/py`, `--adm-card-p`, sidebar widths,
  header height) and an `html[data-density="compact"]` override; body recipe from `T/src/index.html`.
- Dark mode is stamped server-side: `<html class="no-js{% if theme == 'dark' %} dark{% endif %}"
  data-theme="…">` from the `sonata_theme` cookie (default from `sonata_admin.theme.mode`); `.dark`
  never goes on `<body>`.

## 4. Emission rules that must hold (verify first)

| # | Assumption | Consequence if wrong | Status |
|---|---|---|---|
| T1 | `@utility` classes are emitted only when seen in scanned sources or `@source inline()` | safelist strategy for `.adm-*` | **holds** |
| T3 | `@apply` accepts `@utility` names but not `@layer components` classes | `.adm-*` primitives must be `@utility` | **holds** |
| T4 | `@source inline("…")` supports brace expansion with ranges | grid safelist | **holds** |
| T5 | `@theme static` emits all variables; utilities reference `var(--…)` | runtime brand override | **holds** |
| T6 | `@custom-variant dark (&:where(.dark, .dark *))` semantics | dark selectors | **holds** |
| T7 | A layer declared after `@import "tailwindcss"` sorts after `utilities` | `@layer sonata-overrides` for the few collision fixes | **holds** |
| T9 | Bare `z-99999`, `h-(--var)`, `max-sm:` syntax | copied partials, density | **holds** (three separate assertions) |
| T10 | Multi-property custom utilities sort before single-property core utilities | `class="adm-input px-2"` lets `px-2` win | **holds** |
| T11 | Preflight keeps `[hidden]{display:none!important}` | controllers toggle the `hidden` attribute | **holds** |
| T12 | Unscoped preflight is acceptable (the page is adminata's) | decision, not a fact | decision |

All eleven assertions (T9 counts three) are executable: `assets/css/__fixture__/fixture.css` and
`bin/tailwind-fixture.mjs`, run by `npm run fixture` and by the `frontend` workflow. Verified
against tailwindcss 4.3.3 on 2026-09-05. (v1's T2 and T8 concerned the removed compatibility layer.)

**`container` and `collapse` are Tailwind utilities in v4**, emitting `.container{width:100%…}` and
`.collapse{visibility:collapse}` whenever those class names appear in a scanned source. Their
presence in the built CSS therefore says nothing about Bootstrap, and §8's forbidden list drops
them; what it does assert is that no rule whose whole selector is `.btn`, `.box`, `.label` or
`.col-md-*` exists.

## 5. Safelist (`safelist.css`)

- Grid outputs: `{,sm:,md:,lg:,xl:}col-span-{1..12}`, `{sm:,md:,lg:,xl:}col-start-{2..12}`.
- `{,sm:,md:,lg:}hidden`, `{sm:,md:,lg:}block` for `row_attr`/`attr` users.
- Generated: every `adm-*` utility name (from `components/*.css` by `bin/build-css-safelist.mjs`),
  TailAdmin's `menu-item*`, `menu-dropdown-*`, `no-scrollbar`, `custom-scrollbar`.
- Cost: about 104 grid rules (≈ 4 KB) plus utilities the templates emit anyway.

## 6. Sonata SCSS disposition

`styles.scss`, `layout.scss`, `tree.scss`, `flashmessage.scss`, `readmore.scss`,
`admin-lte-fas.scss` (2,877 lines with the JS) have a rule-by-rule disposition in
`R/gap-css-architecture.md` §5; kept rules become `components/*.css` entries (readmore, flash
read-more toggle, list cell hooks, sticky `.stuck`); AdminLTE overrides and `tree.scss` are dropped.
form-extensions' `assets/scss/app.scss` (Tempus Dominus theme) and twig-extensions'
`flashmessage.css` are dropped as well; the flash read-more toggle is re-implemented in
`components/alert.css`.

## 7. Per-app recipe (recomaty-panel: Webpack Encore + `@tailwindcss/postcss`)

adminata ships an npm-facing `assets/package.json` (P19). Its `style` field points at
`assets/css/tailwind.css`, and its `composer.json` carries the `symfony-ux` keyword, which is what
makes Symfony Flex's `PackageJsonSynchronizer` add `"@idct/adminata": "file:vendor/idct/adminata/assets"`
to the application's `package.json` on every `composer update` — and `tailwindcss` beside it, as
the peer dependency that file declares. The application's entry is therefore one import and its
own sources:

```css
/* assets/styles/admin.css */
@import "@idct/adminata";
@source "../../templates";
@source "../../src/Admin";
@source "../../src/Form";
@source "../js";
:root { --color-brand-500: #0ea5e9; } /* optional re-theme */
```

`tailwind.css` is `app.css` — the engine with `source(none)`, `adminata.css`, and the `@source`
list for `src/` and `assets/js` — plus `@source` lines for the two storage layers' views, which are
installed beside adminata under `vendor/` and are skipped silently when they are not. Three facts
this rests on, each verified against tailwindcss 4.3.3 on 2026-09-07 (the probe is recorded in
P19): a `@source` inside an imported file is honoured and resolves relative to that file — through
symlinks, to the real path, so a `path` repository behaves like a `vendor/` install; `source(none)`
on the engine import inside an imported file still lets the entry's own `@source` lines through;
and a bare `@import "@scope/name"` resolves through `node_modules` via the package's `style` field.
The application must not import `"tailwindcss"` itself: a second engine import does not fail, it
emits the preflight twice.

```yaml
sonata_admin:
    assets:
        remove_stylesheets: [bundles/sonataadmin/app.css]
        extra_stylesheets: [build/admin.css]
```

Encore: `.enablePostCssLoader()` with `@tailwindcss/postcss` 4.3.3 in `postcss.config.mjs`, both
the application's to install — they are the toolchain's half. The app's Sass files that fight
AdminLTE are deleted (document 10). Other toolchains (AssetMapper + `symfonycasts/tailwind-bundle`
1.0.0, Vite) are documented post-1.0. The entry file, the PostCSS config, the Encore line and the
YAML are the four edits Flex would make from a recipe; adminata has no recipe repository yet, and
without one Flex's auto-generated recipe registers the bundle and nothing else.

## 8. Icons, fonts, budget

- **Font Awesome 7.3.1 Free**: `all.css` (90 KB min) + `fa-solid-900.woff2` (119 KB) +
  `fa-regular-400.woff2` (20 KB); brands (115 KB) shipped only if an app asks. No `v4-shims`,
  no `v4-font-face`, no `v5-font-face`: every icon name the app uses resolves through FA7's built-in
  aliases except `clock-o` (appendix C §6), which the app renames.
- **Outfit Variable** self-hosted (`@fontsource-variable/outfit` 5.3.0, latin + latin-ext),
  `font-display: swap`.

| File | Sonata 4.43 | adminata target (min / br) |
|---|---|---|
| `app.css` | 345 KB | ≤ 120 KB / ≤ 20 KB |
| `fontawesome.css` | (inside app.css) | ≤ 80 KB / ≤ 15 KB (or `all.css` verbatim ≤ 95 KB) |
| fonts | 2.1 MB | ≤ 320 KB |

CI job `css-contract`: parse built CSS, assert every `contract.json` selector exists, assert that no
rule's whole selector is `.btn`, `.box`, `.label` or `.col-md-*` (matched at a selector boundary, so
an arbitrary variant such as `[&>.btn]:rounded-l-none` in a not-yet-ported template does not trip
it), and that no Google Fonts URL or ttf/eot reference appears; size budgets; dead-class lint over
`class="…"` literals in templates. `.container` and `.collapse` are not on the list: see §4.

## 9. Flags carried into the risk register

F1 missing safelist entry silently drops an `.adm-*` class (CI contract); F2 Tailwind semantics
unverified locally; F3 app classes named like utilities (`mt-10`, `hidden`) change meaning
page-wide once the app compiles Tailwind; F4 FA7 renames in the app.
