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

import { beforeEach, describe, expect, it, vi } from 'vitest';

import PerPageController from '../controllers/per_page_controller.js';
import { mount, settle } from './helpers.js';

/*
 * Markup mirrors `@Adminata/CRUD/Pager/base_results.html.twig`: a select whose option values
 * are the URLs to navigate to. M3 rewrites that template; the shape asserted here is the
 * contract it has to keep.
 */
const markup = `
    <form>
        <select data-controller="adminata-per-page" data-action="change->adminata-per-page#reload">
            <option value="/admin/list?per_page=25" selected>25</option>
            <option value="/admin/list?per_page=50">50</option>
        </select>
        <button type="submit">Filter</button>
    </form>
`;

describe('adminata-per-page', () => {
    beforeEach(() => {
        delete window.top.location;
        window.top.location = { href: '' };
    });

    it('navigates to the URL of the chosen option', async () => {
        const { element } = await mount('adminata-per-page', PerPageController, markup);

        element.selectedIndex = 1;
        element.dispatchEvent(new Event('change'));
        await settle();

        expect(window.top.location.href).toBe('/admin/list?per_page=50');
    });

    it('disables the submit buttons so the page is not submitted twice', async () => {
        const { element } = await mount('adminata-per-page', PerPageController, markup);

        element.dispatchEvent(new Event('change'));
        await settle();

        expect(document.querySelector('button[type=submit]').disabled).toBe(true);
    });

    it('does nothing until the selection changes', async () => {
        await mount('adminata-per-page', PerPageController, markup);

        expect(window.top.location.href).toBe('');
        expect(vi.isMockFunction(window.fetch)).toBe(false);
    });
});
