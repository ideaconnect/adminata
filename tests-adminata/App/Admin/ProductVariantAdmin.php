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

use Adminata\Tests\App\Entity\Product;
use Adminata\Tests\App\Entity\ProductVariant;
use IDCT\Adminata\Admin\AbstractAdmin;
use IDCT\Adminata\Datagrid\DatagridMapper;
use IDCT\Adminata\Datagrid\ListMapper;
use IDCT\Adminata\FieldDescription\FieldDescriptionInterface;
use IDCT\Adminata\Route\RouteCollectionInterface;
use IDCT\Adminata\DoctrineORM\Filter\ModelFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * The child list the application fetches over XHR (`TransactionItemsAccordion.js`, appendix C §2):
 * a plain list, filtered by its parent, whose `ajax_layout` response has to keep
 * `table.adminata-list` parseable.
 *
 * @phpstan-extends AbstractAdmin<ProductVariant>
 */
final class ProductVariantAdmin extends AbstractAdmin
{
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        // `configureRoutes` ×4 with `->remove(…)` ×6 in the application: the variant list is read
        // through the parent's form, so nothing here creates or deletes one.
        $collection->remove('create')->remove('delete');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', FieldDescriptionInterface::TYPE_INTEGER)
            ->addIdentifier('label')
            ->add('stock', FieldDescriptionInterface::TYPE_INTEGER, ['header_class' => 'text-right'])
            ->add('product', FieldDescriptionInterface::TYPE_MANY_TO_ONE);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('label')
            ->add('product', ModelFilter::class, [
                'field_type' => EntityType::class,
                'field_options' => ['class' => Product::class],
            ]);
    }
}
