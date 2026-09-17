Creating and Editing objects
============================

This document will cover the Create and Edit actions. It will cover configuration
of the fields and forms available in these views and any other relevant settings.

Basic configuration
-------------------

Adminata Options that may affect the create or edit view:

.. code-block:: yaml

    # config/packages/adminata.yaml

    adminata:
        options:
            html5_validate:  true     # enable or disable html5 form validation
            confirm_exit:    true     # enable or disable a confirmation before navigating away
            js_debug:        false    # enable or disable to show javascript debug messages
            use_stickyforms: true     # enable or disable the floating buttons
            form_type:       standard # can also be 'horizontal'

        templates:
            edit:              '@Adminata/CRUD/edit.html.twig'
            tab_menu_template: '@Adminata/Core/tab_menu_template.html.twig'

.. note::

    ``use_select2``, ``use_icheck`` and ``use_bootlint`` are **removed nodes**, and leaving one in
    your configuration is a container build error. adminata ships no select enhancement, no
    checkbox replacement and no Bootstrap linter: every ``<select>``, checkbox and radio is the
    browser's own. See :doc:`/upgrading`.

    The tab menu is one of the templates :doc:`not yet ported </porting-status>`.

Routes
------

You can disable creating or editing entities by removing the corresponding routes in your Admin.
For more detailed information about routes, see :doc:`routing`::

    // src/Admin/PersonAdmin.php

    final class PersonAdmin extends AbstractAdmin
    {
        protected function configureRoutes(RouteCollectionInterface $collection): void
        {
            /* Removing the edit route will disable editing entities. It will also
            use the 'show' view as default link on the identifier columns in the list view. */
            $collection->remove('edit');

            /* Removing the create route will disable creating new entities. It will also
            remove the 'Add new' button in the list view. */
            $collection->remove('create');
        }
    }

Adding form fields
------------------

Within the ``configureFormFields`` method you can define which fields should
be shown when editing or creating entities. Each field has to be added to a
specific form group. And form groups can optionally be added to a tab.
See `FormGroup options`_ for additional information about configuring form
groups::

    // src/Admin/PersonAdmin.php

    final class PersonAdmin extends AbstractAdmin
    {
        protected function configureFormFields(FormMapper $form): void
        {
            $form
                ->tab('General') // The tab call is optional
                    ->with('Addresses')
                        ->add('title') // Add a field and let Sonata decide which type to use
                        ->add('streetname', TextType::class) // Add a textfield
                        ->add('housenumber', NumberType::class) // Add a number field
                        ->add('housenumberAddition', TextType::class, ['required' => false]) // Add a non-required text field
                    ->end() // End form group
                ->end() // End tab
            ;
        }
    }

Using the FormMapper add method, you can add form fields. The add method
has 4 parameters:

- ``name``: The name of your entity.
- ``type``: The type of field to show; by defaults this is ``null`` to let
  Sonata decide which type to use. See :doc:`Field Types <field_types>`
  for more information on available types.
- ``options``: The form options to be used for the field. These may differ
  per type. See :doc:`Field Types <field_types>` for more information on
  available options.
- ``fieldDescriptionOptions``: The field description options. Options here
  are passed through to the field template. See :ref:`Form Types, FieldDescription
  options <form_types_fielddescription_options>` for more information.

.. note::

    The property entered in ``name`` should be available in your Entity
    through getters/setters or public access.

FormGroup options
-----------------

When adding a form group to your edit/create form, you may specify some
options for the group itself.

- ``collapsed``: unused at the moment
- ``class``: The class for your form group in the admin; by default, the
  value is set to ``col-span-12``.
- ``fields``: The fields in your form group (you should NOT override this
  unless you know what you're doing).
- ``class``: the class of the group's wrapper — its grid span, and any hook of your own. A
  group that one field's value should show or hide carries a hook here and the field carries
  ``adminata-reveal``: see :doc:`/javascript`.
- ``box_class``: The class for your form group box in the admin; by default,
  the value is set to ``box box-primary``.
- ``column``: a name shared by the groups that should stack in ONE grid cell, one under the
  other, in declaration order. See *Stacking groups in a column* below.
- ``column_class``: the classes of that cell — its span; read from the first group of the
  stack that sets it, ``col-span-12`` when none does.
- ``description``: A text shown at the top of the form group.
- ``translation_domain``: The translation domain for the form group title
  (the Admin translation domain is used by default).

To specify options, do as follows::

    // src/Admin/PersonAdmin.php

    final class PersonAdmin extends AbstractAdmin
    {
        protected function configureFormFields(FormMapper $form): void
        {
            $form
                ->tab('General') // the tab call is optional
                    ->with('Addresses', [
                        'class'       => 'col-span-12 md:col-span-8',
                        'box_class'   => 'box box-solid box-danger',
                        'description' => 'Lorem ipsum',
                        // ...
                    ])
                        ->add('title')
                        // ...
                    ->end()
                ->end()
            ;
        }
    }

Here is an example of what you can do with customizing the box_class on
a group:

.. figure:: ../images/box_class.png
   :align: center
   :alt: Box Class
   :width: 500

.. _form_group_column:

Stacking groups in a column
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Every group is a card in a twelve-column grid, and a grid row is as tall as its tallest card.
Two short groups placed beside one long one therefore each take a row of their own, with the
long card's height of empty page under them. Groups that share a ``column`` are stacked in
a single grid cell instead, so the short cards follow each other and fill that height::

    $form
        ->with('Details', ['class' => 'col-span-12 xl:col-span-8'])
            // ...
        ->end()
        ->with('Publication', ['column' => 'side', 'column_class' => 'col-span-12 xl:col-span-4'])
            // ...
        ->end()
        ->with('Audit', ['column' => 'side'])
            // ...
        ->end();

``Publication`` and ``Audit`` render one under the other in a cell four columns wide, beside
``Details``. Cells — a stack, or a group on its own — are laid out in the order they first
appear, so the reading order of the form stays the declaration order, and a group's own
``class`` still lands on its own wrapper inside the stack: the ``adminata-reveal`` hook above
keeps hiding that one group rather than the whole column. The same two options work on the
show page's groups.

Displaying custom data/template
-------------------------------

If you need a specific layout between some fields, you can define a custom template
with the adminata TemplateType::

    namespace App\Admin;

    use IDCT\Adminata\Admin\AbstractAdmin;
    use IDCT\Adminata\Form\FormMapper;
    use IDCT\Adminata\Form\Type\TemplateType;

   final class PersonAdmin extends AbstractAdmin
    {
        protected function configureFormFields(FormMapper $form): void
        {
            $form
                 ->add('title')
                 ->add('googleMap', TemplateType::class, [
                     'template'   => 'path/to/your/template.html.twig'
                     'parameters' => [
                         'url' => $this->generateGoogleMapUrl($this->getSubject()),
                     ],
                 ])
                 ->add('streetname', TextType::class)
                 ->add('housenumber', NumberType::class);
        }
    }

The related template:

.. code-block:: html+twig

    <a href="{{ url }}">{{ object.title }}</a>

Embedding other Admins
----------------------

.. note::

    **TODO**:
    * how to embed one Admin in another (1:1, 1:M, M:M)
    * how to access the right object(s) from the embedded Admin's code
