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

import AutocompleteController from '../controllers/autocomplete_controller.js';
import { mount, settle } from './helpers.js';

const widget = ({ multiple = false, safeLabel = false, selected = [], hidden = '' } = {}) => `
    <form>
        <div class="adm-combobox"
             data-controller="sonata-autocomplete"
             data-sonata-autocomplete-url-value="/admin/core/get-autocomplete-items"
             data-sonata-autocomplete-request-parameters-value='{"_sonata_admin":"app.admin.category","field":"products","_context":"filter"}'
             data-sonata-autocomplete-search-parameter-value="q"
             data-sonata-autocomplete-page-parameter-value="_page"
             data-sonata-autocomplete-per-page-parameter-value="_per_page"
             data-sonata-autocomplete-min-length-value="2"
             data-sonata-autocomplete-per-page-value="5"
             data-sonata-autocomplete-delay-value="10"
             data-sonata-autocomplete-multiple-value="${multiple}"
             data-sonata-autocomplete-name-value="filter[products][value]"
             data-sonata-autocomplete-safe-label-value="${safeLabel}"
             data-sonata-autocomplete-disabled-value="false"
             data-sonata-autocomplete-selected-value='${JSON.stringify(selected)}'
             data-sonata-autocomplete-min-length-text-value="Type %count% or more characters to search"
             data-sonata-autocomplete-no-results-text-value="no result found"
             data-sonata-autocomplete-loading-text-value="Loading information…"
             data-sonata-autocomplete-more-text-value="Load more results"
             data-sonata-autocomplete-remove-text-value="Remove">
            ${multiple ? '<ul data-sonata-autocomplete-target="chips"></ul>' : ''}
            <input type="text" id="filter_products_value_autocomplete_input"
                   role="combobox" aria-autocomplete="list" aria-expanded="false"
                   aria-controls="filter_products_value_listbox" autocomplete="off"
                   data-sonata-autocomplete-target="input"
                   data-action="input->sonata-autocomplete#search keydown->sonata-autocomplete#navigate">
            <ul id="filter_products_value_listbox" role="listbox" hidden
                data-sonata-autocomplete-target="listbox"></ul>
            <div role="status" data-sonata-autocomplete-target="status"></div>
            <template data-sonata-autocomplete-target="itemTemplate"><li class="adm-combobox__option" data-action="click->sonata-autocomplete#pick"></li></template>
            <template data-sonata-autocomplete-target="chipTemplate"><li class="adm-chip"><span data-label></span><button type="button" data-remove data-action="sonata-autocomplete#remove" aria-label="Remove"><span aria-hidden="true">&times;</span></button></li></template>
            <div id="filter_products_value_hidden_inputs_wrap" data-sonata-autocomplete-target="hiddenInputs">${hidden}</div>
        </div>
    </form>
`;

const parts = (element) => {
    const form = element.closest('form');

    return {
        input: element.querySelector('[data-sonata-autocomplete-target="input"]'),
        listbox: element.querySelector('[data-sonata-autocomplete-target="listbox"]'),
        status: element.querySelector('[data-sonata-autocomplete-target="status"]'),
        chips: element.querySelector('[data-sonata-autocomplete-target="chips"]'),
        hidden: () => [...form.querySelectorAll('[data-sonata-autocomplete-target="hiddenInputs"] input')],
        options: () => [...element.querySelectorAll('[role="option"]')],
    };
};

const type = async (input, value) => {
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(20);
    await settle();
};

const press = (input, key) =>
    input.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }));

/** One page of the response the `sonata_admin_retrieve_autocomplete_items` action sends. */
const page = (items, more = false) => ({
    ok: true,
    status: 200,
    json: async () => ({ status: 'OK', more, items }),
});

const forbidden = { ok: false, status: 403, json: async () => ({ status: 'KO' }) };

