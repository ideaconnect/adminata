<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IDCT\Adminata\Block\Service;

use IDCT\Adminata\Block\BlockContextInterface;
use IDCT\Adminata\Form\BlockFormMapperInterface;
use IDCT\Adminata\Form\Type\CollectionType;
use IDCT\Adminata\Form\Type\ContainerTemplateType;
use IDCT\Adminata\Form\Type\ImmutableArrayType;
use IDCT\Adminata\Meta\Metadata;
use IDCT\Adminata\Meta\MetadataInterface;
use IDCT\Adminata\Model\BlockInterface;
use IDCT\Adminata\Validator\ErrorElement;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Render children pages.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class ContainerBlockService extends AbstractBlockService implements EditableBlockService
{
    public function configureCreateForm(BlockFormMapperInterface $form, BlockInterface $block): void
    {
        $this->configureEditForm($form, $block);
    }

    public function configureEditForm(BlockFormMapperInterface $form, BlockInterface $block): void
    {
        $form->add('settings', ImmutableArrayType::class, [
            'keys' => [
                ['code', TextType::class, [
                    'required' => false,
                    'label' => 'form.label_code',
                    'translation_domain' => 'AdminataBundle',
                ]],
                ['layout', TextareaType::class, [
                    'label' => 'form.label_layout',
                    'translation_domain' => 'AdminataBundle',
                ]],
                ['class', TextType::class, [
                    'required' => false,
                    'label' => 'form.label_class',
                    'translation_domain' => 'AdminataBundle',
                ]],
                ['template', ContainerTemplateType::class, [
                    'label' => 'form.label_template',
                    'translation_domain' => 'AdminataBundle',
                ]],
            ],
            'translation_domain' => 'AdminataBundle',
        ]);

        $form->add('children', CollectionType::class);
    }

    public function execute(BlockContextInterface $blockContext, ?Response $response = null): Response
    {
        $template = $blockContext->getTemplate();

        return $this->renderResponse($template, [
            'block' => $blockContext->getBlock(),
            'decorator' => $this->getDecorator($blockContext->getSetting('layout')),
            'settings' => $blockContext->getSettings(),
        ], $response);
    }

    public function validate(ErrorElement $errorElement, BlockInterface $block): void
    {
    }

    public function configureSettings(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'code' => '',
            'layout' => '{{ CONTENT }}',
            'class' => '',
            'template' => '@Adminata/Block/block_container.html.twig',
        ]);
    }

    public function getMetadata(): MetadataInterface
    {
        return new Metadata('adminata.block.service.container', null, null, 'AdminataBundle', [
            'class' => 'fa fa-square-o',
        ]);
    }

    /**
     * Returns a decorator object/array from the container layout setting.
     *
     * @return array{pre?: string, post?: string}
     */
    private function getDecorator(string $layout): array
    {
        $key = '{{ CONTENT }}';
        if (!str_contains($layout, $key)) {
            return [];
        }

        $segments = explode($key, $layout);
        $decorator = [
            'pre' => $segments[0] ?? '',
            'post' => $segments[1] ?? '',
        ];

        return $decorator;
    }
}
