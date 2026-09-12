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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use IDCT\Adminata\Exporter\Exporter;
use IDCT\Adminata\Exporter\ExporterInterface;
use IDCT\Adminata\Exporter\Writer\CsvWriter;
use IDCT\Adminata\Exporter\Writer\JsonWriter;
use IDCT\Adminata\Exporter\Writer\XlsWriter;
use IDCT\Adminata\Exporter\Writer\XlsxWriter;
use IDCT\Adminata\Exporter\Writer\XmlWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('adminata.exporter.writer.csv', CsvWriter::class)
        ->args([
            param('adminata.exporter.writer.csv.filename'),
            param('adminata.exporter.writer.csv.delimiter'),
            param('adminata.exporter.writer.csv.enclosure'),
            param('adminata.exporter.writer.csv.escape'),
            param('adminata.exporter.writer.csv.show_headers'),
            param('adminata.exporter.writer.csv.with_bom'),
        ]);

    $services->set('adminata.exporter.writer.json', JsonWriter::class)
        ->args([
            param('adminata.exporter.writer.json.filename'),
        ]);

    $services->set('adminata.exporter.writer.xls', XlsWriter::class)
        ->args([
            param('adminata.exporter.writer.xls.filename'),
            param('adminata.exporter.writer.xls.show_headers'),
        ]);

    if (class_exists(Spreadsheet::class)) {
        $services->set('adminata.exporter.writer.xlsx', XlsxWriter::class)
            ->args([
                param('adminata.exporter.writer.xlsx.filename'),
                param('adminata.exporter.writer.xlsx.show_headers'),
                param('adminata.exporter.writer.xlsx.show_filters'),
            ]);
    }

    $services->set('adminata.exporter.writer.xml', XmlWriter::class)
        ->args([
            param('adminata.exporter.writer.xml.filename'),
            param('adminata.exporter.writer.xml.main_element'),
            param('adminata.exporter.writer.xml.child_element'),
        ]);

    $services->set('adminata.exporter.exporter', Exporter::class)
        ->public();

    $services->alias(Exporter::class, 'adminata.exporter.exporter');
    $services->alias(ExporterInterface::class, 'adminata.exporter.exporter');
};
