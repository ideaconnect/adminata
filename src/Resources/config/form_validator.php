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

use IDCT\Adminata\Validator\InlineValidator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->set('adminata.form.validator.inline', InlineValidator::class)
            ->tag('validator.constraint_validator', [
                'alias' => 'adminata.form.validator.inline',
            ])
            ->args([
                service('service_container'),
            ]);
};
