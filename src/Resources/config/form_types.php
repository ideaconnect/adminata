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

use IDCT\Adminata\Form\DataTransformer\BooleanToStringTransformer;
use IDCT\Adminata\Form\DataTransformerResolver;
use IDCT\Adminata\Form\Extension\ChoiceTypeExtension;
use IDCT\Adminata\Form\Extension\Field\Type\FormTypeFieldExtension;
use IDCT\Adminata\Form\Extension\Field\Type\MopaCompatibilityTypeFieldExtension;
use IDCT\Adminata\Form\Type\AdminType;
use IDCT\Adminata\Form\Type\ChoiceFieldMaskType;
use IDCT\Adminata\Form\Type\Filter\ChoiceType;
use IDCT\Adminata\Form\Type\Filter\DateRangeType;
use IDCT\Adminata\Form\Type\Filter\DateTimeRangeType;
use IDCT\Adminata\Form\Type\Filter\DateTimeType;
use IDCT\Adminata\Form\Type\Filter\DateType;
use IDCT\Adminata\Form\Type\Filter\DefaultType;
use IDCT\Adminata\Form\Type\Filter\NumberType;
use IDCT\Adminata\Form\Type\ModelAutocompleteType;
use IDCT\Adminata\Form\Type\ModelHiddenType;
use IDCT\Adminata\Form\Type\ModelListType;
use IDCT\Adminata\Form\Type\ModelReferenceType;
use IDCT\Adminata\Form\Type\ModelType;
use IDCT\Adminata\Form\Type\NativeCollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as SymfonyChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('adminata.admin.form.type.admin', AdminType::class)
            ->tag('form.type', ['alias' => 'adminata_type_admin'])
            ->args([
                service('adminata.admin.helper'),
            ])

        ->set('adminata.admin.form.type.model_choice', ModelType::class)
            ->tag('form.type', ['alias' => 'adminata_type_model'])
            ->args([
                service('property_accessor'),
            ])

        ->set('adminata.admin.form.type.model_list', ModelListType::class)
            ->tag('form.type', ['alias' => 'adminata_type_model_list'])

        ->set('adminata.admin.form.type.model_reference', ModelReferenceType::class)
            ->tag('form.type', ['alias' => 'adminata_type_model_reference'])

        ->set('adminata.admin.form.type.model_hidden', ModelHiddenType::class)
            ->tag('form.type', ['alias' => 'adminata_type_model_hidden'])

        ->set('adminata.admin.form.type.model_autocomplete', ModelAutocompleteType::class)
            ->tag('form.type', ['alias' => 'adminata_type_model_autocomplete'])

        ->set('adminata.admin.form.type.collection', NativeCollectionType::class)
            ->tag('form.type', ['alias' => 'adminata_type_native_collection'])

        ->set('adminata.admin.doctrine_orm.form.type.choice_field_mask', ChoiceFieldMaskType::class)
            ->tag('form.type', ['alias' => 'adminata_type_choice_field_mask'])

        ->set('adminata.admin.form.extension.field', FormTypeFieldExtension::class)
            ->tag('form.type_extension', [
                'alias' => 'form',
                'extended_type' => FormType::class,
            ])
            ->args([
                abstract_arg('default classes'),
                abstract_arg('default options'),
            ])

        ->set('adminata.admin.form.extension.field.mopa', MopaCompatibilityTypeFieldExtension::class)
            ->tag('form.type_extension', [
                'alias' => 'form',
                'extended_type' => FormType::class,
            ])

        ->set('adminata.admin.form.extension.choice', ChoiceTypeExtension::class)
            ->tag('form.type_extension', [
                'alias' => 'choice',
                'extended_type' => SymfonyChoiceType::class,
            ])

        // NEXT_MAJOR: Remove this service definition.
        ->set('adminata.admin.form.filter.type.number', NumberType::class)
            ->tag('form.type', ['alias' => 'adminata_type_filter_number'])

        // NEXT_MAJOR: Remove this service definition.
        ->set('adminata.admin.form.filter.type.choice', ChoiceType::class)
            ->tag('form.type', ['alias' => 'adminata_type_filter_choice'])

        // NEXT_MAJOR: Remove this service definition.
        ->set('adminata.admin.form.filter.type.default', DefaultType::class)
            ->tag('form.type', ['alias' => 'adminata_type_filter_default'])

        // NEXT_MAJOR: Remove this service definition.
        ->set('adminata.admin.form.filter.type.date', DateType::class)
            ->tag('form.type', ['alias' => 'adminata_type_filter_date'])

        // NEXT_MAJOR: Remove this service definition.
        ->set('adminata.admin.form.filter.type.daterange', DateRangeType::class)
            ->tag('form.type', ['alias' => 'adminata_type_filter_date_range'])

        // NEXT_MAJOR: Remove this service definition.
        ->set('adminata.admin.form.filter.type.datetime', DateTimeType::class)
            ->tag('form.type', ['alias' => 'adminata_type_filter_datetime'])

        // NEXT_MAJOR: Remove this service definition.
        ->set('adminata.admin.form.filter.type.datetime_range', DateTimeRangeType::class)
            ->tag('form.type', ['alias' => 'adminata_type_filter_datetime_range'])

        ->set('adminata.admin.form.data_transformer.boolean_to_string', BooleanToStringTransformer::class)
            ->args([
                1,
            ])

        ->set('adminata.admin.form.data_transformer_resolver', DataTransformerResolver::class)
            ->call('addCustomGlobalTransformer', [
                'boolean',
                service('adminata.admin.form.data_transformer.boolean_to_string'),
            ]);
};
