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
import SidebarToolbarController from '../controllers/sidebar_toolbar_controller.js';
import { mount, settle } from './helpers.js';

const STORAGE_KEY = 'adminata_sidebar_open';

const toolbar = `
    <div class="adm-sidebar-toolbar" role="group"
         data-controller="adminata-sidebar-toolbar"
         data-adminata-sidebar-toolbar-adminata-menu-outlet="nav[data-controller~='adminata-menu']">
        <a href="/admin/dashboard">Home</a>
        <button type="button" hidden
                data-adminata-sidebar-toolbar-target="collapse"
                data-action="click->adminata-sidebar-toolbar#collapseAll">Collapse all sections</button>
        <button type="button" hidden
                data-adminata-sidebar-toolbar-target="expand"
                data-action="click->adminata-sidebar-toolbar#expandAll">Expand all sections</button>
        <a href="/logout">Log out</a>
    </div>
`;

/**
 * @param {string[]} groups
 */
const menu = (groups = ['Catalogue', 'Taxonomy']) => `
    <nav data-controller="adminata-menu">
        <ul class="sidebar-menu">
            ${groups
                .map(
                    (label) => `
                <li>
                    <button type="button" class="menu-item" aria-expanded="false"
                            data-adminata-menu-target="toggle" data-action="click->adminata-menu#toggle">
                        <span class="menu-item-text">${label}</span>
                    </button>
                    <ul class="menu-dropdown"><li><a href="/${label}">${label}</a></li></ul>
                </li>`,
                )
                .join('')}
            <li><a href="/admin/dashboard">Dashboard</a></li>
        </ul>
    </nav>
`;

const [collapse, expand] = [0, 1];
const commands = (element) => [...element.querySelectorAll('button')];
const hidden = (element) => commands(element).map((button) => button.hidden);
const expanded = () =>
    [...document.querySelectorAll('[data-adminata-menu-target="toggle"]')].map((button) =>
        button.getAttribute('aria-expanded'),
    );

/**
 * Mounts the toolbar, then the menu beside it — the order the outlet has to cope with, since the
 * layout renders the toolbar first.
 *
 * @param {string} html
 */
const mountWithMenu = async (html) => {
    const mounted = await mount('adminata-sidebar-toolbar', SidebarToolbarController, html);

    mounted.application.register('adminata-menu', MenuController);
    await settle();

    return mounted;
};

beforeEach(() => {
    window.localStorage.clear();
});

describe('adminata-sidebar-toolbar', () => {
    it('keeps the two menu buttons hidden while there is no menu', async () => {
        const { element } = await mount('adminata-sidebar-toolbar', SidebarToolbarController, toolbar);

        expect(hidden(element)).toEqual([true, true]);
    });

    it('shows them once a menu with groups connects, and they fold and unfold it', async () => {
        const { element } = await mountWithMenu(toolbar + menu());

        expect(hidden(element)).toEqual([false, false]);

        commands(element)[expand].click();
        await settle();

        expect(expanded()).toEqual(['true', 'true']);

        commands(element)[collapse].click();
        await settle();

        expect(expanded()).toEqual(['false', 'false']);
        expect(JSON.parse(window.localStorage.getItem(STORAGE_KEY))).toEqual({
            Catalogue: false,
            Taxonomy: false,
        });
    });

    it('keeps them hidden for a menu with nothing to fold', async () => {
        const { element } = await mountWithMenu(toolbar + menu([]));

        expect(hidden(element)).toEqual([true, true]);
    });

    it('hides them again when the menu goes away', async () => {
        const { element } = await mountWithMenu(toolbar + menu());

        expect(hidden(element)).toEqual([false, false]);

        document.querySelector('nav').remove();
        await settle();

        expect(hidden(element)).toEqual([true, true]);
    });
});
