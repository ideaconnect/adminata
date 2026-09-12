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

namespace IDCT\Adminata\Tests\Form;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\FieldDescription\FieldDescriptionInterface;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;

final class AdminLayoutTest extends AbstractLayoutTestCase
{
    public function testLabel(): void
    {
        $form = $this->factory->createNamed('name', TextType::class);
        $html = $this->renderLabel($form->createView());

        $expression = <<<'EOD'
            /label
                [@class="adm-label col-span-12 mb-0 md:col-span-3 md:pt-2.5 required"]
                [@for="name"]
                [.="[trans]Name[/trans]*"]
            EOD;

        self::assertMatchesXpath($html, $expression);
    }

    public function testLabelWithoutTranslation(): void
    {
        $form = $this->factory->createNamed(
            'name',
            TextType::class,
            null,
            [
                'translation_domain' => false,
            ]
        );
        $html = $this->renderLabel($form->createView());

        $expression = <<<'EOD'
            /label
                [@class="adm-label col-span-12 mb-0 md:col-span-3 md:pt-2.5 required"]
                [@for="name"]
                [.="Name*"]
            EOD;

        self::assertMatchesXpath($html, $expression);
    }

    public function testLabelWithCustomTranslationDomain(): void
    {
        $form = $this->factory->createNamed(
            'name',
            TextType::class,
            null,
            [
                'translation_domain' => 'custom_domain',
            ]
        );
        $html = $this->renderLabel($form->createView());

        $expression = <<<'EOD'
            /label
                [@class="adm-label col-span-12 mb-0 md:col-span-3 md:pt-2.5 required"]
                [@for="name"]
                [.="[trans domain=custom_domain]Name[/trans]*"]
            EOD;

        self::assertMatchesXpath($html, $expression);
    }

    public function testLabelWithAdminTranslationDomain(): void
    {
        $fieldDescription = $this->createFieldDescriptionWithTranslationDomain('adminata_translation_domain');

        $form = $this->factory->createNamed('name', TextType::class, null, [
            'adminata_field_description' => $fieldDescription,
        ]);
        $html = $this->renderLabel($form->createView());

        $expression = <<<'EOD'
            /label
                [@class="adm-label col-span-12 mb-0 md:col-span-3 md:pt-2.5 required"]
                [@for="name"]
                [.="[trans domain=adminata_translation_domain]Name[/trans]*"]
            EOD;

        self::assertMatchesXpath($html, $expression);
    }

    public function testHelp(): void
    {
        $form = $this->factory->createNamed('name', TextType::class, null, [
            'help' => 'Help text test!',
        ]);
        $view = $form->createView();
        $html = $this->renderHelp($view);

        $expression = <<<'EOD'
            /div
                [@id="name_help"]
                [@class="adm-help adminata-field-help help-text"]
                [.="[trans]Help text test![/trans]"]
            EOD;

        self::assertMatchesXpath($html, $expression);
    }

    public function testHelpWithAdminTranslationDomain(): void
    {
        $fieldDescription = $this->createFieldDescriptionWithTranslationDomain('adminata_translation_domain');

        $form = $this->factory->createNamed('name', TextType::class, null, [
            'help' => 'Help text test!',
            'adminata_field_description' => $fieldDescription,
        ]);
        $view = $form->createView();
        $html = $this->renderHelp($view);

        $expression = <<<'EOD'
            /div
                [@id="name_help"]
                [@class="adm-help adminata-field-help help-text"]
                [.="[trans domain=adminata_translation_domain]Help text test![/trans]"]
            EOD;

        self::assertMatchesXpath($html, $expression);
    }

    public function testRowSetId(): void
    {
        $form = $this->factory->createNamed('name', TextType::class);
        $view = $form->createView();
        $html = $this->renderRow($view);

        static::assertStringContainsString(
            '<div id="adminata-field-container-name" class="adm-form-row grid grid-cols-12 items-start gap-x-3 gap-y-1">',
            $html
        );
    }

    public function testRowWithErrors(): void
    {
        $form = $this->factory->createNamed('name', TextType::class);
        $form->addError(new FormError('[trans]Error 1[/trans]'));
        $form->addError(new FormError('[trans]Error 2[/trans]'));
        $form->submit([]);
        $view = $form->createView();
        $html = $this->renderRow($view);

        // The error state lives on the field and on the control, not on the row: `has-error` was
        // Bootstrap's, and what an application selects on is `adminata-field-error`.
        static::assertStringContainsString(
            '<div id="adminata-field-container-name" class="adm-form-row grid grid-cols-12 items-start gap-x-3 gap-y-1">',
            $html
        );
        static::assertStringContainsString('adminata-field-error"', $html);
        static::assertStringContainsString('class="adm-input adm-input-error" aria-invalid="true"', $html);
        static::assertStringContainsString('class="mt-1.5 adminata-field-error-messages"', $html);
    }

    public function testErrors(): void
    {
        $form = $this->factory->createNamed('name', TextType::class);
        $form->addError(new FormError('[trans]Error 1[/trans]'));
        $form->addError(new FormError('[trans]Error 2[/trans]'));
        $view = $form->createView();
        $html = $this->renderErrors($view);

        $expression = <<<'EOD'
            /div
                [@class="adm-alert adm-alert-error"]
                [
                    ./ul
                        [@class="adm-error-list"]
                        [
                            ./li
                                [.=" [trans]Error 1[/trans]"]
                                [
                                    ./i[@class="fas fa-circle-exclamation"]
                                ]
                            /following-sibling::li
                                [.=" [trans]Error 2[/trans]"]
                                [
                                    ./i[@class="fas fa-circle-exclamation"]
                                ]
                        ]
                        [count(./li)=2]
                ]
            EOD;

        self::assertMatchesXpath(
            $html,
            $expression
        );
    }

    public function testRowAttr(): void
    {
        $form = $this->factory->createNamed('name', TextType::class, '', [
            'row_attr' => [
                'class' => 'foo',
                'data-value' => 'bar',
            ],
        ]);
        $view = $form->createView();
        $html = $this->renderRow($view);

        static::assertStringContainsString(
            '<div class="foo adm-form-row grid grid-cols-12 items-start gap-x-3 gap-y-1"'
            .' data-value="bar" id="adminata-field-container-name">',
            $html
        );
    }

    /**
     * @return Stub&FieldDescriptionInterface
     */
    private function createFieldDescriptionWithTranslationDomain(string $translationDomain): Stub
    {
        $fieldDescription = static::createStub(FieldDescriptionInterface::class);

        $admin = static::createStub(AdminInterface::class);
        $admin
            ->method('getCode')
            ->willReturn('adminata_code');

        $admin
            ->method('getTranslationDomain')
            ->willReturn($translationDomain);

        $fieldDescription
            ->method('getAdmin')
            ->willReturn($admin);

        return $fieldDescription;
    }
}
