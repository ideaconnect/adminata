# Changelog

All notable changes to `idct/adminata` are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html) — see the versioning contract in
[CONTRIBUTING.md](CONTRIBUTING.md).

Changes inherited from the forked Sonata packages are listed separately in
[CHANGELOG-sonata.md](CHANGELOG-sonata.md).

## [Unreleased]

adminata is pre-1.0 and under construction; [PROJECT_PLAN.md](PROJECT_PLAN.md) tracks what is done.
Milestone M0 (bootstrap) is complete: the seven packages are imported, replaced or merged, and
green under adminata's own tooling, and the PHP changes the Tailwind interface forces are in.
Milestone M1 (foundations) is complete: the Tailwind and Stimulus build, the demo application, and
every gate the template rewrites of M2 to M4 will be measured against.

Milestone M2 (shell) is complete: the layout, the sidebar, the header, the dashboard and the flash
messages are adminata's own, and the pages they own — the dashboard, a login page, an empty layout,
a dialog — have no accessibility and no markup findings in either theme.

Milestone M3 (list) is complete: list pages, filters, batch selection, pagers, row and batch
actions, export and the autocomplete combobox are adminata's own. select2 is replaced by a
hand-written ARIA 1.2 combobox with no dependency, and the request and response the
`sonata_admin_retrieve_autocomplete_items` action speaks are unchanged.

Milestone M4 (forms and show) is complete: the form theme, the collection widget, native date and
time inputs, the edit chrome, the show page, the delete page and the action buttons are adminata's.
**Every page the demo renders passes WCAG 2.1 AA in both themes, validates as HTML and does not
scroll sideways** — `tests/Visual/support/findings.json`, the ledger of what the inherited
interface still owed, is empty.

Milestone M5 (migration and acceptance) is complete: a 46-admin production panel was migrated onto
adminata on a branch, in thirteen commits — 140 files, +2,188 / −3,023 — and the checklist as
executed is [MIGRATION.md](MIGRATION.md). Its §3 is the part worth reading: the suites passed, and
**seventeen defects came from looking at the panel**, every one of which had passed every automated
gate. Eight more came out of the owner's later reviews and the sweep for hard-coded
colours that followed. All twenty-five are recorded as `P5-FIX-nn` in
[PROJECT_PLAN.md](PROJECT_PLAN.md); fifteen were adminata's and are fixed here. The last of them
is the sidebar: its sections slide at AdminLTE's 500 ms rather than a 200 ms that read as a toggle,
the rail slides between its widths as the drawer already did, the menu's root list is TailAdmin's
gapped column (`adm-menu`), and a group title sits evenly in that gap.

Milestone M6 begins with the documentation: `docs/` is one Sphinx site built from the packages'
trees plus adminata's own pages — the block, form and Twig-helper pages inside the admin bundle's,
the way their sources are — and `make docs` builds it with warnings as errors.

**`block-bundle`, `form-extensions`, `twig-extensions` and `exporter` were merged into
`admin-bundle`** (owner directives, 2026-09-06 and 2026-09-07). Seven package directories became
three. Blocks are the dashboard and the `sonata_block_render_event` hooks an application hangs its
markup on; the form types are what `FormMapper` builds every admin form out of; the flash-message
manager and the status helper are what the admin layout renders on every page; the exporter is what
every list page's export menu streams its result set through. Nothing in this fork uses any of the
four without the admin bundle — they are the admin bundle's main functionality, and adminata ships
it integrally — so none of them is worth a bundle of its own. These are the **breaking** changes to
the PHP API a Sonata application sees, and the `SonataBlockBundle`, `SonataFormBundle` and
`SonataTwigBundle` translation domains went with the classes. Read the `CollectionType` table under
*Changed* before updating any import: two collection types exchanged names, and the wrong one
renders silently.

What 1.0 inherits unported is written down rather than forgotten: 37 templates carrying an
`adminata: not yet ported` marker, listed in `tests/Contract/deferred-templates.txt` and asserted
by `DeferredTemplateTest`. They are the association edit flows, history and compare, ACL, preview,
mosaic and tree list modes, global search, the tab menu and four dashboard blocks.

