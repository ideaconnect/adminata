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

import { VIEWPORTS } from '../playwright.config.js';
import { BASE_URL, CREDENTIALS, PAGES, THEMES } from '../tests-adminata/Visual/support/demo.js';
import { validate } from '../tests-adminata/Visual/support/html.js';

const OUTPUT = new URL('../tests-adminata/Visual/support/findings.json', import.meta.url);

const browser = await chromium.launch();
const context = await browser.newContext({
    baseURL: BASE_URL,
    httpCredentials: CREDENTIALS,
    viewport: { width: 1280, height: 900 },
    timezoneId: 'UTC',
    locale: 'en-US',
});

const findings = {
    axe: {},
    markup: {},
    responsive: {},
    'hygiene-translations': {},
    'hygiene-unstyled': {},
    'hygiene-borders': {},
};

/*
 * The hygiene checks read the page rather than a standard, so their captures are made once per
 * page and theme at the widest viewport — none of the three depends on the width.
 */
const HYGIENE = {
    'hygiene-translations': () => {
        const found = new Set();
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        const looksLikeAKey = /^[a-z][a-z0-9]*(_[a-z0-9]+)+$/;

        for (let node = walker.nextNode(); node !== null; node = walker.nextNode()) {
            const text = node.textContent.trim();

            if (looksLikeAKey.test(text)) {
                found.add(text);
            }
        }

        for (const element of document.querySelectorAll('[aria-label], [title]')) {
            for (const value of [element.getAttribute('aria-label'), element.getAttribute('title')]) {
                if (value !== null && looksLikeAKey.test(value.trim())) {
                    found.add(value.trim());
                }
            }
        }

        return [...found].sort();
    },

    'hygiene-unstyled': () => {
        const styled = new Set();

        const collect = (rules) => {
            for (const rule of rules) {
                if (rule.cssRules) {
                    collect(rule.cssRules);
                }

                if (typeof rule.selectorText !== 'string') {
                    continue;
                }

                for (const [, name] of rule.selectorText.matchAll(/\.((?:\\.|[-\w])+)/g)) {
                    styled.add(name.replaceAll('\\', ''));
                }
            }
        };

        for (const sheet of document.styleSheets) {
            try {
                collect(sheet.cssRules);
            } catch {
                // A cross-origin sheet cannot be read; adminata serves none.
            }
        }

        const used = new Set();

        for (const element of document.querySelectorAll('[class]')) {
            for (const name of element.classList) {
                used.add(name);
            }
        }

        return [...used].filter((name) => !styled.has(name)).sort();
    },

    'hygiene-borders': () => {
        const describe = (element) =>
            `${element.tagName.toLowerCase()}.${[...element.classList].slice(0, 2).join('.')}`;
        const found = new Set();
        const visible = (style, side) =>
            parseFloat(style[`border${side}Width`]) > 0 &&
            style[`border${side}Style`] !== 'none' &&
            !/^rgba\(.*,\s*0\)$/.test(style[`border${side}Color`]);

        for (const element of document.querySelectorAll('*')) {
            const parent = element.parentElement;

            if (parent === null || parent === document.body) {
                continue;
            }

            const style = getComputedStyle(element);
            const parentStyle = getComputedStyle(parent);

            if (!visible(style, 'Top') || !visible(parentStyle, 'Top')) {
                continue;
            }

            if (style.borderTopColor !== parentStyle.borderTopColor) {
                continue;
            }

            const box = element.getBoundingClientRect();
            const parentBox = parent.getBoundingClientRect();
            const inset = parseFloat(parentStyle.borderTopWidth);

            if (Math.abs(box.top - (parentBox.top + inset)) < 0.5 && box.height > 0) {
                found.add(`${describe(parent)} > ${describe(element)}`);
            }
        }

        return [...found].sort();
    },
};

for (const [size, viewport] of Object.entries(VIEWPORTS)) {
    for (const { name, path } of PAGES) {
        for (const theme of THEMES) {
            await context.clearCookies();
            await context.addCookies([{ name: 'sonata_theme', value: theme, url: BASE_URL }]);

            const page = await context.newPage();
            await page.setViewportSize(viewport);
            await page.goto(path, { waitUntil: 'networkidle' });
            await page.evaluate(() => document.fonts.ready);

            const { violations } = await new AxeBuilder({ page })
                .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
                .analyze();

            const rules = [...new Set(violations.map((violation) => violation.id))].sort();
            if (rules.length > 0) {
                findings.axe[`${name}:${theme}@${size}`] = rules;
            }

            // None of the hygiene checks depends on the width, so they are captured at one.
            if (size === Object.keys(VIEWPORTS).at(-1)) {
                for (const [kind, check] of Object.entries(HYGIENE)) {
                    const entries = await page.evaluate(check);

                    if (entries.length > 0) {
                        findings[kind][`${name}:${theme}`] = entries;
                    }
                }
            }

            // The markup depends on neither the theme nor the width, so it is captured once.
            if (theme === THEMES[0] && size === Object.keys(VIEWPORTS)[0]) {
                const { rules: ids } = await validate(await page.content(), name);

                if (ids.length > 0) {
                    findings.markup[name] = ids;
                }
            }

            await page.close();
        }
    }
}

// The responsive check runs per viewport, and its keys are named after the Playwright projects
// (`page@browser-size`) because that is what `responsive.spec.js` asserts against.
for (const [size, viewport] of Object.entries(VIEWPORTS)) {
    const page = await context.newPage();
    await page.setViewportSize(viewport);

    for (const { name, path } of PAGES) {
        await page.goto(path, { waitUntil: 'networkidle' });
        await page.evaluate(() => document.fonts.ready);

        const { documentWidth, windowWidth } = await page.evaluate(() => ({
            documentWidth: document.documentElement.scrollWidth,
            windowWidth: window.innerWidth,
        }));

        if (documentWidth > windowWidth + 1) {
            for (const browser of ['chromium', 'firefox', 'webkit']) {
                findings.responsive[`${name}@${browser}-${size}`] = ['horizontal-overflow'];
            }
        }
    }

    await page.close();
}

await context.close();
await browser.close();

writeFileSync(OUTPUT, `${JSON.stringify(findings, null, 4)}\n`);

console.log(
    `Wrote ${Object.keys(findings.axe).length} accessibility, ${Object.keys(findings.markup).length} markup, ` +
        `${Object.keys(findings.responsive).length} responsive and ` +
        `${Object.keys(findings['hygiene-unstyled']).length + Object.keys(findings['hygiene-translations']).length + Object.keys(findings['hygiene-borders']).length} ` +
        'hygiene entries to tests/Visual/support/findings.json.',
);
