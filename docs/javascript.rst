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

``sonata-modal-trigger``
    values ``content``, ``size``, ``target``, ``text``, ``title``

    Fills a dialog and opens it, from anywhere on the page.

``sonata-per-page``
    No targets, values, classes or outlets.

    Navigates to the URL of the chosen option.

``sonata-readmore``
    targets ``button``, ``content``; values ``collapsedHeight``, ``lessText``, ``moreText``

``sonata-reveal``
    values ``target``, ``when``

    Sits on a control; shows the elements ``target`` selects while the control has a ``when``
    value, hides them otherwise.

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

adminata ships no modal library. A modal is a native ``<dialog>``; the top layer, the focus trap,
the backdrop and the Escape key are the browser's, and ``sonata-modal`` on the element decides the
rest — its size, whether the backdrop closes it, and telling the page it opened.

Every page carries one, ready to be filled: the layout's ``sonata-dialog``. To show something in
it, put ``sonata-modal-trigger`` on a button and say what it should show:

.. code-block:: html+twig

    <button type="button" class="adm-btn adm-btn-secondary" aria-haspopup="dialog"
            {{ stimulus_controller('sonata-modal-trigger', {
                title: 'Note #' ~ object.id,
                text: object.note,
                size: 'lg',
            }) }}
            {{ stimulus_action('sonata-modal-trigger', 'open', 'click') }}>
        Show
    </button>

A ``<button>``, not a link: it opens something on this page rather than going somewhere, a
screen reader says so, and Space activates it as Enter does. An icon-only button needs an
``aria-label`` — the trigger's ``title`` names the dialog, not the button.

``text`` is set as text and renders exactly as written — nothing in it becomes markup, which is
what user-entered content needs. For something formatted, render it on the page and name it:

.. code-block:: html+twig

    <template id="note-{{ object.id }}">{{ object.note|nl2br }}</template>

    <button type="button" class="adm-btn adm-btn-secondary" aria-haspopup="dialog"
            {{ stimulus_controller('sonata-modal-trigger', {title: 'Note #' ~ object.id, content: 'note-' ~ object.id}) }}
            {{ stimulus_action('sonata-modal-trigger', 'open', 'click') }}>
        Show
    </button>

``content`` copies that element's markup into the dialog's body; a ``<template>`` keeps it out of
the page until then, a hidden ``<div>`` works too. ``size`` (``sm``, ``md``, ``lg``, ``list``)
lasts for that opening; the dialog goes back to its own size when it closes. A trigger without a
``title`` leaves the dialog the heading it was rendered with — *Details*, in the layout's.

The shared dialog is what the ``sonata_dialog`` block of the layout renders. Override the block to
change its markup, or to leave it out. A dialog of your own works the same way — give it
``sonata-modal``, label it with ``aria-labelledby`` (that is how the trigger finds its title) and
name it in the trigger's ``target``:

.. code-block:: html+twig

    <dialog class="adm-dialog"
            id="my-dialog"
            aria-labelledby="my-dialog-title"
            {{ stimulus_controller('sonata-modal') }}
            {{ stimulus_target('sonata-modal', 'dialog') }}>
        <div class="adm-dialog__header">
            <h2 id="my-dialog-title" class="adm-card-title">…</h2>
        </div>
        <div class="adm-dialog__body">…</div>
    </dialog>

The ``dialog`` target goes on the element as well as the controller: without it ``sonata-modal`` has
nothing to drive, and ``showModal()`` from your own script would open a dialog whose close buttons
and backdrop do nothing.

A value that reveals a section
------------------------------

A form where one answer decides whether the next group applies — "uses geolocation?", then the
coordinates — is ``sonata-reveal`` on the control that answers. ``target`` selects what it
reveals, ``when`` is the value that does; on connect it puts the section in the state the saved
value asks for, and every ``change`` after that keeps it there.

In an admin class it is three attributes on the control, and the group's own ``class`` is the
hook. Render the group with the ``hidden`` class for the state it should start in, so the page
does not flash it before the controller connects; the controller drops that class when it takes
over and uses the ``hidden`` attribute from then on:

.. code-block:: php

    $form
        ->with('General')
            ->add('locationAware', BooleanType::class, [
                'attr' => [
                    'data-controller' => 'sonata-reveal',
                    'data-sonata-reveal-target-value' => '.section-geolocation',
                    'data-sonata-reveal-when-value' => '1',
                ],
            ])
        ->end()
        ->with('Geolocation', ['class' => 'section-geolocation col-span-12'.($subject->isLocationAware() ? '' : ' hidden')])
            ->add('location', TextType::class)
        ->end();

``when`` takes one value, or a JSON list of them — ``'["1","2"]'`` — which is what the Stimulus
helper writes when it is handed a PHP array:
``$this->stimulus->createStimulusAttributes()->addController('sonata-reveal', ['target' => '…', 'when' => ['1', '2']])->toArray()``.
The control can be a ``<select>`` — a multiple one counts each selected value — a checkbox, or the
wrapper of a radio group.

``target`` is resolved from the nearest ancestor that holds both the control and a match, not from
the page. That is what lets the group's class be the hook inside a collection, where every row
renders the same group with the same class: each row's control finds its own row's section.

A hidden field is still a field. It submits, and a ``required`` one still blocks the form; keep
the constraint on the master, or on the server, where it sees the whole picture.

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