### Added

- Hard fork of seven `sonata-project` packages into one Composer package `idct/adminata`, imported
  with `git subtree` at `admin-bundle` 4.43.0, `block-bundle` 5.4.0, `doctrine-extensions` 2.6.0,
  `doctrine-orm-admin-bundle` 4.21.0, `exporter` 3.4.0, `form-extensions` 2.7.0 and
  `twig-extensions` 2.6.0 (see [UPSTREAM.md](UPSTREAM.md)). Three of them are package directories;
  `block-bundle`, `exporter`, `form-extensions` and `twig-extensions` were merged into
  `packages/admin-bundle` — see *Changed*.
- Root `composer.json` replacing those three package names at their exact versions and conflicting
  with `sonata-project/block-bundle`, `sonata-project/exporter`,
  `sonata-project/form-extensions` and `sonata-project/twig-extensions`, with the union of their
  requirements on PHP `^8.4` and Symfony `^7.4 || ^8.0`.
- `sonata_admin.theme` with `mode` (`light`, `dark` or `system`), `logo_dark` and `logo_icon`, and
  the Twig functions `sonata_theme()` and `sonata_html_dir()`. The mode is resolved server-side
  from the `sonata_theme` cookie so a page never paints the wrong theme first.
- One PHPUnit configuration for the imported suites and adminata's own, a `ReplaceTest` that
  proves the `replace` block resolves next to `idct/sonata-admin-mongodb-bundle`, and seven CI
  workflows including a weekly watch on both the forked packages and the pinned dependency versions.
- Repository documents: `README.md`, `LICENSE`, `NOTICE`, `AGENTS.md`, `CONTRIBUTING.md`,
  `CHANGELOG-sonata.md`, `UPSTREAM.md`, `Makefile`, `upstream/{diff,sync}.sh`.
- Tailwind CSS v4 stylesheet built from TailAdmin v2.3.0's tokens, with a `.adm-*` component layer,
  a generated safelist and a CSS contract test; Stimulus 3.2 with an explicit controller registry,
  a Vite 8 build and a JavaScript contract snapshot. **No jQuery**, anywhere.
- A demo application under `tests/App`: the bundles wired the way an application wires them,
  two admins over MySQL and deterministic fixtures. `make demo` serves it; the functional, browser,
  visual and accessibility suites all drive it.
- Contract tests that freeze what a fork may not move: the markup hooks of every template group,
  every `@Sonata*/…` template path including the ones the MongoDB fork hard-codes, the
  configuration tree of six of the seven roots, and the list of templates 1.0 inherits unported.
- Browser and visual gates: Playwright screenshots per page, viewport and theme from a pinned
  container; `@axe-core/playwright` against WCAG 2.1 AA in both themes; `html-validate` over the
  rendered DOM; and a Panther harness that asserts an empty browser console.
- A CI job that installs `idct/adminata` into `idct/sonata-admin-mongodb-bundle` and runs its
  suite, so owner directive 7 is checked on every pull request rather than remembered.
- The TailAdmin shell: a fixed sidebar with collapsible groups, a sticky header with search, a
  light/dark/system theme toggle that never flashes the wrong theme, a skip link, a `<main>`
  landmark and a breadcrumb — plus the dashboard, its admin-list cards and the flash messages.
- Seven new Stimulus controllers: `sonata-layout`, `sonata-menu`, `sonata-theme`,
  `sonata-dropdown`, `sonata-modal`, `sonata-dismiss` and the shared list-mode partial they serve.
  Dialogs are native `<dialog>`, so the focus trap and Escape are the browser's.
- `Core/list_mode_buttons.html.twig`, shared by the standard and ajax layouts.
- The list: a card with a filter panel, a scrolling table, batch selection with an indeterminate
  header checkbox and shift-range selection, five pager templates, a native per-page select, an
  export menu, row and batch actions, and a batch confirmation page. A new additive block
  `list_after_table` replaces the footer an application used to hang its summaries off.
