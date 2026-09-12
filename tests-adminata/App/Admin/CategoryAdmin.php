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
use IDCT\Adminata\Admin\AbstractAdmin;
use IDCT\Adminata\Datagrid\DatagridMapper;
use IDCT\Adminata\Datagrid\ListMapper;
use IDCT\Adminata\DoctrineORM\Filter\DateTimeFilter;
use IDCT\Adminata\DoctrineORM\Filter\ModelAutocompleteFilter;
use IDCT\Adminata\FieldDescription\FieldDescriptionInterface;
use IDCT\Adminata\Form\FormMapper;
use IDCT\Adminata\Form\Type\BooleanType;
use IDCT\Adminata\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

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
            ->with('Category', ['class' => 'col-span-12 xl:col-span-8'])
                ->add('name', TextType::class)
                ->add('description', TextareaType::class, [
                    'required' => false,
                    // `help_html` renders the help as markup, which is the one place a template
                    // may not escape it (PLAN/06 §1).
                    'help' => 'Shown on the <strong>category page</strong>.',
                    'help_html' => true,
                ])
                ->add('active', BooleanType::class, ['transform' => true])
                ->add('highlighted', CheckboxType::class, ['required' => false])
            ->end()
            ->with('Contact', ['class' => 'col-span-12 xl:col-span-4'])
                ->add('contactEmail', EmailType::class, ['required' => false])
                ->add('homepage', UrlType::class, ['required' => false, 'default_protocol' => 'https'])
                ->add('sortOrder', NumberType::class, ['html5' => true, 'scale' => 0])
                ->add('visibility', ChoiceType::class, [
                    'choices' => ['Everyone' => 'everyone', 'Staff only' => 'staff', 'Hidden' => 'hidden'],
                    // Passed straight through: an application hangs its own controllers here.
                    'attr' => ['data-controller' => 'app--visibility'],
                ])
            ->end()
            ->with('Import', ['class' => 'col-span-12', 'description' => 'Nothing here is stored.'])
                ->add('importToken', PasswordType::class, ['mapped' => false, 'required' => false])
                ->add('importFile', FileType::class, ['mapped' => false, 'required' => false])
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('description')
            ->add('active')
            ->add('highlighted')
            ->add('contactEmail', FieldDescriptionInterface::TYPE_EMAIL)
            ->add('homepage', FieldDescriptionInterface::TYPE_URL)
            ->add('sortOrder')
            ->add('visibility', FieldDescriptionInterface::TYPE_CHOICE, [
                'choices' => ['everyone' => 'Everyone', 'staff' => 'Staff only', 'hidden' => 'Hidden'],
            ])
            ->add('createdAt');
    }
}
