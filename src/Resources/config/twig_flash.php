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

use IDCT\Adminata\FlashMessage\FlashManager;
use IDCT\Adminata\Twig\Extension\FlashMessageExtension;
use IDCT\Adminata\Twig\FlashMessageRuntime;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()

        ->set('adminata.twig.flashmessage.manager.class', FlashManager::class)

        ->set('adminata.twig.extension.flashmessage.class', FlashMessageExtension::class);

    $containerConfigurator->services()

        ->set('adminata.twig.flashmessage.manager', '%adminata.twig.flashmessage.manager.class%')
            ->public()
            ->tag('adminata.status.renderer')
            ->args([
                service('request_stack'),
                abstract_arg('Sonata flash message types array (defined in configuration)'),
                abstract_arg('Css classes associated with the types'),
            ])

        ->set('adminata.twig.flashmessage.twig.runtime', FlashMessageRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('adminata.twig.flashmessage.manager'),
            ])

        ->set('adminata.twig.flashmessage.twig.extension', '%adminata.twig.extension.flashmessage.class%')
            ->public()
            ->tag('twig.extension');
};
