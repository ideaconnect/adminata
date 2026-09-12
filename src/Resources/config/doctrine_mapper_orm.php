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

use IDCT\Adminata\Doctrine\Mapper\ORM\DoctrineORMMapper;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('adminata.doctrine.mapper', DoctrineORMMapper::class)
            ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata', 'priority' => 10]);
};
