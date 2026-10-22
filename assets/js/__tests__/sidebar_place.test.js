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

import { readFileSync } from 'node:fs';

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import MenuController from '../controllers/menu_controller.js';
import { mount } from './helpers.js';

/**
 * The inline script in `adminata_sidebar_place` (standard_layout.html.twig), run as the demo
 * renders it: the fixtures are the demo's own pages (`make js-fixtures`), so a change to the
 * template that the fixtures do not follow fails `JsFixtureDumperTest`, and a change that breaks
 * the script fails here.
 *
 * jsdom has no layout, so the menu's box is given one: `height` pixels tall, holding `content`
 * pixels of menu, at 100 px from the top of the window, its `scrollTop` clamped as a browser
 * clamps it; an entry is placed at an offset inside the menu.
 */

const BOX_TOP = 100;

/** Listeners the script adds to `window` and `document`, taken off again after each test. */
const added = [];

beforeEach(() => {
    window.sessionStorage.clear();
    window.localStorage.clear();

    for (const target of [window, document]) {
        const original = target.addEventListener.bind(target);

        vi.spyOn(target, 'addEventListener').mockImplementation((type, listener, options) => {
            added.push([target, type, listener, options]);
            original(type, listener, options);
        });
    }
});

afterEach(() => {
    added
        .splice(0)
        .forEach(([target, type, listener, options]) => target.removeEventListener(type, listener, options));
    document.body.innerHTML = '';
    [...document.body.attributes].forEach(({ name }) => document.body.removeAttribute(name));
});

/**
 * Puts a demo page's body into the document — its scripts are not run by `innerHTML` — and
 * gives the menu's box a layout.
 */
function stage(page, { height = 300, content = 1000 } = {}) {
    const source = new DOMParser().parseFromString(
        readFileSync(`tests-adminata/fixtures/js/${page}.html`, 'utf8'),
        'text/html',
    );

    for (const { name, value } of [...source.body.attributes]) {
        document.body.setAttribute(name, value);
    }

    document.body.innerHTML = source.body.innerHTML;

    const box = document.querySelector('.adm-sidebar-nav');
    let top = 0;
    let size = content;

    Object.defineProperty(box, 'scrollTop', {
        configurable: true,
        get: () => top,
        set: (value) => {
            top = Math.max(0, Math.min(size - height, value));
        },
    });
    Object.defineProperty(box, 'scrollHeight', { configurable: true, get: () => size });
    Object.defineProperty(box, 'clientHeight', { configurable: true, get: () => height });
    box.getBoundingClientRect = () => ({ top: BOX_TOP, bottom: BOX_TOP + height });

    return {
        box,
        menu: box.querySelector('nav[data-controller~="adminata-menu"]'),
        script: document.querySelector('script[data-adminata-sidebar-place]'),
        grow: (pixels) => {
            size += pixels;
        },
    };
}

/** Lays an entry out `offset` pixels down the menu, `height` tall, and visible. */
function place(box, item, offset, height = 40) {
    item.getClientRects = () => [{}];
    item.getBoundingClientRect = () => ({
        top: BOX_TOP + offset - box.scrollTop,
        bottom: BOX_TOP + offset + height - box.scrollTop,
    });
}

/** Runs the script the way the browser does, with itself as `document.currentScript`. */
function run(script) {
    Object.defineProperty(document, 'currentScript', { configurable: true, get: () => script });

    try {
        // The template's own text, read from the fixture.
        new Function(script.textContent)();
    } finally {
        delete document.currentScript;
    }
}

function wide(matches) {
    vi.spyOn(window, 'matchMedia').mockImplementation((query) => ({
        matches,
        media: query,
        addEventListener: () => {},
        removeEventListener: () => {},
    }));
}

