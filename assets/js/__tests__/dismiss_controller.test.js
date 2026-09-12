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

import DismissController from '../controllers/dismiss_controller.js';
import { mount, settle } from './helpers.js';

const alert = (attributes = '') => `
    <div id="wrap">
        <div class="adm-alert alert alert-success" data-controller="adminata-dismiss" ${attributes}>
            <div class="adm-alert__body">Saved.</div>
            <button type="button" id="close" data-action="click->adminata-dismiss#dismiss">Close</button>
        </div>
    </div>
`;

describe('adminata-dismiss', () => {
    it('removes the alert', async () => {
        const { element } = await mount('adminata-dismiss', DismissController, alert());

        element.querySelector('#close').click();
        await settle();

        expect(document.querySelector('.adm-alert')).toBeNull();
    });

    it('hides it instead when it is asked to keep it', async () => {
        const { element } = await mount(
            'adminata-dismiss',
            DismissController,
            alert('data-adminata-dismiss-remove-value="false"'),
        );

        element.querySelector('#close').click();
        await settle();

        const remaining = document.querySelector('.adm-alert');

        expect(remaining).not.toBeNull();
        expect(remaining.hidden).toBe(true);
    });

    it('says so before it goes', async () => {
        const { element } = await mount('adminata-dismiss', DismissController, alert());
        const dismissed = vi.fn();

        document.querySelector('#wrap').addEventListener('adminata-dismiss:dismissed', dismissed);
        element.querySelector('#close').click();
        await settle();

        expect(dismissed).toHaveBeenCalledOnce();
    });
});
