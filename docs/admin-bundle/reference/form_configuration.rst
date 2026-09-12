.. index::
    single: Form
    single: Configuration

Form configuration
==================

The form types are part of the admin bundle. ``FormMapper`` builds every admin form out of them,
and a form theme renders them, so there is nothing to install beyond the admin bundle itself
(:doc:`/admin-bundle/getting_started/installation`). Their classes are ``IDCT\Adminata\`` —
``Form\Type\``, ``Form\DataTransformer\``, ``Form\EventListener\``, ``Validator\`` and
``Test\`` — and ``AdminataBundle`` registers the form services, the datepicker form theme and
the ``adminata_form`` configuration root.

What the form layer has of its own:

* ``adminata_form`` is a configuration root of its own, in its own
  ``config/packages/adminata_form.yaml``; the tree is below.
* Service ids are ``adminata.form.*`` — ``adminata.form.type.array``, ``type.boolean``,
  ``type.collection``, ``type.date_range``, ``type.datetime_range``, ``type.date_picker``,
  ``type.datetime_picker``, ``type.date_range_picker``, ``type.datetime_range_picker`` and
  ``adminata.form.validator.inline``.
* The form templates are in the admin bundle's view directory, under ``@Adminata/Form/``, and
  that is the path every default names — ``datepicker.html.twig`` is added to ``twig.form_themes``
  as ``@Adminata/Form/datepicker.html.twig``. So a form template is overridden exactly like any
  other admin template: a file of the same name under ``templates/bundles/AdminataBundle/``,
  with ``@!Adminata/Form/…`` reaching the shipped one. ``@Adminata`` is kept as a
  compatibility alias of the same directory, for templates outside adminata that still say
  ``@Adminata/…``: ``@Adminata/Form/datepicker.html.twig`` and
  ``@Adminata/Form/datepicker.html.twig`` are one file.

What the form layer shares with the rest of the admin bundle is the translation domain. The button
labels and widget strings — ``link_add``, ``label_type_yes``, ``label_type_no`` and their siblings
— are in ``AdminataBundle`` with the admin bundle's own, and that is the default of every
``btn_translation_domain``. An application overriding one of them puts the unit in
``translations/AdminataBundle.<locale>.xliff`` (:doc:`translation`).

.. note::

    Coming from Sonata? The ``IDCT\Adminata\Form\`` classes are ``IDCT\Adminata\`` here — the map is
    in `UPGRADE-1.0.md <https://github.com/ideaconnect/adminata/blob/main/UPGRADE-1.0.md>`_ §U1 —
    there is no ``SonataFormBundle`` to register in ``bundles.php``, and there is no
    ``SonataFormBundle`` translation domain: units an application kept in
    ``translations/SonataFormBundle.<locale>.xliff`` move to
    ``translations/AdminataBundle.<locale>.xliff``, ids unchanged. Service ids and
    ``config/packages/adminata_form.yaml`` need no edit, and a template of yours that says
    ``@Adminata/…`` still resolves. A ``templates/bundles/SonataFormBundle/`` directory is read
    by nothing any more: its files move to ``templates/bundles/AdminataBundle/``.

    **Read the** :doc:`form_types` **note on the two collection types before you update any
    import.** ``CollectionType`` and ``NativeCollectionType`` exchanged names in the merge, and the
    two render differently. adminata **conflicts** with ``sonata-project/form-extensions``: the two
    cannot be installed together. See :doc:`/upgrading`.

Standard or horizontal
----------------------

Some widgets are wrapped differently depending on whether your forms use the standard style or the
horizontal one. adminata renders both with its own form theme — there is no Bootstrap involved —
and that theme reads ``adminata.options.form_type``, which is where an admin application sets
the style:

.. code-block:: yaml

    # config/packages/adminata.yaml

    adminata:
        options:
            form_type: horizontal

``adminata_form`` keeps a ``form_type`` node of its own for forms built outside an admin class.
``AdminataFormExtension`` prepends ``adminata.options.form_type`` onto it, so the two agree
without being written twice; set it directly only if you have no ``adminata`` configuration:

.. code-block:: yaml

    # config/packages/adminata_form.yaml

    adminata_form:
        form_type: horizontal

The one node that is **not** prepended is :doc:`twig_configuration`'s ``adminata_twig.form_type``,
which is what the ``wrap_fields_with_addons`` Twig global reports. adminata's own templates do not
read that global; a template of yours that does has to set the node.

The tree
--------

``bin/console config:dump-reference adminata_form`` prints the whole tree with its defaults. It is
one node:

.. code-block:: yaml

    adminata_form:
        # standard or horizontal
        form_type: standard
