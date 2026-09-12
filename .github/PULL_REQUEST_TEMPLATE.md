<!--
Read AGENTS.md before opening this. The definition of done is in §5 there; run it in full.
-->

## What this changes

<!-- One paragraph. Link the PROJECT_PLAN.md task id when there is one. -->

## Contract

- [ ] No template file, template-registry key, config node, service id or Twig block from
      [PLAN/02](../PLAN/02-compatibility-contract.md) was removed or renamed — or the removal is
      justified below and the CHANGELOG says so.
- [ ] No Bootstrap or AdminLTE class name, no jQuery, no inline script, no AJAX form submission.
- [ ] `idct/adminata-admin-mongodb-bundle` still resolves and its unit suite still passes.
- [ ] No new name says Sonata: `make check-names` is part of `make lint` and stays clean.

## Gates

- [ ] `make lint`
- [ ] `make phpstan`
- [ ] `make rector`
- [ ] `make test`
- [ ] `make test-contract`
- [ ] Front end, when touched: `make lint-js test-js assets-check`
- [ ] Anything that renders: checked in both light and dark mode

<!-- If a gate could not run here (no browser, no MongoDB), say which and why. -->

## Changelog

<!-- The `Unreleased` entry you added, or "none — internal only". -->
