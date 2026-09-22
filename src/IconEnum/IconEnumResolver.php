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

namespace IDCT\Adminata\IconEnum;

use IDCT\Adminata\FieldDescription\FieldDescriptionInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * What an icon-enum column draws: the case a value renders as, and every case the column
 * can show — its legend.
 *
 * Read from the field description, all optional:
 *
 *  - `cases` — `['Paid' => ['icon' => 'fas fa-check', 'tone' => 'success', 'label' => 'Paid']]`:
 *    glyph, tone and label per case, each key optional. The whole configuration for an
 *    enum that does not implement {@see IconEnumInterface}, an override of single cases for
 *    one that does. Keyed by the case NAME for an enum, by the value itself for a column
 *    holding a string or an integer.
 *  - `class` — the enum, for the legend and for a value the field mapping does not type
 *    (a virtual field); defaults to the mapping's `enumType`. With neither, the legend is
 *    the `cases` keys, in their order.
 *  - `empty` — `['icon' => …, 'tone' => …, 'label' => …]` drawn for a null value. Without
 *    it a null value draws nothing. Listed last in the legend.
 *  - `use_value`, `enum_translation_domain` — the label rules of `TYPE_ENUM`: the enum's
 *    own `trans()` when it implements Symfony's `TranslatableInterface`, otherwise the case
 *    name or, with `use_value`, the backing value, through the domain when one is set. A
 *    label from `cases` or `empty` goes through the domain too.
 *  - `legend` — `false` keeps the column out of the legend. Read by the list template.
 *
 * A case with no glyph from either source is a configuration error and throws: a column
 * that is nothing but glyphs cannot draw a case as nothing.
 */
