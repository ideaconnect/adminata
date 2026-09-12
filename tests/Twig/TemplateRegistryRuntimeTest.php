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

use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Exception\AdminCodeNotFoundException;
use IDCT\Adminata\Templating\MutableTemplateRegistryInterface;
use IDCT\Adminata\Templating\TemplateRegistryInterface;
use IDCT\Adminata\Twig\TemplateRegistryRuntime;
use Symfony\Component\DependencyInjection\Container;

final class TemplateRegistryRuntimeTest extends TestCase
{
    private TemplateRegistryRuntime $templateRegistryRuntime;

    protected function setUp(): void
    {
        $templateRegistry = $this->createMock(TemplateRegistryInterface::class);
        $templateRegistry->expects(static::any())->method('getTemplate')->with('edit')->willReturn('@Adminata/CRUD/edit.html.twig');

        $adminTemplateRegistry = $this->createMock(MutableTemplateRegistryInterface::class);
        $adminTemplateRegistry->expects(static::any())->method('getTemplate')->with('edit')->willReturn('@Adminata/CRUD/edit.html.twig');

        $admin = static::createStub(AdminInterface::class);
        $admin
            ->method('getTemplateRegistry')
            ->willReturn($adminTemplateRegistry);

        $container = new Container();
        $container->set('admin.post', $admin);
        $pool = new Pool($container, ['admin.post']);

        $this->templateRegistryRuntime = new TemplateRegistryRuntime(
            $templateRegistry,
            $pool
        );
    }

    public function testGetAdminTemplate(): void
    {
        static::assertSame(
            '@Adminata/CRUD/edit.html.twig',
            $this->templateRegistryRuntime->getAdminTemplate('edit', 'admin.post')
        );
    }

    public function testGetAdminTemplateFailure(): void
    {
        $this->expectException(AdminCodeNotFoundException::class);

        $this->expectExceptionMessage('Admin service "admin.non-existing" not found in admin pool. Did you mean "admin.post" or one of those: []?');

        static::assertSame(
            '@Adminata/CRUD/edit.html.twig',
            $this->templateRegistryRuntime->getAdminTemplate('edit', 'admin.non-existing')
        );
    }

    public function testGetGlobalTemplate(): void
    {
        static::assertSame(
            '@Adminata/CRUD/edit.html.twig',
            $this->templateRegistryRuntime->getGlobalTemplate('edit')
        );
    }
}
