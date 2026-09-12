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

use IDCT\Adminata\DependencyInjection\Admin\TaggedAdminInterface;
use IDCT\Adminata\DependencyInjection\Compiler\AdminSearchCompilerPass;
use IDCT\Adminata\Tests\Fixtures\Admin\PostAdmin;
use IDCT\Adminata\Tests\Fixtures\Bundle\Entity\Post;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
final class AdminSearchCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testProcess(): void
    {
        $adminFooDefinition = new Definition(PostAdmin::class);
        $adminFooDefinition->addTag(TaggedAdminInterface::ADMIN_TAG, [
            'code' => 'admin_foo_code',
            'model_class' => Post::class,
            'global_search' => true,
        ]);
        $this->setDefinition('admin.foo', $adminFooDefinition);

        $adminBarDefinition = new Definition(PostAdmin::class);
        $adminBarDefinition->addTag(TaggedAdminInterface::ADMIN_TAG, [
            'code' => 'admin_bar_code',
            'model_class' => Post::class,
            'global_search' => false,
        ]);
        $this->setDefinition('admin.bar', $adminBarDefinition);

        $adminBazDefinition = new Definition(PostAdmin::class);
        $adminBazDefinition->addTag(TaggedAdminInterface::ADMIN_TAG, [
            'code' => 'admin_baz_code',
            'model_class' => Post::class,
            'some_attribute' => 42,
        ]);
        $this->setDefinition('admin.baz', $adminBazDefinition);

        $searchHandlerDefinition = new Definition();
        $this->setDefinition('adminata.admin.search.handler', $searchHandlerDefinition);

        $this->compile();

        self::assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'adminata.admin.search.handler',
            'configureAdminSearch',
            [['admin_foo_code' => true, 'admin_bar_code' => false]]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AdminSearchCompilerPass());
    }
}
