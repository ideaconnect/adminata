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

import RevisionController from '../controllers/revision_controller.js';
import { mount, settle } from './helpers.js';

/*
 * The history page is not in the 1.0 scope (backlog B-03), but the controller ships, and this is
 * the one place adminata fetches: a GET that renders a revision into a panel. It never posts —
 * adminata submits no form over fetch, in any phase (PLAN/01 J9).
 */
const markup = `
    <div data-controller="adminata-revision">
        <a href="/admin/history/1/view" data-action="adminata-revision#showPreview">Revision 1</a>
        <div data-adminata-revision-target="preview">stale</div>
    </div>
`;

const preview = () => document.querySelector('[data-adminata-revision-target=preview]');

describe('adminata-revision', () => {
    it('replaces the panel with what the request returned', async () => {
        const fetchMock = vi.fn().mockResolvedValue({ text: () => Promise.resolve('<p>diff</p>') });
        vi.stubGlobal('fetch', fetchMock);

        await mount('adminata-revision', RevisionController, markup);

        document.querySelector('a').click();
        await settle();

        expect(fetchMock).toHaveBeenCalledOnce();
        expect(preview().innerHTML).toBe('<p>diff</p>');
    });

    it('asks for the fragment, not the page, and never posts', async () => {
        const fetchMock = vi.fn().mockResolvedValue({ text: () => Promise.resolve('') });
        vi.stubGlobal('fetch', fetchMock);

        await mount('adminata-revision', RevisionController, markup);

        document.querySelector('a').click();
        await settle();

        const [, options] = fetchMock.mock.calls[0];
        expect(options.method).toBe('GET');
        expect(options.headers['X-Requested-With']).toBe('XMLHttpRequest');
    });

    it('ignores a click that did not come from a link', async () => {
        const fetchMock = vi.fn();
        vi.stubGlobal('fetch', fetchMock);

        await mount(
            'adminata-revision',
            RevisionController,
            `<div data-controller="adminata-revision">
                <button data-action="adminata-revision#showPreview">Not a link</button>
                <div data-adminata-revision-target="preview">kept</div>
            </div>`,
        );

        document.querySelector('button').click();
        await settle();

        expect(fetchMock).not.toHaveBeenCalled();
        expect(preview().innerHTML).toBe('kept');
    });
});
