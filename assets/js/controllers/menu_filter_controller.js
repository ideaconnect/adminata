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
 * The field above the sidebar menu that narrows it to what matches what was typed.
 *
 * The menu does the narrowing — it owns its items, its groups and what is remembered about them
 * (`adminata-menu`'s `filter()`) — and this hands it the field's value through an `adminata-menu`
 * outlet. What the menu answers comes back as `adminata-menu:filtered`, whoever asked: the status
 * says when nothing matches, and a filter the menu ended on its own — it does when the sidebar
 * collapses into the rail, which has no room for the field — empties the field.
 *
 * The row is rendered `hidden` and shown once a menu with a link in it has connected, so a page
 * without JavaScript offers no field that does nothing.
 *
 * In the field, Escape empties it, Enter follows the first link the narrowed menu shows, and the
 * down arrow moves the focus to that link, from where Tab goes on through the rest.
 */
export default class extends Controller {
    static targets = ['input', 'empty'];

    static outlets = ['adminata-menu'];

    connect() {
        this.offer();
    }

    adminataMenuOutletConnected() {
        this.offer();
    }

    adminataMenuOutletDisconnected() {
        this.offer();
    }

    /** Hands the menu what the field holds. */
    filter() {
        this.adminataMenuOutlets.forEach((menu) => menu.filter(this.inputTarget.value));
    }

    /**
     * Empties the field, and the menu is whole again.
     *
     * @param {KeyboardEvent} event
     */
    clear(event) {
        if ('' === this.inputTarget.value) {
            return;
        }

        event.preventDefault();
        this.inputTarget.value = '';
        this.filter();
    }

    /**
     * Follows the first link the narrowed menu shows.
     *
     * @param {KeyboardEvent} event
     */
    follow(event) {
        const link = this.firstLink();

        if (null !== link) {
            event.preventDefault();
            link.click();
        }
    }

    /**
     * Moves the focus to the first link the narrowed menu shows.
     *
     * @param {KeyboardEvent} event
     */
    focusFirst(event) {
        const link = this.firstLink();

        if (null !== link) {
            event.preventDefault();
            link.focus();
        }
    }

    /**
     * Follows the menu's answer.
     *
     * @param {CustomEvent<{query: string, active: boolean, links: number}>} event
     */
    sync({ target, detail }) {
        if (!this.adminataMenuOutlets.some((menu) => menu.element === target)) {
            return;
        }

        if (!detail.active && '' !== this.inputTarget.value.trim()) {
            this.inputTarget.value = '';
        }

        this.emptyTarget.hidden = !detail.active || 0 < detail.links;
    }

    /**
     * The first link the menu shows while the field narrows it, and `null` while it does not: an
     * Enter in an empty field opens nothing.
     *
     * @returns {HTMLAnchorElement | null}
     */
    firstLink() {
        if ('' === this.inputTarget.value.trim()) {
            return null;
        }

        return this.adminataMenuOutlets.flatMap((menu) => menu.visibleLinks())[0] ?? null;
    }

    /**
     * Offers the field only while a menu with a link in it has connected — the controller, not
     * only its element: nothing else would answer what is typed.
     */
    offer() {
        this.element.hidden = !this.adminataMenuOutlets.some(
            (menu) => null !== menu.element.querySelector('a[href]'),
        );
    }
}
