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

use IDCT\Adminata\Form\Type\DatePickerType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
final class DatePickerTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testParentIsDateType(): void
    {
        $form = new DatePickerType(
            'en',
        );

        static::assertSame(DateType::class, $form->getParent());
    }

    public function testGetName(): void
    {
        $type = new DatePickerType(
            'en',
        );

        static::assertSame('adminata_type_datetime_picker', $type->getBlockPrefix());
    }

    public function testSubmitValidData(): void
    {
        \Locale::setDefault('en');
        $form = $this->factory->create(DatePickerType::class, new \DateTime('2018-06-03'), [
            // An IntlDateFormatter constant is ignored: the widget is a native <input type="date">
            // and exchanges its value as yyyy-MM-dd whatever the locale would display.
            'format' => \IntlDateFormatter::LONG,
            'html5' => false,
        ]);

        static::assertSame('2018-06-03', $form->getViewData());
        $form->submit('2018-06-05');
        static::assertSame('2018-06-05', $form->getData()->format('Y-m-d'));
        static::assertTrue($form->isSynchronized());
    }

    public function testTheFormatIsTheHtml5One(): void
    {
        $form = $this->factory->create(DatePickerType::class);

        static::assertSame('yyyy-MM-dd', $form->getConfig()->getOption('format'));
    }

    public function testACustomFormatIsRefused(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot use the "format" option of "IDCT\\Adminata\\Form\\Type\\DatePickerType"');

        $this->factory->create(DatePickerType::class, null, ['format' => 'dd.MM.yyyy']);
    }

    public function testDateConversion(): void
    {
        \Locale::setDefault('en');
        $form = $this->factory->create(DatePickerType::class, new \DateTime('2018-06-03'), [
            'format' => 'yyyy-MM-dd',
            'html5' => false,
            'datepicker_options' => [
                'restrictions' => [
                    'minDate' => new \DateTime('2018-06-01'),
                    'disabledDates' => [new \DateTime('2018-06-02')],
                ],
            ],
        ]);

        static::assertSame('2018-06-03', $form->getViewData());
        $form->submit('2018-06-05');
        static::assertSame('2018-06-05', $form->getData()->format('Y-m-d'));
        static::assertTrue($form->isSynchronized());
    }

    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        $type = new DatePickerType('en');

        return [
            new PreloadedExtension([$type], []),
        ];
    }
}
