Origins
=======

adminata began on 2026-09-04 as a hard fork of the Sonata Admin stack. Seven ``sonata-project``
packages — ``admin-bundle`` 4.43.0, ``block-bundle`` 5.4.0, ``doctrine-extensions`` 2.6.0,
``doctrine-orm-admin-bundle`` 4.21.0, ``exporter`` 3.4.0, ``form-extensions`` 2.7.0 and
``twig-extensions`` 2.6.0 — were imported with their full git history, and their PHP design is the
foundation of everything here: the admin classes, the mappers, the datagrid, the routing, the
security handlers and the exporter are their work under new names. Their sources keep the upstream
copyright headers.

Since 2026-09-12 the code lives under the ``IDCT\Adminata\`` namespace and nothing in the API
carries the Sonata name any more. The debt does. We are grateful to Thomas Rabaix, to the Sonata
Project and to its contributors for the years of work since 2010 that made this project possible —
and for the MIT licence that let it happen. The original lives on at
`sonata-project.org <https://sonata-project.org>`_; bugs in it belong there, and fixes to the PHP
we share still flow from there through the process in the repository's ``UPSTREAM.md``.

What was forked from where, at which commit, and what has been synced since: ``UPSTREAM.md``,
``NOTICE``, ``CHANGELOG-sonata.md`` and the inherited changelogs under ``changelog/`` in the
repository.
