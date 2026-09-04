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

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Points `KERNEL_CLASS` at the right test kernel for the test that is about to run.
 *
 * The seven forked packages each had their own `phpunit.xml.dist` setting a single
 * `KERNEL_CLASS` environment variable. adminata runs all of their suites from one
 * configuration file, and four of them ship a different test kernel, so the variable
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
    private const KERNELS = [
        'Sonata\\AdminBundle\\Tests\\' => \Sonata\AdminBundle\Tests\App\AppKernel::class,
        'Sonata\\BlockBundle\\Tests\\' => \Sonata\BlockBundle\Tests\App\AppKernel::class,
        'Sonata\\DoctrineORMAdminBundle\\Tests\\' => \Sonata\DoctrineORMAdminBundle\Tests\App\AppKernel::class,
        'Sonata\\Twig\\Tests\\' => \Sonata\Twig\Tests\App\AppKernel::class,
        // 'Adminata\\Tests\\' => the demo application kernel, added by task P1-09.
    ];

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
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
        foreach (self::KERNELS as $prefix => $kernelClass) {
            if (!str_starts_with($testClass, $prefix)) {
                continue;
            }

            $_ENV['KERNEL_CLASS'] = $kernelClass;
            $_SERVER['KERNEL_CLASS'] = $kernelClass;
            putenv('KERNEL_CLASS='.$kernelClass);

            return;
        }

        unset($_ENV['KERNEL_CLASS'], $_SERVER['KERNEL_CLASS']);
        putenv('KERNEL_CLASS');
    }
}
