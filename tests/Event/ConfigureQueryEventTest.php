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

namespace IDCT\Adminata\Tests\Event;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Datagrid\ProxyQueryInterface;
use IDCT\Adminata\Event\ConfigureQueryEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfigureQueryEventTest extends TestCase
{
    private ConfigureQueryEvent $event;

    /**
     * @var AdminInterface<object>&MockObject
     */
    private AdminInterface $admin;

    /**
     * @var ProxyQueryInterface<object>&MockObject
     */
    private ProxyQueryInterface $proxyQuery;

    protected function setUp(): void
    {
        $this->admin = $this->createMock(AdminInterface::class);
        $this->proxyQuery = $this->createMock(ProxyQueryInterface::class);

        $this->event = new ConfigureQueryEvent($this->admin, $this->proxyQuery, 'Foo');
    }

    public function testGetContext(): void
    {
        static::assertSame('Foo', $this->event->getContext());
    }

    public function testGetAdmin(): void
    {
        $result = $this->event->getAdmin();

        static::assertInstanceOf(AdminInterface::class, $result);
        static::assertSame($this->admin, $result);
    }

    public function testGetProxyQuery(): void
    {
        $result = $this->event->getProxyQuery();

        static::assertInstanceOf(ProxyQueryInterface::class, $result);
        static::assertSame($this->proxyQuery, $result);
    }
}