final readonly class IconEnumResolver
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * The case `$value` renders as; null for a null value the field draws nothing for.
     */
    public function resolve(mixed $value, FieldDescriptionInterface $fieldDescription): ?IconEnumCase
    {
        if (null === $value) {
            return $this->emptyCase($fieldDescription);
        }

        if (!$value instanceof \UnitEnum && !\is_string($value) && !\is_int($value)) {
            throw new \LogicException(\sprintf(
                'Field "%s": an icon enum draws an enum, a string or an integer, not %s.',
                $fieldDescription->getName(),
                get_debug_type($value),
            ));
        }

        return $this->case($value, $fieldDescription);
    }

    /**
     * Every case the column can show, in the enum's declaration order (or the order of the
     * `cases` option for a scalar column), the `empty` case last.
     *
     * @return list<IconEnumCase>
     */
    public function legend(FieldDescriptionInterface $fieldDescription): array
    {
        $legend = [];
        $class = $this->enumClass($fieldDescription);

        if (null !== $class) {
            foreach ($class::cases() as $case) {
                $legend[] = $this->case($case, $fieldDescription);
            }
        } else {
            foreach (array_keys($this->configuredCases($fieldDescription)) as $key) {
                $legend[] = $this->case($key, $fieldDescription);
            }
        }

        $empty = $this->emptyCase($fieldDescription);
        if (null !== $empty) {
            $legend[] = $empty;
        }

        return $legend;
    }

    private function case(\UnitEnum|string|int $value, FieldDescriptionInterface $fieldDescription): IconEnumCase
    {
        $key = $value instanceof \UnitEnum ? $value->name : (string) $value;
        $configured = $this->configuredCases($fieldDescription)[$key] ?? [];

        if (!\is_array($configured)) {
            throw new \LogicException(\sprintf(
                'Field "%s": the "cases" option entry for "%s" must be an array, %s given.',
                $fieldDescription->getName(),
                $key,
                get_debug_type($configured),
            ));
        }

        $icon = $configured['icon'] ?? ($value instanceof IconEnumInterface ? $value->icon() : null);

        if (!\is_string($icon) || '' === $icon) {
            throw new \LogicException(\sprintf(
                'Field "%s": no icon for case "%s". Implement %s on %s or name the case in the "cases" option.',
                $fieldDescription->getName(),
                $key,
                IconEnumInterface::class,
                $value instanceof \UnitEnum ? $value::class : 'the field',
            ));
        }

        $tone = $this->tone($configured['tone'] ?? null, $fieldDescription, $key)
            ?? ($value instanceof IconEnumInterface ? $value->tone() : IconEnumTone::Neutral);

        $label = $configured['label'] ?? null;

        if (null !== $label && !\is_string($label)) {
            throw new \LogicException(\sprintf(
                'Field "%s": the label of case "%s" must be a string, %s given.',
                $fieldDescription->getName(),
                $key,
                get_debug_type($label),
            ));
        }

        return new IconEnumCase(
            $key,
            $icon,
            $tone,
            $this->translate($label ?? $this->defaultLabel($value, $fieldDescription), $fieldDescription),
        );
    }

    private function emptyCase(FieldDescriptionInterface $fieldDescription): ?IconEnumCase
    {
        $empty = $fieldDescription->getOption('empty');

        if (null === $empty) {
            return null;
        }

        if (!\is_array($empty) || !\is_string($empty['icon'] ?? null) || '' === $empty['icon'] || !\is_string($empty['label'] ?? null)) {
            throw new \LogicException(\sprintf(
                'Field "%s": the "empty" option needs an "icon" and a "label", both strings.',
                $fieldDescription->getName(),
            ));
        }

        return new IconEnumCase(
            '',
            $empty['icon'],
            $this->tone($empty['tone'] ?? null, $fieldDescription, '') ?? IconEnumTone::Neutral,
            $this->translate($empty['label'], $fieldDescription),
        );
    }

    /**
     * The label TYPE_ENUM would show: the enum's own translation, else its name or value.
     */
    private function defaultLabel(\UnitEnum|string|int $value, FieldDescriptionInterface $fieldDescription): string
    {
        if ($value instanceof TranslatableInterface) {
            return $value->trans($this->translator);
        }

        if ($value instanceof \BackedEnum && true === $fieldDescription->getOption('use_value', false)) {
            return (string) $value->value;
        }

        return $value instanceof \UnitEnum ? $value->name : (string) $value;
    }

    private function translate(string $label, FieldDescriptionInterface $fieldDescription): string
    {
        $domain = $fieldDescription->getOption('enum_translation_domain');

        if (!\is_string($domain)) {
            return $label;
        }

        return $this->translator->trans($label, [], $domain);
    }

    private function tone(mixed $tone, FieldDescriptionInterface $fieldDescription, string $key): ?IconEnumTone
    {
        if (null === $tone) {
            return null;
        }

        if ($tone instanceof IconEnumTone) {
            return $tone;
        }

        $resolved = \is_string($tone) ? IconEnumTone::tryFrom($tone) : null;

        if (null === $resolved) {
            throw new \LogicException(\sprintf(
                'Field "%s": unknown tone for case "%s"; one of %s expected.',
                $fieldDescription->getName(),
                $key,
                implode(', ', array_map(static fn (IconEnumTone $t): string => '"'.$t->value.'"', IconEnumTone::cases())),
            ));
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    private function configuredCases(FieldDescriptionInterface $fieldDescription): array
    {
        $cases = $fieldDescription->getOption('cases', []);

        if (!\is_array($cases)) {
            throw new \LogicException(\sprintf(
                'Field "%s": the "cases" option must be an array keyed by case name, %s given.',
                $fieldDescription->getName(),
                get_debug_type($cases),
            ));
        }

        $keyed = [];
        foreach ($cases as $key => $case) {
            $keyed[(string) $key] = $case;
        }

        return $keyed;
    }

    /**
     * @return class-string<\UnitEnum>|null
     */
    private function enumClass(FieldDescriptionInterface $fieldDescription): ?string
    {
        $class = $fieldDescription->getOption('class') ?? ($fieldDescription->getFieldMapping()['enumType'] ?? null);

        if (null === $class) {
            return null;
        }

        if (!\is_string($class) || !enum_exists($class)) {
            throw new \LogicException(\sprintf(
                'Field "%s": "%s" is not an enum.',
                $fieldDescription->getName(),
                \is_string($class) ? $class : get_debug_type($class),
            ));
        }

        return $class;
    }
}
