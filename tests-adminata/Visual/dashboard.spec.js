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
import { knownFindings } from './support/findings.js';

/**
 * A pixel baseline per page, viewport and theme (PLAN/08 §3).
 *
 * The viewport, not the whole page. A full-page shot is sized from `scrollWidth`/`scrollHeight`,
 * and those move by a pixel or two between runs whenever a page overflows horizontally — which the
 * inherited list does at 375px. Different dimensions are a hard failure no tolerance can absorb, so
 * the baseline would flake for a reason that has nothing to do with what changed. `responsive.spec`
 * checks the overflow itself, which is the part that actually matters.
 *
 * The baselines committed under `__snapshots__` are the *inherited* Bootstrap interface: M2 to M4
 * replace every one of them, and a rewrite is expected to fail this suite until its baseline is
 * regenerated in the same commit. That is the point — an unreviewed pixel change is the thing this
 * suite exists to catch.
 */
for (const { name, path } of PAGES) {
    test.describe(name, () => {
        for (const theme of THEMES) {
            test(`renders in ${theme} mode`, async ({ page }, testInfo) => {
                // A page that still scrolls sideways cannot have a stable baseline: the slice the
                // viewport shows is measured differently on every run. `responsive.spec` holds that
                // debt, and striking an entry from it is what brings the baseline back.
                test.skip(
                    knownFindings('responsive', `${name}@${testInfo.project.name}`).includes(
                        'horizontal-overflow',
                    ),
                    'The page still scrolls sideways at this width — see tests-adminata/Visual/support/findings.json.',
                );

                await useTheme(page, theme);
                await open(page, path);

                await expect(page).toHaveScreenshot(`${name}-${theme}.png`);
            });
        }
    });
}
