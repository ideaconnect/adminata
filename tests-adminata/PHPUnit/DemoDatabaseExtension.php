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
use Adminata\Tests\Support\ConsoleRunner;
use Adminata\Tests\Support\TestDatabase;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\TestSuite\Loaded;
use PHPUnit\Event\TestSuite\LoadedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Creates the demo application's database and loads its fixtures, the first time one of the
 * repository's own functional tests runs.
 *
 * Same shape and the same reasons as {@see OrmDatabaseExtension}: once per run rather than per
 * test, because dama/doctrine-test-bundle wraps every test in a transaction and fixtures loaded
 * inside one break its savepoint bookkeeping.
 */
final class DemoDatabaseExtension implements Extension
{
    public const string TEST_NAMESPACE = 'Adminata\\Tests\\Functional\\';

    private static bool $prepared = false;

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class implements LoadedSubscriber {
            public function notify(Loaded $event): void
            {
                foreach ($event->testSuite()->tests() as $test) {
                    if ($test instanceof TestMethod && str_starts_with($test->className(), DemoDatabaseExtension::TEST_NAMESPACE)) {
                        DemoDatabaseExtension::prepare();

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

        TestDatabase::connect(
            TestDatabase::parameters('ADMINATA_DEMO_DATABASE_URL', 'adminata_demo'),
            true
        )->close();

        $environment = $_SERVER['APP_ENV'] ?? null;

        $kernel = new Kernel(
            \is_string($environment) ? $environment : 'test',
            (bool) ($_SERVER['APP_DEBUG'] ?? false)
        );

        new Filesystem()->remove([$kernel->getCacheDir()]);

        ConsoleRunner::run($kernel, [
            ['command' => 'doctrine:schema:create'],
            // The identifiers are deterministic because `TestDatabase::connect(…, true)` above
            // dropped the database first: the purger's DELETE would leave AUTO_INCREMENT where
            // it was.
            ['command' => 'doctrine:fixtures:load', '--no-interaction' => true],
            [
                'command' => 'assets:install',
                'target' => \dirname(__DIR__).'/App/public',
                '--symlink' => true,
            ],
        ]);
    }
}
