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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use IDCT\Adminata\Exception\Block\Filter\DebugOnlyFilter;
use IDCT\Adminata\Exception\Block\Filter\IgnoreClassFilter;
use IDCT\Adminata\Exception\Block\Filter\KeepAllFilter;
use IDCT\Adminata\Exception\Block\Filter\KeepNoneFilter;
use IDCT\Adminata\Exception\Block\Renderer\InlineDebugRenderer;
use IDCT\Adminata\Exception\Block\Renderer\InlineRenderer;
use IDCT\Adminata\Exception\Block\Renderer\MonkeyThrowRenderer;
use IDCT\Adminata\Exception\Block\Strategy\StrategyManager;
use IDCT\Adminata\Exception\BlockExceptionInterface;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('adminata.block.exception.strategy.manager', StrategyManager::class)
        ->args([
            service('service_container'),
            abstract_arg('filters array'),
            abstract_arg('renderers array'),
            abstract_arg('block filters array'),
            abstract_arg('block renderers array'),
        ]);

    $services->set('adminata.block.exception.filter.keep_none', KeepNoneFilter::class)
        ->public();

    $services->set('adminata.block.exception.filter.keep_all', KeepAllFilter::class)
        ->public();

    $services->set('adminata.block.exception.filter.debug_only', DebugOnlyFilter::class)
        ->public()
        ->args([
            param('kernel.debug'),
        ]);

    $services->set('adminata.block.exception.filter.ignore_block_exception', IgnoreClassFilter::class)
        ->public()
        ->args([
            BlockExceptionInterface::class,
        ]);

    $services->set('adminata.block.exception.renderer.inline', InlineRenderer::class)
        ->public()
        ->args([
            service('twig'),
            '@Adminata/Block/block_exception.html.twig',
        ]);

    $services->set('adminata.block.exception.renderer.inline_debug', InlineDebugRenderer::class)
        ->public()
        ->args([
            service('twig'),
            '@Adminata/Block/block_exception_debug.html.twig',
            param('kernel.debug'),
            true,
        ]);

    $services->set('adminata.block.exception.renderer.throw', MonkeyThrowRenderer::class)
        ->public();
};
