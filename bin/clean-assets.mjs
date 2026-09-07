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
 * Removes exactly the files the build generates. The published directory also holds images that
 * are committed by hand, so it is never emptied wholesale.
 */

import { readdirSync, rmSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const published = fileURLToPath(new URL('../src/Resources/public', import.meta.url));

const generated = ['app.js', 'app.css', 'fontawesome.css', 'entrypoints.json', 'manifest.json', 'fonts'];

for (const name of generated) {
    rmSync(join(published, name), { force: true, recursive: true });
}

// Vite writes a stub chunk for every CSS-only entry; they are named so they can be found again.
for (const name of readdirSync(published)) {
    if (name.startsWith('.vite-css-') && statSync(join(published, name)).isFile()) {
        rmSync(join(published, name), { force: true });
    }
}
