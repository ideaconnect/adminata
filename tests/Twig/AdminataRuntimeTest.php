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

namespace IDCT\Adminata\Tests\Twig;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Tests\App\Model\Foo;
use IDCT\Adminata\Tests\Twig\Extension\FakeTemplateRegistryExtension;
use IDCT\Adminata\Twig\Extension\AdminataExtension;
use IDCT\Adminata\Twig\AdminataRuntime;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Twig\Environment;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;

final class AdminataRuntimeTest extends TestCase
{
    private AdminataRuntime $adminataAdminRuntime;

    private Environment $environment;

    /**
     * @var AdminInterface<\stdClass>&MockObject
     */
    private AdminInterface $admin;

    /**
     * @var AdminInterface<\stdClass>&MockObject
     */
    private AdminInterface $adminBar;

    private \stdClass $object;

    private Pool $pool;

    private Container $container;

    protected function setUp(): void
    {
        date_default_timezone_set('Europe/London');

        $this->container = new Container();

        $this->pool = new Pool($this->container, ['adminata_admin_foo_service'], [], [Foo::class => ['adminata_admin_foo_service']]);

        $this->adminataAdminRuntime = new AdminataRuntime($this->pool);

        $loader = new FilesystemLoader([
            __DIR__.'/../../src/Resources/views/CRUD',
            __DIR__.'/../Fixtures/Resources/views/CRUD',
        ]);
        $loader->addPath(__DIR__.'/../../src/Resources/views/', 'Adminata');
        $loader->addPath(__DIR__.'/../Fixtures/Resources/views/', 'App');

        $this->environment = new Environment($loader, [
            'strict_variables' => true,
            'cache' => false,
            'autoescape' => 'html',
            'optimizations' => 0,
        ]);
        $this->environment->addExtension(new AdminataExtension($this->adminataAdminRuntime));
        $this->environment->addExtension(new FakeTemplateRegistryExtension());

        // routing extension
        $phpFileLoader = new PhpFileLoader(new FileLocator([\sprintf('%s/../../src/Resources/config/routing', __DIR__)]));
        $routeCollection = $phpFileLoader->load('adminata.php');

        $phpFileLoader = new PhpFileLoader(new FileLocator([\sprintf('%s/../Fixtures/Resources/config/routing', __DIR__)]));
        $testRouteCollection = $phpFileLoader->load('routing.php');

        $routeCollection->addCollection($testRouteCollection);
        $this->environment->addExtension(new StringExtension());

        // initialize object
        $this->object = new \stdClass();

        // initialize admin
        $this->admin = $this->createMock(AdminInterface::class);

        $this->admin
            ->method('getCode')
            ->willReturn('adminata_admin_foo_service');

        $this->admin
            ->expects(static::any())->method('id')
            ->with(static::equalTo($this->object))
            ->willReturn('12345');

        $this->admin
            ->expects(static::any())->method('getNormalizedIdentifier')
            ->with(static::equalTo($this->object))
            ->willReturn('12345');

        $this->adminBar = $this->createMock(AdminInterface::class);
        $this->adminBar
            ->method('hasAccess')
            ->willReturn(true);
        $this->adminBar
            ->expects(static::any())->method('getNormalizedIdentifier')
            ->with(static::equalTo($this->object))
            ->willReturn('12345');

        $this->container->set('adminata_admin_foo_service', $this->admin);
        $this->container->set('adminata_admin_bar_service', $this->adminBar);
    }

    public function testGetUrlsafeIdentifier(): void
    {
        $model = new \stdClass();

        $pool = new Pool(
            $this->container,
            ['adminata_admin_foo_service'],
            [],
            [\stdClass::class => ['adminata_admin_foo_service']]
        );

        $this->admin->expects(static::once())
            ->method('getUrlSafeIdentifier')
            ->with(static::equalTo($model))
            ->willReturn('1234567');

        $this->container->set('adminata_admin_foo_service', $this->admin);

        $adminataAdminRuntime = new AdminataRuntime($pool);

        static::assertSame('1234567', $adminataAdminRuntime->getUrlSafeIdentifier($model));
    }

    public function testGetUrlsafeIdentifierGivenAdminFoo(): void
    {
        $model = new \stdClass();

        $pool = new Pool(
            $this->container,
            [
                'adminata_admin_foo_service',
                'adminata_admin_bar_service',
            ],
            [],
            [\stdClass::class => [
                'adminata_admin_foo_service',
                'adminata_admin_bar_service',
            ]]
        );

        $this->admin->expects(static::once())
            ->method('getUrlSafeIdentifier')
            ->with(static::equalTo($model))
            ->willReturn('1234567');

        $this->adminBar->expects(static::never())
            ->method('getUrlSafeIdentifier');

        $adminataAdminRuntime = new AdminataRuntime($pool);

        static::assertSame('1234567', $adminataAdminRuntime->getUrlSafeIdentifier($model, $this->admin));
    }

    public function testGetUrlsafeIdentifierGivenAdminBar(): void
    {
        $model = new \stdClass();

        $pool = new Pool(
            $this->container,
            ['adminata_admin_foo_service', 'adminata_admin_bar_service'],
            [],
            [\stdClass::class => [
                'adminata_admin_foo_service',
                'adminata_admin_bar_service',
            ]]
        );

        $this->admin->expects(static::never())
            ->method('getUrlSafeIdentifier');

        $this->adminBar->expects(static::once())
            ->method('getUrlSafeIdentifier')
            ->with(static::equalTo($model))
            ->willReturn('1234567');

        $adminataAdminRuntime = new AdminataRuntime($pool);

        static::assertSame('1234567', $adminataAdminRuntime->getUrlSafeIdentifier($model, $this->adminBar));
    }
}
