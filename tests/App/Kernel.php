<?php

declare(strict_types=1);

/*
 * This file is part of the adminata package.
 *
 * (c) IDCT Bartosz Pachołek <bartosz@idct.tech>
 *
 * Forked from the Sonata Project
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adminata\Tests\App;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Sonata\AdminBundle\SonataAdminBundle;
use Sonata\BlockBundle\SonataBlockBundle;
use Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle;
use Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle;
use Sonata\Exporter\Bridge\Symfony\SonataExporterBundle;
use Sonata\Form\Bridge\Symfony\SonataFormBundle;
use Sonata\Twig\Bridge\Symfony\SonataTwigBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\StimulusBundle\StimulusBundle;

/**
 * adminata's own demo application: the seven packages wired together the way an application
 * wires them, on the MySQL service of the repository's docker-compose.yml (owner directive 9 —
 * MySQL, MariaDB and Percona only, no SQLite).
 *
 * It is three things at once. `make demo` serves it, so a change to a template can be looked at.
 * `tests/Functional` drives it through BrowserKit. And from P1-10 on it is the target of the
 * Playwright, axe and html-validate runs, which is why its fixtures are deterministic.
 *
 * The per-package test kernels stay where they are: they belong to the suites that upstream syncs
 * bring in, and their configuration is tuned to those tests rather than to a realistic
 * application.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            new TwigBundle(),
            new SecurityBundle(),
            new KnpMenuBundle(),
            new DoctrineBundle(),
            new DoctrineFixturesBundle(),
            new SonataDoctrineBundle(),
            new SonataBlockBundle(),
            new SonataTwigBundle(),
            new SonataFormBundle(),
            new SonataExporterBundle(),
            new SonataAdminBundle(),
            new SonataDoctrineORMAdminBundle(),
        ];

        if (class_exists(StimulusBundle::class)) {
            $bundles[] = new StimulusBundle();
        }

        // Wraps every test in a transaction. Loading it outside the test environment would make
        // `make demo` unable to save anything.
        if ('test' === $this->environment && class_exists(DAMADoctrineTestBundle::class)) {
            $bundles[] = new DAMADoctrineTestBundle();
        }

        return $bundles;
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return $this->varDir().'/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->varDir().'/log';
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/config/routes.yaml');
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config/packages.yaml');
        $loader->load(__DIR__.'/config/sonata.yaml');

        if ('test' === $this->environment) {
            $loader->load(__DIR__.'/config/packages_test.yaml');
        }

        $loader->load(__DIR__.'/config/services.php');
    }

    /**
     * Outside the repository, so that a `make demo` run leaves nothing behind for git to see and
     * the two environments cannot share a stale container.
     */
    private function varDir(): string
    {
        return sys_get_temp_dir().'/adminata-demo';
    }
}
