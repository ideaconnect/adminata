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

namespace IDCT\Adminata\Tests\Twig\Extension;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Tests\Admin\NextMajorAdminInterface;
use IDCT\Adminata\Twig\Extension\GroupExtension;
use IDCT\Adminata\Twig\GroupRuntime;
use Symfony\Component\DependencyInjection\Container;

/**
 * NEXT_MAJOR: Remove this test.
 */
#[IgnoreDeprecations]
final class GroupExtensionTest extends TestCase
{
    public function testGetDashboardGroupsWithCreatableAdmins(): void
    {
        $container = new Container();
        $pool = new Pool($container, ['adminata_admin_non_creatable', 'adminata_admin_creatable'], [
            'group_without_creatable' => [
                'label' => 'non_creatable',
                'translation_domain' => 'default',
                'icon' => 'icon1',
                'items' => [
                    [
                        'admin' => 'adminata_admin_non_creatable',
                        'label' => 'admin1',
                        'roles' => [],
                        'route' => 'foo',
                        'route_params' => [],
                        'route_absolute' => false,
                    ],
                ],
                'keep_open' => false,
                'on_top' => false,
                'roles' => [],
            ],
            'group_with_creatable' => [
                'label' => 'creatable',
                'translation_domain' => 'default',
                'icon' => 'icon2',
                'items' => [
                    [
                        'admin' => 'adminata_admin_creatable',
                        'label' => 'admin1',
                        'roles' => [],
                        'route' => 'foo',
                        'route_params' => [],
                        'route_absolute' => false,
                    ],
                ],
                'keep_open' => false,
                'on_top' => false,
                'roles' => [],
            ],
        ]);
        $twigExtension = new GroupExtension(new GroupRuntime($pool));

        // NEXT_MAJOR: Use createMock instead.
        $adminNonCreatable = $this->createMock(AdminInterface::class);
        $adminCreatable = $this->createMock(NextMajorAdminInterface::class);

        $container->set('adminata_admin_non_creatable', $adminNonCreatable);
        $container->set('adminata_admin_creatable', $adminCreatable);

        $adminCreatable
            ->method('showInDashboard')
            ->willReturn(true);

        $adminCreatable
            ->expects(static::any())->method('hasRoute')
            ->with('create')
            ->willReturn(true);

        $adminCreatable
            ->expects(static::any())->method('hasAccess')
            ->with('create')
            ->willReturn(true);

        $adminNonCreatable
            ->expects(static::any())->method('hasAccess')
            ->with('create')
            ->willReturn(false);

        static::assertSame([
            [
                'items' => [
                    $adminCreatable,
                ],
                'label' => 'creatable',
                'translation_domain' => 'default',
                'icon' => 'icon2',
                'keep_open' => false,
                'on_top' => false,
                'roles' => [],
            ],
        ], $twigExtension->getDashboardGroupsWithCreatableAdmins());
    }
}
