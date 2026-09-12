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
use IDCT\Adminata\Datagrid\DatagridMapper;
use IDCT\Adminata\Datagrid\ListMapper;
use IDCT\Adminata\Form\FormMapper;
use IDCT\Adminata\Show\ShowMapper;

/**
 * @phpstan-extends AbstractAdmin<object>
 */
final class AvoidInfiniteLoopAdmin extends AbstractAdmin
{
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $this->getFilterFieldDescriptions();
        $this->hasFilterFieldDescription('help');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $this->getListFieldDescriptions();
        $this->hasFilterFieldDescription('help');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $this->getFormFieldDescriptions();
        $this->hasFilterFieldDescription('help');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $this->getShowFieldDescriptions();
        $this->hasFilterFieldDescription('help');
    }
}
