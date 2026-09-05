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

import FilterController from '../controllers/filter_controller.js';
import { buildQueryString } from '../core/utils.js';
import { mount, settle } from './helpers.js';

/*
 * `prepareSubmit()` is what keeps a filter URL short: a field left at the admin's default value
 * loses its `name` and is not submitted at all. Mirrors the filter panel of
 * `@SonataAdmin/CRUD/base_list.html.twig`, which M3 rewrites.
 */
const markup = (defaults) => `
    <div data-controller="sonata-filter"
         data-sonata-filter-default-values-value='${JSON.stringify(defaults)}'>
        <form data-sonata-filter-target="form" data-action="submit->sonata-filter#prepareSubmit">
            <div id="name" data-sonata-filter-target="group">
                <input name="filter[name][value]" value="">
            </div>
            <div id="status" data-sonata-filter-target="group">
                <input name="filter[status][value]" value="">
            </div>
            <button type="submit" data-sonata-filter-target="submitter">Filter</button>
        </form>
    </div>
`;

const field = (name) => document.querySelector(`[name="filter[${name}][value]"]`);
const form = () => document.querySelector('form');

describe('sonata-filter', () => {
    it('drops the name of a field left at its default so it is not submitted', async () => {
        await mount('sonata-filter', FilterController, markup({ name: { value: 'default' } }));

        field('name').value = 'default';
        field('status').value = 'chosen';
        form().dispatchEvent(new Event('submit'));
        await settle();

        expect(document.querySelector('#name input').hasAttribute('name')).toBe(false);
        expect(document.querySelector('#status input').getAttribute('name')).toBe('filter[status][value]');
    });

    it('keeps the name of a field the user changed away from the default', async () => {
        await mount('sonata-filter', FilterController, markup({ name: { value: 'default' } }));

        field('name').value = 'something else';
        form().dispatchEvent(new Event('submit'));
        await settle();

        expect(document.querySelector('#name input').getAttribute('name')).toBe('filter[name][value]');
    });

    it('disables the submit button so the filters are not submitted twice', async () => {
        await mount('sonata-filter', FilterController, markup({}));

        form().dispatchEvent(new Event('submit'));
        await settle();

        expect(document.querySelector('[data-sonata-filter-target=submitter]').disabled).toBe(true);
    });

    it('clears the fields of a hidden group before submitting', async () => {
        await mount('sonata-filter', FilterController, markup({}));

        field('status').value = 'stale';
        document.querySelector('#status').hidden = true;
        form().dispatchEvent(new Event('submit'));
        await settle();

        expect(document.querySelector('#status input').value).toBe('');
    });

    it('serialises the default values the way PHP reads them back', () => {
        expect(buildQueryString({ filter: { name: { value: 'a b&c' } } })).toBe(
            'filter%5Bname%5D%5Bvalue%5D=a%20b%26c',
        );
    });
});
