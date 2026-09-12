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

namespace IDCT\Adminata\Tests\Translator;

use IDCT\Adminata\Translator\NativeLabelTranslatorStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NativeLabelTranslatorStrategyTest extends TestCase
{
    #[DataProvider('provideLabelCases')]
    public function testLabel(string $expectedLabel, string $label): void
    {
        $strategy = new NativeLabelTranslatorStrategy();

        static::assertSame($expectedLabel, $strategy->getLabel($label, 'form', 'label'));
    }

    /**
     * @phpstan-return iterable<array{string, string}>
     */
    public static function provideLabelCases(): iterable
    {
        yield ['Is Valid', 'isValid'];
        yield ['Is Valid', 'is_Valid'];
        yield ['Is0 Valid', 'is0Valid'];
        yield ['Is Valid', '_isValid'];
        yield ['Is Valid', '__isValid'];
        yield ['Is Valid', 'isValid_'];
        yield ['Is Valid', 'isValid__'];
        yield ['Is Valid', '__isValid__'];
        yield ['Is Valid Super Cool', 'isValid_SuperCool'];
    }
}
