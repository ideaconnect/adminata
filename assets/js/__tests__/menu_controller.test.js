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

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import MenuController from '../controllers/menu_controller.js';
import { mount, mountFixture, settle } from './helpers.js';

const STORAGE_KEY = 'adminata_sidebar_open';

/**
 * @param {{catalogue?: boolean, taxonomy?: boolean, pinned?: boolean}} state
 */
const menu = ({ catalogue = false, taxonomy = false, pinned = false } = {}) => `
    <nav data-controller="adminata-menu">
        <ul class="sidebar-menu">
            <li>
                <button type="button" class="menu-item" aria-expanded="${catalogue}"
                        ${pinned ? 'data-adminata-menu-keep-open="true"' : ''}
                        data-adminata-menu-target="toggle" data-action="click->adminata-menu#toggle">
                    <span class="menu-item-text">Catalogue</span>
                </button>
                <ul class="menu-dropdown"><li><a href="/products">Products</a></li></ul>
            </li>
            <li>
                <button type="button" class="menu-item" aria-expanded="${taxonomy}"
                        data-adminata-menu-target="toggle" data-action="click->adminata-menu#toggle">
                    <span class="menu-item-text">Taxonomy</span>
                </button>
                <ul class="menu-dropdown"><li><a href="/categories">Categories</a></li></ul>
            </li>
        </ul>
    </nav>
`;

const buttons = (element) => [...element.querySelectorAll('[data-adminata-menu-target="toggle"]')];
const expanded = (element) => buttons(element).map((button) => button.getAttribute('aria-expanded'));

beforeEach(() => {
    window.localStorage.clear();
});

describe('adminata-menu', () => {
    it('leaves what the server rendered alone when nothing is stored', async () => {
        const { element } = await mount('adminata-menu', MenuController, menu({ taxonomy: true }));

        expect(expanded(element)).toEqual(['false', 'true']);
    });

    it('opens and closes a group, and remembers both', async () => {
        const { element } = await mount('adminata-menu', MenuController, menu());

        buttons(element)[0].click();
        await settle();

        expect(expanded(element)).toEqual(['true', 'false']);
        expect(JSON.parse(window.localStorage.getItem(STORAGE_KEY))).toEqual({
            Catalogue: true,
            Taxonomy: false,
        });

        buttons(element)[0].click();
        await settle();

        expect(expanded(element)).toEqual(['false', 'false']);
    });

    it('lets several groups stay open at once', async () => {
        const { element } = await mount('adminata-menu', MenuController, menu());

        buttons(element)[0].click();
        buttons(element)[1].click();
        await settle();

        expect(expanded(element)).toEqual(['true', 'true']);
    });

    it('applies what was stored on the next page', async () => {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ Catalogue: true, Taxonomy: false }));

        const { element } = await mount('adminata-menu', MenuController, menu());

        expect(expanded(element)).toEqual(['true', 'false']);
    });

    it('keeps the group holding the current page open whatever was stored', async () => {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ Catalogue: false }));

        const { element } = await mount('adminata-menu', MenuController, menu({ catalogue: true }));

        expect(expanded(element)).toEqual(['true', 'false']);
    });

    it('will not close a group the server pinned', async () => {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ Catalogue: false }));

        const { element } = await mount('adminata-menu', MenuController, menu({ pinned: true }));

        expect(expanded(element)).toEqual(['true', 'false']);

        buttons(element)[0].click();
        await settle();

        expect(expanded(element)).toEqual(['true', 'false']);
    });

    /*
     * jsdom has no layout, so `scrollHeight` is 0 and no transition ever runs. What can be checked
     * here is the contract the animation has with the rest of the controller: that a click leaves
     * the panel with nothing inline on it, and that restoring on connect does not animate — a
     * sidebar that unfolds on every page load would be the obvious way to get this wrong.
     */
    it('leaves no inline styles on the panel after a click', async () => {
        const { element } = await mount('adminata-menu', MenuController, menu());
        const panel = element.querySelector('.menu-dropdown');

        buttons(element)[0].click();
        await settle();

        expect(expanded(element)[0]).toBe('true');
        expect(panel.getAttribute('style')).toBeFalsy();
        expect(panel.dataset.sliding).toBeUndefined();

        buttons(element)[0].click();
        await settle();

        expect(expanded(element)[0]).toBe('false');
        expect(panel.getAttribute('style')).toBeFalsy();
        expect(panel.dataset.sliding).toBeUndefined();
    });

    it('does not animate the groups it restores on connect', async () => {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ Catalogue: true }));

        const { element } = await mount('adminata-menu', MenuController, menu());
        const panel = element.querySelector('.menu-dropdown');

        expect(expanded(element)).toEqual(['true', 'false']);
        expect(panel.dataset.sliding).toBeUndefined();
        expect(panel.getAttribute('style')).toBeFalsy();
    });

    it('collapses every group but a pinned one, and remembers that', async () => {
        const { application, element } = await mount(
            'adminata-menu',
            MenuController,
            menu({ catalogue: true, taxonomy: true, pinned: true }),
        );

        application.getControllerForElementAndIdentifier(element, 'adminata-menu').collapseAll();
        await settle();

        expect(expanded(element)).toEqual(['true', 'false']);
        expect(JSON.parse(window.localStorage.getItem(STORAGE_KEY))).toEqual({
            Catalogue: true,
            Taxonomy: false,
        });
    });

    it('expands every group, remembers that, and leaves no inline styles behind', async () => {
        const { application, element } = await mount('adminata-menu', MenuController, menu());

        application.getControllerForElementAndIdentifier(element, 'adminata-menu').expandAll();
        await settle();

        expect(expanded(element)).toEqual(['true', 'true']);
        expect(JSON.parse(window.localStorage.getItem(STORAGE_KEY))).toEqual({
            Catalogue: true,
            Taxonomy: true,
        });

        for (const panel of element.querySelectorAll('.menu-dropdown')) {
            expect(panel.getAttribute('style')).toBeFalsy();
            expect(panel.dataset.sliding).toBeUndefined();
        }
    });

    it('ignores a stored value that is not a map', async () => {
        window.localStorage.setItem(STORAGE_KEY, '"nonsense"');

        const { element } = await mount('adminata-menu', MenuController, menu({ taxonomy: true }));

        expect(expanded(element)).toEqual(['false', 'true']);
    });
});

