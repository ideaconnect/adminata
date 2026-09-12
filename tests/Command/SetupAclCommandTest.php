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

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Command\SetupAclCommand;
use IDCT\Adminata\Util\AdminAclManipulatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

/**
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
final class SetupAclCommandTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();

        $this->container->set('acme.admin.foo', $this->createMock(AdminInterface::class));
    }

    public function testExecute(): void
    {
        $pool = new Pool($this->container, ['acme.admin.foo']);

        $command = new SetupAclCommand($pool, $this->createMock(AdminAclManipulatorInterface::class));

        $application = new Application();
        $application->addCommand($command);

        $command = $application->find('adminata:setup-acl');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        static::assertMatchesRegularExpression('/Starting ACL AdminBundle configuration/', $commandTester->getDisplay());
    }

    public function testExecuteWithException1(): void
    {
        $this->container->set('acme.admin.foo', null);
        $pool = new Pool($this->container, ['acme.admin.foo']);

        $command = new SetupAclCommand($pool, $this->createMock(AdminAclManipulatorInterface::class));

        $application = new Application();
        $application->addCommand($command);

        $command = $application->find('adminata:setup-acl');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        static::assertMatchesRegularExpression(
            '@Starting ACL AdminBundle configuration\s+Warning : The admin class cannot be initiated from the command line\s+You have requested a non-existent service "acme.admin.foo".@',
            $commandTester->getDisplay()
        );
    }
}
