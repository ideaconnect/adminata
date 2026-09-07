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

import RevealController from '../controllers/reveal_controller.js';
import { mount, settle } from './helpers.js';

const form = (master, sectionClass = '') => `
    <form>
        ${master}
        <div id="geo" class="${sectionClass}"><input name="coords"></div>
        <div id="geo-help" class="${sectionClass}">Help</div>
    </form>
`;

const select = (selected, values = '') =>
    `<select id="master" data-controller="sonata-reveal" data-sonata-reveal-target-value="#geo, #geo-help" ${values}>
        <option value="0"${'0' === selected ? ' selected' : ''}>No</option>
        <option value="1"${'1' === selected ? ' selected' : ''}>Yes</option>
        <option value="2"${'2' === selected ? ' selected' : ''}>Maybe</option>
    </select>`;

const change = (element, value) => {
    element.value = value;
    element.dispatchEvent(new Event('change', { bubbles: true }));
};

describe('sonata-reveal', () => {
    it('puts the sections in the state the saved value asks for, on connect', async () => {
        await mount(
            'sonata-reveal',
            RevealController,
            form(select('0', 'data-sonata-reveal-when-value="1"')),
        );

        expect(document.getElementById('geo').hidden).toBe(true);
        expect(document.getElementById('geo-help').hidden).toBe(true);
    });

    it('shows them when the control takes the value, and hides them again', async () => {
        const { element } = await mount(
            'sonata-reveal',
            RevealController,
            form(select('0', 'data-sonata-reveal-when-value="1"')),
        );

        change(element, '1');
        await settle();
        expect(document.getElementById('geo').hidden).toBe(false);

        change(element, '2');
        await settle();
        expect(document.getElementById('geo').hidden).toBe(true);
    });

    it('takes over from a hidden class the server rendered', async () => {
        await mount(
            'sonata-reveal',
            RevealController,
            form(select('1', 'data-sonata-reveal-when-value="1"'), 'hidden'),
        );

        const section = document.getElementById('geo');
        expect(section.classList.contains('hidden')).toBe(false);
        expect(section.hidden).toBe(false);
    });

    it('accepts a list of values', async () => {
        const { element } = await mount(
            'sonata-reveal',
            RevealController,
            form(select('2', `data-sonata-reveal-when-value='["1","2"]'`)),
        );

        expect(document.getElementById('geo').hidden).toBe(false);

        change(element, '0');
        await settle();
        expect(document.getElementById('geo').hidden).toBe(true);
    });

    it('works on a checkbox', async () => {
        const { element } = await mount(
            'sonata-reveal',
            RevealController,
            form(
                `<input type="checkbox" id="master" value="1" data-controller="sonata-reveal"
                        data-sonata-reveal-target-value="#geo" data-sonata-reveal-when-value="1">`,
            ),
        );

        expect(document.getElementById('geo').hidden).toBe(true);

        element.checked = true;
        element.dispatchEvent(new Event('change', { bubbles: true }));
        await settle();
        expect(document.getElementById('geo').hidden).toBe(false);
    });

    it('resolves the section from the row it shares with the control, not from the document', async () => {
        const row = (n, selected) => `
            <div class="row" id="row-${n}">
                <select id="master-${n}" data-controller="sonata-reveal"
                        data-sonata-reveal-target-value=".details" data-sonata-reveal-when-value="1">
                    <option value="0"${'0' === selected ? ' selected' : ''}>No</option>
                    <option value="1"${'1' === selected ? ' selected' : ''}>Yes</option>
                </select>
                <div class="details" id="details-${n}"></div>
            </div>`;
        await mount('sonata-reveal', RevealController, `<form>${row(1, '1')}${row(2, '0')}</form>`);

        expect(document.getElementById('details-1').hidden).toBe(false);
        expect(document.getElementById('details-2').hidden).toBe(true);

        change(document.getElementById('master-2'), '1');
        await settle();
        expect(document.getElementById('details-2').hidden).toBe(false);

        change(document.getElementById('master-1'), '0');
        await settle();
        expect(document.getElementById('details-1').hidden).toBe(true);
        expect(document.getElementById('details-2').hidden).toBe(false);
    });

    it('counts every selected value of a multiple select', async () => {
        const { element } = await mount(
            'sonata-reveal',
            RevealController,
            form(`<select id="master" multiple data-controller="sonata-reveal"
                          data-sonata-reveal-target-value="#geo" data-sonata-reveal-when-value="b">
                      <option value="a">a</option><option value="b" selected>b</option><option value="c">c</option>
                  </select>`),
        );

        expect(document.getElementById('geo').hidden).toBe(false);

        // "a" sorts before "b", so `.value` alone would now say "a" and hide the section.
        element.querySelector('option[value="a"]').selected = true;
        element.dispatchEvent(new Event('change', { bubbles: true }));
        await settle();
        expect(document.getElementById('geo').hidden).toBe(false);

        element.querySelector('option[value="b"]').selected = false;
        element.dispatchEvent(new Event('change', { bubbles: true }));
        await settle();
        expect(document.getElementById('geo').hidden).toBe(true);
    });

    it('works on a radio group, from its wrapper', async () => {
        const { element } = await mount(
            'sonata-reveal',
            RevealController,
            form(
                `<div id="master" data-controller="sonata-reveal" data-sonata-reveal-target-value="#geo" data-sonata-reveal-when-value="yes">
                    <label><input type="radio" name="uses" value="no" checked> No</label>
                    <label><input type="radio" name="uses" value="yes"> Yes</label>
                </div>`,
            ),
        );

        expect(document.getElementById('geo').hidden).toBe(true);

        const yes = element.querySelector('input[value="yes"]');
        yes.checked = true;
        yes.dispatchEvent(new Event('change', { bubbles: true }));
        await settle();
        expect(document.getElementById('geo').hidden).toBe(false);
    });
});
