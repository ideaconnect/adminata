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

import { beforeEach, describe, expect, it } from 'vitest';

import MenuController from '../controllers/menu_controller.js';
import { mount, settle } from './helpers.js';

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

    it('ignores a stored value that is not a map', async () => {
        window.localStorage.setItem(STORAGE_KEY, '"nonsense"');

        const { element } = await mount('adminata-menu', MenuController, menu({ taxonomy: true }));

        expect(expanded(element)).toEqual(['false', 'true']);
    });
});
