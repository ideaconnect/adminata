# adminata — Definitive Tailwind v4 CSS architecture (gap G2)

Path aliases: `S/` = `scratchpad/sonata-admin-4.43.0/`, `T/` = `scratchpad/tailadmin-html/`, `TR/` = `scratchpad/tailadmin-react/`, `TN/` = `scratchpad/tailadmin-next/`, `VE/` = `scratchpad/vendor-extract/`, `MDB/` = `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle/`, `APP/` = `/home/bartosz/dev/r3/recomaty-panel/`, `R/` = `scratchpad/research/`.

Decisions inherited from `R/critique-round-1.md` §3 and built on here: keep every PHP default string (C7), `<html class="dark">` stamped from the `sonata_theme` cookie (C9), no `@tailwindcss/forms` (C12), compat layer in `@layer components` not `@utility` (C13), `.adm-*` CSS prefix (C14), compat on by default as a separate removable file `bundles/sonataadmin/compat-bootstrap3.css` (C21), public dir stays `bundles/sonataadmin/`, prebuilt `app.css` committed under `src/Resources/public` (js-assets §7.5, §8.2).

---

## 0. Tailwind v4 semantics this spec relies on (no local Tailwind v4 — verify each against tailwindcss.com before implementation)

| # | Semantic relied on | Where it matters | Status |
|---|---|---|---|
| T1 | `@utility name { … }` classes are emitted **only when the name is found in scanned sources (or in `@source inline()`)**; they land in `@layer utilities`, support variants (`hover:adm-btn`), and can be `@apply`-ed. | §2 (a), (c) | verify |
| T2 | Plain author CSS inside `@layer components { .x { @apply … } }` is **always emitted** (Tailwind never tree-shakes author rules). | §2 (b), compat layer | verify |
| T3 | `@apply` accepts core utilities and `@utility`-defined names; it does **not** accept arbitrary classes defined in `@layer components`. Consequence: every recipe that must be re-used by `@apply` (compat layer → `.adm-*`) has to be an `@utility`. | §2 (a) vs (b) | verify |
| T4 | `@source inline("…")` adds literal candidates; brace expansion incl. ranges (`{1..12}`, `{100..900..100}`) exists since v4.1; `@source not inline("…")` excludes a candidate. | §2 (d), safelist, `.collapse` collision | verify (v4.1+) |
| T5 | `@theme { … }` emits `:root` variables **only for used tokens**; `@theme static { … }` emits all of them. Utilities reference tokens as `var(--color-brand-500)`, so redefining the variable later at runtime re-themes without rebuild; opacity modifiers compile to `color-mix()` and also follow the variable. | §1.4 brand decision, skins | verify |
| T6 | `@custom-variant dark (&:where(.dark, .dark *));` is the documented class-strategy dark variant (matches the `.dark` element itself and descendants at zero specificity). | §1.2 | verify |
| T7 | Layer order is `theme, base, components, utilities` (declared by `@import "tailwindcss"`); a layer first declared **after** that import (`@layer sonata-overrides`) sorts after `utilities` and therefore beats any utility regardless of specificity. | compat collision fixes | verify (plain CSS cascade-layer rule) |
| T8 | A second entry file can use `@reference "./app.css";` to get theme tokens, custom variants and `@utility` definitions for `@apply` **without** re-emitting their CSS. | `compat-bootstrap3.css` standalone build | verify |
| T9 | `z-<integer>` (e.g. `z-99999`) works as a bare value in v4 without a `--z-index-*` token; `h-(--var)`, `px-(--var)` are the v4 syntax for CSS-variable arbitrary values; `max-sm:` variants exist. | §1.3 z-ladder, density | verify |
| T10 | Custom multi-property utilities sort **before** single-property core utilities inside `@layer utilities`, so `class="adm-input px-2"` lets `px-2` win. | `.adm-*` usable with utility overrides | verify |
| T11 | Preflight contains `[hidden]:where(:not([hidden="until-found"])) { display: none !important }`. Sonata's `hidden` attributes (`S/src/Resources/views/CRUD/base_list.html.twig:300,321,331`) keep working without Bootstrap's `[hidden]{display:none!important}`. | §5 `[hidden]` row | verify |
| T12 | Using `@import "tailwindcss"` pulls preflight; adminata keeps it unscoped (page is entirely adminata's; the real app already mixes Tailwind/Flowbite components into `sonata_page_content_header`, `APP/templates/layout/standard_layout_override.html.twig:72`). | §1.1 | decision |

---

## 1. `assets/css` file layout, theme, dark variant, z-index ladder, density/radius, brand decision

### 1.1 File layout (bundle side)

```
assets/css/
├── app.css                  # BUILD ENTRY 1 → src/Resources/public/app.css
├── compat-bootstrap3.css    # BUILD ENTRY 2 → src/Resources/public/compat-bootstrap3.css (@reference app.css)
├── fontawesome.css          # BUILD ENTRY 3 → src/Resources/public/fontawesome.css (FA6 all + v4-shims, no Tailwind)
├── adminata.css             # importable aggregate for apps compiling their own CSS (no @source paths, no "tailwindcss" import)
├── theme.css                # @custom-variant dark + @theme static tokens (§1.2)
├── base.css                 # @layer base additions: border-color shim, body, .dark color-scheme, fonts, density vars (§1.5)
├── fonts.css                # @font-face Outfit Variable (self-hosted woff2, OFL notice)
├── components/
│   ├── index.css            # @import of every file below
│   ├── card.css button.css badge.css form.css table.css alert.css dropdown.css tabs.css
│   ├── pagination.css sidebar.css layout.css modal.css list.css show.css misc.css
│   └── (every `.adm-*` primitive is an @utility; multi-part selectors are @layer components) (§4)
├── compat/
│   ├── index.css
│   ├── grid.css box.css buttons.css labels.css alerts.css forms.css tables.css nav.css
│   ├── dropdown.css modal.css adminlte.css helpers.css glyphicons.css
│   └── overrides.css        # @layer sonata-overrides: the un-shimmable collisions (§3.9)
├── vendor/
│   ├── tom-select.css flatpickr.css sortable.css   (§6)
├── skins/                   # skin-black.css … skin-yellow-light.css (12): :root token overrides → published as admin-lte-skins/skin-*.min.css
├── safelist.css             # @source inline() for PHP/Twig-filter-emitted utilities (§2.5)
└── contract.json            # GENERATED list of selectors that must exist in the built files (CI, §6.6)
```

`app.css` (bundle build entry; the only file that names bundle paths):

```css
@import "tailwindcss";
@import "./adminata.css";

/* what the bundle scans — never user code */
@source "../../src/Resources/views";
@source "../js";
@source not "../js/**/*.test.js";
/* never let Tailwind emit the Bootstrap-colliding utilities from our own sources (§3.9) */
@source not inline("collapse");
```

`adminata.css` (importable by apps, §7):

```css
@import "./theme.css";
@import "./fonts.css";
@import "./base.css";
@import "./components/index.css";
@import "./vendor/tom-select.css";
@import "./vendor/flatpickr.css";
@import "./vendor/sortable.css";
@import "./safelist.css";
```

`compat-bootstrap3.css` (bundle build entry 2):

```css
@reference "./app.css";          /* tokens, dark variant and every @utility adm-* — no CSS re-emitted (T8) */
@import "./compat/index.css";
```

Published files (`src/Resources/public/`, committed, `bundles/sonataadmin/` after `assets:install`): `app.css`, `compat-bootstrap3.css`, `fontawesome.css`, `admin-lte-skins/skin-*.min.css` (12 tiny token files), `fonts/outfit-variable-latin*.woff2`, `fonts/fa-{solid-900,regular-400,brands-400,v4compatibility}.woff2`, `fonts/glyphicons-halflings-regular.woff2`, `app.js`, `images/*`. Default stylesheet list in adminata's `Configuration.php` (replacing `S/src/DependencyInjection/Configuration.php:632-633`):

```php
'bundles/sonataadmin/app.css',
'bundles/sonataadmin/compat-bootstrap3.css',   // removable via assets.remove_stylesheets
'bundles/sonataadmin/fontawesome.css',         // removable via assets.remove_stylesheets
'bundles/sonataform/app.css',                  // keep until the G5 (form-extensions/flatpickr) decision drops Tempus Dominus
```

`SonataAdminExtension.php:94-100` keeps appending `bundles/sonataadmin/admin-lte-skins/%s.min.css` — adminata ships those 12 files as `:root { --color-brand-*: … }` overrides (§1.4), so `remove_stylesheets` semantics and `options.skin` validation are untouched.

### 1.2 `theme.css` — exact contents

All values copied verbatim from `T/src/css/style.css` (line refs in comments). The `--font-*: initial` (`style.css:9`) and `--breakpoint-*: initial` (`:12`) resets are **not** copied (they delete `font-mono` and would make `sm..2xl` disappear for user templates — js-assets §7.6). `--shadow-slider-navigation`, `--drop-shadow-4xl` (`:151-157`) are dropped (swiper-only). TailAdmin's `--z-index-*` ladder (`:159-165`) is replaced by the semantic ladder in §1.3.

```css
/* assets/css/theme.css */
@custom-variant dark (&:where(.dark, .dark *));   /* T6; TailAdmin uses (&:is(.dark *)) style.css:6 — does not match .dark itself */

@theme static {                                    /* static: every token emitted so user stylesheets / skins can override or read them (T5) */
  /* fonts — do NOT reset --font-* (style.css:9-10) */
  --font-sans: "Outfit Variable", ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
  --font-outfit: var(--font-sans);                 /* keeps TailAdmin's `font-outfit` class working in copied partials */

  /* extra breakpoints on top of Tailwind's defaults (style.css:13-15) */
  --breakpoint-2xsm: 375px;
  --breakpoint-xsm: 425px;
  --breakpoint-3xl: 2000px;

  /* type scale (style.css:22-37) */
  --text-title-2xl: 72px;  --text-title-2xl--line-height: 90px;
  --text-title-xl: 60px;   --text-title-xl--line-height: 72px;
  --text-title-lg: 48px;   --text-title-lg--line-height: 60px;
  --text-title-md: 36px;   --text-title-md--line-height: 44px;
  --text-title-sm: 30px;   --text-title-sm--line-height: 38px;
  --text-theme-xl: 20px;   --text-theme-xl--line-height: 30px;
  --text-theme-sm: 14px;   --text-theme-sm--line-height: 20px;
  --text-theme-xs: 12px;   --text-theme-xs--line-height: 18px;

  /* colours (style.css:39-138) */
  --color-current: currentColor;
  --color-transparent: transparent;
  --color-white: #ffffff;
  --color-black: #101828;                          /* NB: not pure black (style.css:42) */

  --color-brand-25: #f2f7ff;  --color-brand-50: #ecf3ff;  --color-brand-100: #dde9ff; --color-brand-200: #c2d6ff;
  --color-brand-300: #9cb9ff; --color-brand-400: #7592ff; --color-brand-500: #465fff; --color-brand-600: #3641f5;
  --color-brand-700: #2a31d8; --color-brand-800: #252dae; --color-brand-900: #262e89; --color-brand-950: #161950;

  --color-blue-light-25: #f5fbff; --color-blue-light-50: #f0f9ff;  --color-blue-light-100: #e0f2fe; --color-blue-light-200: #b9e6fe;
  --color-blue-light-300: #7cd4fd; --color-blue-light-400: #36bffa; --color-blue-light-500: #0ba5ec; --color-blue-light-600: #0086c9;
  --color-blue-light-700: #026aa2; --color-blue-light-800: #065986; --color-blue-light-900: #0b4a6f; --color-blue-light-950: #062c41;

  --color-gray-25: #fcfcfd;  --color-gray-50: #f9fafb;  --color-gray-100: #f2f4f7; --color-gray-200: #e4e7ec;
  --color-gray-300: #d0d5dd; --color-gray-400: #98a2b3; --color-gray-500: #667085; --color-gray-600: #475467;
  --color-gray-700: #344054; --color-gray-800: #1d2939; --color-gray-900: #101828; --color-gray-950: #0c111d;
  --color-gray-dark: #1a2231;

  --color-orange-25: #fffaf5;  --color-orange-50: #fff6ed;  --color-orange-100: #ffead5; --color-orange-200: #fddcab;
  --color-orange-300: #feb273; --color-orange-400: #fd853a; --color-orange-500: #fb6514; --color-orange-600: #ec4a0a;
  --color-orange-700: #c4320a; --color-orange-800: #9c2a10; --color-orange-900: #7e2410; --color-orange-950: #511c10;

  --color-success-25: #f6fef9;  --color-success-50: #ecfdf3;  --color-success-100: #d1fadf; --color-success-200: #a6f4c5;
  --color-success-300: #6ce9a6; --color-success-400: #32d583; --color-success-500: #12b76a; --color-success-600: #039855;
  --color-success-700: #027a48; --color-success-800: #05603a; --color-success-900: #054f31; --color-success-950: #053321;

  --color-error-25: #fffbfa;  --color-error-50: #fef3f2;  --color-error-100: #fee4e2; --color-error-200: #fecdca;
  --color-error-300: #fda29b; --color-error-400: #f97066; --color-error-500: #f04438; --color-error-600: #d92d20;
  --color-error-700: #b42318; --color-error-800: #912018; --color-error-900: #7a271a; --color-error-950: #55160c;

  --color-warning-25: #fffcf5;  --color-warning-50: #fffaeb;  --color-warning-100: #fef0c7; --color-warning-200: #fedf89;
  --color-warning-300: #fec84b; --color-warning-400: #fdb022; --color-warning-500: #f79009; --color-warning-600: #dc6803;
  --color-warning-700: #b54708; --color-warning-800: #93370d; --color-warning-900: #7a2e0e; --color-warning-950: #4e1d09;

  --color-theme-pink-500: #ee46bc;
  --color-theme-purple-500: #7a5af8;

  /* shadows (style.css:140-150) */
  --shadow-theme-md: 0px 4px 8px -2px rgba(16, 24, 40, 0.1), 0px 2px 4px -2px rgba(16, 24, 40, 0.06);
  --shadow-theme-lg: 0px 12px 16px -4px rgba(16, 24, 40, 0.08), 0px 4px 6px -2px rgba(16, 24, 40, 0.03);
  --shadow-theme-sm: 0px 1px 3px 0px rgba(16, 24, 40, 0.1), 0px 1px 2px 0px rgba(16, 24, 40, 0.06);
  --shadow-theme-xs: 0px 1px 2px 0px rgba(16, 24, 40, 0.05);
  --shadow-theme-xl: 0px 20px 24px -4px rgba(16, 24, 40, 0.08), 0px 8px 8px -4px rgba(16, 24, 40, 0.03);
  --shadow-datepicker: -5px 0 0 #262d3c, 5px 0 0 #262d3c;
  --shadow-focus-ring: 0px 0px 0px 4px rgba(70, 95, 255, 0.12);
  --shadow-tooltip: 0px 4px 6px -2px rgba(16, 24, 40, 0.05), -8px 0px 20px 8px rgba(16, 24, 40, 0.05);

  /* adminata additions: radii used by every .adm-* recipe (overridable at runtime, §1.5) */
  --radius-control: 0.5rem;   /* TailAdmin rounded-lg on inputs/buttons (form-elements.html:77, button-01.html:3) */
  --radius-card: 1rem;        /* rounded-2xl on cards (table-01.html:2) */
  --radius-panel: 0.75rem;    /* rounded-xl on alerts/dropdowns (alert-success.html:2, Dropdown.tsx:41) */
  --radius-modal: 1.5rem;     /* rounded-3xl on modal panels (profile-info-modal.html:10) */

  /* semantic z-index ladder (§1.3) */
  --z-index-base: 1;
  --z-index-overlay: 10;
  --z-index-sticky: 20;
  --z-index-dropdown: 30;
  --z-index-sidebar: 40;
  --z-index-header: 50;
  --z-index-modal: 60;
  --z-index-popover: 70;
  --z-index-toast: 80;
  --z-index-loader: 90;
}
```

The dead TailAdmin classes `dark:bg-dark-900`, `hover:text-dark-900`, `border-primary`, `max-h-select` (tailadmin-catalog §1.6) are **not** given tokens; strip them when porting partials.

### 1.3 z-index ladder (resolves the TailAdmin header/modal `z-99999` tie)

TailAdmin: overlay `z-9` (`T/src/partials/overlay.html:4`), sidebar `z-9999` (`sidebar.html:3`), header **and** modal `z-99999` (`header.html:3`, `profile/profile-info-modal.html:3`, `TR/src/components/ui/modal/index.tsx:57`), dropdown panels `z-40` (`TR/.../Dropdown.tsx:41`), preloader `z-999999`. Sonata today: `.stuck` `z-index:5` (`S/assets/scss/layout.scss:392,403`), Bootstrap modal 1050 / backdrop 1040 (`S/src/Resources/public/app.css`), Tom Select dropdown `z-index:10` (`APP/node_modules/tom-select/dist/css/tom-select.default.css:116`).

| Token | Value | Owner (adminata class) | Notes |
|---|---|---|---|
| `z-base` | 1 | `body` (`relative z-base`, from `style.css:275`) | |
| `z-overlay` | 10 | `.adm-sidebar-overlay` (mobile drawer backdrop) | TailAdmin `z-9` |
| `z-sticky` | 20 | `.stuck` (`sonata-sticky` navbar + form actions), sticky `thead` | Sonata used 5; must be above table content, below dropdowns |
| `z-dropdown` | 30 | `.adm-dropdown-panel`, `.sonata-bc .dropdown-menu` | TailAdmin React `z-40` |
| `z-sidebar` | 40 | `.adm-sidebar` (`<aside>`) | TailAdmin `z-9999` |
| `z-header` | 50 | `.adm-header` (sticky) | TailAdmin `z-99999`; stays above the mobile drawer so the hamburger/X toggle remains reachable (TailAdmin behaviour) |
| `z-modal` | 60 | `.adm-modal` wrapper (backdrop is a child, panel `relative`) | **tie resolved**: modal > header |
| `z-popover` | 70 | Tom Select `.ts-dropdown`, flatpickr `.flatpickr-calendar`, `.adm-editable-popover`, dropdowns rendered inside modals | flatpickr's own default 99999 and Tom Select's 10 are both overridden in `vendor/*.css` |
| `z-toast` | 80 | flash toasts (if a toast mode is added) | |
| `z-loader` | 90 | full-page spinner (not shipped by default) | TailAdmin `z-999999` |

Rule: adminata templates only use `z-<token>`. Verbatim-copied TailAdmin partials with `z-99999` still work as bare integers (T9) and will sit above everything — acceptable and documented; the `--z-index-1…999999` tokens are not defined.

### 1.4 Brand colour: build-time `@theme static` with **runtime override by redefining the same variable** — no `--adminata-*` indirection

Options considered:

1. tailadmin-catalog §5.1: `:root { --adminata-brand-500 }` + `@theme { --color-brand-500: var(--adminata-brand-500) }`.
2. js-assets §7.6: literal values in `@theme`.

Decision: **(2) with `@theme static`**. Utilities compile to `var(--color-brand-500)` (T5), so a later unlayered stylesheet (`assets.extra_stylesheets`, or a skin file) that sets `:root { --color-brand-500: #039855 }` already re-themes at runtime; the second variable layer of option 1 doubles 100+ tokens, hides literal values from Tailwind IntelliSense/`@theme inline` and gains nothing. `static` is required so that every `--color-*` variable exists even when adminata's own templates never use a shade (a user's `bg-brand-950` in their own CSS or a skin file referencing `var(--color-success-600)` must resolve).

