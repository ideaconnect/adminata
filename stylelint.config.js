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

/** @type {import('stylelint').Config} */
export default {
    extends: ['stylelint-config-standard'],
    plugins: ['stylelint-order'],
    rules: {
        // Tailwind v4's at-rules. `@apply` and friends are not part of the CSS standard Stylelint
        // knows, and `@source`/`@utility`/`@theme`/`@custom-variant` are v4 additions.
        'at-rule-no-unknown': [
            true,
            {
                ignoreAtRules: [
                    'apply',
                    'config',
                    'custom-variant',
                    'plugin',
                    'reference',
                    'source',
                    'tailwind',
                    'theme',
                    'utility',
                    'variant',
                ],
            },
        ],
        // Stylelint validates at-rule preludes against the CSS standard, where `@apply` has none.
        'at-rule-prelude-no-invalid': [
            true,
            { ignoreAtRules: ['apply', 'source', 'utility', 'variant', 'custom-variant'] },
        ],
        'import-notation': null,
        // Tailwind pairs a size token with its line height as `--text-*--line-height`.
        'custom-property-pattern': ['^[a-z][a-z0-9]*(-{1,2}[a-z0-9]+)*$'],
        'order/properties-alphabetical-order': null,
    },
    overrides: [
        {
            /*
             * The token block is TailAdmin's, copied verbatim so it can be diffed against the
             * upstream template when that is updated. Rewriting `rgba(16, 24, 40, 0.1)` as
             * `rgb(16 24 40 / 10%)` would be correct CSS and would destroy that.
             */
            files: ['assets/css/theme.css'],
            rules: {
                'alpha-value-notation': null,
                'color-function-alias-notation': null,
                'color-function-notation': null,
                'color-hex-length': null,
                'custom-property-empty-line-before': null,
                'custom-property-pattern': null,
                'value-keyword-case': null,
            },
        },
    ],
};
