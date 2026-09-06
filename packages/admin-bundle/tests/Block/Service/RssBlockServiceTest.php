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

use Sonata\AdminBundle\Block\BlockContext;
use Sonata\AdminBundle\Block\BlockContextInterface;
use Sonata\AdminBundle\Block\Service\RssBlockService;
use Sonata\AdminBundle\Form\BlockFormMapperInterface;
use Sonata\AdminBundle\Model\Block;
use Sonata\AdminBundle\Model\BlockInterface;
use Sonata\AdminBundle\Test\BlockServiceTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RssBlockServiceTest extends BlockServiceTestCase
{
    /**
     * only test if the API is not broken.
     */
    public function testService(): void
    {
        $service = new RssBlockService($this->twig);

        $block = new Block();
        $block->setType('core.text');
        $block->setSettings([
            'content' => 'my text',
        ]);

        $optionResolver = new OptionsResolver();
        $service->configureSettings($optionResolver);

        $blockContext = new BlockContext($block, $optionResolver->resolve());

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
            ->willReturn('@SonataAdmin/Block/block_core_rss.html.twig');
        $blockContext->method('getSettings')
            ->willReturn([
                'title' => 'foo',
                'url' => 'http://example.com',
            ]);
        $blockContext->method('getBlock')
            ->willReturn($block);

        $this->twig->expects(static::once())->method('render')
            ->with('@SonataAdmin/Block/block_core_rss.html.twig', [
                'feeds' => false,
                'block' => $block,
                'settings' => [
                    'title' => 'foo',
                    'url' => 'http://example.com',
                ],
            ]);

        $service = new RssBlockService($this->twig);
        $response = $service->execute($blockContext);

        static::assertInstanceOf(Response::class, $response);
    }
}
