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
 * No rule in `@layer components` may override a class that is an `@utility`.
 *
 * Cascade layers beat specificity: everything Tailwind emits for `@utility x` lands in the
 * `utilities` layer, which comes after `components`, so a rule in `@layer components` whose
 * subject is `.x` never applies however specific it looks. It compiles, it lints, and it silently
 * does nothing. The header stayed white in dark mode for a whole milestone that way, and the
 * sidebar's closed groups stayed open for an afternoon.
 *
 * The fix is always the same: move the declarations inside the utility — as `@variant dark { … }`,
 * as `@variant md { … }`, or nested with `&` — which keeps them in the same layer as what they
 * override.
 *
 * The subject is what matters, not the whole selector. `.adm-breadcrumb li + li::before` styles an
 * `li`, so `@layer components` is exactly where it belongs; `.dark .adm-header` and
 * `.menu-item[aria-expanded='false'] + .menu-dropdown` both style a utility, and do not.
 */

import { globSync, readFileSync } from 'node:fs';

const files = globSync('assets/css/**/*.css');
const problems = [];

for (const file of files) {
    const css = readFileSync(file, 'utf8');
    const utilities = new Set([...css.matchAll(/@utility\s+([A-Za-z0-9_-]+)\s*\{/g)].map((m) => m[1]));

    if (utilities.size === 0) {
        continue;
    }

    const layer = componentsLayer(css);

    if (layer === null) {
        continue;
    }

    for (const [, selectors] of layer.matchAll(/^[ \t]*([^{}@;]+?)\s*\{/gm)) {
        for (const selector of selectors.split(',').map((s) => s.trim())) {
            for (const utility of subjectClasses(selector)) {
                if (utilities.has(utility)) {
                    problems.push(`${file}: "${selector}" is shadowed by "@utility ${utility}"`);
                }
            }
        }
    }
}

/**
 * The classes on a selector's subject — its last compound, the element the rule actually styles.
 *
 * @param {string} selector
 * @returns {string[]}
 */
function subjectClasses(selector) {
    const subject =
        selector
            .split(/[\s>+~]+/)
            .filter(Boolean)
            .pop() ?? '';

    return [...subject.matchAll(/\.([A-Za-z0-9_-]+)/g)].map((match) => match[1]);
}

/**
 * The body of the file's `@layer components { … }` block, or null.
 *
 * @param {string} css
 * @returns {string | null}
 */
function componentsLayer(css) {
    const start = css.search(/@layer\s+components\s*\{/);

    if (start === -1) {
        return null;
    }

    const open = css.indexOf('{', start);
    let depth = 0;

    for (let i = open; i < css.length; i += 1) {
        if (css[i] === '{') {
            depth += 1;
        } else if (css[i] === '}') {
            depth -= 1;

            if (depth === 0) {
                return css.slice(open + 1, i);
            }
        }
    }

    return null;
}

if (problems.length > 0) {
    console.error('Rules in @layer components that the utilities layer overrides:\n');
    for (const problem of problems) {
        console.error(`  ${problem}`);
    }
    console.error('\nMove each one inside its utility, nested with `&` or under a `@variant`.');
    process.exit(1);
}

console.log(`No shadowed component rules in ${files.length} stylesheets.`);
