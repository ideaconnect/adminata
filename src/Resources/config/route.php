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

use IDCT\Adminata\Route\DefaultRouteGenerator;
use IDCT\Adminata\Route\PathInfoBuilder;
use IDCT\Adminata\Route\RoutesCache;
use IDCT\Adminata\Route\RoutesCacheWarmUp;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('adminata.admin.route.path_info', PathInfoBuilder::class)
            ->args([
                service('adminata.admin.audit.manager'),
            ])

        ->set('adminata.admin.route.default_generator', DefaultRouteGenerator::class)
            ->args([
                service('router'),
                service('adminata.admin.route.cache'),
            ])

        ->set('adminata.admin.route.cache', RoutesCache::class)
            ->args([
                param('kernel.cache_dir')->__toString().'/adminata/admin',
                param('kernel.debug'),
            ])

        ->set('adminata.admin.route.cache_warmup', RoutesCacheWarmUp::class)
            ->tag('kernel.cache_warmer')
            ->args([
                service('adminata.admin.route.cache'),
                service('adminata.admin.pool'),
            ]);
};
