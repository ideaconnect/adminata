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

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\XmlFileLoader;

return static function (RoutingConfigurator $routes) {
    foreach (debug_backtrace() as $trace) {
        /* @phpstan-ignore class.notFound */
        if (isset($trace['object']) && $trace['object'] instanceof XmlFileLoader && 'doImport' === $trace['function'] && isset($trace['args'])) {
            $realpath = realpath($trace['args'][3]);

            if (false !== $realpath && __DIR__ === dirname($realpath)) {
                @trigger_error(
                    'The "adminata.xml" routing configuration is deprecated since sonata-project/admin-bundle 4.39'
                    .' and will throw an error in 5.0. Import "adminata.php" instead.',
                    \E_USER_DEPRECATED
                );

                break;
            }
        }
    }

    $routes->add('adminata_redirect', '/')
        ->controller([RedirectController::class, 'redirectAction'])
        ->defaults([
            'route' => 'adminata_dashboard',
            'permanent' => true,
        ]);

    $routes->add('adminata_dashboard', '/dashboard')
        ->controller('adminata.admin.action.dashboard');

    $routes->add('adminata_retrieve_form_element', '/core/get-form-field-element')
        ->controller('adminata.admin.action.retrieve_form_field_element');

    $routes->add('adminata_append_form_element', '/core/append-form-field-element')
        ->controller('adminata.admin.action.append_form_field_element');

    $routes->add('adminata_short_object_information', '/core/get-short-object-description.{_format}')
        ->controller('adminata.admin.action.get_short_object_description')
        ->defaults(['_format' => 'html'])
        ->requirements(['_format' => 'html|json']);

    $routes->add('adminata_set_object_field_value', '/core/set-object-field-value')
        ->controller('adminata.admin.action.set_object_field_value');

    $routes->add('adminata_search', '/search')
        ->controller('adminata.admin.action.search');

    $routes->add('adminata_retrieve_autocomplete_items', '/core/get-autocomplete-items')
        ->controller('adminata.admin.action.retrieve_autocomplete_items');
};
