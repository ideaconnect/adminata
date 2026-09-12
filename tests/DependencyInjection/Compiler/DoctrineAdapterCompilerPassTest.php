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

use IDCT\Adminata\DependencyInjection\Compiler\DoctrineAdapterCompilerPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Ahmet Akbana <ahmetakbana@gmail.com>
 */
final class DoctrineAdapterCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DoctrineAdapterCompilerPass());
    }

    public function testDefinitionsAdded(): void
    {
        $adapterChain = new Definition();
        $this->setDefinition('adminata.doctrine.model.adapter.chain', $adapterChain);

        $this->registerService('doctrine', 'foo');
        $this->registerService('adminata.doctrine.adapter.doctrine_orm', 'foo');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'adminata.doctrine.model.adapter.chain',
            'addAdapter',
            [new Reference('adminata.doctrine.adapter.doctrine_orm')]
        );
    }

    public function testDefinitionsAddedWithoutOrm(): void
    {
        $adapterChain = new Definition();
        $this->setDefinition('adminata.doctrine.model.adapter.chain', $adapterChain);

        $this->registerService('doctrine', 'foo');

        $this->compile();

        $this->assertContainerBuilderNotHasService('adminata.doctrine.adapter.doctrine_orm');
    }

    public function testDefinitionsRemoved(): void
    {
        $adapterChain = new Definition();
        $this->setDefinition('adminata.doctrine.model.adapter.chain', $adapterChain);

        $this->registerService('adminata.doctrine.adapter.doctrine_orm', 'foo');

        $this->compile();

        $this->assertContainerBuilderNotHasService('adminata.doctrine.adapter.doctrine_orm');
    }
}
