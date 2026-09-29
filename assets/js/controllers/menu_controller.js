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
 * How far a rail popup keeps from the window's top and bottom edges, in pixels: the half-rem the
 * stylesheet's `max-height` leaves twice.
 */
const POPUP_MARGIN = 8;

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
 *
 * On the collapsed rail a group has no room to unfold — the rail is 90px of icons — so there a
 * top-level group's button opens its panel as a popup beside the rail instead, headed by the
 * group's name: the flyout AdminLTE's `sidebar-mini` showed on hover, opened here by a click. One
 * popup at a time, closed by its button, by a click anywhere else, by Escape, or by the focus
 * leaving it. The rail is followed the way the stylesheet follows it: from `breakpoint` up, on a
 * shell whose `data-sidebar` says `collapsed`. There `aria-expanded` says whether a group's popup
 * is open, so what the accordion had open is set aside for as long as the rail lasts, put back
 * when the sidebar widens, and remembered as the visitor left it — never as the popups did.
 */
export default class extends Controller {
    static targets = ['toggle'];

    static values = {
        storageKey: { type: String, default: 'adminata_sidebar_open' },
        breakpoint: { type: Number, default: 1024 },
    };

    initialize() {
        /**
         * What the accordion has open, per top-level group, while the rail is on, and `null` while
         * it is not: on the rail those buttons' `aria-expanded` belongs to their popups.
         *
         * @type {Map<HTMLElement, boolean> | null}
         */
        this.parked = null;

        /** @type {HTMLElement | null} the button whose popup is open */
        this.opened = null;

        this.onOutsideClick = (event) => {
            if (!this.holds(event.target)) {
                this.popup(null);
            }
        };

        this.onKeydown = (event) => {
            if ('Escape' !== event.key || null === this.opened) {
                return;
            }

            const toggle = this.opened;

            this.popup(null);
            toggle.focus();
        };

        this.onFocusOut = (event) => {
            // `null` is the focus going nowhere in particular — a click on something that cannot
            // take it, inside the popup as likely as not. A click outside closes it on its own.
            if (null !== event.relatedTarget && !this.holds(event.relatedTarget)) {
                this.popup(null);
            }
        };

        this.onReposition = () => this.place();
    }

    connect() {
        this.shell = this.element.closest('[data-sidebar]');
        this.query = window.matchMedia(`(min-width: ${this.breakpointValue}px)`);
        this.wide = this.query.matches;

        // `event.matches`, as in `adminata-layout`: the event carries the state it announces, and
        // reading it back off the list is a race with whatever else is resizing.
        this.onBreakpointChange = (event) => {
            this.wide = event.matches;
            this.sync();
        };

        this.query.addEventListener('change', this.onBreakpointChange);

        // The attribute the stylesheet reads, whoever writes it.
        this.observer = new MutationObserver(() => this.sync());

        if (null !== this.shell) {
            this.observer.observe(this.shell, { attributes: true, attributeFilter: ['data-sidebar'] });
        }

        this.restore();
        this.sync();
    }

    disconnect() {
        this.query.removeEventListener('change', this.onBreakpointChange);
        this.observer.disconnect();

        // The accordion's state back on the buttons, which is what the next `connect()` reads.
        this.leaveRail();

        // A panel left mid-animation would keep its inline height and its `overflow: hidden`.
        this.toggleTargets.forEach((toggle) => this.settle(this.panelFor(toggle)));
    }

    toggle(event) {
        const toggle = event.currentTarget;

        // A top-level group on the rail opens its popup, `keep-open` or not: that option is about
        // the accordion, which the rail sets aside.
        if (this.parked?.has(toggle)) {
            this.popup(toggle === this.opened ? null : toggle);

            return;
        }

        if (this.isPinned(toggle)) {
            return;
        }

        this.setExpanded(toggle, 'true' !== toggle.getAttribute('aria-expanded'), { animate: true });
        this.remember();
    }

    /**
     * Closes every group a visitor may close, and remembers that.
     *
     * A `keep-open` group stays open, as it does for `toggle()`. The group holding the current page
     * closes with the rest — it is what the visitor asked for — and the server opens it again on
     * the next page, which `restore()` leaves alone.
     */
    collapseAll() {
        this.toggleTargets.forEach((toggle) => {
            if (!this.isPinned(toggle)) {
                this.setOpen(toggle, false, { animate: true });
            }
        });

        this.remember();
    }

