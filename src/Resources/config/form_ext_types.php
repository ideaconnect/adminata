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

use IDCT\Adminata\Form\Type\BooleanType;
use IDCT\Adminata\Form\Type\CollectionType;
use IDCT\Adminata\Form\Type\DatePickerType;
use IDCT\Adminata\Form\Type\DateRangePickerType;
use IDCT\Adminata\Form\Type\DateRangeType;
use IDCT\Adminata\Form\Type\DateTimePickerType;
use IDCT\Adminata\Form\Type\DateTimeRangePickerType;
use IDCT\Adminata\Form\Type\DateTimeRangeType;
use IDCT\Adminata\Form\Type\ImmutableArrayType;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('adminata.form.type.array', ImmutableArrayType::class)
            ->tag('form.type', ['alias' => 'adminata_type_immutable_array'])

        ->set('adminata.form.type.boolean', BooleanType::class)
            ->tag('form.type', ['alias' => 'adminata_type_boolean'])

        ->set('adminata.form.type.collection', CollectionType::class)
            ->tag('form.type', ['alias' => 'adminata_type_collection'])

        ->set('adminata.form.type.date_range', DateRangeType::class)
            ->tag('form.type', ['alias' => 'adminata_type_date_range'])

        ->set('adminata.form.type.datetime_range', DateTimeRangeType::class)
            ->tag('form.type', ['alias' => 'adminata_type_datetime_range'])

        ->set('adminata.form.type.date_picker', DatePickerType::class)
            ->tag('kernel.locale_aware')
            ->tag('form.type', ['alias' => 'adminata_type_date_picker'])
            ->args([
                param('kernel.default_locale'),
            ])

        ->set('adminata.form.type.datetime_picker', DateTimePickerType::class)
            ->tag('kernel.locale_aware')
            ->tag('form.type', ['alias' => 'adminata_type_datetime_picker'])
            ->args([
                param('kernel.default_locale'),
            ])

        ->set('adminata.form.type.date_range_picker', DateRangePickerType::class)
            ->tag('form.type', ['alias' => 'adminata_type_date_range_picker'])

        ->set('adminata.form.type.datetime_range_picker', DateTimeRangePickerType::class)
            ->tag('form.type', ['alias' => 'adminata_type_datetime_range_picker']);
};
