.. index::
    single: Twig
    single: Configuration

Twig helper configuration
=========================

The flash-message manager and the status helper are part of the admin bundle. The admin layout
renders the flash messages on every page, so there is nothing to install beyond the admin bundle
itself (:doc:`/admin-bundle/getting_started/installation`). Their classes are
``IDCT\Adminata\`` — ``FlashMessage\``, ``Status\``, ``Twig\`` and ``Twig\Extension\`` — and
``AdminataBundle`` registers the Twig extensions, the runtimes and the ``adminata_twig``
configuration root.

What the Twig helpers have of their own:

* ``adminata_twig`` is a configuration root of its own, in its own
  ``config/packages/adminata_twig.yaml``; the tree is below.
* Service ids are ``adminata.twig.*`` — ``adminata.twig.flashmessage.manager``,
  ``adminata.twig.status_runtime``, ``adminata.twig.status_extension``,
  ``adminata.twig.extension.wrapping`` and ``adminata.twig.template_extension`` — and a status renderer
  of yours is tagged ``adminata.status.renderer`` (:doc:`twig_status_helper`).
* The Twig functions are ``adminata_flashmessages_types``, ``adminata_flashmessages_get`` and
  ``adminata_flashmessages_class``, and the filter is ``adminata_status_class``.
* ``FlashMessage/render.html.twig`` is in the admin bundle's view directory, so
  ``@Adminata/FlashMessage/render.html.twig`` is the path adminata's own layout includes, and
  the template is overridden like any other admin template: a file of the same name under
  ``templates/bundles/AdminataBundle/``, with ``@!Adminata/FlashMessage/render.html.twig``
  reaching the shipped one.

What the Twig helpers share with the rest of the admin bundle is the translation domain. Their
strings — ``message_close``, ``more`` and ``less`` on a grouped flash message — are in
``AdminataBundle`` with the admin bundle's own. An application overriding one of them puts the
unit in ``translations/AdminataBundle.<locale>.xliff`` (:doc:`translation`).

.. note::

    Coming from Sonata? The classes of ``sonata-project/twig-extensions`` are ``IDCT\Adminata\``
    here (``Twig\``, ``FlashMessage\``, ``Status\``) — the map and the tool that applies it are
    in `UPGRADE.md <https://github.com/ideaconnect/adminata/blob/main/UPGRADE.md>`_ — there is
    no ``SonataTwigBundle`` to register in ``bundles.php``, and there is no ``SonataTwigBundle``
    translation domain: units an application kept in
    ``translations/SonataTwigBundle.<locale>.xliff`` move to
    ``translations/AdminataBundle.<locale>.xliff``, ids unchanged. The configuration root is
    ``adminata_twig`` in ``config/packages/adminata_twig.yaml``, the service ids
    ``adminata.twig.*``, the Twig functions ``adminata_flashmessages_*`` and the filter
    ``adminata_status_class``. A ``templates/bundles/SonataTwigBundle/`` directory is read by
    nothing: its files move to ``templates/bundles/AdminataBundle/``. adminata **conflicts**
    with ``sonata-project/twig-extensions``: the two cannot be installed together. See
    :doc:`/upgrading`.

The tree
--------

``bin/console config:dump-reference adminata_twig`` prints the whole tree with its defaults:

.. code-block:: yaml

    adminata_twig:
        # standard or horizontal; decides whether the form theme wraps a field with its addons
        form_type: standard
        # one entry per flash message group, keyed by the name the templates ask for
        flashmessage:
            message:
                css_class:  ~
                types:      []

``form_type`` is the value behind the ``wrap_fields_with_addons`` Twig global — ``true`` for
``standard``, ``false`` for ``horizontal``. adminata's own form theme does not read that global (it
reads ``adminata.options.form_type``, see :doc:`form_configuration`) and nothing prepends onto
this node, so set it here if a template of yours consults the global. ``flashmessage`` is
:doc:`twig_flash_messages`.
