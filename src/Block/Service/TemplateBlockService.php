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
use IDCT\Adminata\Form\Type\ImmutableArrayType;
use IDCT\Adminata\Meta\Metadata;
use IDCT\Adminata\Meta\MetadataInterface;
use IDCT\Adminata\Model\BlockInterface;
use IDCT\Adminata\Validator\ErrorElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class TemplateBlockService extends AbstractBlockService implements EditableBlockService
{
    public function execute(BlockContextInterface $blockContext, ?Response $response = null): Response
    {
        $template = $blockContext->getTemplate();

        return $this->renderResponse($template, [
            'block' => $blockContext->getBlock(),
            'settings' => $blockContext->getSettings(),
        ], $response);
    }

    public function configureCreateForm(BlockFormMapperInterface $form, BlockInterface $block): void
    {
        $this->configureEditForm($form, $block);
    }

    public function configureEditForm(BlockFormMapperInterface $form, BlockInterface $block): void
    {
        $form->add('settings', ImmutableArrayType::class, [
            'keys' => [
                ['template', null, [
                    'label' => 'form.label_template',
                    'translation_domain' => 'AdminataBundle',
                ]],
            ],
            'translation_domain' => 'AdminataBundle',
        ]);
    }

    public function validate(ErrorElement $errorElement, BlockInterface $block): void
    {
    }

    public function configureSettings(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'template' => '@Adminata/Block/block_template.html.twig',
        ]);
    }

    public function getMetadata(): MetadataInterface
    {
        return new Metadata('adminata.block.service.template', null, null, 'AdminataBundle', [
            'class' => 'fa fa-code',
        ]);
    }
}
