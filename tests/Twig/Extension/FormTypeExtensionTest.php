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

namespace IDCT\Adminata\Tests\Twig\Extension;

use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Twig\Extension\FormTypeExtension;

final class FormTypeExtensionTest extends TestCase
{
    public function testGetName(): void
    {
        $extension = new FormTypeExtension(true);
        static::assertSame('adminata_twig_wrapping', $extension->getName());
    }

    public function testGetGlobals(): void
    {
        $extension = new FormTypeExtension(true);

        static::assertArrayHasKey(
            'wrap_fields_with_addons',
            $globals = $extension->getGlobals()
        );
        static::assertTrue($globals['wrap_fields_with_addons']);

        $extension = new FormTypeExtension(false);

        static::assertArrayHasKey(
            'wrap_fields_with_addons',
            $globals = $extension->getGlobals()
        );
        static::assertFalse($globals['wrap_fields_with_addons']);
    }
}