Skins (`options.skin`, 12 enum values, `S/src/DependencyInjection/Configuration.php:295-311`) become 12 published files, e.g. `assets/css/skins/skin-green.css`:

```css
/* published as bundles/sonataadmin/admin-lte-skins/skin-green.min.css (kept path, SonataAdminExtension.php:96) */
:root, .skin-green {
  --color-brand-25: var(--color-success-25);  --color-brand-50: var(--color-success-50);   --color-brand-100: var(--color-success-100);
  --color-brand-200: var(--color-success-200); --color-brand-300: var(--color-success-300); --color-brand-400: var(--color-success-400);
  --color-brand-500: var(--color-success-500); --color-brand-600: var(--color-success-600); --color-brand-700: var(--color-success-700);
  --color-brand-800: var(--color-success-800); --color-brand-900: var(--color-success-900); --color-brand-950: var(--color-success-950);
  --shadow-focus-ring: 0px 0px 0px 4px color-mix(in oklab, var(--color-brand-500) 12%, transparent);
}
```

Mapping (layout-nav §7): `skin-blue*` → brand (default, file is empty), `skin-black*` → gray-800 ladder, `skin-green*` → success, `skin-red*` → error, `skin-yellow*` → warning, `skin-purple*` → `theme-purple-500` (+ a generated 25…950 ladder), `*-light` → `--adm-sidebar-bg: var(--color-white)` (light sidebar), non-light → `var(--color-gray-900)`. The `skin-*` class stays on `<body>` (`admin_lte_skin_class` block, `S/src/Resources/views/standard_layout.html.twig:93`; `APP/templates/layout/standard_layout_override.html.twig:11` overrides that block to inject `w-full`, so the block must stay).

### 1.5 `base.css` — density, radius, dark colour-scheme

```css
/* assets/css/base.css */
@layer base {
  /* Tailwind v4 default border colour is currentColor; restore v3/TailAdmin behaviour (style.css:176-183) */
  *, ::after, ::before, ::backdrop, ::file-selector-button { border-color: var(--color-gray-200, currentColor); }
  .dark *, .dark ::after, .dark ::before { border-color: var(--color-gray-800, currentColor); }
  button:not(:disabled), [role="button"]:not(:disabled) { cursor: pointer; }             /* style.css:184-187 */

  :root {
    color-scheme: light;
    /* density (runtime; not @theme so they do not become utilities) */
    --adm-control-h: 2.75rem;      /* h-11   form-elements.html:77 */
    --adm-control-px: 1rem;        /* px-4 */
    --adm-control-py: 0.625rem;    /* py-2.5 */
    --adm-cell-px: 1.25rem;        /* px-5   table-06.html:60 */
    --adm-cell-py: 1rem;           /* py-4 */
    --adm-card-p: 1.25rem;         /* p-5    metric-group-01.html:4 */
    --adm-sidebar-w: 290px;        /* sidebar.html:3 */
    --adm-sidebar-w-collapsed: 90px;/* sidebar.html:2 */
    --adm-header-h: 4.5rem;
    --adm-sidebar-bg: var(--color-white);       /* skins *-light */
  }
  .dark { color-scheme: dark; --adm-sidebar-bg: var(--color-black); }   /* sidebar.html:3 dark:bg-black */
  html[data-density="compact"] {
    --adm-control-h: 2.25rem; --adm-control-py: 0.375rem; --adm-control-px: 0.75rem;
    --adm-cell-px: 0.75rem;   --adm-cell-py: 0.5rem;     --adm-card-p: 1rem;
  }
  body { @apply relative z-base bg-gray-50 font-sans text-base font-normal text-gray-800 dark:bg-gray-900 dark:text-gray-400; }  /* style.css:274-276 + index.html:17 */
  /* native date/time indicator hidden (style.css:279-288) */
  input[type="date"]::-webkit-inner-spin-button, input[type="time"]::-webkit-inner-spin-button,
  input[type="date"]::-webkit-calendar-picker-indicator, input[type="time"]::-webkit-calendar-picker-indicator { display: none; -webkit-appearance: none; }
  .no-js .sonata-collection-add, .no-js .sonata-collection-delete { display: none; }   /* layout.scss:314-317 */
}
```

`data-density` and `data-theme` are stamped on `<html>` by `standard_layout.html.twig`'s `html_attributes` block next to `class="dark"` (js-assets §7.6). Radius overrides for a "sharp" theme: `:root { --radius-control: 0.25rem; --radius-card: 0.5rem }` in a user stylesheet — no rebuild.

Dark mode stamping, restated for the CSS side: `<html class="no-js{% if _theme == 'dark' %} dark{% endif %}" data-theme="{{ _theme }}">`; the `.dark` class lives on `<html>` only, never on `<body>` (TailAdmin HTML puts it on `<body>`, `T/src/index.html:17`; React on `<html>`, `TR/src/context/ThemeContext.tsx`). All `dark:` utilities and every `.dark …` selector in this spec assume that.

---

## 2. Emission mechanism per class family

