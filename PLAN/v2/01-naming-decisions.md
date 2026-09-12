# 01 — Naming decisions

Every decision has an id, a rationale, the alternative that was rejected and a status. *Decided*
means the plan is written against it and execution needs no further word; *needs owner* means the
default below is what execution does unless the owner says otherwise before the task that applies
it. Counts are from [appendix A](appendix-A-inventory.md).

## N1 — The rename lands before adminata's first tag; the storage layers take a major each

adminata is untagged by the owner's instruction and installs as `dev-main`; the only consumers are
the owner's ORM layer (v1.0.0), MongoDB fork (v6.0.0) and panel (lock-pinned). The rename is
therefore not a 2.0 of anything adminata has shipped: it is part of what 1.0.0 will be. The stale
`v1.0.0-rc1` on origin (pre-merge layout, Sonata names) is deleted before the rename merges —
`git push --delete origin v1.0.0-rc1`, the owner's action, recommended once already — so nothing
tagged advertises the old identity, and `^1.0@dev` stops resolving to it.

The ORM layer has a v1.0.0 that the panel requires as `^1.0`; it becomes **2.0.0**. The MongoDB
fork's 6.x is built against Sonata-named adminata; it becomes **7.0.0**. The last Sonata-named
commit of each repository is recorded by sha in its CHANGELOG; no branch, tag or compat release
keeps the old names alive (owner directive of 2026-09-04: no compatibility layers).

*Rejected:* shipping 1.0.0 with the Sonata names and renaming in 2.0. It would tag an identity the
owner has already decided against and make every consumer migrate twice. — **Decided.**

## N2 — `IDCT\Adminata\` is the namespace; the test roots follow

