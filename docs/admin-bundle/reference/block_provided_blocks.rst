.. index::
    single: Block

Provided Blocks
===============

Some block services are already provided. You may use them or check out the code to get ideas on how to create your own.
You can also check this documentation: :doc:`block_your_first_block`.

EmptyBlockService
-----------------

The purpose of this block is to always return content, even on exceptions (``Sonata\AdminBundle\Exception\BlockNotFoundException``). See :doc:`Advanced Usage <block_advanced_usage>`.

TextBlockService
----------------

This block allows you to render anything you'd like. Be warned, the content you feed it with will be directly interpreted (which allows you to put in some HTML for instance).

Pretty straightforward, you need only to add the block service to your page and configure it with the content you'd like to see displayed in HTML.

Its template is ``@SonataAdmin/Block/block_core_text.html.twig``.

RssBlockService
---------------

This block displays an RSS feed.

When you add this block, specify a title and an RSS URL. Then, the last messages from the RSS feed will be displayed in your block.

Base template is ``@SonataAdmin/Block/block_core_rss.html.twig``; override it as
``templates/bundles/SonataAdminBundle/Block/block_core_rss.html.twig``, like any admin template
(:doc:`block_configuration`).

MenuBlockService
----------------

This block service displays a KNP Menu.

Defining a menu could be done by inserting the ``knp_menu.menu`` tag. The menu class is your
application's:

.. code-block:: yaml

    # config/services.yaml

    services:
        App\Menu\MainMenu:
            tags:
                - { name: knp_menu.menu, alias: sonata.main }

Upon configuration, you may set some rendering options (see KNP Doc for those). The default
template is ``@SonataAdmin/Block/block_core_menu.html.twig``.

A second menu template, ``@SonataAdmin/Block/block_side_menu_template.html.twig``, is shipped for
side menus and is selected with the ``menu_template`` option. Upstream wrote it for Bootstrap 3;
adminata inherits it **unported**, so it renders unstyled — see :doc:`/porting-status`.

.. _KnpMenuBundle documentation: https://symfony.com/doc/current/bundles/KnpMenuBundle/index.html
