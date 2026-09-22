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

namespace IDCT\Adminata\Tests\IconEnum;

use IDCT\Adminata\IconEnum\IconEnumCase;
use IDCT\Adminata\IconEnum\IconEnumResolver;
use IDCT\Adminata\IconEnum\IconEnumTone;
use IDCT\Adminata\Tests\Fixtures\Enum\Suit;
use IDCT\Adminata\Tests\Fixtures\Enum\Traffic;
use IDCT\Adminata\Tests\Fixtures\Enum\TranslatableSuit;
use IDCT\Adminata\Tests\Fixtures\FieldDescription\FieldDescription;
use IDCT\Adminata\Tests\Fixtures\StubTranslator;
use PHPUnit\Framework\TestCase;

final class IconEnumResolverTest extends TestCase
{
    private IconEnumResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new IconEnumResolver(new StubTranslator());
    }

    public function testAnEnumImplementingTheInterfaceNeedsNoConfiguration(): void
    {
        $case = $this->resolver->resolve(Traffic::Wait, new FieldDescription('status'));

        static::assertNotNull($case);
        static::assertSame('Wait', $case->key);
        static::assertSame('far fa-clock', $case->icon);
        static::assertSame(IconEnumTone::Warning, $case->tone);
        static::assertSame('Wait', $case->label, 'The label is the case name, as TYPE_ENUM shows it.');
    }

    public function testTheCasesOptionConfiguresAnEnumThatDoesNotImplementTheInterface(): void
    {
        $field = new FieldDescription('suit', ['cases' => [
            'Hearts' => ['icon' => 'fas fa-heart', 'tone' => 'error', 'label' => 'Red hearts'],
            'Spades' => ['icon' => 'fas fa-spade'],
        ]]);

        $hearts = $this->resolver->resolve(Suit::Hearts, $field);
        static::assertNotNull($hearts);
        static::assertSame('fas fa-heart', $hearts->icon);
        static::assertSame(IconEnumTone::Error, $hearts->tone);
        static::assertSame('Red hearts', $hearts->label);

        $spades = $this->resolver->resolve(Suit::Spades, $field);
        static::assertNotNull($spades);
        static::assertSame('fas fa-spade', $spades->icon);
        static::assertSame(IconEnumTone::Neutral, $spades->tone, 'No tone configured: the neutral grey.');
        static::assertSame('Spades', $spades->label);
    }

    public function testTheCasesOptionOverridesSingleCasesOfAnEnumThatImplementsTheInterface(): void
    {
        $field = new FieldDescription('status', ['cases' => [
            'Stop' => ['tone' => IconEnumTone::Neutral, 'label' => 'Halted'],
        ]]);

        $stop = $this->resolver->resolve(Traffic::Stop, $field);
        static::assertNotNull($stop);
        static::assertSame('fas fa-ban', $stop->icon, 'The glyph is the enum\'s own: the override names only a tone and a label.');
        static::assertSame(IconEnumTone::Neutral, $stop->tone);
        static::assertSame('Halted', $stop->label);

        $go = $this->resolver->resolve(Traffic::Go, $field);
        static::assertNotNull($go);
        static::assertSame(IconEnumTone::Success, $go->tone, 'A case the option does not name keeps the enum\'s answer.');
    }

    public function testTheLabelFollowsTheEnumTypeRules(): void
    {
        $translatable = $this->resolver->resolve(TranslatableSuit::Clubs, new FieldDescription('suit', ['cases' => ['Clubs' => ['icon' => 'fas fa-club']]]));
        static::assertNotNull($translatable);
        static::assertSame('[trans domain=render-element-extension-test]enum.suit.clubs[/trans]', $translatable->label, 'A TranslatableInterface enum names itself.');

        $byValue = $this->resolver->resolve(Suit::Clubs, new FieldDescription('suit', ['use_value' => true, 'cases' => ['Clubs' => ['icon' => 'fas fa-club']]]));
        static::assertNotNull($byValue);
        static::assertSame('C', $byValue->label);

        $domain = $this->resolver->resolve(Suit::Clubs, new FieldDescription('suit', ['enum_translation_domain' => 'cards', 'cases' => ['Clubs' => ['icon' => 'fas fa-club']]]));
        static::assertNotNull($domain);
        static::assertSame('[trans domain=cards]Clubs[/trans]', $domain->label);

        $configured = $this->resolver->resolve(Suit::Clubs, new FieldDescription('suit', ['enum_translation_domain' => 'cards', 'cases' => ['Clubs' => ['icon' => 'fas fa-club', 'label' => 'cards.clubs']]]));
        static::assertNotNull($configured);
        static::assertSame('[trans domain=cards]cards.clubs[/trans]', $configured->label, 'A configured label goes through the domain too.');
    }

    public function testAScalarColumnIsKeyedByItsValue(): void
    {
        $field = new FieldDescription('state', ['cases' => [
            'open' => ['icon' => 'fas fa-lock-open', 'tone' => 'success'],
            'closed' => ['icon' => 'fas fa-lock', 'label' => 'Closed'],
            7 => ['icon' => 'fas fa-7'],
        ]]);

        $open = $this->resolver->resolve('open', $field);
        static::assertNotNull($open);
        static::assertSame('open', $open->key);
        static::assertSame('open', $open->label);

        $seven = $this->resolver->resolve(7, $field);
        static::assertNotNull($seven);
        static::assertSame('7', $seven->key);

        static::assertSame(['open', 'closed', '7'], array_map(static fn (IconEnumCase $case): string => $case->key, $this->resolver->legend($field)), 'Without an enum, the legend is the cases option in its order.');
    }

    public function testANullValueDrawsNothingUnlessEmptyIsConfigured(): void
    {
        static::assertNull($this->resolver->resolve(null, new FieldDescription('status')));

        $empty = $this->resolver->resolve(null, new FieldDescription('status', ['empty' => ['icon' => 'fas fa-minus', 'label' => 'n/a']]));
        static::assertNotNull($empty);
        static::assertSame('', $empty->key);
        static::assertSame('fas fa-minus', $empty->icon);
        static::assertSame(IconEnumTone::Neutral, $empty->tone);
        static::assertSame('n/a', $empty->label);
    }

    public function testTheLegendIsTheEnumInDeclarationOrderThenTheEmptyCase(): void
    {
        $field = new FieldDescription('status', ['empty' => ['icon' => 'fas fa-minus', 'tone' => 'info', 'label' => 'Unknown']], ['enumType' => Traffic::class]);

        $legend = $this->resolver->legend($field);

        static::assertSame(['Go', 'Wait', 'Stop', ''], array_map(static fn (IconEnumCase $case): string => $case->key, $legend));
        static::assertSame(['fas fa-check', 'far fa-clock', 'fas fa-ban', 'fas fa-minus'], array_map(static fn (IconEnumCase $case): string => $case->icon, $legend));
        static::assertSame(IconEnumTone::Info, $legend[3]->tone);
    }

    public function testTheClassOptionNamesTheEnumWhenTheMappingDoesNot(): void
    {
        $field = new FieldDescription('virtualStatus', ['class' => Traffic::class]);

        static::assertCount(3, $this->resolver->legend($field));
        static::assertSame([], $this->resolver->legend(new FieldDescription('untyped')), 'No enum, no cases: nothing to list.');
    }

    public function testACaseWithoutAGlyphIsAConfigurationError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Field "suit": no icon for case "Hearts". Implement IDCT\Adminata\IconEnum\IconEnumInterface on IDCT\Adminata\Tests\Fixtures\Enum\Suit or name the case in the "cases" option.');

        $this->resolver->resolve(Suit::Hearts, new FieldDescription('suit'));
    }

    public function testAnUnknownToneIsAConfigurationError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Field "status": unknown tone for case "Go"; one of "neutral", "success", "error", "warning", "info", "brand" expected.');

        $this->resolver->resolve(Traffic::Go, new FieldDescription('status', ['cases' => ['Go' => ['tone' => 'green']]]));
    }

    public function testAnEmptyOptionWithoutIconOrLabelIsAConfigurationError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Field "status": the "empty" option needs an "icon" and a "label", both strings.');

        $this->resolver->resolve(null, new FieldDescription('status', ['empty' => ['icon' => 'fas fa-minus']]));
    }

    public function testAClassThatIsNotAnEnumIsAConfigurationError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Field "status": "stdClass" is not an enum.');

        $this->resolver->legend(new FieldDescription('status', ['class' => \stdClass::class]));
    }

    public function testAValueThatIsNeitherEnumNorScalarIsRefused(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Field "status": an icon enum draws an enum, a string or an integer, not stdClass.');

        $this->resolver->resolve(new \stdClass(), new FieldDescription('status'));
    }
}
