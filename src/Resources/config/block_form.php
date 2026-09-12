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

use IDCT\Adminata\Form\Type\ContainerTemplateType;
use IDCT\Adminata\Form\Type\ServiceListType;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('adminata.block.form.type.block', ServiceListType::class)
        ->tag('form.type', ['alias' => 'adminata_block_service_choice'])
        ->args([
            service('adminata.block.manager'),
        ]);

    $services->set('adminata.block.form.type.container_template', ContainerTemplateType::class)
        ->tag('form.type', ['alias' => 'adminata_type_container_template_choice'])
        ->args([
            abstract_arg('template choices array'),
        ]);
};
