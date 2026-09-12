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

use PHPUnit\Framework\TestCase;
use IDCT\Adminata\DependencyInjection\Admin\TaggedAdminInterface;
use IDCT\Adminata\DependencyInjection\Compiler\AdminAddInitializeCallCompilerPass;
use IDCT\Adminata\Tests\App\Admin\FooAdmin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AdminAddInitializeCallCompilerPassTest extends TestCase
{
    public function testProcess(): void
    {
        $builder = new ContainerBuilder();
        $builder->register('foo', FooAdmin::class)
            ->addTag(TaggedAdminInterface::ADMIN_TAG);

        new AdminAddInitializeCallCompilerPass()->process($builder);

        static::assertSame([['initialize', []]], $builder->getDefinition('foo')->getMethodCalls());
    }
}
