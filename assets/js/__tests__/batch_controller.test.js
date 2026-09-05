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

import BatchController from '../controllers/batch_controller.js';
import { mount, settle } from './helpers.js';

const LIST = `
    <form data-controller="sonata-batch">
        <table>
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="list_batch_checkbox"
                               data-sonata-batch-target="all"
                               data-action="change->sonata-batch#toggleAll">
                    </th>
                </tr>
            </thead>
            <tbody>
                ${[1, 2, 3, 4, 5]
                    .map(
                        (i) => `<tr id="row-${i}"><td class="sonata-ba-list-field-batch">
                            <input type="checkbox" name="idx[]" value="${i}"
                                   data-sonata-batch-target="row"
                                   data-action="click->sonata-batch#toggleRow">
                        </td></tr>`,
                    )
                    .join('')}
            </tbody>
        </table>
    </form>
`;

const rows = (element) => [...element.querySelectorAll('[data-sonata-batch-target="row"]')];
const all = (element) => element.querySelector('#list_batch_checkbox');
const checked = (element) => rows(element).map((row) => row.checked);
const selected = (element) =>
    [...element.querySelectorAll('tbody tr')].map((row) =>
        row.classList.contains('sonata-ba-list-row-selected'),
    );

/**
 * A click that the browser would deliver with the shift key down.
 *
 * Nothing is toggled by hand: dispatching `click` on a checkbox runs its activation behaviour, so
 * setting `checked` first and dispatching after toggles it twice and the handler reads the value
 * back to where it started.
 */
const shiftClick = (input) => input.dispatchEvent(new MouseEvent('click', { bubbles: true, shiftKey: true }));

describe('sonata-batch', () => {
    it('selects and clears every row from the header', async () => {
        const { element } = await mount('sonata-batch', BatchController, LIST);

        all(element).checked = true;
        all(element).dispatchEvent(new Event('change', { bubbles: true }));
        await settle();

        expect(checked(element)).toEqual([true, true, true, true, true]);
        expect(selected(element)).toEqual([true, true, true, true, true]);

        all(element).checked = false;
        all(element).dispatchEvent(new Event('change', { bubbles: true }));
        await settle();

        expect(checked(element)).toEqual([false, false, false, false, false]);
        expect(selected(element)).toEqual([false, false, false, false, false]);
    });

    it('shows the header as indeterminate while only some rows are selected', async () => {
        const { element } = await mount('sonata-batch', BatchController, LIST);

        rows(element)[1].click();
        await settle();

        expect(all(element).indeterminate).toBe(true);
        expect(all(element).checked).toBe(false);

        rows(element).forEach((row) => {
            if (!row.checked) {
                row.click();
            }
        });
        await settle();

        expect(all(element).indeterminate).toBe(false);
        expect(all(element).checked).toBe(true);
    });

    it('marks the row a checkbox belongs to', async () => {
        const { element } = await mount('sonata-batch', BatchController, LIST);

        rows(element)[2].click();
        await settle();

        expect(selected(element)).toEqual([false, false, true, false, false]);
    });

    it('extends the selection downwards with shift', async () => {
        const { element } = await mount('sonata-batch', BatchController, LIST);

        rows(element)[1].click();
        await settle();
        shiftClick(rows(element)[3]);
        await settle();

        expect(checked(element)).toEqual([false, true, true, true, false]);
    });

    /** Upstream's condition read `indexedDB > currentIndex`, so this direction never worked. */
    it('extends the selection upwards with shift', async () => {
        const { element } = await mount('sonata-batch', BatchController, LIST);

        rows(element)[3].click();
        await settle();
        shiftClick(rows(element)[1]);
        await settle();

        expect(checked(element)).toEqual([false, true, true, true, false]);
    });

    it('clears a range with shift when the anchor is being cleared', async () => {
        const { element } = await mount('sonata-batch', BatchController, LIST);

        all(element).checked = true;
        all(element).dispatchEvent(new Event('change', { bubbles: true }));
        await settle();

        rows(element)[1].click();
        await settle();
        shiftClick(rows(element)[3]);
        await settle();

        expect(checked(element)).toEqual([true, false, false, false, true]);
    });

    it('runs on a list with no header checkbox', async () => {
        const { element } = await mount(
            'sonata-batch',
            BatchController,
            LIST.replace(/<thead>[\s\S]*<\/thead>/, ''),
        );

        rows(element)[0].click();
        await settle();

        expect(checked(element)).toEqual([true, false, false, false, false]);
    });
});
