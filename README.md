# adminata

**An admin bundle for Symfony with a Tailwind CSS v4 / TailAdmin user interface.** adminata is a
**hard fork** of the Sonata Admin stack — seven `sonata-project` packages — with their Twig
templates, CSS and JavaScript replaced by a [Tailwind CSS](https://tailwindcss.com) v4 /
[TailAdmin](https://tailadmin.com) interface: light and dark mode, a collapsible sidebar, cards,
and modern forms. **No Bootstrap, no AdminLTE, no jQuery.** Its code lives under the
`IDCT\Adminata\` namespace and nothing in its API carries the Sonata name.

One repository, one Composer package, one bundle: `IDCT\Adminata\AdminataBundle`. Admin classes,
mappers, the datagrid, routing, security and the exporter are the Sonata design under new names;
`block-bundle`, `doctrine-extensions`, `exporter`, `form-extensions` and `twig-extensions` are
inside this bundle rather than packages of their own. The storage layers are the other way round
— one package per backend, installed alongside this one.

> **Status: 1.0 is written and unreleased.** Every milestone of
> [PROJECT_PLAN.md](PROJECT_PLAN.md) is implemented and green, and a 46-admin production panel runs
> on it — see [MIGRATION.md](MIGRATION.md). What is left is the release itself: the tag waits on the
> acceptance sign-off, so [`idct/adminata`](https://packagist.org/packages/idct/adminata) is on
> Packagist as `dev-main` only. [PLAN/README.md](PLAN/README.md) is the design it implements, and
> [PLAN/v2/README.md](PLAN/v2/README.md) the rename that gave it its own name.

## Origins

adminata began on 2026-09-04 as a hard fork of the Sonata Admin stack. Seven `sonata-project`
packages — `admin-bundle` 4.43.0, `block-bundle` 5.4.0, `doctrine-extensions` 2.6.0,
`doctrine-orm-admin-bundle` 4.21.0, `exporter` 3.4.0, `form-extensions` 2.7.0 and
`twig-extensions` 2.6.0 — were imported with their full git history, and their PHP design is the
foundation of everything here: the admin classes, the mappers, the datagrid, the routing, the
security handlers and the exporter are their work under new names. Their sources keep the
upstream copyright headers.

Since 2026-09-12 the code lives under the `IDCT\Adminata\` namespace and nothing in the API
carries the Sonata name any more. The debt does. We are grateful to Thomas Rabaix, to the Sonata
Project and to its contributors for the years of work since 2010 that made this project possible —
and for the MIT licence that let it happen. The original lives on at
<https://sonata-project.org>; bugs in it belong there, and fixes to the PHP we share still flow
from there through the process in [UPSTREAM.md](UPSTREAM.md).

What was forked from where, at which commit, and what has been synced since:
[UPSTREAM.md](UPSTREAM.md), [NOTICE](NOTICE), [CHANGELOG-sonata.md](CHANGELOG-sonata.md) and the
inherited changelogs under [changelog/](changelog/).

## Packages forked

This repository is one Composer package and one bundle. Its sources are [src](src) and its suite is
[tests](tests) — the same layout upstream uses, which is what lets an upstream diff be translated
and merged here with no directory prefix. It **conflicts** with every package it forked: adminata
provides those APIs under its own names, so an installation cannot hold both.

| Upstream package | Version | Here |
|---|---|---|
| `sonata-project/admin-bundle` | 4.43.0 | `IDCT\Adminata\` — the bundle |
| `sonata-project/block-bundle` | 5.4.0 | `IDCT\Adminata\Block\`, `Model\`, … — `AdminataBundle` registers the `adminata_block` root |
| `sonata-project/doctrine-extensions` | 2.6.0 | `IDCT\Adminata\Doctrine\` |
| `sonata-project/exporter` | 3.4.0 | `IDCT\Adminata\Exporter\` — the `adminata_exporter` root |
| `sonata-project/form-extensions` | 2.7.0 | `IDCT\Adminata\Form\`, `Validator\` — the `adminata_form` root |
| `sonata-project/twig-extensions` | 2.6.0 | `IDCT\Adminata\Twig\`, `FlashMessage\`, `Status\` — the `adminata_twig` root |

The seventh is a storage layer and ships separately, because a panel needs one of them and not the
others:

| Backend | Package | Forked from |
|---|---|---|
| Doctrine ORM | [`idct/adminata-doctrine-orm-admin-bundle`](https://github.com/ideaconnect/adminata-doctrine-orm-admin-bundle) 2.x — `IDCT\Adminata\DoctrineORM\` | `sonata-project/doctrine-orm-admin-bundle` 4.21.0 |
| Doctrine MongoDB ODM | [`idct/adminata-admin-mongodb-bundle`](https://github.com/ideaconnect/adminata-admin-mongodb-bundle) 7.x — `IDCT\Adminata\DoctrineMongoDB\` | `sonata-project/doctrine-mongodb-admin-bundle` |

`SonataUserBundle`, the PHPCR admin bundle and every other Sonata package are out of scope.
Exact imported commits and the upstream sync process: [UPSTREAM.md](UPSTREAM.md).

## What lives inside the admin bundle

Five of the seven forked trees are not packages of their own here. Blocks are the admin dashboard
and the way `adminata_block_render_event` puts an application's markup on an admin page; the form
types are what `FormMapper` builds every admin form out of; the flash-message manager and the
status helper are what the admin layout renders on every page; the exporter is what every list
page's export menu streams its result set through; the Doctrine manager, adapter and mapper layer
is what every admin's storage sits on. Nothing in this fork uses any of them without the admin
bundle, and adminata ships that functionality integrally, so none of them is a bundle of its own:
there is no block, doctrine, exporter, form or twig bundle class to register, and `AdminataBundle`
brings the `adminata_block`, `adminata_form`, `adminata_twig` and `adminata_exporter`
configuration roots, their services and their Twig functions with it.

Two `CollectionType`s exist, and the wrong import compiles and renders the other widget:
`IDCT\Adminata\Form\Type\CollectionType` is form-extensions' (`adminata_type_collection`), and the
admin bundle's own is `NativeCollectionType` (`adminata_type_native_collection`).

## The names

Everything an application touches is adminata's own name, derived from the bundle class where
Symfony derives it and prefixed `adminata` everywhere else:

| Kind | Name |
|---|---|
| Namespace, bundle class | `IDCT\Adminata\`, `IDCT\Adminata\AdminataBundle` |
| Configuration roots | `adminata`, `adminata_block`, `adminata_form`, `adminata_twig`, `adminata_exporter`; `adminata_doctrine_orm`, `adminata_doctrine_mongodb` for the storage layers |
| Service ids, tags, parameters, events | `adminata.admin.pool`, `adminata.admin` (the tag every admin carries), `adminata.block.*`, `adminata.exporter.*`, `adminata.admin.event.*` |
| Routes, console | `adminata_dashboard`, `adminata_search`, …; `adminata:list`, `adminata:explain`, `adminata:setup-acl`, `adminata:generate-object-acl`, `debug:adminata:block`, `make:adminata:admin` |
| Twig | `@Adminata/…` (override in `templates/bundles/AdminataBundle/`), `@AdminataDoctrineORM/…`, `@AdminataDoctrineMongoDB/…`; `adminata_block_render`, `adminata_flashmessages_get`, `adminata_status_class`, …; globals `adminata_config`, `adminata_admin`; blocks `adminata_wrapper`, `adminata_content`, … |
| Form types | `adminata_type_model`, `adminata_type_collection`, … |
| Translations | domain `AdminataBundle` |
| Markup and JavaScript | hooks `adminata-list-field`, `adminata-actions`, …; controllers `adminata-modal`, `adminata-autocomplete`, …; `window.adminataApplication` |
| Cookies, assets | `adminata_theme`, `adminata_sidebar_hide`; `public/bundles/adminata/` |

An application's own names — the id it gives an admin service, a route, a CSS class — are its
own; adminata never owned them and does not rename them. Coming from Sonata Admin, or from
adminata before the rename: [UPGRADE.md](UPGRADE.md) has the whole map and the tool that applies
it, `vendor/bin/adminata-rename`.

## What is not preserved from upstream

Bootstrap and AdminLTE class names, AdminLTE skins, **jQuery** and every jQuery plugin (select2,
iCheck, x-editable, jquery-form and its `ajaxSubmit` feature for association widgets), Tempus
Dominus, the `window.Admin` facade, and the `options.{skin,use_select2,use_icheck,use_bootlint}`
configuration nodes. Applications that overrode Sonata's Bootstrap markup port those overrides
once; [UPGRADE-1.0.md](UPGRADE-1.0.md) is the guide for that side, [UPGRADE.md](UPGRADE.md) for
the names.

## Requirements

PHP `^8.4`, Symfony `^7.4 || ^8.0`, Twig `^3.28`.

## Installation

```bash
composer require idct/adminata:dev-main idct/adminata-doctrine-orm-admin-bundle
```

Both come from Packagist. `dev-main` is adminata's only version there until 1.0 is tagged, and
naming that constraint is what lets a project with `minimum-stability: stable` take it; the
storage layers are tagged and need no constraint of their own.

For MongoDB, take [`idct/adminata-admin-mongodb-bundle`](https://packagist.org/packages/idct/adminata-admin-mongodb-bundle)
instead of, or alongside, the ORM package.

Register the bundles in `config/bundles.php`:

```php
IDCT\Adminata\AdminataBundle::class => ['all' => true],
IDCT\Adminata\DoctrineORM\AdminataDoctrineORMBundle::class => ['all' => true],
```

Then the routes and the assets:

```yaml
# config/routes/adminata.yaml
admin_area:
    resource: '@AdminataBundle/Resources/config/routing/adminata.xml'
    prefix: /admin
```

```bash
bin/console assets:install public
```

The documentation site is `docs/`; build it with `make docs`, which needs nothing installed but
Docker. It merges the packages' Sphinx trees with adminata's own pages — theming, icons, the
JavaScript API, compiling Tailwind yourself, and what is not yet ported — with the block, form,
Twig-helper and exporter pages inside the admin bundle's section, the way their sources are inside
the bundle.

Migrating an application that already runs Sonata Admin: [UPGRADE.md](UPGRADE.md) for the names
and the tool that changes them, [UPGRADE-1.0.md](UPGRADE-1.0.md) for the interface, and
[MIGRATION.md](MIGRATION.md) for the checklist as it was actually executed against a 46-admin
production panel, with what each step turned out to involve.

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
                                  #  plus PANTHER_SELENIUM_HOST=http://127.0.0.1:4444/wd/hub)
make check-names                  # nothing may still carry a Sonata name (the rename engine's gate)
make demo                         # http://127.0.0.1:8000/admin — user "admin", password "admin"
```

`make demo` serves the demo application adminata's own suites drive:
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
