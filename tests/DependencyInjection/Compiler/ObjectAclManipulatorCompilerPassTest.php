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

namespace IDCT\Adminata\Tests\DependencyInjection\Compiler;

use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Command\GenerateObjectAclCommand;
use IDCT\Adminata\DependencyInjection\Compiler\ObjectAclManipulatorCompilerPass;
use IDCT\Adminata\Util\ObjectAclManipulator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Olivier Rey <olivier.rey@gmail.com>
 */
final class ObjectAclManipulatorCompilerPassTest extends TestCase
{
    #[DataProvider('provideAvailableManagerCases')]
    public function testAvailableManager(ContainerBuilder $containerBuilder, string $serviceId): void
    {
        $objectAclManipulatorCompilerPass = new ObjectAclManipulatorCompilerPass();

        $objectAclManipulatorCompilerPass->process($containerBuilder);

        $availableManagers = $containerBuilder->getDefinition('adminata.admin.command.generate_object_acl')->getArgument(1);

        static::assertIsArray($availableManagers);
        static::assertArrayHasKey($serviceId, $availableManagers);
    }

    /**
     * @phpstan-return iterable<array-key, array{ContainerBuilder, string}>
     */
    public static function provideAvailableManagerCases(): iterable
    {
        $serviceId = 'adminata.admin.manipulator.acl.object.orm';
        $container = static::createContainer();
        $container
            ->register($serviceId)
            ->setClass(ObjectAclManipulator::class);

        yield [$container, $serviceId];

        $parameterName = 'adminata.admin.manipulator.acl.object.orm.class';
        $container = static::createContainer();
        $container->setParameter($parameterName, ObjectAclManipulator::class);

        $container
            ->register($serviceId)
            ->setClass('%'.$parameterName.'%');

        yield [$container, $serviceId];
    }

    private static function createContainer(): ContainerBuilder
    {
        $pool = new Pool(new Container());
        $container = new ContainerBuilder();
        $container
            ->register('adminata.admin.command.generate_object_acl')
            ->setClass(GenerateObjectAclCommand::class)
            ->setArguments([$pool, []]);

        return $container;
    }
}
