# Upgrading to adminata 1.0

For an application on `sonata-project/admin-bundle` 4.43 that is not recomaty-panel. adminata
`replace`s three Sonata packages at exact versions, so the PHP layer — admin classes, services,
routes — carries over untouched. What changes is the markup, the JavaScript, the form widgets, a
handful of configuration nodes, and four lines of `bundles.php`: the other four packages —
`sonata-project/block-bundle`, `sonata-project/exporter`, `sonata-project/form-extensions` and
`sonata-project/twig-extensions` — are merged into the admin bundle rather than replaced (U1).

There is **no compatibility layer**: nothing is aliased, shimmed or kept "for now". If your
application touched Bootstrap classes, jQuery or `window.Admin`, those places are edits.

A worked example, with real figures for each step, is [MIGRATION.md](MIGRATION.md).

## U1 — Install, and the four merged trees

```console
$ composer require idct/adminata --no-plugins --no-scripts
$ composer remove --no-plugins --no-scripts sonata-project/admin-bundle sonata-project/doctrine-orm-admin-bundle
$ composer install
$ bin/console cache:clear
$ bin/console assets:install public
```

`--no-plugins --no-scripts` is not optional. Uninstalling the `sonata-project/*` packages otherwise
makes Symfony Flex run their recipes' `unconfigure`, which deletes
`config/packages/sonata_{admin,block,form}.yaml`, `config/routes/sonata_admin.yaml` and
`src/Admin/.gitignore` without a hash check. Remove the seven `sonata-project/*` entries from
`symfony.lock` by hand afterwards; it is Flex bookkeeping and nothing reads it at runtime.

If `sonata-project/block-bundle`, `sonata-project/exporter`, `sonata-project/form-extensions` or
`sonata-project/twig-extensions` is an explicit `require` of yours, `composer remove` it too:
adminata **conflicts** with all four and the install will refuse otherwise. They are the four
packages adminata does not replace — it merged them into the admin bundle instead, and the rest of
this section is what that costs you.

### `bundles.php` loses four lines

```diff
-    Sonata\BlockBundle\SonataBlockBundle::class => ['all' => true],
-    Sonata\Exporter\Bridge\Symfony\SonataExporterBundle::class => ['all' => true],
-    Sonata\Form\Bridge\Symfony\SonataFormBundle::class => ['all' => true],
-    Sonata\Twig\Bridge\Symfony\SonataTwigBundle::class => ['all' => true],
```

That is the whole edit **you** make to configuration. `SonataAdminBundle` registers the block,
exporter, form and Twig extensions itself, so the `sonata_block`, `sonata_exporter`, `sonata_form`
and `sonata_twig` configuration roots, the `sonata.block.*`, `sonata.exporter.*`, `sonata.form.*`
and `sonata.twig.*` services and the block and flash Twig functions are all still there —
`config/packages/sonata_block.yaml`, `config/packages/sonata_exporter.yaml`,
`config/packages/sonata_form.yaml` and `config/packages/sonata_twig.yaml` do not change, and
neither does the `sonata_block:` key many applications keep at the end of `sonata_admin.yaml`. The
check is over the files a human edits:

```console
$ git diff -- config/bundles.php config/packages config/routes
```

must show exactly those four deleted lines and nothing else.

`git diff -- config/` on its own is wider than that, because Symfony's FrameworkBundle
auto-generates `config/reference.php` and regenerates it on the next container build. Its
`sonata_block` shape moves, since `sonata_block` is no longer contributed by an entry in
`bundles.php`: the `SonataBlockConfig` type and the root-level `sonata_block?:` key move past every
bundle's to the end, and the key drops out of the `when@dev`, `when@test` and `when@prod` maps.
`sonata_exporter`, `sonata_form` and `sonata_twig` move the same way, for the same reason. In the
reference application the block change alone was 41 lines added and 44 removed, with no
configuration meaning changed. Applications that do not commit the generated file never see it.

### The `Sonata\BlockBundle\` namespace is gone

Blocks are part of adminata's admin bundle: nothing in this fork uses blocks without it, so they
are not a bundle of their own here. If your application names block classes — a custom block
service, a block loader, `BlockContextInterface`, a `BlockEvent` listener, `BlockServiceTestCase`
in a test — those names moved:

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

Nothing else about the block API moved, deliberately: the service ids are still `sonata.block.*`,
the Twig functions are still `sonata_block_render`, `sonata_block_render_event`,
`sonata_block_exists`, `sonata_block_include_javascripts` and `sonata_block_include_stylesheets`,
and a `@SonataBlock/…` path still resolves — the namespace is kept as a compatibility alias of the
admin bundle's views, where the block templates live as `@SonataAdmin/Block/…`, the path adminata's
own defaults use. Service definitions and templates therefore need no edit.

