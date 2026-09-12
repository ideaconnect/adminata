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

use IDCT\Adminata\Twig\BreadcrumbsRuntime;
use IDCT\Adminata\Twig\CanonicalizeRuntime;
use IDCT\Adminata\Twig\Extension\BreadcrumbsExtension;
use IDCT\Adminata\Twig\Extension\CanonicalizeExtension;
use IDCT\Adminata\Twig\Extension\GroupExtension;
use IDCT\Adminata\Twig\Extension\IconExtension;
use IDCT\Adminata\Twig\Extension\RenderElementExtension;
use IDCT\Adminata\Twig\Extension\SecurityExtension;
use IDCT\Adminata\Twig\Extension\AdminataExtension;
use IDCT\Adminata\Twig\Extension\TemplateRegistryExtension;
use IDCT\Adminata\Twig\Extension\ThemeExtension;
use IDCT\Adminata\Twig\Extension\XEditableExtension;
use IDCT\Adminata\Twig\GroupRuntime;
use IDCT\Adminata\Twig\IconRuntime;
use IDCT\Adminata\Twig\RenderElementRuntime;
use IDCT\Adminata\Twig\SecurityRuntime;
use IDCT\Adminata\Twig\AdminataRuntime;
use IDCT\Adminata\Twig\TemplateRegistryRuntime;
use IDCT\Adminata\Twig\ThemeRuntime;
use IDCT\Adminata\Twig\XEditableRuntime;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()

        ->set('adminata.admin.twig.extension.x_editable_type_mapping', XEditableRuntime::FIELD_DESCRIPTION_MAPPING);

    $containerConfigurator->services()

        ->set('adminata.admin.twig.theme_extension', ThemeExtension::class)
            ->tag('twig.extension')

        ->set('adminata.admin.twig.theme_runtime', ThemeRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('request_stack'),
                param('adminata.admin.configuration.theme.mode'),
            ])

        // NEXT_MAJOR: Remove the `args()` call.
        ->set('adminata.admin.twig.adminata_extension', AdminataExtension::class)
            ->tag('twig.extension')
            ->args([
                service('adminata.admin.twig.adminata_runtime'),
            ])

        // NEXT_MAJOR: Remove the alias.
        ->alias('adminata.admin.twig.extension', 'adminata.admin.twig.adminata_extension')
        ->deprecate(
            'sonata-project/admin-bundle',
            '4.7',
            'The "%alias_id%" alias is deprecated since sonata-project/admin-bundle 4.7 and will be removed in 5.0.'
        )

        ->set('adminata.admin.twig.adminata_runtime', AdminataRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('adminata.admin.pool'),
            ])

        // NEXT_MAJOR: Remove the `args()` call.
        ->set('adminata.admin.twig.template_registry_extension', TemplateRegistryExtension::class)
            ->tag('twig.extension')
            ->args([
                service('adminata.admin.twig.template_registry_runtime'),
            ])

        // NEXT_MAJOR: Remove the alias.
        ->alias('adminata.templates.twig.extension', 'adminata.admin.twig.template_registry_extension')
        ->deprecate(
            'sonata-project/admin-bundle',
            '4.7',
            'The "%alias_id%" alias is deprecated since sonata-project/admin-bundle 4.7 and will be removed in 5.0.'
        )

        ->set('adminata.admin.twig.template_registry_runtime', TemplateRegistryRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('adminata.admin.global_template_registry'),
                service('adminata.admin.pool'),
            ])

        // NEXT_MAJOR: Remove the `args()` call.
        ->set('adminata.admin.twig.group_extension', GroupExtension::class)
            ->tag('twig.extension')
            ->args([
                service('adminata.admin.twig.group_runtime'),
            ])

        // NEXT_MAJOR: Remove the alias.
        ->alias('adminata.admin.group.extension', 'adminata.admin.twig.group_extension')
        ->deprecate(
            'sonata-project/admin-bundle',
            '4.7',
            'The "%alias_id%" alias is deprecated since sonata-project/admin-bundle 4.7 and will be removed in 5.0.'
        )

        ->set('adminata.admin.twig.group_runtime', GroupRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('adminata.admin.pool'),
            ])

        // NEXT_MAJOR: Remove the `args()` call.
        ->set('adminata.admin.twig.icon_extension', IconExtension::class)
            ->tag('twig.extension')
            ->args([
                service('adminata.admin.twig.icon_runtime'),
            ])

        ->set('adminata.admin.twig.icon_runtime', IconRuntime::class)
            ->tag('twig.runtime')

        // NEXT_MAJOR: Remove the `args()` call.
        ->set('adminata.admin.twig.security_extension', SecurityExtension::class)
            ->tag('twig.extension')
            ->args([
                service('adminata.admin.twig.security_runtime'),
            ])

        // NEXT_MAJOR: Remove the alias.
        ->alias('adminata.security.twig.extension', 'adminata.admin.twig.security_extension')
        ->deprecate(
            'sonata-project/admin-bundle',
            '4.7',
            'The "%alias_id%" alias is deprecated since sonata-project/admin-bundle 4.7 and will be removed in 5.0.'
        )

        ->set('adminata.admin.twig.security_runtime', SecurityRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('security.authorization_checker'),
            ])

        // NEXT_MAJOR: Remove the `args()` call.
        ->set('adminata.admin.twig.canonicalize_extension', CanonicalizeExtension::class)
            ->tag('twig.extension')
            ->args([
                service('adminata.admin.twig.canonicalize_runtime'),
            ])

        // NEXT_MAJOR: Remove the alias.
        ->alias('adminata.canonicalize.twig.extension', 'adminata.admin.twig.canonicalize_extension')
        ->deprecate(
            'sonata-project/admin-bundle',
            '4.7',
            'The "%alias_id%" alias is deprecated since sonata-project/admin-bundle 4.7 and will be removed in 5.0.'
        )

        ->set('adminata.admin.twig.canonicalize_runtime', CanonicalizeRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('request_stack'),
            ])

        // NEXT_MAJOR: Remove the `args()` call.
        ->set('adminata.admin.twig.xeditable_extension', XEditableExtension::class)
            ->tag('twig.extension')
            ->args([
                service('adminata.admin.twig.xeditable_runtime'),
            ])

        // NEXT_MAJOR: Remove the alias.
        ->alias('adminata.xeditable.twig.extension', 'adminata.admin.twig.xeditable_extension')
        ->deprecate(
            'sonata-project/admin-bundle',
            '4.7',
            'The "%alias_id%" alias is deprecated since sonata-project/admin-bundle 4.7 and will be removed in 5.0.'
        )

        ->set('adminata.admin.twig.xeditable_runtime', XEditableRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('translator'),
                '%adminata.admin.twig.extension.x_editable_type_mapping%',
            ])

        // NEXT_MAJOR: Remove the `args()` call.
        ->set('adminata.admin.twig.render_element_extension', RenderElementExtension::class)
            ->tag('twig.extension')
            ->args([
                service('adminata.admin.twig.render_element_runtime'),
            ])

        // NEXT_MAJOR: Remove the alias.
        ->alias('adminata.render_element.twig.extension', 'adminata.admin.twig.render_element_extension')
        ->deprecate(
            'sonata-project/admin-bundle',
            '4.7',
            'The "%alias_id%" alias is deprecated since sonata-project/admin-bundle 4.7 and will be removed in 5.0.'
        )

        ->set('adminata.admin.twig.render_element_runtime', RenderElementRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('property_accessor'),
            ])

        // NEXT_MAJOR: Remove the `args()` call.
        ->set('adminata.admin.twig.breadcrumbs_extension', BreadcrumbsExtension::class)
            ->tag('twig.extension')
            ->args([
                service('adminata.admin.twig.breadcrumbs_runtime'),
            ])

        ->set('adminata.admin.twig.breadcrumbs_runtime', BreadcrumbsRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('adminata.admin.breadcrumbs_builder'),
            ]);
};
