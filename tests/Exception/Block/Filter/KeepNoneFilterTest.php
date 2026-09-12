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
use IDCT\Adminata\Exception\Block\Filter\KeepNoneFilter;
use IDCT\Adminata\Model\BlockInterface;

/**
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
final class KeepNoneFilterTest extends TestCase
{
    #[DataProvider('provideFilterCases')]
    public function testFilter(\Exception $exception): void
    {
        $filter = new KeepNoneFilter();

        $result = $filter->handle($exception, $this->createMock(BlockInterface::class));

        static::assertFalse($result, 'Should handle no exceptions');
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
