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

namespace IDCT\Adminata\Tests\Route;

use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Model\AuditManagerInterface;
use IDCT\Adminata\Route\PathInfoBuilder;
use IDCT\Adminata\Route\RouteCollection;

final class PathInfoBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $audit = $this->createMock(AuditManagerInterface::class);
        $audit->expects(static::once())->method('hasReader')->willReturn(true);

        $admin = $this->createMock(AdminInterface::class);
        $admin->expects(static::once())->method('getChildren')->willReturn([]);
        $admin->expects(static::once())->method('isAclEnabled')->willReturn(true);

        $routeCollection = new RouteCollection('base.Code.Route', 'baseRouteName', 'baseRoutePattern', 'baseControllerName');

        $pathBuilder = new PathInfoBuilder($audit);

        $pathBuilder->build($admin, $routeCollection);

        static::assertCount(11, $routeCollection->getElements());
    }
}
