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

/**
 * What the browser specs know about the demo application (tests/App).
 *
 * `playwright.config.js` imports BASE_URL from here so that the config and the helpers below can
 * never disagree about which server is being driven.
 */

/** Overridden when the browsers run in a container and the demo is served by the host. */
export const BASE_URL = process.env.ADMINATA_DEMO_URL ?? 'http://127.0.0.1:8000';

/** The demo's firewall is in-memory http_basic (tests/App/config/packages.yaml). */
export const CREDENTIALS = { username: 'admin', password: 'admin' };

/**
 * The pages every visual, accessibility and markup spec walks.
 *
 * One entry per screen 1.0 ships; M3 and M4 add the rest as they rewrite them. The name ends up
 * in a snapshot filename, so it may not change once a baseline is committed.
 *
 * @type {ReadonlyArray<{name: string, path: string}>}
 */
export const PAGES = [
    { name: 'dashboard', path: '/admin/dashboard' },
    { name: 'product-list', path: '/admin/tests/app/product/list' },
    // The combobox filter, with a value already chosen: that is what makes the filter panel and
    // the autocomplete widget visible without a click.
    {
        name: 'category-list-autocomplete',
        path: '/admin/tests/app/category/list?filter%5Bproducts%5D%5Bvalue%5D=1',
    },
    // A list with its batch column removed: no checkboxes, and a footer that has to survive it.
    { name: 'tag-list', path: '/admin/tests/app/tag/list' },
    { name: 'product-create', path: '/admin/tests/app/product/create' },
    { name: 'product-show', path: '/admin/tests/app/product/1/show' },
    { name: 'product-edit', path: '/admin/tests/app/product/1/edit' },
    { name: 'product-delete', path: '/admin/tests/app/product/3/delete' },
    { name: 'login', path: '/login' },
    { name: 'empty-layout', path: '/admin/demo/empty' },
    { name: 'dialog', path: '/admin/demo/dialog' },
];

/** @type {ReadonlyArray<'light' | 'dark'>} */
export const THEMES = ['light', 'dark'];

/**
 * Sets the theme the way a visitor does: ThemeRuntime reads this cookie and falls back to the
 * configured mode when it is absent or holds anything else.
 *
 * @param {import('@playwright/test').Page} page
 * @param {'light' | 'dark'} theme
 */
export async function useTheme(page, theme) {
    await page.context().addCookies([{ name: 'sonata_theme', value: theme, url: BASE_URL }]);
}

/**
 * Opens a demo page and waits for the point where a screenshot is meaningful: fonts resolved and
 * no request still in flight.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} path
 */
export async function open(page, path) {
    await page.goto(path, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
}
