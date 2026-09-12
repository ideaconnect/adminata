.. index::
    single: Block
    single: Configuration

Block configuration
===================

Blocks are part of the admin bundle. The dashboard is built from them, and
``adminata_block_render_event`` is how an application puts its own markup on an admin page
(:doc:`block_events`). Their classes are ``IDCT\Adminata\`` — ``Block\``, ``Block\Service\``,
``Model\``, ``Event\``, ``Exception\`` and their siblings — and ``AdminataBundle`` registers the
block services, the Twig functions and the ``adminata_block`` configuration root, so there is nothing
to install beyond the admin bundle itself (:doc:`/admin-bundle/getting_started/installation`).

What blocks have of their own:

* ``adminata_block`` is a configuration root of its own, in its own
  ``config/packages/adminata_block.yaml``; the tree is below.
* Service ids are ``adminata.block.*``, and a block service of yours is tagged ``adminata.block``
  (:doc:`block_your_first_block`).
* The Twig functions are ``adminata_block_render``, ``adminata_block_render_event``,
  ``adminata_block_exists``, ``adminata_block_include_javascripts`` and
  ``adminata_block_include_stylesheets`` (:doc:`block_twig_helpers`).
* The block templates are in the admin bundle's view directory, under ``@Adminata/Block/``, and
  that is the path every default names: the ``template`` setting of each shipped block service,
  ``adminata_block.templates.block_base`` and ``block_container``, ``adminata_block.profiler.template``
  (``@Adminata/Profiler/block.html.twig``) and the two exception renderers. So a block template
  is overridden exactly like any other admin template — see `Overriding a block template`_ below.
  ``@Adminata`` is kept as a compatibility alias of the same directory, for templates outside
  adminata that still say ``@Adminata/…``: ``@Adminata/Block/block_core_rss.html.twig`` and
  ``@Adminata/Block/block_core_rss.html.twig`` are one file.

What blocks share with the rest of the admin bundle is the translation domain. The block strings —
the ``adminata.block.service.*`` names and the ``form.label_*`` labels of the editable blocks — are in
``AdminataBundle`` with the admin bundle's own, and the block services translate their forms in
that domain. An application overriding one of them puts the unit in
``translations/AdminataBundle.<locale>.xliff`` (:doc:`translation`).

.. note::

    Coming from Sonata? The ``IDCT\Adminata\`` classes are ``IDCT\Adminata\`` here — the
    map is in `UPGRADE-1.0.md <https://github.com/ideaconnect/adminata/blob/main/UPGRADE-1.0.md>`_
    §U1 — there is no ``SonataBlockBundle`` to register in ``bundles.php``, and there is no
    ``SonataBlockBundle`` translation domain: units an application kept in
    ``translations/SonataBlockBundle.<locale>.xliff`` move to
    ``translations/AdminataBundle.<locale>.xliff``, ids unchanged. Service ids, Twig function
    names and ``config/packages/adminata_block.yaml`` need no edit, and a template of yours that says
    ``@Adminata/…`` still resolves. A ``templates/bundles/SonataBlockBundle/`` directory is read
    by nothing any more: its files move to ``templates/bundles/AdminataBundle/Block/``.
    adminata **conflicts** with ``sonata-project/block-bundle``: the two cannot be installed
    together. See :doc:`/upgrading`.

Enabling a block
----------------

A block is enabled for the contexts it may render in. The admin bundle's own dashboard block is
enabled in the ``admin`` context — the one piece of block configuration every admin application
has, and the one :doc:`/admin-bundle/getting_started/installation` asks for:

.. code-block:: yaml

    # config/packages/adminata_block.yaml

    adminata_block:
        blocks:
            adminata.admin.block.admin_list:
                contexts: [admin]

A block of your own goes under its service id in the same list, with its contexts and, when it can
be rendered more than one way, the templates to choose from:

.. code-block:: yaml

    # config/packages/adminata_block.yaml

    adminata_block:
        blocks:
            adminata.admin.block.admin_list:
                contexts: [admin]
            app.block.demo:
                contexts: [admin]
                templates:
                    - { name: 'Simple', template: '@App/Block/demo_simple.html.twig' }
                    - { name: 'Big',    template: '@App/Block/demo_big.html.twig' }

Overriding a block template
---------------------------

Every block template ships under ``@Adminata``, so overriding one is what overriding any admin
template is: a file of the same name under ``templates/bundles/AdminataBundle/``, and nothing to
configure. ``@!Adminata`` is the shipped file — extend it when you want to wrap the original
rather than replace it:

.. code-block:: html+twig

    {# templates/bundles/AdminataBundle/Block/block_core_text.html.twig #}

    {% extends '@!Adminata/Block/block_core_text.html.twig' %}

    {% block block %}
        <div class="app-text-block">{{ parent() }}</div>
    {% endblock %}

That reaches every block rendered with the shipped default — ``adminata.block.service.text`` here.
The same holds for ``Block/block_base.html.twig``, which every block extends, for
``Profiler/block.html.twig`` and for the two exception templates. A block whose ``template``
setting you set explicitly renders that template instead, override or not, and
``adminata_block.templates.*`` still takes any Twig path when a differently named template is what
you want.

.. note::

    ``@Adminata`` is a plain ``twig.paths`` alias, not a bundle, so it has no
    ``templates/bundles/`` directory of its own: a path addressed through it is never overridden
    there. That is why every default inside adminata says ``@Adminata/…``. Address the shipped
    templates the same way in code of your own; ``@Adminata/…`` is for templates outside adminata
    that already use it.

The tree
--------

``bin/console config:dump-reference adminata_block`` prints the whole tree with its defaults. The
nodes an admin application meets:

.. code-block:: yaml

    adminata_block:
        # one entry per block service, keyed by service id
        blocks:
            id:
                contexts: []       # where the block may render
                templates: []      # { name, template } pairs to choose from
                settings: {}       # merged into the block's settings
                exception:         # a per-block exception strategy
                    filter: null
                    renderer: null
        # the same, keyed by block class
        blocks_by_class:
            class:
                settings: {}
        # contexts for a block that declares none
        default_contexts: []
        templates:
            # the template every block extends; null is @Adminata/Block/block_base.html.twig
            block_base: null
            # null is @Adminata/Block/block_container.html.twig
            block_container: null
        context_manager: adminata.block.context_manager.default
        profiler:
            enabled: '%kernel.debug%'
            template: '@Adminata/Profiler/block.html.twig'
        exception:
            default: { filter: debug_only, renderer: throw }
            filters: {}
            renderers: {}

``profiler`` is :doc:`block_profiler`, ``exception`` is :doc:`block_exceptions`, and
``templates.block_base`` is what ``{% extends adminata_block.templates.block_base %}`` resolves to
in a block template of your own (:doc:`block_your_first_block`).
