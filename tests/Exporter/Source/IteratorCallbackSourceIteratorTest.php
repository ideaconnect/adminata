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

namespace IDCT\Adminata\Tests\Exporter\Source;

use IDCT\Adminata\Exporter\Source\IteratorCallbackSourceIterator;
use IDCT\Adminata\Exporter\Source\IteratorSourceIterator;
use PHPUnit\Framework\TestCase;

final class IteratorCallbackSourceIteratorTest extends TestCase
{
    private IteratorCallbackSourceIterator $sourceIterator;

    protected function setUp(): void
    {
        $this->sourceIterator = new IteratorCallbackSourceIterator(
            new \ArrayIterator([[0], [1], [2], [3]]),
            static function (array $data): array {
                $data[0] = 1 << $data[0];

                return $data;
            }
        );
    }

    public function testTransformer(): void
    {
        $result = [1, 2, 4, 8];

        foreach ($this->sourceIterator as $key => $value) {
            static::assertSame([$result[$key]], $value);
        }
    }

    public function testExtends(): void
    {
        static::assertInstanceOf(IteratorSourceIterator::class, $this->sourceIterator);
    }
}
