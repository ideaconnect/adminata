<?php

declare(strict_types=1);

/*
 * This file is part of the adminata package.
 *
 * (c) IDCT Bartosz Pachołek <bartosz@idct.tech>
 *
 * Forked from the Sonata Project
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adminata\Tests\Unit\Rename;

use Adminata\Rename\Engine;
use IDCT\Adminata\DoctrineORM\Filter\StringFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The cases PLAN/v2/02 marks as the ones a rule can get wrong: the shapes the old names took in
 * PHP, JSON, YAML, Twig, JavaScript and prose, the phrases that must stay, and the difference
 * between adminata's own trees and an application.
 *
 * The strings below spell the old names, which is the point of the test; this file is in the
 * gate's skip list for that reason (upstream/rename/allow.php).
 */
final class EngineTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideTreeModeRewritesCases(): iterable
    {
        // Namespaces, in every backslash spelling and the URL-encoded one.
        yield 'PHP use statement' => ['use Sonata\AdminBundle\Admin\AbstractAdmin;', 'use IDCT\Adminata\Admin\AbstractAdmin;', 'src/Foo.php'];
        yield 'PHP namespace declaration' => ['namespace Sonata\AdminBundle\Tests\Admin;', 'namespace IDCT\Adminata\Tests\Admin;', 'tests/Foo.php'];
        yield 'JSON PSR-4 key' => ['"Sonata\\\\AdminBundle\\\\": "src/"', '"IDCT\\\\Adminata\\\\": "src/"', 'composer.json'];
        yield 'PHPStan baseline regex' => ["message: '#Sonata\\\\\\\\AdminBundle\\\\\\\\Form\\\\\\\\X#'", "message: '#IDCT\\\\\\\\Adminata\\\\\\\\Form\\\\\\\\X#'", 'baseline.neon'];
        yield 'URL-encoded query string' => ['_sonata_admin=Sonata%5CDoctrineORMAdminBundle%5CTests%5CApp%5CAdmin%5CFooAdmin', '_adminata_admin=IDCT%5CAdminata%5CDoctrineORM%5CTests%5CApp%5CAdmin%5CFooAdmin', 'tests/Foo.php'];
        yield 'ORM namespace' => ['Sonata\DoctrineORMAdminBundle\Filter\StringFilter', StringFilter::class, 'src/Foo.php'];
        yield 'ODM namespace' => ['Sonata\DoctrineMongoDBAdminBundle\Model\ModelManager', 'IDCT\Adminata\DoctrineMongoDB\Model\ModelManager', 'src/Foo.php'];
        yield 'merged upstream namespaces' => ['Sonata\Form\Type\CollectionType Sonata\Exporter\Writer\CsvWriter Sonata\Doctrine\Mapper\Foo Sonata\BlockBundle\Block\Bar', 'IDCT\Adminata\Form\Type\CollectionType IDCT\Adminata\Exporter\Writer\CsvWriter IDCT\Adminata\Doctrine\Mapper\Foo IDCT\Adminata\Block\Bar', 'src/Foo.php'];

        // Classes.
        yield 'bundle and extension classes' => ['SonataAdminBundle SonataAdminExtension AbstractSonataAdminExtension SonataAdminRuntime SonataConfiguration SonataBlockExtension SonataExporterException', 'AdminataBundle AdminataExtension AbstractAdminataExtension AdminataRuntime AdminataConfiguration AdminataBlockExtension AdminataExporterException', 'src/Foo.php'];
        yield 'storage layers\' bundle classes' => ['SonataDoctrineORMAdminBundle SonataDoctrineORMAdminExtension SonataDoctrineMongoDBAdminBundle', 'AdminataDoctrineORMBundle AdminataDoctrineORMExtension AdminataDoctrineMongoDBBundle', 'src/Foo.php'];
        yield 'PHPStan type aliases that would collide with the class' => ['@phpstan-type SonataAdminConfiguration = array{} SonataAdminConfigurationOptions SonataConfigurationOptions', '@phpstan-type AdminataConfig = array{} AdminataConfigOptions AdminataConfigurationOptions', 'src/Foo.php'];

        // Twig.
        yield 'Twig namespace and its retired aliases' => ["'@SonataAdmin/CRUD/list.html.twig' '@!SonataAdmin/x' '@SonataBlock/a' '@SonataForm/b' '@SonataTwig/c' '@SonataDoctrineORMAdmin/d' '@SonataDoctrineMongoDBAdmin/e'", "'@Adminata/CRUD/list.html.twig' '@!Adminata/x' '@Adminata/a' '@Adminata/b' '@Adminata/c' '@AdminataDoctrineORM/d' '@AdminataDoctrineMongoDB/e'", 'src/Foo.php'];
        yield 'Twig globals, functions, blocks and the form variable' => ["{% block sonata_admin_content %}{{ sonata_config.getOption('x') }} {{ sonata_admin.adminPool }} {{ sonata_block_render() }} {% block sonata_type_model_widget %} {{ form.vars.sonata_admin.field_description }}", "{% block adminata_content %}{{ adminata_config.getOption('x') }} {{ adminata_admin.adminPool }} {{ adminata_block_render() }} {% block adminata_type_model_widget %} {{ form.vars.adminata_admin.field_description }}", 'src/Resources/views/foo.html.twig'];
        yield 'Twig translation domain and asset package' => ["'skip'|trans({}, 'SonataAdminBundle') asset('bundles/sonataadmin/app.css', 'sonata_admin')", "'skip'|trans({}, 'AdminataBundle') asset('bundles/adminata/app.css', 'adminata')", 'src/Resources/views/foo.html.twig'];
        yield 'Stimulus in Twig' => ["{{ stimulus_controller('sonata-modal', {size: 'lg'}) }} data-sonata-modal-target=\"dialog\" sonata-modal:opened", "{{ stimulus_controller('adminata-modal', {size: 'lg'}) }} data-adminata-modal-target=\"dialog\" adminata-modal:opened", 'src/Resources/views/foo.html.twig'];
        yield 'hooks, with and without ba' => ['class="sonata-ba-list-field sonata-ba-list-field-header sonata-actions sonata-ba-content" id="sonata-content"', 'class="adminata-list-field adminata-list-field-header adminata-actions adminata-content" id="adminata-content"', 'src/Resources/views/foo.html.twig'];

        // The `sonata_admin` root against the `sonata_admin` option.
        yield 'root in PHP' => ["new TreeBuilder('sonata_admin'); \$c->getExtensionConfig('sonata_admin'); \$c->loadFromExtension('sonata_admin', []); const DEFAULT_PACKAGE = 'sonata_admin'; 'package_name' => 'sonata_admin', 'alias' => 'sonata_admin', return 'sonata_admin';", "new TreeBuilder('adminata'); \$c->getExtensionConfig('adminata'); \$c->loadFromExtension('adminata', []); const DEFAULT_PACKAGE = 'adminata'; 'package_name' => 'adminata', 'alias' => 'adminata', return 'adminata';", 'src/Foo.php'];
        yield 'option in PHP' => ["\$view->vars['sonata_admin'] = \$x; \$options['sonata_admin_enabled']; 'sonata_field_description'", "\$view->vars['adminata_admin'] = \$x; \$options['adminata_admin_enabled']; 'adminata_field_description'", 'src/Foo.php'];
        yield 'root in YAML' => ["sonata_admin:\n    title: x\n_sonata_admin:\n    type: sonata_admin\n", "adminata:\n    title: x\n_adminata_admin:\n    type: adminata\n", 'config/packages/sonata_admin.yaml'];
        yield 'config path in prose and a code comment' => ['`sonata_admin.options.list_row_link` and `sonata_admin.yaml`', '`adminata.options.list_row_link` and `adminata.yaml`', 'docs/foo.rst'];
        yield 'the Twig global keeps its dot in Twig' => ['{{ sonata_admin.options.x }}', '{{ adminata_admin.options.x }}', 'src/Resources/views/foo.html.twig'];
        yield 'the contract test names the root' => ["yield 'sonata_admin' => ['sonata_admin', new C()];", "yield 'adminata' => ['adminata', new C()];", 'tests-adminata/Contract/ConfigContractTest.php'];

        // Lower-case families.
        yield 'ids, tags, events, params' => ["'sonata.admin.pool' tag('sonata.admin') 'sonata.admin.event.configure.form' sonata.block.manager sonata.exporter.writer.csv 'csrf-token_sonata.delete'", "'adminata.admin.pool' tag('adminata.admin') 'adminata.admin.event.configure.form' adminata.block.manager adminata.exporter.writer.csv 'csrf-token_adminata.delete'", 'src/Foo.php'];
        yield 'the two ids that carry a class name' => ['sonata.admin.twig.sonata_admin_extension sonata.admin.twig.sonata_admin_runtime', 'adminata.admin.twig.adminata_extension adminata.admin.twig.adminata_runtime', 'src/Foo.php'];
        yield 'routes, request attributes, commands' => ["'sonata_admin_dashboard' 'sonata_admin_retrieve_autocomplete_items' '_sonata_admin' '_sonata_csrf_token' 'sonata:admin:list' 'debug:sonata:block' 'make:sonata:admin'", "'adminata_dashboard' 'adminata_retrieve_autocomplete_items' '_adminata_admin' '_adminata_csrf_token' 'adminata:list' 'debug:adminata:block' 'make:adminata:admin'", 'src/Foo.php'];
        yield 'form prefixes, flash types, cookies, menu alias' => ["'sonata_type_model' 'sonata_flash_success' 'sonata_theme' 'sonata_sidebar_hide' knp_menu_render('sonata_admin_sidebar')", "'adminata_type_model' 'adminata_flash_success' 'adminata_theme' 'adminata_sidebar_hide' knp_menu_render('adminata_sidebar')", 'src/Foo.php'];
        yield 'camel case and the JS global' => ['sonataAdmin sonataConfiguration window.sonataApplication defaultSonataDoctrineConfig', 'adminataAdmin adminataConfiguration window.adminataApplication defaultAdminataDoctrineConfig', 'assets/js/foo.js'];
        yield 'roles and the routes cache directory' => ["'ROLE_SONATA_ADMIN' '/sonata/admin' '/sonata/news/post' admin_sonata_news_post", "'ROLE_ADMINATA_ADMIN' '/adminata/admin' '/sonata/news/post' admin_sonata_news_post", 'src/Foo.php'];

        // What stays.
        yield 'the vendor, the people, the project' => ['This file is part of the Sonata Project package. (c) Thomas Rabaix <thomas.rabaix@sonata-project.org> sonata-project/admin-bundle 4.43.0 https://github.com/sonata-project/SonataAdminBundle/issues Sonata Admin, Sonata\'s', 'This file is part of the Sonata Project package. (c) Thomas Rabaix <thomas.rabaix@sonata-project.org> sonata-project/admin-bundle 4.43.0 https://github.com/sonata-project/SonataAdminBundle/issues Sonata Admin, Sonata\'s', 'src/Foo.php'];
        yield 'other Sonata bundles' => ['SonataUserBundle @SonataIntl/foo Sonata\UserBundle\Model\User sonata_user: sonata.media.pool sonata_user_success sonata:user:create', 'SonataUserBundle @SonataIntl/foo Sonata\UserBundle\Model\User sonata_user: sonata.media.pool sonata_user_success sonata:user:create', 'docs/foo.rst'];
        yield 'a page block is ours even though a page bundle is theirs' => ['{% block sonata_page_content %} sonata_page: sonata_page_bundle', '{% block adminata_page_content %} sonata_page: sonata_page_bundle', 'src/Resources/views/x.html.twig'];
        yield 'the retired bundle names, and the fork and file names that keep the word' => ['SonataBlockBundle SonataFormBundle sonata-admin-mongodb-bundle sonata-doctrine-extensions CHANGELOG-sonata.md', 'SonataBlockBundle SonataFormBundle sonata-admin-mongodb-bundle sonata-doctrine-extensions CHANGELOG-sonata.md', 'README.md'];
        yield 'a sentence ending in sonata.' => ['forked from sonata. Then', 'forked from sonata. Then', 'docs/foo.rst'];
        yield 'binary content' => ["PNG\0Sonata\\AdminBundle", "PNG\0Sonata\\AdminBundle", 'docs/x.png'];
    }

    #[DataProvider('provideTreeModeRewritesCases')]
    public function testTreeModeRewrites(string $before, string $after, string $path): void
    {
        $engine = self::engine(Engine::MODE_TREE);

        static::assertSame($after, $engine->rewrite($before, $path));
        static::assertSame($after, $engine->rewrite($after, $path), 'A second pass must change nothing.');
    }

    /**
     * @return iterable<string, array{string, string, 2?: bool}>
     */
    public static function providePathsFollowTheContentRulesCases(): iterable
    {
        yield 'bundle class' => ['src/SonataAdminBundle.php', 'src/AdminataBundle.php'];
        yield 'extension' => ['src/DependencyInjection/SonataBlockExtension.php', 'src/DependencyInjection/AdminataBlockExtension.php'];
        yield 'routing' => ['src/Resources/config/routing/sonata_admin.xml', 'src/Resources/config/routing/adminata.xml'];
        yield 'catalogue' => ['src/Resources/translations/SonataAdminBundle.pt_BR.xliff', 'src/Resources/translations/AdminataBundle.pt_BR.xliff'];
        yield 'form widget template' => ['src/Resources/views/Form/Type/sonata_type_model_list.html.twig', 'src/Resources/views/Form/Type/adminata_type_model_list.html.twig'];
        yield 'override directory' => ['templates/bundles/SonataAdminBundle/CRUD/list.html.twig', 'templates/bundles/AdminataBundle/CRUD/list.html.twig'];
        yield 'application config' => ['config/packages/sonata_admin.yaml', 'config/packages/adminata.yaml'];
        yield 'application routes' => ['config/routes/sonata_admin.yaml', 'config/routes/adminata.yaml'];
        yield 'storage layer config' => ['config/packages/sonata_doctrine_orm_admin.yaml', 'config/packages/adminata_doctrine_orm.yaml'];
        yield 'image' => ['docs/admin-bundle/images/getting_started_sonata_model_type.png', 'docs/admin-bundle/images/getting_started_adminata_model_type.png', false];
        yield 'the inherited changelog index keeps its name' => ['CHANGELOG-sonata.md', 'CHANGELOG-sonata.md'];
        yield 'a skipped file keeps its path' => ['upstream/exclude/sonata-foo.txt', 'upstream/exclude/sonata-foo.txt'];
    }

    /**
     * @param bool $inAnApplicationToo whether application mode must agree — a path only one of
     *                                 adminata's own trees has is not its business
     */
    #[DataProvider('providePathsFollowTheContentRulesCases')]
    public function testPathsFollowTheContentRules(string $before, string $after, bool $inAnApplicationToo = true): void
    {
        static::assertSame($after, self::engine(Engine::MODE_TREE)->rewritePath($before));

        if ($inAnApplicationToo) {
            static::assertSame($after, self::engine(Engine::MODE_APP)->rewritePath($before));
        }
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideAppModeRewritesOnlyKnownNamesCases(): iterable
    {
        yield 'adminata\'s names change' => ["use Sonata\AdminBundle\Admin\AbstractAdmin; tags: [{ name: sonata.admin, manager_type: orm }] '@SonataAdmin/CRUD/list.html.twig' sonata.admin.pool sonata_type_model sonata_flash_success '_sonata_admin' sonata-ba-list-field data-sonata-modal-target sonata-modal:opened 'SonataAdminBundle' ROLE_SUPER_ADMIN", "use IDCT\Adminata\Admin\AbstractAdmin; tags: [{ name: adminata.admin, manager_type: orm }] '@Adminata/CRUD/list.html.twig' adminata.admin.pool adminata_type_model adminata_flash_success '_adminata_admin' adminata-list-field data-adminata-modal-target adminata-modal:opened 'AdminataBundle' ROLE_SUPER_ADMIN", 'src/Admin/FooAdmin.php'];
        yield 'the application\'s own names stay' => ['sonata.admin.user.partner sonata_admin_edit_own_password class="sonata-overrides" ROLE_SONATA_ADMIN_USER_PARTNER_EDIT SonataBlockBundle::class', 'sonata.admin.user.partner sonata_admin_edit_own_password class="sonata-overrides" ROLE_SONATA_ADMIN_USER_PARTNER_EDIT SonataBlockBundle::class', 'config/services.yaml'];
        yield 'the root is unambiguous even here' => ["sonata_admin:\n    security:\n        handler: sonata.admin.security.handler.role\n", "adminata:\n    security:\n        handler: adminata.admin.security.handler.role\n", 'config/packages/sonata_admin.yaml'];
        yield 'a Stimulus value an application derives from an identifier' => ['data-sonata-autocomplete-per-page-value="5" data-sonata-readmore-more-text-value="more"', 'data-adminata-autocomplete-per-page-value="5" data-adminata-readmore-more-text-value="more"', 'templates/foo.html.twig'];
    }

    #[DataProvider('provideAppModeRewritesOnlyKnownNamesCases')]
    public function testAppModeRewritesOnlyKnownNames(string $before, string $after, string $path): void
    {
        static::assertSame($after, self::engine(Engine::MODE_APP)->rewrite($before, $path));
    }

    public function testAppModeReportsWhatItLeaves(): void
    {
        $text = "sonata.admin.user.partner\nsonata.admin.pool\nSonataBlockBundle::class\nsonata-project/admin-bundle\n";
        $left = self::engine(Engine::MODE_APP)->leftovers($text, 'config/services.yaml');

        static::assertSame([1, 2, 3], array_column($left, 'line'), 'The application\'s own id, the rewrite still pending, the retired bundle.');
        static::assertSame([false, false, true], array_column($left, 'retired'));
    }

    public function testTreeModeReportsAnyRemainingName(): void
    {
        $engine = self::engine(Engine::MODE_TREE);

        static::assertSame([], $engine->leftovers("use IDCT\\Adminata\\Admin\\AbstractAdmin;\n// (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>\n", 'src/Foo.php'));
        static::assertSame([1, 2], array_column($engine->leftovers("use Sonata\\AdminBundle\\Admin\\AbstractAdmin;\n\"sonata\"\n", 'src/Foo.php'), 'line'));
    }

    public function testTheKnownListIsWhatTheTreesSay(): void
    {
        $known = Engine::readKnown(\dirname(__DIR__, 3).'/upstream/rename/known.txt');
        $tokens = array_column($known, 'token');

        foreach (['SonataAdminBundle', 'sonata.admin', 'sonata.admin.pool', 'sonata_type_model', 'sonata_theme', 'sonata-modal', 'sonata-ba-list-field', 'sonataApplication'] as $token) {
            static::assertContains($token, $tokens);
        }

        foreach ($known as $entry) {
            static::assertNotSame($entry['token'], self::engine(Engine::MODE_TREE)->translate($entry['token']), $entry['token'].' has no new name');
        }
    }

    private static function engine(string $mode): Engine
    {
        return Engine::fromDirectory(\dirname(__DIR__, 3).'/upstream/rename', $mode);
    }
}
