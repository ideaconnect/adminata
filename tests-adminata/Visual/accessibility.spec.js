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

import { PAGES, THEMES, open, openRailPopup, useTheme } from './support/demo.js';
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

/*
 * The collapsed rail with a group's popup open, in both themes: the rail's buttons keep a name with
 * their labels out of sight, and the popup's title and links have to read on its own ground.
 */
for (const theme of THEMES) {
    test(`the rail's popup has no WCAG 2.1 AA violations in ${theme} mode`, async ({ page }, testInfo) => {
        test.skip(!testInfo.project.name.endsWith('-wide'), 'The rail exists from 1024px up.');

        await useTheme(page, theme);
        await openRailPopup(page);

        const { violations } = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
            .analyze();

        await testInfo.attach(`axe-rail-popup-${theme}.json`, {
            body: JSON.stringify(violations, null, 4),
            contentType: 'application/json',
        });

        assertKnownFindings(
            expect,
            'axe',
            `rail-popup:${theme}@wide`,
            violations.map((violation) => violation.id),
        );
    });
}
