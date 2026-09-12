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

namespace IDCT\Adminata\Tests;

use PHPUnit\Framework\TestCase;
use IDCT\Adminata\DependencyInjection\AdminataBlockExtension;
use IDCT\Adminata\DependencyInjection\AdminataExporterExtension;
use IDCT\Adminata\DependencyInjection\AdminataFormExtension;
use IDCT\Adminata\DependencyInjection\AdminataTwigExtension;
use IDCT\Adminata\AdminataBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
final class AdminataBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        // Eleven admin passes, the block stack's two, the Twig namespace aliases, the
        // exporter's writer pass, and the Doctrine stack's two.
        $containerBuilder->expects(static::exactly(17))
            ->method('addCompilerPass');

        $bundle = new AdminataBundle();
        $bundle->build($containerBuilder);
    }

    /**
     * The block, form, twig and exporter stacks have no bundle of their own, so this bundle
     * registers their four extensions — otherwise an application's adminata_block.yaml,
     * adminata_form.yaml, adminata_twig.yaml or adminata_exporter.yaml fails to load and none of the
     * adminata.block.*, adminata.form.*, adminata.twig.* or adminata.exporter.* services exist.
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

        new AdminataBundle()->build($containerBuilder);

        static::assertSame([
            AdminataBlockExtension::class,
            AdminataFormExtension::class,
            AdminataTwigExtension::class,
            AdminataExporterExtension::class,
        ], $registered);
    }
}
