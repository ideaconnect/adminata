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

namespace IDCT\Adminata\Tests\ArgumentResolver;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\ArgumentResolver\AdminValueResolver;
use IDCT\Adminata\Request\AdminFetcher;
use IDCT\Adminata\Tests\Fixtures\Admin\CommentAdmin;
use IDCT\Adminata\Tests\Fixtures\Admin\PostAdmin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class AdminValueResolverTest extends TestCase
{
    #[DataProvider('provideWithInvalidDataCases')]
    public function testWithInvalidData(Request $request, ArgumentMetadata $argumentMetadata): void
    {
        $admin = new PostAdmin();
        $admin->setCode('adminata.admin.post');

        $container = new Container();
        $container->set('adminata.admin.post', $admin);

        $adminFetcher = new AdminFetcher(new Pool($container, ['adminata.admin.post']));
        $adminValueResolver = new AdminValueResolver($adminFetcher);

        static::assertFalse($adminValueResolver->supports($request, $argumentMetadata));
        static::assertSame(
            [],
            $adminValueResolver->resolve($request, $argumentMetadata)
        );
    }

    /**
     * @phpstan-return iterable<array-key, array{Request, ArgumentMetadata}>
     */
    public static function provideWithInvalidDataCases(): iterable
    {
        yield 'Object with no type' => [
            static::createRequest(),
            static::createArgumentMetadata('_adminata_admin'),
        ];

        yield 'Object must implement AdminInterface' => [
            static::createRequest(),
            static::createArgumentMetadata('_adminata_admin', self::class),
        ];

        yield 'Admin code must be passed' => [
            static::createRequest(),
            static::createArgumentMetadata('_adminata_admin', PostAdmin::class),
        ];

        yield 'Admin code must exist' => [
            static::createRequest(['_adminata_admin' => 'non_existing']),
            static::createArgumentMetadata('_adminata_admin', PostAdmin::class),
        ];

        yield 'Admin fetched must be of the type specified in the action' => [
            static::createRequest(['_adminata_admin' => 'adminata.admin.post']),
            static::createArgumentMetadata('_adminata_admin', CommentAdmin::class),
        ];
    }

    public function testResolvesAdminClass(): void
    {
        $admin = new PostAdmin();
        $admin->setCode('adminata.admin.post');

        $container = new Container();
        $container->set('adminata.admin.post', $admin);

        $adminFetcher = new AdminFetcher(new Pool($container, ['adminata.admin.post']));
        $adminValueResolver = new AdminValueResolver($adminFetcher);

        $request = static::createRequest(['_adminata_admin' => 'adminata.admin.post']);
        $argumentMetadata = static::createArgumentMetadata('_adminata_admin', PostAdmin::class);

        static::assertTrue($adminValueResolver->supports($request, $argumentMetadata));
        static::assertSame(
            [$admin],
            $adminValueResolver->resolve($request, $argumentMetadata)
        );
    }

    public function testResolvesAdminInterface(): void
    {
        $admin = new PostAdmin();
        $admin->setCode('adminata.admin.post');

        $container = new Container();
        $container->set('adminata.admin.post', $admin);

        $adminFetcher = new AdminFetcher(new Pool($container, ['adminata.admin.post']));
        $adminValueResolver = new AdminValueResolver($adminFetcher);

        $request = static::createRequest(['_adminata_admin' => 'adminata.admin.post']);
        $argumentMetadata = static::createArgumentMetadata('_adminata_admin', AdminInterface::class);

        static::assertTrue($adminValueResolver->supports($request, $argumentMetadata));
        static::assertSame(
            [$admin],
            $adminValueResolver->resolve($request, $argumentMetadata)
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private static function createRequest(array $attributes = []): Request
    {
        return new Request([], [], $attributes);
    }

    private static function createArgumentMetadata(string $name, ?string $type = null): ArgumentMetadata
    {
        return new ArgumentMetadata($name, $type, false, false, null);
    }
}
