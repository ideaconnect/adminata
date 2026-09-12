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

namespace IDCT\Adminata\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use IDCT\Adminata\Admin\BreadcrumbsBuilderInterface;
use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Bridge\Exporter\AdminExporter;
use IDCT\Adminata\DependencyInjection\Compiler\AddAuditReadersCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\ModelManagerCompilerPass;
use IDCT\Adminata\DependencyInjection\Configuration;
use IDCT\Adminata\DependencyInjection\AdminataExtension;
use IDCT\Adminata\Doctrine\Adapter\AdapterChain;
use IDCT\Adminata\Doctrine\Adapter\ORM\DoctrineORMAdapter;
use IDCT\Adminata\Doctrine\Mapper\ORM\DoctrineORMMapper;
use IDCT\Adminata\Filter\FilterFactoryInterface;
use IDCT\Adminata\Filter\Persister\FilterPersisterInterface;
use IDCT\Adminata\Model\AuditManagerInterface;
use IDCT\Adminata\Model\AuditReaderInterface;
use IDCT\Adminata\Model\ModelManagerInterface;
use IDCT\Adminata\Translator\LabelTranslatorStrategyInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @phpstan-import-type AdminataConfig from Configuration
 * @phpstan-import-type AdminataAsset from Configuration
 */
