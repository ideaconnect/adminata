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

use IDCT\Adminata\Action\AppendFormFieldElementAction;
use IDCT\Adminata\Action\DashboardAction;
use IDCT\Adminata\Action\GetShortObjectDescriptionAction;
use IDCT\Adminata\Action\RetrieveAutocompleteItemsAction;
use IDCT\Adminata\Action\RetrieveFormFieldElementAction;
use IDCT\Adminata\Action\SearchAction;
use IDCT\Adminata\Action\SetObjectFieldValueAction;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('adminata.admin.action.dashboard', DashboardAction::class)
            ->public()
            ->args([
                param('adminata.admin.configuration.dashboard_blocks'),
                service('adminata.admin.global_template_registry'),
                service('twig'),
            ])

        ->set('adminata.admin.action.search', SearchAction::class)
            ->public()
            ->args([
                service('adminata.admin.pool'),
                service('adminata.admin.global_template_registry'),
                service('twig'),
            ])

        ->set('adminata.admin.action.append_form_field_element', AppendFormFieldElementAction::class)
            ->public()
            ->args([
                service('twig'),
                service('adminata.admin.request.fetcher'),
                service('adminata.admin.helper'),
            ])

        ->set('adminata.admin.action.retrieve_form_field_element', RetrieveFormFieldElementAction::class)
            ->public()
            ->args([
                service('twig'),
                service('adminata.admin.request.fetcher'),
                service('adminata.admin.helper'),
            ])

        ->set('adminata.admin.action.get_short_object_description', GetShortObjectDescriptionAction::class)
            ->public()
            ->args([
                service('twig'),
                service('adminata.admin.request.fetcher'),
            ])

        ->set('adminata.admin.action.set_object_field_value', SetObjectFieldValueAction::class)
            ->public()
            ->args([
                service('twig'),
                service('adminata.admin.request.fetcher'),
                service('validator'),
                service('adminata.admin.form.data_transformer_resolver'),
                service('property_accessor'),
                service('adminata.admin.twig.render_element_runtime'),
            ])

        ->set('adminata.admin.action.retrieve_autocomplete_items', RetrieveAutocompleteItemsAction::class)
            ->public()
            ->args([
                service('adminata.admin.request.fetcher'),
            ]);
};
