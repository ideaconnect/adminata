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

namespace IDCT\Adminata\Tests\Block;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use IDCT\Adminata\Block\BlockContextInterface;
use IDCT\Adminata\Block\BlockContextManager;
use IDCT\Adminata\Block\BlockLoaderInterface;
use IDCT\Adminata\Block\BlockServiceManagerInterface;
use IDCT\Adminata\Block\Service\AbstractBlockService;
use IDCT\Adminata\Model\Block;
use IDCT\Adminata\Model\BlockInterface;

final class BlockContextManagerTest extends TestCase
{
    public function testGetWithValidData(): void
    {
        $service = $this->createMock(AbstractBlockService::class);

        $service->expects(static::once())->method('configureSettings');

        $serviceManager = $this->createMock(BlockServiceManagerInterface::class);
        $serviceManager->expects(static::once())->method('get')->willReturn($service);

        $block = $this->createMock(BlockInterface::class);
        $block->expects(static::once())->method('getSettings')->willReturn([]);

        $manager = new BlockContextManager($this->createMock(BlockLoaderInterface::class), $serviceManager);

        $settings = ['template' => 'custom.html.twig'];

        $blockContext = $manager->get($block, $settings);

        static::assertInstanceOf(BlockContextInterface::class, $blockContext);

        static::assertSame([
            'attr' => [],
            'template' => 'custom.html.twig',
        ], $blockContext->getSettings());
    }

    public function testGetWithBlockMeta(): void
    {
        $service = $this->createMock(AbstractBlockService::class);

        $service->expects(static::once())->method('configureSettings');

        $blockLoader = $this->createMock(BlockLoaderInterface::class);
        $blockLoader->expects(static::once())->method('load')->willReturn($block = new Block());

        $serviceManager = $this->createMock(BlockServiceManagerInterface::class);
        $serviceManager->expects(static::once())->method('get')->willReturn($service);

        $blockMeta = [
            'type' => 'some_block_id',
        ];

        $manager = new BlockContextManager($blockLoader, $serviceManager);

        $settings = ['template' => 'custom.html.twig'];

        $blockContext = $manager->get($blockMeta, $settings);

        static::assertInstanceOf(BlockContextInterface::class, $blockContext);

        static::assertSame([
            'template' => 'custom.html.twig',
        ], $block->getSettings());

        static::assertSame([
            'attr' => [],
            'template' => 'custom.html.twig',
        ], $blockContext->getSettings());
    }

    public function testGetWithSettings(): void
    {
        $service = $this->createMock(AbstractBlockService::class);
        $service->expects(static::once())->method('configureSettings');

        $serviceManager = $this->createMock(BlockServiceManagerInterface::class);
        $serviceManager->expects(static::once())->method('get')->willReturn($service);

        $block = $this->createMock(BlockInterface::class);
        $block->expects(static::once())->method('getSettings')->willReturn([]);

        $manager = new BlockContextManager($this->createMock(BlockLoaderInterface::class), $serviceManager);

        $settings = ['template' => 'custom.html.twig'];

        $blockContext = $manager->get($block, $settings);

        static::assertInstanceOf(BlockContextInterface::class, $blockContext);

        static::assertSame([
            'attr' => [],
            'template' => 'custom.html.twig',
        ], $blockContext->getSettings());
    }

    public function testWithInvalidSettings(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())->method('error');

        $service = $this->createMock(AbstractBlockService::class);
        $service->expects(static::exactly(2))->method('configureSettings');

        $serviceManager = $this->createMock(BlockServiceManagerInterface::class);
        $serviceManager->expects(static::exactly(2))->method('get')->willReturn($service);

        $block = $this->createMock(BlockInterface::class);
        $block->expects(static::once())->method('getSettings')->willReturn([
            'template' => 'custom.html.twig',
            'attr' => 'shouldBeAnArray',
        ]);
        $block->expects(static::once())->method('getSetting')->with('template')->willReturn('custom.html.twig');

        $manager = new BlockContextManager($this->createMock(BlockLoaderInterface::class), $serviceManager, $logger);

        $blockContext = $manager->get($block);

        static::assertInstanceOf(BlockContextInterface::class, $blockContext);
    }
}
