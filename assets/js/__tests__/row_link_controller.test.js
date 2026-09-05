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

import { beforeEach, describe, expect, it, vi } from 'vitest';

import RowLinkController from '../controllers/row_link_controller.js';
import { mount, settle } from './helpers.js';

/*
 * Markup mirrors `@SonataAdmin/CRUD/list_outer_rows_list.html.twig`: the controller on the body,
 * the destination on each row, and a row without one for the object this administrator may not
 * open. The last cell holds the controls a click must be left to.
 */
const markup = `
    <table>
        <tbody data-controller="sonata-row-link"
               data-action="click->sonata-row-link#open auxclick->sonata-row-link#open">
            <tr class="sonata-ba-list-row-link" data-sonata-row-link-url="/admin/product/1/edit">
                <td class="sonata-ba-list-field sonata-ba-list-field-batch">
                    <input type="checkbox" name="idx[]" value="1">
                </td>
                <td class="name">Chair</td>
                <td class="sonata-ba-list-field sonata-ba-list-field-actions">
                    <a class="inside" href="/admin/product/1/show">Show</a>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="locked">Table</td>
                <td></td>
            </tr>
        </tbody>
    </table>
`;

/** A click as the browser delivers it: on the cell, bubbling up to the body. */
function click(selector, { type = 'click', button = 0, ...modifiers } = {}) {
    document
        .querySelector(selector)
        .dispatchEvent(new MouseEvent(type, { bubbles: true, cancelable: true, button, ...modifiers }));
}

describe('sonata-row-link', () => {
    beforeEach(async () => {
        // jsdom's `location` is read only and navigating it is unimplemented, so it is replaced
        // wholesale — the same trick `per_page_controller.test.js` uses.
        delete window.location;
        window.location = { assign: vi.fn() };
        window.open = vi.fn();

        // A selection left over from an earlier case would suppress every click after it.
        window.getSelection().removeAllRanges();

        await mount('sonata-row-link', RowLinkController, markup);
    });

    it('opens the object when the row is clicked', async () => {
        click('td.name');
        await settle();

        expect(window.location.assign).toHaveBeenCalledWith('/admin/product/1/edit');
    });

    it('leaves a click on a link to the link', async () => {
        click('a.inside');
        await settle();

        expect(window.location.assign).not.toHaveBeenCalled();
    });

    it('leaves a click on a checkbox to the checkbox', async () => {
        click('input[type=checkbox]');
        await settle();

        expect(window.location.assign).not.toHaveBeenCalled();
    });

    it.each([
        ['batch', 'td.sonata-ba-list-field-batch'],
        ['actions', 'td.sonata-ba-list-field-actions'],
    ])('leaves the whole %s cell to its controls', async (_name, selector) => {
        click(selector);
        await settle();

        expect(window.location.assign).not.toHaveBeenCalled();
    });

    it('does nothing on a row with no destination', async () => {
        click('td.locked');
        await settle();

        expect(window.location.assign).not.toHaveBeenCalled();
        expect(window.open).not.toHaveBeenCalled();
    });

    it.each([
        ['ctrl', { ctrlKey: true }],
        ['meta', { metaKey: true }],
        ['shift', { shiftKey: true }],
    ])('opens a new tab on a %s click', async (_name, modifiers) => {
        click('td.name', modifiers);
        await settle();

        expect(window.open).toHaveBeenCalledWith('/admin/product/1/edit', '_blank', 'noopener');
        expect(window.location.assign).not.toHaveBeenCalled();
    });

    it('opens a new tab on a middle click', async () => {
        click('td.name', { type: 'auxclick', button: 1 });
        await settle();

        expect(window.open).toHaveBeenCalledWith('/admin/product/1/edit', '_blank', 'noopener');
    });

    it('leaves the right button to the context menu', async () => {
        click('td.name', { type: 'auxclick', button: 2 });
        await settle();

        expect(window.open).not.toHaveBeenCalled();
        expect(window.location.assign).not.toHaveBeenCalled();
    });

    it('does not navigate when the click ended a text selection', async () => {
        const range = document.createRange();
        range.selectNodeContents(document.querySelector('td.name'));
        window.getSelection().addRange(range);

        click('td.name');
        await settle();

        expect(window.location.assign).not.toHaveBeenCalled();
    });
});
