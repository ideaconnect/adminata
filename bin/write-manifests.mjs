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
 * Writes the two manifests Symfony's asset component understands, and removes the stub chunks Vite
 * emits for CSS-only entries. The output names are fixed rather than hashed, so an application's
 * `sonata_admin.assets.remove_stylesheets` entries stay valid across releases.
 */

import { readdirSync, rmSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const published = fileURLToPath(new URL('../packages/admin-bundle/src/Resources/public', import.meta.url));

for (const name of readdirSync(published)) {
    if (name.startsWith('.vite-css-')) {
        rmSync(join(published, name), { force: true });
    }
}

const entrypoints = {
    entrypoints: {
        app: {
            js: ['/bundles/sonataadmin/app.js'],
            css: ['/bundles/sonataadmin/app.css'],
        },
        fontawesome: {
            css: ['/bundles/sonataadmin/fontawesome.css'],
        },
    },
};

const manifest = {
    'build/app.js': '/bundles/sonataadmin/app.js',
    'build/app.css': '/bundles/sonataadmin/app.css',
    'build/fontawesome.css': '/bundles/sonataadmin/fontawesome.css',
};

const json = (value) => `${JSON.stringify(value, null, 2)}\n`;

writeFileSync(join(published, 'entrypoints.json'), json(entrypoints));
writeFileSync(join(published, 'manifest.json'), json(manifest));
