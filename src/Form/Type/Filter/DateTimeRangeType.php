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

namespace IDCT\Adminata\Form\Type\Filter;

use IDCT\Adminata\Form\Type\DateTimeRangeType as FormDateTimeRangeType;
use IDCT\Adminata\Form\Type\Operator\DateRangeOperatorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * NEXT_MAJOR: Remove this form.
 *
 * @deprecated since sonata-project/admin-bundle version 4.14 use the FilterDataType instead
 */
final class DateTimeRangeType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'adminata_type_filter_datetime_range';
    }

    public function getParent(): string
    {
        return FilterDataType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'operator_type' => DateRangeOperatorType::class,
            'field_type' => FormDateTimeRangeType::class,
            'field_options' => [
                'field_options' => [],
            ],
        ]);
    }
}
