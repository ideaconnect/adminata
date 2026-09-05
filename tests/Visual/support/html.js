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

import { HtmlValidate } from 'html-validate';

/**
 * One validator, configured from the repository's `.htmlvalidate.json`.
 *
 * Built by hand rather than left to html-validate's own file resolution: `validateString()`
 * resolves configuration relative to the *filename it is given*, and these documents come from a
 * browser and have no file on disk, so nothing would be found.
 */
const config = JSON.parse(readFileSync(new URL('../../../.htmlvalidate.json', import.meta.url), 'utf8'));

export const validator = new HtmlValidate(config);

/**
 * The rules a document breaks, deduplicated and sorted.
 *
 * @param {string} html
 * @param {string} name
 * @returns {Promise<{rules: string[], results: unknown[]}>}
 */
export async function validate(html, name) {
    const report = await validator.validateString(html, `${name}.html`);
    const rules = report.results.flatMap((result) => result.messages.map((message) => message.ruleId));

    return { rules: [...new Set(rules)].sort(), results: report.results };
}
