Compiling Tailwind yourself
===========================

adminata ships a built stylesheet with every ``.adm-*`` component and the utilities **its own**
templates use. Tailwind only generates a utility it can see in a scanned source, so a class that
exists nowhere in adminata — ``grid-cols-7``, ``dark:text-gray-400``, ``md:col-span-5`` — is
simply not in that file, however correct it looks in your template.

The moment your own templates use Tailwind utilities, compile the stylesheet yourself. You then get
adminata's tokens, base layer and components *plus* whatever your markup asks for.

The entry file
--------------

.. code-block:: css

    /* assets/styles/admin.css */
    @import "tailwindcss";
    @import "../../vendor/idct/adminata/assets/css/adminata.css";

    @source "../../vendor/idct/adminata/packages";
    @source not "../../vendor/idct/adminata/packages/*/tests";
    @source "../../vendor/idct/adminata/assets/js";
    @source "../../templates";
    @source "../../src/Admin";
    @source "../../src/Form";
    @source "../js";

Two imports and a source list, and each line earns its place:

* ``adminata.css`` is the aggregate **without** Tailwind and without source globs — tokens, base
  layer, every component — meant exactly for this.
* The first ``@source`` scans adminata's own templates, so its components keep the utilities they
  compose from. Excluding ``*/tests`` keeps the demo application's markup out of your stylesheet.
* ``assets/js`` matters because controllers add and remove classes at runtime; a class that only
  ever appears in JavaScript is invisible to Tailwind otherwise.
* ``src/Admin`` and ``src/Form`` matter because admin classes put class names in PHP — a group's
  ``class`` option, a dashboard block's, a form ``row_attr``.

.. warning::

    A class assembled at render time is invisible to Tailwind. ``border-t-{{ tone }}`` generates
    nothing, whatever ``tone`` holds. Pass the **whole utility** from PHP or from the caller —
    ``border-t-success-500`` — rather than a fragment to interpolate.

Webpack Encore
--------------

.. code-block:: javascript

    // postcss.config.mjs
    export default {
        plugins: {
            '@tailwindcss/postcss': {},
        },
    };

.. code-block:: javascript

    // webpack.config.js
    Encore
        .addStyleEntry('admin', './assets/styles/admin.css')
        .enablePostCssLoader()

Then tell adminata to stop shipping its own copy, and to load yours:

.. code-block:: yaml

    sonata_admin:
        assets:
            remove_stylesheets:
                - bundles/sonataadmin/app.css
            extra_stylesheets:
                - build/admin.css

Leaving both in place is the mistake worth naming: the bundle's copy is older and narrower than
yours, and whichever loads second wins per property. Remove it.

AssetMapper and Vite
--------------------

Not documented for 1.0. AssetMapper has no PostCSS step, so Tailwind has to run as a separate
build; Vite works but the recipe is not one adminata has exercised. Both are on the list for a
later release — open an issue if you need one now.

Checking your build
-------------------

If a component looks unstyled, check that its class is in the output before looking anywhere else:

.. code-block:: console

    $ grep -c 'adm-card' public/build/admin.css

adminata guards its own build the same way: ``assets/css/contract.json`` lists every component
selector, and a check asserts each one exists in the compiled stylesheet.
