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

namespace IDCT\Adminata\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Link the block service to the Page Manager.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class BlockTweakCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $manager = $container->getDefinition('adminata.block.manager');
        $registry = $container->getDefinition('adminata.block.menu.registry');

        /** @var array<string, mixed> $blocks */
        $blocks = $container->getParameter('adminata_block.blocks');
        /** @var array<string, mixed> $blockTypes */
        $blockTypes = $container->getParameter('adminata_blocks.block_types');
        /** @var string[] $defaultContexts */
        $defaultContexts = $container->getParameter('adminata_blocks.default_contexts');

        /** @var array<string, Reference> $blockServiceReferences */
        $blockServiceReferences = [];
        foreach ($container->findTaggedServiceIds('adminata.block') as $id => $tags) {
            $settings = $this->createBlockSettings($tags, $defaultContexts);

            // Register blocks dynamically
            if (!\array_key_exists($id, $blocks)) {
                $blocks[$id] = $settings;
            }
            if (!\in_array($id, $blockTypes, true)) {
                $blockTypes[] = $id;
            }

            $manager->addMethodCall('add', [$id, $id, $settings['contexts']]);

            $blockServiceReferences[$id] = new Reference($id);
        }

        $manager->setArgument(0, ServiceLocatorTagPass::register($container, $blockServiceReferences));

        foreach ($container->findTaggedServiceIds('knp_menu.menu') as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['alias'])) {
                    throw new \InvalidArgumentException(\sprintf('The alias is not defined in the "knp_menu.menu" tag for the service "%s"', $serviceId));
                }
                $registry->addMethodCall('add', [$attributes['alias']]);
            }
        }

        $services = [];
        foreach ($container->findTaggedServiceIds('adminata.block.loader') as $serviceId => $tags) {
            $services[] = new Reference($serviceId);
        }

        $container->setParameter('adminata_block.blocks', $blocks);
        $container->setParameter('adminata_blocks.block_types', $blockTypes);

        $container->getDefinition('adminata.block.loader.service')->replaceArgument(0, $blockTypes);
        $container->getDefinition('adminata.block.loader.chain')->replaceArgument(0, $services);

        $this->applyContext($container);
    }

    /**
     * Apply configurations to the context manager.
     */
    private function applyContext(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition('adminata.block.context_manager');

        /** @var array<string, array<string, mixed>> $blocks */
        $blocks = $container->getParameter('adminata_block.blocks');
        foreach ($blocks as $service => $settings) {
            if (\count($settings['settings']) > 0) {
                $definition->addMethodCall('addSettingsByType', [$service, $settings['settings'], true]);
            }
        }

        /** @var array<class-string, array<string, mixed>> $blocksByClass */
        $blocksByClass = $container->getParameter('adminata_block.blocks_by_class');
        foreach ($blocksByClass as $class => $settings) {
            if (\count($settings['settings']) > 0) {
                $definition->addMethodCall('addSettingsByClass', [$class, $settings['settings'], true]);
            }
        }
    }

    /**
     * @param array<array<string, mixed>> $tags
     * @param string[]                    $defaultContexts
     *
     * @return array<string, mixed>
     */
    private function createBlockSettings(array $tags = [], array $defaultContexts = []): array
    {
        $contexts = $this->getContextFromTags($tags);

        if (0 === \count($contexts)) {
            $contexts = $defaultContexts;
        }

        return [
            'contexts' => $contexts,
            'templates' => [],
            'settings' => [],
        ];
    }

    /**
     * @param array<array<string, mixed>> $tags
     *
     * @return string[]
     */
    private function getContextFromTags(array $tags): array
    {
        $contexts = [];
        foreach ($tags as $attribute) {
            if (\array_key_exists('context', $attribute) && \is_string($attribute['context'])) {
                $contexts[] = $attribute['context'];
            }
        }

        return $contexts;
    }
}
