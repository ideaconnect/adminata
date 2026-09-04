# 00 — Executive summary

## Goal

Ship `idct/adminata` 1.0.0: the SonataAdminBundle 4.43.0 PHP layer, unchanged in API, with every
one of its 131 Twig templates and its whole asset pipeline rewritten for Tailwind CSS v4 in the
TailAdmin visual language (light and dark mode, collapsible sidebar, cards, badges, modern forms).
An application that runs Sonata Admin 4.43 today should switch with
`composer require idct/adminata`, a cache clear and `assets:install`, and get a working admin.

## What "drop-in" means here

Everything user code, persistence bundles and configuration can touch stays identical:

- Composer identity via `replace: {"sonata-project/admin-bundle": "4.43.0"}` so
  `doctrine-orm-admin-bundle` (`^4.39`) and `idct/sonata-admin-mongodb-bundle` (`^4.39`) resolve.
- PHP namespace `Sonata\AdminBundle`, bundle class `SonataAdminBundle`, Twig namespace
  `@SonataAdmin`, override directory `templates/bundles/SonataAdminBundle/`, public assets under
  `bundles/sonataadmin/`, config root `sonata_admin` with every node, all 121 service ids, the
  8 `sonata_admin_*` routes and their JSON contracts, the `SonataAdminBundle` translation domain.
- All 131 template file names, the 39 template registry keys, all 138 Twig block names, the
  `sonata-ba-*` CSS hooks, the `objectId` cell attribute, the 9 existing Stimulus controller
  identifiers, the `window.Admin` facade (16 members), `window.sonataApplication`, and the
  documented `data-sonata-*` attributes.

What changes: Bootstrap 3, AdminLTE, jQuery plugins (select2, iCheck, x-editable, jQuery UI,
jquery-form), masonry and slimscroll are gone. A Bootstrap-3 compatibility stylesheet and a small
`data-toggle`/`data-dismiss` delegate keep user-overridden templates and sibling bundles rendering
acceptably; jQuery itself is still shipped as a separate, removable, deprecated file for 1.x.

## Approach in one paragraph

Fork the `4.43.0` tag (the 5.x branch is byte-identical in templates and differs in three PHP
files). Keep the PHP layer and sync it from upstream monthly with a filtered cherry-pick script.
Rewrite templates group by group (layout, forms, list, show/misc) against a frozen contract
enforced by tests. Replace the JS stack with Stimulus-only controllers (no Alpine: CSP and
AJAX-fragment re-initialisation), Tom Select, flatpickr, SortableJS and native `<dialog>`. Build
with Vite + `@tailwindcss/vite`, commit the prebuilt `app.css`/`app.js` under
`src/Resources/public` exactly as Sonata does, and add a documented recipe for apps that compile
Tailwind themselves. Ship Font Awesome 6 Free with v4 shims because `parse_icon` and every
`icon:` config string depend on `fa*` classes. Stamp dark mode server-side from a cookie.

## Key numbers

| Item | Value |
|---|---|
| Sonata 4.43.0 templates / Twig lines | 131 files / 7,238 lines (CRUD 99, Button 6, Block 5, Core 5, Pager 5, Form 3, Breadcrumb 2, Helper 2, Menu 1, layouts 3) |
| Twig block names to preserve | 138 unique (standard_layout defines 33 and captures 12 child blocks) |
| PHP files / LOC in `src/` | 242 / 27,892 (unchanged in API) |
| PHP strings coupled to Bootstrap/FA markup | about 20 in 12 files (all kept, translated in Twig/CSS) |
| Sonata JS + SCSS | 2,877 lines; 9 Stimulus controllers; `window.Admin` with 16 members |
| Prebuilt assets today | `app.css` 345 KB, `app.js` 485 KB, fonts 2.1 MB, public dir 5.1 MB |
| adminata targets | `app.css` ≤ 140 KB, `compat-bootstrap3.css` ≤ 60 KB, `fontawesome.css` ≤ 130 KB, fonts ≤ 400 KB, `app.js` ≤ 220 KB |
| New Stimulus controllers | 18 (plus 9 kept verbatim, one with a one-line change) |
| Translations | 34 XLIFF locales inherited unchanged |

