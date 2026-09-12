# AGENTS.md

Project-specific instructions for any AI coding agent (or human contributor) working on
`idct/adminata`. Read this end-to-end before touching code. It is the short form; the long form is
[PLAN/](PLAN/README.md) (the design of 1.0), [PLAN/v2/](PLAN/v2/README.md) (the rename to
adminata's own names) and [PROJECT_PLAN.md](PROJECT_PLAN.md) (the task list).

---

## 1. What this project is

adminata is an admin bundle for Symfony with a Tailwind CSS v4 / TailAdmin user interface, shipped
as one Composer package `idct/adminata` and one bundle, `IDCT\Adminata\AdminataBundle`. It is a
**hard fork** of seven `sonata-project` packages, imported with their history on 2026-09-04: the
PHP design — admin classes, mappers, datagrid, routing, security handlers, exporter — is theirs
under new names, and their sources keep the upstream copyright headers. Five of the seven are
**part of this bundle** (owner directives of 2026-09-06 and 2026-09-07; PLAN/01 P10, P13, P14,
P15 and P16): `block-bundle`, `doctrine-extensions`, `exporter`, `form-extensions` and
`twig-extensions` live under `IDCT\Adminata\` (`Block\`, `Doctrine\`, `Exporter\`, `Form\`,
`Twig\`, …), and there is no bundle class, translation domain or override directory for any of
them. The sixth, `doctrine-orm-admin-bundle`, is a **repository of its own** since 2026-09-07
(PLAN/01 P17): `ideaconnect/adminata-doctrine-orm-admin-bundle`, `IDCT\Adminata\DoctrineORM\`,
a dev dependency here because the demo application and adminata's own suites are built on
Doctrine ORM. The MongoDB layer, `idct/adminata-admin-mongodb-bundle`
(`IDCT\Adminata\DoctrineMongoDB\`), is the owner's separate package and must keep working on top
of adminata at every milestone.

Since 2026-09-12 (PLAN/v2) nothing in the API carries the Sonata name: the namespace, the bundle
class, the configuration roots, every service id, tag, parameter and event, the routes, the Twig
namespace, functions, globals and block names, the form type prefixes, the translation domain,
the markup hooks, the Stimulus identifiers, the cookies and the published asset path are
adminata's own. `composer.json` **conflicts** with every `sonata-project` package it forked and
replaces none — installing adminata makes those packages uninstallable, which is the point. It is
not an overlay, not a theme bundle and not a compatibility layer.

The first release supports what the production application `recomaty-panel` uses
([PLAN/appendix-C-recomaty-panel-scope.md](PLAN/appendix-C-recomaty-panel-scope.md)); everything else
is ported when first needed.

---

## 2. Owner directives (non-negotiable)

1. **Hard fork, not an override.** Rewrite the upstream templates in place; never add a parallel
   template tree, a "legacy" CSS scope, dual class names or a Bootstrap shim.
2. **No jQuery, anywhere.** Not as a dependency, not as a peer, not through another library.
   `npm ls jquery` must come back empty, and ESLint bans `$`, `jQuery` and importing `jquery`.
   When a behaviour needs more than plain DOM code: first check whether Tailwind/TailAdmin already
   covers it in CSS (dropdown panels, native `<dialog>`, `<details>` accordions); otherwise pick a
   modern, actively released, jQuery-free vanilla library. Vetted candidates: SortableJS,
   vanilla-calendar-pro, Tom Select.
3. **No compatibility layers.** Unused configuration nodes are removed, not deprecated. Missing
   features are developed as they come up. No alias, shim or "kept for now" spelling of a name.
4. **Latest releases, pinned exactly.** Nothing inherits Sonata's pins
   ([PLAN/07 §2](PLAN/07-packaging-and-project-setup.md)).
5. **No AJAX form submission** in any phase. `ajaxSubmit` is gone for good.
6. **No inline scripts and no `onclick`** in adminata templates, except the three-line theme
   pre-paint script rendered under the `adminata_script_attributes` block (for a CSP nonce). Every
   script tag carries `defer`.
7. **Seven forked trees, one project.** No sub-bundles of our own, and no bundle class beyond
   `AdminataBundle` and the storage layers' own. Blocks, form types, the Twig helpers and the
   exporter are not usable without the admin bundle in this fork, so they do not earn bundles of
   their own either.
8. **No Sonata-named identifier, ever** (PLAN/v2 N20). `make check-names` is the gate: nothing
   outside the history and upgrade documents may match a rule of `upstream/rename/rules.php`, and
   nothing may say the word at all except the attribution phrases `upstream/rename/allow.php`
   lists. The acknowledgement stays — "a hard fork of Sonata Admin", the upstream headers,
   `NOTICE`, `UPSTREAM.md` — the *names* do not. A new name is spelled `adminata…` from the start.
9. **Push `main` after every milestone** (`Pn-MS` tasks), plan revision and tag.

---

## 3. Layout

```
src/                                        the bundle — `IDCT\Adminata\`
tests/                                      its suite — `IDCT\Adminata\Tests\`
assets/{css,js,images}                      adminata's own UI sources (built by Vite)
src/Resources/public                        committed build output (bundles/adminata/)
tests-adminata/{App,Unit,Functional,…}      adminata-level suites and the demo application,
                                            `Adminata\Tests\`
changelog/                                  the inherited upstream histories
upstream/                                   remotes, merge record, exclusion lists, diff/sync
upstream/rename/                            the rename engine, its rules, allow-list and known names
docs/                                       one Sphinx site (`make docs`)
PLAN/, PLAN/v2/                             the designs; PROJECT_PLAN.md the task list
```

`src/` and `tests/` are upstream's own layout, which is the point: `upstream/sync.sh` translates
an upstream release through the engine and merges it here with no directory prefix at all. Hence
the second test root — the bundle's suite is `tests/`, and adminata's own suites keep a directory
beside it rather than a namespace nested inside one, which would make every optimised autoload
dump warn.

One PSR-4 entry maps `IDCT\Adminata\` onto `src/`. The merged trees' DI service files are named
flat inside the bundle (`block_*.php`, `form_ext_types.php`, `form_validator.php`,
`twig_flash.php`, `twig_ext.php`, `exporter_services.php`, `doctrine*.php`) so they cannot
collide with admin's own. There is one `composer.json`, one `phpunit.xml.dist`, one PHPStan,
Rector and CS-Fixer configuration for the whole repository.

`src/` must never grow a `DoctrineORM/` or `DoctrineMongoDB/` directory: those namespaces belong
to the storage layers' packages (`NamespaceContractTest`).

---

## 4. Contracts you must not break

[PLAN/02-compatibility-contract.md](PLAN/02-compatibility-contract.md) is the full list, re-issued
under the new names by PLAN/v2; it is enforced by `make test-contract`. In short:

- **Namespace, bundle class, config roots, service ids, routes, translation domain** — frozen at
  their adminata spellings: `IDCT\Adminata\`, `AdminataBundle`, `adminata` /
  `adminata_{block,form,twig,exporter}`, `adminata.*`, `adminata_*`, `AdminataBundle`. The
  `adminata_block_*` and `adminata_flashmessages_*` Twig functions, the `adminata_status_class`
  filter, the `adminata.status.renderer` and `adminata.exporter.writer` tags are part of it. There
  is exactly **one** Twig namespace for this bundle, `@Adminata`; every default inside adminata
  says it, so an application overrides in `templates/bundles/AdminataBundle/`. No alias may come
  back.
- **Two `CollectionType`s, and they exchanged names in 1.0.** `IDCT\Adminata\Form\Type\CollectionType`
  is form-extensions' (`adminata_type_collection`); admin's old one is `NativeCollectionType`
  (`adminata_type_native_collection`). Both are alive and both block prefixes are frozen. Check
  which one you mean before touching an import or a widget block — the wrong one compiles and
  renders the other widget.
- **Template paths and template-registry keys** — frozen. A rewritten template keeps its file name
  and its Twig **block names** (`admin_lte_skin_class` and `bootlint` are the only removals from
  upstream's list; the rest carry the `adminata_` spelling the engine gives them); additive
  blocks are allowed.
- **Markup hooks** the PHP layer or an application selects on: `adminata-*` class names, element
  ids, `objectId`, `data-adminata-*` attributes, button `name` attributes, and the literal strings
  the PHP layer emits. Tailwind utilities and `.adm-*` components carry the styling; the hooks
  carry the meaning.
- **JavaScript**: `adminata-<name>` Stimulus identifiers, their targets, values and dispatched
  event names, snapshotted in `assets/js/__contract__/controllers.json`.
- **The MongoDB layer**: its two form themes extend `@Adminata/Form/{form,filter}_admin_fields.html.twig`
  and its `ListBuilder` hard-codes `@Adminata/CRUD/list__action*.html.twig`.
- **The engine's known names** (`upstream/rename/known.txt`): the exact list of what adminata,
  the ORM layer and the MongoDB layer own, generated from the last Sonata-named trees. A new
  name never needs adding (it is spelled `adminata…` from birth); a rule change needs a case in
  `tests-adminata/Unit/Rename/EngineTest.php`.

---

## 5. Definition of done

A task is finished when **all** of these are green — running one and declaring victory is not enough:

```bash
make lint          # php-cs-fixer, composer-normalize, yamllint, xmllint, lint:twig/container/xliff/yaml, check-names
make phpstan       # level 8 + bleedingEdge + strict, no new baseline entries
make rector        # --dry-run must be clean
make test          # every PHPUnit suite: the imported ones and adminata's own
make test-contract # the frozen interfaces of PLAN/02 under their PLAN/v2 names
make lint-js       # ESLint 10, Stylelint 17, Prettier 3.9, and the jQuery gate
make test-js       # Vitest 5
make assets-check  # rebuild + `git diff --exit-code` on the committed output + CSS contract + size budgets
```

Plus, for anything that changes rendering: the demo application renders it in **both** themes, and
the Playwright/axe/Panther coverage of the milestone passes.

---

## 6. Style and conventions

**PHP** (same rules as the MongoDB layer's):

- `declare(strict_types=1);` in every file; the Sonata header comment stays on inherited files, the
  combined adminata header goes on new ones (CS-Fixer enforces both).
- New classes are `final`; stateless services are `final readonly`.
- PHPStan level 8 clean. **No** `@phpstan-ignore`, no `assert()` to narrow a type, no inline `@var`
  to silence the analyser, no new baseline entries — fix the cause.
- Every behaviour has a test that locks it from the public API surface.
- Do not touch upstream PHP outside the list in [PLAN/01 P6](PLAN/01-architecture-decisions.md);
  everything else is synced from upstream and must stay mergeable — through the engine.

**Twig**: `stimulus_controller()` / `stimulus_target()` / `stimulus_action()` from
`symfony/stimulus-bundle`, never hand-written `data-controller` strings. Show and hide with the
`hidden` **attribute**, never a class. A template transcribed from a TailAdmin partial names it in a
leading Twig comment.

**JavaScript**: ES2022 modules, one Stimulus controller per file
(`assets/js/controllers/<name>_controller.js`), registered explicitly in `assets/js/registry.js`, one
Vitest file per controller run against fixtures dumped from the real Twig templates. Events go
through `this.dispatch()`; inherited event names keep their exact spelling.

**CSS**: [PLAN/04](PLAN/04-css-architecture.md). Single-selector primitives are `@utility adm-*`,
descendant rules go in `@layer components`, every `.adm-*` name is safelisted and asserted by
`contract.json`. No Bootstrap or AdminLTE class name may appear in adminata markup or CSS.

**Git**: one branch per PROJECT_PLAN task (`task/P0-01-import-packages`), commit subjects
`P0-01: <what>`, and every commit ends with the
`Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>` trailer. `main` is pushed only in
milestone tasks and after plan revisions.

---

## 7. Upstream syncs

See [UPSTREAM.md](UPSTREAM.md) and [PLAN/07 §10](PLAN/07-packaging-and-project-setup.md). Upstream
speaks the Sonata names and this repository does not, so an upstream release is never applied as a
patch: every file it touched is translated through `upstream/rename/apply.php` — the base tag and
the head tag both — normalised by php-cs-fixer, and merged three-way onto ours:

```bash
make upstream-diff PKG=admin-bundle FROM=4.43.0 TO=4.44.0   # report for the sync issue, with the owned part translated
make upstream-rehearse PKG=admin-bundle TO=4.44.0           # dry run in a scratch worktree
make upstream-sync PKG=admin-bundle TO=4.44.0               # apply, then run the gates
```

Paths adminata owns are listed in `upstream/exclude/<name>.txt` and are never taken from upstream;
upstream UI changes are re-implemented by hand with a CHANGELOG line "Ported upstream `<pkg>`#NNNN".
Never merge `admin-bundle` 5.x before adminata 2.0.

The five trees in `upstream/merged.txt` are the exceptions: `make upstream-diff PKG=block-bundle`
still reports (translated), but they have no tree of their own for a merge to land in, so
`make upstream-sync` refuses them. **Every upstream change to those five is ported by hand.**
`doctrine-orm-admin-bundle` is not adminata's to sync at all any more — it has its own repository,
its own `UPSTREAM.md` and its own remote.

---

## 8. Working on the codebase

- Work the tasks in [PROJECT_PLAN.md](PROJECT_PLAN.md) top to bottom inside a milestone and respect
  `depends:`. Tick a task only when every line under its **Accept** passes; record blockers as dated
  bullets and finished work in the status log.
- If the plan turns out to be wrong, change the plan in the same commit and say so in the status log.
- New work discovered mid-task becomes a new task in the same milestone, never a silent extension of
  the current one.
- Reference checkouts on the maintainer's machine: the application
  `/home/bartosz/dev/r3/recomaty-panel-clean`, the ORM layer
  `/home/bartosz/dev/idct/adminata-doctrine-orm-admin-bundle`, the MongoDB layer
  `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle` (the directory keeps the old name; the
  repository is `ideaconnect/adminata-admin-mongodb-bundle`). TailAdmin is fetched into a scratch
  directory when needed and never vendored.
- Don't generate documentation files unless asked.
