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

import { describe, expect, it } from 'vitest';

import DropdownController from '../controllers/dropdown_controller.js';
import { mount, settle } from './helpers.js';

const DROPDOWN = `
    <div>
        <a href="/before" id="before">before</a>
        <div data-controller="adminata-dropdown"
             data-action="keydown.esc->adminata-dropdown#closeAndFocus keydown->adminata-dropdown#navigate">
            <button type="button" aria-expanded="false"
                    data-adminata-dropdown-target="toggle"
                    data-action="click->adminata-dropdown#toggle">
                Menu
            </button>
            <div class="adm-dropdown__menu" hidden data-adminata-dropdown-target="menu">
                <a href="/one" id="one">One</a>
                <a href="/two" id="two">Two</a>
                <a href="/three" id="three">Three</a>
            </div>
        </div>
    </div>
`;

const parts = (element) => ({
    toggle: element.querySelector('[data-adminata-dropdown-target="toggle"]'),
    menu: element.querySelector('[data-adminata-dropdown-target="menu"]'),
});

const arrow = (element, key) =>
    element
        .querySelector('[data-adminata-dropdown-target="toggle"]')
        .dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));

describe('adminata-dropdown', () => {
    it('starts from what the server rendered', async () => {
        const { element } = await mount('adminata-dropdown', DropdownController, DROPDOWN);
        const { toggle, menu } = parts(element);

        expect(toggle.getAttribute('aria-expanded')).toBe('false');
        expect(menu.hidden).toBe(true);
    });

    it('opens and closes on the button', async () => {
        const { element } = await mount('adminata-dropdown', DropdownController, DROPDOWN);
        const { toggle, menu } = parts(element);

        toggle.click();
        await settle();

        expect(toggle.getAttribute('aria-expanded')).toBe('true');
        expect(menu.hidden).toBe(false);

        toggle.click();
        await settle();

        expect(menu.hidden).toBe(true);
    });

    it('closes when something outside is clicked, and not when something inside is', async () => {
        const { element } = await mount('adminata-dropdown', DropdownController, DROPDOWN);
        const { toggle, menu } = parts(element);

        toggle.click();
        await settle();

        menu.querySelector('#one').dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await settle();
        expect(menu.hidden).toBe(false);

        document.querySelector('#before').dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await settle();
        expect(menu.hidden).toBe(true);
    });

    it('closes on Escape and puts focus back on the button', async () => {
        const { element } = await mount('adminata-dropdown', DropdownController, DROPDOWN);
        const { toggle, menu } = parts(element);

        toggle.click();
        await settle();
        menu.querySelector('#two').focus();

        menu.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await settle();

        expect(menu.hidden).toBe(true);
        expect(document.activeElement).toBe(toggle);
    });

    it('opens on the down arrow with the first item focused', async () => {
        const { element } = await mount('adminata-dropdown', DropdownController, DROPDOWN);
        const { toggle, menu } = parts(element);

        arrow(element, 'ArrowDown');
        await settle();

        expect(toggle.getAttribute('aria-expanded')).toBe('true');
        expect(document.activeElement).toBe(menu.querySelector('#one'));
    });

    it('opens on the up arrow with the last item focused', async () => {
        const { element } = await mount('adminata-dropdown', DropdownController, DROPDOWN);
        const { menu } = parts(element);

        arrow(element, 'ArrowUp');
        await settle();

        expect(document.activeElement).toBe(menu.querySelector('#three'));
    });

    it('walks the items and wraps around', async () => {
        const { element } = await mount('adminata-dropdown', DropdownController, DROPDOWN);
        const { menu } = parts(element);

        arrow(element, 'ArrowDown');
        await settle();

        menu.querySelector('#three').focus();
        menu.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
        await settle();

        expect(document.activeElement).toBe(menu.querySelector('#one'));
    });

    it('announces opening and closing', async () => {
        const { element } = await mount('adminata-dropdown', DropdownController, DROPDOWN);
        const { toggle } = parts(element);
        const seen = [];

        element.addEventListener('adminata-dropdown:opened', () => seen.push('opened'));
        element.addEventListener('adminata-dropdown:closed', () => seen.push('closed'));

        toggle.click();
        await settle();
        toggle.click();
        await settle();

        expect(seen).toEqual(['opened', 'closed']);
    });
});
