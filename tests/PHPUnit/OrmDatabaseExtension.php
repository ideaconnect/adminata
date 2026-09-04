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

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\TestSuite\Loaded;
use PHPUnit\Event\TestSuite\LoadedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Sonata\DoctrineORMAdminBundle\Tests\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Prepares the databases the doctrine-orm-admin-bundle suite needs, the first time one of its
 * tests runs.
 *
 * This replaces `packages/doctrine-orm-admin-bundle/tests/custom_bootstrap.php`, which the
 * deleted per-package `phpunit.xml.dist` pulled in through its bootstrap file. Doing it lazily
 * keeps the other six suites free of any database requirement.
 *
 * adminata supports MySQL, MariaDB and Percona; there is no SQLite support. `docker compose up -d`
 * starts a matching MySQL. `DATABASE_URL` (functional application) and `ADMINATA_TEST_DATABASE_URL`
 * (the entity manager of the unit tests) point the suite at another server.
 */
final class OrmDatabaseExtension implements Extension
{
    public const TEST_NAMESPACE = 'Sonata\\DoctrineORMAdminBundle\\Tests\\';

    private const MAX_HANDLER_RESTORES = 16;

    private static bool $prepared = false;

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        // Not a per-test subscriber: dama/doctrine-test-bundle wraps every test in a
        // transaction, and loading fixtures inside one breaks its savepoint bookkeeping.
        // TestSuite\Loaded fires once, before the first test is prepared.
        $facade->registerSubscriber(new class() implements LoadedSubscriber {
            public function notify(Loaded $event): void
            {
                foreach ($event->testSuite()->tests() as $test) {
                    if ($test instanceof TestMethod && str_starts_with($test->className(), OrmDatabaseExtension::TEST_NAMESPACE)) {
                        OrmDatabaseExtension::prepare();

                        return;
                    }
                }
            }
        });
    }

    /**
     * @internal
     */
    public static function prepare(): void
    {
        if (self::$prepared) {
            return;
        }

        self::$prepared = true;

        // `TestEntityManagerFactory` connects straight through DBAL, so nothing else creates
        // the database its schema goes into.
        self::recreateDatabase(self::url('ADMINATA_TEST_DATABASE_URL', 'adminata_orm_unit_test'), true);
        self::recreateDatabase(self::url('DATABASE_URL', 'adminata_orm_test'), true);

        self::loadApplicationFixtures();
    }

    private static function url(string $variable, string $database): string
    {
        $url = $_SERVER[$variable] ?? null;

        if (\is_string($url) && '' !== $url) {
            return $url;
        }

        return \sprintf(
            'mysql://root:adminata@127.0.0.1:7010/%s?serverVersion=8.4.0&charset=utf8mb4',
            $database
        );
    }

    /**
     * Doctrine's `doctrine:database:drop`/`create` commands and `doctrine:schema:create` share
     * one connection inside a booted kernel, and that connection keeps the database it was
     * opened without. Create the database through a connection of our own instead.
     */
    private static function recreateDatabase(string $url, bool $drop): void
    {
        $parameters = (new DsnParser(['mysql' => 'pdo_mysql', 'mariadb' => 'pdo_mysql']))->parse($url);
        $database = $parameters['dbname'] ?? null;
        unset($parameters['dbname']);

        if (!\is_string($database) || '' === $database) {
            return;
        }

        $connection = DriverManager::getConnection($parameters);
        $quoted = $connection->quoteSingleIdentifier($database);

        if ($drop) {
            $connection->executeStatement(\sprintf('DROP DATABASE IF EXISTS %s', $quoted));
        }

        $connection->executeStatement(\sprintf(
            'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $quoted
        ));
        $connection->close();
    }

    private static function loadApplicationFixtures(): void
    {
        // Symfony's console installs error and exception handlers. PHPUnit compares the handler
        // stack of every test against the one it started with, and the functional tests of the
        // admin bundle call restore_exception_handler() in tearDown(); anything left behind here
        // would make them "remove exception handlers other than their own", i.e. risky.
        $exceptionHandler = self::currentExceptionHandler();
        $errorHandler = self::currentErrorHandler();

        $environment = $_SERVER['APP_ENV'] ?? null;

        $kernel = new AppKernel(
            \is_string($environment) ? $environment : 'test',
            (bool) ($_SERVER['APP_DEBUG'] ?? false)
        );

        (new Filesystem())->remove([$kernel->getCacheDir()]);

        $application = new Application($kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);

        $commands = [
            ['command' => 'doctrine:schema:create'],
            ['command' => 'doctrine:fixtures:load', '--no-interaction' => true],
            [
                'command' => 'assets:install',
                'target' => \dirname(__DIR__, 2).'/packages/doctrine-orm-admin-bundle/tests/App/public',
                '--symlink' => true,
            ],
        ];

        foreach ($commands as $command) {
            $application->run(new ArrayInput($command), new NullOutput());
        }

        $kernel->shutdown();

        self::restoreHandlers($exceptionHandler, $errorHandler);
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
