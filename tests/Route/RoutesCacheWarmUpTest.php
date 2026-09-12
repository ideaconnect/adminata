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

use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Route\RoutesCache;
use IDCT\Adminata\Route\RoutesCacheWarmUp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

final class RoutesCacheWarmUpTest extends TestCase
{
    private RoutesCacheWarmUp $routesCacheWarmUp;

    protected function setUp(): void
    {
        $pool = new Pool(new Container());

        $this->routesCacheWarmUp = new RoutesCacheWarmUp(new RoutesCache('test', false), $pool);
    }

    public function testIsOptional(): void
    {
        static::assertTrue($this->routesCacheWarmUp->isOptional());
    }
}
