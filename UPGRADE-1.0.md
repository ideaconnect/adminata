# Upgrading to adminata 1.0

**Not written yet.** This file becomes the generic upgrade notes (U1–U9) in PROJECT_PLAN task
**P5-12**; the draft is [PLAN/10-migration-guide-outline.md](PLAN/10-migration-guide-outline.md) §3.

The breaking changes it will describe are already fixed by the design:

- Bootstrap and AdminLTE class names are gone from every rewritten template; application overrides
  of Sonata markup have to be ported.
- jQuery, select2, iCheck, x-editable, Tempus Dominus, jquery-form (`ajaxSubmit`) and the
  `window.Admin` facade are gone; application JavaScript that used them has to be rewritten.
- `sonata_admin.options.skin`, `use_select2`, `use_icheck` and `use_bootlint` are removed
  configuration nodes: leaving them in `sonata_admin.yaml` is a container build error.
- Date and time fields are native HTML5 inputs; a custom `format` on
  `sonata_type_date_picker`-family fields is rejected the way Symfony rejects it for
  `DateType` with `html5: true`.
- Group `class` and dashboard block `class` defaults are Tailwind grid classes
  (`col-span-12`, `md:col-span-4`), and `box_class` defaults to an empty string.