## Phases (indicative, one senior engineer)

| Phase | Weeks | Outcome |
|---|---|---|
| 0 Bootstrap | 1 | Repo from tag 4.43.0 with full history, `replace`, floors, Rector/CS/PHPStan pass, inherited tests green, CI skeleton |
| 1 Foundations | 2 | Vite + Tailwind v4 build, tokens, dark mode, `.adm-*` API, compat layer, FA6, controller registry, `window.Admin` facade, contract tests |
| 2 Layout and navigation | 2 | Layouts, sidebar menu, header, breadcrumbs, dashboard, search, blocks, flash messages |
| 3 Forms and filters | 3 | Form theme, filter theme, edit chrome, association modals, autocomplete, collections, datepicker |
| 4 CRUD pages | 3 | List (batch, pager, mosaic, inline edit), show, history, ACL, delete, preview, buttons |
| 5 Ecosystem and migration tooling | 2 | Compat matrix, nightly compat jobs, `adminata:audit-overrides`, `UPGRADE-1.0.md`, acceptance run on `APP/` |
| 6 Release | 1 | Docs, RTD, Packagist, `v1.0.0` |

About 14 weeks. The estimate assumes the owner decisions in document 11 are taken before phase 1.

## Headline risks

1. User templates under `templates/bundles/SonataAdminBundle/` and sibling bundles (User, Media,
   Page, ORM audit block, form-extensions datepicker) render Bootstrap-3 markup inside a Tailwind
   page. Mitigation: compat stylesheet on by default, block-name parity, the audit command.
2. Symfony Flex runs Sonata's recipe `unconfigure` when the real package is uninstalled and
   deletes `config/packages/sonata_admin.yaml` without a hash check. Mitigation: documented
   `composer require idct/adminata --no-plugins --no-scripts` migration path.
3. Tailwind emits only the classes it scans: utilities used only in user templates are absent
   from the prebuilt CSS. Mitigation: `.adm-*` semantic API, compat layer, safelist, per-app
   compile recipe.
4. `replace: 4.43.0` goes stale when a persistence bundle raises its floor. Mitigation: weekly
   upstream watch, sync policy, documented root-level `replace` escape hatch.
5. User JavaScript depends on jQuery plugins Sonata bundled. Mitigation: jQuery global kept,
   `window.Admin` facade, native bubbling events, UPGRADE breakage table.

## Owner decisions needed before phase 1

1. PHP/Symfony floors: `^8.4` and `^7.4 || ^8.0` (fork convention) versus Sonata's wider floors.
2. Confirm packaging Option B (namespace and bundle name kept, `replace` with exact version).
3. Font: self-hosted Outfit versus the system stack.
4. Tier-1 ecosystem scope at 1.0: ORM + MongoDB fork only, or also SonataUserBundle login.
5. AssetMapper first-class support at 1.0 (ESM build and docs) or 1.1.
6. Fix upstream's shift-range selection bug and move the mosaic checkbox out of the tile link.
7. `options.density` as a config key or documentation-only override.
8. Whether the real app `APP/` acceptance suite lives in that app or in adminata's CI.

## What the research did not verify

- Twelve Tailwind v4 semantics the CSS design relies on (`@utility` emission, `@layer`
  behaviour, `@source inline()` brace ranges, `@theme static`, `@reference`) were stated from
  knowledge; no Tailwind v4 install exists locally. First task of phase 1 is a fixture that
  proves each.
- The composer `replace` behaviour was verified empirically (seven scenarios), but the three
  adversarial reviews (composer/ecosystem, maintenance, user migration) did not run.
- Online-only checks: Sonata's Flex recipe file list, SonataUserBundle template names, FA 6.7
  font sizes, `symfonycasts/tailwind-bundle` v4 binary default, `runroom/sortable-behavior-bundle`.
