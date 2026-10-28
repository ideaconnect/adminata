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

namespace IDCT\Adminata\Tests\Form\Extension;

use IDCT\Adminata\Form\Extension\HelpStyleTypeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * `help_style` is set once, on the form at the top (an admin's `configureFormOptions()`), and every
 * field, sub-form and collection entry under it follows unless it names its own.
 */
final class HelpStyleTypeExtensionTest extends TestCase
{
    public function testAFormThatNamesNoStyleTakesTheDefault(): void
    {
        $view = $this->form(new HelpStyleTypeExtension());

        static::assertSame('plain', $view->vars['help_style']);
        static::assertSame('plain', $this->child($view, 'name')->vars['help_style']);
        static::assertSame('plain', $this->child($this->child($view, 'address'), 'city')->vars['help_style']);

        $view = $this->form(new HelpStyleTypeExtension('box'));

        static::assertSame('box', $view->vars['help_style']);
        static::assertSame('box', $this->child($this->child($view, 'address'), 'city')->vars['help_style']);
    }

    /**
     * Case: the top form's style reaches its fields, a sub-form's fields, the entries of a
     * collection and the collection's prototype, which is created inside the collection's own
     * `buildView()`.
     */
    public function testTheTopFormsStyleIsInheritedAllTheWayDown(): void
    {
        $view = $this->form(new HelpStyleTypeExtension(), ['help_style' => 'box']);

        static::assertSame('box', $view->vars['help_style']);
        static::assertSame('box', $this->child($view, 'name')->vars['help_style']);
        static::assertSame('box', $this->child($this->child($view, 'address'), 'city')->vars['help_style']);

        $tags = $this->child($view, 'tags');
        static::assertSame('box', $tags->vars['help_style']);
        static::assertSame('box', $this->child($tags, '0')->vars['help_style']);

        $prototype = $tags->vars['prototype'];
        static::assertInstanceOf(FormView::class, $prototype);
        static::assertSame('box', $prototype->vars['help_style']);
    }

    /**
     * Case: a field or a sub-form naming its own style keeps it, and passes it on to what is
     * under it, whichever way round the two styles are.
     */
    public function testAFieldOrSubFormCanPickTheOtherStyle(): void
    {
        $view = $this->form(new HelpStyleTypeExtension('box'), [], ['help_style' => 'plain'], ['help_style' => 'plain']);

        static::assertSame('box', $view->vars['help_style']);
        static::assertSame('plain', $this->child($view, 'name')->vars['help_style']);
        static::assertSame('plain', $this->child($view, 'address')->vars['help_style']);
        static::assertSame('plain', $this->child($this->child($view, 'address'), 'city')->vars['help_style']);
        static::assertSame('box', $this->child($view, 'tags')->vars['help_style']);

        $view = $this->form(new HelpStyleTypeExtension(), [], ['help_style' => 'box']);

        static::assertSame('plain', $view->vars['help_style']);
        static::assertSame('box', $this->child($view, 'name')->vars['help_style']);
        static::assertSame('plain', $this->child($this->child($view, 'address'), 'city')->vars['help_style']);
    }

    public function testAStyleItDoesNotKnowIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->factory(new HelpStyleTypeExtension())->create(TextType::class, null, ['help_style' => 'side']);
    }

    public function testADefaultItDoesNotKnowIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The default help style must be one of "plain", "box", got "side".');

        new HelpStyleTypeExtension('side');
    }

    private function factory(HelpStyleTypeExtension $extension): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addTypeExtension($extension)
            ->getFormFactory();
    }

    /**
     * A form with a field, a sub-form holding one, and a collection with one entry.
     *
     * @param array<string, mixed> $formOptions
     * @param array<string, mixed> $nameOptions
     * @param array<string, mixed> $addressOptions
     */
    private function form(HelpStyleTypeExtension $extension, array $formOptions = [], array $nameOptions = [], array $addressOptions = []): FormView
    {
        $factory = $this->factory($extension);

        $builder = $factory->createBuilder(FormType::class, ['tags' => ['new']], $formOptions)
            ->add('name', TextType::class, $nameOptions + ['help' => 'The name.'])
            ->add(
                $factory->createNamedBuilder('address', FormType::class, null, $addressOptions)
                    ->add('city', TextType::class, ['help' => 'The city.'])
            )
            ->add('tags', CollectionType::class, [
                'entry_type' => TextType::class,
                'entry_options' => ['help' => 'A tag.'],
                'allow_add' => true,
            ]);

        return $builder->getForm()->createView();
    }

    private function child(FormView $view, string $name): FormView
    {
        static::assertArrayHasKey($name, $view->children);

        return $view->children[$name];
    }
}
