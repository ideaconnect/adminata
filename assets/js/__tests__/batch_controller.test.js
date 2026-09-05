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
import { fixture, mount, settle } from './helpers.js';

/*
 * What the demo's product list actually renders (PLAN/05 §9). The fixture is dumped by
 * `JsFixtureDumperTest`, so a template that renames `data-sonata-batch-target` or drops the
 * action fails here instead of in a browser.
 */
const LIST = fixture('product-list', 'form[action*="batch"]');

const rows = (element) => [...element.querySelectorAll('[data-sonata-batch-target="row"]')];
const all = (element) => element.querySelector('#list_batch_checkbox');
const checked = (element) => rows(element).map((row) => row.checked);
const selected = (element) =>
    [...element.querySelectorAll('tbody tr')].map((row) =>
        row.classList.contains('sonata-ba-list-row-selected'),
    );

/** The indices of the rows a predicate holds for, which is what the assertions compare. */
const indices = (values) => values.flatMap((value, index) => (value ? [index] : []));
const every = (element, value) => rows(element).map(() => value);

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

        expect(checked(element)).toEqual(every(element, true));
        expect(selected(element)).toEqual(every(element, true));

        all(element).checked = false;
        all(element).dispatchEvent(new Event('change', { bubbles: true }));
        await settle();

        expect(checked(element)).toEqual(every(element, false));
        expect(selected(element)).toEqual(every(element, false));
    });

    it('shows the header as indeterminate while only some rows are selected', async () => {
        const { element } = await mount('sonata-batch', BatchController, LIST);

        rows(element)[1].click();
        await settle();

        expect(all(element).indeterminate).toBe(true);
        expect(all(element).checked).toBe(false);

        // Through the header rather than by clicking the other twenty-four rows one at a time: the
        // property under test is the indeterminate state clearing once every row is selected, and
        // twenty-five dispatched clicks is what timed the test out on a slower machine.
        all(element).checked = true;
        all(element).dispatchEvent(new Event('change', { bubbles: true }));
        await settle();

        expect(all(element).indeterminate).toBe(false);
        expect(all(element).checked).toBe(true);
    });

    it('marks the row a checkbox belongs to', async () => {
        const { element } = await mount('sonata-batch', BatchController, LIST);

        rows(element)[2].click();
        await settle();

        expect(indices(selected(element))).toEqual([2]);
    });

    it('extends the selection downwards with shift', async () => {
        const { element } = await mount('sonata-batch', BatchController, LIST);

        rows(element)[1].click();
        await settle();
        shiftClick(rows(element)[3]);
        await settle();

        expect(indices(checked(element))).toEqual([1, 2, 3]);
    });

    /** Upstream's condition read `indexedDB > currentIndex`, so this direction never worked. */
    it('extends the selection upwards with shift', async () => {
        const { element } = await mount('sonata-batch', BatchController, LIST);

        rows(element)[3].click();
        await settle();
        shiftClick(rows(element)[1]);
        await settle();

        expect(indices(checked(element))).toEqual([1, 2, 3]);
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

        expect(indices(checked(element).slice(0, 5))).toEqual([0, 4]);
    });

    it('runs on a list with no header checkbox', async () => {
        const { element } = await mount(
            'sonata-batch',
            BatchController,
            LIST.replace(/<thead>[\s\S]*<\/thead>/, ''),
        );

        rows(element)[0].click();
        await settle();

        expect(indices(checked(element))).toEqual([0]);
    });
});
