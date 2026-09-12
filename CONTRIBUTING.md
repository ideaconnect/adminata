# Contributing to adminata

adminata is a hard fork maintained by [IDCT](https://idct.tech). Issues and pull requests are
welcome; please read [AGENTS.md](AGENTS.md) first — it holds the conventions, the frozen contracts
and the definition of done that every change is measured against.

## Reporting

- **Bugs**: the Symfony version, the PHP version, the admin/field configuration that reproduces it,
  and what the page rendered. Screenshots in both themes help for UI bugs.
- **Missing Sonata features**: adminata 1.0 deliberately ships a subset
  ([PLAN/appendix-C-recomaty-panel-scope.md](PLAN/appendix-C-recomaty-panel-scope.md)). Templates
  that are not ported yet carry a `{# adminata: not yet ported #}` comment and render the inherited
  Bootstrap markup. Say which one you need; the backlog in
  [PROJECT_PLAN.md](PROJECT_PLAN.md) is ordered by demand.
- **Upstream Sonata bugs** in PHP that adminata inherited: report them upstream too, so the fix
  arrives through the normal sync.

## Pull requests

1. Branch off `main`, one topic per branch.
2. Keep the [contracts](PLAN/02-compatibility-contract.md) intact, or say explicitly in the
   description which one you are breaking and why.
3. Run the full definition of done (AGENTS.md §5). If a gate cannot run in your environment (no
   Firefox for Panther, no MongoDB), say so in the description instead of skipping silently.
4. Add a `CHANGELOG.md` entry under `Unreleased`.
5. Every commit ends with a `Co-Authored-By:` trailer when it was written with an assistant.

## Semantic versioning contract

adminata versions itself from **1.0.0**; the upstream versions each forked tree sits at are tracked separately in [UPSTREAM.md](UPSTREAM.md).

| Change | Release |
|---|---|
| PHP API break; removal of a template file, a template-registry key, a config node, a service id or a Twig block listed in [PLAN/02 §5](PLAN/02-compatibility-contract.md) (under the names PLAN/v2 gave them) | **major** |
| Markup rewritten while keeping those blocks and the `adminata-*` hooks; new blocks; new Stimulus controllers, targets or events; a new config node; a BC upstream PHP sync (which also bumps the corresponding `replace` version in the same release) | **minor** |
| CSS-only changes; bug fixes that keep the markup contract | **patch** |

An upstream sync never lands alone: the same release bumps the package's row in
[UPSTREAM.md](UPSTREAM.md) and `upstream/remotes.txt`.

## Licence

By contributing you agree that your work is published under the MIT licence of this repository
([LICENSE](LICENSE)), and that inherited Sonata files keep their upstream copyright headers.
