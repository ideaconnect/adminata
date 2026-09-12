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

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use IDCT\Adminata\Block\BlockContextInterface;
use IDCT\Adminata\Menu\MenuRegistryInterface;
use IDCT\Adminata\Meta\Metadata;
use IDCT\Adminata\Meta\MetadataInterface;
use IDCT\Adminata\Model\BlockInterface;
use IDCT\Adminata\Validator\ErrorElement;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
final class MenuBlockService extends AbstractMenuBlockService
{
    public function __construct(
        Environment $twig,
        private MenuProviderInterface $menuProvider,
        private MenuRegistryInterface $menuRegistry,
    ) {
        parent::__construct($twig);
    }

    public function validate(ErrorElement $errorElement, BlockInterface $block): void
    {
        $name = $block->getSetting('menu_name');
        if (null !== $name && '' !== $name && !$this->menuProvider->has($name)) {
            // If we specified a menu_name, check that it exists
            $errorElement->with('menu_name')
                ->addViolation('adminata.block.menu.not_existing', ['%name%' => $name])
            ->end();
        }
    }

    public function configureSettings(OptionsResolver $resolver): void
    {
        parent::configureSettings($resolver);

        $resolver->setDefaults([
            'menu_name' => '',
        ]);
    }

    public function getMetadata(): MetadataInterface
    {
        return new Metadata('adminata.block.service.menu', null, null, 'AdminataBundle', [
            'class' => 'fa fa-bars',
        ]);
    }

    protected function getFormSettingsKeys(): array
    {
        $choiceOptions = [
            'required' => true,
            'label' => 'form.label_menu_name',
            'translation_domain' => 'AdminataBundle',
        ];

        $choiceOptions['choices'] = array_flip($this->menuRegistry->getAliasNames());

        return array_merge(
            parent::getFormSettingsKeys(),
            [
                ['menu_name', ChoiceType::class, $choiceOptions],
            ]
        );
    }

    protected function getMenu(BlockContextInterface $blockContext): ItemInterface|string
    {
        $settings = $blockContext->getSettings();

        return $settings['menu_name'];
    }
}
