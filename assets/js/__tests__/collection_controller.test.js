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

import CollectionController from '../controllers/collection_controller.js';
import { fixture, mount, settle } from './helpers.js';

/*
 * What the demo's product edit page actually renders (PLAN/05 §9): a collection with two rows and
 * a prototype carrying `__name__` in the id and the name of every field. The fixture is dumped by
 * `JsFixtureDumperTest`, so a rewrite of `adminata_type_native_collection_widget` that changes the
 * prototype contract — or the four event names below, which applications listen for — fails here.
 */
const markup = fixture('product-edit', '[data-controller~="adminata-collection"]');

const addButton = () => document.querySelector('.adminata-collection-add');
const items = () => [...document.querySelectorAll('[data-adminata-collection-target=item]')];

/** The demo's product has two variants; the assertions are written against whatever it has. */
const start = () => items().length;

describe('adminata-collection', () => {
    it('counts the rows it starts with', async () => {
        const { element } = await mount('adminata-collection', CollectionController, markup);

        expect(element.dataset.adminataCollectionNumItemsValue).toBe(String(start()));
    });

    it('adds a row with the index substituted into both the id and the name', async () => {
        await mount('adminata-collection', CollectionController, markup);

        const index = start();

        addButton().click();
        await settle();

        expect(items()).toHaveLength(index + 1);

        const added = items()[index].querySelector('input');
        expect(added.id).toBe(`sfixture00000_variants_${index}_label`);
        expect(added.name).toBe(`sfixture00000[variants][${index}][label]`);
    });

    it('inserts the row before the add button', async () => {
        await mount('adminata-collection', CollectionController, markup);

        addButton().click();
        await settle();

        expect(addButton().previousElementSibling).toBe(items()[items().length - 1]);
    });

    it('dispatches the two names an application may listen for when a row is added', async () => {
        const { element } = await mount('adminata-collection', CollectionController, markup);

        const appended = vi.fn();
        const added = vi.fn();
        element.addEventListener('adminata-admin-append-form-element', appended);
        element.addEventListener('adminata-collection-item-added', added);

        addButton().click();
        await settle();

        expect(appended).toHaveBeenCalledOnce();
        expect(added).toHaveBeenCalledOnce();
        expect(added.mock.calls[0][0].detail.item).toBe(items()[items().length - 1]);
    });

    it('removes the row the delete button belongs to and announces it twice', async () => {
        const { element } = await mount('adminata-collection', CollectionController, markup);

        const before = start();
        const deleted = vi.fn();
        const successful = vi.fn();
        element.addEventListener('adminata-collection-item-deleted', deleted);
        element.addEventListener('adminata-collection-item-deleted-successful', successful);

        items()[0].querySelector('.adminata-collection-delete').click();
        await settle();

        expect(items()).toHaveLength(before - 1);
        expect(deleted).toHaveBeenCalledOnce();
        expect(successful).toHaveBeenCalledOnce();
    });
});
