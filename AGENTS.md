# AGENTS.md

Project-specific instructions for any AI coding agent (or human contributor) working on
`idct/adminata`. Read this end-to-end before touching code. It is the short form; the long form is
[PLAN/](PLAN/README.md) (design) and [PROJECT_PLAN.md](PROJECT_PLAN.md) (the task list).

---

## 1. What this project is

adminata is a **hard fork** of seven `sonata-project` packages, shipped as one Composer package
`idct/adminata` that `replace`s all seven. The PHP layer stays Sonata's; the Twig templates, CSS and
JavaScript are replaced with a Tailwind CSS v4 / TailAdmin user interface.

It is not an overlay, not a theme bundle and not a compatibility layer. There is no
`sonata-project/*` package installed alongside it — installing adminata makes those packages
uninstallable, which is the point.

The first release supports what the production application `recomaty-panel` uses
([PLAN/appendix-C-recomaty-panel-scope.md](PLAN/appendix-C-recomaty-panel-scope.md)); everything else
is ported when first needed. `idct/sonata-admin-mongodb-bundle` must keep working on top of adminata
at every milestone.

---

## 2. Owner directives (non-negotiable)

1. **Hard fork, not an override.** Rewrite the upstream templates in place; never add a parallel
   template tree, a `.sonata-bc` scope, dual class names or a Bootstrap shim.
2. **No jQuery, anywhere.** Not as a dependency, not as a peer, not through another library.
   `npm ls jquery` must come back empty, and ESLint bans `$`, `jQuery` and importing `jquery`.
   When a behaviour needs more than plain DOM code: first check whether Tailwind/TailAdmin already
   covers it in CSS (dropdown panels, native `<dialog>`, `<details>` accordions); otherwise pick a
   modern, actively released, jQuery-free vanilla library. Vetted candidates: SortableJS,
   vanilla-calendar-pro, Tom Select.
3. **No compatibility layers.** Unused configuration nodes are removed, not deprecated. Missing
   features are developed as they come up.
4. **Latest releases, pinned exactly.** Nothing inherits Sonata's pins
   ([PLAN/07 §2](PLAN/07-packaging-and-project-setup.md)).
5. **No AJAX form submission** in any phase. `ajaxSubmit` is gone for good.
6. **No inline scripts and no `onclick`** in adminata templates, except the three-line theme
   pre-paint script rendered under the `sonata_script_attributes` block (for a CSP nonce). Every
   script tag carries `defer`.
7. **Seven packages, one project.** No sub-bundles of our own, no eighth bundle class.
8. **Push `main` after every milestone** (`Pn-MS` tasks), plan revision and tag.

---

## 3. Layout

```
packages/<upstream-name>/{src,tests,docs}   the seven forked trees, imported with `git subtree`
assets/{css,js,images}                      adminata's own UI sources (built by Vite)
packages/admin-bundle/src/Resources/public  committed build output (bundles/sonataadmin/)
tests/{App,Unit,Functional,Contract,Visual} adminata-level suites and the demo application
upstream/                                   remotes, per-package exclusion lists, diff/sync scripts
PLAN/                                       the design; PROJECT_PLAN.md the task list
```

The seven upstream namespaces map onto `packages/*/src` from the root `composer.json`. There is one
`composer.json`, one `phpunit.xml.dist`, one PHPStan, Rector and CS-Fixer configuration for the
whole repository — the per-package ones were deleted at import.

---

## 4. Contracts you must not break

[PLAN/02-compatibility-contract.md](PLAN/02-compatibility-contract.md) is the full list; it is
enforced by `make test-contract`. In short:

- **Namespaces, bundle classes, config roots, service ids, routes, translation domains** — frozen.
- **Template paths and template-registry keys** — frozen. A rewritten template keeps its file name
  and its Twig **block names** (`admin_lte_skin_class` and `bootlint` are the only removals);
  additive blocks are allowed.
- **Markup hooks** the PHP layer or an application selects on: `sonata-*` and `sonata-ba-*` class
  names, element ids, `objectId`, `data-sonata-*` attributes, button `name` attributes, and the
  literal strings the PHP layer emits. Tailwind utilities and `.adm-*` components carry the styling;
  the hooks carry the meaning.
- **JavaScript**: `sonata-<name>` Stimulus identifiers, their targets, values and dispatched event
  names, snapshotted in `assets/js/__contract__/controllers.json`.
- **The MongoDB fork**: its two form themes extend `@SonataAdmin/Form/{form,filter}_admin_fields.html.twig`
  and its `ListBuilder` hard-codes `@SonataAdmin/CRUD/list__action*.html.twig`.

---

## 5. Definition of done

A task is finished when **all** of these are green — running one and declaring victory is not enough:

```bash
make lint          # php-cs-fixer, composer-normalize, yamllint, xmllint, lint:twig/container/xliff/yaml
make phpstan       # level 8 + bleedingEdge + strict, no new baseline entries
make rector        # --dry-run must be clean
make test          # every PHPUnit suite: the seven imported ones and adminata's own
make test-contract # the frozen interfaces of PLAN/02
make lint-js       # ESLint 10, Stylelint 17, Prettier 3.9, and the jQuery gate
make test-js       # Vitest 5
make assets-check  # rebuild + `git diff --exit-code` on the committed output + CSS contract + size budgets
```

Plus, for anything that changes rendering: the demo application renders it in **both** themes, and
the Playwright/axe/Panther coverage of the milestone passes.

---

## 6. Style and conventions

**PHP** (same rules as `idct/sonata-admin-mongodb-bundle`):

- `declare(strict_types=1);` in every file; the Sonata header comment stays on inherited files, the
  combined adminata header goes on new ones (CS-Fixer enforces both).
- New classes are `final`; stateless services are `final readonly`.
- PHPStan level 8 clean. **No** `@phpstan-ignore`, no `assert()` to narrow a type, no inline `@var`
  to silence the analyser, no new baseline entries — fix the cause.
- Every behaviour has a test that locks it from the public API surface.
- Do not touch upstream PHP outside the list in [PLAN/01 P6](PLAN/01-architecture-decisions.md);
  everything else is synced from upstream and must stay mergeable.

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

See [UPSTREAM.md](UPSTREAM.md) and [PLAN/07 §10](PLAN/07-packaging-and-project-setup.md). The PHP
side of an upstream release is applied mechanically:

```bash
make upstream-diff PKG=admin-bundle FROM=4.43.0 TO=4.44.0   # report for the sync issue
make upstream-sync PKG=admin-bundle TO=4.44.0               # apply, then run the gates
```

Paths adminata owns are listed in `upstream/exclude/<name>.txt` and are never taken from upstream;
upstream UI changes are re-implemented by hand with a CHANGELOG line "Ported upstream `<pkg>`#NNNN".
Never merge `admin-bundle` 5.x before adminata 2.0.

---

## 8. Working on the codebase

- Work the tasks in [PROJECT_PLAN.md](PROJECT_PLAN.md) top to bottom inside a milestone and respect
  `depends:`. Tick a task only when every line under its **Accept** passes; record blockers as dated
  bullets and finished work in the status log.
- If the plan turns out to be wrong, change the plan in the same commit and say so in the status log.
- New work discovered mid-task becomes a new task in the same milestone, never a silent extension of
  the current one.
- Reference checkouts on the maintainer's machine: the application
  `/home/bartosz/dev/r3/recomaty-panel-clean`, the MongoDB fork
  `/home/bartosz/dev/idct/sonata-admin-mongodb-bundle`. TailAdmin is fetched into a scratch
  directory when needed and never vendored.
- Don't generate documentation files unless asked.
