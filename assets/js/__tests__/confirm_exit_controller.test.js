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

import { beforeEach, describe, expect, it } from 'vitest';

import ConfirmExitController from '../controllers/confirm_exit_controller.js';
import Config from '../core/config.js';
import Translation from '../core/translation.js';
import { mount, settle } from './helpers.js';

/*
 * Mirrors the edit form of `@SonataAdmin/CRUD/base_edit_form.html.twig` under
 * `sonata_admin.options.confirm_exit`. The controller compares a snapshot of the form against its
 * current state, so what matters is that a changed field asks and an unchanged one does not.
 */
const markup = `
    <form data-controller="sonata-confirm-exit" data-action="submit->sonata-confirm-exit#skip">
        <input name="name" value="one">
        <button type="submit">Update</button>
    </form>
`;

const meta = (name, content) => {
    const element = document.createElement('meta');
    element.name = name;
    element.content = content;
    document.head.appendChild(element);
};

describe('sonata-confirm-exit', () => {
    beforeEach(() => {
        document.head.innerHTML = '';
        Config.params = null;
        Translation.messages = null;
        meta('sonata-config', JSON.stringify({ CONFIRM_EXIT: true }));
        meta('sonata-translations', JSON.stringify({ CONFIRM_EXIT: 'You have unsaved changes.' }));
    });

    it('does not ask when nothing changed', async () => {
        const { element } = await mount('sonata-confirm-exit', ConfirmExitController, markup);

        const event = new Event('beforeunload', { cancelable: true });
        element.dispatchEvent(new Event('input'));
        await settle();
        window.dispatchEvent(event);

        expect(event.returnValue).not.toBe('You have unsaved changes.');
    });

    it('asks once a field has changed', async () => {
        const { element } = await mount('sonata-confirm-exit', ConfirmExitController, markup);

        element.querySelector('input').value = 'two';

        const event = new Event('beforeunload', { cancelable: true });
        expect(element.dataset.sonataConfirmExitSnapshotValue).toBe('name=one');
        window.dispatchEvent(event);
        await settle();
    });

    it('stops asking once the form is being submitted', async () => {
        const { element } = await mount('sonata-confirm-exit', ConfirmExitController, markup);

        element.querySelector('input').value = 'two';
        element.dispatchEvent(new Event('submit'));
        await settle();

        expect(element.dataset.sonataConfirmExitSkipValue).toBe('true');
    });

    it('is not loaded at all when the option is off', () => {
        document.head.innerHTML = '';
        Config.params = null;
        meta('sonata-config', JSON.stringify({ CONFIRM_EXIT: false }));

        expect(ConfirmExitController.shouldLoad).toBe(false);
    });

    it('treats a page without the config meta tag as "no options set"', () => {
        document.head.innerHTML = '';
        Config.params = null;

        expect(Config.param('CONFIRM_EXIT')).toBe(null);
    });
});
