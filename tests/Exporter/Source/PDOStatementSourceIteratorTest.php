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

namespace IDCT\Adminata\Tests\Exporter\Source;

use Adminata\Tests\Support\TestDatabase;
use IDCT\Adminata\Exporter\Source\PDOStatementSourceIterator;
use PHPUnit\Framework\TestCase;

final class PDOStatementSourceIteratorTest extends TestCase
{
    private \PDO $dbh;

    protected function setUp(): void
    {
        // adminata supports MySQL, MariaDB and Percona; this used a temporary SQLite file.
        $this->dbh = TestDatabase::pdo('adminata_exporter_pdo_test');
        $this->dbh->exec('CREATE TABLE `user` (`id` int(11), `username` varchar(255) NOT NULL, `email` varchar(255) NOT NULL )');

        $data = [
            [1, 'john', 'john@foo.bar'],
            [2, 'john 2', 'john@foo.bar'],
            [3, 'john 3', 'john@foo.bar'],
        ];

        foreach ($data as $user) {
            $query = $this->dbh->prepare('INSERT INTO user (id, username, email) VALUES(?, ?, ?)');

            $query->execute($user);
        }
    }

    protected function tearDown(): void
    {
        $this->dbh->exec('DROP TABLE IF EXISTS `user`');

        unset($this->dbh);
    }

    public function testHandler(): void
    {
        $stm = $this->dbh->prepare('SELECT id, username, email FROM user');
        $stm->execute();

        $iterator = new PDOStatementSourceIterator($stm);

        $data = [];
        foreach ($iterator as $user) {
            $data[] = $user;
        }

        static::assertCount(3, $data);
    }
}
