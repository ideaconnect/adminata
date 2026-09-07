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

namespace Sonata\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * Class BasePickerType (to factorize DatePickerType and DateTimePickerType code.
 *
 * @author Hugo Briand <briand@ekino.com>
 */
abstract class BasePickerType extends AbstractType implements LocaleAwareInterface
{
    /**
     * @var array<string, array<string>|string>
     */
    private const array DATEPICKER_ALLOWED_OPTIONS = [
        'allowInputToggle' => 'bool',
        'dateRange' => 'bool',
        'debug' => 'bool',
        'defaultDate' => ['string', \DateTimeInterface::class],
        'keepInvalid' => 'bool',
        'multipleDates' => 'bool',
        'multipleDatesSeparator' => 'string',
        'promptTimeOnDateChange' => 'bool',
        'promptTimeOnDateChangeTransitionDelay' => 'integer',
        'stepping' => 'integer',
        'useCurrent' => 'bool',
        'viewDate' => ['string', \DateTimeInterface::class],
    ];

    /**
     * @var array<string, array<string>|string>
     */
    private const array RESTRICTIONS_OPTIONS = [
        'minDate' => ['string', \DateTimeInterface::class],
        'maxDate' => ['string', \DateTimeInterface::class],
        'disabledDates' => ['string[]', 'DateTimeInterface[]'],
        'enabledDates' => ['string[]', 'DateTimeInterface[]'],
        'daysOfWeekDisabled' => 'integer[]',
        'disabledHours' => 'integer[]',
        'enabledHours' => 'integer[]',
    ];

    /**
     * @var array<string, array<string>|string>
     */
    private const array LOCALIZATION_OPTIONS = [
        'locale' => 'string',
        'hourCycle' => 'string',
    ];

    /**
     * @var array<string, array<string>|string>
     */
    private const array DISPLAY_OPTIONS = [
        'sideBySide' => 'bool',
        'calendarWeeks' => 'bool',
        'viewMode' => 'string',
        'toolbarPlacement' => 'string',
        'keepOpen' => 'bool',
        'inline' => 'bool',
        'theme' => 'string',
    ];

    /**
     * @var array<string, array<string>|string>
     */
    private const array DISPLAY_ICONS_OPTIONS = [
        'time' => 'string',
        'date' => 'string',
        'up' => 'string',
        'down' => 'string',
        'previous' => 'string',
        'next' => 'string',
        'today' => 'string',
        'clear' => 'string',
        'close' => 'string',
    ];

    /**
     * @var array<string, array<string>|string>
     */
    private const array DISPLAY_BUTTONS_OPTIONS = [
        'today' => 'bool',
        'clear' => 'bool',
        'close' => 'bool',
    ];

    /**
     * @var array<string, array<string>|string>
     */
    private const array DISPLAY_COMPONENTS_OPTIONS = [
        'calendar' => 'bool',
        'date' => 'bool',
        'month' => 'bool',
        'year' => 'bool',
        'decades' => 'bool',
        'clock' => 'bool',
        'hours' => 'bool',
        'minutes' => 'bool',
        'seconds' => 'bool',
    ];

    public function __construct(
        private string $locale,
    ) {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults($this->getCommonDefaults());

        /**
         * TODO: use `setOptions` directly once we drop support for Symfony < 7.3.
         *
         * @phpstan-ignore function.alreadyNarrowedType
         */
        $resolverSetOptionsMethod = method_exists($resolver, 'setOptions') ? 'setOptions' : 'setDefault';
        /* @phpstan-ignore method.dynamicName */
        $resolver->{$resolverSetOptionsMethod}('datepicker_options', function (OptionsResolver $datePickerResolver) use ($resolverSetOptionsMethod) {
            $datePickerResolver->setDefined(array_keys(self::DATEPICKER_ALLOWED_OPTIONS));

            foreach (self::DATEPICKER_ALLOWED_OPTIONS as $option => $allowedTypes) {
                $datePickerResolver->setAllowedTypes($option, $allowedTypes);
            }

            $datePickerResolver->setNormalizer('defaultDate', $this->dateTimeNormalizer());
            $datePickerResolver->setNormalizer('viewDate', $this->dateTimeNormalizer());

            $defaults = $this->getCommonDatepickerDefaults();

            $datePickerResolver->setDefaults($defaults);
            /* @phpstan-ignore method.dynamicName */
            $datePickerResolver->{$resolverSetOptionsMethod}('localization', $this->defineLocalizationOptions($defaults['localization'] ?? []));
            /* @phpstan-ignore method.dynamicName */
            $datePickerResolver->{$resolverSetOptionsMethod}('restrictions', $this->defineRestrictionsOptions($defaults['restrictions'] ?? []));
            /* @phpstan-ignore method.dynamicName */
            $datePickerResolver->{$resolverSetOptionsMethod}('display', $this->defineDisplayOptions($defaults['display'] ?? []));
        });

        $resolver->setNormalizer(
            'format',
            static function (Options $options, int|string $format): string {
                $components = $options['datepicker_options']['display']['components'] ?? [];
                \assert(\is_array($components));

                $derived = self::html5Format(
                    false !== ($components['calendar'] ?? true),
                    false !== ($components['clock'] ?? true),
                    true === ($components['seconds'] ?? false),
                );

                // An explicit pattern is refused the way Symfony's DateType refuses one when
                // `html5` is enabled: the widget is a native input and the browser owns how the
                // value is displayed. An int is one of the IntlDateFormatter constants, which is
                // what the type itself defaults to, so it is derived silently.
                if (\is_string($format) && $format !== $derived) {
                    throw new LogicException(\sprintf(
                        'Cannot use the "format" option of "%s": it renders a native HTML5 input,'
                        .' whose format the browser decides. Remove the option — with the current'
                        .' "datepicker_options.display.components" the value is exchanged as "%s".',
                        static::class,
                        $derived,
                    ));
                }

                return $derived;
            }
        );

        $resolver->setAllowedTypes('datepicker_use_button', 'bool');
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $datePickerOptions = $options['datepicker_options'] ?? [];

        if (isset($datePickerOptions['display']['icons'])
            && [] === $datePickerOptions['display']['icons']) {
            unset($datePickerOptions['display']['icons']);
        }

        if (isset($datePickerOptions['display']['buttons'])
            && [] === $datePickerOptions['display']['buttons']) {
            unset($datePickerOptions['display']['buttons']);
        }

        if (isset($datePickerOptions['display']['components'])
            && [] === $datePickerOptions['display']['components']) {
            unset($datePickerOptions['display']['components']);
        }

        if (isset($datePickerOptions['display'])
            && [] === $datePickerOptions['display']) {
            unset($datePickerOptions['display']);
        }

        if (isset($datePickerOptions['restrictions'])
            && [] === $datePickerOptions['restrictions']) {
            unset($datePickerOptions['restrictions']);
        }

        $view->vars['datepicker_options'] = $datePickerOptions;
        $view->vars['datepicker_use_button'] = $options['datepicker_use_button'] ?? false;
    }

    /**
     * Gets base default options for the form types
     * (except `datepicker_options` which should be handled with `getCommonDatepickerDefaults()`).
     *
     * @return array<string, mixed>
     */
    protected function getCommonDefaults(): array
    {
        return [
            'widget' => 'single_text',
            'datepicker_use_button' => true,
            'html5' => false,
        ];
    }

    /**
     * Gets base default options for the `datepicker_options` option.
     *
     * @return array<string, mixed>
     */
    protected function getCommonDatepickerDefaults(): array
    {
        return [
            'display' => [
                'theme' => 'light',
            ],
            'localization' => [
                'locale' => str_replace('_', '-', $this->locale),
            ],
        ];
    }

    /**
     * The ICU pattern a native date, time or datetime-local input exchanges its value in.
     */
    private static function html5Format(bool $calendar, bool $clock, bool $seconds): string
    {
        if (!$clock) {
            return DateType::HTML5_FORMAT;
        }

        $time = $seconds ? 'HH:mm:ss' : 'HH:mm';

        return $calendar ? "yyyy-MM-dd'T'".$time : $time;
    }

    /**
     * @param array<string, mixed> $defaults
     *
     * @return \Closure(OptionsResolver): void
     */
    private function defineLocalizationOptions(array $defaults): callable
    {
        return static function (OptionsResolver $resolver) use ($defaults): void {
            $resolver->setDefined(array_keys(self::LOCALIZATION_OPTIONS));

            foreach (self::LOCALIZATION_OPTIONS as $option => $allowedTypes) {
                $resolver->setAllowedTypes($option, $allowedTypes);
            }

            $resolver->setDefaults($defaults);
        };
    }

    /**
     * @param array<string, mixed> $defaults
     *
     * @return \Closure(OptionsResolver): void
     */
    private function defineRestrictionsOptions(array $defaults): callable
    {
        return function (OptionsResolver $resolver) use ($defaults): void {
            $resolver->setDefined(array_keys(self::RESTRICTIONS_OPTIONS));

            foreach (self::RESTRICTIONS_OPTIONS as $option => $allowedTypes) {
                $resolver->setAllowedTypes($option, $allowedTypes);
            }

            $resolver->setAllowedValues(
                'daysOfWeekDisabled',
                static fn (array $value) => array_filter(
                    $value,
                    static fn ($day) => \is_int($day) && $day >= 0 && $day <= 6
                ) === $value
            );

            $resolver->setAllowedValues(
                'enabledHours',
                static fn (array $value) => array_filter(
                    $value,
                    static fn ($hour) => \is_int($hour) && $hour >= 0 && $hour <= 23
                ) === $value
            );

            $resolver->setAllowedValues(
                'disabledHours',
                static fn (array $value) => array_filter(
                    $value,
                    static fn ($hour) => \is_int($hour) && $hour >= 0 && $hour <= 23
                ) === $value
            );

            $resolver->setNormalizer('minDate', $this->dateTimeNormalizer());
            $resolver->setNormalizer('maxDate', $this->dateTimeNormalizer());
            $resolver->setNormalizer('disabledDates', $this->dateTimeNormalizer());
            $resolver->setNormalizer('enabledDates', $this->dateTimeNormalizer());

            $resolver->setDefaults($defaults);
        };
    }

    /**
     * @param array<string, mixed> $defaults
     *
     * @return \Closure(OptionsResolver): void
     */
    private function defineDisplayOptions(array $defaults): callable
    {
        return function (OptionsResolver $resolver) use ($defaults): void {
            $resolver->setDefined(array_keys(self::DISPLAY_OPTIONS));

            foreach (self::DISPLAY_OPTIONS as $option => $allowedTypes) {
                $resolver->setAllowedTypes($option, $allowedTypes);
            }

            $resolver->setAllowedValues('viewMode', ['clock', 'calendar', 'months', 'years', 'decades']);
            $resolver->setAllowedValues('toolbarPlacement', ['top', 'bottom']);
            $resolver->setAllowedValues('theme', ['light', 'dark', 'auto']);

            $resolver->setDefaults($defaults);

            /**
             * TODO: use `setOptions` directly once we drop support for Symfony < 7.3.
             *
             * @phpstan-ignore function.alreadyNarrowedType
             */
            $resolverSetOptionsMethod = method_exists($resolver, 'setOptions') ? 'setOptions' : 'setDefault';

            /* @phpstan-ignore method.dynamicName */
            $resolver->{$resolverSetOptionsMethod}('icons', $this->defineDisplayIconsOptions($defaults['icons'] ?? []));
            /* @phpstan-ignore method.dynamicName */
            $resolver->{$resolverSetOptionsMethod}('buttons', $this->defineDisplayButtonsOptions($defaults['buttons'] ?? []));
            /* @phpstan-ignore method.dynamicName */
            $resolver->{$resolverSetOptionsMethod}('components', $this->defineDisplayComponentsOptions($defaults['components'] ?? []));
        };
    }

    /**
     * @param array<string, mixed> $defaults
     *
     * @return \Closure(OptionsResolver): void
     */
    private function defineDisplayIconsOptions(array $defaults): callable
    {
        return static function (OptionsResolver $resolver) use ($defaults): void {
            $resolver->setDefined(array_keys(self::DISPLAY_ICONS_OPTIONS));

            foreach (self::DISPLAY_ICONS_OPTIONS as $option => $allowedTypes) {
                $resolver->setAllowedTypes($option, $allowedTypes);
            }

            $resolver->setDefaults($defaults);
        };
    }

    /**
     * @param array<string, mixed> $defaults
     *
     * @return \Closure(OptionsResolver): void
     */
    private function defineDisplayButtonsOptions(array $defaults): callable
    {
        return static function (OptionsResolver $resolver) use ($defaults): void {
            $resolver->setDefined(array_keys(self::DISPLAY_BUTTONS_OPTIONS));

            foreach (self::DISPLAY_BUTTONS_OPTIONS as $option => $allowedTypes) {
                $resolver->setAllowedTypes($option, $allowedTypes);
            }

            $resolver->setDefaults($defaults);
        };
    }

    /**
     * @param array<string, mixed> $defaults
     *
     * @return \Closure(OptionsResolver): void
     */
    private function defineDisplayComponentsOptions(array $defaults): callable
    {
        return static function (OptionsResolver $resolver) use ($defaults): void {
            $resolver->setDefined(array_keys(self::DISPLAY_COMPONENTS_OPTIONS));

            foreach (self::DISPLAY_COMPONENTS_OPTIONS as $option => $allowedTypes) {
                $resolver->setAllowedTypes($option, $allowedTypes);
            }

            $resolver->setDefaults($defaults);
        };
    }

    private function dateTimeNormalizer(): \Closure
    {
        return static function (OptionsResolver $options, string|array|\DateTimeInterface $value): string|array {
            if ($value instanceof \DateTimeInterface) {
                return $value->format(\DateTimeInterface::ATOM);
            }

            if (\is_array($value)) {
                foreach ($value as $key => $singleValue) {
                    if ($singleValue instanceof \DateTimeInterface) {
                        $value[$key] = $singleValue->format(\DateTimeInterface::ATOM);
                    }
                }
            }

            return $value;
        };
    }
}
