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

namespace IDCT\Adminata\Tests\Exception\Block\Filter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Exception\Block\Filter\KeepAllFilter;
use IDCT\Adminata\Model\BlockInterface;

/**
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
final class KeepAllFilterTest extends TestCase
{
    #[DataProvider('provideFilterCases')]
    public function testFilter(\Exception $exception): void
    {
        $filter = new KeepAllFilter();

        $result = $filter->handle($exception, $this->createMock(BlockInterface::class));

        static::assertTrue($result, 'Should handle any exception');
    }

    /**
     * @return iterable<array-key, array{\Exception}>
     */
    public static function provideFilterCases(): iterable
    {
        yield [new \Exception()];
        yield [new \RuntimeException()];
    }
}
