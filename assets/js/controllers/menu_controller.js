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
 * The sidebar's collapsible groups, in place of AdminLTE's `data-widget="tree"`.
 *
 * The server decides which groups start open — the one holding the current page, and any marked
 * `keep-open` — and writes that into `aria-expanded`, so the menu is right before any JavaScript
 * runs and stays right with none at all. This remembers what a visitor opens and closes on top of
 * that, per group, in `localStorage`.
 *
 * Several groups may be open at once. AdminLTE closed the others; a menu with two open sections is
 * not a bug, and closing one because another was opened loses work a visitor did on purpose.
 *
 * A group marked `keep-open` never closes: it is a server-side decision, and the toggle is not
 * offered as a way to overrule it.
 */
export default class extends Controller {
    static targets = ['toggle'];

    static values = {
        storageKey: { type: String, default: 'sonata_sidebar_open' },
    };

    connect() {
        this.restore();
    }

    toggle(event) {
        const toggle = event.currentTarget;

        if (this.isPinned(toggle)) {
            return;
        }

        this.setExpanded(toggle, 'true' !== toggle.getAttribute('aria-expanded'));
        this.remember();
    }

    /**
     * Applies what the visitor last chose, over what the server rendered.
     *
     * A group the server opened because it holds the current page is left open whatever the store
     * says: the page a visitor is on should be visible in the menu they are looking at.
     */
    restore() {
        const stored = this.read();

        this.toggleTargets.forEach((toggle) => {
            const key = this.keyFor(toggle);
            const expanded = 'true' === toggle.getAttribute('aria-expanded');

            if (expanded || this.isPinned(toggle) || !(key in stored)) {
                this.setExpanded(toggle, expanded || this.isPinned(toggle));

                return;
            }

            this.setExpanded(toggle, stored[key]);
        });
    }

    /**
     * `aria-expanded` is the whole state. The stylesheet hides the panel of a button that says
     * `false`, so there is one thing to set and nothing to keep in step with it.
     */
    setExpanded(toggle, expanded) {
        toggle.setAttribute('aria-expanded', String(expanded));
    }

    remember() {
        const open = {};

        this.toggleTargets.forEach((toggle) => {
            open[this.keyFor(toggle)] = 'true' === toggle.getAttribute('aria-expanded');
        });

        this.write(open);
    }

    /**
     * A group is identified by its label, which is what an application sees and what survives a
     * deployment; the DOM order does not.
     */
    keyFor(toggle) {
        return (toggle.textContent ?? '').trim();
    }

    isPinned(toggle) {
        return 'true' === toggle.dataset.sonataMenuKeepOpen;
    }

    /**
     * @returns {Record<string, boolean>}
     */
    read() {
        try {
            const stored = JSON.parse(window.localStorage.getItem(this.storageKeyValue) ?? '{}');

            return null !== stored && 'object' === typeof stored ? stored : {};
        } catch {
            // A browser with storage disabled, or a value someone else wrote. The menu works
            // without it; refusing to render because of it would not help anyone.
            return {};
        }
    }

    write(open) {
        try {
            window.localStorage.setItem(this.storageKeyValue, JSON.stringify(open));
        } catch {
            // Private mode, a full quota: the menu still works, it just forgets.
        }
    }
}
