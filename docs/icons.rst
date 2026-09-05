Icons
=====

adminata ships **Font Awesome 7 Free** (7.3.1), self-hosted, as its own stylesheet. There is no
v4 or v5 compatibility shim: the aliases Font Awesome 7 carries itself are the only ones you get.

The stylesheet is separate from adminata's own so that an application already serving Font Awesome
can drop it:

.. code-block:: yaml

    sonata_admin:
        assets:
            remove_stylesheets:
                - bundles/sonataadmin/fontawesome.css

``parse_icon``
--------------

Everywhere an icon is *configured* rather than written — admin ``icon`` options, menu items,
dashboard groups — the value goes through the ``parse_icon`` filter, and its contract is small
enough to state in full:

* an empty string returns unchanged;
* a value starting with ``<`` returns **unchanged**, so you can pass raw markup — an inline SVG, an
  ``<img>``, a span from another icon set — and adminata will not touch it;
* a value starting with ``fa ``, ``fas ``, ``far ``, ``fab ``, ``fal `` or ``fad `` becomes
  ``<i class="…" aria-hidden="true"></i>``;
* **anything else throws** ``InvalidArgumentException``.

That last rule is the one that catches typos at render time instead of shipping a blank square:

.. code-block:: php

    // fine
    $admin->setIcon('fas fa-box');
    $admin->setIcon('<svg viewBox="0 0 24 24">…</svg>');

    // throws: "The icon format "box" is not supported."
    $admin->setIcon('box');

The generated ``<i>`` is always ``aria-hidden``. An icon is decoration; the accessible name comes
from the text beside it, or from an ``aria-label`` on the control, or from a visually hidden
``<span class="sr-only">``. adminata's own icon-only buttons all carry one.

Upgrading icon names
--------------------

Most Font Awesome 5 names resolve unchanged in 7. The ones that do not are v4-era names, typically
ending in ``-o``:

===================  ===================
Old                  Font Awesome 7
===================  ===================
``fa-clock-o``       ``fa-clock``
``fa-trash-o``       ``fa-trash-can``
``fa-file-o``        ``fa-file``
``fa-star-o``        ``fa-star`` (regular style, ``far``)
===================  ===================

Grep your templates for icon names ending in ``-o``. In the application adminata was built for,
75 of 76 names resolved without a change.

Using another icon set
----------------------

Pass raw markup through ``parse_icon`` — it hands anything starting with ``<`` straight back — and
write your own ``<i>``/``<svg>`` in templates you control. Nothing in adminata requires Font Awesome
except its own templates' class names, and those are yours to override.
