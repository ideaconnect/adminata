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

namespace IDCT\Adminata\Tests\App\Admin\Extension;

use IDCT\Adminata\Admin\AbstractAdminExtension;
use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Route\RouteCollectionInterface;
use IDCT\Adminata\Tests\App\Action\BrowseAction;

/**
 * @phpstan-extends AbstractAdminExtension<object>
 */
final class CustomAdminExtension extends AbstractAdminExtension
{
    public function configureRoutes(AdminInterface $admin, RouteCollectionInterface $collection): void
    {
        $collection->add('browse', 'browse', [
            '_controller' => BrowseAction::class,
        ]);
    }
}
