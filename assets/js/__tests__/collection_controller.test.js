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
import { mount, settle } from './helpers.js';

/*
 * Mirrors `sonata_type_native_collection_widget` in
 * `@SonataAdmin/Form/form_admin_fields.html.twig`: a container carrying `data-prototype` with the
 * placeholder `__name__` in both the id and the name of every field. M4 rewrites that block, but
 * the prototype contract — and the four event names below, which applications listen for — cannot
 * change without a major release (PLAN/02 §9).
 */
const prototype =
    '<div data-sonata-collection-target="item">' +
    '<input id="admin_items___name___label" name="admin[items][__name__][label]">' +
    '<button data-action="sonata-collection#delete">Delete</button>' +
    '</div>';

const markup = `
    <div id="admin_items"
         data-controller="sonata-collection"
         data-prototype='${prototype}'
         data-prototype-name="__name__"
         data-sonata-collection-num-items-value="1">
        <div data-sonata-collection-target="item">
            <input id="admin_items_0_label" name="admin[items][0][label]">
            <button data-action="sonata-collection#delete">Delete</button>
        </div>
        <button data-action="sonata-collection#add">Add</button>
    </div>
`;

const addButton = () => document.querySelector('[data-action="sonata-collection#add"]');
const items = () => document.querySelectorAll('[data-sonata-collection-target=item]');

describe('sonata-collection', () => {
    it('counts the rows it starts with', async () => {
        const { element } = await mount('sonata-collection', CollectionController, markup);

        expect(element.dataset.sonataCollectionNumItemsValue).toBe('1');
    });

    it('adds a row with the index substituted into both the id and the name', async () => {
        await mount('sonata-collection', CollectionController, markup);

        addButton().click();
        await settle();

        expect(items()).toHaveLength(2);

        const added = items()[1].querySelector('input');
        expect(added.id).toBe('admin_items_1_label');
        expect(added.name).toBe('admin[items][1][label]');
    });

    it('inserts the row before the add button', async () => {
        await mount('sonata-collection', CollectionController, markup);

        addButton().click();
        await settle();

        expect(addButton().previousElementSibling).toBe(items()[1]);
    });

    it('dispatches the two names an application may listen for when a row is added', async () => {
        const { element } = await mount('sonata-collection', CollectionController, markup);

        const appended = vi.fn();
        const added = vi.fn();
        element.addEventListener('sonata-admin-append-form-element', appended);
        element.addEventListener('sonata-collection-item-added', added);

        addButton().click();
        await settle();

        expect(appended).toHaveBeenCalledOnce();
        expect(added).toHaveBeenCalledOnce();
        expect(added.mock.calls[0][0].detail.item).toBe(items()[1]);
    });

    it('removes the row the delete button belongs to and announces it twice', async () => {
        const { element } = await mount('sonata-collection', CollectionController, markup);

        const deleted = vi.fn();
        const successful = vi.fn();
        element.addEventListener('sonata-collection-item-deleted', deleted);
        element.addEventListener('sonata-collection-item-deleted-successful', successful);

        items()[0].querySelector('button').click();
        await settle();

        expect(items()).toHaveLength(0);
        expect(deleted).toHaveBeenCalledOnce();
        expect(successful).toHaveBeenCalledOnce();
    });
});
