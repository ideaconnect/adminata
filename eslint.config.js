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

import js from '@eslint/js';
import prettier from 'eslint-config-prettier';
import globals from 'globals';

export default [
    {
        ignores: ['node_modules/**', 'packages/**', 'vendor/**', 'var/**', 'build/**', 'PLAN/**'],
    },
    js.configs.recommended,
    prettier,
    {
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                __ADMINATA_VERSION__: 'readonly',
            },
        },
        rules: {
            // Owner directive 2. jQuery is not a dependency and nothing may reach for it, whether
            // through an import or through a global another script happens to have defined.
            'no-restricted-globals': [
                'error',
                { name: '$', message: 'adminata has no jQuery (owner directive 2).' },
                { name: 'jQuery', message: 'adminata has no jQuery (owner directive 2).' },
                { name: 'Alpine', message: 'adminata uses Stimulus, not Alpine (PLAN/01 J1).' },
            ],
            'no-restricted-imports': [
                'error',
                {
                    paths: [
                        { name: 'jquery', message: 'adminata has no jQuery (owner directive 2).' },
                        { name: 'jquery-ui', message: 'adminata has no jQuery (owner directive 2).' },
                    ],
                },
            ],
            eqeqeq: ['error', 'always'],
            'no-console': ['error', { allow: ['warn', 'error'] }],
            'no-var': 'error',
            'prefer-const': 'error',
        },
    },
    {
        // The build scripts run under Node and print to stdout on purpose.
        files: ['bin/**/*.mjs', '*.config.js', '*.config.mjs'],
        languageOptions: {
            globals: globals.nodeBuiltin,
        },
        rules: {
            'no-console': 'off',
        },
    },
    {
        files: ['**/*.test.js'],
        languageOptions: {
            globals: { ...globals.browser, ...globals.node },
        },
    },
];