`Sonata\AdminBundle\` → `IDCT\Adminata\` on `src/`, `Sonata\AdminBundle\Tests\` →
`IDCT\Adminata\Tests\` on `tests/`. Directories do not move: `src/` and `tests/` stay upstream's
layout, which is what the upstream sync (N16) still relies on. `Adminata\Tests\` on
`tests-adminata/` stays as it is: it is dev-only, ships in no archive, names nothing Sonata, and
nesting it under `IDCT\Adminata\Tests\` is the arrangement AGENTS.md §3 rejected for the optimised
autoload dump.

*Rejected:* `IDCT\AdminataBundle\` (the Symfony-2 habit of putting "Bundle" in the namespace; the
owner wrote `IDCT\Adminata\`). — **Decided** (namespace given by the owner).

## N3 — The bundle class is `IDCT\Adminata\AdminataBundle`

Not `IDCTAdminataBundle`. Symfony derives four names from the bundle class, and `AdminataBundle`
gives the short ones: Twig namespace `@Adminata`, override directory
`templates/bundles/AdminataBundle/`, published assets `public/bundles/adminata/`, container
extension `IDCT\Adminata\DependencyInjection\AdminataExtension` with the derived alias `adminata`.
EasyAdmin (`EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle`) and Doctrine
(`Doctrine\Bundle\DoctrineBundle\DoctrineBundle`) set the precedent of a bundle class without the
vendor prefix; the vendor lives in the namespace.

*Rejected:* `IDCTAdminataBundle` — `@IDCTAdminata`, `bundles/idctadminata/`,
`idct_adminata:`; longer everywhere and "IDCT" is already the namespace. — **Decided.**

## N4 — Classes named after Sonata are renamed by two token rules

`SonataAdmin` → `Adminata` first, then any remaining `Sonata` followed by an upper-case letter →
`Adminata`. In `src/`: `SonataAdminBundle` → `AdminataBundle`, `SonataAdminExtension` (DI and
Twig, different namespaces) → `AdminataExtension`, `AbstractSonataAdminExtension` →
`AbstractAdminataExtension`, `SonataAdminRuntime` → `AdminataRuntime`, `SonataConfiguration` →
`AdminataConfiguration`, `SonataBlockExtension` / `SonataFormExtension` / `SonataTwigExtension` /
`SonataExporterExtension` → `AdminataBlockExtension` / …, `SonataExporterException` →
`AdminataExporterException`. The 12 test classes follow (`FormSonataFilterChoiceWidgetTest` →
`FormAdminataFilterChoiceWidgetTest`, …). The renamed `Adminata*Extension` classes derive exactly
the config aliases N5 wants, so no `getAlias()` override is needed.

The consumers' bundle classes are explicit rules, because "Admin" is dropped where "Adminata"
already says it: `SonataDoctrineORMAdminBundle` → `AdminataDoctrineORMBundle`,
`SonataDoctrineMongoDBAdminBundle` → `AdminataDoctrineMongoDBBundle` (N19).

*Rejected:* keeping the `DependencyInjection\AdminataExtension` / `Twig\Extension\AdminataExtension`
pair apart with a `Twig` infix — upstream has the same pair under the same name and the sync
translation is simpler when the shape is kept. — **Decided.**

## N5 — Five config roots, renamed one-to-one, not folded

`sonata_admin` → `adminata`; `sonata_block` → `adminata_block`; `sonata_form` → `adminata_form`;
`sonata_twig` → `adminata_twig`; `sonata_exporter` → `adminata_exporter`. Each alias is what
Symfony derives from the renamed extension class; the five `TreeBuilder('…')` roots are explicit
rules ([02 §3](02-rename-map.md) R-root). The root `adminata` is the one place the general token
rule (`sonata_admin` → `adminata_admin`, N9) does not apply, so the root contexts are enumerated:
a YAML key at column 0, `TreeBuilder`, `prependExtensionConfig`, `loadFromExtension`,
`getExtensionConfig`, dotted config paths in prose (`sonata_admin.options.x` →
`adminata.options.x`), and the file names `sonata_admin.yaml` / `.php`.

**OQ1 — fold `adminata_{block,form,twig,exporter}` under `adminata:` as sections?** One bundle,
one root reads well, and the merged trees are ported by hand anyway, so the sync loses nothing.
Cost: five `Configuration` classes become one tree, four extension classes' `load()` and
`AdminataFormExtension::prepend()` fold into `AdminataExtension`, four `config-reference` contract
fixtures and four documentation pages merge, and the panel's `sonata_block.yaml` / `sonata_form.yaml`
become sections of `adminata.yaml`. About a day, touching only the DI layer, and it can be done
any time after the names are settled. — **Decided: one-to-one now; OQ1 needs owner, default no.**

## N6 — Service ids, parameters, tags, events and flash types: `sonata.` → `adminata.`

199 distinct `sonata.*` ids and parameters in `src/Resources/config`, the ten tags (`sonata.admin`,
`sonata.admin.audit_reader`, `sonata.admin.manager`, `sonata.admin.extension`,
`sonata.admin.filter.type`, `sonata.admin.template_registry`, `sonata.block`,
`sonata.block.loader`, `sonata.exporter.writer`, `sonata.status.renderer`), the 15
`sonata.admin.event.*` names and the flash types `sonata_flash_{success,error,info}` all take the
one rule: the `sonata` segment becomes `adminata`, the rest is kept — `adminata.admin.pool`,
`adminata.admin` (the tag every admin carries), `adminata.block.manager`,
`adminata.exporter.writer.csv`, `adminata_flash_success`. The `.admin.` segment stays because it
groups the admin bundle's services against `.block.`, `.form.`, `.twig.`, `.exporter.` and
`.doctrine.`; the panel's own admin ids (`sonata.admin.user.partner`, …) are the panel's to rename
or keep, only their tag changes — and they are kept ([04 §4](04-consumers.md)): the role security
handler derives `ROLE_<CODE>_<PERMISSION>` from every admin code. Two ids carry a class name
inside and follow N4 by explicit rule: `sonata.admin.twig.sonata_admin_extension` →
`adminata.admin.twig.adminata_extension`, `sonata.admin.twig.sonata_admin_runtime` →
`adminata.admin.twig.adminata_runtime`.

*Rejected:* `adminata.pool`, `adminata.block.manager` (dropping `.admin.` only) — a per-id
judgement and a second rule family for one word. — **Decided.**

## N7 — Routes, request attributes and console commands drop the redundant "admin"

The eight `sonata_admin_*` routes become `adminata_*` (`adminata_dashboard`, `adminata_search`,
`adminata_redirect`, `adminata_retrieve_form_element`, `adminata_append_form_element`,
`adminata_short_object_information`, `adminata_set_object_field_value`,
`adminata_retrieve_autocomplete_items`); the routing files become `routing/adminata.xml` and
`.php`; an application's `config/routes/sonata_admin.yaml` becomes `adminata.yaml` with
`resource: '@AdminataBundle/Resources/config/routing/adminata.xml'`. Admin CRUD routes
(`admin_<vendor>_<model>_<action>`) never had the name and do not change.

Request attributes: `_sonata_admin` → `_adminata_admin`, `_sonata_name` → `_adminata_name`,
`_sonata_csrf_token` → `_adminata_csrf_token`. Console: `sonata:admin:list` → `adminata:list`,
`sonata:admin:explain` → `adminata:explain`, `sonata:admin:setup-acl` → `adminata:setup-acl`,
`sonata:admin:generate-object-acl` → `adminata:generate-object-acl`, `debug:sonata:block` →
`debug:adminata:block`, `make:sonata:admin` → `make:adminata:admin`. All explicit rules — there
is no `sonata_admin_` prefix rule, because the form options of N9 share that prefix and keep it.
— **Decided.**

## N8 — One Twig namespace, no aliases; functions, globals and blocks take the `adminata` prefix

`@SonataAdmin/` → `@Adminata/`. `@SonataBlock/`, `@SonataForm/` and `@SonataTwig/` (kept in 1.0
as aliases of the same directory for templates outside adminata) are **removed**, and
`TwigNamespaceAliasCompilerPass` is deleted: an alias to a retired name is the compatibility layer
the owner ruled out, and every consumer is renamed in the same series. The rule maps the three to
`@Adminata/` so that nothing outside adminata breaks silently.

Functions and filters: `sonata_block_render*`, `sonata_block_exists`,
`sonata_block_include_*`, `sonata_flashmessages_{get,types,class}`, `sonata_status_class`,
`sonata_theme`, `sonata_html_dir`, `get_sonata_dashboard_groups_with_creatable_admins` → the same
with `adminata`. Globals: `sonata_admin` → `adminata_admin`, `sonata_config` → `adminata_config`.
Block names — 51 carry the prefix: 33 layout and page blocks (`sonata_wrapper`, `sonata_header`,
`sonata_nav`, `sonata_left_side`, `sonata_breadcrumb`, `sonata_page_content`, `sonata_overlay`,
`sonata_script_attributes`, `sonata_head_title`, `sonata_form_actions`, `sonata_top_nav_menu_*`,
`sonata_mosaic_*`, `sonata_sidebar_search`, …) and 18 form-theme blocks (`sonata_type_*_widget`,
`…_widget_row`, `…_format`) — take `adminata_`; `sonata_admin_content` and
`sonata_admin_content_actions_wrappers` take `adminata_content…` (one explicit prefix rule, N7's
spirit); the form-theme blocks follow N9's prefixes. Block names are the one 1.0 contract
row this deliberately breaks (PLAN/02 §5: block names frozen) — under a major, re-issued under
the new names. — **Decided.**

## N9 — Form type prefixes and options

The 32 block prefixes: `sonata_type_` → `adminata_type_` (`adminata_type_model`,
`adminata_type_collection`, `adminata_type_native_collection`, …); `sonata_block_service_choice`
→ `adminata_block_service_choice`. The two `CollectionType`s keep the arrangement of PLAN/01 P14.

Form options and view variables keep the word "admin" because they name *the admin of a field*,
not the product: `sonata_admin` → `adminata_admin`, `sonata_admin_enabled` →
`adminata_admin_enabled`, `sonata_admin_code` → `adminata_admin_code`,
`sonata_admin_translation_domain` → `adminata_admin_translation_domain`,
`sonata_field_description` → `adminata_field_description`, `sonata_deprecation_mute` →
`adminata_deprecation_mute` (`sonata_help` is not among them: upstream 4.x dropped it for
Symfony's own `help`). This is the general token rule
`sonata_admin` → `adminata_admin`, which is why the config root (N5) is the enumerated exception
and not the other way round. — **Decided.**

## N10 — Translation domain `AdminataBundle`

`SonataAdminBundle` → `AdminataBundle` as a domain, and the 35 catalogue files are `git mv`'d to
`AdminataBundle.<locale>.xliff`. Six unit ids carry the name and follow their subject:
`sonata_administration` → `adminata_administration`, `sonata.block.service.{container,menu,rss,
template,text}` → `adminata.block.service.*` (they are the block service ids of N6). The other
161 ids (`btn_create`, `link_action_list`, …) never had it. `validators` is untouched. An
application that overrode strings in `translations/SonataAdminBundle.<locale>.xliff` moves the
file; the ids it carried are unchanged unless they are among the six.

*Rejected:* a lower-case `adminata` domain — Symfony's bundles name their domain after the bundle
(`KnpMenuBundle`, `FOSUserBundle`), and the panel's override file moves either way. — **Decided.**

## N11 — Markup hooks: `adminata-*`, and the `ba` goes

`sonata-ba-*` → `adminata-*`; every other `sonata-*` → `adminata-*`. Measured: 65 class and id
tokens in the templates and CSS (33 of them `ba`), 21 Stimulus identifiers, 73 data-attribute
names, and 203 distinct `sonata-*` strings in all once attribute fragments and the vendor name
are counted. Element ids the same (`#sonata-content` →
`#adminata-content`, `#sonata-dialog`, `#sonata-question-dialog`, `#sonata-search-input`).
`objectId`, button `name` attributes, `.adm-*` components and Tailwind utilities are not Sonata
names and do not change. `sonata-project` (the vendor, in headers and URLs) and
`sonata-admin-mongodb-bundle` (a package name) are excluded from the hyphen rule by lookahead.

