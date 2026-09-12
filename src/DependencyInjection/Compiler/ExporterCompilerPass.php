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

namespace IDCT\Adminata\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Grégoire Paris <postmaster@greg0ire.fr>
 */
final class ExporterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('adminata.exporter.exporter')) {
            return;
        }

        $definition = $container->findDefinition('adminata.exporter.exporter');
        $writers = $container->findTaggedServiceIds('adminata.exporter.writer');

        foreach (array_keys($writers) as $id) {
            $definition->addMethodCall('addWriter', [new Reference($id)]);
        }
    }
}
