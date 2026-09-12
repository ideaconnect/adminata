# Migrating an application from Sonata Admin to adminata

> **Two rounds.** Round 1 below is the migration as it was executed in September 2026, from
> `sonata-project/admin-bundle` 4.43 to adminata **under the Sonata names** it still carried then
> — every identifier in it (`Sonata\AdminBundle\`, `@SonataAdmin`, `sonata_admin`, `sonata-ba-*`)
> is the name of the time. Since 2026-09-12 adminata's names are its own, and round 2, at the
> bottom, is the second pass the same panel made to reach them. An application starting today
> does both in one go: [UPGRADE.md](UPGRADE.md) has the map and the tool.


The checklist below is the one that was **executed**, once, against
[recomaty-panel](https://github.com/ideaconnect) — a Symfony 7 admin of 46 admin classes, 55 cell
templates and a second Stimulus application — on a branch called `adminata`, in thirteen commits
totalling 140 files, +2,188 / −3,023 lines. Every figure in it is measured from that branch rather
than estimated.

There is no compatibility layer in adminata, so every row is a real edit in the application. That
is the point: the classes, the JavaScript and the markup all change, and nothing silently keeps
working by accident.

**About the hours.** The column is the estimate from
[PLAN/10](PLAN/10-migration-guide-outline.md) §1 — roughly 45 hours for an engineer who knows the
application. This migration was not carried out by an engineer with a stopwatch, so those numbers
are not a measurement and are not presented as one. What *is* measured is the size of each step and
what it turned out to involve, which is the part an estimate usually gets wrong. Read the
**Actually** column before you plan.

**Before you start.** Run every Composer command of the migration with `--no-plugins
--no-scripts`. Uninstalling the `sonata-project/*` packages otherwise makes Symfony Flex run their
recipes' `unconfigure`, which deletes `config/packages/sonata_admin.yaml`,
`config/packages/sonata_block.yaml`, `config/packages/sonata_form.yaml`,
`config/routes/sonata_admin.yaml` and `src/Admin/.gitignore` without a hash check. Those
configuration roots all survive the merges of steps 17 to 19 — it is the recipe that deletes the
files, not adminata.

## 1. The procedure

| Step | Edit | Est. | Actually |
|---|---|---|---|
| 0 | Commit everything; `git checkout -b adminata`. | — | — |
| 1 | `composer config repositories.adminata path ../../idct/adminata` and `composer require idct/adminata:@dev --no-plugins --no-scripts` (a path repository until adminata is on Packagist), then `composer remove --no-plugins --no-scripts sonata-project/admin-bundle sonata-project/doctrine-orm-admin-bundle`; delete the seven `sonata-project/*` entries from `symfony.lock` by hand; `bin/console cache:clear && bin/console assets:install public`. | 0.5 | 6 files. `git diff -- config/` was empty as promised, and `bundles.php` kept its seven Sonata bundle classes plus the MongoDB one — the `replace` really is transparent to the container. Later amended by steps 17 to 19, which take four of those seven lines out. |
| 2 | `config/packages/sonata_admin.yaml`: delete `options.use_select2`; add `theme: { mode: system }`; `assets.remove_stylesheets: [bundles/sonataadmin/app.css]` once step 11 lands. | 0.5 | Two further options wanted setting once the panel was on screen — see the note under the table. |
| 3 | `config/packages/twig.yaml`: add `@SonataAdmin/Form/form_admin_fields.html.twig` so the plain Symfony forms on admin pages share the admin look. | 0.2 | As written. |
| 4 | Layout override: delete the `admin_lte_skin_class`, `javascripts`, `sonata_javascript_config` and `sonata_javascript_pool` blocks; **port** `logo` rather than deleting it; drop the Font Awesome CDN link; the universal modal becomes a native `<dialog>` with `sonata-modal`. | 2 | 1 file, +42 −70. Porting `logo` matters: it renders the signed-in administrator's White Label, and deleting it would cost every branded customer their mark. |
| 5 | Login page, password-reset layout, `user_block`. | 3 | 5 files. Two traps: those templates replace `sonata_wrapper`, and adminata nests `sonata_nav` *inside* it, so a `sonata_nav` override there never renders; and a language selector that was still Bootstrap 3 kept working by accident under jQuery and stopped the moment step 15 removed it. |
| 6 | Nine `notice` includes → `{% block notice %}{{ parent() }}{% endblock %}`. | 0.5 | The `@SonataCore/FlashMessage/render.html.twig` fallback went with them: that path has not existed since Sonata 4, so the two-element include list had been resolving to its second element every time. |
| 7 | Ten page templates; delete six dead ones. | 6 | 21 files, +274 −454. A scripted pass did the one-to-one class names and reported what it could not decide; the six it left — a `box-solid`, a `box {{ boxClass }}` assembled at render time, a `box-tools` toolbar, two icon-only buttons — were done by hand. Colour taken from a name pasted into a class (`box-success`) has to become the **whole utility** (`border-t-success-500`): Tailwind generates what it can see in the source. |
| 8 | Sixteen `list__action*` overrides, the create button, `list__select`, `list_enum`, `Association/list_many_to_one`. | 3 | 19 files. Every one was icon-only with its label in `title`, which a screen reader may or may not announce; each got a visually hidden name. Five of these overrides were later **deleted** instead — they had become stale copies of what adminata ships. |
| 9 | `crud/list_with_summaries.html.twig` into `{% block list_after_table %}`. | 1 | 1 file. It had opened with a stray `</div>` to climb out of the AdminLTE box it was rendered inside. |
| 10 | 55 cell templates under `templates/field/`. | 12 | 32 files, +127 −164 — the largest single step, and the one the estimate got closest. |
| 11 | CSS and build: `assets/styles/admin.css`, `@tailwindcss/postcss` in Encore; delete `admin-theme.scss` and its three partials (462 lines); trim `sonata-overrides.scss`. | 4 | 13 files, +619 −874. `sonata-overrides.scss` went 658 → 475 lines. **Trim carefully**: the first pass cut it to 314 and took two things with it that nothing tested — a status column that then rendered blank, and the skin that kept a third-party select from looking like a different decade. |
| 12 | 31 group classes `col-md-N` → `col-span-12 md:col-span-N`; drop `form-control` and `btn btn-*` attrs in three form types. | 1 | 21 files under `src/`. Grep `templates/` too, not only `src/` — four call sites in a macro were missed by an `src`-only check and made the dashboard's charts a twelfth of the width each. |
| 13 | Delete the `format` options on twelve date/time fields. | 1 | As written. adminata's `BasePickerType` fixes the wire format from `display.components` and rejects a custom `format` the way Symfony does for `DateType` with `html5: true`. |
| 14 | `fa fa-clock-o` → `fa fa-clock`. | 0.3 | One template; 75 of 76 icon names resolve unchanged in Font Awesome 7 Free. |
| 15 | JavaScript: three files off jQuery — `dialog.showModal()`, a `hidden` toggle, `fetch` + `insertAdjacentHTML`; delete the jQuery and jquery-ui dependencies and `.addExternals({ jquery: 'jQuery' })`. | 2 | 7 files, +140 −114. `npm ls jquery` is empty. |
| 16 | Run the suites; add the browser scenarios; fix what breaks, in adminata rather than around it. | 8 | This is where the estimate is most wrong, and not because of the tests. See §3. |
| 17 | Delete the `Sonata\BlockBundle\SonataBlockBundle::class` line from `config/bundles.php`. | — | Not in the original run: adminata merged `block-bundle` into `admin-bundle` on 2026-09-06, after this migration was executed. One line, and nothing a human edits besides: `git diff -- config/bundles.php config/packages config/routes` is that deletion alone. `config/packages/sonata_block.yaml` and the `sonata_block:` key at the end of `sonata_admin.yaml` both kept working untouched, because `sonata_block` is still its own configuration root — `SonataAdminBundle` registers its extension now. The panel does commit Symfony's auto-generated `config/reference.php`, and that regenerated on the next container build (+41 −44): the `SonataBlockConfig` type and the root `sonata_block?:` key moved to the end, past every bundle's, and the key left the three `when@<env>` maps. No configuration meaning changed. The panel names no block class of its own, so the namespace map in [UPGRADE-1.0.md](UPGRADE-1.0.md) §U1 cost it nothing; an application that writes its own block services pays there instead. Nor did the block strings' move into the `SonataAdminBundle` translation domain cost it anything: the panel has no `translations/SonataBlockBundle.*.xliff` and no template that names that domain. |
| 18 | Delete the `Sonata\Form\Bridge\Symfony\SonataFormBundle::class` and `Sonata\Twig\Bridge\Symfony\SonataTwigBundle::class` lines from `config/bundles.php`; move the `Sonata\Form\` imports to `Sonata\AdminBundle\Form\Type\`; rename the six admin classes' `CollectionType` to `NativeCollectionType`. | — | Not in the original run either: `form-extensions` and `twig-extensions` were merged into `admin-bundle` on 2026-09-06, the same day as step 17. 23 files under `src/`, +40 −40 — nine `BooleanType`, five `DateTimePickerType`, three `DateRangePickerType`, two each of `DatePickerType` and `DateTimeRangePickerType`, and **six `CollectionType` → `NativeCollectionType`**. That last one is the only edit here that is not a pure import rewrite, and it is the one to do first and on its own: `use Sonata\AdminBundle\Form\Type\CollectionType;` still compiles after the merge and renders the *other* widget ([UPGRADE-1.0.md](UPGRADE-1.0.md) §U1). `config/packages/twig.yaml` lost its `@SonataForm/Form/datepicker.html.twig` entry, because `SonataFormExtension` prepends `@SonataAdmin/Form/datepicker.html.twig` itself, and that spelling is the one `templates/bundles/SonataAdminBundle/` can override. The panel has no `translations/SonataFormBundle.*.xliff` or `SonataTwigBundle.*.xliff` and no template naming either domain, so that half cost it nothing; one docblock referencing `@SonataTwig/FlashMessage/render.html.twig` was corrected to `@SonataAdmin/…`. Applied together with step 17, `config/reference.php` regenerated once for all three merges: +55 −64. |
| 19 | Delete the `Sonata\Exporter\Bridge\Symfony\SonataExporterBundle::class` line from `config/bundles.php`. | — | Not in the original run either: `exporter` was merged into `admin-bundle` on 2026-09-07, the day after steps 17 and 18. One line, and nothing else a human edits: the panel names no exporter class of its own — no writer, no source iterator, no `ExporterInterface` type hint — so the namespace map in [UPGRADE-1.0.md](UPGRADE-1.0.md) §U1 cost it nothing, and it has no `config/packages/sonata_exporter.yaml` to keep either. An application that writes its own writer pays in imports instead, and keeps its `sonata.exporter.writer` tag. Symfony's generated `config/reference.php` regenerates once more on the next container build, the `SonataExporterConfig` type and the root `sonata_exporter?:` key moving past every bundle's and out of the three `when@<env>` maps, exactly as `sonata_block` did in step 17. |
| | **Total** | **≈45** | |

Two configuration options were set only once the panel was on screen, and both are worth deciding
deliberately rather than inheriting:

- `options.list_action_button_content: icon` — the default is `all`, which renders the four
  actions adminata ships as wide text buttons beside an application's own icon-only ones.
- `options.default_admin_route: edit` — the default is `show`, and nine of this application's 46
  admin classes define `configureShowFields()`. With the default, a click on a row (adminata 1.0
  opens the object from the row) would have landed on a blank page for the other 37. adminata
  falls back to `show` for an admin with no `edit` route, which is what the read-only ones need.

## 2. What the application kept unchanged

Verified against the branch, not assumed:

every admin class and its `configure*` methods; `config/services/admin/*.yaml` tags
and calls (`setTemplate`, `setFormTheme`, `setListActions`); `sonata_doctrine_orm_admin.templates.types`;
`sonata_block.yaml`, `sonata_form.yaml`; custom controllers and routes; `SidebarMenuSubscriber`;
`BaseAdmin` summaries; the second Stimulus application and its own controllers; the
`@symfony/ux-autocomplete` fields and filters; Dropzone; `csrf.yaml` stateless tokens;
`lock_protection`; `use_stickyforms`; flash keys; every `admin_app_*` route name; and the
`idct/sonata-admin-mongodb-bundle` dependency with its `DebugRequestAdmin`.

`bundles.php` was on that list until steps 17 to 19 above took four lines out of it — the block,
form, Twig and exporter bundles. Every other line, including the MongoDB one, is still what it was.
The `sonata_block.yaml` and `sonata_form.yaml` in the list above are unchanged by those steps: the
configuration roots outlived the bundle classes.

## 3. What the migration exposed

Step 16 is estimated at eight hours for running the suites. The suites were the easy part: the
Behat suite passed, and so did the panel's PHPUnit. **Seventeen defects were found by looking at
the panel**, and they are the reason this section exists.

Four of them were reported by the application's owner on first sight; the other thirteen came out
of measuring the pages that were supposed to prove the four. They are listed in
[PROJECT_PLAN.md](PROJECT_PLAN.md) as `P5-FIX-01` … `P5-FIX-17`. The pattern is worth more than the
list:

- **A test suite that is green tells you the markup parses, not that the page is usable.** Every
  one of the seventeen passed every automated gate. Row height, column width, a dropdown that
  opens off the side of the screen and a control that is invisible in one theme are all valid
  HTML with no accessibility violations.
- **Look at the widths the tests do not.** The visual suite's widest viewport is 1280px; a
  1536px cap on the content column was therefore invisible to it, and on a real monitor it left
  the table scrolling inside a box with empty page beside it.
- **Look in both themes, and at hover.** One dark rule written above the light rule it had to
  beat made every dropdown paint light text on a light background — but only while hovered.
- **A serving detail can look like a hundred CSS bugs.** `php -S … public/index.php` sends static
  files through PHP, which labels them `text/html`; a browser in standards mode then refuses the
  stylesheet and the whole panel renders unstyled. `tests/adminata-router.php` on the branch is a
  review-only router that types what it serves.
- **Grep the templates, not only `src/`.** Two of the seventeen were classes an `src`-only
  acceptance check never looked at.

Five of the seventeen were the application's own unfinished port; twelve were adminata's, and were
fixed there rather than worked around in the application — which is the rule this project runs on.

## 4. When you think you are done

1. `grep -rn "col-md-\|btn-action-icon\|data-toggle=\|label label-\|form-control" templates src` — empty.
   So is `grep -rn 'Sonata\\BlockBundle' src templates config tests`.
2. `npm ls jquery` — empty.
3. Open every kind of page — list, filtered list, create, edit, show, delete, dashboard, login — in
   **both themes**, at the width of the monitor the panel is actually used on, and check that
   `document.documentElement.scrollWidth === document.documentElement.clientWidth` on each.
4. Open every dropdown and every enhanced control and hover the rows.
5. Read the browser console. It should be empty.