Dropping `ba` ("base admin", a Sonata-ism with no meaning here) collides twice, measured:
`sonata-ba-content` vs `#sonata-content`, and `sonata-ba-tabs` vs `sonata-tabs`. Both get an
explicit rule in R1-01 after reading the templates: the proposal is `adminata-content` for both
the class and the id (a class and an id may share a token; the hooks ledger records the kind) and
`adminata-tabs` for both if they mark the same element, else `adminata-tab-nav` for the `ba` one.

**OQ3 — keep `ba` (`adminata-ba-list-field`)?** Zero collisions, one rule fewer, and the panel's
~80 hook usages change by prefix only either way. — **Decided: drop `ba`; OQ3 needs owner,
default drop.**

## N12 — JavaScript

Stimulus identifiers `sonata-<name>` → `adminata-<name>` for all 21 controllers; data attributes
(`data-sonata-modal-target` → `data-adminata-modal-target`, 70+ distinct names), outlets
(`data-sonata-filter-sonata-filter-list-outlet` → `data-adminata-filter-adminata-filter-list-outlet`)
and dispatched events (`sonata-modal:opened` → `adminata-modal:opened`) follow from the identifier
by Stimulus's own conventions; `stimulus_controller('sonata-modal')` calls in Twig take the string
rule. Camel-case names (`sonataAdmin`, `sonataConfiguration`, `sonataAutocompleteId`,
`defaultSonataDoctrineConfig`) follow the casing rules; `window.sonataApplication` →
`window.adminataApplication`. The contract snapshot `assets/js/__contract__/controllers.json` is
regenerated, not edited. — **Decided.**