final class AdminataExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @var array<string, mixed>
     *
     * @phpstan-var AdminataConfig
     */
    private array $defaultConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->setParameter('kernel.bundles', []);

        /** @phpstan-var AdminataConfig $config */
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
        self::assertContainerBuilderHasService('adminata.admin.lock.extension');
    }

    public function testNotHasServiceDefinitionForLockExtension(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load(['options' => ['lock_protection' => false]]);
        self::assertContainerBuilderNotHasService('adminata.admin.lock.extension');
    }

    /**
     * The exporter is part of this bundle, so the bridge to it is wired unconditionally: no
     * separate bundle has to be registered for "adminata.admin.admin_exporter" to exist.
     */
    public function testLoadsTheAdminExporterServiceDefinition(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load();
        self::assertContainerBuilderHasService(
            'adminata.admin.admin_exporter',
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

        self::assertContainerBuilderHasService('adminata.doctrine.model.adapter.chain', AdapterChain::class);
        self::assertContainerBuilderHasService('adminata.doctrine.adapter.doctrine_orm', DoctrineORMAdapter::class);
        self::assertContainerBuilderHasService('adminata.doctrine.mapper', DoctrineORMMapper::class);
    }

    public function testHasSecurityRoleParameters(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load();

        self::assertContainerBuilderHasParameter('adminata.admin.configuration.security.role_admin');
        self::assertContainerBuilderHasParameter('adminata.admin.configuration.security.role_super_admin');
    }

    public function testHasDefaultServiceParameters(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load();

        self::assertContainerBuilderHasParameter('adminata.admin.configuration.default_group');
        self::assertContainerBuilderHasParameter('adminata.admin.configuration.default_label_catalogue');
        self::assertContainerBuilderHasParameter('adminata.admin.configuration.default_translation_domain');
        self::assertContainerBuilderHasParameter('adminata.admin.configuration.default_icon');
        self::assertContainerBuilderHasParameter('adminata.admin.configuration.default_controller');
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
            ['path' => 'foo/bar.css', 'package_name' => 'adminata'],
            ['path' => 'bar/quux.css', 'package_name' => 'adminata'],
            ['path' => 'foo/bazz.css', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.css', 'package_name' => null],
        ];

        $this->load([
            'assets' => [
                'extra_stylesheets' => $extraStylesheets,
            ],
        ]);

        $options = $this->container->getDefinition('adminata.admin.configuration')->getArgument(2);
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
            'bundles/adminata/app.css',
            'bundles/adminata/fontawesome.css',
        ];
        $this->load([
            'assets' => [
                'remove_stylesheets' => $removeStylesheets,
            ],
        ]);

        $options = $this->container->getDefinition('adminata.admin.configuration')->getArgument(2);
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
            ['path' => 'foo/bar.js', 'package_name' => 'adminata'],
            ['path' => 'bar/quux.js', 'package_name' => 'adminata'],
            ['path' => 'foo/bazz.js', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.js', 'package_name' => null],
        ];

        $this->load([
            'assets' => [
                'extra_javascripts' => $extraJavascripts,
            ],
        ]);

        $options = $this->container->getDefinition('adminata.admin.configuration')->getArgument(2);
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
            'bundles/adminata/app.js',
        ];
        $this->load([
            'assets' => [
                'remove_javascripts' => $removeJavascripts,
            ],
        ]);

        $options = $this->container->getDefinition('adminata.admin.configuration')->getArgument(2);
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
            ['path' => 'foo/bar.css', 'package_name' => 'adminata'],
            ['path' => 'bar/quux.css', 'package_name' => 'adminata'],
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
            ['path' => 'foo/bar.js', 'package_name' => 'adminata'],
            ['path' => 'bar/quux.js', 'package_name' => 'adminata'],
            ['path' => 'foo/bazz.js', 'package_name' => 'another_package'],
            ['path' => 'bar/asd.js', 'package_name' => null],
        ];
        $removeStylesheets = [
            'bundles/adminata/app.css',
            'bundles/adminata/fontawesome.css',
        ];
        $removeJavascripts = [
            'bundles/adminata/app.js',
        ];
        $this->load([
            'assets' => [
                'extra_stylesheets' => $extraStylesheets,
                'remove_stylesheets' => $removeStylesheets,
                'extra_javascripts' => $extraJavascripts,
                'remove_javascripts' => $removeJavascripts,
            ],
        ]);

        $options = $this->container->getDefinition('adminata.admin.configuration')->getArgument(2);
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
            'user_block' => '@Adminata/Core/user_block.html.twig',
            'add_block' => '@Adminata/Core/add_block.html.twig',
            'layout' => '@Adminata/standard_layout.html.twig',
            'ajax' => '@Adminata/ajax_layout.html.twig',
            'dashboard' => '@Adminata/Core/dashboard.html.twig',
            'search' => '@Adminata/Core/search.html.twig',
            'list' => '@Adminata/CRUD/list.html.twig',
            'filter' => '@Adminata/Form/filter_admin_fields.html.twig',
            'show' => '@Adminata/CRUD/show.html.twig',
            'show_compare' => '@Adminata/CRUD/show_compare.html.twig',
            'edit' => '@Adminata/CRUD/edit.html.twig',
            'preview' => '@Adminata/CRUD/preview.html.twig',
            'history' => '@Adminata/CRUD/history.html.twig',
            'acl' => '@Adminata/CRUD/acl.html.twig',
            'history_revision_timestamp' => '@Adminata/CRUD/history_revision_timestamp.html.twig',
            'action' => '@Adminata/CRUD/action.html.twig',
            'select' => '@Adminata/CRUD/list__select.html.twig',
            'list_block' => '@Adminata/Block/block_admin_list.html.twig',
            'search_result_block' => '@Adminata/Block/block_search_result.html.twig',
            'short_object_description' => '@Adminata/Helper/short-object-description.html.twig',
            'delete' => '@Adminata/CRUD/delete.html.twig',
            'batch' => '@Adminata/CRUD/list__batch.html.twig',
            'batch_confirmation' => '@Adminata/CRUD/batch_confirmation.html.twig',
            'inner_list_row' => '@Adminata/CRUD/list_inner_row.html.twig',
            'outer_list_rows_mosaic' => '@Adminata/CRUD/list_outer_rows_mosaic.html.twig',
            'outer_list_rows_list' => '@Adminata/CRUD/list_outer_rows_list.html.twig',
            'outer_list_rows_tree' => '@Adminata/CRUD/list_outer_rows_tree.html.twig',
            'base_list_field' => '@Adminata/CRUD/base_list_field.html.twig',
            'pager_links' => '@Adminata/Pager/links.html.twig',
            'pager_results' => '@Adminata/Pager/results.html.twig',
            'tab_menu_template' => '@Adminata/Core/tab_menu_template.html.twig',
            'knp_menu_template' => '@Adminata/Menu/adminata_menu.html.twig',
            'action_create' => '@Adminata/CRUD/dashboard__action_create.html.twig',
            'button_acl' => '@Adminata/Button/acl_button.html.twig',
            'button_create' => '@Adminata/Button/create_button.html.twig',
            'button_edit' => '@Adminata/Button/edit_button.html.twig',
            'button_history' => '@Adminata/Button/history_button.html.twig',
            'button_list' => '@Adminata/Button/list_button.html.twig',
            'button_show' => '@Adminata/Button/show_button.html.twig',
            'form_theme' => [],
            'filter_theme' => [],
        ], $this->container->getParameter('adminata.admin.configuration.templates'));
    }

    public function testLoadIntlTemplate(): void
    {
        $bundles = $this->container->getParameter('kernel.bundles');
        static::assertIsArray($bundles);

        $this->container->setParameter('kernel.bundles', array_merge($bundles, ['SonataIntlBundle' => true]));
        $this->load();

        $templates = $this->container->getParameter('adminata.admin.configuration.templates');
        static::assertIsArray($templates);
        static::assertSame('@SonataIntl/CRUD/history_revision_timestamp.html.twig', $templates['history_revision_timestamp']);
    }

    public function testDefaultTheme(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load();

        $options = $this->container->getDefinition('adminata.admin.configuration')->getArgument(2);
        static::assertIsArray($options);
        static::assertSame(
            ['mode' => 'system', 'logo_dark' => null, 'logo_icon' => null],
            $options['theme']
        );

        static::assertContainerBuilderHasParameter('adminata.admin.configuration.theme.mode', 'system');
        static::assertContainerBuilderHasParameter('adminata.admin.configuration.theme.logo_dark', null);
        static::assertContainerBuilderHasParameter('adminata.admin.configuration.theme.logo_icon', null);
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

        $options = $this->container->getDefinition('adminata.admin.configuration')->getArgument(2);
        static::assertIsArray($options);
        static::assertSame(
            [
                'mode' => 'dark',
                'logo_dark' => 'bundles/app/logo-dark.svg',
                'logo_icon' => 'bundles/app/icon.svg',
            ],
            $options['theme']
        );

        static::assertContainerBuilderHasParameter('adminata.admin.configuration.theme.mode', 'dark');
        static::assertContainerBuilderHasParameter('adminata.admin.configuration.theme.logo_dark', 'bundles/app/logo-dark.svg');
        static::assertContainerBuilderHasParameter('adminata.admin.configuration.theme.logo_icon', 'bundles/app/icon.svg');
    }

    public function testSetInvalidThemeMode(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value "sepia" is not allowed for path "adminata.theme.mode". Permissible values: "light", "dark", "system"');
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
        return [new AdminataExtension()];
    }

    /**
     * @return list<AdminataAsset>
     */
    private function getDefaultStylesheets(): array
    {
        return $this->defaultConfiguration['assets']['stylesheets'];
    }
}