### The `SonataBlockBundle` translation domain is gone

The block strings — the five `sonata.block.service.*` names and the `form.label_*` labels of the
editable blocks — are units of the `SonataAdminBundle` domain, in `SonataAdminBundle.<locale>.xliff`
with the rest of the admin bundle's, and the block services translate their forms in that domain.
The ids are unchanged; the domain is not. So, if you have them:

- `translations/SonataBlockBundle.<locale>.xliff` is read by nothing. Move its units into
  `translations/SonataAdminBundle.<locale>.xliff`.
- A template or a block service of your own that names the domain —
  `{{ 'form.label_title'|trans({}, 'SonataBlockBundle') }}`, or
  `'translation_domain' => 'SonataBlockBundle'` among a form field's options — names
  `'SonataAdminBundle'` instead.

`templates/bundles/SonataBlockBundle/`, if you have it, no longer overrides anything either. Symfony
builds those directories from **registered bundles**, and there is no `SonataBlockBundle` to
register. Move its files to `templates/bundles/SonataAdminBundle/`, same relative path —
`Block/block_core_text.html.twig` stays `Block/block_core_text.html.twig` — which is how every other
admin template is overridden. Nothing else is needed: the block services' `template` defaults,
`sonata_block.templates.block_base` and `block_container`, the profiler and the exception renderers
all say `@SonataAdmin/…`, so the moved file is what they render, and `@!SonataAdmin/Block/…`
reaches the shipped template from inside yours. (Pointing `sonata_block.templates.block_base`, or a
block's own `template` setting, at a template of your own still works — the setting takes any Twig
path — but it is no longer the only way.)

### The `Sonata\Form\` and `Sonata\Twig\` namespaces are gone

The form types are the admin bundle's main functionality — `FormMapper` builds every admin form out
of them — and the flash-message manager and status helper are what the admin layout renders on every
page. Nothing in this fork uses either without the admin bundle, so neither is a bundle of its own
here. If your application names those classes, they moved:

| Was | Is |
|---|---|
| `Sonata\Form\Type\*` | `Sonata\AdminBundle\Form\Type\*` |
| `Sonata\Form\DataTransformer\*` | `Sonata\AdminBundle\Form\DataTransformer\*` |
| `Sonata\Form\EventListener\*` | `Sonata\AdminBundle\Form\EventListener\*` |
| `Sonata\Form\Validator\ErrorElement`, `InlineValidator` | `Sonata\AdminBundle\Validator\*` |
| `Sonata\Form\Validator\Constraints\InlineConstraint` | `Sonata\AdminBundle\Validator\Constraints\InlineConstraint` |
| `Sonata\Form\Test\AbstractWidgetTestCase` | `Sonata\AdminBundle\Test\AbstractWidgetTestCase` |
| `Sonata\Form\Fixtures\StubTranslator` | `Sonata\AdminBundle\Test\StubTranslator` |
| `Sonata\Form\Bridge\Symfony\DependencyInjection\Configuration` | `Sonata\AdminBundle\DependencyInjection\FormConfiguration` |
| `Sonata\Form\Bridge\Symfony\DependencyInjection\SonataFormExtension` | `Sonata\AdminBundle\DependencyInjection\SonataFormExtension` |
| `Sonata\Form\Bridge\Symfony\SonataFormBundle` | deleted |
| `Sonata\Twig\Extension\*Extension` | `Sonata\AdminBundle\Twig\Extension\*Extension` |
| `Sonata\Twig\Extension\FlashMessageRuntime` | `Sonata\AdminBundle\Twig\FlashMessageRuntime` |
| `Sonata\Twig\Extension\StatusRuntime` | `Sonata\AdminBundle\Twig\StatusRuntime` |
| `Sonata\Twig\FlashMessage\*` | `Sonata\AdminBundle\FlashMessage\*` |
| `Sonata\Twig\Status\StatusClassRendererInterface` | `Sonata\AdminBundle\Status\StatusClassRendererInterface` |
| `Sonata\Twig\Node\*` | `Sonata\AdminBundle\Twig\Node\*` |
| `Sonata\Twig\TokenParser\*` | `Sonata\AdminBundle\Twig\TokenParser\*` |
| `Sonata\Twig\Bridge\Symfony\DependencyInjection\Configuration` | `Sonata\AdminBundle\DependencyInjection\TwigConfiguration` |
| `Sonata\Twig\Bridge\Symfony\DependencyInjection\SonataTwigExtension` | `Sonata\AdminBundle\DependencyInjection\SonataTwigExtension` |
| `Sonata\Twig\Bridge\Symfony\SonataTwigBundle` | deleted |

