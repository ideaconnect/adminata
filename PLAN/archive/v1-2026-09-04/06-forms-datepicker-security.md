# 06 — Form theme, association flows, autocomplete, date pickers, security

Sources: `R/forms-edit.md` (block-by-block table, modal flow step list), `R/critique-round-1.md`
(V2–V4, V14, V17, gaps G4–G6, G8), `R/gap-real-app-audit.md` §2f (stateless CSRF),
`R/gap-js-architecture.md` §4.4. Two planned gap reports (form-theme base, form-extensions)
did not run; their subjects are settled here with the facts already verified.

## 1. Form theme base and block coverage

Facts: `Form/form_admin_fields.html.twig` and `Form/filter_admin_fields.html.twig` both
`{% extends 'form_div_layout.html.twig' %}` (not a Bootstrap theme) and implement the
Bootstrap markup themselves. Symfony twig-bridge 8.1 ships 13 themes including
`tailwind_2_layout.html.twig` (2.6 KB, `{% use 'form_div_layout' %}`, overrides 9 blocks and
threads `row_class`, `widget_class`, `widget_disabled_class`, `widget_errors_class`,
`label_class`, `help_class`, `error_item_class` with Tailwind-v2 defaults). Sonata's test kernel
registers the admin theme **globally** (`twig.form_themes`), and per-view themes set through
`FormRenderer::setTheme()` keep the global themes as the lowest layer; the ORM/Mongo themes are
merged after adminata's.

Decisions:

- Keep `{% extends 'form_div_layout.html.twig' %}`. Do not `use` `tailwind_2_layout`: its
  `widget_attributes` appends v2-era classes and would fight the `form-control` logic; adopt its
  **variable convention** instead so user overrides can pass `row_class`, `widget_class`,
  `label_class`, `help_class`, `error_item_class` without copying whole blocks.
- The 30 blocks Sonata overrides are rewritten keeping every hook (document 02 §8), with
  `form-group` first in the row class list and `help-block` kept as a dual class.
- The 36 `form_div_layout` blocks Sonata does **not** override get an explicit decision each:
  `form_widget_simple`-based widgets already covered (`email, url, tel, search, password, number,
  integer, color, week, range` inherit `adm-input` through `widget_attributes`); `file_widget`
  → TailAdmin file-input recipe; `button/submit/reset_widget` → `adm-btn`; `dateinterval_widget`,
  `repeated_row`, `form_widget_compound`, `collection_widget`, `hidden_row/widget`,
  `form_start/end`, `form_rest`, `form_rows`, `attributes`, `widget_container_attributes`,
  `choice_widget`, `choice_widget_options`, `button_row`, `button_label`,
  `form_help_content`, `form_label_content`, `form_row_render` → inherit unchanged (they
  produce structure, not Bootstrap classes). `widget_attributes` is overridden once to append
  `adm-input`/`widget_class`.
- Precedence per admin page (lowest → highest): `form_div_layout` → global `twig.form_themes`
  (form-extensions datepicker, ux-autocomplete, app themes) → adminata `form_admin_fields` →
  ORM/Mongo theme (block delegation only) → admin-specific `getFormTheme()`. The theme must also
  stand alone on non-admin pages (test kernel registers it globally; the real app's login and
  password pages use it), so no block may rely on `sonata_admin` view variables without a guard
  (already the case upstream: `sonata_admin.options|default`).
