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

namespace IDCT\Adminata\Tests\Form\Widget;

use PHPUnit\Framework\Attributes\DataProvider;
use IDCT\Adminata\Form\Type\DatePickerType;
use IDCT\Adminata\Form\Type\DateTimePickerType;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * `datepicker.html.twig` renders native inputs (PLAN/06 §4).
 *
 * The three `display.components` booleans decide the input type, and they are the same three
 * `BasePickerType` derives the wire format from — so a mismatch between what the browser sends and
 * what the type parses would show up here as the wrong `type` attribute.
 */
final class DatePickerWidgetTest extends FormIntegrationTestCase
{
    private FormRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new \ReflectionClass(TwigRendererEngine::class);
        static::assertNotFalse($reflection->getFileName());

        $loader = new FilesystemLoader([
            __DIR__.'/../../../src/Resources/views/Form',
            \dirname($reflection->getFileName()).'/../Resources/views/Form',
        ]);

        $environment = new Environment($loader, ['strict_variables' => true]);
        $environment->addExtension(new FormExtension());
        $environment->addExtension(new TranslationExtension(static::createStub(TranslatorInterface::class)));

        $engine = new TwigRendererEngine(['datepicker.html.twig', 'form_div_layout.html.twig'], $environment);

        $environment->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static fn (): FormRendererInterface => new FormRenderer($engine),
        ]));

        $renderer = $environment->getRuntime(FormRenderer::class);
        static::assertInstanceOf(FormRenderer::class, $renderer);
        $this->renderer = $renderer;
    }

    /**
     * @return iterable<string, array{class-string<FormTypeInterface<mixed>>, array<string, mixed>, array<string, string>}>
     */
    public static function provideTheInputCases(): iterable
    {
        yield 'a date picker is a date input' => [
            DatePickerType::class,
            [],
            ['type' => 'date'],
        ];

        yield 'a datetime picker is a datetime-local input, with seconds by default' => [
            DateTimePickerType::class,
            [],
            ['type' => 'datetime-local', 'step' => '1'],
        ];

        yield 'without seconds there is no step' => [
            DateTimePickerType::class,
            ['datepicker_options' => ['display' => ['components' => ['seconds' => false]]]],
            ['type' => 'datetime-local'],
        ];

        yield 'without a calendar it is a time input' => [
            DateTimePickerType::class,
            ['datepicker_options' => ['display' => ['components' => ['calendar' => false, 'seconds' => false]]]],
            ['type' => 'time'],
        ];

        yield 'without a clock it is a date input' => [
            DateTimePickerType::class,
            ['datepicker_options' => ['display' => ['components' => ['clock' => false]]]],
            ['type' => 'date'],
        ];
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed>                   $options
     * @param array<string, string>                  $expected
     */
    #[DataProvider('provideTheInputCases')]
    public function testTheInput(string $type, array $options, array $expected): void
    {
        $html = $this->render($type, $options);

        foreach ($expected as $attribute => $value) {
            static::assertStringContainsString(\sprintf('%s="%s"', $attribute, $value), $html);
        }

        if (!\array_key_exists('step', $expected)) {
            static::assertStringNotContainsString('step=', $html);
        }

        static::assertStringContainsString('class="adm-input"', $html);
        static::assertStringNotContainsString('data-controller="datepicker"', $html);
        static::assertStringNotContainsString('input-group', $html);
    }

    public function testTheRestrictionsBecomeMinAndMax(): void
    {
        $html = $this->render(DatePickerType::class, [
            'datepicker_options' => [
                'restrictions' => [
                    'minDate' => new \DateTimeImmutable('2026-01-05 09:00:00'),
                    'maxDate' => '2026-12-31',
                ],
            ],
        ]);

        static::assertStringContainsString('min="2026-01-05"', $html);
        static::assertStringContainsString('max="2026-12-31"', $html);
    }

    public function testAMinuteRestrictionIsShapedForATimeInput(): void
    {
        $html = $this->render(DateTimePickerType::class, [
            'datepicker_options' => [
                'display' => ['components' => ['calendar' => false, 'seconds' => false]],
                'restrictions' => ['minDate' => '2026-01-05 07:30:00'],
            ],
        ]);

        static::assertStringContainsString('type="time"', $html);
        static::assertStringContainsString('min="07:30"', $html);
    }

    /**
     * @return list<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        return [new PreloadedExtension([new DatePickerType('en'), new DateTimePickerType('en')], [])];
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed>                   $options
     */
    private function render(string $type, array $options): string
    {
        $form = $this->factory->create($type, null, $options);

        return $this->renderer->searchAndRenderBlock($form->createView(), 'widget');
    }
}
