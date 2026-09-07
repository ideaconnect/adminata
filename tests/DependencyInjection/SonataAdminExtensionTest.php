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

namespace Sonata\AdminBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sonata\AdminBundle\Admin\BreadcrumbsBuilderInterface;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Bridge\Exporter\AdminExporter;
use Sonata\AdminBundle\DependencyInjection\Compiler\AddAuditReadersCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\ModelManagerCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Configuration;
use Sonata\AdminBundle\DependencyInjection\SonataAdminExtension;
use Sonata\AdminBundle\Doctrine\Adapter\AdapterChain;
use Sonata\AdminBundle\Doctrine\Adapter\ORM\DoctrineORMAdapter;
use Sonata\AdminBundle\Doctrine\Mapper\ORM\DoctrineORMMapper;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\AdminBundle\Filter\Persister\FilterPersisterInterface;
use Sonata\AdminBundle\Model\AuditManagerInterface;
use Sonata\AdminBundle\Model\AuditReaderInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\AdminBundle\Translator\LabelTranslatorStrategyInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @phpstan-import-type SonataAdminConfiguration from Configuration
 * @phpstan-import-type SonataAdminAsset from Configuration
 */
final class SonataAdminExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @var array<string, mixed>
     *
     * @phpstan-var SonataAdminConfiguration
     */
    private array $defaultConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->setParameter('kernel.bundles', []);

        /** @phpstan-var SonataAdminConfiguration $config */
        $config = new Processor()->processConfiguration(new Configuration(), []);
        $this->defaultConfiguration = $config;
    }

    public function testHasCoreServicesAlias(): void
    {
        $this->load();

        self::assertContainerBuilderHasService(Pool::class);
        self::assertContainerBuilderHasService(FilterFactoryInterface::class);
        self::assertContainerBuilderHasService(BreadcrumbsBuilderInterface::class);
        self::assertContainerBuilderHasService(LabelTranslatorStrategyInterface::class);
        self::assertContainerBuilderHasService(AuditManagerInterface::class);
        self::assertContainerBuilderHasService(FilterPersisterInterface::class);
    }

    public function testHasServiceDefinitionForLockExtension(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load(['options' => ['lock_protection' => true]]);
        self::assertContainerBuilderHasService('sonata.admin.lock.extension');
    }

    public function testNotHasServiceDefinitionForLockExtension(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load(['options' => ['lock_protection' => false]]);
        self::assertContainerBuilderNotHasService('sonata.admin.lock.extension');
    }

    /**
     * The exporter is part of this bundle, so the bridge to it is wired unconditionally: no
     * separate bundle has to be registered for "sonata.admin.admin_exporter" to exist.
     */
    public function testLoadsTheAdminExporterServiceDefinition(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load();
        self::assertContainerBuilderHasService(
            'sonata.admin.admin_exporter',
            AdminExporter::class
        );
    }

    /**
     * The Doctrine manager, adapter and mapper layer is part of this bundle too. It used to arrive
     * with a SonataDoctrineBundle of its own, so these three services only existed when an
     * application happened to register that bundle; now they are loaded here. The two ORM ones are
     * behind an interface_exists() guard, because the ORM admin bundle ships separately and a panel
     * running the MongoDB ODM alone need not have Doctrine ORM installed at all.
     */
    public function testLoadsTheDoctrineServiceDefinitions(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load();

        self::assertContainerBuilderHasService('sonata.doctrine.model.adapter.chain', AdapterChain::class);
        self::assertContainerBuilderHasService('sonata.doctrine.adapter.doctrine_orm', DoctrineORMAdapter::class);
        self::assertContainerBuilderHasService('sonata.doctrine.mapper', DoctrineORMMapper::class);
    }

    public function testHasSecurityRoleParameters(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load();

        self::assertContainerBuilderHasParameter('sonata.admin.configuration.security.role_admin');
        self::assertContainerBuilderHasParameter('sonata.admin.configuration.security.role_super_admin');
    }

    public function testHasDefaultServiceParameters(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load();

        self::assertContainerBuilderHasParameter('sonata.admin.configuration.default_group');
        self::assertContainerBuilderHasParameter('sonata.admin.configuration.default_label_catalogue');
        self::assertContainerBuilderHasParameter('sonata.admin.configuration.default_translation_domain');
        self::assertContainerBuilderHasParameter('sonata.admin.configuration.default_icon');
        self::assertContainerBuilderHasParameter('sonata.admin.configuration.default_controller');
    }

    public function testExtraStylesheetsGetAdded(): void
    {
        $this->container->setParameter('kernel.bundles', []);

        $extraStylesheets = [
            'foo/bar.css',
            'bar/quux.css',
            ['path' => 'foo/bazz.css', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.css', 'package_name' => null],
        ];

        $extraStylesheetsNormalized = [
            ['path' => 'foo/bar.css', 'package_name' => 'sonata_admin'],
            ['path' => 'bar/quux.css', 'package_name' => 'sonata_admin'],
            ['path' => 'foo/bazz.css', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.css', 'package_name' => null],
        ];

        $this->load([
            'assets' => [
                'extra_stylesheets' => $extraStylesheets,
            ],
        ]);

        $options = $this->container->getDefinition('sonata.admin.configuration')->getArgument(2);
        static::assertIsArray($options);

        $stylesheets = $options['stylesheets'];
        static::assertSame(
            array_merge($this->getDefaultStylesheets(), $extraStylesheetsNormalized),
            $stylesheets
        );
    }

    public function testRemoveStylesheetsGetRemoved(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $removeStylesheets = [
            'bundles/sonataadmin/app.css',
            'bundles/sonataadmin/fontawesome.css',
        ];
        $this->load([
            'assets' => [
                'remove_stylesheets' => $removeStylesheets,
            ],
        ]);

        $options = $this->container->getDefinition('sonata.admin.configuration')->getArgument(2);
        static::assertIsArray($options);

        $stylesheets = $options['stylesheets'];
        static::assertIsArray($stylesheets);

        $expected = array_values(
            array_filter(
                $this->defaultConfiguration['assets']['stylesheets'],
                static fn (array $item) => !\in_array($item['path'], $removeStylesheets, true)
            )
        );

        static::assertSame($expected, $stylesheets);
    }

    public function testExtraJavascriptsGetAdded(): void
    {
        $this->container->setParameter('kernel.bundles', []);

        $extraJavascripts = [
            'foo/bar.js',
            'bar/quux.js',
            ['path' => 'foo/bazz.js', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.js', 'package_name' => null],
        ];

        $extraJavascriptsNormalized = [
            ['path' => 'foo/bar.js', 'package_name' => 'sonata_admin'],
            ['path' => 'bar/quux.js', 'package_name' => 'sonata_admin'],
            ['path' => 'foo/bazz.js', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.js', 'package_name' => null],
        ];

        $this->load([
            'assets' => [
                'extra_javascripts' => $extraJavascripts,
            ],
        ]);

        $options = $this->container->getDefinition('sonata.admin.configuration')->getArgument(2);
        static::assertIsArray($options);

        $javascripts = $options['javascripts'];
        static::assertSame(
            [...$this->defaultConfiguration['assets']['javascripts'], ...$extraJavascriptsNormalized],
            $javascripts
        );
    }

    public function testRemoveJavascriptsGetRemoved(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $removeJavascripts = [
            'bundles/sonataadmin/app.js',
        ];
        $this->load([
            'assets' => [
                'remove_javascripts' => $removeJavascripts,
            ],
        ]);

        $options = $this->container->getDefinition('sonata.admin.configuration')->getArgument(2);
        static::assertIsArray($options);

        $javascripts = $options['javascripts'];
        static::assertIsArray($javascripts);

        $expected = array_values(
            array_filter(
                $this->defaultConfiguration['assets']['javascripts'],
                static fn (array $item) => !\in_array($item['path'], $removeJavascripts, true)
            )
        );

        static::assertSame($expected, $javascripts);
    }

    public function testAssetsCanBeAddedAndRemoved(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $extraStylesheets = [
            'foo/bar.css',
            'bar/quux.css',
            ['path' => 'foo/bazz.css', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.css', 'package_name' => null],
        ];
        $extraStylesheetsNormalized = [
            ['path' => 'foo/bar.css', 'package_name' => 'sonata_admin'],
            ['path' => 'bar/quux.css', 'package_name' => 'sonata_admin'],
            ['path' => 'foo/bazz.css', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.css', 'package_name' => null],
        ];
        $extraJavascripts = [
            'foo/bar.js',
            'bar/quux.js',
            ['path' => 'foo/bazz.js', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.js', 'package_name' => null],
        ];
        $extraJavascriptsNormalized = [
            ['path' => 'foo/bar.js', 'package_name' => 'sonata_admin'],
            ['path' => 'bar/quux.js', 'package_name' => 'sonata_admin'],
            ['path' => 'foo/bazz.js', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.js', 'package_name' => null],
        ];
        $removeStylesheets = [
            'bundles/sonataadmin/app.css',
            'bundles/sonataadmin/fontawesome.css',
        ];
        $removeJavascripts = [
            'bundles/sonataadmin/app.js',
        ];
        $this->load([
            'assets' => [
                'extra_stylesheets' => $extraStylesheets,
                'remove_stylesheets' => $removeStylesheets,
                'extra_javascripts' => $extraJavascripts,
                'remove_javascripts' => $removeJavascripts,
            ],
        ]);

        $options = $this->container->getDefinition('sonata.admin.configuration')->getArgument(2);
        static::assertIsArray($options);

        $stylesheets = $options['stylesheets'];

        static::assertSame(
            [
                ...array_filter(
                    $this->defaultConfiguration['assets']['stylesheets'],
                    static fn (array $item) => !\in_array($item['path'], $removeStylesheets, true)
                ),
                ...$extraStylesheetsNormalized,
            ],
            $stylesheets
        );

        $javascripts = $options['javascripts'];
        static::assertSame(
            [
                ...array_filter(
                    $this->defaultConfiguration['assets']['javascripts'],
                    static fn (array $item) => !\in_array($item['path'], $removeJavascripts, true)
                ),
                ...$extraJavascriptsNormalized,
            ],
            $javascripts
        );
    }

    public function testDefaultTemplates(): void
    {
        $this->load();

        static::assertSame([
            'user_block' => '@SonataAdmin/Core/user_block.html.twig',
            'add_block' => '@SonataAdmin/Core/add_block.html.twig',
            'layout' => '@SonataAdmin/standard_layout.html.twig',
            'ajax' => '@SonataAdmin/ajax_layout.html.twig',
            'dashboard' => '@SonataAdmin/Core/dashboard.html.twig',
            'search' => '@SonataAdmin/Core/search.html.twig',
            'list' => '@SonataAdmin/CRUD/list.html.twig',
            'filter' => '@SonataAdmin/Form/filter_admin_fields.html.twig',
            'show' => '@SonataAdmin/CRUD/show.html.twig',
            'show_compare' => '@SonataAdmin/CRUD/show_compare.html.twig',
            'edit' => '@SonataAdmin/CRUD/edit.html.twig',
            'preview' => '@SonataAdmin/CRUD/preview.html.twig',
            'history' => '@SonataAdmin/CRUD/history.html.twig',
            'acl' => '@SonataAdmin/CRUD/acl.html.twig',
            'history_revision_timestamp' => '@SonataAdmin/CRUD/history_revision_timestamp.html.twig',
            'action' => '@SonataAdmin/CRUD/action.html.twig',
            'select' => '@SonataAdmin/CRUD/list__select.html.twig',
            'list_block' => '@SonataAdmin/Block/block_admin_list.html.twig',
            'search_result_block' => '@SonataAdmin/Block/block_search_result.html.twig',
            'short_object_description' => '@SonataAdmin/Helper/short-object-description.html.twig',
            'delete' => '@SonataAdmin/CRUD/delete.html.twig',
            'batch' => '@SonataAdmin/CRUD/list__batch.html.twig',
            'batch_confirmation' => '@SonataAdmin/CRUD/batch_confirmation.html.twig',
            'inner_list_row' => '@SonataAdmin/CRUD/list_inner_row.html.twig',
            'outer_list_rows_mosaic' => '@SonataAdmin/CRUD/list_outer_rows_mosaic.html.twig',
            'outer_list_rows_list' => '@SonataAdmin/CRUD/list_outer_rows_list.html.twig',
            'outer_list_rows_tree' => '@SonataAdmin/CRUD/list_outer_rows_tree.html.twig',
            'base_list_field' => '@SonataAdmin/CRUD/base_list_field.html.twig',
            'pager_links' => '@SonataAdmin/Pager/links.html.twig',
            'pager_results' => '@SonataAdmin/Pager/results.html.twig',
            'tab_menu_template' => '@SonataAdmin/Core/tab_menu_template.html.twig',
            'knp_menu_template' => '@SonataAdmin/Menu/sonata_menu.html.twig',
            'action_create' => '@SonataAdmin/CRUD/dashboard__action_create.html.twig',
            'button_acl' => '@SonataAdmin/Button/acl_button.html.twig',
            'button_create' => '@SonataAdmin/Button/create_button.html.twig',
            'button_edit' => '@SonataAdmin/Button/edit_button.html.twig',
            'button_history' => '@SonataAdmin/Button/history_button.html.twig',
            'button_list' => '@SonataAdmin/Button/list_button.html.twig',
            'button_show' => '@SonataAdmin/Button/show_button.html.twig',
            'form_theme' => [],
            'filter_theme' => [],
        ], $this->container->getParameter('sonata.admin.configuration.templates'));
    }

    public function testLoadIntlTemplate(): void
    {
        $bundles = $this->container->getParameter('kernel.bundles');
        static::assertIsArray($bundles);

        $this->container->setParameter('kernel.bundles', array_merge($bundles, ['SonataIntlBundle' => true]));
        $this->load();

        $templates = $this->container->getParameter('sonata.admin.configuration.templates');
        static::assertIsArray($templates);
        static::assertSame('@SonataIntl/CRUD/history_revision_timestamp.html.twig', $templates['history_revision_timestamp']);
    }

    public function testDefaultTheme(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load();

        $options = $this->container->getDefinition('sonata.admin.configuration')->getArgument(2);
        static::assertIsArray($options);
        static::assertSame(
            ['mode' => 'system', 'logo_dark' => null, 'logo_icon' => null],
            $options['theme']
        );

        static::assertContainerBuilderHasParameter('sonata.admin.configuration.theme.mode', 'system');
        static::assertContainerBuilderHasParameter('sonata.admin.configuration.theme.logo_dark', null);
        static::assertContainerBuilderHasParameter('sonata.admin.configuration.theme.logo_icon', null);
    }

    public function testSetTheme(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load([
            'theme' => [
                'mode' => 'dark',
                'logo_dark' => 'bundles/app/logo-dark.svg',
                'logo_icon' => 'bundles/app/icon.svg',
            ],
        ]);

        $options = $this->container->getDefinition('sonata.admin.configuration')->getArgument(2);
        static::assertIsArray($options);
        static::assertSame(
            [
                'mode' => 'dark',
                'logo_dark' => 'bundles/app/logo-dark.svg',
                'logo_icon' => 'bundles/app/icon.svg',
            ],
            $options['theme']
        );

        static::assertContainerBuilderHasParameter('sonata.admin.configuration.theme.mode', 'dark');
        static::assertContainerBuilderHasParameter('sonata.admin.configuration.theme.logo_dark', 'bundles/app/logo-dark.svg');
        static::assertContainerBuilderHasParameter('sonata.admin.configuration.theme.logo_icon', 'bundles/app/icon.svg');
    }

    public function testSetInvalidThemeMode(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value "sepia" is not allowed for path "sonata_admin.theme.mode". Permissible values: "light", "dark", "system"');
        $this->container->setParameter('kernel.bundles', []);
        $this->load([
            'theme' => [
                'mode' => 'sepia',
            ],
        ]);
    }

    public function testAutoregisterAddingTagsToServices(): void
    {
        $this->load();

        $autoconfiguredInstancesOf = $this->container->getAutoconfiguredInstanceof();

        static::assertArrayHasKey(ModelManagerInterface::class, $autoconfiguredInstancesOf);
        static::assertTrue($autoconfiguredInstancesOf[ModelManagerInterface::class]->hasTag(ModelManagerCompilerPass::MANAGER_TAG));

        static::assertArrayHasKey(AuditReaderInterface::class, $autoconfiguredInstancesOf);
        static::assertTrue($autoconfiguredInstancesOf[AuditReaderInterface::class]->hasTag(AddAuditReadersCompilerPass::AUDIT_READER_TAG));
    }

    protected function getContainerExtensions(): array
    {
        return [new SonataAdminExtension()];
    }

    /**
     * @return list<SonataAdminAsset>
     */
    private function getDefaultStylesheets(): array
    {
        return $this->defaultConfiguration['assets']['stylesheets'];
    }
}
