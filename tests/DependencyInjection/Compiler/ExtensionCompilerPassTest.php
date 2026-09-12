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

namespace IDCT\Adminata\Tests\DependencyInjection\Compiler;

use IDCT\Adminata\Admin\AbstractAdmin;
use IDCT\Adminata\Admin\AdminExtensionInterface;
use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\DependencyInjection\Admin\TaggedAdminInterface;
use IDCT\Adminata\DependencyInjection\AdminataExporterExtension;
use IDCT\Adminata\DependencyInjection\AdminataExtension;
use IDCT\Adminata\DependencyInjection\Compiler\ExtensionCompilerPass;
use Knp\Menu\FactoryInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[CoversMethod(AdminataExtension::class, 'load')]
#[CoversMethod(ExtensionCompilerPass::class, 'flattenExtensionConfiguration')]
#[CoversMethod(ExtensionCompilerPass::class, 'process')]
final class ExtensionCompilerPassTest extends TestCase
{
    private AdminataExtension $extension;

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * Root name of the configuration.
     */
    private string $root;

    protected function setUp(): void
    {
        $this->extension = new AdminataExtension();
        $this->config = $this->getConfig();
        $this->root = TaggedAdminInterface::ADMIN_TAG;
    }

    public function testAdminExtensionLoad(): void
    {
        $this->extension->load([], $container = $this->getContainer());

        static::assertTrue($container->hasParameter(\sprintf('%s.extension.map', $this->root)));
        static::assertIsArray($extensionMap = $container->getParameter(\sprintf('%s.extension.map', $this->root)));

        static::assertSame([], $extensionMap);
    }

    public function testFlattenEmptyExtensionConfiguration(): void
    {
        $this->extension->load([], $container = $this->getContainer());
        $extensionMap = $container->getParameter(\sprintf('%s.extension.map', $this->root));

        $method = new \ReflectionMethod(
            ExtensionCompilerPass::class,
            'flattenExtensionConfiguration'
        );
        $extensionMap = $method->invokeArgs(new ExtensionCompilerPass(), [$extensionMap]);

        static::assertIsArray($extensionMap);
        static::assertArrayHasKey('admins', $extensionMap);
        static::assertArrayHasKey('excludes', $extensionMap);
        static::assertArrayHasKey('implements', $extensionMap);
        static::assertArrayHasKey('extends', $extensionMap);
        static::assertArrayHasKey('instanceof', $extensionMap);
        static::assertArrayHasKey('uses', $extensionMap);

        static::assertEmpty($extensionMap['global']);
        static::assertEmpty($extensionMap['admins']);
        static::assertEmpty($extensionMap['excludes']);
        static::assertEmpty($extensionMap['implements']);
        static::assertEmpty($extensionMap['extends']);
        static::assertEmpty($extensionMap['instanceof']);
        static::assertEmpty($extensionMap['uses']);
    }