describe('the sidebar place script', () => {
    it('is rendered inline, after the menu in its box, under the script attributes', () => {
        const { box, script } = stage('dashboard');

        expect(script).not.toBeNull();
        expect(script.hasAttribute('src')).toBe(false);
        expect(script.parentElement).toBe(box);
        expect(
            box.querySelector('nav[data-controller~="adminata-menu"] ~ script[data-adminata-sidebar-place]'),
        ).toBe(script);
    });

    it('defaults to the store and the breakpoint adminata-menu defaults to', () => {
        const { menu, script } = stage('dashboard');

        // The demo renders the menu without either value, so both sides fall back to defaults.
        expect(menu.dataset.adminataMenuStorageKeyValue).toBeUndefined();
        expect(menu.dataset.adminataMenuBreakpointValue).toBeUndefined();
        expect(script.textContent).toContain(`|| '${MenuController.values.storageKey.default}'`);
        expect(script.textContent).toContain(`|| ${MenuController.values.breakpoint.default})`);
    });

    it('puts the menu back where the last page of the tab left it', () => {
        window.sessionStorage.setItem('adminata_sidebar_scroll:menu', '240');
        const { box, menu, script } = stage('dashboard');
        place(box, menu.querySelector('.menu-item-active'), 300);

        run(script);

        expect(box.scrollTop).toBe(240);
    });

    it('then scrolls by the least distance that shows the current entry', () => {
        window.sessionStorage.setItem('adminata_sidebar_scroll:menu', '0');
        const { box, menu, script } = stage('dashboard');
        // 600-640 px down a 300 px box: shown at its bottom edge, 16 px in.
        place(box, menu.querySelector('.menu-item-active'), 600);

        run(script);

        expect(box.scrollTop).toBe(640 - (300 - 16));
    });

    it('scrolls up when the current entry is above the stored place', () => {
        window.sessionStorage.setItem('adminata_sidebar_scroll:menu', '500');
        const { box, menu, script } = stage('dashboard');
        place(box, menu.querySelector('.menu-item-active'), 200);

        run(script);

        expect(box.scrollTop).toBe(200 - 16);
    });

    it('only brings the current entry into view when nothing is stored', () => {
        const { box, menu, script } = stage('dashboard');
        place(box, menu.querySelector('.menu-item-active'), 100);

        run(script);

        expect(box.scrollTop).toBe(0);
    });

    it('brings the current entry inside an open group into view', () => {
        const { box, menu, script } = stage('product-list');
        const current = menu.querySelector('.menu-dropdown-item-active');

        expect(current).not.toBeNull();
        place(box, current, 700);

        run(script);

        expect(box.scrollTop).toBe(740 - (300 - 16));
    });

    it('opens the groups the visitor keeps open, as adminata-menu restores them', async () => {
        const { menu, script } = stage('dashboard');
        const toggles = [...menu.querySelectorAll('[data-adminata-menu-target~="toggle"]')];
        const names = toggles.map((toggle) => toggle.textContent.trim());

        expect(toggles.every((toggle) => 'false' === toggle.getAttribute('aria-expanded'))).toBe(true);
        window.localStorage.setItem(
            MenuController.values.storageKey.default,
            JSON.stringify({ [names[0]]: true, [names[1]]: false }),
        );

        run(script);

        const byScript = toggles.map((toggle) => toggle.getAttribute('aria-expanded'));
        expect(byScript).toEqual(['true', 'false']);

        // The same store, the same page, and the controller instead of the script.
        const fresh = new DOMParser().parseFromString(
            readFileSync('tests-adminata/fixtures/js/dashboard.html', 'utf8'),
            'text/html',
        );
        const { element } = await mount(
            'adminata-menu',
            MenuController,
            fresh.querySelector('nav[data-controller~="adminata-menu"]').outerHTML,
        );

        expect(
            [...element.querySelectorAll('[data-adminata-menu-target~="toggle"]')].map((toggle) =>
                toggle.getAttribute('aria-expanded'),
            ),
        ).toEqual(byScript);
    });

    it("leaves a group the server rendered open, here the current page's, open whatever the store says", () => {
        const { menu, script } = stage('product-list');
        const open = menu.querySelector('[data-adminata-menu-target~="toggle"][aria-expanded="true"]');
        const name = open.textContent.trim();
        window.localStorage.setItem('adminata_sidebar_open', JSON.stringify({ [name]: false }));

        run(script);

        expect(open.getAttribute('aria-expanded')).toBe('true');
    });

    it('survives a store someone else wrote', () => {
        window.localStorage.setItem('adminata_sidebar_open', 'not json');
        const { script } = stage('dashboard');

        expect(() => run(script)).not.toThrow();
    });

    it('shows the filter field before the paint when the menu has a link', () => {
        const { box, script } = stage('dashboard');
        const filter = box.parentElement.querySelector(':scope > .adm-sidebar-filter');

        expect(filter.hidden).toBe(true);

        run(script);

        expect(filter.hidden).toBe(false);
    });

    it('keeps the filter field hidden beside a menu without a link', () => {
        const { box, menu, script } = stage('dashboard');
        menu.querySelectorAll('a[href]').forEach((link) => link.removeAttribute('href'));

        run(script);

        expect(box.parentElement.querySelector(':scope > .adm-sidebar-filter').hidden).toBe(true);
    });

    it('writes the place when the page goes away, per tab and per layout', () => {
        const first = stage('dashboard');
        run(first.script);
        first.box.scrollTop = 321;

        window.dispatchEvent(new Event('pagehide'));

        expect(window.sessionStorage.getItem('adminata_sidebar_scroll:menu')).toBe('321');
        expect(window.sessionStorage.getItem('adminata_sidebar_scroll:rail')).toBeNull();
    });

    it("keeps the collapsed rail's place apart from the full menu's", () => {
        wide(true);
        window.sessionStorage.setItem('adminata_sidebar_scroll:menu', '400');
        window.sessionStorage.setItem('adminata_sidebar_scroll:rail', '120');
        const { box, script } = stage('dashboard');
        document.body.dataset.sidebar = 'collapsed';

        run(script);

        expect(box.scrollTop).toBe(120);

        box.scrollTop = 150;
        window.dispatchEvent(new Event('pagehide'));

        expect(window.sessionStorage.getItem('adminata_sidebar_scroll:rail')).toBe('150');
        expect(window.sessionStorage.getItem('adminata_sidebar_scroll:menu')).toBe('400');
    });

    it('treats a collapsed sidebar below the breakpoint, the drawer, as the full menu', () => {
        wide(false);
        window.sessionStorage.setItem('adminata_sidebar_scroll:menu', '400');
        const { box, script } = stage('dashboard');
        document.body.dataset.sidebar = 'collapsed';

        run(script);

        expect(box.scrollTop).toBe(400);
    });

    it('places the menu once more when the box changed size before DOMContentLoaded', () => {
        window.sessionStorage.setItem('adminata_sidebar_scroll:menu', '0');
        const { box, menu, script, grow } = stage('dashboard');
        const current = menu.querySelector('.menu-item-active');
        place(box, current, 100);

        run(script);
        expect(box.scrollTop).toBe(0);

        // Something above the entry grew by 500 px after the script ran.
        grow(500);
        place(box, current, 600);
        document.dispatchEvent(new Event('DOMContentLoaded'));

        expect(box.scrollTop).toBe(640 - (300 - 16));
    });

    it('leaves the menu alone on DOMContentLoaded when the box kept its size', () => {
        const { box, menu, script } = stage('dashboard');
        const current = menu.querySelector('.menu-item-active');
        place(box, current, 100);

        run(script);
        box.scrollTop = 50;
        document.dispatchEvent(new Event('DOMContentLoaded'));

        expect(box.scrollTop).toBe(50);
    });

    it('starts at the top, as before, with storage switched off', () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new DOMException('denied', 'SecurityError');
        });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new DOMException('denied', 'SecurityError');
        });
        const { box, menu, script } = stage('dashboard');
        place(box, menu.querySelector('.menu-item-active'), 100);

        expect(() => run(script)).not.toThrow();
        expect(box.scrollTop).toBe(0);
        expect(() => window.dispatchEvent(new Event('pagehide'))).not.toThrow();
    });

    it('does nothing in a box that holds no adminata menu', () => {
        window.sessionStorage.setItem('adminata_sidebar_scroll:menu', '240');
        const { box, menu, script } = stage('dashboard');
        menu.remove();

        expect(() => run(script)).not.toThrow();
        expect(box.scrollTop).toBe(0);
        expect(box.parentElement.querySelector(':scope > .adm-sidebar-filter').hidden).toBe(true);
    });
});
