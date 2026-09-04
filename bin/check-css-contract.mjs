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

/*
 * The CSS contract of PLAN/04 §8. Two halves:
 *
 *  - every selector `assets/css/contract.json` names exists in the built stylesheet. That file is
 *    generated from the `.adm-*` component layer by P1-05; until then the list is empty.
 *  - the vocabulary adminata does not use is absent. This half already means something: the
 *    forked templates are being rewritten, and a Bootstrap or AdminLTE selector reappearing in
 *    the built CSS is the signal that one slipped back in.
 */

import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const built = fileURLToPath(
    new URL('../packages/admin-bundle/src/Resources/public/app.css', import.meta.url),
);
const contractFile = fileURLToPath(new URL('../assets/css/contract.json', import.meta.url));

if (!existsSync(built)) {
    console.error(`${built} does not exist; run "npm run build" first.`);
    process.exit(1);
}

const css = readFileSync(built, 'utf8');
const failures = [];

/*
 * The Bootstrap and AdminLTE vocabulary adminata replaced. Each is matched as a whole selector at a
 * selector boundary, so an arbitrary variant in a not-yet-ported template — `[&>.btn]:rounded-l-none`
 * compiles to `.\[\&\>\.btn\]\:rounded-l-none>.btn` — does not trip it.
 *
 * `container` and `collapse` are deliberately absent: they are Tailwind v4 utilities, so their
 * presence says nothing about Bootstrap (PLAN/04 §4).
 */
const forbidden = [
    /(^|[},])\s*\.btn\s*[,{]/,
    /(^|[},])\s*\.box\s*[,{]/,
    /(^|[},])\s*\.label\s*[,{]/,
    /(^|[},])\s*\.col-md-\d/,
    /fonts\.googleapis\.com/,
    /url\([^)]*\.(ttf|eot)[^)]*\)/,
];

for (const pattern of forbidden) {
    const match = css.match(pattern);

    if (null !== match) {
        failures.push(`forbidden selector or reference present: ${match[0].trim()}`);
    }
}

/** @type {{selectors?: string[]}} */
const contract = existsSync(contractFile) ? JSON.parse(readFileSync(contractFile, 'utf8')) : {};

for (const selector of contract.selectors ?? []) {
    if (!css.includes(selector)) {
        failures.push(`missing selector: ${selector}`);
    }
}

if (failures.length > 0) {
    console.error('CSS contract violated:');
    for (const failure of failures) {
        console.error(`  ${failure}`);
    }

    process.exit(1);
}

console.log(`CSS contract holds (${(contract.selectors ?? []).length} selectors checked).`);
