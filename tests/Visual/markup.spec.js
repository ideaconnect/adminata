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
import { validate } from './support/html.js';

/**
 * html-validate over the DOM the browser actually built (PLAN/08 §3).
 *
 * Against the rendered DOM rather than the Twig source, because that is where a tag Twig closed in
 * one branch and left open in another shows up, and because the browser has already normalised
 * everything a parser would forgive.
 */
for (const { name, path } of PAGES) {
    test(`${name} is valid HTML`, async ({ page }, testInfo) => {
        await open(page, path);

        const { rules, results } = await validate(await page.content(), name);

        await testInfo.attach(`html-validate-${name}.json`, {
            body: JSON.stringify(results, null, 4),
            contentType: 'application/json',
        });

        assertKnownFindings(expect, 'markup', name, rules);
    });
}