    public function testFlattenExtensionConfiguration(): void
    {
        $config = $this->getConfig();
        $this->extension->load([$config], $container = $this->getContainer());
        $extensionMap = $container->getParameter(\sprintf('%s.extension.map', $this->root));

        $method = new \ReflectionMethod(
            ExtensionCompilerPass::class,
            'flattenExtensionConfiguration'
        );
        $extensionMap = $method->invokeArgs(new ExtensionCompilerPass(), [$extensionMap]);

        static::assertIsArray($extensionMap);

        // Admins
        static::assertArrayHasKey('admins', $extensionMap);
        static::assertCount(1, $extensionMap['admins']);

        static::assertCount(1, $extensionMap['admins']['adminata_post_admin']);
        static::assertArrayHasKey('adminata_extension_publish', $extensionMap['admins']['adminata_post_admin']);

        // Excludes
        static::assertArrayHasKey('excludes', $extensionMap);
        static::assertCount(2, $extensionMap['excludes']);

        static::assertArrayHasKey('adminata_article_admin', $extensionMap['excludes']);
        static::assertCount(1, $extensionMap['excludes']['adminata_article_admin']);
        static::assertArrayHasKey('adminata_extension_history', $extensionMap['excludes']['adminata_article_admin']);

        static::assertArrayHasKey('adminata_post_admin', $extensionMap['excludes']);
        static::assertCount(1, $extensionMap['excludes']['adminata_post_admin']);
        static::assertArrayHasKey('adminata_extension_order', $extensionMap['excludes']['adminata_post_admin']);

        // Implements
        static::assertArrayHasKey('implements', $extensionMap);
        static::assertCount(1, $extensionMap['implements']);

        static::assertArrayHasKey(Publishable::class, $extensionMap['implements']);
        static::assertCount(2, $extensionMap['implements'][Publishable::class]);
        static::assertArrayHasKey('adminata_extension_publish', $extensionMap['implements'][Publishable::class]);
        static::assertArrayHasKey('adminata_extension_order', $extensionMap['implements'][Publishable::class]);

        // Extends
        static::assertArrayHasKey('extends', $extensionMap);
        static::assertCount(1, $extensionMap['extends']);

        static::assertArrayHasKey(Post::class, $extensionMap['extends']);
        static::assertCount(1, $extensionMap['extends'][Post::class]);
        static::assertArrayHasKey('adminata_extension_order', $extensionMap['extends'][Post::class]);

        // Instanceof
        static::assertArrayHasKey('instanceof', $extensionMap);
        static::assertCount(1, $extensionMap['instanceof']);

        static::assertArrayHasKey(Post::class, $extensionMap['instanceof']);
        static::assertCount(1, $extensionMap['instanceof'][Post::class]);
        static::assertArrayHasKey('adminata_extension_history', $extensionMap['instanceof'][Post::class]);

        // Uses
        static::assertArrayHasKey('uses', $extensionMap);

        static::assertCount(1, $extensionMap['uses']);
        static::assertArrayHasKey(TimestampableTrait::class, $extensionMap['uses']);
        static::assertCount(1, $extensionMap['uses'][TimestampableTrait::class]);
        static::assertArrayHasKey('adminata_extension_post', $extensionMap['uses'][TimestampableTrait::class]);
    }

    public function testProcessWithInvalidExtensionId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $config = [
            'extensions' => [
                'adminata_extension_unknown' => [
                    'excludes' => ['adminata_article_admin'],
                    'instanceof' => [Post::class],
                ],
            ],
        ];

        $container = $this->getContainer();
        $this->extension->load([$config], $container);