| Family | Mechanism | Why | Safelist needed? |
|---|---|---|---|
| (a) semantic `.adm-*` API (§4) | **`@utility adm-*`** for every single-selector primitive; `@layer components` only for descendant selectors (`.adm-table th`, `.adm-sidebar-menu .treeview-menu`) | Must be `@apply`-able by the compat layer and by users' own CSS (T3); gets variants (`lg:adm-btn-block`) and is overridable by later single-property utilities (T10). | **Yes** — because `@utility` is emitted only when seen (T1) and user templates are never scanned, `safelist.css` lists every `adm-*` name (generated from `components/*.css` by `bin/build-css-safelist.mjs`; CI asserts the built file contains each selector, §6.6). |
| (b) Bootstrap-3 / AdminLTE compat (§3) | **`@layer components`** plain selectors, all namespaced `.sonata-bc …`, recipes written as `@apply adm-*` (+ raw utilities where no primitive exists); collision fixes in `@layer sonata-overrides` | Always emitted (T2) regardless of scanning — the whole point is markup the bundle never sees (user overrides, siblings, `APP/templates/security/login_form.html.twig`). `.sonata-bc` (body class, `standard_layout.html.twig:93`; login recipe `docs/cookbook/recipe_sonata_admin_without_user_bundle.rst:343`) keeps `.btn`/`.alert` from leaking into non-Sonata pages that load the same CSS (critique §5 #6). | No |
| (c) TailAdmin `menu-item*`, `menu-dropdown-*`, `no-scrollbar`, `custom-scrollbar` (`T/src/css/style.css:190-267`) | **`@utility`** verbatim (React variants of icon helpers, `TR/src/index.css`: `menu-item-icon`, `menu-item-icon-size`, currentColor-based) | Used inside adminata's own templates → emitted by scanning; kept as utilities so `@apply menu-item` works in the sidebar compat rules. | Add to safelist anyway (cheap) so custom `knp_menu_template` overrides (`docs/cookbook/recipe_knp_menu.rst`) that copy them keep working. |
| (d) PHP-emitted strings (`col-md-4` `Configuration.php:540`; `col-md-12` `base_edit_form_macro.html.twig:7`, `base_show.html.twig:97`; `box box-primary` `BaseGroupedMapper.php:83`; `bg-aqua` `AdminStatsBlockService.php:72`; `nav navbar-nav` `AbstractAdmin.php:2595`; user `'class' => 'btn btn-success'`, `label label-*`) | Raw string is always rendered **and** passed through a Twig filter (`sonata_grid_class`, `sonata_box_class`, §4.6) that appends Tailwind utilities / `.adm-*` names; the raw Bootstrap names are additionally covered by (b) | Keeps user CSS keyed on `.box-danger`/`.col-md-6` working, keeps tests that pin the strings green (`S/tests/Form/FormMapperTest.php:115-266`, `ShowMapperTest.php:405-422`), and still renders correctly when compat is removed. | **Yes** for the filter outputs, which are core utilities not present in any template: `{,sm:,md:,lg:,xl:}col-span-{1..12}`, `{sm:,md:,lg:,xl:}col-start-{2..12}`. Colour classes go through `.adm-*` names (already safelisted). |

### 2.5 `safelist.css` (complete)

```css
/* assets/css/safelist.css — candidates Tailwind must emit although no bundle template contains them.
   Keep a comment per entry naming the PHP/Twig producer. Regenerated section is machine-written. */

/* sonata_grid_class(): FormMapper/ShowMapper group 'class' (BaseGroupedMapper.php:78, default col-md-12 in
   base_edit_form_macro.html.twig:7 / base_show.html.twig:97), dashboard.blocks[].class (Configuration.php:540,
   default col-md-4), Core/dashboard.html.twig:85-106 col-md-{{ width_* }}, add_block.html.twig:27 col-md-N */
@source inline("{,sm:,md:,lg:,xl:}col-span-{1..12}");
@source inline("{sm:,md:,lg:,xl:}col-start-{2..12}");     /* col-*-offset-N → col-start-(N+1) */

/* hidden attribute helpers users write in row_attr / attr */
@source inline("{,sm:,md:,lg:}hidden");
@source inline("{sm:,md:,lg:}block");

/* --- GENERATED from components/*.css: every @utility adm-* and menu-* name (bin/build-css-safelist.mjs) --- */
@source inline("adm-{row,card,card-header,card-title,card-body,card-footer,card-tools,card-primary,card-info,card-success,card-warning,card-danger,card-default,card-solid,card-collapsed}");
@source inline("adm-{btn,btn-primary,btn-secondary,btn-success,btn-info,btn-warning,btn-danger,btn-outline,btn-ghost,btn-link,btn-xs,btn-sm,btn-md,btn-lg,btn-block,btn-icon,btn-group,btn-app}");
@source inline("adm-{badge,badge-primary,badge-success,badge-error,badge-warning,badge-info,badge-light,badge-dark,badge-solid,badge-sm}");
@source inline("adm-{input,select,textarea,checkbox,radio,switch,file,input-error,input-success,input-disabled,input-group,input-addon,input-addon-start,input-addon-end,form-group,form-row,label,help,error,error-list,required,form-horizontal,form-actions,fieldset,legend}");
@source inline("adm-{table,table-wrap,th,td,tr,table-striped,table-hover,table-bordered,table-compact,th-sortable,th-sorted,row-selected}");
@source inline("adm-{alert,alert-success,alert-error,alert-warning,alert-info,alert-dismissible,alert-close,alert-title,alert-body}");
@source inline("adm-{dropdown,dropdown-toggle,dropdown-panel,dropdown-item,dropdown-item-active,dropdown-header,dropdown-divider,dropdown-scrollable,dropdown-multicol}");
@source inline("adm-{tabs,tab,tab-active,tab-error,tab-panel,tab-panel-active,segmented,segmented-item,segmented-item-active}");
@source inline("adm-{pagination,page-item,page-item-active,page-item-disabled,page-prev,page-next,per-page,results}");
@source inline("adm-{sidebar,sidebar-header,sidebar-body,sidebar-footer,sidebar-overlay,sidebar-search,sidebar-menu,sidebar-group-title,header,header-inner,header-toggle,header-search,header-actions,breadcrumb,breadcrumb-item,breadcrumb-active,page-title,page-header,page-nav,content,wrapper,footer-note,noscript-warning,stuck}");
@source inline("adm-{modal,modal-backdrop,modal-dialog,modal-lg,modal-content,modal-header,modal-title,modal-close,modal-body,modal-footer}");
@source inline("adm-{readmore,readmore-btn,tree,tree-item,tree-item-active,mosaic,mosaic-item,mosaic-hover,mosaic-caption,metric,metric-icon,metric-label,metric-value,stat-box,search-box,search-list,list-view-switch,filter-box,filter-row,filter-toggle,editable,editable-popover,editable-empty,sortable-handle,sortable-ghost,spinner,avatar,divider,kbd,empty}");
@source inline("menu-{item,item-active,item-inactive,item-icon,item-icon-active,item-icon-inactive,item-icon-size,item-arrow,item-arrow-active,item-arrow-inactive,dropdown-item,dropdown-item-active,dropdown-item-inactive,dropdown-badge,dropdown-badge-active,dropdown-badge-inactive}");
@source inline("{no-scrollbar,custom-scrollbar}");
```

Size note: the grid safelist is 5×12 + 4×11 = 104 tiny rules (≈4 KB minified); the `adm-*` list forces ~140 utilities that adminata's templates emit anyway (net cost ≈ 0).

---

## 3. Merged Bootstrap-3 / AdminLTE compat inventory (`compat/*.css`, `@layer components`, all selectors prefixed `.sonata-bc`)

Sources merged: `R/php-compat.md` §3, `R/js-assets.md` §7.4, `R/tailadmin-catalog.md` R10/§2.5, `R/layout-nav.md` §5.4/§11, the Sonata template token census (`grep class=` over `S/src/Resources/views`: 131 files), `APP/templates/**` (17 bundle overrides, layout, login, user block), `APP/src` PHP options (`'class' => 'col-md-6'` ×7, `'col-md-12'` ×5, `'col-md-9'` ×3, `'col-md-3'` ×2, `'col-md-8'`, `'col-md-4'`, `'btn btn-success'` ×2, `'form-control'`, `'password-field form-control'`, `'form-group'`, `'text-right mt-10'`, `'header_class' => 'content-width'`), sibling templates (`MDB/vendor/sonata-project/block-bundle/src/Resources/views/Block/block_core_rss.html.twig`, `…/twig-extensions/src/Bridge/Symfony/Resources/views/FlashMessage/render.html.twig`, `…/form-extensions/src/Bridge/Symfony/Resources/views/Form/datepicker.html.twig`, `VE/doctrine-orm-admin-bundle/*/src/Resources/views/Block/block_audit.html.twig`) and the docs strings (`docs/reference/action_show.rst:56-57`, `action_create_edit.rst:134-135`, `dashboard.rst:266,273,361`, `advanced_configuration.rst:312-333`, `preview_mode.rst:88-93`, `cookbook/recipe_custom_action.rst:141,295`, `recipe_customizing_a_mosaic_list.rst:59,63`, `recipe_sonata_admin_without_user_bundle.rst:343-384`).

Conventions in the tables: **Recipe** = body of the rule (all inside `@layer components`, selector `.sonata-bc .<class>` unless noted); "dark" is included in the `adm-*` primitive it applies, otherwise given explicitly. **TW collision** = a Tailwind v4 core utility with the same name exists.

### 3.1 Grid & layout helpers (`compat/grid.css`, `compat/helpers.css`)

| Class | Recipe | Source of recipe | TW collision |
|---|---|---|---|
| `.row` | `@apply adm-row;` (= `grid grid-cols-12 gap-x-6 gap-y-6`) — plus `.row > .clearfix { @apply hidden }` (float clearers become 1-col grid items otherwise; `list_outer_rows_mosaic.html.twig:94,97`) | `T/src/index.html` dashboard grids (`grid grid-cols-12 gap-4 md:gap-6`) | no |
| `.row > [class*="col-"]` | `@apply col-span-12;` (Bootstrap columns are full width below their breakpoint) | — | no |
| `.col-xs-{1..12}` | `@apply col-span-N;` | — | no (`col-span-*`, `col-start-*`, `col-auto` are the TW names) |
| `.col-sm-{1..12}` / `.col-md-*` / `.col-lg-*` | `@apply sm:col-span-N` / `md:col-span-N` / `lg:col-span-N` | — | no |
| `.col-{xs,sm,md,lg}-offset-{0..11}` | `@apply {bp}:col-start-(N+1)` (offset-0 → `col-start-1`) | — | no |
| `.col-*-push-*`, `.col-*-pull-*` | not shimmed (document) | — | — |
| `.container-fluid` | `@apply w-full;` (no padding: TailAdmin content already has `p-4 md:p-6`) | — | no; **`.container` (bare) is a TW utility (max-width per breakpoint) — never redefine; Sonata does not use it** |
| `.pull-right` / `.pull-left` | `@apply float-right ml-2;` / `@apply float-left mr-2;` — plus `.btn-group.pull-right, .box-tools.pull-right { @apply float-none ml-auto }` (flex parents) | — | no |
| `.clearfix` | `@apply after:table after:clear-both after:content-[''];` | — | no (TW has `clear-both`) |
| `.hidden-xs` / `-sm` / `-md` / `-lg` | `@apply max-sm:hidden` / `sm:max-md:hidden` / `md:max-lg:hidden` / `lg:max-xl:hidden` (Bootstrap ranges: xs <768, sm 768-991, md 992-1199, lg ≥1200 ≈ TW sm/md/lg/xl) | — | no |
| `.visible-xs` etc. | `@apply hidden max-sm:block` / … | — | no |
| `.hidden` | not redefined — TW `hidden` = `display:none`, identical meaning | — | **same meaning**, keep TW |
| `.hide` | `@apply hidden!;` (Bootstrap `.hide{display:none!important}`; used by `base.js:20-24` flash toggling, `base_list.html.twig` filter form, `edit_one_to_many_inline_tabs.html.twig`) | — | no |
| `.show` | `@apply block!;` | — | no |
| `.invisible` | not redefined (same meaning) | — | same |
| `.sr-only` | not redefined (same meaning; `list__action_*.html.twig`, `standard_layout.html.twig:128`) | — | same |
| `.text-center` / `.text-left` / `.text-right` / `.text-nowrap` | not redefined (same meaning) | — | same |
| `.text-muted` | `@apply text-gray-500 dark:text-gray-400;` | badge light text (`badge-01.html:46`) | no |
| `.text-primary` / `.text-success` / `.text-info` / `.text-warning` / `.text-danger` | `@apply text-brand-500` / `text-success-600 dark:text-success-500` / `text-blue-light-500` / `text-warning-600 dark:text-orange-400` / `text-error-600 dark:text-error-500` | `badge-01.html:4-39` colour pairs | no |
| `.bg-primary`… `.bg-danger` (BS3 contextual) | `@apply bg-brand-500 text-white` etc. | — | no (TW needs a shade) |
| `.list-unstyled` | `@apply list-none p-0 m-0;` (`form_admin_fields.html.twig:17`, pinned by `S/tests/Form/AdminLayoutTest.php:178-190`) | — | no (`list-none` is TW) |
| `.list-inline` | `@apply flex flex-wrap gap-x-3 list-none p-0;` | — | no |
| `.nopadding`, `.no-padding` | `@apply p-0!;` (`base_show.html.twig:97`, `block_search_result.html.twig:58`, `base_list.html.twig`) | — | no |
| `.small`, `small` | `@apply text-theme-xs;` (`Pager/base_results.html.twig:24` `per-page small form-control`) | — | no |
| `.lead` | `@apply text-theme-xl font-light;` | — | no |
| `.well` | `@apply rounded-panel border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-white/[0.03];` | card body tone (`table-01.html:2`) | no |
| `.well-small` | `@apply p-3;` (`base_edit_form.html.twig:100`, `base_acl_macro.html.twig`) | — | no |
| `.page-header` | `@apply mb-6 border-b border-gray-200 pb-4 text-xl font-semibold text-gray-800 dark:border-gray-800 dark:text-white/90;` (`Core/search.html.twig:17`) | `breadcrumb.html:3` | no |
| `.img-responsive` | `@apply block h-auto max-w-full;` | — | no |
| `.img-thumbnail` / `.img-rounded` / `.img-circle` | `@apply rounded-control border border-gray-200 bg-white p-1 dark:border-gray-800` / `rounded-md` / `rounded-full` | — | no |
| `.close` | `@apply float-right -mt-0.5 ml-3 text-xl font-semibold leading-none text-gray-400 hover:text-gray-700 dark:hover:text-gray-200;` (`edit_modal.html.twig:16`, `render_form_dismissable_errors.html.twig:3`, flash `render.html.twig:20,48`, `APP/…/standard_layout_override.html.twig:61`) | modal close (`profile-info-modal.html:15`) toned down | no |
| `.caret` | `@apply ml-1 inline-block size-0 align-middle border-x-4 border-x-transparent border-t-4 border-t-current;` (`standard_layout.html.twig:270`, `tab_menu_template.html.twig`, `tree.html.twig:54`, `APP` filter toggle) | — | no (TW has `caret-<color>`, not bare `caret`) |
| `.divider` (in `.dropdown-menu`) | `@apply my-2 h-px bg-gray-100 dark:bg-gray-800;` | header dropdown separator | no |
| `.divider-vertical` | `@apply mx-2 h-6 w-px bg-gray-200 dark:bg-gray-800;` | — | no |
| `.progress` / `.progress-bar` / `.progress-bar-success` / `.progress-striped` | `@apply relative block h-2 w-full overflow-hidden rounded-sm bg-gray-200 dark:bg-gray-800` / `absolute left-0 top-0 h-full rounded-sm bg-brand-500` / `bg-success-500` / `bg-[linear-gradient(45deg,rgba(255,255,255,.15)_25%,transparent_25%,transparent_50%,rgba(255,255,255,.15)_50%,rgba(255,255,255,.15)_75%,transparent_75%)] bg-[length:1rem_1rem]` (`base_list.html.twig:121-123`, `APP` upload progress) | `T/src/partials/map-01.html:82` | no |
| `.no-stretch` (on `<html>`, `advanced_configuration.rst:326-333`) | no-op (documented) | — | — |
| `.fa-fw`, `.fa-lg`, `.fa-spin`… | provided by `fontawesome.css` (FA6), nothing to shim | — | no |

### 3.2 Boxes / panels / AdminLTE widgets (`compat/box.css`, `compat/adminlte.css`)

| Class | Recipe | Source | Collision |
|---|---|---|---|
| `.box` | `@apply adm-card mb-6;` (`box_class` default `box box-primary` `BaseGroupedMapper.php:83`; `block_admin_list.html.twig:19`; ORM `block_audit.html.twig:14`; `APP` list override `crud/list_with_summaries.html.twig:8,38`) | card `rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]` (`table-01.html:2`) | no |
| `.box-header` | `@apply adm-card-header;` (`flex items-center justify-between gap-3 px-5 py-4 sm:px-6 sm:py-5`) + `.box-header > .fas, > .far, > .fab { @apply mr-1.5 text-lg }` (`admin-lte-fas.scss:35-45`) | `ComponentCard.tsx:15-33` | no |
| `.box-header.with-border` | `@apply border-b border-gray-100 dark:border-gray-800;` | `form-elements.html:1048` | no |
| `.box-title` | `@apply adm-card-title;` (`text-base font-medium text-gray-800 dark:text-white/90`; `h3`, `h4`, `h1` all pinned by this) | `ComponentCard.tsx:20` | no |
| `.box-body` | `@apply adm-card-body;` (`p-5 sm:p-6`) ; `.box-body.no-padding, .box-body.table-responsive { @apply p-0 }` | `ComponentCard.tsx:27` | no |
| `.box-footer` | `@apply adm-card-footer;` (`border-t border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6`) | — | no |
| `.box-tools` | `@apply adm-card-tools;` (`ml-auto flex items-center gap-2`) | `chart-01.html:9` | no |
| `.btn.btn-box-tool` | `@apply adm-btn adm-btn-ghost adm-btn-xs text-gray-400 hover:text-gray-700;` (`block_search_result.html.twig:34,39`) | kebab trigger colours (`chart-01.html:10`) | no |
| `.box-primary` / `.box-info` / `.box-success` / `.box-warning` / `.box-danger` / `.box-default` | `@apply adm-card-primary` … (top accent: `border-t-[3px] border-t-brand-500` / `border-t-blue-light-500` / `border-t-success-500` / `border-t-warning-500` / `border-t-error-500` / `border-t-gray-300`) — AdminLTE draws a 3px coloured top border | AdminLTE `.box-primary{border-top-color:#3c8dbc}` | no |
| `.box.box-solid.box-<color>` | `@apply adm-card-solid` + `.box-solid.box-danger > .box-header { @apply rounded-t-card bg-error-500 text-white [&_.box-title]:text-white }` etc. (`action_show.rst:57` `box box-solid box-danger`; `block_search_result.html.twig:21` `box box-solid`) | — | no |
| `.collapsed-box > .box-body, .collapsed-box > .box-footer` | `@apply hidden;` (flash `render.html.twig:17` uses `collapsed-box` on an alert → also `.alert.collapsed-box { display:block }` so the alert itself stays visible) | — | no |
| `.panel` / `.panel-default` / `.panel-heading` / `.panel-title` / `.panel-body` / `.panel-footer` / `.panel-group` | `@apply adm-card mb-4` / (no-op) / `adm-card-header` / `adm-card-title` / `adm-card-body` / `adm-card-footer` / `space-y-3` (block-bundle `block_core_rss.html.twig`, ORM `block_audit.html.twig:22-34`) | — | no |
| `.panel-collapse.collapse:not(.in)` | `@apply hidden;` and `.panel-collapse.collapse.in { @apply block }` — **requires §3.9 override** because TW `collapse` = `visibility:collapse` | — | **`collapse` collides** |
| `.info-box` | `@apply adm-card mb-6 flex items-center gap-4 p-5;` (`base_list.html.twig:117`, `block_admin_preview.html.twig:82-83`) | metric card `metric-group-01.html:3-24` | no |
| `.info-box-icon` | `@apply flex size-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-2xl text-gray-800 dark:bg-gray-800 dark:text-white/90;` + `.info-box-icon[class*="bg-"] { @apply text-white }` | `metric-group-01.html:7` | no |
| `.info-box-content` / `.info-box-text` / `.info-box-number` / `.progress-description` | `@apply min-w-0 flex-1` / `block text-sm uppercase text-gray-500 dark:text-gray-400` / `block text-title-sm font-bold text-gray-800 dark:text-white/90` / `mt-2 text-sm text-gray-500` | `metric-group-01.html:28-33` | no |
| `.small-box` | `@apply adm-card relative mb-6 overflow-hidden p-5 text-white bg-brand-500 border-0;` (`block_stats.html.twig:18`, colour from `bg-aqua` etc.) | metric card + solid colour | no |
| `.small-box .inner h3` / `.inner p` / `.icon` / `.small-box-footer` | `@apply m-0 text-title-sm font-bold` / `m-0 text-sm/6 opacity-90` / `absolute right-4 top-3 text-6xl opacity-20` / `-mx-5 -mb-5 mt-4 block bg-black/10 px-5 py-2 text-center text-sm text-white/80 hover:text-white` | — | no |
| `.bg-aqua`, `.bg-light-blue`, `.bg-blue` | `@apply bg-brand-500 text-white;` (`AdminStatsBlockService.php:72`, `dashboard.rst:273`, `base_list.html.twig:118`, `APP/templates/security/user_block.html.twig:10` `bg-light-blue`) | brand | no |
| `.bg-green`, `.bg-olive`, `.bg-teal` | `@apply bg-success-500 text-white;` | | no |
| `.bg-red`, `.bg-maroon` | `@apply bg-error-500 text-white;` | | no |
| `.bg-yellow`, `.bg-orange` | `@apply bg-warning-500 text-white;` / `bg-orange-500 text-white` | | no |
| `.bg-purple`, `.bg-fuchsia` | `@apply bg-theme-purple-500 text-white;` / `bg-theme-pink-500 text-white` | | no |
| `.bg-navy`, `.bg-black` | `@apply bg-gray-900 text-white;` / not redefined (TW `bg-black` = `#101828` here, `style.css:42`) | | `bg-black` same meaning |
| `.bg-gray`, `.bg-gray-light` | `@apply bg-gray-200 text-gray-800;` / `bg-gray-100` | | no (TW has shades only) |
| `.callout` / `.callout-danger|warning|info|success` | `@apply adm-alert border-l-4 rounded-r-panel rounded-l-none` + variant `adm-alert-error` … with `border-l-error-500` | alert recipe | no |
| `.login-page` (on `body`) | `@apply flex min-h-screen items-center justify-center bg-gray-50 p-6 dark:bg-gray-900;` (`recipe_sonata_admin_without_user_bundle.rst:343`, `APP/templates/security/login_form.html.twig:12`) | `signin.html:24-30` | no |
| `.login-box` / `.register-box` | `@apply w-full max-w-md;` | `signin.html:55` `max-w-md` | no |
| `.login-logo` / `.register-logo` | `@apply mb-6 text-center text-title-sm font-semibold text-gray-800 dark:text-white/90 [&_a]:text-inherit [&_img]:mx-auto [&_img]:max-h-16;` | `signin.html:59-61` | no |
| `.login-box-body` / `.register-box-body` | `@apply adm-card p-6 sm:p-8 shadow-theme-sm;` | card | no |
| `.login-box-msg` | `@apply mb-5 text-center text-sm text-gray-500 dark:text-gray-400;` | `signin.html:64` | no |
| `.has-feedback` / `.form-control-feedback` | `@apply relative [&_.form-control]:pr-11` / `pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-gray-400` (+ `.form-control-feedback.fas { line-height:1 }` replacing `admin-lte-fas.scss:165-171`) | input-group addon (`form-elements.html:761`) | no |
| `.user-header` (in `.dropdown-user`) | `@apply -mx-3 -mt-3 mb-3 rounded-t-panel bg-brand-500 px-4 py-5 text-center text-white [&_p]:text-base [&_p]:font-medium [&_hr]:my-3 [&_hr]:border-white/20 [&_.btn]:mx-1;` (`APP/templates/security/user_block.html.twig:14`) | header user dropdown (`header.html:643`) | no |
| `.user-body` / `.user-footer` | `@apply px-2 py-2` / `flex items-center justify-between gap-2 border-t border-gray-100 pt-3 dark:border-gray-800 [&_.pull-left]:float-none [&_.pull-right]:float-none` | — | no |
| `.treeview`, `.treeview-menu`, `.sidebar-menu`, `.keep-open`, `.header` (AdminLTE menu DOM produced by custom `knp_menu_template`) | **components.css**, not compat — adminata's own `Menu/sonata_menu.html.twig` keeps emitting `sidebar-menu`/`treeview`/`treeview-menu`/`active` (`S/tests/Functional/Controller/MenuTest.php:41` selects `.sidebar-menu .dynamic-menu a`). Recipes in §4.9. | `sidebar.html:63-140` | no |
| `.main-header`, `.main-sidebar`, `.content-wrapper`, `.content-header`, `.content`, `.wrapper`, `.sidebar-toggle`, `.navbar-static-top`, `.navbar-custom-menu`, `.sidebar-collapse`, `.sidebar-mini`, `.fixed` (body), `.skin-*` | **not shimmed as layout** — adminata's `standard_layout.html.twig` keeps these names as *marker* classes on its TailAdmin structure (block/marker contract, layout-nav §1.3) but the compat layer gives them no geometry. `body.fixed` handled in §3.9. | — | `.fixed` collides |

### 3.3 Buttons (`compat/buttons.css`)

| Class | Recipe | Source | Collision |
|---|---|---|---|
| `.btn` | `@apply adm-btn adm-btn-md;` (54 uses in Sonata views; `docs` ×6; `APP` ×55) | `button-01.html:3` | no |
| `.btn-default`, `.btn-secondary` (BS4 name, `APP/…/standard_layout_override.html.twig:61`) | `@apply adm-btn-secondary;` (outline: `bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]`) | `button-04.html:3`, `Button.tsx:34-35` | no |
| `.btn-primary` | `@apply adm-btn-primary;` (`bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 disabled:bg-brand-300`) | `button-01.html:3`, `Button.tsx:32-33` | no |
| `.btn-success` | `@apply adm-btn-success;` (`bg-success-500 text-white hover:bg-success-600`) | `TN/.../ModalBasedAlerts.tsx:18-38` | no |
| `.btn-info` | `@apply adm-btn-info;` (`bg-blue-light-500 … hover:bg-blue-light-600`) | same | no |
| `.btn-warning` | `@apply adm-btn-warning;` (`bg-warning-500 … hover:bg-warning-600`) | same | no |
| `.btn-danger` | `@apply adm-btn-danger;` (`bg-error-500 … hover:bg-error-600`) | same | no |
| `.btn-link` | `@apply adm-btn-link;` (`bg-transparent px-1 text-brand-500 shadow-none hover:text-brand-600 hover:underline dark:text-brand-400`) | `signin.html:243` | no |
| `.btn-flat` | no-op (`@apply rounded-control;` — TailAdmin has no square look; `btn btn-link btn-flat` in `dashboard__action*.html.twig`, `recipe_custom_action.rst:295`, `APP` user block) | — | no |
| `.btn-outline` (Sonata `styles.scss:75-87`) | `@apply bg-transparent text-current ring-1 ring-inset ring-current hover:text-white hover:bg-current/90;` | — | no |
| `.btn-xs` | `@apply adm-btn-xs;` (`h-8 px-2.5 text-theme-xs gap-1`) | new size (tailadmin-catalog §2.8 gap) | no |
| `.btn-sm`, `.btn-small` | `@apply adm-btn-sm;` (`h-9 px-3 text-theme-sm`) | `button-01.html:3` px-4 py-3 shrunk | no |
| `.btn-lg` | `@apply adm-btn-lg;` (`h-12 px-5 text-base`) | `button-01.html:9` | no |
| `.btn-block` | `@apply adm-btn-block;` (`flex w-full`) — `block_admin_preview.html.twig:75`, `select_subclass.html.twig:26`, login recipe | `signin.html` submit `w-full` | no |
| `.btn-app` | `@apply adm-btn-app;` (`flex h-24 min-w-24 flex-col items-center justify-center gap-2 rounded-card border border-gray-200 bg-white text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03] [&>.fas]:text-2xl`) — `select_subclass.html.twig:26`, replaces `admin-lte-fas.scss:26-33` | metric icon well | no |
| `.btn-group` | `@apply adm-btn-group;` (`inline-flex items-center gap-1 align-middle`; `list__action.html.twig:15`, `block_admin_list.html.twig:43`, `sonata_type_model_list` actions) — plus `.btn-group > .btn + .dropdown-menu { top-full }` | — | no |
| `.btn.active` | `@apply bg-gray-100 dark:bg-white/[0.06];` (list-mode switch `standard_layout.html.twig:253`) | segmented active (`ChartTab.tsx:10`) | no |
| `.btn.disabled, .btn[disabled]` | `@apply cursor-not-allowed opacity-50;` | `Button.tsx:43` | no |
| `.sonata-ba-action.btn:not(:hover)` (`layout.scss:196-199`) | dropped — the rule made action buttons transparent; new look is `adm-btn-secondary adm-btn-xs` on the `sonata-ba-action` anchors (see §5) | — | — |
| `.btn > .fa, .btn > .fas, .btn > .far` | `@apply text-[1.05em];` | — | no |

### 3.4 Labels, badges, alerts (`compat/labels.css`, `compat/alerts.css`)

| Class | Recipe | Source | Collision |
|---|---|---|---|
| `.label` | `@apply adm-badge;` (`inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-theme-xs font-medium`; Sonata: `display_boolean.html.twig`, `block_search_result.html.twig:47`, `list_outer_rows_mosaic.html.twig:47,57`, `tree.html.twig:26-27`; `APP` ×25; `recipe_customizing_a_mosaic_list.rst:59`) — selector is `.sonata-bc .label` (an HTML `<label>` element without that class is unaffected) | `badge-01.html:4`, `Badge.tsx:29-36` | no |
| `.label-default` | `@apply adm-badge-light;` (`bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-white/80`) | `badge-01.html:39` | no |
| `.label-primary` | `@apply adm-badge-primary;` (`bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400`) | `badge-01.html:4` | no |
| `.label-success` | `@apply adm-badge-success;` (`bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500`) | `badge-01.html:11` | no |
| `.label-info` | `@apply adm-badge-info;` (`bg-blue-light-50 text-blue-light-500 dark:bg-blue-light-500/15`) | `badge-01.html:32` | no |
| `.label-warning` | `@apply adm-badge-warning;` (`bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400`) | `badge-01.html:25` | no |
| `.label-danger` | `@apply adm-badge-error;` (`bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500`) | `badge-01.html:18` | no |
| `.badge` | `@apply adm-badge adm-badge-light;` (filter counter `base_list.html.twig`, `block_search_result.html.twig:32`, flash `render.html.twig:40` `badge badge-default`) ; `.badge-default` no-op | `badge-01.html:39` | no |
| `.alert` | `@apply adm-alert;` (`relative mb-4 rounded-panel border p-4 text-sm text-gray-700 dark:text-gray-300`) | `alert-success.html:2` | no |
| `.alert-success` | `@apply adm-alert-success;` (`border-success-500 bg-success-50 dark:border-success-500/30 dark:bg-success-500/15`) | `alert-success.html:2` | no |
| `.alert-info` | `@apply adm-alert-info;` (`border-blue-light-500 bg-blue-light-50 dark:border-blue-light-500/30 dark:bg-blue-light-500/15`) | `alert-info.html:2` | no |
| `.alert-warning` | `@apply adm-alert-warning;` | `alert-warning.html:2` | no |
| `.alert-danger`, `.alert-error` | `@apply adm-alert-error;` (`form_admin_fields.html.twig:16` pinned by `AdminLayoutTest.php:178`; `sonata_flashmessages_class()` returns `danger`) | `alert-error.html:2` | no |
| `.alert-default` (flash type without mapping) | `@apply border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03];` | — | no |
| `.alert-dismissable, .alert-dismissible` | `@apply pr-10;` + `.alert .close { @apply absolute right-3 top-3 float-none }` | — | no |
| `.alert > ul.list-unstyled > li` | `@apply flex items-start gap-2;` | — | no |
| `.alert .read-more-trigger` etc. | see §5 flashmessage rows (kept) | — | — |

### 3.5 Forms (`compat/forms.css`)

| Class | Recipe | Source | Collision |
|---|---|---|---|
| `.form-control` | `@apply adm-input;` (Sonata theme `form_admin_fields.html.twig:36-47`, filters, `Pager/base_results.html.twig:24`, `standard_layout.html.twig:195`, login recipe, `APP` `'class' => 'form-control'`) ; `select.form-control { @apply adm-select }` ; `textarea.form-control { @apply adm-textarea }` ; `.form-control.input-sm, .input-sm { @apply h-9 px-3 text-theme-xs }` ; `.input-lg { h-12 }` | `form-elements.html:77` | no |
| `.form-group` | `@apply adm-form-group;` (`mb-6 last:mb-0`; horizontal variant below) — anchor of the AJAX violation injector (`edit_many_script.html.twig:399`) | `form-elements.html` `space-y-6` | no |
| `.form-horizontal .form-group` | `@apply sm:grid sm:grid-cols-12 sm:gap-x-4;` ; `.form-horizontal .control-label { @apply sm:col-span-3 sm:pt-2.5 sm:text-right }` ; `.form-horizontal .sonata-ba-field, .form-horizontal .col-sm-9 { sm:col-span-9 }` ; `.col-sm-offset-3 { sm:col-start-4 }` (forms-edit §3.2) | — | no |
| `.form-inline` | `@apply flex flex-wrap items-end gap-3 [&_.form-group]:mb-0;` | — | no |
| `.control-label` | `@apply adm-label;` (`mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400`; pinned `col-sm-3 control-label required` `AdminLayoutTest.php:31`) | `Label.tsx:17` | no |
| `.control-label__text` | `@apply text-sm font-medium text-gray-800 dark:text-gray-200;` | `Checkbox.tsx:74` | no |
| `.required::after` | kept globally (§5) | — | no |
| `.has-error .form-control, .has-error .adm-input` | `@apply adm-input-error;` (`border-error-500 focus:border-error-300 focus:ring-error-500/20 dark:border-error-500`) ; `.has-error .control-label { text-error-600 }` (`AdminLayoutTest.php:163` pins `form-group has-error`) | `InputField.tsx:42` | no |
| `.has-success`, `.has-warning` | `adm-input-success` / `border-warning-500` | `InputField.tsx:44` | no |
| `.help-block` | `@apply adm-help;` (`mt-1.5 block text-xs text-gray-500 dark:text-gray-400`; pinned `help-block sonata-ba-field-help help-text` `AdminLayoutTest.php:113`) ; `.help-block.sonata-ba-field-error-messages { @apply adm-error }` (`text-error-500`) | `InputField.tsx:67-72` | no |
| `.help-inline` | `@apply ml-2 inline text-xs text-gray-500;` | — | no |
| `.input-group` | `@apply adm-input-group;` (`relative flex w-full items-stretch [&>.form-control]:flex-1 [&>.form-control]:min-w-0`) — `money_widget`/`percent_widget`, `datepicker.html.twig:15,42`, `standard_layout.html.twig:194`, `APP` | `InputGroup.tsx:22-31` | no |
| `.input-group-addon` | `@apply adm-input-addon;` (`inline-flex items-center border border-gray-300 bg-gray-50 px-3.5 text-sm text-gray-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400 first:rounded-l-control first:border-r-0 last:rounded-r-control last:border-l-0`) ; `.input-group > .form-control:not(:first-child) { rounded-l-none }` ; `:not(:last-child) { rounded-r-none }` | `form-elements.html:761` | no |
| `.input-group-btn` | `@apply flex [&>.btn]:rounded-l-none [&>.btn]:h-(--adm-control-h);` | — | no |
| `.checkbox`, `.radio` | `@apply adm-form-check;` (`flex items-center gap-3 py-1 [&>label]:flex [&>label]:items-center [&>label]:gap-3 [&>label]:cursor-pointer`) — pinned DOM `<div class="checkbox"><label><input …><span class="control-label__text">` (`S/tests/Form/Widget/FormChoiceWidgetTest.php:47`) | `Checkbox.tsx:21-25` | no |
| `.checkbox-inline`, `.radio-inline` | `@apply inline-flex items-center gap-2 mr-4;` | `form-elements.html:1052` | no |
| `input[type=checkbox]:not(.tableCheckbox)` inside `.sonata-bc` | `@apply adm-checkbox;` ; `input[type=radio] { @apply adm-radio }` (native inputs, no iCheck; `data-sonata-icheck="false"` is honoured by *not* adding the `.adm-checkbox` class in adminata's theme — the compat element selector still styles it, which is acceptable) | `Checkbox.tsx:30`, `Radio.tsx:42-56` | no |
| `input[type=file]` | `@apply adm-file;` | `FileInput.tsx:12` | no |
| `.form-actions` | `@apply adm-form-actions;` (`flex flex-wrap items-center gap-3 rounded-panel border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900`) + `.stuck` (§5) | — | no |
| `.datepicker`, `.input-group.date` | no extra rule (flatpickr/Tempus Dominus owned by G5) | — | no |
| `fieldset legend` | `@apply adm-legend;` (`mb-4 border-b border-gray-200 pb-2 text-base font-medium text-gray-800 dark:border-gray-800 dark:text-white/90`) | — | no |

### 3.6 Tables (`compat/tables.css`)

| Class | Recipe | Source | Collision |
|---|---|---|---|
| `.table` | `@apply adm-table;` (`w-full min-w-full text-left text-theme-sm text-gray-700 dark:text-gray-400`) with descendant rules `.table > thead > tr > th { @apply adm-th }` (`px-(--adm-cell-px) py-3 text-theme-xs font-medium text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800`), `.table > tbody > tr > td, > th { @apply adm-td }` (`px-(--adm-cell-px) py-(--adm-cell-py) align-middle border-b border-gray-100 dark:border-gray-800`) | `table-06.html:8-17,58-60`, `BasicTableOne.tsx:122` | **`.table` collides with TW `table` (`display:table`)** — same display value for `<table>`; adminata never puts `.table` on non-table elements; compat rules are descendant-based so no conflict (§3.9) |
| `.table-bordered` | `@apply adm-table-bordered;` (`border border-gray-200 dark:border-gray-800 [&_th]:border [&_td]:border`) | — | no |
| `.table-striped` | `@apply adm-table-striped;` (`[&>tbody>tr:nth-child(even)]:bg-gray-25 dark:[&>tbody>tr:nth-child(even)]:bg-white/[0.02]`) | tailadmin-catalog §2.6 gap | no |
| `.table-hover` | `@apply adm-table-hover;` (`[&>tbody>tr:hover]:bg-gray-50 dark:[&>tbody>tr:hover]:bg-white/[0.03]`) | same | no |
| `.table-condensed` | `@apply adm-table-compact;` (cells `py-2 px-3`) | `table-01.html:84` | no |
| `.table-responsive` | `@apply adm-table-wrap;` (`w-full overflow-x-auto`) | `table-06.html:5` | no |
| `.sonata-ba-list-row-selected` | kept (§5) | — | no |
| `th.content-width` (`APP` `header_class`) | app-owned; note `header_class` values render verbatim | — | — |

### 3.7 Navs, dropdowns, tabs (`compat/nav.css`, `compat/dropdown.css`)

| Class | Recipe | Source | Collision |
|---|---|---|---|
| `.nav` | `@apply flex list-none flex-wrap gap-1 p-0 m-0;` | — | no |
| `.nav > li > a` | `@apply block rounded-control px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5;` | `menu-item-inactive` | no |
| `.nav-tabs` | `@apply adm-tabs;` (`flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-800`) ; `.nav-tabs > li > a { @apply adm-tab }` (`-mb-px inline-flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400`) ; `.nav-tabs > li.active > a { @apply adm-tab-active }` (`border-brand-500 text-brand-500 dark:text-brand-400`) ; `.nav-tabs a .has-errors:not([hidden]) { @apply text-error-500 }` (`base_edit_form.html.twig:52`) | designed (TailAdmin free has no underline tabs, tailadmin-catalog §2.13) | no |
| `.nav-tabs-custom` | `@apply adm-card;` + `.nav-tabs-custom > .nav-tabs { @apply px-4 pt-2 }` + `.nav-tabs-custom > .tab-content { @apply p-0 }` (`base_edit_form.html.twig:46`, `base_show.html.twig`, `edit_one_to_many_inline_tabs.html.twig`) | card | no |
| `.tab-content > .tab-pane` | `@apply hidden;` ; `.tab-content > .tab-pane.active { @apply block }` ; `.fade`, `.in` no-op | — | no |
| `.nav-pills > li > a` | `@apply rounded-control;` ; `.nav-pills > li.active > a { @apply bg-brand-500 text-white }` | — | no |
| `.nav-stacked` | `@apply flex-col;` + `.nav-stacked > li > a { @apply block border-b border-gray-100 py-3 dark:border-gray-800 }` (`block_search_result.html.twig:59`) | `watchlist.html` rows | no |
| `.nav.navbar-nav`, `.navbar-right`, `.navbar-left`, `.navbar-nav > li`, `.navbar-btn` | `.navbar-nav { @apply flex items-center gap-1 }` ; `.navbar-right { @apply ml-auto }` ; `.navbar-nav > li > a { @apply block px-3 py-2 text-sm font-medium text-gray-700 hover:text-brand-500 dark:text-gray-300 }` ; `.navbar-btn { @apply my-0 }` (`AbstractAdmin.php:2595` tab menu, `standard_layout.html.twig:250-273`, `ajax_layout.html.twig:29-31`, `APP` overrides `.nav.navbar-nav > li > a.sonata-action-element`) | header actions | no |
| `.navbar`, `.navbar-default`, `.navbar-collapse`, `.navbar-header`, `.navbar-brand` | `.navbar { @apply flex flex-wrap items-center gap-3 rounded-panel border border-gray-200 bg-white px-4 py-2 dark:border-gray-800 dark:bg-white/[0.03] }` ; `.navbar-brand { @apply text-base font-semibold text-gray-800 dark:text-white/90 }` ; `.navbar-collapse { @apply flex flex-1 flex-wrap items-center gap-3 }` (adminata's own `sonata_page_content_nav` keeps these marker names) | card header | no |
| `.dropdown` | `@apply relative;` | `header.html:607` | no |
| `.dropdown-toggle` | no-op (JS hook; `sonata-dropdown` controller toggles `.open` on the parent) | — | no |
| `.dropdown-menu` | `@apply adm-dropdown-panel hidden;` (`absolute right-0 z-dropdown mt-2 min-w-48 rounded-panel border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark`) ; `.open > .dropdown-menu, .dropdown-menu.show { @apply block }` ; `.dropdown-menu-left { @apply left-0 right-auto }` (`standard_layout.html.twig`, `add_block.html.twig:8,27`, `dashboard__action_create.html.twig`, `base_list.html.twig`, `tree.html.twig:56`; `layout-nav` R1/R2 for user `<li>` overrides) | `Dropdown.tsx:41` | no |
| `.dropdown-menu > li > a` | `@apply adm-dropdown-item;` (`flex w-full items-center gap-2 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5`) + `> .fas { @apply w-4 text-center }` (replaces `admin-lte-fas.scss:81-87`) | `DropdownItem.tsx:19`, `header.html:661` | no |
| `.dropdown-header` | `@apply adm-dropdown-header;` (`px-3 pb-1 pt-2 text-xs font-medium uppercase text-gray-400`) | `sidebar.html:37` | no |
| `.dropdown-menu-scrollable` (`styles.scss:69-72`) | `@apply adm-dropdown-scrollable;` (`max-h-[75vh] overflow-y-auto custom-scrollbar`) | — | no |
| `.dropdown-menu.multi-column`, `.dropdown-menu .dropdown-menu` (mega-menu `styles.scss:167-177`) | `.multi-column { @apply p-3 }` ; `.dropdown-menu .dropdown-menu { @apply static m-0 block min-w-0 border-0 p-0 shadow-none }` (`add_block.html.twig:8-27` nests `ul.dropdown-menu.col-md-N` inside `.row`) | — | no |
| `.dropdown-user`, `.user-menu` | `.dropdown-user { @apply w-64 p-3 }` | `header.html:643` `w-[260px] p-3` | no |
| `.breadcrumb` | `@apply flex flex-wrap items-center gap-1.5 bg-transparent p-0 text-sm text-gray-500 dark:text-gray-400;` ; `.breadcrumb > li + li::before { content:''; @apply mx-1 inline-block size-3 bg-current [mask:url(chevron.svg)] }` ; `.breadcrumb > li.active { @apply text-gray-800 dark:text-white/90 }` (`Breadcrumb/breadcrumb.html.twig` pinned byte-identical, `BreadcrumbsRuntimeTest.php:146`) | `breadcrumb.html:8-36` | no |
| `.pagination` | `@apply adm-pagination;` (`inline-flex list-none items-center gap-2 p-0 m-0`) ; `.pagination > li > a { @apply adm-page-item }` (`flex h-10 min-w-10 items-center justify-center rounded-control px-3 text-sm font-medium text-gray-700 hover:bg-brand-500/[0.08] hover:text-brand-500 dark:text-gray-400`) ; `.pagination > li.active > a { @apply adm-page-item-active }` (`bg-brand-500 text-white hover:bg-brand-600 hover:text-white`) ; `.pagination > li.disabled > a { opacity-50 pointer-events-none }` (`Pager/base_links.html.twig:13-25`) | `TN/.../Pagination.tsx:22-46` | no |
| `.pager` (`layout.scss:143-161` `td.pager ul`) | `@apply adm-pagination;` | — | no |

### 3.8 Modal (`compat/modal.css`)

Sonata's own modal (`CRUD/Association/edit_modal.html.twig:12-23`, inline copy at `form_admin_fields.html.twig:676`) is rewritten to `.adm-modal` **while keeping** `modal`, `modal-dialog`, `modal-lg`, `modal-content`, `modal-header`, `modal-title`, `modal-body` as marker classes (the Mongo fork's Panther test selects `.modal-content button[name="btn_create"]`, `MDB/tests/Functional/ReferenceMappingTest.php:61`). The compat rules exist for **user-authored** Bootstrap modals (`APP/…/standard_layout_override.html.twig:53-65` `#universal-modal`).

| Class | Recipe | Source | Collision |
|---|---|---|---|
| `.modal` | `@apply adm-modal hidden;` (`fixed inset-0 z-modal overflow-y-auto p-5`) ; `.modal.in, .modal.show, .modal[open] { @apply flex items-center justify-center }` ; `.modal[style*="display: block"] { @apply flex items-center justify-center }` (legacy inline-style show) | `TR/.../modal/index.tsx:57` | no |
| `.modal-backdrop` | `@apply adm-modal-backdrop;` (`fixed inset-0 z-modal bg-gray-400/50 backdrop-blur-[32px] dark:bg-gray-900/60`) ; `.modal.in::before { same, content:'' }` when no backdrop element exists | `index.tsx:60` | no |
| `.modal-dialog` | `@apply adm-modal-dialog;` (`relative z-1 mx-auto w-full max-w-[700px]`) ; `.modal-lg { @apply adm-modal-lg }` (`max-w-[min(90vw,1200px)]`) ; `.modal-sm { max-w-md }` | `profile-info-modal.html:10` | no |
| `.modal-content` | `@apply adm-modal-content;` (`relative rounded-modal bg-white p-4 shadow-theme-xl dark:bg-gray-900 lg:p-6`) | `index.tsx:54` | no |
| `.modal-header` | `@apply adm-modal-header;` (`mb-4 flex items-start justify-between gap-4 pr-10`) ; `.modal-header .close { @apply adm-modal-close }` (round gray 44px button, `profile-info-modal.html:15`) | — | no |
| `.modal-title` | `@apply adm-modal-title;` (`text-xl font-semibold text-gray-800 dark:text-white/90`) | `profile-info-modal.html:34` | no |
| `.modal-body` | `@apply adm-modal-body;` (`max-h-[70vh] overflow-y-auto custom-scrollbar`) | `profile-info-modal.html:42` | no |
| `.modal-footer` | `@apply adm-modal-footer;` (`mt-6 flex flex-wrap items-center justify-end gap-3`) | `profile-info-modal.html:179` | no |
| `.fade` | no-op | — | no |
| `body.modal-open` | `@apply overflow-hidden;` | `index.tsx:40` | no |

`Admin.setup_list_modal` (`S/assets/js/admin.js:36-56`) sizes `.modal-dialog` to 90 %/85 % inline — its replacement adds `adm-modal-lg` instead (JS gap G1).

### 3.9 Collisions with Tailwind utilities and their resolution (`compat/overrides.css`)

| Name | Tailwind v4 meaning | Bootstrap/AdminLTE meaning | Where it appears | Resolution |
|---|---|---|---|---|
| `fixed` | `position: fixed` | AdminLTE fixed layout on `<body>` (`standard_layout.html.twig:93`) | adminata layout, user `body_attributes` overrides copying the old string | Adminata's layout **drops `fixed` from the body class list**. Override for copied strings: `@layer sonata-overrides { body.sonata-bc.fixed { position: relative; } }` (layer declared after `utilities`, wins — T7). |
| `collapse` | `visibility: collapse` | hidden accordion pane (`.panel-collapse.collapse`, ORM `block_audit.html.twig:33`) | sibling/user markup only | `@source not inline("collapse")` in `app.css` so the utility is never emitted by the bundle build; for app builds that scan `vendor/` (§7) add the same line; safety net `@layer sonata-overrides { .sonata-bc .collapse:not(.in):not(.show) { display:none; visibility:visible } .sonata-bc .collapse.in, .sonata-bc .collapse.show { display:block; visibility:visible } }`. |
| `container` | max-width container | not used by Sonata (`container-fluid` only) | — | never define `.container`; document. |
| `hidden` | `display:none` | `display:none!important` | everywhere | identical intent; TW version wins; `.hide` shim carries `!important`. |
| `table` | `display:table` | table skin | `<table class="table …">` | element is already `display:table`; all compat table styling uses descendant selectors, never `.table` alone with layout props. |
| `block` | `display:block` | — (`{{ block.class }}` is a Twig variable) | — | none. |
| `truncate` | overflow ellipsis | — (`truncated` is Sonata's readmore state class, different token) | — | none. |
| `sr-only`, `text-center|left|right|nowrap`, `invisible`, `bg-black` | same meaning | same | — | not redefined. |
| `border` (`div.border`, `layout.scss:14-19`) | `border-width:1px` | Sonata card-ish box | legacy | Sonata rule **dropped** (§5); TW `border` applies. |
| `mt-10` (`APP/assets/styles/sonata-overrides.scss:11-13` defines `.mt-10{margin-top:10px}`) | `margin-top: 2.5rem` | app-defined | `APP` `'class' => 'text-right mt-10'` | app CSS is unlayered and wins; migration audit must flag app-defined utility-named classes. |
| `active`, `open`, `disabled`, `in`, `fade`, `close`, `caret`, `label`, `badge`, `well`, `row`, `nav`, `modal`, `dropdown`, `progress` | not utilities (`active:`/`open:`/`disabled:` are variants only) | Bootstrap state/component classes | — | free to define under `.sonata-bc`. |

### 3.10 Glyphicons (`compat/glyphicons.css`)

Sonata 4.43 ships Bootstrap 3's `glyphicons-halflings-regular.{eot,ttf,woff,woff2}` (`S/src/Resources/public/fonts/`) because `bootstrap.css` is imported whole (`S/assets/scss/app.scss:10`); the login recipe (`recipe_sonata_admin_without_user_bundle.rst:374,379`) and `APP/templates/security/login_form.html.twig:39,44` use `glyphicon glyphicon-user|lock form-control-feedback`. Ship `glyphicons-halflings-regular.woff2` (18 KB, MIT) and the `.glyphicon`/`.glyphicon-*::before` map extracted from Bootstrap 3.3 (≈ 260 rules, ≈ 12 KB minified) inside `compat-bootstrap3.css` so the recipe keeps rendering. `.glyphicon { @apply relative top-px inline-block font-[Glyphicons_Halflings] font-normal not-italic leading-none antialiased }`.

---

## 4. Semantic `.adm-*` API (`components/*.css`) and Twig filters

All single-selector primitives are `@utility` (§2 a); descendant rules are `@layer components`. Every recipe cites its TailAdmin origin. Dark variants are inside each recipe.

### 4.1 Layout / card (`card.css`, `layout.css`)

```css
@utility adm-row        { @apply grid grid-cols-12 gap-x-6 gap-y-6; }                                  /* index.html dashboard grids */
@utility adm-card       { @apply rounded-card border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]; } /* table-01.html:2 */
@utility adm-card-header{ @apply flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6 sm:py-5; } /* ComponentCard.tsx:15-19 */
@utility adm-card-title { @apply text-base font-medium text-gray-800 dark:text-white/90; }              /* ComponentCard.tsx:20 */
@utility adm-card-body  { @apply p-(--adm-card-p) sm:p-6 border-t border-gray-100 dark:border-gray-800; } /* ComponentCard.tsx:27 */
@utility adm-card-footer{ @apply border-t border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6; }
@utility adm-card-tools { @apply ml-auto flex items-center gap-2; }                                     /* chart-01.html:9 */
@utility adm-card-primary { @apply border-t-[3px] border-t-brand-500; }
@utility adm-card-info    { @apply border-t-[3px] border-t-blue-light-500; }
@utility adm-card-success { @apply border-t-[3px] border-t-success-500; }
@utility adm-card-warning { @apply border-t-[3px] border-t-warning-500; }
@utility adm-card-danger  { @apply border-t-[3px] border-t-error-500; }
@utility adm-card-default { @apply border-t-[3px] border-t-gray-300 dark:border-t-gray-700; }
@utility adm-card-solid   { @apply border-t-0 [&>.adm-card-header]:rounded-t-card [&>.adm-card-header]:text-white; }  /* + colour rules in @layer components */
@utility adm-card-collapsed { @apply [&>.adm-card-body]:hidden [&>.adm-card-footer]:hidden; }
@utility adm-wrapper   { @apply flex min-h-screen; }                                                    /* index.html:20-25 */
@utility adm-content   { @apply mx-auto w-full max-w-(--breakpoint-2xl) p-4 md:p-6; }                   /* index.html:44 */
@utility adm-header    { @apply sticky top-0 z-header flex h-(--adm-header-h) w-full border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900; } /* header.html:3 */
@utility adm-page-header { @apply mb-6 flex flex-wrap items-center justify-between gap-3; }           /* breadcrumb.html:1 */
@utility adm-page-title  { @apply text-xl font-semibold text-gray-800 dark:text-white/90; }            /* breadcrumb.html:3 */
@utility adm-breadcrumb  { @apply flex flex-wrap items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400; } /* breadcrumb.html:8 */
@utility adm-stuck       { @apply fixed z-sticky left-0 right-0 rounded-none border-x-0 shadow-theme-md lg:left-(--adm-sidebar-w) transition-[width,transform] duration-300; } /* replaces layout.scss:385-411 */
@utility adm-noscript-warning { @apply w-full bg-error-600 py-1 text-center text-sm font-bold text-white; } /* layout.scss:375-383 */
@utility adm-spinner     { @apply inline-block size-5 animate-spin rounded-full border-2 border-brand-500 border-t-transparent; } /* preloader.html:7 */
@utility adm-empty       { @apply flex flex-col items-center gap-3 rounded-card border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700; }
```

### 4.2 Buttons (`button.css`)

```css
@utility adm-btn { @apply inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-control font-medium transition disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-hidden focus-visible:ring-3 focus-visible:ring-brand-500/20; } /* Button.tsx:40-43 */
@utility adm-btn-xs { @apply h-8 px-2.5 text-theme-xs gap-1; }        /* table-row size (tailadmin-catalog §2.8 gap) */
@utility adm-btn-sm { @apply h-9 px-3 text-theme-sm; }
@utility adm-btn-md { @apply h-(--adm-control-h) px-4 text-sm; }      /* button-01.html:3 px-4 py-3 */
@utility adm-btn-lg { @apply h-12 px-5 text-base; }                    /* button-01.html:9 */
@utility adm-btn-block { @apply flex w-full; }
@utility adm-btn-icon  { @apply size-9 p-0; }                          /* profile.html:97 (44px) shrunk to table rows */
@utility adm-btn-primary   { @apply bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 disabled:bg-brand-300; }        /* Button.tsx:32-33 */
@utility adm-btn-secondary { @apply bg-white text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]; } /* button-04.html:3 */
@utility adm-btn-success { @apply bg-success-500 text-white shadow-theme-xs hover:bg-success-600; }    /* TN ModalBasedAlerts.tsx:18 */
@utility adm-btn-info    { @apply bg-blue-light-500 text-white shadow-theme-xs hover:bg-blue-light-600; }
@utility adm-btn-warning { @apply bg-warning-500 text-white shadow-theme-xs hover:bg-warning-600; }
@utility adm-btn-danger  { @apply bg-error-500 text-white shadow-theme-xs hover:bg-error-600; }
@utility adm-btn-outline { @apply bg-transparent text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-white/[0.03]; }
@utility adm-btn-ghost   { @apply bg-transparent text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5; } /* signin.html:71 tone */
@utility adm-btn-link    { @apply bg-transparent px-1 text-brand-500 shadow-none hover:text-brand-600 hover:underline dark:text-brand-400; } /* signin.html:243 */
@utility adm-btn-app     { @apply flex h-24 min-w-24 flex-col items-center justify-center gap-2 rounded-card border border-gray-200 bg-white text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300; }
@utility adm-btn-group   { @apply inline-flex items-center gap-1 align-middle; }
```

### 4.3 Badges (`badge.css`)

```css
@utility adm-badge         { @apply inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-theme-xs font-medium; } /* Badge.tsx:29-34 (size sm) */
@utility adm-badge-md      { @apply text-sm; }
@utility adm-badge-primary { @apply bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400; }           /* badge-01.html:4 */
@utility adm-badge-success { @apply bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500; }    /* :11 */
@utility adm-badge-error   { @apply bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500; }            /* :18 */
@utility adm-badge-warning { @apply bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400; }     /* :25 */
@utility adm-badge-info    { @apply bg-blue-light-50 text-blue-light-500 dark:bg-blue-light-500/15 dark:text-blue-light-500; } /* :32 */
@utility adm-badge-light   { @apply bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-white/80; }                   /* :39 */
@utility adm-badge-dark    { @apply bg-gray-500 text-white dark:bg-white/5 dark:text-white; }                         /* :46 */
@utility adm-badge-solid   { @apply text-white [&.adm-badge-primary]:bg-brand-500 [&.adm-badge-success]:bg-success-500 [&.adm-badge-error]:bg-error-500 [&.adm-badge-warning]:bg-warning-500 [&.adm-badge-info]:bg-blue-light-500; } /* badge-02.html */
```

### 4.4 Forms (`form.css`)

```css
@utility adm-input { @apply h-(--adm-control-h) w-full appearance-none rounded-control border border-gray-300 bg-transparent px-(--adm-control-px) py-(--adm-control-py) text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800; } /* form-elements.html:77 (minus dead dark:bg-dark-900) */
@utility adm-input-error    { @apply border-error-500 focus:border-error-300 focus:ring-error-500/20 dark:border-error-500 dark:text-error-400 dark:focus:border-error-800; } /* InputField.tsx:42 */
@utility adm-input-success  { @apply border-success-500 focus:border-success-300 focus:ring-success-500/20 dark:border-success-500; } /* :44 */
@utility adm-input-disabled { @apply cursor-not-allowed border-gray-300 bg-gray-100 text-gray-500 opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400; } /* :40 */
@utility adm-select   { @apply adm-input pr-11 bg-none bg-no-repeat bg-[right_1rem_center] bg-[length:1.25rem] [background-image:url("data:image/svg+xml,…chevron…")] [&[multiple]]:h-auto [&[multiple]]:min-h-(--adm-control-h) [&[multiple]]:bg-none [&[multiple]]:py-2 [&_option]:text-gray-700 dark:[&_option]:bg-gray-900 dark:[&_option]:text-gray-400; } /* Select.tsx:34,46 — chevron inlined instead of the sibling <span> so Symfony's choice_widget stays a bare <select> */
@utility adm-textarea { @apply adm-input h-auto min-h-28 py-2.5 leading-normal; }          /* TextArea.tsx:30 */
@utility adm-file     { @apply h-(--adm-control-h) w-full overflow-hidden rounded-control border border-gray-300 bg-transparent text-sm text-gray-500 shadow-theme-xs transition-colors file:mr-5 file:cursor-pointer file:rounded-l-control file:border-0 file:border-r file:border-solid file:border-gray-200 file:bg-gray-50 file:py-3 file:pl-3.5 file:pr-3 file:text-sm file:text-gray-700 hover:file:bg-gray-100 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:file:border-gray-800 dark:file:bg-white/[0.03] dark:file:text-gray-400; } /* FileInput.tsx:12 */
@utility adm-checkbox { @apply size-5 shrink-0 cursor-pointer appearance-none rounded-md border border-gray-300 bg-transparent transition checked:border-transparent checked:bg-brand-500 checked:bg-[url("data:image/svg+xml,…tick…")] checked:bg-center checked:bg-no-repeat hover:border-brand-500 focus-visible:outline-hidden focus-visible:ring-3 focus-visible:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700; } /* Checkbox.tsx:30 + tick path :46 */
@utility adm-radio    { @apply size-5 shrink-0 cursor-pointer appearance-none rounded-full border-[1.25px] border-gray-300 bg-transparent transition checked:border-[6px] checked:border-brand-500 dark:border-gray-700 dark:checked:border-brand-500; } /* Radio.tsx:42-56; CSS-only trick from style.css:726-728 */
@utility adm-switch   { @apply relative h-6 w-11 cursor-pointer appearance-none rounded-full bg-gray-200 transition duration-150 ease-linear checked:bg-brand-500 after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow-theme-sm after:transition after:duration-150 after:ease-linear checked:after:translate-x-full dark:bg-white/10; } /* Switch.tsx:57-64 */
@utility adm-label    { @apply mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400; } /* Label.tsx:17 */
@utility adm-required { @apply after:ml-0.5 after:text-error-500 after:content-['*']; }              /* signin.html:138 */
@utility adm-help     { @apply mt-1.5 block text-xs text-gray-500 dark:text-gray-400; }             /* InputField.tsx:67-72 */
@utility adm-error    { @apply mt-1.5 text-xs text-error-500; }
@utility adm-error-list { @apply list-none space-y-0.5 p-0 m-0 [&>li]:flex [&>li]:items-start [&>li]:gap-1.5; }
@utility adm-form-group { @apply mb-6 last:mb-0; }                                                    /* form-elements.html space-y-6 */
@utility adm-form-horizontal { @apply [&_.adm-form-group]:sm:grid [&_.adm-form-group]:sm:grid-cols-12 [&_.adm-form-group]:sm:gap-x-4 [&_.adm-label]:sm:col-span-3 [&_.adm-label]:sm:pt-2.5 [&_.adm-label]:sm:text-right [&_.sonata-ba-field]:sm:col-span-9; }
@utility adm-form-check  { @apply flex items-center gap-3 py-1 [&>label]:flex [&>label]:cursor-pointer [&>label]:items-center [&>label]:gap-3 [&>label]:text-sm [&>label]:font-medium [&>label]:text-gray-800 dark:[&>label]:text-gray-200; } /* Checkbox.tsx:21-25,74 */
@utility adm-input-group { @apply relative flex w-full items-stretch [&>.adm-input]:min-w-0 [&>.adm-input]:flex-1 [&>.adm-input:not(:first-child)]:rounded-l-none [&>.adm-input:not(:last-child)]:rounded-r-none; } /* form-elements.html:761 */
@utility adm-input-addon { @apply inline-flex items-center border border-gray-300 bg-gray-50 px-3.5 text-sm text-gray-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400 first:rounded-l-control first:border-r-0 last:rounded-r-control last:border-l-0; }
@utility adm-form-actions { @apply flex flex-wrap items-center gap-3 rounded-panel border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900; }
@utility adm-fieldset { @apply mb-6 min-w-0 border-0 p-0; }
@utility adm-legend   { @apply mb-4 w-full border-b border-gray-200 pb-2 text-base font-medium text-gray-800 dark:border-gray-800 dark:text-white/90; }
```

The Symfony form theme keeps emitting the legacy hook classes alongside (`form-control adm-input`, `form-group adm-form-group`, `control-label adm-label`, `help-block sonata-ba-field-help adm-help`, `checkbox adm-form-check`) — see forms-edit §3.1 rows 3, 9, 16 for the tests that pin them.

### 4.5 Tables, lists, pagination (`table.css`, `list.css`, `pagination.css`)

```css
@utility adm-table-wrap { @apply w-full overflow-x-auto; }                                             /* table-06.html:5 */
@utility adm-table      { @apply w-full min-w-full text-left text-theme-sm text-gray-700 dark:text-gray-400; }
@utility adm-th  { @apply whitespace-nowrap border-b border-gray-100 px-(--adm-cell-px) py-3 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400; } /* BasicTableOne.tsx:122 */
@utility adm-td  { @apply border-b border-gray-100 px-(--adm-cell-px) py-(--adm-cell-py) align-middle dark:border-gray-800; } /* table-06.html:60 */
@utility adm-table-striped { @apply [&>tbody>tr:nth-child(even)]:bg-gray-25 dark:[&>tbody>tr:nth-child(even)]:bg-white/[0.02]; }
@utility adm-table-hover   { @apply [&>tbody>tr:hover]:bg-gray-50 dark:[&>tbody>tr:hover]:bg-white/[0.03]; }
@utility adm-table-bordered{ @apply border border-gray-200 dark:border-gray-800 [&_th]:border-r [&_td]:border-r [&_th:last-child]:border-r-0 [&_td:last-child]:border-r-0; }
@utility adm-table-compact { @apply [&_th]:py-2 [&_th]:px-3 [&_td]:py-2 [&_td]:px-3; }
@utility adm-row-selected  { @apply bg-brand-50! dark:bg-brand-500/10!; }                              /* replaces layout.scss:287-290 #e3f7fe */
@utility adm-th-sortable   { @apply cursor-pointer select-none [&>a]:inline-flex [&>a]:items-center [&>a]:gap-1 hover:text-gray-700 dark:hover:text-gray-200; }
@utility adm-th-sorted     { @apply text-gray-800 dark:text-white/90; }
@utility adm-pagination    { @apply inline-flex list-none items-center gap-2 p-0 m-0; }               /* Pagination.tsx:18,26 */
@utility adm-page-item     { @apply flex h-10 min-w-10 items-center justify-center rounded-control px-3 text-sm font-medium text-gray-700 hover:bg-brand-500/[0.08] hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500; } /* :32-36 */
@utility adm-page-item-active { @apply bg-brand-500 text-white hover:bg-brand-600 hover:text-white; }
@utility adm-page-item-disabled { @apply pointer-events-none opacity-50; }
@utility adm-page-prev, adm-page-next → same as adm-btn-secondary adm-btn-sm (Pagination.tsx:22)
@utility adm-per-page { @apply adm-input inline-block h-9 w-auto px-3 py-1 text-theme-xs; }        /* Pager/base_results.html.twig:24 */
@utility adm-results  { @apply flex flex-wrap items-center gap-3 text-theme-sm text-gray-500 dark:text-gray-400; }
@utility adm-list-view-switch { @apply inline-flex items-center gap-0.5 rounded-control bg-gray-100 p-0.5 dark:bg-gray-900; } /* ChartTab.tsx:14 */
@utility adm-segmented-item   { @apply rounded-md px-3 py-2 text-theme-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white; }
@utility adm-segmented-item-active { @apply bg-white text-gray-900 shadow-theme-xs dark:bg-gray-800 dark:text-white; }
```

Sort arrows (`layout.scss:86-123` `::after` `↓/↑`) are replaced by an inline SVG chevron rendered by the list template (`th.sonata-ba-list-field-order-active > a > svg.adm-sort-icon`), rotated with `[.sonata-ba-list-field-header-order-desc_&]:rotate-180`; the `hover` swap is dropped (it inverted the arrow on hover to preview the next direction — replaced by `title` attribute).

### 4.6 Twig filters

`sonata_grid_class(string $class): string` — appends Tailwind utilities, keeps every original token:

| Input token | Appended | Note |
|---|---|---|
| `row` | `adm-row` | |
| `col-xs-N` | `col-span-N` | |
| `col-sm-N` / `col-md-N` / `col-lg-N` | `sm:col-span-N` / `md:col-span-N` / `lg:col-span-N` | `col-span-12` is prepended when no `col-xs-*` token exists (Bootstrap columns are full-width below their breakpoint) |
| `col-{bp}-offset-N` | `{bp}:col-start-{N+1}` (`xs` → no prefix) | |
| `col-*-push-*`, `col-*-pull-*` | nothing (logged at debug) | |
| any other token (`section-fiat`, `nopadding`, `pt-overrides`, user utilities) | passthrough | unknown classes never break |

Examples: `'col-md-6'` → `col-md-6 col-span-12 md:col-span-6`; `'col-lg-3 col-xs-6'` (`dashboard.rst:266`) → `col-lg-3 col-xs-6 col-span-6 lg:col-span-3`; `'section-geolocation col-md-12'` (`APP/src`) → `section-geolocation col-md-12 col-span-12 md:col-span-12`.

`sonata_box_class(string $class): string`:

| Input token | Appended |
|---|---|
| `box` | `adm-card` |
| `box-primary` / `box-info` / `box-success` / `box-warning` / `box-danger` / `box-default` | `adm-card-primary` / … / `adm-card-default` |
| `box-solid` | `adm-card-solid` |
| `collapsed-box` | `adm-card-collapsed` |
| `panel`, `panel-default` | `adm-card` |
| other | passthrough |

Templates render `class="{{ form_group.box_class|sonata_box_class }}"` (`base_edit_form_macro.html.twig:8`) and `class="{{ show_group.class|default('col-md-12')|sonata_grid_class }} …"` (`base_show.html.twig:97`), `Core/dashboard.html.twig:59,120` `{{ block.class|sonata_grid_class }}`, `:85-106` `col-md-{{ width_left }}` → `{{ ('col-md-' ~ width_left)|sonata_grid_class }}`. With compat loaded the raw names are styled twice (identically); with compat removed the filter output alone renders the page.

### 4.7 Alerts, dropdowns, tabs, modal (`alert.css`, `dropdown.css`, `tabs.css`, `modal.css`)

```css
@utility adm-alert          { @apply relative mb-4 rounded-panel border p-4 text-sm text-gray-700 dark:text-gray-300; } /* alert-success.html:2 */
@utility adm-alert-success  { @apply border-success-500 bg-success-50 dark:border-success-500/30 dark:bg-success-500/15; }
@utility adm-alert-error    { @apply border-error-500 bg-error-50 dark:border-error-500/30 dark:bg-error-500/15; }
@utility adm-alert-warning  { @apply border-warning-500 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/15; }
@utility adm-alert-info     { @apply border-blue-light-500 bg-blue-light-50 dark:border-blue-light-500/30 dark:bg-blue-light-500/15; }
@utility adm-alert-title    { @apply mb-1 text-sm font-semibold text-gray-800 dark:text-white/90; }   /* alert-success.html:24 */
@utility adm-alert-dismissible { @apply pr-11; }
@utility adm-alert-close    { @apply absolute right-3 top-3 flex size-7 items-center justify-center rounded-full text-gray-400 hover:bg-black/5 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-gray-200; }
@utility adm-dropdown        { @apply relative; }
@utility adm-dropdown-panel  { @apply absolute right-0 z-dropdown mt-2 min-w-48 rounded-panel border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark; } /* Dropdown.tsx:41, header.html:643 */
@utility adm-dropdown-item   { @apply flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-theme-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-gray-200; } /* header.html:661 */
@utility adm-dropdown-item-active { @apply bg-brand-50 text-brand-500 dark:bg-brand-500/[0.12] dark:text-brand-400; }
@utility adm-dropdown-header { @apply px-3 pb-1 pt-2 text-xs font-medium uppercase text-gray-400; }
@utility adm-dropdown-divider{ @apply my-2 h-px bg-gray-100 dark:bg-gray-800; }
@utility adm-dropdown-scrollable { @apply custom-scrollbar max-h-[75vh] overflow-y-auto; }
@utility adm-dropdown-multicol   { @apply grid gap-4 p-3 [grid-template-columns:repeat(var(--adm-cols,2),minmax(10rem,1fr))]; } /* add_block.html.twig column split */
@utility adm-tabs        { @apply flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-800; }
@utility adm-tab         { @apply -mb-px inline-flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200; }
@utility adm-tab-active  { @apply border-brand-500 text-brand-500 dark:text-brand-400; }
@utility adm-tab-error   { @apply text-error-500; }
@utility adm-tab-panel   { @apply hidden; }
@utility adm-tab-panel-active { @apply block; }
@utility adm-modal          { @apply fixed inset-0 z-modal overflow-y-auto p-5; }                    /* modal/index.tsx:57 */
@utility adm-modal-backdrop { @apply fixed inset-0 bg-gray-400/50 backdrop-blur-[32px] dark:bg-gray-900/60; } /* :60 */
@utility adm-modal-dialog   { @apply relative z-1 mx-auto w-full max-w-[700px]; }                   /* profile-info-modal.html:10 */
@utility adm-modal-lg       { @apply max-w-[min(90vw,1200px)]; }
@utility adm-modal-content  { @apply relative rounded-modal bg-white p-4 shadow-theme-xl dark:bg-gray-900 lg:p-6; }
@utility adm-modal-header   { @apply mb-4 flex items-start justify-between gap-4 pr-10; }
@utility adm-modal-title    { @apply text-xl font-semibold text-gray-800 dark:text-white/90; }
@utility adm-modal-close    { @apply absolute right-4 top-4 flex size-10 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:bg-white/[0.05] dark:hover:bg-white/[0.07]; } /* :15 */
@utility adm-modal-body     { @apply custom-scrollbar max-h-[70vh] overflow-y-auto; }
@utility adm-modal-footer   { @apply mt-6 flex flex-wrap items-center justify-end gap-3; }
```

### 4.8 Sidebar / menu (`sidebar.css`) — classes emitted by `Menu/sonata_menu.html.twig` are kept

```css
@utility adm-sidebar { @apply sidebar fixed left-0 top-0 z-sidebar flex h-screen w-(--adm-sidebar-w) -translate-x-full flex-col overflow-y-hidden border-r border-gray-200 bg-(--adm-sidebar-bg) px-5 transition-[width,transform] duration-300 dark:border-gray-800 lg:static lg:translate-x-0; } /* sidebar.html:3 */
@utility adm-sidebar-header  { @apply flex items-center gap-2 pb-7 pt-8; }                             /* :8 */
@utility adm-sidebar-body    { @apply no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear; } /* :31 */
@utility adm-sidebar-overlay { @apply fixed inset-0 z-overlay hidden bg-gray-900/50 lg:hidden; }      /* overlay.html:4 */
@utility adm-sidebar-group-title { @apply mb-4 text-xs uppercase leading-5 text-gray-400; }           /* :37 */

@layer components {
  .sonata-bc .sidebar-menu { @apply m-0 flex list-none flex-col gap-1 p-0; }                        /* MenuTest.php:41 hook */
  .sonata-bc .sidebar-menu > li > a { @apply menu-item menu-item-inactive group; }
  .sonata-bc .sidebar-menu > li.active > a { @apply menu-item-active; }
  .sonata-bc .sidebar-menu > li > a > .fa, > .fas, > .far, > svg { @apply menu-item-icon size-5 shrink-0 text-center; }   /* admin-lte-fas.scss:89-95 width:20px */
  .sonata-bc .sidebar-menu > li.active > a > .fa, … { @apply menu-item-icon-active; }
  .sonata-bc .sidebar-menu li.header { @apply adm-sidebar-group-title; }
  .sonata-bc .sidebar-menu .treeview-menu { @apply mt-1 hidden list-none flex-col gap-1 pl-9; }
  .sonata-bc .sidebar-menu .treeview.active > .treeview-menu,
  .sonata-bc .sidebar-menu .treeview.menu-open > .treeview-menu,
  .sonata-bc .sidebar-menu li.keep-open > .treeview-menu { @apply flex; }                             /* styles.scss:198-201 */
  .sonata-bc .treeview-menu > li > a { @apply menu-dropdown-item menu-dropdown-item-inactive; }
  .sonata-bc .treeview-menu > li.active > a { @apply menu-dropdown-item-active; }
  .sonata-bc .sidebar-menu .pull-right-container { @apply menu-item-arrow float-none ml-auto; }        /* sonata_menu.html.twig:43 */
  .sonata-bc .sidebar-menu .treeview.active > a .pull-right-container { @apply menu-item-arrow-active; }
  .sidebar-collapse .adm-sidebar { @apply lg:w-(--adm-sidebar-w-collapsed) lg:px-3; }                 /* cookie sonata_sidebar_hide, standard_layout.html.twig:96 */
  .sidebar-collapse .adm-sidebar .menu-item-text, .sidebar-collapse .adm-sidebar .adm-sidebar-group-title, .sidebar-collapse .adm-sidebar .menu-item-arrow, .sidebar-collapse .adm-sidebar .treeview-menu { @apply lg:hidden; }
  .adm-sidebar:hover { width: var(--adm-sidebar-w); }                                                 /* style.css:290-319 hover-expand, kept as CSS */
  .adm-sidebar:hover .menu-item-text, .adm-sidebar:hover .adm-sidebar-group-title, .adm-sidebar:hover .menu-item-arrow { display: revert; }
}
```

TailAdmin's `menu-item*`, `menu-dropdown-*`, `no-scrollbar`, `custom-scrollbar` are copied verbatim as `@utility` from `style.css:190-267`, taking the React icon variants (`menu-item-icon`, `menu-item-icon-size`, currentColor) from `TR/src/index.css` so FA `<i>` icons and SVGs colour alike; `.dark .custom-scrollbar::-webkit-scrollbar-thumb { #344054 }` (`style.css:269-271`) kept.

### 4.9 Misc Sonata components (`misc.css`, `show.css`)

```css
@utility adm-readmore     { @apply block; }                                    /* readmore.scss:1-3 */
@utility adm-readmore-btn { @apply adm-btn-link p-0 text-theme-xs; }
@utility adm-tree         { @apply m-0 list-none p-0 [&_ul]:list-none [&_ul]:pl-7; }          /* tree.scss:10-22 */
@utility adm-tree-item    { @apply relative mb-1.5 flex items-center gap-2 rounded-control border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300; } /* tree.scss:24-55 */
@utility adm-tree-item-active { @apply border-brand-500 ring-1 ring-brand-500/20; }         /* tree.scss:61-93 (arrow pseudo-elements dropped) */
@utility adm-mosaic       { @apply grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6; } /* list_outer_rows_mosaic col-xs-6 col-sm-3 */
@utility adm-mosaic-item  { @apply adm-card group relative overflow-hidden; }                /* styles.scss mosaic-box-outter */
@utility adm-mosaic-hover { @apply absolute inset-0 flex items-start justify-end gap-1 bg-white/80 p-2 opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-900/80; } /* mosaic-inner-box-hover */
@utility adm-mosaic-caption { @apply truncate border-t border-gray-100 px-3 py-2 text-theme-xs text-gray-600 dark:border-gray-800 dark:text-gray-400; }
@utility adm-search-box   { @apply adm-card break-inside-avoid; }                            /* search.html.twig + block_search_result */
@utility adm-search-list  { @apply divide-y divide-gray-100 dark:divide-gray-800 [&>li]:break-words [&>li>a]:block [&>li>a]:px-5 [&>li>a]:py-2.5; } /* layout.scss:126-130 */
@utility adm-filter-box   { @apply adm-card mb-6; }
@utility adm-filter-row   { @apply grid grid-cols-12 items-center gap-3 mb-3; }
@utility adm-editable     { @apply cursor-pointer border-b border-dashed border-brand-400 text-brand-500 hover:border-solid dark:text-brand-400; } /* x-editable .editable-click look */
@utility adm-editable-empty { @apply italic text-gray-400; }                                 /* x-editable .editable-empty */
@utility adm-editable-popover { @apply adm-dropdown-panel z-popover w-72 p-3; }
@utility adm-sortable-handle { @apply cursor-grab text-gray-400 active:cursor-grabbing; }
@utility adm-sortable-ghost  { @apply rounded-panel bg-brand-50 opacity-60 shadow-theme-sm dark:bg-brand-500/10; } /* style.css:744-751 .task.is-dragging */
@utility adm-metric, adm-metric-icon, adm-metric-label, adm-metric-value → metric-group-01.html:3-33 (stats block)
@utility adm-avatar { @apply flex size-10 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300; } /* avatar-01, AvatarText.tsx:36 */
@utility adm-kbd   { @apply inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-[7px] py-[4.5px] text-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400; } /* header.html:132 */

@layer components {
  .sonata-bc .sonata-ba-view-container th { @apply w-48 align-top text-xs font-medium uppercase text-gray-500 dark:text-gray-400; }  /* layout.scss:254-263; profile.html:204-247 key/value */
  .sonata-bc .sonata-ba-view-container td { @apply text-sm text-gray-800 dark:text-white/90; }
  .sonata-bc .history-audit-compare th.diff { @apply bg-warning-50 dark:bg-warning-500/15; }   /* layout.scss:270-272 pink → warning */
}
```

---

## 5. Sonata SCSS rule migration table (`S/assets/scss/*.scss`)

Verdicts: **K** = kept as component CSS (rewritten with tokens, in `components/*.css`), **U** = replaced by utilities in the rewritten template, **C** = moved to the compat layer, **D** = dropped.

### 5.1 `styles.scss`

| Lines | Rule | Verdict | Replacement |
|---|---|---|---|
| 15-19 | `html { font-size: initial !important; min-height:100%; position:relative }` | D | Tailwind preflight; no `!important` root font-size hack needed |
| 21-35 | `footer` fixed black bar | D | no footer in adminata layout; `side_bar_after_nav_content` block stays |
| 37-55 | `.logo img/span` sizing | U | logo block renders `img.max-h-8` + `span.menu-item-text`; `dark:hidden`/`hidden dark:block` pair for `title_logo_dark` (sidebar.html:11-25) |
| 57-59 | `body > .header .logo` font | D | |
| 61-63 | `.main-header { height:50px }` | U | `adm-header` `h-(--adm-header-h)` |
| 65-67 | `.open > .dropdown-menu { animation }` | D | |
| 69-72 | `.dropdown-menu-scrollable` | K → `adm-dropdown-scrollable` (+ C alias) | |
| 75-87 | `.btn.btn-outline` | C | `.sonata-bc .btn-outline` (§3.3) |
| 90-92 | `.navbar-static-side li` border | D | |
| 94-107 | `.navbar-brand`, `.navbar-text .navbar-link` | C | `.navbar-brand` (§3.7) |
| 109-117 | `.content-header .navbar { margin-bottom:0 }`, `.right-side > .content-header` | D | |
| 119-133 | `.nav-second-level/.nav-third-level` indents | K | `.treeview-menu` `pl-9`; third level `pl-12` via `.treeview-menu .treeview-menu { @apply pl-6 }` |
| 135-189 | `div.mosaic-*` (7 rules) | K → `adm-mosaic*` (§4.9); marker classes `mosaic-box`, `mosaic-inner-box`, `mosaic-inner-box-hover`, `mosaic-inner-text`, `mosaic-box-outter`, `mosaic-inner-link`, `mosaic-box-label` stay on the markup (`recipe_customizing_a_mosaic_list.rst`) and get `@layer components` aliases to the same recipes | |
| 191-254 | `.navbar-top-links …` (li inline-block, dropdown widths, `.dropdown-user` right-aligned, mega-menu) | D except `.dropdown-user { left:auto; right:0 }` → C `.dropdown-user` (§3.7) and mega-menu nesting → C `.dropdown-menu .dropdown-menu` | |
| 257-266 | `.sonata-bc .breadcrumb { background:inherit; float:left; … }` | K → `adm-breadcrumb` (+ C `.breadcrumb`) | |
| 268-270 | `.navbar-top-links .dropdown-menu li a:hover { text-decoration:none }` | D | preflight has no underline on `a` |
| 274-284 | mega-menu `.dropdown-menu .dropdown-menu`, `.multi-column` | C | §3.7 |
| 287-289 | `.navbar-static-top { margin-bottom:0 }` | D | |
| 291-298 | `.skin-black .navbar … hover` colours | D | skins are token files now |
| 301-303 | `.sidebar-search { padding:15px }` | U | search form in sidebar header (`adm-sidebar-search`) |
| 305-308 | `.sidebar-menu li.keep-open > .treeview-menu { display:block!important }` | K | `.sidebar-menu li.keep-open > .treeview-menu { @apply flex }` (§4.8) |
| 310-320 | `.arrow`, `.fa.arrow::before` (`\f104`/`\f107`) | D | arrow is an SVG with `menu-item-arrow(-active)` rotate |
| 322-336 | `@media ≥768 .navbar-static-side …`, dropdown margins | D | |
| 339-350 | `table.sonata-ba-list { font-size:14px; img{max-width:100%}; td{overflow:auto} }` | K | `adm-table` `text-theme-sm`; `.sonata-ba-list td img { @apply h-auto max-w-full align-bottom }` (also 192-194 of layout.scss); `td { overflow:auto }` dropped (was for wide content; `adm-table-wrap` scrolls instead) |
| 352-357 | `td.sonata-ba-list-label` bold right | K | `.sonata-bc .sonata-ba-list-label { @apply text-right font-medium text-gray-600 dark:text-gray-400 align-middle }` (dashboard `block_admin_list.html.twig:37`) |
| 360-386 | `.box .box-header h4.box-title.filter_legend` + `h4.filter_legend::before` triangle + `.active` | D | filter panel redesigned: `adm-card-header` with a chevron SVG and `sonata-toggle-filter`; the class `filter_legend` is not in 4.43 templates (grep: 0 hits) |
| 388-391 | `tr.filter.active * { bold }` | D | not in templates |
| 393-395 | `form.sonata-filter-form.form-stacked { padding-left:0 }` | D | |
| 397-401 | `body.fixed .sonata-list-table { margins }` | D | `body.fixed` gone (§3.9) |

### 5.2 `layout.scss`

| Lines | Rule | Verdict | Replacement |
|---|---|---|---|
| 10-12 | `.form-horizontal .control-group { margin-bottom:10px }` | D | Bootstrap-2 leftover |
| 14-19 | `div.border { … }` | **D** — collides with TW `border` (§3.9) | |
| 21-56 | `div.connection` (login box from Bootstrap-2 era) | D | not in templates |
| 58-67 | `.sonata-ba-field-inline-table input.title / textarea.title` | K | `.sonata-bc .sonata-ba-field-inline-table input.title { @apply w-24 }`, `textarea.title { @apply h-12 w-36 }` |
| 69-71 | `.sonata-actions { float:right }` | U | header actions `ml-auto` (`adm-header-actions`) |
| 73-83 | `div.sonata-ba-modal-edit-one-to-one` hides `td.sonata-ba-list-field-batch`, `div.sonata-ba-list-actions`, `th.sonata-ba-list-field-header-batch` | K | verbatim in `components/list.css` (`@apply hidden`); class is set by the association modal JS |
| 86-123 | sort arrows `th.sonata-ba-list-field-header-order-*` `a::after` content `↓/↑` + hover swap | K (redesigned) | `adm-th-sortable` + inline SVG (§4.5); keep the `sonata-ba-list-field-order-active`/`header-order-asc|desc` classes on `th` |
| 126-130 | `.sonata-search-result-list > li { word-wrap }` | K | `adm-search-list` |
| 132-141 | `.search-box-item .matches { overflow-x:auto; nowrap } > a.label { margin }` | K | `.sonata-bc .search-box-item .matches { @apply flex gap-1 overflow-x-auto whitespace-nowrap no-scrollbar }` |
| 143-161 | `td.pager ul …` old pager | C | `.pager` → `adm-pagination` |
| 163-171 | `td.sonata-ba-list-field-boolean i { margin-right:1ex } a:hover { no underline }` | K | `.sonata-ba-list-field-boolean { @apply [&_i]:mr-1 }`; boolean cells now render `adm-badge-success/error` (`display_boolean.html.twig`) |
| 173-181 | `td.sonata-ba-list-field-currency/percent/integer { text-align:right }`, `-select { center }` | K | verbatim with `@apply text-right` / `text-center` (`APP/assets/styles/sonata-overrides.scss` depends on `.sonata-ba-list-field-integer` hooks) |
| 184-186 | `.sonata-bc.sonata-ba-no-side-menu div.container-fluid > div.content { margin-left:0 }` | D | class `sonata-ba-no-side-menu` not in 4.43 templates; keep no rule |
| 188-190 | `h4.filter_legend table` | D | |
| 192-194 | `table.sonata-ba-list td img { vertical-align:bottom }` | K | merged above |
| 196-199 | `.sonata-ba-action.btn:not(:hover) { background:none; color:inherit }` | D | replaced by explicit `adm-btn-secondary adm-btn-xs` / `adm-btn-ghost adm-btn-icon` on action buttons |
| 201-203 | `a.sonata-link-identifier { bold }` | K | `.sonata-bc .sonata-link-identifier { @apply font-medium text-gray-800 hover:text-brand-500 dark:text-white/90 }` (hook pinned by `MDB/tests/Functional/CRUDTest.php:26`) |
| 205-207 | `.sonata-ba-list-field-header-label-icon { margin-right:2px }` | K | `@apply mr-1` |
| 209-213 | `em.sonata-ba-field-help { color:#999; display:block; margin-bottom:10px }` | K | `.sonata-bc .sonata-ba-field-help { @apply adm-help }` |
| 215-217 | `fieldset legend { padding-left:0 }` | U | `adm-legend` |
| 219-231 | `.sonata-medium` widths (400px / 150px inline) | K | `.sonata-bc .sonata-medium { @apply max-w-md }`, `textarea.sonata-medium { @apply h-32 }`, `.sonata-ba-field-inline-table .sonata-medium { @apply max-w-40 }` (documented `attr.class` value) |
| 233-235 | `input[type=file] { height:34px }` | U | `adm-file` |
| 237-240 | `.sonata-ba-field-standard-natural .field-actions { display:block; margin-top:5px }` | K | `@apply mt-2 flex flex-wrap gap-2` |
| 242-252 | `.sonata-ba-view-title` + td/th border 0 | K | `.sonata-bc .sonata-ba-view-title { @apply mb-2 text-lg font-semibold text-gray-800 dark:text-white/90 [&_td]:border-0 [&_th]:border-0 }` |
| 254-285 | `.sonata-ba-view-container th { width:130px }`, td/th borders, `.history-audit-compare` widths + `th.diff` pink, `:nth-child(2n)` zebra | K | §4.9 rules; zebra via `adm-table-striped` on the show table; `th.diff` → warning tint |
| 287-290 | `.table-striped tr.sonata-ba-list-row-selected td/th { #e3f7fe }` | K | `.sonata-bc tr.sonata-ba-list-row-selected > td, > th { @apply adm-row-selected }` (batch checkbox JS toggles the class) |
| 292-297 | zebra hover | K | `adm-table-hover` |
| 299-301 | `.container-fluid > .sidebar { top:auto }` | D | |
| 303-307 | `.sonata-action-element.btn-group { inline-block; padding }` | K | `.sonata-bc .sonata-action-element.btn-group { @apply adm-btn-group px-2 }` |
| 309-312 | `.sonata-collection-add/-delete { box-shadow:none }` | U | buttons are `adm-btn-ghost adm-btn-icon` (no shadow) |
| 314-317 | `.no-js .sonata-collection-add/-delete { display:none }` | K | base.css (§1.5) |
| 319-321 | `ul.inputs-list { padding-left:150px }` | D | Bootstrap-2 |
| 323-334 | `legend + .sonata-ba-collapsed-fields` margins, `.sonata-ba-collapsed-fields > p` | K | `.sonata-bc .sonata-ba-collapsed-fields > p { @apply mb-4 text-sm text-gray-500 dark:text-gray-400 }` (group description); legend spacing via `adm-legend` |
| 336-338 | `.bordered-table tbody.ui-sortable tr { cursor:move }` | K (renamed) | `.sonata-bc tbody.adm-sortable tr { @apply cursor-move }` + `adm-sortable-handle` |
| 340-347 | `.sonata-ba-fieldset-collapsed legend::before '+ '`, `-close '- '` | K | kept verbatim (collapsible fieldset hook) with `@apply` on `legend::before` content |
| 349-352 | `.sonata-preview-form-container fieldset, .tabbable { display:none }` | K | verbatim (`preview_mode.rst:82` documents the container) |
| 354-356 | `.pagination { margin:0 }` | U | `adm-pagination` |
| 358-369 | `.field-short-description` chip | K | `.sonata-bc .field-short-description { @apply inline-flex min-w-64 items-center gap-2 rounded-control border border-gray-200 bg-gray-50 px-4 py-2 text-sm dark:border-gray-800 dark:bg-white/[0.03] }` (forms-edit §3.1 row 25) |
| 371-373 | `.required::after { content:'*' }` | K | `.sonata-bc .required::after { @apply ml-0.5 text-error-500 content-['*'] }` (test-pinned class `AdminLayoutTest.php:31`); `adm-required` is the same recipe |
| 375-383 | `.noscript-warning` | K | `adm-noscript-warning` + alias |
| 385-411 | `.navbar.stuck`, `.form-actions.stuck` (fixed, `top:50px`, `width:calc(100% - 230px)`, `z-index:5`), `.sidebar-collapse` width 100% | K (rewritten) | `.sonata-bc .stuck { @apply adm-stuck }` ; `.navbar.stuck { @apply top-(--adm-header-h) }` ; `.form-actions.stuck { @apply bottom-0 }` ; `.sidebar-collapse .stuck { @apply lg:left-(--adm-sidebar-w-collapsed) }` ; sentinels `.navbar-sentinel`, `.action-sentinel` (`sticky_controller.js:47,74`) get `min-h-[1px]` to keep layout height — class names `stuck`, `navbar-sentinel`, `action-sentinel` are hooks (forms-edit §10.4) |
| 413-451 | `@media ≤768 body.fixed …` header/relative, `.stuck` relative, slimScroll, dropdown min-width | D (`.stuck` mobile part → `max-lg:static max-lg:w-full` inside `adm-stuck`) | |

### 5.3 `tree.scss` — all K, rewritten as `adm-tree`, `adm-tree-item`, `adm-tree-item-active` (§4.9) keeping the hook classes `sonata-tree`, `sonata-tree__item`, `sonata-tree__item__edit`, `sonata-tree__item__is-hybrid`, `sonata-tree--small`, `sonata-tree--toggleable`, `is-toggled`, `is-active`, `js-treeview` as `@layer components` aliases; `tree.scss:64-93` speech-bubble pseudo-elements → D (ring instead); `130-151` toggleable (`li > ul { display:none }`, `.is-toggled` rotate, `:last-child .fa-caret-right { display:none }`) → K verbatim; the `.fa-caret-right` icon becomes an SVG with `[.is-toggled_&]:rotate-90`.

### 5.4 `flashmessage.scss` (`:10-37`, identical to `twig-extensions/.../public/css/flashmessage.css`) — all K verbatim in `components/alert.css` (`.read-more-state`, `.read-more-target`, `.read-more-state:checked ~ .read-more-wrap .read-more-target`, `.read-more-trigger`, `.alert .read-more-trigger`), plus `.read-more-trigger { @apply mt-2 inline-flex cursor-pointer items-center gap-1 text-theme-xs font-medium underline }`; the `more/less` `hide` toggle keeps working through the compat `.hide` (`base.js:20-24`) — but adminata's own flash template applies `hidden` and the JS toggles `hidden` (js-arch gap), so the rule is only needed for the twig-extensions template.

### 5.5 `readmore.scss` (`:1-26`) — all K verbatim (`.sonata-readmore`, `.sonata-readmore-content { overflow:hidden }`, `.expanded { max-height:none!important }`, `:last-child { mb-0 }`, `.sonata-readmore-btn { p-0 }`, `.sonata-readmore-content:not(.truncated,.expanded) + .sonata-readmore-btn, > .sonata-readmore-btn { display:none }`) — `readmore_controller.js:34-53` sets `maxHeight` inline and toggles `truncated`/`expanded`; `.sonata-readmore-btn` additionally `@apply adm-readmore-btn`.

### 5.6 `admin-lte-fas.scss` (`:13-213`) — D except: `.box-header > .fas…` (`:35-45`) → C; `.treeview-menu > li > a > .fas`, `.sidebar-menu > li > a > .fas { width:20px }` (`:65-71,89-95`) → K (§4.8); `.dropdown-menu > li > a > .fas { margin-right:10px }` (`:81-87`) → C; `.form-control-feedback.fas` line-heights (`:165-213`) → C single rule (§3.2 login). Carousel, timeline, todo-list, user-panel, notifications-menu rules → D.

### 5.7 `app.scss` imports (`:10-47`) — Bootstrap 3, AdminLTE, iCheck, jquery-ui sortable, select2 (+ bootstrap theme), x-editable CSS → D (replaced by compat / Tom Select theme / SortableJS / `adm-editable*`); Font Awesome `all.css` + `v4-shims.css` (`:15-16`) → kept in `fontawesome.css` (FA6); Source Sans Pro (`:20-26`) → replaced by Outfit Variable (`fonts.css`).

### 5.8 `[hidden]` — Bootstrap's `[hidden]{display:none!important}` is provided by Tailwind preflight (T11); no rule. Elements adminata toggles use the `hidden` **attribute** (`el.hidden = true`), never the `hidden` class, so a user utility `md:block` on the same element cannot fight it.

---

## 6. Third-party CSS, fonts, size budget, CI

### 6.1 Tom Select (`vendor/tom-select.css`) — same selectors the real app skins

`APP/assets/styles/sonata-overrides.scss:66-100` already targets `.ts-wrapper.single/.multi .ts-control`, `.ts-wrapper.form-control`, `.ts-dropdown` (ux-autocomplete uses `tom-select.default.css`, `APP/assets/controllers.json:8`). Adminata does **not** import a Tom Select stylesheet; it ships its own theme against the same class names (`tom-select.default.css` selectors `.ts-wrapper`, `.ts-control`, `.ts-dropdown`, `.ts-dropdown-content`, `.ts-hidden-accessible`, `APP/node_modules/tom-select/dist/css/tom-select.default.css:15-116`), so ux-autocomplete fields and Sonata selects look identical:

```css
/* assets/css/vendor/tom-select.css — theme for Tom Select ≥ 2.3 (no vendor CSS imported) */
@layer components {
  .ts-hidden-accessible { @apply sr-only; }
  .ts-wrapper { @apply relative; }
  .ts-wrapper.form-control, .ts-wrapper.adm-input { @apply h-auto p-0 border-0 bg-transparent shadow-none; }   /* app override :88-91 equivalent */
  .ts-wrapper .ts-control { @apply adm-input flex min-h-(--adm-control-h) h-auto flex-wrap items-center gap-1.5 py-1.5 pr-10; }
  .ts-wrapper.single .ts-control { @apply cursor-pointer bg-none bg-no-repeat bg-[right_1rem_center] bg-[length:1.25rem] [background-image:url("data:image/svg+xml,…chevron…")]; }
  .ts-wrapper.single .ts-control::after { content: none; }                                        /* removes vendor arrow (app :80-82) */
  .ts-wrapper.focus .ts-control { @apply border-brand-300 ring-3 ring-brand-500/10 dark:border-brand-800; }
  .ts-wrapper.disabled .ts-control, .ts-wrapper.locked .ts-control { @apply adm-input-disabled; }
  .has-error .ts-wrapper .ts-control { @apply adm-input-error; }
  .ts-wrapper .ts-control > input { @apply m-0 min-w-16 flex-1 border-0 bg-transparent p-0 text-sm text-gray-800 shadow-none outline-hidden placeholder:text-gray-400 dark:text-white/90; }
  .ts-wrapper.multi .ts-control > .item { @apply adm-badge adm-badge-primary gap-1 pr-1; }         /* chips = TailAdmin multiselect chips (form-elements.html:416-580) */
  .ts-wrapper.plugin-remove_button .item .remove { @apply ml-0.5 rounded-full px-1 text-current/70 no-underline hover:bg-brand-500/15 hover:text-current; }
  .ts-wrapper.plugin-clear_button .clear-button { @apply absolute right-9 top-1/2 -translate-y-1/2 cursor-pointer text-gray-400 hover:text-gray-700; }
  .ts-dropdown { @apply absolute left-0 top-full z-popover mt-1 w-full rounded-panel border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark; }   /* z: popover, above header & modal (§1.3) */
  .ts-dropdown .ts-dropdown-content { @apply custom-scrollbar max-h-64 overflow-y-auto; }
  .ts-dropdown .option, .ts-dropdown .create, .ts-dropdown .no-results { @apply adm-dropdown-item cursor-pointer; }
  .ts-dropdown .option.active, .ts-dropdown .create.active { @apply adm-dropdown-item-active; }
  .ts-dropdown .option[aria-disabled="true"] { @apply cursor-not-allowed opacity-50; }
  .ts-dropdown .optgroup-header { @apply adm-dropdown-header; }
  .ts-dropdown .highlight { @apply rounded bg-warning-100 dark:bg-warning-500/30; }
  .ts-dropdown.plugin-dropdown_input .dropdown-input-wrap { @apply mb-2; }
  .ts-dropdown.plugin-dropdown_input .dropdown-input { @apply adm-input h-9; }
  .ts-wrapper.plugin-drag_drop .ts-control > .item.ui-sortable-helper, .ts-wrapper.plugin-drag_drop .sortable-ghost { @apply adm-sortable-ghost; }
  .ts-wrapper.plugin-dropdown_header .ts-dropdown-header { @apply border-b border-gray-100 px-3 py-2 text-xs dark:border-gray-800; }
  /* inside modals: dropdownParent is document.body (edit_many_script sets dropdownParent:'.modal' today) */
  .ts-dropdown.adm-in-modal { @apply z-popover; }
}
```

### 6.2 flatpickr (`vendor/flatpickr.css`)

Import flatpickr's own `flatpickr.css` (MIT, ≈ 8 KB) into `app.css` via `@import "flatpickr/dist/flatpickr.css" layer(components);` then TailAdmin's theme **verbatim** from `T/src/css/style.css:407-535` (`.flatpickr-wrapper`, `.flatpickr-calendar` dark/rounded/padding, `.flatpickr-months … svg`, `.flatpickr-calendar.arrowTop:before/after`, `.flatpickr-current-month`, `.flatpickr-prev/next-month`, `.flatpickr-weekdays`, `.flatpickr-weekday`, `.flatpickr-day` incl. `.nextMonthDay/.prevMonthDay`, `.inRange` box-shadows `#f9fafb`/`shadow-datepicker`, `.selected/.startRange/.endRange` `#465fff` set, `.selected.startRange + .endRange` shadow, `.flatpickr-calendar.static`, `@media (max-width:525px)` margin) — one edit: `background: #465fff` (`:507`) becomes `background: var(--color-brand-500)` and the `#465fff` in `:513` likewise, so skins re-theme the picker; plus `.flatpickr-calendar { z-index: var(--z-index-popover) !important }` (flatpickr's default is 99999) and `.flatpickr-calendar.open { @apply shadow-theme-lg }`. The `.input-date-icon::-webkit-*` rule (`style.css:671-675`) is kept. Whether flatpickr replaces Tempus Dominus is G5's decision; the theme is ready either way.

### 6.3 SortableJS (`vendor/sortable.css`)

SortableJS default class names: `sortable-ghost` (ghostClass), `sortable-chosen`, `sortable-drag`, `sortable-fallback`. Adminata initialises with `ghostClass: 'sortable-ghost'` (defaults; no custom names so user-initialised Sortable instances match) and styles:

```css
@layer components {
  .sonata-bc .sortable-ghost  { @apply adm-sortable-ghost; }         /* style.css:744-751 .task.is-dragging */
  .sonata-bc .sortable-chosen { @apply shadow-theme-md; }
  .sonata-bc .sortable-drag   { @apply cursor-grabbing opacity-90; }
  .sonata-bc .sonata-ba-sortable-handler { @apply adm-sortable-handle; }    /* hook class, edit_one_to_many_sortable_script_*.html.twig */
}
```

### 6.4 Font Awesome 6 Free + v4 shims (`fontawesome.css`, separate published file)

Sonata 4.43 ships FA 5.15 `all.css` + `v4-shims.css` and every font format (`S/assets/scss/app.scss:15-16`; `S/src/Resources/public/fonts/fa-*.{eot,ttf,woff,woff2}` = 1.1 MB). The real app already loads FA 6.7.2 from cdnjs on top (`APP/…/standard_layout_override.html.twig:6-8`) and uses FA6-only names (`fa-gauge-high`, `fa-up-right-from-square`, `fa-pen`) plus FA4 names through shims (`fa fa-lock`, `fa fa-sign-out`, `APP/templates/security/user_block.html.twig:18,23`). Adminata ships **FA 6 Free** (`@fortawesome/fontawesome-free` 6.x, CC-BY-4.0 icons / SIL OFL fonts / MIT CSS — keep the attribution file) as `bundles/sonataadmin/fontawesome.css` = `css/all.css` + `css/v4-shims.css` + `css/v4-font-face.css`, rewritten to **woff2 only**:

| Font file (FA 6.7) | Purpose | Approx. size (verify) |
|---|---|---|
| `fonts/fa-solid-900.woff2` | `fas`/`fa-solid` (1,400+ icons) | ~155 KB |
| `fonts/fa-regular-400.woff2` | `far` | ~25 KB |
| `fonts/fa-brands-400.woff2` | `fab` | ~115 KB |
| `fonts/fa-v4compatibility.woff2` | glyphs used by `v4-shims` (`fa-sign-out`, …) | ~5 KB |

Build step (Encore/Vite): copy the four woff2 files, post-process `all.css` to drop `url(...ttf)` sources (FA6 CSS lists `woff2, ttf`). `parse_icon`'s contract (`IconRuntime.php:26-35`, tested `S/tests/Twig/IconRuntimeTest.php:36-42`) is untouched: `fa`, `fas`, `far`, `fab`, `fal`, `fad` prefixes render as `<i class="…">`; `fal`/`fad` are Pro styles and render as empty boxes today too. Removable via `remove_stylesheets: [bundles/sonataadmin/fontawesome.css]` for apps that load FA themselves (the real app would drop its cdnjs `<link>` instead).

### 6.5 Fonts (`fonts.css`)

`@fontsource-variable/outfit` (SIL OFL 1.1): `outfit-latin-wght-normal.woff2` (+ `latin-ext`), `font-display: swap`, `@font-face { font-family: "Outfit Variable"; font-weight: 100 900; }`. ≈ 40 KB per subset (verify). No Google Fonts request (replaces `style.css:1-2`).

### 6.6 Size budget and CI checks

| File | Sonata 4.43 today | adminata target (min, uncompressed / br) |
|---|---|---|
| `app.css` | 345 KB (`S/src/Resources/public/app.css`) | ≤ 140 KB / ≤ 22 KB — Tailwind output for 131 templates + `adm-*` + vendor themes + safelist |
| `compat-bootstrap3.css` | (inside app.css) | ≤ 60 KB / ≤ 10 KB incl. glyphicon map |
| `fontawesome.css` | (inside app.css) | ≤ 130 KB / ≤ 20 KB (FA `all.css` + shims) |
| fonts | 2.1 MB (`fonts/`, 4 formats × FA + 42 Source Sans Pro files) | ≤ 400 KB (4 FA woff2 + 2 Outfit woff2 + glyphicons woff2) |
| `admin-lte-skins/*.css` | 56 KB (12 × AdminLTE skins) | 12 × < 1 KB |

CI job `css-contract` (GitHub Actions, alongside the fork's `lint.yaml`/`qa.yaml`, `MDB/.github/workflows/`), script `bin/check-css-contract.mjs` (Node 24, `postcss` + `postcss-selector-parser`):

1. Parse `src/Resources/public/app.css` and `compat-bootstrap3.css` into a selector set.
2. Assert every entry of `assets/css/contract.json` is present. `contract.json` is generated by `bin/build-css-safelist.mjs` from (a) every `@utility adm-*`/`menu-*` name in `assets/css/components/**`, (b) every `.sonata-bc .<class>` selector in `assets/css/compat/**` (the §3 inventory), (c) the hook-class list from `R/packaging.md` §3.5 that carries a style (`sonata-ba-list-row-selected`, `sonata-link-identifier`, `sonata-readmore-content`, `stuck`, `required`, …), (d) the safelisted grid utilities (`md\:col-span-6` etc. — escape colons), (e) `.dark` variant sanity: at least one selector matching `:where(.dark, .dark *)`.
3. Assert absence: `.collapse{visibility:collapse}`, `.container{`, any `--z-index-99999`, any `url(https://fonts.googleapis.com`, any `.ttf)`/`.eot)` reference.
4. Assert size budget per file (fail above the table).
5. **Dead-class lint**: every literal token inside `class="…"` in `src/Resources/views/**` (Twig expressions stripped) must appear as a selector in `app.css` ∪ `compat-bootstrap3.css` ∪ an allowlist of pure hook classes (`sonata-*`, `field-*`, `mosaic-*`, `read-more-*`, `js-*`, `changer-tab`, `persist-preview`, `has-errors`, …). Catches typos and un-safelisted utilities before a release.
6. Snapshot: `contract.json` is committed; the job fails when generation changes it (forces a deliberate review of API additions/removals, mirroring the fork's `composer-normalize --dry-run` style gates, `MDB/Makefile:12-14`).

---

## 7. Per-app recipe: compiling Tailwind yourself

Needed when an app wants arbitrary utilities in its own templates/`row_attr`/`header_class`, its own brand palette at build time, or one CSS bundle for Sonata + non-Sonata pages. `assets/css/adminata.css` and everything it imports are plain Tailwind v4 CSS (no `@plugin`, no PostCSS-only syntax), so all three toolchains work, including the standalone CLI.

App entry `assets/styles/admin.css`:

```css
@import "tailwindcss";
@import "../../vendor/idct/adminata/assets/css/adminata.css";                 /* tokens, base, .adm-*, vendor themes, safelist */
@import "../../vendor/idct/adminata/assets/css/compat/index.css";             /* optional: Bootstrap-3 compat */
@import "../../vendor/idct/adminata/assets/css/skins/skin-green.css";         /* optional: or override --color-brand-* yourself */

/* sources to scan (paths relative to THIS file) */
@source "../../vendor/idct/adminata/src/Resources/views";
@source "../../vendor/idct/adminata/assets/js";
@source "../../templates";                                  /* incl. templates/bundles/SonataAdminBundle/** */
@source "../../src/Admin";                                  /* 'class' => 'md:col-span-6', row_attr, header_class */
@source "../../vendor/sonata-project/form-extensions/src/Bridge/Symfony/Resources/views";
@source "../../vendor/idct/sonata-admin-mongodb-bundle/src/Resources/views";  /* persistence bundle form themes */
@source not inline("collapse");                             /* §3.9 */

:root { --color-brand-500: #0ea5e9; --color-brand-600: #0284c7; /* … */ }   /* build- or runtime re-theme, same variables */
```

`config/packages/sonata_admin.yaml`:

```yaml
sonata_admin:
    assets:
        remove_stylesheets:
            - bundles/sonataadmin/app.css
            - bundles/sonataadmin/compat-bootstrap3.css      # if the compat import above is used, or if you dropped it
            # keep bundles/sonataadmin/fontawesome.css unless you bundle FA yourself
        extra_stylesheets:
            - { path: 'build/admin.css', package_name: null }   # Encore/Vite output (package_name null allowed, Configuration.php:816-820)
            # AssetMapper: - { path: 'styles/admin.css', package_name: null } (logical path resolved by importmap/asset())
```

The asset list merge/removal algorithm is untouched (`SonataAdminExtension.php:236-285`), so `remove_stylesheets` matches by exact path string.

| Toolchain | Setup | Notes |
|---|---|---|
| **AssetMapper + `symfonycasts/tailwind-bundle`** | `composer require symfonycasts/tailwind-bundle`; `symfonycasts_tailwind: { input_css: ['assets/styles/admin.css'], binary_version: 'v4.1.x' }`; `bin/console tailwind:build --minify` (or `--watch`); serve via `asset('styles/admin.css')` | Standalone CLI: no `node_modules`, no JS plugins — fine because adminata's CSS uses none (the reason `@tailwindcss/forms` is rejected). Verify the bundle version that defaults to a v4 binary. `@import` of `vendor/…` paths works relative to the input file. |
| **Webpack Encore** (the real app's toolchain, `APP/webpack.config.js`) | `npm i -D tailwindcss @tailwindcss/postcss postcss-loader`; `.enablePostCssLoader()`; `postcss.config.js` → `{ plugins: { '@tailwindcss/postcss': {} } }`; `import './styles/admin.css'` from `assets/app.js` | Same plugin the TailAdmin template uses (`T/package.json`, `T/postcss.config.js`). `.enableSassLoader()` may stay for the app's SCSS; do not run Tailwind through Sass. |
| **Vite (`pentatrion/vite-bundle`)** | `npm i -D tailwindcss @tailwindcss/vite`; `vite.config.js` `plugins: [tailwindcss(), symfonyPlugin()]`; `import './styles/admin.css'` | fastest; `@source` globs unchanged. |

Users writing component CSS in a separate file use `@reference "./admin.css";` at its top to `@apply adm-*` (T8). Apps that keep the prebuilt `app.css` and only need a few extra utilities can instead ship a tiny second stylesheet built from `@import "tailwindcss/utilities.css" layer(utilities); @reference ".../adminata.css"; @source "../../templates";` — the layer names coincide, so utilities merge into the same cascade layer.

---

## 8. Drop-in compatibility flags introduced or resolved by this spec

| # | Risk | Severity | Mitigation in this spec |
|---|---|---|---|
| F1 | An `@utility adm-*` missing from the safelist is silently absent from the prebuilt `app.css` (T1) — user templates using it render unstyled. | high | Generated `safelist.css` + `contract.json` + CI assertion (§6.6). |
| F2 | The Tailwind semantics in §0 (T1–T11) are stated from knowledge, not tested locally. | high | First implementation task: build a 20-line fixture with the pinned Tailwind version and assert each item; pin `tailwindcss` exactly in `package.json`. |
| F3 | `body.fixed` in user `body_attributes` overrides → `position:fixed` body. | medium | `@layer sonata-overrides` rule (§3.9); migration audit warns. |
| F4 | `.collapse` (ORM audit block, block-bundle) hidden by Tailwind's `visibility:collapse` when an app build scans `vendor/`. | medium | `@source not inline("collapse")` in both bundle and recipe; override rule. |
| F5 | `remove_stylesheets` targets change: apps that removed `bundles/sonataadmin/app.css` to bring their own CSS now also get `compat-bootstrap3.css` and `fontawesome.css` unless removed. | medium | Documented in UPGRADE; harmless visually (compat is namespaced under `.sonata-bc`, and FA duplicates only cost bytes). |
| F6 | `admin_lte_skin_class`/`skin-*` semantics: skins become brand palettes; user CSS keyed on `.skin-black .navbar …` no longer matches AdminLTE structure. | low | Class kept on `<body>`; token files; documented. |
| F7 | Sort-arrow hover inversion and `.sonata-ba-action.btn:not(:hover)` transparent buttons are visual behaviours users may have styled. | low | Hooks kept; documented. |
| F8 | `.label` compat selector could hit a `<label class="label">` in user forms. | low | Selector `.sonata-bc .label:not(label)`. |
| F9 | Apps defining utility-named classes in their own CSS (`APP` `.mt-10{margin-top:10px}`) override Tailwind's meaning page-wide. | low | Audit tool lists app selectors colliding with Tailwind utility names. |
| F10 | FA6 replaces FA5: a handful of FA5 names were renamed in FA6 (`fa-external-link-square` → `fa-square-up-right`, etc.), covered by FA6's own shims only for FA4 names. | low | Keep `v4-shims`; add a short `fa5-shims.css` section for the ~20 FA5→FA6 renames Sonata/its docs use (grep list in `R/php-compat.md` §3). |
| F11 | Two dark selectors in the wild (`:is(.dark *)` in copied TailAdmin partials vs adminata's `:where(.dark, .dark *)`) — copied partials are compiled by adminata's variant, so no divergence; a user's own Tailwind build must use the same `@custom-variant` (it does, via `theme.css` import). | none | — |

---

## 9. Open questions for the project owner

1. Ship Outfit (TailAdmin look, +80 KB fonts) or default to the system stack (`--font-sans: ui-sans-serif, system-ui`) with Outfit opt-in via a config key?
2. Is the glyphicons font/map worth carrying in `compat-bootstrap3.css` (≈ 30 KB) for the login-recipe users, or should the login docs be rewritten and glyphicons dropped?
3. Keep `bundles/sonataform/app.css` (Tempus Dominus, 48 KB) in the default stylesheet list until G5 decides, or ship the flatpickr datepicker in 1.0 and drop it now?
4. `html[data-density="compact"]` — expose as a config key (`sonata_admin.options.density`) or only as a documented user override?
5. Should the compat layer's `.sonata-bc` scoping also cover `ajax_layout.html.twig` fragments loaded into non-Sonata pages (no `sonata-bc` ancestor)? Proposed: the ajax layout wraps its output in `<div class="sonata-bc">`.
