.. index::
    single: Twig
    single: Configuration

Twig helper configuration
=========================

The flash-message manager and the status helper are part of the admin bundle. The admin layout
renders the flash messages on every page, so there is nothing to install beyond the admin bundle
itself (:doc:`/admin-bundle/getting_started/installation`). Their classes are
``Sonata\AdminBundle\`` — ``FlashMessage\``, ``Status\``, ``Twig\`` and ``Twig\Extension\`` — and
``SonataAdminBundle`` registers the Twig extensions, the runtimes and the ``sonata_twig``
configuration root.

What the Twig helpers have of their own:

* ``sonata_twig`` is a configuration root of its own, in its own
  ``config/packages/sonata_twig.yaml``; the tree is below.
* Service ids are ``sonata.twig.*`` — ``sonata.twig.flashmessage.manager``,
  ``sonata.twig.status_runtime``, ``sonata.twig.status_extension``,
  ``sonata.twig.extension.wrapping`` and ``sonata.twig.template_extension`` — and a status renderer
  of yours is tagged ``sonata.status.renderer`` (:doc:`twig_status_helper`).
* The Twig functions are ``sonata_flashmessages_types``, ``sonata_flashmessages_get`` and
  ``sonata_flashmessages_class``, and the filter is ``sonata_status_class``.
* ``FlashMessage/render.html.twig`` is in the admin bundle's view directory, so
  ``@SonataAdmin/FlashMessage/render.html.twig`` is the path adminata's own layout includes, and
  the template is overridden like any other admin template: a file of the same name under
  ``templates/bundles/SonataAdminBundle/``, with ``@!SonataAdmin/FlashMessage/render.html.twig``
  reaching the shipped one. ``@SonataTwig`` is kept as a compatibility alias of the same directory,
  for templates outside adminata that still say ``@SonataTwig/…``.

What the Twig helpers share with the rest of the admin bundle is the translation domain. Their
strings — ``message_close``, ``more`` and ``less`` on a grouped flash message — are in
``SonataAdminBundle`` with the admin bundle's own. An application overriding one of them puts the
unit in ``translations/SonataAdminBundle.<locale>.xliff`` (:doc:`translation`).

.. note::

    Coming from Sonata? The ``Sonata\Twig\`` classes are ``Sonata\AdminBundle\`` here — the map is
    in `UPGRADE-1.0.md <https://github.com/ideaconnect/adminata/blob/main/UPGRADE-1.0.md>`_ §U1 —
    there is no ``SonataTwigBundle`` to register in ``bundles.php``, and there is no
    ``SonataTwigBundle`` translation domain: units an application kept in
    ``translations/SonataTwigBundle.<locale>.xliff`` move to
    ``translations/SonataAdminBundle.<locale>.xliff``, ids unchanged. Service ids, Twig function
    names and ``config/packages/sonata_twig.yaml`` need no edit, and a template of yours that says
    ``@SonataTwig/…`` still resolves. A ``templates/bundles/SonataTwigBundle/`` directory is read
    by nothing any more: its files move to ``templates/bundles/SonataAdminBundle/``. adminata
    **conflicts** with ``sonata-project/twig-extensions``: the two cannot be installed together.
    See :doc:`/upgrading`.

The tree
--------

``bin/console config:dump-reference sonata_twig`` prints the whole tree with its defaults:

.. code-block:: yaml

    sonata_twig:
        # standard or horizontal; decides whether the form theme wraps a field with its addons
        form_type: standard
        # one entry per flash message group, keyed by the name the templates ask for
        flashmessage:
            message:
                css_class:  ~
                types:      []

``form_type`` is the value behind the ``wrap_fields_with_addons`` Twig global — ``true`` for
``standard``, ``false`` for ``horizontal``. adminata's own form theme does not read that global (it
reads ``sonata_admin.options.form_type``, see :doc:`form_configuration`) and nothing prepends onto
this node, so set it here if a template of yours consults the global. ``flashmessage`` is
:doc:`twig_flash_messages`.
