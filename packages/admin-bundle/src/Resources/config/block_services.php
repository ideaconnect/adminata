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

use Sonata\AdminBundle\Block\Service\ContainerBlockService;
use Sonata\AdminBundle\Block\Service\EmptyBlockService;
use Sonata\AdminBundle\Block\Service\RssBlockService;
use Sonata\AdminBundle\Block\Service\TemplateBlockService;
use Sonata\AdminBundle\Block\Service\TextBlockService;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('sonata.block.service.container', ContainerBlockService::class)
        ->tag('sonata.block')
        ->args([
            service('twig'),
        ]);

    $services->set('sonata.block.service.empty', EmptyBlockService::class)
        ->tag('sonata.block')
        ->args([
            service('twig'),
        ]);

    $services->set('sonata.block.service.text', TextBlockService::class)
        ->tag('sonata.block')
        ->args([
            service('twig'),
        ]);

    $services->set('sonata.block.service.rss', RssBlockService::class)
        ->tag('sonata.block')
        ->args([
            service('twig'),
        ]);

    $services->set('sonata.block.service.template', TemplateBlockService::class)
        ->tag('sonata.block')
        ->args([
            service('twig'),
        ]);
};
