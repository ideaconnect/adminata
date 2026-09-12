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

namespace IDCT\Adminata\Tests\App\Admin;

use IDCT\Adminata\Admin\AbstractAdmin;
use IDCT\Adminata\Datagrid\ListMapper;
use IDCT\Adminata\Form\FormMapper;
use IDCT\Adminata\Show\ShowMapper;

/**
 * @phpstan-extends AbstractAdmin<object>
 */
final class EmptyAdmin extends AbstractAdmin
{
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'empty';
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'admin_empty';
    }

    protected function configureListFields(ListMapper $list): void
    {
        // Empty
    }

    protected function configureFormFields(FormMapper $form): void
    {
        // Empty
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        // Empty
    }
}
