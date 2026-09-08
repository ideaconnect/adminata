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
 * Asks a question in a dialog before an action goes ahead — what `window.confirm()` did, in the
 * page's own dialog rather than the browser's box.
 *
 * It sits on the thing that acts: a submit `<button>`, a `<form>`, or a link. Its `ask` action
 * stops that thing, fills the layout's question dialog (`sonata-question-dialog`, rendered beside
 * the shared one by the `sonata_dialog` block) with the `text`, an optional `title` and the two
 * button labels, and opens it through the `sonata-modal` on it — so the size, the backdrop, Escape
 * and `sonata-modal:opened` stay that controller's. Then one of two things happens:
 *
 * - the person confirms: `sonata-question:confirmed` is dispatched on the element, and unless a
 *   listener called `preventDefault()` on it the action goes ahead by itself — the form is
 *   submitted (with the button as its submitter, so its `name`, `value` and `formaction` count),
 *   or the link is followed;
 * - the dialog closes any other way — the cancel button, the close button, Escape:
 *   `sonata-question:cancelled` is dispatched and nothing else happens. The backdrop does not
 *   close it; a stray click outside a question is not an answer.
 *
 * A controller of the application's own that needs to do something else on a yes — build a form
 * where nothing can enclose it, say — listens for `confirmed`, prevents its default and acts.
 *
 * `text` is set as text: nothing in it becomes markup, which is what a question built from a
 * record's own fields needs. Another dialog can be named with `target`; it must carry
 * `sonata-modal`, an `aria-labelledby` for its heading, and the three `data-sonata-question-*`
 * hooks the layout's has.
 */
/** The labels each dialog was rendered with, so a question that names none puts them back. */
const defaults = new WeakMap();

export default class extends Controller {
    static values = {
        target: { type: String, default: 'sonata-question-dialog' },
        text: String,
        title: String,
        confirm: String,
        cancel: String,
    };

    /**
     * @param {Event} [event]
     */
    ask(event) {
        // The follow-through re-enters a `<form>` trigger's own `submit` action; let it pass.
        if (this.confirmed) {
            this.confirmed = false;

            return;
        }

        event?.preventDefault();

        // A `submit` event names the button that submitted; the follow-through hands it back to
        // the form so its name, value and formaction count exactly as they would have.
        this.submitter = event?.submitter instanceof HTMLElement ? event.submitter : null;

        const dialog = document.getElementById(this.targetValue);

        if (!(dialog instanceof HTMLDialogElement)) {
            throw new Error(`sonata-question: there is no <dialog id="${this.targetValue}"> to ask in.`);
        }

        const confirmButton = dialog.querySelector('[data-sonata-question-confirm]');

        if (!(confirmButton instanceof HTMLButtonElement)) {
            throw new Error(`sonata-question: <dialog id="${this.targetValue}"> has no confirm button.`);
        }

        this.fill(dialog, confirmButton);

        let answered = false;

        const onConfirm = () => {
            answered = true;
            dialog.close();
            const outcome = this.dispatch('confirmed', { cancelable: true });

            if (!outcome.defaultPrevented) {
                this.follow();
            }
        };

        const onClose = () => {
            confirmButton.removeEventListener('click', onConfirm);

            if (!answered) {
                this.dispatch('cancelled');
            }
        };

        confirmButton.addEventListener('click', onConfirm);
        dialog.addEventListener('close', onClose, { once: true });

        const modal = this.application.getControllerForElementAndIdentifier(dialog, 'sonata-modal');

        if (null === modal) {
            dialog.showModal();
        } else {
            modal.open();
        }

        confirmButton.focus();
    }

    /**
     * @param {HTMLDialogElement} dialog
     * @param {HTMLButtonElement} confirmButton
     */
    fill(dialog, confirmButton) {
        const labelledBy = dialog.getAttribute('aria-labelledby');
        const title = null === labelledBy ? null : document.getElementById(labelledBy);
        const text = dialog.querySelector('[data-sonata-question-text]');
        const cancelButton = dialog.querySelector('[data-sonata-question-cancel]');

        for (const [element, value] of [
            [title, this.hasTitleValue ? this.titleValue : ''],
            [confirmButton, this.hasConfirmValue ? this.confirmValue : ''],
            [cancelButton, this.hasCancelValue ? this.cancelValue : ''],
        ]) {
            if (null === element) {
                continue;
            }

            if (!defaults.has(element)) {
                defaults.set(element, element.textContent);
            }

            element.textContent = '' !== value ? value : defaults.get(element);
        }

        if (null !== text) {
            text.textContent = this.hasTextValue ? this.textValue : '';
        }
    }

    /** Does what the element was about to do before the question. */
    follow() {
        const element = this.element;

        if (element instanceof HTMLFormElement) {
            const submitter =
                this.submitter?.form === element && 'submit' === this.submitter.type
                    ? this.submitter
                    : undefined;

            // The guard lasts for the synchronous `submit` dispatch inside requestSubmit() and no
            // longer: a form that fails its own validation submits nothing, and the next question
            // must still be asked.
            this.confirmed = true;

            try {
                element.requestSubmit(submitter);
            } finally {
                this.confirmed = false;
            }

            return;
        }

        if ((element instanceof HTMLButtonElement || element instanceof HTMLInputElement) && element.form) {
            // The button is the submitter when it is one, so its name, value and formaction count
            // exactly as they would have on the click that was stopped.
            if ('submit' === element.type) {
                element.form.requestSubmit(element);
            } else {
                element.form.requestSubmit();
            }

            return;
        }

        if (element instanceof HTMLAnchorElement && '' !== element.href) {
            window.location.assign(element.href);
        }
    }
}
