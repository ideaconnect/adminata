# adminata

**Sonata Admin, re-skinned.** adminata is a **hard fork** of seven `sonata-project` packages with
their Twig templates, CSS and JavaScript replaced by a [Tailwind CSS](https://tailwindcss.com) v4 /
[TailAdmin](https://tailadmin.com) user interface: light and dark mode, a collapsible sidebar,
cards, and modern forms. **No Bootstrap, no AdminLTE, no jQuery.**

One repository, one Composer package. It installs *in place of* the Sonata packages through
Composer's `replace`, so admin classes, configuration and persistence bundles — including
[`idct/sonata-admin-mongodb-bundle`](https://github.com/ideaconnect/sonata-admin-mongodb-bundle) —
keep working unchanged. The exceptions are `block-bundle`, `exporter`, `form-extensions` and
`twig-extensions`, whose sources now live inside adminata's admin bundle under the
`Sonata\AdminBundle\` namespace: see
[What lives inside the admin bundle](#what-lives-inside-the-admin-bundle) below.

> **Status: 1.0 is written and unreleased.** Every milestone of
> [PROJECT_PLAN.md](PROJECT_PLAN.md) is implemented and green, and a 46-admin production panel runs
> on it — see [MIGRATION.md](MIGRATION.md). What is left is the release itself: the tag waits on the
> acceptance sign-off, so `idct/adminata` is not on Packagist yet and installs from a VCS or path
> repository. [PLAN/README.md](PLAN/README.md) is the design it implements.

## Packages replaced

| Upstream package | Version | Directory | Namespace | Bundle class |
|---|---|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | [packages/admin-bundle](packages/admin-bundle) | `Sonata\AdminBundle\` | `SonataAdminBundle` |
| `sonata-project/doctrine-orm-admin-bundle` | 4.21.0 | [packages/doctrine-orm-admin-bundle](packages/doctrine-orm-admin-bundle) | `Sonata\DoctrineORMAdminBundle\` | `SonataDoctrineORMAdminBundle` |

Five more packages were forked and are **not** replaced — they are part of the admin bundle:

| Upstream package | Version | Where it lives now | Namespace | Bundle class |
|---|---|---|---|---|
| `sonata-project/block-bundle` | 5.4.0 | inside [packages/admin-bundle](packages/admin-bundle) | `Sonata\AdminBundle\` | none — `SonataAdminBundle` registers it |
| `sonata-project/doctrine-extensions` | 2.6.0 | inside [packages/admin-bundle](packages/admin-bundle) | `Sonata\AdminBundle\Doctrine\` | none — `SonataAdminBundle` registers it |
| `sonata-project/exporter` | 3.4.0 | inside [packages/admin-bundle](packages/admin-bundle) | `Sonata\AdminBundle\Exporter\` | none — `SonataAdminBundle` registers it |
| `sonata-project/form-extensions` | 2.7.0 | inside [packages/admin-bundle](packages/admin-bundle) | `Sonata\AdminBundle\` | none — `SonataAdminBundle` registers it |
| `sonata-project/twig-extensions` | 2.6.0 | inside [packages/admin-bundle](packages/admin-bundle) | `Sonata\AdminBundle\` | none — `SonataAdminBundle` registers it |

`SonataUserBundle`, the PHPCR admin bundle and every other Sonata package are out of scope.
Exact imported commits and the upstream sync process: [UPSTREAM.md](UPSTREAM.md).

## What lives inside the admin bundle

Four of the seven forked trees are not packages of their own here. Blocks are the admin dashboard
and the way `sonata_block_render_event` puts an application's markup on an admin page; the form
types are what `FormMapper` builds every admin form out of; the flash-message manager and the
status helper are what the admin layout renders on every page; the exporter is what every list
page's export menu streams its result set through. Nothing in this fork uses any of them without
the admin bundle, and adminata ships that functionality integrally, so none of them is a bundle of
its own.

### Blocks

What that means:

- The classes are `Sonata\AdminBundle\` (`Block\`, `Exception\`, `Model\`, `Twig\`, `Util\` …),
  not `Sonata\BlockBundle\`. **This is a breaking change** for an application that names those
  classes; the map is in [CHANGELOG.md](CHANGELOG.md).
- There is no `SonataBlockBundle` class and no line for it in `bundles.php`. `SonataAdminBundle`
  registers the `sonata_block` extension itself.
- `sonata_block:` is still its own configuration root, in its own
  `config/packages/sonata_block.yaml` — unchanged, including the `sonata.admin.block.admin_list`
  context an admin panel needs.
- Every service id (`sonata.block.*`) and Twig function (`sonata_block_render`,
  `sonata_block_render_event`, `sonata_block_exists`, `sonata_block_include_javascripts`,
  `sonata_block_include_stylesheets`) is unchanged.
- The block templates are `@SonataAdmin/Block/…`, and that is what every default inside adminata
  says — the block services' `template` settings, `sonata_block.templates.*`, the profiler, the
  exception renderers — so a block template is overridden like any other admin template, in
  `templates/bundles/SonataAdminBundle/Block/`, with `@!SonataAdmin/Block/…` reaching the shipped
  file. `@SonataBlock/…` still resolves, as a compatibility alias of the same directory for
  templates outside adminata; `templates/bundles/SonataBlockBundle/` is read by nothing.
- The block strings are in the `SonataAdminBundle` translation domain, with the admin bundle's own;
  there is no `SonataBlockBundle` domain. **Breaking** for an application that overrides them in
  `translations/SonataBlockBundle.<locale>.xliff`: the units move to
  `translations/SonataAdminBundle.<locale>.xliff`, ids unchanged ([UPGRADE-1.0.md](UPGRADE-1.0.md)
  §U1).
- adminata **conflicts** with `sonata-project/block-bundle` instead of replacing it: it provides
  that API under a different namespace, so the two cannot be installed side by side.

### Form types and Twig helpers

The same, for `form-extensions` and `twig-extensions` (owner directive, 2026-09-06 — they are the
admin bundle's main functionality, and adminata ships it integrally):

- The classes are `Sonata\AdminBundle\` — `Form\Type\`, `Form\DataTransformer\`,
  `Form\EventListener\`, `Validator\`, `Test\`, `FlashMessage\`, `Status\`, `Twig\` — not
  `Sonata\Form\` or `Sonata\Twig\`. **This is a breaking change** for an application that names
  those classes; the map is in [CHANGELOG.md](CHANGELOG.md).
- **Two `CollectionType`s exchanged names**, which is the one change that breaks silently:
  `Sonata\AdminBundle\Form\Type\CollectionType` (`sonata_type_native_collection`) is now
  `NativeCollectionType`, and `Sonata\Form\Type\CollectionType` (`sonata_type_collection`) took the
  short name. The same import renders the other widget. Table and order of operations:
  [UPGRADE-1.0.md](UPGRADE-1.0.md) §U1.
- There is no `SonataFormBundle` and no `SonataTwigBundle` class, and no line for either in
  `bundles.php`. `SonataAdminBundle` registers `SonataFormExtension` and `SonataTwigExtension`
  itself.
- `sonata_form:` and `sonata_twig:` are still their own configuration roots, in their own
  `config/packages/sonata_{form,twig}.yaml` — unchanged.
- Every service id (`sonata.form.*`, `sonata.twig.*`), Twig function (`sonata_flashmessages_get`,
  `sonata_flashmessages_types`, `sonata_flashmessages_class`), filter (`sonata_status_class`) and
  tag (`sonata.status.renderer`) is unchanged.
- `Form/datepicker.html.twig` and `FlashMessage/render.html.twig` are `@SonataAdmin/…`, and that is
  what every default inside adminata says, so they are overridden like any other admin template in
  `templates/bundles/SonataAdminBundle/`. `@SonataForm/…` and `@SonataTwig/…` still resolve, as
  compatibility aliases of the same directory for templates outside adminata;
  `templates/bundles/SonataFormBundle/` and `templates/bundles/SonataTwigBundle/` are read by
  nothing.
- Their strings are in the `SonataAdminBundle` translation domain; there is no `SonataFormBundle`
  and no `SonataTwigBundle` domain. **Breaking** for an application that overrides one of the eight
  units (`link_add`, `label_type_yes`, `label_type_no`, `date_range_start`, `date_range_end`,
  `message_close`, `more`, `less`): they move to `translations/SonataAdminBundle.<locale>.xliff`,
  ids unchanged ([UPGRADE-1.0.md](UPGRADE-1.0.md) §U1).
- adminata **conflicts** with `sonata-project/form-extensions` and
  `sonata-project/twig-extensions` instead of replacing them, for the same reason as
  `block-bundle`.

### The exporter

The same again, for `exporter` (owner directive, 2026-09-07 — "i consider exporter also an integral
part, no point of making it a separate lib"):

- The classes are `Sonata\AdminBundle\Exporter\` — `Exporter`, `ExporterInterface`, `Handler`,
  `Source\`, `Writer\`, `Exception\` — not `Sonata\Exporter\`. **This is a breaking change** for
  an application that type-hints the exporter, a writer or a source iterator; the map is in
  [CHANGELOG.md](CHANGELOG.md). The admin bundle's own `Exporter\DataSourceInterface`, which the
  storage layer implements, is where it always was.
- There is no `SonataExporterBundle` class and no line for it in `bundles.php`.
  `SonataAdminBundle` registers `SonataExporterExtension` and the writer-collecting compiler pass
  itself.
- `sonata_exporter:` is still its own configuration root, in its own
  `config/packages/sonata_exporter.yaml` — unchanged.
- Every service id (`sonata.exporter.*`, including the public `sonata.exporter.exporter` and the
  `sonata.exporter.writer.<format>` services), the `sonata.exporter.writer` tag and the
  `sonata.exporter.writer.*.*` container parameters are unchanged.
- Nothing else to move: the exporter ships no templates and no translations, so there is no Twig
  namespace to alias and no translation domain to merge.
- adminata **conflicts** with `sonata-project/exporter` instead of replacing it, for the same
  reason as the other three.

## What is preserved

Everything the PHP side of an application touches:

- The three replaced packages' **namespaces** and **bundle classes**, all seven **configuration
  roots** (`sonata_admin`, `sonata_block`, `sonata_doctrine`, `sonata_doctrine_orm_admin`,
  `sonata_exporter`, `sonata_form`, `sonata_twig`) and all five **Twig namespaces**
  (`@SonataAdmin`, `@SonataBlock`, `@SonataForm`, `@SonataTwig`, `@SonataDoctrineORMAdmin`) —
  the middle three now as compatibility aliases of the admin bundle's view directory, which is
  where those templates moved and where `@SonataAdmin/…` addresses them. The four lines an
  existing `config/bundles.php` loses are `SonataBlockBundle`, `SonataExporterBundle`,
  `SonataFormBundle` and `SonataTwigBundle`.
- All **service ids**, tags, compiler passes and container parameters.
- All **routes** (`sonata_admin_*`) and their JSON contracts, and the three replaced packages'
  translation domains and catalogues.
- All **template file paths** and template-registry keys, so `templates/bundles/SonataAdminBundle/`
  overrides keep resolving (the block templates' included), and the **Twig block names** of the
  rewritten templates.
- The published asset path `public/bundles/sonataadmin/`.

## What is not preserved

Bootstrap and AdminLTE class names, AdminLTE skins, **jQuery** and every jQuery plugin (select2,
iCheck, x-editable, jquery-form and its `ajaxSubmit` feature for association widgets), Tempus
Dominus, the `window.Admin` facade, and the `sonata_admin.options.{skin,use_select2,use_icheck,use_bootlint}`
configuration nodes. Applications that overrode Sonata's Bootstrap markup port those overrides once.
On the PHP side, the `Sonata\BlockBundle\`, `Sonata\Exporter\`, `Sonata\Form\` and
`Sonata\Twig\` namespaces, the `SonataBlockBundle`, `SonataExporterBundle`, `SonataFormBundle` and
`SonataTwigBundle` classes, and the three translation domains that carried the first, third and
fourth of those names — the exporter shipped none. On the Twig side,
`templates/bundles/SonataBlockBundle/`, `templates/bundles/SonataFormBundle/` and
`templates/bundles/SonataTwigBundle/` as override directories (see
[What lives inside the admin bundle](#what-lives-inside-the-admin-bundle)).

## Requirements

PHP `^8.4`, Symfony `^7.4 || ^8.0`, Twig `^3.28`.

## Installation

```bash
composer require idct/adminata
```

Register the bundles in `config/bundles.php`:

```php
Sonata\AdminBundle\SonataAdminBundle::class => ['all' => true],
Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle::class => ['all' => true],
```

Two lines, not seven: there is no `SonataBlockBundle`, `SonataDoctrineBundle`,
`SonataExporterBundle`, `SonataFormBundle` or `SonataTwigBundle` to register — `SonataAdminBundle`
brings the block, Doctrine, form, Twig-helper and exporter services and the `sonata_block`,
`sonata_form`, `sonata_twig` and `sonata_exporter` configuration roots with it. Then publish the
assets:

```bash
bin/console assets:install public
```

The documentation site is `docs/`; build it with `make docs`, which needs nothing installed but
Docker. It merges the packages' Sphinx trees with adminata's own pages — theming, icons, the
JavaScript API, compiling Tailwind yourself, and what is not yet ported — with the block, form,
Twig-helper and exporter pages inside the admin bundle's section, the way their sources are inside
the bundle.

Migrating an application that already runs Sonata Admin: [UPGRADE-1.0.md](UPGRADE-1.0.md) for the
generic notes, and [MIGRATION.md](MIGRATION.md) for the checklist as it was actually executed
against a 46-admin production panel, with what each step turned out to involve.

## Theming

```yaml
# config/packages/sonata_admin.yaml
sonata_admin:
    theme:
        mode: system   # light | dark | system (default)
```

The mode is stamped server-side from the `sonata_theme` cookie, so there is no flash of the wrong
theme. Applications can re-theme without rebuilding by redefining the `--color-brand-*` custom
properties, or compile Tailwind themselves against adminata's templates. See
[docs/theming.rst](docs/theming.rst) and [docs/tailwind.rst](docs/tailwind.rst).

## Development

```bash
composer install
make services-up                  # the MySQL the suites and the demo run on
make lint phpstan rector test     # PHP gates
make lint-js test-js assets-build # JavaScript and CSS gates
make test-visual                  # screenshots, axe and html-validate (needs Docker)
make test-functional              # BrowserKit and Panther against the demo
                                  # (needs a geckodriver, or `docker compose up -d selenium`
                                  #  plus PANTHER_SELENIUM_HOST=http://127.0.0.1:4444)
make demo                         # http://127.0.0.1:8000/admin — user "admin", password "admin"
```

`make demo` serves `tests/App`, the application the three package directories are developed against:
two admins over MySQL, deterministic fixtures, and the same pages the functional, Playwright and
accessibility runs drive. `make test-visual` drives it from the pinned
`mcr.microsoft.com/playwright` image — the one that produced the committed baselines under
`tests/Visual/__snapshots__`, since screenshots taken anywhere else differ in their fonts alone.

### Dialogs

adminata ships no modal library. Every dialog is a native `<dialog>` driven by the `sonata-modal`
controller, and applications can use it on their own:

```twig
<div {{ stimulus_controller('sonata-modal', {size: 'lg', closable: true}) }}>
    <button type="button" {{ stimulus_action('sonata-modal', 'open', 'click') }}>Open</button>

    <dialog aria-labelledby="my-dialog-title" {{ stimulus_target('sonata-modal', 'dialog') }}>
        <div class="adm-dialog__header">
            <h2 class="adm-card-title" id="my-dialog-title">Title</h2>
            <button type="button" aria-label="Close" {{ stimulus_action('sonata-modal', 'close', 'click') }}>×</button>
        </div>
        <div class="adm-dialog__body">…</div>
    </dialog>
</div>
```

`size` is `sm`, `md`, `lg` or `list`; `closable: false` keeps Escape and the backdrop from closing
it. The browser's top layer supplies the focus trap and the backdrop, so the dialog must stay where
it is written — moving it out of the controller's element would unbind the actions inside it. It
dispatches `sonata-modal:opened` and `sonata-modal:closed`.

Conventions and the contract every change must keep: [AGENTS.md](AGENTS.md) and
[CONTRIBUTING.md](CONTRIBUTING.md).

## Licence

MIT. adminata bundles or derives from Sonata Admin (MIT), TailAdmin (MIT), Stimulus (MIT), qs
(BSD-3-Clause), Tailwind CSS (MIT), Font Awesome Free (CC BY 4.0 / OFL / MIT) and Outfit (OFL).
See [LICENSE](LICENSE) and [NOTICE](NOTICE).
