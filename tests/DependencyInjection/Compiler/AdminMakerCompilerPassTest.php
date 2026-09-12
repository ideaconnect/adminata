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

use IDCT\Adminata\Controller\CRUDController;
use IDCT\Adminata\DependencyInjection\Compiler\AdminMakerCompilerPass;
use IDCT\Adminata\Maker\AdminMaker;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class AdminMakerCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testDoesNothingWithoutAdminMaker(): void
    {
        $this->compile();

        self::assertContainerBuilderNotHasService('adminata.admin.maker');
    }

    public function testDoesNothingWithoutDefaultControllerParameter(): void
    {
        $definition = new Definition(AdminMaker::class);
        $definition->setArguments([
            'dir',
            [],
            CRUDController::class,
        ]);
        $this->container->setDefinition('adminata.admin.maker', $definition);

        $this->compile();

        self::assertContainerBuilderHasServiceDefinitionWithArgument(
            'adminata.admin.maker',
            2,
            CRUDController::class
        );
    }

    public function testDoesNothingWithoutDefaultControllerNotBeingAService(): void
    {
        $definition = new Definition(AdminMaker::class);
        $definition->setArguments([
            'dir',
            [],
            CRUDController::class,
        ]);
        $this->container->setDefinition('adminata.admin.maker', $definition);

        $this->container->setParameter('adminata.admin.configuration.default_controller', CRUDController::class);

        $this->compile();

        self::assertContainerBuilderHasServiceDefinitionWithArgument(
            'adminata.admin.maker',
            2,
            CRUDController::class
        );
    }

    public function testReplacesTheServiceArgumentWithClassName(): void
    {
        $definition = new Definition(AdminMaker::class);
        $definition->setArguments([
            'dir',
            [],
            'adminata.admin.controller.crud',
        ]);
        $this->container->setDefinition('adminata.admin.maker', $definition);

        $definition = new Definition(CRUDController::class);
        $this->container->setDefinition('adminata.admin.controller.crud', $definition);

        $this->container->setParameter('adminata.admin.configuration.default_controller', 'adminata.admin.controller.crud');

        $this->compile();

        self::assertContainerBuilderHasServiceDefinitionWithArgument(
            'adminata.admin.maker',
            2,
            CRUDController::class
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AdminMakerCompilerPass());
    }
}
