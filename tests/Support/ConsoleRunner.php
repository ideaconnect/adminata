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

namespace Adminata\Tests\Support;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Runs console commands from inside the PHPUnit process without disturbing the handler stack.
 *
 * Symfony's console installs error and exception handlers. PHPUnit compares the handler stack of
 * every test against the one it started with, and the functional tests of the admin bundle call
 * restore_exception_handler() in tearDown(); anything left behind here would make them "remove
 * exception handlers other than their own", i.e. risky.
 */
final class ConsoleRunner
{
    private const int MAX_HANDLER_RESTORES = 16;

    /**
     * @param list<array<string, bool|string>> $commands
     */
    public static function run(KernelInterface $kernel, array $commands): void
    {
        $exceptionHandler = self::currentExceptionHandler();
        $errorHandler = self::currentErrorHandler();

        $application = new Application($kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);

        try {
            foreach ($commands as $command) {
                $application->run(new ArrayInput($command), new NullOutput());
            }
        } finally {
            $kernel->shutdown();

            self::restoreHandlers($exceptionHandler, $errorHandler);
        }
    }

    private static function currentExceptionHandler(): ?callable
    {
        $handler = set_exception_handler(null);
        restore_exception_handler();

        return $handler;
    }

    private static function currentErrorHandler(): ?callable
    {
        $handler = set_error_handler(null);
        restore_error_handler();

        return $handler;
    }

    private static function restoreHandlers(?callable $exceptionHandler, ?callable $errorHandler): void
    {
        for ($i = 0; $i < self::MAX_HANDLER_RESTORES && self::currentExceptionHandler() !== $exceptionHandler; ++$i) {
            restore_exception_handler();
        }

        for ($i = 0; $i < self::MAX_HANDLER_RESTORES && self::currentErrorHandler() !== $errorHandler; ++$i) {
            restore_error_handler();
        }
    }
}
