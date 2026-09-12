Templates
=========

``AdminataBundle`` comes with a significant amount of ``twig`` files used to display the
different parts of each ``Admin`` action's page. If you read the ``Templates`` part of the
:doc:`architecture` section of this guide, you should know by now how these are organized in
the ``views`` folder. If you haven't, now would be a good time to do it.

Besides these, some other views files are included from the storage layer. As their content and
structure are specific to each implementation, they are not discussed here, but it's important
that you keep in mind that they exist and are as relevant as the view files included
directly in ``AdminataBundle``.

Global Templates
----------------

``AdminataBundle`` views are implemented using ``twig`` files, and take full advantage of its
inheritance capabilities. As such, even the most simple page is actually rendered using many
different ``twig`` files. At the end of that ``twig`` inheritance hierarchy is always one of two files:

* layout: @Adminata/standard_layout.html.twig
* ajax: @Adminata/ajax_layout.html.twig

As you might have guessed from their names, the first is used in 'standard' request and the other
for AJAX calls. The ``@Adminata/standard_layout.html.twig`` contains several elements which
exist across the whole page, like the logo, title, upper menu and menu. It also includes the base CSS
and JavaScript files and libraries used across the whole administration section. The AJAX template
doesn't include any of these elements.

Dashboard Template
------------------

The template used for rendering the dashboard can also be configured. See the :doc:`dashboard` page
for more information

CRUDController Actions Templates
--------------------------------

As seen before, the ``CRUDController`` has several actions that allow you to manipulate your
model instances. Each of those actions uses a specific template file to render its content.
By default, ``AdminataBundle`` uses the following templates for their matching action:

* ``list`` : @Adminata/CRUD/list.html.twig
* ``show`` : @Adminata/CRUD/show.html.twig
* ``edit`` : @Adminata/CRUD/edit.html.twig
* ``history`` : @Adminata/CRUD/history.html.twig
* ``preview`` : @Adminata/CRUD/preview.html.twig
* ``delete`` : @Adminata/CRUD/delete.html.twig
* ``batch_confirmation`` : @Adminata/CRUD/batch_confirmation.html.twig
* ``acl`` : @Adminata/CRUD/acl.html.twig

Notice that all these templates extend other templates, and some do only that. This inheritance
architecture is designed to help you to make customizations by extending these templates
in your own bundle, rather than rewriting everything.

If you look closely, all of these templates ultimately extend the ``base_template`` variable that's
passed from the controller. This variable will always take the value of one of the above mentioned
global templates, and this is how changes made to those files affect all the ``AdminataBundle``
interface.

Row Templates
-------------

It is possible to completely change how each row of results is rendered in the
list view, by customizing the ``inner_list_row`` and ``base_list_field`` templates.
For more information about this, see the :doc:`../cookbook/recipe_row_templates`
cookbook entry.

Other Templates
---------------

There are several other templates that can be customized, enabling you to fine-tune
``AdminataBundle``:

* ``user_block`` : customizes the Twig block rendered by default in the top right
  corner of the admin interface, containing user information.
  Empty by default, see ``SonataUserBundle`` for a real example.
* ``add_block`` : customizes the Twig block rendered by default in the top right
  corner of the admin interface, providing quick access to create operations on
  available admin classes.
* ``history_revision_timestamp:`` customizes the way timestamps are rendered when
  using history related actions.
* ``action`` : a generic template you can use for your custom actions
* ``short_object_description`` : used by the ``getShortObjectDescriptionAction``
  action from the ``HelperController``, this template displays a small
  description of a model instance.
* ``list_block`` : the template used to render the dashboard's admin mapping lists.
  More info on the :doc:`dashboard` page.
* batch: template used to render the checkboxes that precede each instance on list views.
* ``select`` : when loading list views as part of adminata form types, this
  template is used to create a button that allows you to select the matching line.
* ``pager_links`` : renders the list of pages displayed at the end of the list view
  (when more than one page exists)
* ``pager_results`` : renders the dropdown that lets you choose the number of
  elements per page on list views

Configuring templates
---------------------

The main goal of this template structure is to make it comfortable for you
to customize the ones you need. You can extend the ones you want in your own bundle, and
tell ``AdminataBundle`` to use your templates instead of the default ones. You can do so
in several ways.

You can specify your templates in the config file:

.. code-block:: yaml

    # config/packages/adminata.yaml

    adminata:
        templates:
            layout:                     '@Adminata/standard_layout.html.twig'
            ajax:                       '@Adminata/ajax_layout.html.twig'
            list:                       '@Adminata/CRUD/list.html.twig'
            show:                       '@Adminata/CRUD/show.html.twig'
            show_compare:               '@Adminata/CRUD/show_compare.html.twig'
            edit:                       '@Adminata/CRUD/edit.html.twig'
            history:                    '@Adminata/CRUD/history.html.twig'
            preview:                    '@Adminata/CRUD/preview.html.twig'
            delete:                     '@Adminata/CRUD/delete.html.twig'
            batch:                      '@Adminata/CRUD/list__batch.html.twig'
            acl:                        '@Adminata/CRUD/acl.html.twig'
            action:                     '@Adminata/CRUD/action.html.twig'
            select:                     '@Adminata/CRUD/list__select.html.twig'
            filter:                     '@Adminata/Form/filter_admin_fields.html.twig'
            dashboard:                  '@Adminata/Core/dashboard.html.twig'
            search:                     '@Adminata/Core/search.html.twig'
            batch_confirmation:         '@Adminata/CRUD/batch_confirmation.html.twig'
            inner_list_row:             '@Adminata/CRUD/list_inner_row.html.twig'
            base_list_field:            '@Adminata/CRUD/base_list_field.html.twig'
            list_block:                 '@Adminata/Block/block_admin_list.html.twig'
            user_block:                 '@Adminata/Core/user_block.html.twig'
            add_block:                  '@Adminata/Core/add_block.html.twig'
            pager_links:                '@Adminata/Pager/links.html.twig'
            pager_results:              '@Adminata/Pager/results.html.twig'
            tab_menu_template:          '@Adminata/Core/tab_menu_template.html.twig'
            history_revision_timestamp: '@Adminata/CRUD/history_revision_timestamp.html.twig'
            short_object_description:   '@Adminata/Helper/short-object-description.html.twig'
            search_result_block:        '@Adminata/Block/block_search_result.html.twig'
            action_create:              '@Adminata/CRUD/dashboard__action_create.html.twig'
            button_acl:                 '@Adminata/Button/acl_button.html.twig'
            button_create:              '@Adminata/Button/create_button.html.twig'
            button_edit:                '@Adminata/Button/edit_button.html.twig'
            button_history:             '@Adminata/Button/history_button.html.twig'
            button_list:                '@Adminata/Button/list_button.html.twig'
            button_show:                '@Adminata/Button/show_button.html.twig'
            form_theme:                 []
            filter_theme:               []

.. warning::

    Notice that this is a global change, meaning it will affect all model mappings
    automatically, both for ``Admin`` mappings defined by you and by other bundles.

.. note::

    The block templates — ``list_block`` and ``search_result_block`` above, and the ones the block
    services themselves default to — are ``@Adminata/Block/…`` like every other path here, and
    are overridden the same way, under ``templates/bundles/AdminataBundle/Block/``; see
    :doc:`block_configuration`.

If you wish, you can specify custom templates on a per ``Admin`` mapping
basis. Internally, the ``CRUDController`` fetches this information from the
``TemplateRegistry`` class instance that belongs with the ``Admin``, so you
can specify the templates to use in the ``Admin`` service definition:

.. code-block:: yaml

    # config/services.yaml

    services:
        app.admin.post:
            class: App\Admin\PostAdmin
            calls:
                - [setTemplate, ['edit', 'PostAdmin/edit.html.twig']]
            tags:
                - { name: adminata.admin, model_class: App\Entity\Post, manager_type: orm, group: 'Content', label: 'Post' }

.. note::

    A ``setTemplates(array $templates)`` (notice the plural) method also
    exists, that allows you to set multiple templates at once.

Changes made using the ``setTemplate()`` and ``setTemplates()`` methods
override the customizations made in the configuration file, so you can specify
a global custom template and then override that customization on a specific
``Admin`` class.

Finding configured templates
----------------------------
Each ``Admin`` has a ``TemplateRegistry`` service connected to it that holds
the templates registered through the configuration above. Through the method
``getTemplate($name)`` of that class, you can access the templates set for
that ``Admin``. The ``TemplateRegistry`` is available through ``$this->getTemplateRegistry()``
within the ``Admin``. Using the service container the template registries can
be accessed outside an ``Admin``. Use the ``Admin`` code + ``.template_registry``
as the service ID (i.e. "app.admin.post" uses the Template Registry
"app.admin.post.template_registry").

The ``TemplateRegistry`` service that holds the global templates can be accessed
using the service ID "adminata.admin.global_template_registry".

Within Twig templates, you can use the ``get_admin_template($name, $adminCode)``
function to access the templates of the current ``Admin``, or the
``get_global_template($name)`` function to access global templates.

.. code-block:: html+twig

    {% extends get_admin_template('base_list_field', admin.code) %}

    {% block field %}
        {# ... #}
    {% endblock %}