## N13 — Cookies, the localStorage key and the menu alias

Two cookies: `sonata_theme` → `adminata_theme`, `sonata_sidebar_hide` → `adminata_sidebar_hide`.
The menu controller's localStorage key `sonata_sidebar_open` → `adminata_sidebar_open`. And,
because it looked like a cookie until the templates were read, the KnpMenu alias
`sonata_admin_sidebar` → `adminata_sidebar` (explicit rule): `knp_menu_render('sonata_admin_sidebar')`
in an overridden layout is an edit. A user's theme, sidebar and open-sections preferences reset
once; documented in UPGRADE.md. — **Decided.**

## N14 — Published assets `public/bundles/adminata/`

Derived from N3. `bin/write-manifests.mjs`, `vite.config.js`'s base, the committed
`entrypoints.json` / `manifest.json`, `.size-limit.json` and the demo's
`tests-adminata/App/public/bundles/sonataadmin` symlink (→ `adminata`) follow; `make assets-check`
proves the committed output. An application's `assets.stylesheets` / `remove_stylesheets` entries
that named `/bundles/sonataadmin/…` are edits. — **Decided.**

## N15 — Composer: conflict, not replace

adminata no longer provides `Sonata\AdminBundle\`, so `replace: sonata-project/admin-bundle` would
let Composer install a Sonata extension that fatals at runtime. `replace` goes; `conflict` lists
all six forked packages at `*` (`admin-bundle` joining the five merged ones), and the ORM layer
and the MongoDB fork do the same for theirs. `bin/check-replace-versions.php` becomes
`bin/check-upstream-versions.php`: `conflict` × 6 against `upstream/remotes.txt`, and the
**Tag** column of `UPSTREAM.md` stays the record of the upstream release each tree sits at.
`tests-adminata/Contract/ReplaceTest.php` becomes `ConflictTest.php`.

Metadata: the keyword `sonata` is dropped (a keyword is naming), the description keeps "a hard
fork of the Sonata Admin stack" (attribution), the `suggest` lines say adminata rather than
Sonata, and `authors` keeps Thomas Rabaix and the Sonata Community as original authors. The
`sonata-project/entity-audit-bundle` dev dependency is a third party's package and keeps its name;
it requires no Sonata bundle, so the conflicts do not touch it.

The XML configuration namespaces the extensions announce — `https://sonata-project.org/schema/dic/admin`
and `http://sonata-project.com/schema/dic/block` — become `https://idct.tech/schema/dic/adminata`
and `https://idct.tech/schema/dic/adminata_block`; no XSD ships, so the URI is the whole change.
— **Decided.**

