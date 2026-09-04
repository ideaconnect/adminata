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
        'order/properties-alphabetical-order': null,
    },
};
