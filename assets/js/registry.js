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

import CollectionController from './controllers/collection_controller.js';
import ConfirmExitController from './controllers/confirm_exit_controller.js';
import EditController from './controllers/edit_controller.js';
import FilterController from './controllers/filter_controller.js';
import FilterListController from './controllers/filter_list_controller.js';
import PerPageController from './controllers/per_page_controller.js';
import ReadmoreController from './controllers/readmore_controller.js';
import RevisionController from './controllers/revision_controller.js';
import StickyController from './controllers/sticky_controller.js';

/**
 * Every controller is registered here by hand: no `stimulus-bridge`, no `require.context`, so the
 * built bundle contains exactly what this file names and `__contract__/controllers.json` can be
 * checked against it.
 *
 * The nine inherited from Sonata are below. The eight new ones arrive with milestones M2 to M4.
 *
 * @type {Record<string, typeof import('@hotwired/stimulus').Controller>}
 */
export const controllers = {
    'sonata-collection': CollectionController,
    'sonata-confirm-exit': ConfirmExitController,
    'sonata-edit': EditController,
    'sonata-filter': FilterController,
    'sonata-filter-list': FilterListController,
    'sonata-per-page': PerPageController,
    'sonata-readmore': ReadmoreController,
    'sonata-revision': RevisionController,
    'sonata-sticky': StickyController,
};

/**
 * @param {import('@hotwired/stimulus').Application} application
 */
export function register(application) {
    for (const [identifier, controller] of Object.entries(controllers)) {
        application.register(identifier, controller);
    }
}
