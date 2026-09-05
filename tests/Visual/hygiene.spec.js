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
import { assertKnownFindings } from './support/findings.js';

/**
 * The defects the other suites are blind to.
 *
 * Nineteen were found in this project's acceptance phase by a person *looking at the panel*, and
 * every one of them had passed every automated gate: axe was clean, the HTML validated, nothing
 * scrolled sideways, and the screenshots matched because the baselines had been taken with the
 * defect already in them. What they had in common is that they were not violations of a standard —
 * they were a class in the markup that no stylesheet had a rule for, a translation key rendered as
 * itself, or two borders drawn a pixel apart.
 *
 * Each check below is one of those, generalised, and each is written so a *new* one fails rather
 * than requiring the whole inherited interface to be clean first — the same bargain
 * `findings.json` makes for the accessibility debt.
 */
for (const { name, path } of PAGES) {
    for (const theme of THEMES) {
        /*
         * Symfony renders the key itself when a catalogue is missing one, so a missing translation
         * is not an error anywhere — it is a page that reads `pager_navigation`. This found
         * fourteen of adminata's own keys shipping English-only, four of them `aria-label`s, which
         * meant a screen reader in any other locale announced the identifier.
         */
        test(`${name} renders no translation keys in ${theme} mode`, async ({ page }) => {
            await useTheme(page, theme);
            await open(page, path);

            const keys = await page.evaluate(() => {
                const found = new Set();
                const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
                // A key is snake_case and nothing else: at least two words, no spaces, no capitals.
                const looksLikeAKey = /^[a-z][a-z0-9]*(_[a-z0-9]+)+$/;

                for (let node = walker.nextNode(); node !== null; node = walker.nextNode()) {
                    const text = node.textContent.trim();

                    if (looksLikeAKey.test(text)) {
                        found.add(text);
                    }
                }

                // `aria-label` and `title` reach a screen reader without ever being a text node.
                for (const element of document.querySelectorAll('[aria-label], [title]')) {
                    for (const value of [element.getAttribute('aria-label'), element.getAttribute('title')]) {
                        if (value !== null && looksLikeAKey.test(value.trim())) {
                            found.add(value.trim());
                        }
                    }
                }

                return [...found];
            });

            assertKnownFindings(expect, 'hygiene-translations', `${name}:${theme}`, keys);
        });

        /*
         * A class the markup uses and no stylesheet has a rule for. That is either a component
         * whose CSS was lost — a status column that went blank when a stylesheet was trimmed — or
         * markup that was never ported, like thirteen row-action templates still carrying an
         * AdminLTE recipe that had not existed for months. Both render as "nothing happens", which
         * is exactly what no other check notices.
         *
         * Sonata's own hooks are deliberately unstyled and are the bulk of the captured list; the
         * ledger is what separates them from a regression.
         */
        test(`${name} uses no unstyled classes in ${theme} mode`, async ({ page }) => {
            await useTheme(page, theme);
            await open(page, path);

            const unstyled = await page.evaluate(() => {
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
            });

            assertKnownFindings(expect, 'hygiene-unstyled', `${name}:${theme}`, unstyled);
        });

        /*
         * Two borders drawn a pixel apart, in the same colour, read as one heavy line and give the
         * corner two radii that do not nest. It happened where a bordered box was put inside a
         * card that already had a border — valid CSS, doing exactly what it said.
         */
        test(`${name} draws no doubled borders in ${theme} mode`, async ({ page }) => {
            await useTheme(page, theme);
            await open(page, path);

            const doubled = await page.evaluate(() => {
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

                    // The child's border box starts exactly where the parent's border ends: the
                    // two lines touch.
                    if (Math.abs(box.top - (parentBox.top + inset)) < 0.5 && box.height > 0) {
                        found.add(`${describe(parent)} > ${describe(element)}`);
                    }
                }

                return [...found].sort();
            });

            assertKnownFindings(expect, 'hygiene-borders', `${name}:${theme}`, doubled);
        });
    }
}
