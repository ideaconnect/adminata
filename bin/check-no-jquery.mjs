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
 * Owner directive 2: jQuery is not part of adminata, nothing adminata depends on may pull it in,
 * and no source of adminata's own may reach for it.
 *
 * Two halves, because neither sees the other's ground: `npm ls jquery` reports transitive
 * dependencies, which no grep can find, and the source scan below catches a `$(…)` written by hand
 * against a jQuery an application happens to load.
 *
 * Prose is exempt. Several controllers exist *because* jQuery was removed, and a comment saying so
 * is documentation — so comments are stripped before the scan rather than matched.
 */

import { execFileSync } from 'node:child_process';
import { readFileSync, readdirSync } from 'node:fs';
import { extname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

let output;

try {
    output = execFileSync('npm', ['ls', 'jquery', '--all', '--json'], { encoding: 'utf8' });
} catch (error) {
    // `npm ls` exits non-zero when the package is missing from the tree, which is what we want.
    output = error.stdout ?? '';
}

const tree = output.trim() ? JSON.parse(output) : {};
const found = [];

const walk = (node, path) => {
    for (const [name, dependency] of Object.entries(node.dependencies ?? {})) {
        if ('jquery' === name) {
            found.push([...path, `jquery@${dependency.version ?? '?'}`].join(' > '));
        }

        walk(dependency, [...path, name]);
    }
};

walk(tree, []);

if (found.length > 0) {
    console.error('jQuery must not be a dependency of adminata (owner directive 2). Found through:');
    for (const path of found) {
        console.error(`  ${path}`);
    }

    process.exit(1);
}

/** Every scannable file under a directory, skipping the Vitest suites. */
function sources(directory) {
    const files = [];

    for (const entry of readdirSync(directory, { withFileTypes: true })) {
        const path = join(directory, entry.name);

        if (entry.isDirectory()) {
            if ('__tests__' !== entry.name) {
                files.push(...sources(path));
            }

            continue;
        }

        if (['.js', '.mjs', '.css', '.json'].includes(extname(entry.name))) {
            files.push(path);
        }
    }

    return files;
}

/**
 * The file with its comments blanked out, line count preserved.
 *
 * A `//` inside a string literal ends the line early, which can only make the scan see less than
 * it should; the dependency check above and ESLint's `no-undef` are what stand behind it.
 */
function withoutComments(code) {
    return code
        .split('\n')
        .map((line) => {
            const trimmed = line.trim();

            if (trimmed.startsWith('*') || trimmed.startsWith('/*') || trimmed.startsWith('//')) {
                return '';
            }

            return line.split('//')[0];
        })
        .join('\n');
}

const assets = fileURLToPath(new URL('../assets', import.meta.url));
const offenders = [];

for (const file of sources(assets)) {
    const code = withoutComments(readFileSync(file, 'utf8'));

    code.split('\n').forEach((line, index) => {
        if (/jquery/i.test(line)) {
            offenders.push(`${file}:${index + 1}: ${line.trim()}`);
        }
    });
}

if (offenders.length > 0) {
    console.error("jQuery must not appear in adminata's own sources (owner directive 2):");
    for (const offender of offenders) {
        console.error(`  ${offender}`);
    }

    process.exit(1);
}

console.log('No package depends on jQuery, and no source names it.');
