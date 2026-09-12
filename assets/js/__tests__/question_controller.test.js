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
import QuestionController from '../controllers/question_controller.js';
import { mount, settle } from './helpers.js';

/** The layout's question dialog, as `Core/question_dialog.html.twig` renders it. */
const dialog = `
    <dialog id="adminata-question-dialog" aria-labelledby="adminata-question-dialog-title"
            data-controller="adminata-modal" data-adminata-modal-target="dialog"
            data-adminata-modal-size-value="sm" data-adminata-modal-backdrop-value="false">
        <div class="adm-dialog__header"><h1 id="adminata-question-dialog-title">Are you sure ?</h1></div>
        <div class="adm-dialog__body"><p data-adminata-question-text></p></div>
        <div class="adm-dialog__footer">
            <button type="button" id="cancel" data-adminata-question-cancel data-action="click->adminata-modal#close">Cancel</button>
            <button type="button" id="confirm" data-adminata-question-confirm>Confirm</button>
        </div>
    </dialog>
`;

const values = (extra = '') =>
    `data-controller="adminata-question" data-adminata-question-text-value="Delete it?" ${extra}`;

const buttonInForm = (extra = '') => `
    <form id="form" action="/delete" method="post">
        <input type="hidden" name="_token" value="t">
        <button type="submit" id="trigger" name="which" value="this" ${values(extra)}
                data-action="click->adminata-question#ask">Delete</button>
    </form>
    ${dialog}
`;

const formTrigger = `
    <form id="form" action="/delete" method="post" ${values()} data-action="submit->adminata-question#ask">
        <button type="submit" id="submit">Delete</button>
    </form>
    ${dialog}
`;

const linkTrigger = `
    <a href="https://example.test/archive" id="trigger" ${values()} data-action="click->adminata-question#ask">Archive</a>
    ${dialog}
`;

/** Both controllers, the way a page has them: the question on the trigger, `adminata-modal` on the dialog. */
async function mountPage(html) {
    const { application, element } = await mount('adminata-question', QuestionController, html);
    application.register('adminata-modal', ModalController);
    await settle();

    return { application, element, dialog: document.getElementById('adminata-question-dialog') };
}

