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
 *
 * Opening and closing slide, the way Sonata's AdminLTE menu did. The height is animated from here
 * rather than in CSS because there is no height to transition *to* — the panel is `auto`, and the
 * CSS that can interpolate that (`interpolate-size`, `calc-size()`) is Chromium-only at the time of
 * writing. Only a click animates: restoring what a visitor last chose must not make the sidebar
 * unfold on every page load.
 */
export default class extends Controller {
    static targets = ['toggle'];

    static values = {
        storageKey: { type: String, default: 'adminata_sidebar_open' },
    };

    connect() {
        this.restore();
    }

    disconnect() {
        // A panel left mid-animation would keep its inline height and its `overflow: hidden`.
        this.toggleTargets.forEach((toggle) => this.settle(this.panelFor(toggle)));
    }

    toggle(event) {
        const toggle = event.currentTarget;

        if (this.isPinned(toggle)) {
            return;
        }

        this.setExpanded(toggle, 'true' !== toggle.getAttribute('aria-expanded'), { animate: true });
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
     * `false`, so there is one thing to set and nothing to keep in step with it — the slide is
     * decoration on top, and the attribute is correct before, during and after it.
     *
     * @param {HTMLElement} toggle
     * @param {boolean} expanded
     * @param {{animate?: boolean}} options
     */
    setExpanded(toggle, expanded, { animate = false } = {}) {
        const wasExpanded = 'true' === toggle.getAttribute('aria-expanded');
        const panel = this.panelFor(toggle);
        const sliding = animate && panel !== null && expanded !== wasExpanded;

        // Measured *before* the attribute changes: the stylesheet hides a closed group's panel, and
        // a hidden element has no height to start a closing animation from.
        const from = sliding ? this.heightOf(panel, wasExpanded) : 0;

        toggle.setAttribute('aria-expanded', String(expanded));

        if (sliding) {
            this.slide(panel, expanded, from);
        }
    }

    /**
     * What a panel is currently showing: the height it is animating through if one is in flight,
     * its full height if it is open, and nothing if it is closed.
     */
    heightOf(panel, expanded) {
        if (panel.dataset.sliding !== undefined) {
            return panel.getBoundingClientRect().height;
        }

        return expanded ? panel.scrollHeight : 0;
    }

    /**
     * The panel a group's button controls, which is the element straight after it.
     *
     * @returns {HTMLElement | null}
     */
    panelFor(toggle) {
        const panel = toggle.nextElementSibling;

        return panel instanceof HTMLElement && panel.classList.contains('menu-dropdown') ? panel : null;
    }

    /**
     * Animates a panel between nothing and its own height.
     *
     * `aria-expanded` is already set, so the stylesheet shows the panel when opening and hides it
     * when closing; an inline `display` keeps a closing one on screen until the transition is done,
     * and `settle()` takes that back off.
     *
     * @param {HTMLElement} panel
     * @param {boolean} expanded
     * @param {number} from the height to start at, measured before the attribute changed
     */
    slide(panel, expanded, from) {
        panel.style.removeProperty('height');

        // A closing panel has to stay visible for the length of the animation, whatever the
        // attribute the stylesheet reads now says.
        if (expanded) {
            panel.style.removeProperty('display');
        } else {
            panel.style.display = 'flex';
        }

        const to = expanded ? panel.scrollHeight : 0;

        panel.dataset.sliding = '';
        panel.style.height = `${from}px`;

        // Read back, so the browser has a height to transition *from* rather than coalescing both
        // assignments into one paint.
        void panel.offsetHeight;

        panel.style.height = `${to}px`;

        const done = (event) => {
            if (event !== undefined && event.propertyName !== 'height') {
                return;
            }

            panel.removeEventListener('transitionend', done);
            this.settle(panel);
        };

        panel.addEventListener('transitionend', done);

        // `transitionend` never fires when the motion preference removed the transition, and it
        // does not fire for a zero-length change either.
        if (from === to || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            done();
        }
    }

    /**
     * Takes the animation's inline state back off, leaving the stylesheet in charge.
     *
     * @param {HTMLElement | null} panel
     * @param {{keepHidden?: boolean}} options
     */
    settle(panel, { keepHidden = false } = {}) {
        if (panel === null) {
            return;
        }

        delete panel.dataset.sliding;
        panel.style.removeProperty('height');

        if (!keepHidden) {
            panel.style.removeProperty('display');
        }
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
        return 'true' === toggle.dataset.adminataMenuKeepOpen;
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
