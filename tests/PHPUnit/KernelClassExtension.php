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

namespace Adminata\Tests\PHPUnit;

use Adminata\Tests\App\Kernel;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Sonata\AdminBundle\Tests\App\AppKernel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Points `KERNEL_CLASS` at the right test kernel for the test that is about to run.
 *
 * The seven forked packages each had their own `phpunit.xml.dist` setting a single
 * `KERNEL_CLASS` environment variable. adminata runs all of their suites from one
 * configuration file, and two of them ship a different test kernel, so the variable
 * has to follow the test class instead of being global. Doing it here keeps the
 * inherited test classes byte-identical, which is what upstream syncs need.
 */
final class KernelClassExtension implements Extension
{
    /**
     * Test namespace prefix => test kernel class, longest prefix wins.
     *
     * @var array<string, class-string>
     */
    private const array KERNELS = [
        'Adminata\\Tests\\' => Kernel::class,
        'Sonata\\AdminBundle\\Tests\\' => AppKernel::class,
        'Sonata\\DoctrineORMAdminBundle\\Tests\\' => \Sonata\DoctrineORMAdminBundle\Tests\App\AppKernel::class,
    ];

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        self::clearKernelCaches();

        $facade->registerSubscriber(new class implements PreparationStartedSubscriber {
            public function notify(PreparationStarted $event): void
            {
                $test = $event->test();

                if (!$test instanceof TestMethod) {
                    return;
                }

                KernelClassExtension::selectKernelFor($test->className());
            }
        });
    }

    /**
     * @internal
     */
    public static function selectKernelFor(string $testClass): void
    {
        $selected = null;
        $matched = '';

        foreach (self::KERNELS as $prefix => $kernelClass) {
            if (!str_starts_with($testClass, $prefix) || \strlen($prefix) <= \strlen($matched)) {
                continue;
            }

            $selected = $kernelClass;
            $matched = $prefix;
        }

        if (null === $selected) {
            unset($_ENV['KERNEL_CLASS'], $_SERVER['KERNEL_CLASS']);
            putenv('KERNEL_CLASS');

            return;
        }

        $_ENV['KERNEL_CLASS'] = $selected;
        $_SERVER['KERNEL_CLASS'] = $selected;
        putenv('KERNEL_CLASS='.$selected);
    }

    /**
     * Removes every test kernel's compiled container and template cache before the run.
     *
     * They live under the system temp directory and survive between runs, so a template or a
     * service that stopped working can keep passing on a machine that has the old build — which is
     * exactly how a 500 from the flash template reached `main` green.
     */
    private static function clearKernelCaches(): void
    {
        $environment = $_SERVER['APP_ENV'] ?? null;
        $filesystem = new Filesystem();

        foreach (array_unique(self::KERNELS) as $kernelClass) {
            $kernel = new $kernelClass(
                \is_string($environment) ? $environment : 'test',
                (bool) ($_SERVER['APP_DEBUG'] ?? false)
            );
            \assert($kernel instanceof KernelInterface);

            $filesystem->remove($kernel->getCacheDir());
        }
    }
}
