.. index::
    double: Twig Status Helpers; Definition

Twig status helper
==================

The admin bundle comes with a `Twig` helper allowing you to generate CSS class names, depending on
an entity field. The interface, the ``adminata.twig.status_extension`` Twig extension and the
``adminata_status_class`` filter are part of it, so there is nothing to install
(:doc:`/admin-bundle/getting_started/installation`).

Define a service
----------------

Each service you want to define must implement the ``IDCT\Adminata\Status\StatusClassRendererInterface`` interface::

    namespace Sonata\Component\Order;

    use IDCT\Adminata\Status\StatusClassRendererInterface;

    class OrderStatusRenderer implements StatusClassRendererInterface
    {
        public function handlesObject($object, $statusName = null)
        {
            // Logic validating if the render is applicable for the given object
        }

        public function getStatusClass($object, $statusName = null, $default = "")
        {
            // Label to render
        }
    }

Now that we have defined our service, we will add it using the ``adminata.status.renderer`` tag, just as follow:

.. code-block:: yaml

    services:
        adminata.order.status.renderer:
            class:  Sonata\Component\Order\OrderStatusRenderer
            tags:
                - { name: adminata.status.renderer }

Use the service
---------------

You can now call your helper in your twig views using the following code:

.. code-block:: html+twig

    {{ my_object|adminata_status_class(status_name, 'default_value') }}
