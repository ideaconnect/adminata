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
 * The identifier column is six characters wide and widens only for an identifier that needs more
 * (recomaty-panel#677).
 *
 * Left to the automatic table layout, the id column took its share of the surplus like every other
 * column: on the category list, five columns in a 1580px card, the column of a one-digit id was
 * 176px wide. `components/list.css` gives the cells `base_list_field` marks
 * `adminata-list-field-id` a width and a minimum width of `6ch`, so the column is the width of
 * "000000" in the cell's font plus its padding, whatever else the list holds, and a longer
 * identifier widens it on one line.
 *
 * Layout, so each engine's wide project only, in all three: each one implements the automatic
 * table layout on its own.
 */

const CATEGORIES = '/admin/tests/app/category/list';
const PRODUCTS = '/admin/tests/app/product/list';

/**
 * The first id cell of the list: its content box, the width `6ch` has in its font, the width and
 * the number of lines of its text, and the table against the scrolling box it sits in.
 */
async function idColumn(page) {
    return page.evaluate(() => {
        const cell = document.querySelector('table.adminata-list tbody td.adminata-list-field-id');
        const style = getComputedStyle(cell);

        const probe = document.createElement('span');
        probe.style.cssText = 'position:absolute;display:inline-block;width:6ch;visibility:hidden';
        for (const property of ['font-family', 'font-size', 'font-weight', 'font-style', 'font-stretch']) {
            probe.style.setProperty(property, style.getPropertyValue(property));
        }
        document.body.append(probe);
        const sixCh = probe.getBoundingClientRect().width;
        probe.remove();

        const range = document.createRange();
        range.selectNodeContents(cell);
        const lines = new Set(
            Array.from(range.getClientRects())
                .filter((rect) => rect.width > 0)
                .map((rect) => Math.round(rect.top)),
        ).size;

        return {
            content:
                cell.getBoundingClientRect().width -
                parseFloat(style.paddingLeft) -
                parseFloat(style.paddingRight),
            sixCh,
            text: range.getBoundingClientRect().width,
            lines,
            table: cell.closest('table').getBoundingClientRect().width,
            box: cell.closest('.adm-table-scroll').clientWidth,
        };
    });
}

/** Puts `text` into every id cell, the way a list of longer identifiers renders them. */
async function writeIds(page, text) {
    await page.evaluate((value) => {
        for (const cell of document.querySelectorAll('table.adminata-list tbody td.adminata-list-field-id')) {
            cell.textContent = value;
        }
    }, text);
}

/** Layout, so one engine's wide project each. */
function wideOnly(testInfo) {
    test.skip(!testInfo.project.name.endsWith('-wide'), 'Layout: one viewport per engine.');
}

test.describe('list id column on a list narrower than its card', () => {
    // The card of a 1920px window, where the category list's one-digit id column was 176px wide;
    // at 1280px the share it got happened to be about six characters.
    test.use({ viewport: { width: 1920, height: 1080 } });

    test.beforeEach(async ({ page }, testInfo) => {
        wideOnly(testInfo);
        await open(page, CATEGORIES);
    });

    test('takes six characters and leaves the surplus to the other columns', async ({ page }) => {
        const column = await idColumn(page);

        // The card is wider than the list needs, and the table still fills it…
        expect(Math.abs(column.table - column.box), JSON.stringify(column)).toBeLessThan(1);
        // …but none of the surplus reached the id column: its content box is six characters.
        expect(Math.abs(column.content - column.sixCh), JSON.stringify(column)).toBeLessThan(1);
    });
});

test.describe('list id column with longer identifiers', () => {
    // At 1280px, where the category list's surplus no longer covers a UUID: left to the automatic
    // layout, Chromium and WebKit broke it at a hyphen.
    test.beforeEach(async ({ page }, testInfo) => {
        wideOnly(testInfo);
        await open(page, CATEGORIES);
    });

    for (const [kind, id] of [
        ['a ten-digit number', '1234567890'],
        ['a UUID', '0b8f6c2e-3d41-4a7b-9e5f-1c2d3e4f5a6b'],
    ]) {
        test(`widens on one line for ${kind}`, async ({ page }) => {
            await writeIds(page, id);

            const column = await idColumn(page);

            expect(column.lines, JSON.stringify(column)).toBe(1);
            expect(column.content, JSON.stringify(column)).toBeGreaterThan(column.sixCh);
            expect(column.content + 1, JSON.stringify(column)).toBeGreaterThanOrEqual(column.text);
        });
    }
});

test.describe('list id column on a list wider than its card', () => {
    test.beforeEach(async ({ page }, testInfo) => {
        wideOnly(testInfo);
        await open(page, PRODUCTS);
    });

    test('is marked on its header and on every cell, and nowhere else', async ({ page }) => {
        const header = page.locator('table.adminata-list thead th.adminata-list-field-header-id');
        await expect(header).toHaveCount(1);
        await expect(header).toHaveText('Id');
        await expect(page.locator('table.adminata-list .adminata-list-field-header-id')).toHaveCount(1);

        const rows = await page.locator('table.adminata-list tbody tr').count();
        const cells = page.locator('table.adminata-list tbody td.adminata-list-field-id');
        await expect(cells).toHaveCount(rows);
        // The `integer` column the admin maps `id` to, and no other.
        await expect(cells.and(page.locator('td.adminata-list-field-integer'))).toHaveCount(rows);
        await expect(page.locator('table.adminata-list .adminata-list-field-id')).toHaveCount(rows);
    });

    test('keeps six characters while every column is squeezed', async ({ page }) => {
        const column = await idColumn(page);

        // The columns do not fit, so each one is at its smallest; the id column's is six characters.
        expect(column.table, JSON.stringify(column)).toBeGreaterThan(column.box);
        expect(Math.abs(column.content - column.sixCh), JSON.stringify(column)).toBeLessThan(1);
    });
});
