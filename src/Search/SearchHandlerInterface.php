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

namespace IDCT\Adminata\Search;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Datagrid\PagerInterface;
use IDCT\Adminata\Datagrid\ProxyQueryInterface;

interface SearchHandlerInterface
{
    /**
     * @throws \RuntimeException
     *
     * @phpstan-template T of object
     * @phpstan-param AdminInterface<T> $admin
     * @phpstan-return PagerInterface<ProxyQueryInterface<T>>|null
     */
    public function search(AdminInterface $admin, string $term, int $page = 0, int $offset = 20): ?PagerInterface;
}
