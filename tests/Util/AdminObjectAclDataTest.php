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

namespace IDCT\Adminata\Tests\Util;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Security\Acl\Permission\MaskBuilder;
use IDCT\Adminata\Security\Handler\AclSecurityHandlerInterface;
use IDCT\Adminata\Util\AdminObjectAclData;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;

/**
 * @author Kévin Dunglas <kevin@les-tilleuls.coop>
 */
final class AdminObjectAclDataTest extends TestCase
{
    public function testGetAdmin(): void
    {
        $adminObjectAclData = $this->createAdminObjectAclData();
        static::assertInstanceOf(AdminInterface::class, $adminObjectAclData->getAdmin());
    }

    public function testGetObject(): void
    {
        $adminObjectAclData = $this->createAdminObjectAclData();
        static::assertInstanceOf(\stdClass::class, $adminObjectAclData->getObject());
    }

    public function testGetAclUsers(): void
    {
        $adminObjectAclData = $this->createAdminObjectAclData();
        static::assertInstanceOf(\ArrayIterator::class, $adminObjectAclData->getAclUsers());
    }

    public function testGetAclRoles(): void
    {
        $adminObjectAclData = $this->createAdminObjectAclData();
        static::assertInstanceOf(\ArrayIterator::class, $adminObjectAclData->getAclRoles());
    }

    public function testSetAcl(): AdminObjectAclData
    {
        $adminObjectAclData = $this->createAdminObjectAclData();
        $ret = $adminObjectAclData->setAcl($this->createMock(MutableAclInterface::class));

        static::assertSame($adminObjectAclData, $ret);

        return $adminObjectAclData;
    }

    #[Depends('testSetAcl')]
    public function testGetAcl(AdminObjectAclData $adminObjectAclData): void
    {
        static::assertInstanceOf(MutableAclInterface::class, $adminObjectAclData->getAcl());
    }

    public function testGetMasks(): void
    {
        $adminObjectAclData = $this->createAdminObjectAclData();

        foreach ($adminObjectAclData->getMasks() as $key => $mask) {
            static::assertIsString($key);
            static::assertIsInt($mask);
        }
    }

    public function testSetForm(): AdminObjectAclData
    {
        $adminObjectAclData = $this->createAdminObjectAclData();
        $ret = $adminObjectAclData->setAclUsersForm($this->createMock(Form::class));

        static::assertSame($adminObjectAclData, $ret);

        return $adminObjectAclData;
    }

    #[Depends('testSetForm')]
    public function testGetForm(AdminObjectAclData $adminObjectAclData): void
    {
        static::assertInstanceOf(Form::class, $adminObjectAclData->getAclUsersForm());
    }

    public function testSetAclUsersForm(): AdminObjectAclData
    {
        $adminObjectAclData = $this->createAdminObjectAclData();
        $ret = $adminObjectAclData->setAclUsersForm($this->createMock(Form::class));

        static::assertSame($adminObjectAclData, $ret);

        return $adminObjectAclData;
    }

    #[Depends('testSetAclUsersForm')]
    public function testGetAclUsersForm(AdminObjectAclData $adminObjectAclData): void
    {
        static::assertInstanceOf(Form::class, $adminObjectAclData->getAclUsersForm());
    }

    public function testSetAclRolesForm(): AdminObjectAclData
    {
        $adminObjectAclData = $this->createAdminObjectAclData();
        $ret = $adminObjectAclData->setAclRolesForm($this->createMock(Form::class));

        static::assertSame($adminObjectAclData, $ret);

        return $adminObjectAclData;
    }

    #[Depends('testSetAclRolesForm')]
    public function testGetAclRolesForm(AdminObjectAclData $adminObjectAclData): void
    {
        static::assertInstanceOf(Form::class, $adminObjectAclData->getAclRolesForm());
    }

    public function testGetPermissions(): void
    {
        $adminObjectAclData = $this->createAdminObjectAclData();

        foreach ($adminObjectAclData->getPermissions() as $permission) {
            static::assertIsString($permission);
        }
    }

    public function testGetUserPermissions(): void
    {
        $adminObjectAclDataOwner = $this->createAdminObjectAclData();

        foreach ($adminObjectAclDataOwner->getUserPermissions() as $permission) {
            static::assertIsString($permission);
        }

        static::assertContains('OWNER', $adminObjectAclDataOwner->getUserPermissions());
        static::assertContains('MASTER', $adminObjectAclDataOwner->getUserPermissions());

        $adminObjectAclData = $this->createAdminObjectAclData(false);

        foreach ($adminObjectAclData->getUserPermissions() as $permission) {
            static::assertIsString($permission);
        }

        static::assertNotContains('OWNER', $adminObjectAclData->getUserPermissions());
        static::assertNotContains('MASTER', $adminObjectAclData->getUserPermissions());
    }

    public function testIsOwner(): void
    {
        $adminObjectAclDataOwner = $this->createAdminObjectAclData();
        static::assertTrue($adminObjectAclDataOwner->isOwner());

        $adminObjectAclData = $this->createAdminObjectAclData(false);
        static::assertFalse($adminObjectAclData->isOwner());
    }

    public function testGetSecurityHandler(): void
    {
        $adminObjectAclData = $this->createAdminObjectAclData();

        static::assertInstanceOf(AclSecurityHandlerInterface::class, $adminObjectAclData->getSecurityHandler());
    }

    public function testGetSecurityInformation(): void
    {
        $adminObjectAclData = $this->createAdminObjectAclData();

        static::assertSame([], $adminObjectAclData->getSecurityInformation());
    }

    public function testAdminAclIsNotEnabled(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->createAdminObjectAclData(true, false);
    }

    protected function createAdminObjectAclData(bool $isOwner = true, bool $isAclEnabled = true): AdminObjectAclData
    {
        return new AdminObjectAclData(
            $this->createAdmin($isOwner, $isAclEnabled),
            new \stdClass(),
            new \ArrayIterator(),
            MaskBuilder::class,
            new \ArrayIterator()
        );
    }

    /**
     * @return AdminInterface<object>
     */
    protected function createAdmin(bool $isOwner = true, bool $isAclEnabled = true): AdminInterface
    {
        $securityHandler = $this->createMock(AclSecurityHandlerInterface::class);

        $securityHandler
            ->method('getObjectPermissions')
            ->willReturn(['VIEW', 'EDIT', 'HISTORY', 'DELETE', 'UNDELETE', 'OPERATOR', 'MASTER', 'OWNER']);

        $securityHandler
            ->expects(static::any())->method('buildSecurityInformation')
            ->with(static::isInstanceOf(AdminInterface::class))
            ->willReturn([]);

        $admin = $this->createMock(AdminInterface::class);

        $admin
            ->method('isGranted')
            ->willReturn($isOwner);

        $admin
            ->method('getSecurityHandler')
            ->willReturn($securityHandler);

        $admin
            ->method('isAclEnabled')
            ->willReturn($isAclEnabled);

        return $admin;
    }
}
