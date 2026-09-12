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

namespace IDCT\Adminata\Builder;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Route\RouteCollectionInterface;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
interface RouteBuilderInterface
{
    /**
     * @param AdminInterface<object> $admin
     */
    public function build(AdminInterface $admin, RouteCollectionInterface $collection): void;
}
