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

import EditController from '../controllers/edit_controller.js';
import { mount, settle } from './helpers.js';

/*
 * The tabs themselves are `adminata-tabs`'; what this controller does about a tab with errors is
 * announce it: the jQuery call upstream made here became a `adminata-tabs:show` event that
 * `adminata-tabs` answers (PLAN/05 §4). The error icons still get toggled, which is what a form
 * with a validation failure depends on.
 */
const markup = `
    <form data-controller="adminata-edit" data-action="submit->adminata-edit#prepareSubmit">
        <div role="tablist">
            <a href="#tab_0" role="tab" aria-selected="true" aria-controls="tab_0" data-adminata-edit-target="tab">
                First <span data-adminata-edit-target="errorMark" hidden></span>
            </a>
            <a href="#tab_1" role="tab" aria-selected="false" aria-controls="tab_1" data-adminata-edit-target="tab">
                Second <span data-adminata-edit-target="errorMark" hidden></span>
            </a>
        </div>
        <div id="tab_0" role="tabpanel"><span class="adminata-field-error">required</span></div>
        <div id="tab_1" role="tabpanel" hidden></div>
        <input type="hidden" name="_tab" data-adminata-edit-target="tabStore">
        <button type="submit">Update</button>
    </form>
`;

const icons = () => document.querySelectorAll('[data-adminata-edit-target="errorMark"]');

describe('adminata-edit', () => {
    it('shows the error icon of the tab that has errors and hides the others', async () => {
        await mount('adminata-edit', EditController, markup);
        // The reveal waits a tick for `adminata-tabs`, which connects after the form does.
        await settle();

        expect(icons()[0].hidden).toBe(false);
        expect(icons()[1].hidden).toBe(true);
    });

    it('announces the first tab with errors for adminata-tabs to select', async () => {
        document.body.innerHTML = '';
        const shown = vi.fn();
        document.addEventListener('adminata-tabs:show', shown);

        await mount('adminata-edit', EditController, markup);
        await settle();

        expect(shown).toHaveBeenCalledOnce();
        // Stimulus's `target` option says where to dispatch, so it is the event's target.
        expect(shown.mock.calls[0][0].target.getAttribute('aria-controls')).toBe('tab_0');

        document.removeEventListener('adminata-tabs:show', shown);
    });

    it('remembers which tab was open and disables the submitters when the form is sent', async () => {
        const { element } = await mount('adminata-edit', EditController, markup);

        element.dispatchEvent(new Event('submit'));
        await settle();

        expect(document.querySelector('[data-adminata-edit-target=tabStore]').value).toBe('tab_0');

        // The disable is deferred by a tick so the browser still submits the button's value.
        await new Promise((resolve) => setTimeout(resolve, 5));
        expect(document.querySelector('button[type=submit]').disabled).toBe(true);
    });
});
