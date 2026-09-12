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

import ModalController from '../controllers/modal_controller.js';
import { mount, settle } from './helpers.js';

/**
 * jsdom implements no `<dialog>` methods at all, so `__tests__/setup.js` supplies the smallest
 * thing that behaves like the specification. What these check is the part the controller owns: the
 * size class, where the dialog ends up, the backdrop rule and the events. The top layer, the focus
 * trap and Escape are the browser's, and the Panther test is what checks those.
 */
const dialog = (attributes = '') => `
    <div data-controller="adminata-modal" ${attributes}>
        <button type="button" id="open" data-action="click->adminata-modal#open">Open</button>
        <dialog data-adminata-modal-target="dialog">
            <button type="button" id="close" data-action="click->adminata-modal#close">Close</button>
        </dialog>
    </div>
`;

describe('adminata-modal', () => {
    it('marks the dialog and gives it the size it was told', async () => {
        const { element } = await mount(
            'adminata-modal',
            ModalController,
            dialog('data-adminata-modal-size-value="lg"'),
        );

        const target = element.querySelector('dialog');

        expect(target.classList.contains('adm-dialog')).toBe(true);
        expect(target.classList.contains('adm-dialog-lg')).toBe(true);
    });

    it('adds no size class for the default', async () => {
        const { element } = await mount('adminata-modal', ModalController, dialog());

        expect(element.querySelector('dialog').className).toBe('adm-dialog');
    });

    it('opens the dialog where it stands and says so', async () => {
        const { element } = await mount('adminata-modal', ModalController, dialog());
        const opened = vi.fn();

        element.addEventListener('adminata-modal:opened', opened);
        element.querySelector('#open').click();
        await settle();

        const target = element.querySelector('dialog');

        expect(target.open).toBe(true);
        // Not moved to the body: that would take every `data-action` inside the dialog out of this
        // controller's scope, and the top layer already escapes stacking contexts and overflow.
        expect(target.parentElement).toBe(element);
        expect(opened).toHaveBeenCalledOnce();
    });

    it('closes and says so', async () => {
        const { element } = await mount('adminata-modal', ModalController, dialog());
        const closed = vi.fn();

        element.addEventListener('adminata-modal:closed', closed);
        element.querySelector('#open').click();
        await settle();

        element.querySelector('#close').click();
        await settle();

        expect(element.querySelector('dialog').open).toBe(false);
        expect(closed).toHaveBeenCalledOnce();
    });

    it('closes when the backdrop is clicked', async () => {
        const { element } = await mount('adminata-modal', ModalController, dialog());

        element.querySelector('#open').click();
        await settle();

        const target = element.querySelector('dialog');
        target.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await settle();

        expect(target.open).toBe(false);
    });

    it('ignores the backdrop when it is not closable', async () => {
        const { element } = await mount(
            'adminata-modal',
            ModalController,
            dialog('data-adminata-modal-closable-value="false"'),
        );

        element.querySelector('#open').click();
        await settle();

        const target = element.querySelector('dialog');
        target.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await settle();

        expect(target.open).toBe(true);
    });

    it('refuses Escape when it is not closable', async () => {
        const { element } = await mount(
            'adminata-modal',
            ModalController,
            dialog('data-adminata-modal-closable-value="false"'),
        );

        element.querySelector('#open').click();
        await settle();

        const target = element.querySelector('dialog');
        const cancel = new Event('cancel', { cancelable: true });
        target.dispatchEvent(cancel);

        expect(cancel.defaultPrevented).toBe(true);
    });

    it('keeps the backdrop inert when told to, while Escape still closes', async () => {
        const { element } = await mount(
            'adminata-modal',
            ModalController,
            dialog('data-adminata-modal-backdrop-value="false"'),
        );

        element.querySelector('#open').click();
        await settle();

        const target = element.querySelector('dialog');
        target.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(target.open).toBe(true);

        const cancel = new Event('cancel', { cancelable: true });
        target.dispatchEvent(cancel);
        expect(cancel.defaultPrevented).toBe(false);
    });

    it('runs on a page whose dialog is missing', async () => {
        const { element } = await mount(
            'adminata-modal',
            ModalController,
            '<div data-controller="adminata-modal"><button data-action="click->adminata-modal#open"></button></div>',
        );

        element.querySelector('button').click();
        await settle();

        expect(element.querySelector('dialog')).toBeNull();
    });
});
