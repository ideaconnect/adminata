<?php

declare(strict_types=1);

/*
 * This file is part of the adminata package.
 *
 * (c) IDCT Bartosz Pachołek <bartosz@idct.tech>
 *
 * Forked from the Sonata Project
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adminata\Tests\App\Admin;

use Adminata\Tests\App\Entity\Category;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter;
use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @phpstan-extends AbstractAdmin<Category>
 */
final class CategoryAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', FieldDescriptionInterface::TYPE_INTEGER)
            ->addIdentifier('name')
            ->add('active', FieldDescriptionInterface::TYPE_BOOLEAN, ['editable' => true])
            ->add('createdAt', FieldDescriptionInterface::TYPE_DATETIME)
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('active')
            ->add('createdAt', DateTimeFilter::class)
            // The one combobox in the demo: `ProductAdmin` is the association admin the
            // autocomplete action resolves, and `property` names a filter on *its* datagrid.
            ->add('products', ModelAutocompleteFilter::class, [
                'field_options' => [
                    'property' => 'name',
                    'minimum_input_length' => 2,
                    'items_per_page' => 5,
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Category', ['class' => 'col-span-12'])
                ->add('name', TextType::class)
                ->add('description', TextareaType::class, ['required' => false])
                ->add('active', BooleanType::class, ['transform' => true])
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('description')
            ->add('active')
            ->add('createdAt');
    }
}
