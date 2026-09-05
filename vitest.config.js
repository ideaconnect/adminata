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

import { defineConfig } from 'vitest/config';

export default defineConfig({
    resolve: {
        // Stimulus ships a UMD build as `main` and an ES module as `module`. Under Vitest the UMD
        // one wins, and it reaches for the global `Node` before jsdom has installed it.
        mainFields: ['module', 'browser', 'main'],
    },
    test: {
        environment: 'jsdom',
        setupFiles: ['assets/js/__tests__/setup.js'],
        include: ['assets/js/**/*.test.js'],
        restoreMocks: true,
        // The suites mount the markup the demo actually renders, and the product list is a
        // quarter of a megabyte of it. Five seconds is Vitest's default and is enough here but
        // not on a shared CI runner, where the same file takes three times as long.
        testTimeout: 20_000,
    },
});
