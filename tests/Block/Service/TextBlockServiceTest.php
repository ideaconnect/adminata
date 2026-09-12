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

use IDCT\Adminata\Block\BlockContext;
use IDCT\Adminata\Block\BlockContextInterface;
use IDCT\Adminata\Block\Service\TextBlockService;
use IDCT\Adminata\Form\BlockFormMapperInterface;
use IDCT\Adminata\Model\Block;
use IDCT\Adminata\Model\BlockInterface;
use IDCT\Adminata\Test\BlockServiceTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TextBlockServiceTest extends BlockServiceTestCase
{
    public function testService(): void
    {
        $service = new TextBlockService($this->twig);

        $block = new Block();
        $block->setType('core.text');
        $block->setSettings([
            'content' => 'my text',
        ]);

        $optionResolver = new OptionsResolver();
        $service->configureSettings($optionResolver);

        $blockContext = new BlockContext($block, $optionResolver->resolve($block->getSettings()));

        $formMapper = $this->createMock(BlockFormMapperInterface::class);
        $formMapper->expects(static::exactly(2))->method('add');

        $service->configureCreateForm($formMapper, $block);
        $service->configureEditForm($formMapper, $block);

        $service->execute($blockContext);
    }

    public function testExecute(): void
    {
        $block = $this->createMock(BlockInterface::class);

        $blockContext = $this->createMock(BlockContextInterface::class);
        $blockContext->method('getTemplate')
            ->willReturn('@Adminata/Block/block_core_text.html.twig');
        $blockContext->method('getSettings')
            ->willReturn(['content' => 'foo']);
        $blockContext->method('getBlock')
            ->willReturn($block);

        $this->twig->expects(static::once())->method('render')
            ->with('@Adminata/Block/block_core_text.html.twig', [
                'block' => $block,
                'settings' => ['content' => 'foo'],
            ]);

        $service = new TextBlockService($this->twig);
        $response = $service->execute($blockContext);

        static::assertInstanceOf(Response::class, $response);
    }
}
