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
import { fileURLToPath } from 'node:url';

import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

const root = fileURLToPath(new URL('.', import.meta.url));
const outDir = 'packages/admin-bundle/src/Resources/public';
const version = JSON.parse(readFileSync(new URL('package.json', import.meta.url), 'utf8')).version;

/*
 * Two passes over one configuration, because the JavaScript is an IIFE — a format Rollup only
 * emits for a single entry — while the stylesheets are two separate files an application can
 * remove independently. `npm run build` runs both and neither empties the output directory:
 * bin/clean-assets.mjs removes exactly the generated files, so the committed images survive.
 */
export default defineConfig(({ mode }) => ({
    root,
    // Relative URLs: the bundle is published under /bundles/sonataadmin/, and an application may
    // publish it somewhere else again, so `url(./fonts/…)` is the only form that always resolves.
    base: './',
    define: {
        __ADMINATA_VERSION__: JSON.stringify(version),
    },
    plugins: 'css' === mode ? [tailwindcss()] : [],
    build: {
        outDir,
        emptyOutDir: false,
        manifest: false,
        sourcemap: false,
        cssMinify: true,
        rollupOptions:
            'css' === mode
                ? {
                      input: {
                          app: 'assets/css/app.css',
                          fontawesome: 'assets/css/fontawesome.css',
                      },
                      output: {
                          assetFileNames: (asset) =>
                              /\.(woff2?|ttf|eot)$/.test(asset.names?.[0] ?? '')
                                  ? 'fonts/[name][extname]'
                                  : '[name][extname]',
                          // A CSS-only entry still produces an (empty) chunk; keep it out of the
                          // published directory by giving it a name bin/clean-assets.mjs knows.
                          entryFileNames: '.vite-css-[name].js',
                      },
                  }
                : {
                      input: 'assets/js/app.js',
                      output: {
                          format: 'iife',
                          entryFileNames: 'app.js',
                          assetFileNames: '[name][extname]',
                          inlineDynamicImports: true,
                      },
                  },
    },
}));
