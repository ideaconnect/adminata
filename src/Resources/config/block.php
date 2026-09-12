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

use IDCT\Adminata\Block\AdminListBlockService;
use IDCT\Adminata\Block\AdminPreviewBlockService;
use IDCT\Adminata\Block\AdminSearchBlockService;
use IDCT\Adminata\Block\AdminStatsBlockService;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('adminata.admin.block.admin_list', AdminListBlockService::class)
            ->public()
            ->tag('adminata.block')
            ->args([
                service('twig'),
                service('adminata.admin.pool'),
                service('adminata.admin.global_template_registry'),
            ])

        ->set('adminata.admin.block.search_result', AdminSearchBlockService::class)
            ->public()
            ->tag('adminata.block')
            ->args([
                service('twig'),
                service('adminata.admin.pool'),
                service('adminata.admin.search.handler'),
                service('adminata.admin.global_template_registry'),
                param('adminata.admin.configuration.global_search.empty_boxes'),
                param('adminata.admin.configuration.global_search.admin_route'),
            ])

        ->set('adminata.admin.block.stats', AdminStatsBlockService::class)
            ->public()
            ->tag('adminata.block')
            ->args([
                service('twig'),
                service('adminata.admin.pool'),
            ])

        ->set('adminata.admin.block.admin_preview', AdminPreviewBlockService::class)
            ->public()
            ->tag('adminata.block')
            ->args([
                service('twig'),
                service('adminata.admin.pool'),
            ]);
};
