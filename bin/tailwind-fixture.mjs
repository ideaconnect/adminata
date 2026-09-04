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
 * PLAN/04 §4 lists the Tailwind CSS v4 behaviour the stylesheet architecture assumes. Each is
 * asserted here against the pinned tailwindcss, so a version bump that changes one of them fails
 * loudly instead of silently dropping `.adm-*` classes or reordering the cascade.
 */

import { readFileSync } from 'node:fs';
import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

import { compile } from '@tailwindcss/node';

const fixture = fileURLToPath(new URL('../assets/css/__fixture__/fixture.css', import.meta.url));

/** The class names the fixture pretends to have found in a template. */
const candidates = [
    'adm-probe',
    'bg-probe-500',
    'dark:text-probe-500',
    'z-99999',
    'h-(--adm-control-h)',
    'max-sm:flex',
    'px-2',
];

const compiler = await compile(readFileSync(fixture, 'utf8'), {
    base: dirname(fixture),
    onDependency() {},
});

const css = compiler.build(candidates);

/** The rule body of a selector, with whitespace collapsed, or null. */
const rule = (selector) => {
    const escaped = selector.replaceAll(/[.:()\\[\]$*+?^{}|/-]/g, String.raw`\$&`);
    const match = css.match(new RegExp(`${escaped}\\s*\\{([^}]*)\\}`));

    return null === match ? null : match[1].replaceAll(/\s+/g, ' ').trim();
};

const position = (needle) => css.indexOf(needle);

const checks = [
    [
        'T1  @utility emits only what the sources use',
        () => null !== rule(String.raw`.adm-probe`) && null === rule(String.raw`.adm-unused-probe`),
    ],
    [
        'T3  @apply accepts an @utility name',
        () => (rule(String.raw`.probe-applied`) ?? '').includes('padding-inline'),
    ],
    [
        'T4  @source inline() expands a brace range',
        // None of these is a candidate: the safelist is the only reason they exist.
        () => [1, 2, 3].every((index) => null !== rule(String.raw`.col-span-` + index)),
    ],
    [
        'T5  @theme static emits the variable and utilities reference it',
        () =>
            css.includes('--color-probe-500:') &&
            (rule(String.raw`.bg-probe-500`) ?? '').includes('var(--color-probe-500)'),
    ],
    [
        'T6  the dark variant matches the .dark element itself',
        () => css.includes(':where(.dark, .dark *)') || css.includes(':where(.dark,.dark *)'),
    ],
    [
        'T7  a layer declared after the import sorts after utilities',
        () => position('@layer sonata-overrides') > position('@layer utilities'),
    ],
    ['T9a bare z-99999', () => (rule(String.raw`.z-99999`) ?? '').includes('z-index: 99999')],
    [
        'T9b h-(--custom-property)',
        () => (rule(String.raw`.h-\(--adm-control-h\)`) ?? '').includes('var(--adm-control-h)'),
    ],
    ['T9c the max-sm: variant', () => css.includes(String.raw`.max-sm\:flex`)],
    [
        'T10 a multi-property @utility sorts before a single-property core utility',
        () => position(String.raw`.adm-probe`) < position(String.raw`.px-2`),
    ],
    [
        'T11 preflight keeps [hidden]{display:none!important}',
        () => /\[hidden\][^{]*\{[^}]*display:\s*none\s*!important/.test(css),
    ],
];

let failed = 0;

for (const [name, assertion] of checks) {
    let held;

    try {
        held = assertion();
    } catch (error) {
        held = false;
        console.error(error);
    }

    if (held) {
        console.log(`PASS ${name}`);
    } else {
        failed += 1;
        console.error(`FAIL ${name}`);
    }
}

if (failed > 0) {
    console.error(
        `\n${failed} of ${checks.length} Tailwind assumptions no longer hold; PLAN/04 §4 needs revisiting.`,
    );
    process.exit(1);
}

console.log(
    `\nAll ${checks.length} Tailwind assumptions hold with tailwindcss ${JSON.parse(readFileSync(new URL('../node_modules/tailwindcss/package.json', import.meta.url), 'utf8')).version}.`,
);
