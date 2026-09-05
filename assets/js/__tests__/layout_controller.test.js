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

import LayoutController from '../controllers/layout_controller.js';
import { mount, settle } from './helpers.js';

/**
 * jsdom has no layout, so `matchMedia` always reports `false` and never fires. These tests drive
 * it: `wide()` puts the controller above its breakpoint, `narrow()` below, and `cross()` fires the
 * change the browser would fire on a resize.
 */
let listeners = [];

function media(matches) {
    listeners = [];

    vi.stubGlobal('matchMedia', (query) => ({
        matches,
        media: query,
        addEventListener: (_, listener) => listeners.push(listener),
        removeEventListener: (_, listener) => {
            listeners = listeners.filter((registered) => registered !== listener);
        },
    }));
}

const wide = () => media(true);
const narrow = () => media(false);

const SHELL = `
    <div data-controller="sonata-layout" data-sonata-layout-cookie-name-value="sonata_sidebar_hide">
        <aside data-sonata-layout-target="sidebar"></aside>
        <div data-sonata-layout-target="overlay" data-action="click->sonata-layout#closeSidebar" hidden></div>
        <button type="button" data-sonata-layout-target="toggle" data-action="click->sonata-layout#toggleSidebar" aria-expanded="false"></button>
        <button type="button" data-sonata-layout-target="collapseOnly" data-action="click->sonata-layout#toggleCollapsed" aria-expanded="true"></button>
        <div data-sonata-layout-target="content"></div>
    </div>
`;

beforeEach(() => {
    document.cookie = 'sonata_sidebar_hide=; path=/; max-age=0';
});

describe('sonata-layout', () => {
    it('writes the state onto its own element', async () => {
        wide();
        const { element } = await mount('sonata-layout', LayoutController, SHELL);

        expect(element.dataset.sidebar).toBe('expanded');
        expect(element.dataset.sidebarMobile).toBe('closed');
    });

    it('seeds itself from the value the server rendered', async () => {
        wide();
        const { element } = await mount(
            'sonata-layout',
            LayoutController,
            '<div data-controller="sonata-layout" data-sonata-layout-collapsed-value="true"></div>',
        );

        expect(element.dataset.sidebar).toBe('collapsed');
    });

    it('collapses the rail above the breakpoint and remembers it in the cookie', async () => {
        wide();
        const { element } = await mount('sonata-layout', LayoutController, SHELL);

        element.querySelector('[data-sonata-layout-target="toggle"]').click();
        await settle();

        expect(element.dataset.sidebar).toBe('collapsed');
        expect(element.dataset.sidebarMobile).toBe('closed');
        expect(document.cookie).toContain('sonata_sidebar_hide=1');

        element.querySelector('[data-sonata-layout-target="toggle"]').click();
        await settle();

        expect(element.dataset.sidebar).toBe('expanded');
        expect(document.cookie).not.toContain('sonata_sidebar_hide=1');
    });

    it('opens the drawer below the breakpoint without touching the cookie', async () => {
        narrow();
        const { element } = await mount('sonata-layout', LayoutController, SHELL);

        element.querySelector('[data-sonata-layout-target="toggle"]').click();
        await settle();

        expect(element.dataset.sidebarMobile).toBe('open');
        expect(element.dataset.sidebar).toBe('expanded');
        expect(document.cookie).not.toContain('sonata_sidebar_hide=1');
    });

    it('shows the overlay and takes the content out of reach while the drawer is open', async () => {
        narrow();
        const { element } = await mount('sonata-layout', LayoutController, SHELL);
        const overlay = element.querySelector('[data-sonata-layout-target="overlay"]');
        const content = element.querySelector('[data-sonata-layout-target="content"]');

        expect(overlay.hidden).toBe(true);
        expect(content.inert).toBe(false);

        element.querySelector('[data-sonata-layout-target="toggle"]').click();
        await settle();

        expect(overlay.hidden).toBe(false);
        expect(content.inert).toBe(true);
    });

    it('closes the drawer when the layout grows past the breakpoint', async () => {
        narrow();
        const { element } = await mount('sonata-layout', LayoutController, SHELL);

        element.querySelector('[data-sonata-layout-target="toggle"]').click();
        await settle();
        expect(element.dataset.sidebarMobile).toBe('open');

        listeners.forEach((listener) => listener({ matches: true }));
        await settle();

        expect(element.dataset.sidebarMobile).toBe('closed');
    });

    it('keeps aria-expanded on both toggles pointed at what each one does', async () => {
        wide();
        const { element } = await mount('sonata-layout', LayoutController, SHELL);
        const drawer = element.querySelector('[data-sonata-layout-target="toggle"]');
        const rail = element.querySelector('[data-sonata-layout-target="collapseOnly"]');

        expect(drawer.getAttribute('aria-expanded')).toBe('false');
        expect(rail.getAttribute('aria-expanded')).toBe('true');

        rail.click();
        await settle();

        expect(rail.getAttribute('aria-expanded')).toBe('false');
        expect(drawer.getAttribute('aria-expanded')).toBe('false');
    });

    it('announces every change', async () => {
        wide();
        const { element } = await mount('sonata-layout', LayoutController, SHELL);
        const seen = [];

        element.addEventListener('sonata-layout:sidebar-changed', (event) => seen.push(event.detail));

        element.querySelector('[data-sonata-layout-target="collapseOnly"]').click();
        await settle();

        expect(seen).toEqual([{ collapsed: true, mobileOpen: false }]);
    });

    it('runs on a page that has none of its targets', async () => {
        wide();
        const { element } = await mount(
            'sonata-layout',
            LayoutController,
            '<div data-controller="sonata-layout"></div>',
        );

        element.querySelector; // the element exists, and connecting did not throw
        expect(element.dataset.sidebar).toBe('expanded');
    });
});
