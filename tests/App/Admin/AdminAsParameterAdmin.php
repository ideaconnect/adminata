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

namespace IDCT\Adminata\Tests\App\Admin;

use IDCT\Adminata\Admin\AbstractAdmin;
use IDCT\Adminata\Route\RouteCollectionInterface;
use IDCT\Adminata\Tests\App\Controller\InvokableController;

/**
 * @phpstan-extends AbstractAdmin<object>
 */
final class AdminAsParameterAdmin extends AbstractAdmin
{
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'tests/app/admin-as-parameter';
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'admin_admin_as_parameter';
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('test', null, [
            '_controller' => 'IDCT\Adminata\Tests\App\Controller\AdminAsParameterController::test',
        ]);

        $collection->add('invokable', null, [
            '_controller' => InvokableController::class,
        ]);
    }
}
