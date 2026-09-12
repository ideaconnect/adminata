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
use IDCT\Adminata\Form\Extension\Field\Type\FormTypeFieldExtension;
use IDCT\Adminata\Form\Type\NativeCollectionType;
use IDCT\Adminata\Tests\Fixtures\TestExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

final class FormAdminataNativeCollectionWidgetTest extends BaseWidgetTestCase
{
    protected $type = 'form';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @phpstan-return iterable<array{array<string, mixed>}>
     */
    public static function providePrototypeIsDeletableNoMatterTheShrinkabilityCases(): iterable
    {
        yield 'shrinkable collection' => [['allow_delete' => true]];
        yield 'unshrinkable collection' => [['allow_delete' => false]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('providePrototypeIsDeletableNoMatterTheShrinkabilityCases')]
    public function testPrototypeIsDeletableNoMatterTheShrinkability(array $options): void
    {
        $choice = $this->factory->create(
            $this->getChoiceClass(),
            null,
            ['allow_add' => true] + $options
        );

        $html = $this->renderWidget($choice->createView());

        static::assertStringContainsString(
            'adminata-collection-delete',
            $this->cleanHtmlWhitespace($html)
        );
    }

    /**
     * @phpstan-return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $extensions = parent::getExtensions();
        $extension = new TestExtension($this->createMock(FormTypeGuesserInterface::class));

        $extension->addTypeExtension(new FormTypeFieldExtension([], [
            'form_type' => 'vertical',
        ]));
        $extensions[] = $extension;

        return $extensions;
    }

    /**
     * @return class-string<FormTypeInterface>
     */
    protected function getChoiceClass(): string
    {
        return NativeCollectionType::class;
    }
}
