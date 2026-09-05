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
 * No dark-mode rule may sit in `@layer components` while the class it overrides is an `@utility`.
 *
 * Cascade layers beat specificity: everything Tailwind emits for `@utility x` lands in the
 * `utilities` layer, which comes after `components`, so `.dark .x { … }` written in `@layer
 * components` never applies however specific it looks. It compiles, it lints, and it silently does
 * nothing — the header stayed white in dark mode for a whole milestone because of it.
 *
 * The fix is always the same: move the declarations inside the utility as `@variant dark { … }`,
 * which keeps them in the same layer as what they override.
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

    for (const [, selectors] of layer.matchAll(/^[ \t]*((?:\.dark[^{};]*?))\{/gm)) {
        for (const selector of selectors.split(',').map((s) => s.trim())) {
            const match = /^\.dark\s+\.([A-Za-z0-9_-]+)$/.exec(selector);

            if (match !== null && utilities.has(match[1])) {
                problems.push(`${file}: "${selector}" is shadowed by "@utility ${match[1]}"`);
            }
        }
    }
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
    console.error('Dark-mode rules that the utilities layer overrides:\n');
    for (const problem of problems) {
        console.error(`  ${problem}`);
    }
    console.error('\nMove each one inside its utility as `@variant dark { … }`.');
    process.exit(1);
}

console.log(`No shadowed dark-mode rules in ${files.length} stylesheets.`);
