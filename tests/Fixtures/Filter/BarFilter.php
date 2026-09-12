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

namespace IDCT\Adminata\Tests\Fixtures\Filter;

use IDCT\Adminata\Datagrid\ProxyQueryInterface;
use IDCT\Adminata\Filter\Filter;
use IDCT\Adminata\Filter\Model\FilterData;

final class BarFilter extends Filter
{
    public function apply(ProxyQueryInterface $query, FilterData $filterData): void
    {
    }

    public function getDefaultOptions(): array
    {
        return ['bar' => 'bar'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormOptions(): array
    {
        return ['label' => 'label'];
    }
}
