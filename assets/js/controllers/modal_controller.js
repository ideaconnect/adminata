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

const SIZES = { sm: 'adm-dialog-sm', md: '', lg: 'adm-dialog-lg', list: 'adm-dialog-list' };

/**
 * A native `<dialog>`, in place of Bootstrap's `.modal()`.
 *
 * The browser's top layer gives the focus trap, the Escape key and the backdrop for nothing, which
 * is why adminata ships no modal library (PLAN/01 J7). What is left is the part the platform does
 * not decide: which size, whether clicking the backdrop closes it, and telling the page it opened.
 *
 * Applications may use it on their own dialogs — that is the point of putting it in the registry
 * rather than wiring it into one template.
 *
 * PLAN/05 §3 also had it move the dialog to `document.body` on first open. It does not, and should
 * not: a top-layer element is already outside every ancestor's stacking context and `overflow`, so
 * the move buys nothing — while taking the dialog out of this controller's element unbinds every
 * `data-action` inside it, including the close button.
 */
export default class extends Controller {
    static targets = ['dialog'];

    static values = {
        size: { type: String, default: 'md' },
        closable: { type: Boolean, default: true },
    };

    connect() {
        this.dialog = this.hasDialogTarget ? this.dialogTarget : null;

        if (null === this.dialog) {
            return;
        }

        this.dialog.classList.add('adm-dialog');
        this.applySize();

        this.onClose = () => this.dispatch('closed', { detail: { dialog: this.dialog } });
        this.onCancel = (event) => {
            // `cancel` is Escape. A dialog that is not closable stays put, which is what a
            // confirmation the page is waiting on needs.
            if (!this.closableValue) {
                event.preventDefault();
            }
        };
        this.onBackdropClick = (event) => {
            if (this.closableValue && event.target === this.dialog) {
                this.dialog.close();
            }
        };

        this.dialog.addEventListener('close', this.onClose);
        this.dialog.addEventListener('cancel', this.onCancel);
        this.dialog.addEventListener('click', this.onBackdropClick);
    }

    disconnect() {
        if (!this.dialog) {
            return;
        }

        this.dialog.removeEventListener('close', this.onClose);
        this.dialog.removeEventListener('cancel', this.onCancel);
        this.dialog.removeEventListener('click', this.onBackdropClick);
    }

    open(event) {
        event?.preventDefault();

        if (!this.dialog) {
            return;
        }

        this.dialog.showModal();
        this.dispatch('opened', { detail: { dialog: this.dialog } });
    }

    close(event) {
        event?.preventDefault();

        this.dialog?.close();
    }

    sizeValueChanged() {
        if (this.dialog) {
            this.applySize();
        }
    }

    applySize() {
        Object.values(SIZES)
            .filter(Boolean)
            .forEach((className) => this.dialog.classList.remove(className));

        const size = SIZES[this.sizeValue];

        if (size) {
            this.dialog.classList.add(size);
        }
    }
}
