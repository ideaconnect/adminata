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

import { defineConfig, devices } from '@playwright/test';

import { BASE_URL, CREDENTIALS } from './tests-adminata/Visual/support/demo.js';

/**
 * Visual regression, accessibility and markup checks against the demo application (PLAN/08 §3).
 *
 * Screenshots are pixels, so they are only comparable when the browser, the fonts and the
 * compositor are the same. `make test-visual` therefore runs this inside
 * `mcr.microsoft.com/playwright:v1.63.0-noble`, the image the `visual` workflow uses, with the
 * demo served from the host — the committed baselines under `tests-adminata/Visual/__snapshots__` come
 * from that image and nowhere else. Running `npx playwright test` on the host works and is useful
 * while writing a spec, but its screenshots will not match.
 */

/**
 * The three widths of PLAN/08: phone, tablet, desktop. Exported because `responsive.spec.js` and
 * `bin/build-visual-findings.mjs` check the same three.
 */
export const VIEWPORTS = {
    narrow: { width: 375, height: 812 },
    medium: { width: 768, height: 1024 },
    wide: { width: 1280, height: 900 },
};

const BROWSERS = {
    chromium: devices['Desktop Chrome'],
    firefox: devices['Desktop Firefox'],
    webkit: devices['Desktop Safari'],
};

/**
 * One project per browser and viewport. The theme is not a project: a spec sets the
 * `adminata_theme` cookie itself, so that a single run can compare light against dark.
 *
 * Screenshots are Chromium-only. PLAN/08 §3 asks for a baseline per page, viewport and theme —
 * eighteen images — and capturing the same eighteen in three engines would triple what the
 * repository carries, and triple it again on every rewrite from M2 to M4, to catch differences
 * that are antialiasing far more often than they are bugs. The accessibility and markup suites do
 * run in all three, which is where an engine actually disagrees about the DOM.
 */
const projects = Object.entries(BROWSERS).flatMap(([browser, device]) =>
    Object.entries(VIEWPORTS).map(([size, viewport]) => ({
        name: `${browser}-${size}`,
        use: { ...device, viewport },
        testIgnore: 'chromium' === browser ? undefined : '**/dashboard.spec.js',
    })),
);

export default defineConfig({
    testDir: './tests-adminata/Visual',
    snapshotDir: './tests-adminata/Visual/__snapshots__',
    outputDir: './tests-adminata/Visual/test-results',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: 0,
    workers: process.env.CI ? 2 : undefined,
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : [['list']],
    expect: {
        toHaveScreenshot: {
            // A hair of tolerance for text antialiasing, which differs between the three engines
            // even inside one image. Anything larger than a few hundred pixels is a real change.
            maxDiffPixelRatio: 0.002,
            animations: 'disabled',
            caret: 'hide',
            scale: 'css',
        },
    },
    use: {
        baseURL: BASE_URL,
        httpCredentials: CREDENTIALS,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        timezoneId: 'UTC',
        locale: 'en-US',
    },
    projects,
    webServer: {
        command: 'php -S 127.0.0.1:8000 -t tests-adminata/App/public',
        // 401 counts as ready: every page behind /admin is authenticated.
        url: `${BASE_URL}/admin/dashboard`,
        reuseExistingServer: true,
        timeout: 60_000,
    },
});