## N16 — The upstream sync survives as a translated three-way merge

Today `upstream/sync.sh` applies upstream's diff to `src/` and `tests/` with `git apply -3`. After
the rename the pre-image no longer matches, so the sync becomes, per changed file: *base′* =
engine(upstream file at FROM), *theirs′* = engine(upstream file at TO), *ours* = our file, then
`git merge-file ours base′ theirs′`; added files are engine(theirs), deleted files are deleted,
paths go through the path rules. Conflict markers are the same hand work `.rej` files are today.
The exclusion lists and `upstream/merged.txt` are unchanged, the five merged trees are still
ported by hand through the class maps, and `upstream/diff.sh` still reports untranslated. The
rule file is therefore permanent infrastructure, not a migration script, and lives under
`upstream/rename/`. — **Decided.**

## N17 — What stays as attribution

Untouched: the upstream header on inherited files, the combined header on adminata's own, the
CS-Fixer configuration that enforces both, `LICENSE`, `NOTICE`, `src/Resources/meta/LICENSE`,
`CHANGELOG-sonata.md`, `changelog/`, `upstream/remotes.txt`, `upstream/merged.txt`,
`docs/conf.py`'s copyright line, `composer.json` `authors`. Edited only where a sentence stops
being true (it says adminata keeps the upstream namespace, or `replace`s a package): `UPSTREAM.md`,
`MIGRATION.md`, `UPGRADE-1.0.md`, `README.md`, `AGENTS.md`, `CONTRIBUTING.md`, `docs/index.rst`.
Added: an **Origins** section in `README.md` and `docs/index.rst` ([03 §2](03-attribution-and-history.md))
and a CHANGELOG entry naming the last Sonata-named commit. — **Decided.**

