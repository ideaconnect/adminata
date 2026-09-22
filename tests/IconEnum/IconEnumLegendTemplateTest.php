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

use IDCT\Adminata\IconEnum\IconEnumResolver;
use IDCT\Adminata\Tests\Fixtures\Enum\Suit;
use IDCT\Adminata\Tests\Fixtures\Enum\Traffic;
use IDCT\Adminata\Tests\Fixtures\FieldDescription\FieldDescription;
use IDCT\Adminata\Tests\Fixtures\StubTranslator;
use IDCT\Adminata\Twig\Extension\IconEnumExtension;
use IDCT\Adminata\Twig\IconEnumRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * The legend under a list (`CRUD/list__legend.html.twig`): a description list, one group per
 * icon-enum column, the column's label as the term and every case the column can draw as a
 * labelled square. Rendered on its own, with the field descriptions base_list would hand it.
 */
final class IconEnumLegendTemplateTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../src/Resources/views/', 'Adminata');

        $translator = new StubTranslator();

        $this->twig = new Environment($loader, ['strict_variables' => true, 'cache' => false]);
        $this->twig->addExtension(new IconEnumExtension());
        $this->twig->addExtension(new TranslationExtension($translator));
        $this->twig->addRuntimeLoader(new FactoryRuntimeLoader([
            IconEnumRuntime::class => static fn (): IconEnumRuntime => new IconEnumRuntime(new IconEnumResolver($translator)),
        ]));
    }

    public function testOneGroupPerColumnWithEveryCaseLabelled(): void
    {
        $status = new FieldDescription('status', ['label' => 'Status', 'translation_domain' => false], ['enumType' => Traffic::class]);
        $suit = new FieldDescription('suit', [
            'label' => 'Suit',
            'translation_domain' => false,
            'class' => Suit::class,
            'cases' => [
                'Hearts' => ['icon' => 'fas fa-heart', 'tone' => 'error'],
                'Diamonds' => ['icon' => 'far fa-gem', 'tone' => 'info'],
                'Clubs' => ['icon' => 'fas fa-clover'],
                'Spades' => ['icon' => 'fas fa-spade'],
            ],
            'empty' => ['icon' => 'fas fa-minus', 'label' => 'No card'],
        ]);

        $html = $this->render([$status, $suit]);

        static::assertSame(1, substr_count($html, '<dl class="adm-list-legend">'));
        static::assertSame(2, substr_count($html, '<div class="adm-list-legend__group">'));
        static::assertSame(2, substr_count($html, '<dt class="adm-list-legend__term">'));
        static::assertSame(3 + 5, substr_count($html, '<dd class="adm-list-legend__item">'), 'Three traffic cases; four suits and the empty card.');

        static::assertStringContainsString('<dt class="adm-list-legend__term">Status</dt>', $html);
        static::assertStringContainsString('<dt class="adm-list-legend__term">Suit</dt>', $html);

        // A legend entry is the labelled form: the square decorative, the name written beside it.
        static::assertStringContainsString(
            '<span class="adm-icon-enum"><span class="adm-badge adm-badge-warning adm-badge-icon" aria-hidden="true"><i class="far fa-clock"></i></span><span class="adm-icon-enum__label">Wait</span></span>',
            $html,
        );
        static::assertStringContainsString('<span class="adm-icon-enum__label">No card</span>', $html);
        static::assertStringNotContainsString('sr-only', $html, 'Nothing in a legend is hidden: it exists to be read.');
        static::assertStringNotContainsString('title=', $html);

        // Declaration order, the empty case last.
        static::assertMatchesRegularExpression('/Go.*Wait.*Stop.*Hearts.*Diamonds.*Clubs.*Spades.*No card/s', $html);
    }

    public function testAColumnLabelGoesThroughItsTranslationDomain(): void
    {
        $status = new FieldDescription('status', ['label' => 'list.status', 'translation_domain' => 'messages'], ['enumType' => Traffic::class]);

        $html = $this->render([$status]);

        static::assertStringContainsString('<dt class="adm-list-legend__term">[trans domain=messages]list.status[/trans]</dt>', $html);
    }

    /**
     * @param list<FieldDescription> $fields
     */
    private function render(array $fields): string
    {
        return $this->twig->render('@Adminata/CRUD/list__legend.html.twig', ['legend_fields' => $fields]);
    }
}
