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

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormTypeInterface;

final class FormChoiceWidgetTest extends BaseWidgetTestCase
{
    protected $type = 'form';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testLabelRendering(): void
    {
        $choices = array_flip(['some', 'choices']);

        $choice = $this->factory->create(
            $this->getChoiceClass(),
            null,
            $this->getDefaultOption() + [
                'multiple' => true,
                'expanded' => true,
            ] + compact('choices')
        );

        $html = $this->renderWidget($choice->createView());

        // TODO: Remove this adapter when dropping support for Symfony < 7.
        $html = str_replace('value="0" />', 'value="0">', $html);

        // The checkbox *is* its label: `adm-checkbox-label` is the flex row, so Bootstrap's
        // wrapping `<div class="checkbox">` is gone. `control-label__text` is not (PLAN/02 §8).
        static::assertStringContainsString(
            '<li><label class="adm-checkbox-label"><input type="checkbox" id="choice_0" name="choice[]"'
            .' class="adm-checkbox" value="0"><span class="control-label__text">[trans]some[/trans]</span>'
            .'</label></li>',
            $this->cleanHtmlWhitespace($html)
        );
    }

    /**
     * PLAN/05 R4 and PLAN/06 §1: the theme appends its class and touches nothing else, which is
     * what lets a ux-autocomplete select — or any of the application's own controllers — keep
     * working on an adminata page.
     */
    public function testAttributesArePassedThroughUntouched(): void
    {
        $choice = $this->factory->create(
            $this->getChoiceClass(),
            null,
            $this->getDefaultOption() + [
                'attr' => [
                    'class' => 'app-choice',
                    'data-controller' => 'symfony--ux-autocomplete--autocomplete',
                    'data-app-target' => 'city',
                ],
            ]
        );

        $html = $this->cleanHtmlWhitespace($this->renderWidget($choice->createView()));

        static::assertStringContainsString('data-controller="symfony--ux-autocomplete--autocomplete"', $html);
        static::assertStringContainsString('data-app-target="city"', $html);
        static::assertStringContainsString('class="app-choice adm-select"', $html);
    }

    public function testAnExpandedChoiceKeepsItsAttributes(): void
    {
        $choices = array_flip(['some', 'choices']);

        $choice = $this->factory->create(
            $this->getChoiceClass(),
            null,
            $this->getDefaultOption() + [
                'expanded' => true,
                'attr' => ['data-controller' => 'app--choice'],
            ] + compact('choices')
        );

        $html = $this->cleanHtmlWhitespace($this->renderWidget($choice->createView()));

        static::assertStringContainsString('data-controller="app--choice"', $html);
        static::assertStringContainsString('class="adm-choice-list"', $html);
    }

    public function testDefaultValueRendering(): void
    {
        $choice = $this->factory->create(
            $this->getChoiceClass(),
            null,
            $this->getDefaultOption()
        );

        $html = $this->renderWidget($choice->createView());

        static::assertStringContainsString(
            '<option value="" selected="selected">[trans]Choose an option[/trans]</option>',
            $this->cleanHtmlWhitespace($html)
        );
    }

    public function testRequiredIsDisabledForEmptyPlaceholder(): void
    {
        $choice = $this->factory->create(
            $this->getChoiceClass(),
            null,
            $this->getRequiredOption()
        );

        $html = $this->renderWidget($choice->createView());

        static::assertStringNotContainsString(
            'required="required"',
            $this->cleanHtmlWhitespace($html)
        );
    }

    public function testRequiredIsEnabledIfPlaceholderIsSet(): void
    {
        $choice = $this->factory->create(
            $this->getChoiceClass(),
            null,
            array_merge($this->getRequiredOption(), $this->getDefaultOption())
        );

        $html = $this->renderWidget($choice->createView());

        static::assertStringContainsString(
            'required="required"',
            $this->cleanHtmlWhitespace($html)
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRequiredOption(): array
    {
        return ['required' => true];
    }

    /**
     * @return class-string<FormTypeInterface>
     */
    protected function getChoiceClass(): string
    {
        return ChoiceType::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultOption(): array
    {
        return [
            'placeholder' => 'Choose an option',
        ];
    }
}
