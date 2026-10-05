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
import MenuFilterController from '../controllers/menu_filter_controller.js';
import { fixture, mount, settle } from './helpers.js';

const field = `
    <div class="adm-sidebar-filter" hidden
         data-controller="adminata-menu-filter"
         data-adminata-menu-filter-adminata-menu-outlet="nav[data-controller~='adminata-menu']"
         data-action="adminata-menu:filtered@window->adminata-menu-filter#sync">
        <label for="filter">Filter the menu</label>
        <input type="search" id="filter"
               data-adminata-menu-filter-target="input"
               data-action="input->adminata-menu-filter#filter keydown.esc->adminata-menu-filter#clear keydown.enter->adminata-menu-filter#follow keydown.down->adminata-menu-filter#focusFirst">
        <p role="status"><span hidden data-adminata-menu-filter-target="empty">No menu item matches.</span></p>
    </div>
`;

/** @param {{links?: boolean}} options */
const menu = ({ links = true } = {}) => `
    <nav data-controller="adminata-menu">
        <ul class="adm-menu sidebar-menu">
            <li>
                <button type="button" class="menu-item" aria-expanded="false"
                        data-adminata-menu-target="toggle" data-action="click->adminata-menu#toggle">
                    <span class="menu-item-text">Catalogue</span>
                </button>
                <ul class="menu-dropdown">
                    ${links ? '<li><a href="#products" class="menu-dropdown-item"><span class="menu-item-text">Products</span></a></li>' : ''}
                    ${links ? '<li><a href="#variants" class="menu-dropdown-item"><span class="menu-item-text">Product variants</span></a></li>' : ''}
                </ul>
            </li>
            ${links ? '<li><a href="#dashboard" class="menu-item"><span class="menu-item-text">Dashboard</span></a></li>' : ''}
        </ul>
    </nav>
`;

/**
 * Mounts the field, then the menu beside it — the order the outlet has to cope with, since the
 * layout renders the field first.
 *
 * @param {string} html
 */
const mountWithMenu = async (html) => {
    const mounted = await mount('adminata-menu-filter', MenuFilterController, html);

    mounted.application.register('adminata-menu', MenuController);
    await settle();

    return mounted;
};

const input = () => document.querySelector('[data-adminata-menu-filter-target="input"]');
const empty = () => document.querySelector('[data-adminata-menu-filter-target="empty"]');
const nav = () => document.querySelector('nav[data-controller~="adminata-menu"]');

/** @param {string} value */
const type = async (value) => {
    input().value = value;
    input().dispatchEvent(new Event('input', { bubbles: true }));
    await settle();
};

/** @param {string} key */
const press = (key) => {
    const event = new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true });

    input().dispatchEvent(event);

    return event;
};

const shownLinks = () =>
    [...nav().querySelectorAll('a[href]')]
        .filter((link) => null === link.closest('li[hidden]'))
        .map((link) => link.textContent.trim());

beforeEach(() => {
    window.localStorage.clear();
});

describe('adminata-menu-filter', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('stays hidden until a menu with a link in it connects', async () => {
        const { element } = await mount('adminata-menu-filter', MenuFilterController, field + menu());

        // The menu's controller is not registered yet: nothing would answer the field.
        expect(element.hidden).toBe(true);

        await mountWithMenu(field + menu());

        expect(document.querySelector('[data-controller~="adminata-menu-filter"]').hidden).toBe(false);
    });

    it('stays hidden beside a menu without a link', async () => {
        const { element } = await mountWithMenu(field + menu({ links: false }));

        expect(element.hidden).toBe(true);
    });

    it('hides itself when the menu goes', async () => {
        const { element } = await mountWithMenu(field + menu());

        nav().remove();
        await settle();

        expect(element.hidden).toBe(true);
    });

    it('narrows the menu as the field changes, and puts it back when the field is empty', async () => {
        await mountWithMenu(field + menu());

        await type('variant');
        expect(shownLinks()).toEqual(['Product variants']);

        await type('');
        expect(shownLinks()).toEqual(['Products', 'Product variants', 'Dashboard']);
    });

    it('says so when nothing matches, and only then', async () => {
        await mountWithMenu(field + menu());

        expect(empty().hidden).toBe(true);

        await type('zzz');
        expect(empty().hidden).toBe(false);
        // The status is always there, so that what appears in it is announced.
        expect(empty().parentElement.getAttribute('role')).toBe('status');
        expect(empty().parentElement.hidden).toBe(false);

        await type('prod');
        expect(empty().hidden).toBe(true);

        await type('zzz');
        await type('');
        expect(empty().hidden).toBe(true);
    });

    it('empties the field and the filter on Escape, and leaves an empty field alone', async () => {
        await mountWithMenu(field + menu());

        await type('dash');

        const escape = press('Escape');
        await settle();

        expect(escape.defaultPrevented).toBe(true);
        expect(input().value).toBe('');
        expect(shownLinks()).toHaveLength(3);

        expect(press('Escape').defaultPrevented).toBe(false);
    });

    it('follows the first link the narrowed menu shows on Enter', async () => {
        await mountWithMenu(field + menu());

        const followed = [];

        nav().addEventListener('click', (event) => {
            event.preventDefault();
            followed.push(event.target.closest('a').getAttribute('href'));
        });

        await type('prod');
        const enter = press('Enter');

        expect(enter.defaultPrevented).toBe(true);
        expect(followed).toEqual(['#products']);
    });

    it('opens nothing on Enter in an empty field, or when nothing matches', async () => {
        await mountWithMenu(field + menu());

        const click = vi.spyOn(HTMLAnchorElement.prototype, 'click');

        expect(press('Enter').defaultPrevented).toBe(false);

        await type('zzz');
        expect(press('Enter').defaultPrevented).toBe(false);
        expect(click).not.toHaveBeenCalled();
    });

    it('moves the focus to the first link the narrowed menu shows on the down arrow', async () => {
        await mountWithMenu(field + menu());

        await type('variant');
        input().focus();

        const down = press('ArrowDown');

        expect(down.defaultPrevented).toBe(true);
        expect(document.activeElement).toBe(nav().querySelector('a[href="#variants"]'));
    });

    it('empties the field when the menu ends the filter on its own', async () => {
        const { application } = await mountWithMenu(field + menu());
        const controller = application.getControllerForElementAndIdentifier(nav(), 'adminata-menu');

        await type('prod');
        // What the menu does when the sidebar collapses into the rail.
        controller.filter('');
        await settle();

        expect(input().value).toBe('');
        expect(empty().hidden).toBe(true);
    });

    it('ignores what another menu answers', async () => {
        await mountWithMenu(field + menu() + '<nav id="other"></nav>');

        await type('prod');
        document.getElementById('other').dispatchEvent(
            new CustomEvent('adminata-menu:filtered', {
                bubbles: true,
                detail: { query: '', active: false, links: 0 },
            }),
        );
        await settle();

        expect(input().value).toBe('prod');
    });

    it("is wired to the menu in the demo's own sidebar", async () => {
        const sidebar = fixture('dashboard', 'aside.adm-sidebar');

        await mountWithMenu(sidebar);

        const row = document.querySelector('[data-controller~="adminata-menu-filter"]');

        expect(row.hidden).toBe(false);

        await type('tag');
        expect(shownLinks()).toEqual(['Tags']);

        await type('zzz');
        expect(empty().hidden).toBe(false);
    });
});
