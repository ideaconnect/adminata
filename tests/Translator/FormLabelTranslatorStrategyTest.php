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

use IDCT\Adminata\Translator\FormLabelTranslatorStrategy;
use PHPUnit\Framework\TestCase;

final class FormLabelTranslatorStrategyTest extends TestCase
{
    public function testLabel(): void
    {
        $strategy = new FormLabelTranslatorStrategy();

        static::assertSame('Isvalid', $strategy->getLabel('isValid', 'form', 'label'));
        static::assertSame('Plainpassword', $strategy->getLabel('plainPassword', 'form', 'label'));
    }
}