/*
 * The collapsed rail. jsdom has no layout, so `matchMedia` is driven here — `viewport.wide()` puts
 * the window above the breakpoint and `viewport.cross()` fires the change a resize would — and the
 * shell is `<body>`, whose `data-sidebar` the tests set the way `adminata-layout` does.
 */
const viewport = {
    matches: true,
    listeners: [],

    wide() {
        this.set(true);
    },

    narrow() {
        this.set(false);
    },

    set(matches) {
        this.matches = matches;
        this.listeners = [];

        vi.stubGlobal('matchMedia', (query) => ({
            matches: this.matches,
            media: query,
            addEventListener: (_, listener) => this.listeners.push(listener),
            removeEventListener: (_, listener) => {
                this.listeners = this.listeners.filter((registered) => registered !== listener);
            },
        }));
    },

    cross(matches) {
        this.matches = matches;
        this.listeners.forEach((listener) => listener({ matches }));
    },
};

/**
 * A menu inside a sidebar, with the markup the template renders for the rail — each top-level panel
 * opens with its title — a group nested in Catalogue's panel, and something outside to click and
 * to focus.
 *
 * @param {{catalogue?: boolean, taxonomy?: boolean, archive?: boolean, pinned?: boolean}} state
 */
const railMenu = ({ catalogue = false, taxonomy = false, archive = false, pinned = false } = {}) => `
    <aside>
        <nav data-controller="adminata-menu">
            <ul class="adm-menu sidebar-menu">
                <li>
                    <button type="button" class="menu-item" aria-expanded="${catalogue}"
                            ${pinned ? 'data-adminata-menu-keep-open="true"' : ''}
                            data-adminata-menu-target="toggle" data-action="click->adminata-menu#toggle">
                        <span class="menu-item-text">Catalogue</span>
                    </button>
                    <ul class="menu-dropdown menu_level_1">
                        <li class="adm-menu-popup-title" aria-hidden="true">Catalogue</li>
                        <li><a href="#products" id="products">Products</a></li>
                        <li>
                            <button type="button" class="menu-item" aria-expanded="${archive}"
                                    data-adminata-menu-target="toggle" data-action="click->adminata-menu#toggle">
                                <span class="menu-item-text">Archive</span>
                            </button>
                            <ul class="menu-dropdown menu_level_2"><li><a href="#old">Old products</a></li></ul>
                        </li>
                    </ul>
                </li>
                <li>
                    <button type="button" class="menu-item" aria-expanded="${taxonomy}"
                            data-adminata-menu-target="toggle" data-action="click->adminata-menu#toggle">
                        <span class="menu-item-text">Taxonomy</span>
                    </button>
                    <ul class="menu-dropdown menu_level_1">
                        <li class="adm-menu-popup-title" aria-hidden="true">Taxonomy</li>
                        <li><a href="#categories">Categories</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </aside>
    <button type="button" id="elsewhere">Elsewhere</button>
`;

