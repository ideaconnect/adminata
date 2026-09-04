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

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

final class TestEntityManagerFactory
{
    public static function create(): EntityManagerInterface
    {
        if (version_compare(\PHP_VERSION, '8.0.0', '>=')) {
            /* @phpstan-ignore function.alreadyNarrowedType */
            if (\PHP_VERSION_ID >= 80400 && method_exists(ORMSetup::class, 'createAttributeMetadataConfig')) {
                $config = ORMSetup::createAttributeMetadataConfig([], true);
            } else {
                $config = ORMSetup::createAttributeMetadataConfiguration([], true);
            }
        } else {
            /**
             * @var Configuration $config
             *
             * @phpstan-ignore-next-line
             */
            $config = ORMSetup::createAnnotationMetadataConfiguration([], true);
        }

        if (\PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        }

        // adminata supports MySQL, MariaDB and Percona only, so these tests run against the
        // MySQL service of the repository's docker-compose.yml on their own database.
        // Override ADMINATA_TEST_DATABASE_URL to point them somewhere else.
        $url = $_SERVER['ADMINATA_TEST_DATABASE_URL'] ?? null;

        if (!\is_string($url) || '' === $url) {
            $url = 'mysql://root:adminata@127.0.0.1:7010/adminata_orm_unit_test?serverVersion=8.4.0&charset=utf8mb4';
        }

        $connection = DriverManager::getConnection(
            (new DsnParser(['mysql' => 'pdo_mysql', 'mariadb' => 'pdo_mysql']))->parse($url),
            $config
        );

        return new EntityManager(
            $connection,
            $config,
            new EventManager()
        );
    }
}
