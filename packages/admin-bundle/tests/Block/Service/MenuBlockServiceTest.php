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

namespace Sonata\AdminBundle\Tests\Block\Service;

use Knp\Menu\Provider\MenuProviderInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Sonata\AdminBundle\Block\Service\MenuBlockService;
use Sonata\AdminBundle\Form\BlockFormMapperInterface;
use Sonata\AdminBundle\Form\Type\ImmutableArrayType;
use Sonata\AdminBundle\Menu\MenuRegistryInterface;
use Sonata\AdminBundle\Model\BlockInterface;
use Sonata\AdminBundle\Test\BlockServiceTestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class MenuBlockServiceTest extends BlockServiceTestCase
{
    /**
     * @var MenuProviderInterface&MockObject
     */
    private MenuProviderInterface $menuProvider;

    /**
     * @var MenuRegistryInterface&MockObject
     */
    private MenuRegistryInterface $menuRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->menuProvider = $this->createMock(MenuProviderInterface::class);
        $this->menuRegistry = $this->createMock(MenuRegistryInterface::class);
    }

    #[Group('legacy')]
    public function testBuildEditForm(): void
    {
        $this->menuRegistry->expects(static::once())->method('getAliasNames')
            ->willReturn([
                'acme:demobundle:menu' => 'Test Menu',
            ]);

        $formMapper = $this->createMock(BlockFormMapperInterface::class);

        $choiceOptions = [
            'required' => true,
            'label' => 'form.label_menu_name',
            'translation_domain' => 'SonataAdminBundle',
        ];

        $choiceOptions['choices'] = [
            'Test Menu' => 'acme:demobundle:menu',
        ];

        $formMapper->expects(static::once())->method('add')
            ->with('settings', ImmutableArrayType::class, [
                'keys' => [
                    ['title', TextType::class, [
                        'required' => false,
                        'label' => 'form.label_title',
                        'translation_domain' => 'SonataAdminBundle',
                    ]],
                    ['safe_labels', CheckboxType::class, [
                        'required' => false,
                        'label' => 'form.label_safe_labels',
                        'translation_domain' => 'SonataAdminBundle',
                    ]],
                    ['current_class', TextType::class, [
                        'required' => false,
                        'label' => 'form.label_current_class',
                        'translation_domain' => 'SonataAdminBundle',
                    ]],
                    ['first_class', TextType::class, [
                        'required' => false,
                        'label' => 'form.label_first_class',
                        'translation_domain' => 'SonataAdminBundle',
                    ]],
                    ['last_class', TextType::class, [
                        'required' => false,
                        'label' => 'form.label_last_class',
                        'translation_domain' => 'SonataAdminBundle',
                    ]],
                    ['menu_template', TextType::class, [
                        'required' => false,
                        'label' => 'form.label_menu_template',
                        'translation_domain' => 'SonataAdminBundle',
                    ]],
                    ['menu_name', ChoiceType::class, $choiceOptions],
                ],
                'translation_domain' => 'SonataAdminBundle',
            ]);

        $blockService = new MenuBlockService($this->twig, $this->menuProvider, $this->menuRegistry);
        $blockService->configureEditForm($formMapper, $this->createMock(BlockInterface::class));
    }

    public function testDefaultSettings(): void
    {
        $blockService = new MenuBlockService($this->twig, $this->menuProvider, $this->menuRegistry);
        $blockContext = $this->getBlockContext($blockService);

        $this->assertSettings([
            'title' => '',
            'template' => '@SonataAdmin/Block/block_core_menu.html.twig',
            'menu_name' => '',
            'safe_labels' => false,
            'current_class' => 'active',
            'first_class' => false,
            'last_class' => false,
            'menu_template' => null,
        ], $blockContext);
    }
}
