# Appendix A — Inventory of Sonata names, 2026-09-12

Measured on `question-dialog` at `f6918a7a1` (the tip of `main` plus the question dialog), with
`vendor/`, `node_modules/`, `var/`, `upstream/`, `.git/`, `PLAN/`, `build/`, caches, lock files
and `PROJECT_PLAN.md` excluded. "Hits" are case-insensitive occurrences of the string `sonata`;
they over-count (headers, prose, package names) and are the ceiling the gate starts from, not the
number of edits. The commands are given so that the run can be repeated before R0-01.

## 1. Where the string lives

| Tree | Files | Hits | What it mostly is |
|---|---|---|---|
| `src/` | 594 | 5,065 | namespaces, headers, ids, templates, translations |
| `tests/` | 369 | 4,327 | namespaces, headers, ids |
| `tests-adminata/` | 77 | 3,176 | JS fixtures (HTML dumped from templates), contract fixtures, demo config |
| `assets/` | 64 | 749 | Stimulus identifiers, hooks, `sonata-project` headers |
| `docs/` | 78 | 1,565 | code samples, prose |
| `changelog/` | 6 | 3,841 | inherited histories — skipped |
| `README.md` / `UPGRADE-1.0.md` / `CHANGELOG.md` / `MIGRATION.md` / `AGENTS.md` / `UPSTREAM.md` / `NOTICE` / `CHANGELOG-sonata.md` / `CONTRIBUTING.md` / `LICENSE` | 1 each | 164 / 249 / 266 / 63 / 80 / 39 / 10 / 16 / 4 / 2 | prose, tables, headers |
| `bin/` | 13 | 42 | headers, `bundles/sonataadmin`, `sonata-project/` prefix |
| `.github/` | 6 | 14 | mongo-compat, templates, dependabot |
| `composer.json` | 1 | 23 | replace, conflict, authors, keywords, suggest, autoload |
| tooling configs (`rector.php`, `.php-cs-fixer.*`, `phpstan*`, `eslint`, `prettier`, `stylelint`, `vite`, `vitest`, `playwright`, `.editorconfig`, `.yamllint`, `.gitattributes`) | 16 | ~45 | headers, one kernel FQCN, the `sonata_theme` cookie, `bundles/sonataadmin` |

By extension (in `src/`, `tests/`, `tests-adminata/`, `assets/`, `docs/`): php 7,871 · html 2,347
· rst 1,562 · twig 1,374 · js 776 · xliff 430 · json 281 · yaml 108 · yml 69 · css 52.

```bash
EX='--exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=var --exclude-dir=upstream --exclude-dir=.git --exclude-dir=PLAN --exclude-dir=build --exclude=composer.lock --exclude=package-lock.json --exclude=PROJECT_PLAN.md --exclude=*.cache'
for d in src tests tests-adminata assets docs bin .github; do printf "%-16s %5s files %6s hits\n" $d $(grep -rli sonata $EX $d | wc -l) $(grep -rio sonata $EX $d | wc -l); done
```

## 2. The identity surfaces of adminata

