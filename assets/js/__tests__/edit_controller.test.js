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
 * Tabs are not in the 1.0 scope, so the only thing this controller can do about a tab with errors
 * is announce it: the jQuery call upstream made here became a `sonata-tabs:show` event that
 * nothing listens for yet (PLAN/05 §2). The error icons still get toggled, which is what a form
 * with a validation failure depends on.
 */
const markup = `
    <form data-controller="sonata-edit" data-action="submit->sonata-edit#prepareSubmit">
        <ul>
            <li class="active">
                <a href="#tab_0" aria-controls="tab_0" data-sonata-edit-target="tab">
                    First <span class="has-errors" hidden></span>
                </a>
            </li>
            <li>
                <a href="#tab_1" aria-controls="tab_1" data-sonata-edit-target="tab">
                    Second <span class="has-errors" hidden></span>
                </a>
            </li>
        </ul>
        <div id="tab_0"><span class="sonata-ba-field-error">required</span></div>
        <div id="tab_1"></div>
        <input type="hidden" name="_tab" data-sonata-edit-target="tabStore">
        <button type="submit">Update</button>
    </form>
`;

const icons = () => document.querySelectorAll('.has-errors');

describe('sonata-edit', () => {
    it('shows the error icon of the tab that has errors and hides the others', async () => {
        await mount('sonata-edit', EditController, markup);

        expect(icons()[0].hidden).toBe(false);
        expect(icons()[1].hidden).toBe(true);
    });

    it('announces the first tab with errors instead of reaching for a tab plugin', async () => {
        document.body.innerHTML = '';
        const shown = vi.fn();
        document.addEventListener('sonata-tabs:show', shown);

        await mount('sonata-edit', EditController, markup);

        expect(shown).toHaveBeenCalledOnce();
        // Stimulus's `target` option says where to dispatch, so it is the event's target.
        expect(shown.mock.calls[0][0].target.getAttribute('aria-controls')).toBe('tab_0');

        document.removeEventListener('sonata-tabs:show', shown);
    });

    it('remembers which tab was open and disables the submitters when the form is sent', async () => {
        const { element } = await mount('sonata-edit', EditController, markup);

        element.dispatchEvent(new Event('submit'));
        await settle();

        expect(document.querySelector('[data-sonata-edit-target=tabStore]').value).toBe('tab_0');

        // The disable is deferred by a tick so the browser still submits the button's value.
        await new Promise((resolve) => setTimeout(resolve, 5));
        expect(document.querySelector('button[type=submit]').disabled).toBe(true);
    });
});
