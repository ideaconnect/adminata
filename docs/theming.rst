Theming
=======

adminata's look is a set of CSS custom properties. Redefining one in a stylesheet that loads after
adminata's changes the interface, with no rebuild: ``@theme static`` emits every variable whether
or not a utility uses it, which is what makes that possible.

Light, dark and system
----------------------

The mode is resolved **on the server**, before a byte is sent, from the ``adminata_theme`` cookie:

.. code-block:: yaml

    # config/packages/adminata.yaml
    adminata:
        options:
            theme:
                mode: system   # light | dark | system

``light`` and ``dark`` pin the interface. ``system`` follows the operating system, and stays
following it — the toggle in the header cycles ``light`` → ``dark`` → ``system`` rather than
dropping ``system`` once a visitor has touched it, so a machine that switches at sunset keeps doing
so.

Because the server writes ``<html class="dark" data-theme="dark">`` itself, there is no flash of
the wrong theme on the first paint. The only inline script adminata ships is the three lines that
resolve ``system`` against ``prefers-color-scheme`` before the stylesheet loads.

Writing your own dark rules
^^^^^^^^^^^^^^^^^^^^^^^^^^^

The ``dark`` variant is defined as ``&:where(.dark, .dark *)``. The ``:where()`` is deliberate: it
adds **no specificity**, so your own rules can override adminata's without an arms race.

The cost is that a dark rule and the light rule it has to beat are an exact specificity tie, and
**source order decides**:

.. code-block:: text

    /* Wrong: the light :hover below wins, and dark mode paints light-on-light. */
    .thing {
        @variant dark { &:hover { background: #000; } }
        &:hover { background: #eee; }
    }

    /* Right: the dark block comes last. */
    .thing {
        &:hover { background: #eee; }
        @variant dark { &:hover { background: #000; } }
    }

adminata shipped that bug once, in its dropdown items, and now has a contract check that reads the
built stylesheet and fails when a dark rule precedes a plain rule with the same selector.

Brand colour
------------

Eleven steps, ``--color-brand-25`` through ``--color-brand-950``. ``--color-brand-500`` is the one
you see most: primary buttons, the active menu item, focus rings, links.

.. code-block:: css

    /* after adminata's stylesheet */
    :root {
        --color-brand-500: #0f766e;
        --color-brand-600: #0d5f59;
        --color-brand-300: #5eead4;  /* focus borders */
    }

Redefine the whole ramp rather than one step: the components use several of them together, and a
single overridden step reads as a mismatch on hover and focus.

The same applies to ``--color-success-*``, ``--color-error-*``, ``--color-warning-*``,
``--color-blue-light-*`` and the ``--color-gray-*`` scale the chrome is built from.

Density
-------

Eight measurements decide how tight the interface is. The components read them instead of
hard-coding, so a compact mode is a matter of redefining values rather than rewriting components:

===============================  ==========  ==========
Token                            Default     Compact
===============================  ==========  ==========
``--adm-control-h``              2.75rem     2.25rem
``--adm-control-px``             1rem        0.75rem
``--adm-control-py``             0.625rem    0.375rem
``--adm-cell-px``                1rem        0.75rem
``--adm-cell-py``                0.75rem     0.5rem
``--adm-card-p``                 1.5rem      1rem
``--adm-header-h``               4rem        3.25rem
``--adm-sidebar-w``              290px       —
``--adm-sidebar-collapsed-w``    90px        —
===============================  ==========  ==========

The compact column ships: put ``data-density="compact"`` on ``<html>`` and adminata redefines them
for you.

Radius
------

``--radius-control`` (0.5rem) for inputs, buttons and menu items; ``--radius-card`` (1rem) for
cards, dialogs and dropdown panels.

A box nested inside a card should take ``calc(var(--radius-card) - 1px)``, not the card's own
radius: the inner curve has to be a border-width tighter than the outer one for the two to look
concentric.

Fonts
-----

adminata self-hosts **Outfit** as a variable font and exposes it as ``--font-outfit``; the base
layer sets it on ``body``. Nothing fetches from Google Fonts.

To use your own, redefine the token and ship the ``@font-face``:

.. code-block:: css

    @font-face {
        font-family: 'Inter Variable';
        src: url('/fonts/inter.woff2') format('woff2-variations');
        font-weight: 100 900;
        font-display: swap;
    }

    :root {
        --font-outfit: 'Inter Variable', ui-sans-serif, system-ui, sans-serif;
    }

Always keep a real fallback stack: a variable font that fails to load should degrade to the
system's, not to a serif default.
