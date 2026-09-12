adminata
========

adminata is an admin bundle for Symfony with a Tailwind CSS v4 interface — a hard fork of seven
Sonata packages under its own name, ``IDCT\Adminata\`` (:doc:`origins`). This site documents the
admin bundle, which is six of them: ``sonata-project/admin-bundle`` is its tree, and
``block-bundle``, ``doctrine-extensions``, ``exporter``, ``form-extensions`` and ``twig-extensions``
are part of it — blocks, Doctrine managers, form types, the Twig helpers and the exporter are
``IDCT\Adminata\`` classes, and ``AdminataBundle`` brings their ``adminata_block``,
``adminata_form``, ``adminata_twig`` and ``adminata_exporter`` configuration roots with it (see
:doc:`admin-bundle/reference/block_configuration`,
:doc:`admin-bundle/reference/form_configuration`,
:doc:`admin-bundle/reference/twig_configuration` and
:doc:`admin-bundle/reference/exporter_configuration`). adminata **conflicts** with every package
it forked: it provides their behaviour under its own names, so an installation cannot hold both.

The seventh, the Doctrine ORM storage layer, is a package of its own:
`idct/adminata-doctrine-orm-admin-bundle <https://github.com/ideaconnect/adminata-doctrine-orm-admin-bundle>`_
(``IDCT\Adminata\DoctrineORM\``), which carries its own documentation. So is the MongoDB ODM one,
`idct/adminata-admin-mongodb-bundle <https://github.com/ideaconnect/adminata-admin-mongodb-bundle>`_
(``IDCT\Adminata\DoctrineMongoDB\``).

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
    origins
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
