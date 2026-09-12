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

namespace IDCT\Adminata;

use IDCT\Adminata\DependencyInjection\AdminataBlockExtension;
use IDCT\Adminata\DependencyInjection\AdminataExporterExtension;
use IDCT\Adminata\DependencyInjection\AdminataFormExtension;
use IDCT\Adminata\DependencyInjection\AdminataTwigExtension;
use IDCT\Adminata\DependencyInjection\Compiler\AddAuditReadersCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\AddDependencyCallsCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\AddFilterTypeCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\AdminAddInitializeCallCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\AdminMakerCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\AdminSearchCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\BlockGlobalVariablesCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\BlockTweakCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\DoctrineAdapterCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\DoctrineMapperCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\ExporterCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\ExtensionCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\GlobalVariablesCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\ModelManagerCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\ObjectAclManipulatorCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\TwigNamespaceAliasCompilerPass;
use IDCT\Adminata\DependencyInjection\Compiler\TwigStringExtensionCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AdminataBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        // The block, form, twig and exporter stacks' extensions and compiler passes live in this
        // bundle. Extensions registered from build() are collected by Kernel::prepareContainer()
        // before it builds the MergeExtensionConfigurationPass, so the "adminata_block",
        // "adminata_form", "adminata_twig" and "adminata_exporter" roots are loaded and their prepend()
        // is called just like the extension of a bundle of its own.
        $container->registerExtension(new AdminataBlockExtension());
        $container->registerExtension(new AdminataFormExtension());
        $container->registerExtension(new AdminataTwigExtension());
        $container->registerExtension(new AdminataExporterExtension());

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

        // The exporter stack's compiler pass, which hands "adminata.exporter.exporter" every service
        // tagged "adminata.exporter.writer".
        $container->addCompilerPass(new ExporterCompilerPass());

        // The Doctrine stack's compiler passes. They drop the ORM adapter and the metadata mapper
        // again when Doctrine ORM is not part of the container, which is what a panel running the
        // MongoDB ODM alone looks like now that the ORM admin bundle ships separately.
        $container->addCompilerPass(new DoctrineAdapterCompilerPass());
        $container->addCompilerPass(new DoctrineMapperCompilerPass());
    }
}
