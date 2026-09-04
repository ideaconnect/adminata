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
 * Owner directive 2: jQuery is not part of adminata and nothing adminata depends on may pull it
 * in. `npm ls jquery` reports transitive dependencies too, which is the half a grep cannot see.
 */

import { execFileSync } from 'node:child_process';

let output = '';

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

console.log('No package depends on jQuery.');