        $extensionsPass = new ExtensionCompilerPass();
        $extensionsPass->process($container);
        $container->compile();
    }

    #[DoesNotPerformAssertions]
    public function testProcessWithInvalidAdminId(): void
    {
        $config = [
            'extensions' => [
                'adminata_extension_publish' => [
                    'admins' => ['adminata_unknown_admin'],
                    'implements' => [Publishable::class],
                ],
            ],
        ];

        $container = $this->getContainer();
        $this->extension->load([$config], $container);

        $extensionsPass = new ExtensionCompilerPass();
        $extensionsPass->process($container);
        $container->compile();

        // nothing should fail the extension just isn't added to the 'adminata_unknown_admin'
    }

    public function testProcess(): void
    {
        $container = $this->getContainer();
        $this->extension->load([$this->config], $container);

        $extensionsPass = new ExtensionCompilerPass();
        $extensionsPass->process($container);
        $container->compile();

        static::assertTrue($container->hasDefinition('adminata_extension_global'));
        static::assertTrue($container->hasDefinition('adminata_extension_publish'));
        static::assertTrue($container->hasDefinition('adminata_extension_history'));
        static::assertTrue($container->hasDefinition('adminata_extension_order'));
        static::assertTrue($container->hasDefinition('adminata_extension_security'));
        static::assertTrue($container->hasDefinition('adminata_extension_timestamp'));
        static::assertTrue($container->hasDefinition('adminata_extension_admin_publish'));
        static::assertTrue($container->hasDefinition('adminata_extension_admin_instanceof'));
        static::assertTrue($container->hasDefinition('adminata_extension_admin_extends'));
        static::assertTrue($container->hasDefinition('adminata_extension_admin_uses'));

        static::assertTrue($container->hasDefinition('adminata_post_admin'));
        static::assertTrue($container->hasDefinition('adminata_article_admin'));
        static::assertTrue($container->hasDefinition('adminata_news_admin'));
        static::assertTrue($container->hasDefinition('adminata_super_admin'));
        static::assertTrue($container->hasDefinition('adminata_timestampable_admin'));
        static::assertTrue($container->hasDefinition('adminata_publishable_admin'));

        $globalExtension = $container->get('adminata_extension_global');
        $securityExtension = $container->get('adminata_extension_security');
        $publishExtension = $container->get('adminata_extension_publish');
        $historyExtension = $container->get('adminata_extension_history');
        $orderExtension = $container->get('adminata_extension_order');
        $filterExtension = $container->get('adminata_extension_filter');
        $adminPublishExtension = $container->get('adminata_extension_admin_publish');
        $adminInstanceOfExtension = $container->get('adminata_extension_admin_instanceof');
        $adminExtendsExtension = $container->get('adminata_extension_admin_extends');
        $adminUsesExtension = $container->get('adminata_extension_admin_uses');

        $def = $container->get('adminata_post_admin');
        static::assertInstanceOf(AdminInterface::class, $def);

        $extensions = $def->getExtensions();
        static::assertCount(7, $extensions);

        static::assertSame($historyExtension, $extensions[0]);
        static::assertSame($adminInstanceOfExtension, $extensions[1]);
        static::assertSame($securityExtension, $extensions[2]);
        static::assertSame($publishExtension, $extensions[4]);
        static::assertSame($globalExtension, $extensions[6]);

        $def = $container->get('adminata_article_admin');
        static::assertInstanceOf(AdminInterface::class, $def);

        $extensions = $def->getExtensions();
        static::assertCount(8, $extensions);

        static::assertSame($filterExtension, $extensions[0]);
        static::assertSame($adminInstanceOfExtension, $extensions[1]);
        static::assertSame($securityExtension, $extensions[2]);
        static::assertSame($publishExtension, $extensions[3]);
        static::assertSame($orderExtension, $extensions[6]);
        static::assertSame($globalExtension, $extensions[7]);

        $def = $container->get('adminata_news_admin');
        static::assertInstanceOf(AdminInterface::class, $def);

        $extensions = $def->getExtensions();
        static::assertCount(8, $extensions);

        static::assertSame($historyExtension, $extensions[0]);
        static::assertSame($filterExtension, $extensions[1]);
        static::assertSame($adminInstanceOfExtension, $extensions[4]);
        static::assertSame($securityExtension, $extensions[3]);
        static::assertSame($orderExtension, $extensions[6]);
        static::assertSame($globalExtension, $extensions[7]);

        $def = $container->get('adminata_super_admin');
        static::assertInstanceOf(AdminInterface::class, $def);

        $extensions = $def->getExtensions();
        static::assertCount(5, $extensions);

        static::assertSame($adminInstanceOfExtension, $extensions[1]);
        static::assertSame($adminExtendsExtension, $extensions[2]);
        static::assertSame($globalExtension, $extensions[4]);

        $def = $container->get('adminata_timestampable_admin');
        static::assertInstanceOf(AdminInterface::class, $def);

        $extensions = $def->getExtensions();
        static::assertCount(5, $extensions);

        static::assertSame($adminUsesExtension, $extensions[1]);
        static::assertSame($filterExtension, $extensions[2]);
        static::assertSame($globalExtension, $extensions[4]);

        $def = $container->get('adminata_publishable_admin');
        static::assertInstanceOf(AdminInterface::class, $def);

        $extensions = $def->getExtensions();
        static::assertCount(4, $extensions);

        static::assertSame($adminPublishExtension, $extensions[1]);
        static::assertSame($globalExtension, $extensions[3]);
    }

    #[DoesNotPerformAssertions]
    public function testProcessThrowsExceptionIfTraitsAreNotAvailable(): void
    {
        $config = [
            'extensions' => [
                'adminata_extension_post' => [
                    'uses' => [TimestampableTrait::class],
                ],
            ],
        ];

        $container = $this->getContainer();
        $this->extension->load([$config], $container);

        $extensionsPass = new ExtensionCompilerPass();
        $extensionsPass->process($container);
        $container->compile();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfig(): array
    {
        return [
            'extensions' => [
                'adminata_extension_global' => [
                    'global' => true,
                    'priority' => -255,
                ],
                'adminata_extension_publish' => [
                    'admins' => ['adminata_post_admin'],
                    'implements' => [Publishable::class],
                ],
                'adminata_extension_history' => [
                    'excludes' => ['adminata_article_admin'],
                    'instanceof' => [Post::class],
                    'priority' => 255,
                ],
                'adminata_extension_order' => [
                    'excludes' => ['adminata_post_admin'],
                    'extends' => [Post::class],
                    'implements' => [Publishable::class],
                    'priority' => -128,
                ],
                'adminata_extension_post' => [
                    'uses' => [TimestampableTrait::class],
                ],
                'adminata_extension_admin_publish' => [
                    'admin_implements' => [Publishable::class],
                ],
                'adminata_extension_admin_instanceof' => [
                    'admin_instanceof' => [MockAdmin::class],
                ],
                'adminata_extension_admin_extends' => [
                    'admin_extends' => [MockAdmin::class],
                ],
                'adminata_extension_admin_uses' => [
                    'admin_uses' => [TimestampableTrait::class],
                ],
            ],
        ];
    }

    private function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', [
            'KnpMenuBundle' => true,
        ]);
        $container->setParameter('kernel.cache_dir', '/tmp');
        $container->setParameter('kernel.debug', true);

        // AdminataExtension wires "adminata.admin.admin_exporter" onto
        // "adminata.exporter.exporter", which the bundle's exporter extension defines.
        new AdminataExporterExtension()->load([], $container);

        // Add dependencies for AdminataBundle (these services will never get called so dummy classes will do)
        $container
            ->register('twig')
            ->setClass(Environment::class);
        $container
            ->register('translator')
            ->setClass(Translator::class);
        $container->setAlias(TranslatorInterface::class, 'translator');
        $container
            ->register('validator.validator_factory')
            ->setClass(ConstraintValidatorFactoryInterface::class);
        $container
            ->register('router')
            ->setClass(RouterInterface::class);
        $container
            ->register('property_accessor')
            ->setClass(PropertyAccessor::class);
        $container
            ->register('form.factory')
            ->setClass(FormFactoryInterface::class);
        $container
            ->register('validator')
            ->setClass(ValidatorInterface::class);
        $container
            ->register('knp_menu.factory')
            ->setClass(FactoryInterface::class);
        $container
            ->register('knp_menu.matcher')
            ->setClass(MatcherInterface::class);
        $container
            ->register('knp_menu.menu_provider')
            ->setClass(MenuProviderInterface::class);
        $container
            ->register('request_stack')
            ->setClass(RequestStack::class);
        $container
            ->register('session')
            ->setClass(Session::class);
        $container
            ->register('security.authorization_checker')
            ->setClass(AuthorizationCheckerInterface::class);
        $container
            ->register('controller_resolver')
            ->setClass(ControllerResolverInterface::class);
        $container
            ->register(HttpKernelInterface::class)
            ->setClass(HttpKernelInterface::class);

        // Add admin definition's
        $container
            ->register('adminata_post_admin')
            ->setPublic(true)
            ->setClass(MockAdmin::class)
            ->addTag(TaggedAdminInterface::ADMIN_TAG, ['model_class' => Post::class]);
        $container
            ->register('adminata_news_admin')
            ->setPublic(true)
            ->setClass(MockAdmin::class)
            ->addTag(TaggedAdminInterface::ADMIN_TAG, ['model_class' => News::class]);
        $container
            ->register('adminata_article_admin')
            ->setPublic(true)
            ->setClass(MockAdmin::class)
            ->addTag(TaggedAdminInterface::ADMIN_TAG, ['model_class' => Article::class]);
        $container
            ->register('adminata_super_admin')
            ->setPublic(true)
            ->setClass(SuperMockAdmin::class)
            ->addTag(TaggedAdminInterface::ADMIN_TAG, ['model_class' => \stdClass::class]);
        $container
            ->register('adminata_timestampable_admin')
            ->setPublic(true)
            ->setClass(TimestampableAdmin::class)
            ->addTag(TaggedAdminInterface::ADMIN_TAG, ['model_class' => \stdClass::class]);
        $container
            ->register('adminata_publishable_admin')
            ->setPublic(true)
            ->setClass(PublishableAdmin::class)
            ->addTag(TaggedAdminInterface::ADMIN_TAG, ['model_class' => \stdClass::class]);
        $container
            ->register('event_dispatcher')
            ->setClass(EventDispatcher::class);

        // Add admin extension definition's
        $extensionClass = $this->createMock(AdminExtensionInterface::class)::class;

        $container
            ->register('adminata_extension_global')
            ->setPublic(true)
            ->setClass($extensionClass);
        $container
            ->register('adminata_extension_publish')
            ->setPublic(true)
            ->setClass($extensionClass);
        $container
            ->register('adminata_extension_history')
            ->setPublic(true)
            ->setClass($extensionClass);
        $container
            ->register('adminata_extension_order')
            ->setPublic(true)
            ->setClass($extensionClass);
        $container
            ->register('adminata_extension_post')
            ->setPublic(true)
            ->setClass($extensionClass);
        $container
            ->register('adminata_extension_timestamp')
            ->setPublic(true)
            ->setClass($extensionClass);
        $container
            ->register('adminata_extension_admin_publish')
            ->setPublic(true)
            ->setClass($extensionClass);
        $container
            ->register('adminata_extension_admin_instanceof')
            ->setPublic(true)
            ->setClass($extensionClass);
        $container
            ->register('adminata_extension_admin_extends')
            ->setPublic(true)
            ->setClass($extensionClass);
        $container
            ->register('adminata_extension_admin_uses')
            ->setPublic(true)
            ->setClass($extensionClass);
        $container
            ->register('adminata_extension_security')
            ->setPublic(true)
            ->setClass($extensionClass)
            ->addTag('adminata.admin.extension', ['global' => true]);
        $container
            ->register('adminata_extension_filter')
            ->setPublic(true)
            ->setClass($extensionClass)
            ->addTag('adminata.admin.extension', ['global' => false])
            ->addTag('adminata.admin.extension', ['target' => 'adminata_news_admin', 'priority' => 10])
            ->addTag('adminata.admin.extension', ['target' => 'adminata_article_admin'])
            ->addTag('adminata.admin.extension', ['implements' => Publishable::class])
            ->addTag('adminata.admin.extension', ['admin_uses' => TimestampableTrait::class]);

        // Add definitions for adminata.templating service
        $container
            ->register('kernel')
            ->setClass(KernelInterface::class);
        $container
            ->register('file_locator')
            ->setClass(FileLocatorInterface::class);

        return $container;
    }
}

trait TimestampableTrait
{
}
class Post
{
    use TimestampableTrait;
}
interface Publishable
{
}
final class News extends Post
{
}
final class Article implements Publishable
{
}
/** @phpstan-extends AbstractAdmin<object> */
class MockAdmin extends AbstractAdmin
{
}
final class SuperMockAdmin extends MockAdmin
{
}
/** @phpstan-extends AbstractAdmin<object> */
final class TimestampableAdmin extends AbstractAdmin
{
    use TimestampableTrait;
}
/** @phpstan-extends AbstractAdmin<object> */
final class PublishableAdmin extends AbstractAdmin implements Publishable
{
}
