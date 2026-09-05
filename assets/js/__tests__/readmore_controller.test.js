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

import ReadmoreController from '../controllers/readmore_controller.js';
import { mount, settle } from './helpers.js';

/*
 * Mirrors what `RenderElementRuntime::renderRelationElement()` emits for a truncated cell. The
 * `truncated` and `expanded` class names are asserted by the admin bundle's own render tests, so
 * they are part of the contract, not styling.
 */
const markup = `
    <div data-controller="sonata-readmore"
         data-sonata-readmore-collapsed-height-value="40"
         data-sonata-readmore-more-text-value="Read more"
         data-sonata-readmore-less-text-value="Read less">
        <div data-sonata-readmore-target="content">A long value</div>
        <button data-sonata-readmore-target="button" data-action="sonata-readmore#toggle"></button>
    </div>
`;

const content = () => document.querySelector('[data-sonata-readmore-target=content]');
const button = () => document.querySelector('[data-sonata-readmore-target=button]');

/** jsdom reports every height as 0, so the measurements have to be declared. */
const measure = (scrollHeight, clientHeight) => {
    Object.defineProperty(content(), 'scrollHeight', { configurable: true, value: scrollHeight });
    Object.defineProperty(content(), 'clientHeight', { configurable: true, value: clientHeight });
};

describe('sonata-readmore', () => {
    it('collapses the content to the configured height and offers the more text', async () => {
        await mount('sonata-readmore', ReadmoreController, markup);

        expect(content().style.maxHeight).toBe('40px');
        expect(button().innerHTML).toBe('Read more');
    });

    it('marks the content truncated only when it overflows', async () => {
        await mount('sonata-readmore', ReadmoreController, markup);

        measure(120, 40);
        ResizeObserver.instances.at(0).trigger(content());
        expect(content().classList.contains('truncated')).toBe(true);

        measure(40, 40);
        ResizeObserver.instances.at(0).trigger(content());
        expect(content().classList.contains('truncated')).toBe(false);
    });

    it('swaps the button text as it expands and collapses', async () => {
        await mount('sonata-readmore', ReadmoreController, markup);

        button().click();
        await settle();
        expect(content().classList.contains('expanded')).toBe(true);
        expect(button().innerHTML).toBe('Read less');

        button().click();
        await settle();
        expect(content().classList.contains('expanded')).toBe(false);
        expect(button().innerHTML).toBe('Read more');
    });

    it('stops observing when the element leaves the page', async () => {
        const { element } = await mount('sonata-readmore', ReadmoreController, markup);

        // The controller shares one module-level ResizeObserver across every instance, so what
        // matters is whether this element is still in it — not how many are.
        const observer = ResizeObserver.instances.at(0);
        const observed = content();
        expect(observer.elements.has(observed)).toBe(true);

        element.remove();
        await settle();

        expect(observer.elements.has(observed)).toBe(false);
    });
});
