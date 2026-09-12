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

namespace IDCT\Adminata\Tests\Form\Type;

use IDCT\Adminata\Form\Type\DateRangeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DateRangeTypeTest extends TypeTestCase
{
    public function testGetDefaultOptions(): void
    {
        $type = new DateRangeType();

        static::assertSame('adminata_type_date_range', $type->getBlockPrefix());

        $type->configureOptions($resolver = new OptionsResolver());

        $options = $resolver->resolve();

        static::assertSame(
            [
                'field_options' => [],
                'field_options_start' => [],
                'field_options_end' => [],
                'field_type' => DateType::class,
            ],
            $options
        );
    }
}
