Compiling Tailwind yourself
===========================

adminata ships a built stylesheet with every ``.adm-*`` component and the utilities **its own**
templates use. Tailwind only generates a utility it can see in a scanned source, so a class that
exists nowhere in adminata — ``grid-cols-7``, ``dark:text-gray-400``, ``md:col-span-5`` — is
simply not in that file, however correct it looks in your template.

The moment your own templates use Tailwind utilities, compile the stylesheet yourself. You then get
adminata's tokens, base layer and components *plus* whatever your markup asks for.

What Composer already did
-------------------------

adminata ships an npm-facing ``assets/package.json``, and Symfony Flex treats it the way it treats
every Symfony UX package: on ``composer require`` (or any ``composer update``) it adds

.. code-block:: json

    "@idct/adminata": "file:vendor/idct/adminata/assets"

to your ``package.json``, and ``tailwindcss`` beside it if you did not have one — it is a peer
dependency of that package. Run ``npm install`` and ``node_modules/@idct/adminata`` is a link into
the bundle. Nothing here is a step you take; it is what installing the bundle does.

.. note::

    Flex does this on the ``update`` path, not on a plain ``composer install`` from a lock file. A
    fresh clone that only runs ``composer install`` has the link already, because ``package.json``
    is committed; it is the first ``composer require idct/adminata`` that writes it.

The entry file
--------------

.. code-block:: css

    /* assets/styles/admin.css */
    @import "@idct/adminata";

    @source "../../templates";
    @source "../../src/Admin";
    @source "../../src/Form";
    @source "../js";

One import and your own source list — and only your own:

* ``@idct/adminata`` is adminata's entry. It brings Tailwind itself, with automatic source
  detection switched off; adminata's tokens, base layer and every component; and the ``@source``
  list for adminata's own templates and controllers, and for the templates the storage layers
  ship. Where adminata keeps any of that is not your concern, and it is not something you have to
  keep in step with when a release moves a directory.
* Your ``@source`` lines are the part only you can write. ``templates`` is where your utilities
  are. ``src/Admin`` and ``src/Form`` matter because admin classes put class names in PHP — a
  group's ``class`` option, a dashboard block's, a form ``row_attr``. ``assets/js`` matters if
  your controllers add and remove classes at runtime; a class that only ever appears in
  JavaScript is invisible to Tailwind otherwise.

Do **not** add ``@import "tailwindcss"`` above the adminata import. adminata's entry already
imports the engine; a second import does not error, it emits the preflight twice.

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

``@tailwindcss/postcss`` and ``postcss-loader`` are yours to install: they are the toolchain's
half, not adminata's, and a project on Vite or AssetMapper would want different ones.

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

What is still yours to do, and why
----------------------------------

The entry file, the PostCSS config, the Encore line and the YAML above are four edits adminata
cannot make for you. Flex writes files into a project only from a recipe, and a recipe is served
by a recipe repository — ``symfony/recipes`` for the packages Symfony curates, or a private
endpoint a project points ``extra.symfony.endpoint`` at. Without one, all Flex does for a bundle
is register it in ``config/bundles.php``, and that it does. adminata is not on Packagist and has
no recipe repository yet; when it has one, these four edits are exactly what the recipe will
make.

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