Nothing else about either API moved, deliberately: the service ids are still `sonata.form.*` and
`sonata.twig.*`, the Twig functions are still `sonata_flashmessages_get`,
`sonata_flashmessages_types` and `sonata_flashmessages_class`, the filter is still
`sonata_status_class`, the `sonata.status.renderer` tag is unchanged, `sonata_form:` and
`sonata_twig:` are still their own configuration roots in their own
`config/packages/sonata_{form,twig}.yaml`, and `@SonataForm/…` and `@SonataTwig/…` paths still
resolve — both namespaces are kept as compatibility aliases of the admin bundle's views, where
those templates live as `@SonataAdmin/Form/…` and `@SonataAdmin/FlashMessage/…`, the paths
adminata's own defaults use. Service definitions and templates therefore need no edit.

### `CollectionType` and `NativeCollectionType` changed places

**Read this one before you update any import.** Both classes exist, both are used, and both are now
`Sonata\AdminBundle\Form\Type\`. The short name changed hands:

| Was | Is | Block prefix | What it renders |
|---|---|---|---|
| `Sonata\AdminBundle\Form\Type\CollectionType` | `Sonata\AdminBundle\Form\Type\NativeCollectionType` | `sonata_type_native_collection` | Symfony's own collection type with `add` and `delete` buttons, driven by the `sonata-collection` Stimulus controller |
| `Sonata\Form\Type\CollectionType` | `Sonata\AdminBundle\Form\Type\CollectionType` | `sonata_type_collection` | the association collection, rendered by the storage layer's form theme (`edit`, `inline`, `sortable`) |

A careless import update is silent: `use Sonata\AdminBundle\Form\Type\CollectionType;` still
compiles, the field still builds, and the page renders **the other widget**. So do it in this
order:

1. Every `use Sonata\AdminBundle\Form\Type\CollectionType;` that came from Sonata Admin becomes
   `use Sonata\AdminBundle\Form\Type\NativeCollectionType;`, and the `CollectionType::class`
   references in those files become `NativeCollectionType::class`.
2. Only then, every `use Sonata\Form\Type\CollectionType;` becomes
   `use Sonata\AdminBundle\Form\Type\CollectionType;`, with its `::class` references unchanged.

`grep -rn 'Type\\CollectionType' src` after step 1 and before step 2 should return exactly the
fields you mean to keep on the association widget. The block prefixes did not change, so a Twig
block of yours named `sonata_type_collection_widget` or `sonata_type_native_collection_widget`
still overrides what it always did.

### The `SonataFormBundle` and `SonataTwigBundle` translation domains are gone

Their strings are units of the `SonataAdminBundle` domain now, in `SonataAdminBundle.<locale>.xliff`
with the rest of the admin bundle's. The ids are unchanged; the domain is not. So, if you have them:

- `translations/SonataFormBundle.<locale>.xliff` and `translations/SonataTwigBundle.<locale>.xliff`
  are read by nothing. Move their units into `translations/SonataAdminBundle.<locale>.xliff`.
- A template, form type or service of your own that names either domain —
  `{{ 'message_close'|trans({}, 'SonataTwigBundle') }}`, or
  `'btn_translation_domain' => 'SonataFormBundle'` among a collection field's options — names
  `'SonataAdminBundle'` instead.

`templates/bundles/SonataFormBundle/` and `templates/bundles/SonataTwigBundle/`, if you have them,
no longer override anything either, for the same reason `templates/bundles/SonataBlockBundle/` does
not: Symfony builds those directories from **registered bundles**. Move their files to
`templates/bundles/SonataAdminBundle/`, same relative path — `Form/datepicker.html.twig` stays
`Form/datepicker.html.twig`, `FlashMessage/render.html.twig` stays
`FlashMessage/render.html.twig` — and `@!SonataAdmin/…` reaches the shipped template from inside
yours.

### The `Sonata\Exporter\` namespace is gone

The exporter is what every list page's export menu streams its result set through, and nothing in
this fork exports without the admin bundle, so it is not a library of its own here. The admin
bundle already had an `Exporter\` directory — `Sonata\AdminBundle\Exporter\DataSourceInterface`,
which has not moved — and the whole tree folded under it. If your application type-hints the
exporter, a writer or a source iterator, those are the names to update:

| Was | Is |
|---|---|
| `Sonata\Exporter\ExporterInterface` | `Sonata\AdminBundle\Exporter\ExporterInterface` |
| `Sonata\Exporter\Exporter` | `Sonata\AdminBundle\Exporter\Exporter` |
| `Sonata\Exporter\Handler` | `Sonata\AdminBundle\Exporter\Handler` |
| `Sonata\Exporter\Writer\{WriterInterface,TypedWriterInterface}` | `Sonata\AdminBundle\Exporter\Writer\*` |
| `Sonata\Exporter\Writer\{Csv,Json,Xls,Xlsx,Xml,XmlExcel,Sitemap,GsaFeed,InMemory,FormattedBool}Writer` | `Sonata\AdminBundle\Exporter\Writer\*` (same short names) |
| `Sonata\Exporter\Source\*SourceIterator` (14 of them) | `Sonata\AdminBundle\Exporter\Source\*` (same short names) |
| `Sonata\Exporter\Exception\*` | `Sonata\AdminBundle\Exporter\Exception\*` |
| `Sonata\Exporter\Bridge\Symfony\DependencyInjection\Configuration` | `Sonata\AdminBundle\DependencyInjection\ExporterConfiguration` |
| `Sonata\Exporter\Bridge\Symfony\DependencyInjection\SonataExporterExtension` | `Sonata\AdminBundle\DependencyInjection\SonataExporterExtension` |
| `Sonata\Exporter\Bridge\Symfony\DependencyInjection\Compiler\ExporterCompilerPass` | `Sonata\AdminBundle\DependencyInjection\Compiler\ExporterCompilerPass` |
| `Sonata\Exporter\Bridge\Symfony\SonataExporterBundle` | deleted |

Nothing else about the exporter moved, deliberately: `sonata_exporter:` is still its own
configuration root in its own `config/packages/sonata_exporter.yaml`, the service ids are still
`sonata.exporter.*` (`sonata.exporter.exporter` included, still public and still autowired through
the `Exporter` and `ExporterInterface` aliases), a writer of yours is still tagged
`sonata.exporter.writer`, and the `sonata.exporter.writer.<format>.<setting>` container parameters
are unchanged. So an exporter configuration file and a writer service definition need no edit
beyond the class name in the definition's `class` or `use`.

This merge is the quiet one: the exporter ships no templates and no translations, so there is no
`@Sonata…` namespace to keep as an alias, no `templates/bundles/` directory to move and no
translation domain to re-file. The line in `bundles.php` and the imports above are all of it.

```console
$ grep -rn 'BlockBundle\|SonataFormBundle\|SonataTwigBundle\|SonataExporterBundle\|Sonata\\Form\\\|Sonata\\Twig\\\|Sonata\\Exporter\\' src templates translations config tests
```

must be empty when you are done: it catches the four namespaces, the four bundle classes, the
three override directories and the three translation domains at once.

## U2 — Configuration

Removed nodes — leaving one in `sonata_admin.yaml` is a container build error:

| Node | Why |
|---|---|
| `options.skin` | AdminLTE skins are gone; the theme is light/dark/system |
| `options.use_select2` | no select library; every `<select>` is the browser's |
| `options.use_icheck` | native checkboxes and radios |
| `options.use_bootlint` | Bootstrap is gone |

Changed defaults: the asset lists, `dashboard.blocks[].class` and admin group `class` (Tailwind grid
classes now — `col-span-12`, `md:col-span-4`), and `box_class`, which defaults to an empty string.

New nodes:

| Node | Default | Meaning |
|---|---|---|
| `options.theme.mode` | `system` | `light`, `dark` or `system`; resolved server-side from the `sonata_theme` cookie, so a page never paints the wrong theme first |
| `options.list_row_link` | `true` | clicking a list row opens the object |

Two existing nodes are worth revisiting once you can see the result, because their defaults were
chosen for a different UI:

- `options.list_action_button_content` (`all`) renders the row actions as text buttons. If your
  application ships icon-only actions of its own, `icon` is what matches them.
- `options.default_admin_route` (`show`) is where the identifier column links **and** where a row
  click goes. If most of your admin classes do not define `configureShowFields()`, that is a blank
  page; use `edit`. adminata falls back to the other of `show`/`edit` for an admin that has only
  one of them.

## U3 — Markup vocabulary

Bootstrap and AdminLTE class names are gone from every rewritten template. The replacements are the
`.adm-*` components — `adm-btn`, `adm-card`, `adm-table`, `adm-input`, `adm-badge`, `adm-alert`,
`adm-callout`, `adm-dropdown`, `adm-dialog` and the rest. `assets/css/contract.json` is the
generated list of every one adminata ships, and `assets/css/components/*.css` is where each is
defined and commented.

Sonata's own hooks are **unchanged and frozen**: `sonata-ba-list`, `sonata-ba-list-field*`,
`sonata-link-identifier`, `edit_link`, `view_link`, `delete_link`, `sonata-filter-form`,
`sonata-toggle-filter`, `#list_batch_checkbox` and the rest of the contract in PLAN/02 §8. Select on
those, not on `.adm-*`, if you are writing CSS or a test that has to keep working across releases.

For anything the components do not cover, compile Tailwind yourself: import
`vendor/idct/adminata/assets/css/adminata.css` from your own entry and point `@source` at your
templates. You then get arbitrary utilities in your own markup, and adminata's tokens and
components come with them.

## U4 — Layout overrides

The blocks an application may override are listed in PLAN/02 §5 and are unchanged, with two
exceptions: `admin_lte_skin_class` and `_skin` no longer exist, and a hard-coded `notice` include
should become `{% block notice %}{{ parent() }}{% endblock %}`.

One structural difference bites the sign-in screens: `sonata_nav` is nested **inside**
`sonata_wrapper`. A login template that replaces `sonata_wrapper` wholesale — which is the usual
shape — will find its `sonata_nav` override never renders. Draw that content at the top of your own
wrapper instead.

## U5 — JavaScript

No jQuery, no jquery-ui, no jquery-form, no `window.Admin`. The single entry point is
`window.sonataApplication`, a Stimulus 3 application with an explicit registry; the identifiers,
targets, values and outlets it promises are in `assets/js/__contract__/controllers.json`.

- Modals are the native `<dialog>` element driven by `sonata-modal`. The top layer, the focus trap,
  the backdrop and Escape are the browser's.
- **There is no AJAX form submission anywhere.** `ajaxSubmit` is gone and is not coming back;
  forms post.
- Register your own controllers on `window.sonataApplication`, or run a second Stimulus
  application of your own — both work, and unknown identifiers are ignored by each.

## U6 — Forms

Native selects, and native HTML5 date and time inputs. `bundles/sonataform/*` is gone.

The form types are `Sonata\AdminBundle\Form\Type\` now — the map, and the `CollectionType` /
`NativeCollectionType` swap that is the one silent breakage of this release, are in U1.

`datepicker_options.display.components` is still honoured — it is what decides whether a field is a
`date`, `time` or `datetime-local` input — but `format` is no longer configurable: the type fixes
the wire format, the way Symfony does for `DateType` with `html5: true`. Delete the `format`
options you have; a leftover one is rejected with a message that says which components it saw.

## U7 — Icons

Font Awesome 7 Free, with no v4 or v5 shim. Most v5 names carry over; the v4-only ones do not —
`fa-clock-o` is now `fa-clock`, and similar. Grep your templates for names ending in `-o`.

## U8 — Dark mode

`html.dark` is stamped by the server before the first byte, from the `sonata_theme` cookie, so
there is no flash of the wrong theme. Anything in your own templates or stylesheets that hard-codes
a colour needs a `dark:` variant beside it.

If you write your own dark rules against adminata's `dark` variant, note that it is defined as
`&:where(.dark, .dark *)` — deliberately zero-specificity, so that your rules can override
adminata's. The cost is that a dark rule and the light rule it must beat are an exact specificity
tie, and **source order decides**: put the dark block *after* the declaration it overrides.

## U9 — Templates not yet ported

Thirty-seven templates are inherited from upstream unported and still render Bootstrap markup, which
is now unstyled. Each carries a `{# adminata: not yet ported #}` marker as its first line, they are
listed in `tests/Contract/deferred-templates.txt`, and `DeferredTemplateTest` checks that the list,
the markers and the files on disk agree — so one cannot quietly appear or disappear.

What is in there:

- the eleven `CRUD/Association/edit_*` flows and the `ModelListType` widget that drives them, which
  are deferred because redesigning them without AJAX submission is a design job, not a port;
- the audit trail (`history`, `show_compare` and their bases);
- the ACL screen;
- the mosaic list view and the flat list;
- `preview`, `select_subclass`, `tree`, `search`, the tab menu, the short-object-description
  helper, and seven `Block/*` templates.

They are ported on demand rather than speculatively. Open an issue when you need one.
