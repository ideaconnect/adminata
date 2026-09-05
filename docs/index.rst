adminata
========

adminata is a hard fork of seven Sonata packages with a Tailwind CSS v4 interface. It ``replace``\ s
those packages at exact versions, so an application's admin classes, services and routes carry over
untouched; what changes is the markup, the JavaScript and the form widgets.

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

.. toctree::
    :caption: The other packages
    :name: packages
    :maxdepth: 2

    doctrine-orm-admin-bundle/index
    block-bundle/index
    form-extensions/index
    twig-extensions/index
    exporter/index
