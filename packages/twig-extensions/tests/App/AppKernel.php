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

namespace Sonata\Twig\Tests\App;

use Sonata\Twig\Bridge\Symfony\SonataTwigBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\StimulusBundle\StimulusBundle;

final class AppKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @return BundleInterface[]
     */
    public function registerBundles(): array
    {
        $bundles = [
            new FrameworkBundle(),
            new TwigBundle(),
            new SonataTwigBundle(),
        ];

        // The flash template calls `stimulus_controller()` and `stimulus_action()`: adminata is one
        // package and its `composer.json` requires symfony/stimulus-bundle, so an application
        // always has it. Guarded all the same, because upstream's twig-extensions did not.
        if (class_exists(StimulusBundle::class)) {
            $bundles[] = new StimulusBundle();
        }

        return $bundles;
    }

    public function getCacheDir(): string
    {
        return $this->getBaseDir().'cache';
    }

    public function getLogDir(): string
    {
        return $this->getBaseDir().'log';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(\sprintf('%s/config/routes.yaml', $this->getProjectDir()));
    }

    protected function configureContainer(ContainerBuilder $containerBuilder, LoaderInterface $loader): void
    {
        $frameworkConfig = [
            'secret' => 'secret',
            'test' => true,
            'router' => ['utf8' => true],
        ];

        $frameworkConfig['session'] = ['storage_factory_id' => 'session.storage.factory.mock_file'];

        $containerBuilder->loadFromExtension('framework', $frameworkConfig);

        $containerBuilder->loadFromExtension('twig', [
            'strict_variables' => '%kernel.debug%',
            'paths' => ['%kernel.project_dir%/templates'],
        ]);
    }

    private function getBaseDir(): string
    {
        return sys_get_temp_dir().'/sonata-twig-extensions/var/';
    }
}
