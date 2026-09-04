# Migrating an application from Sonata Admin to adminata

**Not written yet.** This file becomes the executed migration checklist with real hours in
PROJECT_PLAN task **P5-12**, at the end of the recomaty-panel migration (PLAN/09 phase 5).

Until then, the planned checklist — 16 steps, about 45 hours for an application of recomaty-panel's
size — is [PLAN/10-migration-guide-outline.md](PLAN/10-migration-guide-outline.md) §1 and §2.

The one thing to know before starting: **run every Composer command of the migration with
`--no-plugins --no-scripts`**. Uninstalling the `sonata-project/*` packages otherwise makes Symfony
Flex run their recipes' `unconfigure`, which deletes `config/packages/sonata_admin.yaml`,
`config/packages/sonata_block.yaml`, `config/packages/sonata_form.yaml`,
`config/routes/sonata_admin.yaml` and `src/Admin/.gitignore` without a hash check.
