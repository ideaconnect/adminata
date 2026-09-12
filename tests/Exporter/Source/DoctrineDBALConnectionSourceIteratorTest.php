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
use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Exporter\Source\DoctrineDBALConnectionSourceIterator;

final class DoctrineDBALConnectionSourceIteratorTest extends TestCase
{
    public function testRewindWithEmptyQuery(): void
    {
        // adminata supports MySQL, MariaDB and Percona; this used an in-memory SQLite database.
        $connection = TestDatabase::connect(
            TestDatabase::parameters(database: 'adminata_exporter_dbal_test'),
            true
        );

        $iterator = new DoctrineDBALConnectionSourceIterator($connection, 'SELECT :param AS foo', ['param' => '1']);
        $iterator->rewind();

        static::assertSame([2 => ['foo' => '1']], iterator_to_array($iterator));
    }
}
