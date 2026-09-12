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

namespace IDCT\Adminata\Tests\Util;

use IDCT\Adminata\Exception\AbstractClassException;
use IDCT\Adminata\Tests\Fixtures\Entity\AbstractEntity;
use IDCT\Adminata\Tests\Fixtures\Entity\Bar;
use IDCT\Adminata\Util\Instantiator;
use PHPUnit\Framework\TestCase;

/**
 * @author Morgan Abraham <morgan@geekimo.me>
 */
final class InstantiatorTest extends TestCase
{
    public function testAbstractClassThrowsException(): void
    {
        $this->expectException(AbstractClassException::class);

        Instantiator::instantiate(AbstractEntity::class);
    }

    public function testNotAbstractClassDoesntThrowsException(): void
    {
        static::assertInstanceOf(Bar::class, Instantiator::instantiate(Bar::class));
    }
}
