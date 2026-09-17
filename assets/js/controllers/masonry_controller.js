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
 * Masonry for a grid of cards of unequal height — the `masonry` layout of a form or show tab.
 *
 * A grid row is as tall as its tallest item, so a short card beside a tall one drags a card's
 * worth of empty page under it. Here the grid's implicit rows are `unit` pixels tall and every
 * item spans as many of them as its content is high, gap included; the grid's own row-major
 * auto-placement then puts each item into the first free cell, which is under whichever column
 * ended soonest. That is masonry with nothing positioned absolutely and nothing moved in the DOM:
 * focus stays where it is, the controllers inside a card never reconnect, a listbox overflows its
 * card as usual, and the reading order is the declaration order. The column count is whatever the
 * container's `grid-cols-*` classes say at the current width, and an item with a column span of
 * its own (`col-span-full` for a wide table) keeps it.
 *
 * Once the browser has placed the items, each one is pinned to the column it landed in. From then
 * on a card that grows — a collection row added, a validation error shown, a section revealed —
 * only pushes the cards under it down, exactly as a column of ordinary flow would; without the
 * pin, auto-placement would re-slot every later card on each change, and a form that shuffles its
 * groups while somebody types in one is not a form anybody wants. The pins are dropped and the
 * packing redone only when an item's WIDTH changes — a breakpoint crossed, the sidebar folded —
 * which is the one change the person can see coming.
 *
 * Width, and not the column count: a pin is a `grid-column-start`, and a card pinned to the
 * third column of a desktop grid that has since become a one-column grid makes the grid open two
 * implicit columns to hold it — and the resolved `grid-template-columns` reports those implicit
 * tracks too, so a count taken while the pins are in place reads three columns either side of
 * the breakpoint and never repacks. `pack()` releases every pin before it looks at the grid.
 *
 * One observer, on the items and nothing else. Observing the container as well would put a
 * shallower target under the same observer loop: an item's new span grows the container, whose
 * notification then arrives at a depth already delivered, and the browser logs "ResizeObserver
 * loop completed with undelivered notifications" on every collection row added. A column-count
 * change resizes every item anyway, so the items are enough to notice it.
 *
 * Without the script the container is an ordinary grid with `align-items: start` — the layout
 * every form had before, one card per cell.
 */
export default class extends Controller {
    static targets = ['item'];
    static values = {
        unit: { type: Number, default: 4 },
    };

    connect() {
        /** @type {WeakMap<Element, number>} the width each item was last packed at */
        this.widths = new WeakMap();
        this.gap = parseFloat(getComputedStyle(this.element).columnGap) || 0;
        this.element.style.gridAutoRows = `${this.unitValue}px`;
        this.element.style.rowGap = '0px';

        this.observer = new ResizeObserver((entries) => {
            const resized = entries.some((entry) => {
                const width = entry.target.getBoundingClientRect().width;

                return width > 0 && width !== this.widths.get(entry.target);
            });

            if (resized) {
                this.pack();

                return;
            }

            for (const entry of entries) {
                this.fit(entry.target);
            }
        });

        this.itemTargets.forEach((item) => this.observer.observe(item));
        this.pack();
    }

    disconnect() {
        this.observer.disconnect();
        this.itemTargets.forEach((item) => {
            this.release(item);
            item.style.removeProperty('grid-row-end');
            item.style.removeProperty('align-self');
        });
        this.element.style.removeProperty('grid-auto-rows');
        this.element.style.removeProperty('row-gap');
    }

    itemTargetConnected(item) {
        if (this.observer) {
            this.observer.observe(item);
            this.fit(item);
        }
    }

    itemTargetDisconnected(item) {
        if (this.observer) {
            this.observer.unobserve(item);
        }
    }

    /**
     * Sizes every item to its content, lets the grid place them, then pins each to its column.
     */
    pack() {
        const items = this.itemTargets;

        // Released first: a pin can hold implicit columns open, and the tracks read next must be
        // the explicit grid's.
        items.forEach((item) => this.release(item));

        const tracks = this.tracks();

        items.forEach((item) => {
            this.widths.set(item, item.getBoundingClientRect().width);
            this.fit(item);
        });

        // Reading the positions forces the layout the pins are read from.
        const columns = items.map((item) => this.columnOf(item, tracks));
        items.forEach((item, index) => this.pin(item, columns[index]));
    }

    /**
     * The container's resolved column tracks, in pixels.
     *
     * @returns {number[]}
     */
    tracks() {
        return getComputedStyle(this.element)
            .gridTemplateColumns.split(' ')
            .map((track) => parseFloat(track))
            .filter((track) => !Number.isNaN(track));
    }

    /**
     * Spans the item over as many unit rows as its content and the gap under it need.
     *
     * The item is start-aligned first, whatever the container says: an item stretched to its
     * area is as tall as its span, and a span computed from that grows by a gap on every
     * measurement, for ever.
     *
     * @param {HTMLElement} item
     */
    fit(item) {
        item.style.alignSelf = 'start';

        const height = item.getBoundingClientRect().height;

        if (0 === height) {
            // Hidden, or not laid out: a `hidden` group is no grid item at all.
            return;
        }

        item.style.gridRowEnd = `span ${Math.max(1, Math.ceil((height + this.gap) / this.unitValue))}`;
    }

    /**
     * The 1-based column the item was placed in, from where its inline-start edge sits among the
     * container's resolved column tracks.
     *
     * @param {HTMLElement} item
     * @param {number[]} tracks
     * @returns {number}
     */
    columnOf(item, tracks) {
        const style = getComputedStyle(this.element);
        const container = this.element.getBoundingClientRect();
        const box = item.getBoundingClientRect();
        const offset =
            'rtl' === style.direction
                ? container.right - (parseFloat(style.paddingRight) || 0) - box.right
                : box.left - container.left - (parseFloat(style.paddingLeft) || 0);

        let start = 0;

        for (let index = 0; index < tracks.length; index += 1) {
            if (offset < start + tracks[index] + this.gap / 2) {
                return index + 1;
            }

            start += tracks[index] + this.gap;
        }

        return Math.max(1, tracks.length);
    }

    /**
     * An item that placed itself — `col-span-full`, or any explicit start line — is left alone:
     * a pin would only repeat what its own classes already say. One that merely spans
     * (`col-span-2`) is auto-placed like the rest and is pinned with its span kept.
     *
     * @param {HTMLElement} item
     * @param {number} column
     */
    pin(item, column) {
        const start = getComputedStyle(item).gridColumnStart;

        if ('auto' !== start && !start.startsWith('span')) {
            return;
        }

        item.style.gridColumnStart = String(column);
        item.dataset.adminataMasonryPinned = '';
    }

    /**
     * @param {HTMLElement} item
     */
    release(item) {
        if ('adminataMasonryPinned' in item.dataset) {
            item.style.removeProperty('grid-column-start');
            delete item.dataset.adminataMasonryPinned;
        }
    }
}
