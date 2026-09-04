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

/**
 * Every controller is registered here by hand: no `stimulus-bridge`, no `require.context`, so the
 * built bundle contains exactly what this file names and `__contract__/controllers.json` can be
 * checked against it.
 *
 * The nine inherited controllers arrive with P1-06, the eight new ones with M2 to M4.
 *
 * @param {import('@hotwired/stimulus').Application} application
 */
export function register(application) {
    for (const [identifier, controller] of Object.entries(controllers)) {
        application.register(identifier, controller);
    }
}

/** @type {Record<string, typeof import('@hotwired/stimulus').Controller>} */
const controllers = {};
