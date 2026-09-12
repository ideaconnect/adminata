Overwrite Admin Configuration
=============================

Sometimes you might want to overwrite some Admin settings from vendors.
This recipe will explain how to achieve this operation. However, keep
in mind this operation is quite dangerous and might break code.

From the configuration file, you can add a new section named ``default_admin_services``
with the following templates:

.. code-block:: yaml

    adminata:
        default_admin_services:
            # service configuration
            model_manager:              adminata.admin.manager.orm
            data_source:                adminata.admin.data_source.orm
            field_description_factory:  adminata.admin.field_description_factory.orm
            form_contractor:            adminata.admin.builder.orm_form
            show_builder:               adminata.admin.builder.orm_show
            list_builder:               adminata.admin.builder.orm_list
            datagrid_builder:           adminata.admin.builder.orm_datagrid
            translator:                 translator
            configuration_pool:         adminata.admin.pool
            route_generator:            adminata.admin.route.default_generator
            security_handler:           adminata.admin.security.handler
            menu_factory:               knp_menu.factory
            route_builder:              adminata.admin.route.path_info
            label_translator_strategy:  adminata.admin.label.strategy.native
            pager_type:                 default

With these settings you will be able to change default services and templates used by the admin instances.

If you need to override the service of a specific admin, you can do it during the service declaration:

.. code-block:: yaml

    # config/services.yaml

    services:
        admin.blog_post:
            class: App\Admin\BlogPostAdmin
            tags:
                - name: adminata.admin
                  model_class: App\Entity\BlogPost
                  manager_type: orm
                  label: 'Blog post'
                  label_translator_strategy: adminata.admin.label.strategy.native
                  route_builder: adminata.admin.route.path_info
                  pager_type: simple
                  # and so on
