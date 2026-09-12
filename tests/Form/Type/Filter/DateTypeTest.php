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

namespace IDCT\Adminata\Tests\Form\Type\Filter;

use IDCT\Adminata\Form\Type\Filter\DateType;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * NEXT_MAJOR: Remove this class.
 */
#[IgnoreDeprecations]
final class DateTypeTest extends BaseTypeTestCase
{
    public function testDefaultOptions(): void
    {
        $form = $this->factory->create($this->getTestedType());

        $view = $form->createView();

        static::assertFalse($view->children['type']->vars['required']);
        static::assertFalse($view->children['value']->vars['required']);
    }

    protected function getTestedType(): string
    {
        return DateType::class;
    }
}
