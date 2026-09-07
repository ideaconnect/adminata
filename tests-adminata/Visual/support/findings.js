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

import { readFileSync } from 'node:fs';

/**
 * The accessibility and markup findings the *inherited* interface already has.
 *
 * adminata 1.0 ships Sonata's Bootstrap templates until M2 to M4 rewrite them, and those templates
 * do not meet PLAN/08 §8: the dashboard alone is missing `lang`, pins the viewport scale and has
 * unnamed buttons. Skipping the checks until then would mean noticing none of that until the very
 * end, so instead every finding is written down here and asserted exactly.
 *
 * That makes both directions fail. A new violation fails, which is the regression gate. And fixing
 * one *also* fails until it is struck from this file in the same commit, which is what keeps the
 * list honest and shrinking. `findings.json` is meant to end M4 as `{}`.
 */
const FINDINGS = JSON.parse(readFileSync(new URL('./findings.json', import.meta.url), 'utf8'));

/**
 * The findings recorded for one check, or an empty list.
 *
 * @param {'axe' | 'markup' | 'responsive' | 'hygiene-translations' | 'hygiene-unstyled' | 'hygiene-borders'} kind
 * @param {string} key
 * @returns {string[]}
 */
export function knownFindings(kind, key) {
    return FINDINGS[kind]?.[key] ?? [];
}

/**
 * @param {import('@playwright/test').Expect} expect
 * @param {'axe' | 'markup' | 'responsive' | 'hygiene-translations' | 'hygiene-unstyled' | 'hygiene-borders'} kind
 * @param {string} key `page`, `page:theme` (accessibility) or `page@project` (responsive)
 * @param {ReadonlyArray<string>} found rule identifiers this run reported
 */
export function assertKnownFindings(expect, kind, key, found) {
    const known = knownFindings(kind, key);
    const unique = [...new Set(found)].sort();

    expect(
        unique,
        `${kind} findings for "${key}" changed. Update tests-adminata/Visual/support/findings.json in the ` +
            'same commit: add nothing without a reason, and strike every entry a rewrite fixes ' +
            '(PLAN/08 §8).',
    ).toEqual([...known].sort());
}
