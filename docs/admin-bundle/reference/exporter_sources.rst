.. index::
    single: Exporter
    single: Sources

Sources
=======

A source is any ``\Iterator`` whose values are arrays of scalars keyed by column name, so an
iterator of your own is a source. ``Sonata\AdminBundle\Exporter\Source\`` ships these:

======================================== ==================================================
Class                                    Reads from
======================================== ==================================================
``ArraySourceIterator``                  a PHP array
``IteratorSourceIterator``               any ``\Iterator``
``IteratorCallbackSourceIterator``       any ``\Iterator``, with a ``\Closure`` per row
``CsvSourceIterator``                    a CSV file; delimiter and enclosure are arguments
``XmlSourceIterator``                    an XML file, one row per ``<data>`` in ``<datas>``
``XmlExcelSourceIterator``               a SpreadsheetML (Excel XML) file
``PDOStatementSourceIterator``           an executed ``\PDOStatement``
``DoctrineDBALConnectionSourceIterator`` a DBAL ``Connection``, a query and its parameters
``DoctrineORMQuerySourceIterator``       a Doctrine ORM ``Query``
``DoctrineODMQuerySourceIterator``       a Doctrine MongoDB ODM ``Query``
``ChainSourceIterator``                  several iterators in turn, as one
``SymfonySitemapSourceIterator``         another iterator, adding a routed ``url``
======================================== ==================================================

Two of them are abstract and there to be extended: ``AbstractPropertySourceIterator``, which turns
objects into rows through the property accessor and is what the two Doctrine query iterators
extend, and ``AbstractXmlSourceIterator``, which drives the XML parser for the two XML ones.

The list admin's source is not one of these directly. It is the storage layer's
``DataSourceInterface`` implementation, which builds a
``Sonata\AdminBundle\Exporter\Source\DoctrineORMQuerySourceIterator`` over the query the list is
showing — see :doc:`/doctrine-orm-admin-bundle/reference/data_source` for replacing it.
