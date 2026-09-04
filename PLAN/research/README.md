# Research reports

Raw research produced on 2026-09-04 by parallel readers over Sonata Admin 4.43.0, the 5.x branch,
TailAdmin v2.3.0 (HTML/React/Next), the ORM admin bundle, form-extensions, block-bundle,
twig-extensions, the `idct/sonata-admin-mongodb-bundle` conventions and the production app
`recomaty-panel`. Path aliases are defined at the top of each report. `critique-round-1.md`
lists verified claims (V1–V22), corrections, and the 25 resolved contradictions (C1–C25) that
the plan documents rely on. `_summaries.md` is the structured index (summary, key findings,
recommendations, risks, open questions) of every report.

Not produced (session limit): critique round 2, gap reports form-theme-base, form-extensions,
security-csrf-csp, siblings-login, i18n-rtl, and the three adversarial packaging reviews. Their
subjects are covered in the plan documents 06, 07 and 11.

| File | Dimension |
|---|---|
| layout-nav.md | Layouts, sidebar menu, header, breadcrumbs, dashboard, blocks, flash messages, config options |
| list-datagrid.md | List page, fields, filters, pager, batch, inline edit, mosaic, tree |
| forms-edit.md | Form theme, edit chrome, association modals, autocomplete, collections, date pickers |
| show-misc.md | Show, history, ACL, delete, preview, action buttons, display helpers |
| js-assets.md | JS/CSS inventory, dependency dispositions, DOM contract, Tailwind build strategy, AssetMapper |
| php-compat.md | PHP coupling strings, the compatibility contract, Bootstrap shim analysis, docs impact, fork base |
| packaging.md | Options analysis, Composer/Flex verification, skeleton, gates, CI, tests, upstream sync, licensing |
| tailadmin-catalog.md | TailAdmin component catalogue, gap analysis, theming, accessibility, licensing |
| critique-round-1.md | Verification log, corrections, contradiction resolutions, gap list |
| gap-css-architecture.md | Definitive Tailwind v4 CSS architecture (file layout, tokens, compat inventory, `.adm-*` API, budgets) |
| gap-js-architecture.md | Definitive JS architecture (controller registry, facade, events, coexistence, build, breakage table) |
| gap-real-app-audit.md | Audit of the production app as the drop-in acceptance case; audit rules; UPGRADE sections |