    /** Opens every group, and remembers that. */
    expandAll() {
        this.toggleTargets.forEach((toggle) => this.setOpen(toggle, true, { animate: true }));

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
            const expanded = this.isOpen(toggle);

            if (expanded || this.isPinned(toggle) || !(key in stored)) {
                this.setOpen(toggle, expanded || this.isPinned(toggle));

                return;
            }

            this.setOpen(toggle, stored[key]);
        });
    }

    /**
     * Follows the stylesheet into the rail and out of it: from the breakpoint up, on a shell whose
     * `data-sidebar` says `collapsed`.
     */
    sync() {
        if (this.wide && 'collapsed' === this.shell?.dataset.sidebar) {
            this.enterRail();
        } else {
            this.leaveRail();
        }
    }

    /**
     * Sets the accordion's state aside and hands the top-level buttons to their popups, all closed,
     * then marks the element — which is what lets the stylesheet show a popup at all.
     */
    enterRail() {
        if (null !== this.parked) {
            return;
        }

        this.parked = new Map();

        this.toggleTargets.forEach((toggle) => {
            if (!this.isTopLevel(toggle)) {
                return;
            }

            this.parked.set(toggle, 'true' === toggle.getAttribute('aria-expanded'));
            // A panel caught mid-slide carries an inline `display`, which would show it on the rail.
            this.settle(this.panelFor(toggle));
            toggle.setAttribute('aria-expanded', 'false');
        });

        this.element.dataset.adminataMenuRail = '';
    }

    /** Closes the popup and gives the top-level buttons back the accordion's state. */
    leaveRail() {
        if (null === this.parked) {
            return;
        }

        this.popup(null);
        this.parked.forEach((open, toggle) => toggle.setAttribute('aria-expanded', String(open)));
        this.parked = null;
        delete this.element.dataset.adminataMenuRail;
    }

    /**
     * Opens one group's popup and closes the one that was open, or — given `null` — only closes.
     *
     * @param {HTMLElement | null} toggle
     */
    popup(toggle) {
        if (toggle === this.opened) {
            return;
        }

        if (null !== this.opened) {
            this.opened.setAttribute('aria-expanded', 'false');
            this.unplace(this.panelFor(this.opened));
        }

        this.opened = toggle;

        if (null === toggle) {
            this.unlisten();

            return;
        }

        toggle.setAttribute('aria-expanded', 'true');
        this.place();
        this.listen();
    }

    /**
     * Puts the open popup beside the rail, its title level with the icon that opened it, moved up
     * only as far as it takes to end inside the window. The stylesheet draws it; this says where,
     * measured once `aria-expanded` has let the stylesheet show it — a hidden panel has no height.
     */
    place() {
        const panel = null === this.opened ? null : this.panelFor(this.opened);

        if (null === panel) {
            return;
        }

        const rail = (this.element.closest('aside') ?? this.element).getBoundingClientRect();
        const button = this.opened.getBoundingClientRect();
        const box = panel.getBoundingClientRect();
        const title = panel.firstElementChild?.getBoundingClientRect() ?? box;
        // Centre on centre: the title row is not as tall as a button holding only its icon.
        const level = button.top + button.height / 2 - (title.top - box.top + title.height / 2);
        const bottom = document.documentElement.clientHeight - POPUP_MARGIN;
        const top = Math.max(POPUP_MARGIN, Math.min(level, bottom - box.height));
        const rtl = 'rtl' === window.getComputedStyle(this.element).direction;

        panel.style.setProperty('--adm-menu-popup-top', `${top}px`);
        panel.style.setProperty(
            '--adm-menu-popup-start',
            `${rtl ? document.documentElement.clientWidth - rail.left : rail.right}px`,
        );
    }

    /** @param {HTMLElement | null} panel */
    unplace(panel) {
        panel?.style.removeProperty('--adm-menu-popup-top');
        panel?.style.removeProperty('--adm-menu-popup-start');
    }

    /**
     * What closes a popup, or moves it, listened for only while one is open. Adding a listener
     * twice is a no-op, so going from one popup to the next needs no bookkeeping.
     */
    listen() {
        document.addEventListener('click', this.onOutsideClick);
        document.addEventListener('keydown', this.onKeydown);
        this.element.addEventListener('focusout', this.onFocusOut);
        window.addEventListener('resize', this.onReposition);
        // Capturing, so the menu's own scrolling box is heard as well as the window.
        document.addEventListener('scroll', this.onReposition, { capture: true, passive: true });
    }

    unlisten() {
        document.removeEventListener('click', this.onOutsideClick);
        document.removeEventListener('keydown', this.onKeydown);
        this.element.removeEventListener('focusout', this.onFocusOut);
        window.removeEventListener('resize', this.onReposition);
        document.removeEventListener('scroll', this.onReposition, { capture: true });
    }

    /**
     * Whether a node is the open popup's button or inside the popup.
     *
     * @param {EventTarget | null} node
     */
    holds(node) {
        if (null === this.opened || !(node instanceof Node)) {
            return false;
        }

        return this.opened.contains(node) || true === this.panelFor(this.opened)?.contains(node);
    }

    /**
     * Whether a group is open in the accordion: for a top-level group on the rail, what was set
     * aside rather than what its button says.
     *
     * @param {HTMLElement} toggle
     */
    isOpen(toggle) {
        return this.parked?.get(toggle) ?? 'true' === toggle.getAttribute('aria-expanded');
    }

    /**
     * Opens or closes a group in the accordion. On the rail a top-level group's state is only
     * noted, for when the sidebar widens again.
     *
     * @param {HTMLElement} toggle
     * @param {boolean} open
     * @param {{animate?: boolean}} options
     */
    setOpen(toggle, open, options = {}) {
        if (this.parked?.has(toggle)) {
            this.parked.set(toggle, open);

            return;
        }

        this.setExpanded(toggle, open, options);
    }

    /**
     * A group straight under the menu's root, as opposed to one nested in another's panel — which
     * unfolds inside that panel, popup or not.
     *
     * @param {HTMLElement} toggle
     */
    isTopLevel(toggle) {
        return null === toggle.parentElement?.closest('.menu-dropdown');
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
            open[this.keyFor(toggle)] = this.isOpen(toggle);
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