/** Every group's `aria-expanded`, by its label. */
const groups = (element) =>
    Object.fromEntries(
        buttons(element).map((button) => [
            button.querySelector('.menu-item-text').textContent.trim(),
            button.getAttribute('aria-expanded'),
        ]),
    );

const group = (element, label) =>
    buttons(element).find((button) => label === button.querySelector('.menu-item-text').textContent.trim());

const panelOf = (element, label) => group(element, label).nextElementSibling;

const stored = () => JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? 'null');

/** @returns {Promise<{application: import('@hotwired/stimulus').Application, element: HTMLElement}>} */
const onTheRail = (state = {}) => {
    document.body.dataset.sidebar = 'collapsed';
    viewport.wide();

    return mount('adminata-menu', MenuController, railMenu(state));
};

describe('adminata-menu on the collapsed rail', () => {
    afterEach(() => {
        delete document.body.dataset.sidebar;
        vi.unstubAllGlobals();
    });

    it('hands the top-level groups to their popups, all closed, and marks itself', async () => {
        const { element } = await onTheRail({ catalogue: true, archive: true });

        // The server opened Catalogue for the accordion; on the rail no popup is open yet. The
        // group nested inside a panel is not the rail's, and keeps what it had.
        expect(groups(element)).toEqual({ Catalogue: 'false', Archive: 'true', Taxonomy: 'false' });
        expect(element.hasAttribute('data-adminata-menu-rail')).toBe(true);
    });

    it('opens one popup at a time, and remembers none of them', async () => {
        const { element } = await onTheRail();

        group(element, 'Catalogue').click();
        await settle();

        expect(groups(element)).toMatchObject({ Catalogue: 'true', Taxonomy: 'false' });
        expect(panelOf(element, 'Catalogue').style.getPropertyValue('--adm-menu-popup-top')).not.toBe('');
        expect(panelOf(element, 'Catalogue').style.getPropertyValue('--adm-menu-popup-start')).not.toBe('');

        group(element, 'Taxonomy').click();
        await settle();

        expect(groups(element)).toMatchObject({ Catalogue: 'false', Taxonomy: 'true' });
        expect(panelOf(element, 'Catalogue').getAttribute('style')).toBeFalsy();

        group(element, 'Taxonomy').click();
        await settle();

        expect(groups(element)).toMatchObject({ Catalogue: 'false', Taxonomy: 'false' });
        expect(panelOf(element, 'Taxonomy').getAttribute('style')).toBeFalsy();
        expect(stored()).toBeNull();
    });

    it('closes the popup on a click outside it, and not on one inside it', async () => {
        const { element } = await onTheRail();

        group(element, 'Catalogue').click();
        await settle();

        panelOf(element, 'Catalogue').querySelector('.adm-menu-popup-title').click();
        await settle();

        expect(groups(element).Catalogue).toBe('true');

        document.getElementById('elsewhere').click();
        await settle();

        expect(groups(element).Catalogue).toBe('false');
        expect(panelOf(element, 'Catalogue').getAttribute('style')).toBeFalsy();
    });

    it('closes the popup on Escape and gives its button the focus', async () => {
        const { element } = await onTheRail();

        group(element, 'Catalogue').click();
        await settle();
        document.getElementById('products').focus();

        document
            .getElementById('products')
            .dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await settle();

        expect(groups(element).Catalogue).toBe('false');
        expect(document.activeElement).toBe(group(element, 'Catalogue'));
    });

    it('closes the popup when the focus leaves it, and not while the focus moves inside it', async () => {
        const { element } = await onTheRail();

        group(element, 'Catalogue').focus();
        group(element, 'Catalogue').click();
        await settle();

        document.getElementById('products').focus();
        await settle();

        expect(groups(element).Catalogue).toBe('true');

        document.getElementById('elsewhere').focus();
        await settle();

        expect(groups(element).Catalogue).toBe('false');
    });

    it('unfolds a group nested in a popup in place, and remembers it', async () => {
        const { element } = await onTheRail({ catalogue: true });

        group(element, 'Catalogue').click();
        group(element, 'Archive').click();
        await settle();

        expect(groups(element)).toEqual({ Catalogue: 'true', Archive: 'true', Taxonomy: 'false' });
        // Catalogue as the accordion had it — open, from the server — not as its popup is.
        expect(stored()).toEqual({ Catalogue: true, Archive: true, Taxonomy: false });
    });

    it('opens a keep-open group as a popup like any other', async () => {
        const { element } = await onTheRail({ catalogue: true, pinned: true });

        group(element, 'Catalogue').click();
        await settle();

        expect(groups(element).Catalogue).toBe('true');

        group(element, 'Catalogue').click();
        await settle();

        expect(groups(element).Catalogue).toBe('false');
    });

    it('gives the accordion back, and closes the popup, when the sidebar widens', async () => {
        const { element } = await onTheRail({ catalogue: true });

        group(element, 'Taxonomy').click();
        await settle();

        document.body.dataset.sidebar = 'expanded';
        await settle();

        expect(groups(element)).toEqual({ Catalogue: 'true', Archive: 'false', Taxonomy: 'false' });
        expect(element.hasAttribute('data-adminata-menu-rail')).toBe(false);
        expect(panelOf(element, 'Taxonomy').getAttribute('style')).toBeFalsy();

        // Nothing is listening any more: a click elsewhere changes nothing.
        document.getElementById('elsewhere').click();
        await settle();

        expect(groups(element)).toEqual({ Catalogue: 'true', Archive: 'false', Taxonomy: 'false' });
    });

    it('leaves the rail when the window narrows past the breakpoint, and takes it back when it widens', async () => {
        const { element } = await onTheRail({ taxonomy: true });

        group(element, 'Catalogue').click();
        await settle();

        viewport.cross(false);

        expect(groups(element)).toEqual({ Catalogue: 'false', Archive: 'false', Taxonomy: 'true' });
        expect(element.hasAttribute('data-adminata-menu-rail')).toBe(false);

        viewport.cross(true);

        expect(groups(element)).toEqual({ Catalogue: 'false', Archive: 'false', Taxonomy: 'false' });
        expect(element.hasAttribute('data-adminata-menu-rail')).toBe(true);
    });

    it('notes collapse all and expand all for the accordion, not for the popups', async () => {
        const { application, element } = await onTheRail({ catalogue: true });
        const controller = application.getControllerForElementAndIdentifier(element, 'adminata-menu');

        controller.collapseAll();
        await settle();

        expect(groups(element)).toEqual({ Catalogue: 'false', Archive: 'false', Taxonomy: 'false' });
        expect(stored()).toEqual({ Catalogue: false, Archive: false, Taxonomy: false });

        controller.expandAll();
        await settle();

        // The popups are still closed; the accordion is open all the way, and says so on the
        // buttons as soon as the sidebar is wide.
        expect(groups(element)).toEqual({ Catalogue: 'false', Archive: 'true', Taxonomy: 'false' });
        expect(stored()).toEqual({ Catalogue: true, Archive: true, Taxonomy: true });

        document.body.dataset.sidebar = 'expanded';
        await settle();

        expect(groups(element)).toEqual({ Catalogue: 'true', Archive: 'true', Taxonomy: 'true' });
    });

    it('puts the accordion back on the buttons when it disconnects', async () => {
        const { element } = await onTheRail({ catalogue: true });
        const catalogue = group(element, 'Catalogue');

        catalogue.click();
        await settle();

        element.remove();
        await settle();

        expect(catalogue.getAttribute('aria-expanded')).toBe('true');
        expect(catalogue.nextElementSibling.getAttribute('style')).toBeFalsy();
        expect(element.hasAttribute('data-adminata-menu-rail')).toBe(false);
    });

    it('is an accordion below the breakpoint, collapsed or not', async () => {
        document.body.dataset.sidebar = 'collapsed';
        viewport.narrow();

        const { element } = await mount('adminata-menu', MenuController, railMenu());

        group(element, 'Catalogue').click();
        await settle();

        expect(element.hasAttribute('data-adminata-menu-rail')).toBe(false);
        expect(stored()).toEqual({ Catalogue: true, Archive: false, Taxonomy: false });
    });

    it('is an accordion outside a shell', async () => {
        viewport.wide();

        const { element } = await mount('adminata-menu', MenuController, railMenu());

        group(element, 'Catalogue').click();
        await settle();

        expect(element.hasAttribute('data-adminata-menu-rail')).toBe(false);
        expect(stored()).toEqual({ Catalogue: true, Archive: false, Taxonomy: false });
    });

    /*
     * jsdom has no layout, so the boxes are stubbed: the rail 90px wide, the button at 100px, the
     * popup 200px tall with its title 9px down its side — its border and padding — and 36px tall.
     */
    it('puts the popup beside the rail, its title level with the button, inside the window', async () => {
        const { element } = await onTheRail();
        const catalogue = group(element, 'Catalogue');
        const panel = catalogue.nextElementSibling;
        const box = (left, top, width, height) => () => ({
            left,
            top,
            width,
            height,
            right: left + width,
            bottom: top + height,
        });

        element.closest('aside').getBoundingClientRect = box(0, 0, 90, 900);
        catalogue.getBoundingClientRect = box(20, 100, 50, 30);
        panel.getBoundingClientRect = box(98, 0, 192, 200);
        panel.firstElementChild.getBoundingClientRect = box(99, 9, 190, 36);
        vi.spyOn(document.documentElement, 'clientHeight', 'get').mockReturnValue(900);

        catalogue.click();
        await settle();

        // 100 + 30 / 2 − (9 + 36 / 2): the title's middle on the button's.
        expect(panel.style.getPropertyValue('--adm-menu-popup-top')).toBe('88px');
        expect(panel.style.getPropertyValue('--adm-menu-popup-start')).toBe('90px');

        catalogue.click();
        vi.spyOn(document.documentElement, 'clientHeight', 'get').mockReturnValue(250);
        catalogue.click();
        await settle();

        // A window 250px tall: the popup ends 8px above its bottom edge instead.
        expect(panel.style.getPropertyValue('--adm-menu-popup-top')).toBe('42px');
    });

    it("opens a popup headed by the group's name on the demo's own menu", async () => {
        document.body.dataset.sidebar = 'collapsed';
        viewport.wide();

        const { element } = await mountFixture(
            'adminata-menu',
            MenuController,
            'dashboard',
            'nav[data-controller~="adminata-menu"]',
        );
        const [first] = buttons(element);

        first.click();
        await settle();

        const title = first.nextElementSibling.firstElementChild;

        expect(first.getAttribute('aria-expanded')).toBe('true');
        expect(title.classList.contains('adm-menu-popup-title')).toBe(true);
        expect(title.getAttribute('aria-hidden')).toBe('true');
        expect(title.textContent.trim()).toBe(first.querySelector('.menu-item-text').textContent.trim());
    });
});
