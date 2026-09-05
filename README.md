# adminata

**Sonata Admin, re-skinned.** adminata is a **hard fork** of seven `sonata-project` packages with
their Twig templates, CSS and JavaScript replaced by a [Tailwind CSS](https://tailwindcss.com) v4 /
[TailAdmin](https://tailadmin.com) user interface: light and dark mode, a collapsible sidebar,
cards, and modern forms. **No Bootstrap, no AdminLTE, no jQuery.**

One repository, one Composer package. It installs *in place of* the Sonata packages through
Composer's `replace`, so admin classes, configuration and persistence bundles — including
[`idct/sonata-admin-mongodb-bundle`](https://github.com/ideaconnect/sonata-admin-mongodb-bundle) —
keep working unchanged.

> **Status: pre-1.0, under construction.** Follow [PROJECT_PLAN.md](PROJECT_PLAN.md) for what is
> done and [PLAN/README.md](PLAN/README.md) for the design it implements.

## Packages replaced

| Upstream package | Version | Directory | Namespace | Bundle class |
|---|---|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | [packages/admin-bundle](packages/admin-bundle) | `Sonata\AdminBundle\` | `SonataAdminBundle` |
| `sonata-project/block-bundle` | 5.4.0 | [packages/block-bundle](packages/block-bundle) | `Sonata\BlockBundle\` | `SonataBlockBundle` |
| `sonata-project/doctrine-extensions` | 2.6.0 | [packages/doctrine-extensions](packages/doctrine-extensions) | `Sonata\Doctrine\` | `SonataDoctrineBundle` |
| `sonata-project/doctrine-orm-admin-bundle` | 4.21.0 | [packages/doctrine-orm-admin-bundle](packages/doctrine-orm-admin-bundle) | `Sonata\DoctrineORMAdminBundle\` | `SonataDoctrineORMAdminBundle` |
| `sonata-project/exporter` | 3.4.0 | [packages/exporter](packages/exporter) | `Sonata\Exporter\` | `SonataExporterBundle` |
| `sonata-project/form-extensions` | 2.7.0 | [packages/form-extensions](packages/form-extensions) | `Sonata\Form\` | `SonataFormBundle` |
| `sonata-project/twig-extensions` | 2.6.0 | [packages/twig-extensions](packages/twig-extensions) | `Sonata\Twig\` | `SonataTwigBundle` |

`SonataUserBundle`, the PHPCR admin bundle and every other Sonata package are out of scope.
Exact imported commits and the upstream sync process: [UPSTREAM.md](UPSTREAM.md).

## What is preserved

Everything the PHP side of an application touches:

- The seven **namespaces**, **bundle classes**, **configuration roots** (`sonata_admin`,
  `sonata_block`, `sonata_doctrine`, `sonata_doctrine_orm_admin`, `sonata_exporter`, `sonata_form`,
  `sonata_twig`) and **Twig namespaces** (`@SonataAdmin`, `@SonataBlock`, `@SonataForm`,
  `@SonataTwig`, `@SonataDoctrineORMAdmin`). An existing `config/bundles.php` does not change.
- All **service ids**, tags, compiler passes and container parameters.
- All **routes** (`sonata_admin_*`), their JSON contracts, and the translation domains and
  catalogues.
- All **template file paths** and template-registry keys, so `templates/bundles/SonataAdminBundle/`
  overrides keep resolving, and the **Twig block names** of the rewritten templates.
- The published asset path `public/bundles/sonataadmin/`.

## What is not preserved

Bootstrap and AdminLTE class names, AdminLTE skins, **jQuery** and every jQuery plugin (select2,
iCheck, x-editable, jquery-form and its `ajaxSubmit` feature for association widgets), Tempus
Dominus, the `window.Admin` facade, and the `sonata_admin.options.{skin,use_select2,use_icheck,use_bootlint}`
configuration nodes. Applications that overrode Sonata's Bootstrap markup port those overrides once.

## Requirements

PHP `^8.4`, Symfony `^7.4 || ^8.0`, Twig `^3.28`.

## Installation

```bash
composer require idct/adminata
```

Register the bundles in `config/bundles.php`:

```php
Sonata\Twig\Bridge\Symfony\SonataTwigBundle::class => ['all' => true],
Sonata\Form\Bridge\Symfony\SonataFormBundle::class => ['all' => true],
Sonata\Exporter\Bridge\Symfony\SonataExporterBundle::class => ['all' => true],
Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle::class => ['all' => true],
Sonata\BlockBundle\SonataBlockBundle::class => ['all' => true],
Sonata\AdminBundle\SonataAdminBundle::class => ['all' => true],
Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle::class => ['all' => true],
```

then publish the assets:

```bash
bin/console assets:install public
```

Migrating an application that already runs Sonata Admin: [MIGRATION.md](MIGRATION.md) and
[UPGRADE-1.0.md](UPGRADE-1.0.md) (written in phase 5).

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
[PLAN/04-css-architecture.md](PLAN/04-css-architecture.md) until the documentation site exists.

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

`make demo` serves `tests/App`, the application the seven packages are developed against: two
admins over MySQL, deterministic fixtures, and the same pages the functional, Playwright and
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
