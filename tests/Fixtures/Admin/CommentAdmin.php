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

namespace IDCT\Adminata\Tests\Fixtures\Admin;

use IDCT\Adminata\Admin\AbstractAdmin;
use IDCT\Adminata\Route\RouteCollectionInterface;

/**
 * @phpstan-extends AbstractAdmin<object>
 */
class CommentAdmin extends AbstractAdmin
{
    public function setClassnameLabel(string $label): void
    {
        $this->classnameLabel = $label;
    }

    public function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('edit');
    }
}