| Surface | Count | Items |
|---|---|---|
| PSR-4 roots | 3 | `Sonata\AdminBundle\` → `src/`; `Sonata\AdminBundle\Tests\` → `tests/`; `Adminata\Tests\` → `tests-adminata/` (unchanged) |
| Classes named after Sonata in `src/` | 10 | `SonataAdminBundle`, `SonataConfiguration`, `DependencyInjection\{Abstract,}SonataAdminExtension`, `Sonata{Block,Form,Twig,Exporter}Extension`, `Twig\Extension\SonataAdminExtension`, `Twig\SonataAdminRuntime`, `Exporter\Exception\SonataExporterException` |
| Test classes named after Sonata | 12 | `SonataAdminBundleTest`, `SonataConfigurationTest`, five `Sonata*ExtensionTest`, `Twig\…\SonataAdminExtensionTest`, `SonataAdminRuntimeTest`, `FormSonataFilterChoiceWidgetTest`, `FormSonataNativeCollectionWidgetTest`, the `templates/bundles/SonataAdminBundle` dir |
| Config roots | 5 | `sonata_admin`, `sonata_block`, `sonata_form`, `sonata_twig`, `sonata_exporter` (`TreeBuilder` in `DependencyInjection/*Configuration.php`; no `getAlias()` overrides — all derived) |
| Distinct `sonata.*` ids and parameters in `src/Resources/config` | 199 | prefixes: `sonata.admin.*` (~150), `sonata.block.*` (~35), `sonata.exporter.*` (~25), `sonata.form.*` (11), `sonata.twig.*` (7), `sonata.doctrine.*` (3) |
| Tags | 10 | `sonata.admin` (`TaggedAdminInterface::ADMIN_TAG`), `sonata.admin.audit_reader`, `sonata.admin.manager`, `sonata.admin.extension`, `sonata.admin.filter.type`, `sonata.admin.template_registry`, `sonata.block`, `sonata.block.loader`, `sonata.exporter.writer`, `sonata.status.renderer` (`sonata.admin.maker`, `sonata.admin.search.handler` and `sonata.doctrine.mapper` are service ids the compiler passes look up, not tags) |
| Event names | 15 | `sonata.admin.event.configure.{form,list,show,datagrid,query,menu.sidebar}`, `…persistence.{pre,post}_{persist,update,remove}`, `…batch_action.pre_batch_action`, `…extension`, `sonata.admin.event_listener.configure_crud_controller` |
| Flash types | 3 | `sonata_flash_success`, `sonata_flash_error`, `sonata_flash_info` |
| Routes | 8 | `sonata_admin_{dashboard,search,redirect,retrieve_form_element,append_form_element,short_object_information,set_object_field_value,retrieve_autocomplete_items}`; files `routing/sonata_admin.{php,xml}` |
| Request attributes | 3 | `_sonata_admin`, `_sonata_name`, `_sonata_csrf_token` |
| Console commands | 6 | `sonata:admin:{list,explain,setup-acl,generate-object-acl}`, `debug:sonata:block`, `make:sonata:admin` |
| Twig namespaces referenced | 4 + foreign | `@SonataAdmin` (658), `@SonataBlock` (10), `@SonataForm` (7), `@SonataTwig` (6); in docs only: `@SonataIntl` (15), `@SonataPage` (5), `@SonataSeo`, `@SonataMedia`, `@SonataUser`, `@SonataPost`, `@SonataDemo`, `@SonataXxx` (samples of other bundles) |
| Twig functions / filters / globals | 11 + 2 | `sonata_block_render`, `sonata_block_render_event`, `sonata_block_exists`, `sonata_block_include_{javascripts,stylesheets}`, `sonata_flashmessages_{get,types,class}`, `sonata_theme`, `sonata_html_dir`, `get_sonata_dashboard_groups_with_creatable_admins`, filter `sonata_status_class`; globals `sonata_admin`, `sonata_config` |
| Twig block names with the prefix (src) | 51 | 33 layout and page blocks: `sonata_admin_content`, `sonata_admin_content_actions_wrappers`, `sonata_breadcrumb`, `sonata_dialog`, `sonata_form_action_url`, `sonata_form_actions`, `sonata_form_attributes`, `sonata_head_title`, `sonata_header`, `sonata_header_noscript_warning`, `sonata_header_search`, `sonata_javascript_config`, `sonata_javascript_pool`, `sonata_left_side`, `sonata_list_filter_group_class`, `sonata_mosaic_{background,default_view,description,hover_view}`, `sonata_nav`, `sonata_overlay`, `sonata_page_content`, `sonata_page_content_{header,nav}`, `sonata_post_fieldsets`, `sonata_pre_fieldsets`, `sonata_script_attributes`, `sonata_side_nav`, `sonata_sidebar_search`, `sonata_tab_content`, `sonata_top_nav_menu`, `sonata_top_nav_menu_{add_block,dark_mode,user_block}`, `sonata_wrapper`; 18 form-theme blocks: `sonata_type_{choice_field_mask,date_range,datetime_picker,datetime_range,immutable_array,model_autocomplete,model_list,native_collection,template}_widget`, `sonata_type_datetime_picker_widget_html`, `sonata_type_immutable_array_widget_row`, `sonata_type_native_collection_widget_row`, `sonata_type_model_autocomplete_{ajax_request_parameters,dropdown_item_format,selection_format}`, `sonata_type_choice_multiple_sortable` |
| Form block prefixes | 32 | `sonata_type_{admin,model,model_list,model_hidden,model_reference,model_autocomplete,collection,native_collection,immutable_array,choice_field_mask,boolean,template,acl_matrix,container_template_choice,date_range,datetime_range,datetime_picker,datetime_range_picker,filter_default,filter_choice,filter_number,filter_date,filter_date_range,filter_datetime,filter_datetime_range,operator_string,operator_number,operator_equal,operator_contains,operator_date,operator_date_range}`, `sonata_block_service_choice` |
| Form options / view variables | 6 | `sonata_admin`, `sonata_admin_enabled`, `sonata_admin_code`, `sonata_admin_translation_domain`, `sonata_field_description`, `sonata_deprecation_mute` (`sonata_help` is gone since upstream 4.0) |
| Translation domain | 1 × 35 | `SonataAdminBundle.{ar,bg,bs,ca,cs,de,en,es,eu,fa,fi,fr,hr,hu,it,ja,lb,lt,lv,nl,no,pl,pt,pt_BR,ro,ru,sk,sl,sr_Cyrl,sr_Latn,sv_SE,tr,uk,zh_CN,zh_HK}.xliff`; 167 units in `en`; 6 ids carry the name: `sonata_administration`, `sonata.block.service.{container,menu,rss,template,text}` |
| Markup hooks: class and id tokens in `class="…"`, `id="…"` and CSS selectors | 65 | 33 `sonata-ba-*`, 32 others; collisions if `ba` is dropped: `content`, `tabs`. All `sonata-*` strings in views, assets and tests-adminata, attribute fragments and `sonata-project` included: 203 distinct |
| Element ids | 7 | `sonata-content`, `sonata-dialog`, `sonata-dialog-title`, `sonata-question-dialog`, `sonata-question-dialog-title`, `sonata-search-input`, `sonata-ba-field-container-*` |
| Stimulus identifiers | 21 | `sonata-{autocomplete,batch,collection,confirm-exit,dismiss,dropdown,edit,filter,filter-list,layout,menu,modal,modal-trigger,per-page,question,readmore,reveal,revision,row-link,sticky,theme}` |
| `data-sonata-*` attribute names | 73 distinct | targets, values, params, outlets, `data-sonata-question-{text,confirm,cancel}`, `data-sonata-choice-field-mask-*`, `data-sonata-autocomplete-id`, `data-sonata-menu-keep-open` |
| JS names | 1 global + camel case | `window.sonataApplication`; `sonataAdmin` (24), `sonataAutocompleteId`, `sonataConfiguration`, `sonataAutocompleteIndex`, `defaultSonataDoctrineConfig`, `sonataRowLinkUrl`, `sonataMenuKeepOpen`, `sonataFilterOutlet`, `sonataFilterListOutlet`, `sonataDebug` |
| Cookies, storage, menu alias | 2 + 1 + 1 | cookies `sonata_theme`, `sonata_sidebar_hide`; localStorage key `sonata_sidebar_open` (menu controller); KnpMenu alias `sonata_admin_sidebar` (`menu.php`, `standard_layout.html.twig`). `sonata_sidebar_search` is a Twig block |
| Published asset path | 1 | `public/bundles/sonataadmin/` (`bin/write-manifests.mjs`, `vite.config.js`, committed `entrypoints.json` / `manifest.json`, the demo symlink) |
| Composer | — | `name idct/adminata` (unchanged); `replace sonata-project/admin-bundle 4.43.0`; `conflict` × 5 `sonata-project/*`; keyword `sonata`; two Sonata `authors`; `suggest` mentions; `autoload` keys |
| npm | — | `@idct/adminata` (unchanged); `assets/css/tailwind.css` `@source '../../../sonata-admin-mongodb-bundle/…'` |
| CI | 3 files | `mongo-compat.yaml` (`FORK_REPOSITORY: ideaconnect/sonata-admin-mongodb-bundle`, `! test -d vendor/sonata-project/admin-bundle`), `dependabot.yml` (ignore `sonata-project/*`), issue/PR templates (prose) |
| Upstream tooling | — | `upstream/remotes.txt` (6 rows), `upstream/merged.txt` (5), `upstream/exclude/*.txt` (paths incl. `src/DependencyInjection/SonataAdminExtension.php`, `src/SonataConfiguration.php`), `upstream/sync.sh` (`git apply -3`), `bin/check-replace-versions.php` (`'sonata-project/'.$directory`) |
| Files named after Sonata (src, tests, tests-adminata, docs) | 76 | listed in 02 §4.1: 10 + 11 classes and 1 override dir, 2 routing, 35 catalogues, 3 templates, 1 demo config, 1 symlink, 6 contract fixtures, 2 recipes, 3 images |
| XML configuration namespaces | 2 | `AdminataExtension::getNamespace()` `https://sonata-project.org/schema/dic/admin`; `AdminataBlockExtension` `http://sonata-project.com/schema/dic/block`; no XSD shipped |
| Deferred (not yet ported) templates' upstream classes | ~20 | `sonata-medium-date`, `sonata-tree`, `sonata-list-table`, `sonata-preview-form-container`, `sonata-feeds-container`, `sonata-association`, … — hooks of the 36 templates 1.0 did not rewrite; rule 32 renames them with the rest |

```bash
grep -rn "new TreeBuilder(" src/DependencyInjection
grep -rhoE "'sonata\.[a-z_.]+'" src/Resources/config | sort -u | wc -l
grep -rhoE "sonata_admin_[a-z_]+" src/Resources/config/routing | sort -u
grep -rhoE "new (TwigFunction|TwigFilter)\(\s*'[a-z_]+'" src/Twig | sort -u
grep -rhoE "return '[a-z_]+';" $(grep -rl getBlockPrefix src/Form) | sort -u
ls src/Resources/translations | sed -E 's/\.[a-zA-Z_]+\.xliff$//' | sort -u
grep -rhoE "\bsonata-[a-z0-9-]+" src/Resources/views assets tests-adminata | sort -u | wc -l
grep -rhoE "data-sonata-[a-z0-9-]+" src assets tests-adminata | sort -u | wc -l
ls assets/js/controllers
grep -rhoE "sonata_[a-z_]*(theme|sidebar)[a-z_]*" src assets/js | sort -u
grep -rhoE "\{%-?\s*block\s+[a-z_]*sonata[a-z_]*" src/Resources/views | sed -E 's/.*block\s+//' | sort -u | wc -l
{ grep -rhoE 'class="[^"]*"' src/Resources/views | grep -oE '\bsonata-[a-z0-9-]+'; grep -rhoE 'id="[^"]*"' src/Resources/views | grep -oE '\bsonata-[a-z0-9-]+'; grep -rhoE '\.sonata-[a-z0-9-]+' assets/css | sed 's/^\.//'; } | sort -u | wc -l
find src tests tests-adminata docs -iname '*sonata*' | wc -l
```

## 3. The consumers

| Repository | Branch, version | Own Sonata identity | References into adminata | Other |
|---|---|---|---|---|
| `ORM/` `idct/adminata-doctrine-orm-admin-bundle` | `main`, v1.0.0 | `Sonata\DoctrineORMAdminBundle\` (+ Tests), `SonataDoctrineORMAdminBundle`, root `sonata_doctrine_orm_admin`, `@SonataDoctrineORMAdmin` (6), ids `sonata.admin.*` (63) + `sonata.admin_doctrine_orm` (1), `replace sonata-project/doctrine-orm-admin-bundle 4.21.0` | 79 of 171 PHP files name `Sonata\AdminBundle\`; `@SonataAdmin` 29 | 206 files with the string; README, NOTICE, UPSTREAM.md, CHANGELOG, docs |
| `ODM/` `idct/sonata-admin-mongodb-bundle` | `6.x`, v6.0.0, on Packagist | `Sonata\DoctrineMongoDBAdminBundle\` (+ Tests), `SonataDoctrineMongoDBAdminBundle`, root `sonata_doctrine_mongo_db_admin`, `@SonataDoctrineMongoDBAdmin` (6); the package name itself | 55 of 106 PHP files; `@SonataAdmin` 54 (form themes, `ListBuilder`) | README with Packagist badges, AGENTS.md, UPGRADE-{4,5,6}.0.md, BEST_VERSION.md, WAR_AGAINST_THE_MUTANTS.md |
| `APP/` recomaty-panel | `develop` (0.17.0), adminata `dev-main` lock-pinned, ORM `^1.0`, ODM `^6.0` | its own ids `sonata.admin.<domain>.<name>` (30) — from which `sonata.admin.security.handler.role` derives 73 distinct `ROLE_SONATA_ADMIN_*` names found in `security.yaml`, `src/`, `templates/` and `migrations/`; route `sonata_admin_edit_own_password`; class `sonata-overrides` | 74 of 701 PHP files (`Sonata\AdminBundle` 265, `Sonata\DoctrineORMAdminBundle` 92, `Sonata\DoctrineMongoDBAdminBundle` 7); `bundles.php` 3 lines; roots `sonata_admin`, `sonata_block`, `sonata_form`, `sonata_doctrine_orm_admin`; `config/routes/sonata_admin.yaml`; 16 templates under `templates/bundles/SonataAdminBundle/`; `@SonataAdmin` 41; tags `sonata.admin` 44, `sonata.admin.request.fetcher` 3, `sonata.admin.extension` 2; 14 overridden `sonata_*` block names; `sonata_config` 8; flash types 22; routes 5; hooks ~80 (`sonata-ba-list-field` 39) | 304 files with the string; no `SonataAdminBundle` translation overrides; `symfony.lock` entries for the three packages; `docs/INSTALL.md` |

```bash
cd ORM/ && grep -rl 'Sonata\\AdminBundle' src tests | wc -l && grep -rhoE '@Sonata[A-Za-z]+' src tests | sort | uniq -c
cd ODM/ && grep -rl 'Sonata\\AdminBundle' src tests | wc -l && grep -rhoE '@Sonata[A-Za-z]+' src tests | sort | uniq -c
cd APP/ && grep -rl 'Sonata\\' src | wc -l && grep -n Sonata config/bundles.php && ls config/packages | grep sonata && find templates/bundles/SonataAdminBundle -type f | wc -l && grep -rhoE "sonata\.[a-z_.]+" config/services*.yaml | sort | uniq -c | sort -rn | head -3 && grep -rhoE '\bsonata-[a-z0-9-]+' templates assets | wc -l
```
