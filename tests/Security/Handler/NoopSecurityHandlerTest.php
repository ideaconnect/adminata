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

namespace IDCT\Adminata\Tests\Security\Handler;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Security\Handler\NoopSecurityHandler;

final class NoopSecurityHandlerTest extends TestCase
{
    private NoopSecurityHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new NoopSecurityHandler();
    }

    public function testIsGranted(): void
    {
        static::assertTrue($this->handler->isGranted($this->getAdminataObject(), ['TOTO']));
        static::assertTrue($this->handler->isGranted($this->getAdminataObject(), 'TOTO'));
    }

    public function testBuildSecurityInformation(): void
    {
        static::assertSame([], $this->handler->buildSecurityInformation($this->getAdminataObject()));
    }

    #[DoesNotPerformAssertions]
    public function testCreateObjectSecurity(): void
    {
        $this->handler->createObjectSecurity($this->getAdminataObject(), new \stdClass());
    }

    #[DoesNotPerformAssertions]
    public function testDeleteObjectSecurity(): void
    {
        $this->handler->deleteObjectSecurity($this->getAdminataObject(), new \stdClass());
    }

    public function testGetBaseRole(): void
    {
        static::assertSame('', $this->handler->getBaseRole($this->getAdminataObject()));
    }

    /**
     * @return AdminInterface<object>&MockObject
     */
    private function getAdminataObject(): AdminInterface
    {
        return $this->createMock(AdminInterface::class);
    }
}