- `sonata-autocomplete`: a hand-written ARIA 1.2 combobox for `sonata_type_model_autocomplete`,
  single and multiple, with debouncing, remote paging, keyboard navigation and
  `aria-activedescendant`. select2 and jQuery are gone; the request parameters, the JSON response,
  `#{id}_autocomplete_input` and `#{id}_hidden_inputs_wrap` are not.
- `sonata-batch`, replacing the JavaScript upstream printed into every list page.
- The form theme: rows, labels, help (`help_html` kept), errors, and native widgets for text,
  textarea, select, checkbox, radio, file, money and percent — each recipe *appended* to whatever
  the application passed through `attr`, so a `data-controller` reaches the page untouched.
  Horizontal mode is a twelve-column grid. A control with errors carries `aria-invalid`.
- Native `<input type="date|time|datetime-local">` in place of Tempus Dominus, with the format
  derived from `datepicker_options.display.components` on both sides of the wire.
- The edit chrome: groups as cards in a twelve-column grid, a sticky action bar, the optimistic-lock
  conflict as a dismissible alert; the show page, the delete page and the action buttons.
- `tests/fixtures/js/*.html`, dumped from the demo application: every Stimulus controller is mounted
  against the markup the templates actually render, so a renamed target fails the JavaScript suite.
- **Clicking a list row opens the object.** `sonata-row-link` is one controller on the `<tbody>`,
  delegating, with the destination on each `<tr>`; the route is `default_admin_route`, then the
  other of `show`/`edit`, then nothing, checked per object. A click on a control, on a cell that
  exists only to hold controls, or one that ends a text selection is left alone; a middle or
  modified click opens a new tab. The row gets no `tabindex` and no `role="link"` — the accessible
  name would be the whole row, and the destination is already one Tab away. New option
  `sonata_admin.options.list_row_link`, default `true`.
- `adm-dropdown__menu-start`, for a dropdown whose button sits on the left of the page.
- `docs/`: one Sphinx site out of the six the packages carried, with five pages a fork has to
  write — theming, icons, the JavaScript API, compiling Tailwind yourself, and the porting status.
  `make docs` builds it with `-W`, `.readthedocs.yaml` sets `fail_on_warning`, and `bin/docs.sh`
  falls back to a container so the only thing a contributor needs installed is Docker.
- [MIGRATION.md](MIGRATION.md) and [UPGRADE-1.0.md](UPGRADE-1.0.md), written from what was actually
  executed rather than from the plan.
- `TranslationContractTest`: every key the English catalogue defines exists in every other. It
  found fourteen of adminata's own keys shipping English-only — including four `aria-label`s and
  the skip link, so a Polish screen-reader user heard `pager_navigation` — and seven more gaps
  inherited from upstream.
- A guard in `bin/check-css-contract.mjs` against the ordering trap the `dark` variant's
  zero specificity creates: a `:where(.dark…)` rule written before the plain rule it must override
  is dead CSS, and now fails the build.

### Changed

