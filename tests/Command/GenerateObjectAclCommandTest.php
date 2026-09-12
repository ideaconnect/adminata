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

namespace IDCT\Adminata\Tests\Command;

use IDCT\Adminata\Admin\AbstractAdmin;
use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Command\GenerateObjectAclCommand;
use IDCT\Adminata\Tests\Fixtures\Entity\Foo;
use IDCT\Adminata\Util\ObjectAclManipulatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

/**
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
final class GenerateObjectAclCommandTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
    }

    public function testExecuteWithDeprecatedDoctrineService(): void
    {
        $pool = new Pool($this->container);

        $command = new GenerateObjectAclCommand($pool, []);

        $application = new Application();
        $application->addCommand($command);

        $command = $application->find('adminata:generate-object-acl');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        static::assertMatchesRegularExpression('/No manipulators are implemented : ignoring/', $commandTester->getDisplay());
    }

    public function testExecuteWithEmptyManipulators(): void
    {
        $pool = new Pool($this->container);

        $command = new GenerateObjectAclCommand($pool, []);

        $application = new Application();
        $application->addCommand($command);

        $command = $application->find('adminata:generate-object-acl');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        static::assertMatchesRegularExpression('/No manipulators are implemented : ignoring/', $commandTester->getDisplay());
    }

    public function testExecuteWithManipulatorNotFound(): void
    {
        $admin = static::createStub(AbstractAdmin::class);
        $container = new Container();
        $container->set('acme.admin.foo', $admin);
        $pool = new Pool($container, ['acme.admin.foo']);

        $admin->setManagerType('bar');

        $aclObjectManipulators = [
            'bar' => $this->createMock(ObjectAclManipulatorInterface::class),
        ];

        $command = new GenerateObjectAclCommand($pool, $aclObjectManipulators);

        $application = new Application();
        $application->addCommand($command);

        $command = $application->find('adminata:generate-object-acl');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        static::assertMatchesRegularExpression('/Admin class is using a manager type that has no manipulator implemented : ignoring/', $commandTester->getDisplay());
    }

    public function testExecuteWithManipulator(): void
    {
        $admin = static::createStub(AbstractAdmin::class);
        $container = new Container();
        $container->set('acme.admin.foo', $admin);
        $pool = new Pool($container, ['acme.admin.foo']);

        $admin->setManagerType('bar');

        $manipulator = $this->createMock(ObjectAclManipulatorInterface::class);
        $manipulator->expects(static::once())->method('batchConfigureAcls')
            ->with(static::isInstanceOf(StreamOutput::class), $admin, null);

        $aclObjectManipulators = [
            'adminata.admin.manipulator.acl.object.bar' => $manipulator,
        ];

        $command = new GenerateObjectAclCommand($pool, $aclObjectManipulators);

        $application = new Application();
        $application->addCommand($command);

        $command = $application->find('adminata:generate-object-acl');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
    }

    public function testExecuteWithUserModel(): void
    {
        $admin = static::createStub(AbstractAdmin::class);
        $container = new Container();
        $container->set('acme.admin.foo', $admin);
        $pool = new Pool($container, ['acme.admin.foo']);

        $admin->setManagerType('bar');

        $manipulator = $this->createMock(ObjectAclManipulatorInterface::class);
        $manipulator
            ->expects(static::once())
            ->method('batchConfigureAcls')
            ->with(
                static::isInstanceOf(StreamOutput::class),
                $admin,
                static::callback(static fn (UserSecurityIdentity $userSecurityIdentity): bool => Foo::class === $userSecurityIdentity->getClass())
            );

        $aclObjectManipulators = [
            'adminata.admin.manipulator.acl.object.bar' => $manipulator,
        ];

        $command = new GenerateObjectAclCommand($pool, $aclObjectManipulators);

        $application = new Application();
        $application->addCommand($command);

        $command = $application->find('adminata:generate-object-acl');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--user_model' => Foo::class,
            '--object_owner' => true,
        ]);
    }
}
