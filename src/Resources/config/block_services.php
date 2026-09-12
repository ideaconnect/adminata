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

use IDCT\Adminata\Block\Service\ContainerBlockService;
use IDCT\Adminata\Block\Service\EmptyBlockService;
use IDCT\Adminata\Block\Service\RssBlockService;
use IDCT\Adminata\Block\Service\TemplateBlockService;
use IDCT\Adminata\Block\Service\TextBlockService;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('adminata.block.service.container', ContainerBlockService::class)
        ->tag('adminata.block')
        ->args([
            service('twig'),
        ]);

    $services->set('adminata.block.service.empty', EmptyBlockService::class)
        ->tag('adminata.block')
        ->args([
            service('twig'),
        ]);

    $services->set('adminata.block.service.text', TextBlockService::class)
        ->tag('adminata.block')
        ->args([
            service('twig'),
        ]);

    $services->set('adminata.block.service.rss', RssBlockService::class)
        ->tag('adminata.block')
        ->args([
            service('twig'),
        ]);

    $services->set('adminata.block.service.template', TemplateBlockService::class)
        ->tag('adminata.block')
        ->args([
            service('twig'),
        ]);
};
