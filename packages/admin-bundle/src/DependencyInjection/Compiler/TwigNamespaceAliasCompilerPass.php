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

namespace Sonata\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Points "@SonataBlock", "@SonataForm" and "@SonataTwig" at this bundle's Resources/views.
 *
 * The block, form and twig packages are part of this bundle, so their templates live beside the
 * admin bundle's own and adminata addresses every one of them as "@SonataAdmin/...", which an
 * application overrides under templates/bundles/SonataAdminBundle/. The three older namespaces are
 * kept for templates outside adminata that still address them.
 *
 * Registering them here rather than through twig.paths is deliberate: that configuration node is a
 * map keyed by directory holding one namespace per key, so three prepends naming the same directory
 * would collapse into whichever merged last, silently dropping two of the aliases.
 *
 * @internal
 */
final class TwigNamespaceAliasCompilerPass implements CompilerPassInterface
{
    private const array ALIASES = ['SonataBlock', 'SonataForm', 'SonataTwig'];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('twig.loader.native_filesystem')) {
            return;
        }

        $definition = $container->getDefinition('twig.loader.native_filesystem');
        $views = \dirname(__DIR__, 2).'/Resources/views';

        foreach (self::ALIASES as $alias) {
            $definition->addMethodCall('addPath', [$views, $alias]);
        }
    }
}
