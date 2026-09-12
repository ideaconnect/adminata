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
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class DoctrineAdapterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('adminata.doctrine.model.adapter.chain')) {
            return;
        }

        $definition = $container->findDefinition('adminata.doctrine.model.adapter.chain');

        if ($this->isDoctrineOrmLoaded($container)) {
            $definition->addMethodCall('addAdapter', [new Reference('adminata.doctrine.adapter.doctrine_orm')]);
        } else {
            $container->removeDefinition('adminata.doctrine.adapter.doctrine_orm');
        }
    }

    private function isDoctrineOrmLoaded(ContainerBuilder $container): bool
    {
        return $container->has('doctrine') && $container->has('adminata.doctrine.adapter.doctrine_orm');
    }
}
