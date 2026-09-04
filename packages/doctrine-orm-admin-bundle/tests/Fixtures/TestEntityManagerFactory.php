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

use Adminata\Tests\Support\TestDatabase;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

final class TestEntityManagerFactory
{
    public static function create(): EntityManagerInterface
    {
        $config = ORMSetup::createAttributeMetadataConfig([], true);
        $config->enableNativeLazyObjects(true);

        // adminata supports MySQL, MariaDB and Percona only, so these tests run against the
        // MySQL service of the repository's docker-compose.yml on their own database, created by
        // Adminata\Tests\PHPUnit\OrmDatabaseExtension.
        $connection = DriverManager::getConnection(
            TestDatabase::parameters('ADMINATA_TEST_DATABASE_URL', 'adminata_orm_unit_test'),
            $config
        );

        return new EntityManager(
            $connection,
            $config,
            new EventManager()
        );
    }
}
