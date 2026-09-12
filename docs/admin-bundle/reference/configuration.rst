Configuration
=============

.. note::

    This page will be removed soon, as it's content is being improved and moved to
    other pages of the documentation. Please refer to each section's documentation for up-to-date
    information on AdminataBundle configuration options.

Configuration
-------------

Configuration options

.. code-block:: yaml

    # config/packages/adminata.yaml

    adminata:
        security:

            # the default value
            handler: adminata.admin.security.handler.role

            # use this service if you want ACL
            handler: adminata.admin.security.handler.acl

Full Configuration Options
--------------------------

Everything below is the tree adminata's ``Configuration`` class actually declares. It is the same
text ``ConfigContractTest`` compares against on every build, so it cannot drift from the code: if a
node is added, removed or given a different default, that test fails until the capture is updated,
and this page is that capture.

Four nodes upstream documented here no longer exist, and leaving one in your ``adminata.yaml``
is a container build error rather than a warning: ``options.skin``, ``options.use_select2``,
``options.use_icheck`` and ``options.use_bootlint``. See :doc:`/upgrading`.

.. code-block:: yaml

    # config/packages/adminata.yaml

    adminata:
        security:
            handler:              adminata.admin.security.handler.noop
            information:

                # Prototype
                id:                   []
            admin_permissions:

                # Defaults:
                - CREATE
                - LIST
                - DELETE
                - UNDELETE
                - EXPORT
                - OPERATOR
                - MASTER

            # Role which will see the top nav bar and dropdown groups regardless of its configuration
            role_admin:           ROLE_ADMINATA_ADMIN

            # Role which will perform all admin actions, see dashboard, menu and search groups regardless of its configuration
            role_super_admin:     ROLE_SUPER_ADMIN
            object_permissions:

                # Defaults:
                - VIEW
                - EDIT
                - HISTORY
                - DELETE
                - UNDELETE
                - OPERATOR
                - MASTER
                - OWNER
            acl_user_manager:     null
        title:                'Sonata Admin'
        title_logo:           bundles/adminata/images/logo_title.png

        # Enable/disable the search form in the sidebar
        search:               true

        # Light and dark mode, and the logos each of them uses
        theme:

            # Which mode a visitor without a "adminata_theme" cookie gets
            mode:                 system # One of "light"; "dark"; "system"

            # Logo shown in dark mode; the "title_logo" one is used when this is null
            logo_dark:            null

            # Square logo shown when the sidebar is collapsed
            logo_icon:            null
        global_search:

            # Perhaps one of the three options: show, fade, hide.
            empty_boxes:          show

            # Change the default route used to generate the link to the object
            admin_route:          show

        # Name of the controller class to be used as a default in admin definitions
        default_controller:   adminata.admin.controller.crud
        breadcrumbs:

            # Change the default route used to generate the link to the parent object, when in a child admin
            child_admin_route:    show
        options:
            html5_validate:       true

            # Auto order groups and admins by label or id
            sort_admins:          false
            confirm_exit:         true
            js_debug:             false
            use_stickyforms:      true
            pager_links:          null
            form_type:            standard

            # Name of the admin route to be used as a default to generate the link to the object
            default_admin_route:  show

            # Group used for admin services if one isn't provided.
            default_group:        default

            # Label Catalogue used for admin services if one isn't provided.
            default_label_catalogue: AdminataBundle # Deprecated (Since sonata-project/admin-bundle 4.9: The "default_label_catalogue" node is deprecated, use "default_translation_domain" instead.)

            # Translation domain used for admin services if one isn't provided.
            default_translation_domain: null

            # Icon used for admin services if one isn't provided.
            default_icon:         'fas fa-folder'
            dropdown_number_groups_per_colums: 2
            logo_content:         all # One of "text"; "icon"; "all"
            list_action_button_content: all # One of "text"; "icon"; "all"

            # Open the object when a list row is clicked, using the route named by default_admin_route
            list_row_link:        true

            # Enable locking when editing an object, if the corresponding object manager supports it.
            lock_protection:      false

            # Background used in mosaic view
            mosaic_background:    bundles/adminata/images/default_mosaic_image.png
        dashboard:
            groups:

                # Prototype
                id:
                    label:                ~
                    translation_domain:   ~
                    label_catalogue:      ~ # Deprecated (Since sonata-project/admin-bundle 4.9: The "label_catalogue" node is deprecated, use "translation_domain" instead.)
                    icon:                 ~

                    # Show menu item in side dashboard menu without treeview
                    on_top:               false

                    # Keep menu group always open
                    keep_open:            false
                    provider:             ~
                    items:

                        # Prototype
                        -
                            admin:                ~
                            label:                ~
                            route:                ~
                            roles:                []
                            route_params:         []

                            # Whether the generated url should be absolute
                            route_absolute:       false
                    item_adds:            [] # Deprecated (Since sonata-project/admin-bundle 4.9: The "item_adds" node is deprecated)
                    roles:                []
            blocks:

                # Prototype
                -
                    type:                 ~
                    roles:                []
                    settings:

                        # Prototype
                        id:                   ~
                    position:             right
                    class:                'md:col-span-4'
        default_admin_services:
            model_manager:        null
            data_source:          null
            field_description_factory: null
            form_contractor:      null
            show_builder:         null
            list_builder:         null
            datagrid_builder:     null
            translator:           null
            configuration_pool:   null
            route_generator:      null
            security_handler:     null
            menu_factory:         null
            route_builder:        null
            label_translator_strategy: null
            pager_type:           null
        templates:
            user_block:           '@Adminata/Core/user_block.html.twig'
            add_block:            '@Adminata/Core/add_block.html.twig'
            layout:               '@Adminata/standard_layout.html.twig'
            ajax:                 '@Adminata/ajax_layout.html.twig'
            dashboard:            '@Adminata/Core/dashboard.html.twig'
            search:               '@Adminata/Core/search.html.twig'
            list:                 '@Adminata/CRUD/list.html.twig'
            filter:               '@Adminata/Form/filter_admin_fields.html.twig'
            show:                 '@Adminata/CRUD/show.html.twig'
            show_compare:         '@Adminata/CRUD/show_compare.html.twig'
            edit:                 '@Adminata/CRUD/edit.html.twig'
            preview:              '@Adminata/CRUD/preview.html.twig'
            history:              '@Adminata/CRUD/history.html.twig'
            acl:                  '@Adminata/CRUD/acl.html.twig'
            history_revision_timestamp: '@Adminata/CRUD/history_revision_timestamp.html.twig'
            action:               '@Adminata/CRUD/action.html.twig'
            select:               '@Adminata/CRUD/list__select.html.twig'
            list_block:           '@Adminata/Block/block_admin_list.html.twig'
            search_result_block:  '@Adminata/Block/block_search_result.html.twig'
            short_object_description: '@Adminata/Helper/short-object-description.html.twig'
            delete:               '@Adminata/CRUD/delete.html.twig'
            batch:                '@Adminata/CRUD/list__batch.html.twig'
            batch_confirmation:   '@Adminata/CRUD/batch_confirmation.html.twig'
            inner_list_row:       '@Adminata/CRUD/list_inner_row.html.twig'
            outer_list_rows_mosaic: '@Adminata/CRUD/list_outer_rows_mosaic.html.twig'
            outer_list_rows_list: '@Adminata/CRUD/list_outer_rows_list.html.twig'
            outer_list_rows_tree: '@Adminata/CRUD/list_outer_rows_tree.html.twig'
            base_list_field:      '@Adminata/CRUD/base_list_field.html.twig'
            pager_links:          '@Adminata/Pager/links.html.twig'
            pager_results:        '@Adminata/Pager/results.html.twig'
            tab_menu_template:    '@Adminata/Core/tab_menu_template.html.twig'
            knp_menu_template:    '@Adminata/Menu/adminata_menu.html.twig'
            action_create:        '@Adminata/CRUD/dashboard__action_create.html.twig'
            button_acl:           '@Adminata/Button/acl_button.html.twig'
            button_create:        '@Adminata/Button/create_button.html.twig'
            button_edit:          '@Adminata/Button/edit_button.html.twig'
            button_history:       '@Adminata/Button/history_button.html.twig'
            button_list:          '@Adminata/Button/list_button.html.twig'
            button_show:          '@Adminata/Button/show_button.html.twig'
            form_theme:           []
            filter_theme:         []
        assets:
            stylesheets:

                # Prototype
                -
                    path:                 ~ # Required
                    package_name:         adminata

            # stylesheets to add to the page
            extra_stylesheets:

                # Prototype
                -
                    path:                 ~ # Required
                    package_name:         adminata

            # stylesheets to remove from the page
            remove_stylesheets:

                # Prototype
                -
                    path:                 ~ # Required
                    package_name:         adminata
            javascripts:

                # Prototype
                -
                    path:                 ~ # Required
                    package_name:         adminata

            # javascripts to add to the page
            extra_javascripts:

                # Prototype
                -
                    path:                 ~ # Required
                    package_name:         adminata

            # javascripts to remove from the page
            remove_javascripts:

                # Prototype
                -
                    path:                 ~ # Required
                    package_name:         adminata
        extensions:

            # Prototype
            id:
                global:               false
                admins:               []
                excludes:             []
                implements:           []
                extends:              []
                instanceof:           []
                uses:                 []
                admin_implements:     []
                admin_extends:        []
                admin_instanceof:     []
                admin_uses:           []

                # Positive or negative integer. The higher the priority, the earlier it’s executed.
                priority:             0
        persist_filters:      false
        filter_persister:     adminata.admin.filter_persister.session

        # Show mosaic button on all admin screens
        show_mosaic_button:   true
