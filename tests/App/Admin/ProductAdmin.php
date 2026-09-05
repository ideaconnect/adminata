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
use Adminata\Tests\App\Entity\Tag;
use Adminata\Tests\App\Enum\ProductStatus;
use Adminata\Tests\App\Form\ProductVariantType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;
use Sonata\Form\Type\BooleanType;
use Sonata\Form\Type\DatePickerType;
use Sonata\Form\Type\DateRangePickerType;
use Sonata\Form\Type\DateTimePickerType;
use Sonata\Form\Type\DateTimeRangePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * The demo's "everything" admin (appendix C §2, PLAN/08 §2): every list cell type the application
 * uses, both shapes of custom cell template, a custom row action, a custom batch action with a
 * confirmation step, export fields, a `templates.list` override and every filter type but the
 * autocomplete one, which `CategoryAdmin` carries.
 *
 * @phpstan-extends AbstractAdmin<Product>
 */
final class ProductAdmin extends AbstractAdmin
{
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('archive', $this->getRouterIdParameter().'/archive');
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_BY] = 'name';
        $sortValues[DatagridInterface::SORT_ORDER] = 'ASC';
        $sortValues[DatagridInterface::PER_PAGE] = 25;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', FieldDescriptionInterface::TYPE_INTEGER)
            ->addIdentifier('name')
            ->add('sku', FieldDescriptionInterface::TYPE_STRING)
            ->add('price', FieldDescriptionInterface::TYPE_INTEGER, [
                'header_class' => 'text-right',
                'row_align' => 'right',
            ])
            // A cell template that extends the envelope, and one that writes its own `<td>`.
            ->add('stock', FieldDescriptionInterface::TYPE_INTEGER, [
                'template' => 'admin/list_stock.html.twig',
            ])
            ->add('specification', FieldDescriptionInterface::TYPE_ARRAY, [
                'template' => 'admin/list_specification.html.twig',
            ])
            ->add('status', FieldDescriptionInterface::TYPE_ENUM)
            ->add('category', FieldDescriptionInterface::TYPE_MANY_TO_ONE, [
                'sortable' => true,
                'sort_field_mapping' => ['fieldName' => 'name'],
                'sort_parent_association_mappings' => [['fieldName' => 'category']],
            ])
            ->add('tags', FieldDescriptionInterface::TYPE_MANY_TO_MANY)
            ->add('featured', FieldDescriptionInterface::TYPE_BOOLEAN)
            ->add('availableFrom', FieldDescriptionInterface::TYPE_DATE)
            ->add('pickupAt', FieldDescriptionInterface::TYPE_TIME)
            ->add('releasedAt', FieldDescriptionInterface::TYPE_DATETIME)
            ->add('highlights', FieldDescriptionInterface::TYPE_HTML)
            ->add('description', FieldDescriptionInterface::TYPE_TEXTAREA, [
                'collapse' => ['height' => 40],
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'archive' => ['template' => 'admin/list__action_archive.html.twig'],
                    'delete' => [],
                ],
            ]);
    }

    /**
     * `configureBatchActions` ×2 in the application: the default delete plus one custom action
     * that asks first.
     */
    protected function configureBatchActions(array $actions): array
    {
        $actions['archive'] = [
            'label' => 'Archive',
            'translation_domain' => false,
            'ask_confirmation' => true,
        ];

        return $actions;
    }

    protected function configureExportFields(): array
    {
        return ['id', 'name', 'sku', 'price', 'status', 'stock', 'category'];
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('sku')
            ->add('price', NumberFilter::class)
            ->add('status', ChoiceFilter::class, [
                'field_type' => EnumType::class,
                'field_options' => [
                    'class' => ProductStatus::class,
                    // ux-autocomplete's own markup, which adminata renders and never touches
                    // (PLAN/05 R4). The demo writes the attribute rather than installing the
                    // package: what is under test is that the theme leaves it alone.
                    'attr' => ['data-controller' => 'symfony--ux-autocomplete--autocomplete'],
                ],
            ])
            ->add('category', ModelFilter::class, [
                'field_type' => EntityType::class,
                'field_options' => ['class' => Category::class],
            ])
            ->add('tags', ModelFilter::class, [
                'field_type' => EntityType::class,
                'field_options' => ['class' => Tag::class, 'multiple' => true],
            ])
            ->add('featured', BooleanFilter::class)
            // The picker types as filter field types, which is how the application uses them.
            ->add('availableFrom', DateRangeFilter::class, ['field_type' => DateRangePickerType::class])
            ->add('releasedAt', DateTimeRangeFilter::class, ['field_type' => DateTimeRangePickerType::class])
            // A filter over a property no column holds, which is the shape the application's nine
            // callback filters take.
            ->add('inStock', CallbackFilter::class, [
                'callback' => $this->filterInStock(...),
                'field_type' => BooleanType::class,
                'label' => 'In stock',
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Details', ['class' => 'col-span-8'])
                ->add('name', TextType::class)
                ->add('sku', TextType::class, ['help' => 'Unique stock keeping unit.'])
                ->add('price', IntegerType::class, ['help' => 'In minor units.'])
                // The combobox in its form context: `_context` is absent, so the action resolves
                // the field description from the *form* rather than from the datagrid.
                ->add('category', ModelAutocompleteType::class, ['property' => 'name'])
                ->add('tags', ModelAutocompleteType::class, [
                    'property' => 'name',
                    'multiple' => true,
                    'required' => false,
                ])
            ->end()
            ->with('Publication', ['class' => 'col-span-4'])
                ->add('status', EnumType::class, ['class' => ProductStatus::class])
                ->add('featured', BooleanType::class, ['transform' => true])
                ->add('releasedAt', DateTimePickerType::class, [
                    'required' => false,
                    // Minutes, not seconds: `yyyy-MM-dd'T'HH:mm` on the wire and no `step` on the
                    // input, which is what an application that shows a release time wants.
                    'datepicker_options' => ['display' => ['components' => [
                        'calendar' => true,
                        'clock' => true,
                        'seconds' => false,
                    ]]],
                ])
                ->add('availableFrom', DatePickerType::class, ['required' => false])
                // Time only: no calendar, so the widget is `<input type="time">`.
                ->add('pickupAt', DateTimePickerType::class, [
                    'required' => false,
                    'datepicker_options' => ['display' => ['components' => [
                        'calendar' => false,
                        'clock' => true,
                        'seconds' => false,
                    ]]],
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
            ->add('stock')
            ->add('status', FieldDescriptionInterface::TYPE_ENUM)
            ->add('category')
            ->add('tags', FieldDescriptionInterface::TYPE_MANY_TO_MANY)
            ->add('featured')
            ->add('availableFrom', FieldDescriptionInterface::TYPE_DATE)
            ->add('pickupAt', FieldDescriptionInterface::TYPE_TIME)
            ->add('releasedAt')
            ->add('specification', FieldDescriptionInterface::TYPE_ARRAY)
            ->add('highlights', FieldDescriptionInterface::TYPE_HTML)
            ->add('description', FieldDescriptionInterface::TYPE_TEXTAREA);
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
     * `true` means "stock left", `false` means "none": `BooleanType` submits 1 and 2, and an
     * unset filter never reaches here.
     *
     * @phpstan-param ProxyQueryInterface<Product> $query
     */
    private function filterInStock(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool
    {
        if (!$data->hasValue()) {
            return false;
        }

        $query->getQueryBuilder()->andWhere(\sprintf(
            '%s.stock %s 0',
            $alias,
            BooleanType::TYPE_YES === $data->getValue() ? '>' : '='
        ));

        return true;
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
