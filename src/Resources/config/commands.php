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

use IDCT\Adminata\Command\ExplainAdminCommand;
use IDCT\Adminata\Command\ListAdminCommand;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('adminata.admin.command.explain', ExplainAdminCommand::class)
            ->tag('console.command')
            ->args([
                service('adminata.admin.pool'),
            ])

        ->set('adminata.admin.command.list', ListAdminCommand::class)
            ->tag('console.command')
            ->args([
                service('adminata.admin.pool'),
            ]);
};
