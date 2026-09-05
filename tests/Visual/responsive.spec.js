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

import { PAGES, open } from './support/demo.js';
import { assertKnownFindings } from './support/findings.js';

/**
 * No page scrolls sideways (PLAN/08 §8).
 *
 * Wide content — tables, diagrams, code — scrolls inside its own container; the document does not.
 * This is the rule that made the full-page screenshots flake before they were narrowed to the
 * viewport: a document wider than the window is measured a pixel or two differently on every run.
 */
for (const { name, path } of PAGES) {
    test(`${name} does not scroll sideways`, async ({ page }, testInfo) => {
        await open(page, path);

        const { documentWidth, windowWidth } = await page.evaluate(() => ({
            documentWidth: document.documentElement.scrollWidth,
            windowWidth: window.innerWidth,
        }));

        assertKnownFindings(
            expect,
            'responsive',
            `${name}@${testInfo.project.name}`,
            // A pixel of slack: a scrollbar-less overlay scrollbar and sub-pixel text measurement
            // can each round the document one pixel past the window without anything overflowing.
            documentWidth > windowWidth + 1 ? ['horizontal-overflow'] : [],
        );
    });
}
