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
use IDCT\Adminata\Security\Handler\AclSecurityHandlerInterface;
use IDCT\Adminata\Tests\Fixtures\Util\DummyDomainObject;
use IDCT\Adminata\Util\AdminObjectAclData;
use IDCT\Adminata\Util\AdminObjectAclManipulator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * @author Kévin Dunglas <kevin@les-tilleuls.coop>
 */
final class AdminObjectAclManipulatorTest extends TestCase
{
    /**
     * @var MockObject&FormFactoryInterface
     */
    private FormFactoryInterface $formFactory;

    private AdminObjectAclManipulator $adminObjectAclManipulator;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->adminObjectAclManipulator = new AdminObjectAclManipulator(
            $this->formFactory,
            MaskBuilder::class
        );
    }

    public function testGetMaskBuilder(): void
    {
        static::assertSame(
            MaskBuilder::class,
            $this->adminObjectAclManipulator->getMaskBuilderClass()
        );
    }

    public function testUpdateAclRoles(): void
    {
        $form = static::createStub(Form::class);
        // The interface, not Symfony\Component\Security\Acl\Domain\Acl: that concrete class
        // has an `addPropertyChangedListener()` signature incompatible with
        // doctrine/persistence 4's `NotifyPropertyChanged`, so loading it is a fatal error.
        $acl = $this->createMock(MutableAclInterface::class);
        $securityHandler = $this->createMock(AclSecurityHandlerInterface::class);

        $form->method('getData')->willReturn([
            ['acl_value' => 'MASTER'],
        ]);
        $acl->method('getObjectAces')->willReturn([]);
        $acl->method('isGranted')
            ->willReturnCallback(static fn (array $masks, array $securityIdentities, bool $administrativeMode = false): bool => $masks === [MaskBuilder::MASK_MASTER]);

        $acl
            ->expects(static::once())
            ->method('insertObjectAce')
            ->with(static::isInstanceOf(RoleSecurityIdentity::class), MaskBuilder::MASK_MASTER);

        $securityHandler->expects(static::once())->method('updateAcl')->with($acl);

        $securityHandler
            ->method('getObjectPermissions')
            ->willReturn(['MASTER', 'OWNER']);

        $admin = static::createStub(AdminInterface::class);
        $admin
            ->method('isAclEnabled')
            ->willReturn(true);

        $admin
            ->method('getSecurityHandler')
            ->willReturn($securityHandler);

        $aclData = new AdminObjectAclData(
            $admin,
            new DummyDomainObject(),
            new \ArrayIterator(['ACL_USER']),
            MaskBuilder::class
        );

        $aclData->setAclRolesForm($form);
        $aclData->setAcl($acl);

        $this->adminObjectAclManipulator->updateAclRoles($aclData);
    }

    public function testCreateAclUsersForm(): void
    {
        $form = static::createStub(Form::class);
        $formBuilder = static::createStub(FormBuilder::class);
        $securityHandler = $this->createMock(AclSecurityHandlerInterface::class);
        $acl = static::createStub(MutableAclInterface::class);

        $securityHandler
            ->method('getObjectPermissions')
            ->willReturn(['MASTER', 'OWNER']);

        $admin = static::createStub(AdminInterface::class);
        $admin
            ->method('isAclEnabled')
            ->willReturn(true);

        $admin
            ->method('getSecurityHandler')
            ->willReturn($securityHandler);

        $aclData = new AdminObjectAclData(
            $admin,
            new DummyDomainObject(),
            new \ArrayIterator(['ACL_USER']),
            MaskBuilder::class
        );

        $aclData->setAclRolesForm($form);
        $aclData->setAcl($acl);

        $this->formFactory->expects(static::any())->method('createNamedBuilder')->with(
            AdminObjectAclManipulator::ACL_USERS_FORM_NAME,
            FormType::class
        )->willReturn($formBuilder);
        $formBuilder->method('getForm')->willReturn($form);
        $securityHandler->expects(static::any())->method('getObjectAcl')->with(static::isInstanceOf(ObjectIdentityInterface::class))->willReturn($acl);

        $resultForm = $this->adminObjectAclManipulator->createAclUsersForm($aclData);

        static::assertSame($form, $resultForm);
    }

    public function testCreateAclRolesForm(): void
    {
        $form = static::createStub(Form::class);
        $formBuilder = static::createStub(FormBuilder::class);
        $securityHandler = static::createStub(AclSecurityHandlerInterface::class);
        $acl = static::createStub(MutableAclInterface::class);

        $securityHandler
            ->method('getObjectPermissions')
            ->willReturn(['MASTER', 'OWNER']);

        $admin = static::createStub(AdminInterface::class);
        $admin
            ->method('isAclEnabled')
            ->willReturn(true);

        $admin
            ->method('getSecurityHandler')
            ->willReturn($securityHandler);

        $aclData = new AdminObjectAclData(
            $admin,
            new DummyDomainObject(),
            new \ArrayIterator(['ACL_USER']),
            MaskBuilder::class
        );

        $aclData->setAclRolesForm($form);
        $aclData->setAcl($acl);
        $this->formFactory->expects(static::any())->method('createNamedBuilder')->with(
            AdminObjectAclManipulator::ACL_ROLES_FORM_NAME,
            FormType::class
        )->willReturn($formBuilder);
        $formBuilder->method('getForm')->willReturn($form);
        $securityHandler->method('getObjectAcl')->willReturn($acl);

        $resultForm = $this->adminObjectAclManipulator->createAclRolesForm($aclData);

        static::assertSame($form, $resultForm);
    }
}
