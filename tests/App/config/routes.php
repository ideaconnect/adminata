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

use IDCT\Adminata\Tests\App\Controller\BlockDemoController;
use IDCT\Adminata\Tests\App\Controller\FlashMessageDemoController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('@AdminataBundle/Resources/config/routing/adminata.php')
        ->prefix('/admin');

    $routes->import('.', 'adminata')
        ->prefix('/admin');

    $routes->add('blocks', '/blocks')
        ->controller(BlockDemoController::class);

    $routes->add('flash', '/flash')
        ->controller(FlashMessageDemoController::class);
};
