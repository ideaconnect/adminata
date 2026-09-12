.. index::
    single: Block
    single: Tutorial
    single: RSS Block

Your first block
================

This quick tutorial explains how to create an `RSS reader` block.

A `block service` is just a service which must implement the ``BlockServiceInterface`` interface — plus ``EditableBlockService`` when the block is edited through a form. There is only one instance of a block service, however there are many block instances.

First namespaces
----------------

The ``AbstractBlockService`` implements some basic methods defined by the interface.
The current RSS block will extend this base class. The other `use` statements are required by the interface's remaining methods::

    namespace App\Block;

    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\Form\Extension\Core\Type\UrlType;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use IDCT\Adminata\Block\BlockContextInterface;
    use IDCT\Adminata\Block\Service\AbstractBlockService;
    use IDCT\Adminata\Block\Service\EditableBlockService;
    use IDCT\Adminata\Form\BlockFormMapperInterface;
    use IDCT\Adminata\Model\BlockInterface;
    use IDCT\Adminata\Form\Type\ImmutableArrayType;
    use IDCT\Adminata\Validator\ErrorElement;

.. note::

    Those are adminata's names. Upstream ``sonata-project/block-bundle`` puts the same classes under
    ``IDCT\Adminata\``; here the block sources are part of the admin bundle, so they are
    ``IDCT\Adminata\``.

Default settings
----------------

A `block service` needs settings to work properly, so to ensure consistency, the service should define a ``configureSettings`` method::

    public function configureSettings(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'url' => false,
            'title' => 'Insert the rss title',
            'template' => '@Adminata/Block/block_core_rss.html.twig',
        ]);
    }

In the current tutorial, the default settings are:

* `URL`: the feed url,
* `title`: the block title,
* `template`: the template to render the block — here the shipped RSS template, which lives under
  ``@Adminata/Block/`` like every block template and is overridden, like every admin template,
  in ``templates/bundles/AdminataBundle/Block/`` (:doc:`block_configuration`).

Form Editing
------------

You can define an editing config the following way::

    public function configureEditForm(BlockFormMapperInterface $form, BlockInterface $block): void
    {
        $form->add('settings', ImmutableArrayType::class, [
            'keys' => [
                ['url', UrlType::class, ['required' => false]],
                ['title', TextType::class, ['required' => false]],
            ],
        ]);
    }

``EditableBlockService`` also asks for ``configureCreateForm()``; when the two forms are the same, delegate one to the other, which is what the block services adminata ships do.

The validation is done at runtime through a ``validate`` method. You can call any Symfony assertions, like::

    public function validate(ErrorElement $errorElement, BlockInterface $block): void
    {
        $errorElement
            ->with('settings.url')
                ->assertNotNull([])
                ->assertNotBlank()
            ->end()
            ->with('settings.title')
                ->assertNotNull([])
                ->assertNotBlank()
                ->assertMaxLength(['limit' => 50])
            ->end()
        ;
    }

``ImmutableArrayType`` (form alias ``adminata_type_immutable_array``) is a specific `form type` which allows to edit an array.

Execute
-------

The next step is to implement the `execute` method. This method must return a ``Response`` object, which is used to render the block::

    public function execute(BlockContextInterface $blockContext, ?Response $response = null): Response
    {
        // merge settings
        $settings = $blockContext->getSettings();
        $feeds = false;

        if ($settings['url']) {
            $options = [
                'http' => [
                    'user_agent' => 'Sonata/RSS Reader',
                    'timeout' => 2,
                ]
            ];

            // retrieve contents with a specific stream context to avoid php errors
            $content = @file_get_contents($settings['url'], false, stream_context_create($options));

            if ($content) {
                // generate a simple xml element
                try {
                    $feeds = new \SimpleXMLElement($content);
                    $feeds = $feeds->channel->item;
                } catch (\Exception $e) {
                    // silently fail error
                }
            }
        }

        return $this->renderResponse($blockContext->getTemplate(), [
            'feeds'     => $feeds,
            'block'     => $blockContext->getBlock(),
            'settings'  => $settings
        ], $response);
    }

Template
--------

In this tutorial, the block template is very simple. We loop through feeds, or if none are available, an error message is displayed.

.. code-block:: twig

    {% extends adminata_block.templates.block_base %}

    {% block block %}
        <h3 class="adminata-feed-title">{{ settings.title }}</h3>

        <div class="adminata-feeds-container">
            {% for feed in feeds %}
                <div>
                    <strong><a href="{{ feed.link}}" rel="nofollow" title="{{ feed.title }}">{{ feed.title }}</a></strong>
                    <div>{{ feed.description|raw }}</div>
                </div>
            {% else %}
                    No feeds available.
            {% endfor %}
        </div>
    {% endblock %}

Service
-------

We are almost done! Now, just declare the block as a service:

.. code-block:: yaml

    # config/services.yaml

    services:
        adminata.block.service.rss:
            class: App\Block\RssBlockService
            arguments:
                - '@twig'
            tags:
                - { name: adminata.block }

Then, add the service to Sonata configuration:

.. code-block:: yaml

    # config/packages/adminata_block.yaml

    adminata_block:
        blocks:
            adminata.block.service.rss: ~
