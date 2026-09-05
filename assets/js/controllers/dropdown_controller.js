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
 * A disclosure with a panel, in place of Bootstrap's `data-toggle="dropdown"`.
 *
 * The panel is a plain `hidden` element and the button carries `aria-expanded`, so the closed
 * state is correct in the HTML the server sends. Clicking outside closes it, Escape closes it and
 * returns focus to the button, and the arrow keys walk the panel's focusable items — which is what
 * a menu is expected to do, and what Bootstrap's plugin did.
 *
 * Used by the header's add and user menus, by an application's own dropdowns, and from M3 by the
 * list's filter and action panels.
 */
export default class extends Controller {
    static targets = ['toggle', 'menu'];

    static values = {
        open: { type: Boolean, default: false },
    };

    connect() {
        this.onDocumentClick = (event) => {
            if (this.openValue && !this.element.contains(event.target)) {
                this.openValue = false;
            }
        };

        document.addEventListener('click', this.onDocumentClick);
        this.render();
    }

    disconnect() {
        document.removeEventListener('click', this.onDocumentClick);
    }

    toggle(event) {
        event?.preventDefault();
        // Otherwise the same click reaches the document listener and closes what it just opened.
        event?.stopPropagation();

        this.openValue = !this.openValue;
    }

    open(event) {
        event?.preventDefault();
        event?.stopPropagation();

        this.openValue = true;
    }

    close(event) {
        event?.preventDefault();

        this.openValue = false;
    }

    /** Escape closes the panel and puts focus back where it came from. */
    closeAndFocus(event) {
        if (!this.openValue) {
            return;
        }

        event?.preventDefault();

        this.openValue = false;
        this.toggleTargets[0]?.focus();
    }

    /**
     * Down and up walk the panel; down from the closed button opens it on the first item, which is
     * what makes the menu reachable from the keyboard at all.
     */
    navigate(event) {
        const forward = 'ArrowDown' === event.key;

        if (!forward && 'ArrowUp' !== event.key) {
            return;
        }

        event.preventDefault();

        if (!this.openValue) {
            this.openValue = true;
            // `render()` before focusing, not after: Stimulus delivers `openValueChanged` on a
            // microtask, and a browser refuses to focus an element that is still `hidden`. jsdom
            // does not, which is why the Panther test is what holds this.
            this.render();
            this.focusItem(forward ? 0 : -1);

            return;
        }

        const items = this.items();
        const current = items.indexOf(document.activeElement);

        if (-1 === current) {
            this.focusItem(forward ? 0 : -1);

            return;
        }

        this.focusItem((current + (forward ? 1 : -1) + items.length) % items.length);
    }

    openValueChanged() {
        this.render();
        this.dispatch(this.openValue ? 'opened' : 'closed');
    }

    render() {
        this.toggleTargets.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', String(this.openValue));
        });

        this.menuTargets.forEach((menu) => {
            menu.hidden = !this.openValue;
        });
    }

    focusItem(index) {
        const items = this.items();

        items.at(index)?.focus();
    }

    /**
     * @returns {HTMLElement[]}
     */
    items() {
        return this.menuTargets.flatMap((menu) => [
            ...menu.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'),
        ]);
    }
}
