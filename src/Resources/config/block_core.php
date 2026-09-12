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

use IDCT\Adminata\Block\BlockContextManager;
use IDCT\Adminata\Block\BlockLoaderChain;
use IDCT\Adminata\Block\BlockRenderer;
use IDCT\Adminata\Block\BlockServiceManager;
use IDCT\Adminata\Block\Loader\ServiceLoader;
use IDCT\Adminata\Menu\MenuRegistry;
use IDCT\Adminata\Templating\BlockHelper;
use IDCT\Adminata\Twig\BlockGlobalVariables;
use IDCT\Adminata\Twig\Extension\BlockExtension;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('adminata.block.manager', BlockServiceManager::class)
        ->public()
        ->args([
            abstract_arg('container of block services'),
            param('adminata.block.container.types'),
        ]);

    $services->set('adminata.block.menu.registry', MenuRegistry::class)
        ->public();

    $services->set('adminata.block.context_manager.default', BlockContextManager::class)
        ->public()
        ->args([
            service('adminata.block.loader.chain'),
            service('adminata.block.manager'),
            service('logger')->nullOnInvalid(),
        ]);

    $services->set('adminata.block.renderer.default', BlockRenderer::class)
        ->public()
        ->args([
            service('adminata.block.manager'),
            service('adminata.block.exception.strategy.manager'),
            service('logger')->nullOnInvalid(),
        ]);

    $services->set('adminata.block.twig.extension', BlockExtension::class)
        ->tag('twig.extension')
        ->args([
            service('adminata.block.templating.helper'),
        ]);

    $services->set('adminata.block.templating.helper', BlockHelper::class)
        ->tag('twig.runtime')
        ->args([
            service('adminata.block.renderer'),
            service('adminata.block.context_manager'),
            service('event_dispatcher'),
            service('debug.stopwatch')->nullOnInvalid(),
        ]);

    $services->set('adminata.block.loader.chain', BlockLoaderChain::class)
        ->args([
            abstract_arg('loaders array'),
        ]);

    $services->set('adminata.block.loader.service', ServiceLoader::class)
        ->tag('adminata.block.loader')
        ->args([
            abstract_arg('types array'),
        ]);

    $services->set('adminata.block.twig.global', BlockGlobalVariables::class)
        ->args([
            abstract_arg('templates array'),
        ]);

    $services->alias(BlockHelper::class, 'adminata.block.templating.helper');
};
