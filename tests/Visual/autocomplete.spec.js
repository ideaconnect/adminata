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

import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

import { THEMES, open, useTheme } from './support/demo.js';
import { assertKnownFindings } from './support/findings.js';
import { validate } from './support/html.js';

/**
 * The combobox with its listbox open (PLAN/06 §3).
 *
 * The page specs see the widget closed, which is the only state the server renders. Every ARIA
 * relationship that matters — `aria-expanded`, `aria-activedescendant`, the options themselves —
 * exists only once the controller has answered a search, so it gets its own pass.
 */
const PATH = '/admin/tests/app/category/list?filter%5Bproducts%5D%5Bvalue%5D=1';
const INPUT = '#filter_products_value_autocomplete_input';
const LISTBOX = '#filter_products_value_listbox';

/**
 * @param {import('@playwright/test').Page} page
 */
async function openTheListbox(page) {
    await open(page, PATH);
    await page.fill(INPUT, '');
    await page.type(INPUT, 'Product 0');
    await page.waitForSelector(`${LISTBOX} [role="option"]`);
}

for (const theme of THEMES) {
    test(`the open combobox has no WCAG 2.1 AA violations in ${theme} mode`, async ({ page }, testInfo) => {
        await useTheme(page, theme);
        await openTheListbox(page);

        const { violations } = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
            .analyze();

        await testInfo.attach(`axe-autocomplete-open-${theme}.json`, {
            body: JSON.stringify(violations, null, 4),
            contentType: 'application/json',
        });

        assertKnownFindings(
            expect,
            'axe',
            `autocomplete-open:${theme}@${testInfo.project.name.split('-').pop()}`,
            violations.map((violation) => violation.id),
        );
    });
}

test('the open combobox is valid HTML', async ({ page }) => {
    await openTheListbox(page);

    const { rules } = await validate(await page.content(), 'autocomplete-open');

    assertKnownFindings(expect, 'markup', 'autocomplete-open', rules);
});

test('the listbox carries the ARIA 1.2 combobox relationships', async ({ page }) => {
    await openTheListbox(page);

    const input = page.locator(INPUT);
    const options = page.locator(`${LISTBOX} [role="option"]`);

    await expect(input).toHaveAttribute('aria-expanded', 'true');
    await expect(input).toHaveAttribute('aria-controls', LISTBOX.slice(1));
    await expect(options).toHaveCount(6);

    await input.press('ArrowDown');

    const active = await input.getAttribute('aria-activedescendant');

    expect(active).toBeTruthy();
    await expect(page.locator(`#${active}`)).toHaveAttribute('aria-selected', 'true');
});
