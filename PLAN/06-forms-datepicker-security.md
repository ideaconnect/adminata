# 06 — Form theme, collections, autocomplete, native date inputs, security

Sources: `R/forms-edit.md` (block-by-block table), `R/critique-round-1.md` (V2–V4, V14, V17),
`R/gap-real-app-audit.md` §2f (stateless CSRF), appendix C. Decisions F1, F2, J5, J7, J9 apply.

## 1. Form theme base and block coverage

Facts: `Form/form_admin_fields.html.twig` and `Form/filter_admin_fields.html.twig` both
`{% extends 'form_div_layout.html.twig' %}` and implement the Bootstrap markup themselves. Symfony
twig-bridge ships `tailwind_2_layout.html.twig` (threads `row_class`, `widget_class`,
`widget_disabled_class`, `widget_errors_class`, `label_class`, `help_class`, `error_item_class`).
Per-view themes set through `FormRenderer::setTheme()` keep the global themes as the lowest layer;
the ORM/Mongo themes are merged after adminata's; the app adds three per-admin themes through
`setFormTheme` (`field/operator_support.html.twig`, `field/json_editor.html.twig`,
`field/chunked_file_asset.html.twig`).

Decisions:

- Keep `{% extends 'form_div_layout.html.twig' %}`; do not `use` `tailwind_2_layout` (its
  `widget_attributes` appends v2-era classes); adopt its **variable convention** so overrides can
  pass `row_class`, `widget_class`, `label_class`, `help_class`, `error_item_class`.
- The 30 blocks Sonata overrides are rewritten keeping the hooks of document 02 §8. Row:
  `<div id="sonata-ba-field-container-{id}" class="sonata-ba-field sonata-ba-field-{edit}-{inline} {row_class} …">`;
  widgets get `adm-input`, `adm-select` (native, styled chevron), `adm-textarea`, `adm-checkbox`,
  `adm-radio` (`T/src/form-elements.html`, `TR/components/form/*`); help via `sonata-ba-field-help`
  (`help_html` keeps `|raw`); errors via `sonata-ba-field-error-messages`.
