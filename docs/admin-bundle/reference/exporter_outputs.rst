.. index::
    single: Exporter
    single: Outputs

Outputs
=======

A writer implements ``IDCT\Adminata\Exporter\Writer\WriterInterface`` — ``open()``,
``write()`` once per row, ``close()`` — and ``IDCT\Adminata\Exporter\Writer\`` ships these:

======================= ==================================================
Class                   Writes
======================= ==================================================
``CsvWriter``           CSV
``JsonWriter``          JSON
``XmlWriter``           XML
``XlsWriter``           an HTML table under an Excel MIME type
``XlsxWriter``          XLSX, through ``phpoffice/phpspreadsheet``
``XmlExcelWriter``      SpreadsheetML (Excel XML)
``SitemapWriter``       a sitemap, split into parts with an optional index
``GsaFeedWriter``       a Google Search Appliance feed
``InMemoryWriter``      an array in memory, for tests
``FormattedBoolWriter`` another writer, booleans replaced by labels first
======================= ==================================================

The five whose format an admin can ask for by name — ``csv``, ``json``, ``xls``, ``xlsx`` and
``xml`` — implement ``TypedWriterInterface``, which adds ``getFormat()`` and
``getDefaultMimeType()``. That is what lets ``adminata.exporter.exporter`` pick a writer from the
format string in an export URL and set the response's ``Content-Type``; the others are used
directly, through a ``Handler``. ``XlsxWriter`` is registered only when
``phpoffice/phpspreadsheet`` is installed (:doc:`exporter_configuration`).

``FormattedBoolWriter`` is a decorator: it takes another writer plus the two labels to write
``true`` and ``false`` as, and passes every other value through. ``CsvWriterTerminate`` is not a
writer at all but the stream filter ``CsvWriter`` registers to replace the line terminator.

Writing your own
----------------

Implement ``WriterInterface``, or — if you know the ``Content-Type`` your output needs and the
format name it should answer to — ``TypedWriterInterface``, which is what makes the writer
available to an admin's export menu once it is tagged. Tagging and configuration are
:doc:`exporter_configuration`.
