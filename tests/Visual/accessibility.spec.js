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

import { PAGES, THEMES, open, useTheme } from './support/demo.js';
import { assertKnownFindings } from './support/findings.js';

/**
 * WCAG 2.1 AA over every page, in both themes (PLAN/08 §8).
 *
 * Contrast is checked in both themes on purpose: a token that reads in light mode and vanishes in
 * dark is the failure this catches, and it cannot be found by looking at one of them.
 */
for (const { name, path } of PAGES) {
    for (const theme of THEMES) {
        test(`${name} has no WCAG 2.1 AA violations in ${theme} mode`, async ({ page }, testInfo) => {
            await useTheme(page, theme);
            await open(page, path);

            const { violations } = await new AxeBuilder({ page })
                .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
                .analyze();

            await testInfo.attach(`axe-${name}-${theme}.json`, {
                body: JSON.stringify(violations, null, 4),
                contentType: 'application/json',
            });

            // Keyed by width as well as theme: a table that only overflows at 375px, or a control
            // the layout only shows there, is a different page to axe.
            assertKnownFindings(
                expect,
                'axe',
                `${name}:${theme}@${testInfo.project.name.split('-').pop()}`,
                violations.map((violation) => violation.id),
            );
        });
    }
}
