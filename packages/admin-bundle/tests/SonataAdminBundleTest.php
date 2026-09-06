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

namespace Sonata\AdminBundle\Tests;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\DependencyInjection\SonataBlockExtension;
use Sonata\AdminBundle\DependencyInjection\SonataExporterExtension;
use Sonata\AdminBundle\DependencyInjection\SonataFormExtension;
use Sonata\AdminBundle\DependencyInjection\SonataTwigExtension;
use Sonata\AdminBundle\SonataAdminBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
final class SonataAdminBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        // Eleven admin passes, the block stack's two, the Twig namespace aliases, and the
        // exporter's writer pass.
        $containerBuilder->expects(static::exactly(15))
            ->method('addCompilerPass');

        $bundle = new SonataAdminBundle();
        $bundle->build($containerBuilder);
    }

    /**
     * The block, form, twig and exporter stacks have no bundle of their own, so this bundle
     * registers their four extensions — otherwise an application's sonata_block.yaml,
     * sonata_form.yaml, sonata_twig.yaml or sonata_exporter.yaml fails to load and none of the
     * sonata.block.*, sonata.form.*, sonata.twig.* or sonata.exporter.* services exist.
     */
    public function testBuildRegistersTheBlockFormTwigAndExporterExtensions(): void
    {
        $registered = [];

        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder->expects(static::exactly(4))
            ->method('registerExtension')
            ->willReturnCallback(
                static function (ExtensionInterface $extension) use (&$registered): void {
                    $registered[] = $extension::class;
                }
            );

        new SonataAdminBundle()->build($containerBuilder);

        static::assertSame([
            SonataBlockExtension::class,
            SonataFormExtension::class,
            SonataTwigExtension::class,
            SonataExporterExtension::class,
        ], $registered);
    }
}
