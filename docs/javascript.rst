JavaScript
==========

adminata's JavaScript is `Stimulus <https://stimulus.hotwired.dev/>`_ 3 and nothing else.

There is **no jQuery**, no jquery-ui, no jquery-form, no select2, no iCheck, no x-editable, no
Tempus Dominus, and no ``window.Admin`` facade. There is also **no AJAX form submission anywhere**:
``ajaxSubmit`` is gone and is not coming back. Forms post.

``window.sonataApplication``
----------------------------

One Stimulus application, started by adminata's bundle, with an explicit registry — no
``stimulus-bridge``, no ``require.context``. The built file contains exactly the controllers named
below.

.. code-block:: javascript

    import { Controller } from '@hotwired/stimulus';

    window.sonataApplication.register('my-thing', class extends Controller {
        connect() { /* … */ }
    });

Running a second application of your own alongside it works too, and is what the application
adminata was built for does. Each application ignores identifiers it has not registered, so the two
never collide.

Controllers
-----------

The identifiers, targets, values, classes and outlets below are a **contract**: they are recorded in
``assets/js/__contract__/controllers.json``, checked against the built bundle by the test suite, and
removing one is a major release.

``sonata-autocomplete``
    targets ``chipTemplate``, ``chips``, ``hiddenInputs``, ``input``, ``itemTemplate``, ``listbox``, ``status``; values ``delay``, ``disabled``, ``loadingText``, ``minLength``, ``minLengthText``, ``moreText``, ``multiple``, ``name``, ``noResultsText``, ``pageParameter``, ``perPage``, ``perPageParameter``, ``removeText``, ``requestParameters``, ``safeLabel``, ``searchParameter``, ``selected``, ``url``

``sonata-batch``
    targets ``all``, ``row``

``sonata-collection``
    targets ``item``; values ``numItems``

``sonata-confirm-exit``
    values ``skip``, ``snapshot``

``sonata-dismiss``
    values ``remove``

``sonata-dropdown``
    targets ``menu``, ``toggle``; values ``open``

``sonata-edit``
    targets ``tab``, ``tabStore``

``sonata-filter``
    targets ``advanced``, ``form``, ``group``, ``submitter``; values ``defaultValues``; outlets ``sonata-filter-list``

``sonata-filter-list``
    targets ``counter``, ``field``; classes ``active``; outlets ``sonata-filter``

``sonata-layout``
    targets ``collapseOnly``, ``content``, ``headerMenu``, ``headerMenuToggle``, ``overlay``, ``sidebar``, ``toggle``; values ``breakpoint``, ``collapsed``, ``cookieName``, ``headerMenuOpen``, ``mobileOpen``

``sonata-menu``
    targets ``toggle``; values ``storageKey``

``sonata-modal``
    targets ``dialog``; values ``closable``, ``size``

``sonata-per-page``
    No targets, values, classes or outlets.

    Navigates to the URL of the chosen option.

``sonata-readmore``
    targets ``button``, ``content``; values ``collapsedHeight``, ``lessText``, ``moreText``

``sonata-revision``
    targets ``preview``

``sonata-row-link``
    No targets, values, classes or outlets.

    Reads ``data-sonata-row-link-url`` off each ``<tr>``; sits on the ``<tbody>`` and delegates.

``sonata-sticky``
    targets ``action``, ``navbar``, ``topNavbar``

``sonata-theme``
    targets ``label``; values ``cookieName``, ``labels``, ``theme``

Modals are ``<dialog>``
-----------------------

adminata ships no modal library. ``sonata-modal`` opens and closes a native ``<dialog>``; the top
layer, the focus trap, the backdrop and the Escape key are the browser's.

.. code-block:: html+twig

    <dialog class="adm-dialog adm-dialog-lg"
            id="my-dialog"
            aria-labelledby="my-dialog-title"
            {{ stimulus_controller('sonata-modal') }}>
        <div class="adm-dialog__header">
            <h2 id="my-dialog-title" class="adm-card-title">…</h2>
        </div>
        <div class="adm-dialog__body">…</div>
    </dialog>

Open it from anywhere with ``document.getElementById('my-dialog').showModal()``.

Clicking a list row
-------------------

``sonata-row-link`` sits on the ``<tbody>``, once, and delegates. Each ``<tr>`` carries the
destination in ``data-sonata-row-link-url``; a row the administrator may not open carries none and
does nothing.

It stays out of the way of everything else a click on a row can mean: a click that lands on a
control, or anywhere in a cell that exists only to hold controls (the batch checkbox, the select
column, the actions), or one that ends a text selection, is left alone. A middle-click or a
modified click opens a new tab, the way it would on a link.

The row is deliberately **not** given ``tabindex`` or ``role="link"``: the accessible name of such a
control would be the entire row, and the same destination is already one Tab away in the identifier
cell and the action column. Turn the whole thing off with
``sonata_admin.options.list_row_link: false``.

Coexistence rules
-----------------

* Register on ``window.sonataApplication``, or run your own application — not both for the same
  identifier.
* Prefix your identifiers. Everything adminata owns starts with ``sonata-``.
* Do not rely on load order between the two applications. Stimulus connects controllers as elements
  appear, and an element may be connected by one application before the other has started.
