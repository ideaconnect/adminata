Upgrading from Sonata Admin
===========================

adminata ``replace``\ s seven ``sonata-project/*`` packages at exact versions, so an application's
admin classes, service definitions, routes and ``bundles.php`` carry over untouched. What changes is
the markup, the JavaScript, the form widgets and a handful of configuration nodes.

The full notes are `UPGRADE-1.0.md
<https://github.com/ideaconnect/adminata/blob/main/UPGRADE-1.0.md>`_ in the repository, in nine
sections. A worked example with real figures per step — the migration of a 46-admin production
panel — is `MIGRATION.md
<https://github.com/ideaconnect/adminata/blob/main/MIGRATION.md>`_.

The short version
-----------------

Install with ``--no-plugins --no-scripts``:

.. code-block:: console

    $ composer require idct/adminata --no-plugins --no-scripts
    $ composer remove --no-plugins --no-scripts sonata-project/admin-bundle sonata-project/doctrine-orm-admin-bundle
    $ composer install
    $ bin/console cache:clear
    $ bin/console assets:install public

Those flags are not optional. Uninstalling the ``sonata-project/*`` packages otherwise makes Symfony
Flex run their recipes' ``unconfigure``, which deletes ``config/packages/sonata_admin.yaml``,
``config/packages/sonata_block.yaml``, ``config/packages/sonata_form.yaml``,
``config/routes/sonata_admin.yaml`` and ``src/Admin/.gitignore`` without a hash check. Remove the
seven ``sonata-project/*`` entries from ``symfony.lock`` by hand afterwards.

Then work through, in roughly this order:

#. **Configuration.** ``options.skin``, ``use_select2``, ``use_icheck`` and ``use_bootlint`` are
   removed nodes — leaving one is a container build error. Group and dashboard-block ``class``
   defaults are Tailwind grid classes now.
#. **Markup.** Bootstrap and AdminLTE class names are gone; the replacements are the ``.adm-*``
   components. Sonata's own hooks — ``sonata-ba-list``, ``edit_link``, ``sonata-filter-form`` and
   the rest — are unchanged and frozen, so select on those in CSS and tests.
#. **JavaScript.** No jQuery, no ``window.Admin``, no AJAX form submission. See :doc:`javascript`.
#. **Forms.** Native selects and native HTML5 date and time inputs; a custom ``format`` on a picker
   field is now rejected.
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

Finally, read :doc:`porting-status`. Thirty-seven templates are inherited unported and render
unstyled; if one of them is a screen you use daily, you want to know before you start, not after.
