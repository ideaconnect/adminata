<?php

declare(strict_types=1);

/*
 * This file is part of the adminata package.
 *
 * (c) IDCT Bartosz Pachołek <bartosz@idct.tech>
 *
 * Forked from the Sonata Project
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adminata\Tests\Contract;

use IDCT\Adminata\AdminataBundle;
use IDCT\Adminata\DependencyInjection\AdminataBlockExtension;
use IDCT\Adminata\DependencyInjection\AdminataExporterExtension;
use IDCT\Adminata\DependencyInjection\AdminataExtension;
use IDCT\Adminata\DependencyInjection\AdminataFormExtension;
use IDCT\Adminata\DependencyInjection\AdminataTwigExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * The identity the rename settled (PLAN/v2 N2–N5, N14, N19): one namespace, one bundle class
 * whose name derives everything else, five configuration roots, and a `src/` that leaves the
 * storage layers' namespaces to the storage layers.
 */
final class NamespaceContractTest extends ContractTestCase
{
    public function testTheBundleIsNamedAfterTheProject(): void
    {
        $bundle = new AdminataBundle();

        static::assertSame('AdminataBundle', $bundle->getName());
        static::assertSame('IDCT\Adminata', $bundle->getNamespace());
        static::assertSame(self::root().'/src', $bundle->getPath());
    }

    /**
     * Symfony derives a bundle's Twig namespace, its override directory and its published asset
     * directory from the bundle name: `@Adminata`, `templates/bundles/AdminataBundle/` and
     * `public/bundles/adminata/`. The last is the one an application's `assets.*` entries name.
     */
    public function testTheAssetDirectoryFollowsTheBundleName(): void
    {
        static::assertSame('adminata', strtolower(preg_replace('/bundle$/i', '', new AdminataBundle()->getName()) ?? ''));
    }

    /**
     * @return iterable<string, array{class-string<ExtensionInterface&ConfigurationExtensionInterface>, string}>
     */
    public static function provideTheConfigurationRootsAreDerivedFromTheExtensionNamesCases(): iterable
    {
        yield 'adminata' => [AdminataExtension::class, 'adminata'];
        yield 'adminata_block' => [AdminataBlockExtension::class, 'adminata_block'];
        yield 'adminata_exporter' => [AdminataExporterExtension::class, 'adminata_exporter'];
        yield 'adminata_form' => [AdminataFormExtension::class, 'adminata_form'];
        yield 'adminata_twig' => [AdminataTwigExtension::class, 'adminata_twig'];
    }

    /**
     * @param class-string<ExtensionInterface&ConfigurationExtensionInterface> $extension
     */
    #[DataProvider('provideTheConfigurationRootsAreDerivedFromTheExtensionNamesCases')]
    public function testTheConfigurationRootsAreDerivedFromTheExtensionNames(string $extension, string $root): void
    {
        $instance = new $extension();

        static::assertSame($root, $instance->getAlias());

        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);
        $configuration = $instance->getConfiguration([], $container);
        static::assertNotNull($configuration);
        static::assertSame($root, $configuration->getConfigTreeBuilder()->buildTree()->getName());
    }

    public function testTheBundleRegistersItsFourExtraExtensions(): void
    {
        $container = new ContainerBuilder();
        new AdminataBundle()->build($container);

        foreach (['adminata_block', 'adminata_form', 'adminata_twig', 'adminata_exporter'] as $alias) {
            static::assertTrue($container->hasExtension($alias), $alias);
        }
    }

    /**
     * The storage layers nest under `IDCT\Adminata\` from packages of their own
     * (`IDCT\Adminata\DoctrineORM\`, `IDCT\Adminata\DoctrineMongoDB\`), the way Flysystem's
     * adapters sit beside its core. This bundle must never grow those directories, or two packages
     * would map the same namespace.
     */
    public function testSrcLeavesTheStorageLayersTheirNamespaces(): void
    {
        foreach (['DoctrineORM', 'DoctrineMongoDB'] as $reserved) {
            static::assertDirectoryDoesNotExist(self::root().'/src/'.$reserved);
        }
    }

    /**
     * The XML configuration namespaces the two extensions with an XSD-less schema announce.
     */
    public function testTheXmlNamespacesAreOurs(): void
    {
        static::assertSame('https://idct.tech/schema/dic/adminata', new AdminataExtension()->getNamespace());
        static::assertSame('https://idct.tech/schema/dic/adminata_block', new AdminataBlockExtension()->getNamespace());
    }
}
