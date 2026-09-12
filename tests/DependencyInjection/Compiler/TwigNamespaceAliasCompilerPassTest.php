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

use IDCT\Adminata\DependencyInjection\Compiler\TwigNamespaceAliasCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class TwigNamespaceAliasCompilerPassTest extends TestCase
{
    /**
     * All three aliases must reach the loader. A twig.paths map cannot carry them, because it is
     * keyed by directory and these three name the same one.
     */
    public function testItAddsEveryAliasToTheFilesystemLoader(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('twig.loader.native_filesystem', new Definition());

        new TwigNamespaceAliasCompilerPass()->process($container);

        $views = \dirname(__DIR__, 3).'/src/Resources/views';
        $calls = $container->getDefinition('twig.loader.native_filesystem')->getMethodCalls();

        static::assertSame([
            ['addPath', [$views, 'AdminataBlock']],
            ['addPath', [$views, 'AdminataForm']],
            ['addPath', [$views, 'AdminataTwig']],
        ], $calls);
    }

    public function testItDoesNothingWithoutTheTwigBundle(): void
    {
        $container = new ContainerBuilder();

        new TwigNamespaceAliasCompilerPass()->process($container);

        static::assertFalse($container->hasDefinition('twig.loader.native_filesystem'));
    }
}