- `widget_attributes` only **appends** classes; every attribute passed through `attr` (the app's
  `data-controller`, `data-section`, `data-trigger`, ux-autocomplete's `data-controller=
  "symfony--ux-autocomplete--autocomplete"`) is emitted untouched.
- The 36 `form_div_layout` blocks Sonata does not override: `form_widget_simple`-based widgets
  inherit `adm-input`; `file_widget` → TailAdmin file-input recipe; `button/submit/reset_widget` →
  `adm-btn`; structural blocks inherit unchanged.
- Horizontal mode (`form_type: horizontal`): label `col-span-3`, field `col-span-9` in a
  `grid grid-cols-12`; standard mode stacks.
- The theme stands alone on non-admin pages: recomaty-panel registers it globally in
  `twig.form_themes` so its plain forms on admin pages (`transaction/create_manual`,
  `promo_code/create`, password pages) get the same look; no block may rely on `sonata_admin`
  view variables without a guard (already the case upstream).
- Filter theme: operator selects native; value inputs TailAdmin recipes; date filters stay Symfony
  HTML5 `type=date`; additive `sonata_type_date_range_widget` renders start/end side by side;
  ux-autocomplete `<select>`s inside the filter panel render through the global ux theme.
- Sonata `BooleanType` (form-extensions, yes/no select) and `CheckboxType` render native controls;
  iCheck is gone.

## 2. Collections (Symfony `CollectionType` through `sonata_type_native_collection_widget`)

The app has 14 `CollectionType` usages (`allow_add`/`allow_delete`, `by_reference: false`), with
entry types ranging from `EntityType` and `EmailType` to sub-forms carrying date/time fields and
the app's own Stimulus controllers. Nothing changes in the mechanism:

- `sonata_type_native_collection_widget` renders `data-prototype` (escaped), `data-prototype-name`,
  the `sonata-collection` controller with `numItems`, rows as `sonata-collection-row` cards with an
  `adm-btn-icon` remove button, and an add button after the rows.
- `sonata-collection` (unchanged) clones the prototype, replaces `__name__`, appends the row and
  dispatches `sonata-collection-item-added`; the app's controllers inside the new row connect
  through its own application's MutationObserver (R6).
- No sortable collections in 1.0 (the app has none).

## 3. Autocomplete (`sonata_type_model_autocomplete`) — hand-written combobox

Used by the app once as a form field type inside a `ModelFilter` (`RecomatAdmin`) and once through
the ORM `ModelAutocompleteFilter`; the app's other autocompletes are ux-autocomplete fields and are
not adminata's concern.

- **Markup** (template `Form/Type/sonata_type_model_autocomplete.html.twig`):
  wrapper `data-controller="sonata-autocomplete"` with values `url`, `reqParams` (JSON from the
  `…_ajax_request_parameters` block, includes `_sonata_admin`, `uniqid`, `field`, `_context`),
  `minLength`, `perPage`, `delay`, `multiple`, `placeholder`, `safeLabel`; an
  `<input type="text" role="combobox" aria-autocomplete="list" aria-expanded aria-controls="{id}_listbox"
  id="{id}_autocomplete_input">`; a `<ul role="listbox" id="{id}_listbox" hidden>`; for `multiple`
  a chip list with remove buttons; `<div id="{id}_hidden_inputs_wrap">` with
  `<input type="hidden" name="{full_name}[]">` per selected id (single: `name="{full_name}"`).
  The blocks `…_dropdown_item_format` and `…_selection_format` are rendered as `<template>`
  elements the controller clones; `…_select2_options_js` is removed.
- **Behaviour**: debounce by `delay`; below `minLength` show "type N characters" (the server's
  `403` is handled the same way); `GET url?q=…&_page=…&_per_page=…` + `reqParams`; response
  `{status, more, items:[{id,label}]}`; "load more" item while `more`; keyboard Down/Up/Home/End/
  Enter/Escape, Backspace removes the last chip when the input is empty; `aria-activedescendant`;
  click outside closes; `safe_label` → `innerHTML`, otherwise `textContent`; events
  `sonata-autocomplete:selected/removed/cleared`; the hidden inputs are the only submitted state.
- **PHP options mapping**: `minimum_input_length`, `items_per_page`, `delay`, `placeholder`,
  `cache`, `container_css_class`, `dropdown_css_class`, `dropdown_item_css_class` used;
  `width`, `dropdown_auto_width` ignored.
- **Filter context**: the filter form's `prepareSubmit` sees the hidden inputs; an empty value is
  not submitted; `_context=filter` is passed.
- Estimated size: about 350 lines plus Vitest coverage; accessibility verified with axe and a
  keyboard-only Panther scenario. The app may instead swap its one field for the
  `CityAutocompleteField` it already owns; not required.

## 4. Date and time fields — native HTML5 inputs

Facts (form-extensions 2.7.0): `BasePickerType` sets `widget: single_text`, `html5: false`, an ICU
`format`, and exposes `datepicker_options` (`display.components.{calendar,clock,seconds}`,
`restrictions`, `localization`) plus `localization.format` in Tempus Dominus tokens; block prefix
`sonata_type_datetime_picker`; `DateRangePickerType` is `DateRangeType` with `field_type =
DatePickerType`. Sonata's own date filters use Symfony `DateType`/`DateTimeType` with `single_text`
and HTML5 on, so they are native already.

Plan:

- adminata's `form_admin_fields.html.twig` overrides `sonata_type_datetime_picker_widget` and
  `sonata_type_datetime_picker_widget_html`: `type="time"` when `calendar` is false, `type="date"`
  when `clock` is false, otherwise `type="datetime-local"`; `step="1"` when `seconds`; `min`/`max`
  from `restrictions.minDate/maxDate` when ISO-formatted; `value` passed verbatim; class
  `adm-input`; `color-scheme` from `base.css` makes the native pickers dark in dark mode.
- Requirement on the app: each picker field's `format` must be HTML5-compatible —
  `yyyy-MM-dd` (date), `yyyy-MM-dd'T'HH:mm` (datetime, `…:ss` with seconds), `HH:mm` (time).
  recomaty-panel's twelve usages (appendix C §2) currently use `dd-MM-yyyy HH:mm`, `dd.MM.yyyy`,
  `dd.MM.yyyy H:i:s`, `H:mm`, `yyyy-MM-dd`; each becomes one option change. The alternative is to
  switch those fields to Symfony's core `DateType`/`DateTimeType`/`TimeType` with `widget:
  single_text` (HTML5 by default), which needs no adminata block at all.
- `bundles/sonataform/app.{js,css}` are dropped from the default asset lists; the app replaces
  `@SonataForm/Form/datepicker.html.twig` in `twig.form_themes` with adminata's form theme.
- No picker library in 1.0. If a popup calendar is wanted later, `sonata-datepicker` wraps
  `vanilla-calendar-pro` (3.3.2, released 2026-09-04) over the same native inputs.
- Tests: PHPUnit widget tests for the two blocks; acceptance F3/F4 round-trip `07:30` and
  `2026-09-04T10:15`.

## 5. Security review

| Topic | Fact | Plan |
|---|---|---|
| Session CSRF | `sonata.delete` and `sonata.batch` intentions are session tokens (`CRUDController.php:138,203,263,393,495`) | unchanged; delete/batch stay full-page POSTs |
| Stateless CSRF | The app declares `submit`, `authenticate`, `logout` as stateless token ids; Symfony's `csrf_protection_controller.js` mints the token in a capture-phase `submit` listener; once a double-submit succeeded, later requests without the cookie/token pair are rejected | 1.0 has no fetch-based POST (batch, filters and edit forms are plain submits; autocomplete is GET). Post-1.0 `sonata-association` submits via `form.requestSubmit()`, then intercepts with a capturing listener registered later and calls `fetch` with `credentials: 'same-origin'` |
| Inline edit | `SetObjectFieldValueAction` relies solely on `X-Requested-With` | post-1.0; keep parity, send the header |
| XSS surfaces | 52 `\|raw` sites, `safe_label`, `help_html`, `objectName` in JSON | inventory each during the rewrite; JSON travels in `data-*` attributes parsed with `JSON.parse`; labels inserted with `textContent` unless `safe_label` |
| CSP | no inline scripts except the theme pre-paint under `sonata_script_attributes`; no `onclick` | nonce-friendly by construction |
| Cookies | `sonata_sidebar_hide` (JS), `sonata_theme` (JS) | `SameSite=Lax; path=/; max-age=31536000`, `Secure` on https |
| Links | `target="new"` in list url templates | `target="_blank" rel="noopener"` |
| Dialogs | `<dialog>` top layer | app dialogs opt in with `sonata-modal` |

## 6. i18n and RTL

- `<html lang dir>` with a small RTL map (`ar`, `fa`, `he`, `ur`).
- Logical utilities (`ps-*`, `pe-*`, `ms-*`, `me-*`, `start-*`, `end-*`, `text-start`) in adminata
  templates so `dir="rtl"` works without a second build.
- New UI strings get ids in the `SonataAdminBundle` domain and flow to JS through
  `<meta name="sonata-translations">`.
