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

import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it } from 'vitest';

import FilterController from '../controllers/filter_controller.js';
import FilterListController from '../controllers/filter_list_controller.js';
import { settle } from './helpers.js';

/*
 * The filter list is the "Filters" menu of `@SonataAdmin/CRUD/base_list.html.twig`: one entry per
 * filter, a counter of how many are on, and an outlet to the filter form itself. M3 rewrites the
 * template; the identifiers, targets, classes and the outlet are the contract.
 */
const markup = `
    <div data-controller="sonata-filter"
         data-sonata-filter-sonata-filter-list-outlet="#filter-list"
         data-sonata-filter-default-values-value="{}">
        <form data-sonata-filter-target="form">
            <div id="name" data-sonata-filter-target="group">
                <input name="filter[name][value]">
            </div>
            <div id="status" data-sonata-filter-target="group" hidden>
                <input name="filter[status][value]">
            </div>
        </form>
    </div>
    <div id="filter-list"
         data-controller="sonata-filter-list"
         data-sonata-filter-list-active-class="active"
         data-sonata-filter-list-sonata-filter-outlet="[data-controller~='sonata-filter']">
        <span data-sonata-filter-list-target="counter"></span>
        <button data-sonata-filter-list-target="field"
                data-filter="name"
                class="active"
                data-action="sonata-filter-list#toggle">Name</button>
        <button data-sonata-filter-list-target="field"
                data-filter="status"
                data-action="sonata-filter-list#toggle">Status</button>
    </div>
`;

let application;

const start = async () => {
    document.body.innerHTML = markup;
    application = new Application(document.documentElement);
    application.register('sonata-filter', FilterController);
    application.register('sonata-filter-list', FilterListController);
    application.start();
    await settle();
};

afterEach(() => {
    application?.stop();
    document.body.innerHTML = '';
});

describe('sonata-filter-list', () => {
    it('counts the filters that are on when it connects', async () => {
        await start();

        expect(document.querySelector('[data-sonata-filter-list-target=counter]').innerHTML).toBe('1');
    });

    it('toggles a filter on and updates the counter', async () => {
        await start();

        document.querySelectorAll('[data-sonata-filter-list-target=field]')[1].click();
        await settle();

        expect(document.querySelector('[data-sonata-filter-list-target=counter]').innerHTML).toBe('2');
    });

    it('toggles a filter off again', async () => {
        await start();

        document.querySelectorAll('[data-sonata-filter-list-target=field]')[0].click();
        await settle();

        expect(document.querySelector('[data-sonata-filter-list-target=counter]').innerHTML).toBe('0');
    });

    it('shows and hides the matching group in the filter form', async () => {
        await start();

        const status = document.querySelectorAll('[data-sonata-filter-target=group]')[1];
        expect(status.hidden).toBe(true);

        document.querySelectorAll('[data-sonata-filter-list-target=field]')[1].click();
        await settle();

        expect(status.hidden).toBe(false);
    });
});
