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

namespace Sonata\AdminBundle\Tests\Exception\Block\Filter;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Exception\Block\Filter\DebugOnlyFilter;
use Sonata\AdminBundle\Model\BlockInterface;

/**
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
final class DebugOnlyFilterTest extends TestCase
{
    public function testWithDebugEnabled(): void
    {
        $filter = new DebugOnlyFilter(true);

        $result = $filter->handle($this->createMock(\Exception::class), $this->createMock(BlockInterface::class));

        static::assertTrue($result, 'Should handle it since we have enabled debug');
    }

    public function testWithDebugDisabled(): void
    {
        $filter = new DebugOnlyFilter(false);

        $result = $filter->handle($this->createMock(\Exception::class), $this->createMock(BlockInterface::class));

        static::assertFalse($result, 'Should NOT handle it since we have disabled debug');
    }
}
