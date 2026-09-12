/*
 * This file is part of the adminata package.
 *
 * (c) IDCT Bartosz Pachołek <bartosz@idct.tech>
 *
 * Forked from the Sonata Project
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';

/**
 * Opens a dialog from anywhere on the page, with a title and content of the trigger's choosing.
 *
 * `adminata-modal` sits on a `<dialog>` and drives it; its `open` action can only be wired from
 * inside that dialog's own markup. This is the other half: a button in a table cell, a show page,
 * a dashboard block, that wants the layout's shared dialog to show *this* note. A `<button>`
 * rather than a link — it opens something here, and Space has to work — and, when it is only an
 * icon, one with an `aria-label`: the `title` names the dialog, not the button. It
 * fills the dialog's title and body and hands the opening to the `adminata-modal` instance on it,
 * so the size, the backdrop, the announcement of `adminata-modal:opened` and every close button stay
 * that controller's business.
 *
 * Content comes in one of two forms, and the difference is deliberate. `text` is set as text and
 * renders exactly as written, which is what user-entered content — a note, a rejection reason —
 * needs; nothing in it becomes markup. `content` names an element on the page whose markup is
 * copied into the body: server-rendered, already trusted as part of the page, and the way to show
 * something formatted. A `<template>` works there as well as a hidden `<div>`.
 *
 * The dialog is the layout's `adminata-dialog` unless `target` names another one, which must carry
 * `adminata-modal` and label itself with `aria-labelledby` — that is how its title is found. A
 * trigger with no `title` leaves the dialog the heading it was rendered with, rather than an empty
 * one: an open dialog with no name is what that would be.
 */
/** The title each dialog was rendered with, so a trigger that names none can put it back. */
const defaults = new WeakMap();

export default class extends Controller {
    static values = {
        target: { type: String, default: 'adminata-dialog' },
        title: String,
        text: String,
        content: String,
        size: String,
    };

    /**
     * @param {Event} [event]
     */
    open(event) {
        event?.preventDefault();

        const dialog = document.getElementById(this.targetValue);

        if (!(dialog instanceof HTMLDialogElement)) {
            throw new Error(`adminata-modal-trigger: there is no <dialog id="${this.targetValue}"> to open.`);
        }

        this.fill(dialog);

        const modal = this.application.getControllerForElementAndIdentifier(dialog, 'adminata-modal');

        if (null === modal) {
            dialog.showModal();

            return;
        }

        if (this.hasSizeValue && '' !== this.sizeValue) {
            // The trigger's size lasts for its own opening: the next trigger, or the dialog's own
            // buttons, get the size the dialog declared for itself.
            const declared = modal.sizeValue;
            dialog.addEventListener('close', () => (modal.sizeValue = declared), { once: true });
            modal.sizeValue = this.sizeValue;
        }

        modal.open();
    }

    /**
     * @param {HTMLDialogElement} dialog
     */
    fill(dialog) {
        const labelledBy = dialog.getAttribute('aria-labelledby');
        const title = null === labelledBy ? null : document.getElementById(labelledBy);
        const body = dialog.querySelector('.adm-dialog__body');

        if (null !== title) {
            if (!defaults.has(title)) {
                defaults.set(title, title.textContent);
            }

            title.textContent =
                this.hasTitleValue && '' !== this.titleValue ? this.titleValue : defaults.get(title);
        }

        if (null === body) {
            return;
        }

        if (this.hasContentValue && '' !== this.contentValue) {
            const source = document.getElementById(this.contentValue);

            if (null === source) {
                throw new Error(
                    `adminata-modal-trigger: there is no element "${this.contentValue}" to take the content from.`,
                );
            }

            body.innerHTML = source.innerHTML;

            return;
        }

        body.textContent = this.hasTextValue ? this.textValue : '';
    }
}
