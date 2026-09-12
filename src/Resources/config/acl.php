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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use IDCT\Adminata\Command\GenerateObjectAclCommand;
use IDCT\Adminata\Command\SetupAclCommand;
use IDCT\Adminata\Security\Acl\Permission\MaskBuilder;
use IDCT\Adminata\Security\Handler\AclSecurityHandler;
use IDCT\Adminata\Util\AdminAclManipulator;
use IDCT\Adminata\Util\AdminObjectAclManipulator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()
        ->set('adminata.admin.security.handler.acl.class', AclSecurityHandler::class)

        ->set('adminata.admin.security.mask.builder.class', MaskBuilder::class)

        ->set('adminata.admin.manipulator.acl.admin.class', AdminAclManipulator::class)

        ->set('adminata.admin.object.manipulator.acl.admin.class', AdminObjectAclManipulator::class);

    $containerConfigurator->services()
        ->set('adminata.admin.command.generate_object_acl', GenerateObjectAclCommand::class)
            ->tag('console.command')
            ->args([
                service('adminata.admin.pool'),
                abstract_arg('acl object manipulators'),
            ])

        ->set('adminata.admin.command.setup_acl', SetupAclCommand::class)
            ->tag('console.command')
            ->args([
                service('adminata.admin.pool'),
                service('adminata.admin.manipulator.acl.admin'),
            ])

        ->set('adminata.admin.security.handler.acl', (string) param('adminata.admin.security.handler.acl.class'))
            ->args([
                service('security.token_storage'),
                service('security.authorization_checker'),
                service('security.acl.provider')->nullOnInvalid(),
                param('adminata.admin.security.mask.builder.class'),
                param('adminata.admin.configuration.security.role_super_admin'),
            ])
            ->call('setAdminPermissions', [param('adminata.admin.configuration.security.admin_permissions')])
            ->call('setObjectPermissions', [param('adminata.admin.configuration.security.object_permissions')])

        ->set('adminata.admin.manipulator.acl.admin', (string) param('adminata.admin.manipulator.acl.admin.class'))
            ->args([
                param('adminata.admin.security.mask.builder.class'),
            ])

        ->set('adminata.admin.object.manipulator.acl.admin', (string) param('adminata.admin.object.manipulator.acl.admin.class'))
            ->args([
                service('form.factory'),
                param('adminata.admin.security.mask.builder.class'),
            ])

        ->alias(AdminObjectAclManipulator::class, 'adminata.admin.object.manipulator.acl.admin');
};
