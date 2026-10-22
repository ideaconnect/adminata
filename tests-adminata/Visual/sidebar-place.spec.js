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

import { expect, test } from '@playwright/test';

import { open } from './support/demo.js';

/*
 * The sidebar keeps its place across page loads (`adminata_sidebar_place`), in a real browser
 * and from the first frame: the unit suite (assets/js/__tests__/sidebar_place.test.js) runs the
 * script in jsdom, which neither lays out nor paints, and the whole point is what the first
 * painted frame shows.
 *
 * Every frame of the next page is recorded from the document's start, through
 * `requestAnimationFrame`, which runs right before each paint: the menu's offset and the box's top
 * edge. Wide only, where the sidebar is on screen; in a short window, so that the demo's menu,
 * all groups open, overflows its box.
 */

const VIEWPORT = { width: 1280, height: 420 };

/** Records the menu box's offset and top edge on every frame of the page about to load. */
async function recordFrames(page) {
    await page.addInitScript(() => {
        window.__sidebarFrames = [];

        const tick = () => {
            const box = document.querySelector('.adm-sidebar-nav');

            if (null !== box) {
                window.__sidebarFrames.push({
                    offset: box.scrollTop,
                    top: Math.round(box.getBoundingClientRect().top),
                });
            }

            if (window.__sidebarFrames.length < 20) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    });
}

/** Opens every group of the menu, which `adminata-menu` remembers for the next page. */
async function openEveryGroup(page) {
    for (const toggle of await page.locator('.adm-sidebar-nav [data-adminata-menu-target~="toggle"]').all()) {
        if ('true' !== (await toggle.getAttribute('aria-expanded'))) {
            await toggle.click();
        }
    }

    // The groups slide open; their final height is what the box scrolls through.
    await page.waitForFunction(() => null === document.querySelector('.adm-sidebar-nav [data-sliding]'));
}

test.describe('sidebar-place', () => {
    test.beforeEach(async ({ page }, testInfo) => {
        test.skip(!testInfo.project.name.endsWith('-wide'), 'The sidebar is a drawer below 1024px.');

        await page.setViewportSize(VIEWPORT);
    });

    test('a click in a scrolled menu leaves the menu where it was, from the first frame', async ({
        page,
    }) => {
        await open(page, '/admin/dashboard');
        await openEveryGroup(page);

        const box = page.locator('.adm-sidebar-nav');
        const room = await box.evaluate((element) => element.scrollHeight - element.clientHeight);
        expect(
            room,
            'The menu does not overflow its box; the window is too tall for this test.',
        ).toBeGreaterThan(80);

        // Scrolled to the bottom, so the last entry is in view and the top ones are not.
        await box.evaluate((element) => {
            element.scrollTop = element.scrollHeight;
        });
        const before = await box.evaluate((element) => element.scrollTop);
        const target = page.locator('.adm-sidebar-nav nav a[href]').last();
        const name = (await target.innerText()).trim();

        await recordFrames(page);
        await Promise.all([page.waitForEvent('load'), target.click()]);
        await page.evaluate(() => document.fonts.ready);

        const frames = await page.evaluate(() => window.__sidebarFrames);
        expect(frames.length).toBeGreaterThan(0);

        // Every frame, the first painted one included, shows the menu where the click left it.
        for (const frame of frames) {
            expect(
                Math.abs(frame.offset - before),
                `a frame showed the menu at ${frame.offset}, not ${before}`,
            ).toBeLessThanOrEqual(1);
        }

        // The box never moved: the filter field above it was there from the first frame.
        expect(new Set(frames.map((frame) => frame.top)).size).toBe(1);

        // And the entry clicked is the current one, in view.
        const current = page
            .locator('.adm-sidebar-nav nav')
            .locator('.menu-item-active, .menu-dropdown-item-active')
            .last();
        await expect(current).toHaveText(name);
        await expect(current).toBeInViewport();
    });

    test('a page opened with nothing stored scrolls just far enough to show its entry', async ({
        page,
        context,
    }) => {
        await open(page, '/admin/dashboard');
        await openEveryGroup(page);

        // The last entry of the menu, opened in a new tab: no stored place there.
        const href = await page.locator('.adm-sidebar-nav nav a[href]').last().getAttribute('href');
        const tab = await context.newPage();
        await tab.setViewportSize(VIEWPORT);
        await recordFrames(tab);
        await tab.goto(href, { waitUntil: 'load' });

        const frames = await tab.evaluate(() => window.__sidebarFrames);
        const box = tab.locator('.adm-sidebar-nav');
        const bottom = await box.evaluate((element) => element.scrollHeight - element.clientHeight);

        expect(frames.length).toBeGreaterThan(0);
        expect(frames[0].offset).toBeGreaterThan(0);
        expect(frames[0].offset).toBeLessThanOrEqual(bottom);
        await expect(
            tab
                .locator(
                    '.adm-sidebar-nav nav .menu-dropdown-item-active, .adm-sidebar-nav nav .menu-item-active',
                )
                .last(),
        ).toBeInViewport();
    });
});
