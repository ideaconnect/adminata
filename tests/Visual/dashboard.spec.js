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

import { PAGES, THEMES, open, useTheme } from './support/demo.js';

/**
 * A pixel baseline per page, viewport and theme (PLAN/08 §3).
 *
 * The baselines committed under `__snapshots__` are the *inherited* Bootstrap interface: M2 to M4
 * replace every one of them, and a rewrite is expected to fail this suite until its baseline is
 * regenerated in the same commit. That is the point — an unreviewed pixel change is the thing this
 * suite exists to catch.
 */
for (const { name, path } of PAGES) {
    test.describe(name, () => {
        for (const theme of THEMES) {
            test(`renders in ${theme} mode`, async ({ page }) => {
                await useTheme(page, theme);
                await open(page, path);

                await expect(page).toHaveScreenshot(`${name}-${theme}.png`, { fullPage: true });
            });
        }
    });
}
