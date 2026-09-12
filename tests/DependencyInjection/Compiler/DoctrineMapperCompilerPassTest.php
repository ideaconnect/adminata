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

use IDCT\Adminata\DependencyInjection\Compiler\DoctrineMapperCompilerPass;
use IDCT\Adminata\Doctrine\Mapper\Builder\OptionsBuilder;
use IDCT\Adminata\Doctrine\Mapper\DoctrineCollector;
use IDCT\Adminata\Doctrine\Mapper\ORM\DoctrineORMMapper;
use IDCT\Adminata\Tests\Doctrine\App\Entity\TestEntity;
use IDCT\Adminata\Tests\Doctrine\App\Entity\TestRelatedEntity;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
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

        $this->assertContainerBuilderNotHasService('adminata.doctrine.mapper');
    }

    public function testDefinitionsRemovedWithMapper(): void
    {
        $this->registerService('adminata.doctrine.mapper', 'foo');

        $this->compile();

        $this->assertContainerBuilderNotHasService('adminata.doctrine.mapper');
    }

    public function testDefinitionsRemovedWithDoctrine(): void
    {
        $this->registerService('doctrine', 'foo');

        $this->compile();

        $this->assertContainerBuilderNotHasService('adminata.doctrine.mapper');
    }

    public function testDefinitionsNotRemoved(): void
    {
        $this->registerService('adminata.doctrine.mapper', 'foo');
        $this->registerService('doctrine', 'foo');

        $this->compile();

        $this->assertContainerBuilderHasService('adminata.doctrine.mapper');
    }

    public function testAssociationMapping(): void
    {
        $definition = $this->registerService('adminata.doctrine.mapper', DoctrineORMMapper::class);
        $definition->setPublic(true);

        $this->registerService('doctrine', 'foo');

        $options = OptionsBuilder::createManyToOne('relation', TestRelatedEntity::class)
            ->add('joinColumns', [['referencedColumnName' => 'id']]);

        $collector = DoctrineCollector::getInstance();
        $collector->addAssociation(TestEntity::class, 'mapManyToOne', $options);

        $this->compile();

        $compiledMapper = $this->container->get('adminata.doctrine.mapper');

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'adminata.doctrine.mapper',
            'addAssociation',
            [TestEntity::class, 'mapManyToOne', [$options->getOptions()]]
        );

        static::assertInstanceOf(DoctrineORMMapper::class, $compiledMapper);

        $collector->clear();
    }
}
