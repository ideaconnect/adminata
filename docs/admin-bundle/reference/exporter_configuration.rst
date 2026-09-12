.. index::
    single: Exporter
    single: Configuration

Exporter configuration
======================

The exporter is part of the admin bundle. Every list page's export menu goes through it
(:doc:`action_export`), so there is nothing to install beyond the admin bundle itself
(:doc:`/admin-bundle/getting_started/installation`) and nothing to register in ``bundles.php``.
Its classes are ``IDCT\Adminata\Exporter\`` — ``Exporter``, ``Handler``, ``Source\`` and
``Writer\`` (:doc:`exporter_introduction`) — and ``AdminataBundle`` registers the exporter
services, the writer-collecting compiler pass and the ``adminata_exporter`` configuration root.

What the exporter has of its own:

* ``adminata_exporter`` is a configuration root of its own, in its own
  ``config/packages/adminata_exporter.yaml``; the tree is below.
* Service ids are ``adminata.exporter.*`` — ``adminata.exporter.exporter``, which is public and also
  aliased to ``IDCT\Adminata\Exporter\Exporter`` and
  ``IDCT\Adminata\Exporter\ExporterInterface`` for autowiring, and one
  ``adminata.exporter.writer.<format>`` per shipped writer (``csv``, ``json``, ``xls``, ``xml``, and
  ``xlsx`` when ``phpoffice/phpspreadsheet`` is installed).
* A writer is offered to the exporter by the tag ``adminata.exporter.writer``; a compiler pass hands
  every tagged service to ``adminata.exporter.exporter``.
* No templates and no translation domain: the exporter writes files, and the export menu on a list
  page is the admin bundle's template, in the admin bundle's domain.

.. note::

    Coming from Sonata? The ``IDCT\Adminata\Exporter\`` classes are ``IDCT\Adminata\Exporter\``
    here — the map is in `UPGRADE-1.0.md
    <https://github.com/ideaconnect/adminata/blob/main/UPGRADE-1.0.md>`_ §U1 — and there is no
    ``SonataExporterBundle`` to register in ``bundles.php``: an application's ``bundles.php``
    loses that line and nothing else. Service ids, the tag and
    ``config/packages/adminata_exporter.yaml`` need no edit. adminata **conflicts** with
    ``sonata-project/exporter``: the two cannot be installed together. See :doc:`/upgrading`.

XLSX needs one more package
---------------------------

``XlsxWriter`` builds its spreadsheet with PhpSpreadsheet, which is not a dependency of adminata.
The ``adminata.exporter.writer.xlsx`` service is registered only when the class is there, and the
``xlsx`` format is offered only when the service is:

.. code-block:: bash

    composer require phpoffice/phpspreadsheet

Every other format works out of the box.

Exporting from a controller
---------------------------

``adminata.exporter.exporter`` builds a streamed response from a format, a filename and a source,
ready to return from a controller::

    use IDCT\Adminata\Exporter\ExporterInterface;
    use IDCT\Adminata\Exporter\Source\ArraySourceIterator;
    use Symfony\Component\HttpFoundation\StreamedResponse;

    final class ReportController
    {
        public function __construct(private ExporterInterface $exporter)
        {
        }

        public function __invoke(): StreamedResponse
        {
            $source = new ArraySourceIterator([/* your data */]);

            return $this->exporter->getResponse('csv', 'report.csv', $source);
        }
    }

The response streams the rows as they are written, and its ``Content-Type`` is the writer's own.
An unknown format is a ``RuntimeException`` naming the formats there are;
``getAvailableFormats()`` is that list.

The default writers
-------------------

Under the hood, the exporter uses one service for each available format. Each service takes its
settings from container parameters, and each parameter has a configuration counterpart:

.. code-block:: yaml

    # config/packages/adminata_exporter.yaml

    adminata_exporter:
        writers:
            some_format:
                some_setting: some_value

The CSV writer service
~~~~~~~~~~~~~~~~~~~~~~

This service can be configured through the following parameters:

* ``adminata.exporter.writer.csv.filename``: defaults to ``php://output``
* ``adminata.exporter.writer.csv.delimiter``: defaults to ``,``
* ``adminata.exporter.writer.csv.enclosure``: defaults to ``"``
* ``adminata.exporter.writer.csv.escape``: defaults to ``\``
* ``adminata.exporter.writer.csv.show_headers``: defaults to ``true``
* ``adminata.exporter.writer.csv.with_bom``: defaults to ``false``

The JSON writer service
~~~~~~~~~~~~~~~~~~~~~~~

Only the filename may be configured for this service:
``adminata.exporter.writer.json.filename``: defaults to ``php://output``

The XLS writer service
~~~~~~~~~~~~~~~~~~~~~~

This service can be configured through the following parameters:

* ``adminata.exporter.writer.xls.filename``: defaults to ``php://output``
* ``adminata.exporter.writer.xls.show_headers``: defaults to ``true``

The XLSX writer service
~~~~~~~~~~~~~~~~~~~~~~~

This service can be configured through the following parameters:

* ``adminata.exporter.writer.xlsx.filename``: defaults to ``php://output``
* ``adminata.exporter.writer.xlsx.show_headers``: defaults to ``true``
* ``adminata.exporter.writer.xlsx.show_filters``: defaults to ``true``

The XML writer service
~~~~~~~~~~~~~~~~~~~~~~

This service can be configured through the following parameters:

* ``adminata.exporter.writer.xml.filename``: defaults to ``php://output``
* ``adminata.exporter.writer.xml.show_headers``: defaults to ``true``
* ``adminata.exporter.writer.xml.main_element``: defaults to ``datas``
* ``adminata.exporter.writer.xml.child_element``: defaults to ``data``

Adding a custom writer to the list
----------------------------------

If you want to add a custom writer to the list of writers supported by the exporter, you simply
need to tag your service, which must implement
``IDCT\Adminata\Exporter\Writer\TypedWriterInterface``, with the ``adminata.exporter.writer``
tag. Its ``getFormat()`` is the name an export URL asks for, and its ``getDefaultMimeType()`` is
the response's ``Content-Type`` (:doc:`exporter_outputs`).

Configuring the default writers
-------------------------------

The default writers list can be altered through configuration:

.. code-block:: yaml

    # config/packages/adminata_exporter.yaml

    adminata_exporter:
        exporter:
            default_writers:
                - csv
                - json

Only the listed services are tagged, so only those formats appear in an admin's export menu. An
admin can narrow it further for itself with ``getExportFormats()`` (:doc:`action_export`).

The tree
--------

``bin/console config:dump-reference adminata_exporter`` prints the whole tree with its defaults:

.. code-block:: yaml

    adminata_exporter:
        exporter:
            # the formats offered; xlsx is in this default only when PhpSpreadsheet is installed
            default_writers:
                - csv
                - json
                - xls
                - xml
        writers:
            csv:
                filename:     php://output
                delimiter:    ','
                enclosure:    '"'
                escape:       \
                show_headers: true
                with_bom:     false
            json:
                filename:     php://output
            xls:
                filename:     php://output
                show_headers: true
            xlsx:
                filename:     php://output
                show_headers: true
                show_filters: true
            xml:
                filename:      php://output
                show_headers:  true
                main_element:  datas
                child_element: data
