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

namespace IDCT\Adminata\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use IDCT\Adminata\DependencyInjection\BlockConfiguration;
use Symfony\Component\Config\Definition\Processor;

final class BlockConfigurationTest extends TestCase
{
    /**
     * @param string[] $contexts
     */
    #[DataProvider('provideOptionsCases')]
    public function testOptions(array $contexts): void
    {
        $defaultTemplates = [
            '@SonataPage/Block/block_container.html.twig' => 'SonataPageBundle template',
            '@SonataSeo/Block/block_social_container.html.twig' => 'SonataSeoBundle (to contain social buttons)',
        ];

        $processor = new Processor();

        $config = $processor->processConfiguration(new BlockConfiguration($defaultTemplates), [[
            'default_contexts' => $contexts,
            'blocks' => [
                'my.block.type' => [],
                'my.block_with_context.type' => ['context' => 'custom'],
            ],
        ]]);

        $expected = [
            'default_contexts' => $contexts,
            'blocks' => [
                'my.block.type' => [
                    'contexts' => $contexts,
                    'templates' => [],
                    'settings' => [],
                ],
                'my.block_with_context.type' => [
                    'contexts' => ['custom'],
                    'templates' => [],
                    'settings' => [],
                ],
            ],
            'profiler' => [
                'enabled' => '%kernel.debug%',
                'template' => '@Adminata/Profiler/block.html.twig',
            ],
            'context_manager' => 'adminata.block.context_manager.default',
            'http_cache' => false,
            'templates' => [
                'block_base' => null,
                'block_container' => null,
            ],
            'container' => [
                'types' => [
                    0 => 'adminata.block.service.container',
                    1 => 'sonata.page.block.container',
                    2 => 'adminata.dashboard.block.container',
                    3 => 'cmf.block.container',
                    4 => 'cmf.block.slideshow',
                ],
                'templates' => $defaultTemplates,
            ],
            'blocks_by_class' => [],
            'exception' => [
                'default' => [
                    'filter' => 'debug_only',
                    'renderer' => 'throw',
                ],
                'filters' => [
                    'debug_only' => 'adminata.block.exception.filter.debug_only',
                    'ignore_block_exception' => 'adminata.block.exception.filter.ignore_block_exception',
                    'keep_all' => 'adminata.block.exception.filter.keep_all',
                    'keep_none' => 'adminata.block.exception.filter.keep_none',
                ],
                'renderers' => [
                    'inline' => 'adminata.block.exception.renderer.inline',
                    'inline_debug' => 'adminata.block.exception.renderer.inline_debug',
                    'throw' => 'adminata.block.exception.renderer.throw',
                ],
            ],
        ];

        static::assertSame($expected, $config);
    }

    /**
     * @return iterable<array-key, array{array<string>}>
     */
    public static function provideOptionsCases(): iterable
    {
        yield [[]];
        yield [['cms']];
        yield [['cms', 'sonata_page_bundle']];
    }
}