## N18 — Git

No history rewrite, no force push, no squash of the series. File renames use `git mv`. The
mechanical rewrite is **one commit** whose diff equals the engine's output on its parent (an
Accept line of R1-02); hand edits follow in their own commits so that a reader can tell rule from
judgement. Subject lines say what moved: "Rename the bundle to IDCT\Adminata: mechanical pass",
"…: config roots and Twig aliases", "…: contracts", "…: documents". The upstream remotes and
`refs/upstream/*` stay. — **Decided.**

## N19 — Consumers ([04](04-consumers.md))

ORM layer: `IDCT\Adminata\DoctrineORM\`, `AdminataDoctrineORMBundle`, root `adminata_doctrine_orm`,
`@AdminataDoctrineORM`, ids by N6, `conflict` with `sonata-project/doctrine-orm-admin-bundle`;
package name unchanged; v2.0.0. MongoDB fork: `IDCT\Adminata\DoctrineMongoDB\`,
`AdminataDoctrineMongoDBBundle`, root `adminata_doctrine_mongodb` (explicit `getAlias()`, since
the derived one would be `adminata_doctrine_mongo_db`), `@AdminataDoctrineMongoDB`; v7.0.0.
Nesting a package's namespace under `IDCT\Adminata\` is the Flysystem-adapter pattern
(`League\Flysystem\AwsS3V3\` beside `League\Flysystem\`); adminata's `src/` must never grow a
`DoctrineORM/` or `DoctrineMongoDB/` directory, and a contract test says so.

**OQ2 — the MongoDB fork's package and repository name.** `idct/sonata-admin-mongodb-bundle` is a
Sonata name and it is on Packagist. Default: rename the GitHub repository to
`ideaconnect/adminata-admin-mongodb-bundle` (GitHub redirects the old URL), publish
`idct/adminata-admin-mongodb-bundle` as a new Packagist package and mark the old one
abandoned with the new as replacement; 7.0.0 is the first release under the new name. The panel's
`composer.json` names the new package. — **Decided on the names; OQ2 needs owner (Packagist and
GitHub are the owner's accounts), default rename.**

## N20 — The gate

`make check-names` runs the engine in `--check` mode: it fails if any rule's left-hand side
matches anywhere outside the allow-list ([02 §5](02-rename-map.md)). The allow-list is files
(the attribution and history files of N17, `PLAN/`, `PROJECT_PLAN.md`, `CHANGELOG*.md`,
`MIGRATION.md`, `UPGRADE-*.md`, `docs/upgrading.rst`, `upstream/`) and phrases (`sonata-project`,
`sonata-project.org`, the foreign bundle names the cookbook pages mention). The gate joins
`make lint` and AGENTS.md §5. Run on an application (`--app`), engine and gate use the generated
lists of the names adminata, the ORM layer and the MongoDB fork own, and *report* the
application's own `sonata…` names rather than rewrite them ([02 §1](02-rename-map.md)). It is the reason the README's Origins section names *packages*
(`sonata-project/admin-bundle`) and *people*, never the old class names — those live only in the
upgrade documents, which the gate skips. — **Decided.**

## N21 — Documentation

The engine runs over `docs/` like any tree (code samples are identifiers). Prose is read page by
page in R2-02: a sentence about adminata's current identity is rewritten, a sentence about the
origin stays. `docs/admin-bundle/` keeps its directory name (it names the upstream tree, is
excluded from syncs, and is not an API); `recipe_sonata_admin_without_user_bundle.rst` and the
three `sonata_*.png` images are renamed for hygiene. A short `docs/origins.rst` joins the
adminata toctree. — **Decided.**

## N22 — Out of scope

Folding roots (OQ1), merging `tests-adminata/` into `tests/`, restructuring the docs tree,
renaming the GitHub organisation or the `idct/` vendor, any change to behaviour. A behaviour
change discovered during the rename becomes a task of its own, never a passenger in a rename
commit. — **Decided.**
