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

/** @type {import('prettier').Config} */
export default {
    printWidth: 110,
    singleQuote: true,
    tabWidth: 4,
    trailingComma: 'all',
    plugins: ['prettier-plugin-tailwindcss'],
    overrides: [
        {
            files: ['*.json', '*.yaml', '*.yml'],
            options: { tabWidth: 4 },
        },
    ],
};