- **`sonata-project/block-bundle` is merged into `packages/admin-bundle`.** Its classes are spread
  into the admin bundle's existing directories rather than under a `Block\` umbrella, so every
  `Sonata\BlockBundle\` name an application referenced has moved:

  | Was | Is |
  |---|---|
  | `Sonata\BlockBundle\Block\*` (including `Loader\`, `Service\`) | `Sonata\AdminBundle\Block\*` |
  | `Sonata\BlockBundle\Command\*` | `Sonata\AdminBundle\Command\*` |
  | `Sonata\BlockBundle\DependencyInjection\Configuration` | `Sonata\AdminBundle\DependencyInjection\BlockConfiguration` |
  | `Sonata\BlockBundle\DependencyInjection\SonataBlockExtension` | `Sonata\AdminBundle\DependencyInjection\SonataBlockExtension` |
  | `…\Compiler\GlobalVariablesCompilerPass` | `…\Compiler\BlockGlobalVariablesCompilerPass` |
  | `…\Compiler\TweakCompilerPass` | `…\Compiler\BlockTweakCompilerPass` |
  | `Sonata\BlockBundle\Event\BlockEvent` | `Sonata\AdminBundle\Event\BlockEvent` |
  | `Sonata\BlockBundle\Exception\Block*Exception*` | `Sonata\AdminBundle\Exception\*` (same short names) |
  | `Sonata\BlockBundle\Exception\{Filter,Renderer,Strategy}\*` | `Sonata\AdminBundle\Exception\Block\{Filter,Renderer,Strategy}\*` |
  | `Sonata\BlockBundle\Form\Mapper\FormMapper` | `Sonata\AdminBundle\Form\BlockFormMapperInterface` |
  | `Sonata\BlockBundle\Form\Type\*` | `Sonata\AdminBundle\Form\Type\*` |
  | `Sonata\BlockBundle\{Menu,Meta,Model,Profiler,Util}\*` | `Sonata\AdminBundle\{Menu,Meta,Model,Profiler,Util}\*` |
  | `Sonata\BlockBundle\Templating\Helper\BlockHelper` | `Sonata\AdminBundle\Templating\BlockHelper` |
  | `Sonata\BlockBundle\Test\*` | `Sonata\AdminBundle\Test\*` |
  | `Sonata\BlockBundle\Twig\Extension\BlockExtension` | `Sonata\AdminBundle\Twig\Extension\BlockExtension` |
  | `Sonata\BlockBundle\Twig\GlobalVariables` | `Sonata\AdminBundle\Twig\BlockGlobalVariables` |

  **What did not change**, because an application's configuration and templates should not have
  to: every service id stays `sonata.block.*`; the Twig functions stay `sonata_block_render`,
  `sonata_block_render_event`, `sonata_block_exists`, `sonata_block_include_javascripts` and
  `sonata_block_include_stylesheets`; a `@SonataBlock/…` path still resolves (the namespace is kept
  as a compatibility alias of the admin bundle's view directory, where the block templates live);
  and `sonata_block:` stays its own configuration root in its own
  `config/packages/sonata_block.yaml`, registered by `SonataAdminBundle` itself.
  `git diff -- config/packages/` is empty across the merge.

  **The block templates are addressed as `@SonataAdmin/…` by default.** Every default inside
  adminata that used to say `@SonataBlock/…` — the `template` setting of the text, template,
  container, RSS and menu block services, `sonata_block.templates.block_base` and
  `block_container`, `sonata_block.profiler.template` (`@SonataAdmin/Profiler/block.html.twig`) and
  the two exception renderers — now says `@SonataAdmin/…`. The alias is a plain `twig.paths` entry
  with no `templates/bundles/` directory of its own, so a default addressed through it was never
  overridden by `templates/bundles/SonataAdminBundle/Block/` while the admin-list block, addressed
  as `@SonataAdmin`, was. Now every block template is overridden the way every other admin template
  is — a file under `templates/bundles/SonataAdminBundle/Block/`, `@!SonataAdmin/Block/…` reaching
  the shipped one — and `@SonataBlock` remains for templates outside adminata that still address
  it. `templates/bundles/SonataBlockBundle/` is read by nothing: move it to
  `templates/bundles/SonataAdminBundle/Block/`.

  **What did change besides the class names is the translation domain.** The block strings — the
  five `sonata.block.service.*` names and the fifteen `form.label_*` labels of the editable blocks —
  are units of `SonataAdminBundle`, appended to its catalogues, and the block services translate
  their forms in that domain. There is no `SonataBlockBundle` domain: an application that overrides
  a block string in `translations/SonataBlockBundle.<locale>.xliff` moves the unit into
  `translations/SonataAdminBundle.<locale>.xliff` (the ids are unchanged), and a template that
  passes `'SonataBlockBundle'` to `trans` passes `'SonataAdminBundle'`. **Breaking** for such an
  application; the reference application has neither. The block test application is folded into
  `packages/admin-bundle/tests/App` the same way, and the block render test runs with the other
  functional tests.
- **`sonata-project/form-extensions` and `sonata-project/twig-extensions` are merged into
  `packages/admin-bundle`.** The form types are the admin bundle's main functionality —
  `FormMapper` builds every admin form out of them — and the flash-message manager and the status
  helper are what the admin layout renders on every page; adminata ships all of it integrally.
  Their classes are spread into the admin bundle's existing directories, so every `Sonata\Form\`
  and `Sonata\Twig\` name an application referenced has moved:

  | Was | Is |
  |---|---|
  | `Sonata\Form\Type\*` | `Sonata\AdminBundle\Form\Type\*` |
  | `Sonata\Form\DataTransformer\*` | `Sonata\AdminBundle\Form\DataTransformer\*` |
  | `Sonata\Form\EventListener\*` | `Sonata\AdminBundle\Form\EventListener\*` |
  | `Sonata\Form\Validator\{ErrorElement,InlineValidator}` | `Sonata\AdminBundle\Validator\*` |
  | `Sonata\Form\Validator\Constraints\InlineConstraint` | `Sonata\AdminBundle\Validator\Constraints\InlineConstraint` |
  | `Sonata\Form\Test\AbstractWidgetTestCase` | `Sonata\AdminBundle\Test\AbstractWidgetTestCase` |
  | `Sonata\Form\Fixtures\StubTranslator` | `Sonata\AdminBundle\Test\StubTranslator` |
  | `Sonata\Form\Bridge\Symfony\DependencyInjection\Configuration` | `Sonata\AdminBundle\DependencyInjection\FormConfiguration` |
  | `Sonata\Form\Bridge\Symfony\DependencyInjection\SonataFormExtension` | `Sonata\AdminBundle\DependencyInjection\SonataFormExtension` |
  | `Sonata\Twig\Extension\*Extension` | `Sonata\AdminBundle\Twig\Extension\*Extension` |
  | `Sonata\Twig\Extension\{FlashMessage,Status}Runtime` | `Sonata\AdminBundle\Twig\*Runtime` |
  | `Sonata\Twig\FlashMessage\*` | `Sonata\AdminBundle\FlashMessage\*` |
  | `Sonata\Twig\Status\StatusClassRendererInterface` | `Sonata\AdminBundle\Status\StatusClassRendererInterface` |
  | `Sonata\Twig\{Node,TokenParser}\*` | `Sonata\AdminBundle\Twig\{Node,TokenParser}\*` |
  | `Sonata\Twig\Bridge\Symfony\DependencyInjection\Configuration` | `Sonata\AdminBundle\DependencyInjection\TwigConfiguration` |
  | `Sonata\Twig\Bridge\Symfony\DependencyInjection\SonataTwigExtension` | `Sonata\AdminBundle\DependencyInjection\SonataTwigExtension` |

  **What did not change**, because an application's configuration and templates should not have
  to: every service id stays `sonata.form.*` and `sonata.twig.*`; the Twig functions stay
  `sonata_flashmessages_get`, `sonata_flashmessages_types` and `sonata_flashmessages_class`, the
  filter stays `sonata_status_class` and the tag stays `sonata.status.renderer`; a
  `@SonataForm/…` or `@SonataTwig/…` path still resolves (both namespaces are kept as compatibility
  aliases of the admin bundle's view directory, where those templates live); and `sonata_form:` and
  `sonata_twig:` stay their own configuration roots in their own
  `config/packages/sonata_{form,twig}.yaml`, registered by `SonataAdminBundle` itself.
  `datepicker.html.twig` and `FlashMessage/render.html.twig` are addressed as `@SonataAdmin/…` by
  default, so they are overridden under `templates/bundles/SonataAdminBundle/` like every other
  admin template; `templates/bundles/SonataFormBundle/` and `templates/bundles/SonataTwigBundle/`
  are read by nothing.
- **Two `CollectionType`s exchanged names, and the wrong one renders silently.** Both classes
  exist, both are used, and both are now `Sonata\AdminBundle\Form\Type\`:

  | Was | Is | Block prefix | What it renders |
  |---|---|---|---|
  | `Sonata\AdminBundle\Form\Type\CollectionType` | `Sonata\AdminBundle\Form\Type\NativeCollectionType` | `sonata_type_native_collection` | Symfony's own collection type with `add` and `delete` buttons |
  | `Sonata\Form\Type\CollectionType` | `Sonata\AdminBundle\Form\Type\CollectionType` | `sonata_type_collection` | the association collection the storage layer's form theme renders |

  `use Sonata\AdminBundle\Form\Type\CollectionType;` compiles either way and the field still
  builds, so an import updated carelessly changes which widget the page gets with nothing to catch
  it. Rename to `NativeCollectionType` **first**, then move the `Sonata\Form\` imports. The block
  prefixes did not change, so a Twig block override of either keeps working.
- **The `SonataFormBundle` and `SonataTwigBundle` translation domains went the way of
  `SonataBlockBundle`'s.** Their eight units — `link_add`, `label_type_yes`, `label_type_no`,
  `date_range_start`, `date_range_end`, `message_close`, `more` and `less` — are units of
  `SonataAdminBundle`, appended to its catalogues, and every `btn_translation_domain` default names
  that domain. **Breaking** for an application that overrides one of them in
  `translations/SonataFormBundle.<locale>.xliff` or `translations/SonataTwigBundle.<locale>.xliff`:
  the unit moves into `translations/SonataAdminBundle.<locale>.xliff` with its id unchanged, and a
  template that passes `'SonataFormBundle'` or `'SonataTwigBundle'` to `trans` passes
  `'SonataAdminBundle'`. The reference application has neither.
- **`sonata-project/exporter` is merged into `packages/admin-bundle`.** The exporter is what every
  list page's export menu streams its result set through, and nothing in this fork exports without
  the admin bundle, so it is not a library of its own here (owner directive, 2026-09-07). The admin
  bundle already had an `Exporter\` directory — `Sonata\AdminBundle\Exporter\DataSourceInterface`,
  which is where it always was — so the whole tree folded under it. These are the names an
  application actually touches:

  | Was | Is |
  |---|---|
  | `Sonata\Exporter\ExporterInterface`, `Exporter`, `Handler` | `Sonata\AdminBundle\Exporter\*` (same short names) |
  | `Sonata\Exporter\Source\*` (14 iterators) | `Sonata\AdminBundle\Exporter\Source\*` |
  | `Sonata\Exporter\Writer\*` (10 writers, the CSV stream filter, `WriterInterface`, `TypedWriterInterface`) | `Sonata\AdminBundle\Exporter\Writer\*` |
  | `Sonata\Exporter\Exception\*` | `Sonata\AdminBundle\Exporter\Exception\*` |
  | `Sonata\Exporter\Bridge\Symfony\DependencyInjection\Configuration` | `Sonata\AdminBundle\DependencyInjection\ExporterConfiguration` |
  | `Sonata\Exporter\Bridge\Symfony\DependencyInjection\SonataExporterExtension` | `Sonata\AdminBundle\DependencyInjection\SonataExporterExtension` |
  | `…\DependencyInjection\Compiler\ExporterCompilerPass` | `Sonata\AdminBundle\DependencyInjection\Compiler\ExporterCompilerPass` |
  | `Sonata\Exporter\Bridge\Symfony\SonataExporterBundle` | deleted |

  **What did not change**, because an application's configuration should not have to:
  `sonata_exporter:` stays its own configuration root in its own
  `config/packages/sonata_exporter.yaml`, registered by `SonataAdminBundle` itself; every service
  id stays `sonata.exporter.*`, including the public `sonata.exporter.exporter` and the
  `sonata.exporter.writer.<format>` services; the `sonata.exporter.writer` tag and the
  `sonata.exporter.writer.*.*` container parameters are untouched. The exporter ships no templates and no translations, so unlike the other three merges
  this one moves no Twig namespace and no translation domain — there is nothing to alias and
  nothing to re-file. `git diff -- config/packages/` is empty across the merge.

  The one byte of exporter output that changed is the `xls` writer's attribution: every `.xls` it
  streams carries `<meta name=Generator content="https://github.com/ideaconnect/adminata">`, the
  repository this code is in, instead of the upstream one it left.
- `composer.json` `replace`s three packages, not seven, and gains a **`conflict`** on
  `sonata-project/block-bundle`, `sonata-project/exporter`, `sonata-project/form-extensions` and
  `sonata-project/twig-extensions`: adminata provides those APIs under a different namespace, so
  they cannot be installed side by side. `bin/check-replace-versions.php` checks the three replaced
  trees and the four merged trees against `UPSTREAM.md`, `upstream/remotes.txt` and the new
  `upstream/merged.txt`.
- Upstream `block-bundle`, `exporter`, `form-extensions` and `twig-extensions` releases are now
  ported **by hand**. `upstream/sync.sh` needs a `packages/<name>/` to apply a diff into and there
  is no longer one, so the mechanical replay is gone for those four trees — their subtree
  histories, their remotes and `upstream/diff.sh` remain. See [UPSTREAM.md](UPSTREAM.md).
- `Menu/sonata_menu.html.twig`, `Core/{dashboard,add_block,user_block}.html.twig`,
  `Block/block_admin_list.html.twig`, `CRUD/dashboard__action*.html.twig`, both layouts and
  twig-extensions' `FlashMessage/render.html.twig` are rewritten. Every Twig block name, every
  markup hook of the compatibility contract and the `<li>` pass-through of `user_block`,
  `add_block` and `Button/*` are kept; `admin_lte_skin_class` and `bootlint` are gone.
- The header's user menu shows who is signed in even when `user_block` is empty, which is its
  default; upstream hid the menu entirely until an application wrote one.
- Page actions render as a button row. The upstream heuristic that folded two or more into an
  "Actions" dropdown by counting `</a>` in a captured string is gone.
- Font Awesome is imported into a `vendor` cascade layer, so adminata's utilities win over it.
- Block ids no longer contain the dot `uniqid('', true)` puts in: they reach the page as
  `id="cms-block-…"`, where a dot makes a selector nobody can write without escaping it.
- Date and time pickers exchange their value in the format a native HTML5 input uses, derived from
  `datepicker_options.display.components`. An explicit `format` is refused, the way Symfony's
  `DateType` refuses one when `html5` is enabled.
- Default assets are `bundles/sonataadmin/app.css`, `bundles/sonataadmin/fontawesome.css` and
  `bundles/sonataadmin/app.js`; the AdminLTE skin stylesheet is no longer appended.
- A form group's `box_class` defaults to an empty string, and a dashboard block's `class` to
  `md:col-span-4`.
- The test suites, the demo application and CI run on **MySQL**; adminata 1.0 supports MySQL,
  MariaDB and Percona, and not SQLite.
- The admin content column is no longer capped at 1536px. TailAdmin's cap is written for
  dashboards; an admin list is sixteen columns of data, and past the cap the table scrolled inside
  a box with empty page beside it.
- A list's batch, select and action columns take their content width, and the row actions no longer
  wrap. On a sixteen-column list the action column had been squeezed to a fraction of the row and
  five icon buttons stacked into five lines — 221px rows, four to a screen.
- A wide table scrolls inside its own box rather than scrolling the page, which needed three things
  to be true: the table inside `adm-table-scroll`, `min-w-0` on the card, and `position: relative`
  on the scroll box so the `sr-only` labels stop being positioned against the page.
- `adm-card-body` draws no rule when it is the card's first child, and `adm-table-wrap` no longer
  draws a second border inside the card's.
- Row actions have a resting surface inside the actions column: a bare glyph in a table cell reads
  as content rather than as a control.
- Filters and the list share one grid, so the `gap` puts air between them.

### Removed

- `Sonata\BlockBundle\SonataBlockBundle`. There is no block bundle class to register: the line goes
  out of `config/bundles.php` and `SonataAdminBundle` registers `SonataBlockExtension` itself. It
  is one of the four `bundles.php` lines the merges cost an application that neither names the
  moved classes nor overrides the moved strings.
- The `SonataBlockBundle` translation domain and its eight catalogues (`ar`, `de`, `en`, `fr`,
  `hu`, `it`, `nl`, `ru`). The block strings are units of `SonataAdminBundle` — see *Changed*.
- `packages/block-bundle/`. Its `src/`, `tests/` and `Resources/` are under
  `packages/admin-bundle/`; its `CHANGELOG.md` is `packages/admin-bundle/CHANGELOG-block.md`; its
  copy of the Sonata `LICENSE` is gone because the admin bundle already carries the same file.
- `Sonata\Form\Bridge\Symfony\SonataFormBundle` and `Sonata\Twig\Bridge\Symfony\SonataTwigBundle`.
  There is no form bundle and no Twig-extensions bundle class to register: the two lines go out of
  `config/bundles.php` and `SonataAdminBundle` registers `SonataFormExtension` and
  `SonataTwigExtension` itself.
- The `SonataFormBundle` translation domain and its 27 catalogues, and the `SonataTwigBundle`
  domain and its 7. Their eight units are units of `SonataAdminBundle` — see *Changed*.
- `packages/form-extensions/` and `packages/twig-extensions/`. Their `src/`, `tests/` and
  `Resources/` are under `packages/admin-bundle/`; their `CHANGELOG.md` files are
  `packages/admin-bundle/CHANGELOG-form.md` and `CHANGELOG-twig.md`; their copies of the Sonata
  `LICENSE` are gone because the admin bundle already carries the same file. The DI service files
  are renamed flat so they cannot collide with the admin bundle's own: `form_ext_types.php`,
  `form_validator.php`, `twig_flash.php` and `twig_ext.php`.
- `Sonata\Exporter\Bridge\Symfony\SonataExporterBundle`. There is no exporter bundle class to
  register either: the fourth line goes out of `config/bundles.php` and `SonataAdminBundle`
  registers `SonataExporterExtension` and the `sonata.exporter.writer` compiler pass itself.
- `packages/exporter/`. Its `src/` and `tests/` are under `packages/admin-bundle/` — the sources
  under `Sonata\AdminBundle\Exporter\`, the DI classes beside the admin bundle's own and the tests
  under `tests/Exporter/` and `tests/DependencyInjection/` — and its `CHANGELOG.md` is
  `packages/admin-bundle/CHANGELOG-exporter.md`; its copy of the Sonata `LICENSE` is gone because
  the admin bundle already carries the same file. The DI service file is renamed
  `exporter_services.php`, because the admin bundle already ships an `exporter.php` that wires
  `sonata.admin.exporter` and the `AdminExporter` bridge.
- The upstream repo scaffolding under `packages/*/`: `Makefile`, `bin/console`, `README.md`,
  `CONTRIBUTING.md`, `UPGRADE-*.md` and the seven dev-kit dotfiles, 83 files in all. A forked
  package is a directory of this repository, so one root governs them; what the per-package
  `.gitignore` and `.gitattributes` actually did moved into the root ones. The upstream
  `LICENSE` and `CHANGELOG.md` stay. The Composer archive is unchanged, 1040 files either way.
- `sonata_admin.options.skin`, `use_select2`, `use_icheck` and `use_bootlint`. They are removed, not
  deprecated: leaving them in `sonata_admin.yaml` is a container build error.
- `Sonata\Form\Date\JavaScriptFormatConverter`, form-extensions' `assets/` and its published
  `Resources/public/`, twig-extensions' `flashmessage.css`, the prebuilt AdminLTE skins and select2
  locales, the packages' Webpack/Babel/ESLint/Stylelint/Prettier configuration, and the cookbook
  recipes for bootlint, iCheck, jQuery UI and select2.

[Unreleased]: https://github.com/ideaconnect/adminata/commits/main
