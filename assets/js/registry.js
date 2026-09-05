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
import DismissController from './controllers/dismiss_controller.js';
import DropdownController from './controllers/dropdown_controller.js';
import EditController from './controllers/edit_controller.js';
import FilterController from './controllers/filter_controller.js';
import FilterListController from './controllers/filter_list_controller.js';
import LayoutController from './controllers/layout_controller.js';
import MenuController from './controllers/menu_controller.js';
import ModalController from './controllers/modal_controller.js';
import PerPageController from './controllers/per_page_controller.js';
import ReadmoreController from './controllers/readmore_controller.js';
import RevisionController from './controllers/revision_controller.js';
import StickyController from './controllers/sticky_controller.js';
import ThemeController from './controllers/theme_controller.js';

/**
 * Every controller is registered here by hand: no `stimulus-bridge`, no `require.context`, so the
 * built bundle contains exactly what this file names and `__contract__/controllers.json` can be
 * checked against it.
 *
 * The nine inherited from Sonata are below, and the new ones join them as M2 to M4 land.
 *
 * @type {Record<string, typeof import('@hotwired/stimulus').Controller>}
 */
export const controllers = {
    'sonata-collection': CollectionController,
    'sonata-confirm-exit': ConfirmExitController,
    'sonata-dismiss': DismissController,
    'sonata-dropdown': DropdownController,
    'sonata-edit': EditController,
    'sonata-filter': FilterController,
    'sonata-filter-list': FilterListController,
    'sonata-layout': LayoutController,
    'sonata-menu': MenuController,
    'sonata-modal': ModalController,
    'sonata-per-page': PerPageController,
    'sonata-readmore': ReadmoreController,
    'sonata-revision': RevisionController,
    'sonata-sticky': StickyController,
    'sonata-theme': ThemeController,
};

/**
 * @param {import('@hotwired/stimulus').Application} application
 */
export function register(application) {
    for (const [identifier, controller] of Object.entries(controllers)) {
        application.register(identifier, controller);
    }
}
