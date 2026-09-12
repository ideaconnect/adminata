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
final class ModelAdmin extends AbstractAdmin
{
    /**
     * @param DatagridMapper<object> $filter
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('foo')
            ->add('bar')
            ->add('baz');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('foo')
            ->add('bar')
            ->add('baz')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('foo')
            ->add('bar')
            ->add('baz');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('foo')
            ->add('bar')
            ->add('baz');
    }
}
