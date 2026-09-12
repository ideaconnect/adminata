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

use IDCT\Adminata\Block\Service\MenuBlockService;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('adminata.block.service.menu', MenuBlockService::class)
        ->tag('adminata.block')
        ->args([
            service('twig'),
            service('knp_menu.menu_provider'),
            service('adminata.block.menu.registry'),
        ]);
};
