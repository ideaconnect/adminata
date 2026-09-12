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

use IDCT\Adminata\Admin\AdminHelper;
use IDCT\Adminata\Admin\BreadcrumbsBuilder;
use IDCT\Adminata\Admin\BreadcrumbsBuilderInterface;
use IDCT\Adminata\Admin\Extension\LockExtension;
use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\AdminataConfiguration;
use IDCT\Adminata\ArgumentResolver\AdminValueResolver;
use IDCT\Adminata\ArgumentResolver\ProxyQueryResolver;
use IDCT\Adminata\Asset\LastModifiedVersionStrategy;
use IDCT\Adminata\Controller\CRUDController;
use IDCT\Adminata\Event\AdminEventExtension;
use IDCT\Adminata\Filter\FilterFactory;
use IDCT\Adminata\Filter\FilterFactoryInterface;
use IDCT\Adminata\Filter\Persister\FilterPersisterInterface;
use IDCT\Adminata\Filter\Persister\SessionFilterPersister;
use IDCT\Adminata\Model\AuditManager;
use IDCT\Adminata\Model\AuditManagerInterface;
use IDCT\Adminata\Request\AdminFetcher;
use IDCT\Adminata\Request\AdminFetcherInterface;
use IDCT\Adminata\Route\AdminPoolLoader;
use IDCT\Adminata\Search\SearchHandler;
use IDCT\Adminata\Search\SearchHandlerInterface;
use IDCT\Adminata\Templating\TemplateRegistry;
use IDCT\Adminata\Translator\BCLabelTranslatorStrategy;
use IDCT\Adminata\Translator\Extractor\AdminExtractor;
use IDCT\Adminata\Translator\FormLabelTranslatorStrategy;
use IDCT\Adminata\Translator\LabelTranslatorStrategyInterface;
use IDCT\Adminata\Translator\NativeLabelTranslatorStrategy;
use IDCT\Adminata\Translator\NoopLabelTranslatorStrategy;
use IDCT\Adminata\Translator\UnderscoreLabelTranslatorStrategy;
use Psr\Container\ContainerInterface;
use Symfony\Component\Asset\PathPackage;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()

        ->set('adminata.admin.assets.public_dir', '/public')
        ->set('adminata.admin.assets.base_path', '/');

    $containerConfigurator->services()

        ->set('adminata.admin.assets.version_strategy', LastModifiedVersionStrategy::class)
            ->args([
                param('kernel.project_dir'),
                param('adminata.admin.assets.public_dir'),
            ])

        ->set('adminata.admin.assets.package', PathPackage::class)
            ->tag('assets.package', ['package' => 'adminata'])
            ->args([
                param('adminata.admin.assets.base_path'),
                service('adminata.admin.assets.version_strategy'),
                service('assets.context'),
            ])

        ->set('adminata.admin.pool', Pool::class)
            ->args([
                abstract_arg('admin service locator'),
                abstract_arg('admin service ids'),
                abstract_arg('admin service groups'),
                abstract_arg('admin service clasess'),
            ])

        ->alias(Pool::class, 'adminata.admin.pool')

        ->set('adminata.admin.configuration', AdminataConfiguration::class)
            ->args([
                abstract_arg('title'),
                abstract_arg('logo'),
                abstract_arg('options'),
            ])

        ->set('adminata.admin.route_loader', AdminPoolLoader::class)
            ->tag('routing.loader')
            ->args([
                service('adminata.admin.pool'),
            ])

        // @phpstan-ignore-next-line classConstant.internalClass
        ->set('adminata.admin.helper', AdminHelper::class)
            ->args([
                service('property_accessor'),
            ])

        ->set('adminata.admin.builder.filter.factory', FilterFactory::class)
            ->args([
                abstract_arg('service locator'),
            ])

        ->alias(FilterFactoryInterface::class, 'adminata.admin.builder.filter.factory')

        ->set('adminata.admin.breadcrumbs_builder', BreadcrumbsBuilder::class)
            ->args([
                param('adminata.admin.configuration.breadcrumbs'),
            ])

        ->alias(BreadcrumbsBuilderInterface::class, 'adminata.admin.breadcrumbs_builder')

        // Services used to format the label, default is adminata.admin.label.strategy.noop

        // NEXT_MAJOR: Remove this line.
        ->set('adminata.admin.label.strategy.bc', BCLabelTranslatorStrategy::class)

        ->set('adminata.admin.label.strategy.native', NativeLabelTranslatorStrategy::class)

        ->alias(LabelTranslatorStrategyInterface::class, 'adminata.admin.label.strategy.native')

        ->set('adminata.admin.label.strategy.noop', NoopLabelTranslatorStrategy::class)

        ->set('adminata.admin.label.strategy.underscore', UnderscoreLabelTranslatorStrategy::class)

        ->set('adminata.admin.label.strategy.form_component', FormLabelTranslatorStrategy::class)

        // @phpstan-ignore-next-line classConstant.internalClass
        ->set('adminata.admin.translation_extractor', AdminExtractor::class)
            ->tag('translation.extractor', [
                'alias' => 'adminata',
            ])
            ->args([
                service('adminata.admin.pool'),
                service('adminata.admin.breadcrumbs_builder'),
            ])

        ->set('adminata.admin.audit.manager', AuditManager::class)
            ->args([
                abstract_arg('service locator'),
            ])

        ->alias(AuditManagerInterface::class, 'adminata.admin.audit.manager')

        ->set('adminata.admin.search.handler', SearchHandler::class)

        ->alias(SearchHandlerInterface::class, 'adminata.admin.search.handler')

        ->set('adminata.admin.controller.crud', CRUDController::class)
            ->public()
            ->tag('container.service_subscriber')
            ->call('setContainer', [service(ContainerInterface::class)])

        ->set('adminata.admin.event.extension', AdminEventExtension::class)
            ->tag('adminata.admin.extension', ['global' => true])
            ->args([
                service('event_dispatcher'),
            ])

        ->set('adminata.admin.lock.extension', LockExtension::class)
            ->tag('adminata.admin.extension', ['global' => true])

        ->set('adminata.admin.filter_persister.session', SessionFilterPersister::class)
            ->args([
                service('request_stack'),
            ])

        ->alias(FilterPersisterInterface::class, 'adminata.admin.filter_persister.session')

        ->set('adminata.admin.global_template_registry', TemplateRegistry::class)
            ->args([
                param('adminata.admin.configuration.templates'),
            ])

        ->set('adminata.admin.request.fetcher', AdminFetcher::class)
            ->args([
                service('adminata.admin.pool'),
            ])

        ->alias(AdminFetcherInterface::class, 'adminata.admin.request.fetcher')

        ->set('adminata.admin.argument_resolver.admin', AdminValueResolver::class)
            ->args([
                service('adminata.admin.request.fetcher'),
            ])
            ->tag('controller.argument_value_resolver')

        ->set('adminata.admin.argument_resolver.proxy_query', ProxyQueryResolver::class)
            ->tag('controller.argument_value_resolver');
};
