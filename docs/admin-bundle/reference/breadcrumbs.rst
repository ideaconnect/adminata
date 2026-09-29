The breadcrumbs builder
=======================

The ``adminata.admin.breadcrumbs_builder`` service is used in the layout of every
page to compute the underlying data for two breadcrumbs:

* one as text, appearing in the ``title`` tag of the document's ``head`` tag;
* the other as html, visible as an horizontal bar at the top of the page.

Getting the breadcrumbs for a given action of a given admin is done like this::

   $this->get('adminata.admin.breadcrumbs_builder')->getBreadcrumbs($admin, $action);

Naming the screens
------------------

An admin's crumb — the second one on every page of the admin, linking to its list — is its
**title**, and that is also what the document title of its list page says. By default the title
is the admin's ``label``, the name the sidebar gives it, translated in the admin's
``translation_domain`` exactly as the sidebar translates it. So a screen the menu calls
*Products* is *Products* in the breadcrumb and in the browser tab too.

The ``titles`` attribute of the ``adminata.admin`` tag names the screens otherwise, keyed by
action:

.. code-block:: yaml

    # config/services.yaml

    app.admin.variant:
        class: App\Admin\VariantAdmin
        tags:
            -
                name: adminata.admin
                model_class: App\Entity\Variant
                manager_type: orm
                group: 'Catalogue'
                label: 'Variants'          # the sidebar, under "Catalogue"
                titles:
                    list: 'Product variants'   # the crumb and the list page's document title
                    create: 'New variant'      # the create page's last crumb and its heading
                    import: 'Import variants'  # a custom route that shows no object

* ``list`` names the admin itself, in place of its label.
* ``create`` names the create page — its last crumb, its heading and its document title. Without
  it the page says the generic *Create* (``title_create``): the words its heading always said,
  and its last crumb now too.
* Any other key names the page of a custom route that shows no object — its last crumb.

A page about an object ends on the object, so a title for ``edit``, ``show``, ``delete``,
``history`` or ``acl`` would be read nowhere, and neither would one for ``batch`` (the
confirmation is a list page) or ``export`` (a download): the container refuses them, and a
``titles`` value that does not map action names to titles, when it is built. The titles are
translated in the admin's ``translation_domain``, and ``translation:extract`` collects them.

Only a screen with no title at all — an admin without a ``label`` — is still named after its
model class, through the admin's label translator strategy (``Product List``,
``Product Launch``).

Configuration
-------------

.. code-block:: yaml

    # config/packages/adminata.yaml

    adminata:
        breadcrumbs:
            # use this to change the default route used to generate the link
            # to the parent object inside a breadcrumb, when in a child admin
            child_admin_route: show
