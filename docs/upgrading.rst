Upgrading from Sonata Admin
===========================

Two things change for an application that comes from ``sonata-project/admin-bundle`` 4.43, and
they have a guide each in the repository:

- **The names.** Since 2026-09-12 nothing in adminata carries the Sonata name: the namespace is
  ``IDCT\Adminata\``, the bundle ``AdminataBundle``, the Twig namespace ``@Adminata``, the
  configuration root ``adminata``, and every service id, route, form type, translation domain,
  markup hook, Stimulus controller and cookie follows. adminata **conflicts** with every
  ``sonata-project`` package it forked rather than replacing it. `UPGRADE.md
  <https://github.com/ideaconnect/adminata/blob/main/UPGRADE.md>`_ is the complete map — Composer,
  ``bundles.php``, configuration, routes, PHP, Twig, translations, markup, JavaScript — and it
  ships the tool that makes those edits for you: ``vendor/bin/adminata-rename --app --dry-run .``
  shows what it would change and which ``sonata…`` names are your own (those it leaves alone),
  ``vendor/bin/adminata-rename --app .`` makes the change. Read its §6.3 before you touch the id
  of an admin service of yours: the ``ROLE_*`` names the security handlers derive from it are
  stored in your users' roles, and renaming the id is a data migration, not a rename.
- **The interface.** Bootstrap and AdminLTE class names are gone, so are jQuery, ``window.Admin``,
  select2, iCheck, Tempus Dominus and AJAX form submission; the replacements are the ``.adm-*``
  components, native ``<dialog>``, native selects and native HTML5 date inputs, and Stimulus
  controllers. `UPGRADE-1.0.md
  <https://github.com/ideaconnect/adminata/blob/main/UPGRADE-1.0.md>`_ walks through that side —
  templates, form themes, JavaScript, CSS, the removed configuration nodes — and `MIGRATION.md
  <https://github.com/ideaconnect/adminata/blob/main/MIGRATION.md>`_ is the worked example with real
  figures per step, the migration of a 46-admin production panel.

There is **no compatibility layer** in either: nothing is aliased, shimmed or kept "for now".

The short version
-----------------

#. **Composer.** ``composer require idct/adminata:dev-main
   idct/adminata-doctrine-orm-admin-bundle --no-plugins --no-scripts`` — both from Packagist —
   then ``composer remove --no-plugins --no-scripts`` every
   ``sonata-project/*`` package you require, then ``composer install``. The flags are not
   optional: uninstalling the ``sonata-project/*`` packages otherwise makes Symfony Flex run their
   recipes' ``unconfigure``, which deletes your ``config/packages/sonata_*.yaml`` and
   ``config/routes/sonata_admin.yaml`` without a hash check.
#. **The rename tool.** ``vendor/bin/adminata-rename --app --dry-run .``, read in full; then
   ``--app .``; then the hand edits it lists — the ``bundles.php`` lines of the bundles that no
   longer exist, the two ``CollectionType`` s that exchanged names in 1.0, your own names.
#. **Configuration.** ``options.skin``, ``use_select2``, ``use_icheck`` and ``use_bootlint`` are
   removed nodes — leaving one is a container build error. Group and dashboard-block ``class``
   defaults are Tailwind grid classes now.
#. **Markup.** Bootstrap and AdminLTE class names are gone; the replacements are the ``.adm-*``
   components. adminata's own hooks — ``adminata-list``, ``edit_link``, ``adminata-filter-form``
   and the rest — are frozen, so select on those in CSS and tests.
#. **JavaScript.** No jQuery, no ``window.Admin``, no AJAX form submission. See :doc:`javascript`.
#. **Forms.** Native selects and native HTML5 date and time inputs; a custom ``format`` on a picker
   field is now rejected. The collection-type swap is the one to check field by field.
#. **Icons.** Font Awesome 7 Free with no shim. See :doc:`icons`.
#. **Dark mode.** ``html.dark`` is stamped by the server; hard-coded colours in your own templates
   need ``dark:`` variants. See :doc:`theming`.
#. **Tailwind.** If your templates use utilities of their own, compile the stylesheet yourself. See
   :doc:`tailwind`.

Two settings worth deciding rather than inheriting
--------------------------------------------------

Both default to Sonata's behaviour, so an upgrade is a no-op until you choose otherwise — but both
defaults were chosen for a different interface.

``options.list_action_button_content`` (default ``all``)
    Renders the row actions as text buttons. If your application ships icon-only actions of its
    own, ``icon`` is what matches them.

``options.default_admin_route`` (default ``show``)
    Where the identifier column links, **and** where a click on a list row goes. If most of your
    admin classes do not implement ``configureShowFields()``, that is a blank page — use ``edit``.
    adminata falls back to the other of ``show``/``edit`` for an admin that registers only one.

``security.role_admin`` (default ``ROLE_ADMINATA_ADMIN``)
    Upstream's default was ``ROLE_SONATA_ADMIN``. If that role is in your users' database, either
    set this node to it or migrate the roles together with the deploy (UPGRADE.md §6.3).

Finally, read :doc:`porting-status`. Thirty-seven templates are inherited unported and render
unstyled; if one of them is a screen you use daily, you want to know before you start, not after.
