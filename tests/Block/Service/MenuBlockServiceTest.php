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

namespace IDCT\Adminata\Tests\Block\Service;

use Knp\Menu\Provider\MenuProviderInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use IDCT\Adminata\Block\Service\MenuBlockService;
use IDCT\Adminata\Form\BlockFormMapperInterface;
use IDCT\Adminata\Form\Type\ImmutableArrayType;
use IDCT\Adminata\Menu\MenuRegistryInterface;
use IDCT\Adminata\Model\BlockInterface;
use IDCT\Adminata\Test\BlockServiceTestCase;
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
            'translation_domain' => 'AdminataBundle',
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
                        'translation_domain' => 'AdminataBundle',
                    ]],
                    ['safe_labels', CheckboxType::class, [
                        'required' => false,
                        'label' => 'form.label_safe_labels',
                        'translation_domain' => 'AdminataBundle',
                    ]],
                    ['current_class', TextType::class, [
                        'required' => false,
                        'label' => 'form.label_current_class',
                        'translation_domain' => 'AdminataBundle',
                    ]],
                    ['first_class', TextType::class, [
                        'required' => false,
                        'label' => 'form.label_first_class',
                        'translation_domain' => 'AdminataBundle',
                    ]],
                    ['last_class', TextType::class, [
                        'required' => false,
                        'label' => 'form.label_last_class',
                        'translation_domain' => 'AdminataBundle',
                    ]],
                    ['menu_template', TextType::class, [
                        'required' => false,
                        'label' => 'form.label_menu_template',
                        'translation_domain' => 'AdminataBundle',
                    ]],
                    ['menu_name', ChoiceType::class, $choiceOptions],
                ],
                'translation_domain' => 'AdminataBundle',
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
            'template' => '@Adminata/Block/block_core_menu.html.twig',
            'menu_name' => '',
            'safe_labels' => false,
            'current_class' => 'active',
            'first_class' => false,
            'last_class' => false,
            'menu_template' => null,
        ], $blockContext);
    }
}
