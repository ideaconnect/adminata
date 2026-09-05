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
 * Regenerates `tests/Visual/support/findings.json` — the accessibility and markup debt of the
 * inherited interface that `accessibility.spec.js` and `markup.spec.js` assert against.
 *
 * Run it through `make visual-findings`, which starts the demo and runs this in the same
 * container the specs use. Every entry it writes is a page that does not yet meet PLAN/08 §8; the
 * file is meant to shrink to `{}` as M2 to M4 rewrite the templates, so read a diff of it as the
 * report it is rather than accepting it blind.
 */

import { writeFileSync } from 'node:fs';

import AxeBuilder from '@axe-core/playwright';
import { chromium } from '@playwright/test';

import { BASE_URL, CREDENTIALS, PAGES, THEMES } from '../tests/Visual/support/demo.js';
import { validate } from '../tests/Visual/support/html.js';

const OUTPUT = new URL('../tests/Visual/support/findings.json', import.meta.url);

const browser = await chromium.launch();
const context = await browser.newContext({
    baseURL: BASE_URL,
    httpCredentials: CREDENTIALS,
    viewport: { width: 1280, height: 900 },
    timezoneId: 'UTC',
    locale: 'en-US',
});

const findings = { axe: {}, markup: {} };

for (const { name, path } of PAGES) {
    for (const theme of THEMES) {
        await context.clearCookies();
        await context.addCookies([{ name: 'sonata_theme', value: theme, url: BASE_URL }]);

        const page = await context.newPage();
        await page.goto(path, { waitUntil: 'networkidle' });
        await page.evaluate(() => document.fonts.ready);

        const { violations } = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
            .analyze();

        const rules = [...new Set(violations.map((violation) => violation.id))].sort();
        if (rules.length > 0) {
            findings.axe[`${name}:${theme}`] = rules;
        }

        // The markup does not depend on the theme, so it is captured once, from the light run.
        if (theme === THEMES[0]) {
            const { rules: ids } = await validate(await page.content(), name);

            if (ids.length > 0) {
                findings.markup[name] = ids;
            }
        }

        await page.close();
    }
}

await context.close();
await browser.close();

writeFileSync(OUTPUT, `${JSON.stringify(findings, null, 4)}\n`);

console.log(
    `Wrote ${Object.keys(findings.axe).length} accessibility and ${Object.keys(findings.markup).length} markup entries to tests/Visual/support/findings.json.`,
);