- Horizontal mode keeps both the legacy `col-sm-3/9/offset-3` tokens and the grid utilities.
- Filter theme: operator selects native; value selects `sonata-select` when `use_select2`;
  date filters stay Symfony HTML5 `type=date` (Sonata's filters never used form-extensions);
  additive `sonata_type_date_range_widget` renders start/end side by side.
- ACL matrix checkboxes come from `checkbox_widget` (native, `appearance-none` recipe, `aria-label`).

## 2. Association modal and inline flows (contracts to keep byte-for-byte)

Server side is untouched. The browser side moves from `edit_many_script.html.twig` (22 KB of
per-field jQuery) into `sonata-association` with these exact behaviours:

| Flow | Request | Response handling |
|---|---|---|
| Open add/edit modal | `GET associationadmin.generateUrl('create'\|'edit', link_parameters + {uniqid, subclass})` with `X-Requested-With` → rendered through `ajax_layout` (only the `form` block) | inject into `#field_dialog_{id}` (moved to `document.body` on first open), run `Admin.shared_setup(dialog)`, activate injected `<script>` tags, bind submit |
| Submit modal form | `fetch(form.action, {method: 'POST', body: FormData, headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})` with `_xml_http_request=1` appended | `{result:'ok', objectId, objectName}` → list mode: set `#{id}` hidden input and dispatch `change`; form mode: POST the **outer** form to `sonata_admin_retrieve_form_element` with `_sonata_admin` (root code), `elementId`, `subclass`, `objectId`, `uniqid`, replace `#field_container_{id}`, select the new value, dispatch `sonata-admin-append-form-element` and `sonata-association:created`. `400` `{title, violations[{propertyPath, message}]}` → inject into `[name="{propertyPath}"]` closest `.form-group` as `has-error` + `help-block sonata-ba-field-error-messages`; `406` when Accept lacks JSON (never happens with the header above) |
| List selection | `GET generateUrl('list', {select: true} + link_parameters)`; every `<a>`/`<form>` inside the modal is re-fetched with `_xml_http_request` (sort, pager, filters keep working as plain anchors) | click in `td.sonata-ba-list-field[objectId]` → set `#{id}`, dispatch `change`; `change` → `GET sonata_admin_short_object_information` (`OBJECT_ID` substitution) → `{result:{id,label}}` into `#field_widget_{id}`; toggle `#field_actions_{id}` edit/remove buttons; `Admin.setup_list_modal` semantics (wide size + `sonata-admin-setup-list-modal` event) |
| Remove selected | client only | clear `#{id}`, dispatch `change`, `sonata-association:removed` |
| Inline collection add (`edit_one_script`) | POST the outer form to `sonata_admin_append_form_element` (`AdminHelper::appendFormFieldElement` strips/restores `_delete`) | replace `#field_container_{id}` preserving `input[type=file]` elements by id, set `enctype=multipart/form-data`, dispatch `sonata.add_element` (sortable renumbering) and `sonata-admin-append-form-element` |
| Retrieve (`start_field_retrieve_{id}`) | POST outer form to `sonata_admin_retrieve_form_element` | replace `#field_container_{id}` |

Known UX gap preserved for parity: only form errors with a `ConstraintViolation` cause reach the
`violations` list; CSRF/transformation errors show the generic "Validation Failed" alert.

Cascaded modals (a modal form containing a model-list widget) work through the `<dialog>` top
layer; Tom Select dropdowns inside a dialog keep the default inline `dropdownParent` (R8).

## 3. Autocomplete (`sonata_type_model_autocomplete`)

- Markup kept: `<select id="{id}_autocomplete_input" data-sonata-select2="false">` +
  `#{id}_hidden_inputs_wrap` hidden inputs; blocks `…_widget`, `…_ajax_request_parameters`,
  `…_dropdown_item_format`, `…_selection_format` kept; `…_select2_options_js` kept as a
  deprecated block whose object is merged into Tom Select options (`theme`, `width`,
  `dropdownAutoWidth` ignored).
- Request: `q, _per_page, _page, uniqid, _sonata_admin, field` (+ `_context=filter`); response
  `{status, more, items:[{id,label}]}`; `403 'Too short search string'` below
  `minimum_input_length`. Tom Select `load` + `virtual_scroll`-style pagination (`more`).
- PHP options keep their select2-shaped names (`container_css_class`, `dropdown_css_class`,
  `dropdown_item_css_class`, `dropdown_auto_width`, `width`, `minimum_input_length`,
  `items_per_page`, `delay`, `cache`, `safe_label`) mapped onto Tom Select `wrapperClass`,
  `dropdownClass`, `render` callbacks and `loadThrottle`.
- Created-object auto-select: replace the `$(document).ajaxSuccess` URL sniffing with the
  `sonata-association:created` event (`detail.createUrl`).
- Sortable multiple selection: Tom Select `drag_drop` (SortableJS) rewrites `name[i]` inputs on
  submit like `Admin.setup_sortable_select2` does today.

## 4. Date and time pickers (form-extensions 2.7.0)

Facts: form-extensions renders `DatePickerType`/`DateTimePickerType` (shared block prefix
`sonata_type_datetime_picker`) with Tempus Dominus 6 through a Stimulus controller registered as
`datepicker` on `global.sonataApplication`; its theme is prepended globally to
`twig.form_themes`; view vars are `datepicker_options` (`display`, `restrictions`,
`localization` tree) and `localization.format` (Tempus Dominus tokens from
`JavaScriptFormatConverter`); ranges link via a `linked_to` outlet; its CSS is
Bootstrap-flavoured and light-only.

Plan:

- adminata's `form_admin_fields.html.twig` overrides `sonata_type_datetime_picker_widget` and
  `sonata_type_datetime_picker_widget_html`, plus the `sonata_type_date_range_*` and
  `sonata_type_datetime_range_*` blocks, rendering the TailAdmin datepicker input with
  `sonata-datepicker` (D17). A standalone `@SonataAdmin/Form/datepicker.html.twig` theme is
  offered for non-admin forms (`twig.form_themes` replacement of `@SonataForm/Form/datepicker.html.twig`).
- Option mapping: `display.components.{calendar,clock,seconds}` → `noCalendar`, `enableTime`,
  `enableSeconds`; `display.viewMode` → `flatpickr` plugins (month/year select); `stepping` →
  `minuteIncrement`; `restrictions.{minDate,maxDate,disabledDates,daysOfWeekDisabled}` →
  `minDate`, `maxDate`, `disable`; `useCurrent` → `defaultDate`; `localization.locale` →
  `flatpickr.l10ns[locale]` (dynamic import); `localization.format` → a Tempus-Dominus-token →
  flatpickr-token converter (`yyyy`→`Y`, `MM`→`m`, `dd`→`d`, `HH`→`H`, `mm`→`i`, `ss`→`S`,
  `h`/`t` → 12-hour + `K`), unit-tested against form-extensions' own converter cases.
- Range linking: outlet `sonata-datepicker` between start and end; `onChange` sets the partner's
  `minDate`/`maxDate`. Time-only fields (`noCalendar + enableTime`) must round-trip `07:30`.
- Default asset lists drop `bundles/sonataform/app.{js,css}`; apps re-adding them keep Tempus
  Dominus for non-admin forms only and must order the script after adminata's (R12). Dark-mode
  fallback CSS for `.tempus-dominus-widget` and `.input-group.date` ships in the compat layer.
- Vitest coverage mirrors form-extensions' `datepicker_controller.test.js`.

## 5. Security review of the JS rewrite

| Topic | Fact | Plan |
|---|---|---|
| Session CSRF | `sonata.delete` and `sonata.batch` intentions are session tokens (`CRUDController.php:138,203,263,393,495`); `_sonata_csrf_token` hidden field | unchanged; delete/batch stay full-page POSTs |
| Stateless CSRF | Symfony 7.2+ `SameOriginCsrfTokenManager` with `stateless_token_ids` mints the form `_token` client-side in a capture-phase `submit` listener (`csrf_protection_controller.js`); once a double-submit succeeded, later requests without the cookie/token pair are rejected | `sonata-association` submits via `form.requestSubmit()` so the native `submit` event fires and the app's listener mints the token, then intercepts with a capturing `submit` listener registered after it, performs `fetch` with `credentials: 'same-origin'` (POST requests carry `Origin`), and prevents default. Documented in `UPGRADE-1.0.md` §U11 |
| Inline edit | `SetObjectFieldValueAction` relies solely on `X-Requested-With` (no CSRF) | keep parity; send the header; note as an upstream improvement candidate |
| XSS surfaces | 52 `\|raw` sites, `safe_label`, `{% autoescape false %}` in scripts, x-editable `data-source` literal, `objectName` in JSON, `help_html` | inventory each during the rewrite; JSON travels in `data-*` attributes parsed with `JSON.parse`, never `eval`; `objectName` inserted with `textContent`; `safe_label` semantics unchanged |
| CSP | 8 files with inline `<script>`, 5 with `onclick`; no Alpine ⇒ no `unsafe-eval` | `sonata_script_attributes` nonce block on every remaining inline script; `data-action` in adminata markup; `onclick` shims only on association buttons (owner) |
| Cookies | `sonata_sidebar_hide` written by JS; new `sonata_theme` | `SameSite=Lax; path=/; max-age=31536000`, `Secure` when served over https; no `__Host-` prefix in 1.x (would rename cookies users read server-side) |
| Links | `target="new"` in list url templates | `target="_blank" rel="noopener"` |
| Dialogs | `<dialog>` top layer vs third-party popups | Tom Select/flatpickr inside dialogs keep inline parents (R8); document `tom_select_options.dropdownParent: 'body'` clash |

## 6. i18n and RTL

- `<html lang="{{ app.request.locale }}" dir="{{ locale is rtl ? 'rtl' : 'ltr' }}">` with a small
  RTL locale map (`ar`, `fa`, `he`, `ur`); Sonata ships `ar` and `fa` translations.
- Transcribe TailAdmin recipes with logical utilities (`ps-*`, `pe-*`, `ms-*`, `me-*`, `start-*`,
  `end-*`, `text-start`) so `dir="rtl"` works without a second build; sidebar translate
  direction flips via `rtl:` variants.
- New UI strings get ids in the `SonataAdminBundle` domain (English source; other locales fall
  back) and flow to JS through `<meta name="sonata-translations">`.
- flatpickr l10n and Tom Select messages follow `app.request.locale`;
  `canonicalize_locale_for_select2()` stays registered.
