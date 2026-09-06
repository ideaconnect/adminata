Upgrading from Sonata Admin
===========================

adminata ``replace``\ s three ``sonata-project/*`` packages at exact versions, so an application's
admin classes, service definitions and routes carry over untouched. What changes is
the markup, the JavaScript, the form widgets, a handful of configuration nodes, and four lines of
``bundles.php``: the other four packages — ``block-bundle``, ``exporter``, ``form-extensions`` and
``twig-extensions`` — are merged into the admin bundle rather than replaced.

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

#. **The four merged trees.** Delete the ``SonataFormBundle``, ``SonataTwigBundle`` and
   ``SonataExporterBundle`` lines from ``bundles.php`` as well as the ``SonataBlockBundle`` one.
   The form types are
   ``Sonata\AdminBundle\Form\Type\`` and the Twig helpers ``Sonata\AdminBundle\FlashMessage\``
   and ``Sonata\AdminBundle\Status\``; ``sonata_form`` and ``sonata_twig`` are still their own
   configuration roots and the ``sonata.form.*`` and ``sonata.twig.*`` service ids are unchanged,
   as are ``@SonataForm/…`` and ``@SonataTwig/…`` as compatibility aliases of the admin bundle's
   views. **Watch the collection types**: ``Sonata\Form\Type\CollectionType`` is now
   ``Sonata\AdminBundle\Form\Type\CollectionType``, and what used to hold that name is
   ``Sonata\AdminBundle\Form\Type\NativeCollectionType`` — the same import can now render the
   other widget. Strings an application overrode in
   ``translations/SonataFormBundle.<locale>.xliff`` or
   ``translations/SonataTwigBundle.<locale>.xliff`` move into
   ``translations/SonataAdminBundle.<locale>.xliff``, ids unchanged. adminata **conflicts** with
   ``sonata-project/form-extensions`` and ``sonata-project/twig-extensions``, so ``composer remove``
   them if you require them explicitly. See :doc:`admin-bundle/reference/form_configuration`,
   :doc:`admin-bundle/reference/form_types` and
   :doc:`admin-bundle/reference/twig_configuration`.
#. **Blocks.** Delete ``Sonata\BlockBundle\SonataBlockBundle::class`` from ``bundles.php`` —
   ``SonataAdminBundle`` registers the block extension itself, so ``sonata_block`` is still its own
   configuration root and ``config/packages/sonata_block.yaml`` does not change. If your application
   names block classes, they moved from ``Sonata\BlockBundle\`` to ``Sonata\AdminBundle\``; the
   map is in ``UPGRADE-1.0.md`` §U1. Block strings are in the ``SonataAdminBundle`` translation
   domain: a ``translations/SonataBlockBundle.<locale>.xliff`` of yours moves into
   ``translations/SonataAdminBundle.<locale>.xliff``, ids unchanged, and a template that names the
   ``SonataBlockBundle`` domain names ``SonataAdminBundle`` instead. Service ids and Twig function
   names are unchanged, and a template of yours that says ``@SonataBlock/…`` still resolves — the
   namespace is a compatibility alias of the admin bundle's views, where the block templates live
   as ``@SonataAdmin/Block/…``, the path adminata's own defaults use. A
   ``templates/bundles/SonataBlockBundle/`` directory is read by nothing: move its files to
   ``templates/bundles/SonataAdminBundle/Block/``, which overrides the shipped templates with no
   configuration. adminata **conflicts** with ``sonata-project/block-bundle``, so
   ``composer remove`` it if you require it explicitly. See
   :doc:`admin-bundle/reference/block_configuration`.
#. **The exporter.** Delete ``Sonata\Exporter\Bridge\Symfony\SonataExporterBundle::class`` from
   ``bundles.php`` — ``SonataAdminBundle`` registers the exporter extension itself, so
   ``sonata_exporter`` is still its own configuration root and
   ``config/packages/sonata_exporter.yaml`` does not change, and neither do the
   ``sonata.exporter.*`` service ids or the ``sonata.exporter.writer`` tag a writer of yours
   carries. If your application type-hints ``Sonata\Exporter\ExporterInterface``, a writer or a
   source iterator, those imports are ``Sonata\AdminBundle\Exporter\`` now; the map is in
   ``UPGRADE-1.0.md`` §U1. The exporter ships no templates and no translations, so there is nothing
   else to move. adminata **conflicts** with ``sonata-project/exporter``, so ``composer remove`` it
   if you require it explicitly. See :doc:`admin-bundle/reference/exporter_configuration`.
#. **Configuration.** ``options.skin``, ``use_select2``, ``use_icheck`` and ``use_bootlint`` are
   removed nodes — leaving one is a container build error. Group and dashboard-block ``class``
   defaults are Tailwind grid classes now.
#. **Markup.** Bootstrap and AdminLTE class names are gone; the replacements are the ``.adm-*``
   components. Sonata's own hooks — ``sonata-ba-list``, ``edit_link``, ``sonata-filter-form`` and
   the rest — are unchanged and frozen, so select on those in CSS and tests.
#. **JavaScript.** No jQuery, no ``window.Admin``, no AJAX form submission. See :doc:`javascript`.
#. **Forms.** Native selects and native HTML5 date and time inputs; a custom ``format`` on a picker
   field is now rejected. The collection-type swap above is the one to check field by field.
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
