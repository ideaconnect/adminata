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

namespace IDCT\Adminata\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use IDCT\Adminata\DependencyInjection\Compiler\BlockTweakCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class BlockTweakCompilerPassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();

        $this->container->setDefinition('adminata.block.menu.registry', $this->createMock(Definition::class));
        $this->container->setDefinition('adminata.block.loader.chain', $this->createMock(Definition::class));
        $this->container->setDefinition('adminata.block.context_manager', $this->createMock(Definition::class));
        $this->container->setDefinition('adminata.block.loader.service', $this->createMock(Definition::class));

        $this->container->setParameter('adminata_block.blocks', []);
        $this->container->setParameter('adminata_blocks.block_types', []);
        $this->container->setParameter('adminata_block.cache_blocks', []);
        $this->container->setParameter('adminata_blocks.default_contexts', []);
        $this->container->setParameter('adminata_block.blocks_by_class', []);
    }

    public function testProcessAutowired(): void
    {
        $blockDefinition = new Definition(null, ['acme.block.service']);
        $blockDefinition->addTag('adminata.block');
        $blockDefinition->setAutoconfigured(true);

        $managerDefinition = $this->createMock(Definition::class);
        $managerDefinition->expects(static::once())->method('addMethodCall')->with('add', ['acme.block.service', 'acme.block.service', []]);

        $this->container->setDefinition('acme.block.service', $blockDefinition);
        $this->container->setDefinition('adminata.block.manager', $managerDefinition);

        $pass = new BlockTweakCompilerPass();
        $pass->process($this->container);
    }

    public function testProcessSameBlockId(): void
    {
        $blockDefinition = new Definition(null, ['acme.block.service']);
        $blockDefinition->addTag('adminata.block');

        $managerDefinition = $this->createMock(Definition::class);
        $managerDefinition->expects(static::once())->method('addMethodCall')->with('add', ['acme.block.service', 'acme.block.service', []]);

        $this->container->setDefinition('acme.block.service', $blockDefinition);
        $this->container->setDefinition('adminata.block.manager', $managerDefinition);

        $pass = new BlockTweakCompilerPass();
        $pass->process($this->container);
    }

    #[Group('legacy')]
    public function testProcessDifferentBlockId(): void
    {
        $blockDefinition = new Definition(null, ['acme.block.service.name']);
        $blockDefinition->addTag('adminata.block');

        $managerDefinition = $this->createMock(Definition::class);
        $managerDefinition->expects(static::once())->method('addMethodCall')->with('add', ['acme.block.service', 'acme.block.service', []]);

        $this->container->setDefinition('acme.block.service', $blockDefinition);
        $this->container->setDefinition('adminata.block.manager', $managerDefinition);

        $pass = new BlockTweakCompilerPass();
        $pass->process($this->container);
    }
}
