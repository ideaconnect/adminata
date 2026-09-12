.. index::
    single: Configuration
    single: Exception
    single: Filter
    single: Renderer

Exception strategy
==================

Any exception thrown by a block service is handled by an exception strategy manager that determines how the exception should be handled.
A default strategy can be defined for all block types but a specific strategy can also be defined on a per block type basis.

The exception strategy uses an `exception filter` and an `exception renderer`.

Filters
-------

The role of an `exception filter` is to define which exceptions should be handled and which should be ignored silently. There are currently 4 filters available:

* ``debug_only``: only handle exceptions when in debug mode (default),
* ``ignore_block_exception``: only handle exceptions that don't implement ``BlockExceptionInterface``,
* ``keep_all``: handle all exceptions,
* ``keep__none``: ignore all exceptions.

.. warning::

    Use the ``keep__none`` filter with care!

These filters may be modified or combined with other filters in the configuration file:

.. code-block:: yaml

    # config/packages/adminata_block.yaml

    adminata_block:
        exception:
            default:
                filter: debug_only
            filters:
                debug_only:             adminata.block.exception.filter.debug_only
                ignore_block_exception: adminata.block.exception.filter.ignore_block_exception
                keep_all:               adminata.block.exception.filter.keep_all
                keep_none:              adminata.block.exception.filter.keep_none

A default filter may be configured to be applied to all block types. If you wish to customize a filter on a particular block type, you may also add the following option in the configuration file:

.. code-block:: yaml

    # config/packages/adminata_block.yaml

    adminata_block:
        blocks:
            adminata.block.service.text:
                exception: { filter: keep_all }

Renderers
---------

The role of an `exception renderer` is to define what to do with the exceptions that have passed the filter. There are currently 3 kind of renderer available:

* ``inline``: renders a `Twig` template within the rendering workflow with minimal information regarding the exception,
* ``inline_debug``: renders a twig template with the full debug exception information from Symfony,
* ``throw``: throws the exception to let the framework handle the exception.

These filters may be modified or completed with other filters in the configuration file:

.. code-block:: yaml

    # config/packages/adminata_block.yaml

    adminata_block:
        exception:
            default:
                renderer: throw
            renderers:
                inline:       adminata.block.exception.renderer.inline
                inline_debug: adminata.block.exception.renderer.inline_debug
                throw:        adminata.block.exception.renderer.throw

A `default renderer` will be applied to all block types. If you wish to use a different renderer on a particular block type, you should add the following option in the configuration file:

.. code-block:: yaml

    # config/packages/adminata_block.yaml

    adminata_block:
        blocks:
            adminata.block.service.text:
                exception: { renderer: inline }
