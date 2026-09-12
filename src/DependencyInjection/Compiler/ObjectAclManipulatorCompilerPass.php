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

use IDCT\Adminata\Util\ObjectAclManipulatorInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This class injects available object ACL manipulators to services which depend on them.
 *
 * @internal
 *
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
final class ObjectAclManipulatorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('adminata.admin.command.generate_object_acl')) {
            return;
        }

        $availableManagers = [];

        foreach ($container->getServiceIds() as $id) {
            if (!str_starts_with($id, 'adminata.admin.manipulator.acl.object.') || null === $class = $container->getDefinition($id)->getClass()) {
                continue;
            }

            // We trim the possible "%" characters around the class definition since it could be using "%parameter%" syntax.
            $class = trim($class, '%');

            if (!class_exists($class, false) && $container->hasParameter($class)) {
                $class = $container->getParameter($class);
                \assert(\is_string($class));
            }

            if (!is_subclass_of($class, ObjectAclManipulatorInterface::class)) {
                continue;
            }

            $availableManagers[$id] = $container->getDefinition($id);
        }

        $generateAdminCommandDefinition = $container->getDefinition('adminata.admin.command.generate_object_acl');
        $generateAdminCommandDefinition->replaceArgument(1, $availableManagers);
    }
}
