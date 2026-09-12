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

namespace IDCT\Adminata\Tests\Exporter;

use IDCT\Adminata\Exporter\Handler;
use IDCT\Adminata\Exporter\Writer\WriterInterface;
use PHPUnit\Framework\TestCase;

final class HandlerTest extends TestCase
{
    public function testHandler(): void
    {
        $writer = $this->createMock(WriterInterface::class);
        $writer->expects(static::once())->method('open');
        $writer->expects(static::once())->method('close');

        $exporter = new Handler($this->createMock(\Iterator::class), $writer);
        $exporter->export();
    }
}