describe('adminata-question', () => {
    it('stops the click, fills the dialog and opens it through adminata-modal', async () => {
        const { element, dialog } = await mountPage(
            buttonInForm(
                'data-adminata-question-title-value="Delete?" data-adminata-question-confirm-value="Yes, delete" data-adminata-question-cancel-value="Keep"',
            ),
        );
        const opened = vi.fn();
        const submitted = vi.fn((event) => event.preventDefault());
        dialog.addEventListener('adminata-modal:opened', opened);
        document.getElementById('form').addEventListener('submit', submitted);

        element.click();
        await settle();

        expect(dialog.open).toBe(true);
        expect(opened).toHaveBeenCalledTimes(1);
        expect(submitted).not.toHaveBeenCalled();
        expect(document.getElementById('adminata-question-dialog-title').textContent).toBe('Delete?');
        expect(dialog.querySelector('[data-adminata-question-text]').textContent).toBe('Delete it?');
        expect(document.getElementById('confirm').textContent).toBe('Yes, delete');
        expect(document.getElementById('cancel').textContent).toBe('Keep');
    });

    it('sets the question as text, never as markup', async () => {
        const { element, dialog } = await mountPage(
            buttonInForm().replace('Delete it?', 'Delete &lt;b&gt;this&lt;/b&gt;?'),
        );

        element.click();
        await settle();

        expect(dialog.querySelector('[data-adminata-question-text]').textContent).toBe('Delete <b>this</b>?');
        expect(dialog.querySelector('[data-adminata-question-text] b')).toBeNull();
    });

    it('puts the rendered labels back for a question that names none', async () => {
        const { element } = await mountPage(
            buttonInForm(
                'data-adminata-question-title-value="Delete?" data-adminata-question-confirm-value="Yes"',
            ),
        );

        element.click();
        await settle();
        document.getElementById('cancel').click();
        await settle();

        // A second, plainer question on the same page.
        element.removeAttribute('data-adminata-question-title-value');
        element.removeAttribute('data-adminata-question-confirm-value');
        await settle();
        element.click();
        await settle();

        expect(document.getElementById('adminata-question-dialog-title').textContent).toBe('Are you sure ?');
        expect(document.getElementById('confirm').textContent).toBe('Confirm');
    });

    it('submits the form with the button as its submitter once confirmed, and says so', async () => {
        const { element, dialog } = await mountPage(buttonInForm());
        const form = document.getElementById('form');
        const requestSubmit = vi.spyOn(form, 'requestSubmit').mockImplementation(() => {});
        const confirmed = vi.fn();
        element.addEventListener('adminata-question:confirmed', confirmed);

        element.click();
        await settle();
        document.getElementById('confirm').click();
        await settle();

        expect(dialog.open).toBe(false);
        expect(confirmed).toHaveBeenCalledTimes(1);
        expect(requestSubmit).toHaveBeenCalledWith(element);
    });

    it('lets a listener take over: a prevented confirmed event submits nothing', async () => {
        const { element } = await mountPage(buttonInForm());
        const requestSubmit = vi
            .spyOn(document.getElementById('form'), 'requestSubmit')
            .mockImplementation(() => {});
        element.addEventListener('adminata-question:confirmed', (event) => event.preventDefault());

        element.click();
        await settle();
        document.getElementById('confirm').click();
        await settle();

        expect(requestSubmit).not.toHaveBeenCalled();
    });

    it('does nothing but say so when the dialog closes any other way', async () => {
        const { element, dialog } = await mountPage(buttonInForm());
        const requestSubmit = vi
            .spyOn(document.getElementById('form'), 'requestSubmit')
            .mockImplementation(() => {});
        const cancelled = vi.fn();
        const confirmed = vi.fn();
        element.addEventListener('adminata-question:cancelled', cancelled);
        element.addEventListener('adminata-question:confirmed', confirmed);

        element.click();
        await settle();
        document.getElementById('cancel').click();
        await settle();

        expect(dialog.open).toBe(false);
        expect(cancelled).toHaveBeenCalledTimes(1);
        expect(confirmed).not.toHaveBeenCalled();
        expect(requestSubmit).not.toHaveBeenCalled();

        // The confirm button no longer belongs to a question: a later click on it is inert.
        document.getElementById('confirm').click();
        await settle();
        expect(confirmed).not.toHaveBeenCalled();
    });

    it('lets Escape cancel: the dialog closes and cancelled goes out', async () => {
        const { element, dialog } = await mountPage(buttonInForm());
        const cancelled = vi.fn();
        element.addEventListener('adminata-question:cancelled', cancelled);

        element.click();
        await settle();

        // What the polyfilled dialog does for Escape: `cancel`, then `close` unless prevented.
        const cancel = new Event('cancel', { cancelable: true });
        dialog.dispatchEvent(cancel);
        expect(cancel.defaultPrevented).toBe(false);
        dialog.close();
        await settle();

        expect(dialog.open).toBe(false);
        expect(cancelled).toHaveBeenCalledTimes(1);
    });

    it('hands a form its submitting button back, so its name and value still count', async () => {
        const { element, dialog } = await mountPage(formTrigger);
        const button = document.getElementById('submit');
        const requestSubmit = vi.spyOn(element, 'requestSubmit').mockImplementation(() => {});

        element.dispatchEvent(
            new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: button }),
        );
        await settle();
        expect(dialog.open).toBe(true);

        document.getElementById('confirm').click();
        await settle();

        expect(requestSubmit).toHaveBeenCalledWith(button);
    });

    it('asks again after a confirmed submission that the form itself refused', async () => {
        const { element, dialog } = await mountPage(formTrigger);
        const submit = () =>
            element.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true }));

        submit();
        await settle();
        expect(dialog.open).toBe(true);

        // The follow-through's requestSubmit() runs the form's validation first; a required
        // field that is empty by then means no `submit` event — and the guard, set for that
        // event, must not be left waiting for it.
        const required = document.createElement('input');
        required.required = true;
        required.name = 'reason';
        element.append(required);

        document.getElementById('confirm').click();
        await settle();
        expect(dialog.open).toBe(false);

        submit();
        await settle();

        expect(dialog.open).toBe(true);
    });

    it('asks on a form and lets its own submission through once confirmed', async () => {
        const { element, dialog } = await mountPage(formTrigger);
        const submits = [];
        element.addEventListener('submit', (event) => {
            submits.push(event.defaultPrevented);
            event.preventDefault();
        });
        // jsdom's requestSubmit() fires `submit` like a browser's; nothing navigates under it.

        element.requestSubmit();
        await settle();

        expect(dialog.open).toBe(true);
        expect(submits).toEqual([true]);

        document.getElementById('confirm').click();
        await settle();

        // The second submit is the follow-through: the controller let it pass.
        expect(submits).toEqual([true, false]);
        expect(dialog.open).toBe(false);
    });

    it('follows a link once confirmed', async () => {
        const { element } = await mountPage(linkTrigger);
        // jsdom's `location` is read only and navigating it is unimplemented, so it is replaced
        // for this test the way the row-link suite does, and put back afterwards.
        const location = window.location;
        delete window.location;
        window.location = { assign: vi.fn() };

        try {
            element.click();
            await settle();
            document.getElementById('confirm').click();
            await settle();

            expect(window.location.assign).toHaveBeenCalledWith('https://example.test/archive');
        } finally {
            window.location = location;
        }
    });

    it('opens the dialog itself when no adminata-modal drives it', async () => {
        const { element } = await mount(
            'adminata-question',
            QuestionController,
            buttonInForm().replace('data-controller="adminata-modal" ', ''),
        );
        const dialog = document.getElementById('adminata-question-dialog');

        element.click();
        await settle();

        expect(dialog.open).toBe(true);
    });

    it('refuses to ask in a dialog that is not there', async () => {
        const { application, element } = await mountPage(
            buttonInForm('data-adminata-question-target-value="nowhere"'),
        );
        const controller = application.getControllerForElementAndIdentifier(element, 'adminata-question');

        expect(() => controller.ask()).toThrow('there is no <dialog id="nowhere">');
    });
});
