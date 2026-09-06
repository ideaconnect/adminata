Admin Bundle
============

The core of adminata: the ``Admin`` classes, the CRUD controller, the datagrid, the form types and
their validator, the flash-message and status Twig helpers, the blocks the dashboard is built from,
the exporter the export menu streams a result set through, and the templates that render all of
it.

Storage is a separate package. The Doctrine ORM integration ships **with** adminata and is
documented under :doc:`/doctrine-orm-admin-bundle/index`; MongoDB is
`idct/sonata-admin-mongodb-bundle <https://github.com/ideaconnect/sonata-admin-mongodb-bundle>`_,
which is maintained against adminata but installed separately.

.. note::

    These pages are inherited from Sonata Admin's documentation and edited where adminata differs.
    Where a page describes a screen adminata has not rewritten, it says so at the top; the whole
    list is :doc:`/porting-status`.

.. toctree::
    :caption: Getting Started
    :name: admin-bundle-getting-started
    :maxdepth: 1
    :numbered:

    getting_started/installation
    getting_started/creating_an_admin
    getting_started/the_form_view
    getting_started/the_list_view
    getting_started/the_show_view

.. toctree::
   :caption: Reference Guide
   :name: admin-bundle-reference-guide
   :maxdepth: 1
   :numbered:

   reference/configuration
   reference/architecture
   reference/child_admin
   reference/dashboard
   reference/search
   reference/action_list
   reference/action_create_edit
   reference/action_show
   reference/action_delete
   reference/action_export
   reference/saving_hooks
   reference/field_types
   reference/batch_actions
   reference/console
   reference/troubleshooting
   reference/breadcrumbs

.. toctree::
   :caption: Advanced Options
   :name: admin-bundle-advanced-options
   :maxdepth: 1
   :numbered:

   reference/routing
   reference/translation
   reference/templates
   reference/security
   reference/extensions
   reference/events
   reference/advanced_configuration
   reference/preview_mode

.. toctree::
   :caption: Forms
   :name: admin-bundle-forms
   :maxdepth: 1
   :numbered:

   reference/form_types
   reference/form_configuration
   reference/form_inline_validation
   reference/form_testing

.. toctree::
   :caption: Twig helpers
   :name: admin-bundle-twig-helpers
   :maxdepth: 1
   :numbered:

   reference/twig_configuration
   reference/twig_status_helper
   reference/twig_flash_messages

.. toctree::
   :caption: Blocks
   :name: admin-bundle-blocks
   :maxdepth: 1
   :numbered:

   reference/block_configuration
   reference/block_twig_helpers
   reference/block_provided_blocks
   reference/block_your_first_block
   reference/block_profiler
   reference/block_exceptions
   reference/block_advanced_usage
   reference/block_events
   reference/block_testing

.. toctree::
   :caption: Exporter
   :name: admin-bundle-exporter
   :maxdepth: 1
   :numbered:

   reference/exporter_introduction
   reference/exporter_sources
   reference/exporter_outputs
   reference/exporter_configuration

.. toctree::
   :caption: Cookbook
   :name: admin-bundle-cookbook
   :maxdepth: 1
   :numbered:

   cookbook/recipe_knp_menu
   cookbook/recipe_file_uploads
   cookbook/recipe_image_previews
   cookbook/recipe_row_templates
   cookbook/recipe_sortable_listing
   cookbook/recipe_dynamic_form_modification
   cookbook/recipe_custom_action
   cookbook/recipe_decouple_crud_controller
   cookbook/recipe_customizing_a_mosaic_list
   cookbook/recipe_overwrite_admin_configuration
   cookbook/recipe_improve_performance_large_datasets
   cookbook/recipe_virtual_field
   cookbook/recipe_lock_protection
   cookbook/recipe_sortable_sonata_type_model
   cookbook/recipe_delete_field_group
   cookbook/recipe_data_mapper
   cookbook/recipe_persisting_filters
   cookbook/recipe_workflow_integration
   cookbook/recipe_sonata_admin_without_user_bundle
   cookbook/recipe_rapid_prototyping
