.. index::
    single: Exporter
    single: Export

The exporter
============

The exporter is what the list page's export menu streams a result set through, and it is part of
the admin bundle (:doc:`action_export`). It converts a large amount of data from a source to an
output format — most generally a file — one row at a time, so the memory it costs does not grow
with the number of rows.

Its classes are ``IDCT\Adminata\Exporter\``: ``Handler`` and ``Exporter``, the source
iterators of :doc:`exporter_sources` and the writers of :doc:`exporter_outputs`. There is nothing
to install beyond the admin bundle itself (:doc:`/admin-bundle/getting_started/installation`), and
nothing to register in ``bundles.php``; ``AdminataBundle`` brings the ``adminata.exporter.*``
services and the ``adminata_exporter`` configuration root with it
(:doc:`exporter_configuration`).

.. note::

    Coming from Sonata? The classes of ``sonata-project/exporter`` are
    ``IDCT\Adminata\Exporter\`` here — the map and the tool that applies it are in `UPGRADE.md
    <https://github.com/ideaconnect/adminata/blob/main/UPGRADE.md>`_ — and there is no
    ``SonataExporterBundle`` to register. The rest is on :doc:`exporter_configuration`.

Three parts
-----------

A **source** is any ``\Iterator`` whose values are arrays of scalars, keyed by column name;
``Source\`` ships the ones worth having (:doc:`exporter_sources`). A **writer** implements
``IDCT\Adminata\Exporter\Writer\WriterInterface`` — ``open()``, ``write()`` per row,
``close()`` — and ``Writer\`` ships one per format (:doc:`exporter_outputs`). ``Handler`` runs the
loop between them::

    use IDCT\Adminata\Exporter\Handler;
    use IDCT\Adminata\Exporter\Source\ArraySourceIterator;
    use IDCT\Adminata\Exporter\Writer\JsonWriter;

    // any \Iterator will do
    $source = new ArraySourceIterator([/* your data */]);

    // any writer will do
    $writer = new JsonWriter('php://output');

    Handler::create($source, $writer)->export();

Neither the source nor the writer holds the whole set: ``Handler::export()`` opens the writer,
walks the iterator writing one row at a time, and closes it.

Inside an admin, none of that is written by hand. The export action asks the admin's
``DataSourceInterface`` for an iterator over the current query and the fields
``configureExportFields()`` chose, and hands it to the ``adminata.exporter.exporter`` service, which
returns a streamed response — see :doc:`action_export` for the admin side and
:doc:`exporter_configuration` for the service.
