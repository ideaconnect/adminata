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

import { describe, expect, it, vi } from 'vitest';

import TabsController from '../controllers/tabs_controller.js';
import { mount, settle } from './helpers.js';

const markup = (selected = 0) => `
    <div data-controller="adminata-tabs">
        <div role="tablist">
            ${['one', 'two', 'three']
                .map(
                    (id, index) => `
                <a href="#${id}" role="tab" id="${id}-tab" aria-controls="${id}"
                        aria-selected="${index === selected ? 'true' : 'false'}" tabindex="${index === selected ? '0' : '-1'}"
                        data-adminata-tabs-target="tab"
                        data-action="adminata-tabs#select keydown->adminata-tabs#move">${id}</a>`,
                )
                .join('')}
        </div>
        ${['one', 'two', 'three']
            .map(
                (id, index) => `
            <section id="${id}" role="tabpanel" aria-labelledby="${id}-tab" data-adminata-tabs-target="panel"${index === selected ? '' : ' hidden'}>
                <input name="${id}">
            </section>`,
            )
            .join('')}
    </div>
`;

const tab = (id) => document.getElementById(`${id}-tab`);
const panel = (id) => document.getElementById(id);
const selected = () =>
    [...document.querySelectorAll('[role="tab"]')].map((element) => element.getAttribute('aria-selected'));
const focusable = () =>
    [...document.querySelectorAll('[role="tab"]')].map((element) => element.getAttribute('tabindex'));
const hidden = () => [...document.querySelectorAll('[role="tabpanel"]')].map((element) => element.hidden);

const press = (element, key) => {
    element.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }));
};

describe('adminata-tabs', () => {
    it('keeps what the server selected, on connect', async () => {
        await mount('adminata-tabs', TabsController, markup(1));

        expect(selected()).toEqual(['false', 'true', 'false']);
        expect(focusable()).toEqual(['-1', '0', '-1']);
        expect(hidden()).toEqual([true, false, true]);
    });

    it('selects a clicked tab, shows its panel alone and announces it', async () => {
        const { element } = await mount('adminata-tabs', TabsController, markup());
        const shown = vi.fn();
        element.addEventListener('adminata-tabs:shown', shown);

        tab('three').click();
        await settle();

        expect(selected()).toEqual(['false', 'false', 'true']);
        expect(focusable()).toEqual(['-1', '-1', '0']);
        expect(hidden()).toEqual([true, true, false]);
        expect(shown).toHaveBeenCalledOnce();
        expect(shown.mock.calls[0][0].detail).toEqual({ tab: tab('three'), panel: panel('three') });
    });

    it('moves with the arrow keys, wrapping, and with Home and End — and selects where it lands', async () => {
        await mount('adminata-tabs', TabsController, markup());

        press(tab('one'), 'ArrowRight');
        await settle();
        expect(selected()).toEqual(['false', 'true', 'false']);
        expect(document.activeElement).toBe(tab('two'));

        press(tab('two'), 'ArrowLeft');
        await settle();
        expect(selected()).toEqual(['true', 'false', 'false']);

        press(tab('one'), 'ArrowLeft');
        await settle();
        expect(selected()).toEqual(['false', 'false', 'true']);
        expect(document.activeElement).toBe(tab('three'));

        press(tab('three'), 'Home');
        await settle();
        expect(selected()).toEqual(['true', 'false', 'false']);

        press(tab('one'), 'End');
        await settle();
        expect(selected()).toEqual(['false', 'false', 'true']);
    });

    it('lets other keys through to the browser', async () => {
        await mount('adminata-tabs', TabsController, markup());

        const event = new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true });
        tab('one').dispatchEvent(event);
        await settle();

        expect(event.defaultPrevented).toBe(false);
        expect(selected()).toEqual(['true', 'false', 'false']);
    });

    it('selects the tab an adminata-tabs:show event is sent to', async () => {
        await mount('adminata-tabs', TabsController, markup());

        // What `adminata-edit` dispatches, on the tab, for the first tab holding an error.
        tab('two').dispatchEvent(new CustomEvent('adminata-tabs:show', { bubbles: true }));
        await settle();

        expect(selected()).toEqual(['false', 'true', 'false']);
        expect(hidden()).toEqual([true, false, true]);
    });

    it('falls back to the first tab when the server selected none', async () => {
        await mount('adminata-tabs', TabsController, markup(-1));

        expect(selected()).toEqual(['true', 'false', 'false']);
        expect(hidden()).toEqual([false, true, true]);
    });
});
