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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

/**
 * Resolves the databases the test suites run against.
 *
 * adminata supports MySQL, MariaDB and Percona; there is no SQLite support, so the tests that
 * need a database use the MySQL of the repository's `docker-compose.yml`. Point them at another
 * server with `ADMINATA_TEST_DATABASE_URL` (or `DATABASE_URL` for the test applications); the
 * database name in that URL is replaced per suite, so one server serves them all.
 *
 * @phpstan-import-type Params from DriverManager
 */
final class TestDatabase
{
    public const DEFAULT_URL = 'mysql://root:adminata@127.0.0.1:7010/adminata_test?serverVersion=8.4.0&charset=utf8mb4';

    /**
     * @return array<string, mixed>
     *
     * @phpstan-return Params
     */
    public static function parameters(string $variable = 'ADMINATA_TEST_DATABASE_URL', ?string $database = null): array
    {
        $url = $_SERVER[$variable] ?? $_ENV[$variable] ?? null;

        if (!\is_string($url) || '' === $url) {
            $url = self::DEFAULT_URL;
        }

        $parameters = (new DsnParser(['mysql' => 'pdo_mysql', 'mariadb' => 'pdo_mysql']))->parse($url);

        if (null !== $database) {
            $parameters['dbname'] = $database;
        }

        return $parameters;
    }

    /**
     * Creates the database of the given parameters, optionally dropping it first, and returns a
     * connection to it.
     *
     * @param array<string, mixed> $parameters
     *
     * @phpstan-param Params $parameters
     */
    public static function connect(array $parameters, bool $drop = false): Connection
    {
        $database = $parameters['dbname'] ?? null;

        if (\is_string($database) && '' !== $database) {
            $server = $parameters;
            unset($server['dbname']);

            $connection = DriverManager::getConnection($server);
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

        return DriverManager::getConnection($parameters);
    }

    /**
     * A plain PDO handle on a freshly created database, for the tests that exercise
     * `PDOStatementSourceIterator`.
     */
    public static function pdo(string $database): \PDO
    {
        $parameters = self::parameters(database: $database);
        self::connect($parameters, true)->close();

        $host = \is_string($parameters['host'] ?? null) ? $parameters['host'] : '127.0.0.1';
        $port = (int) ($parameters['port'] ?? 3306);
        $user = \is_string($parameters['user'] ?? null) ? $parameters['user'] : 'root';
        $password = \is_string($parameters['password'] ?? null) ? $parameters['password'] : '';

        return new \PDO(
            \sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
            $user,
            $password,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }
}
