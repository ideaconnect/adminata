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

use PHPUnit\Framework\Attributes\DataProvider;
use IDCT\Adminata\Form\Type\DateTimePickerType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
final class DateTimePickerTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testParentIsDateTimeType(): void
    {
        $form = new DateTimePickerType(
            'en'
        );

        static::assertSame(DateTimeType::class, $form->getParent());
    }

    public function testGetName(): void
    {
        $type = new DateTimePickerType(
            'en'
        );

        static::assertSame('adminata_type_datetime_picker', $type->getBlockPrefix());
    }

    public function testSubmitUnmatchingDateFormat(): void
    {
        \Locale::setDefault('en');
        $form = $this->factory->create(DateTimePickerType::class, new \DateTime('2018-06-03 20:02:03'), [
            'format' => \IntlDateFormatter::NONE,
            'datepicker_options' => [
                'display' => [
                    'components' => [
                        'calendar' => false,
                        'seconds' => true,
                    ],
                ],
            ],
            'html5' => false,
        ]);

        $form->submit('05:23');
        static::assertFalse($form->isSynchronized());
    }

    public function testSubmitMatchingDateFormat(): void
    {
        \Locale::setDefault('en');
        $form = $this->factory->create(DateTimePickerType::class, new \DateTime('2018-06-03 20:02:03'), [
            'format' => \IntlDateFormatter::NONE,
            'datepicker_options' => [
                'display' => [
                    'components' => [
                        'calendar' => false,
                        'seconds' => false,
                    ],
                ],
            ],
            'html5' => false,
        ]);

        // A time-only picker exchanges its value the way <input type="time"> does.
        static::assertSame('20:02', $form->getViewData());

        $form->submit('05:23');
        static::assertSame('1970-01-01 05:23:00', $form->getData()->format('Y-m-d H:i:s'));
        static::assertTrue($form->isSynchronized());
    }

    /**
     * @param array<string, bool> $components
     */
    #[DataProvider('provideTheFormatIsDerivedFromTheComponentsCases')]
    public function testTheFormatIsDerivedFromTheComponents(array $components, string $expected): void
    {
        $form = $this->factory->create(DateTimePickerType::class, null, [
            'datepicker_options' => [
                'display' => [
                    'components' => $components,
                ],
            ],
        ]);

        static::assertSame($expected, $form->getConfig()->getOption('format'));
    }

    /**
     * @return iterable<array-key, array{array<string, bool>, string}>
     */
    public static function provideTheFormatIsDerivedFromTheComponentsCases(): iterable
    {
        yield 'date only' => [['clock' => false], 'yyyy-MM-dd'];
        yield 'time only' => [['calendar' => false, 'seconds' => false], 'HH:mm'];
        yield 'time only with seconds' => [['calendar' => false, 'seconds' => true], 'HH:mm:ss'];
        yield 'date and time' => [['seconds' => false], "yyyy-MM-dd'T'HH:mm"];
        yield 'date and time with seconds' => [['seconds' => true], "yyyy-MM-dd'T'HH:mm:ss"];
    }

    public function testACustomFormatIsRefused(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('it renders a native HTML5 input');

        $this->factory->create(DateTimePickerType::class, null, ['format' => 'dd.MM.yyyy HH:mm']);
    }

    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        $type = new DateTimePickerType('en');

        return [
            new PreloadedExtension([$type], []),
        ];
    }
}
