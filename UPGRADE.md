# Upgrading an application to adminata under the IDCT names

adminata's code no longer answers to any Sonata name. The PHP namespace is `IDCT\Adminata\`, the
bundle is `AdminataBundle`, the Twig namespace is `@Adminata`, the configuration root is
`adminata`, and every service id, tag, route, form type, translation domain, markup hook,
Stimulus controller and cookie follows. There is **no compatibility layer**: nothing is aliased,
shimmed or kept "for now". Every row below is an edit in your application. Most of them are
mechanical, and the tool in [§10](#10-the-rename-tool) makes them for you; the rest are listed.

## Who this is for

| Your application runs on | Read |
|---|---|
| adminata **before the rename** — `Sonata\AdminBundle\` namespace, Tailwind interface (an application that followed [UPGRADE-1.0.md](UPGRADE-1.0.md) or [MIGRATION.md](MIGRATION.md) round 1) | this guide, top to bottom |
| `sonata-project/admin-bundle` 4.x and its sibling packages (Bootstrap, jQuery) | this guide first — packages, bundles and every name — then [UPGRADE-1.0.md](UPGRADE-1.0.md) for the interface: templates, JavaScript, CSS, form themes, the removed configuration nodes. Rows that only apply to you are marked **upstream** |

Two things to know before anything else:

- **Your own names are yours.** An admin service you registered as `sonata.admin.post`, a route
  you named `sonata_admin_edit_own_password`, a CSS class you called `sonata-overrides` — these
  are names *you* chose, adminata never owned them, and nothing here requires you to change them.
  The rename tool leaves them alone and lists them for you. One of them is not a rename at all
  but a data migration: see [§6.3](#63-your-own-service-ids-and-the-roles-derived-from-them)
  before you decide.
- **Names are all that changes.** Every class keeps its shape, methods and behaviour; every
  template keeps its file path under the new namespace; every configuration node keeps its
  meaning. If your application worked on adminata yesterday, it works after this guide with the
  same semantics.

## 1. Before you start

- Start from a clean, committed tree on a branch; run your suites once so that you know what
  green looks like.
- Requirements are unchanged: PHP `^8.4`, Symfony `^7.4 || ^8.0`, Twig `^3.28`, Doctrine ORM
  `^3.6` or MongoDB ODM `^2.17`.
- Decide about your own service ids first ([§6.3](#63-your-own-service-ids-and-the-roles-derived-from-them)).
  Keeping them is the default and needs no database change.
- Expect `bin/console cache:clear` to fail between §2 and §5: the container cannot build while
  `bundles.php` and the configuration files disagree. That is normal.

## 2. Composer

Edit `composer.json`, then update the three packages together. `idct/adminata` and the MongoDB
layer are on Packagist now, so the `vcs` entries that named them can leave `repositories`
(`composer config --unset repositories.<name>`); the ORM layer's stays until that package is
published too. A `path` entry for a checkout beside your project is fine for development, but the
lock file must be resolved from Packagist or GitHub, never from a `path` dist.

| Package | Before | After |
|---|---|---|
| `idct/adminata` | `dev-main` | `dev-main`, from Packagist (the rename is on `main`; adminata is untagged until its first release) |
| `idct/adminata-doctrine-orm-admin-bundle` | `^1.0` | `^2.0` |
| MongoDB ODM layer | `idct/sonata-admin-mongodb-bundle` `^6.0` | `idct/adminata-admin-mongodb-bundle` `^7.0` — the renamed package, on Packagist |

```console
$ composer remove --no-update idct/sonata-admin-mongodb-bundle          # MongoDB applications only
$ composer require --no-update idct/adminata:dev-main \
      idct/adminata-doctrine-orm-admin-bundle:^2.0 \
      idct/adminata-admin-mongodb-bundle:^7.0                   # the layers you use
$ composer update idct/adminata idct/adminata-doctrine-orm-admin-bundle idct/adminata-admin-mongodb-bundle
```

None of the three has a Symfony Flex recipe, so nothing is written to or deleted from your
configuration, and `symfony.lock` only gains or loses the MongoDB package's entry.

**upstream** — coming from `sonata-project/*`: run every Composer command with `--no-plugins
--no-scripts`. Removing the `sonata-project/*` packages otherwise makes Flex run their recipes'
`unconfigure`, which deletes `config/packages/sonata_admin.yaml`, `sonata_block.yaml`,
`sonata_form.yaml`, `config/routes/sonata_admin.yaml` and `src/Admin/.gitignore` without a hash
check. Remove the `sonata-project/*` entries from `symfony.lock` by hand afterwards. adminata
**conflicts** with every package it forked (`sonata-project/admin-bundle`, `block-bundle`,
`doctrine-extensions`, `exporter`, `form-extensions`, `twig-extensions`; the ORM and ODM layers
with theirs), so an explicit `require` of any of them refuses to install: remove it.
`sonata-project/entity-audit-bundle` is not forked and stays installable.

## 3. `config/bundles.php`

```diff
-    Sonata\AdminBundle\SonataAdminBundle::class => ['all' => true],
-    Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle::class => ['all' => true],
-    Sonata\DoctrineMongoDBAdminBundle\SonataDoctrineMongoDBAdminBundle::class => ['all' => true],
+    IDCT\Adminata\AdminataBundle::class => ['all' => true],
+    IDCT\Adminata\DoctrineORM\AdminataDoctrineORMBundle::class => ['all' => true],
+    IDCT\Adminata\DoctrineMongoDB\AdminataDoctrineMongoDBBundle::class => ['all' => true],
```

Register only the storage layers you use. **upstream** — also delete the lines for
`SonataBlockBundle`, `SonataDoctrineBundle`, `SonataExporterBundle`, `SonataFormBundle` and
`SonataTwigBundle`: those bundles do not exist; `AdminataBundle` registers the block, Doctrine,
exporter, form and Twig-helper services itself.

## 4. Configuration

Rename the files and their root keys. Every node *inside* a root keeps its name; only values
that name something adminata owns change (next table).

| Before | After |
|---|---|
| `config/packages/sonata_admin.yaml`, root `sonata_admin:` | `config/packages/adminata.yaml`, root `adminata:` |
| `sonata_block.yaml`, `sonata_block:` | `adminata_block.yaml`, `adminata_block:` |
| `sonata_form.yaml`, `sonata_form:` | `adminata_form.yaml`, `adminata_form:` |
| `sonata_twig.yaml`, `sonata_twig:` | `adminata_twig.yaml`, `adminata_twig:` |
| `sonata_exporter.yaml`, `sonata_exporter:` | `adminata_exporter.yaml`, `adminata_exporter:` |
| `sonata_doctrine_orm_admin.yaml`, `sonata_doctrine_orm_admin:` | `adminata_doctrine_orm.yaml`, `adminata_doctrine_orm:` |
| `sonata_doctrine_mongo_db_admin.yaml`, `sonata_doctrine_mongo_db_admin:` | `adminata_doctrine_mongodb.yaml`, `adminata_doctrine_mongodb:` |

Values inside those files that change:

| Node | Before | After |
|---|---|---|
| `adminata.templates.*`, `adminata_block.templates.*`, `adminata.options.form_type`, `twig.form_themes` | `'@SonataAdmin/…'` | `'@Adminata/…'` |
| `adminata.assets.stylesheets` / `javascripts` / `remove_*` | `'/bundles/sonataadmin/…'`, `package_name: sonata_admin` | `'/bundles/adminata/…'`, `package_name: adminata` |
| `adminata.security.handler` | `sonata.admin.security.handler.role` (or `.acl`, `.noop`) | `adminata.admin.security.handler.role` |
| `adminata.default_controller` | `sonata.admin.controller.crud` | `adminata.admin.controller.crud` |
| `adminata.dashboard.blocks[].type`, `adminata_block.blocks` keys | `sonata.admin.block.admin_list`, `sonata.admin.block.search_result`, `sonata.admin.block.stats` | `adminata.admin.block.admin_list`, `…search_result`, `…stats` |
| `adminata.dashboard.groups.*.items`, `adminata.extensions.*.admins` | your admin ids | unchanged unless you rename them ([§6.3](#63-your-own-service-ids-and-the-roles-derived-from-them)) |
| `adminata_form.*`, `adminata_twig.flashmessage.*` type keys | `sonata_flash_success`, `sonata_flash_error`, `sonata_flash_info` | `adminata_flash_success`, `adminata_flash_error`, `adminata_flash_info` |
| `adminata.security.role_admin` | `ROLE_SONATA_ADMIN` (the old default) | `ROLE_ADMINATA_ADMIN` — see [§6.3](#63-your-own-service-ids-and-the-roles-derived-from-them) before relying on the new default |
| `adminata.theme.mode` | unchanged | unchanged |

**upstream** — the nodes 1.0 removed (`options.skin`, `options.use_select2`, `options.use_icheck`,
`options.use_bootlint`) and the one it added (`theme.mode`) are in
[UPGRADE-1.0.md](UPGRADE-1.0.md).

XML configuration only: the namespaces `https://sonata-project.org/schema/dic/admin` and
`http://sonata-project.com/schema/dic/block` are `https://idct.tech/schema/dic/adminata` and
`https://idct.tech/schema/dic/adminata_block`.

## 5. Routes

```yaml
# config/routes/adminata.yaml (was config/routes/sonata_admin.yaml)
admin_area:
    resource: '@AdminataBundle/Resources/config/routing/adminata.xml'   # was @SonataAdminBundle/…/sonata_admin.xml
    prefix: /admin

_adminata_admin:                                                        # was _sonata_admin
    resource: .
    type: adminata                                                      # the route loader type, was sonata_admin
    prefix: /admin
```

| Route name before | After |
|---|---|
| `sonata_admin_dashboard` | `adminata_dashboard` |
| `sonata_admin_search` | `adminata_search` |
| `sonata_admin_redirect` | `adminata_redirect` |
| `sonata_admin_retrieve_form_element` | `adminata_retrieve_form_element` |
| `sonata_admin_append_form_element` | `adminata_append_form_element` |
| `sonata_admin_short_object_information` | `adminata_short_object_information` |
| `sonata_admin_set_object_field_value` | `adminata_set_object_field_value` |
| `sonata_admin_retrieve_autocomplete_items` | `adminata_retrieve_autocomplete_items` |

Admin CRUD routes (`admin_app_post_list`, …) are built from your admin's `baseRouteName` and do
not change. If a route of your own carries the request attributes adminata reads, rename them:
`_sonata_admin` → `_adminata_admin`, `_sonata_name` → `_adminata_name`, `_sonata_csrf_token` →
`_adminata_csrf_token`.

## 6. PHP

### 6.1 Namespaces

| Before | After |
|---|---|
| `Sonata\AdminBundle\` | `IDCT\Adminata\` |
| `Sonata\DoctrineORMAdminBundle\` | `IDCT\Adminata\DoctrineORM\` |
| `Sonata\DoctrineMongoDBAdminBundle\` | `IDCT\Adminata\DoctrineMongoDB\` |
| **upstream** `Sonata\BlockBundle\`, `Sonata\Form\`, `Sonata\Twig\` | `IDCT\Adminata\` |
| **upstream** `Sonata\Exporter\` | `IDCT\Adminata\Exporter\` |
| **upstream** `Sonata\Doctrine\` | `IDCT\Adminata\Doctrine\` |

The class name after the namespace is unchanged: `Sonata\AdminBundle\Admin\AbstractAdmin` is
`IDCT\Adminata\Admin\AbstractAdmin`, `Sonata\DoctrineORMAdminBundle\Filter\StringFilter` is
`IDCT\Adminata\DoctrineORM\Filter\StringFilter`. The exceptions are the classes that carried the
word Sonata:

### 6.2 Classes that carried the name

| Before | After |
|---|---|
| `Sonata\AdminBundle\SonataAdminBundle` | `IDCT\Adminata\AdminataBundle` |
| `Sonata\AdminBundle\SonataConfiguration` (the `sonata_config` Twig global's class) | `IDCT\Adminata\AdminataConfiguration` |
| `Sonata\AdminBundle\DependencyInjection\SonataAdminExtension`, `AbstractSonataAdminExtension` | `IDCT\Adminata\DependencyInjection\AdminataExtension`, `AbstractAdminataExtension` |
| `…\DependencyInjection\SonataBlockExtension`, `SonataFormExtension`, `SonataTwigExtension`, `SonataExporterExtension` | `AdminataBlockExtension`, `AdminataFormExtension`, `AdminataTwigExtension`, `AdminataExporterExtension` |
| `Sonata\AdminBundle\Twig\Extension\SonataAdminExtension`, `Sonata\AdminBundle\Twig\SonataAdminRuntime` | `IDCT\Adminata\Twig\Extension\AdminataExtension`, `IDCT\Adminata\Twig\AdminataRuntime` |
| `Sonata\AdminBundle\Exporter\Exception\SonataExporterException` | `IDCT\Adminata\Exporter\Exception\AdminataExporterException` |
| `Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle` | `IDCT\Adminata\DoctrineORM\AdminataDoctrineORMBundle` |
| `Sonata\DoctrineMongoDBAdminBundle\SonataDoctrineMongoDBAdminBundle` | `IDCT\Adminata\DoctrineMongoDB\AdminataDoctrineMongoDBBundle` |

**upstream** — two `CollectionType`s exchanged names in 1.0 and the wrong import compiles and
renders the other widget: form-extensions' `Sonata\Form\Type\CollectionType`
(`sonata_type_collection`) is `IDCT\Adminata\Form\Type\CollectionType` (`adminata_type_collection`);
the admin bundle's old `Sonata\AdminBundle\Form\Type\CollectionType` (`sonata_type_native_collection`)
is `IDCT\Adminata\Form\Type\NativeCollectionType` (`adminata_type_native_collection`).

### 6.3 Your own service ids, and the roles derived from them

adminata's own ids all change by one rule — the `sonata` segment becomes `adminata`:
`sonata.admin.pool` → `adminata.admin.pool`, `sonata.admin.security.handler.role` →
`adminata.admin.security.handler.role`, `sonata.block.manager` → `adminata.block.manager`,
`sonata.exporter.exporter` → `adminata.exporter.exporter`, `sonata.admin.manager.orm` →
`adminata.admin.manager.orm`. So do the container parameters (`sonata.admin.configuration.*` →
`adminata.admin.configuration.*`) and the tags:

| Tag before | After |
|---|---|
| `sonata.admin` (every admin service) | `adminata.admin` — attributes `manager_type`, `group`, `label`, … unchanged |
| `sonata.admin.extension` | `adminata.admin.extension` |
| `sonata.admin.filter.type` | `adminata.admin.filter.type` |
| `sonata.admin.template_registry` | `adminata.admin.template_registry` |
| `sonata.admin.audit_reader`, `sonata.admin.manager` | `adminata.admin.audit_reader`, `adminata.admin.manager` |
| `sonata.block`, `sonata.block.loader` | `adminata.block`, `adminata.block.loader` |
| `sonata.exporter.writer` | `adminata.exporter.writer` |
| `sonata.status.renderer` | `adminata.status.renderer` |

**The id you gave your own admin service is a different matter.** Both security handlers derive
role names from the admin code, which is that id:

```
ROLE_ + strtoupper(str_replace('.', '_', $admin->getCode())) + _ + PERMISSION
sonata.admin.user.partner  →  ROLE_SONATA_ADMIN_USER_PARTNER_EDIT
```

Those roles are in your `security.yaml` hierarchy, in `isGranted()` calls, in templates, in
fixtures and migrations — and in the database, in every user's roles column, and in
`acl_security_identities` if you use the ACL handler. Renaming the id renames the role, and a
user whose stored roles still say `ROLE_SONATA_ADMIN_…` loses access the moment the new
container boots. Persisted filters (`persist_filters: true`) are keyed by the code too and would
reset once.

One default of adminata's changed too: `security.role_admin` was `ROLE_SONATA_ADMIN` and is
`ROLE_ADMINATA_ADMIN`. If the old role is in your users' database, either set the node back —

```yaml
adminata:
    security:
        role_admin: ROLE_SONATA_ADMIN
```

— or migrate that role with the deploy, the same way as below.

So:

- **Keep your ids** (recommended). `sonata.admin.user.partner` stays exactly that; only its tag
  becomes `adminata.admin`. Nothing in the database changes. The rename tool does this by
  default and prints the ids it left alone.
- **Or rename them**, as one deployment that changes `security.yaml`, the code, and the data
  together: a migration that rewrites the roles column (for a JSON column on MySQL, for example,
  `UPDATE app_user SET roles = REPLACE(roles, 'ROLE_SONATA_ADMIN_', 'ROLE_ADMINATA_ADMIN_')`),
  the same on `acl_security_identities.identifier` for ACL users, and every `dashboard.groups`
  and `extensions` entry that names the id. Do it on a copy of production data first.

### 6.4 Form types and options

Every block prefix takes `adminata_type_` instead of `sonata_type_`: `sonata_type_model` →
`adminata_type_model`, `sonata_type_model_autocomplete` → `adminata_type_model_autocomplete`,
`sonata_type_collection` → `adminata_type_collection`, `sonata_type_native_collection` →
`adminata_type_native_collection`, `sonata_type_immutable_array` → `adminata_type_immutable_array`,
`sonata_type_choice_field_mask`, `sonata_type_boolean`, `sonata_type_template`,
`sonata_type_admin`, `sonata_type_acl_matrix`, `sonata_type_date_range`, `sonata_type_datetime_range`,
`sonata_type_datetime_picker`, `sonata_type_datetime_range_picker`, `sonata_type_filter_*` (7),
`sonata_type_operator_*` (6), `sonata_type_model_list`, `sonata_type_model_hidden`,
`sonata_type_model_reference`, `sonata_type_container_template_choice` — 32 in all — and
`sonata_block_service_choice` → `adminata_block_service_choice`. You meet them as form theme block
names (`{% block sonata_type_model_widget %}` → `{% block adminata_type_model_widget %}`) and in
`getBlockPrefix()` of a type that extends one of them.

Options and view variables keep the word "admin" because they name the admin *of a field*:

| Before | After |
|---|---|
| `sonata_admin` (option and form view variable) | `adminata_admin` |
| `sonata_admin_enabled`, `sonata_admin_code`, `sonata_admin_translation_domain` | `adminata_admin_enabled`, `adminata_admin_code`, `adminata_admin_translation_domain` |
| `sonata_field_description` | `adminata_field_description` |
| `sonata_deprecation_mute` | `adminata_deprecation_mute` |

### 6.5 Events, flash messages, console commands

| Before | After |
|---|---|
| `sonata.admin.event.configure.{form,list,show,datagrid,query,menu.sidebar}` | `adminata.admin.event.configure.{…}` |
| `sonata.admin.event.persistence.{pre,post}_{persist,update,remove}` | `adminata.admin.event.persistence.{…}` |
| `sonata.admin.event.batch_action.pre_batch_action`, `sonata.admin.event.extension` | `adminata.admin.event.batch_action.pre_batch_action`, `adminata.admin.event.extension` |
| `$this->addFlash('sonata_flash_success', …)`, `sonata_flash_error`, `sonata_flash_info` | `adminata_flash_success`, `adminata_flash_error`, `adminata_flash_info` |
| `bin/console sonata:admin:list` / `explain` / `setup-acl` / `generate-object-acl` | `adminata:list` / `adminata:explain` / `adminata:setup-acl` / `adminata:generate-object-acl` |
| `bin/console debug:sonata:block`, `make:sonata:admin` | `debug:adminata:block`, `make:adminata:admin` |

## 7. Twig

| Before | After |
|---|---|
| `@SonataAdmin/…` (and `@!SonataAdmin/…`) | `@Adminata/…` (`@!Adminata/…`) |
| `@SonataBlock/…`, `@SonataForm/…`, `@SonataTwig/…` (aliases of the same directory in 1.0) | `@Adminata/…` — the aliases are gone |
| `templates/bundles/SonataAdminBundle/` | `templates/bundles/AdminataBundle/` — `git mv` the directory; every file path inside is unchanged |
| **upstream** `templates/bundles/SonataBlockBundle/`, `SonataFormBundle/`, `SonataTwigBundle/` | the same files under `templates/bundles/AdminataBundle/` |
| `@SonataDoctrineORMAdmin/…`, `@SonataDoctrineMongoDBAdmin/…` | `@AdminataDoctrineORM/…`, `@AdminataDoctrineMongoDB/…` |
| `sonata_block_render`, `sonata_block_render_event`, `sonata_block_exists`, `sonata_block_include_javascripts`, `sonata_block_include_stylesheets` | `adminata_block_render`, `adminata_block_render_event`, `adminata_block_exists`, `adminata_block_include_javascripts`, `adminata_block_include_stylesheets` |
| `sonata_flashmessages_get`, `sonata_flashmessages_types`, `sonata_flashmessages_class` | `adminata_flashmessages_get`, `adminata_flashmessages_types`, `adminata_flashmessages_class` |
| `sonata_theme`, `sonata_html_dir`, `get_sonata_dashboard_groups_with_creatable_admins`; filter `sonata_status_class` | `adminata_theme`, `adminata_html_dir`, `get_adminata_dashboard_groups_with_creatable_admins`; `adminata_status_class` |
| globals `sonata_config` (`sonata_config.getOption(…)`), `sonata_admin` (`sonata_admin.adminPool`, `sonata_admin.url(…)`) | `adminata_config`, `adminata_admin` |
| `knp_menu_render('sonata_admin_sidebar', …)` | `knp_menu_render('adminata_sidebar', …)` |
| `'…'|trans({}, 'SonataAdminBundle')` | `'…'|trans({}, 'AdminataBundle')` |

**Block names.** Every block whose name starts with `sonata_` starts with `adminata_`, and the
two content blocks lose the redundant word:

| Before | After |
|---|---|
| `sonata_admin_content`, `sonata_admin_content_actions_wrappers` | `adminata_content`, `adminata_content_actions_wrappers` |
| `sonata_wrapper`, `sonata_header`, `sonata_nav`, `sonata_left_side`, `sonata_side_nav`, `sonata_breadcrumb`, `sonata_page_content`, `sonata_page_content_header`, `sonata_page_content_nav`, `sonata_overlay`, `sonata_dialog`, `sonata_head_title`, `sonata_header_search`, `sonata_header_noscript_warning`, `sonata_sidebar_search`, `sonata_top_nav_menu`, `sonata_top_nav_menu_add_block`, `sonata_top_nav_menu_dark_mode`, `sonata_top_nav_menu_user_block`, `sonata_javascript_config`, `sonata_javascript_pool`, `sonata_script_attributes` | the same with `adminata_` |
| `sonata_form_actions`, `sonata_form_action_url`, `sonata_form_attributes`, `sonata_pre_fieldsets`, `sonata_post_fieldsets`, `sonata_tab_content`, `sonata_list_filter_group_class`, `sonata_mosaic_background`, `sonata_mosaic_default_view`, `sonata_mosaic_description`, `sonata_mosaic_hover_view` | the same with `adminata_` |
| `sonata_type_*_widget`, `sonata_type_*_widget_row`, `sonata_type_model_autocomplete_*_format`, `sonata_type_choice_multiple_sortable` (the form theme) | `adminata_type_*` |

A block of your own that you named `sonata_…` in your own templates is yours; the tool reports it
and leaves it.

## 8. Translations

| Before | After |
|---|---|
| domain `SonataAdminBundle` | `AdminataBundle` |
| `translations/SonataAdminBundle.<locale>.xliff` (your overrides) | `translations/AdminataBundle.<locale>.xliff` — `git mv`; the unit ids inside are unchanged except the six below |
| ids `sonata_administration`, `sonata.block.service.container`, `….menu`, `….rss`, `….template`, `….text` | `adminata_administration`, `adminata.block.service.*` |
| **upstream** domains `SonataBlockBundle`, `SonataFormBundle`, `SonataTwigBundle` | their units live in `AdminataBundle` since 1.0; move your overrides into that file, ids unchanged |

Your admins' own translation domain (`messages` by default, or what `getTranslationDomain()`
returns) is not adminata's and does not change.

## 9. Markup, CSS and JavaScript

**Hooks.** The class names and ids adminata promises — the ones the PHP layer emits and your CSS
or JavaScript may select on — take the `adminata-` prefix, and the `ba` of the old
`sonata-ba-*` family goes:

| Before | After |
|---|---|
| `sonata-ba-list-field`, `sonata-ba-list-field-header`, `sonata-ba-list`, `sonata-ba-form`, `sonata-ba-field`, `sonata-ba-field-error`, `sonata-ba-collapsed-fields`, `sonata-ba-action`, `sonata-ba-delete`, `sonata-ba-view`, … (every `sonata-ba-*`) | `adminata-list-field`, `adminata-list-field-header`, `adminata-list`, `adminata-form`, `adminata-field`, `adminata-field-error`, `adminata-collapsed-fields`, `adminata-action`, `adminata-delete`, `adminata-view`, … |
| `sonata-actions`, `sonata-action-element`, `sonata-filter-form`, `sonata-filters-box`, `sonata-toggle-filter`, `sonata-link-identifier`, `sonata-medium-date`, … (every other `sonata-*`) | `adminata-actions`, `adminata-action-element`, … |
| ids `sonata-content`, `sonata-dialog`, `sonata-dialog-title`, `sonata-question-dialog`, `sonata-question-dialog-title`, `sonata-search-input`, `sonata-ba-field-container-*` | `adminata-content`, `adminata-dialog`, …, `adminata-field-container-*` |
| `sonata-ba-content` and `#sonata-content`; `sonata-ba-tabs` and `sonata-tabs` | one token each — `adminata-content`; `adminata-tabs` — the two places where dropping `ba` made two names one |
| `objectId`, button `name` attributes, `.adm-*` components, Tailwind utilities | unchanged — they never carried the name |

**Stimulus.** Every controller identifier `sonata-<name>` is `adminata-<name>`, and everything
Stimulus derives from it follows:

| Before | After |
|---|---|
| `stimulus_controller('sonata-modal', …)`, `stimulus_target('sonata-modal', 'dialog')`, `stimulus_action('sonata-modal', 'open')` | `'adminata-modal'` — likewise `adminata-autocomplete`, `adminata-batch`, `adminata-collection`, `adminata-confirm-exit`, `adminata-dismiss`, `adminata-dropdown`, `adminata-edit`, `adminata-filter`, `adminata-filter-list`, `adminata-layout`, `adminata-menu`, `adminata-modal-trigger`, `adminata-per-page`, `adminata-question`, `adminata-readmore`, `adminata-reveal`, `adminata-revision`, `adminata-row-link`, `adminata-sticky`, `adminata-theme` |
| `data-sonata-modal-target`, `data-sonata-readmore-more-text-value`, `data-sonata-filter-sonata-filter-list-outlet`, … | `data-adminata-modal-target`, `data-adminata-readmore-more-text-value`, `data-adminata-filter-adminata-filter-list-outlet`, … |
| events `sonata-modal:opened`, `sonata-modal:closed`, `sonata-question:…` | `adminata-modal:opened`, `adminata-modal:closed`, `adminata-question:…` |
| `window.sonataApplication` | `window.adminataApplication` |

**Cookies and storage.** `sonata_theme` → `adminata_theme`, `sonata_sidebar_hide` →
`adminata_sidebar_hide` (cookies); `sonata_sidebar_open` → `adminata_sidebar_open` (localStorage).
Nothing to migrate: each user's theme, sidebar and open-sections preferences reset once.

**Published assets.** `public/bundles/sonataadmin/` is `public/bundles/adminata/`. Run
`bin/console assets:install public` again, delete the old directory, and change any
`assets.*` entry or hard-coded `<link>`/`<script>` that named the old path. If you compile
Tailwind yourself, `@import "@idct/adminata"` and your `@source` lines are unchanged: the npm
package name did not move.

## 10. The rename tool

adminata ships the program that renamed itself, and it runs on your application:

```console
$ vendor/bin/adminata-rename --app --dry-run .    # the diff it would make, and your own names it will not touch
$ vendor/bin/adminata-rename --app .              # make it
$ vendor/bin/adminata-rename --check --app .      # nothing of adminata's left
```

In `--app` mode it rewrites **only the names adminata, the ORM layer and the MongoDB layer own**
— namespaces, class names, ids, tags, parameters, events, routes, request attributes, block
prefixes, options, Twig namespaces, functions, globals and block names, translation domain and
ids, hooks, controller identifiers, data attributes, cookies, asset paths, and the file names of
§4, §5, §7 and §8 (with `git mv`) — and prints every other `sonata…` token it found with its file
and line: your own service ids ([§6.3](#63-your-own-service-ids-and-the-roles-derived-from-them)),
routes, classes and block names, for you to decide about. Read the dry run in full before you
let it write.

What it does not do, because a regular expression cannot: delete the `bundles.php` lines of
bundles that no longer exist (§3, **upstream**); choose between the two `CollectionType`s (§6.2,
**upstream**); migrate roles (§6.3); rebuild `public/bundles/` (§9); edit `symfony.lock` or
`composer.lock`. Commit its output as one commit and your hand edits as another, so that the
mechanical part is reviewable on its own.

## 11. Verify

```console
$ bin/console cache:clear
$ bin/console lint:container
$ bin/console lint:twig templates
$ bin/console lint:yaml config
$ bin/console debug:config adminata >/dev/null           # and adminata_block, adminata_form, adminata_twig, adminata_exporter
$ bin/console debug:router | grep -c ' adminata_'          # 8
$ bin/console debug:container --tag=adminata.admin         # every admin of yours
$ bin/console debug:twig | grep -E '@Sonata'               # nothing
$ bin/console assets:install public && ls public/bundles/  # adminata, no sonataadmin
$ grep -rnE 'Sonata\\|@SonataAdmin|sonata_admin|sonata\.admin|sonata-ba-|SonataAdminBundle' src config templates translations assets   # only names you chose to keep
```

Then open the dashboard, a list with filters, an edit form with a collection and an autocomplete,
a delete page and the question dialog, signed in as a user whose roles are not `ROLE_SUPER_ADMIN`
— the last point is the one that catches a role that stopped matching.

## 12. What your users will notice

- Their theme and sidebar preferences reset once (§9).
- Nothing else — unless you renamed your own admin ids, in which case their persisted filters
  reset once and their roles were migrated with the deploy (§6.3).

## Appendix — the map in one table

| Kind | Before | After |
|---|---|---|
| Namespace | `Sonata\AdminBundle\` | `IDCT\Adminata\` |
| Namespace (ORM, ODM) | `Sonata\DoctrineORMAdminBundle\`, `Sonata\DoctrineMongoDBAdminBundle\` | `IDCT\Adminata\DoctrineORM\`, `IDCT\Adminata\DoctrineMongoDB\` |
| Namespace (**upstream**) | `Sonata\BlockBundle\`, `Sonata\Form\`, `Sonata\Twig\`; `Sonata\Exporter\`; `Sonata\Doctrine\` | `IDCT\Adminata\`; `IDCT\Adminata\Exporter\`; `IDCT\Adminata\Doctrine\` |
| Bundle classes | `SonataAdminBundle`, `SonataDoctrineORMAdminBundle`, `SonataDoctrineMongoDBAdminBundle` | `AdminataBundle`, `AdminataDoctrineORMBundle`, `AdminataDoctrineMongoDBBundle` |
| Other classes | `SonataAdmin*`, `Sonata*Extension`, `SonataConfiguration`, `SonataExporterException` | `Adminata*` |
| Config roots | `sonata_admin`, `sonata_block`, `sonata_form`, `sonata_twig`, `sonata_exporter`, `sonata_doctrine_orm_admin`, `sonata_doctrine_mongo_db_admin` | `adminata`, `adminata_block`, `adminata_form`, `adminata_twig`, `adminata_exporter`, `adminata_doctrine_orm`, `adminata_doctrine_mongodb` |
| Service ids, parameters, tags, events (adminata's) | `sonata.…` | `adminata.…` |
| Flash types | `sonata_flash_{success,error,info}` | `adminata_flash_{success,error,info}` |
| Routes | `sonata_admin_<name>` (8) | `adminata_<name>` |
| Request attributes | `_sonata_admin`, `_sonata_name`, `_sonata_csrf_token` | `_adminata_admin`, `_adminata_name`, `_adminata_csrf_token` |
| Console | `sonata:admin:<cmd>`, `debug:sonata:block`, `make:sonata:admin` | `adminata:<cmd>`, `debug:adminata:block`, `make:adminata:admin` |
| Twig namespaces | `@SonataAdmin`, `@SonataBlock`, `@SonataForm`, `@SonataTwig`; `@SonataDoctrineORMAdmin`, `@SonataDoctrineMongoDBAdmin` | `@Adminata`; `@AdminataDoctrineORM`, `@AdminataDoctrineMongoDB` |
| Override directory | `templates/bundles/SonataAdminBundle/` | `templates/bundles/AdminataBundle/` |
| Twig functions, filters, globals | `sonata_*`, `get_sonata_*`, `sonata_config`, `sonata_admin` | `adminata_*`, `get_adminata_*`, `adminata_config`, `adminata_admin` |
| Twig blocks | `sonata_*`; `sonata_admin_content*` | `adminata_*`; `adminata_content*` |
| KnpMenu alias | `sonata_admin_sidebar` | `adminata_sidebar` |
| Form block prefixes | `sonata_type_*`, `sonata_block_service_choice` | `adminata_type_*`, `adminata_block_service_choice` |
| Form options | `sonata_admin*`, `sonata_field_description`, `sonata_deprecation_mute` | `adminata_admin*`, `adminata_field_description`, `adminata_deprecation_mute` |
| Translation domain | `SonataAdminBundle` | `AdminataBundle` |
| Translation ids | `sonata_administration`, `sonata.block.service.*` | `adminata_administration`, `adminata.block.service.*` |
| Hooks | `sonata-ba-*`, `sonata-*` | `adminata-*` |
| Stimulus | `sonata-<name>`, `data-sonata-*`, `sonata-<name>:<event>`, `window.sonataApplication` | `adminata-<name>`, `data-adminata-*`, `adminata-<name>:<event>`, `window.adminataApplication` |
| Cookies, storage | `sonata_theme`, `sonata_sidebar_hide`; `sonata_sidebar_open` | `adminata_theme`, `adminata_sidebar_hide`; `adminata_sidebar_open` |
| Published assets | `/bundles/sonataadmin/` | `/bundles/adminata/` |
| XML config namespaces | `https://sonata-project.org/schema/dic/admin`, `http://sonata-project.com/schema/dic/block` | `https://idct.tech/schema/dic/adminata`, `https://idct.tech/schema/dic/adminata_block` |
| Composer | `idct/adminata` `replace`s `sonata-project/admin-bundle` | `idct/adminata` `conflict`s with the six packages it forked; the layers with theirs |
| Your own ids, routes, classes, blocks, hooks | yours | yours — reported, not rewritten |
