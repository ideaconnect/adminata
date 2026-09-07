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

namespace Sonata\AdminBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sonata\AdminBundle\DependencyInjection\Compiler\DoctrineMapperCompilerPass;
use Sonata\AdminBundle\Doctrine\Mapper\Builder\OptionsBuilder;
use Sonata\AdminBundle\Doctrine\Mapper\DoctrineCollector;
use Sonata\AdminBundle\Doctrine\Mapper\ORM\DoctrineORMMapper;
use Sonata\AdminBundle\Tests\Doctrine\App\Entity\TestEntity;
use Sonata\AdminBundle\Tests\Doctrine\App\Entity\TestRelatedEntity;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Ahmet Akbana <ahmetakbana@gmail.com>
 */
final class DoctrineMapperCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DoctrineMapperCompilerPass());
    }

    public function testDefinitionsRemoved(): void
    {
        $this->compile();

        $this->assertContainerBuilderNotHasService('sonata.doctrine.mapper');
    }

    public function testDefinitionsRemovedWithMapper(): void
    {
        $this->registerService('sonata.doctrine.mapper', 'foo');

        $this->compile();

        $this->assertContainerBuilderNotHasService('sonata.doctrine.mapper');
    }

    public function testDefinitionsRemovedWithDoctrine(): void
    {
        $this->registerService('doctrine', 'foo');

        $this->compile();

        $this->assertContainerBuilderNotHasService('sonata.doctrine.mapper');
    }

    public function testDefinitionsNotRemoved(): void
    {
        $this->registerService('sonata.doctrine.mapper', 'foo');
        $this->registerService('doctrine', 'foo');

        $this->compile();

        $this->assertContainerBuilderHasService('sonata.doctrine.mapper');
    }

    public function testAssociationMapping(): void
    {
        $definition = $this->registerService('sonata.doctrine.mapper', DoctrineORMMapper::class);
        $definition->setPublic(true);

        $this->registerService('doctrine', 'foo');

        $options = OptionsBuilder::createManyToOne('relation', TestRelatedEntity::class)
            ->add('joinColumns', [['referencedColumnName' => 'id']]);

        $collector = DoctrineCollector::getInstance();
        $collector->addAssociation(TestEntity::class, 'mapManyToOne', $options);

        $this->compile();

        $compiledMapper = $this->container->get('sonata.doctrine.mapper');

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sonata.doctrine.mapper',
            'addAssociation',
            [TestEntity::class, 'mapManyToOne', [$options->getOptions()]]
        );

        static::assertInstanceOf(DoctrineORMMapper::class, $compiledMapper);

        $collector->clear();
    }
}
