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

import ModalController from '../controllers/modal_controller.js';
import ModalTriggerController from '../controllers/modal_trigger_controller.js';
import { mount, settle } from './helpers.js';

const page = (trigger, dialog = '') => `
    <div id="wrap">
        <template id="note-7"><p><strong>Formatted</strong> note</p></template>
        ${trigger}
        <dialog id="sonata-dialog" aria-labelledby="sonata-dialog-title"
                data-controller="sonata-modal" data-sonata-modal-target="dialog">
            <div class="adm-dialog__header"><h2 id="sonata-dialog-title">Old title</h2></div>
            <div class="adm-dialog__body"><p>Old body</p></div>
        </dialog>
        ${dialog}
    </div>
`;

const trigger = (values) =>
    `<a href="#" id="trigger" data-controller="sonata-modal-trigger"
        data-action="click->sonata-modal-trigger#open" ${values}>Show</a>`;

/** Both controllers, the way a page has them: the trigger anywhere, `sonata-modal` on the dialog. */
async function mountPage(html) {
    const { application, element } = await mount('sonata-modal-trigger', ModalTriggerController, html);
    application.register('sonata-modal', ModalController);
    await settle();

    return { application, element, dialog: document.getElementById('sonata-dialog') };
}

describe('sonata-modal-trigger', () => {
    it('fills the shared dialog and opens it, setting text as text', async () => {
        const { element, dialog } = await mountPage(
            page(
                trigger(
                    'data-sonata-modal-trigger-title-value="Note #7" data-sonata-modal-trigger-text-value="Plain &lt;b&gt;text&lt;/b&gt;"',
                ),
            ),
        );

        element.click();
        await settle();

        expect(dialog.open).toBe(true);
        expect(document.getElementById('sonata-dialog-title').textContent).toBe('Note #7');
        expect(dialog.querySelector('.adm-dialog__body').textContent).toBe('Plain <b>text</b>');
        expect(dialog.querySelector('.adm-dialog__body b')).toBeNull();
    });

    it("copies the named element's markup into the body", async () => {
        const { element, dialog } = await mountPage(
            page(
                trigger(
                    'data-sonata-modal-trigger-title-value="Note #7" data-sonata-modal-trigger-content-value="note-7"',
                ),
            ),
        );

        element.click();
        await settle();

        expect(dialog.open).toBe(true);
        expect(dialog.querySelector('.adm-dialog__body strong')?.textContent).toBe('Formatted');
    });

    it('opens through sonata-modal, which announces it, and keeps the title it has when given none', async () => {
        const { element, dialog } = await mountPage(
            page(trigger('data-sonata-modal-trigger-text-value="x"')),
        );
        const events = [];
        dialog.addEventListener('sonata-modal:opened', () => events.push('opened'));

        element.click();
        await settle();

        expect(events).toEqual(['opened']);
        expect(document.getElementById('sonata-dialog-title').textContent).toBe('Old title');
    });

    it('lends the dialog its size for that opening only', async () => {
        const { element, dialog } = await mountPage(
            page(
                trigger('data-sonata-modal-trigger-text-value="x" data-sonata-modal-trigger-size-value="lg"'),
            ),
        );

        element.click();
        await settle();
        expect(dialog.classList.contains('adm-dialog-lg')).toBe(true);

        dialog.close();
        await settle();
        expect(dialog.classList.contains('adm-dialog-lg')).toBe(false);
    });

    it('opens another dialog when told which', async () => {
        const other = `
            <dialog id="other" aria-labelledby="other-title"
                    data-controller="sonata-modal" data-sonata-modal-target="dialog">
                <h2 id="other-title"></h2>
                <div class="adm-dialog__body"></div>
            </dialog>`;
        const { element, dialog } = await mountPage(
            page(
                trigger(
                    'data-sonata-modal-trigger-target-value="other" data-sonata-modal-trigger-title-value="Elsewhere" data-sonata-modal-trigger-text-value="x"',
                ),
                other,
            ),
        );

        element.click();
        await settle();

        expect(dialog.open).toBe(false);
        expect(document.getElementById('other').open).toBe(true);
        expect(document.getElementById('other-title').textContent).toBe('Elsewhere');
    });

    it('still opens a dialog that has no sonata-modal on it', async () => {
        const bare = `<dialog id="bare" aria-labelledby="bare-title"><h2 id="bare-title"></h2><div class="adm-dialog__body"></div></dialog>`;
        const { element } = await mountPage(
            page(
                trigger(
                    'data-sonata-modal-trigger-target-value="bare" data-sonata-modal-trigger-text-value="x"',
                ),
                bare,
            ),
        );

        element.click();
        await settle();

        expect(document.getElementById('bare').open).toBe(true);
    });

    it('says which dialog or content it could not find', async () => {
        const { application, element } = await mountPage(
            page(trigger('data-sonata-modal-trigger-target-value="nowhere"')),
        );
        const controller = application.getControllerForElementAndIdentifier(element, 'sonata-modal-trigger');

        expect(() => controller.open()).toThrow('there is no <dialog id="nowhere">');

        controller.targetValue = 'sonata-dialog';
        controller.contentValue = 'missing';
        expect(() => controller.open()).toThrow('there is no element "missing"');
    });
});
