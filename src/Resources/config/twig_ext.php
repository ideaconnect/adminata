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

use IDCT\Adminata\Twig\Extension\FormTypeExtension;
use IDCT\Adminata\Twig\Extension\StatusExtension;
use IDCT\Adminata\Twig\Extension\TemplateExtension;
use IDCT\Adminata\Twig\StatusRuntime;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('adminata.twig.extension.wrapping', FormTypeExtension::class)
            ->tag('twig.extension')
            ->args([param('adminata.twig.form_type')])

        ->set('adminata.twig.status_runtime', StatusRuntime::class)
            ->tag('twig.runtime')

        ->set('adminata.twig.status_extension', StatusExtension::class)
            ->tag('twig.extension')

        ->set('adminata.twig.template_extension', TemplateExtension::class)
            ->tag('twig.extension')
            ->args([param('kernel.debug')]);
};
