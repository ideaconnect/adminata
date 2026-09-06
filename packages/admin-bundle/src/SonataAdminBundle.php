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

namespace Sonata\AdminBundle;

use Sonata\AdminBundle\DependencyInjection\Compiler\AddAuditReadersCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\AddDependencyCallsCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\AddFilterTypeCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\AdminAddInitializeCallCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\AdminMakerCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\AdminSearchCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\BlockGlobalVariablesCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\BlockTweakCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\ExporterCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\ExtensionCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\GlobalVariablesCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\ModelManagerCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\ObjectAclManipulatorCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\TwigNamespaceAliasCompilerPass;
use Sonata\AdminBundle\DependencyInjection\Compiler\TwigStringExtensionCompilerPass;
use Sonata\AdminBundle\DependencyInjection\SonataBlockExtension;
use Sonata\AdminBundle\DependencyInjection\SonataExporterExtension;
use Sonata\AdminBundle\DependencyInjection\SonataFormExtension;
use Sonata\AdminBundle\DependencyInjection\SonataTwigExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SonataAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        // The block, form, twig and exporter stacks' extensions and compiler passes live in this
        // bundle. Extensions registered from build() are collected by Kernel::prepareContainer()
        // before it builds the MergeExtensionConfigurationPass, so the "sonata_block",
        // "sonata_form", "sonata_twig" and "sonata_exporter" roots are loaded and their prepend()
        // is called just like the extension of a bundle of its own.
        $container->registerExtension(new SonataBlockExtension());
        $container->registerExtension(new SonataFormExtension());
        $container->registerExtension(new SonataTwigExtension());
        $container->registerExtension(new SonataExporterExtension());

        $container->addCompilerPass(new AddDependencyCallsCompilerPass());
        $container->addCompilerPass(new AddFilterTypeCompilerPass());
        $container->addCompilerPass(new AdminSearchCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
        $container->addCompilerPass(new ExtensionCompilerPass());
        $container->addCompilerPass(new GlobalVariablesCompilerPass());
        $container->addCompilerPass(new ModelManagerCompilerPass());
        $container->addCompilerPass(new ObjectAclManipulatorCompilerPass());
        $container->addCompilerPass(new TwigStringExtensionCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
        $container->addCompilerPass(new AdminMakerCompilerPass());
        $container->addCompilerPass(new AddAuditReadersCompilerPass());
        $container->addCompilerPass(new AdminAddInitializeCallCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING, -100);

        // The block stack's compiler passes.
        $container->addCompilerPass(new TwigNamespaceAliasCompilerPass());
        $container->addCompilerPass(new BlockTweakCompilerPass());
        $container->addCompilerPass(new BlockGlobalVariablesCompilerPass());

        // The exporter stack's compiler pass, which hands "sonata.exporter.exporter" every service
        // tagged "sonata.exporter.writer".
        $container->addCompilerPass(new ExporterCompilerPass());
    }
}
