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

use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Exception\Block\Filter\IgnoreClassFilter;
use IDCT\Adminata\Model\BlockInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
final class IgnoreClassFilterTest extends TestCase
{
    public function testWithInheritedException(): void
    {
        $filter = new IgnoreClassFilter(\RuntimeException::class);

        $result = $filter->handle($this->createMock(NotFoundHttpException::class), $this->createMock(BlockInterface::class));

        static::assertFalse($result, 'Should NOT handle it since NotFoundHttpException inherits RuntimeException');
    }

    public function testWithNonInheritedException(): void
    {
        $filter = new IgnoreClassFilter(\RuntimeException::class);

        $result = $filter->handle($this->createMock(\Exception::class), $this->createMock(BlockInterface::class));

        static::assertTrue($result, 'Should handle it since an \Exception does not inherit RuntimeException');
    }
}
