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

import Config from '../core/config.js';
import StickyController from '../controllers/sticky_controller.js';
import { mount } from './helpers.js';

/*
 * The sticky action bar of an edit form. adminata keeps the window-scroll layout precisely so this
 * controller's viewport observers stay valid (PLAN/01 T4), and `use_stickyforms` still decides
 * whether it loads at all.
 */
// The real template puts this on `<body>`; a wrapper is equivalent and survives being assigned
// into `document.body.innerHTML`.
const markup = `
    <div data-controller="sonata-sticky">
        <div data-sonata-sticky-target="topNavbar" class="adm-header"></div>
        <div data-sonata-sticky-target="action" class="adm-sticky"></div>
    </div>
`;

const meta = (name, content) => {
    const element = document.createElement('meta');
    element.name = name;
    element.content = content;
    document.head.appendChild(element);
};

describe('sonata-sticky', () => {
    beforeEach(() => {
        document.head.innerHTML = '';
        Config.params = null;
    });

    it('loads only when use_stickyforms is on', () => {
        meta('sonata-config', JSON.stringify({ USE_STICKYFORMS: true }));
        expect(StickyController.shouldLoad).toBe(true);

        document.head.innerHTML = '';
        Config.params = null;
        meta('sonata-config', JSON.stringify({ USE_STICKYFORMS: false }));
        expect(StickyController.shouldLoad).toBe(false);
    });

    it('observes the bars it was given without touching the ones it was not', async () => {
        meta('sonata-config', JSON.stringify({ USE_STICKYFORMS: true }));

        const { element } = await mount('sonata-sticky', StickyController, markup);

        expect(element.querySelector('[data-sonata-sticky-target=action]')).not.toBeNull();
        expect(element.querySelector('[data-sonata-sticky-target=navbar]')).toBeNull();
    });
});
