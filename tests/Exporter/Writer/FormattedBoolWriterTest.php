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

namespace IDCT\Adminata\Tests\Exporter\Writer;

use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Exporter\Writer\FormattedBoolWriter;
use IDCT\Adminata\Exporter\Writer\TypedWriterInterface;

/**
 * Format boolean before use another writer.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
final class FormattedBoolWriterTest extends TestCase
{
    private string $trueLabel;

    private string $falseLabel;

    protected function setUp(): void
    {
        $this->trueLabel = 'yes';
        $this->falseLabel = 'no';
    }

    public function testValidDataFormat(): void
    {
        $data = ['john', 'doe', false, true];
        $expected = ['john', 'doe', 'no', 'yes'];
        $mock = $this->createMock(TypedWriterInterface::class);
        $mock->expects(static::once())
               ->method('write')
               ->with(static::equalTo($expected));
        $writer = new FormattedBoolWriter($mock, $this->trueLabel, $this->falseLabel);
        $writer->open();
        $writer->write($data);
        $writer->close();
    }
}
