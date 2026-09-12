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

use Adminata\Tests\App\Entity\Tag;
use IDCT\Adminata\Admin\AbstractAdmin;
use IDCT\Adminata\Datagrid\DatagridMapper;
use IDCT\Adminata\Datagrid\ListMapper;
use IDCT\Adminata\FieldDescription\FieldDescriptionInterface;
use IDCT\Adminata\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * The other side of `Product::$tags`: it exists so that a many-to-many cell has an admin to link
 * to and so that the demo has a list with the batch action removed.
 *
 * @phpstan-extends AbstractAdmin<Tag>
 */
final class TagAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', FieldDescriptionInterface::TYPE_INTEGER)
            ->addIdentifier('name')
            ->add('products', FieldDescriptionInterface::TYPE_MANY_TO_MANY)
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['edit' => [], 'delete' => []],
            ]);

        // `->remove('batch')` ×1 in the application (appendix C §2): a list with no checkbox
        // column at all, which the header, the footer and `adminata-batch` all have to survive.
        $list->remove(ListMapper::NAME_BATCH);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('name');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Tag', ['class' => 'col-span-12'])
                ->add('name', TextType::class)
            ->end();
    }
}
