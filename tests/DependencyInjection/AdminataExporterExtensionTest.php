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

namespace IDCT\Adminata\Tests\DependencyInjection;

use IDCT\Adminata\DependencyInjection\AdminataExporterExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

final class AdminataExporterExtensionTest extends AbstractExtensionTestCase
{
    public function testExporterServiceIsPresent(): void
    {
        $this->load();
        $this->assertContainerBuilderHasService('adminata.exporter.exporter');
    }

    public function testServiceParametersArePresent(): void
    {
        $this->load();
        foreach ([
            'adminata.exporter.writer.csv.filename',
            'adminata.exporter.writer.csv.delimiter',
            'adminata.exporter.writer.csv.enclosure',
            'adminata.exporter.writer.csv.escape',
            'adminata.exporter.writer.csv.show_headers',
            'adminata.exporter.writer.csv.with_bom',
            'adminata.exporter.writer.json.filename',
            'adminata.exporter.writer.xls.filename',
            'adminata.exporter.writer.xls.show_headers',
            'adminata.exporter.writer.xlsx.filename',
            'adminata.exporter.writer.xlsx.show_headers',
            'adminata.exporter.writer.xlsx.show_filters',
            'adminata.exporter.writer.xml.filename',
            'adminata.exporter.writer.xml.main_element',
            'adminata.exporter.writer.xml.child_element',
        ] as $parameter) {
            $this->assertContainerBuilderHasParameter($parameter);
        }
    }

    protected function getContainerExtensions(): array
    {
        return [
            new AdminataExporterExtension(),
        ];
    }
}