const PRODUCTS = [
    { id: '1', label: 'Product 01' },
    { id: '2', label: 'Product 02' },
    { id: '3', label: 'Product 03' },
];

describe('sonata-autocomplete', () => {
    beforeEach(() => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => page(PRODUCTS)),
        );
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('says how much more to type instead of searching', async () => {
        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());
        const { input, status, listbox } = parts(element);

        await type(input, 'P');

        expect(status.textContent).toBe('Type 1 or more characters to search');
        expect(listbox.hidden).toBe(true);
        expect(fetch).not.toHaveBeenCalled();
    });

    it('debounces, then asks the server with the parameters the block rendered', async () => {
        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());
        const { input } = parts(element);

        input.value = 'Pro';
        input.dispatchEvent(new Event('input', { bubbles: true }));

        expect(fetch).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(20);

        expect(fetch).toHaveBeenCalledTimes(1);

        const url = new URL(fetch.mock.calls[0][0], 'http://localhost');

        expect(url.pathname).toBe('/admin/core/get-autocomplete-items');
        expect(Object.fromEntries(url.searchParams)).toEqual({
            _sonata_admin: 'app.admin.category',
            field: 'products',
            _context: 'filter',
            q: 'Pro',
            _page: '1',
            _per_page: '5',
        });
    });

    it('fires one request for a burst of keystrokes', async () => {
        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());
        const { input } = parts(element);

        for (const value of ['Pr', 'Pro', 'Prod']) {
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            await vi.advanceTimersByTimeAsync(5);
        }

        await vi.advanceTimersByTimeAsync(20);

        expect(fetch).toHaveBeenCalledTimes(1);
        expect(new URL(fetch.mock.calls[0][0], 'http://localhost').searchParams.get('q')).toBe('Prod');
    });

    it('reads a 403 as "you have not typed enough"', async () => {
        vi.mocked(fetch).mockResolvedValue(forbidden);

        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());
        const { input, status, listbox } = parts(element);

        await type(input, 'Pro');

        expect(status.textContent).toBe('Type 0 or more characters to search');
        expect(listbox.hidden).toBe(true);
    });

    it('says so when nothing matched', async () => {
        vi.mocked(fetch).mockResolvedValue(page([]));

        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());
        const { input, status } = parts(element);

        await type(input, 'Pro');

        expect(status.textContent).toBe('no result found');
    });

    it('opens the listbox on the results', async () => {
        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());
        const { input, listbox, options } = parts(element);

        await type(input, 'Pro');

        expect(listbox.hidden).toBe(false);
        expect(input.getAttribute('aria-expanded')).toBe('true');
        expect(options().map((option) => option.textContent)).toEqual([
            'Product 01',
            'Product 02',
            'Product 03',
        ]);
    });

    it('walks the options with the arrow keys and Home and End', async () => {
        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());
        const { input, options } = parts(element);

        await type(input, 'Pro');

        press(input, 'ArrowDown');
        expect(input.getAttribute('aria-activedescendant')).toBe(options()[0].id);
        expect(options()[0].getAttribute('aria-selected')).toBe('true');

        press(input, 'ArrowDown');
        expect(input.getAttribute('aria-activedescendant')).toBe(options()[1].id);

        press(input, 'ArrowUp');
        expect(input.getAttribute('aria-activedescendant')).toBe(options()[0].id);

        press(input, 'End');
        expect(input.getAttribute('aria-activedescendant')).toBe(options()[2].id);

        press(input, 'Home');
        expect(input.getAttribute('aria-activedescendant')).toBe(options()[0].id);

        // Up from the first option wraps to the last.
        press(input, 'ArrowUp');
        expect(input.getAttribute('aria-activedescendant')).toBe(options()[2].id);
    });

    it('leaves Enter to the form while nothing is highlighted', async () => {
        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());
        const { input } = parts(element);

        await type(input, 'Pro');

        const event = new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true });
        input.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(false);
    });

    it('writes the chosen identifier into the hidden input', async () => {
        const { element } = await mount(
            'sonata-autocomplete',
            AutocompleteController,
            widget({ hidden: '<input type="hidden" name="filter[products][value]" value="">' }),
        );
        const { input, listbox, hidden } = parts(element);
        const selected = vi.fn();

        element.addEventListener('sonata-autocomplete:selected', selected);

        await type(input, 'Pro');
        press(input, 'ArrowDown');
        press(input, 'ArrowDown');
        press(input, 'Enter');

        expect(hidden().map((field) => [field.name, field.value])).toEqual([
            ['filter[products][value]', '2'],
        ]);
        expect(input.value).toBe('Product 02');
        expect(listbox.hidden).toBe(true);
        expect(selected.mock.calls[0][0].detail).toEqual({ id: '2', label: 'Product 02' });
    });

    it('clears the identifier as soon as the box is edited again', async () => {
        const { element } = await mount(
            'sonata-autocomplete',
            AutocompleteController,
            widget({ hidden: '<input type="hidden" name="filter[products][value]" value="7">' }),
        );
        const { input, hidden } = parts(element);
        const cleared = vi.fn();

        element.addEventListener('sonata-autocomplete:cleared', cleared);

        await type(input, 'Pro');

        expect(hidden().map((field) => field.value)).toEqual(['']);
        expect(cleared).toHaveBeenCalledTimes(1);
    });

    it('picks an option with the pointer', async () => {
        const { element } = await mount(
            'sonata-autocomplete',
            AutocompleteController,
            widget({ hidden: '<input type="hidden" name="filter[products][value]" value="">' }),
        );
        const { input, options, hidden } = parts(element);

        await type(input, 'Pro');
        options()[2].click();
        await settle();

        expect(hidden().map((field) => field.value)).toEqual(['3']);
        expect(input.value).toBe('Product 03');
    });

    it('closes on Escape and on a click outside', async () => {
        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());
        const { input, listbox } = parts(element);

        await type(input, 'Pro');
        press(input, 'Escape');

        expect(listbox.hidden).toBe(true);
        expect(input.hasAttribute('aria-activedescendant')).toBe(false);

        press(input, 'ArrowDown');
        expect(listbox.hidden).toBe(false);

        document.body.click();
        await settle();

        expect(listbox.hidden).toBe(true);
    });

    it('appends a page behind a "load more" option', async () => {
        vi.mocked(fetch)
            .mockResolvedValueOnce(page(PRODUCTS, true))
            .mockResolvedValueOnce(page([{ id: '4', label: 'Product 04' }]));

        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());
        const { input, options } = parts(element);

        await type(input, 'Pro');

        expect(options().map((option) => option.textContent)).toEqual([
            'Product 01',
            'Product 02',
            'Product 03',
            'Load more results',
        ]);

        press(input, 'End');
        press(input, 'Enter');
        await settle();

        expect(new URL(fetch.mock.calls[1][0], 'http://localhost').searchParams.get('_page')).toBe('2');
        expect(options().map((option) => option.textContent)).toEqual([
            'Product 01',
            'Product 02',
            'Product 03',
            'Product 04',
        ]);
    });

    it('renders a label as text unless the server said it is markup', async () => {
        vi.mocked(fetch).mockResolvedValue(page([{ id: '9', label: '<b>Bold</b> & co' }]));

        const { element } = await mount('sonata-autocomplete', AutocompleteController, widget());

        await type(parts(element).input, 'Pro');

        expect(parts(element).options()[0].innerHTML).toBe('&lt;b&gt;Bold&lt;/b&gt; &amp; co');
    });

    it('trusts markup when safe_label is set, and still puts text in the box', async () => {
        vi.mocked(fetch).mockResolvedValue(page([{ id: '9', label: '<b>Bold</b> name' }]));

        const { element } = await mount(
            'sonata-autocomplete',
            AutocompleteController,
            widget({
                safeLabel: true,
                hidden: '<input type="hidden" name="filter[products][value]" value="">',
            }),
        );
        const { input, options } = parts(element);

        await type(input, 'Pro');

        expect(options()[0].querySelector('b')).not.toBeNull();

        press(input, 'ArrowDown');
        press(input, 'Enter');

        expect(input.value).toBe('Bold name');
    });

    describe('with multiple', () => {
        const multiple = (extra = {}) => widget({ multiple: true, ...extra });

        it('draws a chip and a hidden input per pre-selected model', async () => {
            const { element } = await mount(
                'sonata-autocomplete',
                AutocompleteController,
                multiple({
                    selected: [
                        { id: '1', label: 'Product 01' },
                        { id: '2', label: 'Product 02' },
                    ],
                    hidden:
                        '<input type="hidden" name="filter[products][value][]" value="1">' +
                        '<input type="hidden" name="filter[products][value][]" value="2">',
                }),
            );
            const { chips } = parts(element);

            expect([...chips.children].map((chip) => chip.dataset.sonataAutocompleteId)).toEqual(['1', '2']);
            expect(chips.children[0].querySelector('[data-label]').textContent).toBe('Product 01');
            expect(chips.children[0].querySelector('[data-remove]').getAttribute('aria-label')).toBe(
                'Remove Product 01',
            );
        });

        it('adds a chip and an array-named hidden input, and empties the box', async () => {
            const { element } = await mount('sonata-autocomplete', AutocompleteController, multiple());
            const { input, chips, hidden } = parts(element);

            await type(input, 'Pro');
            press(input, 'ArrowDown');
            press(input, 'Enter');

            expect(chips.children).toHaveLength(1);
            expect(hidden().map((field) => [field.name, field.value])).toEqual([
                ['filter[products][value][]', '1'],
            ]);
            expect(input.value).toBe('');
        });

        it('refuses to select the same model twice', async () => {
            const { element } = await mount('sonata-autocomplete', AutocompleteController, multiple());
            const { input, chips } = parts(element);

            for (let round = 0; round < 2; round += 1) {
                await type(input, 'Pro');
                press(input, 'ArrowDown');
                press(input, 'Enter');
            }

            expect(chips.children).toHaveLength(1);
        });

        it('removes a chip from its button', async () => {
            const { element } = await mount(
                'sonata-autocomplete',
                AutocompleteController,
                multiple({
                    selected: [{ id: '1', label: 'Product 01' }],
                    hidden: '<input type="hidden" name="filter[products][value][]" value="1">',
                }),
            );
            const { chips, hidden } = parts(element);
            const removed = vi.fn();

            element.addEventListener('sonata-autocomplete:removed', removed);

            chips.querySelector('[data-remove]').click();
            await settle();

            expect(chips.children).toHaveLength(0);
            expect(hidden()).toHaveLength(0);
            expect(removed.mock.calls[0][0].detail).toEqual({ id: '1' });
        });

        it('drops the last chip on Backspace in an empty box, and only then', async () => {
            const { element } = await mount(
                'sonata-autocomplete',
                AutocompleteController,
                multiple({
                    selected: [
                        { id: '1', label: 'Product 01' },
                        { id: '2', label: 'Product 02' },
                    ],
                    hidden:
                        '<input type="hidden" name="filter[products][value][]" value="1">' +
                        '<input type="hidden" name="filter[products][value][]" value="2">',
                }),
            );
            const { input, chips, hidden } = parts(element);

            input.value = 'Pro';
            press(input, 'Backspace');

            expect(chips.children).toHaveLength(2);

            input.value = '';
            press(input, 'Backspace');

            expect([...chips.children].map((chip) => chip.dataset.sonataAutocompleteId)).toEqual(['1']);
            expect(hidden().map((field) => field.value)).toEqual(['1']);
        });
    });
});
