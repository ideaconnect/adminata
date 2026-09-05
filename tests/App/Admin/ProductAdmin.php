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
use Adminata\Tests\App\Entity\Product;
use Adminata\Tests\App\Enum\ProductStatus;
use Adminata\Tests\App\Form\ProductVariantType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;
use Sonata\Form\Type\BooleanType;
use Sonata\Form\Type\DateTimePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * The field types P1-09 puts on the page: string, integer, datetime, boolean, enum and
 * many-to-one in the list and the show, the matching filters, and one native Symfony
 * CollectionType with `allow_add`/`allow_delete`. M3 and M4 widen this to the rest of
 * appendix C §2.
 *
 * @phpstan-extends AbstractAdmin<Product>
 */
final class ProductAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', FieldDescriptionInterface::TYPE_INTEGER)
            ->addIdentifier('name')
            ->add('sku', FieldDescriptionInterface::TYPE_STRING)
            ->add('price', FieldDescriptionInterface::TYPE_INTEGER, ['header_class' => 'text-right'])
            ->add('status', FieldDescriptionInterface::TYPE_ENUM)
            ->add('category', FieldDescriptionInterface::TYPE_MANY_TO_ONE, [
                'sortable' => true,
                'sort_field_mapping' => ['fieldName' => 'name'],
                'sort_parent_association_mappings' => [['fieldName' => 'category']],
            ])
            ->add('featured', FieldDescriptionInterface::TYPE_BOOLEAN)
            ->add('releasedAt', FieldDescriptionInterface::TYPE_DATETIME)
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
            ->add('sku')
            ->add('price', NumberFilter::class)
            ->add('status', ChoiceFilter::class, [
                'field_type' => EnumType::class,
                'field_options' => ['class' => ProductStatus::class],
            ])
            ->add('category', ModelFilter::class, [
                'field_type' => EntityType::class,
                'field_options' => ['class' => Category::class],
            ])
            ->add('featured', BooleanFilter::class)
            ->add('releasedAt', DateTimeRangeFilter::class);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Details', ['class' => 'col-span-8'])
                ->add('name', TextType::class)
                ->add('sku', TextType::class, ['help' => 'Unique stock keeping unit.'])
                ->add('price', IntegerType::class, ['help' => 'In minor units.'])
                ->add('category', EntityType::class, ['class' => Category::class])
            ->end()
            ->with('Publication', ['class' => 'col-span-4'])
                ->add('status', EnumType::class, ['class' => ProductStatus::class])
                ->add('featured', BooleanType::class, ['transform' => true])
                ->add('releasedAt', DateTimePickerType::class, [
                    'required' => false,
                    'datepicker_options' => ['display' => ['components' => ['calendar' => true, 'clock' => true]]],
                ])
            ->end()
            ->with('Variants', ['class' => 'col-span-12'])
                ->add('variants', CollectionType::class, [
                    'entry_type' => ProductVariantType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'label' => false,
                ])
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('sku')
            ->add('price')
            ->add('status', FieldDescriptionInterface::TYPE_ENUM)
            ->add('category')
            ->add('featured')
            ->add('releasedAt');
    }

    /**
     * @param Product $object
     */
    protected function prePersist(object $object): void
    {
        $this->syncVariants($object);
    }

    /**
     * @param Product $object
     */
    protected function preUpdate(object $object): void
    {
        $this->syncVariants($object);
    }

    /**
     * `by_reference: false` calls addVariant()/removeVariant(), but a row the browser posted
     * without ever passing through them — an entry the form built directly — would reach the
     * database with no product. Setting the owning side here keeps the collection valid whatever
     * the form did.
     */
    private function syncVariants(Product $product): void
    {
        foreach ($product->getVariants() as $variant) {
            $variant->setProduct($product);
        }
    }
}
