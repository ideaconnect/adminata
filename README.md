# adminata

**Sonata Admin, re-skinned.** adminata is a **hard fork** of seven `sonata-project` packages with
their Twig templates, CSS and JavaScript replaced by a [Tailwind CSS](https://tailwindcss.com) v4 /
[TailAdmin](https://tailadmin.com) user interface: light and dark mode, a collapsible sidebar,
cards, and modern forms. **No Bootstrap, no AdminLTE, no jQuery.**

One repository, one Composer package, one bundle. It installs *in place of*
`sonata-project/admin-bundle` through Composer's `replace`, so admin classes, configuration and
persistence bundles — including
[`idct/sonata-admin-mongodb-bundle`](https://github.com/ideaconnect/sonata-admin-mongodb-bundle) —
keep working unchanged. `block-bundle`, `doctrine-extensions`, `exporter`, `form-extensions` and
`twig-extensions` are inside this bundle under the `IDCT\Adminata\` namespace rather than
packages of their own: see
[What lives inside the admin bundle](#what-lives-inside-the-admin-bundle) below. The storage layers
are the other way round — one package per backend, installed alongside this one.

> **Status: 1.0 is written and unreleased.** Every milestone of
> [PROJECT_PLAN.md](PROJECT_PLAN.md) is implemented and green, and a 46-admin production panel runs
> on it — see [MIGRATION.md](MIGRATION.md). What is left is the release itself: the tag waits on the
> acceptance sign-off, so `idct/adminata` is not on Packagist yet and installs from a VCS or path
> repository. [PLAN/README.md](PLAN/README.md) is the design it implements.

## Packages replaced

This repository is one Composer package and one bundle. Its sources are [src](src) and its suite is
[tests](tests) — the same layout upstream uses, which is what lets an upstream diff apply here with
no directory prefix. It replaces one package:

| Upstream package | Version | Namespace | Bundle class |
|---|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | `IDCT\Adminata\` | `AdminataBundle` |

Five more were forked and are **not** replaced — they are part of that bundle, so `composer.json`
`conflict`s with them instead:

| Upstream package | Version | Namespace here | Bundle class |
|---|---|---|---|
| `sonata-project/block-bundle` | 5.4.0 | `IDCT\Adminata\` | none — `AdminataBundle` registers it |
| `sonata-project/doctrine-extensions` | 2.6.0 | `IDCT\Adminata\Doctrine\` | none — `AdminataBundle` registers it |
| `sonata-project/exporter` | 3.4.0 | `IDCT\Adminata\Exporter\` | none — `AdminataBundle` registers it |
| `sonata-project/form-extensions` | 2.7.0 | `IDCT\Adminata\Form\` | none — `AdminataBundle` registers it |
| `sonata-project/twig-extensions` | 2.6.0 | `IDCT\Adminata\Twig\` | none — `AdminataBundle` registers it |

The seventh is a storage layer and ships separately, because a panel needs one of them and not the
others:

| Backend | Package | Replaces |
|---|---|---|
| Doctrine ORM | [`idct/adminata-doctrine-orm-admin-bundle`](https://github.com/ideaconnect/adminata-doctrine-orm-admin-bundle) | `sonata-project/doctrine-orm-admin-bundle` 4.21.0 |
| Doctrine MongoDB ODM | [`idct/sonata-admin-mongodb-bundle`](https://github.com/ideaconnect/sonata-admin-mongodb-bundle) | `sonata-project/doctrine-mongodb-admin-bundle` |

`SonataUserBundle`, the PHPCR admin bundle and every other Sonata package are out of scope.
Exact imported commits and the upstream sync process: [UPSTREAM.md](UPSTREAM.md).

## What lives inside the admin bundle

Five of the seven forked trees are not packages of their own here. Blocks are the admin dashboard
and the way `adminata_block_render_event` puts an application's markup on an admin page; the form
types are what `FormMapper` builds every admin form out of; the flash-message manager and the
status helper are what the admin layout renders on every page; the exporter is what every list
page's export menu streams its result set through; the Doctrine manager, adapter and mapper layer
is what every admin's storage sits on. Nothing in this fork uses any of them without
the admin bundle, and adminata ships that functionality integrally, so none of them is a bundle of
its own.

### Blocks

What that means:

- The classes are `IDCT\Adminata\` (`Block\`, `Exception\`, `Model\`, `Twig\`, `Util\` …),
  not `IDCT\Adminata\`. **This is a breaking change** for an application that names those
  classes; the map is in [CHANGELOG.md](CHANGELOG.md).
- There is no `SonataBlockBundle` class and no line for it in `bundles.php`. `AdminataBundle`
  registers the `adminata_block` extension itself.
- `adminata_block:` is still its own configuration root, in its own
  `config/packages/adminata_block.yaml` — unchanged, including the `adminata.admin.block.admin_list`
  context an admin panel needs.
- Every service id (`adminata.block.*`) and Twig function (`adminata_block_render`,
  `adminata_block_render_event`, `adminata_block_exists`, `adminata_block_include_javascripts`,
  `adminata_block_include_stylesheets`) is unchanged.
- The block templates are `@Adminata/Block/…`, and that is what every default inside adminata
  says — the block services' `template` settings, `adminata_block.templates.*`, the profiler, the
  exception renderers — so a block template is overridden like any other admin template, in
  `templates/bundles/AdminataBundle/Block/`, with `@!Adminata/Block/…` reaching the shipped
  file. `@Adminata/…` still resolves, as a compatibility alias of the same directory for
  templates outside adminata; `templates/bundles/SonataBlockBundle/` is read by nothing.
- The block strings are in the `AdminataBundle` translation domain, with the admin bundle's own;
  there is no `SonataBlockBundle` domain. **Breaking** for an application that overrides them in
  `translations/SonataBlockBundle.<locale>.xliff`: the units move to
  `translations/AdminataBundle.<locale>.xliff`, ids unchanged ([UPGRADE-1.0.md](UPGRADE-1.0.md)
  §U1).
- adminata **conflicts** with `sonata-project/block-bundle` instead of replacing it: it provides
  that API under a different namespace, so the two cannot be installed side by side.

### Form types and Twig helpers

The same, for `form-extensions` and `twig-extensions` (owner directive, 2026-09-06 — they are the
admin bundle's main functionality, and adminata ships it integrally):

- The classes are `IDCT\Adminata\` — `Form\Type\`, `Form\DataTransformer\`,
  `Form\EventListener\`, `Validator\`, `Test\`, `FlashMessage\`, `Status\`, `Twig\` — not
  `IDCT\Adminata\Form\` or `IDCT\Adminata\Twig\`. **This is a breaking change** for an application that names
  those classes; the map is in [CHANGELOG.md](CHANGELOG.md).
- **Two `CollectionType`s exchanged names**, which is the one change that breaks silently:
  `IDCT\Adminata\Form\Type\CollectionType` (`adminata_type_native_collection`) is now
  `NativeCollectionType`, and `IDCT\Adminata\Form\Type\CollectionType` (`adminata_type_collection`) took the
  short name. The same import renders the other widget. Table and order of operations:
  [UPGRADE-1.0.md](UPGRADE-1.0.md) §U1.
- There is no `SonataFormBundle` and no `SonataTwigBundle` class, and no line for either in
  `bundles.php`. `AdminataBundle` registers `AdminataFormExtension` and `AdminataTwigExtension`
  itself.
- `adminata_form:` and `adminata_twig:` are still their own configuration roots, in their own
  `config/packages/adminata_{form,twig}.yaml` — unchanged.
- Every service id (`adminata.form.*`, `adminata.twig.*`), Twig function (`adminata_flashmessages_get`,
  `adminata_flashmessages_types`, `adminata_flashmessages_class`), filter (`adminata_status_class`) and
  tag (`adminata.status.renderer`) is unchanged.
- `Form/datepicker.html.twig` and `FlashMessage/render.html.twig` are `@Adminata/…`, and that is
  what every default inside adminata says, so they are overridden like any other admin template in
  `templates/bundles/AdminataBundle/`. `@Adminata/…` and `@Adminata/…` still resolve, as
  compatibility aliases of the same directory for templates outside adminata;
  `templates/bundles/SonataFormBundle/` and `templates/bundles/SonataTwigBundle/` are read by
  nothing.
- Their strings are in the `AdminataBundle` translation domain; there is no `SonataFormBundle`
  and no `SonataTwigBundle` domain. **Breaking** for an application that overrides one of the eight
  units (`link_add`, `label_type_yes`, `label_type_no`, `date_range_start`, `date_range_end`,
  `message_close`, `more`, `less`): they move to `translations/AdminataBundle.<locale>.xliff`,
  ids unchanged ([UPGRADE-1.0.md](UPGRADE-1.0.md) §U1).
- adminata **conflicts** with `sonata-project/form-extensions` and
  `sonata-project/twig-extensions` instead of replacing them, for the same reason as
  `block-bundle`.

### The exporter

The same again, for `exporter` (owner directive, 2026-09-07 — "i consider exporter also an integral
part, no point of making it a separate lib"):

- The classes are `IDCT\Adminata\Exporter\` — `Exporter`, `ExporterInterface`, `Handler`,
  `Source\`, `Writer\`, `Exception\` — not `IDCT\Adminata\Exporter\`. **This is a breaking change** for
  an application that type-hints the exporter, a writer or a source iterator; the map is in
  [CHANGELOG.md](CHANGELOG.md). The admin bundle's own `Exporter\DataSourceInterface`, which the
  storage layer implements, is where it always was.
- There is no `SonataExporterBundle` class and no line for it in `bundles.php`.
  `AdminataBundle` registers `AdminataExporterExtension` and the writer-collecting compiler pass
  itself.
- `adminata_exporter:` is still its own configuration root, in its own
  `config/packages/adminata_exporter.yaml` — unchanged.
- Every service id (`adminata.exporter.*`, including the public `adminata.exporter.exporter` and the
  `adminata.exporter.writer.<format>` services), the `adminata.exporter.writer` tag and the
  `adminata.exporter.writer.*.*` container parameters are unchanged.
- Nothing else to move: the exporter ships no templates and no translations, so there is no Twig
  namespace to alias and no translation domain to merge.
- adminata **conflicts** with `sonata-project/exporter` instead of replacing it, for the same
  reason as the other three.

## What is preserved

Everything the PHP side of an application touches:

- The three replaced packages' **namespaces** and **bundle classes**, all seven **configuration
  roots** (`adminata`, `adminata_block`, `adminata_doctrine`, `adminata_doctrine_orm`,
  `adminata_exporter`, `adminata_form`, `adminata_twig`) and all five **Twig namespaces**
  (`@Adminata`, `@Adminata`, `@Adminata`, `@Adminata`, `@AdminataDoctrineORM`) —
  the middle three now as compatibility aliases of the admin bundle's view directory, which is
  where those templates moved and where `@Adminata/…` addresses them. The four lines an
  existing `config/bundles.php` loses are `SonataBlockBundle`, `SonataExporterBundle`,
  `SonataFormBundle` and `SonataTwigBundle`.
- All **service ids**, tags, compiler passes and container parameters.
- All **routes** (`adminata_admin_*`) and their JSON contracts, and the three replaced packages'
  translation domains and catalogues.
- All **template file paths** and template-registry keys, so `templates/bundles/AdminataBundle/`
  overrides keep resolving (the block templates' included), and the **Twig block names** of the
  rewritten templates.
- The published asset path `public/bundles/adminata/`.

## What is not preserved

Bootstrap and AdminLTE class names, AdminLTE skins, **jQuery** and every jQuery plugin (select2,
iCheck, x-editable, jquery-form and its `ajaxSubmit` feature for association widgets), Tempus
Dominus, the `window.Admin` facade, and the `adminata.options.{skin,use_select2,use_icheck,use_bootlint}`
configuration nodes. Applications that overrode Sonata's Bootstrap markup port those overrides once.
On the PHP side, the `IDCT\Adminata\`, `IDCT\Adminata\Exporter\`, `IDCT\Adminata\Form\` and
`IDCT\Adminata\Twig\` namespaces, the `SonataBlockBundle`, `SonataExporterBundle`, `SonataFormBundle` and
`SonataTwigBundle` classes, and the three translation domains that carried the first, third and
fourth of those names — the exporter shipped none. On the Twig side,
`templates/bundles/SonataBlockBundle/`, `templates/bundles/SonataFormBundle/` and
`templates/bundles/SonataTwigBundle/` as override directories (see
[What lives inside the admin bundle](#what-lives-inside-the-admin-bundle)).

## Requirements

PHP `^8.4`, Symfony `^7.4 || ^8.0`, Twig `^3.28`.

## Installation

```bash
composer require idct/adminata idct/adminata-doctrine-orm-admin-bundle
```

Neither is on Packagist yet, so add the repositories they install from first — a `path` one for a
checkout beside your project, or the `vcs` ones:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/ideaconnect/adminata.git" },
    { "type": "vcs", "url": "https://github.com/ideaconnect/adminata-doctrine-orm-admin-bundle.git" }
]
```

For MongoDB, take [`idct/sonata-admin-mongodb-bundle`](https://github.com/ideaconnect/sonata-admin-mongodb-bundle)
instead of, or alongside, the ORM package.

Register the bundles in `config/bundles.php`:

```php
IDCT\Adminata\AdminataBundle::class => ['all' => true],
IDCT\Adminata\DoctrineORM\AdminataDoctrineORMBundle::class => ['all' => true],
```

Two lines, not seven: there is no `SonataBlockBundle`, `SonataDoctrineBundle`,
`SonataExporterBundle`, `SonataFormBundle` or `SonataTwigBundle` to register — `AdminataBundle`
brings the block, Doctrine, form, Twig-helper and exporter services and the `adminata_block`,
`adminata_form`, `adminata_twig` and `adminata_exporter` configuration roots with it. Then publish the
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
# config/packages/adminata.yaml
adminata:
    theme:
        mode: system   # light | dark | system (default)
```

The mode is stamped server-side from the `adminata_theme` cookie, so there is no flash of the wrong
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

adminata ships no modal library. Every dialog is a native `<dialog>` driven by the `adminata-modal`
controller, and applications can use it on their own:

```twig
<div {{ stimulus_controller('adminata-modal', {size: 'lg', closable: true}) }}>
    <button type="button" {{ stimulus_action('adminata-modal', 'open', 'click') }}>Open</button>

    <dialog aria-labelledby="my-dialog-title" {{ stimulus_target('adminata-modal', 'dialog') }}>
        <div class="adm-dialog__header">
            <h2 class="adm-card-title" id="my-dialog-title">Title</h2>
            <button type="button" aria-label="Close" {{ stimulus_action('adminata-modal', 'close', 'click') }}>×</button>
        </div>
        <div class="adm-dialog__body">…</div>
    </dialog>
</div>
```

`size` is `sm`, `md`, `lg` or `list`; `closable: false` keeps Escape and the backdrop from closing
it. The browser's top layer supplies the focus trap and the backdrop, so the dialog must stay where
it is written — moving it out of the controller's element would unbind the actions inside it. It
dispatches `adminata-modal:opened` and `adminata-modal:closed`.

Conventions and the contract every change must keep: [AGENTS.md](AGENTS.md) and
[CONTRIBUTING.md](CONTRIBUTING.md).

## Licence

MIT. adminata bundles or derives from Sonata Admin (MIT), TailAdmin (MIT), Stimulus (MIT), qs
(BSD-3-Clause), Tailwind CSS (MIT), Font Awesome Free (CC BY 4.0 / OFL / MIT) and Outfit (OFL).
See [LICENSE](LICENSE) and [NOTICE](NOTICE).
