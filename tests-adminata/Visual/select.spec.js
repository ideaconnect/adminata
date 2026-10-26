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

import { THEMES, open, useTheme } from './support/demo.js';

/*
 * A multiple select shows what is chosen while it has no focus, in both themes
 * (recomaty-panel#663).
 *
 * The product list's Tags filter with one tag chosen: a native `<select multiple>`
 * (`adm-select adm-select-multiple`) with "New" selected and the other three not, beside the
 * filter's single select of operators. The dark theme paints every option of an `adm-select`
 * gray-900, and in Chromium and Firefox that rule used to cover the colour the browser gives the
 * selected options of a list box without focus as well, so "New" looked like the rest until the
 * box was clicked. What colour a browser picks for a selection is its own business; what is
 * tested is that a selected option never looks like an unselected one, and that the options
 * adminata paints keep their gray-900.
 *
 * The colours are computed styles, so one viewport per engine is enough. WebKit is left out: it
 * paints a list box's selection itself, over whatever the CSS says, so its computed style never
 * shows the selection, and the bug never reached it.
 */

const PATH = '/admin/tests/app/product/list?filter%5Btags%5D%5Bvalue%5D%5B%5D=1';

/** The background of every option of a select, as the browser computes it. */
async function optionBackgrounds(page, selector) {
    return page.locator(selector).evaluate((select) =>
        Array.from(select.options, (option) => ({
            label: option.textContent.trim(),
            selected: option.selected,
            background: getComputedStyle(option).backgroundColor,
        })),
    );
}

/** `--color-gray-900` as the browser computes a background of it. */
async function gray900(page) {
    return page.evaluate(() => {
        const probe = document.createElement('div');
        probe.style.backgroundColor = 'var(--color-gray-900)';
        document.body.append(probe);
        const colour = getComputedStyle(probe).backgroundColor;
        probe.remove();

        return colour;
    });
}

test.describe('select', () => {
    test.beforeEach(async ({ browserName }, testInfo) => {
        test.skip(!testInfo.project.name.endsWith('-wide'), 'Computed colours: one viewport per engine.');
        test.skip('webkit' === browserName, 'WebKit paints the selection of a list box over its CSS.');
    });

    for (const theme of THEMES) {
        test(`a multiple select shows its selection without focus in ${theme} mode`, async ({ page }) => {
            await useTheme(page, theme);
            await open(page, PATH);

            const select = page.locator('#filter_tags_value');
            await expect(select).toBeVisible();
            await expect(select).not.toBeFocused();

            const options = await optionBackgrounds(page, '#filter_tags_value');
            const selected = options.filter((option) => option.selected);
            const unselected = options.filter((option) => !option.selected);

            expect(selected.map((option) => option.label)).toEqual(['New']);
            expect(unselected).toHaveLength(3);

            for (const option of unselected) {
                expect(
                    option.background,
                    `"${selected[0].label}" (selected) and "${option.label}" (not) have the same background`,
                ).not.toBe(selected[0].background);
            }

            if ('dark' === theme) {
                const paint = await gray900(page);

                // adminata's own paint stays on what is not selected…
                for (const option of unselected) {
                    expect(option.background, `"${option.label}" lost adminata's gray-900`).toBe(paint);
                }

                // …and on every option of a single select, the selected one included.
                for (const option of await optionBackgrounds(page, '#filter_tags_type')) {
                    expect(option.background, `the operator "${option.label}" lost adminata's gray-900`).toBe(
                        paint,
                    );
                }
            }
        });
    }
});
