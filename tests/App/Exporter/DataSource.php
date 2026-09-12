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

namespace IDCT\Adminata\Tests\App\Exporter;

use IDCT\Adminata\Datagrid\ProxyQueryInterface;
use IDCT\Adminata\Exporter\DataSourceInterface;
use IDCT\Adminata\Exporter\Source\ArraySourceIterator;

final class DataSource implements DataSourceInterface
{
    public function createIterator(ProxyQueryInterface $query, array $fields): \Iterator
    {
        return new ArraySourceIterator([]);
    }
}
