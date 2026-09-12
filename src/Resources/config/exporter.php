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

use IDCT\Adminata\Bridge\Exporter\AdminExporter;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('adminata.admin.admin_exporter', AdminExporter::class)
            ->args([
                service('adminata.exporter.exporter'),
            ])

        ->alias(AdminExporter::class, 'adminata.admin.admin_exporter');
};
