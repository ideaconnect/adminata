adminata
========

adminata is a hard fork of seven Sonata packages with a Tailwind CSS v4 interface. This site
documents the admin bundle, which is six of them: it ``replace``\ s ``sonata-project/admin-bundle``
at an exact version, so an application's admin classes, services and routes carry over untouched —
what changes is the markup, the JavaScript and the form widgets — and ``block-bundle``,
``doctrine-extensions``, ``exporter``, ``form-extensions`` and ``twig-extensions`` are part of it:
blocks, Doctrine managers, form types, the Twig helpers and the exporter are
``Sonata\AdminBundle\`` classes and there is no ``SonataBlockBundle``, ``SonataDoctrineBundle``,
``SonataFormBundle``, ``SonataTwigBundle`` or ``SonataExporterBundle`` (see
:doc:`admin-bundle/reference/block_configuration`,
:doc:`admin-bundle/reference/form_configuration`,
:doc:`admin-bundle/reference/twig_configuration` and
:doc:`admin-bundle/reference/exporter_configuration`).

The seventh, the Doctrine ORM storage layer, is a package of its own:
`idct/adminata-doctrine-orm-admin-bundle <https://github.com/ideaconnect/adminata-doctrine-orm-admin-bundle>`_,
which carries its own documentation. So is the MongoDB ODM one,
`idct/sonata-admin-mongodb-bundle <https://github.com/ideaconnect/sonata-admin-mongodb-bundle>`_.

There is **no compatibility layer**. Bootstrap and AdminLTE class names are gone, so is jQuery, and
nothing is aliased or shimmed to soften that. If you are coming from Sonata Admin 4.43, read
:doc:`upgrading` first.

.. warning::

    Some pages below are inherited from upstream and describe a screen adminata has not rewritten
    yet. Those carry a *not yet ported* banner at the top. See :doc:`porting-status` for the whole
    list and how to ask for one.

.. toctree::
    :caption: adminata
    :name: adminata
    :maxdepth: 1

    upgrading
    theming
    icons
    javascript
    tailwind
    porting-status

.. toctree::
    :caption: Admin Bundle
    :name: admin-bundle
    :maxdepth: 2

    admin-bundle/index
